<?php
    require_once( GETHALAL_MAILER_PLUGIN_DIR . '/class/class-gm-cf-list-table.php' );

    $gethalal_mailer = GethalalMailer::instance();
    $gethalal_config = $gethalal_mailer->getMailConfig();
	$gethmailer_options = $gethalal_mailer->getMailSetting();
    $smtp_test_mail = $gethalal_mailer->getTestMail();

    $message = '';
	$error   = '';

	// Test Mails
    if( isset($_POST['gethmailer_mails_submit'])){
        $result = $gethalal_mailer->send_mail_preprocessing_products();
        //$result = $gethalal_mailer->mail_cron_job();
        if(isset($result['error'])){
            $error .= $result['error'];
        } else {
            $message .= __( 'Proceed action successfully.', 'gethalal-mailer' );
        }
    }
    // Submit Mailer Config
    else if( isset( $_POST['gethmailer_config_submit'])) {
        $gethalal_config['mode'] = (isset($_POST['gethmailer_mode'])) ? $_POST['gethmailer_mode'] : 'no';

        if (isset($_POST['gethmailer_to'])) {
            if (is_email($_POST['gethmailer_to'])) {
                $gethalal_config['gethmailer_to'] = sanitize_email($_POST['gethmailer_to']);
            } else {
                $error .= ' ' . __("Please enter a valid email address in the 'To' field.", 'gethalal-mailer');
            }
        }

        if(isset($_POST['gethmailer_schedule'])){
            if($gethalal_config['schedule_time'] != $_POST['gethmailer_schedule_time']){
                $gethalal_mailer->reScheduleWorking($_POST['gethmailer_schedule_time']);
            }
            $gethalal_config['schedule_enabled'] = 1;
        } else {
            $gethalal_mailer->unScheduleWorking();
            $gethalal_config['schedule_enabled'] = 0;
        }

        $gethalal_config['schedule_time'] = $_POST['gethmailer_schedule_time'];
        $gethalal_config['delivery_time'] = $_POST['gethmailer_delivery_time'];

        /* Update settings in the database */
        if (empty($error)) {
            $gethalal_mailer->setMailConfig($gethalal_config);
            $message .= __('Config saved.', 'gethalal-mailer');
        } else {
            $error .= ' ' . __('Config are not saved.', 'gethalal-mailer');
        }
    }
    // Submit SMTP Settings
    else if ( isset( $_POST['gethmailer_form_submit'] ) ) {
        if (!check_admin_referer(plugin_basename(__FILE__), 'gethmailer_nonce_name')) {
            $error .= ' ' . __('Nonce check failed.', 'gethalal-mailer');
        }

        if (isset($_POST['gethmailer_from_email'])) {
            if (is_email($_POST['gethmailer_from_email'])) {
                $gethmailer_options['from_email_field'] = sanitize_email($_POST['gethmailer_from_email']);
            } else {
                $error .= ' ' . __("Please enter a valid email address in the 'FROM' field.", 'gethalal-mailer');
            }
        }

        $gethmailer_options['from_name_field'] = isset($_POST['gethmailer_from_name']) ? sanitize_text_field(wp_unslash($_POST['gethmailer_from_name'])) : '';
        $gethmailer_options['smtp_settings']['host'] = stripslashes($_POST['gethmailer_smtp_host']);
        $gethmailer_options['smtp_settings']['type_encryption'] = (isset($_POST['gethmailer_smtp_type_encryption'])) ? sanitize_text_field($_POST['gethmailer_smtp_type_encryption']) : 'none';
        $gethmailer_options['smtp_settings']['autentication'] = (isset($_POST['gethmailer_smtp_autentication'])) ? sanitize_text_field($_POST['gethmailer_smtp_autentication']) : 'yes';
        $gethmailer_options['smtp_settings']['username'] = stripslashes($_POST['gethmailer_smtp_username']);
        $gethmailer_options['smtp_settings']['password'] = stripslashes($_POST['gethmailer_smtp_password']);

        if (isset($_POST['gethmailer_smtp_port'])) {
            if (empty($_POST['gethmailer_smtp_port']) || 1 > intval($_POST['gethmailer_smtp_port']) || (!preg_match('/^\d+$/', $_POST['gethmailer_smtp_port']))) {
                $gethmailer_options['smtp_settings']['port'] = '25';
                $error .= ' ' . __("Please enter a valid port in the 'SMTP Port' field.", 'gethalal-mailer');
            } else {
                $gethmailer_options['smtp_settings']['port'] = sanitize_text_field($_POST['gethmailer_smtp_port']);
            }
        }

        /* Update settings in the database */
        if (empty($error)) {
            $gethalal_mailer->setMailSetting($gethmailer_options);
            $message .= __('Settings saved.', 'gethalal-mailer');
        } else {
            $error .= ' ' . __('Settings are not saved.', 'gethalal-mailer');
        }
    }
    // Send Test Mail
    else if (isset($_POST['gethmailer_test_submit'])){

        $gethmailer_test_to = '';
        if ( isset( $_POST['gethmailer_test_to'] ) ) {
			$to_email = sanitize_text_field( $_POST['gethmailer_test_to'] );
			if ( is_email( $to_email ) ) {
				$gethmailer_test_to = $to_email;
			} else {
				$error .= __( 'Please enter a valid email address in the recipient email field.', 'gethalal-mailer' );
			}
		}

        $gethmailer_test_subject = isset( $_POST['gethmailer_test_subject'] ) ? sanitize_text_field( $_POST['gethmailer_test_subject'] ) : '';
		$gethmailer_test_message = isset( $_POST['gethmailer_test_message'] ) ? sanitize_textarea_field( $_POST['gethmailer_test_message'] ) : '';

		//Save the test mail details so it doesn't need to be filled in everytime.
        $smtp_test_mail = array();
		$smtp_test_mail['gethmailer_to']      = $gethmailer_test_to;
		$smtp_test_mail['gethmailer_subject'] = $gethmailer_test_subject;
		$smtp_test_mail['gethmailer_message'] = $gethmailer_test_message;
		$gethalal_mailer->setTestMail( $smtp_test_mail );

		if ( ! empty( $gethmailer_test_to ) ) {
			$test_res = $gethalal_mailer->sendMail( $gethmailer_test_to, $gethmailer_test_subject, $gethmailer_test_message, true );
		}
    }

    $schedule_working = $gethalal_mailer->getScheduleWorking();
    $uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
    $action_url = $uri_parts[0];
