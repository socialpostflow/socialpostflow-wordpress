<?php
/**
 * Outputs a tag dropdown for the status configuration form.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<select size="1" class="left tags" data-textarea="<?php echo esc_attr( $textarea ); ?>">
	<option value=""><?php esc_attr_e( '--- Insert Tag ---', 'social-post-flow' ); ?></option>
	<?php
	foreach ( social_post_flow()->get_class( 'common' )->get_tags( $post_type ) as $tag_group => $tag_group_tags ) {
		?>
		<optgroup label="<?php echo esc_attr( $tag_group ); ?>">
			<?php
			foreach ( $tag_group_tags as $status_tag => $tag_attributes ) {
				// If the tag attributes is an array, this is a more complex tag
				// that requires user input.
				if ( is_array( $tag_attributes ) ) {
					?>
					<option value="<?php echo esc_attr( $status_tag ); ?>" data-question="<?php echo esc_attr( $tag_attributes['question'] ); ?>" data-default-value="<?php echo esc_attr( $tag_attributes['default_value'] ); ?>" data-replace="<?php echo esc_attr( $tag_attributes['replace'] ); ?>"><?php echo esc_attr( $tag_attributes['label'] ); ?></option>
					<?php
				} else {
					?>
					<option value="<?php echo esc_attr( $status_tag ); ?>"><?php echo esc_attr( $tag_attributes ); ?></option>
					<?php
				}
			}
			?>
		</optgroup>
		<?php
	}
	?>
</select>
