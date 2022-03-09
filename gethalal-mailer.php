<?php

 /**
 * Plugin Name: Gethalal Mailer
 * Description: Send mail about preprocessing orders
 * Version: 2.2.2
 * Author: Kzar
 * Author URI: mailto:kzar1102@outlook.com?subject=Gethalal%20Mailer
 * Requires at least: 4.9
 * Tested up to: 5.3
 * Requires PHP: 5.6
 * Text Domain: gethalal-mailer
 * License: GPL2+
 *
 */

if (!defined('ABSPATH')) {
    die;
}
// Include Class File
include("gethalal-functions.php");
include("class/class-gethalal-mailer.php");
include("class/class-gethalal-profit.php");
include("class/class-gethalal-delivery.php");
include("includes/class-dompdf.php");

GethalalMailer::instance();
GethalalProfit::instance();

// Define constants.
define('GETHALAL_MAILER_PLUGIN_NAME', basename(__DIR__));
define('GETHALAL_MAILER_VERSION', '2.2.0');
define('GETHALAL_MAILER_PLUGIN_DIR', untrailingslashit(plugin_dir_path(__FILE__)));
define('GETHALAL_MAILER_PLUGIN_URL', untrailingslashit(plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__))));
define('GETHALAL_MAILER_PLUGIN_BASENAME', plugin_basename(__FILE__));

