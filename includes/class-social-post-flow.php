<?php
/**
 * Social Post Flow class.
 *
 * @package Social_Post_Flow
 * @author Social Post Flow
 */

/**
 * Main Social Post Flow class, used to load the Plugin.
 *
 * @package   Social_Post_Flow
 * @author    Social Post Flow
 * @version   1.0.0
 */
class Social_Post_Flow {

	/**
	 * Holds the class object.
	 *
	 * @since   1.0.0
	 *
	 * @var     object
	 */
	public static $instance;

	/**
	 * Plugin
	 *
	 * @since   1.0.0
	 *
	 * @var     object
	 */
	public $plugin = '';

	/**
	 * Dashboard
	 *
	 * @since   1.0.0
	 *
	 * @var     object
	 */
	public $dashboard = '';

	/**
	 * Classes
	 *
	 * @since   1.0.0
	 *
	 * @var     array
	 */
	public $classes = '';

	/**
	 * Constructor. Acts as a bootstrap to load the rest of the plugin
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

		// Plugin Details.
		$this->plugin                    = new stdClass();
		$this->plugin->name              = 'social-post-flow';
		$this->plugin->displayName       = 'Social Post Flow';
		$this->plugin->description       = 'Send WordPress Pages, Posts or Custom Post Types to your Social Post Flow account for scheduled publishing to social networks.';
		$this->plugin->author_name       = 'Social Post Flow';
		$this->plugin->account           = 'Social Post Flow';
		$this->plugin->version           = SOCIAL_POST_FLOW_PLUGIN_VERSION;
		$this->plugin->buildDate         = SOCIAL_POST_FLOW_PLUGIN_BUILD_DATE;
		$this->plugin->folder            = SOCIAL_POST_FLOW_PLUGIN_PATH;
		$this->plugin->url               = SOCIAL_POST_FLOW_PLUGIN_URL;
		$this->plugin->documentation_url = 'https://www.socialpostflow.com/documentation/wordpress-plugin/';
		$this->plugin->support_url       = 'https://www.socialpostflow.com/support/';

		// Logo.
		$this->plugin->logo                        = SOCIAL_POST_FLOW_PLUGIN_URL . 'assets/images/icons/logo-dark.svg';
		$this->plugin->header_background_color     = '#ffffff';
		$this->plugin->header_primary_text_color   = '#3d3d3d';
		$this->plugin->header_secondary_text_color = '#6e6e6e';

		// Review.
		$this->plugin->review_name   = $this->plugin->name;
		$this->plugin->review_notice = 'Thanks for using Social Post Flow to schedule and publish to social media!';

		// Dashboard Submodule.
		if ( ! class_exists( 'WPZincDashboardWidget' ) ) {
			require_once $this->plugin->folder . '_modules/dashboard/class-wpzincdashboardwidget.php';
		}
		$this->dashboard = new WPZincDashboardWidget( $this->plugin );

		// Show Support Menu and hide Upgrade Menu.
		$this->dashboard->show_support_menu();
		$this->dashboard->hide_upgrade_menu();

		// Defer loading of Plugin Classes.
		add_action( 'init', array( $this, 'initialize' ), 1 );
		add_action( 'init', array( $this, 'upgrade' ), 2 );

		// Admin Menus.
		add_action( 'social_post_flow_admin_admin_menu', array( $this, 'admin_menus' ) );

	}

	/**
	 * Register menus and submenus.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $minimum_capability     Minimum required capability.
	 */
	public function admin_menus( $minimum_capability ) {

		// Main menu title.
		$menu_title = $this->plugin->displayName;

		// If the user's trial ended, add a badge to the menu title.
		if ( $this->get_class( 'user_access' )->user_has_no_access() ) {
			$menu_title = 'Social Post<br />Flow <span class="update-plugins count-1"><span class="plugin-count">!</span></span>';
		}

		// Settings.
		add_menu_page( $this->plugin->displayName, $menu_title, $minimum_capability, $this->plugin->name, array( $this->get_class( 'admin' ), 'settings_screen' ), $this->plugin->logo );
		add_submenu_page( $this->plugin->name, __( 'Settings', 'social-post-flow' ), __( 'Settings', 'social-post-flow' ), $minimum_capability, $this->plugin->name, array( $this->get_class( 'admin' ), 'settings_screen' ) );

		// Only show Bulk Publish and Logs if connected to the API.
		if ( $this->get_class( 'validation' )->api_connected() ) {
			// Bulk Publish.
			$bulk_publish_page = add_submenu_page( $this->plugin->name, __( 'Bulk Publish', 'social-post-flow' ), __( 'Bulk Publish', 'social-post-flow' ), $minimum_capability, $this->plugin->name . '-bulk-publish', array( $this->get_class( 'admin' ), 'bulk_publish_screen' ) );

			// Logs.
			if ( $this->get_class( 'log' )->is_enabled() ) {
				$log_page = add_submenu_page( $this->plugin->name, __( 'Logs', 'social-post-flow' ), __( 'Logs', 'social-post-flow' ), $minimum_capability, $this->plugin->name . '-log', array( $this->get_class( 'admin' ), 'log_screen' ) );
				add_action( "load-$log_page", array( $this->get_class( 'log' ), 'add_screen_options' ) );
			}
		}

		// Import & Export.
		do_action( 'social_post_flow_admin_menu_import_export' );

		// Support.
		do_action( 'social_post_flow_admin_menu_support' );

	}

