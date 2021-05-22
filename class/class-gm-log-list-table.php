<?php
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class GM_Log_List_Table extends WP_List_Table
{
    function __construct()
    {
        parent::__construct(array(
            'singular' => 'singular_name',
            'plural' => 'plural_name',
            'ajax' => false
        ));
    }

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        usort($data, array(&$this, 'sort_data'));

        $perPage = 10;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args(array(
            'total_items' => $totalItems,
            'per_page' => $perPage
        ));

        $data = array_slice($data, (($currentPage - 1) * $perPage), $perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    public function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'datetime' => 'DateTime',
            'message' => 'Message'
        );

        return $columns;
    }

    public function get_hidden_columns()
    {
        return ['id'];
    }

    public function get_sortable_columns()
    {
        return array('datetime' => array('datetime', false), 'message' => array('message', false));
    }

    private function table_data()
    {
        $data = array();
        global $wpdb;

        $config_table = $wpdb->prefix . 'gethmailer_logs';

        $select_sql = "SELECT * FROM ${config_table}";

        $configs = $wpdb->get_results($select_sql);

        foreach ($configs as $config) {
            $data[] = [
                'id' => $config->id,
                'datetime' => $config->datetime,
                'message' => $config->message
            ];
        }

        return $data;
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'datetime':
                return $item[$column_name];
            case 'message':
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }

    // Displaying checkboxes!
    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="%1$s" id="%2$s" value="checked" />',
            //$this->_args['singular'],
            $item['id'] . '_status',
            $item['id'] . '_status'
        );
    }

    private function sort_data($a, $b)
    {
        // Set defaults
        $orderby = 'datetime';
        $order = 'desc';

        // If orderby is set, use this as the sort column
        if (!empty($_GET['orderby']) && $_GET['orderby'] != 'config') {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if (!empty($_GET['order'])) {
            $order = $_GET['order'];
        }


        $result = strcmp($a[$orderby], $b[$orderby]);

        if ($order === 'asc') {
            return $result;
        }

        return -$result;
    }
}