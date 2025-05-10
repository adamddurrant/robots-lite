<?php

/**
 * Plugin Name: Robots.txt Lite
 * Plugin URI: https://wordpress.org/plugins/robots-txt-lite/
 * Description: Instantly edit and manage your robots.txt file from the WordPress admin dashboard settings.
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Author: Adam Durrant
 * Author URI: https://adamdurrant.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: robots-txt-lite
 * Domain Path: /languages
 *
 * @package RobotsTxtLite
 * @author Adam Durrant
 * @copyright 2024 Adam Durrant
 * @license GPL-2.0-or-later
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    die('Direct access is not allowed.');
}

// Define plugin constants
define('ROBOTS_TXT_LITE_VERSION', '1.0.0');
define('ROBOTS_TXT_LITE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ROBOTS_TXT_LITE_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class for Robots.txt Lite
 *
 * @since 1.0.0
 */
class Robots_Txt_Lite {

    /**
     * Plugin instance.
     *
     * @var Robots_Txt_Lite
     */
    private static $instance = null;

    /**
     * Get plugin instance.
     *
     * @return Robots_Txt_Lite
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        if (is_admin()) {
            add_action('admin_menu', [$this, 'add_menu']);
            add_action('admin_init', [$this, 'register_settings']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        }
        add_filter('robots_txt', [$this, 'filter_robots_txt'], 10, 2);
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting('robots_txt_lite_options', 'irt_robots_txt', [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitize_robots_txt'],
            'default' => $this->get_default_robots_txt(),
        ]);
    }

    /**
     * Add menu page.
     */
    public function add_menu() {
        add_options_page(
            __('Robots.txt Lite', 'robots-txt-lite'),
            __('Robots.txt Lite', 'robots-txt-lite'),
            'manage_options',
            'robots-txt-lite',
            [$this, 'settings_page']
        );
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueue_admin_assets($hook) {
        if ('settings_page_robots-txt-lite' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'robots-txt-lite-admin',
            ROBOTS_TXT_LITE_PLUGIN_URL . 'assets/css/admin.css',
            [],
            ROBOTS_TXT_LITE_VERSION
        );
    }

    /**
     * Get default robots.txt content.
     *
     * @return string
     */
    private function get_default_robots_txt() {
        return "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n\nSitemap: " . get_site_url() . "/wp-sitemap.xml";
    }

    /**
     * Sanitize robots.txt content.
     *
     * @param string $content The robots.txt content to sanitize.
     * @return string
     */
    public function sanitize_robots_txt($content) {
        return sanitize_textarea_field($content);
    }

    /**
     * Settings page callback.
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'robots-txt-lite'));
        }

        $robots_txt = get_option('irt_robots_txt', $this->get_default_robots_txt());
        ?>
        <div class="wrap robots-txt-lite-wrap">
            <h1><?php echo esc_html__('Robots.txt Lite', 'robots-txt-lite'); ?></h1>
            
            <?php settings_errors(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('robots_txt_lite_options');
                ?>
                <div class="robots-txt-lite-container">
                    <div class="robots-txt-lite-editor">
                        <textarea 
                            name="irt_robots_txt" 
                            id="robots-txt-content" 
                            rows="15" 
                            class="large-text code"
                        ><?php echo esc_textarea($robots_txt); ?></textarea>
                    </div>
                    <div class="robots-txt-lite-sidebar">
                        <div class="robots-txt-lite-info">
                            <h3><?php echo esc_html__('About Robots.txt', 'robots-txt-lite'); ?></h3>
                            <p><?php echo esc_html__('Edit the robots.txt content as needed. Ensure that search engines can access the necessary parts of your site.', 'robots-txt-lite'); ?></p>
                            
                            <h3><?php echo esc_html__('Useful Links', 'robots-txt-lite'); ?></h3>
                            <ul>
                                <li>
                                    <a href="https://developers.google.com/search/docs/crawling-indexing/robots/intro" target="_blank" rel="noopener noreferrer">
                                        <?php echo esc_html__('Learn more about robots.txt', 'robots-txt-lite'); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="https://www.realrobotstxt.com/" target="_blank" rel="noopener noreferrer">
                                        <?php echo esc_html__('Test your robots.txt', 'robots-txt-lite'); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="<?php echo esc_url(get_site_url() . '/robots.txt'); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php echo esc_html__('View live robots.txt', 'robots-txt-lite'); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="https://search.google.com/search-console/about" target="_blank" rel="noopener noreferrer">
                                        <?php echo esc_html__('Set up search console', 'robots-txt-lite'); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Filter robots.txt content.
     *
     * @param string $output The robots.txt content.
     * @param bool   $public Whether the site is public.
     * @return string
     */
    public function filter_robots_txt($output, $public) {
        return get_option('irt_robots_txt', $this->get_default_robots_txt());
    }
}

// Initialize the plugin
function robots_txt_lite_init() {
    return Robots_Txt_Lite::get_instance();
}

// Start the plugin
robots_txt_lite_init();


