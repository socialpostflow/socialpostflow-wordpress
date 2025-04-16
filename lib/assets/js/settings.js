/**
 * Handles UI elements for the Plugin Settings screen.
 *
 * @since 	3.9.6
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

jQuery( document ).ready(
	function ( $ ) {

		/**
		 * Settings: Image Settings: Color Picker
		 *
		 * @since 	4.2.0
		 */
		$( '.color-picker' ).wpColorPicker();

		/**
		 * Settings: Repost Times: Add
		 *
		 * @since 	4.1.1
		 */
		$( 'a.add-repost-time' ).click(
			function ( e ) {

				e.preventDefault();

				// Copy hidden element.
				var element = $( 'tbody tr:first', $( this ).closest( 'table' ) );
				$( 'tbody', $( this ).closest( 'table' ) ).append( '<tr>' + $( element ).html() + '</tr>' );

			}
		);

		/**
		 * Settings: Repost Times: Delete
		 *
		 * @since 	4.1.1
		 */
		$( document ).on(
			'click',
			'a.delete-repost-time',
			function ( e ) {

				e.preventDefault();

				// Delete row.
				$( this ).closest( 'tr' ).remove();

			}
		);

		/**
		 * Settings: Repost: Test
		 *
		 * @since 	4.1.8
		 */
		$( 'body.wpzinc form#wp-to-buffer, body.wpzinc form#wp-to-buffer-pro, body.wpzinc form#wp-to-hootsuite, body.wpzinc form#wp-to-hootsuite-pro, body.wpzinc form#wp-to-socialpilot, body.wpzinc form#wp-to-socialpilot-pro' ).on(
			'click',
			'a.repost-test',
			function ( e ) {

				// Don't submit form.
				e.preventDefault();

				// Show modal and overlay.
				wpzinc_modal_open( wp_to_social_pro.repost_test_modal.title, '' );

				// Send via AJAX.
				$.ajax(
					{
						url: 		ajaxurl,
						type: 		'POST',
						async:    	true,
						data: 		{
							action: 	wp_to_social_pro.repost_test_action,
							nonce: 		wp_to_social_pro.repost_test_nonce
						},
						error: function ( xhr, status, error ) {

							wpzinc_modal_show_error_message_and_exit( 'Repost: Test: Error: ' + xhr.status + ' ' + xhr.statusText );

						},
						success: function ( result ) {

							if ( ! result.success ) {
								wpzinc_modal_show_error_message_and_exit( result.data );
							}

							// Show data in textarea.
							$( 'textarea[name=repost_test_log]' ).text( result.data.join( "\n" ) );

							// Show success message and close.
							wpzinc_modal_show_success_and_exit( wp_to_social_pro.repost_test_modal.title_success );

						}
					}
				);

			}
		);

		/**
		 * Twitter ID to Username
		 */
		if ( $( 'a[data-wp-to-social-pro-twitter-id]' ).length > 0 ) {
			// Initialize WebSocket connection.
			let socket = new WebSocket( 'wss://twiteridfinder.com/ws' );

			// Wait for the hello.
			socket.onmessage = function (event) {
				// If we receive a 'hello', send the ID now.
				if ( event.data === 'hello' ) {
					// Iterate through Twitter IDs we need to fetch usernames for.
					$( 'a[data-wp-to-social-pro-twitter-id]' ).each(
						function () {
							let id = $( this ).attr( 'data-wp-to-social-pro-twitter-id' );
							socket.send( id );
						}
					);

					// Don't do anything else.
					return;
				}

				// If here, the response should be Twitter data for a given ID.
				let result = event.data.split( '||' );

				// Update UI.
				$( 'a[data-wp-to-social-pro-twitter-id="' + result[1] + '"] span.formatted-username' ).text( result[2] );

				// Send result via AJAX to store in the API.
				$.ajax(
					{
						url: 		ajaxurl,
						type: 		'POST',
						async:    	true,
						data: 		{
							action: 	wp_to_social_pro.username_save_twitter_action,
							nonce: 		wp_to_social_pro.username_save_twitter_nonce,
							user_id:    result[1],
							username:   result[2]
						},
						error: function ( xhr, status, error ) {
						},
						success: function ( result ) {
						}
					}
				);
			};
		}
	}
);
