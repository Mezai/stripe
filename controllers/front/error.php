<?php


class StripeErrorModuleFrontController extends ModuleFrontController
{
	public function initContent()
	{
		$this->display_column_left = true;
		parent::initContent();

		$this->setTemplate('error.tpl');
	}
}