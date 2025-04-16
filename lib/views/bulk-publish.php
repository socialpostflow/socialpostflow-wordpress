<?php
/**
 * Outputs Bulk Publish View
 *
 * @since 3.0.5
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 */

?>
<header>
	<h1>
		<?php echo esc_html( $this->base->plugin->displayName ); ?>

		<span>
			<?php esc_html_e( 'Bulk Publish', 'social-post-flow' ); ?>
		</span>
	</h1>
</header>

<hr class="wp-header-end" />

<div class="wrap">
	<?php
	// Output notices.
	social_post_flow()->get_class( 'notices' )->set_key_prefix( 'social_post_flow_' . wp_get_current_user()->ID );
	social_post_flow()->get_class( 'notices' )->output_notices();
	?>

	<!-- Container for JS notices -->
	<div class="js-notices"></div>

	<div class="wrap-inner">
		<!-- Tabs -->
		<h2 class="nav-tab-wrapper wpzinc-horizontal-tabbed-ui">
			<?php
			// Go through all Post Types, if API is authenticated.
			$access_token = $this->get_setting( '', 'access_token' );
			if ( ! empty( $access_token ) ) {
				foreach ( $post_types as $public_post_type => $post_type_obj ) {
					// Work out the icon to display.
					$icon = '';
					if ( ! empty( $post_type_obj->menu_icon ) ) {
						$icon = 'dashicons ' . $post_type_obj->menu_icon;
					} elseif ( $public_post_type === 'post' || $public_post_type === 'page' ) {
							$icon = 'dashicons dashicons-admin-' . $public_post_type;
					}
					?>
					<a href="admin.php?page=<?php echo esc_attr( 'social-post-flow' ); ?>-bulk-publish&amp;tab=post&amp;type=<?php echo esc_attr( $public_post_type ); ?>" class="nav-tab<?php echo esc_attr( $post_type === $public_post_type ? ' nav-tab-active' : '' ); ?>" title="<?php echo esc_attr( $post_type_obj->labels->name ); ?>">
						<span class="<?php echo esc_attr( $icon ); ?>"></span>
						<span class="text">
							<?php echo esc_attr( $post_type_obj->labels->name ); ?>
						</span>
					</a>
					<?php
				}
			}
			?>
		</h2>

		<!-- Form Start -->
		<form name="post" method="post" action="<?php echo ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ); ?>" id="<?php echo esc_attr( 'social-post-flow' ); ?>">    
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-1">
					<!-- Content -->
					<div id="post-body-content">
						<div id="normal-sortables" class="meta-box-sortables ui-sortable publishing-defaults">  
							<?php
							// Load sub view.
							require_once $this->base->plugin->folder . 'lib/views/bulk-publish-' . $stage . '.php';

							// Nonce.
							wp_nonce_field( 'social-post-flow', 'social_post_flow_nonce' );
							?>
						</div>
						<!-- /normal-sortables -->
					</div>
					<!-- /post-body-content -->
				</div>
			</div> 
		</form>
		<!-- /form end -->		
	</div><!-- ./wrap-inner -->           
</div>
