<?php

/**
 * The order class is a databound object for handling Orders
 * within SilverStripe.
 *
 * @property string|float Currency
 * @property string Reference
 * @property string Placed
 * @property string Paid
 * @property string ReceiptSent
 * @property string Printed
 * @property string Dispatched
 * @property string Status
 * @property string FirstName
 * @property string Surname
 * @property string Email
 * @property string Notes
 * @property string IPAddress
 * @property string|bool SeparateBillingAddress
 * @property string Locale
 * @property string|int MemberID
 * @property string|int ShippingAddressID
 * @property string|int BillingAddressID
 * @method Member|ShopMember Member
 * @method Address BillingAddress
 * @method Address ShippingAddress
 * @method OrderItem[]|HasManyList Items
 * @method OrderModifier[]|HasManyList Modifiers
 * @method OrderStatusLog[]|HasManyList OrderStatusLogs
 *
 * @package shop
 */
class Order extends DataObject
{
    /**
     * Status codes and what they mean:
     *
     * Unpaid (default): Order created but no successful payment by customer yet
     * Query: Order not being processed yet (customer has a query, or could be out of stock)
     * Paid: Order successfully paid for by customer
     * Processing: Order paid for, package is currently being processed before shipping to customer
     * Sent: Order paid for, processed for shipping, and now sent to the customer
     * Complete: Order completed (paid and shipped). Customer assumed to have received their goods
     * AdminCancelled: Order cancelled by the administrator
     * MemberCancelled: Order cancelled by the customer (Member)
     */
    private static $db                = array(
        'Total'                  => 'Currency',
        'Reference'              => 'Varchar', //allow for customised order numbering schemes
        //status
        'Placed'                 => "SS_Datetime", //date the order was placed (went from Cart to Order)
        'Paid'                   => 'SS_Datetime', //no outstanding payment left
        'ReceiptSent'            => 'SS_Datetime', //receipt emailed to customer
        'Printed'                => 'SS_Datetime',
        'Dispatched'             => 'SS_Datetime', //products have been sent to customer
        'Status'                 => "Enum('Unpaid,Paid,Processing,Sent,Complete,AdminCancelled,MemberCancelled,Cart','Cart')",
        //customer (for guest orders)
        'FirstName'              => 'Varchar',
        'Surname'                => 'Varchar',
        'Email'                  => 'Varchar',
        'Notes'                  => 'Text',
        'IPAddress'              => 'Varchar(15)',
        //separate shipping
        'SeparateBillingAddress' => 'Boolean',
        // keep track of customer locale
        'Locale'                 => 'DBLocale',
    );

    private static $has_one           = array(
        'Member'          => 'Member',
        'ShippingAddress' => 'Address',
        'BillingAddress'  => 'Address',
    );

    private static $has_many          = array(
        'Items'           => 'OrderItem',
        'Modifiers'       => 'OrderModifier',
        'OrderStatusLogs' => 'OrderStatusLog',
    );

    private static $defaults          = array(
        'Status' => 'Cart',
    );

    private static $casting           = array(
        'FullBillingAddress'  => 'Text',
        'FullShippingAddress' => 'Text',
        'Total'               => 'Currency',
        'SubTotal'            => 'Currency',
        'TotalPaid'           => 'Currency',
        'Shipping'            => 'Currency',
        'TotalOutstanding'    => 'Currency',
    );

    private static $summary_fields    = array(
        'Reference'   => 'Order No',
        'Placed'      => 'Date',
        'Name'        => 'Customer',
        'LatestEmail' => 'Email',
        'Total'       => 'Total',
        'Status'      => 'Status',
    );

    private static $searchable_fields = array(
        'Reference' => array(),
        'FirstName' => array(
            'title' => 'Customer Name',
        ),
        'Email'     => array(
            'title' => 'Customer Email',
        ),
        'Status'    => array(
            'filter' => 'ExactMatchFilter',
            'field'  => 'CheckboxSetField',
        ),
    );

    private static $singular_name     = "Order";

    private static $plural_name       = "Orders";

    private static $default_sort      = "\"Placed\" DESC, \"Created\" DESC";

    /**
     * Statuses for orders that have been placed.
     */
    private static $placed_status = array(
        'Paid',
        'Unpaid',
        'Processing',
        'Sent',
        'Complete',
        'MemberCancelled',
        'AdminCancelled',
    );

