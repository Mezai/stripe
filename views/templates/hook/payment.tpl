<p class="payment_module">
	<form action="{$link->getModuleLink('stripe', 'charge')|escape:'htmlall':'UTF-8'}" method="post">
  		<script src="https://checkout.stripe.com/checkout.js" class="stripe-button"
          data-key="{$stripe_key|escape:'htmlall':'UTF-8'}"
          data-description="Access for a year"
          data-amount="{$total_amount|intval}"
          data-currency="{$currency}"
          data-locale="auto"></script>
	
</p>