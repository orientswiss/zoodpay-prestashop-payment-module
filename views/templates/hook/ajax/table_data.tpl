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



<table class="table" id="orderProductsTable" data-currency-precision="2">
	<thead>
		<tr>
			<th><strong>{l s='Poduct Name' mod='Poduct Name'}</strong></th>
			<th><strong>{l s='Quantity' mod='Quantity'}</strong></th>
			<th><strong>{l s='Total Price' mod='Total Price'}</strong></th>
			<th><strong>{l s='Refund' mod='Refund'}</strong></th>
			<th><strong>{l s='Action' mod='Action'}</strong></th>
		</tr>
	</thead>
	<tbody>

		{foreach $ajax_data as $val }

			<tr id="orderProduct_{$val['id_order_detail']}" class="cellProduct" >
				<td class="cellProductName">{$val['product_name']}</td>
				<td class="">
					<input type="text" id="cancel_product_quantity_{$val['id_order_detail']}" name="quantity_product_{$val['id_order_detail']}" max="{$val['product_quantity']}"
					            class="refund-quantity form-control" value="{$val['product_quantity']}">
				</td>
				<td class="">{(float)$val['total_price_tax_incl']}</td>

				<td class="">
				<input type="text" id="product_{$val['id_order_detail']}" name="amount_product_{$val['id_order_detail']}" value="{(float)$val['total_price_tax_incl']}" class="ammount" >

				</td>

				<td class="">
				<input type="checkbox" name="refund_action[]" value="{$val['id_order_detail']}" class="select_prod" >

				</td>
			</tr>

		{/foreach}

	</tbody>
</table>