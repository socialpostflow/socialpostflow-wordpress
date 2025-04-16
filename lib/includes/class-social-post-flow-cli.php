<?php
/**
 * WP-CLI class.
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * Register WP-CLI commands.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 */
class Social_Post_Flow_CLI {

	/**
	 * Holds the base object.
	 *
	 * @since   4.8.4
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   4.8.4
	 *
	 * @param   string $cli_function_prefix    CLI commands prefix (e.g. wp-to-buffer-pro, wp-to-hootsuite-pro).
	 * @param   string $cli_class_prefix       CLI class prefix (e.g. Social_Post_Flow, WP_To_Hootsuite_Pro).
	 */
	public function __construct( $cli_function_prefix, $cli_class_prefix ) {

		// Repost.
		WP_CLI::add_command(
			$cli_function_prefix . '-repost',
			$cli_class_prefix . '_CLI_Repost',
			array(
				'shortdesc' => __( 'Reposts Posts, Pages and Custom Post Types to Buffer based on the status settings at Plugin and Post level.', 'social-post-flow' ),
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
		foreach ( get_taxonomies() as $wp_to_social_pro_taxonomy ) {
			$args[] = array(
				'type'     => 'assoc',
				'name'     => $wp_to_social_pro_taxonomy,
				'optional' => true,
				'multiple' => false,
			);
		}
		WP_CLI::add_command(
			$cli_function_prefix . '-bulk-publish',
			$cli_class_prefix . '_CLI_Bulk_Publish',
			array(
				'shortdesc' => __( 'Bulk Publish the given Post IDs to Buffer based on the status settings at Plugin and Post level.', 'social-post-flow' ),
				'synopsis'  => $args,
				'when'      => 'before_wp_load',
			)
		);

	}

}
