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

jQuery(document).ready(function () {

		$("#configbutton").click(function () {		
			
			var STATUS = '';
			var token_check =$('#token_check').val();
			$.ajax({			
						type: 'POST',			
						url: "",			
						cache: false,			
						dataType: 'json',			
						data: 'ajax=true&action=AjaxSaveConifgration&token_check='+token_check,

						beforeSend: function () {

							$(".loader").remove();				
							$(".panel-footer").append('<div class="loader"></div>');			
						},			
						success: function (data) {	

							$(".loader").remove();

							var response = JSON.parse(JSON.stringify(data));

							if (response.status == 'true') {	

								$.growl.notice({ message: response.success });	

							} else if (response.status == 'hold') {		

								$.growl.warning({ message: response.warning });	

							} else {			

								$.growl.error({ message: response.error });	

							}							
						}		
					})	
				});

		$("#healthcheck").click(function () {

			var STATUS = '';	

			var token_check =$('#token_check').val();		
			$.ajax({			
				type: 'POST',			
				url: "",			
				dataType: 'json',			
				data: 'ajax=true&action=AjaxGetAPIResponse&token_check='+token_check,			
				beforeSend: function () {				
					$(".loader").remove();				
					$(".panel-footer").append('<div class="loader"></div>');			
				},			
				success: function (data) {				
					$(".loader").remove();				
					var response = JSON.parse(JSON.stringify(data));				
					if (response.status == 'true') {					
						$.growl.notice({ message: response.success });				
					} else if (response.status == 'hold') {					
						$.growl.warning({ message: response.warning });				
					} else {					
						$.growl.error({ message: response.error });				
					}			
				}		
			})	
		});	

		$("#configuration_form").submit(function (e) {	
			e.preventDefault();		
			var TRNID = $("#T__ID").val();				
			var AMOUNT = $("#Refund_Amount").val();	
			var token_check =$('#token_check').val();
			if (TRNID.trim() != '') {			
				var form = $(this);			
				$(".zoodpay_trn").remove();			
				$.ajax({				
					type: 'POST',				
					url: "",				
					data: form.serialize(),				
					beforeSend: function () {					
						$(".loader").remove();					
						$("#T__ID").after('<div class="loader"></div>');				
					},				
					success: function (data) {					
						$(".loader").remove();
						var response = JSON.parse(data);
						
							if (response.status !== 'fail') {	

								$.growl.notice({ message: response.message });	

							} else if (response.status == 'fail') {		

								$.growl.error({ message: response.message });	

							} 							
					}			
				})		
			} else {			
				$(".zoodpay_trn").remove();						
				$(".tabs").prepend('<div class="bootstrap zoodpay_trn"><div class="module_error alert alert-danger"><button type="button" class="close" data-dismiss="alert">×</button>Transtion ID is required.</div></div>');		
			}	
		});	

		$(".migration-tab").click(function () {		
			var thisID = $(this).attr('data');		
			$(".section-cls").css("display", "none");		
			$("#section-shape-" + thisID).css("display", "block");	
		});	

		jQuery('#T__ID').on('input propertychange paste', function () {	
			var Thisval = $(this).val();
			var token_check =$('#token_check').val();
			$.ajax({			
				type: 'POST',			
				url: "",			
				data: 'ajax=true&action=GETREFUND&ThisVal=' + Thisval + '&token_check='+token_check,			
				beforeSend: function () {				
					$("#loader").remove();				
					$("#T__ID").after('<div class="loader" id="loader"></div>');			
				},			
				success: function (data) {				
					$("#loader").remove();				
					$("#poduct_data").html(data);			
				}		
			})	
		});

		
});