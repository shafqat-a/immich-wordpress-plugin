jQuery(document).ready(function($) {
    let searchTimeout;
    const searchDelay = 500;
    const modal = $('#immich-modal');
    const modalClose = $('.immich-modal-close');
    const searchInput = $('#immich-admin-search');
    const resultsContainer = $('#immich-admin-results');
    let editor = null;

    // Handle the media button click
    $('#immich-media-button').on('click', function(e) {
        e.preventDefault();
        editor = tinyMCE.activeEditor;
        modal.show();
        searchInput.focus();
    });

    // Close modal when clicking close button or outside
    modalClose.on('click', function() {
        modal.hide();
    });

    $(window).on('click', function(e) {
        if ($(e.target).is(modal)) {
            modal.hide();
        }
    });

    // Handle search input
    searchInput.on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, searchDelay);
    });

    function performSearch() {
        const query = searchInput.val();
        
        if (query.length < 2) {
            resultsContainer.html('<p>Please enter at least 2 characters to search</p>');
            return;
        }
        
        resultsContainer.html('<div class="immich-loading">Searching...</div>');
        
        $.ajax({
            url: immichAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'immich_search_images',
                nonce: immichAdmin.nonce,
                query: query
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(function(image) {
                        html += `
                            <div class="immich-grid-item" data-id="${image.id}" data-filename="${image.filename}">
                                <img src="${image.thumbnail}" alt="${image.filename}" loading="lazy" />
                            </div>
                        `;
                    });
                    resultsContainer.html(html);
                } else if (response.success && response.data.length === 0) {
                    resultsContainer.html('<p>No images found matching your search.</p>');
                } else {
                    resultsContainer.html('<div class="immich-error">Error: ' + (response.data || 'Unknown error occurred') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                resultsContainer.html('<div class="immich-error">Error connecting to the server: ' + error + '</div>');
            }
        });
    }

    // Handle image selection
    resultsContainer.on('click', '.immich-grid-item', function() {
        const imageId = $(this).data('id');
        const filename = $(this).data('filename');
        
        $(this).addClass('selected').siblings().removeClass('selected');
        
        // Show loading state
        $(this).append('<div class="immich-loading">Importing...</div>');
        
        $.ajax({
            url: immichAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'immich_get_image',
                nonce: immichAdmin.nonce,
                image_id: imageId
            },
            success: function(response) {
                if (response.success) {
                    if (editor) {
                        editor.insertContent(response.data.html);
                    } else {
                        wp.media.editor.insert(response.data.html);
                    }
                    modal.hide();
                } else {
                    alert('Error: ' + (response.data || 'Failed to import image'));
                }
            },
            error: function() {
                alert('Error importing image');
            },
            complete: function() {
                $('.immich-loading').remove();
            }
        });
    });

    // Handle keyboard events
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && modal.is(':visible')) {
            modal.hide();
        }
    });
});