<?php

class CustomSiteConfig extends DataExtension {

    public static $db = array(
        'CompanyName' => 'Varchar(100)',
        'Address'     => 'Text',
        'Tel'         => 'Varchar(20)',
        'Fax'         => 'Varchar(20)',

        'Twitter'     => 'Varchar',
        'Facebook'    => 'Varchar',
        'Vimeo'       => 'Varchar',
        'Youtube'     => 'Varchar',
        'Intagrams'   => 'Varchar'
    );

    public static $has_one = array(
        'Logo' => 'Image'
    );


    public function updateCMSFields(FieldList $fields) {
        $fields->addFieldsToTab('Root.Main', array(
            new UploadField('Logo', 'Logo (100x100)'),
            new TextField('CompanyName', 'Company Name'),
            new TextField('Tel', 'Tel'),
            new TextField('Fax', 'Fax'),
            new TextareaField('Address', 'Address'),
            new HeaderField('Socials'),
            new TextField('Twitter', 'Twitter'),
            new TextField('Facebook', 'Facebook'),
            new TextField('Vimeo', 'Vimeo'),
            new TextField('Youtube', 'Youtube'),
            new TextField('Intagrams', 'Intagrams'),
        ));
    }

}
