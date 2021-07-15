<?php
    require_once( GETHALAL_MAILER_PLUGIN_DIR . '/class/class-gm-pc-list-table.php' );
    global $wpdb;

    $gethalal_mailer = GethalalMailer::instance();
    $id = $_GET['id'] ?? null;

    $table_name =  $wpdb->prefix . 'gethmailer_configs';


    $message = '';
	$error   = '';

    $uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);

    if( isset( $_POST['gethmailer_config_submit'])){

        $config = [];
        $config['id'] = $id??null;
        if(!empty($_POST['config_name'])){
           $config['name'] = sanitize_text_field( $_POST['config_name'] );
        } else {
            $error .= ' ' . __( "Please enter a valid name in the 'Name' field.", 'gethalal-mailer' );
        }

        $config['priority'] = $_POST['config_priority'];
        $config['order_status'] = $_POST['config_order_status'];
        $config['config'] = $_POST['config_categories'];
        $config['supplier_id'] = $_POST['config_supplier_id'];

        /* Update settings in the database */
        if ( empty( $error ) ) {
            $gethalal_mailer->setConfig( $config );
            $message .= __( 'Config saved.', 'gethalal-mailer' );
            $action_url = (empty($_SERVER['HTTPS'])?"http://":"https://") . $_SERVER['HTTP_HOST'] . $uri_parts[0] . "?page=gethalal_mailer";
            wp_redirect($action_url);
        } else {
            $error .= ' ' . __( 'Config are not saved.', 'gethalal-mailer' );
        }
    }

    if(!empty($id)){
        if(isset($_GET['action']) && $_GET['action'] == 'trash'){
            $wpdb->query("DELETE FROM $table_name WHERE id=$id");
            $action_url = (empty($_SERVER['HTTPS'])?"http://":"https://") . $_SERVER['HTTP_HOST'] . $uri_parts[0] . "?page=gethalal_mailer";
            wp_redirect($action_url);
        }
        $result=$wpdb->get_results("SELECT * FROM $table_name WHERE id = $id");
        $config = (array)($result[0]);
        $config_category_ids = explode(",", $config['config']);
        $category_ids = gm_lang_object_ids($config_category_ids, 'product_cat');
        $config['config'] = $category_ids;
    } else {
        $config = [];
    }

?>

<div class="wrap" id="swpsmtp-mail">
	<h2><?php esc_html_e( 'Mailer Setting', 'gethalal-mailer' ); ?></h2>

    <div class="updated fade" <?php echo empty( $message ) ? ' style="display:none"' : ''; ?>>
		<p><strong><?php echo esc_html( $message ); ?></strong></p>
	</div>
	<div class="error" <?php echo empty( $error ) ? 'style="display:none"' : ''; ?>>
		<p><strong><?php echo esc_html( $error ); ?></strong></p>
	</div>

    <div class="gethmailer-settings-container">
        <div class="gethmailer-settings-grid gethmailer-settings-main-cont">
                <form autocomplete="off" id="gethmailer_config_form" method="post" action="">
                    <div class="postbox" style="padding: 8px">
                        <h3 class="hndle"><label for="title"><?php esc_html_e( 'Preprocessing Config', 'gethalal-mailer' ); ?></label></h3>
                        <div class="inside">
                            <table class="form-table">
                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e( 'Name', 'gethalal-mailer' ); ?>:</th>
                                    <td>
                                        <input id="config_name" type="text" class="gc-form-field" name="config_name" value="<?php echo isset( $config['name'] ) ? esc_attr( $config['name'] ) : ''; ?>" /><br />
                                        <p class="description"><?php esc_html_e( "Enter the config name (Mail Title)", 'gethalal-mailer' ); ?></p>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e( 'Priority', 'gethalal-mailer' ); ?>:</th>
                                    <td>
                                        <input id="config_priority" type="number" class="gc-form-field" name="config_priority" value="<?php echo isset( $config['priority'] ) ? esc_attr( $config['priority'] ) : 0; ?>" /><br />
                                        <p class="description"><?php esc_html_e( "Enter number", 'gethalal-mailer' ); ?></p>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e( 'Order Status', 'gethalal-mailer' ); ?>:</th>
                                    <td>
                                        <?php
                                        $orderStatuses = wc_get_order_statuses();
                                        $output = '<select class="gc-form-field" id="gethmailer_order_status" name="config_order_status">';
                                        if(!empty($orderStatuses)){
                                            foreach( $orderStatuses as $key => $orderStatus ) {
                                                $output.= '<option value="'. esc_attr( $key ) .'" ' .((isset($config['order_status']) && $key==$config['order_status'])?'selected':''). '>'. esc_attr( $orderStatus ).'</option>';
                                            }
                                        }
                                        $output.='</select>';
                                        if(empty($orderStatuses))
                                            $output.='<div class="post_field_label" style="color:red; font-size:14px">' . __('No Order Statuses. Please Insert Order Status!', 'gethalal-mailer'). '</div>';
                                        echo $output;
                                        ?>
                                        <p class="description"><?php esc_html_e( "Select Order Status for preprocessing orders", 'gethalal-mailer' ); ?></p>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e( 'Select Supplier', 'gethalal-mailer' ); ?>:</th>
                                    <td>
                                        <?php
                                            $suppliers = $gethalal_mailer->getSuppliers();
                                            $output = '<select class="gc-form-field" id="gethmailer_supplier" name="config_supplier_id">';
                                            if(!empty($suppliers)){
                                                foreach( $suppliers as $key => $supplier ) {
                                                    $output.= '<option value="'. esc_attr( $key ) .'" ' .((isset($config['supplier_id']) && $key==$config['supplier_id'])?'selected':''). '>'. esc_attr( $supplier ).'</option>';
                                                }
                                            }
                                            $output.='</select>';
                                            if(empty($suppliers))
                                                $output.='<div class="post_field_label" style="color:red; font-size:14px">' . __('No Supplier. Please Insert Supplier!', 'gethalal-mailer'). '</div>';
                                            echo $output;
                                        ?>
                                        <p class="description"><?php esc_html_e( "Select supplier for preprocessing orders", 'gethalal-mailer' ); ?></p>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e( 'Product Categories', 'gethalal-mailer' ); ?>:</th>
                                    <td>
                                        <?php
                                            $pc_list_table = new GM_PC_List_Table($config['config']??[]);
                                            $pc_list_table->prepare_items();
                                            $pc_list_table->display();
                                        ?>
                                        <p class="description"><?php esc_html_e( "Select product categories for preprocessing orders", 'gethalal-mailer' ); ?></p>
                                    </td>
                                </tr>

                            </table>
                            <p class="submit">
                                <input type="submit" id="gethmailer_config-form-submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'gethalal-mailer' ); ?>" />
                                <input type="hidden" name="gethmailer_config_submit" value="submit" />
                                <?php wp_nonce_field( plugin_basename( __FILE__ ), 'gethmailer_config_nonce_name' ); ?>
                            </p>
                        </div><!-- end of inside -->
                    </div><!-- end of postbox -->
                </form>
            </div>
        </div>
    </div>
</div>
