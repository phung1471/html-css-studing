<?php

use SilverStripe\Omnipay\Service\PaymentService;

class ShopPaymentTest extends FunctionalTest
{
    protected static $fixture_file  = array(
        'silvershop/tests/fixtures/Pages.yml',
        'silvershop/tests/fixtures/shop.yml',
    );
    public static    $disable_theme = true;

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

        //set supported gateways
        Payment::config()->allowed_gateways = array(
            'Dummy', //onsite
            'Manual', //manual
            'PaymentExpress_PxPay', //offsite
            'PaymentExpress_PxPost' //onsite
        );

        PaymentService::setHttpClient($this->getHttpClient());
        PaymentService::setHttpRequest($this->getHttpRequest());

        //publish products
        $this->objFromFixture("Product", "socks")->publish('Stage', 'Live');
        $this->objFromFixture("CheckoutPage", "checkout")->publish('Stage', 'Live');
        $this->objFromFixture("CartPage", "cart")->publish('Stage', 'Live');
    }

    public function testManualPayment()
    {
        $this->markTestIncomplete("Process a manual payment");
    }

    public function testOnsitePayment()
    {
        $this->markTestIncomplete("Process an onsite payment");
    }

    public function testOffsitePayment()
    {
        $this->markTestIncomplete("Process an off-site payment");
    }

    public function testOffsitePaymentWithGatewayCallback()
    {
        //set up cart
        $cart = ShoppingCart::singleton()
            ->setCurrent($this->objFromFixture("Order", "cart"))
            ->current();
        //collect checkout details
        $cart->update(
            array(
                'FirstName' => 'Foo',
                'Surname'   => 'Bar',
                'Email'     => 'foo@example.com',
            )
        );
        $cart->write();
        //pay for order with external gateway
        $processor = OrderProcessor::create($cart);
        $this->setMockHttpResponse('paymentexpress/tests/Mock/PxPayPurchaseSuccess.txt');
        $response = $processor->makePayment("PaymentExpress_PxPay", array());
        //gateway responds (in a different session)
        $oldsession = $this->mainSession;
        $this->mainSession = new TestSession();
        ShoppingCart::singleton()->clear();
        $this->setMockHttpResponse('paymentexpress/tests/Mock/PxPayCompletePurchaseSuccess.txt');
        $this->getHttpRequest()->query->replace(array('result' => 'abc123'));
        $identifier = $response->getPayment()->Identifier;

        //bring back client session
        $this->mainSession = $oldsession;
        // complete the order
        $response = $this->get("paymentendpoint/$identifier/complete");

        //reload cart as new order
        $order = Order::get()->byId($cart->ID);
        $this->assertFalse($order->isCart(), "order is no longer in cart");
        $this->assertTrue($order->isPaid(), "order is paid");
        $this->assertNull(Session::get("shoppingcartid"), "cart session id should be removed");
        $this->assertNotEquals(404, $response->getStatusCode(), "We shouldn't get page not found");

        $this->markTestIncomplete("Should assert other things");
    }

    protected $payment;
    protected $httpClient;
    protected $httpRequest;

    protected function getHttpClient()
    {
        if (null === $this->httpClient) {
            $this->httpClient = new Guzzle\Http\Client;
        }

        return $this->httpClient;
    }

    public function getHttpRequest()
    {
        if (null === $this->httpRequest) {
            $this->httpRequest = new Symfony\Component\HttpFoundation\Request;
        }

        return $this->httpRequest;
    }

    protected function setMockHttpResponse($paths)
    {
        $testspath = BASE_PATH . '/vendor/omnipay';
        $mock = new Guzzle\Plugin\Mock\MockPlugin(null, true);
        $this->getHttpClient()->getEventDispatcher()->removeSubscriber($mock);
        foreach ((array)$paths as $path) {
            $mock->addResponse($testspath . '/' . $path);
        }
        $this->getHttpClient()->getEventDispatcher()->addSubscriber($mock);

        return $mock;
    }
}
