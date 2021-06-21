<?php

$message = '';
$error   = '';
if( isset( $_POST['cost_setting_submit'])) {
    if(get_option('pl_handling_cost', -1) < 0){
        add_option('pl_handling_cost', $_POST['handling_cost']);
        add_option('pl_delivery_cost_in_city', $_POST['delivery_cost_in_city']);
        add_option('pl_shipping_cost_out_of_city', $_POST['shipping_cost_out_of_city']);
    } else {
        update_option('pl_handling_cost', $_POST['handling_cost']);
        update_option('pl_delivery_cost_in_city', $_POST['delivery_cost_in_city']);
        update_option('pl_shipping_cost_out_of_city', $_POST['shipping_cost_out_of_city']);
    }
}


$handling_cost = get_option('pl_handling_cost', 0);
$delivery_cost_in_city = get_option('pl_delivery_cost_in_city', 0);
$shipping_cost_out_of_city = get_option('pl_shipping_cost_out_of_city', 0);
?>
<div class="wrap" id="gethcost_setting">
    <h2><?php esc_html_e('Profit Calculator', 'gethalal-mailer'); ?></h2>

    <div class="updated fade" <?php echo empty($message) ? ' style="display:none"' : ''; ?>>
        <p><strong><?php echo esc_html($message); ?></strong></p>
    </div>
    <div class="error" <?php echo empty($error) ? 'style="display:none"' : ''; ?>>
        <p><strong><?php echo esc_html($error); ?></strong></p>
    </div>

    <div class="gethmailer-settings-container">
        <div class="gethmailer-settings-grid gethmailer-settings-main-cont">
            <form autocomplete="off" id="gethcost_setting-form" method="post" action="">
                <div class="postbox" style="padding: 8px">
                    <h3 class="hndle"><label
                                for="title"><?php esc_html_e('Cost Setting', 'gethalal-mailer'); ?></label></h3>
                    <div class="inside">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"><?php echo sprintf(__('Handling cost (%s)', 'gethalal-mailer'), get_woocommerce_currency_symbol()) ?>:</th>
                                <td>
                                    <input id="handling_cost" type="number" class="gc-form-field" name="handling_cost" value="<?php echo $handling_cost; ?>" step=".01"/><br />
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php echo sprintf(__('Delivery cost in city (%s)', 'gethalal-mailer'), get_woocommerce_currency_symbol()) ?>:</th>
                                <td>
                                    <input id="delivery_cost_in_city" type="number" class="gc-form-field" name="delivery_cost_in_city" value="<?php echo $delivery_cost_in_city; ?>" step=".01"/><br />
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php echo sprintf(__('Shipping cost out of city (%s)', 'gethalal-mailer'), get_woocommerce_currency_symbol()) ?>:</th>
                                <td>
                                    <input id="shipping_cost_out_of_city" type="number" class="gc-form-field" name="shipping_cost_out_of_city" value="<?php echo $shipping_cost_out_of_city; ?>" step=".01"/><br />
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" id="gethcost_setting-form-submit" class="button-primary"
                                   value="<?php esc_attr_e('Save Changes', 'gethalal-mailer'); ?>"/>
                            <input type="hidden" name="cost_setting_submit" value="submit"/>
                            <?php wp_nonce_field(plugin_basename(__FILE__), 'gethprofit_config_nonce_name'); ?>
                        </p>
                    </div><!-- end of inside -->
                </div><!-- end of postbox -->
            </form>
        </div>
    </div>
</div>
