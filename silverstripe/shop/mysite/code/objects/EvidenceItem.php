<?php

Class EvidenceItem extends DataObject{
    static $db = array(
        'SortOrder' => 'Int',
        'Title' => 'Varchar',
        'Content' => 'Text',
        'Published' => 'Varchar'
    );

    static $default_sort = 'SortOrder';

    static $has_one = array(
        'ProductEvidencePage' => 'ProductEvidencePage'
    );

    public function getCMSFields(){
        return new FieldList(array(
            new TextField('Title', 'Title'),
            new TextareaField('Content', 'Content'),
            new TextField('Published', 'Published'),
        ));
    }

    public function summaryFields(){
        return array(
            'Title' => 'Title'
        );
    }
}