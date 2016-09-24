{*
* 2007-2016 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @version  Release: $Revision: 6844 $
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<p class="payment_module" id="stripePayment">
	<a href="" title="{l s='Pay with Stripe' mod='stripe'}">
		<img src="{$this_path_img|escape:'htmlall':'UTF-8'}logo.png" alt="{l s='Pay with Stripe' mod='stripe'}"/>

		{l s='Pay with Stripe' mod='stripe'}
	</a>
</p>
<form action="{$link->getModuleLink('stripe', 'charge')|escape:'htmlall':'UTF-8'}" method="post" id="stripe_charge"></form>

<script type="text/javascript">
	var $form = $('#stripe_charge');
	var handler = StripeCheckout.configure({
		key: "{$stripe_pk_key|escape:'htmlall':'UTF-8'}",
		image: '',
		locale: 'auto',
		token: function(token) {

			$form.append($('<input type="hidden" name="stripeToken"/>').val(token.id));
			$form.submit();
		}

	});

	$('#stripePayment').on('click', function(e) {
		handler.open({
			name: "{$shop_name|escape:'htmlall':'UTF-8'}",
			currency: "{$stripe_currency|escape:'htmlall':'UTF-8'}",
			description: "{$stripe_desc|escape:'htmlall':'UTF-8'}",
			amount: "{$total_amount|intval|escape:'htmlall':'UTF-8'}"
		});
		e.preventDefault();
	});

	$(window).on('popstate', function() {
		handler.close();
	});

</script>