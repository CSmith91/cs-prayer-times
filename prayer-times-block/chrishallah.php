<?php
/**
 * Plugin Name:       Chrishallah
 * Description:       REQUIRES CHRISSMI PRAYER TIMES PLUGIN FOR SHORTCODES. Shows prayer times local to Lambeth.
 * Version:           1.1.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            Chris Smith
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       chrishallah
 *
 * @package CreateBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */

// Initialise the plugin 
function create_block_chrishallah_block_init() {
	if ( function_exists( 'wp_register_block_types_from_metadata_collection' ) ) { // Function introduced in WordPress 6.8.
		wp_register_block_types_from_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
	} else {
		if ( function_exists( 'wp_register_block_metadata_collection' ) ) { // Function introduced in WordPress 6.7.
			wp_register_block_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
		}
		$manifest_data = require __DIR__ . '/build/blocks-manifest.php';
		foreach ( array_keys( $manifest_data ) as $block_type ) {
			register_block_type( __DIR__ . "/build/{$block_type}" );
		}
	}
}
add_action( 'init', 'create_block_chrishallah_block_init' );

// Load JavaScript for the countdown and CSS
function enqueue_prayer_assets() {
    if ( ! is_admin() ) { // Load only on frontend

        // Enqueue JavaScript
        wp_enqueue_script('ptm-prayer-times', plugin_dir_url(__FILE__) . 'assets/js/prayer-timer.js', array(), '1.0', true);

        // Get prayer data from DB
        global $wpdb;
        $table = $wpdb->prefix . 'prayer_times';
        $today = date('Y-m-d');
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE prayer_date = %s", $today));
    
        if ($row) { 
            $iqamah = [
                'fajr'    => $row->fajr_iqamah,
                'zuhr'    => $row->zuhr_iqamah,
                'asr'     => $row->asr_iqamah,
                'maghrib' => $row->maghrib_iqamah,
                'isha'    => $row->isha_iqamah,
            ];

            $timestamps = [
                'timestamp-fajr'    => strtotime($row->fajr_iqamah),
                'timestamp-zuhr'    => strtotime($row->zuhr_iqamah),
                'timestamp-asr'     => strtotime($row->asr_iqamah),
                'timestamp-maghrib' => strtotime($row->maghrib_iqamah),
                'timestamp-isha'    => strtotime($row->isha_iqamah),
            ];
    
            $data = [
                'iqamah_times' => $iqamah,
                'timestamps'   => $timestamps
            ];
        } else {
            $data = [];
        }
    
        wp_localize_script('ptm-prayer-times', 'ptmData', $data);

        // Enqueue CSS
        wp_enqueue_style(
            'prayer-times-style', 
            plugin_dir_url(__FILE__) . 'assets/css/styles.css', 
            array(), 
            '1.0.0'
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_prayer_assets');

