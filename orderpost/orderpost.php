<?php
/**
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
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class orderpost extends Module
{

    /**
     * @var string
     */
    protected $html = '';

    /**
     * @var bool
     */
    protected $config_form = false;

    /**
     * orderpost constructor.
     */
    public function __construct()
    {
        $this->name = 'orderpost';
        $this->tab = 'checkout';
        $this->version = '1.0.0';
        $this->author = 'Oleh Vasylyev';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Order Status POST request');
        $this->description = $this->l('This module for Prestashop makes POST request after order status changed to your API URL with order data');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('orderpost_post_url', false);
        Configuration::updateValue('orderpost_order_sum', false);
        Configuration::updateValue('orderpost_order_id', false);
        Configuration::updateValue('orderpost_customer_id', false);
        Configuration::updateValue('orderpost_customer_name', false);
        Configuration::updateValue('orderpost_customer_email', false);
        Configuration::updateValue('orderpost_product_name', false);
        Configuration::updateValue('orderpost_product_quantity', false);
        Configuration::updateValue('orderpost_order_total', false);
        Configuration::updateValue('orderpost_order_status', false);
        Configuration::updateValue('orderpost_date_added', false);
        Configuration::updateValue('orderpost_order_modified', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('actionOrderStatusPostUpdate');
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        Configuration::deleteByName('orderpost_post_url');
        Configuration::deleteByName('orderpost_order_sum');
        Configuration::deleteByName('orderpost_order_id');
        Configuration::deleteByName('orderpost_customer_id');
        Configuration::deleteByName('orderpost_customer_name');
        Configuration::deleteByName('orderpost_customer_email');
        Configuration::deleteByName('orderpost_product_name');
        Configuration::deleteByName('orderpost_product_quantity');
        Configuration::deleteByName('orderpost_order_total');
        Configuration::deleteByName('orderpost_order_status');
        Configuration::deleteByName('orderpost_date_added');
        Configuration::deleteByName('orderpost_order_modified');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitorderpostModule')) == true) {
            $this->postProcess();
            $this->html .= $this->confirm;
            $this->html .= $this->inform;
            $this->html .= $this->warn;
            $this->html .= $this->error;
        }

        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('widget', $this->widget('ps_orderpost_free'));

        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        $this->html .= $this->renderForm();
        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/widget.tpl');

        return $this->html;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitorderpostModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Fill your API Url and choose which data will be sending'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-cog"></i>',
                        'placeholder' => 'https://my.api.url',
                        'desc' => $this->l('URL where POST-Request will be send'),
                        'name' => 'orderpost_post_url',
                        'label' => $this->l('URL for sending'),
                        'empty_message' => $this->l('To be displayed when the field is empty.'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-usd"></i>',
                        'placeholder' => '-1',
                        'desc' => $this->l('Sending POST request if order sum is above than this value. (set -1 for free orders)'),
                        'name' => 'orderpost_order_sum',
                        'label' => $this->l('Minimum order sum'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Order ID (reference code)'),
                        'name' => 'orderpost_order_id',
                        'is_bool' => true,
                        'desc' => $this->l('$_POST[\'order_id\']'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Customer ID'),
                        'name' => 'orderpost_customer_id',
                        'is_bool' => true,
                        'desc' => $this->l('$_POST[\'customer_id\']'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Customer name'),
                        'name' => 'orderpost_customer_name',
                        'is_bool' => true,
                        'desc' => $this->l('$_POST[\'firstname\'] $_POST[\'lastname\'] OR $_POST[\'fullname\'] '),
                        'options' => array(
                            'query' => $options = array(
                                array(
                                    'id_option' => false,
                                    'name' => 'OFF'
                                ),
                                array(
                                    'id_option' => 'firstname_lastname',
                                    'name' => 'First name and Last name (two fields)'
                                ),
                                array(
                                    'id_option' => 'fullname',
                                    'name' => 'Fullname (First name and Last name in one field)'
                                ),
                            ),
                            'id' => 'id_option',
                            'name' => 'name'
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Customer eMail'),
                        'name' => 'orderpost_customer_email',
                        'is_bool' => true,
                        'desc' => $this->l('$_POST[\'customer_email\']'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Product Name'),
                        'name' => 'orderpost_product_name',
                        'is_bool' => true,
                        'desc' => $this->l('$_POST[\'product_name\']'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Product Quantity'),
                        'name' => 'orderpost_product_quantity',
                        'is_bool' => true,
                        'desc' => $this->l('$_POST[\'product_quantity\']'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Order Total'),
                        'name' => 'orderpost_order_total',
                        'is_bool' => true,
                        'desc' => $this->l('$_POST[\'order_total\']'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Order Status'),
                        'name' => 'orderpost_order_status',
                        'is_bool' => true,
                        'desc' => $this->l('$_POST[\'order_status\']'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Date Order placed'),
                        'name' => 'orderpost_date_added',
                        'is_bool' => true,
                        'desc' => $this->l('$_POST[\'date_added\']'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Date Order modified'),
                        'name' => 'orderpost_order_modified',
                        'is_bool' => true,
                        'desc' => $this->l('$_POST[\'order_modified\']'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'orderpost_post_url' => Configuration::get('orderpost_post_url', null),
            'orderpost_order_sum' => Configuration::get('orderpost_order_sum', null),
            'orderpost_order_id' => Configuration::get('orderpost_order_id', null),
            'orderpost_customer_id' => Configuration::get('orderpost_customer_id', null),
            'orderpost_customer_name' => Configuration::get('orderpost_customer_name', null),
            'orderpost_customer_email' => Configuration::get('orderpost_customer_email', null),
            'orderpost_product_name' => Configuration::get('orderpost_product_name', null),
            'orderpost_product_quantity' => Configuration::get('orderpost_product_quantity', null),
            'orderpost_order_total' => Configuration::get('orderpost_order_total', null),
            'orderpost_order_status' => Configuration::get('orderpost_order_status', null),
            'orderpost_date_added' => Configuration::get('orderpost_date_added', null),
            'orderpost_order_modified' => Configuration::get('orderpost_order_modified', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    /**
     * @param $params
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionOrderStatusPostUpdate($params)
    {

//        $fp = fopen('order.txt', 'w');
//        fwrite($fp, 'test test test hookActionOrderStatusPostUpdate' . PHP_EOL);
//        fclose($fp);

        $config = $this->getConfigFormValues();

        if (!$config['orderpost_post_url']) {
            return true;
        }

        $order = new Order((int)$params['id_order']);

        if (!$config['orderpost_order_sum']) {
            $config['orderpost_order_sum'] = -1;
        }

        if ($order->total_paid > (int)$config['orderpost_order_sum']) {

            $customer = new Customer($params['cart']->id_customer);
            $products = $order->getCartProducts();

            $send = array();

            if ($config['orderpost_order_id']){
                $send['order_id'] = $order->reference;
//              $send['order_id'] = $params['id_order'];
            }
            if ($config['orderpost_customer_id']){
                $send['customer_id'] = $params['cart']->id_customer;
            }
            if ($config['orderpost_customer_name'] == 'firstname_lastname'){
                $send['firstname'] = $customer->firstname;
                $send['lastname'] = $customer->lastname;
            } elseif ($config['orderpost_customer_name'] == 'fullname'){
                $send['fullname'] = $customer->firstname.' '.$customer->lastname;
            }
            if ($config['orderpost_customer_email']){
                $send['customer_email'] = $customer->email;
            }
            if ($config['orderpost_product_name']){
                $send['product_name'] = $products[0]['product_name'];
            }
            if ($config['orderpost_product_quantity']){
                $send['product_quantity'] = $products[0]['product_quantity'];
            }
            if ($config['orderpost_order_total']){
                $send['order_total'] = round($order->total_paid, 2);
            }
            if ($config['orderpost_order_status']){
                $send['order_status'] = $params['newOrderStatus']->name;
            }
            if ($config['orderpost_date_added']){
                $send['date_added'] = $order->date_add;
            }
            if ($config['orderpost_order_modified']){
                $send['order_modified'] = $order->date_upd;
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $config['orderpost_post_url']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $send);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 3);
//            $server_output = curl_exec($ch);
            curl_exec($ch);
            curl_close($ch);

            /*
            //$fp = fopen('order.txt', 'w+');
            //fwrite($fp, print_r($send, TRUE) . PHP_EOL);
            //fwrite($fp, print_r($products, TRUE) . PHP_EOL);
            //fwrite($fp, print_r($server_output, TRUE) . PHP_EOL);
            //fwrite($fp, print_r($order, TRUE) . PHP_EOL);
            //fwrite($fp, print_r($customer, TRUE) . PHP_EOL);
            //fclose($fp);
            */

        }
    }

    /**
     * @param $param
     * @return mixed
     */
    public function widget($param)
    {
        $send['widget'] = $param;
        $send['http_host'] = $_SERVER['HTTP_HOST'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://tobiksoft.com/market/widget/api.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $send);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 5);
        $output = curl_exec ($ch);
        curl_close ($ch);


        return $output;
    }

}
