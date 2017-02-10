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

/** @var WirecardCheckoutSeamlessTransaction $wcsTransaction */
/** @var WirecardCheckoutSeamlessBackend $wcsBackend */
/** @var int $txId */
/** @var string $action */

$amount = null;
if (isset($_POST['amount']) && strlen($_POST['amount'])) {
    $amount = (float)$_POST['amount'];
}

if (isset($_POST['paymentnumber']) && strlen($_POST['paymentnumber'])) {
    $paymentnumber = (int)$_POST['paymentnumber'];
}

if (isset($_POST['creditnumber']) && strlen($_POST['creditnumber'])) {
    $creditnumber = (int)$_POST['creditnumber'];
}

$errors = array();
$op = null;
switch ($_POST['wcs_operation']) {
    case 'DEPOSIT':
        $op = $wcsBackend->getClient()->deposit($transaction['ordernumber'], $amount, $transaction['currency']);
        break;

    case 'DEPOSITREVERSAL':
        $op = $wcsBackend->getClient()->depositReversal($transaction['ordernumber'], $paymentnumber);
        break;

    case 'APPROVEREVERSAL':
        $op = $wcsBackend->getClient()->approveReversal($transaction['ordernumber']);
        break;

    case 'REFUND':
        $op = $wcsBackend->getClient()->refund($transaction['ordernumber'], $amount, $transaction['currency']);
        break;

    case 'REFUNDREVERSAL':
        $op = $wcsBackend->getClient()->refundReversal($transaction['ordernumber'], $creditnumber);
        break;
}

if ($op !== null) {
    $wcsBackend->log('backend-op:' . $_POST['wcs_operation'] . ' ordernumber:' . $transaction['ordernumber'] . ' amount:' . $amount);

    if ($op->hasFailed()) {
        $errors = array_map(function ($e) {
            return $e->getConsumerMessage();
        }, $op->getErrors());
        $wcsBackend->log('backend-op: error: ' . print_r($op->getErrors(), true));
    } else {
        $wcsBackend->log('backend-op: response:' . print_r($op->getResponse(), true));
    }
}

$operationsAllowed = array();
$payments = array();
$credits = array();
$orderInfo = array();
$orderDetails = $wcsBackend->getOrderDetails($transaction['ordernumber']);
if (!$orderDetails->hasFailed()) {
    $orderInfo = $orderDetails->getOrder()->getData();
    ksort($orderInfo);

    $operationsAllowed = $orderDetails->getOrder()->getOperationsAllowed();
    $payments = $orderDetails->getOrder()->getPayments()->getArray();
    usort($payments, function ($a, $b) {
        /**
         * @var WirecardCEE_QMore_Response_Backend_Order_Payment $a
         * @var WirecardCEE_QMore_Response_Backend_Order_Payment $b
         */
        return $a->getTimeCreated() > $b->getTimeCreated();
    });

    $credits = $orderDetails->getOrder()->getCredits()->getArray();
    usort($credits, function ($a, $b) {
        /**
         * @var WirecardCEE_QMore_Response_Backend_Order_Payment $a
         * @var WirecardCEE_QMore_Response_Backend_Order_Payment $b
         */
        return $a->getTimeCreated() > $b->getTimeCreated();
    });
} else {
    $orderDetails = null;
}


?>

<div class="heading"><?php echo $wcsBackend->getText('TRANSACTION'); ?>:</div>
<table border="0" cellspacing="0" cellpadding="2" class="table">
    <tr>
        <td>
            <table border="0" cellspacing="0" cellpadding="2">
                <tr>
                    <td class="main" style="width:140px;">
                        <b><?php echo $wcsBackend->getText('TABLE_HEADING_ORDER'); ?></b></td>
                    <td class="main"><?php echo(($transaction['orders_id'] != '') ? '<a href="' . xtc_href_link(FILENAME_ORDERS,
                                'action=edit&oID=' . $transaction['orders_id']) . '"><b>' . $transaction['orders_id'] . '</b></a>' : 'n/a'); ?></td>
                </tr>
                <tr>
                    <td class="main"><b><?php echo $wcsBackend->getText('TABLE_HEADING_MODULE'); ?></b></td>
                    <td class="main"><?php echo $transaction['paymentname']; ?></td>
                </tr>
                <tr>
                    <td class="main"><b><?php echo $wcsBackend->getText('TABLE_HEADING_PAYMENTMETHOD'); ?></b></td>
                    <td class="main"><?php echo $transaction['paymentmethod']; ?></td>
                </tr>
                <tr>
                    <td class="main"><b><?php echo $wcsBackend->getText('TABLE_HEADING_PAYMENTSTATE'); ?></b></td>
                    <td class="main"><?php echo $transaction['paymentstate']; ?></td>
                </tr>
                <tr>
                    <td class="main"><b><?php echo $wcsBackend->getText('TABLE_HEADING_ORDERNUMBER'); ?></b></td>
                    <td class="main"><?php echo $transaction['ordernumber']; ?></td>
                </tr>
                <tr>
                    <td class="main"><b><?php echo $wcsBackend->getText('TABLE_HEADING_GATEWAYREFERENCE'); ?></b></td>
                    <td class="main"><?php echo $transaction['gatewayreference']; ?></td>
                </tr>
                <tr>
                    <td class="main"><b><?php echo $wcsBackend->getText('TABLE_HEADING_AMOUNT'); ?></b></td>
                    <td class="main"><?php echo $transaction['amount']; ?></td>
                </tr>
                <tr>
                    <td class="main"><b><?php echo $wcsBackend->getText('TABLE_HEADING_CURRENCY'); ?></b></td>
                    <td class="main"><?php echo $transaction['currency']; ?></td>
                </tr>
                <tr>
                    <td class="main"><b><?php echo $wcsBackend->getText('TABLE_HEADING_STATUS'); ?></b></td>
                    <td class="main"><?php echo $transaction['status']; ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="heading"><?php echo $wcsBackend->getText('Orderdetails'); ?>:</div>
