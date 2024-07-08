<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('ALOA_REPORTING_ELEMENTOR_DIR', plugin_dir_url(__FILE__));
define('ALOA_REPORTING_ELEMENTOR_URL', plugin_dir_url(__DIR__));
define('ALOA_REPORTING_ELEMENTOR_PATH', plugin_dir_path(__FILE__));

/**
 * Register Custom Widget.
 *
 * Include widget file and register widget class.
 */
function aloa_register_widgets($widgets_manager) {

    /*
     * ALOA Widgets
     */

    require_once( __DIR__ . '/widgets/aloa-reporting-group.php' );
    $widgets_manager->register(new Elementor_Aloa_Reporting_Group());

}

add_action('elementor/widgets/register', 'aloa_register_widgets');

/*
 * Register Elementor widget type
 */

function aloa_register_widgets_section_category($category_manager) {
    $category_manager->add_category(
            'aloa_elementor', [
        'title' => __('ALOA Widgets', 'aloa-agency-learndash-reporting-tool'),
        'icon' => 'fa fa-home',
            ]
    );
}

add_action('elementor/elements/categories_registered', 'aloa_register_widgets_section_category');