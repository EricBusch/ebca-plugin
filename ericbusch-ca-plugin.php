<?php
/**
 * Plugin Name: EricBusch.ca Plugin
 * Plugin URI: https://ericbusch.ca/
 * Description: My custom plugin
 * Version: 1.0.0
 * Author: Eric Busch
 * Author URI: https://ericbusch.ca/
 * Text Domain: busch
 */

/**
 * Enable ACF Options page.
 */
if ( function_exists( 'acf_add_options_page' ) ) {
	acf_add_options_page();
}

/**
 * Get background image for homepage.
 *
 * @param string $size
 *
 * @return string|null
 */
function busch_get_homepage_bg_image( string $size = 'full' ): ?string {

	$ids = busch_get_homepage_background_image_ids();

	if ( ! $ids ) {
		return null;
	}

	shuffle( $ids );

	return wp_get_attachment_image_src( $ids[0], $size )[0] ?? null;
}

/**
 * Get array of background image IDs for homepage.
 *
 * @return array
 */
function busch_get_homepage_background_image_ids(): array {

	$rows = get_field( 'background_images', absint( get_option( 'page_on_front' ) ) );

	$ids = [];

	foreach ( $rows as $key => $value ) {
		if ( isset( $value['image']['ID'] ) ) {
			$ids[] = absint( $value['image']['ID'] );
		}
	}

	$ids = array_unique( array_filter( $ids ) );

	return $ids;
}

/**
 * Add custom WordPress Admin Area CSS Styles.
 */
add_action( 'admin_head', function () {
	echo '<style>
	#busch-acf-collection-images .acf-relationship .list {height:850px;}
	#busch-acf-collection-images .acf-relationship .list .acf-rel-item {display:flex;}	
	#busch-acf-collection-images .acf-relationship .list .acf-rel-item .thumbnail {width:75px;height:75px;}	
	#busch-acf-collection-images .acf-relationship .list .acf-rel-item .thumbnail img {max-width:75px;max-height:75px;}
</style>';
} );

/**
 * Return an array of WP_Post objects for post_type "collection".
 *
 * @return WP_Post[]
 */
function busch_get_collections(): array {
	return get_posts( [
		'post_type'        => 'collection',
		'orderby'          => 'modified',
		'order'            => 'DESC',
		'numberposts'      => 99,
		'suppress_filters' => true,
		'post_status'      => is_user_logged_in() ? 'any' : 'publish',
	] );
}

/**
 * Return the number of images a Collection contains.
 *
 * @param int $post_id
 *
 * @return int
 */
function busch_get_image_count_for_collection( int $post_id ): int {
	return count( get_field( 'images', $post_id, false ) );
}