	/**
	 * Initializes required and licensed classes
	 *
	 * @since   1.0.0
	 */
	public function initialize() {

		$this->classes = new stdClass();

		// Initialize required classes.
		$this->classes->admin         = new Social_Post_Flow_Admin();
		$this->classes->ajax          = new Social_Post_Flow_AJAX();
		$this->classes->api           = new Social_Post_Flow_API();
		$this->classes->bulk_actions  = new Social_Post_Flow_Bulk_Actions();
		$this->classes->bulk_publish  = new Social_Post_Flow_Bulk_Publish();
		$this->classes->cli           = new Social_Post_Flow_CLI();
		$this->classes->common        = new Social_Post_Flow_Common();
		$this->classes->cron          = new Social_Post_Flow_Cron();
		$this->classes->date          = new Social_Post_Flow_Date();
		$this->classes->export        = new Social_Post_Flow_Export();
		$this->classes->image         = new Social_Post_Flow_Image();
		$this->classes->import        = new Social_Post_Flow_Import();
		$this->classes->install       = new Social_Post_Flow_Install();
		$this->classes->media_library = new Social_Post_Flow_Media_Library();
		$this->classes->log           = new Social_Post_Flow_Log();
		$this->classes->notices       = new Social_Post_Flow_Notices();
		$this->classes->post          = new Social_Post_Flow_Post();
		$this->classes->publish       = new Social_Post_Flow_Publish();
		$this->classes->repost        = new Social_Post_Flow_Repost();
		$this->classes->screen        = new Social_Post_Flow_Screen();
		$this->classes->settings      = new Social_Post_Flow_Settings();
		$this->classes->user_access   = new Social_Post_Flow_User_Access();
		$this->classes->validation    = new Social_Post_Flow_Validation();

		// Integrations.
		$this->classes->acf                    = new Social_Post_Flow_ACF();
		$this->classes->aioseo                 = new Social_Post_Flow_AIOSEO();
		$this->classes->envira_gallery         = new Social_Post_Flow_Envira_Gallery();
		$this->classes->events_manager         = new Social_Post_Flow_Events_Manager();
		$this->classes->featured_image_caption = new Social_Post_Flow_Featured_Image_Caption();
		$this->classes->modern_events_calendar = new Social_Post_Flow_Modern_Events_Calendar();
		$this->classes->rank_math              = new Social_Post_Flow_Rank_Math();
		$this->classes->seopress               = new Social_Post_Flow_SEOPress();
		$this->classes->the_events_calendar    = new Social_Post_Flow_The_Events_Calendar();
		$this->classes->woocommerce            = new Social_Post_Flow_WooCommerce();
		$this->classes->wpml                   = new Social_Post_Flow_WPML();
		$this->classes->yoast_seo              = new Social_Post_Flow_Yoast_SEO();

	}

	/**
	 * Runs the upgrade routine once the plugin has loaded
	 *
	 * @since   1.1.7
	 */
	public function upgrade() {

		// Run upgrade routine.
		$this->get_class( 'install' )->upgrade();

	}

	/**
	 * Returns the given class
	 *
	 * @since   1.0.0
	 *
	 * @param   string $name   Class Name.
	 */
	public function get_class( $name ) {

		// If the class hasn't been loaded, throw a WordPress die screen
		// to avoid a PHP fatal error.
		if ( ! isset( $this->classes->{ $name } ) ) {
			// Define the error.
			$error = new WP_Error(
				'social_post_flow_get_class',
				sprintf(
					/* translators: %1$s: Plugin Name, %2$s: PHP class name */
					__( '%1$s: Error: Could not load Plugin class %2$s', 'social-post-flow' ),
					$this->plugin->displayName,
					$name
				)
			);

			// Depending on the request, return or display an error.
			// Admin UI.
			if ( is_admin() ) {
				wp_die(
					esc_html( $error->get_error_message() ),
					sprintf(
						/* translators: Plugin Name */
						esc_html__( '%s: Error', 'social-post-flow' ),
						esc_html( $this->plugin->displayName )
					),
					array(
						'back_link' => true,
					)
				);
			}

			// Cron / CLI.
			return $error;
		}

		// Return the class object.
		return $this->classes->{ $name };

	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @since   1.0.0
	 *
	 * @return  object Class.
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof self ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

}
