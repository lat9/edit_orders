<?php
// -----
// Part of the 'Edit Orders' plugin, providing a 'stub' class for the order's shipping-method to
// prevent PHP Notices being issued from the ot_shipping.php class' constructor.
//
// Copyright (C) 2020-2024, Vinos de Frutas Tropicales.
//
// Last updated: EO v5.0.0
//
namespace Zencart\Plugins\Admin\EditOrders;

if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

// -----
// The ot_shipping class constructor checks to see if the active shipping-method is to be taxed.
// This 'stub' class indicates that there is no tax on shipping, enabling the EO handling to allow
// an admin to override the shipping tax-rate.
//
// The class is instantiated by EO's admin observer (EditOrdersAdminObserver.php).
//
class EditOrdersOtShippingStub
{
    public $tax_class = 0;
    public function __construct()
    {
    }
}
