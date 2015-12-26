<?php


class StripeChargeModuleFrontController extends ModuleFrontController
{
	public function initContent()
	{

			$cart = $this->context->cart;
			$error_page = $this->context->link->getModuleLink('stripe', 'error');
			$customer = new Customer((int)$cart->id_customer);
			$amount = $cart->getOrderTotal(true, Cart::BOTH);
			$currency = new Currency((int)$cart->id_currency);

		try {	
			
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

		  	$extra = array(
		  		'transaction_id' => $charge['id']
		  		);

			$this->module->validateOrder($cart->id, Configuration::get('PS_OS_PAYMENT'), $amount, $this->module->displayName, $extra['transaction_id'],
			array(), (int)$currency->id, false, $this->context->cart->secure_key);

			Tools::redirect('index.php?controller=order-confirmation&id_cart='.
						$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);


		} catch(\Stripe\Error\Card $e) {

		  $body = $e->getJsonBody();
		  $err  = $body['error'];

		  Logger::addLog('Stripe module: declined transaction. Message :'. $err['message'] .'Param:'. $err['param'] .'Code:'. $err['code'] .'Type:'. $err['type'] .'Status:'. $e->getHttpStatus());
		  Tools::redirect($error_page);

		} catch (\Stripe\Error\RateLimit $e) {
			Logger::addLog('Stripe module: too many requests executed response message :'.$e->getMessage());
			Tools::redirect($error_page);

		} catch (\Stripe\Error\InvalidRequest $e) {
			Logger::addLog('Stripe module: invalid Api request response message'.$e->getMessage());
			Tools::redirect($error_page);

		} catch (\Stripe\Error\Authentication $e) {
		  	Logger::addLog('Stripe module: autentication failure check api credentials. Response message:'.$e->getMessage());
		  	Tools::redirect($error_page);

		} catch (\Stripe\Error\ApiConnection $e) {
			Logger::addLog('Stripe module: network communication failed response message:'.$e->getMessage());
			Tools::redirect($error_page);

		} catch (\Stripe\Error\Base $e) {
		  	Logger::addLog('Stripe module: error in payment message :'.$e->getMessage());
			Tools::redirect($error_page);

		} catch (Exception $e) {
		  	Logger::addLog('Stripe module error message: '.$e->getMessage());
			Tools::redirect($error_page);

		}
		

	}
}

