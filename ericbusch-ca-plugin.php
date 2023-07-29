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
 * Return an array of WP_Post objects for post_type 'collection'.
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

/**
 * Return an array of Image (attachment) IDs for a collection where
 * the image is newer than $days old.
 *
 * @param int $post_id
 * @param int $days
 *
 * @return array
 */
function busch_get_new_image_ids_for_collection( int $post_id, int $days = 30 ): array {

	$new_image_ids = [];
	$images        = get_field( 'images', $post_id );

	if ( empty( $images ) ) {
		return $new_image_ids;
	}

	try {
		$now = new DateTime( date_i18n( 'Y-m-d H:i:s' ) );
	} catch ( Exception $e ) {
		return $new_image_ids;
	}

	foreach ( $images as $image ) {

		try {
			$image_date = new DateTime( $image->post_date );
		} catch ( Exception $e ) {
			continue;
		}

		$days_old = $now->diff( $image_date )->days;

		if ( $days_old <= $days ) {
			$new_image_ids[] = $image->ID;
		}
	}

	return $new_image_ids;
}

function busch_get_collection_images( int $post_id, string $size = 'full', $attr = '' ): array {

	$image_ids = get_field( 'images', $post_id, false );

	$images = get_posts( [
		'post__in'       => $image_ids,
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'posts_per_page' => 99,
		'orderby'        => 'post__in'
	] );

	$total_images = count( $images );

	foreach ( $images as $key => $image ) {

		$html = wp_get_attachment_image( $image->ID, $size, false, $attr );
		$meta = wp_get_attachment_metadata( $image->ID );

		if ( $meta['width'] > $meta['height'] ) {
			$orientation = 'landscape';
		} elseif ( $meta['height'] > $meta['width'] ) {
			$orientation = 'portrait';
		} else {
			$orientation = 'square';
		}

		$images[ $key ]->_html             = $html;
		$images[ $key ]->_meta             = $meta;
		$images[ $key ]->_first            = $key === 0;
		$images[ $key ]->_last             = $key === ( $total_images - 1 );
		$images[ $key ]->_orientation      = $orientation;
		$images[ $key ]->_prev_orientation = null;
		$images[ $key ]->_next_orientation = null;
	}

	foreach ( $images as $key => $image ) {
		$next_key = $key < ( $total_images - 1 ) ? $key + 1 : 0;
		$prev_key = $key === 0 ? $total_images - 1 : $key - 1;

		$images[ $key ]->_prev_orientation = $images[ $prev_key ]->_orientation;
		$images[ $key ]->_next_orientation = $images[ $next_key ]->_orientation;
	}

	$cols        = 0;
	$max_per_row = 2;

	foreach ( $images as $key => $image ) {

		if ( in_array( $image->_orientation, [ 'landscape', 'square' ] ) ) {
			$cols += 2;
		} else {
			$cols += 1;
		}




//		if ( in_array( $image->_orientation, [ 'landscape', 'square' ] ) ) {
//			$images[ $key ]->_colspan = 2;
//		} else {
//			if ( $image->_next_orientation !== 'portrait' ) {
//				if ( $image->_prev_orientation !== 'portrait' ) {
//					$images[ $key ]->_colspan = 2;
//				} else {
//					$images[ $key ]->_colspan = 1;
//				}
//			}
//		}


		continue;
//		$images[ $key ]->_colspan = $value;
//
//		if ($value === $max_per_row) {
//			$value = 0;
//		}
//


		// The rest of these conditionals only apply to "portrait"

		if ( $image->_next_orientation === 'portrait' ) {
			$images[ $key ]->_cols = 2;
		}

		if ( $image->_prev_orientation === 'portrait' && $image->_next_orientation !== 'portrait' ) {
			$images[ $key ]->_cols = 2;
		}


//		if ( $image->_last ) {
//			$images[ $key ]->_cols = 2;
//		}

	}

	return $images;
}

function busch_get_attachments( array $ids = [], string $size = 'full', $attr = '' ): array {

	$images = get_posts( [
		'post__in'       => $ids,
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'posts_per_page' => 99,
		'orderby'        => 'post__in',
	] );

	foreach ( $images as $key => $image ) {
		$images[ $key ]->img_html = wp_get_attachment_image( $image->ID, $size, $attr );
	}

	return $images;
}