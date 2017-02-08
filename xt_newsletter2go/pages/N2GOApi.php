<?php
include '../../../xtCore/main.php';

class N2GOApi
{
    /** @var ADODB_mysql  */
    private $db;
    private $error;
//    private $version = 4000;

    public function __construct()
    {
        global $db;
        $this->db = $db;
        $this->init();
    }

    public function exec($funcName)
    {
        $output = array('success' => false);
        $output['errorMsg'] = $this->error;
        if (!strlen($this->error) && method_exists($this, $funcName)) {
            $this->$funcName($output);
        }

        header('Content-type: application/json');
        echo json_encode($output);
    }

    private function init()
    {
        $user = filter_input(INPUT_POST, 'user');
        $pass = filter_input(INPUT_POST, 'pass');
        $table_plugin_products = TABLE_PLUGIN_PRODUCTS;
        $table_plugin_code = TABLE_PLUGIN_CODE;
        $api_plugin = 'xt_newsletter2go';
        $sql_plugin = "
                SELECT plugin_status
                FROM $table_plugin_products
                    INNER JOIN $table_plugin_code
                        ON $table_plugin_products.plugin_id = $table_plugin_code.plugin_id
                WHERE $table_plugin_code.plugin_code LIKE '$api_plugin'";

        $plugin_status = (int)$this->db->GetOne($sql_plugin);
        $this->error = '';
        try {
            if (!is_int($plugin_status)) {
                throw new Exception('Plugin not found');
            }

            if (!$plugin_status) {
                throw new Exception('Plugin not installed');
            }
        } catch (Exception $e) {
            $this->error = $e->getMessage();
        }

        if (isset($user) && isset($pass)) {
            if (XT_NEWSLETTER2GO_API_USER == '' || XT_NEWSLETTER2GO_API_KEY == '') {
                $this->error = 'No user and password in configuration';
            } elseif (XT_NEWSLETTER2GO_API_USER != $user || XT_NEWSLETTER2GO_API_KEY != $pass) {
                $this->error = 'Api credentials are incorrect';
            }
        } else {
            $this->error = 'Api credentials incomplete';
        }
    }

    private function getPluginVersion(&$output)
    {
        $table = TABLE_PLUGIN_PRODUCTS;
        $pluginVersion = $this->db->GetOne("SELECT version FROM $table WHERE code = 'xt_newsletter2go'");
        $output['data'] = str_replace('.', '', $pluginVersion);
        $output['success'] = true;
    }

    private function testConnection(&$output)
    {
        $output['data'] = strlen($this->error) === 0;
        $output['success'] = true;
    }

    public function getLanguages(&$output)
    {
        $languages = array();
        $table = TABLE_LANGUAGES;

        try {
            $langQuery = $this->db->getAll("SELECT code, name FROM $table WHERE language_status = 1");
            foreach ($langQuery as $lang) {
                $languages[$lang['code']] = $lang['name'];
            }

            $output['data'] = $languages;
            $output['success'] = true;
        } catch (Exception $exc) {
            $output['errorMsg'] = 'Failed to retrieve languages';
        }
    }

