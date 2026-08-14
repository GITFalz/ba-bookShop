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

    wp_enqueue_script(
        'ba-bookShop-admin-js',
        BA_BOOKSHOP_URL . '/src/assets/js/admin-panel.js',
        ['jquery'],
        null,
        true
    );

    wp_localize_script('ba-bookShop-admin-js', 'BAData', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ba_upload_handler')
    ]);

    include BA_BOOKSHOP_PATH . 'src/templates/adminPanel.php';
}

add_action('add_meta_boxes', function() {
    add_meta_box(
        'ba_printapi_order',
        'Print API',
        'ba_printapi_metabox',
        'woocommerce_page_wc-orders',
        'side',
        'high'
    );
});

function ba_printapi_metabox($post_or_order) {
    $order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order($post_or_order->ID);
    $print_order_id = $order->get_meta('ba_printapi_order_id');

    if (!$print_order_id) {
        echo '<p>No Print API order found.</p>';
        return;
    }

    $api = ba_get_printapi_client();
    $result = $api->get('/orders/' . $print_order_id);

    if (!$result) {
        echo '<p style="color:red">Failed to fetch Print API order.</p>';
        return;
    }

    ?>
    <table class="widefat" style="font-size:12px">
        <tr>
            <td><strong>Order ID</strong></td>
            <td><?= esc_html($result->id) ?></td>
        </tr>
        <tr>
            <td><strong>Status</strong></td>
            <td><?= esc_html($result->status) ?></td>
        </tr>
        <tr>
            <td><strong>Date</strong></td>
            <td><?= esc_html(date('d/m/Y H:i', strtotime($result->dateTime))) ?></td>
        </tr>
        <tr>
            <td><strong>Production</strong></td>
            <td><?= esc_html($result->productionSpeed) ?></td>
        </tr>
        <tr>
            <td><strong>Shipping</strong></td>
            <td><?= esc_html($result->shipping->method->name) ?></td>
        </tr>
        <tr>
            <td><strong>Trackable</strong></td>
            <td><?= $result->shipping->method->isTrackable ? 'Yes' : 'No' ?></td>
        </tr>
        <tr>
            <td><strong>Total</strong></td>
            <td>€<?= esc_html($result->invoice->total->cost) ?></td>
        </tr>
        <tr>
            <td><strong>File status</strong></td>
            <td>
                <?php foreach ($result->items as $item): ?>
                    Cover: <?= esc_html($item->files->cover->status) ?><br>
                    Content: <?= esc_html($item->files->content->status) ?>
                <?php endforeach; ?>
            </td>
        </tr>
    </table>

    <?php if ($order->get_meta('ba_printapi_failed')): ?>
        <p style="color:red; margin-top:8px">⚠ This order previously failed.</p>
    <?php endif; ?>
    <?php
}


// PAYMENTS
function ba_complete_woocommerce_order_handler($order_id)
{    
    ba_complete_woocommerce_order($order_id);
}
add_action('woocommerce_payment_complete', "ba_complete_woocommerce_order_handler");

function ba_upload_cover_handler()
{
    ba_upload_pdf("ba_cover", "cover");
}
add_action('wp_ajax_ba_upload_cover', 'ba_upload_cover_handler');

function ba_upload_content_handler()
{
    ba_upload_pdf("ba_content", "content");
}
add_action('wp_ajax_ba_upload_content', 'ba_upload_content_handler');


// WOOCOMMERCE
function ba_add_book_meta_box() {
    add_meta_box(
        'ba_book_files',
        'Book Files',
        'ba_book_meta_box_html',
        'product',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'ba_add_book_meta_box');

function ba_book_meta_box_html($post) {
    $covers   = ba_get_covers();
    $contents = ba_get_contents(); // your equivalent for content PDFs

    $selected_cover   = get_post_meta($post->ID, 'ba_book_cover', true);
    $selected_content = get_post_meta($post->ID, 'ba_book_content', true);
    $page_count = get_post_meta($post->ID, 'ba_page_count', true);
    $product_id = get_post_meta($post->ID, 'ba_product_id', true);

    wp_nonce_field('ba_book_files', 'ba_book_files_nonce');
    ?>

    <p>
        <label>Cover PDF</label><br>
        <select name="ba_book_cover">
            <option value="">— Select a cover —</option>
            <?php foreach ($covers as $cover) : ?>
                <option value="<?= $cover['id'] ?>" <?= selected($selected_cover, $cover['id'], false) ?>>
                    <?= esc_html($cover['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>

    <p>
        <label>Content PDF</label><br>
        <select name="ba_book_content">
            <option value="">— Select content —</option>
            <?php foreach ($contents as $content) : ?>
                <option value="<?= $content['id'] ?>" <?= selected($selected_content, $content['id'], false) ?>>
                    <?= esc_html($content['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>

    <p>
        <label>Page count</label>
        <input type="number" name="ba_page_count" value="<?=$page_count?>">
    </p>

    <p>
        <label>Product ID (found in Print API product list)</label>
        <input type="text" name="ba_product_id" value="<?=$product_id?>">
    </p>

    <?php
}
add_action('save_post_product', 'ba_save_book_meta');

function ba_save_book_meta($post_id) {
    if (!isset($_POST['ba_book_files_nonce'])) return;
    if (!wp_verify_nonce($_POST['ba_book_files_nonce'], 'ba_book_files')) return;

    if (isset($_POST['ba_book_cover'])) {
        update_post_meta($post_id, 'ba_book_cover', sanitize_text_field($_POST['ba_book_cover']));
    }
    if (isset($_POST['ba_book_content'])) {
        update_post_meta($post_id, 'ba_book_content', sanitize_text_field($_POST['ba_book_content']));
    }
    if (isset($_POST['ba_page_count'])) {
        update_post_meta($post_id, 'ba_page_count', intval($_POST['ba_page_count']));
    }
    if (isset($_POST['ba_product_id'])) {
        update_post_meta($post_id, 'ba_product_id', sanitize_text_field($_POST['ba_product_id']));
    }
}