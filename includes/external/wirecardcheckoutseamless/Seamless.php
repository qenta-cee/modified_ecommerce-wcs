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


ini_set('include_path',
    ini_get('include_path')
    . PATH_SEPARATOR . realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'library'
    . PATH_SEPARATOR . realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'models');

define('TABLE_PAYMENT_WCS', 'payment_wirecard_checkout_seamless');

require_once(DIR_FS_EXTERNAL . 'wirecardcheckoutseamless/library/autoload.php');

require_once(DIR_FS_EXTERNAL . 'wirecardcheckoutseamless/Transaction.php');


class WirecardCheckoutSeamless
{
    /**
     * @var string
     */
    protected $_plugintype = 'modified ecommerce wcs core';

    /**
     * @var string
     */
    protected $_pluginversion = '1.0.4';

    /**
     * available config params
     *
     * @var array
     */
    protected $_config = array(
        'basicdata' => array(
            'fields' => array(
                array(
                    'name' => 'configmode',
                    'label' => 'Configuration',
                    'type' => 'select',
                    'default' => 'production',
                    'required' => true,
                    'options' => array(
                        array('id' => 'production', 'text' => 'Production'),
                        array('id' => 'demo', 'text' => 'Demo'),
                        array('id' => 'test', 'text' => 'Test'),
                        array('id' => 'test3d', 'text' => 'Test 3D')
                    ),
                    'doc' => 'For integration, select predefined configuration settings or "Production" for live systems',
                ),
                array(
                    'name' => 'customer_id',
                    'label' => 'Customer ID',
                    'type' => 'text',
                    'default' => 'D200001',
                    'maxchar' => 7,
                    'required' => true,
                    'sanitize' => 'trim',
                    'doc' => 'Customer number you received from Wirecard (customerId, i.e. D2#####).',
                    'docref' => 'https://guides.wirecard.at/request_parameters#customerid',
                ),
                array(
                    'name' => 'shop_id',
                    'label' => 'Shop ID',
                    'type' => 'text',
                    'default' => 'seamless',
                    'maxchar' => 16,
                    'sanitize' => 'trim',
                    'doc' => 'Shop identifier in case of more than one shop.',
                    'docref' => 'https://guides.wirecard.at/request_parameters#shopid',
                ),
                array(
                    'name' => 'secret',
                    'label' => 'Secret',
                    'type' => 'text',
                    'default' => 'B8AKTPWBRMNBV455FG6M2DANE99WU2',
                    'required' => true,
                    'sanitize' => 'trim',
                    'cssclass' => 'fixed-width-xxxl',
                    'doc' => 'String which you received from Wirecard for signing and validating data to prove their authenticity.',
                    'docref' => 'https://guides.wirecard.at/security:start#secret_and_fingerprint',
                ),
                array(
                    'name' => 'backendpw',
                    'label' => 'Back-end password',
                    'type' => 'text',
                    'default' => 'jcv45z',
                    'required' => true,
                    'sanitize' => 'trim',
                    'doc' => 'Password for back-end operations (Toolkit).',
                    'docref' => 'https://guides.wirecard.at/doku.php/back-end_operations:technical_wcs:start#password',
                ),
                array(
                    'name' => 'service_url',
                    'label' => 'URL to contact page',
                    'type' => 'text',
                    'required' => true,
                    'sanitize' => 'trim',
                    'doc' => 'URL of web page containing your contact information (imprint).',
                    'docref' => 'https://guides.wirecard.at/request_parameters#serviceurl',
                ),
                array(
                    'name' => 'order_status_success',
                    'label' => 'Status successful payment',
                    'type' => 'orderstatus',
                    'required' => true,
                    'default' => 2,
                    'doc' => 'Order status for successful payments.'
                ),
                array(
                    'name' => 'order_status_pending',
                    'label' => 'Status pending payment',
                    'type' => 'orderstatus',
                    'required' => true,
                    'default' => 1,
                    'doc' => 'Order status for pending payments.'
                ),
                array(
                    'name' => 'order_status_cancel',
                    'label' => 'Status canceled payment',
                    'type' => 'orderstatus',
                    'default' => 4,
                    'required' => true,
                    'doc' => 'Order status for canceled payments.'
                ),
                array(
                    'name' => 'order_status_failure',
                    'label' => 'Status failed payment',
                    'type' => 'orderstatus',
                    'default' => 4,
                    'required' => true,
                    'doc' => 'Order status for failed payments.'
                )
            )
        ),
        'options' => array(
            'fields' => array(
                array(
                    'name' => 'order_creation',
                    'label' => 'Create orders',
                    'type' => 'select',
                    'options' => array(
                        array('id' => 'always', 'text' => 'Always'),
                        array('id' => 'success', 'text' => 'Only for successful payments'),
                    ),
                    'default' => 'always',
                    'doc' => array(
                        'Selecting "Always", orders are created even if the payment process leads to failed payment.',
                        'Selecting "Only for successful payments", orders are created if the payment process was successful or pending.'
                    )
                ),
                array(
                    'name' => 'keep_pending',
                    'label' => 'Always keep pending payments',
                    'type' => 'onoff',
                    'default' => 0,
                    'doc' => array(
                        'Selecting "Yes", pending orders will remain in the order list even if payment fails. Selecting "No", they are deleted.',
                        'This option is taken into account only if order creation is set to "Only for successful payments".',
                    )
                ),
                array(
                    'name' => 'shopname',
                    'label' => 'Shop reference in posting text',
                    'type' => 'text',
                    'default' => 'Web Shop',
                    'maxchar' => 9,
                    'doc' => 'Reference to your online shop on your consumer\'s bank statement, limited to 9 characters. (used together with the order number to create the parameter customerStatement)',
                    'docref' => 'https://guides.wirecard.at/request_parameters#customerstatement'
                ),
                array(
                    'name' => 'send_shippingdata',
                    'label' => 'Forward consumer shipping data',
                    'default' => 1,
                    'type' => 'onoff',
                    'doc' => 'Forwarding shipping data about your consumer to the respective financial service provider.'
                ),
                array(
                    'name' => 'send_billingdata',
                    'label' => 'Forward consumer billing data',
                    'default' => 1,
                    'type' => 'onoff',
                    'doc' => 'Forwarding billing data about your consumer to the respective financial service provider.'
                ),
                array(
                    'name' => 'send_basketinformation',
                    'label' => 'Forward basket data',
                    'default' => 1,
                    'type' => 'onoff',
                    'doc' => 'Forwarding basket data to the respective financial service provider.'
                ),
                array(
                    'name' => 'sendconfirmationemail',
                    'label' => 'Notification e-mail',
                    'type' => 'onoff',
                    'doc' => array(
                        'Receiving notification by e-mail regarding the orders of your consumers if an error occurred in the communication between Wirecard and your online shop.',
                        'Must be enabled by Wirecard on your behalf.'
                    ),
                    'docref' => 'https://guides.wirecard.at/request_parameters#confirmmail'
                ),
                array(
                    'name' => 'autodeposit',
                    'label' => 'Automated deposit',
                    'default' => 0,
                    'type' => 'onoff',
                    'doc' => array(
                        'Enabling an automated deposit of payments.',
                        'Must be enabled by Wirecard on your behalf.'
                    ),
                    'docref' => 'https://guides.wirecard.at/request_parameters#autodeposit'
                ),
                array(
                    'name' => 'displaysepa_bic',
                    'label' => 'Display SEPA Direct Debit BIC field',
                    'default' => 1,
                    'type' => 'onoff',
                    'doc' => 'Display BIC field for SEPA Direct Debit.',
                    'Must be enabled by Wirecard on your behalf.'
                ),
            )
        ),
        'creditcardoptions' => array(
            'fields' => array(
                array(
                    'name' => 'pci3_dss_saq_a_enable',
                    'label' => 'SAQ A compliance',
                    'type' => 'onoff',
                    'default' => 0,
                    'doc' => 'Selecting \'NO\', the stringent SAQ A-EP is applicable. Selecting \'YES\', Wirecard Checkout Seamless is integrated with the \'PCI DSS SAQ A Compliance\' feature and SAQ A is applicable. ',
                    'docref' => 'https://guides.wirecard.at/doku.php/wcs:pci3_fallback:start'
                ),
                array(
                    'name' => 'iframe_css_url',
                    'label' => 'Iframe CSS-URL',
                    'type' => 'text',
                    'default' => 'iframe.css',
                    'cssclass' => 'fixed-width-xxl',
                    'doc' => 'Entry of a name for the CSS file in order to customize the iframe input fields when using the \'PCI DSS SAQ A Compliance\' feature. File must be placed in the css directory of the plugin.',
                    'docref' => 'https://guides.wirecard.at/doku.php/wcs:pci3_fallback:start#customization_via_css'
                ),
                array(
                    'name' => 'pan_placeholder',
                    'label' => 'Credit card number placeholder text',
                    'type' => 'text',
                    'doc' => 'Placeholder text for the credit card number field.'
                ),
                array(
                    'name' => 'displayexpirationdate_placeholder',
                    'default' => 0,
                    'label' => 'Display expiration date placeholder text',
                    'type' => 'onoff',
                    'doc' => 'Display placeholder text for the expiration date field.',
                ),
                array(
                    'name' => 'displaycardholder',
                    'label' => 'Display card holder field',
                    'type' => 'onoff',
                    'default' => 1,
                    'doc' => 'Display input field to enter the card holder name in your credit card form during the checkout process.',
                ),
                array(
                    'name' => 'cardholder_placeholder',
                    'label' => 'Card holder placeholder text',
                    'type' => 'text',
                    'doc' => 'Display placeholder text for the card holder field.'
                ),
                array(
                    'name' => 'displaycvc',
                    'label' => 'Display CVC field',
                    'default' => 1,
                    'type' => 'onoff',
                    'doc' => 'Display input field to enter the CVC in your credit card form during the checkout process.',
                ),
                array(
                    'name' => 'cvc_placeholder',
                    'label' => 'CVC placeholder text',
                    'type' => 'text',
                    'doc' => 'Display placeholder text for the CVC field.'
                ),
                array(
                    'name' => 'displayissuedate',
                    'label' => 'Display issue date field',
                    'default' => 0,
                    'type' => 'onoff',
                    'doc' => 'Display input field to enter the credit card issue date in your credit card form during the checkout process. Some credit cards do not have an issue date.'
                ),
                array(
                    'name' => 'displayissuedate_placeholder',
                    'label' => 'Display issue date placeholder text',
                    'default' => 0,
                    'type' => 'onoff',
                    'doc' => 'Display placeholder text for the issue date field.'
                ),
                array(
                    'name' => 'displayissuenumber',
                    'label' => 'Display issue number field',
                    'default' => 0,
                    'type' => 'onoff',
                    'doc' => 'Display input field to enter the credit card issue number in your credit card form during the checkout process. Some credit cards do not have an issue number.'
                ),
                array(
                    'name' => 'issuenumber_placeholder',
                    'label' => 'Issue number placeholder text',
                    'default' => 0,
                    'type' => 'text',
                    'doc' => 'Display placeholder text for the issue number field.'
                ),
            )
        )
    );

