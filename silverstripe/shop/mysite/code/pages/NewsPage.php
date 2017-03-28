<?php

class NewsPage extends Page{

    static $has_one = array(
        'Image' => 'Image'
    );

    static $many_many = array(
        'NewsTags' => 'NewsTag',
        'NewsCategories' => 'NewsCategory'
    );

    public function getCMSFields(){
        $fields = parent::getCMSFields();

        $fields->addFieldsToTab('Root.Main', array(
            new UploadField('Image', 'Image'),
            ListboxField::create('NewsTags', 'Tags', NewsTag::get()->map()->toArray())->setMultiple(true),
            ListboxField::create('NewsCategories', 'Categories', NewsCategory::get()->map()->toArray())->setMultiple(true)
        ), 'Content');

        return $fields;
    }
}

class NewsPage_Controller extends Page_Controller{

}