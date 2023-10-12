<?php
/**
 * Plugin Name: Page Filter By Template
 * Description: Adds custom filters to the admin pages section to filter pages by template and parent page.
 * Version: 1.1
 * Author: Serg
 */

// Add custom dropdown filter for page templates to admin pages section
function custom_template_filter() {
    global $wpdb;

    $selected_template = isset($_GET['template_filter']) ? sanitize_text_field($_GET['template_filter']) : '';

    $page_templates = wp_get_theme()->get_page_templates();

    echo '<select name="template_filter">';
    echo '<option value="">All Templates</option>';
    foreach ($page_templates as $template_file => $template_name) {
        echo '<option value="' . esc_attr($template_file) . '"' . selected($selected_template, $template_file, false) . '>' . esc_html($template_name) . '</option>';
    }
    echo '</select>';
}

add_action('restrict_manage_posts', 'custom_template_filter');

// Add custom dropdown filter for parent pages to admin pages section
function custom_page_filter() {
    global $wpdb;

    $selected_parent = isset($_GET['parent_filter']) ? intval($_GET['parent_filter']) : 0;

    $parent_pages = get_pages(array(
        'parent' => 0,
        //create a logic for this after - display parents regardless if they are children or not 'post_type' => 'page',
    ));

    echo '<select name="parent_filter">';
    echo '<option value="0">All Parents</option>';
    echo '<option value="-1">No Parent</option>'; // Add the "No Parent" option
    foreach ($parent_pages as $parent_page) {
        echo '<option value="' . $parent_page->ID . '"' . selected($selected_parent, $parent_page->ID, false) . '>' . $parent_page->post_title . '</option>';
    }
    echo '</select>';
}

add_action('restrict_manage_posts', 'custom_page_filter');

// Modify page query based on custom filters
function modify_page_query($query) {
    global $pagenow, $wpdb;

    if (is_admin() && $pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'page') {
        $selected_template = isset($_GET['template_filter']) ? sanitize_text_field($_GET['template_filter']) : '';
        $selected_parent = isset($_GET['parent_filter']) ? intval($_GET['parent_filter']) : 0;

        if (!empty($selected_template)) {
            $query->query_vars['meta_key'] = '_wp_page_template';
            $query->query_vars['meta_value'] = $selected_template;
        }

        if ($selected_parent === -1) {
            // Filter for pages without a parent using a custom query variable
            $query->query_vars['custom_parent_filter'] = 1;
        } elseif ($selected_parent > 0) {
            // Filter for pages with the selected parent
            $query->query_vars['post_parent'] = $selected_parent;
        }
    }
}

add_filter('parse_query', 'modify_page_query');

// Filter pages without a parent using the custom query variable
function filter_pages_without_parent($where, $query) {
    global $wpdb;

    if (isset($query->query_vars['custom_parent_filter']) && $query->query_vars['custom_parent_filter'] == 1) {
        $where .= " AND {$wpdb->posts}.post_parent = 0";
    }

    return $where;
}

add_filter('posts_where', 'filter_pages_without_parent', 10, 2);

// Activate the plugin
function custom_template_filter_plugin_activate() {
    // No specific activation code needed in this case
}
register_activation_hook(__FILE__, 'custom_template_filter_plugin_activate');

// Deactivate the plugin
function custom_template_filter_plugin_deactivate() {
    // Remove the actions and filters added by the plugin
    remove_action('restrict_manage_posts', 'custom_template_filter');
    remove_action('restrict_manage_posts', 'custom_page_filter');
    remove_filter('parse_query', 'modify_page_query');
    remove_filter('posts_where', 'filter_pages_without_parent');
}
register_deactivation_hook(__FILE__, 'custom_template_filter_plugin_deactivate');
