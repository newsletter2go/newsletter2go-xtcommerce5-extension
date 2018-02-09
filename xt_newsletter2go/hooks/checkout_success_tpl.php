<?php
defined('_VALID_CALL') or die('Direct Access is not allowed.');

echo _displayNL2Gotracking();

function _displayNL2Gotracking()
{
    $companyId = XT_NEWSLETTER2GO_COMPANY_ID;
    $conversionTracking = XT_NEWSLETTER2GO_CHECKBOX_TRACKING;

    if (!empty($companyId) && $conversionTracking == 'true') {
        global $db, $success_order;
        $shipping = '';
        $addItem = '';
        $configTable = TABLE_CONFIGURATION_MULTI . $success_order->order_data['shop_id'];

        $cd = TABLE_CATEGORIES_DESCRIPTION;
        $ptc = TABLE_PRODUCTS_TO_CATEGORIES;
        $langCode = $db->GetOne("SELECT config_value FROM $configTable WHERE config_key = '_STORE_LANGUAGE'");

        foreach ($success_order->order_total_data as $data) {
            if ($data['orders_total_key'] == 'shipping') {
                $shipping = $data['orders_total_price']['plain'];
            }
        }

        $tax = round(
            $success_order->order_total['total']['plain'] - $success_order->order_total['total_otax']['plain'],
            2
        );

        foreach ($success_order->order_products as $product) {
            $productId = $product['products_id'];
            $category = $db->GetOne("SELECT categories_name FROM $ptc 
                                     LEFT JOIN $cd ON $cd.categories_id = $ptc.categories_id 
                                     WHERE $cd.language_code = '$langCode' AND $ptc.products_id = '$productId'");

            $addItem .= 'n2g("ecommerce:addItem", {
                "id": "' . $success_order->oID . '",
                "name": "' . $product['products_name'] . '",
                "sku": "' . $product['products_model'] . '",
                "category": "' . $category . '",
                "price": "' . $product['products_price']['plain'] . '",
                "quantity": "' . $product['products_quantity'] . '"
                 });' . PHP_EOL;
        }

        $shop = new multistore();
        $storeName = $shop->getStoreName($success_order->order_data['shop_id']);
        $js = '<script type="text/javascript" id="n2g_script">!function(e,t,n,c,r,a,i)
            { e.Newsletter2GoTrackingObject=r,e[r]=e[r]||function() { (e[r].q=e[r].q||[]).push(arguments) },
            e[r].l=1*new Date,a=t.createElement(n),i=t.getElementsByTagName(n)[0],
            a.async=1,a.src=c,i.parentNode.insertBefore(a,i) }
            (window,document,"script","//static.newsletter2go.com/utils.js","n2g");
            
            n2g("create", "' . $companyId . '");
            n2g("ecommerce:addTransaction", {
                "id": "' . $success_order->oID . '",
                "affiliation": "' . $storeName . '",
                "revenue": "' . $success_order->order_total['total']['plain'] . '",
                "shipping": "' . $shipping . '",
                "tax": "' . $tax . '"
                 });
            ' . $addItem . '
            n2g("ecommerce:send");
                    </script>';

        return $js;
    }
}