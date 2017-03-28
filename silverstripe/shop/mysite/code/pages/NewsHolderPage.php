<?php

class NewsHolderPage extends Page{

    static $allowed_children = array(
        'NewsPage'
    );

    public function getCMSFields(){
        $fields = parent::getCMSFields();

        $cf = new GridFieldConfig_RecordEditor();
        $cf->addComponent(new GridFieldSortableRows('SortOrder'));
        $fields->addFieldsToTab('Root.Categories', array(
            new GridField('Categories', 'Categories', NewsCategory::get(), $cf)
        ));

        $cf = new GridFieldConfig_RecordEditor();
        $cf->addComponent(new GridFieldSortableRows('SortOrder'));
        $fields->addFieldsToTab('Root.Tags', array(
            new GridField('Tags', 'Tags', NewsTag::get(), $cf)
        ));

        return $fields;
    }
}

class NewsHolderPage_Controller extends Page_Controller{

    public function Paginated() {
        $page = new PaginatedList($this->GroupedByDate(), $this->getRequest());
        $page->setPageLength(1);

        return $page;
    }

    public function GroupedByDate(){
        $list = $this->Children();
        $group = array();

        foreach($list as $item){
            $i_date = date('d F Y', strtotime($item->Created));
            $group[$i_date][] = $item;
        }

        $groupList = new ArrayList();
        foreach($group as $k => $v){
            $groupList->push(new ArrayData(array(
                'date' => date('d M', strtotime($k)),
                'items' => new ArrayList($v)
            )));
        }

        return $groupList;
    }
}