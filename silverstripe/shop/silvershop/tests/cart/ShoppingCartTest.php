<?php

class ShoppingCartTest extends SapphireTest
{
    public static $fixture_file   = 'silvershop/tests/fixtures/shop.yml';
    public static $disable_theme  = true;
    public static $use_draft_site = false;

    public function setUp()
    {
        parent::setUp();
        ShopTest::setConfiguration(); //reset config
        Config::inst()->update('Order', 'extensions', ['ShoppingCartTest_TestShoppingCartHooksExtension']);

        ShoppingCart::singleton()->clear();
        ShoppingCartTest_TestShoppingCartHooksExtension::reset();
        $this->cart = ShoppingCart::singleton();
        $this->product = $this->objFromFixture('Product', 'mp3player');
        $this->product->publish('Stage', 'Live');
    }

    public function testAddToCart()
    {
        $this->assertTrue((boolean)$this->cart->add($this->product), "add one item");
        $this->assertEquals(
            ['onStartOrder', 'beforeAdd', 'afterAdd'],
            ShoppingCartTest_TestShoppingCartHooksExtension::$stack
        );

        $this->assertTrue((boolean)$this->cart->add($this->product), "add another item");
        $this->assertEquals(
            ['onStartOrder', 'beforeAdd', 'afterAdd', 'beforeAdd', 'afterAdd'],
            ShoppingCartTest_TestShoppingCartHooksExtension::$stack
        );

        $item = $this->cart->get($this->product);
        $this->assertEquals($item->Quantity, 2, "quantity is 2");
    }

    public function testRemoveFromCart()
    {
        $this->assertTrue((boolean)$this->cart->add($this->product), "add item");
        $this->assertEquals(
            ['onStartOrder', 'beforeAdd', 'afterAdd'],
            ShoppingCartTest_TestShoppingCartHooksExtension::$stack
        );

        $this->assertTrue($this->cart->remove($this->product), "item was removed");
        $this->assertEquals(
            ['onStartOrder', 'beforeAdd', 'afterAdd', 'beforeRemove', 'afterRemove'],
            ShoppingCartTest_TestShoppingCartHooksExtension::$stack
        );
        $item = $this->cart->get($this->product);
        $this->assertFalse($item, "item not in cart");
        $this->assertFalse($this->cart->remove($this->product), "try remove non-existent item");
    }

    public function testSetQuantity()
    {
        $this->assertTrue((boolean)$this->cart->setQuantity($this->product, 25), "quantity set");

        $this->assertEquals(
            ['onStartOrder', 'beforeSetQuantity', 'afterSetQuantity'],
            ShoppingCartTest_TestShoppingCartHooksExtension::$stack
        );

        $item = $this->cart->get($this->product);
        $this->assertEquals($item->Quantity, 25, "quantity is 25");

    }

    public function testClear()
    {
        //$this->assertFalse($this->cart->current(),"there is no cart initally");
        $this->assertTrue((boolean)$this->cart->add($this->product), "add one item");
        $this->assertTrue((boolean)$this->cart->add($this->product), "add another item");
        $this->assertEquals($this->cart->current()->class, "Order", "there a cart");
        $this->assertTrue($this->cart->clear(), "clear the cart");
        $this->assertFalse($this->cart->current(), "there is no cart");
    }

    public function testProductVariations()
    {
        $this->loadFixture('silvershop/tests/fixtures/variations.yml');
        $ball1 = $this->objFromFixture('ProductVariation', 'redlarge');
        $ball2 = $this->objFromFixture('ProductVariation', 'redsmall');

        $this->assertTrue((boolean)$this->cart->add($ball1), "add one item");

        $this->assertEquals(
            ['onStartOrder', 'beforeAdd', 'afterAdd'],
            ShoppingCartTest_TestShoppingCartHooksExtension::$stack
        );

        $this->assertTrue((boolean)$this->cart->add($ball2), "add another item");
        $this->assertTrue($this->cart->remove($ball1), "remove first item");

        $this->assertEquals(
            ['onStartOrder', 'beforeAdd', 'afterAdd', 'beforeAdd', 'afterAdd', 'beforeRemove', 'afterRemove'],
            ShoppingCartTest_TestShoppingCartHooksExtension::$stack
        );

        $this->assertFalse($this->cart->get($ball1), "first item not in cart");
        $this->assertNotNull($this->cart->get($ball1), "second item is in cart");
    }

