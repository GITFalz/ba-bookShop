<?php
/**
 * Plugin Name: BA Book Shop
 * Description: Book shop for your website, BuroAmstelveen.
 * Version:     0.0.1
 * Author:      Bjornar Schinkel
 */

if (!defined('ABSPATH')) {
    exit; 
}

define('BA_BOOK_SHOP_PATH', plugin_dir_path(__FILE__));
define('BA_BOOK_SHOP_URL', plugin_dir_url(__FILE__));
define('BA_BOOK_SHOP_VERSION', '0.0.1');

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

function ba_bookshop_activate() 
{

}
register_activation_hook(__FILE__, 'ba_bookshop_activate');

function ba_bookshop_deactivate() 
{
    
}
register_deactivation_hook(__FILE__, 'ba_bookshop_deactivate');

function ba_bookshop_init() 
{

}
add_action('init', 'ba_bookshop_init');

function ba_bookshop_update_check()
{
    $installed = get_option('ba_bookshop_version');

    if ($installed !== BA_BOOK_SHOP_VERSION)
    {
        ba_bookshop_run_updates($installed);
        update_option('ba_bookshop_version', BA_BOOK_SHOP_VERSION);
    }
}

add_action('plugins_loaded', 'ba_bookshop_update_check');



require_once BA_BOOK_SHOP_PATH . 'vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';

$updateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/GITFalz/ba-bookShop/',
    __FILE__,
    'ba-bookShop'
);

$updateChecker->setBranch('main');
