<?php

class Distributor extends DataObject{
    private static $db = array(
        'SortOrder' => 'Int',
        'Title' => 'Varchar(100)',
        'Tel' => 'Varchar(20)',
        'Fax' => 'Varchar(20)',
        'Email' => 'Varchar(50)',
        'Website' => 'Varchar(50)',
    );

    private static $default_sort = 'SortOrder';

    private static $belong_many_many = array(
        'ProductPages' => 'ProductPage'
    );

    private static $has_one = array(
        'Continent' => 'Continent'
    );

    public function getCMSFields(){
        return new FieldList(array(
            new TextField('Title', 'Name'),
            new TextField('Tel', 'Tel'),
            new TextField('Fax', 'Fax'),
            new TextField('Email', 'Email'),
            new TextField('Website', 'Website'),
            new DropdownField('ContinentID', 'Continent', Continent::get()->map()->toArray()),
        ));
    }

    public function summaryFields(){
        return array(
            'Title' => 'Name',
            'Tel' => 'Tel',
            'Fax' => 'Fax',
            'Email' => 'Email',
            'Website' => 'Website',
            'Continent.Title' => 'Continent'
        );
    }
}