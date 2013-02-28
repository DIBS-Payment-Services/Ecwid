<?php
class dibs_pw_helpers extends dibs_pw_helpers_cms implements dibs_pw_helpers_interface {
     /**
     * Flag if this module uses tax amounts instead of tax percents.
     * 
     * @var bool
     */
    public static $bTaxAmount = true;
    
    /**
     * Return settings with CMS method.
     * 
     * @param string $sVar Variable name.
     * @param string $sPrefix Variable prefix.
     * @return string 
     */
    function helper_dibs_tools_conf($sVar, $sPrefix = 'DIBSPW_') {
        // We can't get config data from database,
        // we can get it only in realtime query
        $conStorage = $_POST;
        switch($sVar) {
            case 'mid' :
                return $conStorage['x_login'];
                break;
            case 'lang' :
                return $this->helper_dibs_tools_lang($_SERVER['HTTP_ACCEPT_LANGUAGE']);
                break;
            case 'fee' :
                $addfee = $this->helper_dibs_get_config_key('addfee');
                return ($addfee == '1') ? 'yes' : '0';
                break;
            case 'testmode' :
                 return ($conStorage["x_test_request"]) ? 'yes' : '0';
                 break;
            case 'paytype' :
                return $this->helper_dibs_get_config_key('paytype');
                break;
            case 'account' :
                return $this->helper_dibs_get_config_key('account');
                break;
            case 'capturenow': 
                return (!isset($conStorage["x_type"])) ? 'yes' : '0';
                break;
            case 'hmac':
                return $this->helper_dibs_get_config_key('hmac');
                break;
        }
    }
        

    /**
     * Get full CMS url for page.
     * 
     * @param string $sLink Link or its part to convert to full CMS-specific url.
     * @return string 
     */
    function helper_dibs_tools_url($sLink) {
        return $this->helper_dibs_get_config_key('host').$sLink;
        
    }
    
    /**
     * Build CMS order information to API object.
     * 
     * @param mixed $mOrderInfo All order information, needed for DIBS (in shop format).
     * @param bool $bResponse Flag if it's response call of this method.
     * @return object 
     */
    function helper_dibs_obj_order($mOrderInfo, $bResponse = FALSE) {
        $arr = explode('#', $mOrderInfo["x_description"]);
        return (object)array(
            'orderid'  => $arr[1],
            'amount'   => $mOrderInfo["x_amount"],
            'currency' => dibs_pw_api::api_dibs_get_currencyValue($mOrderInfo["x_currency_code"])
        );

    }
    
    /**
     * Build CMS each ordered item information to API object.
     * 
     * @param mixed $mOrderInfo All order information, needed for DIBS (in shop format).
     * @return object 
     */
    function helper_dibs_obj_items($mOrderInfo) {
        $aItems = array();
        foreach($mOrderInfo['x_line_item'] as $mItem) {
            $itemsArr = explode('<|>', $mItem); 
            $aItems[] = (object)array(
                'id'    => $itemsArr[1],
                'name'  => $itemsArr[0],
                'sku'   => "emptySku",
                'price' => $itemsArr[4],
                'qty'   => $itemsArr[3],
                'tax'   => 0
            );
        }
        
        $aItems[] = (object)array(
            'id'    => 'tax0',
            'name'  => 'Ecwid total tax',
            'sku'   => '',
            'price' => (isset($mOrderInfo['x_tax']))?  $mOrderInfo['x_tax'] : 0,
            'qty'   => 1,
            'tax'   => 0
        );
        
        return $aItems;
    }
    
    /**
     * Build CMS shipping information to API object.
     * 
     * @param mixed $mOrderInfo All order information, needed for DIBS (in shop format).
     * @return object 
     */
    function helper_dibs_obj_ship($mOrderInfo) {
         return (object)array(
                'id'    => 'shipping',
                'name'  => "Shipping Rate",
                'sku'   => "",
                'price' => (isset($mOrderInfo['x_freight']))?  $mOrderInfo['x_freight'] : 0,
                'qty'   => 1,
                'tax'   => 0
        );
    }
    
    /**
     * Build CMS customer addresses to API object.
     * 
     * @param mixed $mOrderInfo All order information, needed for DIBS (in shop format).
     * @return object 
     */
    function helper_dibs_obj_addr($mOrderInfo) {
        return (object)array(
            'shippingfirstname'  => $mOrderInfo['x_ship_to_first_name'],
            'shippinglastname'   => $mOrderInfo['x_ship_to_last_name'],
            'shippingpostalcode' => $mOrderInfo['x_ship_to_zip'],
            'shippingpostalplace'=> $mOrderInfo['x_ship_to_city'],
            'shippingaddress2'   => $mOrderInfo['x_address'],
            'shippingaddress'    => $mOrderInfo['x_ship_to_country'] . " " . 
                                    $mOrderInfo['x_ship_to_state'],
            
            'billingfirstname'   => $mOrderInfo['x_first_name'],
            'billinglastname'    => $mOrderInfo['x_last_name'],
            'billingpostalcode'  => $mOrderInfo['x_last_name'],
            'billingpostalplace' => $mOrderInfo['x_zip'],
            'billingaddress2'    => $mOrderInfo['x_address'],
            'billingaddress'     => $mOrderInfo['x_address'],
            'billingmobile'      => $mOrderInfo['x_phone'],
            'billingemail'       => $mOrderInfo['x_email']
        );
    }
    
