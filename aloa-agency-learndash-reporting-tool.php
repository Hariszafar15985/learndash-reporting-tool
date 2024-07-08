<?php

/**
 * Plugin Name:       ALOA Agency Learndash Reporting Tool
 * Plugin URI:        https://aloa-agency.net
 * Description:       This is the learndash reporting plugin by the ALOA Agency.
 * Version:           1.0.0
 * Author:            Aloa Agency
 * Author URI:        https://aloa-agency.net
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       aloa-agency-learndash-reporting-tool
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('ALOA_REPORTING_VERSION', '1.0.0');

define('ALOA_REPORTING_PLUGIN_DIR', plugin_dir_url(__DIR__));
define('ALOA_REPORTING_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ALOA_REPORTING_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Globalizing option
 */
global $aloa_options;
$aloa_options = get_option('aloa_options');


/**
 * Load plugin strings
 *
 */
function aloa_marketing_load_textdomain()
{
    load_plugin_textdomain('aloa-agency-learndash-reporting-tool', false, dirname(plugin_basename(__FILE__)) . '/languages/');

    require_once(ALOA_REPORTING_PLUGIN_PATH . '/inc/shortcodes/aloa-resume-learning.php');
}

add_action('plugins_loaded', 'aloa_marketing_load_textdomain');


/**
 * Reporting Files
 */

require_once(ALOA_REPORTING_PLUGIN_PATH . '/inc/aloa-marketing-functions.php');
require_once(ALOA_REPORTING_PLUGIN_PATH . '/inc/aloa-reporting-options.php');
require_once(ALOA_REPORTING_PLUGIN_PATH . '/inc/elementor/aloa-elementor.php');



/**
 * Adding Scripts Data
 */


add_action('wp_enqueue_scripts', 'aloa_marketing_adding_scripts');

function aloa_marketing_adding_scripts()
{

    //if (is_page_template('aloa-reporting.php')) {
    wp_enqueue_style('percircle', ALOA_REPORTING_PLUGIN_URL . 'assets/css/percircle.css');
    wp_enqueue_script('percircle', ALOA_REPORTING_PLUGIN_URL . '/assets/js/percircle.js', array('jquery'), ALOA_REPORTING_VERSION, false);
    // }

    wp_enqueue_style('aloa-reporting', ALOA_REPORTING_PLUGIN_URL . 'assets/css/aloa-reporting.css');
    wp_enqueue_script('aloa-reporting', ALOA_REPORTING_PLUGIN_URL . '/assets/js/aloa-reporting.js', array('jquery'), ALOA_REPORTING_VERSION, true);

    wp_localize_script(
        'aloa-reporting',
        'aloa_globals',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'ajax_nonce' => wp_create_nonce('ajax-nonce')
        ),
    );
}

/**
 * Allowed User roles
 */

add_filter('ulgm_gm_allowed_roles', 'aloa_reporting_allowed_roles');

function aloa_reporting_allowed_roles($roles_arr)
{

    $roles_arr[] = 'build_educator';
    return $roles_arr;
}
