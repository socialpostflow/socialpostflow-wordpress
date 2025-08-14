<?php
/**
 * WP-CLI Class
 *
 * @package Social_Post_Flow
 * @author Social Post Flow
 */

/**
 * Registers CLI commands for this Plugin.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */
class Social_Post_Flow_CLI {

	/**
	 * Register CLI commands.
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

		// Bail if WP-CLI is not installed.
		if ( ! class_exists( 'WP_CLI' ) ) {
			return;
		}

		// Require CLI class files.
		require_once SOCIAL_POST_FLOW_PLUGIN_PATH . 'includes/class-social-post-flow-cli-bulk-publish.php';
		require_once SOCIAL_POST_FLOW_PLUGIN_PATH . 'includes/class-social-post-flow-cli-repost.php';

		// Register commands.
		// Repost.
		WP_CLI::add_command(
			'social-post-flow-repost',
			'Social_Post_Flow_CLI_Repost',
			array(
				'shortdesc' => __( 'Reposts Posts, Pages and Custom Post Types to social media based on the status settings at Plugin and Post level.', 'social-post-flow' ),
				'synopsis'  => array(
					array(
						'type'     => 'assoc',
						'name'     => 'post_types',
						'optional' => true,
						'multiple' => false,
					),
					array(
						'type'     => 'flag',
						'name'     => 'test_mode',
						'optional' => true,
					),
				),
				'when'      => 'before_wp_load',
			)
		);

		// Bulk Publish.
		$args = array(
			array(
				'type'     => 'assoc',
				'name'     => 'ids',
				'optional' => true,
				'multiple' => false,
			),
			array(
				'type'     => 'assoc',
				'name'     => 'post_type',
				'optional' => true,
				'multiple' => false,
			),
			array(
				'type'     => 'assoc',
				'name'     => 'start_date',
				'optional' => true,
				'multiple' => false,
			),
			array(
				'type'     => 'assoc',
				'name'     => 'end_date',
				'optional' => true,
				'multiple' => false,
			),
			array(
				'type'     => 'assoc',
				'name'     => 'authors',
				'optional' => true,
				'multiple' => false,
			),
			array(
				'type'     => 'assoc',
				'name'     => 'meta_key',
				'optional' => true,
				'multiple' => false,
			),
			array(
				'type'     => 'assoc',
				'name'     => 'meta_value',
				'optional' => true,
				'multiple' => false,
			),
			array(
				'type'     => 'assoc',
				'name'     => 'meta_compare',
				'optional' => true,
				'multiple' => false,
			),
			array(
				'type'     => 'assoc',
				'name'     => 's',
				'optional' => true,
				'multiple' => false,
			),
			array(
				'type'     => 'assoc',
				'name'     => 'orderby',
				'optional' => true,
				'multiple' => false,
			),
			array(
				'type'     => 'assoc',
				'name'     => 'order',
				'optional' => true,
				'multiple' => false,
			),
			array(
				'type'     => 'flag',
				'name'     => 'test_mode',
				'optional' => true,
			),
		);
		foreach ( get_taxonomies() as $social_post_flow_taxonomy ) {
			$args[] = array(
				'type'     => 'assoc',
				'name'     => $social_post_flow_taxonomy,
				'optional' => true,
				'multiple' => false,
			);
		}
		WP_CLI::add_command(
			'social-post-flow-bulk-publish',
			'Social_Post_Flow_CLI_Bulk_Publish',
			array(
				'shortdesc' => __( 'Bulk Publish the given Post IDs to social media based on the status settings at Plugin and Post level.', 'social-post-flow' ),
				'synopsis'  => $args,
				'when'      => 'before_wp_load',
			)
		);

	}

}