    /**
     * @var LoggingManager
     */
    protected $_logger;

    /**
     * @var WirecardCheckoutSeamlessTransaction
     */
    protected $_transaction;

    /**
     * predefined test/demo accounts
     *
     * @var array
     */
    protected $_presets = array(
        'demo' => array(
            'customer_id' => 'D200001',
            'shop_id' => 'seamless',
            'secret' => 'B8AKTPWBRMNBV455FG6M2DANE99WU2',
            'backendpw' => 'jcv45z'
        ),
        'test' => array(
            'customer_id' => 'D200411',
            'shop_id' => 'seamless',
            'secret' => 'CHCSH7UGHVVX2P7EHDHSY4T2S4CGYK4QBE4M5YUUG2ND5BEZWNRZW5EJYVJQ',
            'backendpw' => '2g4f9q2m'
        ),
        'test3d' => array(
            'customer_id' => 'D200411',
            'shop_id' => 'seamless3D',
            'secret' => 'DP4TMTPQQWFJW34647RM798E9A5X7E8ATP462Z4VGZK53YEJ3JWXS98B9P4F',
            'backendpw' => '2g4f9q2m'
        )
    );

    public function __construct($lang = 'english')
    {
        if (isset($_SESSION['language'])) {
            $lang = $_SESSION['language'];
        }

        require_once(DIR_FS_EXTERNAL . 'wirecardcheckoutseamless/lang/' . $lang . '.php');

        $config = array(
            'LogEnabled' => true,
            'SplitLogging' => true,
            //'LogLevel' => ((defined('LOGGING_LEVEL')) ? LOGGING_LEVEL : 'INFO'), // DEBUG, FINE, INFO, WARN, ERROR, CUSTOM
            'LogLevel' => 'DEBUG',
            'LogThreshold' => '2MB',
            'FileName' => DIR_FS_LOG . 'wirecardcheckoutseamless' . ((defined('RUN_MODE_ADMIN')) ? 'admin_' : '_') . date('Y-m-d') . '.log',
        );

        $this->_logger = new LoggingManager($config);
        $this->_transaction = new WirecardCheckoutSeamlessTransaction();
    }

