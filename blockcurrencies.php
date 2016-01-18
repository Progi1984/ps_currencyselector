<?php
/*
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

if (!defined('_PS_VERSION_'))
	exit;

class BlockCurrencies extends Module implements WidgetInterface
{
	public function __construct()
	{
		$this->name = 'blockcurrencies';
		$this->tab = 'front_office_features';
		$this->version = '2.0.0';
		$this->author = 'PrestaShop';
		$this->need_instance = 0;

		parent::__construct();

		$this->displayName = $this->l('Currency block');
		$this->description = $this->l('Adds a block allowing customers to choose their preferred shopping currency.');
		$this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
	}

	public function getWidgetVariables($hookName, array $configuration)
	{
		$current_currency = null;
		$serializer = new Adapter_ObjectSerializer;
		$currencies = array_map(
			function ($currency) use ($serializer, &$current_currency) {
				$currencyArray = $serializer->toArray($currency);

				// serializer doesn't see 'sign' because it is not a regular
				// ObjectModel field.
				$currencyArray['sign'] = $currency->sign;

				$url = $this->context->link->getLanguageLink(
					$this->context->language->id
				);

				$extraParams = [
					'SubmitCurrency' => 1,
					'id_currency' => $currency->id
				];

				$partialQueryString = http_build_query($extraParams);
				$separator = empty(parse_url($url)['query']) ? '?' : '&';

				$url .= $separator . $partialQueryString;

				$currencyArray['url'] = $url;

				if ($currency->id === $this->context->currency->id) {
					$currencyArray['current'] = true;
					$current_currency = $currencyArray;
				} else {
					$currencyArray['current'] = false;
				}

				return $currencyArray;
			},
			Currency::getCurrencies(true, true)
		);

		return [
			'currencies' => $currencies,
			'current_currency' => $current_currency
		];
	}

	public function renderWidget($hookName, array $configuration)
	{
		if (Configuration::get('PS_CATALOG_MODE'))
			return '';

		if (!Currency::isMultiCurrencyActivated())
			return '';

		$this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
		return $this->display(__FILE__, 'blockcurrencies.tpl');
	}
}
