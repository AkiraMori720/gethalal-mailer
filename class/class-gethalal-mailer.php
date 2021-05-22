<?php

if (!defined('ABSPATH')) {
	exit;
}

class GethalalMailer
{

    private static $instance = null;

    public $mailConfig;
    public $opts;

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

        $this->mailConfig = get_option('gethalal_mail_config');
        $this->mailConfig = ! is_array( $this->mailConfig ) ? array() : $this->mailConfig;

        $this->opts = get_option( 'gethalal_smtp_options' );
        $this->opts = ! is_array( $this->opts ) ? array() : $this->opts;

        // Disable Auto schedule
        add_action( 'wp', array($this, 'mail_cron_job'));
        add_action( 'mail_preprocessing_products', array($this, 'send_mail_preprocessing_products'));
    }

	public function credentials_configured() {
		$credentials_configured = true;
		if ( ! isset( $this->opts['from_email_field'] ) || empty( $this->opts['from_email_field'] ) ) {
			$credentials_configured = false;
		}
		if ( ! isset( $this->opts['from_name_field'] ) || empty( $this->opts['from_name_field'] ) ) {
			$credentials_configured = false;
		}
		return $credentials_configured;
	}

	public function mail_cron_job(){
		// Schedule Cron Job Event
		if ($this->mailConfig['schedule_enabled']) {
		    if( !wp_next_scheduled( 'mail_preprocessing_products' )){
                $time = $this->mailConfig['schedule_time']??23;
                $this->scheduleWorking($time);
            }
		} else {
            $this->unScheduleWorking();
        }
	}

    public function scheduleWorking($time){
	    try{
            $now = new DateTime('now', new DateTimeZone('Europe/Berlin'));
        } catch (Exception $exception) {
	        $now = new DateTime();
        }
        $now->setTime($time, 00);
        wp_schedule_event( $now->getTimestamp(), 'daily', 'mail_preprocessing_products' );
    }

	public function reScheduleWorking($time){
	    $this->unScheduleWorking();
	    $this->scheduleWorking($time);
    }
	public function getScheduleWorking(){
	    return wp_next_scheduled( 'mail_preprocessing_products' );
    }

    public function unScheduleWorking(){
       $timestamp = wp_next_scheduled( 'mail_preprocessing_products' );
       if($timestamp){
           var_dump("unschedule",$timestamp);
           wp_unschedule_event($timestamp, 'mail_preprocessing_products');
       }
    }

    public function getScheduleTimes(){
	    return [
	        '0' => '00:00:00 AM',
            '1' => '01:00:00 AM',
            '2' => '02:00:00 AM',
            '3' => '03:00:00 AM',
            '4' => '04:00:00 AM',
            '5' => '05:00:00 AM',
            '6' => '06:00:00 AM',
            '7' => '07:00:00 AM',
            '8' => '08:00:00 AM',
            '9' => '09:00:00 AM',
            '10' => '10:00:00 AM',
            '11' => '11:00:00 AM',
            '12' => '12:00:00 AM',
            '13' => '01:00:00 PM',
            '14' => '02:00:00 PM',
            '15' => '03:00:00 PM',
            '16' => '04:00:00 PM',
            '17' => '05:00:00 PM',
            '18' => '06:00:00 PM',
            '19' => '07:00:00 PM',
            '20' => '08:00:00 PM',
            '21' => '09:00:00 PM',
            '22' => '10:00:00 PM',
            '23' => '11:00:00 PM',
        ];
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
        $select_sql = "SELECT * FROM ". $wpdb->prefix . "gethmailer_configs Order By priority ASC";
        $configs=$wpdb->get_results($select_sql);

        $this->config = array_map(function($config){
            $result = (array)$config;
            $result['config'] = explode(",", $result['config']);
            return $result;
        }, $configs);
    }

    function setConfig($config){
	    global $wpdb;

	    $configuration = implode($config['config'], ",");
        if($config['id']){
            $wpdb->query("Update ". $wpdb->prefix . "gethmailer_configs" . " set name=\"${config['name']}\", priority=${config['priority']}, order_status=\"${config['order_status']}\", config=\"${configuration}\", updated_at=" . time() . " WHERE id = ${config['id']}");
        } else {
            $wpdb->query("insert into ". $wpdb->prefix . "gethmailer_configs" . " (name, priority, order_status, config) values ('" . $config['name'] . "', " . $config['priority'] . ", '" . $config['order_status'] . "', '" . $configuration . "')");
        }

        $this->fetchConfig();
    }

    function getMailConfig(){
        return $this->mailConfig;
    }

    function setMailConfig($mailConfig){
        update_option('gethalal_mail_config', $mailConfig);
        $this->mailConfig = $mailConfig;
    }

    function getMailSetting(){
		return $this->opts;
    }

	function setMailSetting($settings){
		update_option('gethalal_smtp_options', $settings);
		$this->opts = $settings;
	}

    function getTestMail(){
		$test_mail = get_option( 'gethalal_test_mail' );
		if ( empty( $test_mail ) ) {
			$test_mail = array(
				'gethmailer_to'      => '',
				'gethmailer_subject' => '',
				'gethmailer_message' => '',
			);
		}
		return $test_mail;
	}

	function setTestMail($test_mail){
		update_option('gethalal_test_mail', $test_mail);
	}

	function sendMail($to_email, $subject, $message, $test=false){
		$ret = array();
		global $wp_version;

		if ( ! $this->credentials_configured() ) {
			return false;
		}

		if ( version_compare( $wp_version, '5.4.99' ) > 0 ) {
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
			$mail = new PHPMailer\PHPMailer\PHPMailer( true );
		} else {
			require_once ABSPATH . WPINC . '/class-phpmailer.php';
			$mail = new \PHPMailer( true );
		}

		try {

			$charset       = get_bloginfo( 'charset' );
			$mail->CharSet = $charset;

			$mail->IsSMTP();

			// send plain text test email
			$mail->ContentType = 'text/html';
			$mail->IsHTML( true );

			/* If using smtp auth, set the username & password */
			if ( 'yes' === $this->opts['smtp_settings']['autentication'] ) {
				$mail->SMTPAuth = true;
				$mail->Username = $this->opts['smtp_settings']['username'];
				$mail->Password = $this->opts['smtp_settings']['password'];
			}

			/* Set the SMTPSecure value, if set to none, leave this blank */
			if ( 'none' !== $this->opts['smtp_settings']['type_encryption'] ) {
				$mail->SMTPSecure = $this->opts['smtp_settings']['type_encryption'];
			}

			/* PHPMailer 5.2.10 introduced this option. However, this might cause issues if the server is advertising TLS with an invalid certificate. */
			$mail->SMTPAutoTLS = false;

			/* Set the other options */
			$mail->Host = $this->opts['smtp_settings']['host'];
			$mail->Port = $this->opts['smtp_settings']['port'];

			$from_name  = $this->opts['from_name_field'];
			$from_email = $this->opts['from_email_field'];
			$mail->SetFrom( $from_email, $from_name );
			//This should set Return-Path header for servers that are not properly handling it, but needs testing first
			//$mail->Sender		 = $mail->From;
			$mail->Subject = $subject;
			$mail->Body    = $message;
			$mail->AddAddress( $to_email );
			
			global $debug_msg;
			$debug_msg         = '';
			$mail->Debugoutput = function ( $str, $level ) {
				global $debug_msg;
				$debug_msg .= $str;
			};

			$mail->SMTPDebug   = $test?1:0;
			//set reasonable timeout
			$mail->Timeout = 10;

			/* Send mail and return result */
			$mail->Send();
			$mail->ClearAddresses();
			$mail->ClearAllRecipients();
		} catch ( \Exception $e ) {
			$ret['error'] = $mail->ErrorInfo;
		} catch ( \Throwable $e ) {
			$ret['error'] = $mail->ErrorInfo;
		}

		if($test){
			$ret['debug_log'] = $debug_msg;
		}

		return $ret;
	}


	///////////////////////////////////////////////////////////////////////////////////////////////

	function send_mail_preprocessing_products(){
		$orders = wc_get_orders([
			'limit'    => -1
		]);

		$to=$this->mailConfig['gethmailer_to']??'';


		$processing_orders = [];
		foreach ($orders as $order) {
		    $order_status = $order->get_status('edit');
		    foreach ($this->config as $config) {
                $subject = $config['name'];
                $config_order_status = $config['order_status'];
                $config_order_status   = 'wc-' === substr( $config_order_status, 0, 3 ) ? substr( $config_order_status, 3 ) : $config_order_status;
                if($order_status != $config_order_status){
                    continue;
                }

                $orderData = $this->filterOrder($order, $config['config']);
                if ($orderData) {
                    if(!isset($processing_orders[$config['id']])){
                        $processing_orders[$config['id']] = ['subject' => $subject, 'content' => []];
                    }
                    $processing_orders[$config['id']]['content'][] = $orderData;
                    break;
                }
            }
        }

		$errors=[];
		foreach ($processing_orders as $key => $data){
            $content = $this->buildMailContent($data['content']);

            // SendMail
             $result = $this->sendMail($to, $data['subject'], $content);
             // Success
             if(!isset($result['error'])){

             // Failure
             } else {
                 $errors['error'] .= $result['error'] . PHP_EOL;
             }
        }
		return $errors;
	}


    /**
     * @param WC_Order $order
     * @return bool|array
     */
	function filterOrder(WC_Order $order, $allow_category_ids)
    {
        $productData = [];

        $createdAt = $order->get_date_created();
        $order_delivery_date = get_post_meta($order->get_id(), '_delivery_date', true);
        var_dump("delivery_date: " . $order_delivery_date);

		foreach ( $order->get_items() as $item ) {
			if ( $item->is_type( 'line_item' ) ) {

                /** @var WC_Product $product */
				$product = $item->get_product();
				$quantity = $item->get_quantity();
				if($product){
				    $category_ids = $product->get_category_ids();

				    if(!$this->allowCategory($category_ids, $allow_category_ids)){
				        continue;
                    }

				    $product_weight = $product->get_weight();
				    $all_weight = "${product_weight} * ${quantity}";

                    $productData[] = [
                        'weight' => $all_weight,
                        'product_id' => $product->get_id(),
                        'product_name' => $product->get_name(),
                        'product_sku' => $product->get_sku(),
                    ];
                }
			}
		}

		if(empty($productData)){
		    return false;
        }
		return [
		    'order_id' => $order->get_id(),
            'products' => $productData
        ];
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

    /**
     * @param $orders
     * @return string
     */
    function buildMailContent($orders){
	    $content = "<table border='0'>
                        <tr>
                            <th>No</th>
                            <th>Order ID</th>
                            <th>Product Name</th>
                            <th>Product SKU</th>
                            <th>Weight</th>
                        </tr>
                        ";
	    foreach ($orders as $ino => $order){
	        $no = $ino + 1;
	        $rows = count($order['products']);
	        foreach ($order['products'] as $index => $product){
                $content .= "<tr>";

                if($index == 0){
                    $content .= "<td rowspan=\"${rows}\">${no}</td>
                                <td rowspan=\"${rows}\">#${order['order_id']}</td>";
                }
                $content .= "<td>${product['product_name']}</td>
                            <td>${product['product_sku']}</td>
                            <td align=\"right\">${product['weight']}</td>";

	            $content .= "</tr>";
            }
        }
        $content .= "</table>";
	    return $content;
    }
}