<table border="0" cellspacing="0" cellpadding="2" class="table">
    <tr>
        <td>
            <table border="0" cellspacing="0" cellpadding="2">

                <?php
                $blacklist = array('amount', 'currency', 'merchantNumber', 'orderNumber');
                foreach ($orderInfo as $k => $v) {
                    if (in_array($k, $blacklist)) {
                        continue;
                    }
                    ?>
                    <tr>
                        <td class="main" style="width:140px;">
                            <b><?php echo htmlspecialchars($k); ?></b></td>
                        <td class="main"><?php echo htmlspecialchars($v); ?></td>
                    </tr>
                    <?php
                }
                ?>
                <tr>
                    <td class="main" style="width:140px;"></td>
                    <td class="main">
                        <?php
                        echo xtc_draw_form('wcs_payment', 'wirecardcheckoutseamless_tx.php',
                            "action=payment&action=edit&txId=$txId", 'post');
                        ?>
                        <input type="hidden" name="amount" class="wcs-amount"/>
                        <?php
                        if ($orderDetails != null) {

                            foreach ($orderDetails->getOrder()->getOperationsAllowed() as $op) {
                                if ($op == "DEPOSIT" || $op == "REFUND") {
                                    ?>
                                    <input type="text" name="amount-transaction" value=""
                                           autocomplete="off"
                                           id="wcs-amount-transaction"
                                           class="form-control fixed-width-sm pull-left"/>
                                    <?php
                                }
                                ?>
                                <input class="button wcs-payment-ops" type="submit"
                                       name="wcs_operation"
                                       data-payment=""
                                       data-amount-fieldid="wcs-amount-transaction"
                                       value="<?php echo htmlspecialchars($op); ?>"/>
                                <?php
                            }
                        }
                        ?>
                        </form>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<?php

if (count($errors)) {
    printf('<div class="error_message">%s</div>', implode("<br/>", $errors));
}
?>

