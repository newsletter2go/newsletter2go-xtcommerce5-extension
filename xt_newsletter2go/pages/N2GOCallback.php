<?php
include '../../../xtCore/main.php';

class N2GOCallback
{
    /** @var ADODB_mysql  */
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    public function exec($funcName)
    {
        $output = array('success' => false);
        if (method_exists($this, $funcName)) {
            $this->$funcName($output);
        }

        header('Content-type: application/json');
        echo json_encode($output);
    }


    private function callback(&$output)
    {
        $companyId = filter_input(INPUT_POST, 'company_id', FILTER_SANITIZE_SPECIAL_CHARS);
        if (!empty($companyId)) {
            $table_config_plugin = TABLE_PLUGIN_CONFIGURATION;
            $execute_sql = "UPDATE $table_config_plugin SET config_value = '$companyId' WHERE config_key = 'XT_NEWSLETTER2GO_COMPANY_ID'";
            $this->db->Execute($execute_sql);

            $output['success'] = true;
        }
    }
}