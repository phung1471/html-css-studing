<?php

class ContactPage extends Page {

    public static $db = array(
       
    );
    public static $has_one = array(
 
    );

    function getCMSFields() {
        $fields = parent::getCMSFields();

      

        return $fields;
    }

}

class ContactPage_Controller extends Page_Controller {

    private static $allowed_actions = array(
    );

}
