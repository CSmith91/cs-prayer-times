<?php
/*
Plugin Name: Chrissmi Prayer Times Manager
Description: A plugin to manage a full calendar year of daily prayer times.
Version: 1.0.0
Author: Chris Smith
*/

// ---------- STEP 1: Create the Custom Database Table on Activation ----------

register_activation_hook(__FILE__, 'ptm_create_prayer_times_table');

function ptm_create_prayer_times_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'prayer_times';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        prayer_date date NOT NULL,
        fajr_begins time NULL,
        fajr_iqamah time NULL,
        sunrise_begins time NULL,
        zuhr_begins time NULL,
        zuhr_iqamah time NULL,
        asr_begins time NULL,
        asr_iqamah time NULL,
        maghrib_begins time NULL,
        maghrib_iqamah time NULL,
        isha_begins time NULL,
        isha_iqamah time NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY prayer_date (prayer_date)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // We leave it empty until the import is run.
}

// ---------- STEP 2: Add an Admin Menu Page ----------

add_action('admin_menu', 'ptm_add_admin_menu');

function ptm_add_admin_menu() {
    add_menu_page(
        'Prayer Times Manager',      // Page title
        'CS Prayer Times',              // Menu title
        'manage_options',            // Capability
        'prayer-times-manager',      // Menu slug
        'ptm_admin_page',            // Callback function
        'dashicons-clock',           // Icon
        90                           // Position
    );
}

// ---------- STEP 3: Admin Page Content with Month Dropdown, Import Button, and Editable Table ----------

function ptm_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'prayer_times';

    // Determine the selected month (default to current month)
    $selected_month = isset($_GET['ptm_month']) ? intval($_GET['ptm_month']) : date('n');
    $selected_year = isset($_GET['ptm_year']) ? intval($_GET['ptm_year']) : date('Y');

    // Fetch rows for the selected month and year
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE MONTH(prayer_date) = %d AND YEAR(prayer_date) = %d ORDER BY prayer_date ASC",
        $selected_month, $selected_year
    ));

    // Get month names
    $months = array(
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    );
    
    // Get current date
    $current_date = date('Y-m-d');
    ?>
    <div class="wrap">
        <h1>Prayer Times Management</h1>
        <?php if (isset($_GET['updated'])): ?>
            <div id="message" class="updated notice is-dismissible"><p>Prayer times updated.</p></div>
        <?php elseif (isset($_GET['imported'])): ?>
            <div id="message" class="updated notice is-dismissible"><p>Prayer times imported from API.</p></div>
        <?php endif; ?>

        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-bottom:20px;">
            <?php wp_nonce_field('ptm_import_nonce', 'ptm_import_nonce_field'); ?>
            <input type="submit" class="button" value="Import Data for <?php echo esc_attr($selected_year); ?>">
            <input type="hidden" name="action" value="ptm_import_data">
            <input type="hidden" name="ptm_month" value="<?php echo esc_attr($selected_month); ?>">
            <input type="hidden" name="ptm_year" value="<?php echo esc_attr($selected_year); ?>">
        </form>

        <!-- Month & Year Dropdown -->
        <form method="get" action="">
            <input type="hidden" name="page" value="prayer-times-manager">
            <label for="ptm_month">Select Month: </label>
            <select name="ptm_month" id="ptm_month" onchange="this.form.submit()">
                <?php foreach ($months as $num => $name): ?>
                    <option value="<?php echo $num; ?>" <?php selected($selected_month, $num); ?>><?php echo esc_html($name); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="ptm_year">Select Year: </label>
            <select name="ptm_year" id="ptm_year">
                <?php for ($y = date('Y') -1; $y <= date('Y') + 3; $y++): ?>
                    <option value="<?php echo $y; ?>" <?php selected($selected_year, $y); ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>

            <input type="submit" class="button" value="View">
        </form>

        <!-- Editable Prayer Times Table -->
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-top:20px;">
            <?php wp_nonce_field('ptm_update_nonce', 'ptm_update_nonce_field'); ?>
            <input type="hidden" name="action" value="ptm_update">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Fajr Begins</th>
                        <th>Fajr Iqamah</th>
                        <th>Sunrise</th>
                        <th>Zuhr Begins</th>
                        <th>Zuhr Iqamah</th>
                        <th>Asr Begins</th>
                        <th>Asr Iqamah</th>
                        <th>Maghrib Begins</th>
                        <th>Maghrib Iqamah</th>
                        <th>Isha Begins</th>
                        <th>Isha Iqamah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows): ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $row_date = $row->prayer_date;
                            $day_of_week = date('N', strtotime($row_date)); // 5 for Friday
                            $is_friday = ($day_of_week == 5);
                            $is_today = ($row_date == $current_date);
                            ?>
                            <tr<?php if ($is_friday) echo ' class="friday-row"'; if ($is_today) echo ' class="today-row"'; ?>>
                                <td><?php echo esc_html(date('F j, Y', strtotime($row_date))); ?></td>
                                <?php
                                $fields = [
                                    'fajr_begins',
                                    'fajr_iqamah',
                                    'sunrise_begins',
                                    'zuhr_begins',
                                    'zuhr_iqamah',
                                    'asr_begins',
                                    'asr_iqamah',
                                    'maghrib_begins',
                                    'maghrib_iqamah',
                                    'isha_begins',
                                    'isha_iqamah'
                                ];
                                foreach ($fields as $field):
                                    $value = $row->$field;
                                    // Reformat time to HH:MM (omit seconds)
                                    $value = $value ? date('H:i', strtotime($value)) : '';
                                ?>
                                    <td>
                                        <input type="time" step="60" name="prayer_times[<?php echo intval($row->id); ?>][<?php echo esc_attr($field); ?>]" value="<?php echo esc_attr($value); ?>">
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12">No data available for <?php echo esc_html($months[$selected_month]); ?> <?php echo esc_html($selected_year); ?>.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php submit_button('Save Changes'); ?>
        </form>
    </div>
    <style>
        .friday-row td {
            color: red;
        }
        .today-row td {
            background-color: #5cb574;
        }
    </style>
    <?php
}

