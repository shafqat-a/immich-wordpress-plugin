jQuery(document).ready(function($) {
    let searchTimeout;
    const searchDelay = 500; // Delay in milliseconds

    $('#immich-search-form').on('submit', function(e) {
        e.preventDefault();
        performSearch();
    });

    $('#immich-search-input').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, searchDelay);
    });

    function performSearch() {
        const searchQuery = $('#immich-search-input').val();
        const resultsContainer = $('#immich-search-results');
        
        if (searchQuery.length < 2) {
            resultsContainer.html('<p>Please enter at least 2 characters to search</p>');
            return;
        }
        
        resultsContainer.html('<p>Searching...</p>');
        
        $.ajax({
            url: immichAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'immich_search_images',
                nonce: immichAjax.nonce,
                query: searchQuery
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(function(image) {
                        html += `
                            <div class="immich-image-item">
                                <img src="${image.thumbnail}" 
                                     alt="${image.filename}"
                                     loading="lazy"
                                     title="${image.filename}" />
                            </div>
                        `;
                    });
                    resultsContainer.html(html);
                } else if (response.success && response.data.length === 0) {
                    resultsContainer.html('<p>No images found matching your search.</p>');
                } else {
                    resultsContainer.html('<p>Error: ' + (response.data || 'Unknown error occurred') + '</p>');
                }
            },
            error: function(xhr, status, error) {
                resultsContainer.html('<p>Error connecting to the server: ' + error + '</p>');
            }
        });
    }
});