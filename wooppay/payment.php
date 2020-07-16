<?
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2012-2015 Wooppay
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @copyright   Copyright (c) 2012-2015 Wooppay
 * @author      Yaroshenko Vladimir <mr.struct@mail.ru>
 * @version     1.0
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

include(GetLangFileName(dirname(__FILE__) . "/", "/wooppay.php"));
if (!class_exists('WooppaySoapClient')) {
    require('WooppaySoapClient.php');
}

$orderID = IntVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ID"]);
$order = CSaleOrder::GetByID($orderID);
if ($order && $order['PAYED'] == 'N' && !isset($_POST['WOOPPAY_PAY'])) {
    ?>
    <form method="post" action="<?= POST_FORM_ACTION_URI?>">
        <input type="submit" name="WOOPPAY_PAY" value="Оплатить" class="pull-right btn btn-default btn-lg"/>
    </form>
    <?
} else if ($order && $order['PAYED'] != 'Y') {
    $connect_options = array();
    $connect_options['login'] = 'test';
    $connect_options['password'] = 'gjvtyzkgfhjkm!';
    $connect_options['authentication'] = SOAP_AUTHENTICATION_BASIC;

    $client = new WooppaySoapClient(CSalePaySystemAction::GetParamValue('URL'), $connect_options);
    $login_request = new CoreLoginRequest();
    $login_request->username = CSalePaySystemAction::GetParamValue('LOGIN');
    $login_request->password = CSalePaySystemAction::GetParamValue('PASS');

    try {
        if ($client->login($login_request)) {
            $prefix = trim(CSalePaySystemAction::GetParamValue('ORDER_PREFIX'));
            $invoice_request = new CashCreateInvoiceExtended2Request();
            $invoice_request->referenceId = $prefix . $order['ID'];
            $invoice_request->backUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/personal/order/';
            $invoice_request->requestUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/bitrix/modules/sale/payment/wooppay/wooppay_result.php?orderId=' . $order['ID'] . '&key=' . md5($order['ID']);
            $invoice_request->addInfo = 'Payment for Order ' . $order['ID'];
            $invoice_request->amount = (float)$order['PRICE'];
            $invoice_request->deathDate = '';
            $invoice_request->description = '';
            $invoice_request->userPhone = $GLOBALS['SALE_INPUT_PARAMS']['PROPERTY']['PHONE'];
            $invoice_request->userEmail = $USER->GetEmail();
            $invoice_data = $client->createInvoice($invoice_request);
            LocalRedirect($invoice_data->response->operationUrl, true);
        }
    } catch (Exception $e) {
        echo 'К сожалению, в системе <em>Wooppay</em> не удалось создать счёт на оплату вашего заказа. Попробуйте повторить оплату позже из персонального раздела.';
    }
} else {
    echo 'Заказ оплачен';
}
?>