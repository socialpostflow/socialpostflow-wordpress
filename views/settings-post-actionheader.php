<?php
/**
 * Outputs status settings when editing a Post.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 */

?>
<!-- Action Header -->
<div class="postbox">
	<header>
		<h3>
			<?php
			echo esc_html(
				sprintf(
				/* translators: %1$s: Social Media Service (Facebook, Twitter etc.), %2$s: Social Media Profile Name */
					__( '%1$s: %2$s: Settings', 'social-post-flow' ),
					$profile['provider'],
					$profile['profile_name']
				)
			);
			?>
		</h3>

		<?php
		// If this Profile's timezone doesn't match WordPress' timezone, show a warning.
		if ( isset( $profile['timezone'] ) ) {
			$timezones_match = social_post_flow()->get_class( 'validation' )->timezones_match(
				$profile['timezone'],
				$profile['profile_name'],
				social_post_flow()->get_class( 'api' )->get_timezone_settings_url( $profile['id'] )
			);
			if ( is_wp_error( $timezones_match ) ) {
				?>
				<div class="notice-inline notice-warning">
					<p>
						<?php echo $timezones_match->get_error_message(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</p>
				</div>
				<?php
			}
		}
		?>
	</header>

	<!-- Account Enabled -->
	<div class="wpzinc-option">        
		<div class="left">
			<label for="<?php echo esc_attr( $profile_id ); ?>_enabled"><?php esc_html_e( 'Account Enabled', 'social-post-flow' ); ?></label>
		</div>
		<div class="right">
			<input type="checkbox" id="<?php echo esc_attr( $profile_id ); ?>_enabled" class="enable" name="social-post-flow[<?php echo esc_attr( $profile_id ); ?>][enabled]" id="<?php echo esc_attr( $profile_id ); ?>_enabled" value="1"<?php checked( $this->get_setting( $post_type, '[' . $profile_id . '][enabled]', 0 ), 1, true ); ?> data-tab="profile-<?php echo esc_attr( $profile_id ); ?>" />
			<p class="description"><?php esc_html_e( 'Enabling this social media account means that Posts will be sent to this social media account, if the conditions in the Settings are met.', 'social-post-flow' ); ?></p>
		</div>
	</div>
	<?php
	// Force override if a subprofile is required.
	$override = $this->get_setting( $post_type, '[' . $profile_id . '][override]', 0 );
	$disabled = false;
	if ( isset( $profile['provider'] ) && $profile['provider'] === 'pinterest' ) {
		if ( ! isset( $profile['can_be_subprofile'] ) || ! $profile['can_be_subprofile'] ) {
			// Subprofile is required.
			$override = 1;
			$disabled = true;
		}
	}

	// If Override is Disabled, store the value in a hidden field.
	if ( $disabled ) {
		?>
		<input type="hidden" name="social-post-flow[<?php echo esc_attr( $profile_id ); ?>][override]" value="<?php echo esc_attr( $override ); ?>" data-conditional="<?php echo esc_attr( $post_type ); ?>-<?php echo esc_attr( $profile_id ); ?>-actions-panel" />
		<?php
	} else {
		?>
		<!-- Override Default Settings -->
		<div class="wpzinc-option">
			<div class="left">
				<label for="<?php echo esc_attr( $profile_id ); ?>_override"><?php esc_html_e( 'Override Defaults', 'social-post-flow' ); ?></label>
			</div>
			<div class="right">
				<input type="checkbox" class="override" id="<?php echo esc_attr( $profile_id ); ?>_override" name="social-post-flow[<?php echo esc_attr( $profile_id ); ?>][override]" value="1"<?php checked( $override, 1, true ); ?> data-conditional="<?php echo esc_attr( $post_type ); ?>-<?php echo esc_attr( $profile_id ); ?>-actions-panel" />
				<p class="description"><?php esc_html_e( 'Check this box to define custom settings when publishing or updating to this social media account. Not checking this box will mean that this social media account uses settings from the "Defaults" tab', 'social-post-flow' ); ?></p>
			</div>
		</div>
		<?php
	}
	?>
</div>
