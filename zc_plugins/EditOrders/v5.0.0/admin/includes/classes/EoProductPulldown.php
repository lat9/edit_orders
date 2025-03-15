<?php
// -----
// Part of the "Edit Orders" plugin for Zen Cart.
//
// Last modified: EO v5.0.0
//
namespace Zencart\Plugins\Admin\EditOrders;

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

/**
 * An extension of the base productPulldown.php class, enabling EO
 * to not include products that are sold out in its dropdowns for
 * choosing a product to be added to the order.
 */
class EoProductPulldown extends \productPulldown
{
    public function __construct()
    {
        parent::__construct();

        if (STOCK_ALLOW_CHECKOUT === 'false') {
            $this->condition = ' AND p.products_quantity > 0 ';
        }
    }
}