// ---------- STEP 4: Handle Form Submission for Updating Prayer Times ----------

add_action('admin_post_ptm_update', 'ptm_handle_update');

function ptm_handle_update() {
    if (!current_user_can('manage_options')) {
        wp_die('Not allowed');
    }
    if (!isset($_POST['ptm_update_nonce_field']) || !wp_verify_nonce($_POST['ptm_update_nonce_field'], 'ptm_update_nonce')) {
        wp_die('Nonce verification failed.');
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'prayer_times';

    if (isset($_POST['prayer_times']) && is_array($_POST['prayer_times'])) {
        foreach ($_POST['prayer_times'] as $id => $data) {
            $update_data = array();
            $fields = [
                'fajr_begins', 'fajr_iqamah', 'sunrise_begins',
                'zuhr_begins', 'zuhr_iqamah',
                'asr_begins', 'asr_iqamah',
                'maghrib_begins', 'maghrib_iqamah',
                'isha_begins', 'isha_iqamah'
            ];
            foreach ($fields as $field) {
                $value = isset($data[$field]) ? sanitize_text_field($data[$field]) : '';
                $update_data[$field] = ($value === '') ? null : $value;
            }
            $wpdb->update($table_name, $update_data, array('id' => intval($id)));
        }
    }
    $redirect = add_query_arg('updated', 'true', wp_get_referer());
    wp_redirect($redirect);
    exit;
}

// ---------- STEP 5: Handle API Import ----------

add_action('admin_post_ptm_import_data', 'ptm_import_from_api');

function ptm_import_from_api() {
    if (!current_user_can('manage_options')) {
        wp_die('Not allowed');
    }
    if (!isset($_POST['ptm_import_nonce_field']) || !wp_verify_nonce($_POST['ptm_import_nonce_field'], 'ptm_import_nonce')) {
        wp_die('Nonce verification failed.');
    }
    // // Get selected month from POST so we can redirect back appropriately.
    // $selected_month = isset($_POST['ptm_month']) ? intval($_POST['ptm_month']) : date('n');

    // Call the external API
    $selected_year = isset($_POST['ptm_year']) ? intval($_POST['ptm_year']) : date('Y');
    $api_url = "http://www.londonprayertimes.com/api/times/?format=json&year={$selected_year}&key=" . LONDON_PRAYER_API_KEY . "&24hours=true";
    
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        wp_die('Error fetching API data.');
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!isset($data['times']) || !is_array($data['times'])) {
        wp_die('Invalid API data.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'prayer_times';

    // Loop through each date in the API response and update/insert into the table.
    foreach ($data['times'] as $date => $times) {
        // Iterating through the JSON. We any adjustments to this data are made prior to populating the table 

        // Create a DateTime object for asr_jamat time
        $asrIqamahTime = new DateTime("{$times['date']} {$times['asr_jamat']}");
        $asrIqamahTime->modify('-1 hour');
        $adjustedAsrIqamah = $asrIqamahTime->format('H:i');         // Format back to H:i
        
        // Map API fields to our table columns.
        $row_data = array(
            'prayer_date'    => $times['date'],
            'fajr_begins'    => $times['fajr'],
            'fajr_iqamah'    => $times['fajr_jamat'],
            'sunrise_begins' => $times['sunrise'],
            'zuhr_begins'    => $times['dhuhr'],
            'zuhr_iqamah'    => $times['dhuhr_jamat'],
            'asr_begins'     => $times['asr'],
            'asr_iqamah'     => $adjustedAsrIqamah, // adjusted time here
            'maghrib_begins' => $times['magrib'],
            'maghrib_iqamah' => $times['magrib_jamat'],
            'isha_begins'    => $times['isha'],
            'isha_iqamah'    => $times['isha_jamat'],
        );

        // Use $wpdb->replace to insert or update the row (based on unique prayer_date).
        $wpdb->replace($table_name, $row_data);
    }

    $redirect = add_query_arg([
        'imported' => 'true',
        'ptm_month' => $selected_month,
        'ptm_year' => $selected_year
    ], admin_url('admin.php?page=prayer-times-manager'));
    wp_redirect($redirect);
    exit;
}

// ---------- STEP 6: Helper functions for today's date & Front-end Shortcode ----------
function convert_to_hijri($gregorian_date) {
    $formatter = new IntlDateFormatter(
        "en@calendar=islamic",
        IntlDateFormatter::LONG,
        IntlDateFormatter::NONE,
        'UTC',
        IntlDateFormatter::TRADITIONAL
    );
    $timestamp = strtotime($gregorian_date);
    $hijri_date = $formatter->format($timestamp);

    // Remove "AH" and commas
    $hijri_date = preg_replace('/AH|\s*,/', '', $hijri_date);

    // Swap "Shawwal 4 1446" → "4 Shawwal 1446"
    if (preg_match('/(\w+)\s+(\d+)\s+(\d+)/', $hijri_date, $matches)) {
        $hijri_date = "{$matches[2]} {$matches[1]} {$matches[3]}";
    }

    return trim($hijri_date);
}

function ptm_get_today_date() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'prayer_times';
    $today = date('Y-m-d');
    $row = $wpdb->get_row($wpdb->prepare("SELECT prayer_date FROM $table_name WHERE prayer_date = %s", $today));
    if (!$row) {
        return '<p>No prayer times set for today.</p>';
    }
    $gregorian = date('jS F Y', strtotime($row->prayer_date));
    $hijri = convert_to_hijri($row->prayer_date);
    $date_output = "$gregorian&nbsp;&nbsp;•&nbsp;&nbsp;$hijri";
    return $date_output;
}
add_shortcode('prayer_date', 'ptm_get_today_date');

// ---------- STEP 7: Front-end Shortcode to Display Today's Prayer Times ----------

function ptm_get_prayer_time($atts) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'prayer_times';
    $today = date('Y-m-d');

    // Extract the prayer attribute from the shortcode
    $atts = shortcode_atts(array(
        'prayer' => '',
    ), $atts);

    $prayer = sanitize_text_field($atts['prayer']);

    // Map shortcode attributes to database columns
    $prayer_columns = array(
        'fajr_start'    => 'fajr_begins',
        'fajr_iqamah'   => 'fajr_iqamah',
        'sunrise'       => 'sunrise_begins',
        'zuhr_start'    => 'zuhr_begins',
        'zuhr_iqamah'   => 'zuhr_iqamah',
        'asr_start'     => 'asr_begins',
        'asr_iqamah'    => 'asr_iqamah',
        'maghrib_start' => 'maghrib_begins',
        'maghrib_iqamah'=> 'maghrib_iqamah',
        'isha_start'    => 'isha_begins',
        'isha_iqamah'   => 'isha_iqamah',
    );

    if (!array_key_exists($prayer, $prayer_columns)) {
        return '<p>Invalid prayer time requested.</p>';
    }

    $row = $wpdb->get_row($wpdb->prepare("SELECT {$prayer_columns[$prayer]} FROM $table_name WHERE prayer_date = %s", $today));

    if (!$row) {
        return '<p>No prayer times set for today.</p>';
    }

    $time = $row->{$prayer_columns[$prayer]};
    return $time ? date('H:i', strtotime($time)) : '';
}
add_shortcode('prayer_time', 'ptm_get_prayer_time');

