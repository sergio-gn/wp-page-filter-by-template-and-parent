<?php
/**
 * Plugin Name: Custom Template Filter Plugin
 * Description: Adds a custom filter to the admin pages section to filter pages by template.
 * Version: 1.0
 * Author: Serg
 */

// Add custom dropdown filter to admin pages section
function custom_page_filter() {
    global $wpdb;

    $selected_parent = isset($_GET['parent_filter']) ? intval($_GET['parent_filter']) : 0;

    $parent_pages = get_pages(array(
        'parent' => 0,
        'post_type' => 'page',
    ));

    echo '<select name="parent_filter">';
    echo '<option value="0">All Parents</option>';
    foreach ($parent_pages as $parent_page) {
        echo '<option value="' . $parent_page->ID . '"' . selected($selected_parent, $parent_page->ID, false) . '>' . $parent_page->post_title . '</option>';
    }
    echo '</select>';
}

add_action('restrict_manage_posts', 'custom_page_filter');

// Modify page query based on custom filter
function modify_page_query($query) {
    global $pagenow, $wpdb;

    if (is_admin() && $pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'page' && isset($_GET['parent_filter']) && intval($_GET['parent_filter']) > 0) {
        $parent_id = intval($_GET['parent_filter']);
        $query->query_vars['post_parent'] = $parent_id;
    }
}

add_filter('parse_query', 'modify_page_query');

// Add custom dropdown filter for page templates to admin pages section
function custom_template_filter() {
    global $wpdb;

    if (isset($_GET['post_type']) && $_GET['post_type'] == 'page') {
        $selected_template = isset($_GET['template_filter']) ? sanitize_text_field($_GET['template_filter']) : '';

        $page_templates = wp_get_theme()->get_page_templates();

        echo '<select name="template_filter">';
        echo '<option value="">All Templates</option>';
        foreach ($page_templates as $template_file => $template_name) {
            echo '<option value="' . esc_attr($template_file) . '"' . selected($selected_template, $template_file, false) . '>' . esc_html($template_name) . '</option>';
        }
        echo '</select>';
    }
}

add_action('restrict_manage_posts', 'custom_template_filter');

// Modify page query based on custom template filter
function modify_template_query($query) {
    global $pagenow, $wpdb;

    if (is_admin() && $pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'page' && isset($_GET['template_filter']) && !empty($_GET['template_filter'])) {
        $template = sanitize_text_field($_GET['template_filter']);
        $query->query_vars['meta_key'] = '_wp_page_template';
        $query->query_vars['meta_value'] = $template;
    }
}

add_filter('parse_query', 'modify_template_query');

// Activate the plugin
function custom_template_filter_plugin_activate() {
    // No specific activation code needed in this case
}
register_activation_hook(__FILE__, 'custom_template_filter_plugin_activate');

// Deactivate the plugin
function custom_template_filter_plugin_deactivate() {
    // Remove the actions and filters added by the plugin
    remove_action('restrict_manage_posts', 'custom_page_filter');
    remove_action('admin_menu', 'custom_template_filter');
    remove_filter('parse_query', 'modify_page_query');
    remove_filter('parse_query', 'modify_template_query');
}
register_deactivation_hook(__FILE__, 'custom_template_filter_plugin_deactivate');