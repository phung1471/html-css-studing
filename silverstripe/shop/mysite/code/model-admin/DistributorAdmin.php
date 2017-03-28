<?php
class DistributorAdmin extends ModelAdmin
{
    public static $managed_models = array('Distributor', 'Continent');
    static $url_segment = 'Distributor-manager';
    static $menu_title = 'Distributor';

    public function getSearchContext()
    {
        $context = parent::getSearchContext();
        return $context;
    }

}
