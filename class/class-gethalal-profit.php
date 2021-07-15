<?php

if (!defined('ABSPATH')) {
	exit;
}

class GethalalProfit
{

    private static $instance = null;

	public $config;

	public static function instance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

    /**
	 * Constructor.
	 */
	public function __construct()
	{
        $this->fetchConfig();
        $this->setupOrderProfit();
        add_action( 'wp_dashboard_setup', array($this, 'setupDashboardWidget'));
    }

    function addLog($message){
        global $wpdb;
        $config_table = $wpdb->prefix . 'gethmailer_logs';
        $wpdb->query("INSERT INTO $config_table (datetime, message) values (" . time() . ", '" . $message . "')");
    }

	function clearLogs(){
        global $wpdb;
        $config_table = $wpdb->prefix . 'gethmailer_logs';
        $wpdb->query("DELETE FROM $config_table");
    }

    function getConfig(){
	    return $this->config;
    }

    function fetchConfig(){
        global $wpdb;
        $select_sql = "SELECT * FROM ". $wpdb->prefix . "gethprofit_configs Order By priority ASC";
        $configs=$wpdb->get_results($select_sql);

        $this->config = array_map(function($config){
            $result = (array)$config;
            $config_category_ids = explode(",", $result['config']);
            $result['config'] = $config_category_ids;
            return $result;
        }, $configs);
    }

    function setConfig($config){
	    global $wpdb;

	    $configuration = implode($config['config'], ",");
        if($config['id']){
            $wpdb->query("Update ". $wpdb->prefix . "gethprofit_configs" . " set name=\"${config['name']}\", priority=${config['priority']}, config=\"${configuration}\", updated_at=" . time() . " WHERE id = ${config['id']}");
        } else {
            $wpdb->query("insert into ". $wpdb->prefix . "gethprofit_configs" . " (name, priority, config) values ('" . $config['name'] . "', " . $config['priority'] . ", '" . $configuration . "')");
        }

        $this->fetchConfig();
    }

    function setupOrderProfit(){
        add_action('woocommerce_admin_order_totals_after_total', array($this, 'display_admin_order_profit_information'), 10, 1);
    }

    function display_admin_order_profit_information($order_id){
        $revenues = [];
        $items_profit = 0;
	    $order = wc_get_order($order_id);
        foreach ( $order->get_items() as $item ) {

            $refunded_qty = abs( $order->get_qty_refunded_for_item( $item->get_id() ) );
            if ( $refunded_qty && $item->get_quantity() === $refunded_qty ) {
                continue;
            }

            if ($item->is_type('line_item')) {

                $order_status = $order->get_status();

                foreach ($this->config as $config) {
                    $subject = $config['name'];

                    // Filter Product Categories
                    $category_ids = gm_lang_object_ids($config['config'], 'product_cat');
                    $revenue = $this->filterProduct($item, $refunded_qty, $category_ids);

                    if ($revenue) {
                        if (!isset($revenues[$config['id']])) {
                            $revenues[$config['id']] = ['subject' => $subject, 'revenue' => 0];
                        }
                        $revenues[$config['id']]['revenue'] += $revenue;
                        break;
                    }

                }

                // Profit in_city/out_city
                $product = $item->get_product();
                $quantity = $item->get_quantity() - $refunded_qty;
                $product_cost = $product->get_meta('_cost_price');
                $item_profit = wc_get_price_including_tax($product, ['qty' => $quantity]) - ($product_cost==''?0:$product_cost) * $quantity;
                $items_profit += $item_profit;
            }
        }

        $prices_precision = wc_get_price_decimals();

        if(count($revenues) > 0){
            ?>
            <tr>
                <td colspan="3"><div style="border-top: 1px solid #999; margin-top:12px; padding-top:12px"></div></td>
            </tr>
            <tr>
                <td class="label label-highlight">Revenue</td>
            </tr>
            <?php
        }
        foreach($revenues as $revenue){
            ?>
                <tr>
                    <td class="label"><?php echo $revenue['subject'] ?>:</td>
                    <td width="1%"></td>
                    <td class="total"><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol"><?php echo get_woocommerce_currency_symbol() ?></span><?php echo  wc_format_decimal( $revenue['revenue'], $prices_precision ) ?></bdi></span></td>
                </tr>
            <?php
        }

        // Profit/
        $handling_cost = get_option('pl_handling_cost', 0);
        $delivery_cost_in_city = get_option('pl_delivery_cost_in_city', 0);
        $shipping_cost_out_of_city = get_option('pl_shipping_cost_out_of_city', 0);
        $shipping = floatval($order->get_shipping_total()) + floatval($order->get_shipping_tax());

        $order_profit_in_city = $items_profit - $handling_cost - $delivery_cost_in_city + $shipping;
        $order_profit_out_city = $items_profit - $handling_cost - $shipping_cost_out_of_city + $shipping;

        ?>
        <tr>
            <td colspan="3"><div style="border-top: 1px solid #999; margin-top:12px; padding-top:12px"></div></td>
        </tr>
        <tr style="color:<?php  echo $order_profit_in_city<0?'red':'inherit' ?>">
            <td class="label">Profit/Loss (In-City):</td>
            <td width="1%"></td>
            <td class="total"><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol"><?php echo get_woocommerce_currency_symbol() ?></span><?php echo  wc_format_decimal( $order_profit_in_city, $prices_precision ) ?></bdi></span></td>
        </tr>
        <tr style="color:<?php  echo $order_profit_out_city<0?'red':'inherit' ?>">
            <td class="label">Profit/Loss (Out of the city):</td>
            <td width="1%"></td>
            <td class="total"><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol"><?php echo get_woocommerce_currency_symbol() ?></span><?php echo  wc_format_decimal( $order_profit_out_city, $prices_precision ) ?></bdi></span></td>
        </tr>
        <?php
    }

