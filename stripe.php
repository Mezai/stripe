<?php
/**
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2016 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__).'/vendor/autoload.php');

class stripe extends PaymentModule
{
    private $post_errors = array();
    private $html = '';

    public $image;
    public $image_name;

    public function __construct()
    {
        $this->name = 'stripe';
        $this->version = '1.0.0';
        $this->author = 'JET';
        $this->bootstrap = true;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->tab = 'payments_gateways';
    

        parent::__construct();
        $this->displayName = $this->l('Stripe payment gateway');
        $this->description = $this->l('Lets you use the Stripe checkout service');

        $this->image_name = 'stripe_logo';
        $this->image = Tools::getMediaServer($this->name)._MODULE_DIR_.$this->name.'/views/img/'.$this->image_name.'.'.Configuration::get('STRIPE_IMAGE_EXT');
    }

    /**
     * Install module
     * 
     * @return bool success
     */
    public function install()
    {
        require_once(dirname(__FILE__).'/stripe_install.php');

        $stripe_install = new StripeInstall();

        foreach (scandir(_PS_MODULE_DIR_.$this->name.'/views/img/') as $file) {
            if (in_array($file, array('stripe_logo.jpg', 'stripe_logo.gif', 'stripe_logo.png'))) {
                Configuration::updateGlobalValue('STRIPE_IMAGE_EXT', substr($file, strrpos($file, '.') + 1));
            }
        }

        return parent::install()
        && $this->registerHook('payment')
        && $this->registerHook('paymentReturn')
        && $this->registerHook('header')
        && $this->registerHook('backOfficeHeader')
        && $stripe_install->addTabs()
        && $stripe_install->createTables();
    }


    /**
     * Uninstall module
     * 
     * @return bool success
     */
    public function uninstall()
    {
        $tab = new Tab(Tab::getIdFromClassName('StripeAdminOrder'));
        return parent::uninstall()
        && Configuration::deleteByName('STRIPE_SECRET_KEY')
        && Configuration::deleteByName('STRIPE_PUBLISHABLE_KEY')
        && Configuration::deleteByName('STRIPE_MODE')
        && Configuration::deleteByName('STRIPE_SHIPPING_ADDRESS')
        && Configuration::deleteByName('STRIPE_ALIPAY')
        && Configuration::deleteByName('STRIPE_BITCOIN')
        && Configuration::deleteByName('STRIPE_IMAGE_EXT')
        && $tab->delete();
    }

    /**
     * HookHeader
     * 
     * @return void
     */
    public function hookHeader()
    {
        $this->context->controller->addJS("https://checkout.stripe.com/checkout.js");
    }


    /**
     * Hook payment
     * 
     * @param  array $params
     * @return tpl
     */
    public function hookPayment($params)
    {
        if (!$this->active) {
            return;
        }
        
        $cart = $this->context->cart;
        $currency = new Currency((int)$cart->id_currency);


        $stripe = array(
            'secret_key' => (String)Configuration::get('STRIPE_SECRET_KEY'),
            'publishable_key' => (String)Configuration::get('STRIPE_PUBLISHABLE_KEY')
            );

        \Stripe\Stripe::setApiKey((String)Configuration::get('STRIPE_SECRET_KEY'));

        $desc = '';
        foreach ($cart->getProducts() as $product) {
            $desc .= $product['name'];
        }
        

        $this->context->smarty->assign(array(
            'stripe_pk_key' => $stripe['publishable_key'],
            'shop_name' => $this->context->shop->name,
            'stripe_currency' => Tools::strtolower($currency->iso_code),
            'stripe_desc' => $desc,
            'stripe_shipping' => ((int)Configuration::get('STRIPE_SHIPPING_ADDRESS') === 1) ? 'true' : 'false',
            'stripe_billing' => ((int)Configuration::get('STRIPE_BILLING_ADDRESS') === 1) ? 'true' : 'false',
            'stripe_alipay' => ((int)Configuration::get('STRIPE_ALIPAY') === 1) ? 'true' : 'false',
            'stripe_bitcoin' => ((int)Configuration::get('STRIPE_BITCOIN') === 1) ? 'true' : 'false',
            'stripe_email' => $this->context->customer->email,
            'stripe_remember_me' => ((int)Configuration::get('STRIPE_REMEMBER_ME') === 1) ? 'true' : 'false',
            'stripe_logo' => (file_exists(_PS_MODULE_DIR_.$this->name.'/views/img/'.$this->image_name.'.'.Configuration::get('STRIPE_IMAGE_EXT'))) ? $this->context->link->protocol_content.$this->image : '',
            'stripe_zip_code' => ((int)Configuration::get('STRIPE_VALIDATE_ZIP') === 1) ? 'true' : 'false',
            'stripe_label' => Configuration::get('STRIPE_PANEL_LABEL'),
            'total_amount' => (int)($cart->getOrderTotal(true, CART::BOTH) * 100),
            'this_path_img' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/views/img/'
            ));



        return $this->display(__FILE__, 'payment.tpl');
    }


    /**
     * hookDisplayPaymentReturn
     * 
     * @return tpl
     */
    public function hookDisplayPaymentReturn()
    {
        if (!$this->active) {
            return;
        }

        return $this->display(__FILE__, 'payment_return.tpl');
    }

    /**
     * Delete logo images
     * 
     * @return void
     */
    private function deleteImages()
    {
        $images = array(
            'stripe_logo.png',
            'stripe_logo.jpg',
            'stripe_logo.jpeg',
            'stripe_logo.gif',
        );

        foreach (scandir(_PS_MODULE_DIR_.$this->name.'/views/img/') as $file) {
            if (in_array($file, $images)) {
                unlink(_PS_MODULE_DIR_.$this->name.'/views/img/'.$file);
            }
        }
    }

    /**
     * Update module configuration
     *
     * 
     * @return void
     */
    public function postProcess()
    {
        if (Tools::isSubmit('STRIPE_DELETE_IMAGE')) {
            $this->deleteImages();
        }

        if (Tools::isSubmit('saveBtn')) {
            Configuration::updateValue('STRIPE_SECRET_KEY', Tools::getValue('STRIPE_SECRET_KEY'));
            Configuration::updateValue('STRIPE_PUBLISHABLE_KEY', Tools::getValue('STRIPE_PUBLISHABLE_KEY'));
            Configuration::updateValue('STRIPE_MODE', Tools::getValue('STRIPE_MODE'));
            Configuration::updateValue('STRIPE_ALIPAY', Tools::getValue('STRIPE_ALIPAY'));
            Configuration::updateValue('STRIPE_BITCOIN', Tools::getValue('STRIPE_BITCOIN'));
            Configuration::updateValue('STRIPE_SHIPPING_ADDRESS', Tools::getValue('STRIPE_SHIPPING_ADDRESS'));
            Configuration::updateValue('STRIPE_BILLING_ADDRESS', Tools::getValue('STRIPE_BILLING_ADDRESS'));
            Configuration::updateValue('STRIPE_REMEMBER_ME', Tools::getValue('STRIPE_REMEMBER_ME'));
            Configuration::updateValue('STRIPE_VALIDATE_ZIP', Tools::getValue('STRIPE_VALIDATE_ZIP'));
            Configuration::updateValue('STRIPE_PANEL_LABEL', Tools::getValue('STRIPE_PANEL_LABEL'));

            if (isset($_FILES['STRIPE_IMAGE'])) {
                Configuration::updateValue('STRIPE_IMAGE_EXT', substr($_FILES['STRIPE_IMAGE']['name'], strrpos($_FILES['STRIPE_IMAGE']['name'], '.') + 1));
            }
            $this->html .= $this->displayConfirmation($this->l('Settings updated'));
        }
    }

    /**
     * Validate user input
     * 
     * @return void
     */
    public function postValidation()
    {
        if (Tools::isSubmit('saveBtn')) {
            if (!Tools::getValue('STRIPE_SECRET_KEY')) {
                $this->post_errors[] = $this->l('You need to set a secret from Stripe');
            }

            if (!Tools::getValue('STRIPE_PUBLISHABLE_KEY')) {
                $this->post_errors[] = $this->l('You need to set a publishable key from Stripe');
            }

            if (isset($_FILES['STRIPE_IMAGE']) && isset($_FILES['STRIPE_IMAGE']['tmp_name']) && !empty($_FILES['STRIPE_IMAGE']['tmp_name'])) {
                if (!in_array($_FILES['STRIPE_IMAGE']['type'], array('image/jpeg', 'image/png', 'image/gif'))) {
                    $this->post_errors[] = $this->l('Invalid file format please upload a jpg, png or gif file');
                }

                if ($error = ImageManager::validateUpload($_FILES['STRIPE_IMAGE'], Tools::convertBytes(ini_get('upload_max_filesize')))) {
                    $this->post_errors[] = $error;
                }

                if (!move_uploaded_file($_FILES['STRIPE_IMAGE']['tmp_name'], _PS_MODULE_DIR_.$this->name.'/views/img/'.$this->image_name.'.'.Configuration::get('STRIPE_IMAGE_EXT'))) {
                    $this->post_errors[] = $this->l('File upload error');
                }
            }
        }
    }


    /**
     * Show module config page
     * 
     * @return $html content
     */
    public function getContent()
    {
        if (Tools::isSubmit('saveBtn')) {
            $this->postValidation();
            if (!count($this->post_errors)) {
                $this->postProcess();
            } else {
                foreach ($this->post_errors as $error) {
                    $this->html = $this->displayError($error);
                }
            }
        } else {
            $this->html .= '<br />';
        }
        $this->html .= $this->renderForm();

        return $this->html;
    }

    /**
     * Render backoffice form
     * 
     * @return a tpl fetched
     */
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
                    'type' => 'file',
                    'label' => $this->l('Image to display in checkout'),
                    'name' => 'STRIPE_IMAGE',
                    'desc' => $this->l('The recommended minimum size is 128x128px'),
                    'thumb' => (file_exists(_PS_MODULE_DIR_.$this->name.'/views/img/'.$this->image_name.'.'.Configuration::get('STRIPE_IMAGE_EXT'))) ? $this->context->link->protocol_content.$this->image : __PS_BASE_URI__.'/img/questionmark.png'
                ),
                array(
                    'type' => 'html',
                    'name' => 'STRIPE_DELETE_IMAGE',
                    'html_content' => '<button type="submit" class="btn btn-default" name="STRIPE_DELETE_IMAGE">Delete image</button>'
                ),
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
                    'type' => 'text',
                    'label' => $this->l('Panel label'),
                    'desc' => $this->l('The label of the payment button in the Checkout form. If left blank "Pay" will be displayed.'),
                    'class' => 'fixed-width-xxl',
                    'name' => 'STRIPE_PANEL_LABEL',
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Alipay'),
                    'desc' => $this->l('Select to allow payments with Alipay'),
                    'name' => 'STRIPE_ALIPAY',
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                            ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Bitcoin'),
                    'desc' => $this->l('Select to allow payments with Bitcoin'),
                    'name' => 'STRIPE_BITCOIN',
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                            ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Shipping address'),
                    'desc' => $this->l('Specify whether Stripe should collect the users shipping address'),
                    'name' => 'STRIPE_SHIPPING_ADDRESS',
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                            ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Billing address'),
                    'desc' => $this->l('Specify whether Stripe should collect the users billing address'),
                    'name' => 'STRIPE_BILLING_ADDRESS',
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                            ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Remember me'),
                    'desc' => $this->l('Specify whether to include the option to "Remember Me"'),
                    'name' => 'STRIPE_REMEMBER_ME',
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                            ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Validate zip code'),
                    'desc' => $this->l('Specify whether Stripe should validate zip codes'),
                    'name' => 'STRIPE_VALIDATE_ZIP',
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                            ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
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

    /**
     * getConfigFieldsValues
     * 
     * @return array config values
     */
    public function getConfigFieldsValues()
    {
        return array(
            'STRIPE_SECRET_KEY' => Tools::getValue('STRIPE_SECRET_KEY', Configuration::get('STRIPE_SECRET_KEY')),
            'STRIPE_PUBLISHABLE_KEY' => Tools::getValue('STRIPE_PUBLISHABLE_KEY', Configuration::get('STRIPE_PUBLISHABLE_KEY')),
            'STRIPE_MODE' => Tools::getValue('STRIPE_MODE', Configuration::get('STRIPE_MODE')),
            'STRIPE_BITCOIN' => Tools::getValue('STRIPE_BITCOIN', Configuration::get('STRIPE_BITCOIN')),
            'STRIPE_ALIPAY' => Tools::getValue('STRIPE_ALIPAY', Configuration::get('STRIPE_ALIPAY')),
            'STRIPE_SHIPPING_ADDRESS' => Tools::getValue('STRIPE_SHIPPING_ADDRESS', Configuration::get('STRIPE_SHIPPING_ADDRESS')),
            'STRIPE_BILLING_ADDRESS' => Tools::getValue('STRIPE_BILLING_ADDRESS', Configuration::get('STRIPE_BILLING_ADDRESS')),
            'STRIPE_REMEMBER_ME' => Tools::getValue('STRIPE_REMEMBER_ME', Configuration::get('STRIPE_REMEMBER_ME')),
            'STRIPE_VALIDATE_ZIP' => Tools::getValue('STRIPE_VALIDATE_ZIP', Configuration::get('STRIPE_VALIDATE_ZIP')),
            'STRIPE_PANEL_LABEL' => Tools::getValue('STRIPE_PANEL_LABEL', Configuration::get('STRIPE_PANEL_LABEL')),
        );
    }
}
