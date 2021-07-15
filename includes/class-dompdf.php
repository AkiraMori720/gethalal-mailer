<?php
/**
 * DOMPDF library
 *
 * @link
 * @since 2.6.6
 *
 * @package  Wf_Woocommerce_Packing_List
 */
if (!defined('ABSPATH')) {
    exit;
}
class GM_Dompdf
{
	public $dompdf=null;
	public function __construct()
	{
		$path=plugin_dir_path(__FILE__).'vendor/';
        include_once($path.'autoload.php');

        // initiate dompdf class
        $this->dompdf = new Dompdf\Dompdf();
	}
	public function generate($upload_dir, $html, $file_path)
	{
		$this->dompdf->tempDir = $upload_dir;
        $this->dompdf->set_option('isHtml5ParserEnabled', true);
        $this->dompdf->set_option('enableCssFloat', true);
        $this->dompdf->set_option('isRemoteEnabled', true);

        $this->dompdf->set_option('defaultFont', 'dejavu sans');
        $this->dompdf->loadHtml($html);
        // (Optional) Setup the paper size and orientation
        $this->dompdf->setPaper('A4', 'portrait');
        $this->dompdf->set_option('enable_font_subsetting', true);

        // Render the HTML as PDF
        $this->dompdf->render();

        @file_put_contents($file_path, $this->dompdf->output());

        return $this->dompdf->output();
	}
}
