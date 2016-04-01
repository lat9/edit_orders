<?php
if (!defined('IS_ADMIN_FLAG')) {
  die('Illegal Access');
}

/**
 * This is a very basic mock shopping cart class.
 *
 * Many methods are not implemented and may return incorrect values,
 */
class mockCart extends base {

	// Do Nothing
	public function restore_contents() { }

	// Do Nothing
	public function reset($reset_database = false) { }

	// Do Nothing
	public function add_cart($products_id, $qty = '1', $attributes = '', $notify = true) { }

	// Do Nothing
	public function update_quantity($products_id, $quantity = '', $attributes = '') { }

	// Do Nothing
	public function cleanup() { }

	/**
	 * Method to count total number of items in cart
	 *
	 * Note this is not just the number of distinct items in the cart,
	 * but the number of items adjusted for the quantity of each item
	 * in the cart, So we have had 2 items in the cart, one with a quantity
	 * of 3 and the other with a quantity of 4 our total number of items
	 * would be 7
	 *
	 * @return total number of items in cart
	 */
	public function count_contents() {
		global $order;

		$this->notify('NOTIFIER_CART_COUNT_CONTENTS_START');
		$retval = 0;
		foreach($order->products as $product) {
			$retval += $product['qty'];
		}
		$this->notify('NOTIFIER_CART_COUNT_CONTENTS_END');
		return $retval;
	}

	/**
	 * Method to get the quantity of an item in the cart
	 *
	 * @param mixed product ID of item to check
	 * @return decimal the quantity of the item
	 */
	public function get_quantity($products_id) {
		global $order;

		$this->notify('NOTIFIER_CART_GET_QUANTITY_START', array(), $products_id);
		foreach($order->products as $product) {
			if($product['id'] == $products_id) {
				$this->notify('NOTIFIER_CART_GET_QUANTITY_END_QTY', array(), $products_id);
				return $product['qty'];
			}
		}

		$this->notify('NOTIFIER_CART_GET_QUANTITY_END_FALSE', $products_id);
		return 0;
	}

	/**
	 * Method to check whether a product exists in the cart
	 *
	 * @param mixed product ID of item to check
	 * @return boolean
	 */
	public function in_cart($products_id) {
		global $order;

		$this->notify('NOTIFIER_CART_IN_CART_START', array(), $products_id);
		foreach($order->products as $product) {
			if($product['id'] == $products_id) {
				$this->notify('NOTIFIER_CART_IN_CART_END_TRUE', array(), $products_id);
				return true;
			}
		}
		$this->notify('NOTIFIER_CART_IN_CART_END_FALSE', $products_id);
		return false;
	}


	// Do Nothing
	public function remove($products_id) { }

	// Do Nothing
	public function remove_all() { }

	/**
	 * Method return a comma separated list of all products in the cart
	 *
	 * @return string
	 * @todo ICW - is this actually used anywhere?
	 */
	function get_product_id_list() {
		global $order;

		$product_id_list = '';
		foreach($order->products as $product) {
			$product_id_list .= ',' . zen_db_input($product['id']);
		}
		return substr($product_id_list, 1);
	}

	// Do Nothing
	public function calculate() { }

	// Do Nothing
	public function attributes_price($products_id) { return 0; }

	// Do Nothing
	public function attributes_price_onetime_charges($products_id, $qty) { return 0; }

	/**
	 * Method to calculate weight of attributes for a given item
	 *
	 * @param mixed the product ID of the item to check
	 * @return decimal the weight of the items attributes
	 */
	function attributes_weight($products_id) {
		global $order;

		foreach($order->products as $product) {
			if($product['id'] == $products_id) {
				return $this->get_attribute_weight($product);
			}
		}
		return 0;
	}

	/**
	 * Method to return details of all products in the cart
	 *
	 * @param boolean whether to check if cart contents are valid
	 * @return array
	 */
	function get_products($check_for_valid_cart = false) {
		global $db, $order;

		$retval = array();
		foreach($order->products as $product) {
			// Adjust fields to match
			$product['quantity'] = $product['qty'];
			unset($product['qty']);

			$products = $db->Execute(
					'SELECT `p`.`master_categories_id`,`p`.`products_image`,' .
					'`p`.`products_weight`,`p`.`products_virtual`,' .
					'`p`.`product_is_always_free_shipping`,' .
					'`p`.`products_tax_class_id` ' .
					'FROM `' . TABLE_PRODUCTS . '` AS `p` ' .
					'WHERE `p`.`products_id`=\'' . (int)$product['id'] . '\''
			);
			if(!$products->EOF) {
				$merge = array(
						'category' => $products->fields['master_categories_id'],
						'image' => $products->fields['products_image'],
						'weight' => $products->fields['products_weight'],
						'products_virtual' => $query->fields['products_virtual'],
						'product_is_always_free_shipping' => $query->fields['product_is_always_free_shipping'],
						'tax_class_id' => $query->fields['products_tax_class_id']
				);
				$product = array_merge($product, $merge);
				unset($merge);
			}
			$product['weight'] += $this->get_attribute_weight($product);

			$retval[] = $product;
		}
		return $retval;
	}

