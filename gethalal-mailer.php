<?php

 /**
 * Plugin Name: Gethalal Mailer
 * Description: Send mail about preprocessing orders
 * Version: 1.0.0
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

// load plugin text domain
// function gethalal_mailer_load_plugin_textdomain() {

//     $result = load_plugin_textdomain(
//         'gethalal_mailer',
//         false, 
//         dirname( plugin_basename( __FILE__ ) ) . '/translation' 
//     );
// }

// add_action('plugins_loaded', 'gethalal_mailer_load_plugin_textdomain');


// Include Class File
include("gethalal-functions.php");
include("class/class-gethalal-mailer.php");
GethalalMailer::instance();

// Define constants.
define('GETHALAL_MAILER_PLUGIN_NAME', basename(__DIR__));
define('GETHALAL_MAILER_VERSION', '1.0.0');
define('GETHALAL_MAILER_PLUGIN_DIR', untrailingslashit(plugin_dir_path(__FILE__)));
define('GETHALAL_MAILER_PLUGIN_URL', untrailingslashit(plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__))));
define('GETHALAL_MAILER_PLUGIN_BASENAME', plugin_basename(__FILE__));

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
}

/**
 * Add Admin Menu
 */
function gethalal_mailer_register_menu(){
    add_menu_page('Mailer Setting','Mailer Setting','read','gethalal_mailer','goto_gethalal_mailer_page','',26);
    add_submenu_page("gethalal_mailer",__( 'New Config', 'gethalal-mailer' ), __( 'New Config', 'gethalal-mailer' ), "manage_options", "gm_mailer_config", 'goto_gethalal_mailer_config_page');
    add_submenu_page("gethalal_mailer",__( 'Mailer Log', 'gethalal-mailer' ), __( 'Mailer Log', 'gethalal-mailer' ), "manage_options", "gm_mailer_log", 'goto_gethalal_mailer_log_page');
}

function goto_gethalal_mailer_page(){
    require_once GETHALAL_MAILER_PLUGIN_DIR . '/pages/mailer.php';  
}

function goto_gethalal_mailer_config_page(){
    require_once GETHALAL_MAILER_PLUGIN_DIR . '/pages/mailer_config.php';
}

function goto_gethalal_mailer_log_page(){
    require_once GETHALAL_MAILER_PLUGIN_DIR . '/pages/mailer_log.php';
}

add_action('admin_menu','gethalal_mailer_register_menu');

function fontawesome_icon_gethalal_mailer_menu() {
    echo '<style type="text/css" media="screen">
        icon16.icon-media:before, #toplevel_page_gethalal_mailer .toplevel_page_gethalal_mailer div.wp-menu-image:before {
        font-family: "Font Awesome 5 Free" !important;
        content: "\\f0e0";
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
    } else if($hook == "mailer-setting_page_gm_mailer_config"){
        wp_enqueue_style( 'gethmailer_config_css', plugins_url('css/setting.css',__FILE__ ), array(), GETHALAL_MAILER_VERSION );
    } else if($hook == "mailer-setting_page_gm_mailer_log"){
        wp_enqueue_style( 'gethmailer_log_css', plugin_dir_url(__FILE__) . 'css/setting.css', array(), GETHALAL_MAILER_VERSION );
    }
}
add_action('admin_enqueue_scripts',  'gethalal_enqueue');

// Plugin Deactivation
register_deactivation_hook(__FILE__, 'gethalal_mailer_deactivate');


function gethalal_mailer_deactivate(){
    flush_rewrite_rules();
}