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

class ZoodPayActionModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $marchentKey  = Configuration::get('ZoodPay_MARCHENT_KEY');
        $salt         = Configuration::get('ZoodPay_MARCHENT_SALT_KEY');
        $marketCode   = Configuration::get('ZoodPay_MARKET_CODE');
        $currency     = $this->context->currency;
        $VALUE        = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $total        = $VALUE['amount'];
        $orderId      = $VALUE['merchant_order_reference'];
        $get_trn_ID   = $VALUE['transaction_id'];
        $id_order     = Order::getOrderByCartId($orderId);

        $order        = new Order($id_order);

        $history      = new OrderHistory();

        if ($VALUE['status'] != "" && $VALUE['status'] != null && $VALUE['status'] != 'Failed') {
            $sign = $marketCode . '|' . $currency->iso_code . '|' . $total .
            '|' . $orderId . '|' . $marchentKey . '|' . $get_trn_ID . '|' . htmlspecialchars_decode($salt);

            $signature = hash('sha512', $sign);

            $history->id_order = (int)$order->id;

            if ($VALUE['signature'] == $signature) {
                if ($VALUE['status'] == "Paid") {
                    $history->changeIdOrderState(2, (int)($order->id));

                    $order_payment_collection = $order->getOrderPaymentCollection();
                    $order_payment = $order_payment_collection[0];
                    $order_payment->transaction_id = $VALUE['transaction_id'];
                    $order_payment->update();

                    $paid = "UPDATE  " . _DB_PREFIX_ . "order_history SET
                    id_order_state = '2' WHERE id_order ='" . (int)$order->id . "'";

                    Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($paid);

                    Tools::redirect(
                        'index.php?controller=order-confirmation&id_cart=' . $VALUE['merchant_order_reference'] .
                        '&id_module=' . $this->module->id . '&id_order=' . $id_order . '&key=' . $order->secure_key
                    );
                } else {
                    Tools::redirect('index.php?module=' . $this->module->name .
                    '&controller=failure&fc=module&key=' . $order->secure_key);
                }
            }

            Tools::redirect('index.php?module=' . $this->module->name .
            '&controller=success&fc=module&key=' . $order->secure_key);
        } elseif ($VALUE['status'] != "" && $VALUE['status'] != null && $VALUE['status'] == 'Failed') {
            $history->changeIdOrderState(8, (int)($order->id));

            $error = "UPDATE  " . _DB_PREFIX_ . "order_history SET
            id_order_state = '8' WHERE id_order ='" . $order->id . "'";

            Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($error);

            Tools::redirect('index.php?module=' . $this->module->name .
            '&controller=failure&fc=module&key=' . $order->secure_key);
        }
    }
}
