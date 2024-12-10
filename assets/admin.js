jQuery(document).ready(function($) {
    $('#sortable-category-list').sortable();

    $('#wc-category-form').on('submit', function(e) {
        e.preventDefault();

        let sortedCategories = [];
        $('#sortable-category-list .category-item').each(function() {
            sortedCategories.push($(this).data('id'));
        });

        let hideEmpty = $('#hide_empty').is(':checked') ? '1' : '0';
        let showProductCount = $('#show_product_count').is(':checked') ? '1' : '0';

        $.post(ajax_object.ajax_url, {
            action: 'save_sorted_categories',
            security: ajax_object.nonce,
            sorted_categories: sortedCategories,
            hide_empty: hideEmpty,
            show_product_count: showProductCount
        }, function(response) {
            if (response.success) {
                alert('Settings saved successfully.');
            } else {
                alert('Failed to save settings.');
            }
        });
    });
});
