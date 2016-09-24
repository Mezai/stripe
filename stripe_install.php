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
