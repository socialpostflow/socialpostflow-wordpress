<?php
/**
 * Social Post Flow WordPress Plugin.
 *
 * @package Social_Post_Flow
 * @author Social Post Flow
 *
 * @wordpress-plugin
 * Plugin Name: Social Post Flow
 * Plugin URI: http://www.socialpostflow.com/integrations/wordpress
 * Version: 1.1.3
 * Author: Social Post Flow
 * Author URI: http://www.socialpostflow.com
 * Description: Send WordPress Pages, Posts or Custom Post Types to social media for scheduled publishing to social networks.
 * Text Domain: social-post-flow
 * License:     GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Bail if Plugin is alread loaded.
if ( class_exists( 'Social_Post_Flow' ) ) {
	return;
}

// Define Plugin version and build date.
define( 'SOCIAL_POST_FLOW_PLUGIN_VERSION', '1.1.3' );
define( 'SOCIAL_POST_FLOW_PLUGIN_BUILD_DATE', '2025-11-28 11:00:00' );

// Define Plugin paths.
define( 'SOCIAL_POST_FLOW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SOCIAL_POST_FLOW_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Define the autoloader for this Plugin
 *
 * @since   1.0.0
 *
 * @param   string $class_name     The class to load.
 */
function social_post_flow_autoloader( $class_name ) {

	// Define the required start of the class name.
	$class_start_name = 'Social_Post_Flow';

	// Get the number of parts the class start name has.
	$class_parts_count = count( explode( '_', $class_start_name ) );

	// Break the class name into an array.
	$class_path = explode( '_', $class_name );

	// Bail if it's not a minimum length (i.e. doesn't potentially have Social_Post_Flow).
	if ( count( $class_path ) < $class_parts_count ) {
		return;
	}

	// Build the base class path for this class.
	$base_class_path = '';
	for ( $i = 0; $i < $class_parts_count; $i++ ) {
		$base_class_path .= $class_path[ $i ] . '_';
	}
	$base_class_path = trim( $base_class_path, '_' );

	// Bail if the first parts don't match what we expect.
	if ( $base_class_path !== $class_start_name ) {
		return;
	}

	// Define the file name.
	$file_name = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';

	// Define the paths to search for the file.
	$include_paths = array(
		SOCIAL_POST_FLOW_PLUGIN_PATH . 'includes',
		SOCIAL_POST_FLOW_PLUGIN_PATH . 'includes/integrations',
	);

	// Iterate through the include paths to find the file.
	foreach ( $include_paths as $path ) {
		if ( file_exists( $path . '/' . $file_name ) ) {
			require_once $path . '/' . $file_name;
			return;
		}
	}

}
spl_autoload_register( 'social_post_flow_autoloader' );

// Load Activation, Cron and Deactivation functions.
require_once SOCIAL_POST_FLOW_PLUGIN_PATH . 'includes/activation.php';
require_once SOCIAL_POST_FLOW_PLUGIN_PATH . 'includes/cron.php';
require_once SOCIAL_POST_FLOW_PLUGIN_PATH . 'includes/deactivation.php';
register_activation_hook( __FILE__, 'social_post_flow_activate' );
if ( version_compare( get_bloginfo( 'version' ), '5.1', '>=' ) ) {
	add_action( 'wp_insert_site', 'social_post_flow_activate_new_site' );
} else {
	add_action( 'wpmu_new_blog', 'social_post_flow_activate_new_site' );
}
add_action( 'activate_blog', 'social_post_flow_activate_new_site' );
register_deactivation_hook( __FILE__, 'social_post_flow_deactivate' );

/**
 * Main function to return Plugin instance.
 *
 * @since   1.0.0
 */
function social_post_flow() {

	return Social_Post_Flow::get_instance();

}

// Finally, initialize the Plugin.
require_once SOCIAL_POST_FLOW_PLUGIN_PATH . 'includes/class-social-post-flow.php';
$social_post_flow = social_post_flow();
