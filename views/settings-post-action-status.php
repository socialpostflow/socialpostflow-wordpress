<?php
/**
 * Outputs the single status configuration form.  Its values are populated by statuses.js, based
 * on the status that has been selected for editing.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div id="social-post-flow-status-form-container" class="hidden">
	<div id="social-post-flow-status-form">
		<!-- Status Type and Text -->
		<div class="wpzinc-option status">
			<div class="full">
				<h3><?php esc_html_e( 'Status Type and Text', 'social-post-flow' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'The type of status to create and its text.', 'social-post-flow' ); ?>
				</p>

				<select name="social-post-flow_post_type" class="post_type" size="1">
					<?php
					foreach ( social_post_flow()->get_class( 'common' )->get_status_post_type_options() as $status_post_type_key => $status_post_type ) {
						?>
						<option value="<?php echo esc_attr( $status_post_type_key ); ?>" data-provider="<?php echo esc_attr( implode( ',', $status_post_type['conditions']['provider'] ) ); ?>">
							<?php echo esc_attr( $status_post_type['label'] ); ?>
						</option>
						<?php
					}
					?>
				</select>

				<?php
				// Tags.
				$textarea = 'textarea.text';
				require 'settings-post-action-status-tags.php';
				?>

				<textarea name="social-post-flow_text" rows="3" class="widefat wpzinc-autosize-js text"></textarea>

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
			</div>
		</div>

		<!-- First Comment -->
		<div class="wpzinc-option status">
			<div class="full">
				<h3><?php esc_html_e( 'First Comment', 'social-post-flow' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Optional: Define the first comment to include below the status.', 'social-post-flow' ); ?>
				</p>

				<?php
				// Tags.
				$textarea = 'textarea.first_comment';
				require 'settings-post-action-status-tags.php';
				?>
				<textarea name="social-post-flow_first_comment" rows="3" class="widefat wpzinc-autosize-js first_comment"></textarea>
			</div>
		</div>

		<!-- Schedule -->
		<div class="wpzinc-option">
			<div class="full">
				<h3><?php esc_html_e( 'Schedule', 'social-post-flow' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'When the status should be added to social media.', 'social-post-flow' ); ?>
				</p>
			
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
					 * @since   1.0.0
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
		</div>

		<!-- Link -->
		<div class="wpzinc-option link">
			<div class="full">
				<h3><?php esc_html_e( 'Link', 'social-post-flow' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'The "primary" URL to use for the link preview / card. Additional links can be included in the status text above.', 'social-post-flow' ); ?>
				</p>

				<input type="text" name="social-post-flow_url" class="widefat url" />
			</div>
		</div>

		<!-- Images -->
		<div class="wpzinc-option images">
			<div class="full">
				<h3><?php esc_html_e( 'Image', 'social-post-flow' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'The type of image(s) to use.', 'social-post-flow' ); ?>
				</p>
	
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
									foreach ( social_post_flow()->get_class( 'image' )->get_status_image_options( false, $post_type ) as $value => $image_option ) {
										?>
										<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_attr( $image_option['label'] ); ?></option>
										<?php
									}
									?>
								</select>
							</td>
						</tr>
						<tr class="additional-images" data-conditional-value="<?php echo esc_attr( implode( ',', array_keys( social_post_flow()->get_class( 'image' )->get_status_image_options_supporting_additional_images( false, $post_type ) ) ) ); ?>">
							<td width="20%">
								<label for="social-post-flow_image_additional">
									<?php esc_html_e( 'Additional Images', 'social-post-flow' ); ?>
								</label>
							</td>
							<td>
								<select id="social-post-flow_image_additional" name="social-post-flow_image_additional" size="1">
									<?php
									foreach ( social_post_flow()->get_class( 'image' )->get_status_additional_image_options( false, $post_type ) as $value => $label ) {
										?>
										<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_attr( $label ); ?></option>
										<?php
									}
									?>
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
						<tr class="additional-images" data-conditional-value="<?php echo esc_attr( implode( ',', array_keys( social_post_flow()->get_class( 'image' )->get_status_image_options_supporting_additional_images( false, $post_type ) ) ) ); ?>">
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
						<tr class="text-to-image" data-conditional-value="<?php echo esc_attr( implode( ',', array_keys( social_post_flow()->get_class( 'image' )->get_status_image_options_supporting_text_to_image( false, $post_type ) ) ) ); ?>">
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
						<tr class="text-to-image" data-conditional-value="<?php echo esc_attr( implode( ',', array_keys( social_post_flow()->get_class( 'image' )->get_status_image_options_supporting_text_to_image( false, $post_type ) ) ) ); ?>">
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

		<!-- Post Conditions -->
		<div class="wpzinc-option post-conditions">
			<div class="full">
				<h3><?php esc_html_e( 'Post Conditions', 'social-post-flow' ); ?></h3>
				<p class="description">
					<?php
					esc_html_e( 'Optional: Define Post conditions that are required for this status to be sent to Social Post Flow. All conditions must be met.', 'social-post-flow' );
					?>
				</p>

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
						 * @since   1.0.0
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
	
		<!-- Author Conditions -->
		<div class="wpzinc-option author-conditions">
			<div class="full">
				<h3><?php esc_html_e( 'Author Conditions', 'social-post-flow' ); ?></h3>
				<p class="description">
					<?php
					esc_html_e( 'Optional: Define the Post\'s Author conditions that are required for this status to be sent to Social Post Flow. All conditions must be met.', 'social-post-flow' );
					?>
				</p>

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
