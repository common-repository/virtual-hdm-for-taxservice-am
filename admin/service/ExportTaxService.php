<?php

if (!class_exists('ExportTaxService')) {
    class ExportTaxService
    {

        public static function exportSettingsAndData()
        {
            global $wpdb;
            $tables = [$wpdb->prefix . 'tax_service_report', $wpdb->prefix . 'tax_service'];
            $sqlFileContent = '';
            foreach ($tables as $table) {
                $results = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);
                $sqlFileContent .= "DROP TABLE IF EXISTS $table;";
                $resultShowCreateTable = (array)$wpdb->get_row("SHOW CREATE TABLE  $table");
                $sqlFileContent .= "\n\n" . $resultShowCreateTable["Create Table"] . ";\n\n";
                $sqlFileContent .= "\n\n";
                if (empty(!$results)) {
                    $keys = array_keys($results[0]);
                    $sqlFileContent .= 'INSERT INTO ' . $table . ' (`' . implode('`,`', $keys) . '`) VALUES ';
                }
                foreach ($results as $key => $item) {
                    $sqlFileContent .= "('" . implode("','", array_values($item)) . "')";
                    if ($key !== count($results) - 1) {
                        $sqlFileContent .= ",";
                    }else{
                        $sqlFileContent .= ";";
                    }
                }
                $sqlFileContent .= "\n\n";
            }
            $sqlFileContent .= "\n\n";

            $settingArray = [
                'hkd_tax_settings_completed',
                'hkd_tax_service_register_number',
                'hkd_tax_service_tin',
                'hkd_tax_service_tax_type',
                'hkd_tax_service_check_both_type',
                'hkd_tax_service_vat_percent',
                'hkd_tax_service_treasurer',
                'hkd_tax_service_atg_code',
                'hkd_tax_service_units_value',
                'hkd_tax_service_api_activated',
                'hkd_tax_service_api_url',
                'hkd_tax_service_enabled',
                'hkd_tax_service_enabled_type',
                'hkd_tax_service_verification_code_same_sku',
                'hkd_tax_service_shipping_atg_code',
                'hkd_tax_service_shipping_description',
                'hkd_tax_service_shipping_good_code',
                'hkd_tax_service_shipping_unit_value',
                'hkd_tax_service_automatically_print_status',
                'hkd_tax_service_shipping_activated',
                'hkd_tax_service_send_refund_to_user',
                'hkd_tax_service_send_refund_to_admin',
                'hkd_tax_service_send_to_admin',
                'taxServiceSeqNumber'
            ];

            $sqlFileContent .= "DELETE FROM " . $wpdb->prefix . "options WHERE `option_name` IN ('"
                . implode("','", $settingArray)
                . "');";

            $sqlFileContent .= "\n\n";

            $sqlFileContent .= "\n\n";

            foreach ($settingArray as $key =>$item){
                if($key === 0){
                    $sqlFileContent .= 'INSERT INTO ' . $wpdb->prefix . 'options (`option_name`, `option_value`, `autoload`) VALUES ';
                }
                $itemValue = get_option($item);
                $sqlFileContent .= "('".$item."', '".$itemValue."', 'yes')";
                if ($key !== count($settingArray) - 1) {
                    $sqlFileContent .= ",";
                }else{
                    $sqlFileContent .= ";";
                }
            }

            $sqlFileContent .= "\n\n";

            $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
            $filename = $DOCUMENT_ROOT . '/wp-content/uploads/tax-service-backup-' . time() . '-' . (md5(implode(',', $tables))) . '.sql';

            $handle = fopen($filename, 'w+');
            fwrite($handle, $sqlFileContent);
            fclose($handle);


            return str_replace($_SERVER['DOCUMENT_ROOT'], '', $filename);

        }
    }
}