<?php
/**
 * Outputs the settings screen.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 */

?>
<header>
	<h1>
		<?php echo esc_html_e( 'Social Post Flow', 'social-post-flow' ); ?>

		<span>
			<?php esc_html_e( 'Settings', 'social-post-flow' ); ?>
		</span>
	</h1>
</header>

<div class="wrap">
	<?php
	// Output notices.
	social_post_flow()->get_class( 'notices' )->set_key_prefix( 'social_post_flow_' . wp_get_current_user()->ID );
	social_post_flow()->get_class( 'notices' )->output_notices();

	// Get access token.
	$api_key = social_post_flow()->get_class( 'settings' )->get_api_key();
	?>

	<!-- Container for JS notices -->
	<div class="js-notices"></div>

	<div class="wrap-inner">
		<!-- Notices -->
		<hr class="wp-header-end" />

		<!-- Tabs -->
		<h2 class="nav-tab-wrapper wpzinc-horizontal-tabbed-ui">
			<!-- Settings -->
			<a href="admin.php?page=social-post-flow-settings" class="nav-tab<?php echo esc_attr( $tab === 'auth' ? ' nav-tab-active' : '' ) . ( ! empty( $api_key ) ? ' enabled' : ' error' ); ?>" title="<?php esc_attr_e( 'Settings', 'social-post-flow' ); ?>">
				<span class="dashicons dashicons-lock"></span> 
				<?php
				if ( ! empty( $api_key ) ) {
					?>
					<span class="dashicons dashicons-yes"></span>
					<?php
				} else {
					?>
					<span class="dashicons dashicons-warning"></span>
					<?php
				}
				?>
				<span class="text">
					<?php esc_html_e( 'Settings', 'social-post-flow' ); ?>
				</span>
			</a>

			<!-- Public Post Types -->
			<?php
			// Go through all Post Types, if authenticated.
			if ( ! empty( $api_key ) ) {
				foreach ( $post_types as $public_post_type => $post_type_obj ) {
					// Work out the icon to display.
					$icon = '';
					if ( ! empty( $post_type_obj->menu_icon ) ) {
						$icon = 'dashicons ' . $post_type_obj->menu_icon;
					} elseif ( $public_post_type === 'post' || $public_post_type === 'page' ) {
							$icon = 'dashicons dashicons-admin-' . $public_post_type;
					}

					// Determine if the Post Type is set to post.
					$is_post_type_enabled = social_post_flow()->get_class( 'settings' )->is_post_type_enabled( $public_post_type );
					?>
					<a href="admin.php?page=social-post-flow-settings&amp;tab=post&amp;type=<?php echo esc_attr( $public_post_type ); ?>" class="nav-tab<?php echo esc_attr( $post_type === $public_post_type ? ' nav-tab-active' : '' ) . ( $is_post_type_enabled ? ' enabled' : '' ); ?>" title="<?php echo esc_attr( $post_type_obj->labels->name ); ?>" data-post-type="<?php echo esc_attr( $public_post_type ); ?>">
						<span class="<?php echo esc_attr( $icon ); ?>"></span>
						<span class="dashicons dashicons-yes"></span>
						<span class="text">
							<?php echo esc_attr( $post_type_obj->labels->name ); ?>
						</span>
					</a>
					<?php
				}
			}
			?>

			<!-- Documentation -->
			<a href="<?php echo esc_attr( $documentation_url ); ?>" class="nav-tab last documentation" title="<?php esc_html_e( 'Documentation', 'social-post-flow' ); ?>" target="_blank">
				<span class="text">
					<?php esc_html_e( 'Documentation', 'social-post-flow' ); ?>
				</span>
				<span class="text-mobile">
					<?php esc_html_e( 'Docs', 'social-post-flow' ); ?>
				</span>
				<span class="dashicons dashicons-admin-page"></span>
			</a>
		</h2>

		<!-- Form Start -->
		<form name="post" method="post" action="<?php echo ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ); ?>" id="social-post-flow">      
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-1">
					<!-- Content -->
					<div id="post-body-content">
						<div id="normal-sortables" class="meta-box-sortables ui-sortable publishing-defaults">  
							<?php
							// Load sub view.
							require_once SOCIAL_POST_FLOW_PLUGIN_PATH . 'views/settings-' . $tab . '.php';
							?>
						</div>
						<!-- /normal-sortables -->

						<?php
						if ( ! $disable_save_button ) {
							?>
							<!-- Save -->
							<div>
								<?php wp_nonce_field( 'social-post-flow', 'social_post_flow_nonce' ); ?>
								<input type="submit" name="submit" value="<?php esc_attr_e( 'Save', 'social-post-flow' ); ?>" class="button button-primary" />
							</div>
							<?php
						}
						?>
					</div>
					<!-- /post-body-content -->
				</div>
			</div> 
		</form>
		<!-- /form end -->		
	</div><!-- ./wrap-inner -->         
</div>