    private function getItem(&$output)
    {
        $item = filter_input(INPUT_POST, 'item');
        $postLanguage = filter_input(INPUT_POST, 'language');
        if (isset($item)) {
            $itemid = isset($item) ? $item : null;


            if (!empty($postLanguage)) {
                $language = $postLanguage;
            } else {
                $table = TABLE_LANGUAGES;
                $langQuery =
                    $this->db->getAll("SELECT code, name FROM $table WHERE language_status = 1 ORDER BY sort_order");
                $lang = current($langQuery);
                $language = $lang['code'];

            }

            $i = 0;
            $uriParts = explode('/', $_SERVER['REQUEST_URI']);
            $imgDir = 'http://';
            $imgDir .= $_SERVER['HTTP_HOST'];
            while ($uriParts[$i] != 'plugins') {
                $imgDir .= $uriParts[$i++] . '/';
            }

            $baseUrl = $imgDir;
            $imgDir .= _SRV_WEB_IMAGES;
            $imgDir .= 'info/';

            $table_products = TABLE_PRODUCTS;
            $table_products_description = TABLE_PRODUCTS_DESCRIPTION;
            $table_products_price_special = TABLE_PRODUCTS_PRICE_SPECIAL;
            $table_media = TABLE_MEDIA;
            $table_media_link = TABLE_MEDIA_LINK;
            $table_seo_url = TABLE_SEO_URL;
            $table_tax_rates = TABLE_TAX_RATES;
            $table_manufacturers = TABLE_MANUFACTURERS;
            $postAttributes = filter_input(INPUT_POST, 'attributes');

            $attributes = json_decode($postAttributes, true);
            if (!empty($attributes)) {
                $result = $this->getItemSelectSql($attributes, false);
            } else {
                $result = $this->getItemSelectSql(self::getProductAttributesArray(), true);
            }

            $select = $result['select'];
            $attributes = $result['attributesForCheck'];

            $sql_item = "SELECT  $select
                        FROM $table_products
                        LEFT JOIN $table_products_description
                            ON $table_products.products_id = $table_products_description.products_id
                        LEFT JOIN $table_products_price_special
                            ON $table_products_price_special.products_id = $table_products.products_id
                        LEFT JOIN $table_seo_url
                            ON $table_seo_url.link_id = $table_products.products_id
                        LEFT JOIN $table_media_link
                            ON $table_media_link.link_id = $table_products.products_id
                        LEFT JOIN $table_media
                            ON $table_media.id = $table_media_link.m_id
                        LEFT JOIN $table_tax_rates
                            ON $table_tax_rates.tax_class_id = $table_products.products_tax_class_id AND $table_tax_rates.tax_zone_id = 31
                        LEFT JOIN $table_manufacturers
                            ON $table_manufacturers.manufacturers_id = $table_products.manufacturers_id
                                     WHERE ($table_products.products_model LIKE '" . $itemid . "' OR
                        $table_products.products_id = '$itemid')
                            AND $table_products_description.language_code = '" . $language . "'
                            AND $table_seo_url.language_code = '" . $language . "'
                            AND $table_seo_url.link_type = 1";

            if (count($attributes) == 1 && $attributes[0] == 'xt_seo_url.shop_url') {
                $res = array();
            } else {
                $res = $this->db->getAll($sql_item);
            }

            $images = array();
            if (count($res)) {
                if ($res[0]["$table_products.products_image"] != null) {
                    $images[] = $imgDir . $res[0]["$table_products.products_image"];
                }

                foreach ($res as $row) {
                    if ($row["$table_media.file"] != null) {
                        $images[] = $imgDir . $row["$table_media.file"];
                    }
                }
            }

            $product = array();
            if (is_array($res) && count($res) > 0) {
                foreach ($res[0] as $key => $attribute) {
                    switch ($key) {
                        case 'xt_products.products_price':
                            if (in_array('xt_products.products_price', $attributes)) {
                                $product[$key] = (is_null($res[0][$key]) ? 0 : $res[0][$key]) *
                                    (100 + $res[0]['xt_tax_rates.tax_rate']) / 100;
                            }

                            if (in_array('xt_products.net_price_old', $attributes)) {
                                $product['xt_products.net_price_old'] = floatval($res[0]['xt_products.products_price']);
                            }

                            break;
                        case 'xt_products_price_special.specials_price':
                            $price = (is_null($res[0]['xt_products.products_price']) ? 0 :
                                    $res[0]['xt_products.products_price']) * (100 + $res[0]['xt_tax_rates.tax_rate']) /
                                100;
                            $special_price = is_null($res[0][$key]) ? $price : floatval($res[0][$key]);
                            if (in_array('xt_products_price_special.specials_price', $attributes)) {
                                $product[$key] = $special_price;
                            }

                            if (in_array('xt_products_price_special.net_price_new', $attributes)) {
                                $product['xt_products_price_special.net_price_new'] =
                                    ($special_price * 100) / ($res[0]['xt_tax_rates.tax_rate'] + 100);
                            }

                            break;
                        case 'xt_tax_rates.tax_rate':
                            if (in_array($key, $attributes)) {
                                $product[$key] = is_null($res[0][$key]) ? 0 : $res[0][$key] / 100;
                            }
                            break;
                        case 'xt_products.products_image':
                            $product[$key] = count($images) > 0 ? array_unique($images) : array();
                            break;
                        case 'xt_seo_url.url_text':
                            $product[$key] = is_null($res[0][$key]) ? "" : $res[0][$key];
                            break;
                        default:
                            if (in_array($key, $attributes)) {
                                $product[$key] = is_null($res[0][$key]) ? "" : $res[0][$key];
                            }
                            break;
                    }
                }

                if (in_array('xt_seo_url.shop_url', $attributes)) {
                    $product['xt_seo_url.shop_url'] = $baseUrl;
                }

                $output['data'] = $product;
                $output['success'] = true;
            } else {
                $output['errorMsg'] = 'Item not found';
            }
        } else {
            $output['errorMsg'] = 'Item not specified';
        }
    }

