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

class wcs_ccard extends WirecardCheckoutSeamlessPayment
{
    protected $_defaultSortOrder = 10;
    protected $_paymenttype = WirecardCEE_Stdlib_PaymentTypeAbstract::CCARD;
    protected $_logoFilename = 'cc.png';
    protected $_hasSeamless = true;
    protected $_iframeBuildFunc = 'buildIframeCreditCard';

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

        if ($this->_seamless->getConfigValue('pci3_dss_saq_a_enable')) {
            $field = sprintf('<div id="wcsIframeContainer%s"></div>', $this->code);
            $jsCode = json_encode($this->code);
            $field .= <<<HTML
            <script type="text/javascript">
                $(function () {
                    var wirecardCee = new WirecardCEE_DataStorage;
                    wirecardCee.$this->_iframeBuildFunc('wcsIframeContainer' + $jsCode, '100%', '150px');
                });
            </script>
HTML;
            $content['fields'][] = array(
                'title' => '',
                'field' => $field
            );

            return $content;
        }

        $cssClass = $this->code;

        if ($this->_seamless->getConfigValue('displaycardholder')) {
            $content['fields'][] = array(
                'title' => $this->_seamless->getText('creditcard_cardholder'),
                'field' => sprintf('<input type="text" class="%s input-text" name="wcs_cardholder" data-wcs-fieldname="cardholdername" autocomplete="off" value=""/>',
                    $cssClass)
            );
        }
        $content['fields'][] = array(
            'title' => $this->_seamless->getText('creditcard_pan'),
            'field' => sprintf('<input type="text" class="%s input-text" name="wcs_cardnumber" data-wcs-fieldname="pan" autocomplete="off" value=""/>',
                $cssClass)
        );

        $field = sprintf('<select name="wcs_expirationmonth" class="%s wcs_expirationmonth input-select" data-wcs-fieldname="expirationMonth" autocomplete="off">',
            $cssClass);
        for ($m = 1; $m <= 12; $m++) {
            $field .= sprintf('<option value="%d">%02d</option>', $m, $m);
        }
        $field .= '</select>&nbsp;';

        $field .= sprintf('<select name="wcs_expirationyear" class="%s wcs_expirationyear input-select" data-wcs-fieldname="expirationYear" autocomplete="off">',
            $cssClass);
        foreach ($this->getCreditCardYears() as $y) {
            $field .= sprintf('<option value="%d">%s</option>', $y, $y);
        }
        $field .= '</select>';

        $content['fields'][] = array(
            'title' => $this->_seamless->getText('creditcard_expiry'),
            'field' => $field
        );

        if ($this->_seamless->getConfigValue('displaycvc')) {
            $content['fields'][] = array(
                'title' => $this->_seamless->getText('creditcard_cvc'),
                'field' => sprintf('<input type="text" class="%s wcs_cvc input-text" name="wcs_cvc" data-wcs-fieldname="cardverifycode" autocomplete="off" value="" maxlength="4"/>',
                    $cssClass)
            );
        }

        if ($this->_seamless->getConfigValue('displayissuedate')) {
            $field = sprintf('<select name="wcs_issuemonth" class="%s wcs_issuemonth input-select" data-wcs-fieldname="issueMonth" autocomplete="off">',
                $cssClass);
            for ($m = 1; $m <= 12; $m++) {
                $field .= sprintf('<option value="%d">%02d</option>', $m, $m);
            }
            $field .= '</select>&nbsp;';

            $field .= sprintf('<select name="wcs_issueyear" class="%s wcs_issueyear input-select" data-wcs-fieldname="issueYear" autocomplete="off">',
                $cssClass);
            foreach ($this->getCreditCardIssueYears() as $y) {
                $field .= sprintf('<option value="%d">%s</option>', $y, $y);
            }
            $field .= '</select>';
            $content['fields'][] = array(
                'title' => $this->_seamless->getText('creditcard_issuedate'),
                'field' => $field
            );
        }

        if ($this->_seamless->getConfigValue('displayissuenumber')) {
            $content['fields'][] = array(
                'title' => $this->_seamless->getText('creditcard_issuenumber'),
                'field' => sprintf('<input type="text" class="%s wcs_issuenumber input-text" name="wcs_issuenumber" data-wcs-fieldname="issueNumber" autocomplete="off" value="" maxlength="2"/>',
                    $cssClass)
            );
        }

        return $content;
    }

    /**
     * return additional info to be displayed on the checkout confirmation page
     *
     * @return array|bool
     */
    public function confirmation()
    {
        $response = $this->_seamless->readDataStorage();
        if ($response->hasFailed()) {
            return false;
        }

        $info = $response->getPaymentInformation($this->_paymenttype);
        if (!count($info)) {
            return false;
        }

        $ret = array(
            'title' => $this->title,
            'fields' => array()
        );

        $fields = '';
        $values = '';

        foreach ($info as $k => $v) {
            if ($k == 'anonymousPan') {
                continue;
            }

            if (strlen($fields)) {
                $fields .= '<br/>';
                $values .= '<br/>';
            }

            $fields .= htmlspecialchars($k);
            $values .= htmlspecialchars($v);
        }

        $ret['fields'][] = array('field' => $values, 'title' => $fields);

        return $ret;
    }

}
