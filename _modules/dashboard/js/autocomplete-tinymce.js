/**
 * Initialises Keyword Autocomplete for TinyMCE instances.
 *
 * @package WPZincDashboardWidget
 * @author WP Zinc
 */

( function () {

	// Define key binds.
	const DOWN_ARROW_KEY = 40;
	const UP_ARROW_KEY   = 38;
	const ESC_KEY        = 27;
	const ENTER_KEY      = 13;
	const BACKSPACE      = 8;

	// Define key binds to ignore.
	var keyBindsToIgnore = [
		DOWN_ARROW_KEY,
		UP_ARROW_KEY,
		ESC_KEY,
		ENTER_KEY
	];

	// Define triggers as character codes.
	var textToOpenAutoComplete = [
		// Changing this to 219 i.e. left curly brace wipes an entire paragraph, no idea why.
		'123'
	];

	// Iterate through autocompleters.
	if ( typeof wpzinc_autocomplete !== 'undefined' ) {

		wpzinc_autocomplete.forEach(
			function ( autocompleter, i ) {
				autocompleter.triggers.forEach(
					function ( trigger, j ) {

						// Skip remote autocompleters.
						if ( 'url' in trigger ) {
							return;
						}

						// Create TinyMCE Plugin for this Autocompleter.
						tinymce.create(
							'tinymce.plugins.' + trigger.tinyMCEName,
							{

								init: function ( editor ) {

									var autoCompleteDisplayed = false,
									autoCompleteContainer     = createAutoComplete();

									/**
									 * Creates an unordered list comprising of all keywords that can be
									 * searched and displayed as a list.
									 *
									 * @since 	2.0.2
									 */
									function createAutoComplete() {

										// Define <ul>.
										var ul = document.createElement( 'ul' );
										ul.setAttribute( 'class', 'wpzinc-tinymce-autocomplete' );

										// Define <li>'s, appending to <ul>.
										trigger.values.forEach(
											function ( value, key ) {
												var li = document.createElement( 'li' );
												li.classList.add( 'displayed' );
												ul.appendChild( li );
												li.innerHTML = li.innerHTML + value.value;
											}
										);

										// Append <ul> to body.
										document.body.appendChild( ul );

										// Return <ul>.
										return ul;

									}

									/**
									 * Shows the autocomplete list.
									 *
									 * @since 	2.0.2
									 *
									 * @param 	tinymce 	editor 					Editor Object.
									 * @param 	object 		autoCompleteContainer 	Autocomplete <ul> DOM Element.
									 */
									function showAutoComplete( editor, autoCompleteContainer ) {

										// Get caret position, so we can determine precisely where to show the autocomplete list.
										var caretPosition = getCaretPosition( editor );

										// Position autocomplete.
										positionAutoComplete( autoCompleteContainer, caretPosition.top, caretPosition.left );

										// Display autocomplete.
										autoCompleteContainer.classList.remove( 'displayed' );
										autoCompleteContainer.classList.add( 'displayed' );

										// Set flag that we're displaying autocomplete.
										autoCompleteDisplayed = true;

									}

									/**
									 * Get the top and left position of the caret in the active editor, relative
									 * to the browser window.
									 *
									 * @since 	2.0.2
									 *
									 * @param 	tinymce 	editor 		Editor Object.
									 * @return 	object 					Top and Left Position of Caret
									 */
									function getCaretPosition( editor ) {

										// Get the editor's container and toolbar elements.
										var editorContainer = ( editor.getContainer() ? editor.getContainer() : document.getElementById( editor.id ) );

										// Get the editor position within the browser window (top + left), and.
										var editorPositionWithinWindow = {
											top: editorContainer.getBoundingClientRect().top + window.scrollY,
											left: editorContainer.getBoundingClientRect().left + window.scrollX
										};

										var caretPositionWithinEditor = {
											top: 0,
											left: 0
										};

										// Get the caret position within the editor.
										if ( editor.selection.getRng().getClientRects().length > 0 ) {
											caretPositionWithinEditor = {
												top: editor.selection.getRng().getClientRects()[0].top + 20,
												left: editor.selection.getRng().getClientRects()[0].left
											};
										} else {
											caretPositionWithinEditor = {
												top: editor.selection.getNode().getClientRects()[0].top + 20,
												left: editor.selection.getNode().getClientRects()[0].left
											};
										}

										// Get toolbar.
										var editorToolbar = editorContainer.getElementsByClassName( 'mce-toolbar-grp' )[0];

										// Return position directly on and below the caret, factoring the toolbar position if the toolbar exists.
										if ( editorToolbar ) {
											return {
												top: editorPositionWithinWindow.top + editorToolbar.getBoundingClientRect().height + caretPositionWithinEditor.top,
												left: editorPositionWithinWindow.left + caretPositionWithinEditor.left
											}
										}

										return caretPositionWithinEditor;

									}

									/**
									 * Positions the autocomplete list to the given top and left position,
									 * relative to the browser window.
									 * The autocomplete list's CSS must define position:absolute.
									 *
									 * @since 	2.0.2
									 *
									 * @param 	object 		autoCompleteContainer 	Autocomplete <ul> DOM Element.
									 * @param 	int 		top 					Top Position, in pixels.
									 * @param 	int 		left 					Left Position, in pixels.
									 */
									function positionAutoComplete( autoCompleteContainer, top, left ) {

										autoCompleteContainer.style.marginTop  = top + 'px';
										autoCompleteContainer.style.marginLeft = left + 'px';

									}

									/**
									 * Fetches the search word, from the last occurance of the opening trigger relative
									 * to the current caret / cursor position within the editor..
									 *
									 * @since 	2.0.2
									 *
									 * @param 	tinymce 	editor 		Editor Object.
									 * @return 	string 					Search Word, including opening trigger
									 */
									function getSearchWord( editor ) {

										// Get the text from the editor, and the current caret position.
										var text          = ( editor.selection.getSel().focusNode == null ? "" : editor.selection.getSel().focusNode.nodeValue ),
											caretPosition = editor.selection.getSel().focusOffset,
											startPosition = 0;

										if ( text == null || text.length == 0 ) {
											return '';
										}

										// Go backwards from the current carat position to the start of the editor text, until we find
										// a trigger that opened the autocomplete list.
										for ( var i = caretPosition; i >= 0; i-- ) {
											// If this character matches a trigger that opened the autocomplete list, note the start
											// position of it and break the loop.
											if ( textToOpenAutoComplete.indexOf( text.charCodeAt( i ).toString() ) != -1 ) {
												startPosition = i;
												break;
											}
										}

										// Extract the search from the text between the opening trigger and the current caret position.
										var search = text.substr( startPosition, caretPosition - startPosition );

										// Return.
										return {
											search: search,
											start: startPosition,
											end: caretPosition
										}

									}

									/**
									 * Filters the autocomplete list based on the given search input.
									 *
									 * @since 	2.0.2
									 *
									 * @param 	tinymce 	editor 					Editor Object.
									 * @param 	object 		autoCompleteContainer 	Autocomplete <ul> DOM Element.
									 */
									function filterAutoComplete( search, editor, autoCompleteContainer ) {

										// Iterate through list, showing / hiding options based on whether they match
										// the given search text.
										var items     = autoCompleteContainer.getElementsByTagName( 'li' ),
											firstItem = true,
											length    = items.length;
										for ( var i = 0; i < ( length - 1 ); i++ ) {
											// Remove higlight class.
											items.item( i ).classList.remove( 'highlight' );

											if ( items.item( i ).innerText.indexOf( search.search ) == -1 ) {
												// Hide.
												items.item( i ).classList.remove( 'displayed' );
											} else {
												// Show.
												items.item( i ).classList.add( 'displayed' );

												// Highlight if this is the first autocomplete suggestion.
												if ( firstItem ) {
													items.item( i ).classList.add( 'highlight' );
													firstItem = false;
												}
											}
										}

									}

									/**
									 * Highlights the next or previous displayed list item in the autocomplete list.
									 *
									 * @since 	3.5.7
									 *
									 * @param 	string 		direction 				Item to higlight (next|previous).
									 * @param 	tinymce 	editor 					Editor Object.
									 * @param 	object 		autoCompleteContainer 	Autocomplete <ul> DOM Element.
									 */
									function changeAutoCompleteItemHighlighted( direction, editor, autoCompleteContainer ) {

										// Iterate through list of displayed items.
										var items  = autoCompleteContainer.querySelectorAll( 'li.displayed' ),
											length = items.length;
										for ( var i = 0; i < ( length - 1 ); i++ ) {
											// Skip if this item isn't highlighted.
											if ( ! items[ i ].classList.contains( 'highlight' ) ) {
												continue;
											}

											// Depending on the direction, add the highlight class to the previous or next item in the list.
											if ( direction == 'previous' ) {
												// If the first item is highlighted, do nothing, as we cannot highlight a previous item.
												if ( i == 0 ) {
													break;
												}

												// Change item that has highlight class.
												items[ i ].classList.remove( 'highlight' );
												items[ i - 1 ].classList.add( 'highlight' );
												break;
											}

											if ( direction == 'next' ) {
												// If the last item is highlighted, do nothing, as we cannot highlight a next item.
												if ( i == ( items.length - 1 ) ) {
													break;
												}

												// Change item that has highlight class.
												items[ i ].classList.remove( 'highlight' );
												items[ i + 1 ].classList.add( 'highlight' );
												break;
											}
										}

									}

									/**
									 * Insert the highlighted autocomplete suggestion from the list into the editor content.
									 *
									 * @since 	2.0.2
									 *
									 * @param 	tinymce 	editor 					Editor Object.
									 * @param 	object 		autoCompleteContainer 	Autocomplete <ul> DOM Element.
									 */
									function insertAutoCompleteItemHighlighted( editor, autoCompleteContainer ) {

										// Fetch highlighted autocomplete suggestion.
										var text = autoCompleteContainer.querySelectorAll( 'li.highlight' )[0].innerText;

										// Insert the text into the editor.
										insertAutoCompleteItemByText( text, editor, autoCompleteContainer );

									}

									/**
									 * Insert the text into the editor content.
									 *
									 * @since 	2.0.2
									 *
									 * @param 	string 		text 	Text.
									 * @param 	tinymce 	editor 	Editor Object.
									 */
									function insertAutoCompleteItemByText( text, editor ) {

										// Get search word, editor text and range.
										var search      = getSearchWord( editor ),
											editorText  = editor.selection.getSel().focusNode,
											editorRange = editor.selection.getRng();

										// Select the search word.
										editorRange.setStart( editorText, search.start );
										editorRange.setEnd( editorText, search.end );
										editor.selection.setRng( editorRange );

										// Insert autocomplete text into editor.
										// This replaces the selected search word.
										editor.selection.setContent( text );

									}

									/**
									 * Hides the autocomplete list, resetting anything within the list.
									 *
									 * @since 	2.0.2
									 *
									 * @param 	tinymce 	editor 					Editor Object.
									 * @param 	object 		autoCompleteContainer 	Autocomplete <ul> DOM Element.
									 */
									function hideAutoComplete( editor, autoCompleteContainer ) {

										// Hide autocomplete.
										autoCompleteContainer.classList.remove( 'displayed' );

										// Set flag that we're not displaying autocomplete.
										autoCompleteDisplayed = false;

									}

									/**
									 * Shows or hides the autocomplete list, depending on whether the user presses
									 * a character
									 *
									 * @since 	2.0.2
									 *
									 * @param 	tinymce 	editor 		Editor Instance.
									 * @param 	object 		event 		Event .
									 */
									function keyDownEvent( editor, event ) {

										// If the user types a character that will trigger opening autocomplete, and we're not
										// displaying autocomplete, show it now.
										if ( trigger.triggerKeyCode == event.keyCode && ! autoCompleteDisplayed ) {
											if ( trigger.triggerKeyShiftRequired ) {
												if ( event.shiftKey ) {
													showAutoComplete( editor, autoCompleteContainer );
													return;
												}
											} else {
												showAutoComplete( editor, autoCompleteContainer );
												return;
											}
										}

										// If the user types a character that will close the autocomplete, and we're
										// displaying autocomplete, hide it now.
										if ( ESC_KEY == event.keyCode && autoCompleteDisplayed ) {
											hideAutoComplete( editor, autoCompleteContainer );
											return;
										}

										// If autocomplete is displayed and the user pressed the up or down key, change the highlighted result
										// in the list.
										if ( autoCompleteDisplayed && ( UP_ARROW_KEY == event.keyCode || DOWN_ARROW_KEY == event.keyCode ) ) {
											var direction = ( UP_ARROW_KEY == event.keyCode ? 'previous' : 'next' );
											changeAutoCompleteItemHighlighted( direction, editor, autoCompleteContainer );
										}

										// If autocomplete is displayed and the user presses enter, add the first displayed result in the list
										// to the editor, and close the autocomplete list.
										if ( autoCompleteDisplayed && ENTER_KEY == event.keyCode ) {
											// Prevent the enter event propagating to the editor instance.
											tinymce.dom.Event.cancel( event );

											// Insert the highlighted autocomplete item into the editor.
											insertAutoCompleteItemHighlighted( editor, autoCompleteContainer );

											// Hide the autocomplete list.
											hideAutoComplete( editor, autoCompleteContainer );
											return;
										}

									}

									/**
									 * Filters the autocomplete list, if displayed, and the user types
									 *
									 * @since 	2.0.2
									 *
									 * @param 	tinymce 	editor 		Editor Instance.
									 * @param 	object 		event 		Event .
									 */
									function keyUpEvent( editor, event ) {

										// If autocomplete is displayed and the user hasn't typed a character that we're ignoring,
										// filter the results list.
										if ( keyBindsToIgnore.indexOf( event.keyCode ) == -1 && autoCompleteDisplayed ) {
											// Reposition the autocomplete list.
											showAutoComplete( editor, autoCompleteContainer );

											// Get search word from the opening trigger to the caret.
											var search = getSearchWord( editor );

											// Filter the autocomplete list based on the search term.
											filterAutoComplete( search, editor, autoCompleteContainer );
										}

									}

									/**
									 * Listens to all click events in the editor instance.
									 *
									 * @since 	2.0.2
									 *
									 * @param 	tinymce 	editor 		Editor Instance.
									 * @param 	object 		event 		Event .
									 */
									function clickEventInEditor( editor, event ) {

										// Hide the autocomplete list.
										hideAutoComplete( editor, autoCompleteContainer );

									}

									/**
									 * Listens to all click events in the DOM, outside of the editor instance.
									 *
									 * @since 	2.0.2
									 *
									 * @param 	object 		event 		Event .
									 */
									function clickEvent( event ) {

										// Hide the autocomplete list if the user clicked outside of it.
										if ( ! event.target.matches( 'li.displayed' ) ) {
											hideAutoComplete( editor, autoCompleteContainer );
											return;
										}

										// If here, the user clicked an autocomplete list item.
										// Insert that item's text into the editor.
										insertAutoCompleteItemByText( event.target.innerText, editor );

										// Hide the autocomplete list.
										hideAutoComplete( editor, autoCompleteContainer );

									}

									// Bind events to TinyMCE Editor instance.
									editor.onKeyDown.add( keyUpEvent );
									editor.onKeyDown.add( keyDownEvent );
									editor.onClick.add( clickEventInEditor );

									// Bind events to DOM (outside editor).
									document.addEventListener( 'click', clickEvent );

								},

								getInfo: function () {
									return {
										longname: 	'Autocomplete',
										author: 	'WP Zinc',
										version: 	tinymce.majorVersion + '.' + tinymce.minorVersion
									};
								}

							}
						);

						// Add TinyMCE Plugin.
						tinymce.PluginManager.add( trigger.tinyMCEName, tinymce.plugins[ trigger.tinyMCEName ] );

					}
				);

			}
		);

	}

} )();