    private function getCustomers(&$output)
    {
        $table_customers = TABLE_CUSTOMERS;
        $table_customers_addresses = TABLE_CUSTOMERS_ADDRESSES;
        $table_orders = TABLE_ORDERS;
        $table_orders_stats = TABLE_ORDERS_STATS;
        $table_orders_status_history = TABLE_ORDERS_STATUS_HISTORY;
        $table_countries = TABLE_COUNTRIES_DESCRIPTION;
        $select = '';
        $postFields = filter_input(INPUT_POST, 'fields');
        $postEmails = filter_input(INPUT_POST, 'emails');
        $postGroups = filter_input(INPUT_POST, 'groups');
        $subscribed = filter_input(INPUT_POST, 'subscribed');
        $timeframe = filter_input(INPUT_POST, 'timeframe');
        $limit =  filter_input(INPUT_POST, 'limit');
        $offset =  filter_input(INPUT_POST, 'offset');
        $subShopId =  filter_input(INPUT_POST, 'subShopId');
        $conditions = array();

        $fields = json_decode($postFields, true);
        if (!empty($fields)) {
            $select = $this->getCustomerSelectSql($fields, false);
        } else {
            $select = $this->getCustomerSelectSql(self::getCustomerFieldsArray(), true);
        }

        $sql_customers = "SELECT $select
                        FROM $table_customers";

        if (strpos($select, $table_customers_addresses) !== false) {
            $sql_customers .= " LEFT JOIN $table_customers_addresses
                                ON $table_customers_addresses.customers_id = $table_customers.customers_id";
        }

        if (strpos($select, $table_countries) !== false) {
            $sql_customers .= " LEFT JOIN $table_countries
                                ON $table_countries.countries_iso_code_2 = $table_customers_addresses.customers_country_code";
        }

        if ($subscribed) {
            $conditions[] = "$table_customers.nl2go_newsletter_status='1'";
        }

        $groups = json_decode($postGroups, true);
        if (!empty($groups)) {
            $conditions[] = "$table_customers.customers_status in (" . implode(',', $groups) . ")";
        }

        if ($subShopId != 0) {
            $conditions[] = "$table_customers.shop_id = $subShopId";
        }

        $emails = json_decode($postEmails, true);
        if (!empty($emails)) {
            $conditions[] = "$table_customers.customers_email_address in ('" . implode("','", $emails) . "')";
        }

        if ($timeframe) {
            $timeframe = intval($timeframe);
            $conditions[] = "(GREATEST($table_customers.date_added, $table_customers.last_modified, $table_customers_addresses.date_added, $table_customers_addresses.last_modified ) >
                DATE_SUB( NOW(), INTERVAL $timeframe HOUR ) OR ($table_customers.date_added = '0000-00-00 00:00:00' AND $table_customers.last_modified = '0000-00-00 00:00:00'))";
        }

