jQuery(document).ready(function($) {
    let rowsPerPage = 10; // Default rows per page
    let currentPage = 1;
    let filteredColumns = []; // Track visible columns

    // Open filters popup
    $('#open-filters').on('click', function() {
        $('#filters-popup').show();
    });

    // Close filters popup
    $('#close-filters').on('click', function() {
        $('#filters-popup').hide();
    });

    // Apply filters
    $('#apply-filters').on('click', function() {
        filteredColumns = [];
        $('.column-filter:checked').each(function() {
            filteredColumns.push($(this).data('column'));
        });
        $('#filters-popup').hide();
        renderTable();
    });

    // Apply rows per page
    $('#apply-rows').on('click', function() {
        rowsPerPage = parseInt($('#rows-per-page').val(), 10);
        currentPage = 1; // Reset to first page
        renderTable();
    });

    // Render table with filters and pagination
    function renderTable() {
        const $table = $('#excel-data-table');
        const $rows = $table.find('tbody tr');
        const totalRows = $rows.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage);

        // Hide all rows
        $rows.hide();

        // Show filtered rows for the current page
        $rows.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage).each(function() {
            const $row = $(this);
            $row.find('td').each(function(index) {
                if (filteredColumns.length === 0 || filteredColumns.includes(index)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            $row.show();
        });

        // Render pagination
        let paginationHtml = '';
        for (let i = 1; i <= totalPages; i++) {
            paginationHtml += `<a href="#" class="page-link ${i === currentPage ? 'active' : ''}">${i}</a> `;
        }
        $('.pagination').html(paginationHtml);

        // Handle pagination clicks
        $('.page-link').on('click', function(e) {
            e.preventDefault();
            currentPage = parseInt($(this).text(), 10);
            renderTable();
        });
    }

    // Initial render
    renderTable();
});