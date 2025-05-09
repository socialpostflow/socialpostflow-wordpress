<?php
/**
 * Bulk Actions class.
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * Registers and handles bulk actions on WP_List_Tables,
 * primarily for Bulk Publishing selected Post(s).
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 3.0.0
 */
class Social_Post_Flow_Bulk_Actions {

	/**
	 * Constructor
	 *
	 * @since   3.3.8
	 */
	public function __construct() {

		// Actions.
		add_action( 'admin_init', array( $this, 'register_bulk_action_filters' ) );

	}

	/**
	 * Registers Bulk Action Filters
	 *
	 * @since   3.3.8
	 */
	public function register_bulk_action_filters() {

		// Get public Post Types.
		$post_types = social_post_flow()->get_class( 'common' )->get_post_types();

		// Bail if no Post Types.
		if ( empty( $post_types ) ) {
			return;
		}

		// For each Post Type, add filters for Bulk Actions.
		foreach ( $post_types as $post_type ) {
			add_filter( 'bulk_actions-edit-' . $post_type->name, array( $this, 'register_bulk_actions' ) );
			add_filter( 'handle_bulk_actions-edit-' . $post_type->name, array( $this, 'handle_bulk_actions' ), 10, 3 );
		}

	}

	/**
	 * Adds Bulk Action options to Post Type WP_List_Tables
	 *
	 * @since   3.3.8
	 *
	 * @param   array $actions    Registered Bulk Actions.
	 * @return  array               Registered Bulk Actions
	 */
	public function register_bulk_actions( $actions ) {

		// If no bulk actions exist, cast as an array now.
		// This may be due to e.g. User capability Plugins removing all actions for a given
		// WordPress User Role.
		if ( ! is_array( $actions ) ) {
			$actions = array();
		}

		// Define Actions.
		$bulk_actions = array(
			'social-post-flow' => __( 'Send to Social Post Flow', 'social-post-flow' ),
		);

		/**
		 * Defines Bulk Actions to be added to the select dropdown on WP_List_Tables.
		 *
		 * @since   3.3.8
		 *
		 * @param   array   $bulk_actions   Plugin Specific Bulk Actions.
		 * @param   string  $actions        Existing Registered Bulk Actions (excluding Plugin Specific Bulk Actions).
		 */
		$bulk_actions = apply_filters( 'social_post_flow_bulk_actions_register_bulk_actions', $bulk_actions, $actions );

		// Merge with default Bulk Actions.
		$actions = array_merge( $bulk_actions, $actions );

		// Return.
		return $actions;

	}

	/**
	 * Handles Bulk Actions when one is selected to run
	 *
	 * @since   3.3.8
	 *
	 * @param   string $redirect_to    Redirect URL.
	 * @param   string $action         Bulk Action to Run.
	 * @param   array  $post_ids       Post IDs to apply Action on.
	 * @return  string                  Redirect URL
	 */
	public function handle_bulk_actions( $redirect_to, $action, $post_ids ) {

		// Bail if the action isn't specified.
		if ( empty( $action ) ) {
			return $redirect_to;
		}

		// Bail if no Post IDs.
		if ( empty( $post_ids ) ) {
			return $redirect_to;
		}

		switch ( $action ) {
			case 'social-post-flow':
				// Get the Post Type from the screen.
				$screen = get_current_screen();

				// Redirect to Bulk Publishing, with the chosen Post IDs preselected
				// and the required nonce verification.
				$args        = array(
					'page'                               => 'social-post-flow-bulk-publish',
					'post_ids'                           => implode( ',', $post_ids ),
					'type'                               => $screen->post_type,
					'social_post_flow_nonce' => wp_create_nonce( 'social-post-flow' ),
				);
				$redirect_to = admin_url( add_query_arg( $args, 'admin.php' ) );
				break;

			default:
				// Allow developers to run their Bulk Action now.
				do_action( 'social_post_flow_bulk_actions_handle_bulk_actions', $action, $post_ids );
				do_action( 'social_post_flow_bulk_actions_handle_bulk_actions_' . $action, $post_ids );
				break;
		}

		// Return redirect.
		return $redirect_to;

	}

}
