<?php
 /*
  $Id$

  DIBS module for Ecwid

  DIBS Payment Systems
  http://www.dibs.dk

  Copyright (c) 2012 DIBS A/S

  Released under the GNU General Public License
 
*/
 require "module/lib/includes/DIBSPayment.php";
 $Payment = new DIBSPayment();
 $Payment->DIBSPaymentRequest();
 
 