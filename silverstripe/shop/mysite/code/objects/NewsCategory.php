<?php

class NewsCategory extends DataObject{
    static $db = array(
        'SortOrder' => 'Int',
        'Title' => 'Varchar(100)',
//        'URLSegment' => 'Varchar(100)',
    );

    static $default_sort = 'SortOrder';

    static $belong_many_many = array(
        'News' => 'News'
    );

    public function getCMSFields(){
        return new FieldList(array(
            new TextField('Title', 'Title')
        ));
    }

    public function summaryFields(){
        return array(
            'Title' => 'Title'
        );
    }

//    public function onBeforeWrite(){
//        $this->URLSegment = SiteTree::create()->generateURLSegment($this->Title);
//        parent::onBeforeWrite();
//    }
}