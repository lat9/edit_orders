<?php
// -----
// A 'somewhat' modified shopping-cart class, used when editing an
// order during admin processing.
//
// Last updated: EO v5.0.0 (new)
//
namespace Zencart\Plugins\Admin\EditOrders;

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

/**
 * This class extends the storefront shopping-cart class and reuses methods
 * where possible.
 */
class EoCart extends \shoppingCart
{
    const UNSUPPORTED_LOG_MESSAGE = 'Call to unsupported shopping-cart method during Edit Orders processing.';

    public function __construct()
    {
        parent::__construct();
    }

    // -----
    // Reconstruct the cart from the ordered products, for use during pricing/quantity
    // updates.
    //
    public function loadFromOrder(\order $order): void
    {
        $this->content_type = $order->content_type;
        $option_types = [];
        foreach ($order->products as &$product) {
            $uprid = $product['uprid'];

            $this->contents[$uprid] = [
                'qty' => (float)$product['qty'],
            ];
            if (isset($product['attributes'])) {
                $this->contents[$uprid]['attributes'] = [];
                foreach ($product['attributes'] as $attribute) {
                    $option_id = (int)$attribute['option_id'];
                    $value_id = (int)$attribute['value_id'];
                    $option_type = $option_types[$option_id] ?? false;
                    if ($option_type === false) {
                        $option_info = zen_get_option_details($option_id);
                        if ($option_info->EOF) {
                            $product['missing_options'][] = $option_id;
                        } else {
                            $option_type = (int)$option_info->fields['products_options_type'];
                            $option_types[$option_id] = $option_type;
                        }
                    }

                    // -----
                    // Checkbox attributes' option_id is formatted as 33chk_37,
                    //  where 33 is the option-id and 37 is the option-value
                    //
                    if ($option_type === 3) {
                        $option_id = $option_id . 'chk_' . $value_id;
                    }

                    $this->contents[$uprid]['attributes'][$option_id] = $value_id;
                    if ($value_id === 0) {
                        $this->contents[$uprid]['attributes_values'][$option_id] = $attribute['value'];
                    }
                }
            }
        }
        unset($product);

        // -----
        // Initialize the calculated values; EO's cart doesn't re-calculate
        // unless there's a change to the order's products.
        //
        $this->calculateTotalAndWeight($order->products);
    }

    public function calculateTotalAndWeight(array $ordered_products): void
    {
        $this->total = 0;
        $this->weight = 0;
        foreach ($ordered_products as $product) {
            $this->contents[$product['uprid']]['qty'] = (float)$product['qty'];
            $this->total += ($product['qty'] * $product['final_price']) + $product['onetime_charges'];
            $this->weight += $product['qty'] * $product['products_weight'];
        }
    }

    // -----
    // Remove a product from the cart.
    //
    public function removeProduct(string $uprid, array $product): void
    {
        unset($this->contents[$uprid]);
        $this->total -= (($product['qty'] * $product['final_price']) + $product['onetime_charges']);
        $this->weight -= $product['qty'] * $product['products_weight'];
    }

    // -----
    // Start shoppingCart class method overrides ...
    //
    public function restore_contents()
    {
        trigger_error(self::UNSUPPORTED_LOG_MESSAGE, E_USER_WARNING);
    }

    // -----
    // Let the base cart do its reset, disregarding any request
    // to actually reset the database.
    //
    public function reset($reset_database = false)
    {
        parent::reset();
    }

    public function add_cart($product_id, $qty = 1, $attributes = [], $notify = true)
    {
        trigger_error(self::UNSUPPORTED_LOG_MESSAGE, E_USER_WARNING);
    }

    public function update_quantity($uprid, $quantity = 0, $attributes = [])
    {
        trigger_error(self::UNSUPPORTED_LOG_MESSAGE, E_USER_WARNING);
    }

    // -----
    // Let the base cart do its cleanup.
    //
    public function cleanup()
    {
        parent::cleanup();
    }

    /**
     * Protected method that removes a uprid from the cart.
     *
     * @param string|int $uprid 'uprid' of product to remove
     * @return void
     */
    protected function removeUprid($uprid)
    {
        unset($this->contents[$uprid]);
    }

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
    public function count_contents()
    {
        return parent::count_contents();
    }

    /**
     * Get the quantity of an item in the cart
     * NOTE: This accepts attribute hash as $products_id, such as: 12:a35de52391fcb3134
     * ... and treats 12 as unique from 12:a35de52391fcb3134
     * To lookup based only on prid (ie: 12 here) regardless of the attribute hash, use another method: in_cart_product_total_quantity()
     *
     * @param int|string $uprid product ID of item to check
     * @return int|float the quantity of the item
     */
    public function get_quantity($uprid)
    {
        return parent::get_quantity($uprid);
    }

