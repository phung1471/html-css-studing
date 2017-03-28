<?php

class ProductPage extends Page
{
    static $db = array(
        'Display' => 'Enum("Vertical, Horizontal", "Vertical")'
    );
    static $has_one = array(
        'Image' => 'Image'
    );

    static $many_many= array(
        'Distributors' => 'Distributor'
    );

    public function getCMSFields(){
        $fields = parent::getCMSFields();

        $fields->addFieldsToTab('Root.Main', array(
            new UploadField('Image', 'Image (570x420)'),
            new DropdownField('Display', 'Display', singleton('ProductPage')->dbObject('Display')->enumValues()),
            new CheckboxSetField('Distributors', 'Distributors', Distributor::get()->map()->toArray())
        ), 'Content');

        return $fields;
    }

}

class ProductPage_Controller extends Page_Controller
{

}