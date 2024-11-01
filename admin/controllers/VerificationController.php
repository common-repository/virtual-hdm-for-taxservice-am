<?php

function checkTaxServiceVerification()
{
    if (!class_exists('VerificationController')) {
        class VerificationController
        {
            private $taxServiceVerificationId;
            private $ownerSiteUrl = 'https://plugins.hkdigital.am/api/';

            public function __construct()
            {
                $this->taxServiceVerificationId = wp_kses_post($_REQUEST['verificationId']);
                $this->validateFields();
            }

            public function validateFields()
            {
                if ($this->taxServiceVerificationId == '') {
                    echo json_encode(['message' => __('Խնդրում ենք անցնել նույնականացում։ Հարցերի դեպքում զանգահարել 033 779-779 հեռախոսահամարով։', 'tax-service'), 'success' => false]);
                    exit;
                }
                update_option('hkd_tax_service_verification_id', sanitize_text_field($this->taxServiceVerificationId));
                $response = wp_remote_post($this->ownerSiteUrl .
                    'bank/tax-service/checkActivation', [
                    'headers' =>
                        array('Accept' => 'application/json'),
                    'sslverify' => false,
                    'body' => [
                        'domain' => wp_kses_post($_SERVER['SERVER_NAME']),
                        'checkoutId' => $this->taxServiceVerificationId,
                        'lang' => 'hy'
                    ]]);
                if (!is_wp_error($response)) {
                    print_r($response['body']);
                    exit;
                } else {
                    echo json_encode(['message' => __('Հարցման խնդիր։', 'tax-service'), 'success' => false]);
                    exit;
                }
            }
        }
    }

    new VerificationController();
}
