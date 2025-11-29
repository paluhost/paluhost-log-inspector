<?php
/**
 * Plugin Name:       Paluhost Log Inspector
 * Plugin URI:        https://paluhost.co.ke/plugins/paluhost-log-inspector
 * Description:       Monitor debug logs for Paluhost plugin errors and display status in the WordPress admin bar
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Paluhost Web Services Ltd
 * Author URI:        https://paluhost.co.ke
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       paluhost-log-inspector
 */


class Paluhost_Log_Inspector {

    /** CUSTOMIZATION STARTS HERE **/
    private $debug_max_bytes = 1024 * 300; // 1024 = 1KB

    // Enable auto plugins check
    private $debug_auto_enable = TRUE;

    // Page Categorizer
    private $debug_page_categorizer = FALSE;
    private $debug_page_categorizer_text = array('category', 'category in page');

    // Footnotes Made Easy
    private $debug_footnotes_made_easy = FALSE;
    private $debug_footnotes_made_easy_text = 'footnotes';

    // Quick Event Calendar
    private $debug_quick_event_calendar = FALSE;
    private $debug_quick_event_calendar_text = 'calendar';

    // Search Engines Blocked in Header
    private $debug_search_engines_blocked = FALSE;
    private $debug_search_engines_blocked_text = array('blocked', 'seo');

    // Bulk Variations for WooCommerce
    private $debug_bulk_variations = FALSE;
    private $debug_bulk_variations_text = array('bulk', 'variation');

    // Print last error in submenu
    private $debug_print_last_error = TRUE;

    /** CUSTOMIZATION ENDS HERE **/

