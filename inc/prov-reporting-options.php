<?php

/**
 * Class Aloa_Reporting_Settings
 *
 * Configure the plugin settings page.
 */
class Aloa_Reporting_Settings
{

    /**
     * Capability required by the user to access the My Plugin menu entry.
     *
     * @var string $capability
     */
    private $capability = 'manage_options';

    /**
     * Array of fields that should be displayed in the settings page.
     *
     * @var array $fields
     */
    private function aloa_option_fields()
    {

        $fields = [
            [
                'id' => 'reporting-page',
                'label' => 'Reporting Page',
                'description' => 'select reporting page.',
                'type' => 'select',
                'options' => $this->get_posts('page'),
            ],
            [
                'id' => 'new-group-page-id',
                'label' => 'Add New Group Page',
                'description' => 'select a new group page.',
                'type' => 'select',
                'options' => $this->get_posts('page'),
            ],
            [
                'id' => 'course-id',
                'label' => 'Course Post',
                'description' => 'select course post.',
                'type' => 'select',
                'options' => $this->get_posts('sfwd-courses'),
            ],
            [
                'id' => 'loggedin-allowed-pages',
                'label' => 'Login Allowed Pages',
                'description' => 'Choose each page that you prefer to send users who aren\'t logged in to the homepage..',
                'type' => 'select_multiple',
                'options' => $this->get_posts('page'),
            ],
            [
                'id' => 'student-restrict-pages',
                'label' => 'Student Restrict Pages',
                'description' => 'Choose each page that you prefer to send students to the Account Page.',
                'type' => 'select_multiple',
                'options' => $this->get_posts('page'),
            ],
			[
                'id' => 'group-added-message',
                'label' => 'Group Addded Page Text',
                'description' => 'Enter the acknowledgement message to be shown after group created.',
                'type' => 'textarea',
            ],
            [
                'id' => 'educator-signup-form',
                'label' => 'Select Educator Signup Form',
                'description' => 'Chooose a form for educator registration.',
                'type' => 'select',
                'options' => $this->get_posts('wpforms'),
            ],
            [
                'id' => 'student-signup-form',
                'label' => 'Select Student Signup Form',
                'description' => 'Chooose a form for student registration.',
                'type' => 'select',
                'options' => $this->get_posts('wpforms'),
            ]
        ];

        return $fields;
    }

    /**
     * The Plugin Settings constructor.
     */
    function __construct()
    {
        add_action('admin_init', [$this, 'settings_init']);
        add_action('admin_menu', [$this, 'options_page']);
    }

    /**
     * Register the settings and all fields.
     */
    function settings_init(): void
    {

        // Register a new setting this page.
        register_setting('aloa-settings', 'aloa_options');


        // Register a new section.
        add_settings_section(
            'aloa-settings-section',
            __('General Settings', 'aloa-agency-learndash-reporting-tool'),
            [$this, 'render_section'],
            'aloa-settings'
        );


        /* Register All The Fields. */
        foreach ($this->aloa_option_fields() as $field) {
            // Register a new field in the main section.
            add_settings_field(
                $field['id'], /* ID for the field. Only used internally. To set the HTML ID attribute, use $args['label_for']. */
                __($field['label'], 'aloa-agency-learndash-reporting-tool'), /* Label for the field. */
                [$this, 'render_field'], /* The name of the callback function. */
                'aloa-settings', /* The menu page on which to display this field. */
                'aloa-settings-section', /* The section of the settings page in which to show the box. */
                [
                    'label_for' => $field['id'], /* The ID of the field. */
                    'class' => 'wporg_row', /* The class of the field. */
                    'field' => $field, /* Custom data for the field. */
                ]
            );
        }
    }

    /**
     * Add a subpage to the WordPress Settings menu.
     */
    function options_page(): void
    {
        add_menu_page(
            'Aloa Settings', /* Page Title */
            'Aloa Reporting', /* Menu Title */
            $this->capability, /* Capability */
            'aloa-settings', /* Menu Slug */
            [$this, 'render_options_page'], /* Callback */
            'dashicons-admin-tools', /* Icon */
            '4', /* Position */
        );
    }

    /**
     * Get all pages
     */

