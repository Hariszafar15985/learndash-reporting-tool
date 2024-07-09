<?php

/**
 * Plugin Name:       Learndash Reporting Tool
 * Plugin URI:        provelopers.net
 * Description:       This is the learndash reporting plugin by the PROV Agency.
 * Version:           1.0.0
 * Author:            PROV Agency
 * Author URI:        https://provelopers.net
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       learndash-reporting-tool
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('PROV_REPORTING_VERSION', '1.0.0');

define('PROV_REPORTING_PLUGIN_DIR', plugin_dir_url(__DIR__));
define('PROV_REPORTING_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PROV_REPORTING_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Globalizing option
 */
global $PROV_options;
$PROV_options = get_option('PROV_options');


/**
 * Load plugin strings
 *
 */
function PROV_marketing_load_textdomain()
{
    load_plugin_textdomain('PROV-agency-learndash-reporting-tool', false, dirname(plugin_basename(__FILE__)) . '/languages/');

    require_once(PROV_REPORTING_PLUGIN_PATH . '/inc/shortcodes/PROV-resume-learning.php');
}

add_action('plugins_loaded', 'PROV_marketing_load_textdomain');


/**
 * Reporting Files
 */

require_once(PROV_REPORTING_PLUGIN_PATH . '/inc/PROV-marketing-functions.php');
require_once(PROV_REPORTING_PLUGIN_PATH . '/inc/PROV-reporting-options.php');
require_once(PROV_REPORTING_PLUGIN_PATH . '/inc/elementor/PROV-elementor.php');



/**
 * Adding Scripts Data
 */


add_action('wp_enqueue_scripts', 'PROV_marketing_adding_scripts');

function PROV_marketing_adding_scripts()
{

    //if (is_page_template('PROV-reporting.php')) {
    wp_enqueue_style('percircle', PROV_REPORTING_PLUGIN_URL . 'assets/css/percircle.css');
    wp_enqueue_script('percircle', PROV_REPORTING_PLUGIN_URL . '/assets/js/percircle.js', array('jquery'), PROV_REPORTING_VERSION, false);
    // }

    wp_enqueue_style('PROV-reporting', PROV_REPORTING_PLUGIN_URL . 'assets/css/PROV-reporting.css');
    wp_enqueue_script('PROV-reporting', PROV_REPORTING_PLUGIN_URL . '/assets/js/PROV-reporting.js', array('jquery'), PROV_REPORTING_VERSION, true);

    wp_localize_script(
        'PROV-reporting',
        'PROV_globals',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'ajax_nonce' => wp_create_nonce('ajax-nonce')
        ),
    );
}

/**
 * Allowed User roles
 */

add_filter('ulgm_gm_allowed_roles', 'PROV_reporting_allowed_roles');

function PROV_reporting_allowed_roles($roles_arr)
{

    $roles_arr[] = 'build_educator';
    return $roles_arr;
}
