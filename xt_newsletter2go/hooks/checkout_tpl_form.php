<?php
defined('_VALID_CALL') or die('Direct Access is not allowed.');

if (XT_NEWSLETTER2GO_CHECKBOX_CHECKOUT == 'true') {
    echo _displayNL2GocheckBox($data_nl);
}

function _displayNL2GocheckBox ($data)
{
    global $xtPlugin, $xtLink, $db;

    $table = TABLE_CUSTOMERS;
    $customer_id = $_SESSION['customer']->customers_id;

    $result = $db->getOne("SELECT nl2go_newsletter_status FROM $table WHERE customers_id= $customer_id");

    $tpl = 'newsletter_checkbox_checkout.html';
    $tpl_data = array('newsletter2go' => $result);
    $template = new Template();
    $template->getTemplatePath($tpl, 'xt_newsletter2go', '', 'plugin');

    $tmp_data = $template->getTemplate('xt_newsletter2go_smarty', $tpl, $tpl_data);
    return $tmp_data;
}