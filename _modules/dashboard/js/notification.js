/**
 * Inline notification functionality.
 *
 * @package WPZincDashboardWidget
 * @author WP Zinc
 */

/**
 * Show a success message.
 *
 * @since 	1.0.0
 *
 * @param 	string 	message 	Success Message to display.
 */
function wpzinc_notification_show_success_message( message ) {

	wpzinc_notification_show( message, 'success' );

}

/**
 * Show a warning message.
 *
 * @since 	1.0.0
 *
 * @param 	string 	message 	Warning Message to display.
 */
function wpzinc_notification_show_warning_message( message ) {

	wpzinc_notification_show( message, 'warning' );

}

/**
 * Show an error message.
 *
 * @since 	1.0.0
 *
 * @param 	string 	message 	Error Message to display.
 */
function wpzinc_notification_show_error_message( message ) {

	wpzinc_notification_show( message, 'error' );

}

/**
 * Show a message.
 *
 * @since 	1.0.0
 *
 * @param 	string 	message 	Message to display.
 * @param 	string 	type 		Message Type (success,warning,error)
 */
function wpzinc_notification_show( message, type ) {

	jQuery( '.wpzinc-notification' ).text( message );
	jQuery( '.wpzinc-notification' ).addClass( 'wpzinc-notification-' + type );
	jQuery( '.wpzinc-notification' ).fadeIn( 'fast' );

	setTimeout(
		function () {
			jQuery( '.wpzinc-notification' ).fadeOut(
				'fast',
				function () {
					jQuery( '.wpzinc-notification' ).removeClass( 'wpzinc-notification-' + type );
					jQuery( '.wpzinc-notification' ).hide();
				}
			);
		},
		2000
	);

}
