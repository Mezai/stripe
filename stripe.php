<?php
/**
* 2007-2015 PrestaShop
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_'))
	exit;


require_once(dirname(__FILE__).'/vendor/autoload.php');

class Stripe extends PaymentModule
{
	private $post_errors = array();
	private $html = '';

	public function __construct()
	{
		$this->name = 'Stripe';
		$this->version = '1.0.0';
		$this->author = 'JET';
		$this->bootstrap = true;
		$this->currencies = true;
		$this->currencies_mode = 'checkbox';
		$this->tab = 'payments_gateways';
	

		parent::__construct();
		$this->displayName = $this->l('Stripe payment gateway');
		$this->description = $this->l('Lets you use the Stripe checkout service');
	}


	public function install()
	{

		require_once(dirname(__FILE__).'/stripe_install.php');

		$stripe_install = new StripeInstall();

		$stripe_install->createTables();

		return parent::install()
		&& $this->registerHook('payment')
		&& $this->registerHook('paymentReturn')
		&& $this->registerHook('header')
		&& $this->registerHook('backOfficeHeader');

	}

	public function uninstall()
	{
		return parent::uninstall();
	}

	
	public function hookPayment()
	{
		if (!$this->active)
			return;
		
		$cart = $this->context->cart;

		 $stripe = array(
		 	'secret_key' => (String)Configuration::get('STRIPE_SECRET_KEY'),
		 	'publishable_key' => (String)Configuration::get('STRIPE_PUBLISHABLE_KEY')
		 	);

		\Stripe\Stripe::setApiKey((String)Configuration::get('STRIPE_SECRET_KEY'));		 
		
		 $this->context->smarty->assign(array(
		 	'stripe_key' => $stripe['publishable_key'],
		 	'currency' => Tools::strtolower($this->context->currency->iso_code),
		 	'total_amount' => (int)($cart->getOrderTotal(true, CART::BOTH) * 100)
		 	));

		 return $this->display(__FILE__, 'payment.tpl');

	}

	public function postProcess()
	{
		if (Tools::isSubmit('saveBtn'))
		{
			Configuration::updateValue('STRIPE_SECRET_KEY', Tools::getValue('STRIPE_SECRET_KEY'));
			Configuration::updateValue('STRIPE_PUBLISHABLE_KEY', Tools::getValue('STRIPE_PUBLISHABLE_KEY'));
			Configuration::updateValue('STRIPE_MODE', Tools::getValue('STRIPE_MODE'));
		}
	}

	public function postValidation()
	{
		if (Tools::isSubmit('saveBtn'))
		{
			if (!Tools::getValue('STRIPE_SECRET_KEY'))
				$this->post_errors[] = $this->l('You need to set a secret from Stripe');

			if (!Tools::getValue('STRIPE_PUBLISHABLE_KEY'))
				$this->post_errors[] = $this->l('You need to set a publishable key from Stripe');
		}
	}

	public function getContent()
	{
		if (Tools::isSubmit('saveBtn'))
		{
			$this->postValidation();
			if (!count($this->post_errors))
				$this->postProcess();

			else

				foreach ($this->postErrors as $error) {
					$this->html = $this->displayError($error);
				}
		}
		else
			$this->html .= '<br />';
			$this->html .= $this->renderForm();

		return $this->html;
	}

	public function renderForm()
	{
		$fields_form = array(
		'form' => array(
			'legend' => array(
				'title' => $this->l('Configure Stripe'),
				'icon' => 'icon-cogs'
				),
			'input' => array(
				array(
					'type' => 'text',
					'label' => $this->l('Secret key'),
					'desc' => $this->l('Secret key from Stripe'),
					'required' => true,
					'name' => 'STRIPE_SECRET_KEY',
					'class' => 'fixed-width-xxl',
				),
				array(
					'type' => 'text',
					'label' => $this->l('Publishable key'),
					'desc' => $this->l('Publishable key from Stripe'),
					'class' => 'fixed-width-xxl',
					'name' => 'STRIPE_PUBLISHABLE_KEY',
					'required' => true
				),
				array(
					'type' => 'switch',
					'label' => $this->l('Test mode'),
					'desc' => $this->l('Select test or live mode'),
					'name' => 'STRIPE_MODE',
					'values' => array(
						array(
							'id' => 'active_on',
							'value' => 1,
							'label' => $this->l('Live')
							),
						array(
							'id' => 'active_off',
							'value' => 0,
							'label' => $this->l('Test')
							)
						),
					),
				),
				'submit' => array(
					'title' => $this->l('Save'),
					'class' => 'button pull-right'
					)
				),
			);


		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
		? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();
		$helper->id = (int)Tools::getValue('id_carrier');
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'saveBtn';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).
		'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
			);
		return $helper->generateForm(array($fields_form));
	}

	public function getConfigFieldsValues()
	{
		return array(
			'STRIPE_SECRET_KEY' => Tools::getValue('STRIPE_SECRET_KEY', Configuration::get('STRIPE_SECRET_KEY')),
			'STRIPE_PUBLISHABLE_KEY' => Tools::getValue('STRIPE_PUBLISHABLE_KEY', Configuration::get('STRIPE_PUBLISHABLE_KEY')),
			'STRIPE_MODE' => Tools::getValue('STRIPE_MODE', Configuration::get('STRIPE_MODE'))
			);
	}
}