<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class StripeInstall extends Stripe
{
    public function __construct()
    {
        parent::__construct();
    }
        
    protected function createTables()
    {
        if (!Db::getInstance()->Execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'stripe_orders` (
				`id_stripe_order` int(10) unsigned NOT NULL,
				`id_transaction` varchar(255) NOT NULL,
				PRIMARY KEY(`id_stripe_order`)
				) ENGINE ='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')) {
            return false;
        }
        return true;
    }

    protected function addTabs()
    {
        $parent_tab = new Tab();
        foreach (Language::getLanguages() as $language) {
            $parent_tab->name[$language['id_lang']] = $this->l('Stripe');
        }
        $parent_tab->class_name = 'StripeAdminMain';
        $parent_tab->id_parent = 0;
        $parent_tab->module = $this->name;

        $stripe_tab = new Tab();
        foreach (Language::getLanguages() as $language) {
            $stripe_tab->name[$language['id_lang']] = $this->l('Handle Stripe Orders');
        }
        $stripe_tab->class_name = 'StripeAdminOrder';
        $stripe_tab->id_parent = $parent_tab->id;
        $stripe_tab->module = $this->name;

        return $parent_tab->add() && $stripe_tab->add();
    }
}
