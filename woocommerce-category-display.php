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

            $product_count = $show_product_count ? $category->count : '';

            $output .= '<div class="wc-category">';
            $output .= '<a href="' . esc_url($category_url) . '">';
            $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($category->name) . '" class="category-image">';
            $output .= '<div class="textData">';
            $output .= '<h3>' . esc_html($category->name) . '</h3>';
            $output .= '<span> ' . $product_count . ' productos </span>';
            $output .= '</div>';
            $output .= '</a>';
            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }
}

new WooCategoryDisplayPlugin();


function custom_product_selection_enqueue_assets()
{
    $screen = get_current_screen();

    if (strpos($screen->id, 'custom-product-selection') === false) {
        return;
    }

    // Load Select2
    wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], null, true);

    // Custom JS to initialize Select2 with images
    wp_add_inline_script('select2-js', '
        jQuery(document).ready(function($) {
            function formatProduct(product) {
                if (!product.id) return product.text;
                var img = $(product.element).data("image");
                if (!img) return product.text;
                return $(\'<span><img src="\' + img + \'" style="width:30px;height:30px;margin-right:10px;vertical-align:middle;" />\' + product.text + \'</span>\');
            }

            $("#custom-product-select").select2({
                templateResult: formatProduct,
                templateSelection: formatProduct,
                width: "100%",
                placeholder: "Search and select products..."
            });
        });
    ');
}
add_action('admin_enqueue_scripts', 'custom_product_selection_enqueue_assets');


// Add Admin Menu Page
function custom_product_selection_menu()
{
    add_menu_page(
        'Product Selection',
        'Product Selection',
        'manage_options',
        'custom-product-selection',
        'custom_product_selection_page'
    );
}
add_action('admin_menu', 'custom_product_selection_menu');

// Display the Admin Page
function custom_product_selection_page()
{
    ?>
    <div class="wrap">
        <h1>Select Products</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('custom_product_selection_group');
            do_settings_sections('custom-product-selection');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register Setting
function custom_product_selection_settings()
{
    register_setting('custom_product_selection_group', 'selected_product_ids');

    add_settings_section(
        'custom_product_selection_section',
        'Select Products to Display',
        '',
        'custom-product-selection'
    );

    add_settings_field(
        'selected_product_ids',
        'Select Products',
        'custom_product_selection_field',
        'custom-product-selection',
        'custom_product_selection_section'
    );
}
add_action('admin_init', 'custom_product_selection_settings');

// Render the Select Products Field
function custom_product_selection_field()
{
    $selected_products = get_option('selected_product_ids', []);
    if (!is_array($selected_products)) {
        $selected_products = [];
    }

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1
    );

    $products = get_posts($args);

    echo '<select id="custom-product-select" name="selected_product_ids[]" multiple="multiple">';
    foreach ($products as $product) {
        $product_id = $product->ID;
        $title = get_the_title($product_id);
        $image_url = get_the_post_thumbnail_url($product_id, 'thumbnail');
        $selected = in_array($product_id, $selected_products) ? 'selected' : '';

        echo '<option value="' . esc_attr($product_id) . '" ' . $selected . ' data-image="' . esc_url($image_url) . '">' . esc_html($title) . '</option>';
    }
    echo '</select>';
}

