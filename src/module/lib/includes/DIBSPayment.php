<?php
  /*
  $Id$

  DIBS module for Ecwid

  DIBS Payment Systems
  http://www.dibs.dk

  Copyright (c) 2012 DIBS A/S

  Released under the GNU General Public License
 
*/
 
 require  dirname(__FILE__)."/../dibs_api/pw/dibs_pw_api.php";
     
 class DIBSPayment {
      const AUTHORIZE_NET_URL = "http://app.ecwid.com/authorizenet/";
      const ECWID_API_URL     = "https://app.ecwid.com/api/v1/";
      private $confECWID;
      private $DIBSAPIObj;
              
      public function __construct() {
          $this->init();
      }
      
      /*
       * Accept params from Ecwid service, 
       * perform request to DIBS Payment Service  
       *        
       */
      public function DIBSPaymentRequest() {
        $aPostRaw = array();
        $sRaw = urldecode(file_get_contents("php://input"));
        $sRaw = str_replace('x_line_item', 'x_line_item[]', $sRaw);
        parse_str($sRaw, $aPostRaw);
        $this->paymentData = $aPostRaw;
        $paymentData = $this->DIBSAPIObj->api_dibs_get_requestFields($this->paymentData);
        $this->sendDataByForm($paymentData, $this->DIBSAPIObj->api_dibs_get_formAction());
      }
      
      
      /*
       * Handle succesfull response from DIBS Payment Service
       * update order status in Ecwid
       * 
       */
      public function DIBSResponseSuccess(){
        $_REQUEST['md5ANHash'] = $this->confECWID['md5Hash'];
        $_REQUEST['lang'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $DIBSResponse = $this->DIBSAPIObj->helper_dibs_tools_prepareANFullParams($_REQUEST,1);
        $this->sendDataByForm($DIBSResponse, self::AUTHORIZE_NET_URL.$this->confECWID['shopId']);
      }
      
      
      /*
      * Server to server callback from DIBS to Ecwid 
      * 
      */
      public function DIBSResponseCallback() {
        $_REQUEST['md5ANHash'] = $this->confECWID['md5Hash'];
        $DIBSResponse = $this->DIBSAPIObj->helper_dibs_tools_prepareANFullParams($_REQUEST,1);
        $this->sendDataByCurl(self::AUTHORIZE_NET_URL.$this->confECWID['shopId'], $DIBSResponse);
     } 
      
      /*
       * Action on cancelling payment !!! In this version of module 
       * we use DECLINED status instead of CANCELLED. Because it was tested for free account !!!
       */
      public function DIBSResponseCancel() {
        $_REQUEST['md5ANHash'] = $this->confECWID['md5Hash'];
        $data = $this->DIBSAPIObj->helper_dibs_tools_prepareANShortParams($_REQUEST,1);
        $this->sendDataByCurl(self::AUTHORIZE_NET_URL.$this->confECWID['shopId'], http_build_query($data, '', '&'));
        header("Location: ". $this->confECWID['host']."/#ecwid:mode=cart"); 
      }
      
      public function DIBSResponseError() {
          
      }
      
      /*
       * Send data to Ecwid for updating order status, using php curl library.
       * 
       * @param  string $url Url to send requet to.
       * @param  array $data Data nedded for request.
       * 
       */
      private function sendDataByCurl($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);    
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_close($ch);
     }
      
      /*
       * Initializing
       */
      private function init() {
          $this->confECWID = parse_ini_file(dirname(__FILE__)."/../../conf/config.ini");
          $this->DIBSAPIObj = new dibs_pw_api();
      }
      
      /*
       * Return config
       */
      public function getConfig() {
          return $this->confECWID;
      }
      
      /*
       * Send data by POST method to the DIBS.
       * 
       * @param  string $action Url to send requet to.
       * @param  array  $data Data nedded for request.
       * 
       */
      private function sendDataByForm($data, $action) {
        $formStr = "<form id=\"paymentform\" method=\"post\" action=$action>";
        foreach($data as $key=>$value) {
            $formStr.= "<input type=\"hidden\" name=\"$key\" value=\"$value\">";
        }
        $formStr.= "</form><script type=\"text/javascript\">document.getElementById(\"paymentform\").submit();</script>";
        echo $formStr;
      }
        
    }

?>