    /**
     * return all available config params
     *
     * @return array
     */
    public function getConfigParameters()
    {
        return $this->_config;
    }

    /**
     * search config param by name
     *
     * @param $name
     *
     * @return null
     */
    public function getConfigParam($name)
    {
        foreach ($this->_config as $grp) {
            foreach ($grp['fields'] as $f) {
                if ($f['name'] == $name) {
                    return $f;
                }
            }
        }

        return null;
    }

    /**
     * validate param value
     *
     * @param $param
     * @param $value
     *
     * @return array
     */
    public function validateConfigParam($param, $value)
    {
        $errors = array();
        if (isset($param['sanitize'])) {
            switch ($param['sanitize']) {
                case 'trim':
                    $value = trim($value);
                    break;
            }
        }

        if (isset($param['maxchar'])) {
            if (strlen($value) > $param['maxchar']) {
                $errors[] = sprintf($this->getText('validator_maxchar'), $param['label'], $param['maxchar']);
            }
        }

        if ($param['required'] && !strlen($value)) {
            $configmode = $this->getConfigValue('configmode');
            if (in_array($param['name'], array('customer_id', 'shop_id', 'secret', 'backendpw'))) {
                if ($configmode == 'production') {
                    $errors[] = sprintf($this->getText('validator_required'), $param['label']);
                }
            } else {
                $errors[] = sprintf($this->getText('validator_required'), $param['label']);
            }
        }

        if (!isset($param['validator'])) {
            return $errors;
        }

        switch ($param['validator']) {
            case 'numeric':
                if (strlen($value) && !is_numeric($value)) {
                    $errors[] = sprintf($this->getText('validator_numeric'), $param['label']);
                }
                break;
        }

        return $errors;
    }

    /**
     * update/insert configuration param into database
     *
     * @param $config_key
     * @param $config_value
     *
     * @return $this
     */
    public function saveConfigValue($config_key, $config_value)
    {
        $key = 'MODULE_PAYMENT_WCS_' . strtoupper($config_key);

        $query = xtc_db_query("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = '" . xtc_db_input($key) . "'");
        $config = xtc_db_num_rows($query);
        if ($config === 0) {
            $q = "INSERT INTO " . TABLE_CONFIGURATION
                . " ( configuration_key, configuration_value,  configuration_group_id, sort_order, date_added) "
                . "VALUES ('" . $key . "', '"
                . $config_value . "', '6', '100', NOW())";
        } else {
            $q = "UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '" . $config_value . "' WHERE configuration_key = '" . $key . "'";
        }

        xtc_db_query($q);

        return $this;
    }

    /**
     * delete configuration parameter
     *
     * @param $config_key
     *
     * @return $this
     */
    public function deleteConfigValue($config_key)
    {
        $key = 'MODULE_PAYMENT_WCS_' . strtoupper($config_key);

        xtc_db_query("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = '" . xtc_db_input($key) . "'");

        return $this;
    }

    /**
     * return config param value (global, i.e. non payment specific)
     *
     * @param $config_key
     *
     * @return null
     * @throws Exception
     */
    public function getConfigValue($config_key)
    {
        $presets = array('customer_id', 'shop_id', 'secret', 'backendpw');
        if (in_array($config_key, $presets)) {
            $mode = $this->getConfigValue('configmode');
            if (isset($this->_presets[$mode])) {
                return $this->_presets[$mode][$config_key];
            }
        }
        $param = $this->getConfigParam($config_key);
        if ($param === null) {
            throw new Exception('unknown config parameter: ' . $config_key);
        }
        $key = 'MODULE_PAYMENT_WCS_' . strtoupper($config_key);
        $config_query = xtc_db_query("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = '" . $key . "'");
        $config = xtc_db_fetch_array($config_query);
        if ($config === false) {
            return isset($param['default']) ? $param['default'] : null;
        }

        return $config['configuration_value'];
    }

