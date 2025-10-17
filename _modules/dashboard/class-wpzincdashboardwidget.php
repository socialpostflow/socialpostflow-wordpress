<?php
/**
 * Provides common functionality, styling and views for Plugins.
 *
 * Historically named dashboard, as it used to provide a widget
 * on the WordPress Admin Dashboard.
 *
 * @package WPZincDashboardWidget
 * @author WP Zinc
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides common functionality, styling and views for Plugins.
 *
 * Historically named dashboard, as it used to provide a widget
 * on the WordPress Admin Dashboard.
 */
class WPZincDashboardWidget {

	/**
	 * Holds the plugin object
	 *
	 * @since   1.0.0
	 *
	 * @var     object
	 */
	public $plugin;

	/**
	 * Holds the exact path to this file's folder
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	public $dashboard_folder;

	/**
	 * Holds the exact URL to this file's folder
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	public $dashboard_url;

	/**
	 * Flag to show the Import and Export Sub Menu
	 *
	 * @since   1.0.0
	 *
	 * @var     bool
	 */
	private $show_import_export_menu = true;

	/**
	 * Flag to show the Upgrade Sub Menu
	 *
	 * @since   1.0.0
	 *
	 * @var     bool
	 */
	private $show_upgrade_menu = true;

	/**
	 * Flag to show the Support Sub Menu
	 *
	 * @since   1.0.0
	 *
	 * @var     bool
	 */
	private $show_support_menu = false;

	/**
	 * Flag to show the Review Request
	 *
	 * @since   1.0.0
	 *
	 * @var     bool
	 */
	private $show_review_request = true;

	/**
	 * Holds the message to display when importing or exporting a configuration file.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	private $message = '';

	/**
	 * Holds the error message to display when importing or exporting a configuration file.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	private $error_message = '';

	/**
	 * Constructor
	 *
	 * @since   1.0.0
	 *
	 * @param   object $plugin    WordPress Plugin.
	 */
	public function __construct( $plugin ) {

		// Plugin Details.
		$this->plugin = $plugin;

		// Set class vars.
		$this->dashboard_folder = plugin_dir_path( __FILE__ );
		$this->dashboard_url    = plugin_dir_url( __FILE__ );

		// Admin CSS, JS and Menu.
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts_css' ) );
		add_action( str_replace( '-', '_', $this->plugin->name ) . '_admin_menu_import_export', array( $this, 'register_import_export_menu' ), 99 );
		add_action( str_replace( '-', '_', $this->plugin->name ) . '_admin_menu_support', array( $this, 'register_support_menu' ), 99 );
		add_action( str_replace( '-', '_', $this->plugin->name ) . '_admin_menu', array( $this, 'admin_menu' ), 99 );

		// Plugin Actions.
		if ( ! isset( $this->plugin->hide_upgrade_menu ) || ! $this->plugin->hide_upgrade_menu ) {
			add_filter( 'plugin_action_links_' . $this->plugin->name . '/' . $this->plugin->name . '.php', array( $this, 'add_action_link' ), 10, 1 );
		}

		// Reviews.
		if ( $this->plugin->review_name !== false ) {
			add_action( 'wp_ajax_' . str_replace( '-', '_', $this->plugin->name ) . '_dismiss_review', array( $this, 'dismiss_review' ) );
			add_action( 'admin_notices', array( $this, 'maybe_display_review_request' ) );
			add_filter( 'admin_footer_text', array( $this, 'maybe_display_footer_review_request' ) );
		}

		// Export and Redirects.
		add_action( 'init', array( $this, 'export' ), 9999 );
		add_action( 'init', array( $this, 'maybe_redirect' ), 2 );

		// Permit wpzinc.com to be redirected to when using wp_safe_redirect().
		add_filter( 'allowed_redirect_hosts', array( $this, 'allowed_redirect_hosts' ) );

	}

	/**
	 * Helper function to determine whether to load minified Javascript
	 * files or not.
	 *
	 * @since   1.0.0
	 *
	 * @return  bool    Load Minified JS
	 */
	public function should_load_minified_js() {

		// If script debugging is enabled, don't load minified JS.
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			return false;
		}

