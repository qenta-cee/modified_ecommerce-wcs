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

var wcsValidateMinAge =function (dob, minage) {
    if (!minage)
        return true;

    var birthdate = new Date(dob);
    var year = birthdate.getFullYear();
    var today = new Date();
    var limit = new Date((today.getFullYear() - minage), today.getMonth(), today.getDate());

    return birthdate <= limit;
};

$(function () {

    var wirecardCheckoutSeamlessStore = function (data, messageContainer, onSuccess, onError) {
        var wirecardCee = new WirecardCEE_DataStorage;
        wirecardCee.storePaymentInformation(data, function (response) {

            if (response.getErrors()) {
                var errors = response.response.error;
                for (var i = 0; i <= response.response.errors; i++) {
                    if (typeof errors[i] === 'undefined') {
                        continue;
                    }
                    messageContainer.append('<p>' + htmlEntityDecode(errors[i].consumerMessage) + '</p>');
                }

                onError(response);
            }
            else {
                onSuccess(response);
            }
        });
    };

    var htmlEntityDecode = function (str) {
        var tarea = document.createElement('textarea');
        tarea.innerHTML = str;
        return tarea.value;
    };

    var wcsValidateField = function (field) {
        var result = true;

        if ($(field).hasClass('mandatory')) {
            result = ($(field).val().length > 0);
            if (result)
                $(field).removeClass('field-error');
            else
                $(field).addClass('field-error');
        }

        if ($(field).hasClass('mandatory-checked')) {
            result = ($(field).attr('checked'));
            if (result)
                $(field).removeClass('field-error');
            else
                $(field).addClass('field-error');
        }

        return result;
    };

    var paymentform = $('#checkout_payment');
    paymentform.find('.button_right input').on('click', function (evt) {
        var code = paymentform.find("input[name='payment']:checked").val();

        var paymenttype = $('#' + code + '_paymenttype').val();
        var messagebox = $('#' + code + '_messagebox');

        messagebox.empty();
        // not a wirecard payment
        if (!paymenttype)
            return true;

        var hasError = false;

        var paymentData = {
            'paymentType': paymenttype
        };

        if (code == 'wcs_invoice') {
            if (!paymentform.wcsValidateInvoice(messagebox)) {
                return false;
            }
        }

        if (code == 'wcs_installment') {
            if (!paymentform.wcsValidateInstallment(messagebox)) {
                return false;
            }
        }

        $('.' + code).each(function () {
            if (typeof $(this).data('wcs-fieldname') == 'undefined')
                return true;

            if (!wcsValidateField(this))
                hasError = true;

            paymentData[$(this).data('wcs-fieldname')] = $(this).val();
        });

        if (hasError)
            return false;

        if ($('#' + code + '_seamless').val() == 1) {
            wirecardCheckoutSeamlessStore(paymentData, messagebox, function (response) {
                paymentform.submit();
            }, function (response) {
                messagebox.css('display', 'block');
            });
            return false;
        } else {
            return true;
        }
    });
});