<div class="heading"><?php echo $wcsBackend->getText('PAYMENTS'); ?>:</div>
<table border="0" cellspacing="0" cellpadding="2" class="table">
    <tr>
        <td>
            <?php
            echo xtc_draw_form('wcs_payment', 'wirecardcheckoutseamless_tx.php',
                "action=payment&action=edit&txId=$txId", 'post');
            ?>
            <input type="hidden" name="paymentnumber" id="wcs-paymentnumber"/>
            <input type="hidden" name="amount" class="wcs-amount"/>

            <table border="0" cellspacing="0" cellpadding="2">
                <tr class="dataTableHeadingRow">
                    <td class="dataTableHeadingContent"><?php echo $wcsBackend->getText('PAYMENT_NUMBER'); ?></td>
                    <td class="dataTableHeadingContent"><?php echo $wcsBackend->getText('PAYMENT_DATE'); ?></td>
                    <td class="dataTableHeadingContent"><?php echo $wcsBackend->getText('GATEWAYREFERENCE'); ?></td>
                    <td class="dataTableHeadingContent"><?php echo $wcsBackend->getText('PAYMENT_STATE'); ?></td>
                    <td class="dataTableHeadingContent"><?php echo $wcsBackend->getText('PAYMENT_APPROVEDAMOUNT'); ?></td>
                    <td class="dataTableHeadingContent"><?php echo $wcsBackend->getText('PAYMENT_DEPOSITEDAMOUNT'); ?></td>
                    <td class="dataTableHeadingContent"><?php echo $wcsBackend->getText('PAYMENT_OPERATIONS'); ?></td>
                </tr>
                <?php
                foreach ($payments as $p) {
                    /** @var WirecardCEE_QMore_Response_Backend_Order_Payment $p */
                    ?>
                    <tr class="dataTableRow">
                        <td class="dataTableContent"><?php echo htmlspecialchars($p->getPaymentNumber()); ?></td>
                        <td class="dataTableContent"><?php echo htmlspecialchars($p->getTimeCreated()->format('Y-m-d H:i:s')); ?></td>
                        <td class="dataTableContent"><?php echo htmlspecialchars($p->getGatewayReferencenumber()); ?></td>
                        <td class="dataTableContent"><?php echo htmlspecialchars($p->getState()); ?></td>
                        <td class="dataTableContent"><?php echo htmlspecialchars($p->getApproveAmount()); ?></td>
                        <td class="dataTableContent"><?php echo htmlspecialchars($p->getDepositAmount()); ?></td>
                        <td class="dataTableContent">
                            <?php
                            foreach ($p->getOperationsAllowed() as $op) {
                                if (!$op) {
                                    continue;
                                }

                                if ($op == 'DEPOSIT' || $op == 'REFUND') {
                                    ?>
                                    <input type="text" name="amount-<?php echo $p->getPaymentNumber() ?>"
                                           value="<?php echo $p->getApproveAmount() ?>" autocomplete="off"
                                           id="wcs-amount-<?php echo $p->getPaymentNumber() ?>"
                                           class="wcs-amount"/>
                                    <?php
                                }
                                ?>
                                <input class="button wcs-payment-ops" type="submit"
                                       name="wcs_operation"
                                       data-payment="<?php echo $p->getPaymentNumber() ?>"
                                       data-amount-fieldid="wcs-amount-<?php echo $p->getPaymentNumber() ?>"
                                       value="<?php echo htmlspecialchars($op); ?>"/>
                                <?php
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>
            </form>
        </td>
    </tr>
</table>

<div class="heading"><?php echo $wcsBackend->getText('CREDITS'); ?>:</div>
<table border="0" cellspacing="0" cellpadding="2" class="table">
    <tr>
        <td>
            <?php
            echo xtc_draw_form('wcs_credit', 'wirecardcheckoutseamless_tx.php', "action=credit&txId=$txId", 'post');
            ?>
            <input type="hidden" name="creditnumber" id="wcs-creditnumber"/>
            <table border="0" cellspacing="0" cellpadding="2">
                <tr class="dataTableHeadingRow">
                    <td class="dataTableHeadingContent"><?php echo $wcsBackend->getText('PAYMENT_NUMBER'); ?></td>
                    <td class="dataTableHeadingContent"><?php echo $wcsBackend->getText('PAYMENT_DATE'); ?></td>
                    <td class="dataTableHeadingContent"><?php echo $wcsBackend->getText('GATEWAYREFERENCE'); ?></td>
                    <td class="dataTableHeadingContent"><?php echo $wcsBackend->getText('PAYMENT_STATE'); ?></td>
                    <td class="dataTableHeadingContent"><?php echo $wcsBackend->getText('AMOUNT'); ?></td>
                    <td class="dataTableHeadingContent"><?php echo $wcsBackend->getText('PAYMENT_OPERATIONS'); ?></td>
                </tr>
                <?php
                foreach ($credits as $p) {
                    /** @var WirecardCEE_QMore_Response_Backend_Order_Credit $p */
                    ?>
                    <tr class="dataTableRow">
                        <td class="dataTableContent"><?php echo htmlspecialchars($p->getCreditNumber()); ?></td>
                        <td class="dataTableContent"><?php echo htmlspecialchars($p->getTimeCreated()->format('Y-m-d H:i:s')); ?></td>
                        <td class="dataTableContent"><?php echo htmlspecialchars($p->getGatewayReferenceNumber()); ?></td>
                        <td class="dataTableContent"><?php echo htmlspecialchars($p->getState()); ?></td>
                        <td class="dataTableContent"><?php echo htmlspecialchars($p->getAmount()); ?></td>
                        <td class="dataTableContent">
                            <?php
                            foreach ($p->getOperationsAllowed() as $op) {
                                if (!$op) {
                                    continue;
                                }
                                ?>
                                <input class="button wcs-payment-ops" type="submit"
                                       name="wcs_operation"
                                       data-credit="<?php echo $p->getCreditNumber() ?>"
                                       data-payment=""
                                       value="<?php echo htmlspecialchars($op); ?>"/>
                                <?php
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>
            </form>
        </td>
    </tr>
</table>


<script type="text/javascript">

    $(document).ready(function () {

        $('.wcs-payment-ops').on('click', function () {
            var paymentnumber = $(this).data('payment');
            if (paymentnumber) {
                $('#wcs-paymentnumber').val(paymentnumber);
            }

            var creditnumber = $(this).data('credit');
            if (creditnumber) {
                $('#wcs-creditnumber').val(creditnumber);
            }

            var amountFieldId = '#' + $(this).data('amount-fieldid');
            $('.wcs-amount').val($(amountFieldId).val());
        });

    });


</script>