function gethalal_add_every_day( $schedules ) {
    // add a 'weekly' schedule to the existing set
    $schedules['gethdaily'] = array(
        'interval' => 86400,
        'display' => __('Mailer Every day')
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'gethalal_add_every_day' );

function gethalal_cron_job(){
    $instance = GethalalMailer::instance();
    $instance->mail_cron_job();
}
add_action( 'init',  'gethalal_cron_job');

function gethalal_send_notification_for_preprocessing_products(){
    $instance = GethalalMailer::instance();
    $instance->send_notification_for_preprocessing_products();
}

// Cron schedule
add_action( 'mail_preprocessing_products', 'gethalal_send_notification_for_preprocessing_products');

if(!is_admin()){
    return;
}

// Plugin Activation
register_activation_hook(__FILE__, 'gethalal_mailer_activate');


function gethalal_mailer_activate(){
    // Global Managers Activate
    gethalal_mailer_db_install();
}

function gethalal_mailer_db_install() {
    global $wpdb;

    $wpdb_prefix = $wpdb->prefix;
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $sql = "CREATE TABLE `${wpdb_prefix}gethmailer_configs` (
        `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `priority` int(11) NOT NULL,
        `order_status` varchar(32) NOT NULL,
        `supplier_id` int(11) NOT NULL,
        `config` varchar(512) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    dbDelta( $sql );

    $sql = "CREATE TABLE `${wpdb_prefix}gethmailer_logs` (
        `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
        `datetime` varchar(255) NOT NULL,
        `message` varchar(512)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    dbDelta( $sql );

    $sql = "CREATE TABLE `${wpdb_prefix}gethmailer_suppliers` (
        `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `phone_number` varchar(255) NOT NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    dbDelta( $sql );

    $sql = "CREATE TABLE `${wpdb_prefix}gethprofit_configs` (
        `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `priority` int(11) NOT NULL,
        `config` varchar(512) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    dbDelta( $sql );
}

/**
 * Add Admin Menu
 */
function gethalal_mailer_register_menu(){
    // Mailer Setting
    add_menu_page('Mailer Setting','Mailer Setting','read','gethalal_mailer','goto_gethalal_mailer_page','',26);
    add_submenu_page("gethalal_mailer",__( 'New Config', 'gethalal-mailer' ), __( 'New Config', 'gethalal-mailer' ), "manage_options", "gm_mailer_config", 'goto_gethalal_mailer_config_page');
    add_submenu_page("gethalal_mailer",__( 'Mailer Log', 'gethalal-mailer' ), __( 'Mailer Log', 'gethalal-mailer' ), "manage_options", "gm_mailer_log", 'goto_gethalal_mailer_log_page');
    add_submenu_page("gethalal_mailer",__( 'New Supplier', 'gethalal-mailer' ), __( 'New Supplier', 'gethalal-mailer' ), "manage_options", "gm_mailer_supplier", 'goto_gethalal_mailer_supplier_page');

    // Profit/Loss
    add_menu_page('Profit Calculator','Profit Calculator','read','gethalal_profit','goto_gethalal_profit_page','',26);
    add_submenu_page("gethalal_profit",__( 'New Config', 'gethalal-mailer' ), __( 'New Config', 'gethalal-mailer' ), "manage_options", "gm_profit_config", 'goto_gethalal_profit_config_page');
    add_submenu_page("gethalal_profit",__( 'Cost Setting', 'gethalal-mailer' ), __( 'Cost Setting', 'gethalal-mailer' ), "manage_options", "gm_cost_setting", 'goto_gethalal_cost_setting_page');

    // Delivery Automation
    add_menu_page('Delivery Automation','Delivery Automation','read','gethalal_delivery','goto_gethalal_delivery_page','',26);
    add_submenu_page("gethalal_delivery",__( 'New Column', 'gethalal-mailer' ), __( 'New Column', 'gethalal-mailer' ), "manage_options", "gm_ds_column_config", 'goto_gethalal_dscolumn_config_page');
}

function goto_gethalal_mailer_page(){
    require_once GETHALAL_MAILER_PLUGIN_DIR . '/pages/mailer.php';
}

function goto_gethalal_profit_page(){
    require_once GETHALAL_MAILER_PLUGIN_DIR . '/pages/profit.php';
}

function goto_gethalal_delivery_page(){
    require_once GETHALAL_MAILER_PLUGIN_DIR . '/pages/delivery.php';
}

function goto_gethalal_mailer_config_page(){
    require_once GETHALAL_MAILER_PLUGIN_DIR . '/pages/mailer_config.php';
}

function goto_gethalal_profit_config_page(){
    require_once GETHALAL_MAILER_PLUGIN_DIR . '/pages/profit_config.php';
}

function goto_gethalal_mailer_log_page(){
    require_once GETHALAL_MAILER_PLUGIN_DIR . '/pages/mailer_log.php';
}

function goto_gethalal_mailer_supplier_page(){
    require_once GETHALAL_MAILER_PLUGIN_DIR . '/pages/mailer_supplier.php';
}

function goto_gethalal_cost_setting_page(){
    require_once GETHALAL_MAILER_PLUGIN_DIR . '/pages/cost_setting.php';
}

function goto_gethalal_dscolumn_config_page(){
    require_once GETHALAL_MAILER_PLUGIN_DIR . '/pages/ds_column_config.php';
}

add_action('admin_menu','gethalal_mailer_register_menu');

function fontawesome_icon_gethalal_mailer_menu() {
    echo '<style type="text/css" media="screen">
        #toplevel_page_gethalal_mailer .toplevel_page_gethalal_mailer div.wp-menu-image:before {
        font-family: "Font Awesome 5 Free" !important;
        content: "\\f0e0";
        font-style:normal;
        font-weight:900;
        }
        #toplevel_page_gethalal_delivery .toplevel_page_gethalal_delivery div.wp-menu-image:before {
        font-family: "Font Awesome 5 Free" !important;
        content: "\\f0d1";
        font-style:normal;
        font-weight:900;
        }
    </style>';
    }
add_action('admin_head', 'fontawesome_icon_gethalal_mailer_menu');


/**
 * Import js files
 */
