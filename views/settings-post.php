<?php
/**
 * Outputs Settings View for a Post Type
 *
 * @since    1.0.0
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<!-- Post Type -->
<div class="postbox wpzinc-vertical-tabbed-ui">

	<!-- Profile Tabs -->
	<ul class="wpzinc-nav-tabs wpzinc-js-tabs" data-panels-container="#profiles-container" data-panel=".profile" data-active="wpzinc-nav-tab-vertical-active">
		<!-- Default Settings -->
		<li class="wpzinc-nav-tab default">
			<a href="#profile-default" class="wpzinc-nav-tab-vertical-active">
				<?php esc_html_e( 'Defaults', 'social-post-flow' ); ?>
			</a>
		</li>

		<?php
		// Account tabs.
		if ( ! is_wp_error( $profiles ) ) {
			foreach ( $profiles as $key => $profile ) {
				$profile_enabled = $this->get_setting( $post_type, '[' . $profile['id'] . '][enabled]', 0 );

				// Show tick only when profile is connected and enabled in the plugin.
				// Show warning whenever the profile is not connected, regardless of enabled state.
				$link_classes = array();
				if ( $profile['connected'] && $profile_enabled ) {
					$link_classes[] = 'enabled';
				}
				if ( ! $profile['connected'] ) {
					$link_classes[] = 'error';
				}
				?>
				<li class="wpzinc-nav-tab <?php echo esc_attr( $profile['provider'] ); ?>">
					<a href="#profile-<?php echo esc_attr( $profile['id'] ); ?>"<?php echo ( ! empty( $link_classes ) ? ' class="' . esc_attr( implode( ' ', $link_classes ) ) . '"' : '' ); ?> title="<?php echo esc_attr( $profile['provider_name'] . ': ' . $profile['profile_name'] ); ?>">
						<span class="formatted-username" data-text="<?php echo esc_attr( $profile['profile_name'] ); ?>"><?php echo esc_html( $profile['profile_name'] ); ?></span>
						<?php if ( ! $profile['connected'] ) { ?>
							<span class="dashicons dashicons-warning"></span>
						<?php } else { ?>
							<span class="dashicons dashicons-yes"></span>
						<?php } ?>
					</a>
				</li>
				<?php

			}
		}
		unset( $profile );
		?>

		<!-- Add Profiles -->
		<li class="wpzinc-nav-tab external-link">
			<a href="<?php echo esc_url( social_post_flow()->get_class( 'api' )->get_connect_profiles_url() ); ?>" target="_blank" class="wpzinc-nav-tab-vertical-active">
				<?php esc_html_e( 'Add Profiles', 'social-post-flow' ); ?>
			</a>
		</li>
	</ul>

	<!-- Content -->
	<div id="profiles-container" class="wpzinc-nav-tabs-content">
		<!-- Defaults -->
		<?php
		$profile_id = 'default';
		?>
		<div id="profile-<?php echo esc_attr( $profile_id ); ?>" class="profile">
			<!-- Action Tabs -->
			<ul class="wpzinc-nav-tabs-horizontal wpzinc-js-tabs" data-panels-container="#profile-<?php echo esc_attr( $profile_id ); ?>-actions-container" data-panel=".action" data-active="wpzinc-nav-tab-horizontal-active">
				<?php
				foreach ( $post_actions as $post_action => $action_label ) {
					$action_enabled = $this->get_setting( $post_type, '[' . $profile_id . '][' . $post_action . '][enabled]', 0 );
					?>
					<li class="wpzinc-nav-tab-horizontal <?php echo esc_attr( $post_action ); ?>">
						<a href="#profile-<?php echo esc_attr( $profile_id ); ?>-<?php echo esc_attr( $post_action ); ?>" class="<?php echo esc_attr( $action_enabled ? ' enabled' : '' ) . ( $post_action === 'publish' ? ' wpzinc-nav-tab-horizontal-active' : '' ); ?>">
							<?php
							echo esc_html( $action_label );
							?>
							<span class="dashicons dashicons-yes"></span>
						</a>
					</li>
					<?php
				}
				?>
			</ul>

			<div id="profile-<?php echo esc_attr( $profile_id ); ?>-actions-container">
				<?php
				// Iterate through Post Actions (Publish, Update etc).
				foreach ( $post_actions as $post_action => $action_label ) {
					?>
					<div id="profile-<?php echo esc_attr( $profile_id ); ?>-<?php echo esc_attr( $post_action ); ?>" class="action">
						<?php
						require SOCIAL_POST_FLOW_PLUGIN_PATH . 'views/settings-post-action.php';
						?>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<!-- /Defaults -->

		<!-- Profiles -->
		<?php
		if ( is_wp_error( $profiles ) ) {
			?>
			<div>
				<?php esc_html_e( 'Hmm, we couldn\'t fetch your social media profiles.  Please refresh the Page.', 'social-post-flow' ); ?>
			</div>
			<?php
		} else {
			foreach ( $profiles as $key => $profile ) {
				$profile_id = $profile['id'];
				?>
				<div id="profile-<?php echo esc_attr( $profile_id ); ?>" class="profile <?php echo esc_attr( $profile['provider'] ); ?>">
					<?php
					require SOCIAL_POST_FLOW_PLUGIN_PATH . 'views/settings-post-actionheader.php';
					?>

					<div id="<?php echo esc_attr( $post_type ); ?>-<?php echo esc_attr( $profile_id ); ?>-actions-panel">
						<!-- Action Tabs -->
						<ul class="wpzinc-nav-tabs-horizontal wpzinc-js-tabs" data-panels-container="#profile-<?php echo esc_attr( $profile_id ); ?>-actions-container" data-panel=".action" data-active="wpzinc-nav-tab-horizontal-active">
							<?php
							foreach ( $post_actions as $post_action => $action_label ) {
								$action_enabled = $this->get_setting( $post_type, '[' . $profile_id . '][' . $post_action . '][enabled]', 0 );
								?>
								<li class="wpzinc-nav-tab-horizontal <?php echo esc_attr( $post_action ); ?>">
									<a href="#profile-<?php echo esc_attr( $profile_id ); ?>-<?php echo esc_attr( $post_action ); ?>" class="<?php echo esc_attr( $action_enabled ? ' enabled' : '' ) . ( $post_action === 'publish' ? ' wpzinc-nav-tab-horizontal-active' : '' ); ?>">
										<?php
										echo esc_html( $action_label );
										?>
										<span class="dashicons dashicons-yes"></span>
									</a>
								</li>
								<?php
							}
							?>
						</ul>

						<div id="profile-<?php echo esc_attr( $profile_id ); ?>-actions-container">
							<?php
							// Iterate through Post Actions (Publish, Update etc).
							foreach ( $post_actions as $post_action => $action_label ) {
								?>
								<div id="profile-<?php echo esc_attr( $profile_id ); ?>-<?php echo esc_attr( $post_action ); ?>" class="action">
									<?php
									require SOCIAL_POST_FLOW_PLUGIN_PATH . 'views/settings-post-action.php';
									?>
								</div>
								<?php
							}
							?>
						</div>
					</div>
				</div>
				<?php
			}
		}
		?>
		<!-- /Profiles -->

		<!-- Status Editor -->
		<?php
		require SOCIAL_POST_FLOW_PLUGIN_PATH . 'views/settings-post-action-status.php';
		?>

		<!-- Submitted Form Data -->
		<input type="hidden" name="social-post-flow[statuses]" value='<?php echo wp_json_encode( $original_statuses, JSON_HEX_APOS ); ?>' />
	</div>
</div>
<!-- /post_type -->
