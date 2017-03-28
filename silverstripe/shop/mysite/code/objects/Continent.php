<?php

class Continent extends DataObject{
    static $db = array(
        'SortOrder' => 'Int',
        'Title' => 'Varchar(20)'
    );

    static $default_sort = 'SortOrder';

    static $has_many = array(
        'Distributors' => 'Distributor'
    );

    public function getCMSFields(){
        return new FieldList(array(
            new TextField('Title', 'Name')
        ));
    }

    public function summaryFields(){
        return array(
            'Title' => 'Name'
        );
    }
}