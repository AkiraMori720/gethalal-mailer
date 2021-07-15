<?php
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class GM_DS_COLUMN_List_Table extends WP_List_Table
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
            'name' => 'Name',
            'priority' => 'Priority',
            'config' => 'Categories'
        );

        return $columns;
    }

    public function get_hidden_columns()
    {
        return ['id'];
    }

    public function first_column($item, $field)
    {
        $uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);

        $config_id = $item['id'];
        $action_url = (empty($_SERVER['HTTPS']) ? "http://" : "https://") . $_SERVER['HTTP_HOST'] . $uri_parts[0] . "?page=gm_ds_column_config&id=$config_id";

        $actions = array(

            'edit' => sprintf('<a href="%s">%s</a>', $action_url, __("Edit", "gethalal-mailer")),

            'trash' => sprintf('<a href="%s&action=trash">' . __("Trash", "gethalal-mailer") . '</a>', $action_url),

        );
        return sprintf('%1$s %2$s', $field, $this->row_actions($actions));
    }


    public function get_sortable_columns()
    {
        return array('name' => array('name', false), 'priority' => array('priority', false), 'config' => array('config', false));
    }

    private function table_data()
    {
        $data = [];
        $delivery_instance = GethalalDelivery::instance();
        $columns = $delivery_instance->getColumns();

        foreach ($columns as $column) {
            $data[] = [
                'id' => $column['id'],
                'name' => $column['name'],
                'priority' => $column['priority'],
                'config' => explode(",", $column['config']),
            ];
        }

        return $data;
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'name':
                return $this->first_column($item, "<strong>" . $item['name'] . "</strong>");
            case 'priority':
                return $item[$column_name];
            case 'config':
            {
                $categories = [];
                foreach ($item['config'] as $id) {
                    if ($term = get_term_by('id', $id, 'product_cat')) {
                        $categories[] = $term->name;
                    }
                }
                return implode($categories, ",");
            }
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
        $orderby = 'priority';
        $order = 'asc';

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
