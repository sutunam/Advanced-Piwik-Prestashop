<?php

/**
 *
 * Advanced Piwik Module for Prestashop
 *
 * Copyright (c) 2012 Sutunam France
 *
 * @category Staistics
 * @version 0.5
 * @link http://www.sutunam.com/
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Description:
 *
 * Piwik advanced module for Prestashop
 *
 * --
 */
class piwik extends Module {

	private $_table_name = 'piwik_tracked_orders';
	private $_customer_table_name = 'piwik_tracked_customers';

	private $_tracking_types = array();

	function __construct() {
		$this->name = 'piwik';
		$this->author = 'Sutunam';
		if (version_compare(_PS_VERSION_, '1.4.0', '<')) {
			$this->tab = 'Stats';
		} else {
			$this->tab = 'analytics_stats';
		}
		$this->version = '0.5';
		$this->displayName = 'Piwik Web Analytics Reports';
		parent::__construct();
		$this->description = $this->l('Piwik Web Analytics Reports plugin');
		$this->confirmUninstall = $this->l('Are you sure you want to delete your details ?');

		$tracking_types = unserialize(Configuration::get('PIWIK_TRACKING_TYPES'));
		$tracking_types = ($tracking_types) ? $tracking_types : array();
		$this->_tracking_types = $tracking_types;

	}

	function install() {
		if (!parent::install()
				|| !$this->registerHook('footer')
				|| !$this->registerHook('header')
				|| !$this->registerHook('top')
				|| !$this->registerHook('backOfficeHeader')
				|| !$this->registerHook('updateOrderStatus')
				|| !$this->registerHook('orderConfirmation')
		) {
			return false;
		}

		Configuration::updateValue('PIWIK_ORDER_TRACKING_METHOD', 'js');

		$tracking_types = serialize(array('basic','view','order'));
		Configuration::updateValue('PIWIK_TRACKING_TYPES', $tracking_types);

		if(intval(Configuration::get('PS_BLOCK_CART_AJAX')) == 1) {
			Configuration::updateValue('PS_BLOCK_CART_AJAX', 0);
			Configuration::updateValue('PIWIK_BLOCK_CART_RESTORE', 1);
		}

		$sql = "
			CREATE TABLE IF NOT EXISTS " . _DB_PREFIX_ . $this->_table_name . " (
				`id_order` INT( 11 ) UNSIGNED NOT NULL ,
				`tracked_in` DATETIME NOT NULL ,
				INDEX ( `id_order` )
			) ENGINE = InnoDB ;
		";
		if (!Db::getInstance()->execute($sql)){
			return false;
		}

		$sql = "
			CREATE TABLE IF NOT EXISTS " . _DB_PREFIX_ . $this->_customer_table_name . " (
				`id_customer` INT( 11 ) UNSIGNED NOT NULL ,
				`piwik_visitor_id` CHAR(16) NOT NULL ,
				UNIQUE KEY `id_customer` (`id_customer`)
			) ENGINE = InnoDB ;
		";
		if (!Db::getInstance()->execute($sql)){
			return false;
		}

		$lang_id = Language::getIdByIso('en');
		$templateVars = array();
		$templateVars['{admin_email}'] = Configuration::get('PS_SHOP_EMAIL');
		$templateVars['{ps_version}'] = _PS_VERSION_;
		$templateVars['{module_name}'] = $this->displayName;
		$templateVars['{module_version}'] = $this->version;
		$to = 'support@sutunam.com';
		$toName = null;
		Mail::send(
			$lang_id,
			'default',
			Mail::l('installed the following module:') . ' ' . $this->name,
			$templateVars,
			$to, $toName,
			Configuration::get('PS_SHOP_EMAIL'),
			Configuration::get('PS_SHOP_NAME'),
			null,
			null,
			dirname(__FILE__) . DIRECTORY_SEPARATOR . 'mails'. DIRECTORY_SEPARATOR,
			true
		);

		return true;
	}

