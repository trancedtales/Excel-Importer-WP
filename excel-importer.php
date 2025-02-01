<?php
/*
Plugin Name: Excel Importer
Description: A plugin to import Excel files from the dashboard and display them on the frontend with pagination and CSV download.
Version: 1.0
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
        // Pagination logic
        $per_page = 10; // Number of rows per page
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $total_rows = count($data);
        $total_pages = ceil($total_rows / $per_page);
        $offset = ($current_page - 1) * $per_page;
        $paginated_data = array_slice($data, $offset, $per_page);

        // Display table
        echo '<table id="excel-data-table">';
        echo '<thead><tr>';
        foreach ($paginated_data[0] as $header) {
            echo '<th>' . esc_html($header) . '</th>';
        }
        echo '</tr></thead>';
        echo '<tbody>';
        foreach (array_slice($paginated_data, 1) as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . esc_html($cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';

        // Pagination links
        echo '<div class="pagination">';
        for ($i = 1; $i <= $total_pages; $i++) {
            echo '<a href="?paged=' . $i . '">' . $i . '</a> ';
        }
        echo '</div>';

        // Download CSV button
        echo '<button id="download-csv" class="button">Download Filtered CSV</button>';
    } else {
        echo '<p>No data available. Please upload an Excel file from the dashboard.</p>';
    }
    return ob_get_clean();
}
add_shortcode('excel_importer', 'excel_importer_shortcode');

// Handle CSV download
function handle_csv_download() {
    if (isset($_GET['download_csv']) && $_GET['download_csv'] === '1') {
        $data = get_transient('excel_import_data');
        if ($data) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="filtered_data.csv"');
            $output = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
            exit;
        }
    }
}
add_action('init', 'handle_csv_download');

// Enqueue scripts and styles
function excel_importer_enqueue_scripts() {
    wp_enqueue_style('excel-importer-style', plugins_url('style.css', __FILE__));
    wp_enqueue_script('excel-importer-script', plugins_url('script.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('excel-importer-script', 'excel_importer_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
}
add_action('wp_enqueue_scripts', 'excel_importer_enqueue_scripts');