/**
 * Review notice functionality.
 *
 * @package WPZincDashboardWidget
 * @author WP Zinc
 */

jQuery( document ).ready(
	function ( $ ) {

		// Dismiss Review Notification.
		$( 'div.wpzinc-review-' + wpzinc_admin_review_notice.plugin_name ).on(
			'click',
			'a, button.notice-dismiss',
			function ( e ) {

				// Do request.
				$.post(
					ajaxurl,
					{
						action: wpzinc_admin_review_notice.action,
						nonce: wpzinc_admin_review_notice.nonce,
					},
					function ( response ) {
					}
				);

				// Hide notice.
				$( 'div.wpzinc-review-' + wpzinc_admin_review_notice.plugin_name ).hide();

			}
		);
	}
);
