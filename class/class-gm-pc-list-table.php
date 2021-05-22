<?php
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class GM_PC_List_Table extends WP_List_Table
{
    private $check_ids;

    function __construct($check_ids = []){
        parent::__construct(array(
            'singular' => 'singular_name',
            'plural' => 'plural_name',
            'ajax' => false
        ));

        $this->check_ids = $check_ids == null?[]:$check_ids;
    }

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        usort( $data, array( &$this, 'sort_data' ) );

        $perPage = 10;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    public function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'name'       => 'Name',
            'description' => 'Description',
            'slug'        => 'Slug',
            'count'        => 'Count'
        );

        return $columns;
    }

    public function get_hidden_columns()
    {
        return ['id'];
    }


    public function get_sortable_columns()
    {
        return array('name' => array('Name', false), 'description' => array('Description', false), 'slug' => array('Slug', false), 'count' => array('Count', false));
    }

    private function table_data()
    {
        $data = array();
        $productCats = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false
        ));

        foreach ($productCats as $productCat){
            $data[] = [
                'id' => $productCat->term_id,
                'parent' => $productCat->parent,
                'name' => $productCat->name,
                'description' => $productCat->description,
                'slug' => $productCat->slug,
                'count' => $productCat->count
            ];
        }

        return $data;
    }

    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'name':
                return $item['parent']!=0?"— ${item['name']}":$item['name'];
            case 'description':
            case 'slug':
            case 'count':
                return $item[ $column_name ];

            default:
                return print_r( $item, true ) ;
        }
    }

    // Displaying checkboxes!
    function column_cb($item) {
        $is_checked = in_array($item['id']??'', $this->check_ids);
        return sprintf(
            '<input type="checkbox" %s name="%s" value="%s" />',
            $is_checked?'checked':'',
            'config_categories[]',
            $item['id']
        );
    }

    private function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'name';
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }


        $result = strcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;
    }
}