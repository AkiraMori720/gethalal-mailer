<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function getChildren($parent, $items)
{
    $firstChildren = [];
    $children = [];
    foreach ($items as $item) {
        if ($item['parent'] == $parent['id']) {
            $firstChildren[] = $item;
        } else {
            $children[] = $item;
        }
    }

    if (empty($firstChildren)) {
        return [];
    }
    $result = [];
    foreach ($firstChildren as $child) {
        $result[] = $child;
        $result = array_merge($result, getChildren($child, $children));
    }
    return $result;
}

$productCats = get_terms(array(
    'taxonomy' => 'product_cat',
    'hide_empty' => false
));

$parents = [];
$children = [];
foreach ($productCats as $productCat) {
    $data = [
        'id' => $productCat->term_id,
        'parent' => $productCat->parent,
        'name' => ($productCat->parent ? ('-- ' . $productCat->name) : $productCat->name),
    ];
    if ($productCat->parent) {
        $children[] = $data;
    } else {
        $parents[] = $data;
    }
}

$selectCats = [
    ['id' => 0, 'name' => __('All Category', 'gethalal-mailer')]
];
foreach ($parents as $parent) {
    $selectCats[] = $parent;
    $selectCats = array_merge($selectCats, getChildren($parent, $children));
}

if (isset($_POST['pl_dashboard_submit'])) {
    $pl_category = $_POST['gethprofit_category'];
    $pl_from_date = $_POST['pl_from_date'];
    $pl_to_date = $_POST['pl_to_date'];
    if(get_option('pl-dashboard-config-category', -1) < 0){
        var_dump($pl_category, $pl_from_date, $pl_to_date);
        add_option('pl-dashboard-config-category', $pl_category);
        add_option('pl-dashboard-config-from-date', $pl_from_date);
        add_option('pl-dashboard-config-to-date', $pl_to_date);
    } else {
        update_option('pl-dashboard-config-category', $pl_category);
        update_option('pl-dashboard-config-from-date', $pl_from_date);
        update_option('pl-dashboard-config-to-date', $pl_to_date);
    }
}
$selectedCat = get_option('pl-dashboard-config-category', 0);

$pl_from_date = get_option('pl-dashboard-config-from-date', date_i18n('Y-m-01'));
$pl_to_date = get_option('pl-dashboard-config-to-date', date_i18n('Y-m-d'));

$gethalal_profit = GethalalProfit::instance();
$summary = $gethalal_profit->getDashboardSummary($selectedCat, $pl_from_date, $pl_to_date);
$prices_precision = wc_get_price_decimals();
?>
<div class="wrap pl-dashboard">
    <form autocomplete="off" id="pl-dashboard-form" method="post" action="">
        <div class="pl-dashboard-row">
            <label class="title">Product Category:</label>
            <?php
            $output = '<select class="gl-categtory-select" id="gethprofit_category" name="gethprofit_category">';
            foreach ($selectCats as $cat) {
                $output .= '<option value="' . esc_attr($cat['id']) . '" ' . (($cat['id'] == $selectedCat) ? 'selected' : '') . '>' . esc_attr($cat['name']) . '</option>';
            }
            $output .= '</select>';
            echo $output;
            ?>
        </div>
        <div class="pl-dashboard-row pl-data-range">
            <div><label class="title">Date Range:</label></div>
            <input type="text" class="date-picker pl-from-date" name="pl_from_date" maxlength="10"
                   value="<?php echo esc_attr($pl_from_date); ?>"
                   pattern="<?php echo esc_attr(apply_filters('woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])')); ?>"/>
            ~
            <input type="text" class="date-picker pl-to-date" name="pl_to_date" maxlength="10"
                   value="<?php echo esc_attr($pl_to_date); ?>"
                   pattern="<?php echo esc_attr(apply_filters('woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])')); ?>"/>
            <script>
                jQuery(document).ready(function ($) {
                    $(".pl-from-date").datepicker({dateFormat: 'yy-mm-dd'});
                    $(".pl-to-date").datepicker({dateFormat: 'yy-mm-dd'});
                });
            </script>
        </div>
        <p class="submit">
            <input type="submit" id="pl-dashboard-form-submit" class="button-primary"
                   value="<?php esc_attr_e('Save Changes', 'gethalal-mailer'); ?>"/>
            <input type="hidden" name="pl_dashboard_submit" value="submit"/>
            <?php wp_nonce_field(plugin_basename(__FILE__), 'pl_dashboard_nonce_name'); ?>
        </p>
    </form>
    <hr/>
    <table id="aj_latest_gtmetrix_results" class="form-table aj-steps-table" width="100%" cellpadding="10">
        <?php if($summary['overlimit']) { ?>
        <tr>
            <td colspan="2">
                <span style="color: red;">? Number of orders is over 1000. The following is the value for only 1000 orders. Please reduce date range!</span>
            </td>
        </tr>
        <?php } ?>
        <tr>
            <td scope="row" align="center">
                <h3><?php _e('Cost', 'gethalal-mailer'); ?></h3>
                <span class="pl_cost"><span
                            class="woocommerce-Price-currencySymbol"><?php echo get_woocommerce_currency_symbol() ?></span><?php echo wc_format_decimal( $summary['cost'], $prices_precision ); ?></span>
            </td>
            <td scope="row" align="center">
                <h3><?php _e('Refund', 'gethalal-mailer'); ?></h3>
                <span class="pl-return" style="color:<?php  echo $summary['refund']<0?'red':'' ?>"><span
                            class="woocommerce-Price-currencySymbol"><?php echo get_woocommerce_currency_symbol() ?></span><?php echo wc_format_decimal( $summary['refund'], $prices_precision ); ?></span>
            </td>
        </tr>
        <tr>
            <td scope="row" align="center">
                <h3><?php _e('Profit', 'gethalal-mailer'); ?></h3>
                <span class="pl_profit"><span
                            class="woocommerce-Price-currencySymbol"><?php echo get_woocommerce_currency_symbol() ?></span><?php echo wc_format_decimal( $summary['profit'], $prices_precision ); ?></span>
            </td>
            <td scope="row" align="center">
                <h3><?php _e('Revenue', 'gethalal-mailer'); ?></h3>
                <span class="pl-revenue"><span
                            class="woocommerce-Price-currencySymbol"><?php echo get_woocommerce_currency_symbol() ?></span><?php echo wc_format_decimal( $summary['revenue'], $prices_precision ); ?></span>
            </td>
        </tr>
    </table>
</div>
