TODO
mysql error: [1054: Unknown column 'xt_customers_addresses.customers_firstname' in 'field list'] in EXECUTE("SELECT xt_customers_addresses.customers_firstname as 'xt_customers_addresses.customers_firstname'
                        FROM xt_customers GROUP BY xt_customers_addresses.customers_firstname,
                            xt_customers_addresses.customers_lastname,
                            xt_customers_addresses.customers_gender,
                            xt_customers.customers_email_address,
                            xt_customers_addresses.customers_phone")
(2015-12-15 17:33:14) mysql error: [1054: Unknown column 'xt_customers_addresses.customers_firstname' in 'field list'] in EXECUTE("SELECT xt_customers_addresses.customers_firstname as 'xt_customers_addresses.customers_firstname'
                        FROM xt_customers GROUP BY xt_customers_addresses.customers_firstname,
                            xt_customers_addresses.customers_lastname,
                            xt_customers_addresses.customers_gender,
                            xt_customers.customers_email_address,
                            xt_customers_addresses.customers_phone")
(2015-12-15 17:33:25) mysql error: [1054: Unknown column 'xt_customers_addresses.customers_firstname' in 'field list'] in EXECUTE("SELECT xt_customers_addresses.customers_firstname as 'xt_customers_addresses.customers_firstname',xt_customers_addresses.customers_lastname as 'xt_customers_addresses.customers_lastname',xt_customers_addresses.customers_gender as 'xt_customers_addresses.customers_gender',date(xt_customers_addresses.customers_dob) as 'xt_customers_addresses.customers_dob',xt_customers_addresses.customers_street_address as 'xt_customers_addresses.customers_street_address',xt_customers_addresses.customers_city as 'xt_customers_addresses.customers_city',xt_customers_addresses.customers_postcode as 'xt_customers_addresses.customers_postcode',xt_customers_addresses.customers_country_code as 'xt_customers_addresses.customers_country_code',xt_countries_description.countries_name as 'xt_countries_description.countries_name',xt_customers_addresses.customers_company as 'xt_customers_addresses.customers_company',xt_customers.customers_email_address as 'xt_customers.customers_email_address',xt_customers_addresses.customers_phone as 'xt_customers_addresses.customers_phone',xt_customers.customers_id as 'xt_customers.customers_id',xt_customers.nl2go_newsletter_status as 'xt_customers.nl2go_newsletter_status',count(xt_orders.orders_id) as 'xt_orders.total_order',sum(xt_orders_stats.orders_stats_price) as 'xt_orders_stats.total_revenue',avg(xt_orders_stats.orders_stats_price) as 'xt_orders_stats.avg_cart',xt_orders_status_history.date_added as 'xt_orders_status_history.date_added',xt_customers.date_added as 'xt_customers.date_added',xt_customers.last_modified as 'xt_customers.last_modified',xt_customers_addresses.customers_fax as 'xt_customers_addresses.customers_fax',xt_customers.payment_unallowed as 'xt_customers.payment_unallowed',xt_customers.shipping_unallowed as 'xt_customers.shipping_unallowed'
                        FROM xt_customers GROUP BY xt_customers_addresses.customers_firstname,
                            xt_customers_addresses.customers_lastname,
                            xt_customers_addresses.customers_gender,
                            xt_customers.customers_email_address,
                            xt_customers_addresses.customers_phone")