?>

<div class="wrap" id="swpsmtp-mail">
	<h2><?php esc_html_e( 'Mailer Setting', 'gethalal-mailer' ); ?></h2>

    <div class="updated fade" <?php echo empty( $message ) ? ' style="display:none"' : ''; ?>>
		<p><strong><?php echo esc_html( $message ); ?></strong></p>
	</div>
	<div class="error" <?php echo empty( $error ) ? 'style="display:none"' : ''; ?>>
		<p><strong><?php echo esc_html( $error ); ?></strong></p>
	</div>

    <div class="nav-tab-wrapper gethmailer-tab-wrapper">
        <a href="#product" data-tab-name="product" class="nav-tab"><?php esc_html_e( 'Preprocessing Config', 'gethalal-mailer' ); ?></a>
        <a href="#smtp" data-tab-name="smtp" class="nav-tab"><?php esc_html_e( 'SMTP Settings', 'gethalal-mailer' ); ?></a>
        <a href="#testemail" data-tab-name="testemail" class="nav-tab"><?php esc_html_e( 'Test Email', 'gethalal-mailer' ); ?></a>
        <a href="#suppliers" data-tab-name="suppliers" class="nav-tab"><?php esc_html_e( 'Suppliers List', 'gethalal-mailer' ); ?></a>
    </div>

    <div class="gethmailer-settings-container">
        <div class="gethmailer-settings-grid gethmailer-settings-main-cont">

            <input type="hidden" id="gethmailer_urlHash" name="gethmailer_urlHash" value="">

            <div class="gethmailer-tab-container" data-tab-name="product">
                <div class="postbox">
                    <form autocomplete="off" id="gethmailer_config_form" method="post" action="">
                        <h3 class="hndle"><label for="title"><?php esc_html_e( 'Preprocessing Config', 'gethalal-mailer' ); ?></label></h3>
                        <div class="inside">
                            <table class="form-table">
                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e( 'Mode', 'gethalal-mailer' ); ?></th>
                                    <td>
                                        <label for="gethmailer_mode"><input type="radio" id="gethmailer_mode_1" name="gethmailer_mode" value='no'
                                                <?php
                                                if ( !isset( $gethalal_config['mode'] ) || 'no' === $gethalal_config['mode'] ) {
                                                    echo 'checked="checked"';}
                                                ?>
                                            /> <?php esc_html_e( 'Send Email', 'gethalal-mailer' ); ?></label>
                                        <label for="gethmailer_mode" style="margin-left: 12px"><input type="radio" id="gethmailer_mode_2" name="gethmailer_mode" value='yes'
                                                <?php
                                                if ( isset( $gethalal_config['mode'] ) && 'yes' === $gethalal_config['mode'] ) {
                                                    echo 'checked="checked"';}
                                                ?>
                                            /> <?php esc_html_e( 'Send Whatsapp Message To Suppliers', 'gethalal-mailer' ); ?></label><br />
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e( 'To EmailAddress', 'gethalal-mailer' ); ?>:</th>
                                    <td>
                                        <input id="gethmailer_to" type="text" class="gc-form-field" name="gethmailer_to" value="<?php echo isset( $gethalal_config['gethmailer_to'] ) ? esc_attr( $gethalal_config['gethmailer_to'] ) : ''; ?>" /><br />
                                        <p class="description"><?php esc_html_e( "Enter the recipient's email address", 'gethalal-mailer' ); ?></p>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e( 'Order Filter', 'gethalal-mailer' ); ?>:</th>
                                    <td>
                                        <?php
                                        $deliveryTimes = $gethalal_mailer->getDeliveryTimes();
                                        $output = '<select class="order-delivery-time" id="gethmailer_delivery_time" name="gethmailer_delivery_time">';
                                        foreach( $deliveryTimes as $key => $deliveryTime ) {
                                            $output.= '<option value="'. esc_attr( $key ) .'" ' .((isset($gethalal_config['delivery_time']) && $key==$gethalal_config['delivery_time'])?'selected':''). '>'. esc_attr( $deliveryTime ).'</option>';
                                        }
                                        $output.='</select>';
                                        echo $output;
                                        ?>
                                        <p class="description"><?php esc_html_e( "Select target order`s delivery time", 'gethalal-mailer' ); ?></p>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e( 'Mail Config', 'gethalal-mailer' ); ?>:</th>
                                    <td>
                                        <a class="add-config-btn" href="<?PHP echo (empty($_SERVER['HTTPS'])?"http://":"https://") . $_SERVER['HTTP_HOST'] . $action_url . '?page=gm_mailer_config' ; ?>">Add New</a>
                                        <?php
                                            $pc_list_table = new GM_CF_List_Table();
                                            $pc_list_table->prepare_items();
                                            $pc_list_table->display();
                                        ?>
                                        <p class="description"><?php esc_html_e( "Select product categories for preprocessing orders", 'gethalal-mailer' ); ?></p>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e( 'Scheduler Time', 'gethalal-mailer' ); ?>:</th>
                                    <td>
                                        <?php
                                            $scheduleTimes = $gethalal_mailer->getScheduleTimes();
                                            $output = '<select class="schedule-time" id="gethmailer_schedule_time" name="gethmailer_schedule_time">';
                                            foreach( $scheduleTimes as $key => $scheduleTime ) {
                                                $output.= '<option value="'. esc_attr( $key ) .'" ' .((isset($gethalal_config['schedule_time']) && $key==$gethalal_config['schedule_time'])?'selected':''). '>'. esc_attr( $scheduleTime ).'</option>';
                                            }
                                            $output.='</select>';
                                            echo $output;
                                        ?>
                                        <p class="description"><?php esc_html_e( "Select Time for preprocessing schedule (Berlin Timezone)", 'gethalal-mailer' ); ?></p>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e( 'Schedule Enabled', 'gethalal-mailer' ); ?>:</th>
                                    <td>
                                        <input id="gethmailer_schedule" type="checkbox" class="gc-form-field" name="gethmailer_schedule" <?php echo (isset($gethalal_config['schedule_enabled'])&&$gethalal_config['schedule_enabled']) ? 'checked' : ''; ?> /><br />
                                        <p class="description">
                                            <?php
                                            if($schedule_working){
                                                $schedule_time = new DateTime('now', new DateTimeZone('Europe/Berlin'));
                                                $schedule_time->setTimestamp($schedule_working);
                                                echo "Schedule is working. Next Schedule Time: ". $schedule_time->format('D, Y-m-d H:i:s') . " (Berlin Timezone)";
                                            } else {
                                                esc_html_e( "Not Scheduled. Check for starting schedule.", 'gethalal-mailer' );
                                            } ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <p class="submit">
                                <input type="submit" id="gethmailer_config-form-submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'gethalal-mailer' ); ?>" />
                                <input type="hidden" name="gethmailer_config_submit" value="submit" />
                                <?php wp_nonce_field( plugin_basename( __FILE__ ), 'gethmailer_config_nonce_name' ); ?>
                            </p>
                        </div><!-- end of inside -->
                    </form>
                    <form autocomplete="off" id="gethmailer_mails_form" method="post" action="" style="text-align: right; padding: 8px">
                        <input type="submit" id="gethmailer_mails-form-submit" class="button-primary" value="<?php esc_attr_e( 'Test Action', 'gethalal-mailer' ); ?>" />
                        <input type="hidden" name="gethmailer_mails_submit" value="submit" />
                        <?php wp_nonce_field( plugin_basename( __FILE__ ), 'gethmailer_mails_nonce_name' ); ?>
                    </form>
                </div><!-- end of postbox -->
            </div>

            <div class="gethmailer-tab-container" data-tab-name="smtp">
                <form autocomplete="off" id="gethmailer_settings_form" method="post" action="">
                <div class="postbox">
                    <h3 class="hndle"><label for="title"><?php esc_html_e( 'SMTP Configuration Settings', 'gethalal-mailer' ); ?></label></h3>
                    <div class="inside">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"><?php esc_html_e( 'From Email Address', 'gethalal-mailer' ); ?></th>
                                <td>
                                    <input id="gethmailer_from_email" type="text" name="gethmailer_from_email" value="<?php echo isset( $gethmailer_options['from_email_field'] ) ? esc_attr( $gethmailer_options['from_email_field'] ) : ''; ?>" /><br />
                                    <p class="description"><?php esc_html_e( "This email address will be used in the 'From' field.", 'gethalal-mailer' ); ?></p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php esc_html_e( 'From Name', 'gethalal-mailer' ); ?></th>
                                <td>
                                    <input id="gethmailer_from_name" type="text" name="gethmailer_from_name" value="<?php echo isset( $gethmailer_options['from_name_field'] ) ? esc_attr( $gethmailer_options['from_name_field'] ) : ''; ?>" /><br />
                                    <p class="description"><?php esc_html_e( "This text will be used in the 'FROM' field", 'gethalal-mailer' ); ?></p>
                                </td>
                            </tr>
                            <tr class="ad_opt gethmailer_smtp_options">
                                <th><?php esc_html_e( 'SMTP Host', 'gethalal-mailer' ); ?></th>
                                <td>
                                    <input id='gethmailer_smtp_host' type='text' name='gethmailer_smtp_host' value='<?php echo isset( $gethmailer_options['smtp_settings']['host'] ) ? esc_attr( $gethmailer_options['smtp_settings']['host'] ) : ''; ?>' /><br />
                                    <p class="description"><?php esc_html_e( 'Your mail server', 'gethalal-mailer' ); ?></p>
                                </td>
                            </tr>
                            <tr class="ad_opt gethmailer_smtp_options">
                                <th><?php esc_html_e( 'Type of Encryption', 'gethalal-mailer' ); ?></th>
                                <td>
                                    <label for="gethmailer_smtp_type_encryption_1"><input type="radio" id="gethmailer_smtp_type_encryption_1" name="gethmailer_smtp_type_encryption" value='none'
                                    <?php
                                    if ( isset( $gethmailer_options['smtp_settings']['type_encryption'] ) && 'none' === $gethmailer_options['smtp_settings']['type_encryption'] ) {
                                        echo 'checked="checked"';}
                                    ?>
                                    /> <?php esc_html_e( 'None', 'gethalal-mailer' ); ?></label>
                                    <label for="gethmailer_smtp_type_encryption_2"><input type="radio" id="gethmailer_smtp_type_encryption_2" name="gethmailer_smtp_type_encryption" value='ssl'
                                    <?php
                                    if ( isset( $gethmailer_options['smtp_settings']['type_encryption'] ) && 'ssl' === $gethmailer_options['smtp_settings']['type_encryption'] ) {
                                        echo 'checked="checked"';}
                                    ?>
                                    /> <?php esc_html_e( 'SSL/TLS', 'gethalal-mailer' ); ?></label>
                                    <label for="gethmailer_smtp_type_encryption_3"><input type="radio" id="gethmailer_smtp_type_encryption_3" name="gethmailer_smtp_type_encryption" value='tls'
                                    <?php
                                    if ( isset( $gethmailer_options['smtp_settings']['type_encryption'] ) && 'tls' === $gethmailer_options['smtp_settings']['type_encryption'] ) {
                                        echo 'checked="checked"';}
                                    ?>
                                    /> <?php esc_html_e( 'STARTTLS', 'gethalal-mailer' ); ?></label><br />
                                    <p class="description"><?php esc_html_e( 'For most servers SSL/TLS is the recommended option', 'gethalal-mailer' ); ?></p>
                                </td>
                            </tr>
                            <tr class="ad_opt gethmailer_smtp_options">
                                <th><?php esc_html_e( 'SMTP Port', 'gethalal-mailer' ); ?></th>
                                <td>
                                    <input id='gethmailer_smtp_port' type='text' name='gethmailer_smtp_port' value='<?php echo isset( $gethmailer_options['smtp_settings']['port'] ) ? esc_attr( $gethmailer_options['smtp_settings']['port'] ) : ''; ?>' /><br />
                                    <p class="description"><?php esc_html_e( 'The port to your mail server', 'gethalal-mailer' ); ?></p>
                                </td>
                            </tr>
                            <tr class="ad_opt gethmailer_smtp_options">
                                <th><?php esc_html_e( 'SMTP Authentication', 'gethalal-mailer' ); ?></th>
                                <td>
                                    <label for="gethmailer_smtp_autentication"><input type="radio" id="gethmailer_smtp_autentication_1" name="gethmailer_smtp_autentication" value='no'
                                    <?php
                                    if ( isset( $gethmailer_options['smtp_settings']['autentication'] ) && 'no' === $gethmailer_options['smtp_settings']['autentication'] ) {
                                        echo 'checked="checked"';}
                                    ?>
                                    /> <?php esc_html_e( 'No', 'gethalal-mailer' ); ?></label>
                                    <label for="gethmailer_smtp_autentication"><input type="radio" id="gethmailer_smtp_autentication_2" name="gethmailer_smtp_autentication" value='yes'
                                    <?php
                                    if ( isset( $gethmailer_options['smtp_settings']['autentication'] ) && 'yes' === $gethmailer_options['smtp_settings']['autentication'] ) {
                                        echo 'checked="checked"';}
                                    ?>
                                    /> <?php esc_html_e( 'Yes', 'gethalal-mailer' ); ?></label><br />
                                    <p class="description"><?php esc_html_e( "This options should always be checked 'Yes'", 'gethalal-mailer' ); ?></p>
                                </td>
                            </tr>
                            <tr class="ad_opt gethmailer_smtp_options">
                                <th><?php esc_html_e( 'SMTP Username', 'gethalal-mailer' ); ?></th>
                                <td>
                                    <input id='gethmailer_smtp_username' type='text' name='gethmailer_smtp_username' value='<?php echo isset( $gethmailer_options['smtp_settings']['username'] ) ? esc_attr( $gethmailer_options['smtp_settings']['username'] ) : ''; ?>' /><br />
                                    <p class="description"><?php esc_html_e( 'The username to login to your mail server', 'gethalal-mailer' ); ?></p>
                                </td>
                            </tr>
                            <tr class="ad_opt gethmailer_smtp_options">
                                <th><?php esc_html_e( 'SMTP Password', 'gethalal-mailer' ); ?></th>
                                <td>
                                    <input id="gethmailer_smtp_password" type="password" name="gethmailer_smtp_password" value="<?php echo isset( $gethmailer_options['smtp_settings']['password'] ) ? esc_attr( $gethmailer_options['smtp_settings']['password'] ) : ''; ?>" autocomplete="new-password" /><br />
                                    <p class="description"><?php echo esc_html( __( 'The password to login to your mail server', 'gethalal-mailer' ) ); ?></p>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" id="mail-settings-form-submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'gethalal-mailer' ); ?>" />
                            <input type="hidden" name="gethmailer_form_submit" value="submit" />
                            <?php wp_nonce_field( plugin_basename( __FILE__ ), 'gethmailer_nonce_name' ); ?>
                        </p>
                    </div><!-- end of inside -->
                </div><!-- end of postbox -->
                </form>
            </div>

            <div class="gethmailer-tab-container" data-tab-name="testemail">
                <div class="postbox">
                    <h3 class="hndle"><label for="title"><?php esc_html_e( 'Test Email', 'gethalal-mailer' ); ?></label></h3>
                    <div class="inside">
                        <?php
                        if ( isset( $test_res ) && is_array( $test_res ) ) {
                            if ( isset( $test_res['error'] ) ) {
                                $errmsg_class = ' msg-error';
                                $errmsg_text  = '<b>' . esc_html__( 'Following error occurred when attempting to send test email:', 'gethalal-mailer' ) . '</b><br />' . esc_html( $test_res['error'] );
                            } else {
                                $errmsg_class = ' msg-success';
                                $errmsg_text  = '<b>' . esc_html__( 'Test email was successfully sent. No errors occurred during the process.', 'gethalal-mailer' ) . '</b>';
                            }
                            ?>

                            <div class="gethmailer_msg-cont<?php echo esc_attr( $errmsg_class ); ?>">
                                <?php echo $errmsg_text; //phpcs:ignore?>

                                <?php
                                if ( isset( $test_res['debug_log'] ) ) {
                                    ?>
                                    <br /><br />
                                    <a id="gethmailer_show-hide-log-btn" href="#0"><?php esc_html_e( 'Show Debug Log', 'gethalal-mailer' ); ?></a>
                                    <p id="gethmailer_debug-log-cont"><textarea rows="20" style="width: 100%;"><?php echo esc_html( $test_res['debug_log'] ); ?></textarea></p>
                                    <script>
                                        jQuery(function($) {
                                            $('#gethmailer_show-hide-log-btn').click(function(e) {
                                                e.preventDefault();
                                                var logCont = $('#gethmailer_debug-log-cont');
                                                if (logCont.is(':visible')) {
                                                    $(this).html('<?php esc_attr_e( 'Show Debug Log', 'gethalal-mailer' ); ?>');
                                                } else {
                                                    $(this).html('<?php esc_attr_e( 'Hide Debug Log', 'gethalal-mailer' ); ?>');
                                                }
                                                logCont.toggle();
                                            });
                                            <?php
                                            if ( isset( $test_res['error'] ) ) {
                                                ?>
                                                $('#gethmailer_show-hide-log-btn').click();
                                                <?php
                                            }
                                            ?>
                                        });
                                    </script>
                                    <?php
                                }
                                ?>
                            </div>
                            <?php
                        }
                        ?>

                        <form id="gethmailer_settings_test_email_form" method="post" action="">
                            <table class="form-table">
                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e( 'To', 'gethalal-mailer' ); ?>:</th>
                                    <td>
                                        <input id="gethmailer_test_to" type="text" class="ignore-change" name="gethmailer_test_to" value="<?php echo esc_html( $smtp_test_mail['gethmailer_to'] ); ?>" /><br />
                                        <p class="description"><?php esc_html_e( "Enter the recipient's email address", 'gethalal-mailer' ); ?></p>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e( 'Subject', 'gethalal-mailer' ); ?>:</th>
                                    <td>
                                        <input id="gethmailer_test_subject" type="text" class="ignore-change" name="gethmailer_test_subject" value="<?php echo esc_html( $smtp_test_mail['gethmailer_subject'] ); ?>" /><br />
                                        <p class="description"><?php esc_html_e( 'Enter a subject for your message', 'gethalal-mailer' ); ?></p>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php esc_html_e( 'Message', 'gethalal-mailer' ); ?>:</th>
                                    <td>
                                        <textarea name="gethmailer_test_message" id="gethmailer_test_message" rows="5"><?php echo esc_textarea( stripslashes( $smtp_test_mail['gethmailer_message'] ) ); ?></textarea><br />
                                        <p class="description"><?php esc_html_e( 'Write your email message', 'gethalal-mailer' ); ?></p>
                                    </td>
                                </tr>
                            </table>
                            <p class="submit">
                                <input type="submit" id="test-email-form-submit" class="button-primary" value="<?php esc_attr_e( 'Send Test Email', 'gethalal-mailer' ); ?>" />
                                <input type="hidden" name="gethmailer_test_submit" value="submit" />
                                <?php wp_nonce_field( plugin_basename( __FILE__ ), 'gethmailer_test_nonce_name' ); ?>
                                <span id="gethmailer_spinner" class="spinner"></span>
                            </p>
                        </form>
                    </div><!-- end of inside -->
                </div><!-- end of postbox -->

            </div>

            <div class="gethmailer-tab-container" data-tab-name="suppliers">
                <div class="postbox" id="gethmailer_suppliers_form">
                    <h3 class="hndle"><label for="title"><?php esc_html_e( 'Suppliers List', 'gethalal-mailer' ); ?></label></h3>
                    <div class="inside">
                        <table class="form-table">
                            <tr valign="top">
                                <td>
                                    <a class="add-config-btn" href="<?PHP echo (empty($_SERVER['HTTPS'])?"http://":"https://") . $_SERVER['HTTP_HOST'] . $action_url . '?page=gm_mailer_supplier' ; ?>">Add New</a>
                                    <?php
                                        $sp_list_table = new GM_SP_List_Table();
                                        $sp_list_table->prepare_items();
                                        $sp_list_table->display();
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div><!-- end of postbox -->
            </div>

        </div>
    </div>
</div>
