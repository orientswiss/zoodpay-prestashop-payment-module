/*
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
 * @author 2007-2021 ZoodPay
 * @copyright ZoodPay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
 
jQuery("body").delegate('.t_c', 'click' , function(){
			
			var TData = jQuery(this).attr('data');
			var thidData = jQuery("#"+TData).val();
			jQuery("#main-id").html(thidData);
			jQuery('.email-popup-con').fadeIn();
		})

			jQuery('.nothanks,.message-overlay-con').click(function() {
			jQuery('.email-popup-con').fadeOut();
});

function getUrlVars()
{
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}
var error = getUrlVars()["error"];

if(error && error != ''){

	alert(decodeURIComponent(error));
}
