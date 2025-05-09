<?php
/**
 * Outputs settings for a specific profile and action.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 */

?>
<!-- Action -->
<div id="profile-<?php echo esc_attr( $profile_id ); ?>-<?php echo esc_attr( $post_action ); ?>" class="postbox">
	<header>
		<h3>
			<?php
			if ( $profile_id === 'default' ) {
				echo esc_html(
					sprintf(
					/* translators: Translated Action (Publish, Update, Repost, Bulk Publish) */
						__( 'Defaults: ', 'social-post-flow' ),
						$action_label
					)
				);
			} else {
				echo esc_html( sprintf( '%s: %s: %s', $profile['formatted_service'], $profile['formatted_username'], $action_label ) );
			}
			?>

			<label for="<?php echo esc_attr( $profile_id ); ?>_<?php echo esc_attr( $post_action ); ?>_enabled">
				<input type="checkbox" id="<?php echo esc_attr( $profile_id ); ?>_<?php echo esc_attr( $post_action ); ?>_enabled" class="enable" name="social-post-flow[<?php echo esc_attr( $profile_id ); ?>][<?php echo esc_attr( $post_action ); ?>][enabled]" value="1"<?php checked( $this->get_setting( $post_type, '[' . $profile_id . '][' . $post_action . '][enabled]', 0 ), 1, true ); ?> data-tab="profile-<?php echo esc_attr( $profile_id ); ?>-<?php echo esc_attr( $post_action ); ?>" data-conditional="<?php echo esc_attr( $post_type ); ?>-<?php echo esc_attr( $profile_id ); ?>-<?php echo esc_attr( $post_action ); ?>-statuses" />
				<?php esc_html_e( 'Enabled', 'social-post-flow' ); ?>
			</label>
		</h3>

		<p class="description">
			<?php
			echo esc_html(
				sprintf(
				/* translators: %1$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %2$s: Post Type, Singular, %3$s: Translated Action (Publish, Update, Repost, Bulk Publish), %4$s: Additional Translated Message */
					__( 'If enabled, any status(es) defined here will be sent to %1$s when a WordPress %2$s is %3$s %4$s', 'social-post-flow' ),
					$this->base->plugin->account,
					$post_type_object->labels->singular_name,
					strtolower( $actions_plural[ $post_action ] ),
					( $profile_id === 'default' ? '' : sprintf(
					/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
						__( 'to %s. These override the status(es) specified on the Defaults tab.', 'social-post-flow' ),
						$profile['formatted_username']
					) )
				)
			);
			?>
		</p>
	</header>

	<div id="<?php echo esc_attr( $post_type ); ?>-<?php echo esc_attr( $profile_id ); ?>-<?php echo esc_attr( $post_action ); ?>-statuses" class="statuses" data-profile-id="<?php echo esc_attr( $profile_id ); ?>" data-profile='<?php echo ( isset( $profile ) ? wp_json_encode( $profile, JSON_HEX_APOS ) : '' ); ?>' data-action="<?php echo esc_attr( $post_action ); ?>">
		<div class="wpzinc-option">
			<div class="full">
				<table class="widefat striped">
					<thead>
						<tr>
							<th>&nbsp;</th>
							<th><?php esc_html_e( 'Actions', 'social-post-flow' ); ?></th>
							<th><?php esc_html_e( 'Text', 'social-post-flow' ); ?></th>
							<th><?php esc_html_e( 'Image', 'social-post-flow' ); ?></th>
							<th><?php esc_html_e( 'Schedule', 'social-post-flow' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						// Fetch Publish / Update / Repost Statuses.
						$statuses = $this->get_setting( $post_type, '[' . $profile_id . '][' . $post_action . '][status]' );

						if ( ! is_array( $statuses ) || ! count( $statuses ) ) {
							// Define default status.
							$key    = 0;
							$status = social_post_flow()->get_class( 'settings' )->get_default_status( $post_type, false, 'queue_end' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							$labels = array();
							$row    = social_post_flow()->get_class( 'settings' )->get_status_row( $status, $post_type, $post_action );

							// Load sub view.
							require SOCIAL_POST_FLOW_PLUGIN_PATH . 'lib/views/settings-post-action-status-row.php';
						} else {
							// Iterate through saved statuses.
							foreach ( $statuses as $key => $status ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
								$status = social_post_flow()->get_class( 'settings' )->get_status( $status, $post_type ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
								$labels = social_post_flow()->get_class( 'settings' )->get_status_value_labels( $status, $post_type );
								$row    = social_post_flow()->get_class( 'settings' )->get_status_row( $status, $post_type, $post_action );

								// Load sub view.
								require SOCIAL_POST_FLOW_PLUGIN_PATH . 'lib/views/settings-post-action-status-row.php';
							}
						}
						?>
						<tr class="hidden status-form-container"><td colspan="6"></td></tr>
					</tbody>
				</table>
			</div>
		</div>

		<?php
		// Upgrade Notice.
		if ( class_exists( 'WP_To_Buffer' ) || class_exists( 'WP_To_Hootsuite' ) || class_exists( 'WP_To_SocialPilot' ) ) {
			if ( $post_action === 'publish' ) {
				require SOCIAL_POST_FLOW_PLUGIN_PATH . 'lib/views/settings-post-action-status-upgrade.php';
			}
		} else {
			?>
			<div class="wpzinc-option last">
				<a href="#" class="button add-status" data-status-index="<?php echo esc_attr( $key ); ?>"><?php esc_html_e( 'Add Status Update', 'social-post-flow' ); ?></a>
			</div>
			<?php
		}
		?>
	</div>
</div>
