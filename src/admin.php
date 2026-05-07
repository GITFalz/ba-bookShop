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
    include BA_BOOKSHOP_PATH . 'src/templates/adminPanel.php';
}