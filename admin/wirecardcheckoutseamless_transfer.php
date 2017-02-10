<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard Central Eastern Europe GmbH
 * (abbreviated to Wirecard CEE) and are explicitly not part of the Wirecard CEE range of
 * products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 2 (GPLv2) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard CEE does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard CEE does not guarantee their full
 * functionality neither does Wirecard CEE assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard CEE does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

require('includes/application_top.php');

// include needed classes
require_once(DIR_FS_EXTERNAL . 'wirecardcheckoutseamless/Backend.php');
$wcsBackend = new WirecardCheckoutSeamlessBackend();

$errors = array();
$infos = array();
$fields = array(
    'type',
    'ordernumber',
    'orderReference',
    'creditnumber',
    'amount',
    'currency',
    'orderDescription',
    'sourceOrdernumber',
    'customerStatement'
);

$params = array();
foreach ($fields as $f) {
    $params[$f] = null;
}

if (isset($_POST['transfer']) && is_array($_POST['transfer'])) {
    try {
        foreach ($_POST['transfer'] as $f => $v) {
            if (!in_array($f, $fields)) {
                continue;
            }

            $params[$f] = rtrim($v);
        }

        $type = strtoupper(trim($params['type']));

        $client = $wcsBackend->getClient()->transferFund($type);

        $sourceOrdernumber = $params['sourceOrdernumber'];

        $amount = $params['amount'];
        $currency = $params['currency'];
        $orderDescription = $params['orderDescription'];

        $wcsBackend->log('transfer-fund:' . print_r($params, true));

        if (strlen($params['ordernumber'])) {
            $client->setOrderNumber($params['ordernumber']);
        }

        if (strlen($params['orderReference'])) {
            $client->setOrderReference($params['orderReference']);
        }

        if (strlen($params['creditnumber'])) {
            $client->setCreditNumber($params['creditnumber']);
        }

        $ret = false;
        switch ($type) {
            case \WirecardCEE_QMore_BackendClient::$TRANSFER_FUND_TYPE_EXISTING:
                /** @var \WirecardCEE_QMore_Request_Backend_TransferFund_Existing $client */
                if (strlen($params['customerStatement'])) {
                    $client->setCustomerStatement($params['customerStatement']);
                }

                $ret = $client->send($amount, $currency, $orderDescription,
                    $sourceOrdernumber);
                break;
            default:
                $this->errors[] = 'Invalid fund transfer type';
        }

        if ($ret !== false) {
            if ($ret->hasFailed()) {
                foreach ($ret->getErrors() as $err) {
                    $errors[] = $err->getConsumerMessage();
                }
                $wcsBackend->log('transfer-fund: error: ' . print_r($ret->getErrors(), true));
            } else {
                $infos[] = 'Fund transfer submitted successfully';
                if (strlen($ret->getCreditNumber())) {
                    $infos[] = 'Credit number: ' . $ret->getCreditNumber();
                }

                $wcsBackend->log('transfer-fund: response:' . print_r($ret->getResponse(), true));
                $transaction = new WirecardCheckoutSeamlessTransaction();

                $result = xtc_db_query('SELECT * FROM ' . TABLE_PAYMENT_WCS
                    . ' WHERE ordernumber="' . (int)$sourceOrdernumber . '";');
                $sourceOrder = xtc_db_fetch_array($result);
                if ($sourceOrder === false) {
                    return null;
                }

                //create transaction table entry for transferfund
                $txId = $transaction->create($sourceOrder['orders_id'], -$amount, $currency,
                    $sourceOrder['paymentname'], $sourceOrder['paymentmethod']);
                $transaction->update($txId, array('paymentstate' => 'CREDIT'));
                $transaction->update($txId, array('status' => 'ok'));
                $transaction->update($txId, array('message' => 'Credit number: ' . $ret->getCreditNumber()));

                if (strlen($params['ordernumber'])) {
                    $transaction->update($txId, array('ordernumber' => $params['ordernumber']));
                } else {
                    $transaction->update($txId, array('ordernumber' => $ret->getCreditNumber()));
                }

                foreach ($params as $k => $v) {
                    $params[$k] = '';
                }


            }
        }
    } catch (\Exception $e) {
        $errors[] = $e->getMessage();
    }
}

// get currencies
$qry = xtc_db_query("SELECT * FROM `" . TABLE_CURRENCIES . "` ORDER BY title");
$currencies = array();
while ($row = xtc_db_fetch_array($qry)) {
    $currencies[] = array('id' => $row["code"], 'text' => $row["title"]);
}