    /**
     * Check whether a product exists in the cart
     *
     * @param mixed $uprid product ID of product to check
     * @return boolean
     */
    public function in_cart($uprid)
    {
        return parent::in_cart($uprid);
    }

    /**
     * Remove a product from the cart
     *
     * @param string|int $uprid product ID of product to remove
     * @return void
     */
    public function remove($uprid)
    {
        trigger_error(self::UNSUPPORTED_LOG_MESSAGE, E_USER_WARNING);
    }

    /**
     * Remove all products from the cart
     */
    public function remove_all()
    {
        trigger_error(self::UNSUPPORTED_LOG_MESSAGE, E_USER_WARNING);
    }

    /**
     * Return a comma separated list of all products in the cart
     * NOTE: Not used in core ZC, but some plugins and shipping modules make use of it as a helper function
     *
     * @return string csv
     */
    public function get_product_id_list()
    {
        return parent::get_product_id_list();
    }

    /**
     * Calculate cart totals(price and weight)
     *
     * @return int
     */
    public function calculate()
    {
        trigger_error(self::UNSUPPORTED_LOG_MESSAGE, E_USER_WARNING);
    }

    /**
     * Calculate price of attributes for a given item
     *
     * @param mixed $uprid the product ID of the item to check
     * @return float the price of the item's attributes
     */
    public function attributes_price($uprid)
    {
        return parent::attributes_price($uprid);
    }

    /**
     * Calculate one-time price of attributes for a given item
     *
     * @param mixed $uprid the product ID of the item to check
     * @param float $qty item quantity
     * @return float the price of the items attributes
     */
    public function attributes_price_onetime_charges($uprid, $qty)
    { 
        return parent::attributes_price_onetime_charges($uprid, $qty);; 
    }

    /**
     * Calculate weight of attributes for a given item
     *
     * @param mixed $product_id the product ID of the item to check
     * @return float the weight of the items attributes
     */
    public function attributes_weight($uprid)
    {
        return parent::attributes_weight($uprid);
    }

    /**
     * Get all products in the cart
     *
     * @param bool $check_for_valid_cart whether to also check if cart contents are valid
     * @return array|false
     */
    public function get_products(bool $check_for_valid_cart = false)
    {
        $updated_order = $_SESSION['eoChanges']->getUpdatedOrder();
        $cart_products = [];
        foreach ($updated_order->products as $next_product) {
            if ($next_product['qty'] != 0) {
                $next_product['id'] = $next_product['uprid'];
                $next_product['quantity'] = $next_product['qty'];
                $cart_products[] = $next_product;
            }
        }
        return $cart_products;
    }

    /**
     * Calculate total price of items in cart
     *
     * @return float Total Price
     */
    public function show_total()
    {
        return $this->total;
    }

    /**
     * Calculate total price of items in cart before Specials, Sales, Discounts
     *
     * @return float Total Price before Specials, Sales, Discounts
     */
    public function show_total_before_discounts()
    {
        trigger_error(self::UNSUPPORTED_LOG_MESSAGE, E_USER_WARNING);
        return $this->total;
    }

    /**
     * Calculate total weight of items in cart
     *
     * @return float Total Weight
     */
    public function show_weight()
    {
        return $this->weight;
    }

    /**
     * Generate a cart ID, used to ensure contents have not been altered unexpectedly
     *
     * @param int $length length of ID to generate
     * @return string cart ID
     */
    public function generate_cart_id($length = 5)
    {
        return parent::generate_cart_id($length);
    }

    /**
     * Calculate the content type of a cart
     *
     * @param bool $gv_only whether to test for Gift Vouchers only
     * @return string
     */
    public function get_content_type($gv_only = false)
    {
        return parent::get_content_type($gv_only);
    }

    /**
     * Calculate item quantity, bounded by the mixed/min units settings
     *
     * @param int|string $uprid_to_check product id of item to check
     */
    public function in_cart_mixed(int|string $uprid_to_check): float|int
    {
        return parent::in_cart_mixed($uprid_to_check); 
    }

    /**
     * Calculate item quantity, bounded by the mixed/min units settings
     *
     * @NOTE: NOT USED IN CORE CODE
     *
     * @param int|string $uprid_to_check product id of item to check
     * @return float
     */
    public function in_cart_mixed_discount_quantity($uprid_to_check)
    {
        return parent::in_cart_mixed_discount_quantity($uprid_to_check); 
    }

