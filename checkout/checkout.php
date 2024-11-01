<?php
add_action('woocommerce_thankyou', 'woocomerceTaxServiceThankYouPage', 999);
function woocomerceTaxServiceThankYouPage($order_id)
{
    global $tax_service_plugin_url;
    $order = wc_get_order($order_id);
    $taxServiceReport = get_post_meta($order->get_id(), 'TaxServiceReport', true);
    if ($taxServiceReport) {
        $taxServiceReportDecoded = json_decode($taxServiceReport, true);
        $taxServiceTin = get_option('hkd_tax_service_tin');
        $taxServiceGlobalAtgCode = get_option('hkd_tax_service_atg_code');
        $taxServiceGlobalUnitValue = get_option('hkd_tax_service_units_value');
        $taxServiceTaxType = get_option('hkd_tax_service_tax_type');
        $taxServiceVatPercent = get_option('hkd_tax_service_vat_percent');
        $taxServiceFirstSection = get_option('hkd_tax_service_section_1');
        $taxServiceSecondSection = get_option('hkd_tax_service_section_2');
        $taxServiceCheckBothType = get_option('hkd_tax_service_check_both_type');
        $taxServiceTreasurer = get_option('hkd_tax_service_treasurer');
        $taxServiceShippingAtgCode = get_option('hkd_tax_service_shipping_atg_code');
        $taxServiceShippingDescription = get_option('hkd_tax_service_shipping_description');
        $taxServiceShippingGoodCode = get_option('hkd_tax_service_shipping_good_code');
        $taxServiceShippingUnitValue = get_option('hkd_tax_service_shipping_unit_value');
        $taxServiceShippingActivated = get_option('hkd_tax_service_shipping_activated');
        $items = $order->get_items();
        $taxServiceVerificationCodeSameSKU = get_option('hkd_tax_service_verification_code_same_sku');
        $count = 0;
        $vatPrice = 0;
        $withoutPrice = 0;
        if ($taxServiceTaxType === 'vat' && $taxServiceCheckBothType != 'no') {
            foreach ($items as $item) {
                $checkIsVaTValue = get_post_meta($item->get_product_id(), 'checkIsVaT', true);
                if ($checkIsVaTValue === 'yes') {
                    $vatPrice += $item->get_total();
                } else {
                    $withoutPrice += $item->get_total();
                }
            }
        } else if ($taxServiceTaxType === 'vat') {
            foreach ($items as $item) {
                $vatPrice += $item->get_total();
            }
        }
        $isSale = false;
        $salePrice = 0;
        foreach ($items as $item) {
            $product = $item->get_product();
            if ($product->is_on_sale()) {
                $isSale = true;
                break;
            }
        }
        $receiptTime = date("Y-m-d H:i:s", substr($taxServiceReportDecoded['time'], 0, 10));


        $printQr = explode(',', $taxServiceReportDecoded['qr']);
        foreach ($printQr as $item) {
            if (strpos($item, 'Receipt_ID') !== false) {
                $receiptId = str_replace('Receipt_ID:', '', $item);
                $receiptId = sprintf("%08d", $receiptId);

            }
        }
        if (substr(get_headers('https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . str_replace(' ', '', esc_html($taxServiceReportDecoded['qr'])))[0], 9, 3) != "200") {
            if (substr(get_headers('https://quickchart.io/chart?cht=qr&chs=300x300&chl=' . str_replace(' ', '', esc_html($taxServiceReportDecoded['qr'])))[0], 9, 3) != "200") {
                $QRUrl = 'https://qrcode.tec-it.com/API/QRCode?data=' . str_replace(' ', '', esc_html($taxServiceReportDecoded['qr']));
            } else {
                $QRUrl = 'https://quickchart.io/chart?cht=qr&chs=300x300&chl=' . str_replace(' ', '', esc_html($taxServiceReportDecoded['qr']));
            }
        } else {
            $QRUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . str_replace(' ', '', esc_html($taxServiceReportDecoded['qr']));
        }

        ?>
        <div
                style="box-sizing: border-box;width: 100%;padding-right: 15px;padding-left: 15px;margin-right: auto;margin-left: auto;min-width: 992px!important;">
            <div
                    style="    max-width: 100%;box-sizing: border-box;position: relative;-ms-flex-direction: column;flex-direction: column;min-width: 0;word-wrap: break-word;background-color: #fff;background-clip: border-box;border: 1px solid rgba(0,0,0,.125);border-radius: .25rem;margin-top: 1rem!important;">
                <div
                        style="box-sizing: border-box;padding: .75rem 1.25rem;margin-bottom: 0;background-color: rgba(0,0,0,.03);border-bottom: 1px solid rgba(0,0,0,.125);border-radius: calc(.25rem - 1px) calc(.25rem - 1px) 0 0;">
                    ԿՏՐՈՆԻ ՖԻՍԿԱԼ ՀԱՄԱՐ <strong
                            style="box-sizing: border-box;font-weight: bolder;"><?php echo esc_html($taxServiceReportDecoded['fiscal']) ?></strong>
                    <span style="box-sizing: border-box;float: right!important;"> <strong
                                style="box-sizing: border-box;font-weight: bolder;">ՊԱՏՎԵՐԻ ՀԱՄԱՐ </strong> <?php echo esc_html($order_id) ?></span>
                </div>
                <div style="box-sizing: border-box;-ms-flex: 1 1 auto;flex: 1 1 auto;padding: 1.25rem;">
                    <div
                            style="box-sizing: border-box;display: flex;-ms-flex-wrap: wrap;flex-wrap: wrap;margin-right: -15px;margin-left: -15px;margin-bottom: 3rem!important;">
                        <div
                                style="box-sizing: border-box;position: relative;width: 100%;min-height: 1px;padding-right: 15px;padding-left: 15px;-ms-flex: 0 0 50%;flex: 0 0 50%;max-width: 50%;margin-top: 1.5rem!important;">
                            <h6 style="box-sizing: border-box;margin-top: 0;margin-bottom: .5rem;font-family: inherit;font-weight: 500;line-height: 1.2;color: inherit;font-size: 1rem;">
                                Կազմակերպության տվյալներ</h6>
                            <div style="box-sizing: border-box;"><strong
                                        style="box-sizing: border-box;font-weight: bolder;"><?php echo esc_html($taxServiceReportDecoded['taxpayer']) ?></strong>
                            </div>
                            <div style="box-sizing: border-box;"><strong
                                        style="box-sizing: border-box;font-weight: bolder;"><?php echo esc_html($taxServiceReportDecoded['address']) ?></strong>
                            </div>
                            <div style="box-sizing: border-box;">ՀՎՀՀ՝ <strong
                                        style="box-sizing: border-box;font-weight: bolder;"><?php echo esc_html($taxServiceTin) ?></strong>
                            </div>
                            <div style="box-sizing: border-box;">ԳՀ՝ <strong
                                        style="box-sizing: border-box;font-weight: bolder;"><?php echo esc_html($taxServiceReportDecoded['crn']) ?></strong>
                            </div>
                            <div style="box-sizing: border-box;">ՍՀ՝ <strong
                                        style="box-sizing: border-box;font-weight: bolder;"><?php echo esc_html($taxServiceReportDecoded['sn']) ?></strong>
                            </div>
                            <div style="box-sizing: border-box;">
                                Կայք՝ <strong
                                        style="box-sizing: border-box;font-weight: bolder;"><?php echo esc_html(get_bloginfo('url')) ?></strong>
                            </div>
                        </div>
                        <div
                                style="box-sizing: border-box;position: relative;width: 100%;min-height: 1px;padding-right: 15px;padding-left: 15px;-ms-flex: 0 0 33.333333%;flex: 0 0 33.333333%;max-width: 33.333333%;margin-top: 1.5rem!important;">
                            <h6 style="box-sizing: border-box;margin-top: 0;margin-bottom: .5rem;font-family: inherit;font-weight: 500;line-height: 1.2;color: inherit;font-size: 1rem;">
                                Հաճախորդի տվյալներ</h6>
                            <div style="box-sizing: border-box;">
                                <strong style="box-sizing: border-box;font-weight: bolder;"><?php echo esc_html($order->get_billing_first_name()) . ' ' . esc_html($order->get_billing_last_name()) ?></strong>
                            </div>
                            <?php if ($order->get_billing_address_1()): ?>
                                <div style="box-sizing: border-box;">Հասցե՝ <strong
                                            style="box-sizing: border-box;font-weight: bolder;"><?php echo esc_html($order->get_billing_address_1()) ?></strong>
                                </div>
                            <?php endif; ?>
                            <div style="box-sizing: border-box;">Էլ-հասցե՝ <strong
                                        style="box-sizing: border-box;font-weight: bolder;"><?php echo esc_html($order->get_billing_email()) ?></strong>
                            </div>
                            <div style="box-sizing: border-box;">Հեռախոս՝ <strong
                                        style="box-sizing: border-box;font-weight: bolder;"><?php echo esc_html($order->get_billing_phone()) ?></strong>
                            </div>
                        </div>
                        <div style="box-sizing: border-box;position: relative;width: 100%;min-height: 1px;padding-right: 15px;padding-left: 15px;-ms-flex: 0 0 16.666667%;flex: 0 0 16.666667%;max-width: 16.666667%;display: flex!important;-ms-flex-pack: end!important;justify-content: flex-end!important;margin-top: 1.5rem!important;">
                            <div
                                    style="font-size: 0.9rem;box-sizing: border-box;display: flex;-ms-flex-wrap: wrap;flex-wrap: wrap;margin-right: -15px;margin-left: -15px;-ms-flex-align: center!important;align-items: center!important;">
                                <div
                                        style="box-sizing: border-box;position: relative;width: auto;min-height: 1px;padding-right: 15px;padding-left: 15px;-ms-flex: 0 0 auto;flex: 0 0 auto;max-width: none;">
                                    <img src="<?php echo $QRUrl; ?>"
                                         alt=""
                                         style="max-width: 114px;box-sizing: border-box;vertical-align: middle;border-style: none;page-break-inside: avoid;height: auto;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if (isset($receiptId)): ?>
                        <div
                                style="margin-top: 20px;box-sizing: border-box;display: flex;-ms-flex-wrap: wrap;flex-wrap: wrap;margin-right: -15px;margin-left: -15px;">
                            <div
                                    style="box-sizing: border-box;position: relative;width: 100%;min-height: 1px;padding-right: 15px;padding-left: 15px;-ms-flex: 0 0 100%;flex: 0 0 100%;max-width: 100%;">
                                <div style="box-sizing: border-box;">Էլեկտրոնային կտրոնի հերթական համար (ԿՀ) -
                                    <strong style="box-sizing: border-box;font-weight: bolder;"><?php echo esc_html($receiptId) ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div
                            style="margin-top: 20px;box-sizing: border-box;display: flex;-ms-flex-wrap: wrap;flex-wrap: wrap;margin-right: -15px;margin-left: -15px;">
                        <div
                                style="box-sizing: border-box;position: relative;width: 100%;min-height: 1px;padding-right: 15px;padding-left: 15px;-ms-flex: 0 0 100%;flex: 0 0 100%;max-width: 100%;">
                            <div style="box-sizing: border-box;">Կտրոնի գրանցման/տպման ամիս ամսաթիվ ու ժամ։ (&#177; 4)
                                <strong style="box-sizing: border-box;font-weight: bolder;"><?php echo esc_html($receiptTime); ?></strong>
                            </div>
                        </div>
                    </div>
                    <div style="box-sizing: border-box;display: flex;-ms-flex-wrap: wrap;flex-wrap: wrap;margin-right: -15px;margin-left: -15px;margin-bottom: .5rem!important;">
                        <div style="box-sizing: border-box;position: relative;width: 100%;min-height: 1px;padding-right: 15px;padding-left: 15px;-ms-flex: 0 0 100%;flex: 0 0 100%;max-width: 100%;">
                            <div style="box-sizing: border-box;">ԳԱՆՁԱՊԱՀ ։ <strong
                                        style="box-sizing: border-box;font-weight: bolder;"><?php echo esc_html($taxServiceTreasurer) ?></strong>
                            </div>
                        </div>
                    </div>
                    <div
                            style="margin-top: 20px;box-sizing: border-box;display: flex;-ms-flex-wrap: wrap;flex-wrap: wrap;margin-right: -15px;margin-left: -15px;margin-bottom: 1rem!important;">
                        <div
                                style="box-sizing: border-box;position: relative;width: 100%;min-height: 1px;padding-right: 15px;padding-left: 15px;-ms-flex: 0 0 100%;flex: 0 0 100%;max-width: 100%;">
                            <?php
                            switch ($taxServiceTaxType) {
                                case 'vat':
                                    echo '<div style="box-sizing: border-box;"><strong style="box-sizing: border-box;font-weight: bolder;">ԲԱԺԻՆ՝ 1</strong></div>';
                                    echo '<div style="box-sizing: border-box;">/ԱԱՀ-ով հարկվող / = ' . number_format((float)$vatPrice, 2) . ' </div>';
                                    break;
                                case 'without_vat':
                                    echo '<div style="box-sizing: border-box;"><strong style="box-sizing: border-box;font-weight: bolder;">ԲԱԺԻՆ՝ 2</strong></div>';
                                    echo '<div style="box-sizing: border-box;">/ԱԱՀ-ով չհարկվող/ = ' . number_format((float)$taxServiceReportDecoded['total'], 2) . ' </div>';
                                    break;
                                case 'around_tax':
                                    echo '<div style="box-sizing: border-box;"><strong style="box-sizing: border-box;font-weight: bolder;">ԲԱԺԻՆ՝ 3</strong></div>';
                                    echo '<div style="box-sizing: border-box;">/Շրջ. հարկ/ = ' . number_format((float)$taxServiceReportDecoded['total'], 2) . ' </div>';
                                    break;
                                case 'micro':
                                    echo '<div style="box-sizing: border-box;"><strong style="box-sizing: border-box;font-weight: bolder;">ԲԱԺԻՆ՝ 7</strong></div>';
                                    echo '<div style="box-sizing: border-box;">/Միկրոձեռնարկատիրություն/ = ' . number_format((float)$taxServiceReportDecoded['total'], 2) . ' </div>';
                                    break;
                            }
                            ?>

                            <?php if ($taxServiceTaxType === 'vat'): ?>
                                <div style="box-sizing: border-box;">Որից ԱԱՀ
                                    = <?php echo esc_html(number_format((float)(($vatPrice * $taxServiceVatPercent) / 100), 2)); ?></div>
                            <?php else: ?>
                                <div style="box-sizing: border-box;">Որից ԱԱՀ = 0.00</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="overflow: auto;box-sizing: border-box; margin-top: 20px;">
                        <table
                                style="box-sizing: border-box;border-collapse: collapse!important;width: 100%;margin-bottom: 1rem;background-color: transparent;">
                            <thead style="box-sizing: border-box;display: table-header-group;">
                            <tr style="box-sizing: border-box;page-break-inside: avoid;">
                                <th
                                        style="box-sizing: border-box;text-align: inherit;padding: .75rem;vertical-align: bottom;border-top: 1px solid #dee2e6;border-bottom: 2px solid #dee2e6;background-color: #fff!important;">
                                    #
                                </th>
                                <th style="box-sizing: border-box;text-align: inherit;padding: .75rem;vertical-align: bottom;border-top: 1px solid #dee2e6;border-bottom: 2px solid #dee2e6;background-color: #fff!important;">
                                    ԱՏԳ կոդ
                                </th>
                                <th style="box-sizing: border-box;text-align: inherit;padding: .75rem;vertical-align: bottom;border-top: 1px solid #dee2e6;border-bottom: 2px solid #dee2e6;background-color: #fff!important;">
                                    Ապրանքի Կոդ
                                </th>
                                <th
                                        style="box-sizing: border-box;text-align: inherit;padding: .75rem;vertical-align: bottom;border-top: 1px solid #dee2e6;border-bottom: 2px solid #dee2e6;background-color: #fff!important;">
                                    Նկարագրություն
                                </th>
                                <th
                                        style="box-sizing: border-box;text-align: inherit;padding: .75rem;vertical-align: bottom;border-top: 1px solid #dee2e6;border-bottom: 2px solid #dee2e6;background-color: #fff!important;">
                                    Քանակ
                                </th>
                                <th
                                        style="box-sizing: border-box;text-align: inherit;padding: .75rem;vertical-align: bottom;border-top: 1px solid #dee2e6;border-bottom: 2px solid #dee2e6;background-color: #fff!important;">
                                    Չափման միավոր
                                </th>
                                <th
                                        style="box-sizing: border-box;text-align: inherit;padding: .75rem;vertical-align: bottom;border-top: 1px solid #dee2e6;border-bottom: 2px solid #dee2e6;background-color: #fff!important;">
                                    Միավորի գին
                                </th>
                                <?php if ($isSale): ?>
                                    <th
                                            style="box-sizing: border-box;text-align: inherit;padding: .75rem;vertical-align: bottom;border-top: 1px solid #dee2e6;border-bottom: 2px solid #dee2e6;background-color: #fff!important;">
                                        Զեղչ
                                    </th>
                                <?php endif; ?>
                                <th
                                        style="box-sizing: border-box;text-align: inherit;padding: .75rem;vertical-align: bottom;border-top: 1px solid #dee2e6;border-bottom: 2px solid #dee2e6;background-color: #fff!important;">
                                    Ընդամենը
                                </th>
                            </tr>
                            </thead>
                            <tbody style="box-sizing: border-box;">

                            <?php foreach ($items as $item):
                                $count++;
                                $product = $item->get_product();

                                $productAtgCode = get_post_meta($item->get_product_id(), 'atgCode', true);
                                $productUnitsValue = get_post_meta($item->get_product_id(), 'unitValue', true);
                                $checkIsVaTValue = get_post_meta($item->get_product_id(), 'checkIsVaT', true);
                                $productValidationCode = get_post_meta($item->get_product_id(), 'validationCode', true);
                                $itemAtgCode = ($productAtgCode) ? $productAtgCode : $taxServiceGlobalAtgCode;
                                $validationCode = ($productValidationCode) ? $productValidationCode : (($taxServiceVerificationCodeSameSKU != 'no') ? $product->get_sku() : 'no');
                                $itemUnit = ($productUnitsValue && $productAtgCode) ? $productUnitsValue : $taxServiceGlobalUnitValue;
                                if ($checkIsVaTValue !== 'yes' && $taxServiceCheckBothType != 'no' && $taxServiceTaxType === 'vat') continue;
                                ?>
                                <tr style="box-sizing: border-box;page-break-inside: avoid;">
                                    <td
                                            style="box-sizing: border-box;padding: .75rem;vertical-align: top;border-top: 1px solid #dee2e6;background-color: #fff!important;"><?php echo esc_html($count) ?></td>
                                    <td
                                            style="box-sizing: border-box;padding: .75rem;vertical-align: top;border-top: 1px solid #dee2e6;background-color: #fff!important;"><?php echo esc_html($itemAtgCode) ?></td>
                                    <td
                                            style="box-sizing: border-box;padding: .75rem;vertical-align: top;border-top: 1px solid #dee2e6;background-color: #fff!important;"><?php echo esc_html($validationCode) ?></td>
                                    <td
                                            style="box-sizing: border-box;padding: .75rem;vertical-align: top;border-top: 1px solid #dee2e6;background-color: #fff!important;"><?php echo esc_html($item->get_name()) ?></td>
                                    <td
                                            style="box-sizing: border-box;padding: .75rem;vertical-align: top;border-top: 1px solid #dee2e6;background-color: #fff!important;"><?php echo esc_html($item->get_quantity()) ?></td>
                                    <td
                                            style="box-sizing: border-box;padding: .75rem;vertical-align: top;border-top: 1px solid #dee2e6;background-color: #fff!important;"><?php echo esc_html($itemUnit) ?></td>
                                    <td
                                            style="box-sizing: border-box;padding: .75rem;vertical-align: top;border-top: 1px solid #dee2e6;background-color: #fff!important;"><?php echo esc_html($product->get_regular_price()) . ' դրամ'; ?></td>
                                    <?php if ($isSale): ?>
                                        <td
                                                style="box-sizing: border-box;padding: .75rem;vertical-align: top;border-top: 1px solid #dee2e6;background-color: #fff!important;"><?php if ($product->is_on_sale()) {
                                                $isSale = true;
                                                $salePrice += ((float)$product->get_regular_price() - (float)$product->get_sale_price());
                                                echo $product->get_regular_price() - $product->get_sale_price() . ' դրամ';
                                            } ?></td>
                                    <?php endif; ?>
                                    <td
                                            style="box-sizing: border-box;padding: .75rem;vertical-align: top;border-top: 1px solid #dee2e6;background-color: #fff!important;"><?php echo esc_html($item->get_total()) . ' դրամ'; ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if ($order->get_shipping_total() > 0 && $taxServiceShippingActivated): $count++; ?>
                                <tr style="box-sizing: border-box;page-break-inside: avoid;">
                                    <td
                                            style="color:#3B4B58;box-sizing: border-box;padding: .75rem;vertical-align: top;border-top: 1px solid #dee2e6;background-color: #fff!important;"><?php echo esc_html($count) ?></td>
                                    <td
                                            style="color:#3B4B58;box-sizing: border-box;padding: .75rem;vertical-align: top;border-top: 1px solid #dee2e6;background-color: #fff!important;"><?php echo esc_html($taxServiceShippingAtgCode) ?></td>
                                    <td
                                            style="color:#3B4B58;box-sizing: border-box;padding: .75rem;vertical-align: top;border-top: 1px solid #dee2e6;background-color: #fff!important;"><?php echo esc_html($taxServiceShippingGoodCode) ?></td>
                                    <td
                                            style="color:#3B4B58;box-sizing: border-box;padding: .75rem;vertical-align: top;border-top: 1px solid #dee2e6;background-color: #fff!important;"><?php echo esc_html($taxServiceShippingDescription) ?></td>
                                    <td
                                            style="color:#3B4B58;box-sizing: border-box;padding: .75rem;vertical-align: top;border-top: 1px solid #dee2e6;background-color: #fff!important;">
                                        1
                                    </td>
                                    <td
                                            style="color:#3B4B58;box-sizing: border-box;padding: .75rem;vertical-align: top;border-top: 1px solid #dee2e6;background-color: #fff!important;"><?php echo esc_html($taxServiceShippingUnitValue) ?></td>
                                    <td
                                            style="color:#3B4B58;box-sizing: border-box;padding: .75rem;vertical-align: top;border-top: 1px solid #dee2e6;background-color: #fff!important;"><?php echo esc_html($order->get_shipping_total()) . ' դրամ'; ?></td>
                                    <?php if ($isSale): ?>
                                        <td
                                                style="color:#3B4B58;box-sizing: border-box;padding: .75rem;vertical-align: top;border-top: 1px solid #dee2e6;background-color: #fff!important;">
                                        </td>
                                    <?php endif; ?>
                                    <td
                                            style="color:#3B4B58;box-sizing: border-box;padding: .75rem;vertical-align: top;border-top: 1px solid #dee2e6;background-color: #fff!important;"><?php echo esc_html($order->get_shipping_total()) . ' դրամ'; ?></td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($taxServiceCheckBothType != 'no' && $taxServiceTaxType === 'vat' && $withoutPrice != 0): ?>
                        <div
                                style="margin-top: 20px;box-sizing: border-box;display: flex;-ms-flex-wrap: wrap;flex-wrap: wrap;margin-right: -15px;margin-left: -15px;margin-bottom: 1rem!important;">
                            <div
                                    style="box-sizing: border-box;position: relative;width: 100%;min-height: 1px;padding-right: 15px;padding-left: 15px;-ms-flex: 0 0 100%;flex: 0 0 100%;max-width: 100%;">
                                <div style="box-sizing: border-box;"><strong
                                            style="box-sizing: border-box;font-weight: bolder;">ԲԱԺԻՆ՝ 2</strong>
                                </div>
                                <div style="box-sizing: border-box;"> /ԱՀՀ-ով չհարկվող/
                                    = <?php echo esc_html(number_format((float)$withoutPrice, 2)); ?>
                                </div>
                            </div>
                        </div>
                        <div style="overflow: auto;box-sizing: border-box; margin-top: 20px;">
                            <table
                                    style="box-sizing: border-box;border-collapse: collapse!important;width: 100%;margin-bottom: 1rem;background-color: transparent;">
                                <thead style="box-sizing: border-box;display: table-header-group;">
                                <tr>
                                    <th>#</th>
                                    <th>ԱՏԳ կոդ</th>
                                    <th>Ապրանքի Կոդ</th>
                                    <th>Նկարագրություն</th>
                                    <th>Քանակ</th>
                                    <th>Չափման միավոր</th>
                                    <th>Միավորի գին</th>
                                    <?php if ($isSale): ?>
                                        <th>Զեղչ</th>
                                    <?php endif; ?>
                                    <th>Ընդամենը</th>
                                </tr>
                                </thead>
                                <tbody>

                                <?php foreach ($items as $item):
                                    $count++;
                                    $product = $item->get_product();
                                    $productAtgCode = get_post_meta($item->get_product_id(), 'atgCode', true);
                                    $productUnitsValue = get_post_meta($item->get_product_id(), 'unitValue', true);
                                    $checkIsVaTValue = get_post_meta($item->get_product_id(), 'checkIsVaT', true);
                                    $productValidationCode = get_post_meta($item->get_product_id(), 'validationCode', true);
                                    $itemAtgCode = ($productAtgCode) ? $productAtgCode : $taxServiceGlobalAtgCode;
                                    $validationCode = ($productValidationCode) ? $productValidationCode : (($taxServiceVerificationCodeSameSKU != 'no') ? $product->get_sku() : 'no');
                                    $itemUnit = ($productUnitsValue && $productAtgCode) ? $productUnitsValue : $taxServiceGlobalUnitValue;
                                    if ($checkIsVaTValue === 'yes') continue;
                                    ?>
                                    <tr style="box-sizing: border-box;page-break-inside: avoid;">
                                        <td style="box-sizing: border-box;"><?php echo esc_html($count) ?></td>
                                        <td style="box-sizing: border-box;"><?php echo esc_html($itemAtgCode) ?></td>
                                        <td style="box-sizing: border-box;"><?php echo esc_html($validationCode) ?></td>
                                        <td style="box-sizing: border-box;"><?php echo esc_html($item->get_name()) ?></td>
                                        <td style="box-sizing: border-box;"><?php echo esc_html($item->get_quantity()) ?></td>
                                        <td style="box-sizing: border-box;"><?php echo esc_html($itemUnit) ?></td>
                                        <td
                                                style="box-sizing: border-box;"><?php echo esc_html($product->get_regular_price()) ?></td>
                                        <?php if ($isSale): ?>
                                            <td style="box-sizing: border-box;"><?php if ($product->is_on_sale()) {
                                                    $isSale = true;
                                                    $salePrice += ((float)$product->get_regular_price() - (float)$product->get_sale_price());
                                                    echo $product->get_regular_price() - $product->get_sale_price() . ' դրամ';
                                                } ?></td>
                                        <?php endif; ?>
                                        <td style="box-sizing: border-box;"><?php echo esc_html($item->get_total()) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php endif; ?>

                    <div
                            style="width:100%;margin-top: 20px;box-sizing: border-box;display: flex;-ms-flex-wrap: wrap;flex-wrap: wrap;margin-right: -15px;margin-left: -15px;margin-bottom: 1rem!important;">
                        <div
                                style="box-sizing: border-box;position: relative;width: 100%;min-height: 1px;padding-right: 15px;padding-left: 15px;-ms-flex: 0 0 33.333333%;flex: 0 0 33.333333%;max-width: 33.333333%;">
                            <strong style="box-sizing: border-box;font-weight: bolder;"> (Ֆ)</strong></div>
                        <div
                                style="box-sizing: border-box;position: relative;width: 100%;min-height: 1px;padding-right: 15px;padding-left: 15px;-ms-flex: 0 0 50%;flex: 0 0 50%;max-width: 50%;margin-left: auto!important;text-align: right!important;">
                            <div
                                    style="box-sizing: border-box;position: relative;width: 100%;min-height: 1px;padding-right: 15px;padding-left: 15px;-ms-flex: 0 0 100%;flex: 0 0 100%;max-width: 100%;">
                                <div style="box-sizing: border-box;"><strong
                                            style="box-sizing: border-box;font-weight: bolder;">Ընդամենը՝&nbsp;&nbsp;&nbsp;<?php echo esc_html(number_format((float)$taxServiceReportDecoded['total'], 2)) ?></strong>
                                </div>
                                <?php if ($isSale): ?>
                                    <div style="box-sizing: border-box;"><strong
                                                style="box-sizing: border-box;font-weight: bolder;">Զեղչ՝&nbsp;&nbsp;&nbsp;&nbsp;<?php echo esc_html(number_format((float)$salePrice, 2)); ?></strong>
                                    </div>
                                <?php endif; ?>
                                <?php if ($order->get_payment_method() == 'cod'): ?>
                                    <div style="box-sizing: border-box;"><strong
                                                style="box-sizing: border-box;font-weight: bolder;">Առձեռն՝&nbsp;&nbsp;&nbsp;<?php echo esc_html(number_format((float)$taxServiceReportDecoded['total'], 2)) ?>
                                        </strong></div>
                                <?php else: ?>
                                    <div style="box-sizing: border-box;"><strong
                                                style="box-sizing: border-box;font-weight: bolder;">Անկանխիկ՝ &nbsp;&nbsp;&nbsp;<?php echo esc_html(number_format((float)$taxServiceReportDecoded['total'], 2)) ?>
                                        </strong></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