    /**
     * returns config preformated as string, used in support email
     *
     * @return string
     */
    public function getConfigString()
    {
        $ret = '';
        $exclude = array('secret', 'backendpw');
        foreach ($this->_config as $group => $fields) {
            foreach ($fields['fields'] as $field) {
                if (in_array($field['name'], $exclude)) {
                    continue;
                }

                $value = $this->getConfigValue($field['name']);

                if (strlen($ret)) {
                    $ret .= "<br/>\n";
                }
                $ret .= sprintf("%s: %s", $field['label'], $value);
            }
        }

        return $ret;
    }

    /**
     * check if translations is available
     *
     * @param $code
     *
     * @return mixed|string
     */
    public function hasText($code)
    {
        return defined('TEXT_WIRECARDCHECKOUTSEAMLESS_' . strtoupper($code));
    }

    /**
     * return translated text, if not found return name
     *
     * @param $code
     *
     * @return mixed|string
     */
    public function getText($code)
    {
        $name = 'TEXT_WIRECARDCHECKOUTSEAMLESS_' . strtoupper($code);

        $ret = (defined($name)) ? constant($name) : $name;
        if (func_num_args() > 1) {
            $args = func_get_args();
            array_shift($args);

            return vsprintf($ret, $args);
        } else {
            return $ret;
        }
    }

    /**
     * return config array to be used for client lib
     *
     * @return array
     */
    public function _getConfigArray()
    {
        return Array(
            'CUSTOMER_ID' => $this->getConfigValue('customer_id'),
            'SHOP_ID' => $this->getConfigValue('shop_id'),
            'SECRET' => $this->getConfigValue('secret'),
            'LANGUAGE' => $_SESSION['language_code'],
        );
    }

    /**
     * return config array to be used for client lib, backend ops
     *
     * @return array
     */
    protected function _getBackendConfigArray()
    {
        $cfg = $this->_getConfigArray();
        $cfg['PASSWORD'] = $this->getConfigValue('backendpw');

        return $cfg;
    }

    /**
     * return plugin version
     *
     * @return string
     */
    public function getPluginVersion()
    {
        return WirecardCEE_QMore_FrontendClient::generatePluginVersion('modified ecommerce', PROJECT_VERSION,
            $this->_plugintype, $this->_pluginversion);
    }

    /**
     * Returns desription of customer - will be displayed in Wirecard backend
     *
     * @param $order
     *
     * @return string
     */
    public function getUserDescription($order)
    {
        return sprintf('%s %s %s', $order->customer['email_address'], $order->customer['firstname'],
            $order->customer['lastname']);
    }

    /**
     * @param order $order
     * @param WirecardCheckoutSeamlessPayment $payment
     *
     * @return WirecardCEE_QMore_Response_Initiation
     * @throws Exception
     */
    public function initPayment($order, $payment)
    {
        /** @var xtcPrice $xtPrice */
        global $xtPrice;

        $total = isset($order->info['pp_total']) ? $order->info['pp_total'] : $total = $order->info['total'];
        $decimalPlaces = $xtPrice->get_decimal_places($order->info['currency']);
        $amount = number_format($total, $decimalPlaces, '.', '');
        $txId = $this->_transaction->create($order->info['orders_id'], $amount, $order->info['currency'],
            $payment->code, $payment->getPaymentType());


        $init = new WirecardCEE_QMore_FrontendClient($this->_getConfigArray());

        $init->setPluginVersion($this->getPluginVersion());

        $init->setConfirmUrl(xtc_href_link('callback/wirecard/checkout_seamless_confirm.php', '', 'SSL', false));
        $init->setOrderReference(sprintf('%010d', $order->info['orders_id']));

        if ($this->getConfigValue('sendconfirmationemail')) {
            $init->setConfirmMail(STORE_OWNER_EMAIL_ADDRESS);
        }

        $init->setStorageReference(session_id(), $_SESSION['wcs_storage_id']);

        if (isset($_SESSION['wcs_financialinstitution']) && $payment->getSendFinancialInstitution()) {
            $init->setFinancialInstitution($_SESSION['wcs_financialinstitution']);
        }

        $returnUrl = xtc_href_link('callback/wirecard/checkout_seamless_return.php', 'txId=' . $txId, 'SSL', false);

        $autoDeposit = $this->getConfigValue('autodeposit');
        if (!$payment->isAutoDepositAllowed()) {
            $autoDeposit = false;
        }

        $init->setAmount($amount)
            ->setCurrency($order->info['currency'])
            ->setPaymentType($payment->getPaymentType())
            ->setOrderDescription($this->getUserDescription($order))
            ->setSuccessUrl($returnUrl)
            ->setPendingUrl($returnUrl)
            ->setCancelUrl($returnUrl)
            ->setFailureUrl($returnUrl)
            ->setServiceUrl($this->getConfigValue('service_url'))
            ->setAutoDeposit($autoDeposit)
            ->setConsumerData($this->getConsumerData($order, $payment))
            ->createConsumerMerchantCrmId($order->customer['email_address']);

        $init->modifiedWcsTxid = $txId;
	    if(isset($_SESSION['wcs-consumerDeviceId'])){
	    	$init->consumerDeviceId = $_SESSION['wcs-consumerDeviceId'];
	    	unset($_SESSION['wcs-consumerDeviceId']);
	    }

        if ($payment->forceSendingBasket() || $this->getConfigValue('send_basketinformation')) {
            $init->setBasket($this->getBasket($order));
        }

        $this->setCustomerStatement($init, $order);

        $data = array_map(function ($e) {
            return iconv($_SESSION['language_charset'], "UTF-8", $e);
        }, $init->getRequestData());

        $this->log(__METHOD__ . ':' . print_r($data, true));

        $this->_transaction->update($txId, array('request' => json_encode($data)));

        try {
            $initResponse = $init->initiate();

            if ($initResponse->getStatus() == \WirecardCEE_QMore_Response_Initiation::STATE_FAILURE) {
                $message = 'An error occurred during the payment process';
                if ($initResponse->getNumberOfErrors() > 0) {
                    $msg = implode(',',
                        array_map(function ($e) {
                            /** @var \WirecardCEE_QMore_Error $e */
                            return $e->getConsumerMessage();
                        }, $initResponse->getErrors()));

                    if (strlen($msg)) {
                        $message = $msg;
                    }
                }

                $this->_transaction->update($txId, array(
                    'paymentstate' => 'INITIATED',
                    'status' => 'error',
                    'message' => $message
                ));

            } else {
                $this->_transaction->update($txId, array('paymentstate' => 'INITIATED'));
            }

        } catch (Exception $e) {
            $this->_transaction->update($txId, array(
                    'message' => $e->getMessage(),
                    'status' => 'error'
                )
            );
            throw $e;
        }

        return $initResponse;
    }

