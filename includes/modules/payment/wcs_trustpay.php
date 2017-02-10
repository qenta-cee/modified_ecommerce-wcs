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

class wcs_trustpay extends WirecardCheckoutSeamlessPayment
{
    protected $_defaultSortOrder = 110;
    protected $_paymenttype = WirecardCEE_Stdlib_PaymentTypeAbstract::TRUSTPAY;
    protected $_logoFilename = 'trustpay.png';
    protected $_sendFinancialInstitution = true;
    protected $_financialInstitutions = array();

    /**
     * display additional input fields on payment page
     *
     * @return array|bool
     */
    function selection()
    {
        $content = parent::selection();
        if ($content === false) {
            return false;
        }

        $field = '<select class="wcs_trustpay input-select mandatory" data-wcs-fieldname="fi" name="wcs_trustpay_financialinstitution">';

        $field .= sprintf('<option value="">%s</option>', $this->_seamless->getText('CHOOSE_FINANCIALINSTITUTION'));

        $c = null;
        foreach ($this->_financialInstitutions as $country => $fins) {
            $field .= sprintf('<optgroup label="%s">', $country);
            foreach ($fins as $fin) {
                $field .= sprintf('<option value="%s" class="wcs-select-option-grouped">%s</option>',
                    htmlspecialchars($fin['id']),
                    iconv("UTF-8", $_SESSION['language_charset'] . '//IGNORE', $fin['name']));
            }

            $field .= '</optgroup>';
        }

        $field .= '</optgroup>';
        $field .= '</select>';
        $content['fields'][] = array(
            'title' => $this->_seamless->getText('financialinstitution'),
            'field' => $field
        );

        return $content;
    }

    /**
     * @return bool
     */
    function _preCheck()
    {
        try {
            $this->_financialInstitutions = $this->_seamless->getFinancialInstitutions($this->_paymenttype);
            if ($this->_financialInstitutions === false) {
                return false;
            }
        } catch (\Exception $e) {
            $this->_seamless->log(__METHOD__ . ':' . $e->getMessage(), LOG_WARNING);

            return false;
        }

        return true;
    }

    /**
     * store financial institution in session
     */
    public function pre_confirmation_check()
    {
        if (isset($_POST['wcs_trustpay_financialinstitution'])) {
            $_SESSION['wcs_financialinstitution'] = $_POST['wcs_trustpay_financialinstitution'];
        }
    }
}
