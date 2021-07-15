<?php

if (!defined('ABSPATH')) {
	exit;
}

class GethalalDelivery
{

    private static $instance = null;

	public $columns = [];

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
        $columns = get_option('gethalal_ds_columns');
        $this->columns = ! is_array( $columns ) ? array() : $columns;
    }

    function getColumns(){
	    return $this->columns;
    }


    function setColumns($columns){
        update_option('gethalal_ds_columns', $columns);
        $this->columns = $columns;
    }

    function downloadSheet(){
        $sorted_columns = $this->columns;
	    usort($sorted_columns, function($a, $b){
	        return ($a['priority'] - $b['priority']);
        });

	    $sheetData = [];
        $page = 1;
        do {
            $orders = wc_get_orders([
                'type' => 'shop_order',
                'status' => 'wc-processing',
                'page' => $page,
                'limit' => 100
            ]);

            foreach ($orders as $order) {
                $order = wc_get_order($order);
                $sheetData[$order->get_id()] = [
                    'Postcode' => $order->get_shipping_postcode(),
                    'Customer Name' => $order->get_shipping_first_name() . ' ' .  $order->get_shipping_last_name(),
                    'Order' => $order->get_id(),
                    'fields' => [],
                    'Address' => $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2(),
                    'Payment Method' => $order->get_payment_method_title(),
                    'Cost' => ($order->get_payment_method() == 'cod')?(html_entity_decode(get_woocommerce_currency_symbol()) . $order->get_total()):'finished',
                    'Telephone' => $order->get_billing_phone(),
                    'Notes' => $order->get_customer_note(),
                ];
                foreach ( $order->get_items() as $item ) {
                    if ($item->is_type('line_item')) {

                        /** @var WC_Product $product */
                        $product = $item->get_product();

                        if (!$product) {
                            continue;
                        }

                        foreach ($sorted_columns as $column) {
                            $title = $column['name'];
                            $config_category_ids = explode(",", $column['config']);
                            $category_ids = gm_lang_object_ids($config_category_ids, 'product_cat');
                            $productData = GethalalMailer::instance()->filterProduct($item, $category_ids);
                            $sheetData[$order->get_id()]['fields'][$title] = $productData?'Yes':'';
                        }
                    }
                }
            }
            $page++;
        }while(count($orders) == 100);

        $sheetValues = [];

        $category_titles = [];
        foreach ($sorted_columns as $column) {
            $title = $column['name'];
            $category_titles[] = $title;
        }
        $sheetValues[] = array_merge(['Postcode', 'Customer Name', 'Order'], $category_titles, ['Address', 'Payment Method', 'Cost', 'Telephone', 'Notes']);

        $row_no = 1;
        foreach ($sheetData as $row){
            foreach ($row as $key => $value){
                if($key == 'fields'){
                    foreach ($value as $v){
                        $sheetValues[$row_no][] = $v;
                    }
                } else {
                    $sheetValues[$row_no][] = $value;
                }
            }
            $row_no++;
        }

	    return $this->exportDataToExcelWithValues($sheetValues, plugin_dir_path(__DIR__) . 'templates/delivery_plan_template.xlsx');
    }

    function exportDataToExcelWithValues($values, $templateFile) {
        $upload = wp_upload_dir();
        $upload_dir = $upload['basedir'].'/'.GETHALAL_MAILER_PLUGIN_NAME;
        $upload_url=$upload['baseurl'].'/'.GETHALAL_MAILER_PLUGIN_NAME;
        $fileName = "delivery_plan_" . (new DateTime())->format('d_m_Y') . ".xlsx";
        $excel_path = $upload_dir . "/${fileName}";
        $excel_url = $upload_dir . "/${fileName}";
        try{
            $path=plugin_dir_path(__DIR__).'includes/vendor/';
            include_once($path.'autoload.php');

            $spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($templateFile);
            $worksheet = $spreadsheet->getActiveSheet();

            for ($rowNo = 0; $rowNo < count($values); $rowNo ++) {
                $row = $values[$rowNo];

                for ($colNo = 0; $colNo < count($row); $colNo++) {
                    $value = $row[$colNo];

                    $cellName = $this->getExcelColNameFromIndex($colNo) . ($rowNo + 1);

                    $worksheet->setCellValue($cellName, $value);
                }
            }

            if(file_exists($excel_path)) {
                unlink($excel_path);
            }

            $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($excel_path);
        } catch (Exception $e){
            return false;
        }

        return ['name' => $fileName, 'url' => $excel_url];
    }

    function getExcelColNameFromIndex($colNo)
    {
        $numeric = $colNo % 26;
        $letter = chr(65 + $numeric);
        $num2 = intval($colNo / 26);
        if ($num2 > 0) {
            return $this->getExcelColNameFromIndex($num2 - 1) . $letter;
        }
        return $letter;
    }
}


