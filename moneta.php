<?php
defined('_JEXEC') or die('Direct Access to ' . basename(__FILE__) . ' is not allowed.');

/**
 *
 * @author Moneta
 * @package VirtueMart
 * @subpackage payment
 * @copyright Copyright (C) 2015 - 2016 VirtueMart - All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 *          VirtueMart is free software. This version may have been modified pursuant
 *          to the GNU General Public License, and as distributed it includes or
 *          is derivative of works licensed under the GNU General Public License or
 *          other free or open source software licenses.
 *          See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *         
 */
if (! class_exists('vmPSPlugin'))
    require (JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');

include_once 'sdk/payments.php';
    
use Payments\Payments;
use Payments\Config;
    
class plgVmPaymentMoneta extends vmPSPlugin
{

    public static $_this = false;
    
    
    /**
     * begin - switching constants which will be used to hide or show the UI controls 
     * 
     ***/
    const ST_SHOW_IFRAME = "1";
    const ST_SHOW_REDIRECT = "1";
    const ST_SHOW_HOSTEDPAY = "1";
    
    const ST_SHOW_SANDBOX_FIELDS = "0";
    const ST_SHOW_LIVE_FIELDS = "0";
    
    const ST_EVO_CASHIER_URL_SANDBOX = "https://cashierui-apiuat.test.monetaplatebnisluzby.cz/ui/cashier";
    const ST_EVO_JAVASCRIPT_URL_SANDBOX = "https://cashierui-apiuat.test.monetaplatebnisluzby.cz/js/api.js";
    const ST_EVO_TOKEN_URL_SANDBOX = "https://apiuat.test.monetaplatebnisluzby.cz/token";
    const ST_EVO_PAYMENT_URL_SANDBOX = "https://apiuat.test.monetaplatebnisluzby.cz/payments";
   
    const ST_EVO_CASHIER_URL_LIVE = "https://cashierui-api.monetaplatebnisluzby.cz/ui/cashier";
    const ST_EVO_JAVASCRIPT_URL_LIVE = "https://cashierui-api.monetaplatebnisluzby.cz/js/api.js";
    const ST_EVO_TOKEN_URL_LIVE = "https://api.monetaplatebnisluzby.cz/token";
    const ST_EVO_PAYMENT_URL_LIVE = "https://api.monetaplatebnisluzby.cz/payments";
    //DEFAULT TO IFRAME
    // 1 - IFRAME
    // 0 - REDIRECT
    // 2 - HOSTED PAY
    const ST_EVO_PAYMENT_TYPE = "2";
    /** end**/


    

    function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);

        $this->_loggable = true;
        $this->tableFields = array_keys($this->getTableSQLFields());
        $this->_tablepkey = 'id';
        $this->_tableId = 'id';

        $varsToPush = array(
            'intelligentpayment_merchant_id' => array(
                '',
                'char'
            ),
            'intelligentpayment_api_password' => array(
                '',
                'char'
            ),
            'intelligentpayment_brand_id' => array(
                '',
                'char'
            ),
            'payment_logos' => array(
                '',
                'char'
            ),
            'sandbox' => array(
                0,
                'char'
            ),
            'mode' => array(
                0,
                'char'
            ),
            'debug' => array(
                0,
                'int'
            ),
            'xtype' => array(
                '',
                'char'
            ),
            'status_pending' => array(
                '',
                'char'
            ),
            'status_success' => array(
                '',
                'char'
            ),
            'status_canceled' => array(
                '',
                'char'
            ),
            'status_refunded' => array(
                '',
                'char'
            ),
            'ipg_token_testUrl' => array(
                '',
                'char'
            ),
            'ipg_token_liveUrl' => array(
                '',
                'char'
            ),
            'ipg_cashier_testUrl' => array(
                '',
                'char'
            ),
            'ipg_cashier_liveUrl' => array(
                '',
                'char'
            ),
            'ipg_js_testUrl' => array(
                '',
                'char'
            ),
            'ipg_js_liveUrl' => array(
                '',
                'char'
            ),
            'ipg_payment_testUrl' => array(
                '',
                'char'
            ),
            'ipg_payment_liveUrl' => array(
                '',
                'char'
            ),
            'tax_id' => array(
                0,
                'int'
            ),
            'ipg_merchat_countries' => array(
                '',
                'char'
            )
        );

        $this->setConfigParameterable($this->_configTableFieldName, $varsToPush);
    }

    function _getIntelligentpaymentDetails($method)
    {
        $tokenUrl = null;
        $jsUrl = null;
        $cashierUrl = null;
        $paymentUrl = null;
        
        if($method->sandbox) {
            if(! self::ST_SHOW_SANDBOX_FIELDS ) {
                $tokenUrl = self::ST_EVO_TOKEN_URL_SANDBOX;
                $jsUrl = self::ST_EVO_JAVASCRIPT_URL_SANDBOX;
                $cashierUrl = self::ST_EVO_CASHIER_URL_SANDBOX;
                $paymentUrl = self::ST_EVO_PAYMENT_URL_SANDBOX;
            }else {
                $tokenUrl = $method->ipg_token_testUrl;
                $jsUrl = $method->ipg_js_testUrl;
                $cashierUrl = $method->ipg_cashier_testUrl;
                $paymentUrl =  $method->ipg_payment_testUrl;
            }
        }else {
            if(! self::ST_SHOW_LIVE_FIELDS ) {
                $tokenUrl = self::ST_EVO_TOKEN_URL_LIVE;
                $jsUrl = self::ST_EVO_JAVASCRIPT_URL_LIVE;
                $cashierUrl = self::ST_EVO_CASHIER_URL_LIVE ;
                $paymentUrl = self::ST_EVO_PAYMENT_URL_LIVE;
            }else {
                $tokenUrl = $method->ipg_token_liveUrl;
                $jsUrl = $method->ipg_js_liveUrl;
                $cashierUrl = $method->ipg_cashier_liveUrl;
                $paymentUrl =  $method->ipg_payment_liveUrl;
            }
        }
		
		$paymentMode = $method->mode;
		if( !isset( $paymentMode) || $paymentMode == null ) {
			$paymentMode = self::ST_EVO_PAYMENT_TYPE;
		}
        
        $intelligentpaymentDetails = array(
            'merchant_id' => $method->intelligentpayment_merchant_id,
            'api_password' => $method->intelligentpayment_api_password,
            'brand_id' => $method->intelligentpayment_brand_id,
            'mode' => $paymentMode,
            'tokenUrl' => $tokenUrl,
            'jsUrl' => $jsUrl,
            'cashierUrl' => $cashierUrl,
            'paymentUrl' => $paymentUrl
        );

        return $intelligentpaymentDetails;
    }

    public function getVmPluginCreateTableSQL()
    {
        return $this->createTableSQL('Payment Intelligent Payment Table');
    }

    function getTableSQLFields()
    {
        $SQLfields = array(
            'id' => 'int(11) unsigned NOT NULL AUTO_INCREMENT ',
            'virtuemart_order_id' => 'int(11) UNSIGNED DEFAULT NULL',
            'order_number' => 'char(32) DEFAULT NULL',
            'virtuemart_paymentmethod_id' => 'mediumint(1) UNSIGNED DEFAULT NULL',
            'payment_name' => 'char(255) NOT NULL DEFAULT \'\' ',
            'payment_order_total' => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\' ',
            'payment_currency' => 'char(3) ',
            'cost_per_transaction' => 'decimal(10,2) DEFAULT NULL ',
            'cost_percent_total' => 'decimal(10,2) DEFAULT NULL ',
            'tax_id' => 'smallint(1) DEFAULT NULL',
            'xtype' => 'varchar(50) DEFAULT NULL ',
            'xmode' => 'varchar(50) DEFAULT NULL ',
            'intelligentpayments_response_raw' => 'text  ',
            'intelligentpayments_response_txId' => 'varchar(50) DEFAULT NULL ',
            'intelligentpayments_response_merchantTxId' => 'varchar(50) DEFAULT NULL ',
            'intelligentpayments_response_refundId' => 'varchar(50) DEFAULT NULL ',
            'intelligentpayments_response_acquirerTxId' => 'varchar(255) DEFAULT NULL ',
            'intelligentpayments_response_resultId' => 'varchar(255) DEFAULT NULL ',
            'intelligentpayments_response_pan' => 'varchar(255) DEFAULT NULL ',
            'intelligentpayments_response_token' => 'varchar(255) DEFAULT NULL ',
            'intelligentpayments_response_status' => 'varchar(255) DEFAULT NULL ',
            'intelligentpayments_response_amount' => 'varchar(255) DEFAULT NULL ',
            'intelligentpayments_response_currency' => 'varchar(255) DEFAULT NULL ',
            'intelligentpayments_response_result' => 'varchar(255) DEFAULT NULL '
        );
        return $SQLfields;
    }

    function plgVmConfirmedOrder($cart, $order)
    {
       
        if (! ($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
            return null; // Another method was selected, do nothing
        }
        if (! $this->selectedThisElement($method->payment_element)) {
            return false;
        }
        $session = JFactory::getSession();
        $return_context = $session->getId();
        $this->_debug = $method->debug;

        if (! class_exists('VirtueMartModelCurrency'))
            require (VMPATH_ADMIN . DS . 'models' . DS . 'currency.php');

        if (! class_exists('TableVendors'))
            require (VMPATH_ADMIN . DS . 'table' . DS . 'vendors.php');

        $this->getPaymentCurrency($method);
        $q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $method->payment_currency . '" ';
        $db = JFactory::getDBO();
        $db->setQuery($q);
        $currency_code_3 = $db->loadResult();

        $paymentCurrency = CurrencyDisplay::getInstance($method->payment_currency);
        $totalInPaymentCurrency = round($paymentCurrency->convertCurrencyTo($method->payment_currency, $order['details']['BT']->order_total, false), 2);

        $intelligentpaymentDetails = $this->_getIntelligentpaymentDetails($method);

        if (empty($intelligentpaymentDetails['merchant_id'])) {
            vmInfo(vmText::_('VMPAYMENT_INTELLIGENTPAYMENT_MERCHANT_ID_NOT_SET'));
            return false;
        }

       // get the merchant account country 
        $midCountry = null;
        if (is_array($method->ipg_merchat_countries)) {
            $midCountry = $method->ipg_merchat_countries[0];
        } else {
            $midCountry = $method->ipg_merchat_countries;
        }
        $midCountry = ShopFunctions::getCountryByID($midCountry, 'country_2_code');

        $lang = JFactory::getLanguage();
        $tag = $lang->getTag ();
        $langArray = explode ("-", $tag);
        $languageCode = strtolower ($langArray[0]);

        $notificationUrl = JRoute::_(JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived&pm=' . $order['details']['BT']->virtuemart_paymentmethod_id . "&o_id={$order['details']['BT']->order_number}");
        $landingUrl = JRoute::_(JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived&landing=1&pm=' . $order['details']['BT']->virtuemart_paymentmethod_id . "&o_id={$order['details']['BT']->order_number}");

        $targetDomain = str_replace(JURI::root(true)."/", '', JURI::root());
       
        $address = ((isset($order['details']['BT'])) ? $order['details']['BT'] : $order['details']['ST']);
        
        $paymentMode = $intelligentpaymentDetails['mode'];

        Config::$MerchantId = $intelligentpaymentDetails['merchant_id'];
        Config::$Password = $intelligentpaymentDetails['api_password'];
        
        if($method->sandbox) {
            Config::buildEvoEnv4Test(
                $intelligentpaymentDetails['cashierUrl'],
                $intelligentpaymentDetails['jsUrl'],
                $intelligentpaymentDetails['tokenUrl'],
                $intelligentpaymentDetails['paymentUrl']
                );
        } else {
            Config::buildEvoEnv4Prod(
                $intelligentpaymentDetails['cashierUrl'],
                $intelligentpaymentDetails['jsUrl'],
                $intelligentpaymentDetails['tokenUrl'],
                $intelligentpaymentDetails['paymentUrl']
                );
        }
        
        $purchase = null;
        $transactionType =  $method->xtype;
        if($transactionType == 'AUTH_CAPTURE') {
            $purchase = (new Payments() )->purchase();
        } else {
            $purchase = (new Payments() )->auth();
        }
    
        $purchase -> merchantNotificationUrl($notificationUrl);

        /**
         * payment mode
         *
         * 0 - redirect
         * 1 - iframe
         * 2 - hosted payment
         */
        $purchase->allowOriginUrl($targetDomain);
        $paymentSolutionId = null;
        if($paymentMode == '0' ) {//redirect 
            $purchase -> merchantLandingPageUrl($landingUrl);
            // default to credit card
            $paymentSolutionId = "500";
        }else if($paymentMode == '2') {//hosted payment
            $purchase -> merchantLandingPageUrl($landingUrl);
            // no specific payment solution specified
            $paymentSolutionId = "";
        } else { // iframe
            // default to credit card
            $paymentSolutionId = "500";
        }
        $purchase -> paymentSolutionId($paymentSolutionId);
        $brandId = trim($intelligentpaymentDetails['brand_id']);
        if($brandId != ''){
            $purchase -> brandId($brandId);
        }


        $purchase->channel(Payments::CHANNEL_ECOM)->
        userDevice(Payments::USER_DEVICE_DESKTOP)->
        merchantTxId($order['details']['BT']->order_number)->
        language($languageCode)->
        amount($totalInPaymentCurrency)->
        timestamp(strtotime(date('Y-m-d H:i:s')) * 1000)->
        country($midCountry)->
        currency($currency_code_3)->
        customerAddressCountry( ShopFunctions::getCountryByID($address->virtuemart_country_id, 'country_2_code')) ->
        customerAddressCity( $address->city) ->
        customerAddressStreet($address->address_1) ->
        customerAddressPostalCode($address->zip) ->
        customerFirstName($address->first_name) ->
        customerLastName($address->last_name)->
        customerEmail($order['details']['BT']->email) ->
        customerPhone($address->phone_1) ->
        s_text1($return_context) ->
        customerAddressHouseName($address->address_1);
 
		$this->logInfo('plgVmConfirmedOrder, parameters for get token action - '. print_r( $purchase , true), 'info');
		
        $result = $purchase->token();
		
        $this->logInfo('plgVmConfirmedOrder, result for get token action - '. print_r( $result , true), 'info');

        if ($result->result !== 'success') {
            $this->logInfo('plgVmConfirmedOrder, Error getting payment tokens from gateway - ' . $result->errors, 'error');
            vmError(vmText::_('VMPAYMENT_INTELLIGENTPAYMENT_CANNOT_GET_TOKENS'));
            return false;
        }

        // Prepare data that should be stored in the database
        $dbValues = array();
        $dbValues['order_number'] = $order['details']['BT']->order_number;
        $dbValues['payment_name'] = $method->payment_name;
        $dbValues['virtuemart_paymentmethod_id'] = $cart->virtuemart_paymentmethod_id;
        $dbValues['intelligentpayment_custom'] = $return_context;
        $dbValues['cost_per_transaction'] = $method->cost_per_transaction;
        $dbValues['cost_percent_total'] = $method->cost_percent_total;
        $dbValues['payment_currency'] = $method->payment_currency;
        $dbValues['payment_order_total'] = $totalInPaymentCurrency;
        $dbValues['tax_id'] = $method->tax_id;
        $dbValues['xtype'] = $method->xtype;
        $dbValues['xmode'] =  $paymentMode;
        
        $this->storePSPluginInternalData($dbValues);

        $cartUrl = JRoute::_('index.php?option=com_virtuemart&view=cart&Itemid=' . vRequest::getInt('Itemid') . '&lang=' . vRequest::getCmd('lang', ''), false);
        $link = JRoute::_("index.php?option=com_virtuemart&view=orders&layout=details&order_number=" . $order['details']['BT']->order_number . "&order_pass=" . $order['details']['BT']->order_pass, false);

		$sessionToken = $result->token();  
        $params = array();
        $params['sessionToken'] = $sessionToken;
        $params['cashierUrl'] = $intelligentpaymentDetails["cashierUrl"];
        $params['jsUrl'] = $intelligentpaymentDetails["jsUrl"];
        $params['merchantId'] = $intelligentpaymentDetails['merchant_id'];
        $params['paymentSolutionId'] = $paymentSolutionId;
        $params['redirectUrl'] = $landingUrl;
        
        $tmplName = null;
        if($paymentMode == '0' ) { 
            $params['integrationMode'] =  'standalone';
            $tmplName = 'redirect';
        } else if($paymentMode == '2'){
            $params['integrationMode'] =  'hostedPayPage';
            $tmplName = 'redirect';
        } else {
            //iframe
            $tmplName = 'iframe';
        }

        $html = $this->renderByLayout($tmplName, $params);
        
        vRequest::setVar('html', $html);

        return true;
    }

    function plgVmgetPaymentCurrency($virtuemart_paymentmethod_id, $type = false)
    {
        if (! ($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
            return null; // Another method was selected, do nothing
        }
        if (! $this->selectedThisElement($method->payment_element)) {
            return false;
        }
        $this->getPaymentCurrency($method);
        $paymentCurrencyId = $method->payment_currency;
        $currency_model = VmModel::getModel('currency');
        $displayCurrency = $currency_model->getCurrency($paymentCurrencyId);
        if ($type == "code") {
            return $currency = $displayCurrency->currency_code_3;
        } else {
            return $currency = $displayCurrency->currency_symbol;
        }
    }


    function plgVmOnPaymentResponseReceived(&$html)
    {
        $virtuemart_paymentmethod_id = vRequest::getInt('pm', 0);
        $order_number = vRequest::getVar('o_id', 0); // ?

        if (! ($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
            return null; // Another method was selected, do nothing
        }
        if (! $this->selectedThisElement($method->payment_element)) {
            return false;
        }
        if (! class_exists('VirtueMartCart'))
            require (JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
        if (! class_exists('shopFunctionsF'))
            require (JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
        
		$this->_debug = $method->debug;
		
        $this->logInfo('plgVmOnPaymentResponseReceived => start ', 'info');
        
        // setup response html
        VmConfig::loadJLang('com_virtuemart');
        $intelligentpayment_data = vRequest::getRequest();
        
        $this->logInfo('plgVmOnPaymentResponseReceived => request details are: '. print_r( $intelligentpayment_data , true), 'info');

        $order_number = $intelligentpayment_data['o_id'];
        $virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);
        if (! $virtuemart_order_id) {
            vmError('Intelligent payment data received, but cannot find the specified order [ '. $order_number .']');
			$this->logInfo('plgVmOnPaymentResponseReceived => end with order not found: '. $order_number , 'info');
            return;
        }
        
        $modelOrder = VmModel::getModel('orders');
		
        /**
         * landing request
         */ 
        if (isset($intelligentpayment_data['landing']) && $intelligentpayment_data['landing'] == '1') {
            $this->logInfo('plgVmOnPaymentResponseReceived => end with landing. ' , 'info');
            if ($intelligentpayment_data['result'] === 'failure') {
                JError::raiseWarning(100, 'Intelligent Payment Failed ');
            }else {
                $cart = VirtueMartCart::getCart();
                $cart->emptyCart();
            }

            $order = $modelOrder->getOrder($virtuemart_order_id);
            // $cart = VirtueMartCart::getCart();
            // $cart->emptyCart();
            $link = JRoute::_("index.php?option=com_virtuemart&view=orders&layout=details&order_number=" . $order['details']['BT']->order_number . "&order_pass=" . $order['details']['BT']->order_pass, false);
            header("location:" . $link);
            exit();
        }

        /**
         * notification request
         */ 
        $order = $modelOrder->getOrder($virtuemart_order_id);
        $nb_history = count($order['history']);
        $payment_status = $intelligentpayment_data['status'];
		
		$this->logInfo('plgVmOnPaymentResponseReceived => notify request, payment status is ['.$payment_status.']', 'info');
        
        if ($payment_status === 'CAPTURED' || $payment_status === 'SET_FOR_CAPTURE' || $payment_status === 'SUCCESS') {
			
			$this->logInfo('plgVmOnPaymentResponseReceived => success.', 'info');
  
            $order['customer_notified'] = 1;
            $order['order_status'] = $method->status_success;
            $order['comments'] = vmText::sprintf('VMPAYMENT_INTELLIGENTPAYMENT_PAYMENT_STATUS_CONFIRMED', $order_number);

            if ($order['history'][$nb_history - 1]->order_status_code != $order['order_status']) {
                $this->_storeIntelligentpaymentInternalData($method, $intelligentpayment_data, $virtuemart_order_id);
                $this->logInfo('plgVmOnPaymentResponseReceived, sentOrderConfirmedEmail ' . $order_number, 'message');
                $order['virtuemart_order_id'] = $virtuemart_order_id;
                $order['comments'] = vmText::sprintf('VMPAYMENT_INTELLIGENTPAYMENT_EMAIL_SENT');
                $modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, true);
            }
        
        } else if($payment_status === 'STARTED' || $payment_status === 'NOT_SET_FOR_CAPTURE' || $payment_status === 'WAITING_RESPONSE' || $payment_status === 'INCOMPLETE') {
            //pending status
			$this->logInfo('plgVmOnPaymentResponseReceived => pending.', 'info');
  
            $this->_storeIntelligentpaymentInternalData($method, $intelligentpayment_data, $virtuemart_order_id);
            
        }else if ($payment_status === 'SET_FOR_REFUND' || $payment_status === 'COMPLETED_REFUND') {
			$this->logInfo('plgVmOnPaymentResponseReceived => refund.', 'info');
			
            $intelligentpayment_data['refundId'] = $intelligentpayment_data['merchantTxId'];
            $intelligentpayment_data['merchantTxId'] = $order_number;

            $this->_storeIntelligentpaymentInternalData($method, $intelligentpayment_data, $virtuemart_order_id);
            $this->logInfo('plgVmOnPaymentResponseReceived, sentOrderConfirmedEmail ' . $order_number, 'message');
            $order['virtuemart_order_id'] = $virtuemart_order_id;
            if ($intelligentpayment_data['status'] === 'SET_FOR_REFUND') {
                $order['comments'] = vmText::sprintf('VMPAYMENT_INTELLIGENTPAYMENT_AMOUNT_SET_FOR_REFUND', $intelligentpayment_data['refundId']);
            } else if ($intelligentpayment_data['status'] === 'COMPLETED_REFUND') {
                $order['comments'] = vmText::sprintf('VMPAYMENT_INTELLIGENTPAYMENT_COMPLETED_REFUND', $intelligentpayment_data['refundId']);
            }
            $modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, true);
        } else {
            $this->logInfo('plgVmOnPaymentResponseReceived => other.', 'info');
			
            $order['customer_notified'] = 0;
            $order['order_status'] = $method->status_canceled;

            if ($order['history'][$nb_history - 1]->order_status_code != $order['order_status']) {
                $this->_storeIntelligentpaymentInternalData($method, $intelligentpayment_data, $virtuemart_order_id);
                $this->logInfo('plgVmOnPaymentResponseReceived, sentOrderConfirmedEmail ' . $order_number, 'message');
                $order['virtuemart_order_id'] = $virtuemart_order_id;
                $order['comments'] = vmText::sprintf('Intelligent payment status: ' . $intelligentpayment_data['status'] . ', Error : ' . $intelligentpayment_data['errorMessage'], $order_number);
                $modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, true);
            }
        }
		
		$this->logInfo('plgVmOnPaymentResponseReceived => end.', 'info');
		
        return true;
    }

    function plgVmOnUserPaymentCancel()
    {
        if (! class_exists('VirtueMartModelOrders'))
            require (JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');

        $order_number = vRequest::getVar('on');
        if (! $order_number)
            return false;
        $db = JFactory::getDBO();
        $query = 'SELECT ' . $this->_tablename . '.`virtuemart_order_id` FROM ' . $this->_tablename . " WHERE  `order_number`= '" . $order_number . "'";

        $db->setQuery($query);
        $virtuemart_order_id = $db->loadResult();

        if (! $virtuemart_order_id) {
            return null;
        }
        $this->handlePaymentUserCancel($virtuemart_order_id);

        return true;
    }

    function _storeIntelligentpaymentInternalData($method, $intelligentpayment_data, $virtuemart_order_id)
    {

        $response_fields = array();
        $payments_internal_list = $this->_getIPGInternalData($virtuemart_order_id);
        
        if (!$payments_internal_list || count($payments_internal_list) == 0 ) {
            vmError(vmText::sprintf('VMPAYMENT_INTELLIGENTPAYMENT_ERROR_NO_DATA', $order->virtuemart_order_id));
            return false;
        }
        $response_fields = (array)$payments_internal_list[0];
        
        // get all know columns of the table
        $db = JFactory::getDBO();
        $query = 'SHOW COLUMNS FROM `' . $this->_tablename . '` ';
        $db->setQuery($query);
        $columns = $db->loadColumn(0);
        $post_msg = '';

        foreach ($intelligentpayment_data as $key => $value) {
            $post_msg .= $key . "=" . $value . "<br />";
            $table_key = 'intelligentpayments_response_' . $key;
            if (in_array($table_key, $columns)) {
                $response_fields[$table_key] = $value;
            }
        }

//         $response_fields['payment_name'] = $method -> payment_name;
        $response_fields['intelligentpayments_response_raw'] = $post_msg;
//         $return_context = $intelligentpayment_data['custom'];
//         $response_fields['order_number'] = $intelligentpayment_data['o_id'];
//         $response_fields['virtuemart_order_id'] = $virtuemart_order_id;
//         $response_fields['virtuemart_paymentmethod_id'] = $intelligentpayment_data['pm'];

        $this->storePSPluginInternalData($response_fields, 'virtuemart_order_id', true);
    }

    function plgVmOnShowOrderBEPayment($virtuemart_order_id, $payment_method_id)
    {
        if (! $this->selectedThisByMethodId($payment_method_id)) {
            return null; // Another method was selected, do nothing
        }

        if (! ($paymentTable = $this->getDataByOrderId($virtuemart_order_id))) {
            return NULL;
        }
        VmConfig::loadJLang('com_virtuemart');
        $html = '<table class="adminlist table">' . "\n";
        $html .= $this->getHtmlHeaderBE();
        $html .= $this->getHtmlRowBE('COM_VIRTUEMART_PAYMENT_NAME', $paymentTable->payment_name);
        $html .= $this->getHtmlRowBE('INTELLIGENTPAYMENT_ORDER_NUMBER', $paymentTable->order_number);
        $html .= '</table>' . "\n";
        return $html;

        return $html;
    }
    
    function getCosts(VirtueMartCart $cart, $method, $cart_prices)
    {
        if (preg_match('/%$/', $method->cost_percent_total)) {
            $cost_percent_total = substr($method->cost_percent_total, 0, - 1);
        } else {
            $cost_percent_total = $method->cost_percent_total;
        }
        return ($method->cost_per_transaction + ($cart_prices['salesPrice'] * $cost_percent_total * 0.01));
    }

    protected function checkConditions($cart, $method, $cart_prices)
    {
        $address = (($cart->ST == 0) ? $cart->BT : $cart->ST);

        $amount = $cart_prices['salesPrice'];
        $amount_cond = ($amount >= $method->min_amount and $amount <= $method->max_amount or ($method->min_amount <= $amount and ($method->max_amount == 0)));

        $countries = array();
        if (! empty($method->countries)) {
            if (! is_array($method->countries)) {
                $countries[0] = $method->countries;
            } else {
                $countries = $method->countries;
            }
        }

        if (! is_array($address)) {
            $address = array();
            $address['virtuemart_country_id'] = 0;
        }

        if (! isset($address['virtuemart_country_id']))
            $address['virtuemart_country_id'] = 0;
        if (in_array($address['virtuemart_country_id'], $countries) || count($countries) == 0) {
            if ($amount_cond) {
                return true;
            }
        }

        return false;
    }
    
    function plgVmOnStoreInstallPaymentPluginTable($jplugin_id)
    {
        return $this->onStoreInstallPluginTable($jplugin_id);
    }

    public function plgVmOnSelectCheckPayment(VirtueMartCart $cart)
    {
        return $this->OnSelectCheck($cart);
    }

    public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn)
    {
        return $this->displayListFE($cart, $selected, $htmlIn);
    }

    public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name)
    {
        return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
    }

    function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array())
    {
        return $this->onCheckAutomaticSelected($cart, $cart_prices);
    }

    public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name)
    {
        $this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
    }

    function plgVmonShowOrderPrintPayment($order_number, $method_id)
    {
        return $this->onShowOrderPrint($order_number, $method_id);
    }

    function plgVmDeclarePluginParamsPayment($name, $id, &$data)
    {
        return $this->declarePluginParams('payment', $name, $id, $data);
    }

    function plgVmDeclarePluginParamsPaymentVM3(&$data)
    {
        return $this->declarePluginParams('payment', $data);
    }

    function plgVmSetOnTablePluginParamsPayment($name, $id, &$table)
    {
        return $this->setOnTablePluginParams($name, $id, $table);
    }

    function _getIPGInternalData($virtuemart_order_id, $order_number = '')
    {
        $db = JFactory::getDBO();
        $q = 'SELECT * FROM `' . $this->_tablename . '` WHERE ';
        if ($order_number) {
            $q .= " `order_number` = '" . $order_number . "'";
        } else {
            $q .= ' `virtuemart_order_id` = ' . $virtuemart_order_id;
        }

        $db->setQuery($q);
        if (! ($payments = $db->loadObjectList())) {
            return '';
        }
        return $payments;
    }

    function plgVmOnUpdateOrderPayment(&$order, $old_order_status)
    {
        if (! $this->selectedThisByMethodId($order->virtuemart_paymentmethod_id)) {
            return NULL; // Another method was selected, do nothing
        }

        if (! ($method = $this->getVmPluginMethod($order->virtuemart_paymentmethod_id))) {
            return NULL; // Another method was selected, do nothing
        }
        
		$this->_debug = $method->debug;
				
		$this->logInfo('plgVmOnUpdateOrderPayment => start.', 'info');
		
        $payments_internal_list = $this->_getIPGInternalData($order->virtuemart_order_id);

        if (!$payments_internal_list || count($payments_internal_list) == 0 ) {
            vmError(vmText::sprintf('VMPAYMENT_INTELLIGENTPAYMENT_ERROR_NO_DATA', $order->virtuemart_order_id));
			$this->logInfo('plgVmOnUpdateOrderPayment => data not found for order [' .$order->virtuemart_order_id .']' , 'info');
            return false;
        }
        $payments_internal = $payments_internal_list[0];

        $intelligentpaymentDetails = $this->_getIntelligentpaymentDetails($method);
        
        Config::$MerchantId = $intelligentpaymentDetails['merchant_id'];
        Config::$Password = $intelligentpaymentDetails['api_password'];
        
        if($method->sandbox) {
            Config::buildEvoEnv4Test(
                $intelligentpaymentDetails['cashierUrl'],
                $intelligentpaymentDetails['jsUrl'],
                $intelligentpaymentDetails['tokenUrl'],
                $intelligentpaymentDetails['paymentUrl']
                );
        } else {
            Config::buildEvoEnv4Prod(
                $intelligentpaymentDetails['cashierUrl'],
                $intelligentpaymentDetails['jsUrl'],
                $intelligentpaymentDetails['tokenUrl'],
                $intelligentpaymentDetails['paymentUrl']
                );
        }
        
        $virtuemart_order_id = $order->virtuemart_order_id;
        $originalMerchantTxId = $order->order_number;
        $targetDomain = str_replace(JURI::root(true)."/", '', JURI::root());
        $paymentCurrencyId = $order->payment_currency_id;
        $paymentCurrency = CurrencyDisplay::getInstance($paymentCurrencyId);
        $totalInPaymentCurrency = round($paymentCurrency->convertCurrencyTo($paymentCurrencyId, $order->order_total, false), 2);
        
        //CAPTURE occurrs when order status change from pending to success
        if($payments_internal->xtype == 'AUTH_ONLY' && $old_order_status == $method->status_pending && $order->order_status == $method->status_success) {
            $captureAction = (new Payments() )->capture();
            $captureAction -> allowOriginUrl($targetDomain) ->
                             amount($totalInPaymentCurrency) ->
                             timestamp(strtotime(date('Y-m-d H:i:s')) * 1000)->
                             originalMerchantTxId($originalMerchantTxId);
            
            $result = $captureAction->execute();
         
			$this->logInfo('plgVmOnUpdateOrderPayment => CAPTURE ACTION', 'info');
		    $this->logInfo('plgVmOnUpdateOrderPayment => request for CAPTURE ACTION' .print_r($captureAction, true), 'info');
			$this->logInfo('plgVmOnUpdateOrderPayment => result for CAPTURE ACTION' .print_r($result, true), 'info');

            if ($result->result == 'success' && $result->status == 'SET_FOR_CAPTURE') {
                $this->_storeIntelligentpaymentInternalData($method, $result, $virtuemart_order_id);
				$this->logInfo('plgVmOnUpdateOrderPayment => CAPTURE ACTION success.', 'info');
                return true;
            }
			
			$this->logInfo('plgVmOnUpdateOrderPayment => end with error.', 'info');
            vmError(vmText::_('VMPAYMENT_INTELLIGENTPAYMENT_GENERAL_ERROR'));
            return false;
        }

        //VOID occurs when order status change from pending to cancel
        if($payments_internal->xtype == 'AUTH_ONLY' && $old_order_status == $method->status_pending && $order->order_status == $method->status_canceled) {
            $voidAction  = (new Payments() )->void();
            $voidAction -> allowOriginUrl($targetDomain) ->
                        timestamp(strtotime(date('Y-m-d H:i:s')) * 1000)->
            			originalMerchantTxId($originalMerchantTxId);
           $result = $voidAction->execute();

			$this->logInfo('plgVmOnUpdateOrderPayment => VOID ACTION', 'info');
		    $this->logInfo('plgVmOnUpdateOrderPayment => request for VOID ACTION' .print_r($voidAction, true), 'info');
			$this->logInfo('plgVmOnUpdateOrderPayment => result for VOID ACTION' .print_r($result, true), 'info');
			
           if ($result->result == 'success' && $result->status == 'VOID') {
               $this->_storeIntelligentpaymentInternalData($method, $result, $virtuemart_order_id);
			   $this->logInfo('plgVmOnUpdateOrderPayment => VOID ACTION success.', 'info');
               return true;
           }

		   $this->logInfo('plgVmOnUpdateOrderPayment => end with error.', 'info');
           vmError(vmText::_('VMPAYMENT_INTELLIGENTPAYMENT_GENERAL_ERROR'));
           return false;
        }
        
        //REFUND
        if ($old_order_status == $method->status_success && $order->order_status == $method->status_refunded) {
                $price = round($order->order_total, 2);
                $refundAction = (new Payments() )->refund();
                $refundAction -> allowOriginUrl($targetDomain) ->
        		                  amount($price) ->
        		                  timestamp(strtotime(date('Y-m-d H:i:s')) * 1000)->
        				          originalMerchantTxId($originalMerchantTxId);
        		$result = $refundAction->execute();
        		
				$this->logInfo('plgVmOnUpdateOrderPayment => REFUND ACTION', 'info');
		        $this->logInfo('plgVmOnUpdateOrderPayment => request for REFUND ACTION' .print_r($refundAction, true), 'info');
			    $this->logInfo('plgVmOnUpdateOrderPayment => result for REFUND ACTION' .print_r($result, true), 'info');

        		if($result->result == 'success' && ( $result->status == 'SET_FOR_REFUND' || $result->status == 'COMPLETED_REFUND')){
                    $this->logInfo('plgVmOnUpdateOrderPayment => REFUND ACTION success.', 'info');
                    return true;
                }

				$this->logInfo('plgVmOnUpdateOrderPayment => end with error.', 'info');
				vmError(vmText::_('VMPAYMENT_INTELLIGENTPAYMENT_GENERAL_ERROR'));
                return false;
        }

        $this->logInfo('plgVmOnUpdateOrderPayment => end.[old_order_status='. $old_order_status . ', current_order_status='. $order->order_status . ']'  , 'info');
        return true;
    }

}

// No closing tag
