<?php
/**
 * AffiliateWP Minimum Requirements API
 *
 * For use by AffiliateWP and its add-ons.
 *
 * @package     AffiliateWP
 * @subpackage  Tools
 * @copyright   Copyright (c) 2021, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @version     1.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class used by AffiliateWP to enforce minimum requirements for itself and its add-ons.
 *
 * @since 1.0.0
 * @since 1.1.0 Renamed to AffiliateWP_Requirements_Check_V1_1
 * @abstract
 */
abstract class AffiliateWP_Requirements_Check_V1_1 {

	/**
	 * Plugin base file.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $file = '';

	/**
	 * Plugin basename.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $base = '';

	/**
	 * Plugin slug.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $slug = 'affiliate-wp';

	/**
	 * Requirements array.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 WordPress minimum version raised to 5.2.
	 * @var   array[]
	 */
	protected $requirements = [
		// PHP.
		'php' => [
			'minimum' => '5.6',
			'name'    => 'PHP',
			'exists'  => true,
			'current' => false,
			'checked' => false,
			'met'     => false,
		],

		// WordPress.
		'wp'  => [
			'minimum' => '5.2.0',
			'name'    => 'WordPress',
			'exists'  => true,
			'current' => false,
			'checked' => false,
			'met'     => false,
		],
	];

	/**
	 * Add-on requirements array.
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	protected $addon_requirements = [];

	/**
	 * Sets up the plugin requirements class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file Main plugin file.
	 */
	public function __construct( $file ) {
		// Setup file & base.
		$this->file = $file;
		$this->base = plugin_basename( $this->get_file() );

		// Merge add-on requirements (if any).
		$this->requirements = array_merge( $this->requirements, $this->addon_requirements );

		$affwp_version = get_option( 'affwp_version' );

		// Always load translations.
		if ( version_compare( $affwp_version, '2.7', '<' ) ) {
			add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		} else {
			add_action( 'affwp_plugins_loaded', [ $this, 'load_textdomain' ] );
		}
	}

	/**
	 * Retrieves the main plugin file.
	 *
	 * @since 1.0.0
	 *
	 * @return string Main plugin file.
	 */
	public function get_file() {
		return $this->file;
	}

	/**
	 * (Maybe) loads the plugin.
	 *
	 * @since 1.0.0
	 */
	public function maybe_load() {
		// Load or quit.
		$this->met() ? $this->load() : $this->quit();
	}

	/**
	 * Getter for requirements.
	 *
	 * The requirements class automatically supports checking 'wp', 'php', and 'affwp' keyed requirements.
	 *
	 * Add-ons can register custom requirements (or override defaults outlined in `$requirements`) by
	 * defining the `$addon_requirements` property in its own sub-class. If overriding default requirements,
	 * the same keys and metadata - save for the new version numbers - should be used.
	 *
	 * Custom requirement example:
	 *
	 *     protected $addon_requirements = array(
	 *         // AffiliateWP - Affiliate Portal.
	 *         'affwp_portal' => array(
	 *             'minimum' => '1.0.0',
	 *             'name'    => 'AffiliateWP - Affiliate Portal',
	 *             'exists'  => true,
	 *             'current' => false,
	 *             'checked' => false,
	 *             'met'     => false
	 *         ),
	 *     );
	 *
	 * To hook up the version check, a corresponding method for each custom requirement MUST be added.
	 * The simplify this, the requirements class will automatically look for a method named
	 * "check_{requirement_name}".
	 *
	 * For example, for the 'affwp_portal' requirement:
	 *
	 *     public function check_affwp_portal() {
	 *         return get_option( 'affwp_ap_version' );
	 *     }
	 *
	 * @since 1.0.0
	 *
	 * @return array Plugin requirements.
	 */
	protected function get_requirements() {
		return $this->requirements;
	}

	/**
	 * Quits without loading the plugin.
	 *
	 * @since 1.0.0
	 */
	protected function quit() {
		add_action( 'admin_head', [ $this, 'admin_head' ] );
		add_filter( "plugin_action_links_{$this->base}", [ $this, 'plugin_row_links' ] );
		add_action( "after_plugin_row_{$this->base}", [ $this, 'plugin_row_notice' ] );
		// Child classes are responsible for any broader activation notices (e.g., AffiliateWP_Activation).
	}

	//
	// Specific Methods.
	//

	/**
	 * Handles actually loading the plugin.
	 *
	 * @since 1.0.0
	 */
	abstract protected function load();

