<?php
/**
 * Outputs Bulk Publish View, Stage 2, for a Post Type
 *
 * @since 1.0.0
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="postbox">
	<h3 class="hndle">
		<?php esc_html_e( 'Publishing...', 'social-post-flow' ); ?>
	</h3>

	<div class="wpzinc-option progressbar">
		<div class="full">
			<p>
				<?php
				esc_html_e( 'Please be patient while Post(s) are published to Social Post Flow. This can take a while if your server is slow (inexpensive hosting), or if you have chosen a lot of Posts and Statuses. Do not navigate away from this page until this script is done or all statuses will not be sent to Social Post Flow. You will be notified via this page when the process is completed.', 'social-post-flow' );
				?>
			</p>

			<!-- Progress Bar -->
			<div id="progress-bar"></div>
		</div>
	</div>

	<!-- Log -->	
	<div id="social-post-flow-log">
		<div class="inside">
			<div class="wpzinc-option">
				<div class="full">
					<table id="log" class="widefat social-post-flow-log">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Request Sent', 'social-post-flow' ); ?></th>
								<th><?php esc_html_e( 'Action', 'social-post-flow' ); ?></th>
								<th><?php esc_html_e( 'Profile', 'social-post-flow' ); ?></th>
								<th><?php esc_html_e( 'Status Text', 'social-post-flow' ); ?></th>
								<th><?php esc_html_e( 'Result', 'social-post-flow' ); ?></th>
								<th><?php esc_html_e( 'Response', 'social-post-flow' ); ?></th>
								<th>
									<?php
									esc_html_e( 'Social Post Flow: Status Created At', 'social-post-flow' );
									?>
								</th>
								<th>
									<?php
									esc_html_e( 'Social Post Flow: Status Scheduled For', 'social-post-flow' );
									?>
								</th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
