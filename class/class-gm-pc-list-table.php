<?php
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class GM_PC_List_Table
{
    private $check_ids;

    private $headers;
    private $items;

    function __construct($check_ids = []){
        $this->check_ids = $check_ids == null?[]:$check_ids;
    }

    public function prepare_items()
    {
        $columns = $this->get_columns();

        $data = $this->table_data();

        $this->headers = $columns;
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
        return array('name' => array('name', false), 'description' => array('description', false), 'slug' => array('slug', false), 'count' => array('count', false));
    }

    private function table_data()
    {
        $productCats = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false
        ));

        $parents = [];
        $children = [];
        foreach ($productCats as $productCat){
            $data= [
                'id' => $productCat->term_id,
                'parent' => $productCat->parent,
                'name' => $productCat->name,
                'description' => $productCat->description,
                'slug' => $productCat->slug,
                'count' => $productCat->count
            ];
            if($productCat->parent){
                $children[] = $data;
            } else {
                $parents[] = $data;
            }
        }

        usort( $parents, array( &$this, 'sort_data' ) );

        $sortedData = [];
        foreach ($parents as $parent){
            $sortedData[]=$parent;
            $sortedData = array_merge($sortedData, $this->getChildren($parent, $children));
        }
        return $sortedData;
    }

    private function getChildren($parent, $items){
        $firstChildren = [];
        $children = [];
        foreach ($items as $item) {
            if($item['parent'] == $parent['id']){
                $firstChildren[] = $item;
            } else {
                $children[] = $item;
            }
        }

        if(empty($firstChildren)){
            return [];
        }
        $result = [];
        foreach ($firstChildren as $child){
            $result[] = $child;
            $result = array_merge($result, $this->getChildren($child, $children));
        }
        return $result;
    }

    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'name':
                return $item['parent']!=0?"— ${item['name']}":$item['name'];
            case 'description':
            case 'count':
                return $item[ $column_name ];
            case 'slug':
                return wc_sanitize_taxonomy_name( stripslashes($item[ $column_name ]));
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
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if(empty($_GET['orderby']))
        {
            return false;
        }
        $orderby = $_GET['orderby'];

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

    public function display(){
        echo "<Table class='gm-pc-table'><thead><tr>";

        foreach ($this->headers as $column => $header){
            echo "<th>" . ($column != 'cb'?$header:'') . "</th>";
        }

        echo "</tr></thead><tbody>";

        //body
        foreach ($this->items as $r => $row){
            echo "<tr>";
            foreach ($this->headers as $column => $header){
                echo "<td>" . ($column == 'cb'?$this->column_cb($row):$this->column_default($row, $column)) . "</td>";
            }
            echo "</tr>";
        }

        echo "</tbody></Table>";
    }
}