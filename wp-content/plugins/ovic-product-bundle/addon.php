<?php
/**
 * Plugin Name: Ovic: Product Bundle
 * Plugin URI: https://kutethemes.com/
 * Description: Support WooCommerce Product Bundle.
 * Author: Ovic Team
 * Author URI: https://themeforest.net/user/kutethemes
 * Version: 1.1.2
 * WC requires at least: 3.0
 * WC tested up to: 5.1.0
 * Text Domain: ovic-bundle
 */
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
if (!class_exists('Ovic_Product_Bundle')) {
    class  Ovic_Product_Bundle
    {
        /**
         * @var Ovic_Product_Bundle The one true Ovic_Product_Bundle
         */
        private static $instance;

        public static function instance()
        {
            if (!isset(self::$instance) && !(self::$instance instanceof Ovic_Product_Bundle)) {
                self::$instance = new Ovic_Product_Bundle;
                self::$instance->setup_constants();
                self::$instance->includes();
                add_action('plugins_loaded', array(self::$instance, 'load_text_domain'));
            }

            return self::$instance;
        }

        public function setup_constants()
        {
            // Plugin version.
            if (!defined('OVIC_BUNDLE_VERSION')) {
                define('OVIC_BUNDLE_VERSION', '1.1.2');
            }
            // Plugin basename.
            if (!defined('OVIC_BUNDLE_BASENAME')) {
                define('OVIC_BUNDLE_BASENAME', plugin_basename(__FILE__));
            }
            // Plugin Folder Path.
            if (!defined('OVIC_BUNDLE_DIR')) {
                define('OVIC_BUNDLE_DIR', trailingslashit(plugin_dir_path(__FILE__)));
            }
            // Plugin Folder URL.
            if (!defined('OVIC_BUNDLE_URI')) {
                define('OVIC_BUNDLE_URI', trailingslashit(plugin_dir_url(__FILE__)));
            }
        }

        public function includes()
        {
            require_once OVIC_BUNDLE_DIR.'includes/welcome.php';
        }

        public function load_text_domain()
        {
            if (!function_exists('WC') || !version_compare(WC()->version, '3.0.0', '>=')) {
                add_action('admin_notices', array(self::$instance, 'bundle_notice_wc'));

                return;
            }
            load_plugin_textdomain('ovic-bundle', false, OVIC_BUNDLE_DIR.'languages');
            /* INCLUDE FILE */
            require_once OVIC_BUNDLE_DIR.'includes/dashboard.php';
            require_once OVIC_BUNDLE_DIR.'includes/settings.php';
            require_once OVIC_BUNDLE_DIR.'includes/bundle.php';
        }

        public function bundle_notice_wc()
        {
            ?>
            <div class="error">
                <p><?php esc_html_e('Ovic Product Bundles require WooCommerce version 3.0.0 or greater.', 'ovic-bundle'); ?></p>
            </div>
            <?php
        }
    }
}
if (!function_exists('Ovic_Product_Bundle')) {
    function Ovic_Product_Bundle()
    {
        return Ovic_Product_Bundle::instance();
    }
}
Ovic_Product_Bundle();