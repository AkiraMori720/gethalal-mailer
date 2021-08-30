<?php

if (!defined('ABSPATH')) {
	exit;
}

const CHAT_API_URL = "https://api.chat-api.com/instance303356/sendMessage?token=a66y8mltdryumq7c";

class GethalalMailer
{

    private static $instance = null;

    public $mailConfig;
    public $opts;

	public $config;
	public $suppliers = [];

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
        $this->fetchData();

        $this->mailConfig = get_option('gethalal_mail_config');
        $this->mailConfig = ! is_array( $this->mailConfig ) ? array() : $this->mailConfig;

        $this->opts = get_option( 'gethalal_smtp_options' );
        $this->opts = ! is_array( $this->opts ) ? array() : $this->opts;
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

	public function cronWork(){
        if ($this->mailConfig && $this->mailConfig['schedule_enabled']) {
            $time = $this->mailConfig['schedule_time']??23;

            try{
                $now = new DateTime('now', new DateTimeZone('Europe/Berlin'));
            } catch (Exception $exception) {
                $now = new DateTime();
            }

            if(intval($now->format('H')) === intval($time)){
                $this->send_notification_for_preprocessing_products();
            }
        }
    }

	public function mail_cron_job(){
		// Schedule Cron Job Event
		if ($this->mailConfig && $this->mailConfig['schedule_enabled']) {
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
        wp_schedule_event( $now->getTimestamp(), 'gethdaily', 'mail_preprocessing_products' );
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

    public function getDeliveryTimes(){
	    return [
	        '0' => 'All From Today',
            '1' => 'Tomorrow',
            '2' => 'Day After Tomorrow'
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

    function fetchData(){
        global $wpdb;
        $select_sql = "SELECT * FROM ". $wpdb->prefix . "gethmailer_configs Order By priority ASC";
        $configs=$wpdb->get_results($select_sql);

        $this->config = array_map(function($config){
            $result = (array)$config;
            $config_category_ids = explode(",", $result['config']);
            $result['config'] = $config_category_ids;
            return $result;
        }, $configs);

        $select_sql = "SELECT * FROM ". $wpdb->prefix . "gethmailer_suppliers Order By name ASC";
        $suppliers=$wpdb->get_results($select_sql);

        foreach ($suppliers as $supplier){
            $this->suppliers[$supplier->id] = (array)$supplier;
        }
    }

    function setConfig($config){
	    global $wpdb;

	    $configuration = implode($config['config'], ",");
        if($config['id']){
            $wpdb->query("Update ". $wpdb->prefix . "gethmailer_configs" . " set name=\"${config['name']}\", priority=${config['priority']}, order_status=\"${config['order_status']}\",  supplier_id=\"${config['supplier_id']}\", config=\"${configuration}\", updated_at=" . time() . " WHERE id = ${config['id']}");
        } else {
            $wpdb->query("insert into ". $wpdb->prefix . "gethmailer_configs" . " (name, priority, order_status, supplier_id, config) values ('" . $config['name'] . "', " . $config['priority'] . ", '" . $config['order_status'] . "', " . $config['supplier_id'] . ", '" . $configuration . "')");
        }

        $this->fetchData();
    }

    function setSupplier($supplier){
        global $wpdb;

        if($supplier['id']){
            $wpdb->query("Update ". $wpdb->prefix . "gethmailer_suppliers" . " set name=\"${supplier['name']}\", phone_number=\"${supplier['phone_number']}\" WHERE id = ${supplier['id']}");
        } else {
            $wpdb->query("insert into ". $wpdb->prefix . "gethmailer_suppliers" . " (name, phone_number) values ('" . $supplier['name'] . "', '" . $supplier['phone_number'] . "')");
        }
    }

    function getMailConfig(){
        return $this->mailConfig;
    }

    function getSuppliers(): array
    {
	    $suppliers = [
	        0 => 'No Selected'
        ];
	    foreach ($this->suppliers as $id => $supplier){
	        $suppliers[$id] = $supplier['name'];
        }
	    return $suppliers;
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


	function sendPdfMessage($key, $phone_number, $subject, $message, $test=false){
        $ret = [];
	    $now = new \DateTime();
	    $curDate = $now->format('Y_m_d');
	    $pdf_file_name = "${key}_${curDate}";

        $upload = wp_upload_dir();
        $upload_dir = $upload['basedir'];
        $upload_url=$upload['baseurl'];
        $upload_dir = $upload_dir.'/'.GETHALAL_MAILER_PLUGIN_NAME;
        $upload_url = $upload_url.'/'.GETHALAL_MAILER_PLUGIN_NAME;
        $file_path=$upload_dir . '/'.$pdf_file_name.'.pdf';
        $file_url=$upload_url . '/'.$pdf_file_name.'.pdf';

        if(!is_dir($upload_dir))
        {
            @mkdir($upload_dir, 0755);
        }

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-type" content="text/html; charset=UTF-8">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Amiri&display=swap"> 

    <style type="text/css">
        body{
            font-family: Amiri, DeJaVu Sans, monospace;
        }
    </style>
</head>
<body>'. $message . '<br/>';


        try {
            $pdf_obj = new GM_Dompdf();
            $pdf_obj->generate($upload_dir, $html, $file_path);

            $curl = curl_init();

            $phone_number = str_replace('+', '', $phone_number);

            curl_setopt_array($curl, array(
                CURLOPT_URL => CHAT_API_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array(
                    'phone' => $phone_number,
                    'previewBase64' => 'data:image/webp;base64,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',
                    'title' => "Gethalal_${subject}_" . $now->format('Y_m_d'),
                    'body' => $file_url
                )
            ));

            $response = curl_exec($curl);
            curl_close($curl);
        } catch (Exception $e){
            $ret['error'] = $e->getMessage();
        }
        return $ret;
    }


	///////////////////////////////////////////////////////////////////////////////////////////////

	function send_notification_for_preprocessing_products($fromSchedule = true){
        $this->addLog("send notification: " . ($fromSchedule?'from schedule':'from form submit') . '  Time: ' .  date('Y-m-d H:i:s'));

	    $valid_order_status = array_map(function($i){return $i['order_status'];}, $this->config);
	    $page = 1;
	    $processing_orders = [];
	    do{
            $orders = wc_get_orders([
                'type'      => 'shop_order',
                'status'    => array_unique($valid_order_status),
                'page'      => $page,
                'limit'     => 100
            ]);

            foreach ($orders as $order) {
                $order = wc_get_order($order);

                // Check Delivery Time
                if(!$order || !$this->filterOrderByDeliveryTime($order, $this->mailConfig['delivery_time']??"1")){
                    continue;
                }

                foreach ( $order->get_items() as $item ) {
                    if ($item->is_type('line_item')) {

                        $order_id = $order->get_id();
                        $order_status = $order->get_status();
                        foreach ($this->config as $config) {
                            $subject = $config['name'];

                            // Check Order Status
                            $config_order_status = $config['order_status'];
                            $config_order_status = 'wc-' === substr($config_order_status, 0, 3) ? substr($config_order_status, 3) : $config_order_status;
                            if ($order_status != $config_order_status) {
                                continue;
                            }

                            $category_ids = gm_lang_object_ids($config['config'], 'product_cat');
                            $productData = $this->filterProduct($item, $category_ids);
                            if ($productData) {
                                if (!isset($processing_orders[$config['id']])) {
                                    $processing_orders[$config['id']] = [
                                        'subject' => $subject,
                                        'supplier' => $this->suppliers[$config['supplier_id']]??null,
                                        'content' => []
                                    ];
                                }
                                if(!isset($processing_orders[$config['id']]['content'][$order_id])) {
                                    $processing_orders[$config['id']]['content'][$order_id] = [];
                                }

                                $processing_orders[$config['id']]['content'][$order_id][] = $productData;
                            }
                        }
                    }
                }
            }
            $page++;
        } while (count($orders) == 100);

        //test
        //$this->addLog("filter orders: " . json_encode($processing_orders));

        $to=$this->mailConfig['gethmailer_to']??'';
        $isWhatsappMode = $this->mailConfig['mode'] && $this->mailConfig['mode'] == 'yes';

		$errors=[];
		foreach ($processing_orders as $key => $data){

            if($isWhatsappMode && $data['supplier']){
                $content = $this->buildMailContent($data['content'], true);
                $phone_number = $data['supplier']['phone_number'];
                $result = $this->sendPdfMessage($key, $phone_number, $data['subject'], $content);
            } else {
                // SendMail
                $content = $this->buildMailContent($data['content']);
                $result = $this->sendMail($to, $data['subject'], $content);
            }
             // Success
             if(!isset($result['error'])){
                 $message = sprintf("Sent Mail to %s, Subject: %s", $to, $data['subject']);
                 //$this->addLog($message);
             // Failure
             } else {
                 $errors['error'] .= $result['error'] . PHP_EOL;
                 //$this->addLog($result['error']);
             }
        }
		return $errors;
	}

    /**
     * @param WC_Order $order
     * @return bool
     */
	function filterOrderByDeliveryTime(WC_Order $order, $deliveryTime): bool
    {
        $_delivery_date = get_post_meta($order->get_id(), '_delivery_date', true);

        if(empty($_delivery_date)){ return false; }
        $delivery_date = (new DateTime())->setTimestamp($_delivery_date)->format('Y-m-d');

        //test
//        $this->addLog(sprintf(
//            "delivery_date: %s => OrderId: %s Customer: %s %s OrderStatus: %s Total: €%.2f",
//            $delivery_date, $order->get_id(), $order->get_billing_first_name(), $order->get_billing_last_name(), $order->get_status(), $order->get_total()
//        ));

        switch ($deliveryTime){
            case "0":
                $target_date = (new DateTime())->setTimezone(new DateTimeZone('Europe/Berlin'))->format('Y-m-d');
                // Next Next Delivery Order
                if($delivery_date < $target_date){ return false; }
                break;
            case "1":
                $target_date = (new DateTime())->add(new DateInterval("P1D"))->setTimezone(new DateTimeZone('Europe/Berlin'))->format('Y-m-d');
                // Next Next Delivery Order
                if($delivery_date != $target_date){ return false; }
                break;
            case "2":
                $target_date = (new DateTime())->add(new DateInterval("P2D"))->setTimezone(new DateTimeZone('Europe/Berlin'))->format('Y-m-d');
                // Next Next Delivery Order
                if($delivery_date != $target_date){ return false; }
                break;
        }

        //test
//        $this->addLog(sprintf(
//            "SecondDayOrder: OrderId: %s Delivery: %s Customer: %s %s OrderStatus: %s Total: €%.2f",
//            $order->get_id(), $delivery_date, $order->get_billing_first_name(), $order->get_billing_last_name(), $order->get_status(), $order->get_total()
//        ));
        return true;
    }


    /**
     * @param WC_Order_Item_Product $order_item
     * @param $allow_category_ids
     * @return bool|array
     */
	function filterProduct(WC_Order_Item_Product $order_item, $allow_category_ids)
    {
        /** @var WC_Product $product */
        $product = $order_item->get_product();
        $quantity = $order_item->get_quantity();
        if ($product) {

            $category_ids = $product->get_category_ids();
            $category_ids = gm_lang_object_ids($category_ids, 'product_cat');

            if (!$this->allowCategory($category_ids, $allow_category_ids)) {
                return false;
            }

            $product_weight = $product->get_weight();
            $all_weight = "${product_weight} * ${quantity}";

            $english_product_id = apply_filters( 'wpml_object_id', $product->get_id(), 'product', false, 'en' );
            $english_product = wc_get_product( $english_product_id );
            return [
                'weight' => $all_weight,
                'product_id' => $product->get_id(),
                'product_name' => $product->get_title(),
                'en_product_name' => $english_product?$english_product->get_title():$product->get_title()
            ];
        }
		return false;
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
    function buildMailContent($orders, $pagination = false){
        $per_page_rows = 0;
        if($pagination){
            $per_page_rows = 20;
        }

        $content = "";

        $header = "<table border='1'>
                        <tr>
                            <th>No</th>
                            <th>Order ID</th>
                            <th>Product Name</th>
                            <th>Weight</th>
                        </tr>
                        ";
        $footer = "</table> <div style='page-break-before:always;'></div>";

        $row_count = 0;
        $no = 0;
        foreach ($orders as $id => $products){
            $no++;
            $rows = count($products);
            foreach ($products as $index => $product){
                if(!$row_count){
                    $content .= $header;
                }

                $body = "<tr>";
                if(!$row_count || !$index){
                    $row_span = $per_page_rows > 0 && ($rows > ($per_page_rows - $row_count))?($per_page_rows - $row_count):$rows;
                    $rows = $per_page_rows > 0 && ($rows > ($per_page_rows - $row_count))? ($rows - $per_page_rows + $row_count):$rows;

                    $body .= "<td rowspan=\"${row_span}\">${no}</td>
                                <td rowspan=\"${row_span}\">#${id}</td>";
                }
                $product_name = $product['product_name'];
                $body .= "<td>${product_name}</td>
                            <td align=\"right\">${product['weight']}</td>";
                $body .= "</tr>";

                $row_count++;
                $content .= $body;
                if($per_page_rows > 0 && $per_page_rows == $row_count){
                    $content .= $footer;
                    $row_count = 0;
                }
            }
        }
        $content .= $footer;

	    return $content;
    }
}
