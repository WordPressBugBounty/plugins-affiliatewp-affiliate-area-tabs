<?php
/**
 * AffiliateWP Add-on Activation Handler
 *
 * For use by AffiliateWP and its add-ons.
 *
 * @package     AffiliateWP
 * @subpackage  Tools
 * @copyright   Copyright (c) 2021, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @version     1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AffiliateWP Activation Handler Class
 *
 * @since 1.0.0
 */
class AffiliateWP_Activation {

	/**
	 * Plugin name.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $plugin_name;

	/**
	 * Main plugin file path.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $plugin_path;

	/**
	 * Main plugin filename.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $plugin_file;

	/**
	 * Whether AffiliateWP is installed.
	 *
	 * @since 1.0.0
	 * @var   bool
	 */
	public $has_affiliatewp;

	/**
	 * Message to display if AffiliateWP needs activation.
	 *
	 * @since 1.0.1
	 * @var   string
	 */
	protected $activate_message = '';

	/**
	 * Message to display if AffiliateWP needs installation.
	 *
	 * @since 1.0.1
	 * @var   string
	 */
	protected $install_message = '';

	/**
	 * Sets up the activation class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_path Main add-on plugin directory path (e.g., affiliatewp-affiliate-area-tabs).
	 * @param string $plugin_file Main add-on plugin file (e.g., affiliatewp-affiliate-area-tabs.php).
	 */
	public function __construct( $plugin_path, $plugin_file ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$plugins = get_plugins();

		// Set plugin directory.
		$plugin_path_array = array_filter( explode( '/', $plugin_path ) );
		$this->plugin_path = end( $plugin_path_array );

		// Set plugin file.
		$this->plugin_file = $plugin_file;

		// Set plugin name.
		$plugin_key = $this->plugin_path . '/' . $this->plugin_file;
		if ( isset( $plugins[ $plugin_key ]['Name'] ) ) {
			$this->plugin_name = $plugins[ $plugin_key ]['Name'];
		} else {
			// Fallback if plugin data isn't available (should be rare).
			$this->plugin_name = 'This plugin';
		}

		// Is AffiliateWP installed?
		foreach ( $plugins as $installed_plugin_path => $plugin ) {
			if ( 'AffiliateWP' === $plugin['Name'] ) {
				$this->has_affiliatewp = true;
				break;
			}
		}
	}


	/**
	 * Prepares and hooks the missing AffiliateWP notice.
	 *
	 * @since 1.0.0
	 * @since 1.0.1 Modified to accept pre-translated messages.
	 *
	 * @param string $activate_message The full HTML message to display if AffiliateWP needs activation.
	 * @param string $install_message  The full HTML message to display if AffiliateWP needs installation.
	 */
	public function run( $activate_message, $install_message ) {
		$this->activate_message = $activate_message;
		$this->install_message  = $install_message;

		// Display notice.
		add_action( 'admin_notices', [ $this, 'missing_affiliatewp_notice' ] );
	}

	/**
	 * Displays a notice if AffiliateWP isn't installed.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function missing_affiliatewp_notice() {
		if ( $this->has_affiliatewp ) {
			if ( ! empty( $this->activate_message ) ) {
				echo $this->activate_message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Message is pre-escaped by calling code.
			}
		} elseif ( ! empty( $this->install_message ) ) {
				echo $this->install_message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Message is pre-escaped by calling code.

		}
	}
}
