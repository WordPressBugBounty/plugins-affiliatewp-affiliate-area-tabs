<?php
/**
 * Plugin Name: AffiliateWP - Affiliate Area Tabs
 * Plugin URI: https://affiliatewp.com/addons/affiliate-area-tabs/
 * Description: Add and reorder tabs in the Affiliate Area
 * Author: AffiliateWP
 * Author URI: https://affiliatewp.com
 * Version: 1.4.2
 * Text Domain: affiliatewp-affiliate-area-tabs
 * Domain Path: languages
 *
 * @package AffiliateWP_Affiliate_Area_Tabs
 *
 * AffiliateWP is distributed under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * AffiliateWP is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with AffiliateWP. If not, see <http://www.gnu.org/licenses/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AffiliateWP_Requirements_Check_v1_1' ) ) {
	require_once __DIR__ . '/includes/lib/affwp/class-affiliatewp-requirements-check-v1-1.php';
}

/**
 * Class used to check requirements for and bootstrap the plugin.
 *
 * @since 1.3
 *
 * @see Affiliate_WP_Requirements_Check
 */
class AffiliateWP_AAT_Requirements_Check extends AffiliateWP_Requirements_Check_v1_1 {

	/**
	 * Plugin slug.
	 *
	 * @since 1.3
	 * @var   string
	 */
	protected $slug = 'affiliatewp-affiliate-area-tabs';

