<?php
/**
 * Outputs a status setting within a table of statuses for an action (publish,update,repost,bulk publish).
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 */

?>
<tr class="status sortable<?php echo esc_attr( $key === 0 ? ' first' : '' ); ?>" data-status-index="<?php echo esc_attr( $key ); ?>" data-status='<?php echo wp_json_encode( $status, JSON_HEX_APOS ); ?>' data-labels='<?php echo wp_json_encode( $labels, JSON_HEX_APOS ); ?>'>
	<td class="count">#<?php echo esc_html( $key + 1 ); ?></td>
	<td class="actions">
		<a href="#" class="dashicons dashicons-move move-status" title="<?php esc_attr_e( 'Reorder Status', 'social-post-flow' ); ?>"></a>
		<a href="#" class="dashicons dashicons-edit edit-status" title="<?php esc_attr_e( 'Edit Status', 'social-post-flow' ); ?>"></a>
		<a href="#" class="dashicons dashicons-trash delete-status" title="<?php esc_attr_e( 'Delete Status', 'social-post-flow' ); ?>"></a>
	</td>
	<td class="post_type"><?php echo esc_html( $row['post_type'] ); ?></td>
	<td class="text"><?php echo esc_html( $row['text'] ); ?></td>
	<td class="schedule"><?php echo esc_html( $row['schedule'] ); ?></td>
</tr>
