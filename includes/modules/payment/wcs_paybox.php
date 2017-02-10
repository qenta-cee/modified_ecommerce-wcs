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

require_once(DIR_FS_CATALOG . 'includes/external/wirecardcheckoutseamless/Payment.php');

class wcs_paybox extends WirecardCheckoutSeamlessPayment
{
    protected $_defaultSortOrder = 240;
    protected $_paymenttype = WirecardCEE_Stdlib_PaymentTypeAbstract::PBX;
    protected $_logoFilename = 'paybox.png';
    protected $_hasSeamless = true;

    /**
     * display additional input fields on payment page
     *
     * @return array|bool
     */
    public function selection()
    {
        $content = parent::selection();
        if ($content === false) {
            return false;
        }

        $cssClass = $this->code;

        $content['fields'][] = array(
            'title' => $this->_seamless->getText('paybox_number'),
            'field' => sprintf('<input type="text" class="%s input-text" name="wcs_payboxnumber" data-wcs-fieldname="payerPayboxNumber" autocomplete="off" value=""/>',
                $cssClass)
        );

        return $content;
    }
}
