<?php
require_once str_replace('\\', '/', dirname(__FILE__)) . '/dibs_pw_helpers_interface.php';
require_once str_replace('\\', '/', dirname(__FILE__)) . '/dibs_pw_helpers_cms.php';
require_once str_replace('\\', '/', dirname(__FILE__)) . '/dibs_pw_helpers.php';

class dibs_pw_api extends dibs_pw_helpers {

    /**
     * Settings of module inner template engine.
     * 
     * @var array 
     */
    private static $aTemplates   = array('folder' => 'tmpl',
                                         'marker' => '#',
                                         'autotranslate' => array('lbl','msg', 'sts', 'err'),
                                         'tmpls' => array('error' => 'dibs_pw_error'));

    /**
     * Default currency code (in two ISO formats).
     * 
     * @var array 
     */
    private static $sDefaultCurr = array(0 => 'EUR', 1 => '978');

    /**
     * DIBS gateway URL.
     * 
     * @var string 
     */
    private static $sFormAction  = 'https://sat1.dibspayment.com/dibspaymentwindow/entrypoint';
    
        
    /**
     * Array of currency's two ISO formats relations.
     * 
     * @var array 
     */
    private static $aCurrency = array('ADP' => '020','AED' => '784','AFA' => '004','ALL' => '008',
                                      'AMD' => '051','ANG' => '532','AOA' => '973','ARS' => '032',
                                      'AUD' => '036','AWG' => '533','AZM' => '031','BAM' => '977',
                                      'BBD' => '052','BDT' => '050','BGL' => '100','BGN' => '975',
                                      'BHD' => '048','BIF' => '108','BMD' => '060','BND' => '096',
                                      'BOB' => '068','BOV' => '984','BRL' => '986','BSD' => '044',
                                      'BTN' => '064','BWP' => '072','BYR' => '974','BZD' => '084',
                                      'CAD' => '124','CDF' => '976','CHF' => '756','CLF' => '990',
                                      'CLP' => '152','CNY' => '156','COP' => '170','CRC' => '188',
                                      'CUP' => '192','CVE' => '132','CYP' => '196','CZK' => '203',
                                      'DJF' => '262','DKK' => '208','DOP' => '214','DZD' => '012',
                                      'ECS' => '218','ECV' => '983','EEK' => '233','EGP' => '818',
                                      'ERN' => '232','ETB' => '230','EUR' => '978','FJD' => '242',
                                      'FKP' => '238','GBP' => '826','GEL' => '981','GHC' => '288',
                                      'GIP' => '292','GMD' => '270','GNF' => '324','GTQ' => '320',
                                      'GWP' => '624','GYD' => '328','HKD' => '344','HNL' => '340',
                                      'HRK' => '191','HTG' => '332','HUF' => '348','IDR' => '360',
                                      'ILS' => '376','INR' => '356','IQD' => '368','IRR' => '364',
                                      'ISK' => '352','JMD' => '388','JOD' => '400','JPY' => '392',
                                      'KES' => '404','KGS' => '417','KHR' => '116','KMF' => '174',
                                      'KPW' => '408','KRW' => '410','KWD' => '414','KYD' => '136',
                                      'KZT' => '398','LAK' => '418','LBP' => '422','LKR' => '144',
                                      'LRD' => '430','LSL' => '426','LTL' => '440','LVL' => '428',
                                      'LYD' => '434','MAD' => '504','MDL' => '498','MGF' => '450',
                                      'MKD' => '807','MMK' => '104','MNT' => '496','MOP' => '446',
                                      'MRO' => '478','MTL' => '470','MUR' => '480','MVR' => '462',
                                      'MWK' => '454','MXN' => '484','MXV' => '979','MYR' => '458',
                                      'MZM' => '508','NAD' => '516','NGN' => '566','NIO' => '558',
                                      'NOK' => '578','NPR' => '524','NZD' => '554','OMR' => '512',
                                      'PAB' => '590','PEN' => '604','PGK' => '598','PHP' => '608',
                                      'PKR' => '586','PLN' => '985','PYG' => '600','QAR' => '634',
                                      'ROL' => '642','RUB' => '643','RUR' => '810','RWF' => '646',
                                      'SAR' => '682','SBD' => '090','SCR' => '690','SDD' => '736',
                                      'SEK' => '752','SGD' => '702','SHP' => '654','SIT' => '705',
                                      'SKK' => '703','SLL' => '694','SOS' => '706','SRG' => '740',
                                      'STD' => '678','SVC' => '222','SYP' => '760','SZL' => '748',
                                      'THB' => '764','TJS' => '972','TMM' => '795','TND' => '788',
                                      'TOP' => '776','TPE' => '626','TRL' => '792','TRY' => '949',
                                      'TTD' => '780','TWD' => '901','TZS' => '834','UAH' => '980',
                                      'UGX' => '800','USD' => '840','UYU' => '858','UZS' => '860',
                                      'VEB' => '862','VND' => '704','VUV' => '548','XAF' => '950',
                                      'XCD' => '951','XOF' => '952','XPF' => '953','YER' => '886',
                                      'YUM' => '891','ZAR' => '710','ZMK' => '894','ZWD' => '716');
    
