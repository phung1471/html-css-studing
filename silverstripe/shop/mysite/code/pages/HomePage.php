<?php

class HomePage extends Page {

    public static $db = array(
        'BottomTitle' => 'Varchar(100)',
        'BottomContent' => 'HTMLText',
    );
    public static $has_one = array(
        'BottomImage' => 'Image'
    );

    function getCMSFields() {
	
        $fields = parent::getCMSFields();

        $fields->addFieldsToTab('Root.Bottom', array(
            new UploadField('Image', 'Image'),
            new TextField('BottomTitle', 'Title'),
            new HtmlEditorField('BottomContent', 'Content')
        ));

        return $fields;
    }

}

class HomePage_Controller extends Page_Controller {

    private static $allowed_actions = array(
    );

}
