<?php
/**
 * Outputs the Settings screen when the Plugin is authenticated with
 * the third party API service.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="postbox wpzinc-vertical-tabbed-ui">
	<!-- Second level tabs -->
	<ul class="wpzinc-nav-tabs wpzinc-js-tabs" data-panels-container="#settings-container" data-panel=".panel" data-active="wpzinc-nav-tab-vertical-active">
		<li class="wpzinc-nav-tab lock">
			<a href="#authentication" class="wpzinc-nav-tab-vertical-active" data-documentation="https://www.socialpostflow.com/documentation/wordpress-plugin/installation/">
				<?php esc_html_e( 'Authentication', 'social-post-flow' ); ?>
			</a>
		</li>
		<li class="wpzinc-nav-tab default">
			<a href="#general-settings" data-documentation="https://www.socialpostflow.com/documentation/wordpress-plugin/general-settings/">
				<?php esc_html_e( 'General Settings', 'social-post-flow' ); ?>
			</a>
		</li>
		<li class="wpzinc-nav-tab image">
			<a href="#image-settings" data-documentation="https://www.socialpostflow.com/documentation/wordpress-plugin/text-to-image/">
				<?php esc_html_e( 'Text to Image', 'social-post-flow' ); ?>
			</a>
		</li>
		<li class="wpzinc-nav-tab file-text">
			<a href="#log-settings" data-documentation="https://www.socialpostflow.com/documentation/wordpress-plugin/log-settings/">
				<?php esc_html_e( 'Log Settings', 'social-post-flow' ); ?>
			</a>
		</li>
		<li class="wpzinc-nav-tab arrow-right-circle">
			<a href="#repost-settings" data-documentation="https://www.socialpostflow.com/documentation/wordpress-plugin/auto-reposting/">
				<?php esc_html_e( 'Repost Settings', 'social-post-flow' ); ?>
			</a>
		</li>
		<?php
		// Only display if we've auth'd and have profiles.
		if ( ! empty( $access_token ) ) {
			?>
			<li class="wpzinc-nav-tab users">
				<a href="#user-access" data-documentation="https://www.socialpostflow.com/documentation/wordpress-plugin/user-access/">
					<?php esc_html_e( 'User Access', 'social-post-flow' ); ?>
				</a>
			</li>
			<?php
		}
		?>
		<li class="wpzinc-nav-tab tag">
			<a href="#custom-tags" data-documentation="https://www.socialpostflow.com/documentation/wordpress-plugin/custom-tags/">
				<?php esc_html_e( 'Custom Tags', 'social-post-flow' ); ?>
			</a>
		</li>
	</ul>

	<!-- Content -->
	<div id="settings-container" class="wpzinc-nav-tabs-content">
		<!-- Authentication -->
		<div id="authentication" class="panel">
			<div class="postbox">
				<header>
					<h3><?php esc_html_e( 'Authentication', 'social-post-flow' ); ?></h3>

					<p class="description">
						<?php
						esc_html_e( 'Authentication allows Social Post Flow to post to your social media profiles.', 'social-post-flow' );
						?>
					</p>
				</header>

				<div class="wpzinc-option">
					<div class="full">
						<?php
						esc_html_e( 'Thanks - you\'ve authorized the plugin to post updates to your Social Post Flow account.', 'social-post-flow' );
						?>
					</div>
				</div>
				<div class="wpzinc-option">
					<div class="full">
						<a href="<?php echo esc_url( $disconnect_url ); ?>" class="button wpzinc-button-red">
							<?php esc_html_e( 'Deauthorize Plugin', 'social-post-flow' ); ?>
						</a>
					</div>
				</div>
			</div>   
		</div>

		<!-- General Settings -->
		<div id="general-settings" class="panel">
			<div class="postbox">
				<header>
					<h3><?php esc_html_e( 'General Settings', 'social-post-flow' ); ?></h3>
					<p class="description">
						<?php esc_html_e( 'Provides options for logging, Post default level settings and whether to use WordPress Cron when publishing or updating Posts.', 'social-post-flow' ); ?>
					</p>
				</header>

				<div class="wpzinc-option">
					<div class="left">
						<label for="test_mode"><?php esc_html_e( 'Enable Test Mode', 'social-post-flow' ); ?></label>
					</div>
					<div class="right">
						<input type="checkbox" name="test_mode" id="test_mode" value="1" <?php checked( $this->get_setting( '', 'test_mode' ), 1 ); ?> />

						<p class="description">
							<?php
							esc_html_e( 'If enabled, status(es) are not sent to Social Post Flow, but will appear in the Log, if logging is enabled. This is useful to test status text, conditions etc.', 'social-post-flow' );
							?>
						</p>
					</div>
				</div>

				<div class="wpzinc-option">
					<div class="left">
						<label for="cron"><?php esc_html_e( 'Use WP Cron?', 'social-post-flow' ); ?></label>
					</div>
					<div class="right">
						<input type="checkbox" name="cron" id="cron" value="1" <?php checked( $this->get_setting( '', 'cron' ), 1 ); ?> data-conditional="cron_delay" />

						<p class="description">
							<?php
							printf(
								'%1$s <strong>%2$s</strong> %3$s',
								esc_html__( 'When enabled, status updates triggered by', 'social-post-flow' ),
								esc_html__( 'publishing or updating', 'social-post-flow' ),
								esc_html__( 'a Post will be asynchronously scheduled to send to Social Post Flow using the WordPress Cron, instead of being sent immediately.', 'social-post-flow' )
							);
							?>
						</p>
						<p class="description">
							<?php
							esc_html_e( 'This improves plugin performance on WordPress Post / Page edit screens.  Status updates may take a few minutes (or longer, on sites with low traffic volumes) to appear on Social Post Flow.', 'social-post-flow' );
							?>
						</p>
						<p class="description">
							<?php
							printf(
								'%1$s <strong>%2$s</strong> %3$s <a href="%4$s" target="_blank">%5$s</a>',
								esc_html__( 'This setting is', 'social-post-flow' ),
								esc_html__( 'required', 'social-post-flow' ),
								esc_html__( 'if using any frontend post submission, feed importer or autoblogging Plugin e.g. User Submitted Posts, WP Property Feed, WPeMatico etc.', 'social-post-flow' ),
								esc_html( 'https://www.socialpostflow.com/documentation/wordpress-plugin/frontend-post-submission-autoblogging-plugins/' ),
								esc_html__( 'See Documentation', 'social-post-flow' )
							);
							?>
						</p>
						<p class="description">
							<?php
							printf(
								'%1$s <a href="%2$s" target="_blank">%3$s</a> %4$s <strong>%5$s</strong>',
								esc_html__( 'Use', 'social-post-flow' ),
								'https://en-gb.wordpress.org/plugins/wp-crontrol/',
								esc_html__( 'WP Crontrol', 'social-post-flow' ),
								esc_html__( 'to monitor Cron Jobs. Social Post Flow will display its jobs with the Hook Name', 'social-post-flow' ),
								esc_html( 'social_post_flow_publish_cron' )
							);
							?>
						</p>
					</div>
				</div>

				<div id="cron_delay" class="wpzinc-option">
					<div class="left">
						<label for="cron_delay"><?php esc_html_e( 'Schedule Event', 'social-post-flow' ); ?></label>
					</div>
					<div class="right">
						<input type="number" name="cron_delay" id="cron_delay" value="<?php echo esc_attr( $this->get_setting( '', 'cron_delay', '30' ) ); ?>" />
						<?php echo esc_html_e( 'seconds after Publish or Update.', 'social-post-flow' ); ?>

						<p class="description">
							<?php echo esc_html_e( 'The approximate number of seconds to schedule the WordPress Cron event after the Post, Page or Custom Post Type has been published or updated.', 'social-post-flow' ); ?>
						</p>
					</div>
				</div>

				<div class="wpzinc-option">
					<div class="left">
						<label for="disable_excerpt_fallback"><?php esc_html_e( 'Disable Excerpt Fallback?', 'social-post-flow' ); ?></label>
					</div>
					<div class="right">
						<input type="checkbox" name="disable_excerpt_fallback" id="disable_excerpt_fallback" value="1" <?php checked( $this->get_setting( '', 'disable_excerpt_fallback' ), 1 ); ?> />

						<p class="description">
							<?php
							esc_html_e( 'If enabled, any {excerpt} tag used in statuses will be blank if no Post Excerpt is explicitly set in the Post.', 'social-post-flow' );
							?>
						</p>
					</div>
				</div>

				<div class="wpzinc-option">
					<div class="left">
						<label for="override"><?php esc_html_e( 'Post Level Default', 'social-post-flow' ); ?></label>
					</div>
					<div class="right">
						<select name="override" size="1" id="override">
							<?php
							foreach ( (array) $override_options as $value => $label ) {
								?>
								<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $this->get_setting( '', 'override', '0' ), $value ); ?>>
									<?php echo esc_attr( $label ); ?>
								</option>
								<?php
							}
							?>
						</select>

						<p class="description">
							<?php
							esc_html_e( 'Determines the default option to be selected in the Social Post Flow metabox when adding/editing Pages, Posts and Custom Post Types.  A user can always change this on a per-Post basis.', 'social-post-flow' );
							?>
						</p>
					</div>
				</div>
			</div>
		</div>

		<!-- Image Settings -->
		<div id="image-settings" class="panel">
			<div class="postbox">
				<header>
					<h3><?php esc_html_e( 'Text to Image Settings', 'social-post-flow' ); ?></h3>
					<p class="description">
						<?php
						esc_html_e(
							'Provides options for automatically generating images from text, when a Status\' image option is set to Use Text to Image
                        and a status has Text to Image defined.',
							'social-post-flow'
						);
						?>
					</p>
				</header>

				<div class="wpzinc-option">
					<div class="left">
						<label for="font"><?php esc_html_e( 'Text Font', 'social-post-flow' ); ?></label>
					</div>
					<div class="right">
						<select name="text_to_image[font]" id="font" size="1" data-conditional="custom_font" data-conditional-value="0">
							<?php
							foreach ( $fonts as $font_file => $font_name ) {
								?>
								<option value="<?php echo esc_attr( $font_file ); ?>"<?php selected( $this->get_setting( 'text_to_image', '[font]', 'OpenSans-Regular' ), $font_file ); ?>>
									<?php echo esc_attr( $font_name ); ?>
								</option>
								<?php
							}
							?>
							<option value="0"<?php selected( $this->get_setting( 'text_to_image', '[font]' ), '0' ); ?>>
								<?php esc_attr_e( 'Custom Font', 'social-post-flow' ); ?>
							</option>
						</select>

						<p class="description">
							<?php
							if ( extension_loaded( 'imagick' ) ) {
								esc_html_e( 'If the text will include emojis, the Open Sans (Regular, with Emoji Support) font must be selected.', 'social-post-flow' );
							} else {
								esc_html_e( 'The Imagick PHP extension is not installed. Emojis in text to image will automatically be removed, as they are not supported by PHP\'s GD extension.', 'social-post-flow' );
								?>
								<br />
								<?php
								esc_html_e( 'If you require emoji support, have your web host enable the PHP Imagick extension.', 'social-post-flow' );
							}
							?>
						</p>

						<div id="custom_font" class="wpzinc-media-library-selector"
							data-input-name="text_to_image[font_custom]"
							data-file-type="application/octet-stream">

							<ul>
								<?php
								if ( $this->get_setting( 'text_to_image', '[font_custom]' ) ) {
									?>
									<li class="wpzinc-media-library-attachment">
										<div class="wpzinc-media-library-insert">
											<input type="hidden" id="font_input" name="text_to_image[font_custom]" value="<?php echo esc_attr( $this->get_setting( 'text_to_image', '[font_custom]' ) ); ?>" />
											<?php
											echo esc_html( basename( get_attached_file( $this->get_setting( 'text_to_image', '[font_custom]' ) ) ) );
											?>
										</div>

										<a href="#" class="wpzinc-media-library-remove" title="<?php esc_attr_e( 'Remove', 'social-post-flow' ); ?>"><?php esc_html_e( 'Remove', 'social-post-flow' ); ?></a>
									</li>
									<?php
								}
								?>
							</ul>

							<button class="wpzinc-media-library-insert button button-secondary">
								<?php esc_html_e( 'Add/Replace Custom Font', 'social-post-flow' ); ?>
							</button>

							<p class="description">
								<?php esc_html_e( 'Upload a TTF to use. If no font is specified, Open Sans will be used.', 'social-post-flow' ); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="wpzinc-option">
					<div class="left">
						<label for="text_size"><?php esc_html_e( 'Text Size', 'social-post-flow' ); ?></label>
					</div>
					<div class="right">
						<input type="number" name="text_to_image[text_size]" id="text_size" min="1" max="200" step="1" value="<?php echo esc_attr( $this->get_setting( 'text_to_image', '[text_size]', 90 ) ); ?>" />
						<?php esc_html_e( 'px', 'social-post-flow' ); ?>
					</div>
				</div>

				<div class="wpzinc-option">
					<div class="left">
						<label for="text_color"><?php esc_html_e( 'Text Color', 'social-post-flow' ); ?></label>
					</div>
					<div class="right">
						<input type="text" name="text_to_image[text_color]" id="text_color" value="<?php echo esc_attr( $this->get_setting( 'text_to_image', '[text_color]', '#000000' ) ); ?>" class="color-picker" />
					</div>
				</div>

				<div class="wpzinc-option">
					<div class="left">
						<label for="text_background_color"><?php esc_html_e( 'Text Background Color', 'social-post-flow' ); ?></label>
					</div>
					<div class="right">
						<input type="text" name="text_to_image[text_background_color]" id="text_background_color" value="<?php echo esc_attr( $this->get_setting( 'text_to_image', '[text_background_color]', '' ) ); ?>" class="color-picker" />

						<p class="description">
							<?php
							esc_html_e(
								'If specified, the text will have the chosen background color applied to it.  This is different to the entire image\'s Background
                            Color and Background Image options below, which apply to the whole image.',
								'social-post-flow'
							);
							?>
						</p>
					</div>
				</div>

				<div class="wpzinc-option">
					<div class="left">
						<label for="background_color"><?php esc_html_e( 'Background Color', 'social-post-flow' ); ?></label>
					</div>
					<div class="right">
						<input type="text" name="text_to_image[background_color]" id="background_color" value="<?php echo esc_attr( $this->get_setting( 'text_to_image', '[background_color]', '#e7e7e7' ) ); ?>" class="color-picker" />

						<p class="description">
							<?php esc_html_e( 'Used if a Featured Image doesn\'t exist or a Background Image isn\'t defined.', 'social-post-flow' ); ?>
						</p>
					</div>
				</div>

				<div class="wpzinc-option">
					<div class="left">
						<label for="background_image">
							<?php esc_html_e( 'Background Image', 'social-post-flow' ); ?>
						</label>
					</div>
					<div class="right">
						<table class="widefat striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Profile', 'social-post-flow' ); ?></th>
									<th><?php esc_html_e( 'Background Image', 'social-post-flow' ); ?></th>
									<th><?php esc_html_e( 'Recommended Dimensions', 'social-post-flow' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								// Iterate through profiles.
								if ( isset( $profiles ) && is_array( $profiles ) ) {
									foreach ( $profiles as $key => $profile ) {
										$background_image_type = $this->get_setting( 'text_to_image', '[type][' . $profile['id'] . ']', 'background' );
										$background_image_id   = $this->get_setting( 'text_to_image', '[background_image][' . $profile['id'] . ']' );
										if ( $background_image_id ) {
											$background_image = wp_get_attachment_image_src( $background_image_id );
										} else {
											$background_image = false;
										}

										$image_size = social_post_flow()->get_class( 'image' )->get_social_media_image_size( $profile['provider'] );
										?>
										<tr>
											<td>
												<?php
												echo esc_html(
													sprintf(
														'%1$s: %2$s',
														$profile['provider_name'],
														$profile['profile_name']
													)
												);
												?>
											</td>
											<td>
												<select name="text_to_image[type][<?php echo esc_attr( $profile['id'] ); ?>]" size="1" data-conditional="text_to_image_background_image_<?php echo esc_attr( $profile['id'] ); ?>" data-conditional-value="background">
													<option value="featured"<?php selected( $background_image_type, 'featured' ); ?>><?php esc_attr_e( 'Use Post\'s Featured Image', 'social-post-flow' ); ?></option>
													<option value="background"<?php selected( $background_image_type, 'background' ); ?>><?php esc_attr_e( 'Use Background Image', 'social-post-flow' ); ?></option>
												</select>

												<div id="text_to_image_background_image_<?php echo esc_attr( $profile['id'] ); ?>" 
													class="full wpzinc-media-library-selector"
													data-input-name="text_to_image[background_image][<?php echo esc_attr( $profile['id'] ); ?>]"
													data-file-type="image"
													data-output-size="small">
													<ul class="images">
														<?php
														if ( $background_image ) {
															?>
															<li class="wpzinc-media-library-attachment">
																<div class="wpzinc-media-library-insert">
																	<input type="hidden" name="text_to_image[background_image][<?php echo esc_attr( $profile['id'] ); ?>]" value="<?php echo esc_attr( $background_image_id ); ?>" />
																	<img src="<?php echo esc_attr( ( $background_image ? $background_image[0] : '' ) ); ?>" />
																</div>
																<a href="#" class="wpzinc-media-library-remove" title="<?php esc_attr_e( 'Remove Background Image', 'social-post-flow' ); ?>"><?php esc_html_e( 'Remove', 'social-post-flow' ); ?></a>
															</li>
															<?php
														}
														?>
													</ul>

													<button class="wpzinc-media-library-insert button button-secondary">
														<?php esc_html_e( 'Select Background Image', 'social-post-flow' ); ?>
													</button>
												</div>
											</td>
											<td>
												<p class="description">
													<?php
													echo esc_html(
														sprintf(
															/* translators: %1$s: Width, %2$s: Height */
															__( '%1$spx width x %2$spx height', 'social-post-flow' ),
															$image_size[0],
															$image_size[1]
														)
													);
													?>
												</p>
											</td>
										</tr>
										<?php
									}
								}
								?>
							</tbody>
						</table>
					</div>
				</div>

			</div>
		</div>

		<!-- Log Settings -->
		<div id="log-settings" class="panel">
			<div class="postbox">
				<header>
					<h3><?php esc_html_e( 'Log Settings', 'social-post-flow' ); ?></h3>
					<p class="description">
						<?php esc_html_e( 'Provides options to enable logging, display logs on Posts and how long to keep logs for.', 'social-post-flow' ); ?>
					</p>
				</header>

				<div class="wpzinc-option">
					<div class="left">
						<label for="log_enabled"><?php esc_html_e( 'Enable Logging?', 'social-post-flow' ); ?></label>
					</div>
					<div class="right">
						<input type="checkbox" name="log[enabled]" id="log_enabled" value="1" <?php checked( $this->get_setting( 'log', '[enabled]' ), 1 ); ?> data-conditional="enable_logging" />
						<p class="description">
							<?php
							if ( $this->get_setting( 'log', '[enabled]' ) ) {
								printf(
									'%1$s <a href="%2$s">%3$s</a> %4$s',
									esc_html__( 'If enabled, the', 'social-post-flow' ),
									esc_html( admin_url( 'admin.php?page=social-post-flow-log' ) ),
									esc_html__( 'Plugin Logs', 'social-post-flow' ),
									esc_html__( 'will detail status(es) sent to Social Post Flow, including any errors or reasons why no status(es) were sent.', 'social-post-flow' )
								);
							} else {
								// Don't link "Plugin Log" text, as Logs are disabled so it won't show anything.
								esc_html_e( 'If enabled, the Plugin Logs will detail status(es) sent to Social Post Flow, including any errors or reasons why no status(es) were sent.', 'social-post-flow' );
							}
							?>
						</p>
					</div>
				</div>

				<div id="enable_logging">
					<div class="wpzinc-option">
						<div class="left">
							<label for="log_display_on_posts"><?php esc_html_e( 'Display on Posts?', 'social-post-flow' ); ?></label>
						</div>
						<div class="right">
							<input type="checkbox" name="log[display_on_posts]" id="log_display_on_posts" value="1" <?php checked( $this->get_setting( 'log', '[display_on_posts]' ), 1 ); ?> />
			   
							<p class="description">
								<?php
								if ( $this->get_setting( 'log', '[enabled]' ) ) {
									printf(
										'%1$s <a href="%2$s">%3$s</a> %4$s',
										esc_html__( 'If enabled, a Log will be displayed when editing a Post.  Logs are always available through the', 'social-post-flow' ),
										esc_html( admin_url( 'admin.php?page=social-post-flow-log' ) ),
										esc_html__( 'Plugin Logs', 'social-post-flow' ),
										esc_html__( 'screen', 'social-post-flow' )
									);
								} else {
									// Don't link "Plugin Log" text, as Logs are disabled so it won't show anything.
									esc_html_e( 'If enabled, a Log will be displayed when editing a Post.  Logs are always available through the Plugin Logs screen.', 'social-post-flow' );
								}
								?>
							</p>
						</div>
					</div>

					<div class="wpzinc-option">
						<div class="left">
							<label for="log_level"><?php esc_html_e( 'Log Level', 'social-post-flow' ); ?></label>
						</div>
						<div class="right">
							<?php
							$log_levels_settings = $this->get_setting( 'log', 'log_level' );

							foreach ( $log_levels as $log_level => $label ) {
								?>
								<label for="log_level_<?php echo esc_attr( $log_level ); ?>">
									<input  type="checkbox" 
											name="log[log_level][]" 
											id="log_level_<?php echo esc_attr( $log_level ); ?>"
											value="<?php echo esc_attr( $log_level ); ?>"
											<?php echo ( in_array( $log_level, $log_levels_settings, true ) || $log_level === 'error' ? ' checked' : '' ); ?>
											<?php echo ( ( $log_level === 'error' ) ? ' disabled' : '' ); ?>
											/>

									<?php echo esc_html( $label ); ?>
								</label>
								<br />
								<?php
							}
							?>

							<p class="description">
								<?php esc_html_e( 'Defines which log results to save to the Log database. Errors will always be logged.', 'social-post-flow' ); ?>
							</p>
						</div>
					</div>

					<div class="wpzinc-option">
						<div class="left">
							<label for="log_preserve_days"><?php esc_html_e( 'Preserve Logs', 'social-post-flow' ); ?></strong>
						</div>
						<div class="right">
							<input type="number" name="log[preserve_days]" id="log_preserve_days" value="<?php echo esc_attr( $this->get_setting( 'log', '[preserve_days]' ) ); ?>" min="0" max="9999" step="1" />
							<?php esc_html_e( 'days', 'social-post-flow' ); ?>
					   
							<p class="description">
								<?php
								esc_html_e( 'The number of days to preserve logs for.  Zero means logs are kept indefinitely.', 'social-post-flow' );
								?>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Repost Settings -->
		<div id="repost-settings" class="panel">
			<!-- Action Tabs -->
			<ul class="wpzinc-nav-tabs-horizontal wpzinc-js-tabs" data-panels-container="#repost-settings-container" data-panel=".settings" data-active="wpzinc-nav-tab-horizontal-active">
				<li class="wpzinc-nav-tab-horizontal repost-post-types">
					<a href="#repost-settings-general" class="enabled wpzinc-nav-tab-horizontal-active">
						<?php esc_html_e( 'General', 'social-post-flow' ); ?>

						<?php
						if ( $repost_event_next_scheduled ) {
							?>
							<span class="dashicons dashicons-yes"></span>
							<?php
						}
						?>
					</a>
				</li>

				<?php
				foreach ( $post_types as $post_type_obj ) {
					?>
					<li class="wpzinc-nav-tab-horizontal repost-<?php echo esc_attr( $post_type_obj->name ); ?>">
						<a href="#repost-settings-<?php echo esc_attr( $post_type_obj->name ); ?>" class="wpzinc-nav-tab-horizontal-active">
							<?php
							// Work out the icon to display.
							$icon = '';
							if ( ! empty( $post_type_obj->menu_icon ) ) {
								$icon = 'dashicons ' . $post_type_obj->menu_icon;
							} elseif ( $post_type_obj->name === 'post' || $post_type_obj->name === 'page' ) {
									$icon = 'dashicons dashicons-admin-' . $post_type_obj->name;
							}
							?>

							<span class="<?php echo esc_attr( $icon ); ?>"></span>

							<?php echo esc_html( $post_type_obj->labels->name ); ?>
						</a>
					</li>
					<?php
				}
				?>
			</ul>

			<div id="repost-settings-container">
				<!-- General -->
				<div id="repost-settings-general" class="postbox settings">
					<header>
						<h3><?php esc_html_e( 'Repost Settings: General', 'social-post-flow' ); ?></h3>
						<p class="description">
							<?php esc_html_e( 'Provides general options for when to run the WordPress Repost Cron Event on this WordPress installation, and to disable the Repost cron entirely.', 'social-post-flow' ); ?><br />
							<?php
							printf(
								'%1$s <a href="https://www.socialpostflow.com/documentation/wordpress-plugin/auto-reposting/" target="_blank">%2$s</a>',
								esc_html__( 'When Post(s) are scheduled on Social Post Flow will depend on the', 'social-post-flow' ),
								esc_html__( 'Repost Status Settings', 'social-post-flow' )
							);
							?>
						</p>
					</header>

					<div class="wpzinc-option">
						<div class="left">
							<strong><?php esc_html_e( 'Status', 'social-post-flow' ); ?></strong>
						</div>
						<div class="right">
							<?php
							if ( ! $repost_event_next_scheduled ) {
								?>
								<span class="error"><strong><?php esc_html_e( 'Disabled', 'social-post-flow' ); ?></strong></span>
								<?php
							} else {
								?>
								<span class="success"><strong><?php esc_html_e( 'Enabled', 'social-post-flow' ); ?></strong></span>
								<?php
							}
							?>
						</div>
					</div>
					<div class="wpzinc-option">
						<div class="left">
							<label for="repost_time"><?php esc_html_e( 'Repost Times', 'social-post-flow' ); ?></label>
						</div>
						<div class="right">
							<table class="widefat">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Monday', 'social-post-flow' ); ?></th>
										<th><?php esc_html_e( 'Tuesday', 'social-post-flow' ); ?></th>
										<th><?php esc_html_e( 'Wednesday', 'social-post-flow' ); ?></th>
										<th><?php esc_html_e( 'Thursday', 'social-post-flow' ); ?></th>
										<th><?php esc_html_e( 'Friday', 'social-post-flow' ); ?></th>
										<th><?php esc_html_e( 'Saturday', 'social-post-flow' ); ?></th>
										<th><?php esc_html_e( 'Sunday', 'social-post-flow' ); ?></th>
										<th><?php esc_html_e( 'Actions', 'social-post-flow' ); ?></th>
									</tr>
								</thead>
								<tfoot>
									<tr>
										<td colspan="8">
											<a href="#" class="button add-repost-time"><?php esc_html_e( 'Add Repost Time', 'social-post-flow' ); ?></a>
										</td>
									</tr>
								</tfoot>
								<tbody>
									<?php
									// Output Repost Schedule.
									if ( is_array( $repost_schedule ) ) {
										foreach ( $repost_schedule['mon'] as $index => $time ) {
											?>
											<tr>
												<?php
												foreach ( $repost_days as $repost_day ) {
													?>
													<td>
														<select name="repost_time[<?php echo esc_attr( $repost_day ); ?>][]" size="1">
															<option value="0"<?php selected( $repost_schedule[ $repost_day ][ $index ], 0 ); ?>><?php esc_attr_e( 'Don\'t Repost', 'social-post-flow' ); ?></option>
															<?php
															for ( $hour = 0; $hour <= 23; $hour++ ) {
																// Pad hour.
																$hour = ( ( $hour < 10 ) ? '0' . $hour : $hour );
																?>
																<option value="<?php echo esc_attr( $hour ); ?>:00"<?php selected( $repost_schedule[ $repost_day ][ $index ], $hour . ':00' ); ?>>
																	<?php echo esc_attr( $hour ); ?>:00
																</option>
																<?php
															}
															?>
														</select>
													</td>
													<?php
												}
												?>
												<td>
													<a href="#" class="delete-repost-time">
														<span class="dashicons dashicons-trash"></span>
														<?php esc_html_e( 'Delete', 'social-post-flow' ); ?>
													</a>
												</td>
											</tr>
											<?php
										}
									}
									?>
								</tbody> 
							</table>

							<p class="description">
								<?php
								esc_html_e( 'For each day(s) and time(s) specified, repost statuses will be sent to Social Post Flow via this Plugin\'s WordPress Cron event.', 'social-post-flow' );
								?>
								<br />
								<?php esc_html_e( 'Use "Don\'t Repost" for a given day if you do not want to repost statuses.', 'social-post-flow' ); ?>
								<br />
								<?php esc_html_e( 'If your site has low traffic volumes, the Repost WordPress Cron event may take several minutes, even hours, to trigger.', 'social-post-flow' ); ?><br />
							</p>
						</div>
					</div>

					<div class="wpzinc-option">
						<div class="left">
							<label for="repost_disable_cron"><?php esc_html_e( 'Disable Repost Cron?', 'social-post-flow' ); ?></label>
						</div>
						<div class="right">
							<input type="checkbox" name="repost_disable_cron" id="repost_disable_cron" value="1" <?php checked( $this->get_setting( '', 'repost_disable_cron' ), 1 ); ?> />

							<p class="description">
								<?php
								printf(
									'%1$s <strong>%2$s</strong> %3$s <strong>%4$s</strong> <a href="%5$s" target="_blank">%6$s</a> %7$s',
									esc_html__( 'Check this option if you do NOT want Automatic Reposting or prefer to manually run Reposting via the', 'social-post-flow' ),
									esc_html( 'social_post_flow_repost_cron' ),
									esc_html__( 'Cron event /', 'social-post-flow' ),
									esc_html( 'social-post-flow-repost' ),
									esc_html( 'https://www.socialpostflow.com/documentation/wordpress-plugin/wp-cli/' ),
									esc_html__( 'CLI', 'social-post-flow' ),
									esc_html__( 'command', 'social-post-flow' )
								);
								?>
								<br />
								<?php esc_html_e( 'If you\'re disabling the Repost Cron and running it manually, you\'ll need to trigger either the Cron event or CLI command to run hourly.', 'social-post-flow' ); ?>
							</p>
						</div>
					</div>

					<div class="wpzinc-option">
						<div class="left">
							<label for="repost_test"><?php esc_html_e( 'Test', 'social-post-flow' ); ?></label>
						</div>
						<div class="right">
							<a href="#" class="button repost-test"><?php esc_html_e( 'Test Repost Cron Now', 'social-post-flow' ); ?></a><br />
							<textarea name="repost_test_log" class="widefat" rows="10" disabled></textarea>
							<p class="description">
								<?php
								printf(
									'%1$s <strong>%2$s</strong> %3$s',
									esc_html__( 'Once you have defined a Repost schedule and settings for each Post Type, click Save, and then optionally click the Test button above to simulate what the Repost Cron event would do if run by WordPress now. This does', 'social-post-flow' ),
									esc_html__( 'not', 'social-post-flow' ),
									esc_html__( 'post to Social Post Flow', 'social-post-flow' )
								);
								?>
							</p>
						</div>
					</div>
				</div>

				<!-- Post Types -->
				<?php
				foreach ( $post_types as $repost_post_type ) {
					?>
					<div id="repost-settings-<?php echo esc_attr( $repost_post_type->name ); ?>" class="postbox settings">
						<header>
							<h3>
								<?php
								echo esc_html(
									sprintf(
										/* translators: Post Type Name */
										__( 'Repost Settings: %s', 'social-post-flow' ),
										$repost_post_type->labels->name
									)
								);
								?>
							</h3>
							<p class="description">
								<?php
								echo esc_html(
									sprintf(
										/* translators: %1$s: Post Type Name */
										__( 'Defines conditions for when %1$s are eligible to be automatically reposted to Social Post Flow.', 'social-post-flow' ),
										$repost_post_type->labels->name
									)
								);
								?>
								<br />
								<?php
								printf(
									'%1$s <a href="%2$s" target="_blank">%3$s</a>',
									sprintf(
										/* translators: %1$s: Post Type Name */
										esc_html__( 'To enable Automatic Reposting of %1$s, and define the status(es) to send to Social Post Flow, visit', 'social-post-flow' ),
										esc_html( $repost_post_type->labels->name ),
									),
									esc_html( admin_url( 'admin.php?page=social-post-flow&tab=post&type=' . $repost_post_type->name ) ),
									sprintf(
										/* translators: Post Type Name, Plural */
										esc_html__( '%s &gt; Repost', 'social-post-flow' ),
										esc_html( $repost_post_type->labels->name )
									)
								);
								?>
							</p>
						</header>

						<div class="wpzinc-option">
							<div class="left">
								<label for="repost_<?php echo esc_attr( $repost_post_type->name ); ?>_limit">
									<?php
									echo esc_html(
										sprintf(
											/* translators: Post Type Name */
											__( 'Max %s', 'social-post-flow' ),
											$repost_post_type->labels->name
										)
									);
									?>
								</label>
							</div>

							<div class="right">
								<input type="number" name="repost[<?php echo esc_attr( $repost_post_type->name ); ?>][limit]" id="repost_<?php echo esc_attr( $repost_post_type->name ); ?>_limit" value="<?php echo esc_attr( $this->get_setting( 'repost', '[' . $repost_post_type->name . '][limit]', 3 ) ); ?>" />
								<?php esc_html_e( 'per run', 'social-post-flow' ); ?>

								<p class="description">
									<?php
									echo esc_html(
										sprintf(
											/* translators: %1$s: Post Type Name */
											__( 'The maximum number of %1$s to automatically repost to Social Post Flow each time the Repost Cron event is run.  This limit applies across the entire Post Type.', 'social-post-flow' ),
											$repost_post_type->labels->name
										)
									);
									?>
								</p>
							</div>  
						</div>

						<div class="wpzinc-option">
							<div class="left">
								<label for="repost_<?php echo esc_attr( $repost_post_type->name ); ?>_frequency">
									<?php esc_html_e( 'Minimum Interval between Reposting', 'social-post-flow' ); ?>  
								</label>
							</div>

							<div class="right">
								<input type="number" name="repost[<?php echo esc_attr( $repost_post_type->name ); ?>][frequency]" id="repost_<?php echo esc_attr( $repost_post_type->name ); ?>_frequency" value="<?php echo esc_attr( $this->get_setting( 'repost', '[' . $repost_post_type->name . '][frequency]', 30 ) ); ?>" />
								<?php esc_html_e( 'days', 'social-post-flow' ); ?>

								<p class="description">
									<?php
									echo esc_html(
										sprintf(
										/* translators: Post Type Nme */
											__( 'Define the minimum number of days before an already reposted %s is eligible for automatic reposting.', 'social-post-flow' ),
											$repost_post_type->labels->name
										)
									);
									?>
								</p>
							</div>  
						</div>

						<!-- Post Age -->
						<div class="wpzinc-option">
							<div class="left">
								<label for="repost_<?php echo esc_attr( $repost_post_type->name ); ?>_min_age">
									<?php
									echo esc_html(
										sprintf(
											/* translators: Post Type Name */
											__( 'Minimum %s Age', 'social-post-flow' ),
											$repost_post_type->labels->name
										)
									);
									?>
								</label>
							</div>
							<div class="right">
								<input type="number" name="repost[<?php echo esc_attr( $repost_post_type->name ); ?>][min_age]" id="repost_<?php echo esc_attr( $repost_post_type->name ); ?>_min_age" min="0" max="999999" step="1" value="<?php echo esc_attr( $this->get_setting( 'repost', '[' . $repost_post_type->name . '][min_age]', 30 ) ); ?>" />
								<?php esc_html_e( 'days', 'social-post-flow' ); ?>

								<p class="description">
									<?php
									echo esc_html(
										sprintf(
											/* translators: Post Type Name */
											__( 'The minimum age of %s available for sharing, in days.', 'social-post-flow' ),
											$repost_post_type->labels->name
										)
									);
									?>
								</p>
							</div>
						</div>
						<div class="wpzinc-option">
							<div class="left">
								<label for="repost_<?php echo esc_attr( $repost_post_type->name ); ?>_max_age">
									<?php
									echo esc_html(
										sprintf(
											/* translators: Post Type Name */
											__( 'Maximum %s Age', 'social-post-flow' ),
											$repost_post_type->labels->name
										)
									);
									?>
								</label>
							</div>
							<div class="right">
								<input type="number" name="repost[<?php echo esc_attr( $repost_post_type->name ); ?>][max_age]" id="repost_<?php echo esc_attr( $repost_post_type->name ); ?>_max_age" min="0" max="999999" step="1" value="<?php echo esc_attr( $this->get_setting( 'repost', '[' . $repost_post_type->name . '][max_age]', 90 ) ); ?>" />
								<?php esc_html_e( 'days', 'social-post-flow' ); ?>

								<p class="description">
									<?php
									echo esc_html(
										sprintf(
											/* translators: Post Type Name */
											__( 'The maximum age of %s available for sharing, in days.  Zero means no maximum.', 'social-post-flow' ),
											$repost_post_type->labels->name
										)
									);
									?>
								</p>
							</div>
						</div> 

						<!-- Order -->
						<div class="wpzinc-option">
							<div class="left">
								<label for="repost_<?php echo esc_attr( $repost_post_type->name ); ?>_orderby"><?php esc_html_e( 'Repost Order', 'social-post-flow' ); ?></label>
							</div>
							<div class="right">
								<select name="repost[<?php echo esc_attr( $repost_post_type->name ); ?>][orderby]" id="repost_<?php echo esc_attr( $repost_post_type->name ); ?>_orderby" size="1">
									<?php
									$repost_order_by = $this->get_setting( 'repost', '[' . $repost_post_type->name . '][orderby]', 'date' );
									foreach ( social_post_flow()->get_class( 'common' )->get_order_by() as $key => $label ) {
										?>
										<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $repost_order_by ); ?>><?php echo esc_attr( $label ); ?></option>
										<?php
									}
									?>
								</select>
								<select name="repost[<?php echo esc_attr( $repost_post_type->name ); ?>][order]" id="repost_<?php echo esc_attr( $repost_post_type->name ); ?>_order" size="1">
									<?php
									$repost_order = $this->get_setting( 'repost', '[' . $repost_post_type->name . '][order]', 'ASC' );
									foreach ( social_post_flow()->get_class( 'common' )->get_order() as $key => $label ) {
										?>
										<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $repost_order ); ?>><?php echo esc_attr( $label ); ?></option>
										<?php
									}
									?>
								</select>

								<p class="description">
									<?php
									echo esc_html(
										sprintf(
											/* translators: Post Type Name */
											__( 'The order to go through %s when reposting.', 'social-post-flow' ),
											$repost_post_type->labels->name
										)
									);
									?>
								</p>
							</div>
						</div> 
					</div>
					<?php
				}
				?>
			</div>
		</div>

	<?php
	// Only display if we've auth'd and have profiles.
	if ( ! empty( $access_token ) ) {
		// User Access.
		?>
		<!-- User Access -->
		<div id="user-access" class="panel">
			<div class="postbox">
				<header>
					<h3><?php esc_html_e( 'User Access', 'social-post-flow' ); ?></h3>
					<p class="description">
						<?php esc_html_e( 'Optionally define which of your connected social media account(s) should be available for configuration and publication based on each WordPress User Role.', 'social-post-flow' ); ?>
					</p>
				</header>

				<!-- Specific Post Types -->
				<div class="wpzinc-option">
					<div class="left">
						<label for="restrict_post_types_toggle"><?php esc_html_e( 'Enable Specific Post Types?', 'social-post-flow' ); ?></label>
					</div>
					<div class="right">
						<input type="checkbox" name="restrict_post_types" id="restrict_post_types_toggle" value="1" <?php checked( $this->get_setting( '', 'restrict_post_types' ), 1 ); ?> data-conditional="restrict_post_types" />
						<p class="description">
							<?php
							esc_html_e(
								'If enabled, options are displayed below by WordPress Role to define which Post Types to enable. 
                            If you have several Post Types, some of which you don\'t want to use for social media, we recommend using 
                            this option for performance.',
								'social-post-flow'
							);
							?>
						</p>
					</div>
				</div>
				<div id="restrict_post_types">
					<?php
					// Iterate through roles.
					foreach ( $roles as $role_name => $restrict_post_types_role ) {
						?>
						<div class="wpzinc-option">
							<div class="left">
								<strong><?php echo esc_html( $restrict_post_types_role['name'] ); ?></strong>
							</div>
							<div class="right">
								<div class="tax-selection">
									<div class="tabs-panel" style="height: 70px;">
										<ul class="list:category categorychecklist form-no-clear" style="margin: 0; padding: 0;">  
											<?php
											// Iterate through Post Types.
											if ( isset( $post_types_public ) && is_array( $post_types_public ) ) {
												foreach ( $post_types_public as $post_type_public => $post_type_obj ) {
													?>
													<li>
														<label for="roles_<?php echo esc_attr( $role_name ); ?>_<?php echo esc_attr( $post_type_public ); ?>" class="selectit">
															<input type="checkbox" name="roles[<?php echo esc_attr( $role_name ); ?>][<?php echo esc_attr( $post_type_public ); ?>]" id="roles_<?php echo esc_attr( $role_name ); ?>_<?php echo esc_attr( $post_type_public ); ?>" value="1" <?php checked( $this->get_setting( 'roles', '[' . $role_name . '][' . $post_type_public . ']' ), 1 ); ?> />
															<?php echo esc_html( $post_type_obj->labels->name ); ?>
														</label>
													</li>
													<?php
												}
											}
											?>
										</ul>
									</div>
								</div>
							</div>
						</div>
						<?php
					}
					?>
				</div>

				<!-- Enable Specific Profiles by Role -->
				<div class="wpzinc-option">
					<div class="left">
						<label for="restrict_roles_checkbox"><?php esc_html_e( 'Enable Specific Profiles?', 'social-post-flow' ); ?></label>
					</div>
					<div class="right">
						<input type="checkbox" name="restrict_roles" id="restrict_roles_checkbox" value="1" <?php checked( $this->get_setting( '', 'restrict_roles' ), 1 ); ?> data-conditional="restrict_roles" />
						<p class="description">
							<?php esc_html_e( 'If enabled, options are displayed below by WordPress Role to define which social media profiles:', 'social-post-flow' ); ?>
							<br />
							<?php esc_html_e( '- The Administrator can configure in the Plugin\'s Status Settings,', 'social-post-flow' ); ?>
							<br />
							<?php esc_html_e( '- The Post\'s Author\'s Role can configure on Per-Post Settings, if Per-Post Settings are not hidden,', 'social-post-flow' ); ?>
							<br />
							<?php esc_html_e( '- The Post\'s Author\'s Role can send statuses to, when a Post is Published, Updated, Reposted or Bulk Published.', 'social-post-flow' ); ?>
							<br />
							<?php
							printf(
								'%1$s <a href="%2$s" target="_blank">%3$s</a> %4$s <strong>%5$s</strong> %6$s',
								esc_html__( 'To hide', 'social-post-flow' ),
								esc_html( 'https://www.socialpostflow.com/documentation/wordpress-plugin/per-post-settings/' ),
								esc_html__( 'Per-Post Settings', 'social-post-flow' ),
								esc_html__( 'by the', 'social-post-flow' ),
								esc_html__( 'Post\'s Author\'s Role', 'social-post-flow' ),
								esc_html__( ', use the "Hide Per-Post Settings" option below.', 'social-post-flow' )
							);
							?>
						</p>
					</div>
				</div>
				<div id="restrict_roles">
					<?php
					// Iterate through roles.
					foreach ( $roles as $role_name => $restrict_role ) {
						?>
						<div class="wpzinc-option">
							<div class="left">
								<strong><?php echo esc_html( $restrict_role['name'] ); ?></strong>
							</div>
							<div class="right">
								<div class="tax-selection">
									<div class="tabs-panel" style="height: 70px;">
										<ul class="list:category categorychecklist form-no-clear" style="margin: 0; padding: 0;">  
											<?php
											// Iterate through profiles.
											if ( isset( $profiles ) && is_array( $profiles ) ) {
												foreach ( $profiles as $key => $profile ) {
													?>
													<li>
														<label for="roles_<?php echo esc_attr( $role_name ); ?>_<?php echo esc_attr( $profile['id'] ); ?>" class="selectit">
															<input type="checkbox" name="roles[<?php echo esc_attr( $role_name ); ?>][<?php echo esc_attr( $profile['id'] ); ?>]" id="roles_<?php echo esc_attr( $role_name ); ?>_<?php echo esc_attr( $profile['id'] ); ?>" value="1" <?php checked( $this->get_setting( 'roles', '[' . $role_name . '][' . $profile['id'] . ']' ), 1 ); ?> />
															<?php echo esc_html( $profile['provider_name'] . ': ' . $profile['profile_name'] ); ?>
														</label>
													</li>
													<?php
												}
											}
											?>
										</ul>
									</div>
								</div>
							</div>
						</div>
						<?php
					}
					?>
				</div>

				<!-- Hide Post Meta Box by Roles -->
				<div class="wpzinc-option">
					<div class="left">
						<label for="hide_meta_box_by_roles_administrator"><?php esc_html_e( 'Hide Per-Post Settings', 'social-post-flow' ); ?></label>
					</div>
					<div class="right">
						<?php
						// Iterate through Roles.
						foreach ( $roles as $role_name => $hide_role ) {
							?>
							<label for="hide_meta_box_by_roles_<?php echo esc_attr( $role_name ); ?>" class="selectit">
								<input type="checkbox" name="hide_meta_box_by_roles[<?php echo esc_attr( $role_name ); ?>]" id="hide_meta_box_by_roles_<?php echo esc_attr( $role_name ); ?>" value="1" <?php checked( $this->get_setting( 'hide_meta_box_by_roles', '[' . $role_name . ']' ), 1 ); ?> />
								<?php echo esc_html( $hide_role['name'] ); ?>
							</label><br />
							<?php
						}
						?>

						<p class="description">
							<?php
							printf(
								'<a href="%1$s" target="_blank">%2$s</a>%3$s <strong>%4$s</strong> %5$s',
								esc_url( 'https://www.socialpostflow.com/documentation/wordpress-plugin/per-post-settings/' ),
								esc_html__( 'Per-Post Settings', 'social-post-flow' ),
								esc_html__( ', Additional Images and the Log are hidden when editing Posts and the', 'social-post-flow' ),
								esc_html__( 'logged in WordPress User\'s Role', 'social-post-flow' ),
								esc_html__( 'matches a Role selected above.', 'social-post-flow' )
							);
							?>
							<br />
							<?php
							printf(
								'%1$s <strong>%2$s</strong> %3$s',
								esc_html__( 'To control which social media profiles to send statuses to by the', 'social-post-flow' ),
								esc_html__( 'Post\'s Author\'s Role', 'social-post-flow' ),
								esc_html__( 'use the "Enable Specific Profiles" option above.', 'social-post-flow' )
							);
							?>
						</p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
	?>

	<!-- Custom Tags -->
	<div id="custom-tags" class="panel">
		<div class="postbox">
			<header>
				<h3><?php esc_html_e( 'Custom Tags', 'social-post-flow' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'If your site uses Custom Fields, ACF or similar, you can specify additional tags to be added to the "Insert Tag" dropdown for each of your Post Types.  These can then be used by Users, instead of having to remember the template tag text to use.', 'social-post-flow' ); ?>
				</p>
			</header>

			<?php
			// Iterate through Post Types.
			foreach ( $post_types as $custom_tags_post_type ) {
				?>
				<div class="wpzinc-option">
					<div class="left">
						<label for="custom_tags"><?php echo esc_html( $custom_tags_post_type->label ); ?></label>
					</div>

					<div class="right">
						<table class="striped widefat">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Custom Field Key', 'social-post-flow' ); ?></th>
									<th><?php esc_html_e( 'Custom Field Label', 'social-post-flow' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'social-post-flow' ); ?></th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<td colspan="3">
										<a href="#" class="button wpzinc-add-table-row" data-table-row-selector="custom-tag">
											<?php esc_html_e( 'Add Custom Tag', 'social-post-flow' ); ?>
										</a>
									</td>
								</tr>
							</tfoot>
							<tbody>
								<?php
								$existing_custom_tags = $this->get_setting( 'custom_tags', $custom_tags_post_type->name );
								if ( ! empty( $existing_custom_tags ) && is_array( $existing_custom_tags ) && isset( $existing_custom_tags['key'] ) ) {
									foreach ( $existing_custom_tags['key'] as $index => $existing_custom_tag ) {
										// Skip empty keys.
										if ( empty( $existing_custom_tag ) ) {
											continue;
										}
										?>
										<tr>
											<td>
												<input type="text" name="custom_tags[<?php echo esc_attr( $custom_tags_post_type->name ); ?>][key][]" id="custom_tags" value="<?php echo esc_attr( $existing_custom_tags['key'][ $index ] ); ?>" placeholder="<?php esc_attr_e( 'my_custom_field', 'social-post-flow' ); ?>" class="widefat" />
											</td>
											<td>
												<input type="text" name="custom_tags[<?php echo esc_attr( $custom_tags_post_type->name ); ?>][label][]" value="<?php echo esc_attr( $existing_custom_tags['label'][ $index ] ); ?>" placeholder="<?php esc_attr_e( 'My Custom Field', 'social-post-flow' ); ?>" class="widefat" />
											</td>
											<td>
												<a href="#" class="wpzinc-delete-table-row">
													<span class="dashicons dashicons-trash"></span>
													<?php esc_html_e( 'Delete', 'social-post-flow' ); ?>
												</a>
											</td>
										</tr>
										<?php
									}
								}
								?>
								<tr class="custom-tag hidden">
									<td>
										<input type="text" name="custom_tags[<?php echo esc_attr( $custom_tags_post_type->name ); ?>][key][]" value="" placeholder="<?php esc_attr_e( 'my_custom_field', 'social-post-flow' ); ?>" class="widefat" />
									</td>
									<td>
										<input type="text" name="custom_tags[<?php echo esc_attr( $custom_tags_post_type->name ); ?>][label][]" value="" placeholder="<?php esc_attr_e( 'My Custom Field', 'social-post-flow' ); ?>" class="widefat" />
									</td>
									<td>
										<a href="#" class="wpzinc-delete-table-row">
											<span class="dashicons dashicons-trash"></span>
											<?php esc_html_e( 'Delete', 'social-post-flow' ); ?>
										</a>
									</td>
								</tr>
							</tbody>
						</table> 
					</div>  
				</div> 
				<?php
			}
			?>
		</div>
	</div>
</div>