    /**
     * set customerstatement, may be adopted by integrator
     *
     * @param WirecardCEE_QMore_FrontendClient $init
     * @param $order
     */
    public function setCustomerStatement($init, $order)
    {
        $init->generateCustomerStatement($this->getConfigValue('shopname'));
    }

    /**
     * confirm server2server request
     */
    public function confirm()
    {
        $response = file_get_contents('php://input');

        $return = null;
        try {

            $return = WirecardCEE_QMore_ReturnFactory::getInstance($response, $this->getConfigValue('secret'));

            $this->log(__METHOD__ . ':' . print_r($return->getReturned(), true));

            if (!$return->validate()) {
                throw new Exception('Validation error: invalid response');
            }

            if (!strlen($return->modifiedWcsTxid)) {
                throw new Exception('wirecard transaction id is missing');
            }

            $transactionData = $this->_transaction->get($return->modifiedWcsTxid);
            if ($transactionData === false) {
                throw new Exception('Transaction data not found: ' . $return->modifiedWcsTxid);
            }

            $orders_id = (int)$transactionData['orders_id'];
            $order = new order($orders_id);

            $ordernumber = null;
            $gatewayref = null;
            $data = array_map(function ($e) {
                return iconv($_SESSION['language_charset'], "UTF-8", $e);
            }, $return->getReturned());
            $txData = array(
                'paymentstate' => $return->getPaymentState(),
                'response' => json_encode($data),
            );

            switch ($return->getPaymentState()) {
                case WirecardCEE_QMore_ReturnFactory::STATE_SUCCESS:
                    /** @var WirecardCEE_QMore_Return_Success $return */
                    $ordernumber = $return->getOrderNumber();
                    $gatewayref = $return->getGatewayReferenceNumber();
                    $this->updateOrderStatus($orders_id, $this->getConfigValue('order_status_success'));
                    break;

                case WirecardCEE_QMore_ReturnFactory::STATE_PENDING:
                    $ordernumber = $return->getOrderNumber();
                    /** @var WirecardCEE_QMore_Return_Pending $return */
                    $this->updateOrderStatus($orders_id, $this->getConfigValue('order_status_pending'));
                    break;

                case WirecardCEE_QMore_ReturnFactory::STATE_CANCEL:
                    $this->updateOrderStatus($orders_id, $this->getConfigValue('order_status_cancel'));
                    break;

                case WirecardCEE_QMore_ReturnFactory::STATE_FAILURE:
                    $this->updateOrderStatus($orders_id, $this->getConfigValue('order_status_failure'));
                    if ($return->getNumberOfErrors() > 0) {

                        $msg = implode(',', array_map(function ($e) {
                            /** @var \WirecardCEE_QMore_Error $e */
                            return html_entity_decode($e->getConsumerMessage());
                        }, $return->getErrors()));

                        if (strlen($msg)) {
                            $txData['message'] = $msg;
                        }

                        $this->log(__METHOD__ . ':msg:' . $msg);
                    }
                default:
            }

            if ($ordernumber !== null) {
                $txData['ordernumber'] = $ordernumber;
            }

            if ($gatewayref !== null) {
                $txData['gatewayreference'] = $gatewayref;
            }

            $this->_transaction->update($return->modifiedWcsTxid, $txData);

            $message = $this->printArray($data, array(
                'paymentState',
                'amount',
                'currency',
                'language',
                'responseFingerprint',
                'responseFingerprintOrder'
            ));

            xtc_db_query("update " . TABLE_ORDERS . " set comments = '". trim(print_r($message, true), ';') ."' where orders_id = '". $orders_id ."'");

            $this->log(__METHOD__ . ':order-creation:' . $this->getConfigValue('order_creation') .
                ' order-id:' . $order->info['orders_id'] .
                ' order-status-id:' . $order->info['orders_status_id'] .
                ' new-state:' . $return->getPaymentState() .
                ' current-state:' . $transactionData['paymentstate']);

            // check if we have to remove the order
            if ($this->getConfigValue('order_creation') == 'success' &&
                ($return->getPaymentState() == WirecardCEE_QMore_ReturnFactory::STATE_CANCEL ||
                    $return->getPaymentState() == WirecardCEE_QMore_ReturnFactory::STATE_FAILURE)
            ) {
                $remove = true;

                // dont remove orders which have been manually set to success
                if ($order->info['orders_status_id'] == $this->getConfigValue('order_status_success') &&
                    $transactionData['paymentstate'] != 'INITIATED'
                ) {
                    $this->log(__METHOD__ . ':order has been manually set to success, keeping');
                    $remove = false;
                }

                if ($transactionData['paymentstate'] == 'PENDING' && $this->getConfigParam('keep_pending')) {
                    $this->log(__METHOD__ . ':keeping pending payment');
                    $remove = false;
                }

                if ($remove) {
                    $this->removeOrder($order);
                    $this->_transaction->update($return->modifiedWcsTxid, ['orders_id' => null]);
                }
            }

        } catch (Exception $e) {
            $this->log(__METHOD__ . ':' . $e->getMessage());
            $this->log(__METHOD__ . ':' . $e->getTraceAsString());

            if ($return !== null && strlen($return->modifiedWcsTxid)) {
                $this->_transaction->update($return->modifiedWcsTxid,
                    array('status' => 'error', 'message' => $e->getMessage()));
            }

            return (WirecardCEE_QMore_ReturnFactory::generateConfirmResponseString($e->getMessage()));
        }

        return WirecardCEE_QMore_ReturnFactory::generateConfirmResponseString();
    }

