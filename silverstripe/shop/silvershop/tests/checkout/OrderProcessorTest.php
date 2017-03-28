<?php

/**
 * Test OrderProcessor
 *
 * @package    silvershop
 * @subpackage tests
 */
class OrderProcessorTest extends SapphireTest
{
    protected static $fixture_file   = 'silvershop/tests/fixtures/shop.yml';
    protected static $disable_theme  = true;
    protected static $use_draft_site = true;
    protected $processor;
    protected $extraDataObjects = array('OrderProcessorTest_CustomOrderItem');

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

        $this->mp3player = $this->objFromFixture('Product', 'mp3player');
        $this->socks = $this->objFromFixture('Product', 'socks');
        $this->beachball = $this->objFromFixture('Product', 'beachball');
        $this->hdtv = $this->objFromFixture('Product', 'hdtv');

        $this->mp3player->publish('Stage', 'Live');
        $this->socks->publish('Stage', 'Live');
        $this->beachball->publish('Stage', 'Live');
        $this->hdtv->publish('Stage', 'Live');

        $this->shoppingcart = ShoppingCart::singleton();
    }

    public function testCreatePayment()
    {
        $order = $this->objFromFixture("Order", "unpaid");
        $processor = OrderProcessor::create($order);
        $payment = $processor->createPayment('Dummy');
        $this->assertTrue((boolean)$payment);
    }

    public function testPlaceOrder()
    {
        //place items in cart
        $this->shoppingcart->add($this->mp3player, 2);
        $this->shoppingcart->add($this->socks);

        $order = $this->shoppingcart->current();

        $factory = new ShopMemberFactory();
        $member = $factory->create(
            array(
                'FirstName' => 'James',
                'Surname'   => 'Brown',
                'Email'     => 'james@example.com',
                'Password'  => 'jbrown',
            )
        );
        $this->assertTrue((bool)$member);
        $member->write();

        $order->calculate();
        //submit checkout page
        $this->assertTrue(
            $this->placeOrder(
                'James',
                'Brown',
                'james@example.com',
                '23 Small Street',
                'North Beach',
                'Springfield',
                'Waikato',
                '1234567',
                'NZ',
                'jbrown',
                'jbrown',
                $member
            ),
            "Order placed sucessfully"
        );

        $order = Order::get()->byID($order->ID); //update order variable to db-stored version

        $this->assertFalse($this->shoppingcart->current(), "Shopping cart is empty");
        $this->assertNotNull($order);
        $this->assertEquals(408, $order->GrandTotal(), 'grand total');
        $this->assertEquals(408, $order->TotalOutstanding(), 'total outstanding');
        $this->assertEquals(0, $order->TotalPaid(), 'total outstanding');

        $this->assertEquals($order->Status, 'Unpaid', 'status is "unpaid"');

        $this->assertEquals(false, $order->IsSent());
        $this->assertEquals(false, $order->IsProcessing());
        $this->assertEquals(false, $order->IsPaid());
        $this->assertEquals(false, $order->IsCart());

        $this->assertEquals('James', $order->FirstName, 'order first name');
        $this->assertEquals('Brown', $order->Surname, 'order surname');
        $this->assertEquals('james@example.com', $order->Email, 'order email');

        $shippingaddress = $order->ShippingAddress();

        $this->assertEquals('23 Small Street', $shippingaddress->Address, 'order address');
        $this->assertEquals('North Beach', $shippingaddress->AddressLine2, 'order address2');
        $this->assertEquals('Springfield', $shippingaddress->City, 'order city');
        $this->assertEquals('1234567', $shippingaddress->PostalCode, 'order postcode');
        $this->assertEquals('NZ', $shippingaddress->Country, 'order country');

        $billingaddress = $order->BillingAddress();

        $this->assertEquals('23 Small Street', $billingaddress->Address, 'order address');
        $this->assertEquals('North Beach', $billingaddress->AddressLine2, 'order address2');
        $this->assertEquals('Springfield', $billingaddress->City, 'order city');
        $this->assertEquals('1234567', $billingaddress->PostalCode, 'order postcode');
        $this->assertEquals('NZ', $billingaddress->Country, 'order country');

        $this->assertTrue($order->Member()->exists(), 'member exists now');
        $this->assertEquals('James', $order->Member()->FirstName, 'member first name matches');
        $this->assertEquals('Brown', $order->Member()->Surname, 'surname matches');
        $this->assertEquals('james@example.com', $order->Member()->Email, 'email matches');
    }

    public function testPlaceFailure()
    {
        if (!ShopTools::DBConn()->supportsTransactions()) {
            $this->markTestSkipped(
                'The Database doesn\'t support transactions.'
            );
        }

        // Add the erroneous extension
        Order::add_extension('OrderProcessorTest_PlaceFailExtension');

        Config::inst()->update('Product', 'order_item', 'OrderProcessorTest_CustomOrderItem');

        //log out the admin user
        Member::currentUser()->logOut();
        $joemember = $this->objFromFixture('Member', 'joebloggs');
        $joemember->logIn();


        $this->shoppingcart->add($this->mp3player);
        $cart = ShoppingCart::curr();
        $cart->calculate();

        $this->assertDOSContains(array(
            array('ClassName' => 'OrderProcessorTest_CustomOrderItem')
        ), $cart->Items());

        $versions = Product_OrderItem::get()->filter('OrderID', $cart->ID)->column('ProductVersion');

        // The Product_OrderItem should not reference a product version while the order is not placed
        $this->assertEquals(array(0), $versions);

        $this->assertTrue($cart->has_extension('OrderProcessorTest_PlaceFailExtension'));

        // Placing the order will fail.
        $this->assertFalse(
            $this->placeOrder(
                'Joseph',
                'Blog',
                'joe@example.com',
                '100 Melrose Place',
                null,
                'Martinsonville',
                'New Mexico',
                null,
                'EG',
                'newpassword',
                'newpassword',
                $joemember
            ),
            "Member order placed successfully"
        );

        $order = Order::get()->byID($cart->ID); //update order variable to db-stored version

        $this->assertNotNull($this->shoppingcart->current(), "Shopping is still present");
        $this->assertNotNull($order);
        $this->assertNull($order->Placed);
        $this->assertEquals($order->Status, 'Cart', 'Status should still be "Cart"');

        // When order failed, everything that was written during the placement should be rolled back

        $versions = Product_OrderItem::get()->filter('OrderID', $cart->ID)->column('ProductVersion');

        // The Product_OrderItem should still not reference a product if the rollback worked
        $this->assertEquals(array(0), $versions);

        $this->assertEquals(
            0,
            OrderProcessorTest_CustomOrderItem::get()->filter('OrderID', $cart->ID)->first()->IsPlaced
        );

        Order::remove_extension('OrderProcessorTest_PlaceFailExtension');
        $this->shoppingcart->clear(false);
    }

    public function testMemberOrder()
    {
        //log out the admin user
        Member::currentUser()->logOut();
        $this->shoppingcart->add($this->mp3player);
        $joemember = $this->objFromFixture('Member', 'joebloggs');
        $joemember->logIn();
        $cart = ShoppingCart::curr();
        $cart->calculate();
        $this->assertTrue(
            $this->placeOrder(
                'Joseph',
                'Blog',
                'joe@example.com',
                '100 Melrose Place',
                null,
                'Martinsonville',
                'New Mexico',
                null,
                'EG',
                'newpassword',
                'newpassword',
                $joemember
            ),
            "Member order placed successfully"
        );

        $order = Order::get()->byID($cart->ID);
        $this->assertTrue((boolean)$order, 'Order exists');
        $this->assertEquals($order->Status, 'Unpaid', 'status is now "unpaid"');
        $this->assertEquals($order->FirstName, 'Joseph', 'order first name');
        $this->assertEquals($order->Surname, 'Blog', 'order surname');
        $this->assertEquals($order->Email, 'joe@example.com', 'order email');

        $shippingaddress = $order->ShippingAddress();

        $this->assertEquals($shippingaddress->Address, '100 Melrose Place', 'order address');
        $this->assertNull($shippingaddress->AddressLine2, 'order address2');
        $this->assertEquals($shippingaddress->City, 'Martinsonville', 'order city');
        $this->assertNull($shippingaddress->PostalCode, 'order postcode');
        $this->assertEquals($shippingaddress->Country, 'EG', 'order country');

        //ASSUMPTION: member details are NOT updated with order
        $this->assertEquals($order->MemberID, $joemember->ID, 'Order associated with member');
        $this->assertEquals($order->Member()->FirstName, 'Joe', 'member first name has not changed');
        $this->assertTrue(
            $order->Member()->inGroup($this->objFromFixture("Group", "customers"), true),
            "Member has been added to ShopMembers group"
        );
    }

    public function testNoMemberOrder()
    {
        //log out the admin user
        Member::currentUser()->logOut();

        $this->shoppingcart->add($this->socks);
        $order = $this->shoppingcart->current();
        $order->calculate();
        $success = $this->placeOrder(
            'Donald',
            'Duck',
            'donald@example.com',
            '4 The Strand',
            null,
            'Melbourne',
            'Victoria',
            null,
            'AU'
        );
        $error = $this->processor->getError();
        $this->assertTrue($success, "Non-member order placed successfully ...$error");

        $order = Order::get()->byID($order->ID); //update $order
        $this->assertTrue((boolean)$order, 'Order exists');
        $this->assertEquals($order->Status, 'Unpaid', 'status is now "unpaid"');
        $this->assertEquals($order->MemberID, 0, 'No associated member');
        $this->assertEquals($order->GrandTotal(), 8, 'grand total');
        $this->assertEquals($order->TotalOutstanding(), 8, 'total outstanding');
        $this->assertEquals($order->TotalPaid(), 0, 'total outstanding');
        $this->assertEquals($order->FirstName, 'Donald', 'order first name');
        $this->assertEquals($order->Surname, 'Duck', 'order surname');
        $this->assertEquals($order->Email, 'donald@example.com', 'order email');

        $shippingaddress = $order->ShippingAddress();

        $this->assertEquals($shippingaddress->Address, '4 The Strand');
        $this->assertNull($shippingaddress->AddressLine2, 'order address2');
        $this->assertEquals($shippingaddress->City, 'Melbourne', 'order city');
        $this->assertNull($shippingaddress->PostalCode, 'order postcode');
        $this->assertEquals($shippingaddress->Country, 'AU', 'order country');
    }

    public function testPlaceOrderMarksAsPaidWithNoOutstandingAmount()
    {
        Config::inst()->update('ShopConfig', 'email_from', 'shopadmin@example.com');

        // Create a new order
        $this->shoppingcart->add($this->socks);
        $order = $this->shoppingcart->current();
        $order->Email = 'receipt@example.com';
        $order->calculate();

        // Create a payment for the order
        $payment = Payment::create()->init('Dummy', 8, 'NZD');
        $payment->Status = 'Captured';
        $payment->OrderID = $order->ID;
        $payment->write();

        // Complete the payment with the order processor
        $processor = OrderProcessor::create($order);
        $processor->completePayment();

        // Order paid date and status should be updated
        $this->assertNotNull($order->Paid, 'Sets paid date');
        $this->assertEquals('Paid', $order->Status, 'Sets paid status');

        $subject = _t(
            'ShopEmail.ReceiptSubject',
            'Order #{OrderNo} receipt',
            '',
            array('OrderNo' => $order->Reference)
        );
        // Ensure receipt was sent
        $this->assertEmailSent('receipt@example.com', 'shopadmin@example.com', $subject);
    }

    /**
     * Helper function that populates a form with data and submits it.
     */
    protected function placeOrder(
        $firstname,
        $surname,
        $email,
        $address1,
        $address2 = null,
        $city = null,
        $state = null,
        $postcode = null,
        $country = null,
        $password = null,
        $confirmpassword = null,
        $member = null
    ) {
        $data = array(
            'FirstName' => $firstname,
            'Surname'   => $surname,
            'Email'     => $email,
            'Address'   => $address1,
            'City'      => $city,
            'State'     => $state,
        );
        if ($address2) {
            $data['AddressLine2'] = $address2;
        }
        if ($postcode) {
            $data['PostalCode'] = $postcode;
        }
        if ($country) {
            $data['Country'] = $country;
        }
        if ($password) {
            $data['Password[_Password]'] = $password;
        }
        if ($confirmpassword) {
            $data['Password[_ConfirmPassword]'] = $confirmpassword;
        }

        $order = $this->shoppingcart->current();
        $order->update($data);
        $address = new Address();
        $address->update($data);
        $address->write();
        $order->ShippingAddressID = $address->ID;
        $order->BillingAddressID = $address->ID; //same (for now)
        if ($member) {
            $order->MemberID = $member->ID;
        }
        $order->write();
        $this->processor = OrderProcessor::create($order);
        return $this->processor->placeOrder();
    }
}

// Class that writes order-item data to the DB upon placement
class OrderProcessorTest_CustomOrderItem extends Product_OrderItem implements TestOnly
{
    private static $db = array(
        'IsPlaced' => 'Boolean'
    );

    public function onPlacement()
    {
        parent::onPlacement();
        $this->isPlaced = true;
    }
}

// Extension to Order that will allow us a failed placement
class OrderProcessorTest_PlaceFailExtension extends DataExtension implements TestOnly
{
    private $willFail = false;

    public function onPlaceOrder()
    {
        // flag this order to fail
        $this->willFail = true;
    }

    public function onAfterWrite()
    {
        // fail after writing, so that we can test if DB rollback works as intended
        if($this->willFail){
            user_error('Order failed');
        }
    }
}
