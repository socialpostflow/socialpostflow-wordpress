<?php
/**
 * Provides common functionality, styling and views for Plugins.
 *
 * Historically named dashboard, as it used to provide a widget
 * on the WordPress Admin Dashboard.
 *
 * @package Social_Post_Flow
 * @author Social Post Flow
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
class Social_Post_Flow_Dashboard {

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
	 * Adds the Social Post Flow CSS class to the <body> tag when we're in the WordPress Admin interface
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

}