    /**
     * Returns object with URLs needed for API, 
     * e.g.: callbackurl, acceptreturnurl, etc.
     * 
     * @param mixed $mOrderInfo All order information, needed for DIBS (in shop format).
     * @return object.
     */
    function helper_dibs_obj_urls($mOrderInfo = null) {
        return (object)array(
            'acceptreturnurl' => "/success.php?invnum=$mOrderInfo[x_invoice_num]&amnt=$mOrderInfo[x_amount]",
            'callbackurl'     => "/callback.php",
            'cancelreturnurl' => "/cancel.php?invnum=$mOrderInfo[x_invoice_num]&amnt=$mOrderInfo[x_amount]",
            'carturl'         => "/success.php"
        );
    }
    
    /**
     * Returns object with additional information to send with payment.
     * 
     * @param mixed $mOrderInfo All order information, needed for DIBS (in shop format).
     * @return object 
     */
    function helper_dibs_obj_etc($mOrderInfo) {
        return (object)array(
                    'sysmod'      => 'ecwid_1_0_0',
                    'callbackfix' => $this->helper_dibs_tools_url("/callback.php?invnum=$mOrderInfo[x_invoice_num]")
                );
    }
        
    /*
     * Create md5hash needed for AuthorizeNet authentification
     * @param string $md5Hash Initial value. Set in admin area
     * @param string $merchId Id mercant
     * @param string $transId Id transaction
     * @param string $amount  Amount
     * @return md5hash for AuthorizeNet
     * 
     */
    function helper_dibs_tools_md5ANHash($md5Hash, $merchId, $transId, $ammount) {
        return md5($md5Hash.$merchId.$transId.$ammount);
    }
    
    
    /*
     * Prepare parameters for emulation AuthorizeNet service.
     * 
     * @return Array of AuthorizeNet parameters to communicate with Ecwid
     */
    function helper_dibs_tools_prepareANFullParams($params, $p) {
            $DIBSResponse = array();
            $amount = $this->helper_dibs_tools_convertANtoDIBS_amount($params['amount']); 
            $DIBSResponse['x_response_code'] =       1;
            $DIBSResponse['x_response_reason_code']= 1;
            $DIBSResponse['x_trans_id']  = $params['transaction'];
            $DIBSResponse['x_amount']    = $params['amnt'];
            $DIBSResponse['x_invoice_num'] = $params['invnum'];
            $DIBSResponse['x_MD5_Hash']  = $this->helper_dibs_tools_md5ANHash($params['md5ANHash'], $params['merchant'], $params['transaction'], $amount);
            $DIBSResponse['x_cust_id']   = $params['merchant'];
            $DIBSResponse['x_card_type'] = $params['cardTypeName'];
            $DIBSResponse['x_auth_code'] = $params['actionCode'];
            return $DIBSResponse;
    }
    
    
    /*
     * Prepare parameters for emulation AuthorizeNet service.
     * 
     * @return Array of AuthorizeNet parameters to communicate with Ecwid
     */
    function helper_dibs_tools_prepareANShortParams($params, $p) {
            $data = array();
            $amount = $this->helper_dibs_tools_convertANtoDIBS_amount($params['amnt']); 
            $data['x_response_code'] =       2;
            $data['x_response_reason_code']= 2;
            $data['x_trans_id']  = $params['invnum'];
            $data['x_amount']    = $params['amnt'];
            $data['x_invoice_num'] = $params['invnum'];
            $data['x_MD5_Hash']  = $this->helper_dibs_tools_md5ANHash($params['md5ANHash'], $params['merchant'], $params['invnum'], $amount);
            return $data;
     }
    
   
    /*
     * Convert amoutn from DIBS format to Ecwid format
     * 
     * @return amount in Ecwid format 1.00 instead of DIBS internal amount format 100
     */
    function helper_dibs_tools_convertANtoDIBS_amount( $amount ) {
            $pref = substr($amount, 0, strlen($amount)-2);
            $end  = substr($amount, -2, 2);
            return $pref.'.'.$end;
    }
    
    /*
     * We have no acces to the Ecwid settings. 
     * We get language from $_SERVER array
     * 
     * @param  string $lansServerStr $_SERVER['HTTP_ACCEPT_LANGUAGE'];
     * @return string DIBS formatted language string value 
     */
    function helper_dibs_tools_lang($lansServerStr) {
        $lang = substr($lansServerStr, 0, 2);
        switch ($lang){
            case "da":
                return "da_DK";
                break;
            case "sv":
                return "sv_SE";
                break;
            case "no":
                return "nb_NO";
                break;
            case "en":
                return "en_US";
                break;
             case "en-gb":
               return "en_GB";
               break;
          default:
                return "en_US";
                break;
        }
  }
    
    /*
     * Get value from cofig file.
     * 
     * @return string Value from our config file  
     */
    function helper_dibs_get_config_key($key) {
       $config = parse_ini_file(dirname(__FILE__)."/../../../conf/config.ini"); 
       return $config[$key];
    }

}
?>
