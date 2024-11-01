<?php
add_action('admin_init', 'pluginActivateTaxService');
if (!function_exists('pluginActivateTaxService')) {

    function pluginActivateTaxService()
    {
        global $apiUrlTaxService;
        global $pluginDataTaxService;
        $pluginVersion = $pluginDataTaxService['Version'];
        update_option('tax_service_version_option', '777');
        if (get_option('tax_service_version_option') !== $pluginVersion) {
            try {
                if (isset($_SERVER['SERVER_NAME']) || isset($_SERVER['REQUEST_URI'])) {
                    $url = isset($_SERVER['SERVER_NAME']) ? wp_kses_post($_SERVER['SERVER_NAME']) : wp_kses_post($_SERVER['REQUEST_URI']);
                    $ip = wp_kses_post($_SERVER['REMOTE_ADDR']);
                    $token = md5('hkd_init_banks_gateway_class');
                    $user = wp_get_current_user();
                    $email = (string)$user->user_email;
                    $data = ['version' => $pluginVersion, 'email' => $email, 'url' => $url, 'ip' => $ip, 'token' => $token, 'status' => 'inactive'];
                    update_option('tax_service_version_option', $pluginVersion);
                    wp_remote_post($apiUrlTaxService . 'bank/tax-service/pluginActivate', [
                        'headers' => array('Accept' => 'application/json'),
                        'sslverify' => false,
                        'body' => $data]);
                }
            } catch (Exception $e) {
            }
        }
    }
}
