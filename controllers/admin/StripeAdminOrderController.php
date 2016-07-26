<?php
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
            if (Tools::isSubmit('credit_stripe')) {
                Stripe::setApiKey(Configuration::get('STRIPE_SECRET_KEY'));
                
                Refund::create(
                    array(
                        'charge' => Tools::getValue('stripe_refund_transaction')
                    )
                );
            }
            $this->displayInformation('Successfully refunded transaction');
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
