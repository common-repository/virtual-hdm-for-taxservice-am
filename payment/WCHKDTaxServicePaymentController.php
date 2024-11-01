<?php


if (class_exists('WC_Payment_Gateway') && !class_exists('WCHKDTaxServicePaymentController')) {

    class WCHKDTaxServicePaymentController extends WC_Payment_Gateway
    {
        private $taxApiUrl;
        private $taxRegisterNumber;
        private $taxServiceEnabled;
        private $taxServiceShippingAtgCode;
        private $taxServiceShippingDescription;
        private $taxServiceType;
        private $taxServiceUploadFilePath;
        private $taxServiceGlobalAtgCode;
        private $taxServiceGlobalUnitValue;
        private $taxServiceShippingGoodCode;
        private $taxServiceShippingUnitValue;
        private $taxServiceActivated;
        private $taxServiceShippingActivated;
        private $taxServiceVerificationCodeSameSKU;
        private $taxServiceTaxType;
        private $tax_service_dirname;
        private $taxServiceAutomaticallyPrintStatus;

        public function __construct()
        {
            global $tax_service_dirname;

            $this->tax_service_dirname = $tax_service_dirname;
            $this->taxApiUrl = 'https://ecrm.taxservice.am/taxsystem-rs-vcr/api/v1.0';
            $this->taxServiceUploadFilePath = get_option('hkd_tax_service_upload_file_path');
            $this->taxServiceGlobalAtgCode = get_option('hkd_tax_service_atg_code');
            $this->taxServiceGlobalUnitValue = get_option('hkd_tax_service_units_value');
            $this->taxRegisterNumber = get_option('hkd_tax_service_register_number');
            $this->taxServiceActivated = get_option('hkd_tax_service_api_activated');
            $this->taxServiceShippingAtgCode = get_option('hkd_tax_service_shipping_atg_code');
            $this->taxServiceTaxType = get_option('hkd_tax_service_tax_type');
            $this->taxServiceShippingDescription = get_option('hkd_tax_service_shipping_description');
            $this->taxServiceShippingGoodCode = get_option('hkd_tax_service_shipping_good_code');
            $this->taxServiceShippingUnitValue = get_option('hkd_tax_service_shipping_unit_value');
            $this->taxServiceShippingActivated = get_option('hkd_tax_service_shipping_activated');
            $this->taxServiceVerificationCodeSameSKU = get_option('hkd_tax_service_verification_code_same_sku');
            $this->taxServiceEnabled = get_option('hkd_tax_service_enabled');
            $this->taxServiceType = get_option('hkd_tax_service_enabled_type');
            $this->taxServiceAutomaticallyPrintStatus = get_option('hkd_tax_service_automatically_print_status');

            add_action('woocommerce_order_status_changed', array($this, 'statusChangeHook'), 3, 3);
            add_action('woocommerce_order_edit_status', array($this, 'statusChangeHookSubscription'), 3, 2);
            add_action('wp_ajax_print_hdm_manually',  array($this, 'checkOrderActions'));
            add_filter('manage_edit-shop_order_columns', array($this, 'addTaxServiceColumnInOrdersPage'));
            add_action('manage_shop_order_posts_custom_column', array($this, 'addTaxServiceOrderContent'), 10, 2);
            add_filter( 'woocommerce_shop_order_list_table_columns',  array($this, 'addTaxServiceColumnInOrdersPage'));
            add_action( 'woocommerce_shop_order_list_table_custom_column',  array($this, 'addTaxServiceOrderContentHook2'), 10, 2 );

            if (is_admin()) {
                add_action('wp_ajax_nopriv_getPrintBody', array($this, 'getPrintBody'));
                add_action('wp_ajax_getPrintBody', array($this, 'getPrintBody'));
            }
        }

        public function getPrintBody()
        {
            $request = $this->hkd_recursive_sanitize_text_field($_POST);
            $type = $request['type'];
            extract(['orderId' => $request['orderId']]);
            if($type === 'print'){
                include($this->tax_service_dirname.'/checkout/email.php');
            }else{
                include($this->tax_service_dirname.'/checkout/refund.php');
            }
            $output = ob_get_clean();
            echo json_encode(['html' => $output]);
            exit;
        }

        private function hkd_recursive_sanitize_text_field( $array ) {
            foreach ( $array as $key => &$value ) {
                if ( is_array( $value ) ) {
                    $value = $this->hkd_recursive_sanitize_text_field($value);
                } else {
                    $value = sanitize_text_field( $value );
                }
            }
            return $array;
        }

        public function addTaxServiceColumnInOrdersPage($columns)
        {
            $columns['tax-service'] = __('Էլեկտրոնային ՀԴՄ', 'tax-service');
            $columns['tax-service-status'] = __('Էլեկտրոնային ՀԴՄ Կարգավիճակ', 'tax-service');
            return $columns;
        }

        public function addTaxServiceOrderContentHook2($column, $order)
        {
            $this->addTaxServiceOrderContent($column, $order->get_id());
        }

        public function addTaxServiceOrderContent($column, $post_id)
        {
            if ('tax-service-status' === $column) {
                $printData = $this->checkByOrderId($post_id);
                if(!$printData) {
                    echo '<mark class="order-status status-trash"><span>Not Print</span></mark>';
                }else{
                    switch ($printData->status){
                        case 'print':
                            echo '<mark class="order-status status-processing"><span>'. esc_html($printData->status).'</span></mark>';
                            break;
                        case 'copy':
                            echo '<mark class="order-status status-completed"><span>'. esc_html($printData->status).'</span></mark>';
                            break;
                        case 'refund':
                            echo '<mark class="order-status status-on-hold"><span>'. esc_html($printData->status).'</span></mark>';
                            break;

                    }
                }
            }

            if ('tax-service' === $column) {
                $order = wc_get_order($post_id);

                if ($order->has_status('processing') || $order->has_status('completed')) {
                    $thisGatewayId = $this->getPaymentGatewayByOrder($order)->id;
                    $taxEnabled = false;
                    if(!empty($this->taxServiceEnabled)) {
                        foreach ($this->taxServiceEnabled as $key => $item) {
                            if ($thisGatewayId == $key && $item) {
                                $taxEnabled = true;
                            }
                        }
                    }
                    if ($taxEnabled) {
                        if (isset($this->taxServiceType[$thisGatewayId])) {
                            if($this->taxServiceType[$thisGatewayId] == 'manually'){
                                $printData = $this->checkByOrderId($post_id);
                                if(!$printData) {
                                    $url = admin_url('admin-ajax.php?action=print_hdm_manually&order_id=' . $post_id);
                                    echo '<p><a class="button wc-action-button wc-action-button" href="' . esc_url($url) . '"> Տպել ՀԴՄ կտրոն </a></p>';
                                }
                            }else{
                                $printData = $this->checkByFailedOrderId($post_id);
                                if($printData) {
                                    $url = admin_url('admin-ajax.php?action=print_hdm_manually&order_id=' . $post_id);
                                    echo '<p><a class="button wc-action-button wc-action-button" href="' . esc_url($url) . '"> Տպել ՀԴՄ կտրոն </a></p>';
                                }
                            }
                        }
                    }
                }
            }
        }

        public function checkOrderActions()
        {
            if (isset($_GET['action']) && isset($_GET['order_id']) && esc_attr($_GET['action']) === 'print_hdm_manually') {
                $order = wc_get_order(esc_attr($_GET['order_id']));
                $thisGatewayId = $this->getPaymentGatewayByOrder($order)->id;
                if(isset($this->taxServiceType[$thisGatewayId]) && ( $this->taxServiceType[$thisGatewayId] == 'manually' ||  $this->taxServiceType[$thisGatewayId] == 'automatically' ) )
                    $print = $this->printTaxPayment($order, $this->taxServiceType[$thisGatewayId]);
                if(!$print){
                    wp_redirect( '/wp-admin/admin.php?page=tax-service-errors', 301 );
                    exit();
                }
            }
            wp_redirect( "/wp-admin/edit.php?post_type=shop_order", 301 );
            exit();
        }

        public function statusChangeHookSubscription($order_id, $new_status)
        {
            if ($new_status == 'completed' || $new_status == 'processing') {
                $order = wc_get_order($order_id);
                $thisGatewayId = $this->getPaymentGatewayByOrder($order)->id;
                $taxEnabled = false;
                if(!empty($this->taxServiceEnabled)) {
                    foreach ($this->taxServiceEnabled as $key => $item) {
                        if ($thisGatewayId == $key) {
                            $taxEnabled = true;
                        }
                    }
                }
                if ($taxEnabled) {
                    if (isset($this->taxServiceType[$thisGatewayId])) {
                        if ($this->taxServiceType[$thisGatewayId] == 'automatically' && $new_status ==  $this->taxServiceAutomaticallyPrintStatus) {
                            return $this->printTaxPayment($order, $this->taxServiceType[$thisGatewayId]);
                        }
                    } else {
                        if ($new_status == $this->taxServiceAutomaticallyPrintStatus) {
                            return $this->printTaxPayment($order);
                        }
                    }
                }
            }
        }

        public function statusChangeHook($order_id, $old_status, $new_status)
        {
            return $this->statusChangeHookSubscription($order_id, $new_status);
        }


        private function getPaymentGatewayByOrder($order)
        {
            return wc_get_payment_gateway_by_order($order);
        }

        public function taxServiceGetAndUpdateSeq()
        {
            $seq = get_option('taxServiceSeqNumber', 1);
            update_option('taxServiceSeqNumber', intval($seq) + 1);
            return intval($seq) + 1;
        }

        public function printTaxPayment($order, $type = 'manually')
        {
            $items = $order->get_items();
            $orderId = $order->get_id();
            $paymentGateway = $order->get_payment_method();

            if (!$this->taxRegisterNumber) {
                ErrorService::hkdTaxServiceInsertTaxErrorReport($orderId, 'CRN is not defined', 'CRN is not defined', $paymentGateway);
                return false;
            }

            $printData = $this->checkByOrderId($orderId);
            if($printData) {
                ErrorService::hkdTaxServiceInsertTaxErrorReport($orderId, 'Order Already Printed', 'Order Already Printed', $paymentGateway);
                return false;
            }

            switch ($this->taxServiceTaxType) {
                case 'without_vat':
                    $dep = 2;
                    break;
                case 'around_tax':
                    $dep = 3;
                    break;
                case 'micro':
                    $dep = 7;
                    break;
                default:
                    $dep = 1;
                    break;
            }

            $body = array(
                'mode' => 2,
                'crn' => $this->taxRegisterNumber,
                'seq' => $this->taxServiceGetAndUpdateSeq(),
                'cashierId' => 1,
                'items' => array()
            );

            $totalAmount = $order->get_total();
            if ($order->get_payment_method() == 'cod') {
                $body['cashAmount'] = (float)$totalAmount;
            } else {
                $body['cardAmount'] = (float)$totalAmount;
            }

            if ($order->get_shipping_total() > 0 && $this->taxServiceShippingActivated) {
                $body['items'][] = array(
                    'dep' => $dep,
                    'adgCode' => $this->taxServiceShippingAtgCode,
                    'goodCode' => $this->taxServiceShippingGoodCode,
                    'goodName' => $this->taxServiceShippingDescription,
                    'quantity' => 1,
                    'unit' => $this->taxServiceShippingUnitValue,
                    'price' => $order->get_shipping_total()
                );
            }

            foreach ($items as $item) {
                $product = wc_get_product( $item->get_product_id() );
                $productAtgCode = get_post_meta($item->get_product_id(), 'atgCode', true);
                $productUnitsValue = get_post_meta($item->get_product_id(), 'unitValue', true);
                $productValidationCode = get_post_meta($item->get_product_id(), 'validationCode', true);
                $validationCode = ($productValidationCode) ? $productValidationCode : (($this->taxServiceVerificationCodeSameSKU != 'no') ? $product->get_sku() : 'no');
                $itemAtgCode = ($productAtgCode) ? $productAtgCode : $this->taxServiceGlobalAtgCode;
                $itemUnit = ($productUnitsValue && $productAtgCode) ? $productUnitsValue : $this->taxServiceGlobalUnitValue;
                $item_data = array(
                    'dep' => $dep,
                    'adgCode' => $itemAtgCode,
                    'goodCode' => $validationCode,
                    'goodName' => mb_substr($item->get_name(), 0, 30),
                    'quantity' =>number_format((float)$item->get_quantity(), 1, '.', ''),
                    'unit' => $itemUnit,
                    'price' => number_format((float) $item->get_total() / $item->get_quantity(), 1, '.', '')
                );

                if ($product->is_on_sale()) {
                    $item_data['discount'] = $product->get_regular_price() - $product->get_sale_price();
                    $item_data['discountType'] = 2;
                    $item_data['price'] += $item_data['discount'];
                }
                $body['items'][] = $item_data;
            }

            $response = wp_remote_post($this->taxApiUrl . '/print', array(
                'sslverify' => false,
                'sslcertificates' => $this->taxServiceUploadFilePath,
                'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
                'body' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ));

            if (is_wp_error($response)) {
                ErrorService::hkdTaxServiceInsertTaxErrorReport($orderId, 'Tax service print request Error', $response->get_error_message(), $paymentGateway);
                return false;
            } else {
                $body = json_decode($response['body']);
                if (empty($body->error)) {
                    if ($body->code == 0) {
                        $qr = $body->result->qr;
                        $args = [
                            'order_id' => $orderId,
                            'crn' => $body->result->crn,
                            'sn' => $body->result->sn,
                            'tin' => $body->result->tin,
                            'taxpayer' => $body->result->taxpayer,
                            'address' => $body->result->address,
                            'time' => $body->result->time,
                            'status' => 'print',
                            'fiscal' => $body->result->fiscal,
                            'total' => $body->result->total,
                            'qr' => $qr,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                        $insert = $this->hkdTaxServiceInsertTaxSuccessReport($args);
                        if ($insert) {
                            $args['receiptId'] = $body->result->receiptId;

                            update_post_meta($orderId, 'TaxServiceReport', json_encode($args, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                            extract(['orderId' => $orderId]);
                            ob_start();
                            include($this->tax_service_dirname.'/checkout/email.php');
                            $message = ob_get_contents();
                            $email_subject = "Ձեր $orderId պատվերի էլեկտրոնային ՀԴՄ կտրոնը";
                            $admin_email = get_option('admin_email');
                            $user_email = $order->get_billing_email();
                            ob_end_clean();

                            $headers = array('From: '.get_bloginfo( 'name' ).' <'.$admin_email.'>');
                            add_filter('wp_mail_content_type', array($this, 'hkd_wp_mail_content_type'));
                            add_filter('wp_mail_charset', array($this, 'hkd_wp_mail_charset'));
                            wp_mail($user_email, $email_subject, $message, $headers);

                            $taxServiceSendToAdmin = get_option('hkd_tax_service_send_to_admin');
                            if($taxServiceSendToAdmin === 'yes'){
                                extract(['orderId' => $orderId]);
                                ob_start();
                                include($this->tax_service_dirname.'/checkout/email.php');
                                $message = ob_get_contents();
                                $email_subject = "$orderId պատվերի էլեկտրոնային ՀԴՄ կտրոնի օրինակը";
                                $admin_email = get_option('admin_email');
                                ob_end_clean();
                                $headers = array('From: '.get_bloginfo( 'name' ).' <'.$admin_email.'>');
                                add_filter('wp_mail_content_type', array($this, 'hkd_wp_mail_content_type'));
                                add_filter('wp_mail_charset', array($this, 'hkd_wp_mail_charset'));
                                wp_mail($admin_email, $email_subject, $message, $headers);
                            }
                            // sent user
                            return true;
                        }
                    } else {
                        ErrorService::hkdTaxServiceInsertTaxErrorReport($orderId, 'Tax service print request response Error', $body->errorMessage, $paymentGateway);
                        return false;
                    }
                } else {
                    ErrorService::hkdTaxServiceInsertTaxErrorReport($orderId, 'Tax service print request response Error', $body->error, $paymentGateway);
                    return false;
                }
            }
        }

        public function hkd_wp_mail_content_type()
        {
            return "text/html";
        }

        public function hkd_wp_mail_charset()
        {
            return "UTF-8";
        }

        private function getOrderIdFromReportId($reportId)
        {
            global $wpdb;
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "tax_service  WHERE id = %d", $reportId));
        }

        private function checkByOrderId($orderId)
        {
            global $wpdb;
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "tax_service  WHERE order_id = %d" , $orderId));
        }

        private function checkByFailedOrderId($orderId)
        {
            global $wpdb;
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "tax_service_report  WHERE order_id = %d" , $orderId));
        }

        private function updateStatusPaymentReport($reportId, $status)
        {
            global $wpdb;
            $data = array('status' => $status);
            $where = array('id' => $reportId);
            return $wpdb->update($wpdb->prefix . "tax_service", $data, $where);
        }

        public function refundPayment($reportId)
        {
            $printData = $this->getOrderIdFromReportId($reportId);
            $orderId = $printData->order_id;
            $order = wc_get_order($orderId);
            $paymentGateway = $order->get_payment_method();
            $items = $order->get_items();
            $taxServiceQRCode = $printData->qr;
            $qr_data = explode(', ', $taxServiceQRCode);
            $receipt_id = '';
            if (!empty($qr_data)) {
                foreach ($qr_data as $value) {
                    $value = trim($value);
                    if (strpos($value, 'Receipt_ID:') === 0) {
                        $receipt_id = trim(str_replace('Receipt_ID:', '', $value));
                        break;
                    }
                }
            }

            if (!empty($receipt_id)) {

                $body = array(
                    'crn' => $this->taxRegisterNumber,
                    'seq' => $this->taxServiceGetAndUpdateSeq(),
                    'receiptId' => $receipt_id,
                    'returnItemList' => array()
                );
                $receiptProductId = 0;

                $totalAmount = $order->get_total();
                if(!$this->taxServiceShippingActivated){
                    $totalAmount = $totalAmount - $order->get_shipping_total();
                }
                if ($order->get_payment_method() == 'cod') {
                    $body['cashAmountForReturn'] = $totalAmount;
                } else {
                    $body['cardAmountForReturn'] = $totalAmount;
                }
                if ($order->get_shipping_total() > 0 && $this->taxServiceShippingActivated) {
                    $body['returnItemList'][] = array(
                        'receiptProductId' => $receiptProductId,
                        'quantity' => 1
                    );
                    $receiptProductId++;
                }

                foreach ($items as $item) {
                    $body['returnItemList'][] = array(
                        'receiptProductId' => $receiptProductId,
                        'quantity' => $item->get_quantity()
                    );
                    $receiptProductId++;
                }

                $response = wp_remote_post($this->taxApiUrl . '/printReturnReceipt', array(
                    'sslverify' => false,
                    'sslcertificates' => $this->taxServiceUploadFilePath,
                    'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
                    'body' => json_encode($body)
                ));


                if (!is_wp_error($response)) {
                    $body = json_decode($response['body']);
                    if (empty($body->error)) {
                        if ($body->code == 0) {

                            $args = [
                                'order_id' => $orderId,
                                'crn' => $body->result->crn,
                                'sn' => $body->result->sn,
                                'tin' => $body->result->tin,
                                'taxpayer' => $body->result->taxpayer,
                                'address' => $body->result->address,
                                'time' => $body->result->time,
                                'receiptId' => $body->result->receiptId,
                                'status' => 'print',
                                'fiscal' => $body->result->fiscal,
                                'total' => $body->result->total,
                                'qr' => $body->result->qr,
                                'created_at' => date('Y-m-d H:i:s'),
                            ];
                            update_post_meta($orderId, 'TaxServiceReturnReport', json_encode($args, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                            $this->updateStatusPaymentReport($reportId, 'refund');
                            $taxServiceSendRefundToUser = get_option('hkd_tax_service_send_refund_to_user');
                            $taxServiceSendRefundToAdmin = get_option('hkd_tax_service_send_refund_to_admin');
                            if($taxServiceSendRefundToUser === 'yes'){
                                extract(['orderId' => $orderId]);
                                ob_start();
                                include($this->tax_service_dirname.'/checkout/refund.php');
                                $message = ob_get_contents();
                                $email_subject = "Ձեր $orderId պատվերի վերադարձի էլեկտրոնային ՀԴՄ կտրոնը";
                                $admin_email = get_option('admin_email');
                                $user_email = $order->get_billing_email();
                                ob_end_clean();
                                $headers = array('From: '.get_bloginfo( 'name' ).' <'.$admin_email.'>');
                                add_filter('wp_mail_content_type', array($this, 'hkd_wp_mail_content_type'));
                                add_filter('wp_mail_charset', array($this, 'hkd_wp_mail_charset'));
                                wp_mail($user_email, $email_subject, $message, $headers);
                            }


                            if($taxServiceSendRefundToAdmin === 'yes'){
                                extract(['orderId' => $orderId]);
                                ob_start();
                                include($this->tax_service_dirname.'/checkout/refund.php');
                                $message = ob_get_contents();
                                $email_subject = "$orderId պատվերի վերադարձի էլեկտրոնային ՀԴՄ կտրոնի օրինակը";
                                $admin_email = get_option('admin_email');
                                ob_end_clean();
                                $headers = array('From: '.get_bloginfo( 'name' ).' <'.$admin_email.'>');
                                add_filter('wp_mail_content_type', array($this, 'hkd_wp_mail_content_type'));
                                add_filter('wp_mail_charset', array($this, 'hkd_wp_mail_charset'));
                                wp_mail($admin_email, $email_subject, $message, $headers);
                            }

                            return true;
                        } else {
                            ErrorService::hkdTaxServiceInsertTaxErrorReport($orderId, 'Tax service refund request response Error', $body->errorMessage, $paymentGateway);
                            return false;
                        }
                    } else {
                        ErrorService::hkdTaxServiceInsertTaxErrorReport($orderId, 'Tax service refund request response Error', $body->error, $paymentGateway);
                        return false;
                    }
                }
            }
        }

        public function copyPrintPayment($reportId)
        {
            $printData = $this->getOrderIdFromReportId($reportId);
            $orderId = $printData->order_id;
            $order = wc_get_order($orderId);
            $taxServiceQRCode = $printData->qr;
            $qr_data = explode(', ', $taxServiceQRCode);
            $paymentGateway = $order->get_payment_method();
            $receipt_id = '';
            if (!empty($qr_data)) {
                foreach ($qr_data as $value) {
                    $value = trim($value);
                    if (strpos($value, 'Receipt_ID:') === 0) {
                        $receipt_id = trim(str_replace('Receipt_ID:', '', $value));
                        break;
                    }
                }
            }

            if (!empty($receipt_id)) {
                $body = array(
                    'crn' => $this->taxRegisterNumber,
                    'seq' => $this->taxServiceGetAndUpdateSeq(),
                    'receiptId' => $receipt_id,
                );

                $response = wp_remote_post($this->taxApiUrl . '/printCopy', array(
                    'sslverify' => false,
                    'sslcertificates' => $this->taxServiceUploadFilePath,
                    'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
                    'body' => json_encode($body)
                ));

                if (!is_wp_error($response)) {
                    $body = json_decode($response['body']);
                    if (empty($body->error)) {
                        if ($body->code == 0) {
                            $this->updateStatusPaymentReport($reportId, 'copy');

                            extract(['orderId' => $orderId]);
                            ob_start();
                            include($this->tax_service_dirname.'/checkout/email.php');
                            $message = ob_get_contents();
                            $email_subject = "Ձեր $orderId պատվերի էլեկտրոնային ՀԴՄ կտրոնը";
                            $admin_email = get_option('admin_email');
                            $user_email = $order->get_billing_email();
                            ob_end_clean();

                            $headers = array('From: '.get_bloginfo( 'name' ).' <'.$admin_email.'>');
                            add_filter('wp_mail_content_type', create_function('', 'return "text/html"; '));
                            add_filter('wp_mail_charset', create_function('', 'return "UTF-8"; '));
                            wp_mail($user_email, $email_subject, $message, $headers);

                            return true;
                        } else {
                            ErrorService::hkdTaxServiceInsertTaxErrorReport($orderId, 'Tax service copy request response Error', $body->errorMessage, $paymentGateway);
                            return false;
                        }
                    } else {
                        ErrorService::hkdTaxServiceInsertTaxErrorReport($orderId, 'Tax service copy request response Error', $body->error, $paymentGateway);
                        return false;
                    }
                }
            }
        }

        public function hkdTaxServiceInsertTaxSuccessReport($args = array())
        {
            global $wpdb;
            $table_name = $wpdb->prefix . 'tax_service';
            if ($wpdb->insert($table_name, $args)) {
                return $wpdb->insert_id;
            }
            return false;
        }

    }
}
