{*
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
*  @author 2007-2021 ZoodPay
*  @copyright ZoodPay
*  @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*
*}

<form id="configuration_form" class="defaultForm form-horizontal" action="#" method="post" enctype="multipart/form-data" novalidate="">
<input type="hidden" name="token_check" id="token_check" value="{Tools::getAdminToken('ajaxcheck')}">

   <input type="hidden" name="btnRefund" value="1">

  <div class="panel" id="fieldset_0_1">

     <div class="panel-heading">
        <i class="icon-envelope"></i>
        {l s='ZoodPay Refund' mod='zoodpay'} 
     </div>
     <div class="form-wrapper">
        <div class="form-group">
           <label class="control-label col-lg-3 required">
           {l s='Transtion Id' mod='zoodpay'}
         </label>

           <div class="col-lg-3">
           <input type="text" name="ZoodPay_TRANSTION_ID" id="T__ID" value="" class="" required="required">
           </div>
           <div class="col-lg-12" id="poduct_data" >
           </div>
        </div>
        <!--<div class="form-group">
           <label class="control-label col-lg-3">
           {l s='Full refund' mod='zoodpay'}
         </label>

         <div class="col-lg-9">
             <div class="radio ">
               <label>
               <input type="radio" name="refund_zoodpay" id="full" value="0" checked="checked">
               </label>

             </div>
           </div>
        </div>















         <div class="form-group">















            <label class="control-label col-lg-3">














            {l s='Partial refund' mod='zoodpay'}
            















            </label>















            <div class="col-lg-9">















               <div class="radio ">















                  <label><input type="radio" name="refund_zoodpay" id="partial" value="1"></label>















               </div>















            </div>















         </div>















         <div class="form-group refund_text">















            <label class="control-label col-lg-3 required">














            {l s='Amount' mod='zoodpay'}
            















            </label>















            <div class="col-lg-2">















               <input type="text" name="Refund_Amount" id="Refund_Amount" value="" class="" required="required">















               <p class="help-block">













                  {l s='Please, enter an amount your want to refund' mod='zoodpay'}

                  















               </p>















            </div>















         </div>















      </div>















       /.form-wrapper -->















      <div class="panel-footer">







         <input type="hidden" name="action" value="REFUNDPROCESS" >







         <button type="submit" value="1" id="configuration_form_submit_btn_1" name="btnRefund" class="btn btn-default pull-right">















         <i class="process-icon-save"></i> Submit















         </button>















      </div>















   </div>















</form>