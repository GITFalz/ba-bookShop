<?php

add_action('admin_menu', function() {
    add_menu_page(
        'Book Shop Admin',
        'Book Shop Admin',
        'manage_options',
        'ba-bookshop-admin',
        'ba_bookshop_admin_panel',
        'dashicons-upload',
        80
    );

    /* keep to remember how to add submenu
    add_submenu_page(
        'ba-bookshop-admin',
        'Analytics',
        'Analytics',
        'manage_options',
        'ai-chatbot-analytics',
        'ai_chatbot_analytics_panel'
    );
    */
});

function ba_bookshop_admin_panel() 
{
    wp_enqueue_style( 
        'ba-bookShop-admin-css', 
        BA_BOOKSHOP_URL . '/src/assets/styles/admin-panel.css',
        array(),
        '1.1'
    );

    include BA_BOOKSHOP_PATH . 'src/templates/adminPanel.php';
}

add_action('woocommerce_payment_complete', function($order_id) {
    $order = wc_get_order($order_id);

    $shipping = [
        'firstName' => $order->get_shipping_first_name(),
        'lastName'  => $order->get_shipping_last_name(),
        'address'   => $order->get_shipping_address_1(),
        'address2'  => $order->get_shipping_address_2(),
        'city'      => $order->get_shipping_city(),
        'zip'       => $order->get_shipping_postcode(),
        'country'   => $order->get_shipping_country(),
    ];

    error_log(print_r($shipping, true));
});

function ba_get_printapi_client()
{
    $helper = new BA_Helper(BA_BOOKSHOP_HELPER);

    $clientID = $helper->get_decrypted("clientID");
    $secret = $helper->get_decrypted("secret");

    if (!$clientID || !$secret)
        return false;

    $client = PrintApi::authenticate($clientID, $secret, 'test');
    return $client;
}