    /**
     * Returns CMS order common information converted to standardized order information objects.
     * 
     * @param mixed $mOrderInfo All order information, needed for DIBS (in shop format).
     * @return object 
     */
    private function api_dibs_commonOrderObject($mOrderInfo) {
        return (object)array(
            'order' => $this->helper_dibs_obj_order($mOrderInfo),
            'urls'  => $this->helper_dibs_obj_urls($mOrderInfo),
            'etc'   => $this->helper_dibs_obj_etc($mOrderInfo)
        );
    }

    /**
     * Returns CMS order invoice information converted to standardized order information objects.
     * 
     * @param mixed $mOrderInfo All order information, needed for DIBS (in shop format).
     * @return object 
     */
    private function api_dibs_invoiceOrderObject($mOrderInfo) {
        return (object)array(
            'items' => $this->helper_dibs_obj_items($mOrderInfo),
            'ship'  => $this->helper_dibs_obj_ship($mOrderInfo),
            'addr'  => $this->helper_dibs_obj_addr($mOrderInfo)
        );
    }

    /**
     * Collects API parameters to send in dependence of checkout type. API entry point.
     * 
     * @param mixed $mOrderInfo All order information, needed for DIBS (in shop format).
     * @return array 
     */
    final public function api_dibs_get_requestFields($mOrderInfo) {
        $aData = array();
        $oOrder = $this->api_dibs_commonOrderObject($mOrderInfo);
        //$this->api_dibs_prepareDB($oOrder->order->orderid);
        $this->api_dibs_commonFields($aData, $oOrder);
        $this->api_dibs_invoiceFields($aData, $mOrderInfo);
        if(count($oOrder->etc) > 0) {
            foreach($oOrder->etc as $sKey => $sVal) $aData['s_' . $sKey] = $sVal;
        }
        array_walk($aData, create_function('&$val', '$val = trim($val);'));
        $sMAC = $this->api_dibs_calcMAC($aData, $this->helper_dibs_tools_conf('hmac'), true);
        if(!empty($sMAC)) $aData['MAC'] = $sMAC;
        return $aData;
    }
    
    /**
     * Adds to $aData common DIBS integration parameters.
     * 
     * @param array $aData Array to fill in by link with DIBS API parameters.
     * @param object $oOrder Formated to object order common information.
     */
    private function api_dibs_commonFields(&$aData, $oOrder) {
        $aData['orderid']  = $oOrder->order->orderid;
        $aData['merchant'] = $this->helper_dibs_tools_conf('mid');
        $aData['amount']   = self::api_dibs_round($oOrder->order->amount);
        $aData['currency'] = $oOrder->order->currency;
        $aData['language'] = $this->helper_dibs_tools_conf('lang');
        if((string)$this->helper_dibs_tools_conf('fee') == 'yes') $aData['addfee'] = 1;
        if((string)$this->helper_dibs_tools_conf('testmode') == 'yes') $aData['test'] = 1;
        $sPaytype = $this->helper_dibs_tools_conf('paytype');
        if(!empty($sPaytype)) $aData['paytype'] = $sPaytype;
        $sAccount = $this->helper_dibs_tools_conf('account');
        if(!empty($sAccount)) $aData['account'] = $sAccount;
        $aData['acceptreturnurl'] = $this->helper_dibs_tools_url($oOrder->urls->acceptreturnurl); 
        $aData['cancelreturnurl'] = $this->helper_dibs_tools_url($oOrder->urls->cancelreturnurl);
        $aData['callbackurl']  =    $this->helper_dibs_tools_url($oOrder->urls->callbackurl);
       
    }
    