	/**
	 * Install, usually on an activation hook.
	 *
	 * Note: A sub-class extension of this method is typically a good place to call a relevant
	 * install function or set the add-on version option directly.
	 *
	 * @since 1.0.0
	 */
	public function install() {
		// Bootstrap to include all of the necessary files.
		$this->bootstrap();
	}

	/**
	 * Bootstraps everything.
	 *
	 * @since 1.0.0
	 */
	public function bootstrap() {
		\Affiliate_WP::instance( $this->get_file() );
	}

	/**
	 * Sets the plugin-specific URL for an external requirements page.
	 *
	 * @since 1.0.0
	 *
	 * @return string Unmet requirements URL.
	 */
	protected function unmet_requirements_url() {
		return '';
	}

	/**
	 * Sets the plugin-specific text to quickly explain what's wrong.
	 *
	 * @since 1.0.0
	 *
	 * @return void Unmet requirements text.
	 */
	private function unmet_requirements_text() {
		esc_html_e( 'This plugin is not fully active.', 'affiliate-wp' );
	}

	/**
	 * Sets the plugin-specific text to describe a single unmet requirement.
	 *
	 * @since 1.0.0
	 *
	 * @return string Unmet requirements description text.
	 */
	private function unmet_requirements_description_text() {
		// translators: %1$s: Name of the required item (e.g., WordPress), %2$s: Minimum required version, %3$s: Currently installed version.
		return esc_html__( 'Requires %1$s (%2$s), but (%3$s) is installed.', 'affiliate-wp' );
	}

	/**
	 * Sets the plugin-specific text to describe a single missing requirement.
	 *
	 * @since 1.0.0
	 *
	 * @return string Unmet missing requirements text.
	 */
	private function unmet_requirements_missing_text() {
		// translators: %1$s: Name of the required item (e.g., AffiliateWP), %2$s: Minimum required version.
		return esc_html__( 'Requires %1$s (%2$s), but it appears to be missing.', 'affiliate-wp' );
	}

	/**
	 * Sets the plugin-specific text used to link to an external requirements page.
	 *
	 * @since 1.0.0
	 *
	 * @return string Unmet requirements link text.
	 */
	private function unmet_requirements_link() {
		return esc_html__( 'Requirements', 'affiliate-wp' );
	}

	/**
	 * Sets the plugin-specific aria label text to describe the requirements link.
	 *
	 * @since 1.0.0
	 *
	 * @return string Aria label text.
	 */
	protected function unmet_requirements_label() {
		return esc_html__( 'AffiliateWP Requirements', 'affiliate-wp' );
	}

	/**
	 * Sets the plugin-specific text used in CSS to identify attribute IDs and classes.
	 *
	 * @since  1.0.0
	 *
	 * @return string CSS selector.
	 */
	protected function unmet_requirements_name() {
		return 'affwp-requirements';
	}

