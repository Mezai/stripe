<?php

class StripeAdminOrderController extends ModuleAdminController {
    public function __construct() {
        $this->bootstrap = true;
        $this->table = 'stripe_orders';
        $this->lang = false;
        $this->list_no_link = true;
        $this->_default_pagination = 10;
        $this->_pagination = array(10,50,100,300,1000);
        $this->toolbar_title = $this->l('Stripe Orders');
        $this->fields_list = array(
            'id_stripe_order' => array('title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'),
            'id_transaction' => array('title' => $this->l('Transaction Id')),
        );
    }
    public function renderForm() {
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

    public function postProcess() {
    // Handle credits and so on 
    }

    public function initPageHeaderToolbar() {
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
