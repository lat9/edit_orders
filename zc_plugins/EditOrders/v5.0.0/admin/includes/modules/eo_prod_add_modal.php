<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2024 The zen-cart developers
//
// Last modified v5.0.0
//
?>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h4 class="modal-title text-center"><?= TEXT_PRODUCT_ADD_MODAL_TITLE ?></h4>
</div>

<div class="modal-body">

    <div class="row">
        <div id="prod-add-choose" class="col-sm-6">
            <h5 class="text-center"><?= TEXT_PRODUCT_CHOOSE_SUBTITLE ?></h5>

            <details name="prod-choose" class="border border-info mb-3" <?= (($_POST['choose_form'] ?? 'id') === 'id') ? 'open' : '' ?>>
                <summary class="h5 bg-info my-0 py-3 text-center"><?= TEXT_PRODUCT_CHOOSE_BY_ID ?></summary>
                <form class="form-horizontal" method="post" action="javascript:void(0);">
                    <div class="form-group mt-3">
                        <label class="control-label col-sm-2" for="prod-id-prid"><?= rtrim(TEXT_PRODUCTS_ID, ' :') ?>:</label>
                        <div class="col-sm-8">
                            <?= zen_draw_input_field('prid', ($prid === 0) ? '' : $prid, 'id="prod-id-prid" class="form-control"') ?>
                        </div>
                        <div class="col-sm-2 text-center">
                            <button class="btn btn-primary prod-add"><?= BUTTON_CHOOSE ?></button>
                            <?= zen_draw_hidden_field('choose_form', 'id') ?>
                        </div>
                    </div>
                </form>
            </details>

            <details name="prod-choose" class="border border-info mb-3" <?= (($_POST['choose_form'] ?? 'id') === 'search') ? 'open' : '' ?>>
                <summary class="h5 bg-info my-0 py-3 text-center"><?= TEXT_PRODUCT_CHOOSE_BY_SEARCH ?></summary>
                <form class="form-horizontal" method="post" action="javascript:void(0);">
                    <div class="form-group mt-3">
                        <?= zen_draw_label(HEADING_TITLE_SEARCH_DETAIL, 'search-keywords', 'class="control-label col-sm-2"') ?>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <?= zen_draw_input_field('keywords', ($_POST['keywords'] ?? ''), 'id="search-keywords" class="form-control"', false, 'search') ?>
                                <span class="input-group-btn">
                                    <button id="search-products" class="btn btn-info"><i class="fa-solid fa-magnifying-glass fa-lg"></i></button>
                                </span>
                            </div>
                        </div>
                        <div class="col-sm-1">
                        </div>
                    </div>
                    <?= zen_draw_hidden_field('choose_form', 'search') ?>
                </form>
                <div id="search-results"></div>
            </details>
<?php
// -----
// Get the site's current category-tree; identifying categories that include products with
// an asterisk (*).
//
// Then traverse through the generated drop-down menu, searching for categories that
// aren't noted as having products.  These selections are disabled for the subsequent
// display.
//
$category_tree = zen_get_category_tree(TOPMOST_CATEGORY_PARENT_ID, '', '', [], false, true);
$category_menu = zen_draw_pull_down_menu('categories_id', $category_tree, ($_POST['categories_id'] ?? '0'), 'id="choose-cat" class="form-control"');
$category_menu_items = explode("\n", $category_menu);
foreach ($category_menu_items as $i => $item) {
    if (!str_starts_with($item, '<option') || str_contains($item, '*</option>') || str_contains($item, 'value="0"')) {
        continue;
    }
    $category_menu_items[$i] = str_replace('">', '" disabled="disabled">', $item);
}
$category_menu = implode("\n", $category_menu_items);
?>
            <details name="prod-choose" class="border border-info mb-3" <?= (($_POST['choose_form'] ?? 'id') === 'category') ? 'open' : '' ?>>
                <summary class="h5 bg-info my-0 py-3 text-center"><?= TEXT_PRODUCT_CHOOSE_BY_CATEGORY ?></summary>
                <form class="form-horizontal" method="post" action="javascript:void(0);">
                    <div class="form-group mt-3">
                        <?= zen_draw_label(HEADING_TITLE_SEARCH_DETAIL, 'choose-cat', 'class="control-label col-sm-2"') ?>
                        <div class="col-sm-9">
                            <?= $category_menu ?>
                        </div>
                        <div class="col-sm-1">
                        </div>
                    </div>
                    <?= zen_draw_hidden_field('choose_form', 'category') ?>
                </form>
                <div id="cat-results"></div>
            </details>

        </div>

        <div id="prod-add-details" class="col-sm-6">
            <form id="prod-add-form" class="form-horizontal" method="post" action="javascript:void(0);">
