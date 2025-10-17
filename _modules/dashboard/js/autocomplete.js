/**
 * Creates tribute.js autocompleters.
 *
 * @package WPZincDashboardWidget
 * @author WP Zinc
 */

var wpzinc_autocompleters         = [];
var wpzinc_autocomplete_listeners = new Map();

/**
 * Sets up tribute.js autocompleters based on the
 * configuration stored in the localized wpzinc_autocomplete
 * global variable.
 *
 * Once setup, wp_zinc_autocomplete_initialize() can be used to initialize
 * them. They don't need to be setup again.
 *
 * @since 	1.0.0
 */
function wp_zinc_autocomplete_setup() {

	wpzinc_autocompleters = [];

	wpzinc_autocomplete.forEach(
		function ( autocompleter, i ) {

			// Build collection.
			var collection = [];
			autocompleter.triggers.forEach(
				function ( trigger, j ) {
					// Don't include the opening trigger in the return value when selected.
					// This prevents e.g. {{something} when { is the trigger.
					trigger.selectTemplate = function ( item ) {
						return item.original.value;
					};

					// Check where values are sourced from for this trigger.
					if ( 'url' in trigger ) {
						// Configure remote datasource.
						trigger.values = function ( text, cb ) {

							// Build form data.
							data = new FormData();
							data.append( 'action', trigger.action );
							data.append( 'nonce', trigger.nonce );
							data.append( 'search', text );

							// Send AJAX request.
							fetch(
								trigger.url,
								{
									method: trigger.method,
									credentials: 'same-origin',
									body: data
								}
							).then(
								function ( response ) {
									return response.json();
								}
							).then(
								function ( result ) {
									cb( result.data );
								}
							).catch(
								function ( error ) {
									console.error( error );
								}
							);

						}
					}

					// Add to collection.
					collection.push( trigger );
				}
			);

			// Initialize autocompleter.
			var tribute = new Tribute(
				{
					collection: collection
				}
			);

			// Store in array.
			wpzinc_autocompleters.push(
				{
					fields: autocompleter.fields,
					instance: tribute
				}
			);

		}
	);

}

/**
 * Attaches all registered tribute.js autocompleters.
 *
 * @since 	1.0.0
 *
 * @param 	string  container   Only attach within the given container element.
 */
function wp_zinc_autocomplete_initialize( container ) {

	wpzinc_autocompleters.forEach(
		function ( autocompleter, i ) {

			autocompleter.fields.forEach(
				function ( field, j ) {

					// If a container is supplied, only attach the autocompleter to the field within
					// the container.
					if ( typeof container !== 'undefined' ) {
						field = container + ' ' + field;
					}

					const elements = document.querySelectorAll( field );

					// Attach Tribute to the elements.
					autocompleter.instance.attach( elements );

					// Add event listener handlers to each attached element
					// which triggers a change event when an autocomplete suggestion
					// is inserted to the field.
					// This ensures page builders e.g. Elementor update their underlying
					// model and reflect the input value's change.
					elements.forEach(
						function (el) {
							const handler = function (e) {
								if (typeof jQuery !== 'undefined') {
									jQuery( e.target ).trigger( 'input' );
								}
							};

							el.addEventListener( 'tribute-replaced', handler );

							// Store the handler for cleanup.
							wpzinc_autocomplete_listeners.set( el, handler );
						}
					);

				}
			);

		}
	);

}

/**
 * Detaches all tribute.js autocompleters
 *
 * @since 	1.0.0
 */
function wp_zinc_autocomplete_destroy() {

	wpzinc_autocompleters.forEach(
		function ( autocompleter, i ) {

			autocompleter.fields.forEach(
				function ( field, j ) {

					const elements = document.querySelectorAll( field );

					// Detach Tribute.
					autocompleter.instance.detach( elements );

					// Remove our event listeners that might have been registered earlier.
					elements.forEach(
						function (el) {
							const handler = wpzinc_autocomplete_listeners.get( el );
							if (handler) {
								el.removeEventListener( 'tribute-replaced', handler );
								wpzinc_autocomplete_listeners.delete( el );
							}
						}
					);

				}
			);

		}
	);

	wpzinc_autocompleters = [];

}

// Setup and initialize.
wp_zinc_autocomplete_setup();
wp_zinc_autocomplete_initialize();
