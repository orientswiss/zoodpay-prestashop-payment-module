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

if (!defined('_PS_VERSION_')) {
    exit;
}

class ZoodPay extends PaymentModule
{
    private $htmlText = '';

    private $formpostErrors = array();

    public $address;

    /**
        * ZoodPay constructor.
        *
        * Set the information about this module
        */

    public function __construct()
    {
        $this->name = $this->l('zoodpay');

        $this->tab = 'payments_gateways';

        $this->version = '1.0.1';

        $this->author =  $this->l('W3care Team');

        $this->controllers = array(
        'payment',
        'validation',
        'ajax',
        );

        $this->currencies = true;

        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;

        parent::__construct();
		
		$action = Tools::getValue('action');
    
		if ($action == "AjaxSaveConifgration") {
			$this->ajaxSaveConifgration();
		} elseif ($action == "REFUNDPROCESS") {
			$this->refundProcess();
		} elseif ($action == "GETREFUND") {
			$this->getRefundProduct();
		} elseif ($action == "AjaxGetAPIResponse") {
			$this->ajaxGetApiResponse();
		}
        $this->displayName =  $this->l('ZoodPay Payment module');

        $this->description =  $this->l('ZoodPay Payment module.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');

        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => _PS_VERSION_);
        $this->module_key = 'a38f936d15871d952b7e6aa8b8a06f92';
    }

    /**
        * Install this module and register the following Hooks:
        *
        * @return bool
        */

    
    

    public function install()
    {
        Db::getInstance()
        ->Execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'zoodpay_refund_order` (
         `id_order` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
         `id_transaction` varchar(50) NOT NULL,
         `order_id` int(11) DEFAULT NULL,
         `cart_id` int(11) DEFAULT NULL,
         `referance` varchar(50) DEFAULT NULL,
         `refund_id` varchar(50) DEFAULT NULL,
         `currency` varchar(10) DEFAULT NULL,
         `total_paid` varchar(50) NOT NULL,
         `refund_amount` varchar(50) NOT NULL,
         `refund_date` varchar(250) NOT NULL,
         `refund_status` varchar(50) DEFAULT NULL,
          PRIMARY KEY (`id_order`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8'
        );

        Db::getInstance()
        ->Execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'zoodpay_payment_trn` (
            `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_transaction` varchar(50) NOT NULL,
            `order_id` int(11) DEFAULT NULL,
             PRIMARY KEY (`id`)
                 ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 '
        );

        if (!Configuration::get('Zoodpay_ORDER_STATE')) {
            $this->setZoodpayOrderState('ZOODPAY_REFUND_STATE', $this->l('Refund Initiated'), '#b5eaaa');
            $this->setZoodpayOrderState('ZOODPAY_REFUND_HOLD', $this->l('Refund Hold'), '#E77471');
            $this->setZoodpayOrderState('ZOODPAY_REFUND_DECLINE', $this->l('Refund Declined'), '#ff0000');
            $this->setZoodpayOrderState('ZOODPAY_PAYMENT_HOLD', $this->l('Awaiting for zoodpay payments'), '#4169E1');
            Configuration::updateValue('Zoodpay_ORDER_STATE', '1');
        }

        return parent::install()
        && $this->registerHook('paymentReturn')
        && $this->registerHook('actionOrderStatusPostUpdate')
        && $this->registerHook('paymentOptions')
        && $this->registerHook('header');
    }

    /**
        * Uninstall this module and remove it from all hooks
        *
        * @return bool
        */

    public function uninstall()
    {

        // if (!Configuration::deleteByName('ZoodPay_MARCHENT_KEY')
        

        //         || !Configuration::deleteByName('ZoodPay_MARCHENT_SECRET_KEY')
        

        //         || !Configuration::deleteByName('ZoodPay_MARCHENT_SALT_KEY')
        

        //         || !Configuration::deleteByName('ZoodPay_MARKET_CODE')
        

        //         || !Configuration::deleteByName('ZoodPay_LANGUAGE_CODE')
        

        //         || !Configuration::deleteByName('ZoodPay_API_URL')
        

        //         || !Configuration::deleteByName('ZoodPay_TC')
        

        //    || !Configuration::deleteByName('_Zoodpay_config_status_')
        

        //         || !parent::uninstall()) {
        

        //     return false;
        

        // }

        parent::uninstall();

        return true;
    }

    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/custom.css');
        $this->context->controller->addJS($this->_path . 'views/js/main.js');

    }

    public function setZoodpayOrderState($var_name, $status, $color)
    {
        $orderState       = new OrderState();
        $orderState->name = array();
        foreach (Language::getLanguages() as $language) {
            $orderState->name[$language['id_lang']] = $status;
        }
        $orderState->send_email   = false;
        $orderState->color        = $color;
        $orderState->hidden       = false;
        $orderState->delivery     = false;
        $orderState->logable      = true;
        $orderState->invoice      = true;
        if ($orderState->add()) {
            $source = _PS_MODULE_DIR_ . 'zoodpay/views/img/os_zoodpay.png';
        }
        $destination = _PS_ROOT_DIR_ . '/img/os/' . (int)$orderState->id . '.gif';
        copy($source, $destination);
        Configuration::updateValue($var_name, (int)$orderState->id);
        return true;
    }

    protected function formZoodpayPostValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('ZoodPay_MARCHENT_KEY')) {
                $this->formpostErrors[] = $this->trans('Merchent key is required.', array(), 'Modules.ZoodPay.Admin');
            } elseif (!Tools::getValue('ZoodPay_MARCHENT_SECRET_KEY')) {
                $this->formpostErrors[]
                = $this->trans('Merchent secret key is required.', array(), "Modules.ZoodPay.Admin");
            } elseif (!Tools::getValue('ZoodPay_MARCHENT_SALT_KEY')) {
                $this->formpostErrors[]
                = $this->trans('Merchent salt key is required.', array(), "Modules.ZoodPay.Admin");
            } elseif (!Tools::getValue('ZoodPay_API_URL')) {
                $this->formpostErrors[] = $this->trans('API URL is required.', array(), "Modules.ZoodPay.Admin");
            } elseif (!preg_match('@^https?://@i', Tools::getValue('ZoodPay_API_URL'))) {
                $this->formpostErrors[] = $this->trans('Enter valid API URL.', array(), "Modules.ZoodPay.Admin");
            } elseif (!Tools::getValue('ZoodPay_TC')) {
                $this->formpostErrors[] = $this->trans('T&C URl is required.', array(), "Modules.ZoodPay.Admin");
            } elseif (!preg_match('@^https?://@i', Tools::getValue('ZoodPay_TC'))) {
                $this->formpostErrors[] = $this->trans('Enter valid T&C URL.', array(), "Modules.ZoodPay.Admin");
            } elseif (!Tools::getValue('ZoodPay_MARKET_CODE')) {
                $this->formpostErrors[] = $this->trans('Select Market Code.', array(), "Modules.ZoodPay.Admin");
            } elseif (!Tools::getValue('ZoodPay_LANGUAGE_CODE')) {
                $this->formpostErrors[] = $this->trans('Select language Code.', array(), "Modules.ZoodPay.Admin");
            }
        }
    }

    protected function formZoodpayPostProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {

            $SS_KEY = Configuration::get('ZoodPay_MARCHENT_SECRET_KEY');

            $SALTKEY = Configuration::get('ZoodPay_MARCHENT_SALT_KEY');
            
            $SS_KEY_TOOLS=Tools::getValue('ZoodPay_MARCHENT_SECRET_KEY');
            
            $SS_SALT_TOOLS=Tools::getValue('ZoodPay_MARCHENT_SALT_KEY');
            
            if ($SS_KEY != Tools::getValue('ZoodPay_MARCHENT_SECRET_KEY')) {
                Configuration::updateValue('ZoodPay_MARCHENT_SECRET_KEY', $SS_KEY_TOOLS);
            } else {
                Configuration::updateValue('ZoodPay_MARCHENT_SECRET_KEY', $SS_KEY_TOOLS);
            }

            if ($SALTKEY != Tools::getValue('ZoodPay_MARCHENT_SALT_KEY')) {
                Configuration::updateValue('ZoodPay_MARCHENT_SALT_KEY', $SS_SALT_TOOLS);
            } else {
                Configuration::updateValue('ZoodPay_MARCHENT_SALT_KEY', $SS_SALT_TOOLS);
            }

            Configuration::updateValue('ZoodPay_MARCHENT_KEY', Tools::getValue('ZoodPay_MARCHENT_KEY'));

            Configuration::updateValue('ZoodPay_MARKET_CODE', Tools::getValue('ZoodPay_MARKET_CODE'));

            Configuration::updateValue('ZoodPay_LANGUAGE_CODE', Tools::getValue('ZoodPay_LANGUAGE_CODE'));

            if (preg_match('@^https?://@i', Tools::getValue('ZoodPay_API_URL'))) {
                Configuration::updateValue('ZoodPay_API_URL', Tools::getValue('ZoodPay_API_URL'));
            }

            if (preg_match('@^https?://@i', Tools::getValue('ZoodPay_TC'))) {
                Configuration::updateValue('ZoodPay_TC', Tools::getValue('ZoodPay_TC'));
            }
        }

        $this->htmlText .= $this->displayConfirmation($this->trans('Settings updated', array(), 'Admin.Global'));
    }

    /**
        * Returns a string containing the HTML necessary to
        * generate a configuration screen on the admin
        *
        * @return string
        */

    public function getContent()
    {
        if (Tools::isSubmit('btnSubmit')) {
            $this->formZoodpayPostValidation();

            if (!count($this->formpostErrors)) {
                $this->formZoodpayPostProcess();
            } else {
                foreach ($this->formpostErrors as $err) {
                    $this->htmlText .= $this->displayError($err);
                }
            }
        } else {
            //$this->htmlText .= '<br />';
        }

        $this->context->controller->addJS($this->_path . 'views/js/custom.js');

        $this->context->controller->addCSS($this->_path . 'views/css/main.css');

        $this->context->controller->addJS($this->_path . 'views/js/jquery.growl.js');

        $this->context->controller->addCSS($this->_path . 'views/css/jquery.growl.css');

        $this->htmlText .= $this->display(__FILE__, 'views/templates/hook/section_start.tpl');

        $this->htmlText .= $this->renderForm();

        $this->htmlText .= $this->display(__FILE__, 'views/templates/hook/section.tpl');

        $this->htmlText .= $this->display(__FILE__, 'views/templates/hook/refund.tpl');

        $this->htmlText .= $this->display(__FILE__, 'views/templates/hook/section_end.tpl');

        

        //$this->html_text .=  $this->display($this->_path, 'views/templates/admin/main.tpl');

        return $this->htmlText;
    }

    /**
        * Display this module as a payment option during the checkout
        *
        * @param array $params
        * @return array|void
        */

    public function hookActionOrderStatusPostUpdate($params)
    {
        $marchentKey = Configuration::get('ZoodPay_MARCHENT_KEY');
        $S_KEY = Configuration::get('ZoodPay_MARCHENT_SECRET_KEY');
        $APIURL = Configuration::get('ZoodPay_API_URL');
        $date = date('Y-m-d\TH:i:s.000');
         $order = new Order($params['id_order']);

        if ($order->current_state == 5) {
            
            $total_paid = (float)Tools::ps_round((float)$order->getOrdersTotalPaid());

            $query = "SELECT `reference` FROM " . _DB_PREFIX_ .
            "orders WHERE `id_order` ='" . $params['id_order'] . "'";
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);

            $trn_query = "SELECT `transaction_id` FROM " . _DB_PREFIX_ .
            "order_payment WHERE `order_reference` ='" . $result . "'";

            $get_trn = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($trn_query);

            $deldata = json_encode(
                array(
                "delivered_at" => $date,
                "final_capture_amount" => $total_paid
                )
            );

            $del_headers = [
            'Accept: application/json',
            'Content-Length: ' . Tools::strlen($deldata),
            'Content-Type: application/json',
            ];

            $delevery_url = $APIURL . 'transactions/' . $get_trn . '/delivery';

            $D_ch = curl_init();
            curl_setopt($D_ch, CURLOPT_POST, 1);
            curl_setopt($D_ch, CURLOPT_URL, $delevery_url);
            curl_setopt($D_ch, CURLOPT_USERPWD, "$marchentKey:$S_KEY");
            curl_setopt($D_ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($D_ch, CURLOPT_POSTFIELDS, $deldata);
            curl_setopt($D_ch, CURLOPT_HEADER, 0);
            curl_setopt($D_ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($D_ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($D_ch, CURLOPT_HTTPHEADER, $del_headers);
            curl_setopt($D_ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($D_ch, CURLOPT_CUSTOMREQUEST, "PUT");
            $del_response = curl_exec($D_ch);
            curl_close($D_ch);
            json_decode($del_response);
        }
    }

    public function hookPaymentOptions($params)
    {
        /*
              * Verify if this module is active
              */

        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return [];
        }

        $cart = $this->context->cart;

        $currency = $this->context->currency;

        $totalAmmount = $cart->getOrderTotal(true, Cart::BOTH);

        $total = $totalAmmount;

        $optionarray = array();

        $_config_status = Tools::getValue('_Zoodpay_config_status_', Configuration::get('_Zoodpay_config_status_'));

        if ($_config_status == 'TRUE') {
            $_config_Array = Tools::getValue('ZOODPAY_CONFIGURATION', Configuration::get('ZOODPAY_CONFIGURATION'));

            $CONFIGURATION = json_decode($_config_Array);

            for ($i = 0; $i < sizeof($CONFIGURATION->configuration); $i++) {
                $min_lim=$CONFIGURATION->configuration[$i]->min_limit;
                if (($total >= $min_lim) && ($total <= $CONFIGURATION->configuration[$i]->max_limit)) {
                    $optionarray[$i]['service_name'] = $this->l($CONFIGURATION->configuration[$i]->service_name);

                    if ($CONFIGURATION->configuration[$i]->description) {
                        $optionarray[$i]['description'] = $this->l($CONFIGURATION->configuration[$i]->description);
                    }
                    $optionarray[$i]['service_code'] = $CONFIGURATION->configuration[$i]->service_code;
                    if (isset($CONFIGURATION->configuration[$i]->instalments)) {
						$installmen=$CONFIGURATION->configuration[$i]->instalments;
                        $optionarray[$i]['instalments'] = $CONFIGURATION->configuration[$i]->instalments;
                        $optionarray[$i]['install_ammount'] = ceil($total / $installmen);
                    }
                }
            }
        } else {
            $config = 'false';
        }
		
		$alloptions=array();
		
		$alloptions=array_merge($alloptions,$optionarray);
		
	
        /**
               * Form action URL. The form data will be sent to the
               * validation controller when the user finishes
               * the order process.
               */
        
        $formAction = $this->context->link->getModuleLink($this->name, 'validation', array(), true);

        /**
               * Assign the url form action to the template var $action
               */

      
        $this->smarty->assign(['action' => $formAction,
        'testarray' => $alloptions, 'currency' => $currency->iso_code]);

        /**
               *  Load form template to be displayed in the checkout step
               */
        
        $paymentForm = $this->fetch('module:zoodpay/views/templates/hook/payment_options.tpl');

        /**
               * Create a PaymentOption object containing the necessary data
               * to display this module in the checkout
               */

        $newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption;

        $newOption->setModuleName($this->displayName)
        ->setCallToActionText($this->displayName)
        ->setAction($formAction)
        ->setForm($paymentForm);

        if ($_config_status == 'TRUE') {
            $payment_options = array(

            $newOption

            );
        }
        if (!empty($optionarray)) {
            return $payment_options;
        } else {
            return [];
        }
    }

    /**
        * Display a message in the paymentReturn hook
        *
        * @param array $params
        * @return string
        */

    public function hookPaymentReturn($params)
    {
        /**
               * Verify if this module is enabled
               */

        if (!$this->active) {
            return;
        }

        return $this->display(__FILE__, 'views/templates/hook/payment_return.tpl');
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);

        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function renderForm()
    {
        $fields_form = array(

        'form' => array(

        'legend' => array(

        'title' => $this->l('ZoodPay Account details'),
        'icon' => 'icon-envelope'

         ),
         'input' => array(

        array(

        'type' => 'text',
        'id' => 'M__K',
        'label' => $this->l('Marchent Key'),
        'name' => 'ZoodPay_MARCHENT_KEY',
        'required' => true

         ),
        array(

        'type' => 'password',
        'id' => 'M_S_K',
        'label' => $this->l('Marchent Secret Key'),
        'name' => 'ZoodPay_MARCHENT_SECRET_KEY',
        'required' => true

         ),
        array(

        'type' => 'password',
        'id' => 'M__S',
        'label' => $this->l('Marchent Salt Key'),
        'name' => 'ZoodPay_MARCHENT_SALT_KEY',
        'required' => true

         ),
        array(

        'type' => 'text',
        'label' => $this->l('API URL'),
        'name' => 'ZoodPay_API_URL',
        'required' => true,
         ),
        array(

        'type' => 'text',
        'label' => $this->l('T&C URL'),
        'name' => 'ZoodPay_TC',
        'required' => true

         ),
        array(

        'type' => 'select',
        'label' => $this->l('Market code'),
        'name' => 'ZoodPay_MARKET_CODE',
        'options' => array(

        'query' => array(

        array(
         'key' => 'KZ',
         'name' => 'KZ'
        ),
        array(
         'key' => 'UZ',
         'name' => 'UZ'
        ),
        array(
         'key' => 'IQ',
         'name' => 'IQ'
        ),
        array(
         'key' => 'JO',
         'name' => 'JO'
        ),
        array(
         'key' => 'KSA',
         'name' => 'KSA'
        ),
        array(
         'key' => 'KW',
         'name' => 'KW'
        ),
        ),
        'id' => 'key',
        'name' => 'name'

        ),
        'required' => true

         ),
        array(

        'type' => 'select',
        'label' => $this->l('Language code'),
        'name' => 'ZoodPay_LANGUAGE_CODE',
        'options' => array(

        'query' => array(

        array(
         'key' => 'en',
         'name' => 'en'
        ),
        array(
         'key' => 'kk',
         'name' => 'kk'
        ),
        array(
         'key' => 'uz',
         'name' => 'uz'
        ),
        array(
         'key' => 'ar',
         'name' => 'ar'
        ),
        array(
         'key' => 'ru',
         'name' => 'ru'
        ),
        ),
        'id' => 'key',
        'name' => 'name'

        ),
        'required' => true

         ),
         ),
         'submit' => array(

        'title' => $this->l('Save'),
        ),
        'buttons' => array(

        array(

        'id' => 'configbutton',
        'title' => $this->l('Get Configuration'),
        'icon' => '',
        'name' => 'get_configration'

        ),
         array(

         'id' => 'healthcheck',
         'title' => $this->l('API Healthcheck'),
         'icon' => '',
         'name' => 'api_health',
        )

        ),
        ),
        );

        $helper = new HelperForm();

        $helper->show_toolbar = false;

        $helper->table = $this->table;

        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));

        $helper->default_form_language = $lang->id;

        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? : 0;

        $this->fields_form = array();

        $helper->id = (int)Tools::getValue('id_carrier');

        $helper->identifier = $this->identifier;

        $helper->submit_action = 'btnSubmit';

        $helper->currentIndex = $this
        ->context
        ->link
        ->getAdminLink('AdminModules', false) . '&configure='
        . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;

        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(

        'fields_value' => $this->getConfigFieldsValues(),
        'languages' => $this
        ->context
        ->controller
        ->getLanguages(),
        'id_language' => $this
        ->context
        ->language
        ->id

        );

        return $helper->generateForm(
            array(
                $fields_form
            )
        );
    }

    public function randomCode()
    {
        $start_letter = str_shuffle('ABCD');

        $number = str_shuffle('0123456789');

        $letter = str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ');

        return Tools::substr(($start_letter), 0, 1) . Tools::substr(($number), 0, 4)
        . '-' . Tools::substr(($letter), 0, 1);
    }

    public function refundProcess()
    {
	
	 if(Tools::getAdminToken('ajaxcheck') == Tools::getValue('token_check')){
        $finalamount = "";
        $final_amount = "";
        $_TRANSTION_ID = Tools::getValue('ZoodPay_TRANSTION_ID', Configuration::get('ZoodPay_TRANSTION_ID'));
        if (Tools::getIsset('refund_action')) {
            $refundaction = Tools::getValue('refund_action');
        }

        if (!$refundaction) {
            echo json_encode(
                array(
                "status" => "fail",
                "message" => $this->l('Select a product to refund')
                )
            );
            return;
        } else {
            foreach ($refundaction as $check) {
                $qq_n = Tools::getValue('amount_product_' . $check);

                if (!is_numeric($qq_n)) {
                    echo json_encode(
                        array(
                         "status" => "fail",
                         "message" => $this->l('Amount is not valid')
                        )
                    );
                    return;
                }

                $final_amount = ($final_amount + $qq_n);
            }

            $get_query = 'SELECT SUM(refund_amount) AS Total FROM `' . _DB_PREFIX_ .
            'zoodpay_refund_order` WHERE id_transaction = "' . $_TRANSTION_ID . '" AND refund_status !=  "Declined"';
            $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($get_query);

            $total_query = 'SELECT `total_paid`  FROM `' . _DB_PREFIX_ .
            'zoodpay_refund_order` WHERE id_transaction = "' . $_TRANSTION_ID . '" ';
            $totalamo = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($total_query);

            if ($rows != '' || $rows != null && $totalamo != '' || $totalamo != null) {
                if ($rows > $totalamo) {
                    echo json_encode(
                        array(
                         "status" => "fail",
                         "message" => $this->l('Total amount is refunded')
                         )
                    );
                    return;
                } elseif (($rows + $final_amount) > $totalamo) {
                    echo json_encode(
                        array(
                        "status" => "fail",
                        "message" => $this->l('Refund amount is grater than paid amount')
                        )
                    );
                    return;
                }
            }
        }

        if (Tools::getIsset($_TRANSTION_ID) && $_TRANSTION_ID == '' || $_TRANSTION_ID == null) {
            return;
        }

        if (version_compare(phpversion(), '7.1', '>=')) {
            ini_set('serialize_precision', -1);
        }

        foreach ($refundaction as $check) {
            $q_n = Tools::getValue('amount_product_' . $check);

            $finalamount = ($finalamount + $q_n);
        }

        $_MARCHENT_KEY = Configuration::get('ZoodPay_MARCHENT_KEY');

        $SECRET_KEY = Configuration::get('ZoodPay_MARCHENT_SECRET_KEY');

        $APIURL = Configuration::get('ZoodPay_API_URL');

        $query = "SELECT `order_reference` FROM " . _DB_PREFIX_ .
        "order_payment WHERE `transaction_id` ='" . $_TRANSTION_ID . "'";

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);

        $cart_query = "SELECT `id_cart` FROM " . _DB_PREFIX_ . "orders WHERE `reference` ='" . $result . "'";

        $cart_ID = Db::getInstance()->getValue($cart_query);

        $order_q = "SELECT `id_order` FROM " . _DB_PREFIX_ . "orders WHERE `reference` ='" . $result . "'";

        $order_id = Db::getInstance()->getValue($order_q);

        $order = new Order($order_id);

        $total_paid = (float)Tools::ps_round((float)$order->getOrdersTotalPaid());

        $date = date('Y-m-d\TH:i:s.000');

        $rstatus = (int)Configuration::get('ZOODPAY_REFUND_STATE');
        $rhold = (int)Configuration::get('ZOODPAY_REFUND_HOLD');
        $refund = json_encode(
            array(

                "merchant_refund_reference" => "" . $cart_ID . "",
                "reason" => $this->l('Refund Request', 'zoodpay'),
                "refund_amount" => (float)$finalamount,
                "transaction_id" => $_TRANSTION_ID,
                "request_id" => $this->randomCode()

            )
        );

        $refund_headers = [

        'Accept: application/json',
        'Content-Length: ' . Tools::strlen($refund),
        'Content-Type: application/json',
        ];

        $refund_url = $APIURL . 'refunds';

        $R_ch = curl_init();

        curl_setopt($R_ch, CURLOPT_POST, 1);

        curl_setopt($R_ch, CURLOPT_URL, $refund_url);

        curl_setopt($R_ch, CURLOPT_USERPWD, "$_MARCHENT_KEY:$SECRET_KEY");

        curl_setopt($R_ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        curl_setopt($R_ch, CURLOPT_POSTFIELDS, $refund);

        curl_setopt($R_ch, CURLOPT_HEADER, 0);

        curl_setopt($R_ch, CURLOPT_SSL_VERIFYHOST, 2);

        curl_setopt($R_ch, CURLOPT_SSL_VERIFYPEER, true);

        curl_setopt($R_ch, CURLOPT_HTTPHEADER, $refund_headers);

        curl_setopt($R_ch, CURLOPT_RETURNTRANSFER, 1);

        $refund_response = curl_exec($R_ch);

        curl_close($R_ch);

        $refund_data = json_decode($refund_response);

        $history = new OrderHistory();

        if ($refund_data->refund_id != '') {

            $query = "INSERT INTO " . _DB_PREFIX_ .
            "zoodpay_refund_order ( `id_transaction`, `order_id`, `cart_id`, `referance`, `refund_id`, `total_paid`,
            `refund_amount`, `refund_date`, `refund_status`)
            VALUES ('" . pSQL($_TRANSTION_ID) . "', '" . (int)$order_id . "', '" . (int)$cart_ID . "', '" . (int)$result . "','" . (int)$refund_data->refund_id . "', '" . $total_paid . "', '" .
            (float)$finalamount . "', '" . $date . "', '" . pSQL($refund_data->refund->status) . "') ";

            Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($query);

            foreach ($refundaction as $check) {
                $ramount = Tools::getValue('amount_product_' . $check);
                $qarefund = Tools::getValue('quantity_product_' . $check);

                $Update_Stat = "UPDATE " . _DB_PREFIX_ .
                "order_detail SET total_refunded_tax_incl = '" . $ramount . "',
                product_quantity_refunded =  '" . (int)$qarefund . "' WHERE `id_order` ='" . (int)$order_id . "'";

                Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($Update_Stat);
            }

            echo json_encode(
                array(
                "status" => "ok",
                "message" => $this->l($refund_data->refund->status)
                )
            );

            $history->changeIdOrderState((int)Configuration::get('ZOODPAY_REFUND_STATE'), (int)($order_id));
            $insert = "INSERT INTO " . _DB_PREFIX_ .
            "order_history (id_employee,id_order,id_order_state,date_add) VALUES ('0','".(int)$order_id."','".pSQL($rstatus)."',NOW())";

            Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($insert);
        } else {
            $history->changeIdOrderState((int)Configuration::get('ZOODPAY_REFUND_HOLD'), (int)($order_id));
            $insert = "INSERT INTO " . _DB_PREFIX_ .
            "order_history (id_employee,id_order,id_order_state,date_add) VALUES ('0','".(int)$order_id."','$rhold',NOW())";

            Db::getInstance(_PS_USE_SQL_SLAVE_)->execute($insert);
            echo json_encode(
                array(
                "status" => "fail",
                "message" => $this->l($refund_data->message)
                )
            );
        }
		
	}else{
		
		     $_MSG = array(
                    'status' => 'false',
                    'error' => $this->l('Token is not valid')
                    );
            echo json_encode($_MSG);

        exit;
        }
	
    }

    public function ajaxSaveConifgration()
    {

       if(Tools::getAdminToken('ajaxcheck') == Tools::getValue('token_check')){

       
        $_MARCHENT_KEY = Tools::getValue('ZoodPay_MARCHENT_KEY', Configuration::get('ZoodPay_MARCHENT_KEY'));

        $SECRETKEY = Tools::getValue('ZoodPay_MARCHENT_SECRET_KEY', Configuration::get('ZoodPay_MARCHENT_SECRET_KEY'));

        $ZoodPay_MARKET_CODE = Tools::getValue('ZoodPay_MARKET_CODE', Configuration::get('ZoodPay_MARKET_CODE'));

        $API_URL = Tools::getValue('ZoodPay_API_URL', Configuration::get('ZoodPay_API_URL'));

        if ($ZoodPay_MARKET_CODE != '' && $ZoodPay_MARKET_CODE != 'null') {
            $payload = json_encode(
                array(

                "market_code" => $ZoodPay_MARKET_CODE

                )
            );

            $headers = [

            'Accept: application/json',
            'Content-Length: ' . Tools::strlen($payload),
            'Content-Type: application/json',
            ];

            $config_url = $API_URL . 'configuration';

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $config_url);

            curl_setopt($ch, CURLOPT_POST, 1);

            curl_setopt($ch, CURLOPT_USERPWD, "$_MARCHENT_KEY:$SECRETKEY");

            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

            curl_setopt($ch, CURLOPT_HEADER, 0);

            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $return = curl_exec($ch);

            curl_close($ch);

            $result = json_decode($return, true);

            Configuration::deleteByName('_Zoodpay_config_status_');

            Configuration::deleteByName('ZOODPAY_CONFIGURATION');

            if ($result['configuration']) {
                Configuration::updateValue('ZOODPAY_CONFIGURATION', json_encode($result, JSON_UNESCAPED_UNICODE));

                Configuration::updateValue('_Zoodpay_config_status_', 'TRUE');

                Configuration::updateValue('_Zoodpay_Market_code_', $ZoodPay_MARKET_CODE);

                $_MSG = array(
                'status' => 'true',
                'success' => $this->l('Save Configuration')
                );
            } else {
                Configuration::updateValue('_Zoodpay_config_status_', 'FALSE');

                $_MSG = array(
                'status' => 'hold',
                'warning' => $this->l($result['message'])
                );
            }
        } else {
            $_MSG = array(
            'status' => 'false',
            'error' => $this->l('Save detail before get configuration')
            );
        }

        echo json_encode($_MSG);

        exit;
		}else{
		
		     $_MSG = array(
                    'status' => 'false',
                    'error' => $this->l('Token is not valid')
                    );
            echo json_encode($_MSG);

        exit;
        }
		
		
    }

    public function ajaxGetApiResponse()
    {
		if(Tools::getAdminToken('ajaxcheck') == Tools::getValue('token_check')){
		
        $_MARCHENT_KEY = Tools::getValue('ZoodPay_MARCHENT_KEY', Configuration::get('ZoodPay_MARCHENT_KEY'));

        $SECRET_KEY = Tools::getValue('ZoodPay_MARCHENT_SECRET_KEY', Configuration::get('ZoodPay_MARCHENT_SECRET_KEY'));

        if ($_MARCHENT_KEY != '' && $SECRET_KEY != '') {
            $config_url = 'https://sandbox-api.zoodpay.com/healthcheck';

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $config_url);

            curl_setopt($ch, CURLOPT_HEADER, 0);

            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $return = curl_exec($ch);

            curl_close($ch);

            $result = json_decode($return, true);

            if ($result == "OK 0.0") {
                $_MSG = array(
                'status' => 'true',
                'success' => $this->l('OK')
                );
            } elseif ($result != "OK 0.0") {
                $_MSG = array(
                'status' => 'hold',
                'warning' => $this->l($return)
                );
            }
        } else {
            $_MSG = array(
            'status' => 'false',
            'error' => $this->l('Save detail before get configuration')
            );
        }

        echo json_encode($_MSG);

        exit;
		
		}else{
		
		     $_MSG = array(
                    'status' => 'false',
                    'error' => $this->l('Token is not valid')
                    );
            echo json_encode($_MSG);

        exit;
        }
    }

    public function getConfigFieldsValues()
    {
        return array(

        'ZoodPay_MARCHENT_KEY' => Tools::getValue('ZoodPay_MARCHENT_KEY', Configuration::get('ZoodPay_MARCHENT_KEY')),
        'ZoodPay_MARCHENT_SECRET_KEY'
        => Tools::getValue('ZoodPay_MARCHENT_SECRET_KEY', Configuration::get('ZoodPay_MARCHENT_SECRET_KEY')),
        'ZoodPay_MARCHENT_SALT_KEY'
        => Tools::getValue('ZoodPay_MARCHENT_SALT_KEY', Configuration::get('ZoodPay_MARCHENT_SALT_KEY')),
        'ZoodPay_MARKET_CODE' => Tools::getValue('ZoodPay_MARKET_CODE', Configuration::get('ZoodPay_MARKET_CODE')),
        'ZoodPay_LANGUAGE_CODE'
        => Tools::getValue('ZoodPay_LANGUAGE_CODE', Configuration::get('ZoodPay_LANGUAGE_CODE')),
        'ZoodPay_API_URL' => Tools::getValue('ZoodPay_API_URL', Configuration::get('ZoodPay_API_URL')),
        'ZoodPay_TC' => Tools::getValue('ZoodPay_TC', Configuration::get('ZoodPay_TC')),
        );
    }

    public function getRefundProduct()
    {   

		 if(Tools::getAdminToken('ajaxcheck') == Tools::getValue('token_check')){

        $_TRANSTION_ID = Tools::getValue('ThisVal', Configuration::get('ThisVal'));

        if (Tools::getIsset($_TRANSTION_ID) && $_TRANSTION_ID == '') {
            return;
        }

        $query = "SELECT `order_reference` FROM " . _DB_PREFIX_ .
        "order_payment WHERE `transaction_id` ='" . pSQL($_TRANSTION_ID) . "'";

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);

        if ($result == '' || $result == null) {
            return;
        }

        $getorder = "SELECT `id_order` FROM " . _DB_PREFIX_ .
        "orders WHERE `reference` ='" . $result . "' AND current_state != '8' ";

        $id_order = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($getorder);

        if ($id_order == '' || $id_order == null) {
            return;
        }

        $order = new OrderDetail;

        $products = $order->getList($id_order);

        $this->context->smarty->assign(array(
            'ajax_data' => $products
                )
            );

            
              
    die( $this->context->smarty->fetch(_PS_MODULE_DIR_.'zoodpay/views/templates/hook/ajax/table_data.tpl'));
		}else{
		
		     $_MSG = array(
                    'status' => 'false',
                    'error' => $this->l('Token is not valid')
                    );
            echo json_encode($_MSG);

        exit;
        }
    
    }

    public static function getIsoCodeById($code)
    {
        $sql = 'SELECT `iso_code` FROM `' . _DB_PREFIX_ . 'country` WHERE `id_country` = \'' . pSQL($code) . '\'';

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);

        return $result['iso_code'];
    }
}