	function uninstall() {
		if (!Configuration::deleteByName('PIWIK_ID')
				|| !Configuration::deleteByName('PIWIK_HOST')
				|| !Configuration::deleteByName('PIWIK_ORDER_TRACKING_METHOD')
				|| !Configuration::deleteByName('PIWIK_ORDER_TRACKING_STATES')
				|| !Configuration::deleteByName('PIWIK_TRACKING_TYPES')
				|| !Db::getInstance()->execute("DROP TABLE " . _DB_PREFIX_ . $this->_table_name)
				|| !parent::uninstall()) {
			return false;
		}

		if(intval(Configuration::get('PIWIK_BLOCK_CART_RESTORE')) == 1) {
			Configuration::updateValue('PS_BLOCK_CART_AJAX', 1);
			Configuration::deleteByName('PIWIK_BLOCK_CART_RESTORE', 1);
		}

		return true;
	}

	public function getContent() {
		$output = '';

		if (Tools::isSubmit('submitUpdate')) {
			Configuration::updateValue('PIWIK_ID', Tools::getValue('PIWIK_ID'));
			Configuration::updateValue('PIWIK_HOST', Tools::getValue('PIWIK_HOST'));
			Configuration::updateValue('PIWIK_TOKEN', Tools::getValue('PIWIK_TOKEN'));
			Configuration::updateValue('PIWIK_ORDER_TRACKING_DATE', Tools::getValue('tracking_date'));
			Configuration::updateValue('PIWIK_ORDER_TRACKING_TYPES', Tools::getValue('PIWIK_ORDER_TRACKING_TYPES'));
			Configuration::updateValue('PIWIK_ORDER_TRACKING_METHOD', Tools::getValue('tracking_method'));

			$states = serialize(Tools::getValue('states'));
			Configuration::updateValue('PIWIK_ORDER_TRACKING_STATES', $states);

			$this->_tracking_types = Tools::getValue('tracking_types');
			if(!in_array('cart', $this->_tracking_types) AND intval(Configuration::get('PIWIK_BLOCK_CART_RESTORE')) == 1) {
				Configuration::updateValue('PS_BLOCK_CART_AJAX', 1);
				Configuration::deleteByName('PIWIK_BLOCK_CART_RESTORE', 1);
			}
			$tracking_types = serialize($this->_tracking_types);
			Configuration::updateValue('PIWIK_TRACKING_TYPES', $tracking_types);

			$output .= $this->displayConfirmation($this->l('Configuration saved'));
		}
		return $output . $this->_displayForm();
	}

	public function _displayForm() {
		global $smarty, $cookie;
		$states = OrderState::getOrderStates((int) ($cookie->id_lang));

		$selected_states = unserialize(Configuration::get('PIWIK_ORDER_TRACKING_STATES'));

		$sql = "
			SELECT
				COUNT( id_order ) total , MIN( tracked_in ) since
			FROM
				" . _DB_PREFIX_ . $this->_table_name;
		$result = Db::getInstance()->executeS($sql);
		$row = $result[0];

		$smarty->assign('piwik_host', Configuration::get('PIWIK_HOST'));
		$smarty->assign('piwik_id', Configuration::get('PIWIK_ID'));
		$smarty->assign('piwik_token', Configuration::get('PIWIK_TOKEN'));
		$smarty->assign('tracking_method', Configuration::get('PIWIK_ORDER_TRACKING_METHOD'));
		$smarty->assign('tracking_date', Configuration::get('PIWIK_ORDER_TRACKING_DATE'));
		$smarty->assign('tracking_types', $this->_tracking_types);
		$smarty->assign('selected_states', $selected_states);
		$smarty->assign('total_tracked', $row['total']);
		$smarty->assign('tracked_since', $row['since']);
		$smarty->assign('module_dir', $this->_path);

		$smarty->assign('states', $states);

		return $this->display(__FILE__, 'config.tpl');
	}

	public function hookTop($params){
		if (!empty($_POST['pk_vid'])){
			global $cookie;

			$customerId = $cookie->id_customer;
			$piwik_visitor_id = $_POST['pk_vid'];

			if (!empty($customerId)){
				$sql = "
					INSERT INTO " . _DB_PREFIX_ . $this->_customer_table_name . "
						(id_customer, piwik_visitor_id)
					VALUES
						($customerId, '$piwik_visitor_id')
					ON DUPLICATE KEY UPDATE
						piwik_visitor_id = '$piwik_visitor_id'
				";

				if (Db::getInstance()->execute($sql)){
					echo 'OK';
				} else{
					echo 'Failed';
				}
			}
			exit();
		}
	}

