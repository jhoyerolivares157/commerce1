<?php
/**
 * Plugin Name: Ovic Toolkit
 * Plugin URI: https://kutethemes.com/wordpress-plugins/
 * Description: The Ovic Toolkit For WordPress Theme WooCommerce Shop.
 * Author: Ovic Team
 * Author URI: https://kutethemes.com
 * Version: 1.6.2
 * WC requires at least: 3.0
 * WC tested up to: 4.9.0
 * Text Domain: ovic-toolkit
 */
// Exit if accessed directly.
if ( ! defined('ABSPATH')) {
    exit;
}
if ( ! class_exists('Ovic_Toolkit')) {
    class  Ovic_Toolkit
    {
        /**
         * @var Ovic_Toolkit The one true Ovic_Toolkit
         */
        private static $instance;

        public static function instance()
        {
            if ( ! isset(self::$instance) && ! (self::$instance instanceof Ovic_Toolkit)) {
                self::$instance = new Ovic_Toolkit;
                self::$instance->setup_constants();
                self::$instance->setup_plugins();
                // includes.
                add_action('plugins_loaded', array(self::$instance, 'includes'));
                // Add action to enqueue scripts.
                add_action('admin_enqueue_scripts', array(self::$instance, 'enqueue_scripts'));
                add_action('after_setup_theme', array(self::$instance, 'after_setup_theme'));
            }

            return self::$instance;
        }

        public function after_setup_theme()
        {
            require_once OVIC_TOOLKIT_PLUGIN_DIR.'includes/admin/updater/theme-updater.php';
            require_once OVIC_TOOLKIT_PLUGIN_DIR.'includes/admin/import/import.php';
        }

        public function enqueue_scripts()
        {
            wp_enqueue_style('backend', OVIC_TOOLKIT_PLUGIN_URL.'/assets/css/backend.css');
        }

        public function setup_constants()
        {
            // Plugin Folder File.
            if ( ! defined('OVIC_TOOLKIT_FILE')) {
                define('OVIC_TOOLKIT_FILE', __FILE__);
            }
            // Plugin version.
            if ( ! defined('OVIC_TOOLKIT_VERSION')) {
                define('OVIC_TOOLKIT_VERSION', '1.6.2');
            }
            // Plugin Folder Path.
            if ( ! defined('OVIC_TOOLKIT_PLUGIN_DIR')) {
                define('OVIC_TOOLKIT_PLUGIN_DIR', trailingslashit(plugin_dir_path(__FILE__)));
            }
            // Plugin Folder URL.
            if ( ! defined('OVIC_TOOLKIT_PLUGIN_URL')) {
                define('OVIC_TOOLKIT_PLUGIN_URL', trailingslashit(plugin_dir_url(__FILE__)));
            }
        }

        public function setup_plugins()
        {
            require_once OVIC_TOOLKIT_PLUGIN_DIR.'includes/frontend/framework.php';
            require_once OVIC_TOOLKIT_PLUGIN_DIR.'includes/frontend/includes/visual-composer/shortcode.php';
            require_once OVIC_TOOLKIT_PLUGIN_DIR.'includes/admin/dashboard.php';
            require_once OVIC_TOOLKIT_PLUGIN_DIR.'includes/admin/settings/ovic-settings.php';
        }

        public function includes()
        {
            /* extends */
            require_once OVIC_TOOLKIT_PLUGIN_DIR.'includes/extends/mailchimp/mailchimp.php';
            require_once OVIC_TOOLKIT_PLUGIN_DIR.'includes/extends/mapper/includes/core.php';
            require_once OVIC_TOOLKIT_PLUGIN_DIR.'includes/extends/live-search/live-search.php';
            require_once OVIC_TOOLKIT_PLUGIN_DIR.'includes/extends/popup/popup.php';
            require_once OVIC_TOOLKIT_PLUGIN_DIR.'includes/extends/megamenu/megamenu.php';
            require_once OVIC_TOOLKIT_PLUGIN_DIR.'includes/extends/post-like/post-like.php';
            require_once OVIC_TOOLKIT_PLUGIN_DIR.'includes/extends/post-rating/post-rating.php';
            require_once OVIC_TOOLKIT_PLUGIN_DIR.'includes/extends/footer-builder/footer-builder.php';
            if (class_exists('WooCommerce')) {
                require_once OVIC_TOOLKIT_PLUGIN_DIR.'includes/extends/shop-ajax/shop.php';
                require_once OVIC_TOOLKIT_PLUGIN_DIR.'includes/extends/product-brand/product-brand.php';
                require_once OVIC_TOOLKIT_PLUGIN_DIR.'includes/extends/attributes-swatches/product-attribute-meta.php';
            }
            /* load text domain */
            load_plugin_textdomain('ovic-toolkit', false, OVIC_TOOLKIT_PLUGIN_DIR.'languages');
        }
    }
}
if ( ! function_exists('ovic_toolkit')) {
    function ovic_toolkit()
    {
        return Ovic_Toolkit::instance();
    }
}
ovic_toolkit();