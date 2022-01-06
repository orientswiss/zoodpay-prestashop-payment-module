<?php
/**
 * 2007-2021 ZoodPay
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author 2007-2021 ZoodPay
 * @copyright ZoodPay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class ZoodPayIpnModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $action = Tools::getValue('zoodpay_action');
        
        if($action != '' && $action == "ipn"){

            $this->ipnUpdateOrder();

        }elseif($action != '' && $action == "refund"){

            $this->ipnRefundOrder();

        }else{
            
            $modulelog = _PS_MODULE_DIR_ . 'zoodpay/zoodpay.log';
            $date = date('m/d/Y h:i:s a', time());
            $message = "Bad IPN request: " . $date;
            error_log($message, 3, $modulelog);
            exit;
        }
       
    }

    public function ipnUpdateOrder(){

        if ($_VALUE = Tools::file_get_contents("php://input")) {

            $data = json_decode($_VALUE, true);
        } else {

            $data = $_VALUE;
        }


        $order_reference = filter_var($data['merchant_order_reference'], FILTER_SANITIZE_STRING);
        $status = filter_var($data['status'], FILTER_SANITIZE_STRING);
        $otransaction_id = filter_var($data['transaction_id'], FILTER_SANITIZE_STRING);
        $osignature = filter_var($data['signature'], FILTER_SANITIZE_STRING);

        $marchentKey = Configuration::get('ZoodPay_MARCHENT_KEY');
        $scretKey = Configuration::get('ZoodPay_MARCHENT_SECRET_KEY');
        $saltKey = Configuration::get('ZoodPay_MARCHENT_SALT_KEY');

        $marketCode = Configuration::get('ZoodPay_MARKET_CODE');
        $orderId      = $order_reference;

        $id_order     = Order::getOrderByCartId($orderId);

        $currency_query = "SELECT `id_currency` FROM " . _DB_PREFIX_ . "orders WHERE `id_cart` ='" . (int)$orderId . "'";
        
        $c_id = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($currency_query);

        $order = new Order((int)$id_order);

        $trn_id = "SELECT `id_transaction` 
        FROM " . _DB_PREFIX_ . "zoodpay_payment_trn WHERE `order_id` ='" . (int)$orderId . "'";

        $get_trn = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($trn_id);
        $get_trn_ID = $get_trn;
        $currency = new CurrencyCore($c_id);
        $currency = $currency->iso_code;
        $total = number_format($order->total_paid, 2);
        $history            = new OrderHistory();
        $history->id_order  = (int)$order->id;

        $sign = $marketCode.'|'.$currency.'|'
        .$total.'|'.$orderId .'|'.$marchentKey.'|'.$get_trn_ID.'|'.htmlspecialchars_decode($saltKey);

        $date = date('m/d/Y h:i:s a', time());
        $modulelog = _PS_MODULE_DIR_ . 'zoodpay/zoodpay.log';
        $message = 'IPN Called: ' . $date . " : " . $sign . '-' . json_encode($data) . PHP_EOL;
        error_log($message, 3, $modulelog);

        $signature = hash('sha512', $sign);

        if ($status == "Paid" && $otransaction_id == $get_trn_ID && $osignature == $signature) {

            if ($order->current_state != 2) {
                $history->changeIdOrderState(2, (int)($order->id));
            }
            
            $order_payment_collection = $order->getOrderPaymentCollection();
            $order_payment = $order_payment_collection[0];
            $order_payment->transaction_id = $otransaction_id;
            $order_payment->update();

            $paid = "UPDATE  " . _DB_PREFIX_ . "order_history
            SET id_order_state = '2' WHERE id_order ='" . (int)$order->id . "'";

            Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($paid);
        } elseif ($status == "Inactive" && $otransaction_id == $get_trn_ID && $osignature == $signature) {
            $history->changeIdOrderState(8, (int)($order->id));

            $s_paid = "UPDATE  " . _DB_PREFIX_ .
            "order_history SET id_order_state = '8' WHERE id_order ='" . (int)$order->id . "'";

            Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($s_paid);
        } elseif ($status == "Failed" && $otransaction_id == $get_trn_ID && $osignature == $signature) {
            $faild = "UPDATE  " . _DB_PREFIX_ .
            "order_history SET id_order_state = '8' WHERE id_order ='" . (int)$order->id . "'";

            Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($faild);
            $history->changeIdOrderState(8, (int)($order->id));
        } elseif ($status == "Cancelled" && $otransaction_id == $get_trn_ID && $osignature == $signature) {
            $can_faild = "UPDATE  " . _DB_PREFIX_ .
            "order_history SET id_order_state = '6' WHERE id_order ='" . (int)$order->id . "'";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($can_faild);

            $history->changeIdOrderState(6, (int)($order->id));
        }
         exit;
    }


    public function ipnRefundOrder(){

        if ($VALUE = Tools::file_get_contents("php://input")) {

         $data = json_decode($VALUE, true);
        } else {

             $data = $VALUE;
        }

     $merchant_refund = filter_var($data['refund']['merchant_refund_reference'], FILTER_SANITIZE_STRING);
     $refund_amount   = filter_var($data['refund']['refund_amount'], FILTER_SANITIZE_STRING);
     $refund_status   = filter_var($data['refund']['status'], FILTER_SANITIZE_STRING);
     $refund_id       = filter_var($data['refund']['refund_id'], FILTER_SANITIZE_STRING);
     $refundsignature = filter_var($data['signature'], FILTER_SANITIZE_STRING);

     $marchentKey = Configuration::get('ZoodPay_MARCHENT_KEY');
     $scretKey = Configuration::get('ZoodPay_MARCHENT_SECRET_KEY');
     $saltKey = Configuration::get('ZoodPay_MARCHENT_SALT_KEY');

     $orderId      = $merchant_refund;

     $id_order     = Order::getOrderByCartId($orderId);

     $order = new Order((int)$id_order);

     $history = new OrderHistory();
     $refund__amount = number_format($refund_amount, 2);
     $sign = $merchant_refund . '|' . $refund__amount . '|'
    . $refund_status . '|' . $marchentKey . '|' . $refund_id . '|' . htmlspecialchars_decode($saltKey);
     $signature = hash('sha512', $sign);

     $date = date('m/d/Y h:i:s a', time());
     $modulelog = _PS_MODULE_DIR_ . 'zoodpay/zoodpay.log';
     $message = 'IPN Refund: ' . $date . " : " . $sign . '-' . json_encode($data) . PHP_EOL;
     error_log($message, 3, $modulelog);

    if ($refundsignature == $signature) {
         $rhold = (int)Configuration::get('ZOODPAY_REFUND_HOLD');
         $rdecline = (int)Configuration::get('ZOODPAY_REFUND_DECLINE');

        if ($refund_status == 'Declined') {
             $sql = 'UPDATE ' . _DB_PREFIX_ . 'zoodpay_refund_order SET refund_amount = "' . $refund_amount . '",
             refund_status = "' . $refund_status . '"  WHERE refund_id = "' . (int)$refund_id . '" ';
             Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($sql);

             $history->changeIdOrderState($rdecline, (int)($id_order));

             $insert = "INSERT INTO " . _DB_PREFIX_ . "order_history
             (id_employee,id_order,id_order_state,date_add) VALUES ('0','$id_order','$rdecline',NOW())";

             Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($insert);
        } elseif ($refund_status == 'Approved') {
             $sql = 'UPDATE ' . _DB_PREFIX_ . 'zoodpay_refund_order SET refund_amount = "' . $refund_amount . '",
             refund_status = "' . $refund_status . '" WHERE refund_id = "' . (int)$refund_id . '"';
             Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($sql);

             $history->changeIdOrderState(7, (int)($id_order));

             $insert = "INSERT INTO " . _DB_PREFIX_ . "order_history 
             (id_employee,id_order,id_order_state,date_add) VALUES ('0','$id_order','7',NOW())";

             Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($insert);
        } else {
             $history->changeIdOrderState($rhold, (int)($order->id));
             $sql = 'UPDATE ' . _DB_PREFIX_ . 'zoodpay_refund_order SET refund_amount = "' . $refund_amount . '",
             refund_status = "' . $refund_status . '"   WHERE refund_id = "' . (int)$refund_id . '" ';
             Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($sql);

             $history->changeIdOrderState($rhold, (int)($id_order));

             $insert = "INSERT INTO " . _DB_PREFIX_ .
             "order_history (id_employee,id_order,id_order_state,date_add) VALUES ('0','".(int)$id_order."','$rhold',NOW())";

             Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($insert);
            }
        }
        exit;
    }
}