	/**
	 * Method to calculate total price of items in cart
	 *
	 * @return decimal Total Price
	 */
   	public function show_total() {
		$total = 0;
		$this->notify('NOTIFIER_CART_SHOW_TOTAL_START');
		foreach($this->get_products() as $products) {
			if(array_key_exists('final_price', $products))
				$total += (float) $products['final_price'];
		}
		$this->notify('NOTIFIER_CART_SHOW_TOTAL_END');
		return $total;
	}

	// Do Nothing
	public function show_total_before_discounts() { return 0; }

	/**
	 * Method to calculate total weight of items in cart
	 *
	 * @return decimal Total Weight
	 */
	public function show_weight() {
		$weight = 0;
		foreach($this->get_products() as $products) {
			if(array_key_exists('weight', $products))
				$weight += (float) $products['weight'];
		}
		return $weight;
	}

	/**
	 * Method to generate a cart ID
	 *
	 * @param length of ID to generate
	 * @return string cart ID
	 */
	public function generate_cart_id($length = 5) {
		return zen_create_random_value($length, 'digits');
	}

	// Do Nothing
	public function get_content_type($gv_only = 'false') { return ''; }

	// Do Nothing
	public function in_cart_mixed($products_id) { return 0; }

	// Do Nothing
	public function in_cart_mixed_discount_quantity($products_id) { return 0; }

	/**
	 * Method to calculate the number of items in a cart based on an arbitrary property
	 *
	 * $check_what is the fieldname example: 'products_is_free'
	 * $check_value is the value being tested for - default is 1
	 * Syntax: $_SESSION['cart']->in_cart_check('product_is_free','1');
	 *
	 * @param string product field to check
	 * @param mixed value to check for
	 * @return integer number of items matching restraint
	 */
	public function in_cart_check($check_what, $check_value='1') {
	   	global $db, $order;

		$retval = 0;
		foreach($order->products as $product) {
			$product_check = $db->Execute(
				'SELECT ' . $check_what .  ' AS `check_it` ' .
				'FROM `' . TABLE_PRODUCTS . '` WHERE products_id=\'' . (int)$product['id'] . '\' limit 1'
			);
			if(!$product_check->EOF && $product_check->fields['check_it'] == $check_value) {
				$retval += $product['qty'];
			}
		}
		return $retval;
	}

	// Do Nothing
	public function gv_only() { return 0; }

	// Do Nothing
	public function free_shipping_items() { return false; }

	// Do Nothing
	public function free_shipping_prices() { return 0; }

	// Do Nothing
	public function free_shipping_weight() { return 0; }

	// Do Nothing
	public function download_counts() { return 0; }

	// Do Nothing
	public function actionUpdateProduct($goto, $parameters) { }

	// Do Nothing
	public function actionAddProduct($goto, $parameters) { }

	// Do Nothing
	public function actionBuyNow($goto, $parameters) { }

	// Do Nothing
	public function actionMultipleAddProduct($goto, $parameters) { }

	// Do Nothing
	public function actionNotify($goto, $parameters) { }

	// Do Nothing
	public function actionNotifyRemove($goto, $parameters) { }

	// Do Nothing
	public function actionCustomerOrder($goto, $parameters) { }

	// Do Nothing
	public function actionRemoveProduct($goto, $parameters) { }

	// Do Nothing
	public function actionCartUserAction($goto, $parameters) { }

	// Do Nothing
	public function adjust_quantity($check_qty, $products, $stack = 'shopping_cart') { return $check_qty; }

	/**
	 * Internal function to determine the attribute weight
	 *
	 * @param array $product the product array from the order
	 * @return number the weight
	 */
	private function get_attribute_weight($product) {
		global $db;

		$weight = 0;
		if(array_key_exists('attributes', $product)) {
			foreach($product['attributes'] as $attribute) {
				$attribute_weight_info = $db->Execute(
					'SELECT products_attributes_weight, products_attributes_weight_prefix ' .
					'FROM `' . TABLE_PRODUCTS_ATTRIBUTES . '` ' .
					'WHERE products_id = \'' . (int)$product['id'] . '\' ' .
						'AND options_id = \'' . (int)$attribute['option_id'] . '\' ' .
						'AND options_values_id = \'' . (int)$attribute['value_id'] . '\''
				);

				if(!$attribute_weight->EOF) {
					// adjusted count for free shipping
					if($product['product_is_always_free_shipping'] != 1) {
						$new_attributes_weight = $attribute_weight_info->fields['products_attributes_weight'];
					} else {
						$new_attributes_weight = 0;
					}

					// + or blank adds
					if ($attribute_weight_info->fields['products_attributes_weight_prefix'] == '-') {
						$weight -= $new_attributes_weight;
					} else {
						$weight += $attribute_weight_info->fields['products_attributes_weight'];
					}
				}
			}
		}
		return $weight;
	}
}