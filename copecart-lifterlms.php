<?php
/**
 * Plugin Name: CopeCart-LifterLMSG
 * Plugin URI: https://wordpress.org/plugins/copecart-lifterlmsg/
 * Description: Used for CopeCart Payment Gateway Integration With Lifter LMS
 * Version: 1.0.0
 * Author: CopeCart
 * Author URI: https://copecart.com
 * Text Domain: copecart-lifterlmsg
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Restrict direct access

/**
 * CopeCart Main Class
 * 
 * @since 1.0.0
 */
if ( ! class_exists( 'LifterLMS_CopeCart') ) :
	
	final class LifterLMS_CopeCart {
		
		/**
		 * Plugin Version
		 * @since 1.0.0
		 */
		public $version = '1.0.0';
		
		/**
		 * Singleton instance of the class
		 * @since 1.0.0
		 */
		private static $_instance = null;
		
		/**
		 * Singleton Instance of the LifterLMS_CopeCart class
		 * 
		 * @since 1.0.0
		 */
		public static function instance() {
			
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			
			return self::$_instance;
		}
		
		/**
		 * Contructor
		 * 
		 * @since 1.0.0
		 */
		private function __construct() {
			
			$this->define_constants();
			add_action( 'plugins_loaded', array( $this, 'init') );
		}

		/**
		 * Plugin Activation
		 * 
		 * @since 1.0.0
		 */
		static function llmscopecart_install() {

			//get option for when plugin is activating first time
			$lifterlms_set_option = get_option( 'lifterlms_set_version_option' );
			
			if( empty( $lifterlms_set_option ) ) { //check plugin version option

				update_option( 'lifterlms_copecart_disable_order_checkout', 'yes' );
				update_option( 'lifterlms_set_version_option', '1.0.0' );
			}

			$lifterlms_set_option = get_option( 'lifterlms_set_version_option' );

			if( $lifterlms_set_option == '1.0.0' ) {
				//Future Code Here.
			}
		}
		
		/**
		 * Define constants for plugin
		 * 
		 * @since  1.0.0
		 */
		private function define_constants() {
			
			if ( ! defined( 'LLMS_COPECART_VERSION' ) ) {
				define( 'LLMS_COPECART_VERSION', __FILE__ ); // plugin version
			}
			if( !defined( 'LLMS_COPECART_DIR' ) ) {
				define( 'LLMS_COPECART_DIR', dirname( __FILE__ ) ); // Plugin dir
			}
			if( !defined( 'LLMS_COPECART_URL' ) ) {
				define( 'LLMS_COPECART_URL', plugin_dir_url( __FILE__ ) ); // Plugin url
			}
			if( !defined( 'LLMS_COPECART_PLUGIN_BASENAME' ) ) {
				define( 'LLMS_COPECART_PLUGIN_BASENAME', basename( LLMS_COPECART_DIR ) ); //Plugin base name
			}
			if( !defined( 'LLMS_COPECART_MEMBERSHIP_CPT' ) ) {
				define( 'LLMS_COPECART_MEMBERSHIP_CPT', 'llms_membership' ); // Membership CPT
			}
			if( !defined( 'LLMS_COPECART_COURSE_CPT' ) ) {
				define( 'LLMS_COPECART_COURSE_CPT', 'course' ); // Course CPT
			}
			if( !defined( 'LLMS_COPECART_STUDENT_ROLE' ) ) {
				define( 'LLMS_COPECART_STUDENT_ROLE', 'student' ); // Student User Role
			}
		}
		
		/**
		 * This function makes sure that the parent plugin (LifterLMS) is active
		 * before trying to tie to hooks in that plugin. If LifterLMS is active,
		 * the plugin will latch on to the hooks. If not, the plugin will not
		 * activate (or will deactive itself)
		 * 
		 * @since   1.0.0
		 */
		public function init() {
			$this->includes();
		}
		
		/**
		 * Include all files
		 * 
		 * @since 1.0.0
		 */
		public function includes() {
			
			// load textdomain
			$this->load_textdomain();
			
			// only load mailchimp plugin if LifterLMS class exists.
			include_once( 'includes/lifterlms-copecart-settings.php' );
			include_once( 'includes/lifterlms-copecart-ipn.php' );
			
			if ( ! $this->is_enabled() ) {
				return;
			}
		}
		
		/**
		 * Determine if LifterLMS MailChimp is enabled based on the value of the setting
		 * mapped to the "Enable" setting on the LLMS integrations screen [yes|no]
		 * 
		 * @since 1.0.0
		 */
		public function is_enabled() {
			return ( 'yes' === get_option( 'lifterlms_copecart_enabled', 'no' ) );
		}
		
		/**
		 * Load Localization files
		 * The first loaded file takes priority
		 * 
		 * @since 1.0.0
		 */
		public function load_textdomain() {
			
			// Set filter for plugin's languages directory
			$llmscopecart_lang_dir	= dirname( plugin_basename( __FILE__ ) ) . '/languages/';
			$llmscopecart_lang_dir	= apply_filters( 'llmscopecart_languages_directory', $llmscopecart_lang_dir );
			
			// Traditional WordPress plugin locale filter
			$locale	= apply_filters( 'plugin_locale',  get_locale(), 'copecart-lifterlms' );
			$mofile	= sprintf( '%1$s-%2$s.mo', 'copecart-lifterlms', $locale );
			
			// Setup paths to current locale file
			$mofile_local	= $llmscopecart_lang_dir . $mofile;
			$mofile_global	= WP_LANG_DIR . '/' . LLMS_COPECART_PLUGIN_BASENAME . '/' . $mofile;
			
			if ( file_exists( $mofile_global ) ) { // Look in global /wp-content/languages/lifterlms-copecart folder
				load_textdomain( 'copecart-lifterlms', $mofile_global );
			} elseif ( file_exists( $mofile_local ) ) { // Look in local /wp-content/plugins/lifterlms-copecart/languages/ folder
				load_textdomain( 'copecart-lifterlms', $mofile_local );
			} else { // Load the default language files
				load_plugin_textdomain( 'copecart-lifterlms', false, $llmscopecart_lang_dir );
			}
		}
	}

endif;


/**
 * Plugin Activation Hook
 * 
 * @since 1.0.1
 */
register_activation_hook( __FILE__, array( 'LifterLMS_CopeCart', 'llmscopecart_install' ) );

/**
 * Main CopeCart Instance
 * 
 * @since 1.0.0
 */
function Lifter_CopeCart() {
	return LifterLMS_CopeCart::instance();
}
return Lifter_CopeCart();