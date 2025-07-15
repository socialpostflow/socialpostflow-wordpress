<?php
/**
 * Outputs Bulk Publish View for a Post Type
 *
 * @since 1.0.0
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 */

?>
<!-- Post Type -->
<div id="<?php echo esc_attr( $post_type ); ?>-panel" class="panel">

	<!-- Post Selection Tool -->
	<div id="post-selection" class="postbox">
		<h3 class="hndle">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %1$s: Post Type Name */
					__( 'Search %1$s to Publish to Social Post Flow', 'social-post-flow' ),
					$post_types[ $post_type ]->labels->name
				)
			);
			?>
		</h3>

		<div class="posts">
			<!-- Post Date -->
			<div class="wpzinc-option">
				<div class="left">
					<label for="start_date"><?php esc_html_e( 'Published Date', 'social-post-flow' ); ?></label>
				</div>
				<div class="right">
					<?php esc_html_e( 'Between', 'social-post-flow' ); ?>
					<input type="date" name="social-post-flow[start_date]" value="<?php echo esc_attr( $params['start_date'] ); ?>" id="start_date" />
					<?php esc_html_e( 'and', 'social-post-flow' ); ?>
					<input type="date" name="social-post-flow[end_date]" value="<?php echo esc_attr( $params['end_date'] ); ?>" />
				</div>
			</div>

			<!-- Post Author -->
			<div class="wpzinc-option">
				<div class="left">
					<label for="authors"><?php esc_html_e( 'Authors', 'social-post-flow' ); ?></label>
				</div>
				<div class="right">
					<input type="text" name="social-post-flow[authors]" id="authors" class="widefat wpzinc-selectize" style="width:100%;" data-action="social_post_flow_search_authors" data-nonce-key="search_authors_nonce" />
				</div>
			</div>

			<!-- Meta -->
			<div class="wpzinc-option">
				<div class="left">
					<label for="custom_field_meta_key"><?php esc_html_e( 'Meta / Custom Fields', 'social-post-flow' ); ?></label>
				</div>
				<div class="right">
					<table class="widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Key', 'social-post-flow' ); ?></th>
								<th><?php esc_html_e( 'Compare', 'social-post-flow' ); ?></th>
								<th><?php esc_html_e( 'Value', 'social-post-flow' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'social-post-flow' ); ?></th>
							</tr>
						</thead>

						<tfoot>
							<tr>
								<th colspan="4">
									<a href="#" class="wpzinc-add-table-row button" data-table-row-selector="custom-field">
										<?php esc_html_e( 'Add Meta / Custom Field Condition', 'social-post-flow' ); ?>
									</a>
								</th>
							</tr>
						</tfoot>

						<tbody>
							<?php
							if ( $params['meta'] ) {
								foreach ( $params['meta'] as $meta ) {
									?>
										<tr class="custom-field">
											<td>
												<input type="text" name="social-post-flow[meta][key][]" id="custom_field_meta_key" placeholder="<?php esc_attr_e( 'Meta Key', 'social-post-flow' ); ?>" value="<?php echo esc_attr( $meta['key'] ); ?>" class="widefat" />
											</td>
											<td>
												<select name="social-post-flow[meta][compare][]" size="1">
												<?php
												foreach ( $custom_field_comparison_operators as $key => $label ) {
													?>
														<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $meta['compare'] ); ?>><?php echo esc_attr( $label ); ?></option>
														<?php
												}
												?>
												</select>
											</td>
											<td>
												<input type="text" name="social-post-flow[meta][value][]" placeholder="<?php esc_attr_e( 'Meta Value', 'social-post-flow' ); ?>" value="<?php echo esc_attr( $meta['value'] ); ?>" class="widefat" />
											</td>
											<td>
												<a href="#" class="wpzinc-delete-table-row button small">
													<?php esc_html_e( 'Remove', 'social-post-flow' ); ?>
												</a>
											</td>
										</tr>
										<?php
								}
							}
							?>
							<tr class="custom-field hide-delete-button">
								<td>
									<input type="text" name="social-post-flow[meta][key][]" id="custom_field_meta_key" placeholder="<?php esc_attr_e( 'Meta Key', 'social-post-flow' ); ?>" class="widefat" />
								</td>
								<td>
									<select name="social-post-flow[meta][compare][]" size="1">
										<?php
										foreach ( $custom_field_comparison_operators as $key => $label ) {
											?>
											<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $label ); ?></option>
											<?php
										}
										?>
									</select>
								</td>
								<td>
									<input type="text" name="social-post-flow[meta][value][]" placeholder="<?php esc_attr_e( 'Meta Value', 'social-post-flow' ); ?>" class="widefat" />
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

			<!-- Search -->
			<div class="wpzinc-option">
				<div class="left">
					<label for="s"><?php esc_html_e( 'Search Terms', 'social-post-flow' ); ?></label>
				</div>
				<div class="right">
					<input type="text" name="social-post-flow[s]" id="s" value="<?php echo esc_attr( $params['s'] ); ?>" class="widefat" />

					<p class="description">
						<?php
						echo esc_html(
							sprintf(
							/* translators: Post Type Name */
								__( 'Will return all %s where the Title or Content contains this value.', 'social-post-flow' ),
								$post_types[ $post_type ]->labels->name
							)
						);
						?>
					</p>
				</div>
			</div>

			<!-- Taxonomies -->
			<?php
			// Output taxonomies.
			foreach ( $taxonomies as $taxonomy_name => $details ) {
				?>
				<div class="wpzinc-option">
					<div class="left">
						<label for="<?php echo esc_attr( $taxonomy_name ); ?>"><?php echo esc_html( $details->labels->singular_name ); ?></label>
					</div>

					<div class="right">
						<input type="text" name="social-post-flow[taxonomies][<?php echo esc_attr( $taxonomy_name ); ?>]" size="1" multiple="multiple" id="<?php echo esc_attr( $taxonomy_name ); ?>" class="widefat wpzinc-selectize" style="width:100%;" data-taxonomy="<?php echo esc_attr( $taxonomy_name ); ?>" data-action="social_post_flow_search_terms" data-nonce-key="search_terms_nonce">
					</div>
				</div>
				<?php
			} // Close loop
			?>

			<!-- Order By and Order -->
			<div class="wpzinc-option">
				<div class="left">
					<label for="orderby"><?php esc_html_e( 'Order By', 'social-post-flow' ); ?></label>
				</div>
				<div class="right">
					<select name="social-post-flow[orderby]" id="orderby" size="1">
						<?php
						foreach ( $orderby as $key => $label ) {
							?>
							<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $params['orderby'] ); ?>><?php echo esc_attr( $label ); ?></option>
							<?php
						}
						?>
					</select>

					<p class="description">
						<?php
						esc_html_e( 'Defines how to order the Posts that will be added to your Social Post Flow queue.', 'social-post-flow' );
						?>
					</p>
				</div>
			</div>

			<div class="wpzinc-option">
				<div class="left">
					<label for="order"><?php esc_html_e( 'Order', 'social-post-flow' ); ?></label>
				</div>
				<div class="right">
					<select name="social-post-flow[order]" id="order" size="1">
						<?php
						foreach ( $order as $key => $label ) {
							?>
							<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $params['order'] ); ?>><?php echo esc_attr( $label ); ?></option>
							<?php
						}
						?>
					</select>

					<p class="description">
						<?php
						esc_html_e( 'Defines the order in which Posts will be added to your Social Post Flow queue.', 'social-post-flow' );
						?>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- /post_type -->

<!-- Buttons -->
<input type="hidden" name="stage" value="1" />
<input type="submit" name="submit" value="<?php esc_attr_e( 'Choose Posts', 'social-post-flow' ); ?>" class="button button-primary" />