    /**
     * Statuses for which an order can be paid for
     */
    private static $payable_status = array(
        'Cart',
        'Unpaid',
        'Processing',
        'Sent',
    );

    /**
     * Statuses that shouldn't show in user account.
     */
    private static $hidden_status = array('Cart');


    /**
     * Status for logging changes
     * @var array
     */
    private static $log_status = array();

    /**
     * Flags to determine when an order can be cancelled.
     */
    private static $cancel_before_payment    = true;

    private static $cancel_before_processing = false;

    private static $cancel_before_sending    = false;

    private static $cancel_after_sending     = false;

    /**
     * Place an order before payment processing begins
     *
     * @var boolean
     */
    private static $place_before_payment = false;

    /**
     * Modifiers represent the additional charges or
     * deductions associated to an order, such as
     * shipping, taxes, vouchers etc.
     */
    private static $modifiers            = array();

    private static $rounding_precision   = 2;

    private static $reference_id_padding = 5;

    /**
     * @var boolean Will allow completion of orders with GrandTotal=0,
     * which could be the case for orders paid with loyalty points or vouchers.
     * Will send the "Paid" date on the order, even though no actual payment was taken.
     * Will trigger the payment related extension points:
     * Order->onPayment, OrderItem->onPayment, Order->onPaid.
     */
    private static $allow_zero_order_total = false;

    /**
     * A flag indicating that an order-status-log entry should be written
     * @var bool
     */
    protected $flagOrderStatusWrite = false;

    public static function get_order_status_options()
    {
        $values = array();
        foreach (singleton('Order')->dbObject('Status')->enumValues(false) as $value) {
            $values[$value] = _t('Order.STATUS_' . strtoupper($value), $value);
        }
        return $values;
    }

    /**
     * Create CMS fields for cms viewing and editing orders
     */
    public function getCMSFields()
    {
        $fields = FieldList::create(TabSet::create('Root', Tab::create('Main')));
        $fs = "<div class=\"field\">";
        $fe = "</div>";
        $parts = array(
            DropdownField::create("Status", _t('Order.db_Status', "Status"), self::get_order_status_options()),
            LiteralField::create('Customer', $fs . $this->renderWith("OrderAdmin_Customer") . $fe),
            LiteralField::create('Addresses', $fs . $this->renderWith("OrderAdmin_Addresses") . $fe),
            LiteralField::create('Content', $fs . $this->renderWith("OrderAdmin_Content") . $fe),
        );
        if ($this->Notes) {
            $parts[] = LiteralField::create('Notes', $fs . $this->renderWith("OrderAdmin_Notes") . $fe);
        }
        $fields->addFieldsToTab('Root.Main', $parts);
        $this->extend('updateCMSFields', $fields);
        if ($payments = $fields->fieldByName("Root.Payments.Payments")) {
            $fields->removeByName("Payments");
            $fields->insertAfter($payments, "Content");
            $payments->addExtraClass("order-payments");
        }

        return $fields;
    }

    /**
     * Adjust scafolded search context
     *
     * @return SearchContext the updated search context
     */
    public function getDefaultSearchContext()
    {
        $context = parent::getDefaultSearchContext();
        $fields = $context->getFields();
        $fields->push(
            ListboxField::create("Status", _t('Order.db_Status', "Status"))
                ->setSource(
                    array_combine(
                        self::config()->placed_status,
                        self::config()->placed_status
                    )
                )
                ->setMultiple(true)
        );

        // add date range filtering
        $fields->insertBefore(
            DateField::create("DateFrom", _t('Order.DateFrom', "Date from"))
                ->setConfig('showcalendar', true),
            'Status'
        );
        $fields->insertBefore(
            DateField::create("DateTo", _t('Order.DateTo', "Date to"))
                ->setConfig('showcalendar', true),
            'Status'
        );

        // get the array, to maniplulate name, and fullname seperately
        $filters = $context->getFilters();
        $filters['DateFrom'] = GreaterThanFilter::create('Placed');
        $filters['DateTo'] = LessThanFilter::create('Placed');

        // filter customer need to use a bunch of different sources
        $filters['FirstName'] = new MultiFieldPartialMatchFilter(
            'FirstName', false,
            array('SplitWords'),
            array(
                'Surname',
                'Member.FirstName',
                'Member.Surname',
                'BillingAddress.FirstName',
                'BillingAddress.Surname',
                'ShippingAddress.FirstName',
                'ShippingAddress.Surname',
            )
        );

        $context->setFilters($filters);

        $this->extend('updateDefaultSearchContext', $context);
        return $context;
    }

