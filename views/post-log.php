<?php
/**
 * Outputs the Logs table when viewing/editing an individual Post.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 */

?>
<div class="wpzinc-option">
	<div class="full">
		<table class="widefat wp-to-social-log">
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
						esc_html_e( '%s: Status Scheduled For', 'social-post-flow' );
						?>
					</th>
				</tr>
			</thead>
			<tbody>
				<?php
				echo social_post_flow()->get_class( 'log' )->build_log_table_output( $log ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</tbody>
		</table>
	</div>
</div>
<div class="wpzinc-option">
	<div class="full">
		<a href="post.php?post=<?php echo esc_attr( $post->ID ); ?>&action=edit&social-post-flow-refresh-log=1" class="social-post-flow-refresh-log button" data-action="social_post_flow_get_log" data-target="#social-post-flow-log">
			<?php esc_html_e( 'Refresh Log', 'social-post-flow' ); ?>
		</a>
		<a href="post.php?post=<?php echo esc_attr( $post->ID ); ?>&action=edit&social-post-flow-export-log=1" class="social-post-flow-export-log button">
			<?php esc_html_e( 'Export Log', 'social-post-flow' ); ?>
		</a>
		<a href="post.php?post=<?php echo esc_attr( $post->ID ); ?>&action=edit&social-post-flow-clear-log=1" class="social-post-flow-clear-log button wpzinc-button-red" data-action="social_post_flow_clear_log" data-target="#social-post-flow-log" data-message="<?php esc_attr_e( 'Are you sure you want to clear the logs associated with this Post?', 'social-post-flow' ); ?>">
			<?php esc_html_e( 'Clear Log', 'social-post-flow' ); ?>
		</a>
	</div>
</div>