        if (!empty($conditions)) {
            $sql_customers .= ' WHERE ' . implode(' AND ', $conditions);
        }
        if (strpos($sql_customers, $table_customers_addresses) !== false) {
            $sql_customers .= " GROUP BY $table_customers_addresses.customers_firstname,
                            $table_customers_addresses.customers_lastname,
                            $table_customers_addresses.customers_gender,
                            $table_customers.customers_email_address,
                            $table_customers_addresses.customers_phone";
        }


        if($limit > 0){
            $sql_customers .= " LIMIT ".$limit;
        }
        if($offset > 0){
            $sql_customers .= " OFFSET ".$offset;
        }

        $res = $this->db->getAll($sql_customers);
        foreach ($res as &$fields) {
            foreach ($fields as &$field) {
                $field = is_null($field) ? '' : $field;
            }
        }

        $output['data'] = $res;
        $output['success'] = true;
    }

    private function getCustomerCount(&$output)
    {
        $this->getCustomers($output);
        $output['data'] = count($output['data']);
    }

    private function unsubscribeCustomer(&$output)
    {
        $postEmail = filter_input(INPUT_POST, 'email');
        if (isset($postEmail)) {
            $email = mysql_real_escape_string($postEmail);
            $table_customers = TABLE_CUSTOMERS;
            $sql_customers =
                "UPDATE $table_customers SET nl2go_newsletter_status = 0 WHERE customers_email_address = '" . $email .
                "'";
            $res = $this->db->Execute($sql_customers);
            $output['data'] = $this->db->_affectedrows() ? true : false;
            $output['success'] = true;
        }
    }

    private function subscribeCustomer(&$output)
    {
        $postEmail = filter_input(INPUT_POST, 'email');
        if (isset($postEmail)) {
            $email = mysql_real_escape_string($postEmail);
            $table_customers = TABLE_CUSTOMERS;
            $sql_customers =
                "UPDATE $table_customers SET nl2go_newsletter_status = 1 WHERE customers_email_address = '" . $email .
                "'";
            $res = $this->db->Execute($sql_customers);
            $output['data'] = $this->db->_affectedrows() ? true : false;
            $output['success'] = true;
        }
    }

    private function getFields(&$output)
    {
        $output['data'] = self::getCustomerFieldsArray();
        $output['success'] = true;
    }

    private function getGroups(&$output)
    {
        $table_groups = TABLE_CUSTOMERS_STATUS_DESCRIPTION;
        $table_customers = TABLE_CUSTOMERS;
        $sql_groups = "SELECT DISTINCT customers_status_id, customers_status_name "
            . "FROM $table_groups "
            . "WHERE language_code = 'en'";
        $res = $this->db->getAll($sql_groups);

        $resGroups = array();
        foreach ($res as $field => $row) {
            $sql_count = "SELECT COUNT(*) AS count "
                . "FROM $table_customers "
                . "WHERE customers_status = " . $row['customers_status_id'];
            $count = $this->db->Execute($sql_count);

            $resGroups[$field]['id'] = is_null($row['customers_status_id']) ? '' : $row['customers_status_id'];
            $resGroups[$field]['name'] = is_null($row['customers_status_name']) ? '' : $row['customers_status_name'];
            $resGroups[$field]['description'] = null;
            $resGroups[$field]['count'] = is_null($count->fields['count']) ? '' : $count->fields['count'];
        }

        $output['data'] = $resGroups;
        $output['success'] = true;
    }

    private function getProductAttributes(&$output)
    {
        $output['data'] = self::getProductAttributesArray();
        $output['success'] = true;
    }

    private function getItemSelectSql($attributes, $all)
    {
        $notInserted = true;
        $attributesForCheck = array();
        $select = "";

        foreach ($attributes as $attribute) {
            if ($all) {
                $attribute = $attribute['id'];
            }

            array_push($attributesForCheck, $attribute);

            switch ($attribute) {
                case 'xt_products.products_price':
                case 'xt_products_price_special.specials_price':
                case 'xt_products.net_price_old':
                case 'xt_products_price_special.net_price_new':
                    if ($notInserted) {
                        $select .= DB_PREFIX . "_products.products_price as 'xt_products.products_price',"
                            . " " . DB_PREFIX .
                            "_products_price_special.specials_price as 'xt_products_price_special.specials_price',"
                            . " " . DB_PREFIX . "_tax_rates.tax_rate as 'xt_tax_rates.tax_rate',";
                        $notInserted = false;
                    }

                    break;
                case 'xt_seo_url.shop_url':
                    break;
                case 'xt_products.products_image':
                    $select .= str_replace('xt_', DB_PREFIX . '_', $attribute) . " as '" . $attribute .
                        "', " . DB_PREFIX . "_media.file as 'xt_media.file',";
                    break;
                default:
                    $select .= str_replace('xt_', DB_PREFIX . '_', $attribute) . " as '" . $attribute . "',";
                    break;
            }
        }

        $select = substr($select, 0, -1);
        $result['select'] = $select;
        $result['attributesForCheck'] = $attributesForCheck;

        return $result;
    }

    private function getCustomerSelectSql($fields, $all)
    {
        $select = "";
        $prefix = DB_PREFIX;
        $longPart = " x1 INNER JOIN `{$prefix}_orders` x2 ON x2.orders_id = x1.orders_id WHERE x2.customers_id = {$prefix}_customers.customers_id";
        foreach ($fields as $field) {
            if ($all) {
                $field = $field['id'];
            }

            switch ($field) {
                case 'xt_orders.total_order':
                    $select .= "(SELECT COUNT(x1.orders_id) FROM {$prefix}_orders x1 WHERE x1.customers_id = {$prefix}_customers.customers_id) AS '" . $field . "',";
                    break;
                case 'xt_orders_stats.total_revenue':
                    $select .= "(SELECT SUM(x1.orders_stats_price) FROM {$prefix}_orders_stats $longPart) AS '" . $field . "',";
                    break;
                case 'xt_orders_stats.avg_cart':
                    $select .= "(SELECT AVG(x1.orders_stats_price) FROM {$prefix}_orders_stats $longPart) AS '" . $field . "',";
                    break;
                case 'xt_orders_status_history.date_added':
                    $select .= "(SELECT x1.date_added FROM {$prefix}_orders_status_history $longPart ORDER BY x1.date_added DESC LIMIT 1) AS '" . $field . "',";
                    break;
                case 'xt_customers_addresses.customers_dob':
                    $select .= "date({$prefix}_customers_addresses.customers_dob) as '" . $field . "',";
                    break;
                default:
                    $select .= str_replace('xt_', $prefix . '_', $field) . " as '" . $field . "',";
                    break;
            }
        }

        $select = substr($select, 0, -1);

        return $select;
    }

    private static function getCustomerFieldsArray()
    {
        return array(
            array(
                'id' => 'xt_customers_addresses.customers_firstname',
                'name' => 'First name',
                'description' => 'Customers firstname',
                'type' => 'String',
            ),
            array(
                'id' => 'xt_customers_addresses.customers_lastname',
                'name' => 'Last name',
                'description' => 'Customers lasttname',
                'type' => 'String',
            ),
            array(
                'id' => 'xt_customers_addresses.customers_gender',
                'name' => 'Gender',
                'description' => 'Customers gender',
                'type' => 'String',
            ),
            array(
                'id' => 'xt_customers_addresses.customers_dob',
                'name' => 'Birthday',
                'description' => 'Customers birthday',
                'type' => 'Date',
            ),
            array(
                'id' => 'xt_customers_addresses.customers_street_address',
                'name' => 'Street',
                'description' => 'Customers street address',
                'type' => 'String',
            ),
            array(
                'id' => 'xt_customers_addresses.customers_city',
                'name' => 'City',
                'description' => 'Customers city address',
                'type' => 'String',
            ),
            array(
                'id' => 'xt_customers_addresses.customers_postcode',
                'name' => 'Zip code',
                'description' => 'Customers zip code address',
                'type' => 'Integer',
            ),
            array(
                'id' => 'xt_customers_addresses.customers_country_code',
                'name' => 'Country code',
                'description' => 'Customers country code',
                'type' => 'String',
            ),
            array(
                'id' => 'xt_countries_description.countries_name',
                'name' => 'Country',
                'description' => 'Customers country address',
                'type' => 'String',
            ),
            array(
                'id' => 'xt_customers_addresses.customers_company',
                'name' => 'Company name',
                'description' => 'Customers company name',
                'type' => 'String',
            ),
            array(
                'id' => 'xt_customers.customers_email_address',
                'name' => 'Email',
                'description' => 'Customers email',
                'type' => 'String',
            ),
            array(
                'id' => 'xt_customers_addresses.customers_phone',
                'name' => 'Mobile phone',
                'description' => 'Customers mobil phone',
                'type' => 'String',
            ),
            array(
                'id' => 'xt_customers.customers_id',
                'name' => 'Customer Id.',
                'description' => 'Customer unique identifier',
                'type' => 'String',
            ),
            array(
                'id' => 'xt_customers.nl2go_newsletter_status',
                'name' => 'Newsletter status',
                'description' => 'Newsletter status if customer is subscribed or not',
                'type' => 'String',
            ),
            array(
                'id' => 'xt_orders.total_order',
                'name' => 'Total order',
                'description' => 'Count of total orders by customer',
                'type' => 'Integer',
            ),
            array(
                'id' => 'xt_orders_stats.total_revenue',
                'name' => 'Total revenue',
                'description' => 'Total revenue of customer',
                'type' => 'Float',
            ),
            array(
                'id' => 'xt_orders_stats.avg_cart',
                'name' => 'Average cart',
                'description' => 'Average cart size',
                'type' => 'Float',
            ),
            array(
                'id' => 'xt_orders_status_history.date_added',
                'name' => 'Last order',
                'description' => 'Date of last order',
                'type' => 'Date',
            ),
            array(
                'id' => 'xt_customers.date_added',
                'name' => 'Date added',
                'description' => 'Date when customer was added in shop',
                'type' => 'Date',
            ),
            array(
                'id' => 'xt_customers.last_modified',
                'name' => 'Last modified',
                'description' => 'Date when customer was modified last time',
                'type' => 'Date',
            ),
            array(
                'id' => 'xt_customers_addresses.customers_fax',
                'name' => 'Fax',
                'description' => 'Customers fax',
                'type' => 'String',
            ),
            array(
                'id' => 'xt_customers.payment_unallowed',
                'name' => 'Payment unallowed',
                'description' => 'Payment methods not allowed',
                'type' => 'String',
            ),
            array(
                'id' => 'xt_customers.shipping_unallowed',
                'name' => 'Shipping unallowed',
                'description' => 'Shipping type not allowed',
                'type' => 'String',
            )
        );
    }

    private static function getProductAttributesArray()
    {
        return array(
            array(
                'id' => 'xt_products.products_id',
                'name' => 'Id',
                'descitption' => 'Id for the product',
                'type' => 'Integer'
            ),
            array(
                'id' => 'xt_seo_url.shop_url',
                'name' => 'Shop url',
                'descitption' => 'Url to the shop',
                'type' => 'String'
            ),
            array(
                'id' => 'xt_products.products_image',
                'name' => 'Images',
                'descitption' => 'Images of product',
                'type' => 'Images'
            ),
            array(
                'id' => 'xt_products.products_price',
                'name' => 'Old price',
                'descitption' => 'Price for product in euros',
                'type' => 'Float'
            ),
            array(
                'id' => 'xt_products_price_special.specials_price',
                'name' => 'New price',
                'descitption' => 'Price for product with discount in euros',
                'type' => 'Float'
            ),
            array(
                'id' => 'xt_products.net_price_old',
                'name' => 'Net old price',
                'descitption' => 'Net price for product in euros',
                'type' => 'Float'
            ),
            array(
                'id' => 'xt_products_price_special.net_price_new',
                'name' => 'Net new price',
                'descitption' => 'Net price for product with discount in euros',
                'type' => 'Float'
            ),
            array(
                'id' => 'xt_products_price_special.date_available',
                'name' => 'Date available discount',
                'descitption' => 'Date from when discount is available',
                'type' => 'Date'
            ),
            array(
                'id' => 'xt_products_price_special.date_expired',
                'name' => 'Date expired discount',
                'descitption' => 'Date when discount is expiring',
                'type' => 'Date'
            ),
            array(
                'id' => 'xt_products_price_special.status',
                'name' => 'Discount status',
                'descitption' => 'Status if discount is active or not',
                'type' => 'Boolean'
            ),
            array(
                'id' => 'xt_products_price_special.group_permission_all',
                'name' => 'Discount perimission',
                'descitption' => 'Status if discount is active for all groups',
                'type' => 'Boolean'
            ),
            array(
                'id' => 'xt_products.products_model',
                'name' => 'Model',
                'descitption' => 'Model of product',
                'type' => 'String'
            ),
            array(
                'id' => 'xt_products.products_ean',
                'name' => 'EAN',
                'descitption' => 'Products international number',
                'type' => 'String'
            ),
            array(
                'id' => 'xt_products.products_quantity',
                'name' => 'Quantity',
                'descitption' => 'Products quantity',
                'type' => 'Float'
            ),
            array(
                'id' => 'xt_products.products_shippingtime',
                'name' => 'Shippingtime',
                'descitption' => 'Products shippingtime in days',
                'type' => 'Integer'
            ),
            array(
                'id' => 'xt_products.date_added',
                'name' => 'Date added',
                'descitption' => 'Date when product was added to shop',
                'type' => 'Date'
            ),
            array(
                'id' => 'xt_products.last_modified',
                'name' => 'Last modified',
                'descitption' => 'Date when product was last time modified',
                'type' => 'Date'
            ),
            array(
                'id' => 'xt_products.date_available',
                'name' => 'Date available',
                'descitption' => 'Date from when product is available in shop',
                'type' => 'Date'
            ),
            array(
                'id' => 'xt_products.products_weight',
                'name' => 'Weight',
                'descitption' => 'Products weight',
                'type' => 'Float'
            ),
            array(
                'id' => 'xt_products.products_status',
                'name' => 'Status',
                'descitption' => 'Status if product is active or not',
                'type' => 'Boolean'
            ),
            array(
                'id' => 'xt_products.products_fsk18',
                'name' => 'FSK18',
                'descitption' => 'Status if product is only available for customers under 18 years',
                'type' => 'Boolean'
            ),
            array(
                'id' => 'xt_products.products_average_rating',
                'name' => 'Average rating',
                'descitption' => 'Average rating for product',
                'type' => 'Float'
            ),
            array(
                'id' => 'xt_products_description.products_name',
                'name' => 'Name',
                'descitption' => 'Products name',
                'type' => 'String'
            ),
            array(
                'id' => 'xt_products_description.products_description',
                'name' => 'Description',
                'descitption' => 'Products description',
                'type' => 'String'
            ),
            array(
                'id' => 'xt_products_description.products_short_description',
                'name' => 'Short description',
                'descitption' => 'Products short description',
                'type' => 'String'
            ),
            array(
                'id' => 'xt_products_description.products_keywords',
                'name' => 'Keywords',
                'descitption' => 'Products keywords',
                'type' => 'String'
            ),
            array(
                'id' => 'xt_seo_url.url_text',
                'name' => 'Link to article',
                'descitption' => 'Link to article in shop',
                'type' => 'String'
            ),
            array(
                'id' => 'xt_tax_rates.tax_rate',
                'name' => 'VAT',
                'descitption' => 'Tax rate for product',
                'type' => 'Float'
            ),
            array(
                'id' => 'xt_manufacturers.manufacturers_name',
                'name' => 'Brand',
                'descitption' => 'Brand for product',
                'type' => 'String'
            )
        );
    }
}
