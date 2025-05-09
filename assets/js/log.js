/**
 * Handles UI elements for the Plugin Log screen.
 *
 * @since 	3.9.6
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

jQuery( document ).ready(
	function ( $ ) {

		/**
		 * Refresh Log
		 *
		 * @since 	4.4.0
		 */
		$( 'a.' + social_post_flow.plugin_name + '-refresh-log' ).on(
			'click',
			function ( e ) {

				// Prevent default action.
				e.preventDefault();

				// Define button.
				var button = $( this );

				// Send AJAX request to clear log.
				$.post(
					social_post_flow.ajax,
					{
						'action': 		$( button ).data( 'action' ),
						'post': 		social_post_flow.post_id,
						'nonce': 		social_post_flow.get_log_nonce
					},
					function ( response ) {

						// Replace the table data with the response data.
						$( 'table.widefat tbody', $( $( button ).data( 'target' ) ) ).html( response.data );

					}
				);
			}
		);

		/**
		 * Clear Log
		 *
		 * @since 	3.0.0
		 */
		$( 'a.' + social_post_flow.plugin_name + '-clear-log' ).on(
			'click',
			function ( e ) {

				// Define button.
				var button = $( this );

				// Bail if the user doesn't want to clear the log.
				var result = confirm( $( button ).data( 'message' ) );
				if ( ! result ) {
					// Prevent default action.
					e.preventDefault();
					return false;
				}

				// If the button doesn't have an action and a target, it's not an AJAX request.
				// Let the request through.
				if ( typeof $( button ).data( 'action' ) === undefined || $( button ).data( 'target' ) === undefined ) {
					return true;
				}

				// Prevent default action.
				e.preventDefault();

				// Send AJAX request to clear log.
				$.post(
					social_post_flow.ajax,
					{
						'action': 		$( button ).data( 'action' ),
						'post': 		$( 'input[name=post_ID]' ).val(),
						'nonce': 		social_post_flow.clear_log_nonce
					},
					function ( response ) {

						// Clear Log.
						$( 'table.widefat tbody', $( $( button ).data( 'target' ) ) ).html( '<tr><td colspan="8">' + social_post_flow.clear_log_completed + '</td></tr>' );

					}
				);
			}
		);

	}
);
