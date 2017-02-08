<?php

defined('_VALID_CALL') or die('Direct Access is not allowed.');

const N2GO_INTEGRATION_URL = 'https://ui.newsletter2go.com/integrations/connect/XTC/';

if (empty($this->url_data['save']) && !$this->url_data['get_singledata']) {

    global $db;
    $shop_id = $store_handler->shop_id;

    $edit_data = $this->getConfigHeaderData();

    if ($this->url_data['edit_id'] && $edit_data['header']['conf_XT_NEWSLETTER2GO_API_KEY_shop_' . $shop_id] && $edit_data['header']['conf_XT_NEWSLETTER2GO_API_USER_shop_' . $shop_id]) {

        $table = TABLE_PLUGIN_PRODUCTS;
        $pluginVersion = $db->GetOne("SELECT version FROM $table WHERE code = 'xt_newsletter2go'");
        $queryParams['version'] = str_replace('.', '', $pluginVersion);

        foreach ($edit_data['header'] as &$pluginParams) {
            if ($pluginParams['name'] == 'conf_XT_NEWSLETTER2GO_API_KEY_shop_' . $shop_id) {
                $queryParams['password'] = $pluginParams['value'];
            } else if ($pluginParams['name'] == 'conf_XT_NEWSLETTER2GO_API_USER_shop_' . $shop_id) {
                $queryParams['username'] = $pluginParams['value'];
            }
        }

        $table = TABLE_CONFIGURATION_MULTI . $store_handler->shop_id;
        $langCode = $db->GetOne("SELECT config_value FROM $table WHERE config_key = '_STORE_LANGUAGE'");
        $queryParams['language'] = $langCode;

        $record = $db->Execute("SELECT * FROM " . TABLE_MANDANT_CONFIG . " where shop_id =?", array($shop_id));
        $shop_url = $record->fields['shop_ssl'] != 'no_ssl' ? $record->fields['shop_https'] : $record->fields['shop_http'];
        $queryParams['url'] = $shop_url;

        $queryParams['subShopId'] = $shop_id;
        $connectUrl = N2GO_INTEGRATION_URL . '?' . http_build_query($queryParams);

        $tpl_data = array('show_n2go_connect' => true, 'connectUrl' => $connectUrl);

        $tpl = 'connect_button.html';
        $template = new Template();
        $template->getTemplatePath($tpl, 'xt_newsletter2go', '', 'plugin');

        $tmp_data = $template->getTemplate('xt_newsletter2go_smarty', $tpl, $tpl_data);

        echo $tmp_data;
    }
}