$fieldNoteFmt = sprintf('<a href="https://guides.wirecard.at/doku.php%%s" target="_blank">%s</a>',
    $wcsBackend->getText('MORE_INFORMATION'));

//$locale_code = array(
require(DIR_WS_INCLUDES . 'head.php');
?>
    <link rel="stylesheet" type="text/css" href="../includes/external/wirecardcheckoutseamless/css/admin.css">
    </head>
    <body>

    <!-- header //-->
    <?php require(DIR_WS_INCLUDES . 'header.php'); ?>
    <!-- header_eof //-->

    <!-- body //-->
    <?php
    echo xtc_draw_form('config', basename($PHP_SELF), xtc_get_all_get_params(array('action')) . 'action=transfer');
    ?>
    <table class="tableBody">
        <tr>
            <?php //left_navigation
            if (USE_ADMIN_TOP_MENU == 'false') {
                echo '<td class="columnLeft2">' . PHP_EOL;
                echo '<!-- left_navigation //-->' . PHP_EOL;
                require_once(DIR_WS_INCLUDES . 'column_left.php');
                echo '<!-- left_navigation eof //-->' . PHP_EOL;
                echo '</td>' . PHP_EOL;
            }
            ?>
            <!-- body_text //-->
            <td class="boxCenter">
                <div
                    class="pageHeadingImage"><?php echo xtc_image(DIR_WS_ICONS . 'heading/icon_configuration.png'); ?></div>
                <div class="flt-l">
                    <div
                        class="pageHeading pdg2"><?php echo $wcsBackend->getText('TRANSFER_HEADING_TITLE'); ?>
                    </div>
                </div>
                <?php
                include_once(DIR_FS_EXTERNAL . 'wirecardcheckoutseamless/admin_menu.php');
                ?>
                <div class="clear div_box mrg5" style="margin-top:-1px;">
                    <?php

                    if (count($errors)) {
                        printf('<div class="error_message">%s</div>', implode("<br/>", $errors));
                    }
                    if (count($infos)) {
                        printf('<div class="info_message">%s</div>', implode("<br/>", $infos));
                    }

                    ?>
                    <table class="clear tableConfig">
                        <tr>
                            <td class="dataTableConfig col-left"><?php echo $wcsBackend->getText('FUNDTRANSFER_TYPE');
                                echo '<br/>' . TEXT_FIELD_REQUIRED; ?></td>
                            <td class="dataTableConfig col-middle wcs-col-middle">
                                <?php
                                echo xtc_draw_pull_down_menu("transfer[type]", array(
                                    array(
                                        'id' => 'existingorder',
                                        'text' => $wcsBackend->getText('FUNDTRANSFER_TYPE_ORDER')
                                    ),
                                ), $params['type'], 'id="wcs-ft-type"');
                                ?>
                            </td>
                            <td class="dataTableConfig col-left"></td>
                        </tr>
                        <tr>
                            <td class="dataTableConfig col-left"><?php echo $wcsBackend->getText('CURRENCY');
                                echo '<br/>' . TEXT_FIELD_REQUIRED; ?></td>
                            <td class="dataTableConfig col-middle wcs-col-middle">
                                <?php
                                echo xtc_draw_pull_down_menu("transfer[currency]", $currencies, $params['currency'],
                                    'style="width: 100px;"');
                                ?>
                            </td>
                            <td class="dataTableConfig col-left"></td>
                        </tr>
                        <tr>
                            <td class="dataTableConfig col-left"><?php echo $wcsBackend->getText('AMOUNT');
                                echo '<br/>' . TEXT_FIELD_REQUIRED; ?></td>
                            <td class="dataTableConfig col-middle wcs-col-middle">
                                <?php
                                echo xtc_draw_input_field("transfer[amount]", $params['amount'],
                                    'style="width: 100px;" required="required"');
                                ?>
                            </td>
                            <td class="dataTableConfig col-left"><?php printf($fieldNoteFmt,
                                    '/request_parameters#amount') ?></td>
                        </tr>
                        <tr>
                            <td class="dataTableConfig col-left"><?php echo $wcsBackend->getText('ORDER_DESCRIPTION');
                                echo '<br/>' . TEXT_FIELD_REQUIRED; ?></td>
                            <td class="dataTableConfig col-middle wcs-col-middle">
                                <?php
                                echo xtc_draw_input_field("transfer[orderDescription]", $params['orderDescription'],
                                    'required="required"');
                                ?>
                            </td>
                            <td class="dataTableConfig col-left"><?php printf($fieldNoteFmt,
                                    '/request_parameters#orderdescription') ?></td>
                        </tr>
                        <tr>
                            <td class="dataTableConfig col-left" id="wcs-customerstatement-label"><?php
                                echo $wcsBackend->getText('CUSTOMER_STATEMENT');
                                echo '<br/>' . TEXT_FIELD_REQUIRED;
                                ?></td>
                            <td class="dataTableConfig col-middle wcs-col-middle">
                                <?php
                                echo xtc_draw_input_field("transfer[customerStatement]", $params['customerStatement'],
                                    'id="wcs-customerstatement" required="required"');
                                ?>
                            </td>
                            <td class="dataTableConfig col-left"><?php printf($fieldNoteFmt,
                                    '/request_parameters#customerstatement') ?></td>
                        </tr>
                        <tr>
                            <td class="dataTableConfig col-left"><?php echo $wcsBackend->getText('CREDITNUMBER'); ?></td>
                            <td class="dataTableConfig col-middle wcs-col-middle">
                                <?php
                                echo xtc_draw_input_field("transfer[creditnumber]", $params['creditnumber']);
                                ?>
                            </td>
                            <td class="dataTableConfig col-left"></td>
                        </tr>
                        <tr>
                            <td class="dataTableConfig col-left"><?php echo $wcsBackend->getText('ORDERNUMBER'); ?></td>
                            <td class="dataTableConfig col-middle wcs-col-middle">
                                <?php
                                echo xtc_draw_input_field("transfer[ordernumber]", $params['ordernumber']);
                                ?>
                            </td>
                            <td class="dataTableConfig col-left"><?php printf($fieldNoteFmt,
                                    '/request_parameters#ordernumber') ?></td>
                        </tr>
                        <tr>
                            <td class="dataTableConfig col-left"><?php echo $wcsBackend->getText('ORDERREFERENCE'); ?></td>
                            <td class="dataTableConfig col-middle wcs-col-middle">
                                <?php
                                echo xtc_draw_input_field("transfer[orderReference]", $params['orderReference']);
                                ?>
                            </td>
                            <td class="dataTableConfig col-left"><?php printf($fieldNoteFmt,
                                    '/request_parameters#orderreference') ?></td>
                        </tr>
                    </table>

                    <?php // existingorder ?>
                    <table class="clear tableConfig wcs-specific existingorder wcs-display-none" id="wcs-existingorder">
                        <tr>
                            <td class="dataTableConfig col-left"><?php echo $wcsBackend->getText('SOURCEORDERNUMBER');
                                echo '<br/>' . TEXT_FIELD_REQUIRED; ?></td>
                            <td class="dataTableConfig col-middle wcs-col-middle">
                                <?php
                                echo xtc_draw_input_field("transfer[sourceOrdernumber]", $params['sourceOrdernumber']);
                                ?>
                            </td>
                            <td class="dataTableConfig col-left"><?php printf($fieldNoteFmt,
                                    '/request_parameters#ordernumber') ?></td>
                        </tr>
                    </table>

                    <table class="clear tableConfig">
                        <tr>
                            <td class="txta-l" colspan="3" style="border:none;">
                                <input class="button btn_wide" type="submit" name="update"
                                       value="<?php echo $wcsBackend->getText('FUNDTRANSFER_SEND'); ?>">
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
            <!-- body_text_eof //-->
        </tr>
    </table>
    </form>
    <!-- body_eof //-->
    <!-- footer //-->
    <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
    <!-- footer_eof //-->


    <script type="text/javascript">

        $(function () {
            $('#wcs-ft-type').on('change', function () {

                var selected = $(this).val();

                if (selected.length == 0) {
                    $('.wcs-specific').addClass('wcs-display-none');
                    return;
                }

                var custStmtField = $('#wcs-customerstatement');
                var custStmtFieldLabel = $('#wcs-customerstatement-label');

                $('.wcs-specific.' + selected).each(function (idx, group) {
                    $(group).removeClass('wcs-display-none');
                    $(group).find(':input').attr('required', 'required');
                });

                $('.wcs-specific:not(.' + selected + ')').each(function (idx, group) {
                    $(group).addClass('wcs-display-none');
                    $(group).find(':input').attr('required', null);
                });

                if (selected == 'sepa-ct' || selected == 'existingorder') {
                    custStmtFieldLabel.find('.fieldRequired').addClass('wcs-display-none');
                    custStmtField.attr('required', null);
                } else {
                    custStmtFieldLabel.find('.fieldRequired').removeClass('wcs-display-none');
                    custStmtField.attr('required', 'required');
                }
            }).trigger('change');
        });

    </script>

    </body>
    </html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>