    /**
     * Adds Invoice API parameters specific for SAT PW.
     * 
     * @param array $aData  Array to fill in by link with DIBS API parameters.
     * @param mixed $mOrderInfo All order information, needed for DIBS (in shop format).
     */
    private function api_dibs_invoiceFields(&$aData, $mOrderInfo) {
        $oOrder = $this->api_dibs_invoiceOrderObject($mOrderInfo);
        foreach($oOrder->addr as $sKey => $sVal) {
            $sVal = trim($sVal);
            if(!empty($sVal)) $aData[$sKey] = self::api_dibs_utf8Fix($sVal);
        }
        $oOrder->items[] = $oOrder->ship;
        if(isset($oOrder->items) && count($oOrder->items) > 0) {
            $aData['oitypes'] = 'QUANTITY;UNITCODE;DESCRIPTION;AMOUNT;ITEMID';
                                
            $aData['oinames'] = 'Qty;UnitCode;Description;Amount;ItemId';
           
            if(isset($oOrder->items[0]->tax)) {
                $aData['oitypes'] .= (self::$bTaxAmount ? ';VATAMOUNT' : ';VATPERCENT');
                $aData['oinames'] .= (self::$bTaxAmount ? ';VatAmount' : ';VatPercent');
            }
            
            $i = 1;
            foreach($oOrder->items as $oItem) {
                $iTmpPrice = self::api_dibs_round($oItem->price);
                if(!empty($iTmpPrice)) {
                    $sTmpName = !empty($oItem->name) ? $oItem->name : $oItem->sku;
                    if(empty($sTmpName)) $sTmpName = $oItem->id;

                    $aData['oiRow' . $i++] = 
                        self::api_dibs_round($oItem->qty, 3) / 1000 . ';' . 
                        'pcs;' . 
                        self::api_dibs_utf8Fix(str_replace(';','\;',$sTmpName)) . ';' .
                        $iTmpPrice . ';' .
                        self::api_dibs_utf8Fix(str_replace(';','\;',$oItem->id)) . 
                        (isset($oItem->tax) ? ';' . self::api_dibs_round($oItem->tax) : '');
                }
                unset($iTmpPrice);
            }
	}
        if(!empty($aData['orderid'])) $aData['yourRef'] = $aData['orderid'];
        if((string)$this->helper_dibs_tools_conf('capturenow') == 'yes') $aData['capturenow'] = 1;
        $sDistribType = $this->helper_dibs_tools_conf('distr');
        if((string)$sDistribType != 'empty') $aData['distributiontype'] = strtoupper($sDistribType);
        $aData['distributiontype']='email';
        
    }
      
    /**
     * Give fallback verification error page 
     * if module has no ability to use CMS for error displaying.
     * 
     * @param int $iCode Error code.
     * @return string 
     */
    public function api_dibs_getFatalErrorPage($iCode) {
        return $this->api_dibs_renderTemplate(self::$aTemplates['tmpls']['error'],
                   array('errname_err' => 0,
                         'errcode_msg' => 'errcode',
                         'errcode'     => $iCode,
                         'errmsg_msg'  => 'errmsg',
                         'errmsg_err'  => $iCode,
                         'link_toshop' => $this->helper_dibs_obj_urls()->carturl,
                         'toshop_msg'  => 'toshop'));
    }
       
    /**
     * Simple template loader and renderer. Used to load fallback error template.
     * Support "autotranslate" for self::$aTemplates['autotranslate'] text types.
     * 
     * @param string $sTmplName Name of template to use.
     * @param array $sParams Parameters to replace markers during render.
     * @return string 
     */
    public function api_dibs_renderTemplate($sTmplName, $sParams = array()) {
        $sTmpl = file_get_contents(str_replace('\\', '/', dirname(__FILE__)) . '/' . 
                                   self::$aTemplates['folder'] . '/' . $sTmplName);
        if($sTmpl !== FALSE) {
            foreach($sParams as $sKey => $sVal) {
                $sValueType = substr($sKey, -3);
                if(in_array($sValueType, self::$aTemplates['autotranslate'])) {
                    $sVal = $this->helper_dibs_tools_lang($sVal, $sValueType);
                }
                $sTmpl = str_replace(self::$aTemplates['marker'] . $sKey . self::$aTemplates['marker'], 
                                     $sVal, $sTmpl);
            }
        }
        else $sTmpl = '';
        
        return $sTmpl;
    }
    
