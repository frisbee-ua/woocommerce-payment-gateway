<?php
/*
Plugin Name: WooCommerce - Frisbee | Buy Now, Pay Later
Plugin URI: https://frisbee.ua/
Description: Frisbee payments plugin for WooCommerce.
Version: 1.0.0
Author: Frisbee | Buy Now, Pay Later
Author URI: https://frisbee.ua/
Domain Path: /languages
Text Domain: frisbee-woocommerce-payment-gateway
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 2.5.0
WC tested up to: 4.7.1
*/

defined( 'ABSPATH' ) or exit;
define( 'FRISBEE_BASE_PATH' ,  plugin_dir_url( __FILE__ ) );
if ( ! class_exists( 'WC_PaymentFrisbee' ) ) :
    class WC_PaymentFrisbee
    {
        private static $instance;

        /**
         * @return WC_PaymentFrisbee
         */
        public static function get_instance()
        {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * WC_PaymentFrisbee constructor.
         */
        protected function __construct()
        {
            add_action( 'plugins_loaded', array( $this, 'init' ) );
            add_action( 'woocommerce_api_frisbee_webhook' , array( $this, 'webhook' ) );
            register_uninstall_hook( __FILE__, 'plugin_uninstall');
        }

        /**
         * init frisbee
         */
        public function init()
        {
            if ( self::check_environment() ) {
                return;
            }
            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
            $this->init_frisbee();
        }

        /**
         * init frisbee
         */
        public function init_frisbee()
        {
            require_once( dirname( __FILE__ ) . '/includes/class-wc-frisbee-gateway.php' );
            load_plugin_textdomain( "frisbee-woocommerce-payment-gateway", false, basename( dirname( __FILE__ )) . '/languages' );
            add_filter( 'woocommerce_payment_gateways', array( $this, 'woocommerce_add_frisbee_gateway' ) );
            add_action('wp_ajax_nopriv_generate_ajax_order_frisbee_info', array('WC_frisbee', 'generate_ajax_order_frisbee_info' ), 99);
            add_action('wp_ajax_generate_ajax_order_frisbee_info', array('WC_frisbee', 'generate_ajax_order_frisbee_info'), 99);
        }

        /**
         * Checks the environment for compatibility problems.  Returns a string with the first incompatibility
         * found or false if the environment has no problems.
         */
        static function check_environment()
        {
            if ( version_compare( phpversion(), '5.4.0', '<' ) ) {
                $message = __( ' The minimum PHP version required for Frisbee is %1$s. You are running %2$s.', 'woocommerce-frisbee' );

                return sprintf( $message, '5.4.0', phpversion() );
            }

            if ( ! defined( 'WC_VERSION' ) ) {
                return __( 'WooCommerce needs to be activated.', 'woocommerce-frisbee' );
            }

            if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) {
                $message = __( 'The minimum WooCommerce version required for Frisbee is %1$s. You are running %2$s.', 'woocommerce-frisbee' );

                return sprintf( $message, '2.0.0', WC_VERSION );
            }

            return false;
        }

        public function plugin_action_links( $links )
        {
            $plugin_links = array(
                '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=frisbee' ) . '">' . __( 'Settings', 'woocommerce-frisbee' ) . '</a>',
            );

            return array_merge( $plugin_links, $links );
        }

        /**
         * Add the Gateway to WooCommerce
         * @param $methods
         * @return array
         */
        public function woocommerce_add_frisbee_gateway( $methods )
        {
            $methods[] = 'WC_frisbee';
            return $methods;
        }

        public function webhook()
        {
            $wcFrisbee = new WC_frisbee();
            $data = $wcFrisbee->getRequestData();

            if (isset($data['order_id'])) {
                $wcFrisbee->validatePayment($data);
            }
        }

        public function plugin_uninstall()
        {
            $options = get_option('woocommerce_frisbee_settings');
            if (isset($options['save_data_after_uninstall']) && $options['save_data_after_uninstall'] == 'no') {
                delete_option('woocommerce_frisbee_settings');
            }
        }
    }

    $GLOBALS['wc_frisbee'] = WC_PaymentFrisbee::get_instance();
endif;
