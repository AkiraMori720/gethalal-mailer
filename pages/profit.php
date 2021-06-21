<?php
require_once(GETHALAL_MAILER_PLUGIN_DIR . '/class/class-gm-cpl-list-table.php');

$uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
$action_url = $uri_parts[0];
?>

<div class="wrap" id="gethalal_profit">
    <h2><?php esc_html_e('Profit Calculator', 'gethalal-mailer'); ?></h2>

    <div class="gethmailer-settings-container">
        <div class="gethmailer-settings-grid gethmailer-settings-main-cont">
            <div id="gethprofit_config_form" class="postbox">
                <h3 class="hndle"><label
                            for="title"><?php esc_html_e('Order`s Revenue Config', 'gethalal-mailer'); ?></label></h3>
                <div class="inside">

                    <a class="add-config-btn"
                       href="<?PHP echo (empty($_SERVER['HTTPS']) ? "http://" : "https://") . $_SERVER['HTTP_HOST'] . $action_url . '?page=gm_profit_config'; ?>">Add
                        New</a>
                    <?php
                    $pc_list_table = new GM_CPL_List_Table();
                    $pc_list_table->prepare_items();
                    $pc_list_table->display();
                    ?>
                    <p class="description"><?php esc_html_e("Select product categories for calculating revenue of order", 'gethalal-mailer'); ?></p>

                </div>
            </div><!-- end of postbox -->
        </div>
    </div>
</div>