    /** DIBS API TOOLS START **/
    /**
     * Calculates MAC for given array of data.
     * 
     * @param array $aData Array of data to calculate the MAC hash.
     * @param string $sHMAC HMAC key for hash calculation.
     * @param bool $bUrlDecode Flag if urldecode before MAC hash calculation is needed (for success action).
     * @return string 
     */
    final public static function api_dibs_calcMAC($aData, $sHMAC, $bUrlDecode = FALSE) {
        $sMAC = '';
        if(!empty($sHMAC)) {
            $sData = '';
            if(isset($aData['MAC'])) unset($aData['MAC']);
            ksort($aData);
            foreach($aData as $sKey => $sVal) {
                $sData .= '&' . $sKey . '=' . (($bUrlDecode === TRUE) ? urldecode($sVal) : $sVal);
            }
            $sMAC = hash_hmac('sha256', ltrim($sData, '&'), self::api_dibs_hextostr($sHMAC));
        }
        
        return $sMAC;
    }
    
    
    /**
     * Compare calculated MAC with MAC from response urldecode response if second parameter is TRUE.
     * 
     * @param string $sHMAC HMAC key for hash calculation.
     * @param bool $bUrlDecode Flag if urldecode before MAC hash calculation is needed (for success action).
     * @return bool 
     */
    final public static function api_dibs_checkMAC($sHMAC, $bUrlDecode = FALSE) {
        if(!isset($_POST['MAC'])) $_POST['MAC'] = '';
        return ($_POST['MAC'] == self::api_dibs_calcMAC($_POST, $sHMAC, $bUrlDecode)) ? TRUE : FALSE;
    }
    
    /**
     * Returns form action URL of gateway.
     * 
     * @return string 
     */
    final public function api_dibs_get_formAction() {
        return self::$sFormAction;
    }
    
    /**
     * Returns ISO to DIBS currency array.
     * 
     * @return array 
     */
    final public static function api_dibs_get_currencyArray() {
        return self::$aCurrency;
    }

      
    /**
     * Gets value by code from currency array. Supports fliped values.
     * 
     * @param string $sCode Currency code (its ISO formats from self::$aCurrency depends on $bFlip value)
     * @param bool $bFlip If we need to flip self::$aCurrency array and look in another format.
     * @return string 
     */
    final public static function api_dibs_get_currencyValue($sCode, $bFlip = FALSE) {
        $aCurrency = ($bFlip === TRUE) ? array_flip(self::api_dibs_get_currencyArray()) : 
                     self::api_dibs_get_currencyArray();
        return isset($aCurrency[$sCode]) ? $aCurrency[$sCode] : 
                                           $aCurrency[self::$sDefaultCurr[$bFlip === TRUE ? 1 : 0]];
    }
    
    /**
     * Convert hex HMAC to string.
     * 
     * @param string $sHMAC HMAC key for hash calculation.
     * @return string 
     */
    private static function api_dibs_hextostr($sHMAC) {
        $sRes = '';
        foreach(explode("\n", trim(chunk_split($sHMAC, 2))) as $h) $sRes .= chr(hexdec($h));
        return $sRes;
    }
    
        
    /**
     * Returns integer representation of amount. Saves two signs that are
     * after floating point in float number by multiplication by 100.
     * E.g.: converts to cents in money context.
     * Workarround of float to int casting.
     * 
     * @param float $fNum Float number to round safely.
     * @param int $iPrec Precision. Quantity of digits to save after decimal point.
     * @return int 
     */
    public static function api_dibs_round($fNum, $iPrec = 2) {
        return empty($fNum) ? (int)0 : (int)(string)(round($fNum, $iPrec) * pow(10, $iPrec));
    }
    
    /**
     * Fixes UTF-8 special symbols if encoding of CMS is not UTF-8.
     * Main using is for wided latin alphabets.
     * 
     * @param string $sText The text to prepare in UTF-8 if it is not encoded to it yet.
     * @return string 
     */
    public static function api_dibs_utf8Fix($sText) {
        return (mb_detect_encoding($sText) == 'UTF-8' && mb_check_encoding($sText, 'UTF-8')) ?
               $sText : utf8_encode($sText);
    }
    /** DIBS API TOOLS END **/
}
?>