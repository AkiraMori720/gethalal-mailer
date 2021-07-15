<?php
require_once(GETHALAL_MAILER_PLUGIN_DIR . '/class/class-gm-ds-column-list-table.php');

$gethalal_delivery = GethalalDelivery::instance();

$uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
$action_url = $uri_parts[0];

$message = '';
$error   = '';

if( isset($_POST['gethmailer_ds_download_submit'])) {
    $sheet = $gethalal_delivery->downloadSheet();
    if(!$sheet){
        $error .= __( 'Creating sheet failed.', 'gethalal-mailer' );
    } else {

        ob_clean();
        header("Expires: 0");
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header('Cache-Control: pre-check=0, post-check=0, max-age=0', false);
        header("Pragma: no-cache");
        header("Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition:attachment; filename={$sheet['name']}");

        readfile("{$sheet['url']}");

        ob_end_flush();
        exit();
    }
}


?>

<div class="wrap" id="gethalal_delivery">
    <h2><?php esc_html_e('Delivery Automation', 'gethalal-mailer'); ?></h2>
    <p class="description"><?php esc_html_e("Download delivery details for orders in 'In Progress' Status", 'gethalal-mailer'); ?></p>

    <div class="updated fade" <?php echo empty( $message ) ? ' style="display:none"' : ''; ?>>
        <p><strong><?php echo esc_html( $message ); ?></strong></p>
    </div>
    <div class="error" <?php echo empty( $error ) ? 'style="display:none"' : ''; ?>>
        <p><strong><?php echo esc_html( $error ); ?></strong></p>
    </div>

    <div class="gethmailer-settings-container">
        <div class="gethmailer-settings-grid gethmailer-settings-main-cont">
            <div id="gethprofit_config_form" class="postbox">
                <h3 class="hndle"><label
                            for="title"><?php esc_html_e('Delivery Sheet Columns', 'gethalal-mailer'); ?></label></h3>
                <div class="inside">

                    <a class="add-config-btn"
                       href="<?PHP echo (empty($_SERVER['HTTPS']) ? "http://" : "https://") . $_SERVER['HTTP_HOST'] . $action_url . '?page=gm_ds_column_config'; ?>">Add
                        New</a>
                    <?php
                        $pc_list_table = new GM_DS_COLUMN_List_Table();
                        $pc_list_table->prepare_items();
                        $pc_list_table->display();
                    ?>
                    <p class="description"><?php esc_html_e("Add/Remove columns for exporting excel file", 'gethalal-mailer'); ?></p>
                    <form autocomplete="off" id="gethmailer_ds_download_form" method="post" action="" style="text-align: right; padding: 8px">
                        <input type="submit" id="gethmailer_ds_download-form-submit" class="button-primary download-btn" value="<?php esc_attr_e( 'Download Delivery Plan', 'gethalal-mailer' ); ?>" />
                        <input type="hidden" name="gethmailer_ds_download_submit" value="submit" />
                        <?php wp_nonce_field( plugin_basename( __FILE__ ), 'gethmailer_ds_download_nonce_name' ); ?>
                    </form>
                </div>
            </div><!-- end of postbox -->
        </div>
    </div>
</div>
