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