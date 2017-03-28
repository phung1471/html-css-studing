<?php

class Page extends SiteTree
{

    private static $has_many = array(
        'Slides' => 'Slide'
    );

    public function getCMSFields()
    {

        $fields = parent::getCMSFields();

        $cf = new GridFieldConfig_RecordEditor();
        $cf->addComponent(new GridFieldSortableRows('SortOrder'));
        $fields->addFieldsToTab('Root.Slider', array(
            new GridField('Sliders', 'Slider', $this->Slides()->sort('SortOrder'), $cf)
        ));

        return $fields;
    }

}

class Page_Controller extends ContentController
{

}
