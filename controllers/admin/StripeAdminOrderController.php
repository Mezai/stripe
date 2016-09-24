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

use Stripe\Stripe;
use Stripe\Refund;

class StripeAdminOrderController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'stripe_orders';
        $this->lang = false;
        $this->identifier = 'id_stripe_order';
        $this->list_no_link = true;
        $this->_default_pagination = 10;
        $this->_pagination = array(10,50,100,300,1000);
        $this->toolbar_title = $this->l('Stripe Orders');
        $this->fields_list = array(
            'id_stripe_order' => array('title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'),
            'id_transaction' => array('title' => $this->l('Transaction Id')),
        );
        $this->fields_options = array(
            'refund' => array(
                'title' => $this->l('Refund order'),
                'description' => $this->l('This function will refund the order'),
                'icon' => 'icon-user',
                'fields' => array(
                    'STRIPE_REFUND_ID' => array(
                        'title' => $this->l('Refund order'),
                        'desc' => $this->l('Fill in the charge id to refund the order'),
                        'validation' => 'isUnsignedInt',
                        'class' => 'fixed-width-xxl',
                        'type' => 'text',
                    ),
                ),
                
                'submit' => array(
                    'title' => $this->l('Process refund'),
                    'class' => 'button pull-right',
                    'name' => 'stripe_refund',
                ),
            ),
        );
        parent::__construct();
    }
    public function renderForm()
    {
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Stripe'),
                'icon' => 'icon-envelope-alt',
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('ID'),
                    'name' => 'id_stripe_order',
                    'required' => true,
                    'lang' => false,
                    'col' => 4,
                    'hint' => $this->l('Stripe order id'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Transaction id'),
                    'name' => 'id_transaction',
                    'required' => false,
                    'col' => 4,
                    'hint' => $this->l('Stripe transaction id'),
                ),
            ),
        );
        return parent::renderForm();
    }

    public function postProcess()
    {
        try {
            if (Tools::isSubmit('stripe_refund')) {
                Stripe::setApiKey(Configuration::get('STRIPE_SECRET_KEY'));
                
                Refund::create(
                    array(
                        'charge' => Tools::getValue('STRIPE_REFUND_ID')
                    )
                );

                $this->displayInformation('Successfully refunded transaction');
            }
        } catch (Exception $e) {
            $this->displayWarning('Credit failed with message : '.$e->getMessage(). 'and error code : '.$e->getCode());
        }
    }

    public function initPageHeaderToolbar()
    {
        $this->initToolbar();
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['module_link'] =  array(
                'href' => 'http://link.com',
                'desc' => $this->l('Go to module', null, null, false),
                'icon' => 'process-icon-modules-list',
            );
        }
        return parent::initPageHeaderToolbar();
    }
}
