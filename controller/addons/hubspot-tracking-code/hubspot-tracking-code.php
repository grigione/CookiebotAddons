<?php

namespace cookiebot_addons_framework\controller\addons\hubspot_tracking_code;

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

use cookiebot_addons_framework\controller\addons\Cookiebot_Addons_Interface;
use cookiebot_addons_framework\lib\script_loader_tag\Script_Loader_Tag_Interface;
use cookiebot_addons_framework\lib\Cookie_Consent_Interface;
use cookiebot_addons_framework\lib\buffer\Buffer_Output_Interface;
use cookiebot_addons_framework\lib\Settings_Service_Interface;

class Hubspot_Tracking_Code implements Cookiebot_Addons_Interface {

	/**
	 * @var Settings_Service_Interface
	 *
	 * @since 1.3.0
	 */
	protected $settings;

	/**
	 * @var Script_Loader_Tag_Interface
	 *
	 * @since 1.3.0
	 */
	protected $script_loader_tag;

	/**
	 * @var Cookie_Consent_Interface
	 *
	 * @since 1.3.0
	 */
	protected $cookie_consent;

	/**
	 * @var Buffer_Output_Interface
	 *
	 * @since 1.3.0
	 */
	protected $buffer_output;

	/**
	 * Jetpack constructor.
	 *
	 * @param $settings Settings_Service_Interface
	 * @param $script_loader_tag Script_Loader_Tag_Interface
	 * @param $cookie_consent Cookie_Consent_Interface
	 * @param $buffer_output Buffer_Output_Interface
	 *
	 * @since 1.3.0
	 */
	public function __construct( Settings_Service_Interface $settings, Script_Loader_Tag_Interface $script_loader_tag, Cookie_Consent_Interface $cookie_consent, Buffer_Output_Interface $buffer_output ) {
		$this->settings          = $settings;
		$this->script_loader_tag = $script_loader_tag;
		$this->cookie_consent    = $cookie_consent;
		$this->buffer_output     = $buffer_output;
	}

	/**
	 * Loads addon configuration
	 *
	 * @since 1.3.0
	 */
	public function load_configuration() {
		/**
		 * We add the action after wp_loaded and replace the original
		 * HubSpot Tracking Code action with our own adjusted version.
		 */
		add_action( 'wp_loaded', array( $this, 'cookiebot_addon_hubspot_tracking_code' ), 10 );
	}

	/**
	 * Manipulate the scripts if they are loaded.
	 *
	 * @since 1.3.0
	 */
	public function cookiebot_addon_hubspot_tracking_code() {
		// Check if HubSpot Tracking Code is loaded
		$options = get_option( 'hs_settings' );
		if ( ! isset( $options['hs_portal'] ) || $options['hs_portal'] == '' ) {
			return;
		}

		// Check if Cookiebot is activated and active.
		if ( ! function_exists( 'cookiebot_active' ) || ! cookiebot_active() ) {
			return;
		}

		// consent is given
		if( $this->cookie_consent->are_cookie_states_accepted( $this->get_cookie_types() ) ) {
			return;
		}

		// Replace original HubSpot Tracking Code with own one and delete cookie if
		// it was perviously set.
		if ( is_plugin_active( 'hubspot-tracking-code/hubspot-tracking-code.php' ) ) {
			/**
			 * Consent not given - no cache
			 */
			$this->buffer_output->add_tag( 'wp_footer', 10, array( 'hs-script-loader' => $this->get_cookie_types() ), false );

			if ( ! $this->cookie_consent->is_cookie_state_accepted( 'marketing' ) && isset( $_COOKIE['hubspotutk'] ) ) {
				unset( $_COOKIE['hubspotutk'] );
			}
		}
	}

	/**
	 * Return addon/plugin name
	 *
	 * @return string
	 *
	 * @since 1.3.0
	 */
	public function get_addon_name() {
		return 'Hubspot Tracking Code';
	}

	/**
	 * Option name in the database
	 *
	 * @return string
	 *
	 * @since 1.3.0
	 */
	public function get_option_name() {
		return 'hubspot_tracking_code';
	}

	/**
	 * Plugin file name
	 *
	 * @return string
	 *
	 * @since 1.3.0
	 */
	public function get_plugin_file() {
		return 'hubspot-tracking-code/hubspot-tracking-code.php';
	}

	/**
	 * Returns checked cookie types
	 * @return mixed
	 *
	 * @since 1.3.0
	 */
	public function get_cookie_types() {
		return $this->settings->get_cookie_types( $this->get_option_name() );
	}

	/**
	 * Check if plugin is activated and checked in the backend
	 *
	 * @since 1.3.0
	 */
	public function is_addon_enabled() {
		return $this->settings->is_addon_enabled( $this->get_option_name() );
	}

	/**
	 * Checks if addon is installed
	 *
	 * @since 1.3.0
	 */
	public function is_addon_installed() {
		return $this->settings->is_addon_installed( $this->get_plugin_file() );
	}

	/**
	 * Checks if addon is activated
	 *
	 * @since 1.3.0
	 */
	public function is_addon_activated() {
		return $this->settings->is_addon_activated( $this->get_plugin_file() );
	}
}
