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

class ZoodpayValidationModuleFrontController extends ModuleFrontController
{

/*** Processa os dados enviados pelo formulário de pagamento*/
    public function postProcess()
    {

        /**
        * Get current cart object from session
        */
        $cart = $this->context->cart;
        $authorized = false;
        /**
        * Verify if this module is enabled and if the cart has
        * a valid customer, delivery address and invoice address
        */
        if (!$this->module->active || $cart->id_customer == 0 || $cart->id_address_delivery == 0
            || $cart->id_address_invoice == 0) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        /**
        * Verify if this payment module is authorized
        */
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'zoodpay') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->l('This payment method is not available.'));
        }
        /** @var CustomerCore $customer */

        $customer = new Customer($cart->id_customer);

        /**
        * Check if this is a valid customer account
        */

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        /**
        * Place the order
        */

            

        $payment = $this->paymentCreateCharge();

        if ($payment['status'] == 0) {
           
            Tools::redirect('index.php?controller=order&error='.rawurlencode($this->l($payment['message'])));

        } elseif ($payment['status'] == 1) {
            $this->module->validateOrder(
                (int)$this->context->cart->id,
                Configuration::get('ZOODPAY_PAYMENT_HOLD'),
                (float)$this->context->cart->getOrderTotal(true, Cart::BOTH),
                $this->module->displayName . '(' . Tools::getValue('service_name') . ')',
                null,
                null,
                (int)$this->context->currency->id,
                false,
                $customer->secure_key
            );

            $query = "INSERT INTO " . _DB_PREFIX_ . "zoodpay_payment_trn ( `id_transaction`, `order_id`) VALUES
            ('" . pSQL($payment['transaction_id']) . "', '" . (int)$payment['order_id'] . "') ";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($query);

                        
            Tools::redirect($payment['url']);
        }
    }
    
    

    private function paymentCreateCharge()
    {
        $config = array();
        $cart = $this->context->cart;
        $currency = $this->context->currency;
        $customer = new Customer((int)$cart->id_customer);
        $billingAddress = new Address((int)$cart->id_address_invoice);
        $shippingAddress = new Address((int)$cart->id_address_delivery);
        //$totalAmmount = (float)Tools::ps_round((float)$this->context->cart->getOrderTotal(true, Cart::BOTH));
        $marchentKey = Configuration::get('ZoodPay_MARCHENT_KEY');
        $secretKey = Configuration::get('ZoodPay_MARCHENT_SECRET_KEY');
        $salt = Configuration::get('ZoodPay_MARCHENT_SALT_KEY');
        $APIURL = Configuration::get('ZoodPay_API_URL');
        $marketCode = Configuration::get('ZoodPay_MARKET_CODE');
        $lang_code = Configuration::get('ZoodPay_LANGUAGE_CODE');
        $orderId = (int)$cart->id;
        $shippingState = new State((int)$shippingAddress->id_state);
        $config['authorization'] = $secretKey;
        $config['mode'] = Configuration::get('CHECKOUTAPI_TEST_MODE');
        $config['timeout'] = Configuration::get('CHECKOUTAPI_GATEWAY_TIMEOUT');
        $billPhoneLength = Tools::strlen($billingAddress->phone);
        $totalReductonValue = (float)$this->context->cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS);
        $total = (float)$this->context->cart->getOrderTotal(true, Cart::BOTH);
        $tax_total =  (float)$this->context->cart->getOrderTotal(false, Cart::BOTH);
        $taxamount = ($total - $tax_total);

        if (Tools::getValue('service_name') == "" || Tools::getValue('service_name') == null) {
            return array(
                'status' => 0,
                'message' => $this->l("Select a zoodpay payment option")
                );
        }
        $billingAddressConfig = array(
                            'addressLine1' => $billingAddress->address1,
                            'addressLine2' => $billingAddress->address2,
                            'postcode' => $billingAddress->postcode,
                            'country' => zoodpay::getIsoCodeById($billingAddress->id_country),
                            'city' => $billingAddress->city,
                            );
        $sign = $marchentKey . '|' . $orderId . '|' . $total . '|' . $currency->iso_code .
        '|' . $marketCode . '|' . htmlspecialchars_decode($salt);
        $signature = hash('sha512', $sign);
        
        if ($billPhoneLength > 6) {
            $bilPhoneArray = array(
            'phone' => array(
            'number' => $billingAddress->phone
            )
            );
            $billingAddressConfig = array_merge_recursive($billingAddressConfig, $bilPhoneArray);
        }

        $shipPhoneLength = Tools::strlen($shippingAddress->phone);

        $shippingAddressConfig = array(
                'addressLine1' => $shippingAddress->address1,
                'addressLine2' => $shippingAddress->address2,
                'postcode' => $shippingAddress->postcode,
                'country' => zoodpay::getIsoCodeById($shippingAddress->id_country) ,
                'city' => $shippingAddress->city,
                );
        if ($shipPhoneLength > 6) {
            $shipPhoneArray = array(
                            'phone' => array(
                            'number' => $shippingAddress->phone
                            )
                        );
            $shippingAddressConfig = array_merge_recursive($shippingAddressConfig, $shipPhoneArray);
        }

        $final_products = array();
        $count = 0;
        $product =  array();
        $products =  array();
            
        foreach ($cart->getProducts() as $item) {
            $product[$item['id_product']]['categories'] = array(
                                    array(
                                    "uncategorised"
                                    )
                                );
                                       
            $products[$item['id_product']] = array(
                                'currency_code' => $currency->iso_code,
                                'discount_amount' => $item['reduction'],
                                'name' => strip_tags($item['name']) ,
                                'price' => "" .$item['price'] . "",
                                'sku' => strip_tags($item['reference']) ,
                                'quantity' => (int)$item['cart_quantity'] ,
                                'tax_amount' => "" . $item['rate'] . "",
                                );
        }
                                 
        
                    
        if ($totalReductonValue == "" || $totalReductonValue == 0) {
            $totalReductonValue = "0.00";
        }

        if ($taxamount == "" || $taxamount == 0) {
            $taxamount = "0.00";
        }
                    
                                                 
        foreach ($products as $key => $value) {
            if (array_key_exists($key, $product)) {
                $final_products[$count] = array_merge(
                    $product[$key],
                    $products[$key]
                );
            }
            $count++;
        }

        $cariar_query = "SELECT `name` FROM "._DB_PREFIX_."carrier WHERE `id_carrier` ='".$cart->id_carrier."'";

        $cariar = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($cariar_query);

        $paymentArray = json_encode(
            array(
                "billing" => array(
                        "address_line1" => $billingAddress->address1,
                        "address_line2" => $billingAddress->address2,
                        "city" => $billingAddress->city,
                        "country_code" => zoodpay::getIsoCodeById($billingAddress->id_country) ,
                        "name" => $billingAddress->firstname,
                        "phone_number" => $billingAddress->phone,
                        "state" => $billingAddress->city,
                        "zipcode" => $billingAddress->postcode
                        ) ,
                "customer" => array(
                        "customer_dob" => date('Y-m-d', strtotime($customer->birthday)) ,
                        "customer_email" => $customer->email,
                        "customer_phone" => $billingAddress->phone,
                        "first_name" => $customer->firstname,
                        "last_name" => $customer->lastname
                        ) ,
                "items" => $final_products,
                "order" => array(
                        "amount" => $total,
                        "currency" => $currency->iso_code,
                        "discount_amount" => $totalReductonValue,
                        "lang" => $lang_code,
                        "market_code" => $marketCode,
                        "merchant_reference_no" => "" . $orderId . "",
                        "service_code" => Tools::getValue('service_name'),
                        "shipping_amount" => $cart->getPackageShippingCost(),
                        "signature" => $signature,
                        "tax_amount" => $taxamount
                    ) ,
                "shipping" => array(
                        "address_line1" => $shippingAddress->address1,
                        "address_line2" => $shippingAddress->address2,
                        "city" => $shippingAddress->city,
                        "country_code" => zoodpay::getIsoCodeById($shippingAddress->id_country) ,
                        "name" => $shippingAddress->firstname,
                        "phone_number" => $shippingAddress->phone,
                        "state" => $shippingState->name,
                        "zipcode" => $shippingAddress->postcode
                        ) ,
                "shipping_service" => array(
                        "name" => json_encode($cariar),
                        "priority" => "",
                        "shipped_at" => "",
                        "tracking" => ""
                        )
                    )
        );

        $payment_headers = [
                'Accept: application/json',
                'Content-Length: ' . Tools::strlen($paymentArray) ,
                'Content-Type: application/json',
            ];
            
        $payment_url = $APIURL . 'transactions';

        $P_ch = curl_init();
        curl_setopt($P_ch, CURLOPT_POST, 1);
        curl_setopt($P_ch, CURLOPT_URL, $payment_url);
        curl_setopt($P_ch, CURLOPT_USERPWD, "$marchentKey:$secretKey");
        curl_setopt($P_ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($P_ch, CURLOPT_POSTFIELDS, $paymentArray);
        curl_setopt($P_ch, CURLOPT_HEADER, 0);
        curl_setopt($P_ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($P_ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($P_ch, CURLOPT_HTTPHEADER, $payment_headers);
        curl_setopt($P_ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($P_ch);
        curl_close($P_ch);

        //var_dump($response);
        $return_data = json_decode($response);
        //  echo "<pre>";
        //  print_r($return_data);
        // die("edd");

        if (!empty($return_data->message)) {
            $message = "";
            if (!empty($return_data->details)) {
                for ($i = 0; $i < count($return_data->details); $i++) {
                    $message .= $return_data->details[0]->error.'\n';
                }
                
                return array(
                'status' => 0,
                'message' => $this->l($message) ,
                    
                );
            // exit;
            } else {
                $message = $this->l($return_data->message);
            
                return array(
                'status' => 0,
                'message' => $this->l($message),
                       
                );
                // exit;
            }
        }
        if ($return_data->transaction_id) {
            return array(
                'status' => 1,
                'url' => $return_data->payment_url,
                'transaction_id' => $return_data->transaction_id,
                'order_id' => $orderId
            );
        } else {
            return array(
                'status' => 0,
                'message' => $this->l($message)
            );
        }
    }
}