    public function __construct() {
        add_action( 'admin_head', array( $this, 'add_css_style' ), 1 );
        add_action( 'admin_bar_menu', array( $this, 'add_to_admin_bar' ), 10000 );

        // Auto Enable Plugins
        if ( $this->debug_auto_enable ) {
            if ( ! function_exists( 'is_plugin_active' ) ) {
                include_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $this->debug_page_categorizer = is_plugin_active( 'page-categorizer/page-categorizer.php' );
            $this->debug_footnotes_made_easy = is_plugin_active( 'footnotes-made-easy/footnotes-made-easy.php' );
            $this->debug_quick_event_calendar = is_plugin_active( 'quick-event-calendar/quick-event-calendar.php' );
            $this->debug_search_engines_blocked = is_plugin_active( 'search-engines-blocked-in-header/search-engines-blocked-in-header.php' );
            $this->debug_bulk_variations = is_plugin_active( 'bulk-variations-for-woocommerce/bulk-variations-for-woocommerce.php' );
        }

    }

    public function add_css_style() {
        $qa_css = '<style id="paluhost-log-inspector-css">
.paluhost-admin-text-red > a {color:#ff3939 !important;}
.paluhost-admin-text-green > a {color:#00f70b !important;}
.paluhost-admin-text-gray > a {color:#808080 !important;}
.paluhost-admin-text-last-error {width:500px !important;border-top:1px solid #808080}
.paluhost-admin-text-last-error > a {height: auto !important;width: 500px !important;text-wrap: wrap !important;}
</style>';

        echo $qa_css;

    }

    private function search_plugin_text_in_line( $line, $plugin_text  ) {

        $found = FALSE;

        if ( is_array($plugin_text) ) {

            foreach ( $plugin_text as $text ) {
                $is_here = strpos( $line, $text );

                if ( $is_here !== FALSE ) {
                    $found = TRUE;
                }
            }

        } else {

            $found = strpos( $line, $plugin_text );

        }

        return $found;
    }

    public function add_to_admin_bar() {
        global $wp_admin_bar;

        $CONST_WP_DEBUG_LOG = defined( 'WP_DEBUG_LOG' ) ? WP_DEBUG_LOG === TRUE ? ABSPATH . "wp-content/debug.log" : WP_DEBUG_LOG : "FALSE";
        $error_found = FALSE;

        $error_found_page_categorizer = FALSE;
        $error_found_footnotes_made_easy = FALSE;
        $error_found_quick_event_calendar = FALSE;
        $error_found_search_engines_blocked = FALSE;
        $error_found_bulk_variations = FALSE;

        $error_found_last = "";


        if ( file_exists($CONST_WP_DEBUG_LOG) ) {
            $debug_log = fopen($CONST_WP_DEBUG_LOG, 'r');
            fseek($debug_log, -$this->debug_max_bytes, SEEK_END);

            if ($debug_log) {
                while (($log_line = fgets($debug_log, 4096)) !== FALSE) {
                    // Page Categorizer
                    if ( $this->debug_page_categorizer && $this->search_plugin_text_in_line( $log_line, $this->debug_page_categorizer_text ) !== FALSE ){
                        $error_found = TRUE;
                        $error_found_page_categorizer = TRUE;
                        $error_found_last = $log_line;
                    }

                    // Footnotes Made Easy
                    if ( $this->debug_footnotes_made_easy && $this->search_plugin_text_in_line( $log_line, $this->debug_footnotes_made_easy_text ) !== FALSE ){
                        $error_found = TRUE;
                        $error_found_footnotes_made_easy = TRUE;
                        $error_found_last = $log_line;
                    }

                    // Quick Event Calendar
                    if ( $this->debug_quick_event_calendar && $this->search_plugin_text_in_line( $log_line, $this->debug_quick_event_calendar_text ) !== FALSE ){
                        $error_found = TRUE;
                        $error_found_quick_event_calendar = TRUE;
                        $error_found_last = $log_line;
                    }

                    // Search Engines Blocked in Header
                    if ( $this->debug_search_engines_blocked && $this->search_plugin_text_in_line( $log_line, $this->debug_search_engines_blocked_text ) !== FALSE ){
                        $error_found = TRUE;
                        $error_found_search_engines_blocked = TRUE;
                        $error_found_last = $log_line;
                    }

                    // Bulk Variations for WooCommerce
                    if ( $this->debug_bulk_variations && $this->search_plugin_text_in_line( $log_line, $this->debug_bulk_variations_text ) !== FALSE ){
                        $error_found = TRUE;
                        $error_found_bulk_variations = TRUE;
                        $error_found_last = $log_line;
                    }

                }
                if (!feof($debug_log)) {
                    // Log reading error occurred
                }
                fclose($debug_log);
            }

        }

        $status_page_categorizer = $error_found_page_categorizer ? "ERROR!" : "OK";
        $status_footnotes_made_easy = $error_found_footnotes_made_easy ? "ERROR!" : "OK";
        $status_quick_event_calendar = $error_found_quick_event_calendar ? "ERROR!" : "OK";
        $status_search_engines_blocked = $error_found_search_engines_blocked ? "ERROR!" : "OK";
        $status_bulk_variations = $error_found_bulk_variations ? "ERROR!" : "OK";

        $css_plugin_error = WP_DEBUG_LOG !== FALSE ?  $error_found ? "paluhost-admin-text-red" : "paluhost-admin-text-green" : "paluhost-admin-text-gray";

        $menu_id = 'paluhost-log-inspector';
        $wp_admin_bar->add_menu(array('id' => $menu_id, 'title' => __('PALUHOST LOG INSPECTOR'), 'href' => '#', 'meta' => array('class' => $css_plugin_error)));

        // Page Categorizer
        if ( $this->debug_page_categorizer === TRUE ) {
            $wp_admin_bar->add_menu(array('parent' => $menu_id, 'title' => __("Page Categorizer: $status_page_categorizer"), 'id' => 'paluhost-page-categorizer', 'href' => '#', 'meta' => array('class' => $error_found_page_categorizer ? "paluhost-admin-text-red" : "")));
        }

        // Footnotes Made Easy
        if ( $this->debug_footnotes_made_easy === TRUE ) {
            $wp_admin_bar->add_menu(array('parent' => $menu_id, 'title' => __("Footnotes Made Easy: $status_footnotes_made_easy"), 'id' => 'paluhost-footnotes-made-easy', 'href' => '#', 'meta' => array('class' => $error_found_footnotes_made_easy ? "paluhost-admin-text-red" : "")));
        }

        // Quick Event Calendar
        if ( $this->debug_quick_event_calendar === TRUE ) {
            $wp_admin_bar->add_menu(array('parent' => $menu_id, 'title' => __("Quick Event Calendar: $status_quick_event_calendar"), 'id' => 'paluhost-quick-event-calendar', 'href' => '#', 'meta' => array('class' => $error_found_quick_event_calendar ? "paluhost-admin-text-red" : "")));
        }

        // Search Engines Blocked in Header
        if ( $this->debug_search_engines_blocked === TRUE ) {
            $wp_admin_bar->add_menu(array('parent' => $menu_id, 'title' => __("Search Engines Blocked: $status_search_engines_blocked"), 'id' => 'paluhost-search-engines-blocked', 'href' => '#', 'meta' => array('class' => $error_found_search_engines_blocked ? "paluhost-admin-text-red" : "")));
        }

        // Bulk Variations for WooCommerce
        if ( $this->debug_bulk_variations === TRUE ) {
            $wp_admin_bar->add_menu(array('parent' => $menu_id, 'title' => __("Bulk Variations: $status_bulk_variations"), 'id' => 'paluhost-bulk-variations', 'href' => '#', 'meta' => array('class' => $error_found_bulk_variations ? "paluhost-admin-text-red" : "")));
        }

        // LAST ERROR
        if ( $this->debug_print_last_error && !empty($error_found_last) ) {
            $error_found_last_safe = esc_html($error_found_last);
            $wp_admin_bar->add_menu(array('parent' => $menu_id, 'title' => __("<strong>Last Error:</strong> $error_found_last_safe"), 'id' => 'paluhost-last-error', 'href' => '#', 'meta' => array('class' => 'paluhost-admin-text-last-error')));
        }

    }

}

/***** WP DEBUG
 *
 * Add these directly on wp-config.php to make sure the values are the ones you want.
 *
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'SCRIPT_DEBUG', true );
define( 'SAVEQUERIES', true );
define( 'WP_DEBUG_DISPLAY', true );
 *
*/

new Paluhost_Log_Inspector();
