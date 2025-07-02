/**
 * Handles UI elements for the Plugin Settings screen.
 *
 * @since 	1.0.0
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

jQuery( document ).ready(
	function ( $ ) {

		/**
		 * Settings: Image Settings: Color Picker
		 *
		 * @since 	1.0.0
		 */
		$( '.color-picker' ).wpColorPicker();

		/**
		 * Settings: Repost Times: Add
		 *
		 * @since 	1.0.0
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
		 * @since 	1.0.0
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
		 * @since 	1.0.0
		 */
		$( 'body.wpzinc form#social-post-flow' ).on(
			'click',
			'a.repost-test',
			function ( e ) {

				// Don't submit form.
				e.preventDefault();

				// Show modal and overlay.
				wpzinc_modal_open( social_post_flow.repost_test_modal.title, '' );

				// Send via AJAX.
				$.ajax(
					{
						url: 		ajaxurl,
						type: 		'POST',
						async:    	true,
						data: 		{
							action: 	social_post_flow.repost_test_action,
							nonce: 		social_post_flow.repost_test_nonce
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
							wpzinc_modal_show_success_and_exit( social_post_flow.repost_test_modal.title_success );

						}
					}
				);

			}
		);
	}
);
