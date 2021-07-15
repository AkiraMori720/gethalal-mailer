<?php
    require_once( GETHALAL_MAILER_PLUGIN_DIR . '/class/class-gm-pc-list-table.php' );
    global $wpdb;

    $gethalal_mailer = GethalalMailer::instance();
    $id = $_GET['id'] ?? null;

    $table_name =  $wpdb->prefix . 'gethmailer_suppliers';


    $message = '';
	$error   = '';

    $uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);

    if( isset( $_POST['gethmailer_supplier_submit'])){

        $supplier = [];
        $supplier['id'] = $id??null;
        if(!empty($_POST['supplier_name'])){
            $supplier['name'] = sanitize_text_field( $_POST['supplier_name'] );
        } else {
            $error .= ' ' . __( "Please enter a valid name in the 'Name' field.", 'gethalal-mailer' );
        }

        $supplier['phone_number'] = $_POST['phone_number'];

        /* Update settings in the database */
        if ( empty( $error ) ) {
            $gethalal_mailer->setSupplier( $supplier );
            $message .= __( 'Supplier saved.', 'gethalal-mailer' );
            $action_url = (empty($_SERVER['HTTPS'])?"http://":"https://") . $_SERVER['HTTP_HOST'] . $uri_parts[0] . "?page=gethalal_mailer#suppliers";
            wp_redirect($action_url);
        } else {
            $error .= ' ' . __( 'Supplier is not saved.', 'gethalal-mailer' );
        }
    }

    if(!empty($id)){
        if(isset($_GET['action']) && $_GET['action'] == 'trash'){
            $wpdb->query("DELETE FROM $table_name WHERE id=$id");
            $action_url = (empty($_SERVER['HTTPS'])?"http://":"https://") . $_SERVER['HTTP_HOST'] . $uri_parts[0] . "?page=gethalal_mailer#suppliers";
            wp_redirect($action_url);
        }
        $result=$wpdb->get_results("SELECT * FROM $table_name WHERE id = $id");
        $supplier = (array)($result[0]);
    } else {
        $supplier = [];
    }

?>

<div class="wrap" id="swpsmtp-mail">
	<h2><?php esc_html_e( 'Supplier', 'gethalal-mailer' ); ?></h2>

    <div class="updated fade" <?php echo empty( $message ) ? ' style="display:none"' : ''; ?>>
		<p><strong><?php echo esc_html( $message ); ?></strong></p>
	</div>
	<div class="error" <?php echo empty( $error ) ? 'style="display:none"' : ''; ?>>
		<p><strong><?php echo esc_html( $error ); ?></strong></p>
	</div>

    <div class="gethmailer-settings-container">
        <div class="gethmailer-settings-grid gethmailer-settings-main-cont">
                <form autocomplete="off" id="gethmailer_supplier_form" name="gethmailer_supplier_form" method="post" onsubmit="saveSupplier(event)" action="">
                    <input id="phone_number" type="hidden" class="gc-form-field" name="phone_number" value="<?php echo isset( $supplier['phone_number'] ) ? esc_attr( $supplier['phone_number'] ) : ''; ?>" /><br />
                    <div class="postbox" style="padding: 8px">
                        <h3 class="hndle"><label for="title"><?php esc_html_e( !empty($id)?'Update Supplier':'Add Supplier','gethalal-mailer' ); ?></label></h3>
                        <div class="inside">
                            <table class="form-table">
                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e( 'Name', 'gethalal-mailer' ); ?>:</th>
                                    <td>
                                        <input id="supplier_name" type="text" class="gc-form-field" name="supplier_name" value="<?php echo isset( $supplier['name'] ) ? esc_attr( $supplier['name'] ) : ''; ?>" required/><br />
                                        <p class="description"><?php esc_html_e( "Enter the supplier name", 'gethalal-mailer' ); ?></p>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e( 'Whatsapp Number', 'gethalal-mailer' ); ?>:</th>
                                    <td>
                                        <input id="phone" type="tel" class="gc-form-field" name="phone" required/><br />
                                        <p class="description"><?php esc_html_e( "Enter whatsapp number", 'gethalal-mailer' ); ?></p>
                                        <script>
                                            const phoneInputField = document.querySelector("#phone");
                                            var phoneInput = window.intlTelInput(phoneInputField, {
                                                initialCountry: 'de',
                                                separateDialCode: true,
                                                utilsScript:
                                                    "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
                                            });
                                            var initTelValue = "<?php echo isset( $supplier['phone_number'] ) ? esc_attr( $supplier['phone_number'] ) : ''; ?>";
                                            if(initTelValue){
                                                phoneInput.setNumber(initTelValue);
                                            }
                                            console.log('init tel number', initTelValue);
                                            function saveSupplier(event){
                                                event.preventDefault();
                                                const phoneNumber = phoneInput.getNumber();
                                                if(!phoneInput.isValidNumber()){
                                                    console.log('Invalid phoneNumber', phoneNumber);
                                                    alert('Invalid PhoneNumber');
                                                    return;
                                                }
                                                const form = document.forms["gethmailer_supplier_form"];
                                                form["phone_number"].value = phoneNumber;
                                                form.submit();
                                                console.log('phoneNumber', phoneNumber);
                                            }
                                        </script>
                                    </td>
                                </tr>
                            </table>
                            <p class="submit">
                                <input type="submit" id="gethmailer_supplier_form-submit" class="button-primary" value="<?php esc_attr_e( !empty($id)?'Update Supplier':'Add Supplier', 'gethalal-mailer' ); ?>" />
                                <input type="hidden" name="gethmailer_supplier_submit" value="submit" />
                                <?php wp_nonce_field( plugin_basename( __FILE__ ), 'gethmailer_supplier_nonce_name' ); ?>
                            </p>
                        </div><!-- end of inside -->
                    </div><!-- end of postbox -->
                </form>
            </div>
        </div>
    </div>
</div>
