<?php
/**
 * Plugin Name: BA Book Shop
 * Description: Book shop for your website, BuroAmstelveen.
 * Version:     1.3.0
 * Author:      Bjornar Schinkel
 */

if (!defined('ABSPATH')) {
    exit; 
}

define('BA_BOOKSHOP_PATH', plugin_dir_path(__FILE__));
define('BA_BOOKSHOP_URL', plugin_dir_url(__FILE__));
define('BA_BOOKSHOP_VERSION', '1.3.0');
define('BA_BOOKSHOP_HELPER', 'ba_bookshop_helper');

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

require_once BA_BOOKSHOP_PATH . 'src/includes/functions.php';
require_once BA_BOOKSHOP_PATH . 'src/includes/helper.php';
require_once BA_BOOKSHOP_PATH . 'src/includes/encryption.php';
require_once BA_BOOKSHOP_PATH . 'src/printapi-php/printapi.php';

require_once BA_BOOKSHOP_PATH . 'src/admin.php';

function ba_bookshop_activate() 
{

}
register_activation_hook(__FILE__, 'ba_bookshop_activate');

function ba_bookshop_deactivate() 
{
    $helper = new BA_Helper(BA_BOOKSHOP_HELPER);
    $helper->clear("clientID");
    $helper->clear("secret");
    $helper->clear("environment");
}
register_deactivation_hook(__FILE__, 'ba_bookshop_deactivate');

function ba_bookshop_init() 
{

}
add_action('init', 'ba_bookshop_init');

function ba_bookshop_update_check()
{
    $installed = get_option('ba_bookshop_version');

    if ($installed !== BA_BOOKSHOP_VERSION)
    {
        //ba_bookshop_run_updates($installed);
        update_option('ba_bookshop_version', BA_BOOKSHOP_VERSION);
    }
}

add_action('plugins_loaded', 'ba_bookshop_update_check');



require_once BA_BOOKSHOP_PATH . 'vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';

$updateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/GITFalz/ba-bookShop/',
    __FILE__,
    'ba-bookShop'
);

$updateChecker->setBranch('main');
