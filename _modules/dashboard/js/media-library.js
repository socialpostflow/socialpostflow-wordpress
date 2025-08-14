/**
 * Choose Attachment(s) from the Media Library, inserting into the DOM
 * hidden field and output preview.
 *
 * @package WPZincDashboardWidget
 * @author WP Zinc
 */

( function ( $ ) {

	// Make Attachment(s) sortable.
	$( '.wpzinc-media-library-selector' ).each(
		function () {

			// Determine if this Media Library selector instance supports multiple Attachments.
			var multiple = $( this ).data( 'multiple' );
			if ( typeof multiple == 'undefined' ) {
				multiple = false;
			}

			// Bail if multiple Attachments are not supported, as we don't need to sort a single Attachment.
			if ( ! multiple ) {
				return;
			}

			// Initialize jQuery UI sortable.
			$( 'ul', $( this ) ).sortable();

		}
	);

	// Open Media Library Backbone Modal to allow selection of attachments.
	$( '#wpbody' ).on(
		'click',
		'.wpzinc-media-library-insert',
		function ( e ) {

			// Prevent default action.
			e.preventDefault();

			// Get container and attributes.
			var container = $( this ).closest( '.wpzinc-media-library-selector' ),
			input_name    = $( container ).data( 'input-name' ),
			input_url     = $( container ).data( 'input-url' ),
			output_size   = $( container ).data( 'output-size' ), // The size of the image to output.
			file_type     = $( container ).data( 'file-type' ),		// The file types that can be selected.
			multiple      = $( container ).data( 'multiple' ),		// If multiple attachments can be selected.
			limit         = $( container ).data( 'limit' );				// The number of attachments that can be selected.

			// Define some attributes if they're not defined in the data- attributes.
			if ( typeof input_url == 'undefined' ) {
				input_url = false;
			}
			if ( typeof output_size == 'undefined' ) {
				output_size = 'thumbnail';
			}
			if ( typeof file_type == 'undefined' ) {
				file_type = 'image';
			}
			if ( typeof multiple == 'undefined' ) {
				multiple = false;
			}
			if ( typeof limit == 'undefined' ) {
				limit = 99999;
			}

			// Get existing Attachment(s) that were selected.
			var existing_selected_attachment_ids = [];
			$( 'input[type=hidden]', $( container ) ).each(
				function () {
					existing_selected_attachment_ids.push( $( this ).val() );
				}
			);

			// If plugin_media_manager has already been defined, open it now.
			if ( wpzinc_plugin_media_manager ) {
				wpzinc_plugin_media_manager.open();
				return;
			}

			// Setup new wp.media instance, if it's not already set by one of our plugins.
			var wpzinc_plugin_media_manager = wp.media(
				{
					title: 'Choose Item',
					button: {
						text: 'Select'
					},
					library: {
						type: file_type
					},
					multiple: ( multiple ? 'add' : false )
				}
			);

			// When the modal is opened, select any existing Attachments that were previously selected.
			wpzinc_plugin_media_manager.on(
				'open',
				function () {

					// Get selected attachments.
					var selection = wpzinc_plugin_media_manager.state().get( 'selection' );

					// Add existing Attachment(s) that were selected to the selection, so they're
					// marked as selected in the modal.
					existing_selected_attachment_ids.forEach(
						function ( id ) {
							attachment = wp.media.attachment( id );
							attachment.fetch();
							selection.add( attachment ? [attachment] : [] );
						}
					);

				}
			);

			// Remove all attached 'select' events.
			wpzinc_plugin_media_manager.off( 'select' );
			wpzinc_plugin_media_manager.off( 'selection:toggle' );

			// When an Attachment is selected or deselected, check that the total
			// number of selected Attachments doesn't exceed the limit.
			// If it does, remove (deselect) the selected attachment.
			wpzinc_plugin_media_manager.on(
				'selection:toggle',
				function () {

					var selection = wpzinc_plugin_media_manager.state().get( 'selection' );

					if ( selection.length > limit ) {
						selection.remove( selection.last() );
					}

				}
			);

			// When the Select button in the modal is clicked, insert.
			wpzinc_plugin_media_manager.on(
				'select',
				function () {

					// Get selected attachments.
					var selection = wpzinc_plugin_media_manager.state().get( 'selection' ).map(
						function ( attachment ) {

							attachment.toJSON();
							return attachment;

						}
					);

					// Clear Attachments HTML.
					$( 'ul', $( container ) ).html( '' );

					// Iterate through selected attachments, outputting HTML for each.
					var length = selection.length;
					for ( var i = 0; i < length; i++ ) {

						// Get this attachment's ID and URL.
						var attachment = selection[ i ],
						attachment_id  = attachment.get( 'id' ),
						attachment_url = attachment.get( 'url' );

						// Build HTML.
						var html = '<li class="wpzinc-media-library-attachment">';
						html    += '<a href="#" class="wpzinc-media-library-insert">';
						html    += '<input type="hidden" name="' + input_name + '" value="' + attachment_id + '" />';

						// Build Output Preview, depending on the Attachment's type.
						switch ( attachment.attributes.type ) {

							case 'image':
								// If the image size we're requesting exists, use that instead.
								if ( typeof attachment.attributes.sizes[ output_size ] !== 'undefined' ) {
									attachment_url = attachment.attributes.sizes[ output_size ].url;
								}

								html += '<img src="' + attachment_url + '" />';

								// Include URL as hidden field if required.
								if ( input_url ) {
									html += '<input type="hidden" name="' + input_url + '" value="' + attachment.attributes.url + '" />';
								}
								break;

							default:
								html += attachment.attributes.filename;
								break;

						}

						// Finish building HTML.
						html += '<a href="#" class="wpzinc-media-library-remove">Remove</a>';
						html += '</li>';

						// Inject.
						$( 'ul', $( container ) ).append( html );

						// Trigger event so other JS can hook into when an attachment is added.
						$( 'body' ).trigger( 'wpzinc-media-library-attachment-added' );

					}

				}
			);

			// Open the Media View.
			wpzinc_plugin_media_manager.open();

		}
	);

	// Removes a selected Attachment.
	$( '#wpbody' ).on(
		'click',
		'.wpzinc-media-library-remove',
		function ( e ) {

			// Prevent default action.
			e.preventDefault();

			// Remove.
			$( this ).closest( '.wpzinc-media-library-attachment' ).remove();

		}
	);

} ( jQuery ) );
