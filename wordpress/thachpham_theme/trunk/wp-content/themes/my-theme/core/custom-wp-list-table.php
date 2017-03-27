<?php
// WP_List_Table is not loaded automatically so we need to load it in our application
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Create a new table class that will extend the WP_List_Table
 */
class Custom_WP_List_Table extends WP_List_Table
{
    protected $post_type = 'posts';
    protected $items_per_page = 10;

    public function __construct($args = array())
    {
        parent::__construct($args);
        $this->post_type = isset($args['post_type'])? $args['post_type'] : $this->post_type;
        $this->items_per_page = isset($args['items_per_page'])? $args['items_per_page'] : $this->items_per_page;
    }


    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items($args = array())
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data($args);

        $perPage = $this->items_per_page;
        $totalItems = count($data);

        $this->set_pagination_args(array(
            'total_items' => $totalItems,
            'per_page' => $perPage
        ));

        $data = $this->get_data($args);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns($callback = null)
    {
        $columns = array(
//            'ID' => 'ID',
            'post_title' => 'Title',
            'post_thumbnail' => 'Image',
            'block-content'  => 'Block Content'
        );

        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns($callback = null)
    {
        return array('ID' => array('ID', 'int'));
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    public function table_data($args = array())
    {
        $default_args = array(
            'post_type' => $this->post_type
        );
        $query = array_merge($default_args, $args);

        $data = new WP_Query($query);

        return $data->posts;
    }

    private function get_data($args = array()){
        $order_by = 'ID';
        $order = 'ASC';
        $paged = 1;

        // If orderby is set, use this as the sort column
        if (!empty($_GET['orderby'])) {
            $order_by = $_GET['orderby'];
        }

        // If order is set use this as the order
        if (!empty($_GET['order'])) {
            $order = $_GET['order'];
        }

        // If page is set use this as the page
        if (!empty($_GET['paged'])) {
            $paged = $_GET['paged'];
        }

        $post_type = $this->post_type;
        $per_page = $this->items_per_page;

        $default_query = array(
            'post_type' => $post_type,
            'order_by' => $order_by,
            'order' => $order,
            'posts_per_page' => $per_page,
            'paged' => $paged
        );

        $query = array_merge($default_query, $args);

        $data = new WP_Query($query);

        return $this->obj2arr($data->posts);
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default($item, $column_name, $callback = null)
    {
        if($callback != null){
            call_user_func($callback);
        }

        switch ($column_name) {
            case 'ID':
            case 'post_title':
            case 'post_modified':
            case 'post_date':
                return $item[$column_name];
            default:
                return print_r($item[$column_name], true);
        }
    }

    public function obj2arr($object_list){
        $arr_data = array();
        for ($i = 0; $i < count($object_list); $i++) {
            $arr_data[] = (array)$object_list[$i];
        }

        return $arr_data;
    }
}
