{*
* 2007-2019 PrestaShop
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
*  @copyright 2007-2019 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="alert alert-info">
	<img src="../modules/orderpost/logo.png" style="float:left; margin-right:15px;" height="60">
	<h3><i class="icon icon-bell-o"></i> {l s='Order Status POST request' mod='orderpost'}</h3>
	<p>{l s='This module for Prestashop makes POST request after order status changed to your API URL with order data' mod='orderpost'}</p>
	<p>{l s='You should have an existing API URL to receive POST request' mod='orderpost'}</p>
	<p>
		{l s='Usage:' mod='orderpost'}
	<ul>
		<li>{l s='Fill your API URL' mod='orderpost'}</a></li>
		<li>{l s='Set the minimum order sum for sending POST request (-1 for free orders)' mod='orderpost'}</a></li>
		<li>{l s='Select data you want to send' mod='orderpost'}</a></li>
		<li>{l s='var_dump $_POST on API side to catch correct keys' mod='orderpost'}</a></li>
	</ul>
	</p>
	<p>
		{l s='If you need additional data for sending - feel free to ' mod='orderpost'}
		<b><a href="https://tobiksoft.com/content/8-contact-us" target="_blank">{l s='CONTACT US' mod='livechat'}</a></b>
		{l s='. We will be happy to help you. Or you can modify module yourself.' mod='orderpost'}
	</p>
</div>
