<?php
require_once( GETHALAL_MAILER_PLUGIN_DIR . '/class/class-gm-log-list-table.php' );

$gethalal_mailer = GethalalMailer::instance();

$message = '';
$error   = '';

if( isset( $_POST['gethmailer_log_clear_submit'])){
    $gethalal_mailer->clearLogs();
    $message .= __( 'Clear logs successfully.', 'gethalal-mailer' );
}

?>

<div class="wrap" id="gm-mail-log">
    <h2><?php esc_html_e( 'Mailer Logs', 'gethalal-mailer' ); ?></h2>

    <div class="updated fade" <?php echo empty( $message ) ? ' style="display:none"' : ''; ?>>
        <p><strong><?php echo esc_html( $message ); ?></strong></p>
    </div>
    <div class="error" <?php echo empty( $error ) ? 'style="display:none"' : ''; ?>>
        <p><strong><?php echo esc_html( $error ); ?></strong></p>
    </div>
    <div class="gethmailer-log-container">
        <div class="postbox">
            <h3 class="hndle"><label for="title"><?php esc_html_e( 'Preprocessing Logs', 'gethalal-mailer' ); ?></label></h3>
            <div class="inside">
                <table class="form-table">
                    <?php
                        $pc_list_table = new GM_Log_List_Table();
                        $pc_list_table->prepare_items();
                        $pc_list_table->display();
                    ?>
                </table>
                <form autocomplete="off" id="gethmailer_log_form" method="post" action="">
                    <p class="submit">
                        <input type="submit" id="gethmailer_log-form-submit" class="button-primary" value="<?php esc_attr_e( 'Clear All', 'gethalal-mailer' ); ?>" />
                        <input type="hidden" name="gethmailer_log_clear_submit" value="submit" />
                        <?php wp_nonce_field( plugin_basename( __FILE__ ), 'gethmailer_log_nonce_name' ); ?>
                    </p>
                </form>
            </div><!-- end of inside -->
        </div><!-- end of postbox -->
    </div>
</div>