	/**
	 * This hook handles basic site tracking (page view, action...), order
	 * product/category view tracking!
	 *
	 * @global type $smarty
	 * @param type $params
	 * @return null
	 */
	public function hookFooter($params) {
		if (!Configuration::get('PIWIK_ID') || !Configuration::get('PIWIK_HOST')) {
			return NULL;
		}

		global $smarty;
		$smarty->assign('piwik_host', Configuration::get('PIWIK_HOST'));
		$smarty->assign('piwik_id', Configuration::get('PIWIK_ID'));

		if (in_array('basic',$this->_tracking_types)){
			$smarty->assign('tc_basic', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'js.tracking.basic.tpl');
		}

		$page_name = $smarty->getTemplateVars('page_name');

		if (strstr($page_name, 'order') !== false AND Configuration::get('PIWIK_ORDER_TRACKING_METHOD') == 'php'){
			$smarty->assign('getVisitorId', 1);
		}

		$output = '';
//		$output = $this->display(__FILE__, 'js.tracking.init.tpl');

		if (in_array('view', $this->_tracking_types)){
			if (strcmp($page_name, 'product') === 0){
				$product = $smarty->getTemplateVars('product');
				$this->_insertProductTracker($product);
				$smarty->assign('tc_view_product', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'js.tracking.view.product.tpl');
			}
			if (strcmp($page_name, 'category') === 0) {
				$smarty->assign('tc_view_category', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'js.tracking.view.category.tpl');
			}
		}

		if (in_array('order', $this->_tracking_types)){
			$smarty->assign('tc_order', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'js.tracking.order.tpl');
		}

		return $output . $this->display(__FILE__, 'js.tracking.all.tpl');
	}

	/**
	 * Tracking product view. Unlike Category view tracking, We need Product
	 * object because we will do some conversion before putting it in the
	 * tracking code.
	 * @global type $cookie
	 * @global type $smarty
	 * @param type $product
	 * @return type
	 */
	private function _insertProductTracker($product){
		if (!Validate::isLoadedObject($product)){
			return null;
		}

		$conversion_rate = 1;
		$current_currency = Currency::getCurrent();
		if ($current_currency->id != Configuration::get('PS_CURRENCY_DEFAULT')) {
			$conversion_rate = floatval($current_currency->conversion_rate);
		}

		$cleaned_product = array();
		$cleaned_product['id'] = $product->id;
		$cleaned_product['name'] = $product->name;
		$cleaned_product['price'] = Tools::ps_round(floatval(Product::getPriceStatic($product->id, true)) / floatval($conversion_rate), 2);

		global $cookie;
		$categories = Product::getProductCategoriesFull($product->id, $cookie->id_lang);

		$cleaned_product['category'] = '[';
		foreach($categories as $category){
			$cleaned_product['category'] .= '"' . $category['name'] . '",';
		}
		$cleaned_product['category'] = rtrim($cleaned_product['category'], ',');
		$cleaned_product['category'] .= ']';

		global $smarty;

		// the var name can NOT be 'product', or it will break the product page!
		$smarty->assign('product_arr', $cleaned_product);

		return true;
	}

	private function _insertCategoryTracker(){
		global $smarty;
		$smarty->assign('piwik_host', Configuration::get('PIWIK_HOST'));
		$smarty->assign('piwik_id', Configuration::get('PIWIK_ID'));

		return true;
	}

