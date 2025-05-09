<?php
/**
 * Outputs the Logs WP_List_Table.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 */

?>
<header>
	<h1>
	<?php echo esc_html_e( 'Social Post Flow', 'social-post-flow' ); ?>

		<span>
			<?php esc_html_e( 'Logs', 'social-post-flow' ); ?>
		</span>
	</h1>
</header>

<hr class="wp-header-end" />

<div class="wrap">
	<?php
	// Search Subtitle.
	if ( $table->is_search() ) {
		?>
		<span class="subtitle left"><?php esc_html_e( 'Search results for', 'social-post-flow' ); ?> &#8220;<?php echo esc_html( $table->get_search() ); ?>&#8221;</span>
		<?php
	}
	?>

	<form action="admin.php?page=social-post-flow-log" method="post" id="posts-filter">
		<?php
		// Output Search Box.
		$table->search_box( __( 'Search', 'social-post-flow' ), 'wp-to-social-log' );

		// Output Table.
		$table->display();
		?>
	</form>
</div><!-- /.wrap -->