function gethalal_enqueue($hook){
    wp_enqueue_style('gethalal_fontawesome','https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.min.css', '', '5.11.2', 'all');
    if($hook == "toplevel_page_gethalal_mailer"){
		wp_enqueue_script( 'gethmailer_admin_js', plugin_dir_url(__FILE__) . 'js/script.js', array(), GETHALAL_MAILER_VERSION);
        wp_enqueue_style( 'gethmailer_admin_css', plugin_dir_url(__FILE__) . 'css/setting.css', array(), GETHALAL_MAILER_VERSION );
    } else if($hook == "mailer-setting_page_gm_mailer_config"
        || $hook == "toplevel_page_gethalal_profit"
        || $hook == "toplevel_page_gethalal_delivery"
        || $hook == "profit-calculator_page_gm_profit_config"
        || $hook == "mailer-setting_page_gm_mailer_log"
        || $hook == "mailer-setting_page_gm_mailer_supplier"
        || $hook == "mailer-setting_page_gm_ds-column-config"
    ){
        wp_enqueue_style( 'gethmailer_config_css', plugins_url('css/setting.css',__FILE__ ), array(), GETHALAL_MAILER_VERSION );
    }
    if($hook == "mailer-setting_page_gm_mailer_supplier"){
        wp_enqueue_script( 'gethmailer_tel_input_js', "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js", array() );
        wp_enqueue_style( 'gethmailer_tel_input_css', "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css", array() );
    }
}
add_action('admin_enqueue_scripts',  'gethalal_enqueue');

/**
 * Profit / Loss
 */

// Add Product Custom Fields
add_action('woocommerce_product_options_general_product_data', 'gethalal_woocommerce_product_custom_fields');

function gethalal_woocommerce_product_custom_fields()
{
    global $woocommerce, $post;
    echo '<div class="product_custom_field">';

    woocommerce_wp_text_input(
        array(
            'id' => '_cost_price',
            'label' => sprintf(__('Cost price (%s)', 'gethalal-mailer'), get_woocommerce_currency_symbol()),
            'data_type' => 'price',
        )
    );

    echo '</div>';
}

add_action('woocommerce_process_product_meta', 'gethalal_woocommerce_product_custom_fields_save');

function gethalal_woocommerce_product_custom_fields_save($post_id)
{
    $woocommerce_custom_product_number_field = $_POST['_cost_price'];
    if (!empty($woocommerce_custom_product_number_field))
        update_post_meta($post_id, '_cost_price', sanitize_text_field($woocommerce_custom_product_number_field));
}


// Add Order custom Fields
add_action( 'woocommerce_admin_order_data_after_order_details', 'gethalal_woocommerce_admin_order_data_after_order_details' );
function gethalal_woocommerce_admin_order_data_after_order_details( $order ){
    ?>
    <br class="clear" />
    <?php
    /*
     * get all the meta data values we need
     */
    $handling_cost = get_post_meta( $order->get_id(), 'handling_cost', true );
    $delivery_cost_in_city = get_post_meta( $order->get_id(), 'delivery_cost_in_city', true );
    $shipping_cost_out_of_city = get_post_meta( $order->get_id(), 'shipping_cost_out_of_city', true );
    ?>

    <div class="edit_custom_field"> <!-- use same css class in h4 tag -->
        <?php
        woocommerce_wp_text_input( array(
            'id' => 'handling_cost',
            'label' => sprintf(__('Handling cost (%s)', 'gethalal-mailer'), get_woocommerce_currency_symbol()),
            'value' => $handling_cost,
            'wrapper_class' => 'form-field-wide',
            'data_type' => 'price',
        ) );
        woocommerce_wp_text_input( array(
            'id' => 'delivery_cost_in_city',
            'label' => sprintf(__('Delivery cost in city (%s)', 'gethalal-mailer'), get_woocommerce_currency_symbol()),
            'value' => $delivery_cost_in_city,
            'wrapper_class' => 'form-field-wide',
            'data_type' => 'price',
        ) );
        woocommerce_wp_text_input( array(
            'id' => 'shipping_cost_out_of_city',
            'label' => sprintf(__('Shipping cost out of city (%s)', 'gethalal-mailer'), get_woocommerce_currency_symbol()),
            'value' => $shipping_cost_out_of_city,
            'wrapper_class' => 'form-field-wide',
            'data_type' => 'price',
        ) );
        ?>
    </div>
    <?php
}

// Plugin Deactivation
register_deactivation_hook(__FILE__, 'gethalal_mailer_deactivate');


function gethalal_mailer_deactivate(){
    flush_rewrite_rules();
}
