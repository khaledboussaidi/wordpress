<?php
/*
Plugin Name:Chronopost & Mondial relay pour WooCommerce - WCMultiShipping
Description: Create Chronopost & Mondial relay shipping labels and send them easily.
Version: 1.0.0
Author: WCMultiShipping Team
Author URI: https://www.wcmultishipping.com/fr
License: GPLv2
Text Domain: wc-multishipping
Domain Path: /languages
*/

namespace WCMultiShipping;

use WCMultiShipping\inc\admin\classes\label_class;
use WCMultiShipping\inc\admin\classes\update_class;
use WCMultiShipping\inc\admin\wms_admin_init;
use WCMultiShipping\inc\front\wms_front_init;

defined('ABSPATH') or die('Sorry you can\'t...');

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
include_once __DIR__.DS.'inc'.DS.'helpers'.DS.'wms_helper_helper.php';

/*
 * First install
 */

register_activation_hook(
    __FILE__,
    function () {

        //Create an hourly cron
        register_wms_cron();
    }
);

//Registers an hourly cron
function register_wms_cron()
{
    if (!wp_next_scheduled('update_wms_statuses')) {
        wp_schedule_event(time(), 'hourly', 'update_wms_statuses');
    }
}


/*
 * Init the plugin
 */

function wms_init($hook)
{
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) return;

    if (is_admin() || is_network_admin()) {
        new wms_admin_init();
    }
    else {
        new wms_front_init();
    }
}


add_action('plugins_loaded', __NAMESPACE__.'\\wms_init', 999);
