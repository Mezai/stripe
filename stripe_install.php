<?php

if (!defined('_PS_VERSION_'))
	exit;

class StripeInstall
{
	public function createTables()
	{
		if (!Db::getInstance()->Execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'stripe_orders` (
				`id_order` int(10) unsigned NOT NULL,
				`id_transaction` varchar(255) NOT NULL,
				PRIMARY KEY(`id_order`)
				) ENGINE ='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8'))
		return false;
	}
}