<?php

class ProductEvidencePage extends Page{
    static $has_many = array(
        'EvidenceItems' => 'EvidenceItem'
    );

    public function getCMSFields(){
        $fields = parent::getCMSFields();

        $cf = new GridFieldConfig_RecordEditor();
        $cf->addComponent(new GridFieldSortableRows('SortOrder'));

        $fields->addFieldsToTab('Root.Items', array(
            new GridField('EvidenceItems', 'Items', $this->EvidenceItems(), $cf)
        ));


        return $fields;
    }
}

class ProductEvidencePage_Controller extends Page_Controller{

}