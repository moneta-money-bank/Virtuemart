<?php
/**
 *
 * Realex payment plugin
 *
 * @author Valerie Isaksen
 * @version $Id: response.php 8414 2014-10-12 20:30:38Z alatak $
 * @package VirtueMart
 * @subpackage payment
 * Copyright (C) 2004 - 2018 Virtuemart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.net
 */
defined('_JEXEC') or die();
vmJsApi::addJScript("ipgCashier", "https://cashierui-apiuat.test.secure.eservice.com.pl/js/api.js");
vmJsApi::addJScript('vm.paymentFormAutoSubmit', '
				jQuery(document).ready(function($){
					jQuery("body").addClass("vmLoading");
					var msg="'.vmText::_('VMPAYMENT_INTELLIGENTPAYMENT_REDIRECT_MESSAGE').'";
					jQuery("body").append("<div class=\"vmLoadingDiv\"><div class=\"vmLoadingDivMsg\"  style=\"text-align:center\" >"+msg+"</div></div>");
					jQuery("#vmPaymentForm").submit();
					window.setTimeout("jQuery(\'.vmLoadingDiv\').hide();",5000);
					window.setTimeout("jQuery(\'#intelligentpayment_submit\').show();", 5000);
    
				})
			');

?>

<div >
    <div style="margin: auto; text-align: center;">
        <form action="<?php echo $viewData['cashierUrl']?>" method="get" id="vmPaymentForm" >
            <input type="hidden" name="token" value="<?php echo $viewData['sessionToken']?>" />
            <input type="hidden" name="merchantId" value="<?php echo $viewData['merchantId']?>" />
            <input type="hidden" name="paymentSolutionId" value="<?php echo $viewData['paymentSolutionId']?>" />
            <input type="hidden" name="integrationMode" value="<?php echo $viewData['integrationMode']?>"/>
			<input type="submit" id="intelligentpayment_submit" class="vm-button-correct" style="display:none" value="<?php echo vmText::_('VMPAYMENT_INTELLIGENTPAYMENT_BUTTON_MESSAGE')?>"  />
        </form>
    </div>

	<div id="ipgCashierDiv" style="width:600px;height:600px; border:0px solid gray; margin:10px"></div>
</div>