    /**
     * Hack for swapping out relation list with OrderItemList
     */
    public function getComponents($componentName, $filter = "", $sort = "", $join = "", $limit = null)
    {
        $components = parent::getComponents($componentName, $filter = "", $sort = "", $join = "", $limit = null);
        if ($componentName === "Items" && get_class($components) !== "UnsavedRelationList") {
            $query = $components->dataQuery();
            $components = OrderItemList::create("OrderItem", "OrderID");
            if ($this->model) {
                $components->setDataModel($this->model);
            }
            $components->setDataQuery($query);
            $components = $components->forForeignID($this->ID);
        }
        return $components;
    }

    /**
     * Returns the subtotal of the items for this order.
     */
    public function SubTotal()
    {
        if ($this->Items()->exists()) {
            return $this->Items()->SubTotal();
        }

        return 0;
    }

    /**
     * Calculate the total
     *
     * @return the final total
     */
    public function calculate()
    {
        if (!$this->IsCart()) {
            return $this->Total;
        }
        $calculator = new OrderTotalCalculator($this);
        return $this->Total = $calculator->calculate();
    }

    /**
     * This is needed to maintain backwards compatiability with
     * some subsystems using modifiers. eg discounts
     */
    public function getModifier($className, $forcecreate = false)
    {
        $calculator = new OrderTotalCalculator($this);
        return $calculator->getModifier($className, $forcecreate);
    }

    /**
     * Enforce rounding precision when setting total
     */
    public function setTotal($val)
    {
        $this->setField("Total", round($val, self::$rounding_precision));
    }

    /**
     * Get final value of order.
     * Retrieves value from DataObject's record array.
     */
    public function Total()
    {
        return $this->getField("Total");
    }

    /**
     * Alias for Total.
     */
    public function GrandTotal()
    {
        return $this->Total();
    }

    /**
     * Calculate how much is left to be paid on the order.
     * Enforces rounding precision.
     *
     * Payments that have been authorized via a non-manual gateway should count towards the total paid amount.
     * However, it's possible to exclude these by setting the $includeAuthorized parameter to false, which is
     * useful to determine the status of the Order. Order status should only change to 'Paid' when all
     * payments are 'Captured'.
     *
     * @param bool $includeAuthorized whether or not to include authorized payments (excluding manual payments)
     * @return float
     */
    public function TotalOutstanding($includeAuthorized = true)
    {
        return round(
            $this->GrandTotal() - ($includeAuthorized ? $this->TotalPaidOrAuthorized() : $this->TotalPaid()),
            self::config()->rounding_precision
        );
    }

    /**
     * Get the order status. This will return a localized value if available.
     *
     * @return string the payment status
     */
    public function getStatusI18N()
    {
        return _t('Order.STATUS_' . strtoupper($this->Status), $this->Status);
    }

    /**
     * Get the link for finishing order processing.
     */
    public function Link()
    {
        if (Member::currentUser()) {
            return Controller::join_links(AccountPage::find_link(), 'order', $this->ID);
        }
        return CheckoutPage::find_link(false, "order", $this->ID);
    }

    /**
     * Returns TRUE if the order can be cancelled
     * PRECONDITION: Order is in the DB.
     *
     * @return boolean
     */
    public function canCancel()
    {
        switch ($this->Status) {
            case 'Unpaid' :
                return self::config()->cancel_before_payment;
            case 'Paid' :
                return self::config()->cancel_before_processing;
            case 'Processing' :
                return self::config()->cancel_before_sending;
            case 'Sent' :
            case 'Complete' :
                return self::config()->cancel_after_sending;
        }
        return false;
    }

    /**
     * Check if an order can be paid for.
     *
     * @return boolean
     */
    public function canPay($member = null)
    {
        if (!in_array($this->Status, self::config()->payable_status)) {
            return false;
        }
        if ($this->TotalOutstanding(true) > 0 && empty($this->Paid)) {
            return true;
        }
        return false;
    }

