<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2025 The zen-cart developers
//
// Last modified v5.0.0
//
if (empty($product_dropdown) || (in_array('keywords', $hidden_fields) && empty(trim($_POST['keywords'])))) {
?>
<p class="text-center text-danger fw-bold"><?= ERROR_NO_MATCHING_PRODUCT ?></p>
<?php
    return;
}
?>
<form class="form-horizontal" method="post" action="javascript:void(0);">
    <p class="text-center"><?= TEXT_PRODUCT_NEW_SELECT_CHOOSE ?></p>
    <div class="mb-3 mx-3">
        <?= $product_dropdown ?>
    </div>
    <div class="text-center mb-2">
        <button class="btn btn-primary prod-add"><?= BUTTON_CHOOSE ?></button>
<?php
foreach ($hidden_fields as $field_name) {
    echo zen_draw_hidden_field($field_name, $_POST[$field_name]);
}
?>
    </div>
</form>