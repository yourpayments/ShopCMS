<?php
/**
 * @connect_module_class_name CPayU
 *
 */

ini_set("display_errors",true);
error_reporting(E_ALL);

	
define("__ENCODE__", "windows-1251");  # utf-8 # windows-1251

class CPayU extends PaymentModule {


        #var $type = PAYMTD_TYPE_ONLINE;
        var $language = 'rus';
        var $default_logo = '/data/images/loaderpayu.gif';
        var $prUrl = "https://secure.payu.ru/order/lu.php";#"; 
        

        var $useSSL = false;
    
        function _initVars(){
            
            $this->title = CPayU::_ENC("Платежная система PayU");
            $this->description = CPayU::_ENC("Платежный агрегатор платежей PayU :<br> <a href='http://payu.ua'>PayU Украина</a><br><a href='http://payu.ru'>PayU Россия</a>");

            $this->sort_order = 1;
    
            $this->Settings = array( 
                    'CONF_PAYU_MERCHANT',
                    'CONF_PAYU_SECRET_KEY',
                    'CONF_PAYU_DEBUG_MODE',
                    'CONF_PAYU_LU_URL',
                    'CONF_PAYU_CURRENCY',
                    'CONF_PAYU_BACK_REF',
                    'CONF_PAYU_VAT',
                    'CONF_PAYU_LANGUAGE',                   
                );
        }
    
        function _initSettingFields(){
    
        
        
                $this->SettingsFields['CONF_PAYU_MERCHANT'] = array(
                    'settings_value'            => "XXXXXXXX",
                    'settings_title'            => CPayU::_ENC("Merchant ID"),
                    'settings_description'      => CPayU::_ENC("Идентификатор мерчанта"),
                    'settings_html_function'    => 'setting_TEXT_BOX(0,',
                    'sort_order'                => 1
                );
                $this->SettingsFields['CONF_PAYU_SECRET_KEY'] = array(
                    'settings_value'            => "*********",
                    'settings_title'            => CPayU::_ENC("Merchant secret key"),
                    'settings_description'      => CPayU::_ENC("Секретный ключ мерчанта"),
                    'settings_html_function'    => 'setting_TEXT_BOX(0,',
                    'sort_order'                => 1
                );
                $this->SettingsFields['CONF_PAYU_DEBUG_MODE'] = array(
                    'settings_value'            => "*********",
                    'settings_title'            => CPayU::_ENC("Debug mode"),
                    'settings_description'      => CPayU::_ENC("Режим отладки"),
                    'settings_html_function'    => 'setting_SELECT_BOX(CPayU::_getDebugMode(),',
                    'sort_order'                => 1
                );

                
                $this->SettingsFields['CONF_PAYU_LU_URL'] = array(
                    'settings_value'            => "https://secure.payu.ru/order/lu.php",
                    'settings_title'            => CPayU::_ENC("LiveUpdate URL"),
                    'settings_description'      => CPayU::_ENC("Ссылка LiveUpdate (default : https://secure.payu.ru/order/lu.php)"),
                    'settings_html_function'    => 'setting_TEXT_BOX(0,',
                    'sort_order'                => 1
                );
                $this->SettingsFields['CONF_PAYU_CURRENCY'] = array(
                    'settings_value'            => "RUB",
                    'settings_title'            => CPayU::_ENC("Валюта мерчанта "),
                    'settings_description'      => CPayU::_ENC("RUB"),
                    'settings_html_function'    => 'setting_TEXT_BOX(0,', #'setting_CURRENCY_SELECT(',
                    'sort_order'                => 1
                );
                $this->SettingsFields['CONF_PAYU_BACK_REF'] = array(
                    'settings_value'            => "NO",
                    'settings_title'            => CPayU::_ENC("Ссылка возврата клиента "),
                    'settings_description'      => CPayU::_ENC("Если оставить значение NO, клиент останется в системе PayU<br>".
                                                   "Если сделать поле пустым - Клиент вернется по дефолтной ссылке"),
                    'settings_html_function'    => 'setting_TEXT_BOX(0,',
                    'sort_order'                => 1
                ); 
                $this->SettingsFields['CONF_PAYU_VAT'] = array(
                    'settings_value'            => "0",
                    'settings_title'            => CPayU::_ENC("НДС"),
                    'settings_description'      => CPayU::_ENC("Если 0 - без НДС"),
                    'settings_html_function'    => 'setting_TEXT_BOX(0,',
                    'sort_order'                => 1
                );
                $this->SettingsFields['CONF_PAYU_LANGUAGE'] = array(
                    'settings_value'            => "RU",
                    'settings_title'            => CPayU::_ENC("Язык страницы"),
                    'settings_description'      => CPayU::_ENC("Доступны ( RU, EN, RO, DE, FR, IT, ES )"),
                    'settings_html_function'    => 'setting_TEXT_BOX(0,',
                    'sort_order'                => 1
                );
        }

