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


require_once(DIR_FS_EXTERNAL . 'wirecardcheckoutseamless/Seamless.php');
require_once(DIR_FS_EXTERNAL . 'wirecardcheckoutseamless/Backend.php');


class WirecardCheckoutSeamlessAdmin extends WirecardCheckoutSeamless
{
    /**
     * test configuration
     * @return bool
     * @throws Exception
     */
    public function configTest()
    {
        $client = new WirecardCheckoutSeamlessBackend();
        if (!$client->isAvailable()) {
            throw new Exception($this->getText('TEST_NOBACKENDPW'));
        } else {
            $init = new WirecardCEE_QMore_FrontendClient($this->_getConfigArray());
            $init->setPluginVersion($this->getPluginVersion());
            $init->setConfirmUrl(xtc_href_link('callback/wirecard/checkout_seamless_confirm.php', '', 'SSL', false));

            $init->setOrderReference(sprintf('Configtest #', uniqid()));

            $returnUrl = xtc_href_link('callback/wirecard/checkout_seamless_return.php', 'txId=' . uniqid(), 'SSL',
                false);

            $consumerData = new WirecardCEE_Stdlib_ConsumerData();
            $consumerData->setIpAddress('127.0.0.1');
            $consumerData->setUserAgent('Mozilla/5.0 (iPad; U; CPU OS 3_2_1 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Mobile/7B405');

            $init->setAmount('10')
                ->setCurrency('EUR')
                ->setPaymentType(WirecardCEE_QMore_PaymentType::CCARD)
                ->setOrderDescription('Configtest #' . uniqid())
                ->setSuccessUrl($returnUrl)
                ->setPendingUrl($returnUrl)
                ->setCancelUrl($returnUrl)
                ->setFailureUrl($returnUrl)
                ->setServiceUrl($this->getConfigValue('service_url'))
                ->setDuplicateRequestCheck(false)
                ->setAutoDeposit($this->getConfigValue('autodeposit'))
                ->setConsumerData($consumerData);

            $initResponse = $init->initiate();

            if ($initResponse->getStatus() == WirecardCEE_QMore_Response_Initiation::STATE_FAILURE) {

                $msg = implode(',', array_map(function ($e) {
                    /** @var \WirecardCEE_QMore_Error $e */
                    return $e->getConsumerMessage();
                }, $initResponse->getErrors()));
                if (!strlen($msg)) {
                    $msg = $initResponse = implode(',', array_map(function ($e) {
                        /** @var \WirecardCEE_QMore_Error $e */
                        return $e->getPaySysMessage();
                    }, $initResponse->getErrors()));;
                }

                throw new Exception($msg);
            }

        }

        return true;
    }
}

