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

{extends file='page.tpl'}
  {block name='page_content_container' prepend}
    <section id="content-hook_order_confirmation" class="card">
      <div class="card-block">
        <div class="row">
          <div class="col-md-12">
            {block name='order_confirmation_header'}
              <h3 class="h1 card-title">
                {l s='Your order is not confirmed' mod='zoodpay'}
              </h3>
            {/block}
            </div>
        </div>
      </div>
    </section>
{/block}