    /**
     * Calculate the number of items in a cart based on an abitrary property
     *
     * $check_what is the fieldname example: 'products_is_free'
     * $check_value is the value being tested for - default is 1
     * Syntax: $_SESSION['cart']->in_cart_check('product_is_free','1');
     *
     * @param string $check_what product field to check
     * @param mixed $check_value value to check for
     * @return int number of items matching constraint
     */
    public function in_cart_check($check_what, $check_value = '1')
    {
        return parent::in_cart_check($check_what, $check_value);
    }

    /**
     * Check whether cart contains only Gift Vouchers
     *
     * @return float|bool value of Gift Vouchers in cart
     */
    public function gv_only()
    {
        return parent::gv_only(); 
    }

    // -----
    // Called by zen_get_shipping_enabled, which is called by "most"
    // shipping modules to see if they should display. EO's cart
    // **always** responds with a cost of 0.0, so that any currently-
    // selected shipping modules' names will display in the dropdown
    // on the main EO page.
    //
    public function free_shipping_items()
    {
        return 0.0;
    }

    // -----
    // This set of storefront cart functions are used **only** during
    // the shipping quote determination and "should not" be required
    // during an order's edit.
    //
    public function free_shipping_prices()
    {
        trigger_error(self::UNSUPPORTED_LOG_MESSAGE, E_USER_WARNING);
        return 0.0;
    }
    public function free_shipping_weight()
    {
        trigger_error(self::UNSUPPORTED_LOG_MESSAGE, E_USER_WARNING);
        return 0.0;
    }
    public function download_counts()
    {
        trigger_error(self::UNSUPPORTED_LOG_MESSAGE, E_USER_WARNING);
        return 0; 
    }

    // -----
    // This set of functions are used when a customer is manipulating the
    // contents of their cart; unused during Edit Orders processing.
    //
    public function actionUpdateProduct($goto, $parameters)
    {
        trigger_error(self::UNSUPPORTED_LOG_MESSAGE, E_USER_WARNING);
    }
    public function actionAddProduct($goto, $parameters = [])
    {
        trigger_error(self::UNSUPPORTED_LOG_MESSAGE, E_USER_WARNING);
    }
    public function actionBuyNow($goto, $parameters = [])
    {
        trigger_error(self::UNSUPPORTED_LOG_MESSAGE, E_USER_WARNING);
    }
    public function actionMultipleAddProduct($goto, $parameters = [])
    {
        trigger_error(self::UNSUPPORTED_LOG_MESSAGE, E_USER_WARNING);
    }
    public function actionNotify($goto, $parameters = ['ignored'])
    {
        trigger_error(self::UNSUPPORTED_LOG_MESSAGE, E_USER_WARNING);
    }
    public function actionNotifyRemove($goto, $parameters = ['ignored'])
    {
        trigger_error(self::UNSUPPORTED_LOG_MESSAGE, E_USER_WARNING);
    }
    public function actionCustomerOrder($goto, $parameters)
    {
        trigger_error(self::UNSUPPORTED_LOG_MESSAGE, E_USER_WARNING);
    }
    public function actionRemoveProduct($goto, $parameters)
    {
        trigger_error(self::UNSUPPORTED_LOG_MESSAGE, E_USER_WARNING);
    }
    public function actionCartUserAction($goto, $parameters)
    {
        trigger_error(self::UNSUPPORTED_LOG_MESSAGE, E_USER_WARNING);
    }

    /**
     * calculate quantity adjustments based on restrictions
     * USAGE:  $qty = $this->adjust_quantity($qty, (int)$products_id, 'shopping_cart');
     *
     * @param float $check_qty
     * @param int $product_id
     * @param string $messageStackPosition messageStack placement
     * @return float|int
     */
    public function adjust_quantity($check_qty, $products, $stack = 'shopping_cart')
    {
        trigger_error(self::UNSUPPORTED_LOG_MESSAGE, E_USER_WARNING);
        return $check_qty; 
    }

    /**
     * calculate the number of items in a cart based on an attribute option_id and option_values_id combo
     * USAGE:  $chk_attrib_1_16 = $this->in_cart_check_attrib_quantity(1, 16);
     * USAGE:  $chk_attrib_1_16 = $_SESSION['cart']->in_cart_check_attrib_quantity(1, 16);
     *
     * @param int $check_option_id
     * @param int $check_option_values_id
     * @return float
     */
    public function in_cart_check_attrib_quantity($check_option_id, $check_option_values_id)
    {
        return parent::in_cart_check_attrib_quantity($check_option_id, $check_option_values_id);
    }

    /**
     * calculate products_id price in cart
     * USAGE:  $product_total_price = $this->in_cart_product_total_price(12);
     * USAGE:  $chk_product_cart_total_price = $_SESSION['cart']->in_cart_product_total_price(12);
     *
     * @param mixed $product_id
     * @return float
     */
    public function in_cart_product_total_price($product_id)
    {
        return parent::in_cart_product_total_price($product_id);
    }

