<?php

// API
function ba_get_printapi_client()
{
    $helper = new BA_Helper(BA_BOOKSHOP_HELPER);

    $clientID = $helper->get_decrypted("clientID");
    $secret = $helper->get_decrypted("secret");

    if (!$clientID || !$secret)
        return false;

    $type = $helper->get("environment", "test");

    $client = PrintApi::authenticate($clientID, $secret, $type);
    return $client;
}


// Book data
function ba_get_covers() {
    return ba_get_pdfs("cover");
}
function ba_get_contents() {
    return ba_get_pdfs("content");
}

function ba_get_pdfs($type) {
    $args = [
        'post_type'      => 'attachment',
        'post_mime_type' => 'application/pdf',
        'post_status'    => 'inherit',
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'   => 'ba_type',
                'value' => $type,
            ]
        ]
    ];

    $attachments = get_posts($args);

    return array_map(function($attachment) {
        $filepath = get_attached_file($attachment->ID);
        $filesize = file_exists($filepath) ? filesize($filepath) : 0;

        return [
            'id'       => $attachment->ID,
            'name'     => $attachment->post_title,
            'url'      => wp_get_attachment_url($attachment->ID),
            'size_str' => size_format($filesize),
        ];
    }, $attachments);
}

function ba_upload_pdf($post, $type)
{
    if (!check_ajax_referer('ba_upload_handler', 'ba_nonce', false)) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }

    if (empty($_FILES[$post])) {
        wp_send_json_error(['message' => 'No file uploaded']);
    }

    $file = $_FILES[$post];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($ext !== 'pdf') {
        wp_send_json_error(['message' => 'Only PDF files are allowed']);
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $attachment_id = media_handle_upload($post, 0);

    if (is_wp_error($attachment_id)) {
        wp_send_json_error(['message' => $attachment_id->get_error_message()]);
    }

    update_post_meta($attachment_id, 'ba_type', $type);

    $url      = wp_get_attachment_url($attachment_id);
    $filepath = get_attached_file($attachment_id);
    $filesize = filesize($filepath);

    wp_send_json_success([
        'id'       => $attachment_id,
        'name'     => $file['name'],
        'url'      => $url,
        'size'     => $filesize,
        'size_str' => size_format($filesize),
        'type'     => $type,
    ]);
}



// Order
function ba_get_order_data($order_id) {
    $order = wc_get_order($order_id);

    $items = [];

    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();

        $cover_id   = get_post_meta($product_id, 'ba_book_cover', true);
        $content_id = get_post_meta($product_id, 'ba_book_content', true);

        $page_count = get_post_meta($product_id, 'ba_page_count', true);
        $productID = get_post_meta($product_id, 'ba_product_id', true);

        $cover_url   = wp_get_attachment_url($cover_id);
        $content_url = wp_get_attachment_url($content_id);

        if (!$cover_url || !$content_url) {
            error_log("[" . $order_id . "] Missing file for product " . $product_id);
            return [];
        }

        $items[] = [
            'productId' => $productID, //'boek_hc_a5_sta'
            'pageCount' => $page_count ? intval($page_count) : 32,
            'quantity'  => $item->get_quantity(),
            'files'     => [
                'cover'   => $cover_url,
                'content' => $content_url,
            ]
        ];
    }

    $shipping = $order->get_address('shipping');
    $billing  = $order->get_address('billing');

    $address_source = !empty($shipping['address_1']) ? $shipping : $billing;

    return [
        'email' => $order->get_billing_email(),
        'items' => $items,
        'shipping' => [
            'address' => [
                'name' => trim(($address_source['first_name'] ?? '') . ' ' . ($address_source['last_name'] ?? '')),
                'line1' => $address_source['address_1'] ?? '',
                'line2' => $address_source['address_2'] ?? '',
                'postCode' => $address_source['postcode'] ?? '',
                'city' => $address_source['city'] ?? '',
                'country' => $address_source['country'] ?? '',
            ]
        ]
    ];
}

function ba_delete_pdf_handler()
{
    if (!check_ajax_referer('ba_upload_handler', 'ba_nonce', false)) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }

    if (!isset($_POST["ba_id"])) {
        wp_send_json_error(['message' => 'No file id found']);
    }

    $attachment_id = intval($_POST["ba_id"]);

    if (!wp_delete_attachment($attachment_id)) {
        wp_send_json_error(['message' => "Failed to delete file"]);
    }

    wp_send_json_success([
        'id'       => $attachment_id,
    ]);
}
add_action('wp_ajax_ba_delete_pdf', 'ba_delete_pdf_handler');



// Woocommerce
function ba_complete_woocommerce_order($order_id)
{
    $order = wc_get_order($order_id);
    if ($order->get_meta('ba_printapi_order_id')) {
        error_log("[" . $order_id . "] " . "ORDER ALREADY SENT, SKIPPING");
        return;
    }

    error_log("[" . $order_id . "] " . "SENDING ORDER");

    $order_data = ba_get_order_data($order_id);
    
    if (empty($order_data)) {
        error_log("[" . $order_id . "] " . "ORDER DATA EMPTY, ABORTING");
        return;
    }

    error_log("[" . $order_id . "] " . "ORDER DATA");
    error_log(print_r($order_data, true));

    return;

    $print_order = null;

    $api = ba_get_printapi_client();

    if (!$api) {
        error_log("[" . $order_id . "] " . "PRINTAPI CLIENT FAILED TO INIT");
        return;
    }

    try 
    {
        $print_order = $api->post('/orders', $order_data);
        error_log("[" . $order_id . "] " . "ORDER SENT");
    }
    catch (Exception $ex)
    {
        error_log("[" . $order_id . "] " . "AN ERROR OCCURED");
        error_log($ex->getMessage());
    }

    if (isset($print_order)) {
        $order = wc_get_order($order_id);
        $order->update_meta_data('ba_printapi_order_id', $print_order->id);
        $order->update_meta_data('ba_printapi_status', $print_order->status);
        $order->save();

        $order->add_order_note('Print API order created: ' . $print_order->id . ' - status: ' . $print_order->status);
        
        error_log("[" . $order_id . "] " . "ORDER STATUS UPDATED");
    } else {
        $order->update_meta_data('ba_printapi_failed', true);
        $order->save();
        $order->add_order_note('Print API order FAILED. Manual review needed.');
        error_log("[" . $order_id . "] " . 'Print API order failed: ' . print_r($order_data, true));
    }

    error_log("[" . $order_id . "] " . "PRINT ORDER");
    error_log(print_r($print_order, true));
}