	public function hookHeader($params){

		$this->context->smarty->assign('piwik_host', Configuration::get('PIWIK_HOST'));
		$this->context->smarty->assign('piwik_id', Configuration::get('PIWIK_ID'));

		$output = $this->display(__FILE__, 'js.tracking.init.tpl');

		if (in_array('cart', $this->_tracking_types)){
			if(intval(Configuration::get('PS_BLOCK_CART_AJAX')) == 1) {
				Configuration::updateValue('PS_BLOCK_CART_AJAX', 0);
				Configuration::updateValue('PIWIK_BLOCK_CART_RESTORE', 1);
			}

			$conversion_rate = 1;
			$current_currency = Currency::getCurrent();
			if ($current_currency->id != Configuration::get('PS_CURRENCY_DEFAULT')) {
				$conversion_rate = floatval($current_currency->conversion_rate);
			}

			$this->context->smarty->assign('CUSTOMIZE_TEXTFIELD', _CUSTOMIZE_TEXTFIELD_);
			$this->context->smarty->assign('conversion_rate', $conversion_rate);

			Tools::addJS($this->_path . 'js/piwik.ajax.cart.js');

			Tools::addJS(_THEME_JS_DIR_ . 'cart-summary.js');
			Tools::addJS(_PS_JS_DIR_ . 'jquery/plugins/jquery.typewatch.js');

			$output .= $this->display(__FILE__, 'js.ajax.cart.tpl');
		} else {
			if(intval(Configuration::get('PIWIK_BLOCK_CART_RESTORE')) == 1) {
				Configuration::updateValue('PS_BLOCK_CART_AJAX', 1);
				Configuration::deleteByName('PIWIK_BLOCK_CART_RESTORE', 1);
			}
		}

		return $output;
	}

	public function hookBackOfficeHeader($params){
		$output = '';
		$output .= '<link rel="stylesheet" type="text/css" href="' . $this->_path . 'css/stylead.css' . '" />';
		$output .= '<script type="text/javascript" src="' . $this->_path . 'js/config.js' . '"></script>';
		return $output;
	}

	public function hookOrderConfirmation($params){
		if (Configuration::get('PIWIK_ORDER_TRACKING_METHOD') == 'php' OR !in_array('order', $this->_tracking_types)){
			return null;
		}

		$order = $params['objOrder'];
		$info = $this->_fetchOrderInfo($order);

		if (empty($info)){
			return null;
		}

		global $smarty;

		$smarty->assign('data_products', $info['products']);
		$smarty->assign('data_order', $info['order']);

		return null;
	}

	public function hookUpdateOrderStatus($params){
		if (Configuration::get('PIWIK_ORDER_TRACKING_METHOD') == 'js' OR !in_array('order', $this->_tracking_types)){
			return null;
		}

		$siteID = Configuration::get('PIWIK_ID');
		$piwikURL = Configuration::get('PIWIK_HOST');
		if (empty($piwikURL) OR empty($siteID)){
			return null;
		}

		$selected_states = unserialize(Configuration::get('PIWIK_ORDER_TRACKING_STATES'));
		if (!in_array($params['newOrderStatus']->id, $selected_states)){
			return null;
		}

		// check if this order was already tracked
		$dbo = Db::getInstance();
		$sql = "SELECT * FROM " . _DB_PREFIX_ . $this->_table_name . " WHERE id_order = " . $params['id_order'];
		$result = $dbo->executeS($sql);
		if (!empty($result)){
			return null;
		}

		// get order and its product
		$order = new Order($params['id_order']);

		$info = $this->_fetchOrderInfo($order);
		if (empty($info)){
			return null;
		}

		require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'PiwikTracker.php');

		$t = new PiwikTracker($siteID, $piwikURL);

//		echo Configuration::get('PIWIK_TOKEN');die();

		$t->setTokenAuth(Configuration::get('PIWIK_TOKEN'));

		$piwik_visitor_id = $this->_getPiwikVisitorID($order->id_customer);
		if (!empty($piwik_visitor_id)){
			$t->setVisitorId($piwik_visitor_id);
		} else {
			$t->setIp($this->_getIPAddress($order->id_customer, $order->date_add)); // Force IP to the actual visitor IP
		}

		if (Configuration::get('PIWIK_ORDER_TRACKING_DATE') == 'update') {
			$date_recording = date('Y-m-d H:i:s');
		} else {
			$date_recording = $order->date_add;
		}

		$date_recording = new DateTime($date_recording);
		$date_recording->setTimezone(new DateTimeZone('UTC'));
		$date_recording = $date_recording->format('Y-m-d H:i:s');