    /**
     * @param WC_Order_Item_Product $order_item
     * @param $allow_category_ids
     * @return bool|float
     */
	function filterProduct(WC_Order_Item_Product $order_item, $refunded_qty, $allow_category_ids)
    {
        /** @var WC_Product $product */
        $product = $order_item->get_product();
        $quantity = $order_item->get_quantity() - $refunded_qty;

        if ($product) {

            $category_ids = $product->get_category_ids();
            $category_ids = gm_lang_object_ids($category_ids, 'product_cat');

            if (!$this->allowCategory($category_ids, $allow_category_ids)) {
                return false;
            }

            return wc_get_price_including_tax($product, ['qty' => $quantity]);
        }
		return false;
	}

	/**
     *
     */
	function allowCategory($categoryIds, $allow_category_ids){
	    foreach ($categoryIds as $categoryId){
	        if(in_array($categoryId, $allow_category_ids)){
	            return true;
            }
        }
	    return false;
    }


    ///////////////////////////////////////////////////////
    /// Dashboard Widget
    ///

    function setupDashboardWidget()
    {
        if ( current_user_can( 'manage_options' ) ) {
            global $wp_meta_boxes;
            wp_add_dashboard_widget(
                'pl_dashboard_widget',
                __('Profit/Loss', 'gethalal-mailer'),
                array( $this, 'pl_dashboard_widget' )
            );
            $dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
            $aj_widget = array( 'pl_dashboard_widget' => $dashboard['pl_dashboard_widget'] );
            unset( $dashboard['pl_dashboard_widget'] );
            $sorted_dashboard = array_merge( $aj_widget, $dashboard );
            $wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;

            wp_enqueue_style('aj_admin_styles',  plugin_dir_url(__DIR__) . 'css/dashboard.css', array(), GETHALAL_MAILER_VERSION );
        }
    }

    function pl_dashboard_widget() {
        require_once GETHALAL_MAILER_PLUGIN_DIR . '/pages/dashboard_widget.php';
    }

    function getDashboardSummary($category_id, $from_date, $to_date)
    {
        $summary = [
            'profit' => 0,
            'revenue' => 0,
            'cost' => 0,
            'refund' => 0
        ];
        $page = 1;

        // todo created_date from delivery_date (minus a month)
        $from_create_date = new DateTime($from_date);
        $from_create_date_str = $from_create_date->modify( 'first day of -1 month' )->format('Y-m-d');

        do {
            $orders = wc_get_orders([
                'type' => 'shop_order',
                'status' => 'wc-completed',
                'date_after' => $from_create_date_str,
                'page' => $page,
                'limit' => 100
            ]);

            foreach ($orders as $order) {
                $order = wc_get_order($order);

                // Check DateTime Range
                $_delivery_date = get_post_meta($order->get_id(), '_delivery_date', true);
                if(empty($_delivery_date)){ continue; }

                $delivery_date = (new DateTime())->setTimestamp($_delivery_date)->format('Y-m-d');
                if($delivery_date > $to_date || $delivery_date < $from_date) {
                    continue;
                }

                foreach ( $order->get_items() as $item ) {

                    /** @var WC_Product $product */
                    $product = $item->get_product();
                    if(!$product) {
                        continue;
                    }

                    $category_ids = $product->get_category_ids();
                    $category_ids = gm_lang_object_ids($category_ids, 'product_cat');
                    if($category_id == 0 || in_array(gm_lang_object_ids($category_id, 'product_cat'), $category_ids)) {
                        // Exclude Refund Order
                        $refunded_qty = abs( $order->get_qty_refunded_for_item($item->get_id()) );
                        if ( $refunded_qty > 0 && $item->get_quantity() === $refunded_qty ) {
                            $summary['refund'] -= $order->get_total_refunded_for_item($item->get_id());
                        } else if ($item->is_type('line_item')) {
                            $quantity = $item->get_quantity();
                            $revenue = wc_get_price_including_tax($product, ['qty' => $quantity]);
                            $summary['revenue'] += $revenue;
                            $product_cost = $product->get_meta('_cost_price');
                            $cost = ($product_cost == ''?0:$product_cost) * $quantity;
                            $summary['cost'] += $cost;
                            $summary['profit'] += $revenue - $cost;
                        }
                    }
                }
            }
            $page++;
        }while(count($orders) == 100);

        return $summary;
    }
}
