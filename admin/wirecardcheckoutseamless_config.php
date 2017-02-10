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
require_once(DIR_FS_EXTERNAL . 'wirecardcheckoutseamless/Admin.php');
$wcsAdmin = new WirecardCheckoutSeamlessAdmin();

$errors = array();
$infos = array();
if (isset($_POST['update']) && strlen($_POST['update'])) {
    foreach ($_POST['config'] as $key => $value) {
        if (($param = $wcsAdmin->getConfigParam($key)) === null) {
            continue;
        }

        $err = $wcsAdmin->validateConfigParam($param, $value);
        if (!count($err)) {
            $wcsAdmin->saveConfigValue($param['name'], $value);
        } else {
            $errors[$param['name']] = implode("<br>\n", $err);
        }
    }
    if (!count($errors)) {
        xtc_redirect(xtc_href_link(basename($PHP_SELF), 'success=1'));
    }
}

if (isset($_POST['test']) && strlen($_POST['test'])) {

    try {
        $wcsAdmin->configTest();
        $infos[] = $wcsAdmin->getText('CONFIG_TEST_OK');
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

if (isset($_GET['success'])) {
    $infos[] = $wcsAdmin->getText('CONFIG_SUCCESS');
}

$orders_statuses = array();
$orders_status_query = xtc_db_query("SELECT orders_status_id,
                                            orders_status_name
                                       FROM " . TABLE_ORDERS_STATUS . "
                                      WHERE language_id = '" . $_SESSION['languages_id'] . "'
                                   ORDER BY sort_order");
while ($orders_status = xtc_db_fetch_array($orders_status_query)) {
    $orders_statuses[] = array(
        'id' => $orders_status['orders_status_id'],
        'text' => $orders_status['orders_status_name']
    );
}

$infoText = $wcsAdmin->getText('wirecardinfo');
$infoText = sprintf($infoText, DIR_WS_EXTERNAL . 'wirecardcheckoutseamless/images/wirecard-logo.png');

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
    <form action="<?php echo xtc_href_link(basename($PHP_SELF)); ?>" method="post">
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
                            class="pageHeading pdg2"><?php echo TEXT_WIRECARDCHECKOUTSEAMLESS_CONFIG_HEADING_TITLE; ?></div>
                        <div style="margin-top: 20px;"><?php echo $infoText ?></div>
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
                            <?php
                            echo xtc_draw_form('config', basename($PHP_SELF),
                                xtc_get_all_get_params(array('action')) . 'action=update');

                            foreach ($wcsAdmin->getConfigParameters() as $group => $tab) {
                                $fields = $tab['fields'];
                                ?>
                                <tr>
                                    <td class="dataTableConfig col-left" style="text-decoration: underline;"
                                        colspan="3"><?php echo $wcsAdmin->getText($group); ?>:
                                    </td>
                                </tr>
                                <?php
                                foreach ($fields as $f) {
                                    $required = isset($f['required']) && $f['required'];
                                    $fieldname = $f['name'];
                                    $configvalue = $wcsAdmin->getConfigValue($f['name']);
                                    ?>
                                    <tr>
                                        <td class="dataTableConfig col-left"><?php
                                            if ($wcsAdmin->hasText($f['name'])) {
                                                echo $wcsAdmin->getText($fieldname);
                                            } else {
                                                echo $f['label'];
                                            }
                                            if ($required) {
                                                echo '<br/>' . TEXT_FIELD_REQUIRED;
                                            }
                                            ?>
                                        </td>
                                        <td class="dataTableConfig col-middle wcs-col-middle"><?php

                                            switch ($f['type']) {
                                                case 'select':
                                                    echo xtc_draw_pull_down_menu("config[$fieldname]", $f['options'],
                                                        $configvalue);
                                                    break;

                                                case 'onoff':
                                                    echo draw_on_off_selection("config[$fieldname]", 'checkbox',
                                                        $configvalue);
                                                    break;

                                                case 'orderstatus':
                                                    echo xtc_draw_pull_down_menu("config[$fieldname]", $orders_statuses,
                                                        $configvalue);
                                                    break;

                                                default:
                                                    echo xtc_draw_input_field("config[$fieldname]",
                                                        $configvalue, 'style="width: 100%;"');

                                            }
                                            ?>
                                        </td>
                                        <td class="dataTableConfig col-right">
                                            <?php
                                            if ($wcsAdmin->hasText($f['name'] . '_DESC')) {
                                                echo $wcsAdmin->getText($f['name'] . '_DESC');
                                            } else {
                                                echo is_array($f['doc']) ? implode('<br/>', $f['doc']) : $f['doc'];
                                            }
                                            if (isset($f['docref'])) {
                                                printf('<br/><a href="%s" target="_blank">%s</a>', $f['docref'],
                                                    $wcsAdmin->getText('more_information'));
                                            }

                                            ?></td>
                                    </tr>

                                    <?php
                                }
                            }
                            ?>

                            <tr>
                                <td class="txta-l" colspan="2" style="border:none;">
                                    <input class="button btn_wide" type="submit" name="update"
                                           value="<?php echo $wcsAdmin->getText('CONFIG_SAVE'); ?>">
                                </td>
                                <td class="txta-r" style="border:none;">
                                    <input class="button btn_wide" type="submit" name="test"
                                           value="<?php echo $wcsAdmin->getText('CONFIG_TEST'); ?>">
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
    </body>
    </html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>