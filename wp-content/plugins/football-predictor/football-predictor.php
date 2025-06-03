<?php
/**
 * Plugin Name:       Football Match Predictor
 * Plugin URI:        https://example.com/plugins/football-predictor/
 * Description:       Integrates with Azscore to provide match predictions using external data and Gemini AI.
 * Version:           0.1.0
 * Author:            AI Developer (Jules)
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       football-predictor
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Enqueue the prediction script.
 */
function fmp_enqueue_scripts() {
    wp_enqueue_script(
        'fmp-prediction-script', // Handle
        plugin_dir_url( __FILE__ ) . 'js/custom-prediction-script.js', // Source
        array( 'jquery' ), // Dependencies
        '0.1.0', // Version
        true // In footer
    );
}
add_action( 'wp_enqueue_scripts', 'fmp_enqueue_scripts' );

// Add the original PHP function that wraps the script,
// but we'll load the script via wp_enqueue_script for better practice.
// The actual JS code will be in js/custom-prediction-script.js
function custom_azscore_prediction_script_loader() {
    // This function is kept if other PHP logic was intended here,
    // but the script itself is now enqueued.
    // If the original add_action('wp_footer', 'custom_azscore_prediction_script', 99);
    // was solely for outputting the <script> tag, it's replaced by wp_enqueue_scripts.
    // However, the user provided it as a PHP function, so let's ensure it's still callable
    // if they had other reasons for it.
    // For now, we'll assume the primary goal was loading the JS.
}
// The original hook: add_action('wp_footer', 'custom_azscore_prediction_script', 99);
// is now handled by wp_enqueue_scripts which is the WordPress standard.
// If the user specifically needs the <script> tags output by PHP in the footer via that hook,
// we'd adjust, but enqueuing is preferred.

?>