		// If we can't determine a Plugin's activation state, minify JS.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			return true;
		}

		// If a known third party Plugin exists that minifies JS, don't load minified JS
		// as double minification seems to break things.
		$minification_plugins = array(
			'wp-rocket/wp-rocket.php',
		);

		/**
		 * Defines an array of third party minification Plugins that, if activate, will result
		 * in this Plugin's JS non-minified files being loaded.
		 *
		 * This allows said third party minification Plugins to minify JS, avoiding double minification
		 * which can result in errors.
		 *
		 * If SCRIPT_DEBUG is enabled, non minified JS will always be loaded, regardless of whether
		 * a minification Plugin is active.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $minification_plugins   Plugin Folder and Filename Paths e.g. wp-rocket/wp-rocket.php
		 */
		$minification_plugins = apply_filters( 'wpzinc_dashboard_should_load_minified_js_plugins', $minification_plugins );

		// If no minification Plugins, load minified JS.
		if ( ! is_array( $minification_plugins ) || ! count( $minification_plugins ) ) {
			return true;
		}

		// Check if any minification Plugin is active.
		foreach ( $minification_plugins as $plugin_folder_filename ) {
			if ( is_plugin_active( $plugin_folder_filename ) ) {
				// A known minification Plugin is active.
				// Don't minify JS, as the third party Plugin will do this.
				return false;
			}
		}

		// If here, OK to load minified JS.
		return true;

	}

	/**
	 * Shows the Import & Export Submenu in the Plugin's Menu
	 *
	 * @since   1.0.0
	 */
	public function show_import_export_menu() {

		$this->show_import_export_menu = true;

	}

	/**
	 * Hides the Import & Export Submenu in the Plugin's Menu
	 *
	 * @since   1.0.0
	 */
	public function hide_import_export_menu() {

		$this->show_import_export_menu = false;

	}

	/**
	 * Shows the Support Submenu in the Plugin's Menu
	 *
	 * @since   1.0.0
	 */
	public function show_support_menu() {

		$this->show_support_menu = true;

	}

	/**
	 * Hides the Support Submenu in the Plugin's Menu
	 *
	 * @since   1.0.0
	 */
	public function hide_support_menu() {

		$this->show_support_menu = false;

	}

	/**
	 * Shows the Upgrade Submenu in the Plugin's Menu
	 *
	 * @since   1.0.0
	 */
	public function show_upgrade_menu() {

		$this->show_upgrade_menu = true;

	}

	/**
	 * Hides the Upgrade Submenu in the Plugin's Menu
	 *
	 * @since   1.0.0
	 */
	public function hide_upgrade_menu() {

		$this->show_upgrade_menu = false;

	}

	/**
	 * Disables the Review Request Notification, regardless of whether
	 * it has been set by the Plugin calling request_review()
	 *
	 * @since   1.0.0
	 */
	public function disable_review_request() {

		$this->show_review_request = false;

	}

	/**
	 * Adds the WP Zinc CSS class to the <body> tag when we're in the WordPress Admin interface
	 * and viewing a Plugin Screen
	 *
	 * This allows us to then override some WordPress layout styling on e.g. #wpcontent, without
	 * affecting other screens, Plugins etc.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $classes    CSS Classes.
	 * @return  string               CSS Classes
	 */
	public function admin_body_class( $classes ) {

		// Define a list of strings that determine whether we're viewing a Plugin Screen.
		$screens = array(
			$this->plugin->name,
		);

		/**
		 * Filter the body classes to output on the <body> tag.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $screens        Screens.
		 * @param   array   $classes        Classes.
		 */
		$screens = apply_filters( 'wpzinc_admin_body_class', $screens, $classes );

		// Determine whether we're on a Plugin Screen.
		$is_plugin_screen = $this->is_plugin_screen( $screens );

		// Bail if we're not a Plugin screen.
		if ( ! $is_plugin_screen ) {
			return $classes;
		}

		// Add the wpzinc class and plugin name.
		$classes .= ' wpzinc ' . $this->plugin->name;

		// Return.
		return trim( $classes );

	}

	/**
	 * Determines whether we're viewing this Plugin's screen in the WordPress Administration
	 * interface.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $screens    Screens.
	 * @return  bool                Is Plugin Screen
	 */
	private function is_plugin_screen( $screens ) {

		// Bail if the current screen can't be obtained.
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		// Bail if no screen names were specified to search for.
		if ( empty( $screens ) || count( $screens ) === 0 ) {
			return false;
		}

		// Get screen.
		$screen = get_current_screen();

		foreach ( $screens as $screen_name ) {
			if ( strpos( $screen->id, $screen_name ) === false ) {
				continue;
			}

			// We're on a Plugin Screen.
			return true;
		}

		// If here, we're not on a Plugin Screen.
		return false;

	}

	/**
	 * Register JS scripts, which Plugins may optionally load via wp_enqueue_script()
	 * Enqueues CSS
	 *
	 * @since   1.0.0
	 */
	public function admin_scripts_css() {

		// Determine whether to load minified versions of JS.
		$minified = $this->should_load_minified_js();

		// JS.
		wp_register_script( 'wpzinc-admin-autocomplete-gutenberg', $this->dashboard_url . 'js/' . ( $minified ? 'min/' : '' ) . 'autocomplete-gutenberg' . ( $minified ? '-min' : '' ) . '.js', false, $this->plugin->version, true );
		wp_register_script( 'wpzinc-admin-autocomplete', $this->dashboard_url . 'js/' . ( $minified ? 'min/' : '' ) . 'autocomplete' . ( $minified ? '-min' : '' ) . '.js', array( 'wpzinc-admin-tribute' ), $this->plugin->version, true );
		wp_register_script( 'wpzinc-admin-autosize', $this->dashboard_url . 'js/' . ( $minified ? 'min/' : '' ) . 'autosize' . ( $minified ? '-min' : '' ) . '.js', false, $this->plugin->version, true );
		wp_register_script( 'wpzinc-admin-conditional', $this->dashboard_url . 'js/' . ( $minified ? 'min/' : '' ) . 'jquery.form-conditionals' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->plugin->version, true );
		wp_register_script( 'wpzinc-admin-inline-search', $this->dashboard_url . 'js/' . ( $minified ? 'min/' : '' ) . 'inline-search' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->plugin->version, true );
		wp_register_script( 'wpzinc-admin-media-library', $this->dashboard_url . 'js/' . ( $minified ? 'min/' : '' ) . 'media-library' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery', 'jquery-ui-sortable' ), $this->plugin->version, true );
		wp_register_script( 'wpzinc-admin-modal', $this->dashboard_url . 'js/' . ( $minified ? 'min/' : '' ) . 'modal' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->plugin->version, true );
		wp_register_script( 'wpzinc-admin-notification', $this->dashboard_url . 'js/' . ( $minified ? 'min/' : '' ) . 'notification' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->plugin->version, true );
		wp_register_script( 'wpzinc-admin-review-notice', $this->dashboard_url . 'js/' . ( $minified ? 'min/' : '' ) . 'review-notice' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->plugin->version, true );
		wp_register_script( 'wpzinc-admin-selectize', $this->dashboard_url . 'js/' . ( $minified ? 'min/' : '' ) . 'selectize' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->plugin->version, true );
		wp_register_script( 'wpzinc-admin-synchronous-ajax', $this->dashboard_url . 'js/' . ( $minified ? 'min/' : '' ) . 'synchronous-ajax' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->plugin->version, true );
		wp_register_script( 'wpzinc-admin-tables', $this->dashboard_url . 'js/' . ( $minified ? 'min/' : '' ) . 'tables' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->plugin->version, true );
		wp_register_script( 'wpzinc-admin-tabs', $this->dashboard_url . 'js/' . ( $minified ? 'min/' : '' ) . 'tabs' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->plugin->version, true );
		wp_register_script( 'wpzinc-admin-tags', $this->dashboard_url . 'js/' . ( $minified ? 'min/' : '' ) . 'tags' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->plugin->version, true );
		wp_register_script( 'wpzinc-admin-tinymce-modal', $this->dashboard_url . 'js/' . ( $minified ? 'min/' : '' ) . 'tinymce-modal' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->plugin->version, true );
		wp_register_script( 'wpzinc-admin-toggle', $this->dashboard_url . 'js/' . ( $minified ? 'min/' : '' ) . 'toggle' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->plugin->version, true );
		wp_register_script( 'wpzinc-admin-tribute', $this->dashboard_url . 'js/' . ( $minified ? 'min/' : '' ) . 'tribute' . ( $minified ? '-min' : '' ) . '.js', false, $this->plugin->version, true );
		wp_register_script( 'wpzinc-admin', $this->dashboard_url . 'js/' . ( $minified ? 'min/' : '' ) . 'admin' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->plugin->version, true );

		// CSS.
		wp_register_style( 'wpzinc-admin-selectize', $this->dashboard_url . 'css/selectize.css', false, $this->plugin->version );
		wp_enqueue_style( 'wpzinc-admin', $this->dashboard_url . 'css/admin.css', false, $this->plugin->version );

		// Depending on the screen we're on, maybe enqueue specific scripts now.
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		// Bail if we couldn't get the current screen.
		$screen = get_current_screen();
		if ( is_null( $screen ) ) {
			return;
		}

		switch ( $screen->id ) {

			/**
			 * Import / Export
			 * - Use of displayName is deliberate.
			 */
			case sanitize_title( $this->plugin->displayName ) . '_page_' . $this->plugin->name . '-import-export':
				wp_enqueue_script( 'wpzinc-admin-tabs' );
				wp_enqueue_script( 'wpzinc-admin-toggle' );
				break;

		}

	}

	/**
	 * Registers the Import / Export Menu Link in the WordPress Administration interface
	 *
	 * @since   1.0.0
	 *
	 * @param   string $parent_slug   Parent Slug.
	 */
	public function register_import_export_menu( $parent_slug = '' ) {

		// Bail if the Import & Export Menu is hidden.
		if ( ! $this->show_import_export_menu ) {
			return;
		}

		// If a parent slug is defined, attach the submenu items to that.
		// Otherwise use the plugin's name.
		$slug = ( ! empty( $parent_slug ) ? $parent_slug : $this->plugin->name );

		/**
		 * Filter the minimum capability for accessing Import and Export Sub Menu.
		 *
		 * @since   1.0.0
		 *
		 * @param   string  $minimum_capability   Minimum Capability.
		 */
		$minimum_capability = apply_filters( str_replace( '-', '_', $this->plugin->name ) . '_admin_admin_menu_minimum_capability', 'manage_options' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName

		add_submenu_page( $slug, __( 'Import & Export', $this->plugin->name ), __( 'Import & Export', $this->plugin->name ), $minimum_capability, $this->plugin->name . '-import-export', array( $this, 'import_export_screen' ) ); // phpcs:ignore WordPress.WP.I18n

	}

	/**
	 * Registers the Support Menu Link in the WordPress Administration interface
	 *
	 * @since   1.0.0
	 *
	 * @param   string $parent_slug   Parent Slug.
	 */
	public function register_support_menu( $parent_slug = '' ) {

		// Bail if the Support Menu is hidden.
		if ( ! $this->show_support_menu ) {
			return;
		}

		// If a parent slug is defined, attach the submenu items to that.
		// Otherwise use the plugin's name.
		$slug = ( ! empty( $parent_slug ) ? $parent_slug : $this->plugin->name );

		/**
		 * Filter the minimum capability for accessing Support Sub Menu.
		 *
		 * @since   1.0.0
		 *
		 * @param   string  $minimum_capability   Minimum Capability.
		 */
		$minimum_capability = apply_filters( str_replace( '-', '_', $this->plugin->name ) . '_admin_admin_menu_minimum_capability', 'manage_options' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName

		add_submenu_page( $slug, __( 'Support', $this->plugin->name ), __( 'Support', $this->plugin->name ), $minimum_capability, $this->plugin->name . '-support', array( $this, 'support_screen' ) ); // phpcs:ignore WordPress.WP.I18n

	}

	/**
	 * Registers the Upgrade Menu Link in the WordPress Administration interface
	 *
	 * @since   1.0.0
	 *
	 * @param   string $parent_slug   Parent Slug.
	 */
	public function register_upgrade_menu( $parent_slug = '' ) {

		// Bail if the Upgrade Menu is hidden.
		if ( ! $this->show_upgrade_menu ) {
			return;
		}

		// If a parent slug is defined, attach the submenu items to that.
		// Otherwise use the plugin's name.
		$slug = ( ! empty( $parent_slug ) ? $parent_slug : $this->plugin->name );

		/**
		 * Filter the minimum capability for accessing Upgrade Sub Menu.
		 *
		 * @since   1.0.0
		 *
		 * @param   string  $minimum_capability   Minimum Capability.
		 */
		$minimum_capability = apply_filters( str_replace( '-', '_', $this->plugin->name ) . '_admin_admin_menu_minimum_capability', 'manage_options' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName

		add_submenu_page( $slug, __( 'Upgrade', $this->plugin->name ), __( 'Upgrade', $this->plugin->name ), $minimum_capability, $this->plugin->name . '-upgrade', array( $this, 'upgrade_screen' ) ); // phpcs:ignore WordPress.WP.I18n

	}

	/**
	 * Registers the Import / Export, Support and Upgrade Menu Links in the WordPress Administration interface.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $parent_slug   Parent Slug.
	 */
	public function admin_menu( $parent_slug = '' ) {

		// Only register Import/Export Menu if enabled.
		if ( $this->show_import_export_menu ) {
			$this->register_import_export_menu( $parent_slug );
		}

		// Only register Support Menu if enabled.
		if ( $this->show_support_menu ) {
			$this->register_support_menu( $parent_slug );
		}

		// Only register Upgrade Menu if enabled.
		if ( $this->show_upgrade_menu ) {
			$this->register_upgrade_menu( $parent_slug );
		}

	}

	/**
	 * Adds Plugin Action Links to the Plugin when activated in the Plugins Screen.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $links  Action Links.
	 * @return  array           Action Links
	 */
	public function add_action_link( $links ) {

		// Bail if the licensing class exists,as this means we're on a Pro version.
		if ( class_exists( 'LicensingUpdateManager' ) ) {
			return $links;
		}

		// Add Links.
		if ( $this->get_upgrade_url( 'plugins' ) ) {
			$links[] = '<a href="' . esc_attr( $this->get_upgrade_url( 'plugins' ) ) . '" rel="noopener" target="_blank">' . __( 'Upgrade', $this->plugin->name ) . '</a>'; //phpcs:ignore WordPress.WP.I18n
		}

		/**
		 * Filter the action links.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $links          Action Links.
		 * @param   string  $plugin_name    Plugin Name.
		 * @param   object  $plugin         Plugin.
		 */
		$links = apply_filters( 'wpzinc_dashboard_add_action_link', $links, $this->plugin->name, $this->plugin );

		// Return.
		return $links;

	}

	/**
	 * Displays a dismissible WordPress Administration notice requesting a review, if requested
	 * by the main Plugin and the Review Request hasn't been disabled.
	 *
	 * @since   1.0.0
	 */
	public function maybe_display_review_request() {

		// If the review request is disabled, bail.
		if ( ! $this->show_review_request ) {
			return;
		}

		// If we're not an Admin user, bail.
		if ( ! function_exists( 'current_user_can' ) ) {
			return;
		}
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// If the review request was dismissed by the user, bail.
		if ( $this->dismissed_review() ) {
			return;
		}

		// If no review request has been set by the plugin, bail.
		if ( ! $this->requested_review() ) {
			return;
		}

		// Enqueue JS.
		wp_enqueue_script( 'wpzinc-admin-review-notice' );
		wp_localize_script(
			'wpzinc-admin-review-notice',
			'wpzinc_admin_review_notice',
			array(
				'plugin_name' => $this->plugin->name,
				'action'      => esc_attr( str_replace( '-', '_', $this->plugin->name ) ) . '_dismiss_review',
				'nonce'       => wp_create_nonce( 'wpzinc_admin_review_notice_dismiss_review' ),
			)
		);

		// If here, display the request for a review.
		include_once $this->dashboard_folder . '/views/review-notice.php';

	}

	/**
	 * Displays a message in the WordPress Administration footer requesting a review if
	 * the Review Request hasn't been disabled.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $text   Message.
	 */
	public function maybe_display_footer_review_request( $text ) {

		// If the review request is disabled, bail.
		if ( ! $this->show_review_request ) {
			return $text;
		}

		// Bail if we're not on a Plugin screen.
		if ( ! filter_has_var( INPUT_GET, 'page' ) ) {
			return $text;
		}
		$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( strpos( $page, $this->plugin->name ) === false ) {
			return $text;
		}

		// Return review request text.
		return sprintf(
			/* translators: %1$s: Plugin Name, %2$s: Five Star Link to Review URL, %3$s: Link to Review URL, %4$s: Plugin Name */
			__( 'Please rate %1$s %2$s on %3$s to help us grow %4$s. Thanks!', $this->plugin->name ), // phpcs:ignore WordPress.WP.I18n
			'<strong>' . $this->plugin->displayName . '</strong>',
			'<a href="' . $this->get_review_url() . '" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>',
			'<a href="' . $this->get_review_url() . '" target="_blank">WordPress.org</a>',
			$this->plugin->displayName
		);

	}

	/**
	 * Flag to indicate whether a review has been requested.
	 *
	 * @since   1.0.0
	 *
	 * @return  bool    Review Requested
	 */
	public function requested_review() {

		// If the review request is disabled, bail.
		if ( ! $this->show_review_request ) {
			return false;
		}

		$time = get_option( $this->plugin->review_name . '-review-request' );
		if ( empty( $time ) ) {
			return false;
		}

		// Check the current date and time matches or is later than the above value.
		$now = time();
		if ( $now >= ( $time + ( 3 * DAY_IN_SECONDS ) ) ) {
			return true;
		}

		// We're not yet ready to show this review.
		return false;

	}

	/**
	 * Requests a review notification, which is displayed on subsequent page loads.
	 *
	 * @since   1.0.0
	 */
	public function request_review() {

		// If the review request is disabled, bail.
		if ( ! $this->show_review_request ) {
			return;
		}

		// If a review has already been requested, bail.
		$time = get_option( $this->plugin->review_name . '-review-request' );
		if ( ! empty( $time ) ) {
			return;
		}

		// Request a review, setting the value to the date and time now.
		update_option( $this->plugin->review_name . '-review-request', time() );

	}

	/**
	 * Flag to indicate whether a review request has been dismissed by the user.
	 *
	 * @since   1.0.0
	 *
	 * @return  bool    Review Dismissed.
	 */
	public function dismissed_review() {

		return get_option( $this->plugin->review_name . '-review-dismissed' );

	}

	/**
	 * Dismisses the review notification, so it isn't displayed again.
	 *
	 * @since   1.0.0
	 */
	public function dismiss_review() {

		// Check nonce.
		check_ajax_referer( 'wpzinc_admin_review_notice_dismiss_review', 'nonce' );

		// Mark review as dismissed.
		update_option( $this->plugin->review_name . '-review-dismissed', 1 );

		// Send success response.
		wp_send_json_success( 1 );

	}

	/**
	 * Returns the Review URL for this Plugin.
	 *
	 * @since   1.0.0
	 *
	 * @return  string  Review URL
	 */
	public function get_review_url() {

		$url = 'https://wordpress.org/support/plugin/' . $this->plugin->review_name . '/reviews/?filter=5#new-post';

		return $url;

	}

	/**
	 * Returns the Upgrade URL for this Plugin.
	 *
	 * Adds Google Analytics UTM tracking, and optional coupon flag.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $utm_content    UTM Content Value.
	 * @return  mixed                   false | Upgrade URL
	 */
	public function get_upgrade_url( $utm_content = '' ) {

		// Bail if no Upgrade URL specified by the Plugin.
		if ( ! isset( $this->plugin->upgrade_url ) ) {
			return false;
		}

		// Build URL.
		$url = $this->plugin->upgrade_url . '?utm_source=' . $this->plugin->name . '&utm_medium=link&utm_content=' . $utm_content . '&utm_campaign=general';

		// Return.
		return $url;

	}

	/**
	 * Import / Export Screen.
	 *
	 * @since   1.0.0
	 */
	public function import_export_screen() {

		// Check nonce.
		if ( isset( $_POST[ $this->plugin->name . '_nonce' ] ) && wp_verify_nonce( sanitize_key( $_POST[ $this->plugin->name . '_nonce' ] ), $this->plugin->name ) ) {
			// Import if requested.
			if ( isset( $_POST['import'] ) ) {
				// Import JSON.
				$this->import();
			}
		}

		/**
		 * Filter the import sources.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $import_sources   Import Sources.
		 */
		$import_sources = apply_filters( str_replace( '-', '_', $this->plugin->name ) . '_import_sources', array() ); // phpcs:ignore WordPress.NamingConventions.ValidHookName

		// Output view.
		include_once $this->dashboard_folder . '/views/import-export.php';

	}

	/**
	 * Import JSON file upload that confirms to our standards.
	 *
	 * @since   1.0.0
	 */
	private function import() {

		// Check nonce.
		if ( ! isset( $_POST[ $this->plugin->name . '_nonce' ] ) ) {
			// Missing nonce.
			return;
		}

		if ( ! wp_verify_nonce( sanitize_key( $_POST[ $this->plugin->name . '_nonce' ] ), $this->plugin->name ) ) {
			// Invalid nonce.
			return;
		}

		if ( ! is_array( $_FILES ) ) {
			$this->error_message = __( 'No file was uploaded', $this->plugin->name ); // phpcs:ignore WordPress.WP.I18n
			return;
		}
		if ( ! isset( $_FILES['import']['type'] ) || ! isset( $_FILES['import']['tmp_name'] ) || ! isset( $_FILES['import']['size'] ) ) {
			$this->error_message = __( 'Could not determine file type', $this->plugin->name ); // phpcs:ignore WordPress.WP.I18n
			return;
		}

		if ( isset( $_FILES['import']['error'] ) && $_FILES['import']['error'] !== 0 ) {
			$this->error_message = __( 'Error when uploading file.', $this->plugin->name ); // phpcs:ignore WordPress.WP.I18n
			return;
		}

		// Determine if the file is JSON or ZIP.
		switch ( $_FILES['import']['type'] ) {
			/**
			 * ZIP File
			 */
			case 'application/zip':
				// Open ZIP file.
				$zip = new ZipArchive();
				if ( $zip->open( sanitize_text_field( wp_unslash( $_FILES['import']['tmp_name'] ) ) ) !== true ) {
					$this->error_message = __( 'Could not extract the supplied ZIP file.', $this->plugin->name ); // phpcs:ignore WordPress.WP.I18n
					return;
				}

				// Extract and close.
				$zip->extractTo( sys_get_temp_dir() );
				$zip->close();

				// Read JSON file.
				// phpcs:disable WordPress.WP.AlternativeFunctions
				$handle = fopen( sys_get_temp_dir() . '/export.json', 'r' );
				$json   = fread( $handle, filesize( sys_get_temp_dir() . '/export.json' ) );
				fclose( $handle );
				// phpcs:enable
				break;

			default:
				// Read file.
				// phpcs:disable WordPress.WP.AlternativeFunctions
				$handle = fopen( sanitize_text_field( wp_unslash( $_FILES['import']['tmp_name'] ) ), 'r' );
				$json   = fread( $handle, sanitize_text_field( wp_unslash( $_FILES['import']['size'] ) ) );
				fclose( $handle );
				// phpcs:enable

		}

		// Remove UTF8 BOM chars.
		$bom  = pack( 'H*', 'EFBBBF' );
		$json = preg_replace( "/^$bom/", '', $json );

		// Decode.
		$import = json_decode( $json, true );

		// Check data is an array.
		if ( ! is_array( $import ) ) {
			$this->error_message = __( 'Supplied file is not a valid JSON settings file, or has become corrupt.', $this->plugin->name ); // phpcs:ignore WordPress.WP.I18n
			return;
		}

		// Allow Plugin to run its Import Routine using the supplied data now.
		$result = true;
		$result = apply_filters( str_replace( '-', '_', $this->plugin->name ) . '_import', $result, $import );  // phpcs:ignore WordPress.NamingConventions.ValidHookName

		// Bail if an error occured.
		if ( is_wp_error( $result ) ) {
			$this->error_message = $result->get_error_message();
			return;
		}

		$this->message = __( 'Settings imported.', $this->plugin->name ); // phpcs:ignore WordPress.WP.I18n

	}

	/**
	 * Support Screen
	 *
	 * @since   1.0.0
	 */
	public function support_screen() {
		// We never reach here, as we redirect earlier in the process.
	}

	/**
	 * Upgrade Screen
	 *
	 * @since   1.0.0
	 */
	public function upgrade_screen() {
		// We never reach here, as we redirect earlier in the process.
	}

	/**
	 * If we have requested the export JSON, force a file download
	 *
	 * @since   1.0.0
	 */
	public function export() {

		// Check nonce.
		if ( ! isset( $_POST[ $this->plugin->name . '_nonce' ] ) ) {
			// Missing nonce.
			return;
		}

		if ( ! wp_verify_nonce( sanitize_key( $_POST[ $this->plugin->name . '_nonce' ] ), $this->plugin->name ) ) {
			// Invalid nonce.
			return;
		}

		// Bail if no POST data.
		if ( empty( $_POST ) ) {
			return;
		}

		// Bail if not exporting.
		if ( ! isset( $_POST['export'] ) ) {
			return;
		}

		// Bail if no format specified.
		if ( ! isset( $_POST['format'] ) ) {
			return;
		}

		// Define the array to hold the export data.
		$data = array();

		/**
		 * Defines the data to include in the export file. Plugins hook into this
		 * to define the data.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $data   Data.
		 * @param   array   $_POST  POST Data.
		 */
		$data = apply_filters( str_replace( '-', '_', $this->plugin->name ) . '_export', $data, $_POST );

		// Force a file download, depending on the export format.
		switch ( sanitize_text_field( wp_unslash( $_POST['format'] ) ) ) {
			/**
			 * JSON, Zipped.
			 */
			case 'zip':
				$this->force_zip_file_download(
					wp_json_encode(
						array(
							'data' => $data,
						)
					)
				);
				break;

			/**
			 * JSON.
			 */
			case 'json':
			default:
				$this->force_json_file_download(
					wp_json_encode(
						array(
							'data' => $data,
						)
					)
				);
				break;
		}

	}

	/**
	 * Force a browser download comprising of the given file, zipped.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $json       JSON string.
	 * @param   string $filename   Filename to Export Data to, excluding extension (default: export).
	 */
	public function force_zip_file_download( $json, $filename = 'export' ) {

		// Create new ZIP file.
		$zip      = new ZipArchive();
		$filename = $filename . '.zip';

		// Bail if ZIP file couldn't be created.
		if ( $zip->open( $filename, ZipArchive::CREATE ) !== true ) {
			return;
		}

		// Add JSON data to export.json and close.
		$zip->addFromString( $filename . '.json', $json );
		$zip->close();

		// Initialize WP_Filesystem.
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		// Read file contents.
		$zip_contents = $wp_filesystem->get_contents( $filename );

		// Output ZIP data, prompting the browser to auto download as a ZIP file now.
		header( 'Content-type: application/zip' );
		header( 'Content-Disposition: attachment; filename=' . $filename . '.zip' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		echo $zip_contents; // phpcs:ignore WordPress.Security.EscapeOutput

		// Delete the temporary file.
		$wp_filesystem->delete( $filename );
		exit();

	}

	/**
	 * Force a browser download comprising of the given JSON data
	 *
	 * @since   1.0.0
	 *
	 * @param   string $json       JSON Data for file.
	 * @param   string $filename   Filename to Export Data to, excluding extension (default: export).
	 */
	public function force_json_file_download( $json, $filename = 'export' ) {

		// Output JSON data, prompting the browser to auto download as a JSON file now.
		header( 'Content-type: application/x-msdownload' );
		header( 'Content-Disposition: attachment; filename=' . $filename . '.json' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput
		exit();

	}

	/**
	 * Force a browser download comprising of the given CSV data
	 *
	 * @since   1.0.0
	 *
	 * @param   string $csv        CSV Data for file.
	 * @param   string $filename   Filename to Export Data to, excluding extension (default: export).
	 */
	public function force_csv_file_download( $csv, $filename = 'export' ) {

		// Output JSON data, prompting the browser to auto download as a JSON file now.
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename . '.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput
		exit();

	}

	/**
	 * If the Support or Upgrade menu item was clicked, redirect.
	 *
	 * @since 3.0
	 */
	public function maybe_redirect() {

		// Check we requested the support page.
		if ( ! filter_has_var( INPUT_GET, 'page' ) ) {
			return;
		}

		// Sanitize page.
		$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		// Redirect to Support.
		if ( $page === $this->plugin->name . '-support' ) {
			wp_safe_redirect( $this->plugin->support_url );
			die();
		}

		// Redirect to Upgrade.
		if ( $page === $this->plugin->name . '-upgrade' ) {
			wp_safe_redirect( $this->get_upgrade_url( 'menu' ) );
			die();
		}

	}

	/**
	 * Permit wpzinc.com to be redirected to when using wp_safe_redirect().
	 *
	 * @since   1.0.0
	 *
	 * @param   array $hosts   Hosts.
	 * @return  array            Hosts.
	 */
	public function allowed_redirect_hosts( $hosts ) {

		$hosts[] = 'www.wpzinc.com';
		$hosts[] = 'www.socialpostflow.com';
		$hosts[] = 'app.socialpostflow.com';

		return $hosts;

	}

}
