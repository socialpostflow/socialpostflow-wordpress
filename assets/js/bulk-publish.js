/**
 * Handles UI elements for the Plugin's Bulk Publish screen.
 *
 * @since 	1.0.0
 *
 * @package Social_Post_Flow
 * @author Social Post Flow
 */

jQuery( document ).ready(
	function ( $ ) {

		/**
		 * Bulk Publishing: Initialize selectize instances
		 */
		if ( $( '#post-selection' ).length > 0 ) {

			socialPostFlowInitSelectize( '#post-selection' );

		}

		/**
		 * Select All
		 */
		$( 'body.wpzinc' ).on(
			'change',
			'input[name=toggle]',
			function ( e ) {
				// Change.
				if ( $( this ).is( ':checked' ) ) {
					$( 'ul.categorychecklist input[type=checkbox]' ).prop( 'checked', true );
				} else {
					$( 'ul.categorychecklist input[type=checkbox]' ).prop( 'checked', false );
				}
			}
		);

		/**
		 * Synchronous AJAX to send statuses
		 */
		if ( $( '#progress-bar' ).length > 0 ) {
			$( '#progress-bar' ).synchronous_request(
				{
					url: 				social_post_flow_bulk_publish.ajax,
					number_requests: 	social_post_flow_bulk_publish.number_of_requests,
					action: 			social_post_flow_bulk_publish.action,
					nonce: 				social_post_flow_bulk_publish.nonce,
					ids:  				social_post_flow_bulk_publish.post_ids,
					stop_on_error:  	-1, // Continue to next request if an error/warning.

					/**
					 * Called when an AJAX request returns a successful response.
					 *
					 * @since   1.0.0
					 *
					 * @param   object  response        Response
					 * @param   int     currentIndex    Current Index
					 */
					onRequestSuccess: function ( response, currentIndex ) {

						$( 'tbody', $( this.log ) ).append( response.data );

						// Run the next request.
						return true;

					},

					/**
					 * Called when an AJAX request results in a HTTP or server error.
					 *
					 * @since   1.0.0
					 */
					onRequestError: function ( xhr, textStatus, e, currentIndex ) {

						$( 'tbody', $( this.log ) ).append( '<tr><td colspan="8">' + textStatus + '</td></tr>' );

						// Run the next request.
						return true;

					},

					/**
					 * Called when all requests have completed, or the user cancelled.
					 *
					 * @since   1.0.0
					 */
					onFinished: function () {

						if ( this.cancelled ) {
							$( 'tbody', $( this.log ) ).append( '<tr><td colspan="8">' + social_post_flow_bulk_publish.finished + '</td></tr>' );
						} else {
							$( 'tbody', $( this.log ) ).append( '<tr><td colspan="8">' + social_post_flow_bulk_publish.finished + '</td></tr>' );
						}

					}

				}
			);
		}

	}
);
