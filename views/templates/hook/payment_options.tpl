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

<form method="post" action="{$action|escape:'htmlall':'UTF-8'}">	
{foreach $testarray as $count => $val }
{if $count == 0}
{$cls = "checked"} 
{/if}
<p><input type="radio" for="{$val['service_code']|escape:'htmlall':'UTF-8'}" name="service_name" value="{$val['service_code']|escape:'htmlall':'UTF-8'}" {$cls|escape:'htmlall':'UTF-8'} > <label for="{$val['service_code']|escape:'htmlall':'UTF-8'}" >{if isset($val['instalments'])}{$val['instalments']|escape:'htmlall':'UTF-8'} {"Instalment of"|escape:'htmlall':'UTF-8'} {$val['install_ammount']|escape:'htmlall':'UTF-8'} {$currency|escape:'htmlall':'UTF-8'} {else if}{$val['service_name']|escape:'htmlall':'UTF-8'} {/if}({$val['service_code']|escape:'htmlall':'UTF-8'}) {if $val['description']}<a href="#" class="t_c" data="{$val['service_code']|escape:'htmlall':'UTF-8'}" >{"T&C"|escape:'htmlall':'UTF-8'}</a>{/if}</label>
<input type="hidden" id="{$val['service_code']|escape:'htmlall':'UTF-8'}" value="{$val['description']|escape:'htmlall':'UTF-8'}" ></p>

	{/foreach}
  </form>	
<div class="email-popup-con">
<div class="email-popup-inner">
         <div class="email-popup-inner-con">
          <div class="message-overlay-con">
                        
                        <span class="nothanks">X</span>
                   </div>
              <div class="email-popup-img-con">
                 
                  
              </div>
             <div id="main-id"></div>
         </div>
         </div>
    </div>