/**
 * Handles populating data in the Quick Edit screen for this Plugin.
 *
 * @since 	1.0.0
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

jQuery( document ).ready(
	function ( $ ) {

		/**
		 * Quick Edit
		 * Bulk Edit
		 */
		if ( typeof inlineEditPost !== 'undefined' ) {
			// Create a copy of the WordPress inline edit post function.
			var wp_inline_edit = inlineEditPost.edit;

			// Oerwrite the function with our own code.
			inlineEditPost.edit = function ( id ) {

				// "call" the original WP edit function.
				// we don't want to leave WordPress hanging.
				wp_inline_edit.apply( this, arguments );

				// Get the post ID.
				var post_id = 0;
				if ( typeof( id ) == 'object' ) {
					post_id = parseInt( this.getId( id ) );
				}

				if ( post_id > 0 ) {
					// Get the Edit and Post Row Elements.
					var edit_row = $( '#edit-' + post_id );
					var post_row = $( '#post-' + post_id );

					// Get our hidden field values.
					if ( $( 'input[name="social_post_flow_override_' + post_id + '"]', $( post_row ) ).length > 0 ) {
						var override = $( 'input[name="social_post_flow_override_' + post_id + '"]', $( post_row ) ).val();
					}
					if ( $( 'input[name="wp_to_hootsuite_pro_override_' + post_id + '"]', $( post_row ) ).length > 0 ) {
						var override = $( 'input[name="wp_to_hootsuite_pro_override_' + post_id + '"]', $( post_row ) ).val();
					}

					// Populate Quick Edit Fields with data from the above hidden fields.
					// These are output in page_columns_output() in our plugin.
					if ( $( 'select[name="social-post-flow[override]"]', $( edit_row ) ).length > 0 ) {
						$( 'select[name="social-post-flow[override]"]', $( edit_row ) ).val( override );
					}
				}
			}

			// Remove all hidden inputs when a search is performed.
			// This stops them from being included in the GET URL, otherwise we'd have a really long search URL
			// which breaks some nginx configurations.
			$( 'form#posts-filter' ).on(
				'submit',
				function (e) {
					$( "input[name*='wp_to_buffer_pro']" ).remove();
					$( "input[name*='wp_to_hootsuite_pro']" ).remove();
				}
			);
		}

	}
);