<?php
if (!empty($new_product)) {
    // -----
    // Initialize the attributes for product-related input fields.
    //
    $name_params = 'maxlength="' . zen_field_length(TABLE_ORDERS_PRODUCTS, 'products_name') . '"';
    $model_params = 'maxlength="' . zen_field_length(TABLE_ORDERS_PRODUCTS, 'products_model') . '"';
    $uprid = $new_product['uprid'];

    $qty_available = $eo->getProductsAvailableStock($uprid, $_SESSION['cart']->contents[$uprid]['attributes'] ?? []);
?>
            <h5 class="text-center"><?= TEXT_PRODUCT_NEW_MODAL_TITLE ?></h5>

            <div class="form-group">
                <label class="control-label col-sm-2" for="prod-qty-avail"><?= TEXT_LABEL_QTY_AVAIL ?></label>
                <div class="col-sm-10">
                    <?= zen_draw_input_field('qty_avail', $qty_available, 'id="prod-qty-avail" class="form-control" disabled') ?>
                </div>
            </div>

            <div id="prod-messages"></div>
<?php
    $max_qty = '';
    if (STOCK_ALLOW_CHECKOUT === 'false') {
        $max_qty = ' max="' . $qty_available . '"';
    }
?>
            <div class="form-group">
                <label class="control-label col-sm-2" for="prod-qty"><?= TEXT_LABEL_QTY ?></label>
                <div class="col-sm-10">
                    <?= zen_draw_input_field('qty', $new_product['qty'], 'id="prod-qty" class="form-control" min="0" step="any"' . $max_qty, false, 'number') ?>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-2" for="prod-model"><?= TEXT_LABEL_MODEL ?></label>
                <div class="col-sm-10">
                    <?= zen_draw_input_field('model', $new_product['model'], 'id="prod-model" class="form-control" ' . $model_params) ?>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-2" for="prod-name"><?= TEXT_LABEL_NAME ?></label>
                <div class="col-sm-10">
                    <?= zen_draw_input_field('name', $new_product['name'], 'id="prod-name" class="form-control" ' . $name_params) ?>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-sm-2" for="prod-tax"><?= TEXT_LABEL_TAX ?></label>
                <div class="col-sm-10">
                    <?= zen_draw_input_field('tax', $new_product['tax'], 'id="prod-tax" class="form-control" disabled', false, 'number') ?>
                </div>
            </div>
<?php
    $final_price = $new_product['final_price'];
    $final_price_label = rtrim((DISPLAY_PRICE_WITH_TAX === 'true') ? TABLE_HEADING_UNIT_PRICE_NET : TABLE_HEADING_UNIT_PRICE, ':');
?>
            <div class="form-group">
                <label class="control-label col-sm-2" for="prod-price-net"><?= $final_price_label ?>:</label>
                <div class="col-sm-10">
                    <?= zen_draw_input_field('final_price', $final_price, 'id="prod-price-net" class="form-control" disabled', false, 'number') ?>
                </div>
            </div>
<?php
    if (DISPLAY_PRICE_WITH_TAX === 'true') {
        $gross_price = zen_add_tax($final_price, $new_product['tax']);
?>
            <div class="form-group">
                <label class="control-label col-sm-2" for="prod-price-gross"><?= rtrim(TABLE_HEADING_UNIT_PRICE_GROSS, ':') ?>:</label>
                <div class="col-sm-10">
                    <?= zen_draw_input_field('gross_price', $gross_price, 'id="prod-price-gross" class="form-control" disabled', false, 'number') ?>
                </div>
            </div>
<?php
    }

    // -----
    // If the to-be-added product has attributes, bring in the attribute-formatting
    // that's common to a product's update and addition.
    //
    // Note: No directory specified for the 'require', since the module is in the same
    // directory as this modal-display handler.
    //
    if (isset($new_product['attributes'])) {
        $updated_product = $new_product;
        require 'eo_attributes_display.php';
    }
?>
                <div class="row text-right">
                    <button id="recalculate-pricing" class="btn btn-info"><?= BUTTON_RECALCULATE ?></button>
                    <button id="add-to-order" class="btn btn-warning"><?= BUTTON_ADD ?></button>
                    <?= zen_draw_hidden_field('prid', $prid) ?>
                    <?= zen_draw_hidden_field('choose_form', $choose_form) ?>
<?php
    if (isset($_POST['keywords'])) {
        echo zen_draw_hidden_field('keywords', $_POST['keywords']);
    }
    if (isset($_POST['categories_id'])) {
        echo zen_draw_hidden_field('categories_id', $_POST['categories_id']);
    }
?>
                </div>
            </form>
        </div>
<?php
}
?>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal"><?= BUTTON_CLOSE ?></button>
</div>