    /*
     * Prevent deleting orders.
     * @return boolean
     */
    public function canDelete($member = null)
    {
        return false;
    }

    /**
     * Check if an order can be viewed.
     *
     * @return boolean
     */
    public function canView($member = null)
    {
        return true;
    }

    /**
     * Check if an order can be edited.
     *
     * @return boolean
     */
    public function canEdit($member = null)
    {
        return true;
    }

    /**
     * Prevent standard creation of orders.
     *
     * @return boolean
     */
    public function canCreate($member = null, $context = array())
    {
        return false;
    }

    /**
     * Return the currency of this order.
     * Note: this is a fixed value across the entire site.
     *
     * @return string
     */
    public function Currency()
    {
        return ShopConfig::get_site_currency();
    }

    /**
     * Get the latest email for this order.z
     */
    public function getLatestEmail()
    {
        if ($this->MemberID && ($this->Member()->LastEdited > $this->LastEdited || !$this->Email)) {
            return $this->Member()->Email;
        }
        return $this->getField('Email');
    }

    /**
     * Gets the name of the customer.
     */
    public function getName()
    {
        $firstname = $this->FirstName ? $this->FirstName : $this->Member()->FirstName;
        $surname = $this->FirstName ? $this->Surname : $this->Member()->Surname;
        return implode(" ", array_filter(array($firstname, $surname)));
    }

    public function getTitle()
    {
        return $this->Reference . " - " . $this->dbObject('Placed')->Nice();
    }

    /**
     * Get shipping address, or member default shipping address.
     */
    public function getShippingAddress()
    {
        return $this->getAddress('Shipping');
    }

    /**
     * Get billing address, if marked to use seperate address, otherwise use shipping address,
     * or the member default billing address.
     */
    public function getBillingAddress()
    {
        if (!$this->SeparateBillingAddress && $this->ShippingAddressID === $this->BillingAddressID) {
            return $this->getShippingAddress();
        } else {
            return $this->getAddress('Billing');
        }
    }

    /**
     * @param string $type - Billing or Shipping
     * @return Address
     * @throws Exception
     */
    protected function getAddress($type)
    {
        $address = $this->getComponent($type . 'Address');

        if (!$address || !$address->exists() && $this->Member()) {
            $address = $this->Member()->{"Default${type}Address"}();
        }

        if (empty($address->Surname) && empty($address->FirstName)) {
            if ($member = $this->Member()) {
                // If there's a member object, use information from the Member.
                // The information from Order should have precendence if set though!
                $address->FirstName = $this->FirstName ?: $member->FirstName;
                $address->Surname = $this->Surname ?: $member->Surname;
            } else {
                $address->FirstName = $this->FirstName;
                $address->Surname = $this->Surname;
            }
        }

        return $address;
    }

    /**
     * Check if the two addresses saved differ.
     *
     * @return boolean
     */
    public function getAddressesDiffer()
    {
        return $this->SeparateBillingAddress || $this->ShippingAddressID !== $this->BillingAddressID;
    }

    /**
     * Has this order been sent to the customer?
     * (at "Sent" status).
     *
     * @return boolean
     */
    public function IsSent()
    {
        return $this->Status == 'Sent';
    }

    /**
     * Is this order currently being processed?
     * (at "Sent" OR "Processing" status).
     *
     * @return boolean
     */
    public function IsProcessing()
    {
        return $this->IsSent() || $this->Status == 'Processing';
    }

    /**
     * Return whether this Order has been paid for (Status == Paid)
     * or Status == Processing, where it's been paid for, but is
     * currently in a processing state.
     *
     * @return boolean
     */
    public function IsPaid()
    {
        return (boolean)$this->Paid || $this->Status == 'Paid';
    }

    public function IsCart()
    {
        return $this->Status == 'Cart';
    }

    /**
     * Create a unique reference identifier string for this order.
     */
    public function generateReference()
    {
        $reference = str_pad($this->ID, self::$reference_id_padding, '0', STR_PAD_LEFT);
        $this->extend('generateReference', $reference);
        $candidate = $reference;
        //prevent generating references that are the same
        $count = 0;
        while (DataObject::get_one('Order', "\"Reference\" = '$candidate'")) {
            $count++;
            $candidate = $reference . "" . $count;
        }
        $this->Reference = $candidate;
    }

