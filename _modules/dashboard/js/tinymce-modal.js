/**
 * Inserts shortcodes into TinyMCE and QuickTag instances when the TinyMCE
 * modal's Insert or Cancel buttons are clicked.
 *
 * For QuickTags, sets up a Backbone modal to look similar to the TinyMCE
 * modal.
 *
 * @package WPZincDashboardWidget
 * @author WP Zinc
 */

/**
 * Handles the Insert and Cancel events on TinyMCE and QuickTag Modals
 *
 * @since   1.0.0
 */
jQuery( document ).ready(
	function ( $ ) {

		// Cancel.
		$( 'body' ).on(
			'click',
			'#wpzinc-tinymce-modal div.mce-cancel button, .wpzinc-backbone-modal .media-frame-toolbar .media-toolbar button.cancel',
			function ( e ) {

				// TinyMCE.
				if ( typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden() ) {
					tinymce.activeEditor.windowManager.close();
					return;
				}

				// Text Editor.
				wpZincModal.close();

				// Reset the non-TinyMCE modal content.
				// If we don't do this, switching from Text to Visual Editor for the same shortcode results
				// code picking up data from the QuickTags modal, not the TinyMCE one.
				if ( typeof wpZincModal !== 'undefined' ) {
					wpZincModal.content( new wpZincModalContent() );
				}

			}
		);

		// Insert.
		$( 'body' ).on(
			'click',
			'#wpzinc-tinymce-modal div.mce-insert button, .wpzinc-backbone-modal .media-frame-toolbar .media-toolbar button.insert',
			function ( e ) {

				// Prevent default action.
				e.preventDefault();

				// Get containing form.
				var form = $( 'form.wpzinc-tinymce-popup' );

				// Build Shortcode.
				var shortcode  = '[' + $( 'input[name="shortcode"]', $( form ) ).val(),
				shortcodeClose = ( $( 'input[name="close_shortcode"]', $( form ) ).val() == '1' ? true : false );

				$( 'input, select, textarea', $( form ) ).each(
					function ( i ) {
						// Skip if no data-shortcode attribute.
						if ( typeof $( this ).data( 'shortcode' ) === 'undefined' ) {
							return true;
						}

						// Skip if the value is empty.
						if ( ! $( this ).val() ) {
							return true;
						}
						if ( $( this ).val().length == 0 ) {
							return true;
						}

						// Get shortcode attribute.
						var key = $( this ).data( 'shortcode' ),
						trim    = ( $( this ).data( 'trim' ) == '0' ? false : true ),
						val     = $( this ).val();

						// Skip if the shortcode is empty.
						if ( ! key.length ) {
							return true;
						}

						// If the shortcode attribute is within curly braces, the shortcode attribute
						// is the value of another field.
						if ( key.search( '}' ) > -1 && key.search( '{' ) > -1 ) {
							// Remove curly braces.
							key = key.replace(
								/{|}/gi,
								function ( x ) {
									return '';
								}
							);

							// Get value of input/select, which will form our attribute.
							key = $( key, $( this ).parent().parent() ).val();
						}

						// If a prepend is specified, prepend the key with it now.
						if ( typeof ( $( this ).data( 'shortcode-prepend' ) ) !== 'undefined' ) {
							key = $( this ).data( 'shortcode-prepend' ) + key;
						}

						// If the value is an array (i.e. from selectize), implode it now.
						if ( Array.isArray( val ) ) {
							val = val.join( ',' );
						}

						// Trim the value, unless the shortcode attribute disables string trimming.
						if ( trim ) {
							val = val.trim();
						}

						// Append attribute and value to shortcode string.
						shortcode += ' ' + key.trim() + '="' + val + '"';
					}
				);

				// Close Shortcode.
				shortcode += ']';

				// If the shortcode includes a closing element, append it now.
				if ( shortcodeClose ) {
					shortcode += '[/' + $( 'input[name="shortcode"]', $( form ) ).val() + ']';
				}

				// Depending on the editor type, insert the shortcode.
				let editor_type = $( 'input[name="editor_type"]', $( form ) ).val();
				switch ( editor_type ) {
					case 'tinymce':
						// Sanity check that a Visual editor exists and is active.
						if ( typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden() ) {
							// Insert into editor.
							tinyMCE.activeEditor.execCommand( 'mceReplaceContent', false, shortcode );

							// Close modal.
							tinyMCE.activeEditor.windowManager.close();
						}
						break;

					case 'quicktags':
						// Insert into editor.
						QTags.insertContent( shortcode );

						// Close modal.
						wpZincModal.close();

						// Reset the modal content.
						// If we don't do this, switching from Text to Visual Editor for the same shortcode results
						// code picking up data from the QuickTags modal, not the TinyMCE one.
						if ( typeof wpZincModal !== 'undefined' ) {
							wpZincModal.content( new wpZincModalContent() );
						}
						break;

					default:
						// Insert into selector.
						$( editor_type ).val( shortcode );

						// Close modal.
						wpZincModal.close();

						// Reset the modal content.
						// If we don't do this, switching from Text to Visual Editor for the same shortcode results
						// code picking up data from the QuickTags modal, not the TinyMCE one.
						if ( typeof wpZincModal !== 'undefined' ) {
							wpZincModal.content( new wpZincModalContent() );
						}
						break;
				}

			}
		);

	}
);

// QuickTags: Setup Backbone Modal and Template.
if ( typeof wp !== 'undefined' && typeof wp.media !== 'undefined' ) {
	var wpZincModal        = new wp.media.view.Modal(
		{
			controller: { trigger: function () {} },
			className: 'wpzinc-backbone-modal'
		}
	);
	var wpZincModalContent = wp.Backbone.View.extend(
		{
			template: wp.template( 'wpzinc-modal' )
		}
	);
	wpZincModal.content( new wpZincModalContent() );
}
