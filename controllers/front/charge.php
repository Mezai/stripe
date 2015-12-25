<?php


class StripeChargeModuleFrontController extends ModuleFrontController
{
	public function postProcess()
	{
		$cart = $this->context->cart;

		$customer = new Customer((int)$cart->id_customer);

		\Stripe\Stripe::setApiKey((String)Configuration::get('STRIPE_SECRET_KEY'));
		
		$token = Tools::getValue('stripeToken');

		$customer = \Stripe\Customer::create(array(
			'email' => $customer->email,
			'card'  => $token
  		));

		$charge =  \Stripe\Charge::create(array(
			'customer' => $customer->id,
			'amount'   => (int)($cart->getOrderTotal(true, CART::BOTH) * 100),
			'currency' => 'sek'
	  ));
	}
}

