<?php
/**
 * Plugin Name: WooCommerce Category Display
 * plugin URI: https://souloftware.com/
 * Description: A plugin to display WooCommerce categories with sorting, product count, and hide-empty options.
 * Version: 1.0.0
 * Author: Souloftware
 * Author URI: https://souloftware.com/contact
 */

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class WooCategoryDisplayPlugin
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'create_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_save_sorted_categories', [$this, 'save_sorted_categories']);
        add_shortcode('woocommerce_category_display', [$this, 'display_categories_shortcode']);
    }

    /**
     * Create the admin menu page
     */
    public function create_admin_page()
    {
        add_menu_page(
            'Category Display',
            'Category Display',
            'manage_options',
            'category-display',
            [$this, 'admin_page_html'],
            'dashicons-list-view',
            20
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook)
    {
        if ($hook !== 'toplevel_page_category-display')
            return;

        wp_enqueue_style('wc-category-admin-style', plugin_dir_url(__FILE__) . 'assets/admin.css');
        wp_enqueue_script('wc-category-admin-script', plugin_dir_url(__FILE__) . 'assets/admin.js', ['jquery', 'jquery-ui-sortable'], null, true);

        wp_localize_script('wc-category-admin-script', 'ajax_object', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_category_nonce')
        ]);
    }

    /**
     * Render the admin page
     */
    public function admin_page_html()
    {
        $saved_categories = get_option('wc_sorted_categories', []);
        $hide_empty = get_option('wc_hide_empty', false);
        $show_product_count = get_option('wc_show_product_count', false);

        $product_categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false
        ]);

        if (is_wp_error($product_categories))
            return;

        require_once plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    /**
     * Handle the AJAX request to save sorted categories
     */
    public function save_sorted_categories()
    {
        check_ajax_referer('wc_category_nonce', 'security');

        $sorted_categories = isset($_POST['sorted_categories']) ? array_map('sanitize_text_field', $_POST['sorted_categories']) : [];
        $hide_empty = isset($_POST['hide_empty']) ? sanitize_text_field($_POST['hide_empty']) : '0';
        $show_product_count = isset($_POST['show_product_count']) ? sanitize_text_field($_POST['show_product_count']) : '0';

        update_option('wc_sorted_categories', $sorted_categories);
        update_option('wc_hide_empty', $hide_empty);
        update_option('wc_show_product_count', $show_product_count);

        wp_send_json_success('Categories saved successfully');
    }

    /**
     * Shortcode to display the categories
     */
    public function display_categories_shortcode($atts)
    {
        $sorted_categories = get_option('wc_sorted_categories', []);
        $hide_empty = get_option('wc_hide_empty', false);
        $show_product_count = get_option('wc_show_product_count', false);

        if (empty($sorted_categories))
            return '<p>No categories selected to display.</p>';

        $output = '<div class="wc-categories-container">';

        foreach ($sorted_categories as $category_id) {
            $category = get_term($category_id, 'product_cat');

            if ($hide_empty && $category->count == 0)
                continue;

            // Get category image and URL
            $image_id = get_term_meta($category->term_id, 'thumbnail_id', true);
            $image_url = $image_id ? wp_get_attachment_url($image_id) : wc_placeholder_img_src();
            $category_url = get_term_link($category);

            $product_count = $show_product_count ? ' (' . $category->count . ')' : '';

            $output .= '<div class="wc-category">';
            $output .= '<a href="' . esc_url($category_url) . '">';
            $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($category->name) . '" class="category-image">';
            $output .= '<h3>' . esc_html($category->name) . $product_count . '</h3>';
            $output .= '</a>';
            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }
}

new WooCategoryDisplayPlugin();