    /**
     * create message structure from given array without ignored fields
     * @param $array
     * @param $ignore
     * @return string
     */
    private function printArray($array, $ignore)
    {
        $ret = "";
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (in_array($key, $ignore)) {
                    continue;
                }
                if (is_array($value)) {
                    $ret .= $this->printArray($value);
                } else {
                    $ret .= '; ' . $key . ':' . $value;
                }
            }
        }
        return $ret;
    }

    /**
     * return from checkout page
     * redirect further, continue with checkout process or redirect to payment page on error
     * store any info into session, to be displayed on the redirected page (payment error,
     * pending info)
     *
     * @return bool|string
     */
    public function back()
    {
        if (!isset($_GET['txId']) || ((int)$_GET['txId']) === 0) {
            $this->log(__METHOD__ . ':bogus txId:' . $_GET['txId']);

            return false;
        }

        $txId = (int)$_GET['txId'];
        $transactionData = $this->_transaction->get($txId);
        if ($transactionData === false) {
            $this->log(__METHOD__ . ':Transaction data not found:' . $_GET['txId']);

            return false;
        }

        $info = array();

        if ($transactionData['paymentstate'] == 'INITIATED') {
            $this->log(__METHOD__ . ':server2server confirm not working');
            $info[] = array(
                'title' => '',
                'field' => $this->getText('frontend_initerror'),
            );

            return false;
        }

        if (strlen($transactionData['gatewayreference'])) {
            $info[] = array(
                'title' => $this->getText('gatewayreference'),
                'field' => $transactionData['gatewayreference']
            );
        }

        if ($transactionData['paymentstate'] == WirecardCEE_QMore_ReturnFactory::STATE_PENDING) {
            $info[] = array(
                'title' => '',
                'field' => $this->getText('pendinginfo'),
            );
        }

        if (strlen($transactionData['message'])) {
            $_SESSION['wcs_error_message'] = $transactionData['message'];
            $info[] = array(
                'title' => '',
                'field' => $transactionData['message'],
            );
        }

        $_SESSION['wcs_success_info'] = $info;

        if ($transactionData['paymentstate'] == WirecardCEE_QMore_ReturnFactory::STATE_PENDING ||
            $transactionData['paymentstate'] == WirecardCEE_QMore_ReturnFactory::STATE_SUCCESS
        ) {
            return xtc_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL', true, false);
        }

        return xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $_SESSION['payment'], 'SSL', true, false);
    }


    /**
     * update order, set status and comment
     *
     * @param        $orders_id
     * @param        $status_id
     * @param string $comment
     */
    public function updateOrderStatus($orders_id, $status_id, $comment = '')
    {
        xtc_db_query(sprintf("UPDATE `orders` SET `orders_status` = %d, `last_modified` = NOW() WHERE orders_id = %d",
            $status_id, $orders_id));
        xtc_db_query(sprintf("INSERT INTO `orders_status_history` SET `orders_id` = %d, `orders_status_id` = %d, `date_added` = NOW(), `customer_notified` = 0, `comments` = '%s'",
            $orders_id, $status_id, xtc_db_input($comment)));
    }

    /**
     * remove order
     *
     * @param order $order
     */
    protected function removeOrder($order)
    {
        if ($_SESSION['customer_id'] == $order->customers_id) {
            require_once(DIR_FS_INC . 'xtc_remove_order.inc.php');
            xtc_remove_order($order->info['orders_id'], ((STOCK_LIMITED == 'true') ? 'on' : false));

            $this->log(__METHOD__ . ':Order removed: ' . $order->info['orders_id']);
        }
    }

    /**
     * build basket
     *
     * @param order $order
     *
     * @return WirecardCEE_Stdlib_Basket
     */
    public function getBasket(order $order)
    {
        /** @var xtcPrice $xtPrice */
        global $xtPrice;

        $decimalPlaces = $xtPrice->get_decimal_places($order->info['currency']);

        $basket = new WirecardCEE_Stdlib_Basket();

        foreach ($order->products as $idx => $p) {
            $product = new product($p['id']);

            $price = $xtPrice->xtcRemoveTax($p['price'], $p['tax']);
            $tax = $xtPrice->calcTax($price, $p['tax']);
            $article = $p['name'];

            if (strlen($p['model'])) {
                $article = $p['model'];
            } else {
                if (strlen($p['ean'])) {
                    $article = $p['ean'];
                }
            }
            $item = new WirecardCEE_Stdlib_Basket_Item($article);

            $item->setUnitGrossAmount(number_format($p['price'], $decimalPlaces, '.', ''))
                ->setUnitNetAmount(number_format($price, $decimalPlaces, '.', ''))
                ->setUnitTaxAmount(number_format($tax, $decimalPlaces, '.', ''))
                ->setUnitTaxRate(number_format($p['tax'], 3, '.', ''))
                ->setDescription(strip_tags($p['name']))
                ->setName($p['name']);

            if (strlen($product->data['products_image'])) {
                $item->setImageUrl(xtc_href_link(DIR_WS_INFO_IMAGES . $product->data['products_image'], '', 'SSL',
                    false, false));
            }

            $basket->addItem($item, $p['qty']);
        }
        if ($order->info['pp_shipping'] > 0) {
            $shipping_module = explode('_', $order->info['shipping_class']);
            $shipping_tax_class = constant('MODULE_SHIPPING_' . strtoupper($shipping_module[0]) . '_TAX_CLASS');
            $shipping_tax_rate = xtc_get_tax_rate($shipping_tax_class);

            $item = new WirecardCEE_Stdlib_Basket_Item('shipping');
            $item->setDescription($order->info['shipping_method'])
                ->setName('Shipping')
                ->setUnitGrossAmount(number_format($order->info['pp_tax'], $decimalPlaces, '.', ''))
                ->setUnitNetAmount(number_format($order->info['pp_shipping'], $decimalPlaces, '.', ''))
                ->setUnitTaxAmount($item->getUnitGrossAmount() - $item->getUnitNetAmount())
                ->setUnitTaxRate(number_format($shipping_tax_rate, 3, '.', ''));

            $basket->addItem($item);
        }

        return $basket;
    }

    /**
     * return tax rate depends on shipping type
     * @param $shipping_id
     *
     * @return float|int
     */
    function get_shipping_tax_rate($shipping_id)
    {
        $check_query = xtc_db_query('SELECT configuration_value FROM ' . TABLE_CONFIGURATION . ' WHERE configuration_key = "MODULE_SHIPPING_' . $shipping_id . '_TAX_CLASS"');
        $configuration = xtc_db_fetch_array($check_query);
        $tax_class_id = $configuration['configuration_value'];
        $shipping_tax_rate = xtc_get_tax_rate($tax_class_id);

        return $shipping_tax_rate;
    }

    /**
     * Returns customer object
     *
     * @param order $order
     * @param WirecardCheckoutSeamlessPayment $payment
     *
     * @return WirecardCEE_Stdlib_ConsumerData
     */
    public function getConsumerData($order, $payment)
    {
        $consumerData = new WirecardCEE_Stdlib_ConsumerData();
        $consumerData->setIpAddress($_SERVER['REMOTE_ADDR']);
        $consumerData->setUserAgent($_SERVER['HTTP_USER_AGENT']);

        $consumerData->setEmail($order->customer['email_address']);

        $data = $this->getCustomerData($order->customer['id']);

        if (isset($_SESSION['wcs_birthday']) && strlen($_SESSION['wcs_birthday'])) {
            $consumerData->setBirthDate(new DateTime($_SESSION['wcs_birthday']));
        } else {
            if ($data !== null) {
                if ($data['customers_dob'] != '0000-00-00 00:00:00') {
                    $consumerData->setBirthDate(new DateTime($data['customers_dob']));
                }
            }
        }

        if ($data !== null) {

            if (strlen($data['customers_vat_id'])) {
                $consumerData->setTaxIdentificationNumber($data['customers_vat_id']);
            }
        }

        if (isset($order->customer['company']) && strlen($order->customer['company'])) {
            $consumerData->setCompanyName($order->customer['company']);
        }

        if ($payment->getForceSendingAdditionalData() || $this->getConfigValue('send_billingdata') ) {
            $consumerData->addAddressInformation($this->_getAddress($order, 'billing'));
        }

        if ($payment->forceSendingShippingData() || $this->getConfigValue('send_shippingdata')) {
            $consumerData->addAddressInformation($this->_getAddress($order, 'shipping'));
        }

        return $consumerData;
    }

    /**
     * fetch additional customer data from db
     *
     * @param $p_customer_id
     *
     * @return array|bool|mixed
     */
    protected function getCustomerData($p_customer_id)
    {
        $sql = 'SELECT customers_dob, customers_fax, customers_vat_id FROM ' . TABLE_CUSTOMERS
            . ' WHERE customers_id="' . (int)$p_customer_id . '" LIMIT 1;';
        $result = xtc_db_query($sql);

        return xtc_db_fetch_array($result);
    }

    /**
     * fetch zonecode from db
     *
     * @param string $p_zonename
     *
     * @return null|string
     */
    protected function getZoneCodeByName($p_zonename)
    {
        $sql = 'SELECT zone_code FROM ' . TABLE_ZONES . ' WHERE zone_name=\'' . xtc_db_input($p_zonename)
            . '\' LIMIT 1;';
        $result = xtc_db_query($sql);
        $resultRow = xtc_db_fetch_array($result);
        if ($resultRow === false) {
            return null;
        }

        return $resultRow[0];
    }

    /**
     * Returns address object
     *
     * @param order $order
     * @param string $type
     *
     * @return WirecardCEE_Stdlib_ConsumerData_Address
     */
    protected function _getAddress($order, $type = 'billing')
    {

        switch ($type) {
            case 'shipping':
                $address = new WirecardCEE_Stdlib_ConsumerData_Address(WirecardCEE_Stdlib_ConsumerData_Address::TYPE_SHIPPING);
                $source = $order->delivery;
                break;

            default:
                $address = new WirecardCEE_Stdlib_ConsumerData_Address(WirecardCEE_Stdlib_ConsumerData_Address::TYPE_BILLING);
                $source = $order->billing;
                break;
        }

        $address->setFirstname($source['firstname']);
        $address->setLastname($source['lastname']);
        $address->setAddress1($source['street_address']);
        $address->setZipCode($source['postcode']);
        $address->setCity($source['city']);
        $address->setCountry($source['country_iso_2']);
        if ($type == 'billing') {
            $address->setPhone($order->customer['telephone']);
        }

        if ($source['country_iso_2'] == 'US' || $source['country_iso_2'] == 'CA') {
            $deliveryState = $this->getZoneCodeByName($source['state']);
        } else {
            $deliveryState = $source['state'];
        }

        if (strlen($deliveryState)) {
            $address->setState($deliveryState);
        }

        return $address;
    }


    /**
     * init wirecard datastorage
     *
     * @return null|\WirecardCEE_QMore_DataStorage_Response_Initiation
     * @throws WirecardCheckoutSeamlessException
     */
    public function initDataStorage()
    {
        $dataStorageInit = new WirecardCEE_QMore_DataStorageClient($this->_getConfigArray());
        $dataStorageInit->setReturnUrl(xtc_href_link('callback/wirecard/checkout_seamless_datastorage_return.php', '',
            'SSL'));
        $dataStorageInit->setOrderIdent(session_id());
        $response = null;

        if ($this->getConfigValue('pci3_dss_saq_a_enable')) {

            $dataStorageInit->setJavascriptScriptVersion('pci3');

            if (strlen(trim($this->getConfigValue('iframe_css_url')))) {
                $url = xtc_href_link(DIR_WS_EXTERNAL . 'wirecardcheckoutseamless/css/' . trim($this->getConfigValue('iframe_css_url')),
                    '', 'SSL', false);
                $dataStorageInit->setIframeCssUrl($url);
            }

            $dataStorageInit->setCreditCardShowCardholderNameField($this->getConfigValue('displaycardholder'));
            $dataStorageInit->setCreditCardShowCvcField($this->getConfigValue('displaycvc'));
            $dataStorageInit->setCreditCardShowIssueDateField($this->getConfigValue('displayissuedate'));
            $dataStorageInit->setCreditCardShowIssueNumberField($this->getConfigValue('displayissuenumber'));

            if (strlen($this->getConfigValue('cvc_placeholder'))) {
                $dataStorageInit->setCreditCardCvcPlaceholder($this->getConfigValue('cvc_placeholder'));
            }

            if (strlen($this->getConfigValue('pan_placeholder'))) {
                $dataStorageInit->setCreditCardPanPlaceholder($this->getConfigValue('pan_placeholder'));
            }
            $dataStorageInit->setCreditCardCardholderNamePlaceholder($this->getConfigValue('cardholder_placeholder'));
            $dataStorageInit->setCreditCardCardIssueNumberPlaceholder($this->getConfigValue('issuenumber_placeholder'));

            $dataStorageInit->setCreditCardShowIssueDatePlaceholder($this->getConfigValue('displayissuedate_placeholder'));
            $dataStorageInit->setCreditCardShowExpirationDatePlaceholder($this->getConfigValue('displayexpirationdate_placeholder'));

        }

        try {
            $this->log(__METHOD__ . ':' . print_r($dataStorageInit->getRequestData(), true));

            $response = $dataStorageInit->initiate();

            if ($response->getStatus() != WirecardCEE_QMore_DataStorage_Response_Initiation::STATE_SUCCESS) {
                $dsErrors = $response->getErrors();
                $msg = array();
                foreach ($dsErrors as $error) {
                    $msg[] = 'DataStorage: ' . $error->getConsumerMessage();
                }

                $msg = implode(', ', $msg);
                $this->log(__METHOD__ . ':' . $msg);
                throw new WirecardCheckoutSeamlessUserException($msg);
            }
        } catch (Exception $e) {

            $this->log(__METHOD__ . ':' . $e->getMessage());
            throw new WirecardCheckoutSeamlessException($e->getMessage());
        }

        return $response;
    }


    /**
     * read wirecard datastorage
     *
     * @return null|\WirecardCEE_QMore_DataStorage_Response_Read
     * @throws WirecardCheckoutSeamlessException
     */
    public function readDataStorage()
    {
        $dataStorageRead = new WirecardCEE_QMore_DataStorageClient($this->_getConfigArray());
        $dataStorageRead->setStorageId($_SESSION['wcs_storage_id']);

        try {
            return $dataStorageRead->read();
        } catch (Exception $e) {
            $this->log(__METHOD__ . ':' . $e->getMessage());

            return null;
        }

    }


    /**
     * return financial institutions from wirecard (backend operation)
     *
     * @param string $paymentType
     *
     * @return bool|array
     */
    public function getFinancialInstitutions($paymentType)
    {
        $cl = new WirecardCEE_QMore_BackendClient($this->_getBackendConfigArray());

        $response = $cl->getFinancialInstitutions($paymentType);
        if (!$response->hasFailed()) {
            $ret = $response->getFinancialInstitutions();
            $c = null;
            if (class_exists('Collator')) {
                $c = new Collator('root');
            }

            uasort($ret, function ($a, $b) use ($c) {
                if ($c === null) {
                    return strcmp($a['id'], $b['id']);
                } else {
                    return $c->compare($a['name'], $b['name']);
                }
            });

            $grouped = array();
            foreach ($ret as $e) {
                if (!isset($grouped[$e['country']])) {
                    $grouped[$e['country']] = array();
                }

                $grouped[$e['country']][] = $e;
            }

            return $grouped;
        } else {
            $this->log(__METHOD__ . ':' . print_r($response->getErrors(), true), LOG_WARNING);

            return false;
        }
    }

    /**
     * log messages
     *
     * @param string $message
     * @param string $loglevel DEBUG, FINE, INFO, WARN, ERROR, CUSTOM
     *
     * @return $this
     */
    public function log($message, $loglevel = 'CUSTOM')
    {
        $this->_logger->log($message, $loglevel);

        return $this;
    }


}