    /**
     * Get the reference for this order, or fall back to order ID.
     */
    public function getReference()
    {
        return $this->getField('Reference') ? $this->getField('Reference') : $this->ID;
    }

    /**
     * Force creating an order reference
     */
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->getField("Reference") && in_array($this->Status, self::$placed_status)) {
            $this->generateReference();
        }

        // perform status transition
        if ($this->isInDB() && $this->isChanged('Status')) {
            $this->statusTransition(
                empty($this->original['Status']) ? 'Cart' : $this->original['Status'],
                $this->Status
            );
        }

        // While the order is unfinished/cart, always store the current locale with the order.
        // We do this everytime an order is saved, because the user might change locale (language-switch).
        if ($this->Status == 'Cart') {
            $this->Locale = ShopTools::get_current_locale();
        }
    }

    /**
     * Called from @see onBeforeWrite whenever status changes
     * @param string $fromStatus status to transition away from
     * @param string $toStatus target status
     */
    protected function statusTransition($fromStatus, $toStatus)
    {
        // Add extension hook to react to order status transitions.
        $this->extend('onStatusChange', $fromStatus, $toStatus);

        if ($toStatus == 'Paid' && !$this->Paid) {
            $this->Paid = SS_Datetime::now()->Rfc2822();
            foreach ($this->Items() as $item) {
                $item->onPayment();
            }
            //all payment is settled
            $this->extend('onPaid');

            if (!$this->ReceiptSent) {
                OrderEmailNotifier::create($this)->sendReceipt();
                $this->ReceiptSent = SS_Datetime::now()->Rfc2822();
            }
        }

        $logStatus = $this->config()->log_status;
        if (!empty($logStatus) && in_array($toStatus, $logStatus)) {
            $this->flagOrderStatusWrite = $fromStatus != $toStatus;
        }
    }

    /**
     * delete attributes, statuslogs, and payments
     */
    protected function onBeforeDelete()
    {
        foreach ($this->Items() as $item) {
            $item->delete();
        }

        foreach ($this->Modifiers() as $modifier) {
            $modifier->delete();
        }

        foreach ($this->OrderStatusLogs() as $logEntry) {
            $logEntry->delete();
        }

        // just remove the payment relations…
        // that way payment objects still persist (they might be relevant for book-keeping?)
        $this->Payments()->removeAll();

        parent::onBeforeDelete();
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();

        //create an OrderStatusLog
        if ($this->flagOrderStatusWrite) {
            $this->flagOrderStatusWrite = false;
            $log = OrderStatusLog::create();

            // populate OrderStatusLog
            $log->Title = _t(
                'ShopEmail.StatusChanged',
                'Status for order #{OrderNo} changed to "{OrderStatus}"',
                '',
                array('OrderNo' => $this->Reference, 'OrderStatus' => $this->getStatusI18N())
            );
            $log->Note = _t('ShopEmail.StatusChange' . $this->Status . 'Note');
            $log->OrderID = $this->ID;
            OrderEmailNotifier::create($this)->sendStatusChange($log->Title, $log->Note);
            $log->SentToCustomer = true;
            $this->extend('updateOrderStatusLog', $log);
            $log->write();
        }
    }

    public function debug()
    {
        $val = "<div class='order'><h1>$this->class</h1>\n<ul>\n";
        if ($this->record) {
            foreach ($this->record as $fieldName => $fieldVal) {
                $val .= "\t<li>$fieldName: " . Debug::text($fieldVal) . "</li>\n";
            }
        }
        $val .= "</ul>\n";
        $val .= "<div class='items'><h2>Items</h2>";
        if ($items = $this->Items()) {
            $val .= $this->Items()->debug();
        }
        $val .= "</div><div class='modifiers'><h2>Modifiers</h2>";
        if ($modifiers = $this->Modifiers()) {
            $val .= $modifiers->debug();
        }
        $val .= "</div></div>";

        return $val;
    }

    /**
     * Provide i18n entities for the order class
     *
     * @return array
     */
    public function provideI18nEntities()
    {
        $entities = parent::provideI18nEntities();

        // collect all the payment status values
        foreach ($this->dbObject('Status')->enumValues() as $value) {
            $key = strtoupper($value);
            $entities["Order.STATUS_$key"] = array(
                $value,
                "Translation of the order status '$value'",
            );
        }

        return $entities;
    }
}