	/**
	 * Sets up the plugin-agnostic method to output the additional plugin row.
	 *
	 * @since 1.0.0
	 */
	public function plugin_row_notice() {
		// Get the requirements name.
		$name = $this->unmet_requirements_name();

		// Bail if requirements are met.
		if ( $this->met() ) {
			return;
		}

		$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
		$columns       = method_exists( $wp_list_table, 'get_columns' ) ? $wp_list_table->get_columns() : [];
		// Calculate colspan for the notice message td: should span all columns except the first one (checkbox/icon).
		$notice_colspan = ! empty( $columns ) ? count( $columns ) - 1 : 2; // Default to 2 (description + auto-updates) if columns unknown.

		// Ensure colspan is at least 1.
		if ( $notice_colspan < 1 ) {
			$notice_colspan = 1;
		}

		?>
		<tr class="active <?php echo esc_attr( $name ); ?>-row">
			<th class="check-column" scope="row">
				<span class="dashicons dashicons-warning"></span>
			</th>
			<td class="column-primary" colspan="<?php echo esc_attr( $notice_colspan ); ?>">
				<?php
				// Heading.
				echo '<p><strong>' . esc_html( $this->unmet_requirements_text() ) . '</strong></p>';

				// Loop through each requirement.
				// Use $this->get_requirements() to ensure we have the latest state after check() has run.
				foreach ( $this->get_requirements() as $requirement_details ) {
					// Skip if met.
					if ( ! empty( $requirement_details['met'] ) ) {
						continue;
					}
					// Output the description for this unmet requirement.
					// The $this->unmet_requirement_description() method (singular) is responsible for one requirement.
					$this->unmet_requirement_description( $requirement_details );
				}
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Sets up the plugin-agnostic method to output specific unmet requirement information
	 *
	 * @since 1.0.0
	 *
	 * @param array $requirement Requirements array.
	 */
	private function unmet_requirement_description( $requirement = [] ) {

		// Requirement could not be found (e.g., plugin not installed).
		if ( empty( $requirement['exists'] ) ) {
			$text = sprintf(
				$this->unmet_requirements_missing_text(), // "Requires %1$s (%2$s), but it appears to be missing."
				'<strong>' . esc_html( $requirement['name'] ) . '</strong>',
				'<strong>' . esc_html( $requirement['minimum'] ) . '</strong>'
			);
			// Requirement exists, but is the special 'not-active' status.
		} elseif ( isset( $requirement['current'] ) && 'not-active' === $requirement['current'] ) {
			// translators: %1$s: Name of the required item (e.g., AffiliateWP), %2$s: Minimum required version.
			$text = sprintf(
				esc_html__( 'Requires %1$s (%2$s), but it is not active.', 'affiliate-wp' ),
				'<strong>' . esc_html( $requirement['name'] ) . '</strong>',
				'<strong>' . esc_html( $requirement['minimum'] ) . '</strong>'
			);
			// Requirement exists, is active, but version is too low.
		} else {
			$text = sprintf(
				$this->unmet_requirements_description_text(), // "Requires %1$s (%2$s), but (%3$s) is installed."
				'<strong>' . esc_html( $requirement['name'] ) . '</strong>',
				'<strong>' . esc_html( $requirement['minimum'] ) . '</strong>',
				'<strong>' . esc_html( $requirement['current'] ) . '</strong>' // This will be the actual low version number.
			);
		}

		// Output the description.
		echo '<p>' . wp_kses_post( $text ) . '</p>';
	}

	/**
	 * Sets up the plugin-agnostic method to output unmet requirements styling
	 *
	 * @since 1.0.0
	 */
	public function admin_head() {

		// Get the requirements row name.
		$name = $this->unmet_requirements_name();
		?>

		<style id="<?php echo esc_attr( $name ); ?>">
			.plugins tr[data-plugin="<?php echo esc_html( $this->base ); ?>"] th,
			.plugins tr[data-plugin="<?php echo esc_html( $this->base ); ?>"] td,
			.plugins .<?php echo esc_html( $name ); ?>-row th,
			.plugins .<?php echo esc_html( $name ); ?>-row td {
				background: #fff5f5;
			}
			.plugins tr[data-plugin="<?php echo esc_html( $this->base ); ?>"] th {
				box-shadow: none;
			}
			.plugins .<?php echo esc_html( $name ); ?>-row th span {
				margin-left: 6px;
				color: #dc3232;
			}
			.plugins tr[data-plugin="<?php echo esc_html( $this->base ); ?>"] th,
			.plugins .<?php echo esc_html( $name ); ?>-row th.check-column {
				border-left: 4px solid #dc3232 !important;
			}
			.plugins .<?php echo esc_html( $name ); ?>-row .column-description p {
				margin: 0;
				padding: 0;
			}
			.plugins .<?php echo esc_html( $name ); ?>-row .column-description p:not(:last-of-type) {
				margin-bottom: 8px;
			}
		</style>
		<?php
	}

	/**
	 * Sets up the plugin-agnostic method to add the "Requirements" link to row actions
	 *
	 * @since 1.0.0
	 *
	 * @param array $links Requirement links.
	 * @return array Requirement links with markup.
	 */
	public function plugin_row_links( $links = [] ) {

		// Add the Requirements link.
		$links['requirements'] =
			'<a href="' . esc_url( $this->unmet_requirements_url() ) . '" aria-label="' . esc_attr( $this->unmet_requirements_label() ) . '">'
			. esc_html( $this->unmet_requirements_link() )
			. '</a>';

		// Return links with Requirements link.
		return $links;
	}

	//
	// Checkers.
	//

	/**
	 * Sets up the plugin-specific requirements checker.
	 *
	 * @since 1.0.0
	 */
	private function check() {

		// Loop through requirements.
		foreach ( $this->requirements as $dependency => $properties ) {

			if ( method_exists( $this, 'check_' . $dependency ) ) {
				$version_or_status = call_user_func( [ $this, 'check_' . $dependency ] );
			} else {
				$version_or_status = false; // Default to false if no checker method.
			}

			// Merge to original array.
			if ( false === $version_or_status ) {
				// If checker returned false, it means the dependency doesn't exist or is not found.
				$this->requirements[ $dependency ] = array_merge(
					$this->requirements[ $dependency ],
					[
						'exists'  => false, // Explicitly set exists to false.
						'current' => false, // Ensure current is also false.
						'checked' => true,
						'met'     => false,
					]
				);
			} elseif ( ! empty( $version_or_status ) ) { // Can be a version string or 'not-active'.
				$this->requirements[ $dependency ] = array_merge(
					$this->requirements[ $dependency ],
					[
						// If check_affwp returned 'not-active', class_exists was true, so 'exists' is true.
						// If it's a version string, it also implies existence.
						'exists'  => true,
						'current' => $version_or_status,
						'checked' => true,
						// 'met' is false if 'not-active', otherwise compare version.
						'met'     => ( 'not-active' !== $version_or_status ) && version_compare( (string) $version_or_status, $properties['minimum'], '>=' ),
					]
				);
			}
			// If $version_or_status was an empty string (but not false) from a custom check,
			// 'exists' remains as initially set, 'current' isn't updated, 'met' becomes false.
			// This case is handled by the default 'met' => false if the condition above isn't hit.
		}
	}

	/**
	 * Checks the PHP version.
	 *
	 * @since 1.0.0
	 *
	 * @return string PHP version.
	 */
	protected function check_php() {
		return phpversion();
	}

	/**
	 * Checks the WordPress version.
	 *
	 * @since 1.0.0
	 *
	 * @return string WordPress version.
	 */
	protected function check_wp() {
		return get_bloginfo( 'version' );
	}

	/**
	 * Checks the AffiliateWP version.
	 *
	 * Add-ons can use this built-in check for the AffiliateWP version by defining
	 * an 'affwp' requirement via the `$addon_requirements` property.
	 *
	 * @since 1.0.0
	 *
	 * @return string|false AffiliateWP version, 'not-active', or false if class doesn't exist.
	 */
	protected function check_affwp() {
		if ( ! class_exists( 'Affiliate_WP' ) ) {
			// If class doesn't exist, it implies it's not installed or files are missing.
			return false;
		}

		// Class exists, so plugin files are there. Now check if it's active by version.
		$version = get_option( 'affwp_version' );

		if ( empty( $version ) ) {
			// Plugin is likely installed (class exists) but not fully active or version not set.
			return 'not-active'; // Special string to indicate inactive state.
		}

		return $version;
	}

	/**
	 * Determines if all requirements been met.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if met, otherwise false.
	 */
	public function met() {

		// Run the check.
		$this->check();

		// Default to true (any false below wins).
		$retval  = true;
		$to_meet = wp_list_pluck( $this->requirements, 'met' );

		// Look for unmet dependencies, and exit if so.
		foreach ( $to_meet as $met ) {
			if ( empty( $met ) ) {
				$retval = false;
				continue;
			}
		}

		// Return.
		return $retval;
	}

	//
	// Translations.
	//

	/**
	 * Handles loading the plugin-specific text-domain.
	 *
	 * Looks first for a global mo file, then one located in the plugin's languages
	 * directory, and if neither of those options are available, the core WordPress
	 * translations path hierarchy is used:
	 *
	 * - Global: wp-content/languages/plugins/{slug}-{locale}.mo
	 * - Local: wp-content/plugins/{plugin_dir}/languages/{slug}-{locale}.mo
	 * - WordPress: See load_plugin_textdomain()
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Refactored for global mo-file handling adjustments
	 *
	 * @return void
	 */
	public function load_textdomain() {
		$plugin_dir = dirname( plugin_basename( $this->get_file() ) );

		// Set filter for plugin's languages directory.
		$lang_dir = $plugin_dir . '/languages';

		/**
		 * Filters the languages directory for AffiliateWP - Affiliate Portal plugin.
		 *
		 * @since 1.0
		 *
		 * @param string $lang_dir Language directory. Includes a trailing slash for back-compat.
		 */
		$lang_dir = apply_filters( $this->base . '_languages_directory', trailingslashit( $lang_dir ) );

		// Traditional WordPress plugin locale filter.
		$locale = apply_filters( 'plugin_locale', get_locale(), $this->slug );
		$mofile = sprintf( '%1$s-%2$s.mo', $this->slug, $locale );

		//
		// Setup and check paths to current locale file.
		//

		$mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;
		$mofile_local  = WP_PLUGIN_DIR . '/' . $lang_dir . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Load wp-content/languages/{slug}/{slug}-{locale}.mo.
			load_textdomain( $this->slug, $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Load wp-content/plugins/{plugin_dir}/languages/{slug}-{locale}.mo.
			load_textdomain( $this->slug, $mofile_local );
		} else {
			// Load the default language files.
			load_plugin_textdomain( $this->slug, false, $lang_dir );
		}
	}
}
