jQuery(document).ready(function($) {
    // Handle CSV download
    $('#download-csv').on('click', function() {
        window.location.href = '?download_csv=1';
    });
});