    public function testCartSingleton()
    {
        $this->assertTrue((boolean)$this->cart->add($this->product), "add one item");
        $order = $this->cart->current();

        $this->assertEquals($order->ID, ShoppingCart::curr()->ID, "if singleton order ids will match");
    }

    public function testAddProductWithVariations()
    {
        $this->loadFixture('silvershop/tests/fixtures/variations.yml');
        $ball = $this->objFromFixture('Product', 'ball');
        $redlarge = $this->objFromFixture('ProductVariation', 'redlarge');
        // setting price of variation to zero, so it can't be added to cart.
        $redlarge->Price = 0;
        $redlarge->write();

        $ball->BasePrice = 0;
        $ball->write();

        $item = $this->cart->add($ball);
        $this->assertNotNull($item, "Product with variations can be added to cart");
        $this->assertInstanceOf('ProductVariation_OrderItem', $item, 'A variation should be added to cart.');
        $this->assertEquals(20, $item->Buyable()->Price, 'The buyable variation was added');
    }

    public function testErrorInCartHooks()
    {
        Config::inst()->update('Order', 'extensions', ['ShoppingCartTest_TestShoppingCartErroringHooksExtension']);

        ShoppingCart::singleton()->clear();
        $cart = ShoppingCart::singleton();

        $this->assertTrue((boolean)$this->cart->add($this->product, 1), "add one item");
        $item = $cart->get($this->product);
        $this->assertFalse(
            (boolean)$this->cart->add($this->product, 1),
            "Cannot add more than one item, extension will error"
        );
        $this->assertEquals($item->Quantity, 1, "quantity is 1");

        $this->assertTrue((boolean)$cart->setQuantity($this->product, 10), "quantity set");
        $item = $cart->get($this->product);
        $this->assertEquals($item->Quantity, 10, "quantity is 10");

        $this->assertFalse((boolean)$cart->setQuantity($this->product, 11), "Cannot set quantity to more than 10 items");
        $item = $cart->get($this->product);
        $this->assertEquals($item->Quantity, 10, "quantity is 10");
    }
}

class ShoppingCartTest_TestShoppingCartHooksExtension extends Extension implements TestOnly
{
    public static $stack = [];

    public static function reset()
    {
        self::$stack = [];
    }

    public function onStartOrder()
    {
        self::$stack[] = 'onStartOrder';
    }

    public function beforeAdd($buyable, $quantity, $filter)
    {
        self::$stack[] = 'beforeAdd';
    }

    public function afterAdd($item, $buyable, $quantity, $filter)
    {
        self::$stack[] = 'afterAdd';
    }

    public function beforeRemove($buyable, $quantity, $filter)
    {
        self::$stack[] = 'beforeRemove';
    }

    public function afterRemove($buyable, $quantity, $filter)
    {
        self::$stack[] = 'afterRemove';
    }

    public function beforeSetQuantity($buyable, $quantity, $filter)
    {
        self::$stack[] = 'beforeSetQuantity';
    }

    public function afterSetQuantity($item, $buyable, $quantity, $filter)
    {
        self::$stack[] = 'afterSetQuantity';
    }
}

class ShoppingCartTest_TestShoppingCartErroringHooksExtension extends Extension implements TestOnly
{
    public function beforeSetQuantity($buyable, $quantity, $filter)
    {
        if($quantity > 10) {
            throw new Exception('Invalid quantity');
        }
    }

    public function afterAdd($item, $buyable, $quantity, $filter)
    {
        if($item->Quantity > 1) {
            throw new Exception('Invalid quantity');
        }
    }
}

