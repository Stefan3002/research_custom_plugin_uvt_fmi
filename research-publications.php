<?php

/*
Plugin Name: Research Publications
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the Plugin.
Version: 1.0
Author: stefan
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/

require_once(plugin_dir_path(__FILE__) . 'all_publications.php');
add_shortcode('publications_years', 'display_all_publications_by_year');

function custom_enqueue_scripts() {
    // Enqueue CSS for form styling
    wp_enqueue_style('custom-styles', plugin_dir_url(__FILE__) . 'utils/available-years-div.css');

}
add_action('wp_enqueue_scripts', 'custom_enqueue_scripts');



add_action('admin_menu', 'custom_plugin_admin_page');

function custom_plugin_admin_page() {
    add_menu_page(
        'Publications_Custom',     // Page title
        'Publications_Custom',     // Menu title
        'manage_options',    // Capability
        'publications-custom',     // Menu slug
        'custom_plugin_page_callback', // Callback function
        'dashicons-admin-generic', // Icon
        20  // Position in menu
    );
}
function custom_plugin_page_callback() {
    ?>
    <div class="wrap">
        <h1>Upload CSV File</h1>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('custom_csv_upload', 'custom_csv_nonce'); ?>
            <input type="file" name="csv_file" accept=".csv" required>
            <input type="submit" name="upload_csv" class="button button-primary" value="Upload CSV">
        </form>
    </div>
    <?php

    // Handle the CSV upload
    if (isset($_POST['upload_csv']) && check_admin_referer('custom_csv_upload', 'custom_csv_nonce')) {
        custom_plugin_handle_csv_upload();
    }
}
function custom_plugin_handle_csv_upload() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have permission to upload files.'));
    }

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        echo '<div class="error"><p>Upload failed. Please try again.</p></div>';
        return;
    }

    $file = $_FILES['csv_file'];

    // Validate file type
    $file_type = wp_check_filetype($file['name']);
    if ($file_type['ext'] !== 'csv') {
        echo '<div class="error"><p>Only CSV files are allowed.</p></div>';
        return;
    }

    // Move file to WordPress uploads directory
    $upload_dir = wp_upload_dir();
    $target_file = $upload_dir['path'] . '/' . basename($file['name']);

    if (!move_uploaded_file($file['tmp_name'], $target_file)) {
        echo '<div class="error"><p>Could not save file.</p></div>';
        return;
    }

    echo '<div class="updated"><p>CSV file uploaded successfully.</p></div>';

    // Process CSV data
    custom_plugin_process_csv($target_file);
}
function custom_plugin_process_csv($file_path) {
    if (!file_exists($file_path)) {
        echo '<div class="error"><p>File not found.</p></div>';
        return;
    }

    $handle = fopen($file_path, 'r');
    if (!$handle) {
        echo '<div class="error"><p>Could not open file.</p></div>';
        return;
    }

    global $wpdb;
    $table_prefix = $wpdb->prefix . "research_publications";
    $table_name = $table_prefix;

    // Skip the header row if present
    $first_row = fgetcsv($handle, 1000, ",");

    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
        // Map CSV columns to table fields (adjust indexes if necessary)
        $doi = sanitize_text_field($row[0]);
        $title = sanitize_text_field($row[1]);
        $authors = sanitize_text_field($row[2]);
        $affiliated_authors = !empty($row[3]) ? sanitize_text_field($row[3]) : NULL;
        $forum = !empty($row[4]) ? sanitize_text_field($row[4]) : NULL;
        $year = intval($row[5]);
        $volume = !empty($row[6]) ? intval($row[6]) : NULL;
        $pages = !empty($row[7]) ? intval($row[7]) : NULL;

        // Insert into database
        $wpdb->insert(
            $table_name,
            [
                'doi' => $doi,
                'title' => $title,
                'authors' => $authors,
                'affiliated_authors' => $affiliated_authors,
                'forum' => $forum,
                'year' => $year,
                'volume' => $volume,
                'pages' => $pages
            ],
            ['%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s']
        );
    }

    fclose($handle);
    echo '<div class="updated"><p>CSV processed successfully, data inserted into database.</p></div>';
}


register_activation_hook(__FILE__, function() {
    global $wpdb;

    $table_prefix = $wpdb->prefix . "research_publications";
    $charset_collate = $wpdb->get_charset_collate();

    $creation_query =
        "(
          doi varchar(255) NOT NULL,
          title varchar(255) NOT NULL,
          authors varchar(255) NOT NULL,
          affiliated_authors varchar(255),
          forum varchar(255),
          year int(5) NOT NULL,
          volume int(5),
          pages varchar(40),
          imported_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY  (doi)
      ); 
    ";
//doi (id-ul doi.org, poate fi primary key), Titlu, autori (toți autorii lucrării), autori afiliați (doar ai noștri, sunt cu bold în site-ul vechi), forum (conferința x, jurnalul y), anul, volum, pagini


        $table_name = $table_prefix;

        if (!($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name)) {

            $query = "
      CREATE TABLE " . $table_name . $creation_query;

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $logs = dbDelta($query);
            error_log('logs ' . print_r($logs, true));
        }
});