// SHORTCODE FOR THE MONTHLY TABLE (FRONTEND)

function ptm_prayer_times_shortcode($atts) {
    ob_start();

    global $wpdb;
    $table_name = $wpdb->prefix . 'prayer_times';

    // Handle selected month/year via GET (default to current)
    $selected_month = isset($_GET['ptm_month']) ? intval($_GET['ptm_month']) : date('n');
    $selected_year = isset($_GET['ptm_year']) ? intval($_GET['ptm_year']) : date('Y');

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE MONTH(prayer_date) = %d AND YEAR(prayer_date) = %d ORDER BY prayer_date ASC",
        $selected_month, $selected_year
    ));

    $months = array(
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    );
    ?>
    <div id="ptm-container">
        <form method="get" action="">
            <label for="ptm_month">Month:</label>
            <select name="ptm_month" id="ptm_month">
                <?php foreach ($months as $num => $name): ?>
                    <option value="<?php echo $num; ?>" <?php selected($selected_month, $num); ?>><?php echo esc_html($name); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="ptm_year">Year:</label>
            <select name="ptm_year" id="ptm_year">
                <?php for ($y = date('Y') - 1; $y <= date('Y') + 3; $y++): ?>
                    <option value="<?php echo $y; ?>" <?php selected($selected_year, $y); ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>

            <input type="submit" value="View">
        </form>

        <?php if ($rows): ?>
            <button onclick="ptmDownloadPDF()">Download as PDF</button>

            <table id="ptm-table" border="1" cellpadding="5" cellspacing="0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Fajr Begins</th>
                        <th>Fajr Iqamah</th>
                        <th>Sunrise</th>
                        <th>Zuhr Begins</th>
                        <th>Zuhr Iqamah</th>
                        <th>Asr Begins</th>
                        <th>Asr Iqamah</th>
                        <th>Maghrib Begins</th>
                        <th>Maghrib Iqamah</th>
                        <th>Isha Begins</th>
                        <th>Isha Iqamah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo esc_html(date('F j, Y', strtotime($row->prayer_date))); ?></td>
                            <td><?php echo esc_html(substr($row->fajr_begins, 0, 5)); ?></td>
                            <td><?php echo esc_html(substr($row->fajr_iqamah, 0, 5)); ?></td>
                            <td><?php echo esc_html(substr($row->sunrise_begins, 0, 5)); ?></td>
                            <td><?php echo esc_html(substr($row->zuhr_begins, 0, 5)); ?></td>
                            <td><?php echo esc_html(substr($row->zuhr_iqamah, 0, 5)); ?></td>
                            <td><?php echo esc_html(substr($row->asr_begins, 0, 5)); ?></td>
                            <td><?php echo esc_html(substr($row->asr_iqamah, 0, 5)); ?></td>
                            <td><?php echo esc_html(substr($row->maghrib_begins, 0, 5)); ?></td>
                            <td><?php echo esc_html(substr($row->maghrib_iqamah, 0, 5)); ?></td>
                            <td><?php echo esc_html(substr($row->isha_begins, 0, 5)); ?></td>
                            <td><?php echo esc_html(substr($row->isha_iqamah, 0, 5)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No prayer times found for <?php echo $months[$selected_month] . ' ' . $selected_year; ?>.</p>
        <?php endif; ?>
    </div>

    <!-- PDF Export Script -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        function ptmDownloadPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('landscape');
            doc.autoTable({ html: '#ptm-table' });
            doc.save("Prayer-Times-<?php echo $selected_month . '-' . $selected_year; ?>.pdf");
        }
    </script>
    <style>
        #ptm-container table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        #ptm-container th, #ptm-container td {
            text-align: center;
        }
        #ptm-container button {
            margin: 10px 0;
        }
    </style>
    <?php

    return ob_get_clean();
}
add_shortcode('prayer_times_table', 'ptm_prayer_times_shortcode');