    function get_posts($type = 'page')
    {

        $args = array(
            'post_type' => $type,
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );

        $query = new WP_Query($args);

        $return_arr = array('' => sprintf(__("Select %s", 'aloa-agency-learndash-reporting-tool'), $type));

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $return_arr[get_the_ID()] = get_the_title();
            }
            wp_reset_postdata();
        }

        return $return_arr;
    }


    /**
     * Render the settings page.
     */
    function render_options_page(): void
    {

        // check user capabilities
        if (!current_user_can($this->capability)) {
            return;
        }

        // add error/update messages

        // check if the user have submitted the settings
        // WordPress will add the "settings-updated" $_GET parameter to the url
        if (isset($_GET['settings-updated'])) {
            // add settings saved message with the class of "updated"
            add_settings_error('wporg_messages', 'wporg_message', __('Settings Saved', 'aloa-agency-learndash-reporting-tool'), 'updated');
        }

        // show error/update messages
        settings_errors('wporg_messages');
?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <h2 class="description"></h2>
            <form action="options.php" method="post">
                <?php
                /* output security fields for the registered setting "wporg" */
                settings_fields('aloa-settings');
                /* output setting sections and their fields */
                /* (sections are registered for "wporg", each field is registered to a specific section) */
                do_settings_sections('aloa-settings');
                /* output save settings button */
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render a settings field.
     *
     * @param array $args Args to configure the field.
     */
    function render_field(array $args): void
    {

        global $aloa_options;
        $field = $args['field'];

        // Get the value of the setting we've registered with register_setting()
        $aloa_options = get_option('aloa_options');

        switch ($field['type']) {

            case "select": {

        ?>
                    <select id="<?php echo esc_attr($field['id']); ?>" name="aloa_options[<?php echo esc_attr($field['id']); ?>]">
                        <?php foreach ($field['options'] as $key => $option) { ?>
                            <option value="<?php echo $key; ?>" <?php echo isset($aloa_options[$field['id']]) ? (selected($aloa_options[$field['id']], $key, false)) : (''); ?>>
                                <?php echo $option; ?>
                            </option>
                        <?php } ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e($field['description'], 'aloa-agency-learndash-reporting-tool'); ?>
                    </p>
                <?php
                    break;
                }

            case "select_multiple": {
                ?>
                    <select id="<?php echo esc_attr($field['id']); ?>" name="aloa_options[<?php echo esc_attr($field['id']); ?>][]" multiple>
                        <?php foreach ($field['options'] as $key => $option) {

                            $select_val = isset($aloa_options[$field['id']]) && !empty($aloa_options[$field['id']]) && is_array($aloa_options[$field['id']]) && sizeof($aloa_options[$field['id']]) > 0 && in_array($key, ($aloa_options[$field['id']])) ? ' selected="selected" ' : '';
                        ?>
                            <option value="<?php echo $key; ?>" <?php echo $select_val; ?>>
                                <?php echo $option; ?>
                            </option>
                        <?php } ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e($field['description'], 'aloa-agency-learndash-reporting-tool'); ?>
                    </p>
        <?php
                    break;
                }
				
			case "textarea": {
                ?>
                    <textarea id="<?php echo esc_attr($field['id']); ?>" name="aloa_options[<?php echo esc_attr($field['id']); ?>]" rows="6" cols="80"><?php echo isset($aloa_options[$field['id']]) ? $aloa_options[$field['id']] : ''; ?></textarea>
                    <p class="description">
                        <?php esc_html_e($field['description'], 'aloa-agency-learndash-reporting-tool'); ?>
                    </p>
        <?php
                    break;
                }
        }
    }


    /**
     * Render a section on a page, with an ID and a text label.
     *
     * @since 1.0.0
     *
     * @param array $args {
     *     An array of parameters for the section.
     *
     *     @type string $id The ID of the section.
     * }
     */
    function render_section(array $args): void
    {
        ?>
        <p id="<?php echo esc_attr($args['id']); ?>"><?php esc_html_e('Aloa Settings', 'aloa-agency-learndash-reporting-tool'); ?></p>
<?php
    }
}

new Aloa_Reporting_Settings();
