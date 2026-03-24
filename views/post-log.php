<?php
/**
 * Outputs the Logs table when viewing/editing an individual Post.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="wpzinc-option">
	<div class="full">
		<table class="widefat social-post-flow-log">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Request Sent', 'social-post-flow' ); ?></th>
					<th><?php esc_html_e( 'Action', 'social-post-flow' ); ?></th>
					<th><?php esc_html_e( 'Profile', 'social-post-flow' ); ?></th>
					<th><?php esc_html_e( 'Status Text', 'social-post-flow' ); ?></th>
					<th><?php esc_html_e( 'Result', 'social-post-flow' ); ?></th>
					<th><?php esc_html_e( 'Response', 'social-post-flow' ); ?></th>
					<th><?php esc_html_e( 'Social Post Flow: Status Created At', 'social-post-flow' ); ?></th>
					<th><?php esc_html_e( 'Social Post Flow: Status Scheduled For', 'social-post-flow' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				echo wp_kses(
					social_post_flow()->get_class( 'log' )->build_log_table_output( $log ),
					array(
						'tr' => array(
							'class' => array(),
						),
						'td' => array(
							'class'   => array(),
							'colspan' => array(),
						),
						'a'  => array(
							'href'   => array(),
							'target' => array(),
						),
						'br' => array(),
					)
				);
				?>
			</tbody>
		</table>
	</div>
</div>
<div class="wpzinc-option">
	<div class="full">
		<a href="<?php echo esc_attr( $urls['refresh'] ); ?>" class="social-post-flow-refresh-log button" data-action="social_post_flow_get_log" data-target="#social-post-flow-log">
			<?php esc_html_e( 'Refresh Log', 'social-post-flow' ); ?>
		</a>
		<a href="<?php echo esc_attr( $urls['export'] ); ?>" class="social-post-flow-export-log button<?php echo ( ! count( $log ) ? ' hidden' : '' ); ?>">
			<?php esc_html_e( 'Export Log', 'social-post-flow' ); ?>
		</a>
		<a href="<?php echo esc_attr( $urls['clear'] ); ?>" class="social-post-flow-clear-log button wpzinc-button-red<?php echo ( ! count( $log ) ? ' hidden' : '' ); ?>" data-action="social_post_flow_clear_log" data-target="#social-post-flow-log" data-message="<?php esc_attr_e( 'Are you sure you want to clear the logs associated with this Post?', 'social-post-flow' ); ?>">
			<?php esc_html_e( 'Clear Log', 'social-post-flow' ); ?>
		</a>
	</div>
</div>
