<div class="wrap">
    <h1>WooCommerce Category Display</h1>

    <form id="wc-category-form">
        <h2>Select and Sort Categories</h2>

        <ul id="sortable-category-list">
            <?php foreach ($product_categories as $category):
                $image_id = get_term_meta($category->term_id, 'thumbnail_id', true);
                $image_url = $image_id ? wp_get_attachment_url($image_id) : wc_placeholder_img_src();
                ?>
                <li class="category-item" data-id="<?php echo esc_attr($category->term_id); ?>">
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($category->name); ?>"
                        class="category-icon">
                    <?php echo esc_html($category->name); ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <label>
            <input type="checkbox" id="hide_empty" name="hide_empty" value="1" <?php checked($hide_empty, '1'); ?>>
            Hide empty categories
        </label>
        <br>
        <br>
        <label>
            <input type="checkbox" id="show_product_count" name="show_product_count" value="1" <?php checked($show_product_count, '1'); ?>>
            Show product count
        </label>
        <br>
        <br>
        <br>
        <button type="submit" class="button button-primary">Save Changes</button>
    </form>
</div>