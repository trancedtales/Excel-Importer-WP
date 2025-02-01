<?php
/*
Plugin Name: Excel Importer
Description: A plugin to import Excel files from the dashboard and display them on the frontend with filters, pagination, and rows per page.
Version: 1.1
Author: Aditya Kumar
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include necessary libraries
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

// Include PhpSpreadsheet library
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

// Add admin menu for Excel upload
function excel_importer_admin_menu() {
    add_menu_page(
        'Excel Importer',
        'Excel Importer',
        'manage_options',
        'excel-importer',
        'excel_importer_admin_page',
        'dashicons-upload',
        6
    );
}
add_action('admin_menu', 'excel_importer_admin_menu');

// Admin page for Excel upload
function excel_importer_admin_page() {
    if (isset($_POST['submit_excel']) && !empty($_FILES['excel_file']['tmp_name'])) {
        $file = $_FILES['excel_file'];
        $uploaded = wp_handle_upload($file, array('test_form' => false));

        if (isset($uploaded['file'])) {
            $spreadsheet = IOFactory::load($uploaded['file']);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            // Store data in a transient for frontend display
            set_transient('excel_import_data', $data, 0); // Store indefinitely
            echo '<div class="notice notice-success"><p>File uploaded successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error uploading file.</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>Excel Importer</h1>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="excel_file" accept=".xlsx, .xls" required>
            <input type="submit" name="submit_excel" value="Upload Excel" class="button button-primary">
        </form>
    </div>
    <?php
}

// Shortcode to display Excel data on the frontend
function excel_importer_shortcode() {
    ob_start();
    $data = get_transient('excel_import_data');
    if ($data) {
        // Filters button
        echo '<button id="open-filters" class="button">Filters</button>';

        // Rows input field
        echo '<div class="rows-input">';
        echo '<label for="rows-per-page">Rows per page:</label>';
        echo '<input type="number" id="rows-per-page" min="1" value="10">';
        echo '<button id="apply-rows" class="button">Apply</button>';
        echo '</div>';

        // Filters popup
        echo '<div id="filters-popup" style="display:none;">';
        echo '<h3>Select Columns to Display</h3>';
        echo '<div id="filters-list">';
        if (!empty($data[0])) {
            foreach ($data[0] as $index => $header) {
                echo '<label><input type="checkbox" class="column-filter" data-column="' . $index . '" checked> ' . esc_html($header) . '</label><br>';
            }
        }
        echo '</div>';
        echo '<button id="apply-filters" class="button">Apply Filters</button>';
        echo '<button id="close-filters" class="button">Close</button>';
        echo '</div>';

        // Display table
        echo '<table id="excel-data-table">';
        echo '<thead><tr>';
        foreach ($data[0] as $header) {
            echo '<th>' . esc_html($header) . '</th>';
        }
        echo '</tr></thead>';
        echo '<tbody>';
        foreach (array_slice($data, 1) as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . esc_html($cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';

        // Pagination
        echo '<div class="pagination"></div>';
    } else {
        echo '<p>No data available. Please upload an Excel file from the dashboard.</p>';
    }
    return ob_get_clean();
}
add_shortcode('excel_importer', 'excel_importer_shortcode');

// Enqueue scripts and styles
function excel_importer_enqueue_scripts() {
    wp_enqueue_style('excel-importer-style', plugins_url('style.css', __FILE__));
    wp_enqueue_script('excel-importer-script', plugins_url('script.js', __FILE__), array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'excel_importer_enqueue_scripts');