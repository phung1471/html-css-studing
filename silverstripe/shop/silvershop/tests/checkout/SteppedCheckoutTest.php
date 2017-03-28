<?php

class SteppedCheckoutTest extends FunctionalTest
{
    protected static $fixture_file = array(
        'silvershop/tests/fixtures/Pages.yml',
        'silvershop/tests/fixtures/shop.yml',
    );
    protected static $use_draft_site = true; //so we don't need to publish
    protected $autoFollowRedirection = false;
    /** @var CheckoutPage_Controller */
    protected $checkout;
    /** @var Product */
    protected $socks;

    public function setUpOnce()
    {
        parent::setUpOnce();
        // clear session
        ShoppingCart::singleton()->clear();
    }

    public function setUp()
    {
        parent::setUp();
        ShopTest::setConfiguration();
        //set up steps
        SteppedCheckout::setupSteps(); //use default steps

        $this->socks = $this->objFromFixture("Product", "socks");
        $this->socks->publish('Stage', 'Live');

        $checkoutpage = $this->objFromFixture("CheckoutPage", "checkout");
        $checkoutpage->publish('Stage', 'Live');
        $this->checkout = new CheckoutPage_Controller();
        $this->checkout->handleRequest(new SS_HTTPRequest("GET", "checkout"), DataModel::inst());

        $this->cart = $this->objFromFixture("Order", "cart");
        ShoppingCart::singleton()->setCurrent($this->cart);
    }

    public function testTemplateFunctionsForFirstStep()
    {
        //put us at the first step index == membership
        $indexRequest = new SS_HTTPRequest('GET', "");
        $this->checkout = new CheckoutPage_Controller(); // from 3.3 on it's necessary to have a clean controller here
        $this->checkout->handleRequest($indexRequest, DataModel::inst());
        $this->assertTrue($this->checkout->StepExists('membership'));
        $this->assertFalse($this->checkout->IsPastStep('membership'));
        $this->assertTrue($this->checkout->IsCurrentStep('membership'));
        $this->assertFalse($this->checkout->IsFutureStep('membership'));

        $this->checkout->NextStepLink();

        $this->assertTrue($this->checkout->StepExists('contactdetails'));
        $this->assertFalse($this->checkout->IsPastStep('contactdetails'));
        $this->assertFalse($this->checkout->IsCurrentStep('contactdetails'));
        $this->assertTrue($this->checkout->IsFutureStep('contactdetails'));
    }

    public function testTemplateFunctionsForOtherSteps()
    {
        $summaryRequest = new SS_HTTPRequest('GET', "summary");
        $this->checkout = new CheckoutPage_Controller();
        $this->checkout->handleRequest($summaryRequest, DataModel::inst()); //change to summary step
        $this->assertTrue($this->checkout->StepExists('summary'));
        $this->assertFalse($this->checkout->IsPastStep('summary'));
        $this->assertTrue($this->checkout->IsCurrentStep('summary'));
        $this->assertFalse($this->checkout->IsFutureStep('summary'));

        $this->assertFalse($this->checkout->StepExists('nosuchstep'));
    }

    public function testMembershipStep()
    {
        //this should still work if there is no cart
        ShoppingCart::singleton()->clear();

        $this->checkout->index();
        $this->checkout->membership();
        $this->post('/checkout/guestcontinue', array()); //redirect to next step
        $this->checkout->createaccount(new SS_HTTPRequest('GET', "/checkout/createaccount"));

        $form = $this->checkout->MembershipForm();
        $data = array();
        $form->loadDataFrom($data);

        $data = array(
            'FirstName'              => 'Michael',
            'Surname'                => 'Black',
            'Email'                  => 'mb@example.com',
            'Password'               => array(
                '_Password'        => 'pass1234',
                '_ConfirmPassword' => 'pass1234',
            ),
            'action_docreateaccount' => 'Create New Account',
        );
        $response = $this->post('/checkout/CreateAccountForm', $data); //redirect to next step

        $member = ShopMember::get_by_identifier("mb@example.com");
        $this->assertTrue((boolean)$member, "Check new account was created");
        $this->assertEquals('Michael', $member->FirstName);
        $this->assertEquals('Black', $member->Surname);
    }

    public function testContactDetails()
    {
        $this->objFromFixture("Member", "joebloggs")->logIn();
        $this->checkout->contactdetails();
        $data = array(
            'FirstName'                => 'Pauline',
            'Surname'                  => 'Richardson',
            'Email'                    => 'p.richardson@example.com',
            'action_setcontactdetails' => 1,
        );
        $response = $this->post('/checkout/ContactDetailsForm', $data);

        $this->markTestIncomplete('check order has been updated');
    }

    public function testShippingAddress()
    {
        $this->objFromFixture("Member", "joebloggs")->logIn();
        $this->checkout->shippingaddress();
        $data = array(
            'Address'           => '2b Baba place',
            'AddressLine2'      => 'Level 2',
            'City'              => 'Newton',
            'State'             => 'Wellington',
            'Country'           => 'NZ',
            'action_setaddress' => 1,
        );
        $response = $this->post('/checkout/AddressForm', $data);

        $this->markTestIncomplete('assertions!');
    }

    public function testBillingAddress()
    {
        $this->objFromFixture("Member", "joebloggs")->logIn();
        $this->checkout->billingaddress();
        $data = array(
            'Address'                  => '3 Art Cresent',
            'AddressLine2'             => '',
            'City'                     => 'Walkworth',
            'State'                    => 'New Caliphoneya',
            'Country'                  => 'ZA',
            'action_setbillingaddress' => 1,
        );
        $response = $this->post('/checkout/AddressForm', $data);

        $this->markTestIncomplete('assertions!');
    }

    public function testPaymentMethod()
    {
        $data = array(
            'PaymentMethod'           => 'Dummy',
            'action_setpaymentmethod' => 1,
        );
        $response = $this->post('/checkout/PaymentMethodForm', $data);
        $this->assertEquals('Dummy', Checkout::get($this->cart)->getSelectedPaymentMethod());
    }

    public function testSummary()
    {
        $this->checkout->summary();
        $form = $this->checkout->ConfirmationForm();
        $data = array(
            'Notes'                  => 'Leave it around the back',
            'ReadTermsAndConditions' => 1,
        );
        $member = $this->objFromFixture("Member", "joebloggs");
        $member->logIn(); //log in member before processing

        Checkout::get($this->cart)->setPaymentMethod("Dummy"); //a selected payment method is required
        $form->loadDataFrom($data);
        $this->assertTrue($form->validate(), "Checkout data is valid");
        $response = $this->post('/checkout/ConfirmationForm', $data);
        $this->assertEquals('Cart', $this->cart->Status, "Order is still in cart");

        $order = Order::get()->byID($this->cart->ID);

        $this->assertEquals("Leave it around the back", $order->Notes);

        //redirect to make payment
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            Director::baseURL() . "checkout/payment",
            $response->getHeader('Location')
        );
    }
}