    /**
     * calculate products_id quantity in cart regardless of attributes
     * USAGE:  $product_total_quantity = $this->in_cart_product_total_quantity(12);
     * USAGE:  $chk_product_cart_total_quantity = $_SESSION['cart']->in_cart_product_total_quantity(12);
     *
     * @param mixed $product_id
     * @return int|mixed
     */
    public function in_cart_product_total_quantity($product_id)
    {
        return parent::in_cart_product_total_quantity($product_id);
    }

    /**
     * calculate products_id weight in cart regardless of attributes
     * USAGE:  $product_total_weight = $this->in_cart_product_total_weight(12);
     * USAGE:  $chk_product_cart_total_weight = $_SESSION['cart']->in_cart_product_total_weight(12);
     *
     * @param mixed $product_id
     * @return float
     */
    public function in_cart_product_total_weight($product_id)
    {
        return parent::in_cart_product_total_weight($product_id);
    }

    /**
     * calculate weight in cart for a category without subcategories
     * USAGE:  $category_total_weight_cat = $this->in_cart_product_total_weight_category(9);
     * USAGE:  $chk_category_cart_total_weight_cat = $_SESSION['cart']->in_cart_product_total_weight_category(9);
     *
     * @param int $category_id
     * @return float
     */
    public function in_cart_product_total_weight_category($category_id)
    {
        return parent::in_cart_product_total_weight_category($category_id);
    }

    /**
     * calculate price in cart for a category without subcategories
     * USAGE:  $category_total_price_cat = $this->in_cart_product_total_price_category(9);
     * USAGE:  $chk_category_cart_total_price_cat = $_SESSION['cart']->in_cart_product_total_price_category(9);
     *
     * @param int $category_id
     * @return float|int
     */
    public function in_cart_product_total_price_category($category_id)
    {
        return parent::in_cart_product_total_price_category($category_id);
    }

    /**
     * calculate quantity in cart for a category without subcategories
     * USAGE:  $category_total_quantity_cat = $this->in_cart_product_total_quantity_category(9);
     * USAGE:  $chk_category_cart_total_quantity_cat = $_SESSION['cart']->in_cart_product_total_quantity_category(9);
     *
     * @param int $category_id
     * @return float
     */
    public function in_cart_product_total_quantity_category($category_id)
    {
        return parent::in_cart_product_total_quantity_category($category_id);
    }

    /**
     * calculate weight in cart for a category with or without subcategories
     * USAGE:  $category_total_weight_cat = $this->in_cart_product_total_weight_category_sub(3);
     * USAGE:  $chk_category_cart_total_weight_cat = $_SESSION['cart']->in_cart_product_total_weight_category_sub(3);
     *
     * @param int $category_id
     * @return float
     */
    public function in_cart_product_total_weight_category_sub($category_id)
    {
        return parent::in_cart_product_total_weight_category_sub($category_id);
    }

    /**
     * calculate price in cart for a category with or without subcategories
     * USAGE:  $category_total_price_cat = $this->in_cart_product_total_price_category_sub(3);
     * USAGE:  $chk_category_cart_total_price_cat = $_SESSION['cart']->in_cart_product_total_price_category_sub(3);
     *
     * @param int $category_id
     * @return float
     */
    public function in_cart_product_total_price_category_sub($category_id)
    {
        return parent::in_cart_product_total_price_category_sub($category_id);
    }

    /**
     * calculate quantity in cart for a category with or without subcategories
     * USAGE:  $category_total_quantity_cat = $this->in_cart_product_total_quantity_category_sub(3);
     * USAGE:  $chk_category_cart_total_quantity_cat = $_SESSION['cart']->in_cart_product_total_quantity_category_sub(3);
     *
     * @param int $category_id
     * @return float
     */
    public function in_cart_product_total_quantity_category_sub($category_id)
    {
        return parent::in_cart_product_total_quantity_category_sub($category_id);
    }

    /**
     * calculate shopping cart stats for a products_id to obtain data about submitted (posted) items as compared to what is in the cart.
     * USAGE:  $mix_increase = in_cart_product_mixed_changed($product_id, 'increase');
     * USAGE:  $mix_decrease = in_cart_product_mixed_changed($product_id, 'decrease');
     * USAGE:  $mix_all = in_cart_product_mixed_changed($product_id);
     * USAGE:  $mix_all = in_cart_product_mixed_changed($product_id, 'all'); (Second value anything other than 'increase' or 'decrease')
     *
     * @param int|string $product_id
     * @param bool $chk
     * @return array|bool
     */
    public function in_cart_product_mixed_changed($product_id, $chk = false)
    {
        return parent::in_cart_product_mixed_changed($product_id, $chk);
    }
}
