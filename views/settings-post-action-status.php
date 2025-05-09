<?php
/**
 * Outputs the single status configuration form.  Its values are populated by statuses.js, based
 * on the status that has been selected for editing.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 */

?>
<div id="social-post-flow-status-form-container" class="hidden">
	<div id="social-post-flow-status-form" class="wp-to-social-pro-status-form">
		<div class="wpzinc-option">
			<div class="notice-inline notice-warning pinterest hidden">
				<p>
					<?php
					esc_html_e( 'You need to create at least one Pinterest Board, and then refresh the screen to choose the board to post this status to.', 'social-post-flow' );
					?>
					<a href="https://www.socialpostflow.com/documentation/wordpress/status-settings/#status--choose-a-pinterest-board" target="_blank">
						<?php echo esc_html_e( 'Click here for instructions on creating a Pinterest board.', 'social-post-flow' ); ?>
					</a>
				</p>
			</div>

			<!-- Status Message -->
			<div class="full status">
				<h3><?php esc_html_e( 'Status Text', 'social-post-flow' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'The text to display on social media, and when to share it.', 'social-post-flow' ); ?>
				</p>

				<?php
				// Tags.
				$textarea = 'textarea.message';
				require 'settings-post-action-status-tags.php';

				// Instagram update type.
				if ( $this->base->supports( 'instagram_update_type' ) ) {
					?>
					<select name="social-post-flow_update_type" size="1" class="right">
						<option value=""><?php esc_html_e( 'Post', 'social-post-flow' ); ?></option>
						<option value="story"><?php esc_html_e( 'Story', 'social-post-flow' ); ?></option>
					</select>
					<?php
				}
				?>

				<textarea name="social-post-flow_message" rows="3" class="widefat wpzinc-autosize-js message"></textarea>

				<?php
				// If we're editing a Post, Page or CPT, show the chararcter count.
				if ( isset( $post ) && ! empty( $post ) ) {
					?>
					<small class="characters">
						<span class="character-count"></span>
						<?php esc_html_e( 'characters', 'social-post-flow' ); ?>
					</small>
					<?php
				}
				?>
			
				<select name="social-post-flow_schedule" size="1" class="schedule widefat">
					<?php
					foreach ( social_post_flow()->get_class( 'common' )->get_schedule_options( $post_type, $is_post_screen ) as $schedule_option => $label ) {
						?>
						<option value="<?php echo esc_attr( $schedule_option ); ?>"><?php echo esc_attr( $label ); ?></option>
						<?php
					}
					?>
				</select> 

				<div class="schedule">
					<span class="hours_mins_secs">
						<!-- Days, Hours, Minutes -->
						<input type="number" name="social-post-flow_days" id="days" min="0" max="9999" step="1" value="" />
						<label for="<?php echo esc_attr( $profile_id ); ?>_status_<?php echo esc_attr( $key ); ?>_days"><?php esc_html_e( 'Days, ', 'social-post-flow' ); ?></label>

						<input type="number" name="social-post-flow_hours" id="hours" />
						<label for="<?php echo esc_attr( $profile_id ); ?>_status_<?php echo esc_attr( $key ); ?>_hours"><?php esc_html_e( 'Hours, ', 'social-post-flow' ); ?></label>

						<input type="number" name="social-post-flow_minutes" id="minutes" />
						<label for="<?php echo esc_attr( $profile_id ); ?>_status_<?php echo esc_attr( $key ); ?>_minutes"><?php esc_html_e( 'Minutes', 'social-post-flow' ); ?></label>
					</span>

					<span class="relative">
						<select name="social-post-flow_schedule_relative_day" id="schedule_relative_day" size="1">
							<?php
							foreach ( social_post_flow()->get_class( 'common' )->get_schedule_relative_days() as $day => $label ) {
								?>
								<option value="<?php echo esc_attr( $day ); ?>"><?php echo esc_attr( $label ); ?></option>
								<?php
							}
							?>
						</select>

						<?php esc_html_e( 'at', 'social-post-flow' ); ?>

						<input type="time" name="social-post-flow_schedule_relative_time" id="schedule_relative_time" />
					</span>

					<span class="custom"></span>

					<span class="custom_field">
						<select name="social-post-flow_schedule_custom_field_relation" size="1">
							<?php
							foreach ( social_post_flow()->get_class( 'common' )->get_schedule_custom_relation_options() as $schedule_option => $label ) {
								?>
								<option value="<?php echo esc_attr( $schedule_option ); ?>"><?php echo esc_attr( $label ); ?></option>
								<?php
							}
							?>
						</select> 
						<input type="text" name="social-post-flow_schedule_custom_field_name" placeholder="<?php esc_attr_e( 'Custom Meta Field Name', 'social-post-flow' ); ?>" />
					</span>

					<?php
					/**
					 * Output Schedule settings for Integrations / Third Party Plugins
					 *
					 * @since   4.4.0
					 *
					 * @param   string  $post_type  Post Type
					 */
					do_action( 'social_post_flow_output_schedule_options_form_fields', $post_type );
					?>

					<span class="specific">
						<input type="datetime-local" name="social-post-flow_schedule_specific" class="widefat" placeholder="<?php esc_attr_e( 'Date and Time', 'social-post-flow' ); ?>" />   
					</span>
				</div>
			</div>

			<!-- Pinterest -->
			<div class="full section conditional pinterest hidden">
				<h3><?php esc_html_e( 'Pinterest', 'social-post-flow' ); ?></h3>
				<p class="description">
					<?php
					esc_html_e( 'Define the Pinterest Board for this status to be sent to.', 'social-post-flow' );
					?>
				</p>

				<div class="wpzinc-option no-styling">
					<div class="full">
						<table class="widefat fixed striped">
							<tbody>
								<tr>
									<td width="20%">
										<label for="social-post-flow_sub_profile">
											<?php esc_html_e( 'Board', 'social-post-flow' ); ?>
										</label>
									</td>
									<td>
										<!-- Pinterest: Sub Profile -->
										<select name="social-post-flow_sub_profile" id="social-post-flow_sub_profile" size="1" class="widefat"></select> 
										<input type="url" name="social-post-flow_sub_profile" id="social-post-flow_sub_profile" placeholder="<?php esc_attr_e( 'Pinterest Board URL', 'social-post-flow' ); ?>" class="widefat" />
									</td>
								</tr>

								<?php
								if ( $this->base->supports( 'pinterest_title' ) ) {
									?>
									<tr>
										<td>
											<label for="pinterest_title">
												<?php esc_html_e( 'Pin Title', 'social-post-flow' ); ?>
											</label>
										</td>
										<td>
											<input type="text" name="social-post-flow_title" id="pinterest_title" placeholder="<?php esc_attr_e( 'Pin Title', 'social-post-flow' ); ?>" class="widefat" />
											<p class="description">
												<?php esc_html_e( 'An optional title. Text Tags are supported.', 'social-post-flow' ); ?>
											</p>
										</td>
									</tr>
									<?php
								}

								if ( $this->base->supports( 'pinterest_source_url' ) ) {
									?>
									<tr>
										<td>
											<label for="pinterest_source_url">
												<?php esc_html_e( 'Destination Link', 'social-post-flow' ); ?>
											</label>
										</td>
										<td>
											<input type="text" name="social-post-flow_source_url" id="pinterest_source_url" placeholder="<?php esc_attr_e( 'e.g. https://example.com or use {url}', 'social-post-flow' ); ?>" class="widefat" />
											<p class="description">
												<?php esc_html_e( 'The URL to link the Pin to. If no URL is entered, the Post\'s URL will be used. Text Tags are supported.', 'social-post-flow' ); ?>
											</p>
										</td>
									</tr>
									<?php
								}
								?>
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<?php
			if ( $this->base->supports( 'googlebusiness' ) ) {
				?>
				<!-- Google Business Profile -->
				<div class="full section conditional googlebusiness hidden">
					<h3><?php esc_html_e( 'Google Business Profile', 'social-post-flow' ); ?></h3>
					<p class="description">
						<?php
						echo esc_html_e( 'Optional: Define the status type (What\'s New, Offer or Event) and additional structured fields / data.', 'social-post-flow' );
						?>
					</p>

					<div class="wpzinc-option no-styling">
						<div class="full">
							<table class="widefat fixed striped">
								<tbody>
									<tr>
										<td width="20%">
											<label for="googlebusiness_post_type">
												<?php esc_html_e( 'Post Type', 'social-post-flow' ); ?>
											</label>
										</td>
										<td>
											<select name="social-post-flow_googlebusiness[post_type]" id="googlebusiness_post_type" size="1" class="widefat">
												<option value="whats_new"><?php esc_attr_e( 'What\'s New', 'social-post-flow' ); ?></option>
												<option value="offer"><?php esc_attr_e( 'Offer', 'social-post-flow' ); ?></option>
												<option value="event"><?php esc_attr_e( 'Event', 'social-post-flow' ); ?></option>
											</select>
										</td>
									</tr>
									<tr class="whats_new event">
										<td>
											<label for="googlebusiness_cta">
												<?php esc_html_e( 'Call to Action', 'social-post-flow' ); ?>
											</label>
										</td>
										<td>
											<select name="social-post-flow_googlebusiness[cta]" id="googlebusiness_cta" size="1" class="widefat">
												<option value="book"><?php esc_attr_e( 'Book', 'social-post-flow' ); ?></option>
												<option value="order"><?php esc_attr_e( 'Order', 'social-post-flow' ); ?></option>
												<option value="shop"><?php esc_attr_e( 'Shop', 'social-post-flow' ); ?></option>
												<option value="learn_more"><?php esc_attr_e( 'Learn More', 'social-post-flow' ); ?></option>
												<option value="signup"><?php esc_attr_e( 'Sign Up', 'social-post-flow' ); ?></option>
											</select>
										</td>
									</tr>
									<tr class="offer event">
										<td>
											<label for="googlebusiness_start_date_option">
												<?php esc_html_e( 'Start Date', 'social-post-flow' ); ?>
											</label>
										</td>
										<td>
											<select name="social-post-flow_googlebusiness[start_date_option]" id="googlebusiness_start_date_option" size="1" class="widefat">
												<?php
												foreach ( social_post_flow()->get_class( 'common' )->get_google_business_start_date_options( $post_type ) as $schedule_option => $label ) {
													?>
													<option value="<?php echo esc_attr( $schedule_option ); ?>"><?php echo esc_attr( $label ); ?></option>
													<?php
												}
												?>
											</select>

											<input type="text" name="social-post-flow_googlebusiness[start_date]" id="googlebusiness_start_date" placeholder="<?php esc_attr_e( 'Custom Meta Field Name', 'social-post-flow' ); ?>" />
										</td>
									</tr>
									<tr class="offer event">
										<td>
											<label for="googlebusiness_end_date_option">
												<?php esc_html_e( 'End Date', 'social-post-flow' ); ?>
											</label>
										</td>
										<td>
											<select name="social-post-flow_googlebusiness[end_date_option]" id="googlebusiness_end_date_option" size="1" class="widefat">
												<?php
												foreach ( social_post_flow()->get_class( 'common' )->get_google_business_end_date_options( $post_type ) as $schedule_option => $label ) {
													?>
													<option value="<?php echo esc_attr( $schedule_option ); ?>"><?php echo esc_attr( $label ); ?></option>
													<?php
												}
												?>
											</select>

											<input type="text" name="social-post-flow_googlebusiness[end_date]" id="googlebusiness_end_date" placeholder="<?php esc_attr_e( 'Custom Meta Field Name', 'social-post-flow' ); ?>" />
										</td>
									</tr>
									<tr class="offer event">
										<td>
											<label for="googlebusiness_title">
												<?php esc_html_e( 'Event / Offer Title', 'social-post-flow' ); ?>
											</label>
										</td>
										<td>
											<input type="text" name="social-post-flow_googlebusiness[title]" id="googlebusiness_title" class="widefat" />
										</td>
									</tr>
									<tr class="offer">
										<td>
											<label for="googlebusiness_code">
												<?php esc_html_e( 'Coupon Code', 'social-post-flow' ); ?>
											</label>
										</td>
										<td>
											<input type="text" name="social-post-flow_googlebusiness[code]" id="googlebusiness_code" class="widefat" />
										</td>
									</tr>
									<tr class="offer">
										<td>
											<label for="googlebusiness_terms">
												<?php esc_html_e( 'Terms and Conditions Text', 'social-post-flow' ); ?>
											</label>
										</td>
										<td>
											<input type="text" name="social-post-flow_googlebusiness[terms]" id="googlebusiness_terms" class="widefat" />
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<?php
			}
			?>

			<!-- Images -->
			<div class="full section images">
				<h3><?php esc_html_e( 'Image', 'social-post-flow' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'The type of link preview / image(s) to use.', 'social-post-flow' ); ?>
				</p>

				<div class="wpzinc-option no-styling">
					<div class="full">
						<table class="widefat fixed striped">
							<tbody>
								<tr>
									<td width="20%">
										<label for="social-post-flow_image">
											<?php esc_html_e( 'Image', 'social-post-flow' ); ?>
										</label>
									</td>
									<td>
										<select id="social-post-flow_image" name="social-post-flow_image" size="1" class="image">
											<?php
											foreach ( social_post_flow()->get_class( 'image' )->get_featured_image_options( $post_type ) as $value => $label ) {
												?>
												<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_attr( $label ); ?></option>
												<?php
											}
											?>
										</select>
									</td>
								</tr>
								<tr class="additional-images">
									<td width="20%">
										<label for="social-post-flow_image_additional">
											<?php esc_html_e( 'Additional Images', 'social-post-flow' ); ?>
										</label>
									</td>
									<td>
										<select id="social-post-flow_image_additional" name="social-post-flow_image_additional" size="1">
											<option value=""><?php esc_html_e( 'Specified in Post settings', 'social-post-flow' ); ?></option>
											<option value="1"><?php esc_html_e( 'Auto populate from Post content', 'social-post-flow' ); ?></option>
										</select>
										<p class="description">
											<?php
											printf(
												'<code>%s</code>: %s',
												esc_html__( 'Specified in Post settings', 'social-post-flow' ),
												esc_html__( 'Include additional images in the status if specified in the "Featured and Additional Images" settings on the individual Post.', 'social-post-flow' )
											);
											?>
											<br />
											<?php
											printf(
												'<code>%s</code>: %s',
												esc_html__( 'Auto populate from Post content', 'social-post-flow' ),
												esc_html__( 'Include additional images in the status from the Post\'s content.', 'social-post-flow' )
											);
											?>
										</p>
									</td>
								</tr>
								<tr class="additional-images">
									<td width="20%">
										<label for="social-post-flow_image_additional_limit">
											<?php esc_html_e( 'Limit', 'social-post-flow' ); ?>
										</label>
									</td>
									<td>
										<input type="number" id="social-post-flow_image_additional_limit" name="social-post-flow_image_additional_limit" min="1" max="9 step="1" />
										<p class="description">
											<?php
											esc_html_e( 'The maximum number of images to include with this status. If this exceeds the social media platform\'s limit, the extra images will be ignored.', 'social-post-flow' );
											?>
										</p>
									</td>
								</tr>
								<tr class="text-to-image">
									<td width="20%">
										<label for="social-post-flow_text_to_image">
											<?php esc_html_e( 'Text to Image', 'social-post-flow' ); ?>
										</label>
									</td>
									<td>
										<?php
										$textarea = 'textarea.text-to-image';
										require 'settings-post-action-status-tags.php';
										?>
									
										<textarea id="social-post-flow_text_to_image" name="social-post-flow_text_to_image" rows="3" class="widefat wpzinc-autosize-js text-to-image"></textarea>
										<p class="description">
											<?php
											esc_html_e( 'Define the text to convert to an image, which will be sent with this status.', 'social-post-flow' );
											?>
										</p>
									</td>
								</tr>
								<tr class="text-to-image">
									<td width="20%">
										<label for="social-post-flow_text_to_image_background_image">
											<?php esc_html_e( 'Background Image', 'social-post-flow' ); ?>
										</label>
									</td>
									<td>
										<select id="social-post-flow_text_to_image_background_image" name="social-post-flow_text_to_image_type" size="1" data-conditional="text_to_image_background_image" data-conditional-value="background">
											<option value=""><?php esc_attr_e( 'Use Plugin Settings', 'social-post-flow' ); ?></option>
											<option value="featured"><?php esc_attr_e( 'Use Post\'s Featured Image', 'social-post-flow' ); ?></option>
											<option value="background"><?php esc_attr_e( 'Use Background Image', 'social-post-flow' ); ?></option>
										</select>

										<div id="text_to_image_background_image" 
											class="full wpzinc-media-library-selector"
											data-input-name="text_to_image_background_image"
											data-input-url="text_to_image_background_image_url"
											data-file-type="image"
											data-output-size="small">
											<ul class="images">
												<li class="wpzinc-media-library-attachment">
													<div class="wpzinc-media-library-insert">
														<input type="hidden" name="text_to_image_background_image" value="" />
														<input type="hidden" name="text_to_image_background_image_url" value="" />
														<img src="" />
													</div>
													<a href="#" class="wpzinc-media-library-remove" title="<?php esc_attr_e( 'Remove Background Image', 'social-post-flow' ); ?>"><?php esc_html_e( 'Remove', 'social-post-flow' ); ?></a>
												</li>
											</ul>

											<button class="wpzinc-media-library-insert button button-secondary">
												<?php esc_html_e( 'Select Background Image', 'social-post-flow' ); ?>
											</button>
										</div>

										<p class="description">
											<?php
											esc_html_e( 'Use Plugin Settings: Use the background image settings specified in Settings > Text to Image.', 'social-post-flow' );
											?>
											<br />
											<?php
											esc_html_e( 'Use Post\'s Featured Image: Use the Post\'s Featured Image as the background image.', 'social-post-flow' );
											?>
											<br />
											<?php
											esc_html_e( 'Use Background Image: Specify a background image below.', 'social-post-flow' );
											?>
										</p>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<!-- Post Conditions -->
			<div class="full section post-conditions">
				<h3><?php esc_html_e( 'Post Conditions', 'social-post-flow' ); ?></h3>
				<p class="description">
					<?php
					echo esc_html(
						sprintf(
							/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
							__( 'Optional: Define Post conditions that are required for this status to be sent to %s. All conditions must be met.', 'social-post-flow' ),
							$this->base->plugin->account
						)
					);
					?>
				</p>

				<!-- Post -->
				<div class="wpzinc-option no-styling">
					<div class="full">
						<table class="widefat fixed striped">
							<thead>
								<tr>
									<th width="20%"><?php esc_html_e( 'Attribute', 'social-post-flow' ); ?></th>
									<th><?php esc_html_e( 'Compare', 'social-post-flow' ); ?></th>
									<th><?php esc_html_e( 'Value', 'social-post-flow' ); ?></th>
									<th class="actions"><?php esc_html_e( 'Actions', 'social-post-flow' ); ?></th>
								</tr>
							</thead>

							<tfoot>
								<tr>
									<th colspan="4">
										<a href="#" class="button wpzinc-add-table-row" data-table-row-selector="custom-field">
											<?php esc_html_e( 'Add Meta / Custom Field Condition', 'social-post-flow' ); ?>
										</a>
									</th>
								</tr>
							</tfoot>

							<tbody>
								<tr>
									<td width="20%">
										<label for="post_title_compare" data-for="post_title_compare_index">
											<?php esc_html_e( 'Title', 'social-post-flow' ); ?>
										</label>
									</td>
									<td>
										<select name="social-post-flow_post_title[compare]" id="post_title_compare" data-id="post_title_compare_index" size="1" class="widefat">
											<option value="0"><?php esc_attr_e( 'No Conditions', 'social-post-flow' ); ?></option>
											<?php
											foreach ( social_post_flow()->get_class( 'common' )->get_comparison_operators() as $comparison_key => $label ) {
												?>
												<option value="<?php echo esc_attr( $comparison_key ); ?>"><?php echo esc_attr( $label ); ?></option>
												<?php
											}
											?>
										</select>
									</td>
									<td>
										<input type="text" name="social-post-flow_post_title[value]" class="widefat" />    
									</td>
									<td class="actions">&nbsp;</td>
								</tr>

								<tr>
									<td>
										<label for="post_excerpt_compare" data-for="post_excerpt_compare_index">
											<?php esc_html_e( 'Excerpt', 'social-post-flow' ); ?>
										</label>
									</td>
									<td>
										<select name="social-post-flow_post_excerpt[compare]" id="post_excerpt_compare" data-id="post_excerpt_compare_index" size="1" class="widefat">
											<option value="0"><?php esc_html_e( 'No Conditions', 'social-post-flow' ); ?></option>
											<?php
											foreach ( social_post_flow()->get_class( 'common' )->get_custom_field_comparison_operators() as $comparison_key => $label ) {
												?>
												<option value="<?php echo esc_attr( $comparison_key ); ?>"><?php echo esc_attr( $label ); ?></option>
												<?php
											}
											?>
										</select>
									</td>
									<td>
										<input type="text" name="social-post-flow_post_excerpt[value]" class="widefat" />    
									</td>
									<td class="actions">&nbsp;</td>
								</tr>

								<tr>
									<td>
										<label for="post_content_compare" data-for="post_content_compare_index">
											<?php esc_html_e( 'Content', 'social-post-flow' ); ?>
										</label>
									</td>
									<td>
										<select name="social-post-flow_post_content[compare]" id="post_content_compare" data-id="post_content_compare_index" size="1" class="widefat">
											<option value="0"><?php esc_html_e( 'No Conditions', 'social-post-flow' ); ?></option>
											<?php
											foreach ( social_post_flow()->get_class( 'common' )->get_custom_field_comparison_operators() as $comparison_key => $label ) {
												?>
												<option value="<?php echo esc_attr( $comparison_key ); ?>"><?php echo esc_attr( $label ); ?></option>
												<?php
											}
											?>
										</select>
									</td>
									<td>
										<input type="text" name="social-post-flow_post_content[value]" class="widefat" />    
									</td>
									<td class="actions">&nbsp;</td>
								</tr>

								<tr>
									<td>
										<label for="start_date_compare" data-for="start_date_compare_index">
											<?php esc_html_e( 'Start Date', 'social-post-flow' ); ?>
										</label>
									</td>
									<td>
										<select name="social-post-flow_start_date[month]" id="start_date_compare" data-id="start_date_compare_index" size="1" class="widefat">
											<option value=""><?php esc_html_e( 'Any Month', 'social-post-flow' ); ?></option>
											<?php
											for ( $month = 1; $month <= 12; $month++ ) {
												?>
												<option value="<?php echo esc_attr( $month ); ?>"><?php echo esc_attr( DateTime::createFromFormat( '!m', $month )->format( 'F' ) ); ?></option>
												<?php
											}
											?>
										</select>
									</td>
									<td>
										<input type="number" name="social-post-flow_start_date[day]" placeholder="<?php esc_attr_e( 'e.g. 1', 'social-post-flow' ); ?>" class="widefat" />    
									</td>
									<td>&nbsp;</td>
								</tr>

								<tr>
									<td>
										<label for="end_date_compare" data-for="end_date_compare_index">
											<?php esc_html_e( 'End Date', 'social-post-flow' ); ?>
										</label>
									</td>
									<td>
										<select name="social-post-flow_end_date[month]" id="end_date_compare" data-id="end_date_compare_index" size="1" class="widefat">
											<option value=""><?php esc_html_e( 'Any Month', 'social-post-flow' ); ?></option>
											<?php
											for ( $month = 1; $month <= 12; $month++ ) {
												?>
												<option value="<?php echo esc_attr( $month ); ?>"><?php echo esc_attr( DateTime::createFromFormat( '!m', $month )->format( 'F' ) ); ?></option>
												<?php
											}
											?>
										</select>
									</td>
									<td>
										<input type="number" name="social-post-flow_end_date[day]" placeholder="<?php esc_attr_e( 'e.g. 30', 'social-post-flow' ); ?>" class="widefat" />    
									</td>
									<td>&nbsp;</td>
								</tr>

								<?php
								/**
								 * Output condition settings for Integrations / Third Party Plugins
								 *
								 * @since   5.1.2
								 *
								 * @param   string  $post_type  Post Type
								 */
								do_action( 'social_post_flow_output_condition_form_fields', $post_type );

								/**
								 * Conditions: Taxonomies
								 */
								$taxonomies = social_post_flow()->get_class( 'common' )->get_taxonomies( $post_type );
								if ( is_array( $taxonomies ) && count( $taxonomies ) > 0 ) {
									foreach ( $taxonomies as $taxonomy_name => $details ) {
										?>
										<tr>
											<td>
												<label for="<?php echo esc_attr( $taxonomy_name ); ?>_compare" data-for="<?php echo esc_attr( $taxonomy_name ); ?>_compare_index">
													<?php echo esc_html( $details->labels->singular_name ); ?>
												</label>
											</td>
											<td>
												<select name="social-post-flow_conditions[<?php echo esc_attr( $taxonomy_name ); ?>]" id="<?php echo esc_attr( $taxonomy_name ); ?>_compare" data-id="<?php echo esc_attr( $taxonomy_name ); ?>_compare_index" size="1" class="widefat" data-conditional="terms" class="widefat">
													<?php
													foreach ( (array) social_post_flow()->get_class( 'common' )->get_condition_options() as $value => $label ) {
														?>
														<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_attr( $label ); ?></option>
														<?php
													}
													?>
												</select>
											</td>
											<td>
												<input type="text" name="social-post-flow_terms[<?php echo esc_attr( $taxonomy_name ); ?>]" id="<?php echo esc_attr( $taxonomy_name ); ?>" class="widefat wpzinc-selectize" style="width:100%;" data-action="social_post_flow_search_terms"  data-nonce-key="search_terms_nonce" data-taxonomy="<?php echo esc_attr( $taxonomy_name ); ?>" />
											</td>
											<td>&nbsp;</td>
										</tr>
										<?php
									}
								}

								/**
								 * Custom Fields
								 */
								?>
								<tr class="custom-field hide-delete-button">
									<td>
										<input type="text" name="social-post-flow_custom_fields[key][]" data-name="social-post-flow_custom_fields[key][]" placeholder="<?php esc_attr_e( 'Meta Key', 'social-post-flow' ); ?>" class="widefat" />
									</td>
									<td>
										<select name="social-post-flow_custom_fields[compare][]" data-name="social-post-flow_custom_fields[compare][]" size="1" class="widefat">
											<?php
											foreach ( social_post_flow()->get_class( 'common' )->get_custom_field_comparison_operators() as $comparison_key => $label ) {
												?>
												<option value="<?php echo esc_attr( $comparison_key ); ?>"><?php echo esc_attr( $label ); ?></option>
												<?php
											}
											?>
										</select>
									</td>
									<td>
										<input type="text" name="social-post-flow_custom_fields[value][]" data-name="social-post-flow_custom_fields[value][]" placeholder="<?php esc_attr_e( 'Meta Value', 'social-post-flow' ); ?>" class="widefat" />
									</td>
									<td>
										<a href="#" class="wpzinc-delete-table-row button small">
											<?php esc_html_e( 'Remove', 'social-post-flow' ); ?>
										</a>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<!-- Author Conditions -->
			<div class="full section author-conditions">
				<h3><?php esc_html_e( 'Author Conditions', 'social-post-flow' ); ?></h3>
				<p class="description">
					<?php
					echo esc_html(
						sprintf(
							/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
							__( 'Optional: Define the Post\'s Author conditions that are required for this status to be sent to %s. All conditions must be met.', 'social-post-flow' ),
							$this->base->plugin->account
						)
					);
					?>
				</p>

				<div class="wpzinc-option no-styling">
					<div class="full">
						<table class="widefat fixed striped">
							<thead>
								<tr>
									<th width="20%"><?php esc_html_e( 'Attribute', 'social-post-flow' ); ?></th>
									<th><?php esc_html_e( 'Compare', 'social-post-flow' ); ?></th>
									<th><?php esc_html_e( 'Value', 'social-post-flow' ); ?></th>
									<th class="actions"><?php esc_html_e( 'Actions', 'social-post-flow' ); ?></th>
								</tr>
							</thead>

							<tfoot>
								<tr>
									<th colspan="4">
										<a href="#" class="button wpzinc-add-table-row" data-table-row-selector="authors-custom-field">
											<?php esc_html_e( 'Add Custom Field Condition', 'social-post-flow' ); ?>
										</a>
									</th>
								</tr>
							</tfoot>

							<tbody>
								<tr>
									<td width="20%">
										<label for="social-post-flow_authors_compare" data-for="authors_index">
											<?php esc_html_e( 'Author', 'social-post-flow' ); ?>
										</label>
									</td>
									<td>
										<select id="social-post-flow_authors_compare" name="social-post-flow_authors_compare" size="1" class="widefat">
											<option value="="><?php esc_html_e( 'Equals', 'social-post-flow' ); ?></option>
											<option value="!="><?php esc_html_e( 'Does not Equal', 'social-post-flow' ); ?></option>
										</select>
									</td>
									<td>
										<input type="text" name="social-post-flow_authors" id="authors" class="widefat wpzinc-selectize" style="width:100%;" data-action="social_post_flow_search_authors" data-nonce-key="search_authors_nonce" />
									</td>
									<td>&nbsp;</td>
								</tr>
								<tr>
									<td>
										<label for="social-post-flow_authors_roles_compare" data-for="authors_role_index">
											<?php esc_html_e( 'Role', 'social-post-flow' ); ?>
										</label>
									</td>
									<td>
										<select id="social-post-flow_authors_roles_compare" name="social-post-flow_authors_roles_compare" size="1" class="widefat">
											<option value="="><?php esc_html_e( 'Equals', 'social-post-flow' ); ?></option>
											<option value="!="><?php esc_html_e( 'Does not Equal', 'social-post-flow' ); ?></option>
										</select>
									</td>
									<td>
										<input type="text" name="social-post-flow_authors_roles" id="authors_roles" class="widefat wpzinc-selectize" style="width:100%;" data-action="social_post_flow_search_roles" data-nonce-key="search_roles_nonce" />
									</td>
									<td class="actions">&nbsp;</td>
								</tr>

								<?php
								/**
								 * Custom Fields
								 */
								?>
								<tr class="authors-custom-field hide-delete-button">
									<td>
										<input type="text" name="social-post-flow_authors_custom_fields[key][]" data-name="social-post-flow_authors_custom_fields[key][]" placeholder="<?php esc_attr_e( 'Author Meta Key', 'social-post-flow' ); ?>" class="widefat" />
									</td>
									<td>
										<select name="social-post-flow_authors_custom_fields[compare][]" data-name="social-post-flow_authors_custom_fields[compare][]" size="1" class="widefat">
											<?php
											foreach ( social_post_flow()->get_class( 'common' )->get_custom_field_comparison_operators() as $comparison_key => $label ) {
												?>
												<option value="<?php echo esc_attr( $comparison_key ); ?>"><?php echo esc_attr( $label ); ?></option>
												<?php
											}
											?>
										</select>
									</td>
									<td>
										<input type="text" name="social-post-flow_authors_custom_fields[value][]" data-name="social-post-flow_authors_custom_fields[value][]" placeholder="<?php esc_attr_e( 'Author Meta Value', 'social-post-flow' ); ?>" class="widefat" />
									</td>
									<td>
										<a href="#" class="wpzinc-delete-table-row button small">
											<?php esc_html_e( 'Remove', 'social-post-flow' ); ?>
										</a>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