        function payment_process( $order ){
        
        $cart = cartGetCartContent();
        
        $total = 0;
            foreach ( $cart['cart_content'] as $item )
            {
                    $price = PaymentModule::_convertCurrency( $item['costUC'], 0, $this->_getSettingValue('CONF_PAYU_CURRENCY'));
                    if ($price == 0) $price = $item['costUC']; 
                    $total += $price * $item['quantity'];
                    $d['ORDER_PNAME'][] = CPayU::_DEC($item['name']); # Array with data of goods
                    $d['ORDER_QTY'][] = $item['quantity']; # Array with data of counts of each goods 
                    $d['ORDER_PRICE'][] = $price; # round( $price, 2 ); # Array with prices of goods
                    $d['ORDER_VAT'][] = 0; #$data['VAT'];# Array with VAT of each goods  => from settings
                    $d['ORDER_PCODE'][] = $item['productID']; # Array with codes of goods
                    $d['ORDER_PINFO'][] = ""; # Array with additional data of goods
            }

        $this->prUrl = $this->_getSettingValue('CONF_PAYU_LU_URL');


        $bill = &$order['billing_info'];
        $forSend = array (
                    'ORDER_REF' => "", # Uniqe order 
                    'ORDER_DATE' => date("Y-m-d H:i:s"), # Date of paying ( Y-m-d H:i:s ) 
                    'ORDER_PNAME' => $d['ORDER_PNAME'], # Array with data of goods
                    'ORDER_PCODE' => $d['ORDER_PCODE'], # Array with codes of goods
                    #'ORDER_PINFO' => $d['ORDER_PINFO'], # Array with additional data of goods
                    'ORDER_PRICE' => $d['ORDER_PRICE'], # Array with prices of goods
                    'ORDER_QTY' => $d['ORDER_QTY'], # Array with data of counts of each goods 
                    'ORDER_VAT' => $d['ORDER_VAT'], # Array with VAT of each goods
                    'ORDER_SHIPPING' => $order["shipping_cost"], # Shipping cost
                    'PRICES_CURRENCY' => $this->_getSettingValue('CONF_PAYU_CURRENCY'),  # Currency
                    'total' => $total
                  );


                $coock = base64_encode(json_encode( $forSend ));
                SetCookie("payuform", $coock, time()+600);
    
            return 1;
        }

        function after_processing_html( $_OrderID )
        {   
            $data = @json_decode( base64_decode($_COOKIE['payuform']), true );
            
           

            if ( !$data ) return false;

            foreach ($data['ORDER_PNAME'] as $k => $v)
            {
                $data['ORDER_PNAME'][$k] = CPayU::_ENC($v);
            }


            $button = "<div style='background-color: #000000; bottom: 0; left: 0; opacity: 0.4; position: fixed; right: 0; top: 0; z-index: 1000; '></div>".
                        "<div style='position:absolute; top:50%; left:50%; margin:-40px 0px 0px -60px; z-index:1002;'>".
                "<div><img src='/data/images/loaderpayu.gif' width='120px' style='margin:0px 5px;'></div>".
                "</div>".
                    "<script>
                    setTimeout( subform, 500 );
                    function subform(){ document.getElementById('PayUForm').submit(); }
                    </script>";

            $data['ORDER_REF'] = $_SERVER['HTTP_HOST'].'_'.$_OrderID.'_'.md5( time() );

            $option  = array(   'merchant' => $this->_getSettingValue('CONF_PAYU_MERCHANT'), 
                                'secretkey' => $this->_getSettingValue('CONF_PAYU_SECRET_KEY'), 
                                'debug' => $this->_getSettingValue('CONF_PAYU_DEBUG_MODE'), 
                                'button' => $button );

            $order = ordGetOrder( $_OrderID );

            $data['DISCOUNT'] = round( ( (double)$order["order_discount"]/100 ) * $data['total'], 2); #$order['order_discount'];

            unset($data['total']);

            $result_url = $this->_getSettingValue('CONF_PAYU_BACK_REF');
            if ($result_url !== "NO") $data['BACK_REF'] = ($result_url !== "") ? $result_url : htmlentities($this->getTransactionResultURL('success'),ENT_QUOTES,'utf-8');

            $pay = PayUCLS::getInst()->setOptions( $option )->setData( $data )->LU();

            $statusID = "3";
            
           
            ostSetOrderStatusToOrder( $_OrderID, $statusID, CPayU::_ENC("Оплата через PayU"), 0, true);

            return $pay;
        }

    public static function _getDebugMode()
    {
        return array(
                    array('title'=> CPayU::_ENC("Выберите режим"), 'value'=>''),
                    array('title'=> CPayU::_ENC("Вкл"),  'value'=>'1'),
                    array('title'=> CPayU::_ENC("Выкл"), 'value'=>'0')
                    );
    }

    public function _ENC( $str )
    {
        if ( __ENCODE__  ==  "utf-8" ) return $str;
        return iconv( "utf-8", __ENCODE__,  $str );
    }

    public function _DEC( $str )
    {
        if ( __ENCODE__  ==  "utf-8" ) return $str;
        return iconv( __ENCODE__, "utf-8",  $str );
    }
}
?>