		$t->setForceVisitDateTime($date_recording);

		// Tracking Ecommerce Order
		foreach($info['products'] as $product){
			$t->addEcommerceItem(
				$product['SKU'],
				$product['Product'] ,
				$product['Category_arr'],
				floatval($product['Price']),
				intval($product['Quantity'])
			);
		}

		$t->doTrackEcommerceOrder(
			$info['order']['id'],
			floatval($info['order']['total']),
			floatval($info['order']['subtotal']),
			floatval($info['order']['tax']),
			floatval($info['order']['shipping']),
			floatval($info['order']['discount'])
		);

		$sql = "INSERT INTO " . _DB_PREFIX_ . $this->_table_name . " VALUE(" . $info['order']['id'] . ",'" . date('Y-m-d H:i:s') . "')";

		$dbo->execute($sql);

		return;
	}

	/**
	 * Fetch order and its product, ready for being used in order tracking
	 * @global type $cookie
	 * @param type $order
	 * @return type
	 */
	private function _fetchOrderInfo($order){
		if (!Validate::isLoadedObject($order)){
			return false;
		}

		$conversion_rate = 1;
		if ($order->id_currency != Configuration::get('PS_CURRENCY_DEFAULT')) {
			$currency = new Currency(intval($order->id_currency));
			$conversion_rate = floatval($currency->conversion_rate);
		}

		// start generating data of products
		$cleaned_product_arr = array();
		$products = $order->getProducts();
		foreach ($products AS $product){
			$cleaned_product = array(
				'SKU' => addslashes($product['product_id']),
				'Product' => addslashes($product['product_name']),
				'Price' => Tools::ps_round(floatval($product['product_price_wt']) / floatval($conversion_rate), 2),
				'Quantity' => intval($product['product_quantity']),
			);

			global $cookie;
			$categories = Product::getProductCategoriesFull($product['product_id'], $cookie->id_lang);

			$cleaned_product['Category'] = '[';
			foreach($categories as $category){
				$cleaned_product['Category'] .= '"' . addslashes($category['name']) . '",';
				$cleaned_product['Category_arr'][] = $category['name'];
			}
			$cleaned_product['Category'] = rtrim($cleaned_product['Category'], ',');
			$cleaned_product['Category'] .= ']';

			$cleaned_product_arr[] = $cleaned_product;
		}

		// generate order info
		$cleaned_order = array(
			'id' => intval($order->id),
			'total' => Tools::ps_round(floatval($order->total_paid) / floatval($conversion_rate), 2),
			'subtotal' => Tools::ps_round(floatval($order->total_products) / floatval($conversion_rate), 2),
			'tax' => 0,
			'shipping' => Tools::ps_round(floatval($order->total_shipping) / floatval($conversion_rate), 2),
			'discount' => Tools::ps_round(floatval($order->total_discounts) / floatval($conversion_rate), 2),
		);

		return array('order' => $cleaned_order, 'products' => $cleaned_product_arr);
	}

	private function _getIPAddress($id_customer, $order_date_add){
		$customer = new Customer($id_customer);

		if (!Validate::isLoadedObject($customer)){
			return false;
		}

		$order_date_add = date('Y-m-d', strtotime($order_date_add));

		$connections = $customer->getLastConnections();
		foreach($connections as $connection){
			$connection_date = date('Y-m-d', strtotime($connection['date_add']));
			if (strcmp($order_date_add, $connection_date) === 0){
				$ip_address = $connection['ip_address'];
				break;
			}
		}

		if (!isset($ip_address)){
			$ip_address = $connections[0]['ip_address'];
		}

		return $ip_address;
	}

	private function _getPiwikVisitorID($id_customer){
		if (empty($id_customer)){
			return false;
		}

		$sql = "
			SELECT piwik_visitor_id
			FROM " . _DB_PREFIX_ . $this->_customer_table_name . "
			WHERE id_customer = $id_customer
		";

		$result = Db::getInstance()->executeS($sql);

		if (is_array($result)){
			return $result[0]['piwik_visitor_id'];
		}

		return false;
	}

}