<?php

defined('_VALID_CALL') or die('Direct Access is not allowed.');

const N2GO_INTEGRATION_URL = 'https://ui.newsletter2go.com/integrations/connect/XTC/';

if (empty($this->url_data['save']) && !$this->url_data['get_singledata']) {

    global $db;
    $shop_id = $store_handler->shop_id;

    $edit_data = $this->getConfigHeaderData();

    if ($this->url_data['edit_id']
        && $edit_data['header']['conf_XT_NEWSLETTER2GO_API_KEY_shop_' . $shop_id]
        && $edit_data['header']['conf_XT_NEWSLETTER2GO_API_USER_shop_' . $shop_id]
    ) {

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

        $http = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https://' : 'http://';
        $baseUrl = $http . $_SERVER['SERVER_NAME'] . dirname($_SERVER["REQUEST_URI"] . '?') . '/';
        $baseUrl = str_replace(_SRV_WEB_ADMIN, '', $baseUrl);
        $queryParams['url'] = $baseUrl;
        $queryParams['callback'] = $baseUrl . 'plugins/xt_newsletter2go/pages/callback.php/';

        $queryParams['subShopId'] = $shop_id;
        $connectUrl = N2GO_INTEGRATION_URL . '?' . http_build_query($queryParams);

        $tplData = array('show_n2go_connect' => true, 'connectUrl' => $connectUrl);

        $tpl = 'connect_button.html';
        $template = new Template();
        $template->getTemplatePath($tpl, 'xt_newsletter2go', '', 'plugin');

        $tmpData = $template->getTemplate('xt_newsletter2go_smarty', $tpl, $tplData);

        echo $tmpData;
    }
}