	/**
	 * Add-on requirements.
	 *
	 * @since 1.3
	 * @var   array[]
	 */
	protected $addon_requirements = [
		// AffiliateWP.
		'affwp' => [
			'minimum' => '2.6',
			'name'    => 'AffiliateWP',
			'exists'  => true,
			'current' => false,
			'checked' => false,
			'met'     => false,
		],
	];
	/**
	 * Retrieves the display name of the current plugin.
	 *
	 * @since 1.4.2
	 * @return string Plugin display name.
	 */
	private function _get_plugin_display_name() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		// $this->get_file() is available from the parent class constructor.
		$plugin_data = get_plugin_data( $this->get_file() );
		return ! empty( $plugin_data['Name'] ) ? $plugin_data['Name'] : basename( dirname( $this->get_file() ) ); // Fallback to dir name.
	}

	/**
	 * Retrieves the activation message for when AffiliateWP needs to be activated.
	 *
	 * @since 1.4.2
	 * @param string $plugin_name The display name of the current plugin.
	 * @return string The translated HTML message.
	 */
	private function _get_affwp_missing_activate_message( $plugin_name ) {
		// translators: %1$s: Plugin name, %2$s: AffiliateWP link text (HTML).
		return '<div class="error"><p>' . sprintf( __( '%1$s requires %2$s. Please activate it to continue.', 'affiliatewp-affiliate-area-tabs' ), esc_html( $plugin_name ), '<a href="https://affiliatewp.com/" title="AffiliateWP" target="_blank">AffiliateWP</a>' ) . '</p></div>';
	}

	/**
	 * Retrieves the installation message for when AffiliateWP needs to be installed.
	 *
	 * @since 1.4.2
	 * @param string $plugin_name The display name of the current plugin.
	 * @return string The translated HTML message.
	 */
	private function _get_affwp_missing_install_message( $plugin_name ) {
		// translators: %1$s: Plugin name, %2$s: AffiliateWP link text (HTML).
		return '<div class="error"><p>' . sprintf( __( '%1$s requires %2$s. Please install it to continue.', 'affiliatewp-affiliate-area-tabs' ), esc_html( $plugin_name ), '<a href="https://affiliatewp.com/" title="AffiliateWP" target="_blank">AffiliateWP</a>' ) . '</p></div>';
	}

	/**
	 * Shows the AffiliateWP missing/inactive notice.
	 *
	 * @since 1.4.2
	 */
	private function _show_affwp_missing_notice() {
		$activation_class_path = dirname( $this->get_file() ) . '/includes/lib/affwp/class-affiliatewp-activation.php';

		if ( ! class_exists( 'AffiliateWP_Activation' ) ) {
			if ( file_exists( $activation_class_path ) ) {
				require_once $activation_class_path;
			} else {
				return; // Can't proceed if library class file is missing.
			}
		}

		if ( class_exists( 'AffiliateWP_Activation' ) ) {
			$plugin_dir_path  = dirname( $this->get_file() );
			$plugin_base_name = basename( $this->get_file() );

			$plugin_name = $this->_get_plugin_display_name();

			$activate_message = $this->_get_affwp_missing_activate_message( $plugin_name );
			$install_message  = $this->_get_affwp_missing_install_message( $plugin_name );

			$activation = new AffiliateWP_Activation( $plugin_dir_path, $plugin_base_name );
			$activation->run( $activate_message, $install_message );
		}
	}

	/**
	 * Quits without loading the plugin and displays appropriate notices.
	 *
	 * This method is called by the parent class when requirements are not met.
	 * We override it to add a more specific notice if AffiliateWP is missing.
	 */
	protected function quit() {
		// Handle parent duties like plugin row notices and links.
		parent::quit();

		// If AffiliateWP is a requirement and it's not met, show the main activation notice.
		if ( isset( $this->requirements['affwp'] ) && false === $this->requirements['affwp']['met'] ) {
			$this->_show_affwp_missing_notice();
		}
	}

	/**
	 * Bootstrap everything.
	 *
	 * @since 1.3
	 */
	public function bootstrap() {
		if ( ! class_exists( 'Affiliate_WP' ) ) {
			// This notice display is primarily for the activation hook context.
			$this->_show_affwp_missing_notice();
		} else {
			// If requirements are met (AffiliateWP class exists), proceed to load the main plugin logic.
			// This was previously in the load() method, called from here.
			// Ensuring it only runs if AffiliateWP is present.
			if ( ! class_exists( 'AffiliateWP_Affiliate_Area_Tabs' ) ) {
				require_once __DIR__ . '/includes/class-affiliatewp-affiliate-area-tabs.php';
			}
			if ( class_exists( 'AffiliateWP_Affiliate_Area_Tabs' ) ) {
				\AffiliateWP_Affiliate_Area_Tabs::instance( $this->get_file() );
			}
		}
	}

	/**
	 * Loads the add-on.
	 *
	 * @since 1.3
	 */
	protected function load() {
		// Bootstrap to plugins_loaded. This will call the bootstrap() method above.
		// The bootstrap() method will then handle showing notice OR loading the plugin instance.
		$affwp_version = get_option( 'affwp_version' );

		if ( $affwp_version && version_compare( $affwp_version, '2.7', '>=' ) ) {
			add_action( 'affwp_plugins_loaded', [ $this, 'bootstrap' ], 100 );
		} else {
			add_action( 'plugins_loaded', [ $this, 'bootstrap' ], 100 );
		}
	}

	/**
	 * Install, usually on an activation hook.
	 *
	 * @since 1.3
	 */
	public function install() {
		// Bootstrap to include all of the necessary files and potentially show notices.
		$this->bootstrap();

		if ( defined( 'AFFWP_AAT_VERSION' ) ) {
			update_option( 'affwp_aat_version', AFFWP_AAT_VERSION );
		}
	}

	/**
	 * Plugin-specific aria label text to describe the requirements link.
	 *
	 * @since 1.3
	 *
	 * @return string Aria label text.
	 */
	protected function unmet_requirements_label() {
		return esc_html__( 'AffiliateWP - Affiliate Area Tabs Requirements', 'affiliatewp-affiliate-area-tabs' );
	}

	/**
	 * Plugin-specific text used in CSS to identify attribute IDs and classes.
	 *
	 * @since 1.3
	 *
	 * @return string CSS selector.
	 */
	protected function unmet_requirements_name() {
		return 'affiliatewp-affiliate-area-tabs-requirements';
	}

	/**
	 * Plugin specific URL for an external requirements page.
	 *
	 * @since 1.3
	 *
	 * @return string Unmet requirements URL.
	 */
	protected function unmet_requirements_url() {
		return 'https://affiliatewp.com/docs/minimum-requirements-roadmap/';
	}
}

$requirements = new AffiliateWP_AAT_Requirements_Check( __FILE__ );

// Register the activation hook here, so it's always registered when the plugin file loads.
register_activation_hook( __FILE__, [ $requirements, 'install' ] );

$requirements->maybe_load();
