<?php
/*
Plugin Name: Cross-sell Discount Test
Description: Apply discount to cross-sell products in WooCommerce
Version: 1.0
Author: ChatGPT & ybabel
*/

// add settings field to the WooCommerce settings page
add_filter('woocommerce_general_settings', 'cross_sell_discount_add_settings');
function cross_sell_discount_add_settings($settings){
    $settings[] = array(
        'title' => __('Cross-sell Discount', 'cross-sell-discount'),
        'type' => 'title',
        'id' => 'cross_sell_discount_title',
    );

    $settings[] = array(
        'title' => __('Discount Percentage', 'cross-sell-discount'),
        'id' => 'cross_sell_discount_percentage',
        'type' => 'text',
        'desc' => __('Enter the discount percentage to apply to cross-sell products.', 'cross-sell-discount'),
        'default' => '',
        'desc_tip' => true,
    );

    $settings[] = array(
        'type' => 'sectionend',
        'id' => 'cross_sell_discount_section_end',
    );

    return $settings;
}

add_action('woocommerce_before_calculate_totals', 'cross_sell_discount_apply');
function cross_sell_discount_apply(){
    if (is_admin()) {
        return;
    }

    $percentage = floatval(get_option('cross_sell_discount_percentage'));
    if ($percentage <= 0) {
        return;
    }

    $cart = WC()->cart;
    if ($cart->is_empty()) {
        return;
    }

    // Récupération des IDs des produits en cross-sell
    $cross_sell_ids = array();
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        if ($product->is_type('simple') && $product->get_cross_sell_ids()) {
            foreach ($product->get_cross_sell_ids() as $cross_sell_id) {
                $cross_sell_ids[] = $cross_sell_id;
            }
        }
    }
    $cross_sell_ids = array_unique($cross_sell_ids);

    // Application de la réduction aux produits en cross-sell
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        if ($product->is_type('simple') && in_array($product->get_id(), $cross_sell_ids)) {
            $product_price = $product->get_price();
            $new_price = $product_price * (1 - $percentage / 100);
            $cart_item['data']->set_sale_price($product_price);
            $cart_item['data']->set_price($new_price);
            add_filter('woocommerce_cart_item_price', 'cross_sell_discount_cart_item_price', 10, 3);
        }
    }
}

function cross_sell_discount_cart_item_price($product_price, $cart_item, $cart_item_key){
    $percentage = floatval(get_option('cross_sell_discount_percentage'));

    if ($percentage > 0 && $cart_item['data']->get_id() == $cart_item['product_id'] && $cart_item['data']->get_sale_price() != 0) {
        $product_price_html = wc_price($cart_item['data']->get_price());
        $product_sale_price_html = wc_price($cart_item['data']->get_sale_price());

        $product_price = sprintf('<del>%s</del> %s', $product_sale_price_html, $product_price_html);
    }

    return $product_price;
}
