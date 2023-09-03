<?php
/**
 * Plugin Name: EBCA Plugin
 * Description: My custom plugin
 * Version: 1.0.5
 * Author: eb
 * Text Domain: busch
 */

defined( 'ABSPATH' ) || exit;

const BUSCH_VERSION = '1.0.5';

define( 'BUSCH_URL', plugin_dir_url( __FILE__ ) ); // https://example.com/wp-content/plugins/ebca-plugin/
define( 'BUSCH_PATH', plugin_dir_path( __FILE__ ) ); // /absolute/path/to/wp-content/plugins/ebca-plugin/

/**
 * Force ACF to load a Default Value for the "Added at" field for Images in a
 * Gallery inside the Flexible Content.
 */
add_filter( 'acf/load_field/name=added_at', function ( $field ) {
	$field['default_value'] = date_i18n( 'Ymd' );

	return $field;
} );

/**
 * Enqueue Lightbox Scripts
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script( 'fslb-js', BUSCH_URL . 'js/fslightbox/fslightbox.js', [], BUSCH_VERSION, true );
	wp_enqueue_script( 'busch-fslb-js', BUSCH_URL . 'js/fslightbox/custom.js', [ 'fslb-js' ], BUSCH_VERSION, true );
} );

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
	#busch-acf-collection-images .acf-relationship .list .acf-rel-item .thumbnail {width:75px;height:75px;background:white;}	
	#busch-acf-collection-images .acf-relationship .list .acf-rel-item .thumbnail img {max-width:75px;max-height:75px;}
</style>';
} );

/**
 * Return an array of WP_Post objects for post_type 'collection'.
 *
 * @return WP_Post[]
 */
function busch_get_collections( array $overrides = [] ): array {
	return get_posts( [
		'post_type'        => 'collection',
		'suppress_filters' => true,
		'orderby'          => $overrides['orderby'] ?? 'modified',
		'order'            => $overrides['order'] ?? 'DESC',
		'numberposts'      => $overrides['numberposts'] ?? 99,
		'post_status'      => $overrides['post_status'] ?? ( is_user_logged_in() ? 'any' : 'publish' ),
	] );
}

/**
 * Returns an array of Attachment IDs associated with a Collection.
 *
 * @param int $post_id Collection ID.
 *
 * @return int[]
 */
function busch_get_all_attachment_ids_for_collection( int $post_id ): array {

	$attachment_ids = [];

	if ( ! have_rows( 'flexible_content', $post_id ) ) {
		return $attachment_ids;
	}

	while ( have_rows( 'flexible_content', $post_id ) ) {

		the_row();

		if ( get_row_layout() !== 'gallery' ) {
			continue;
		}

		$ids = array_map( function ( $image ) {
			return absint( $image['attachment_id'] );
		}, get_sub_field( 'images' ) );

		foreach ( $ids as $id ) {
			$attachment_ids[] = absint( $id );
		}
	}

	return array_filter( array_unique( $attachment_ids ) );
}

/**
 * Returns an array of Attachment IDs associated with a Collection.
 *
 * @param int $post_id Collection ID.
 *
 * @return int[]
 */
function busch_get_newest_attachment_ids_for_collection( int $post_id, int $days = 30 ): array {

	$attachment_ids = [];

	try {
		$now = new DateTime( date_i18n( 'Y-m-d' ) );
	} catch ( Exception $e ) {
		return $attachment_ids;
	}

	if ( ! have_rows( 'flexible_content', $post_id ) ) {
		return $attachment_ids;
	}

	while ( have_rows( 'flexible_content', $post_id ) ) {

		the_row();

		if ( get_row_layout() !== 'gallery' ) {
			continue;
		}

		foreach ( get_sub_field( 'images' ) as $image ) {

			try {
				/**
				 * $image[
				 *      'attachment_id' => 123,
				 *      'added_at'      => 2023-09-02
				 * ]
				 */
				$image_date = new DateTime( $image['added_at'] );
			} catch ( Exception $e ) {
				return $attachment_ids;
			}

			$days_old = $now->diff( $image_date )->days;

			if ( $days_old <= $days ) {
				$attachment_ids[] = $image['attachment_id'];
			}
		}
	}

	return array_filter( array_unique( $attachment_ids ) );
}

/**
 * Return the number of images a Collection contains.
 *
 * @param int $post_id
 *
 * @return int
 */
function busch_get_image_count_for_collection( int $post_id ): int {
	return count( busch_get_all_attachment_ids_for_collection( $post_id ) );
}

/**
 * Return an array of images for a given Collection.
 *
 * @param int $post_id Collection ID.
 *
 * @return WP_Post[]
 */
function busch_get_collection_images( int $post_id ): array {

	$images = [];
	$rows   = get_field( 'images2', $post_id );

	if ( ! $rows ) {
		return $images;
	}

	foreach ( $rows as $row ) {
		if ( $post = get_post( $row['image'] ) ) {
			$images[] = $post;
		}
	}

	return $images;
}

/**
 * Returns an array of images (as WP_Post objects) but with some additional properties
 * available to make formatting in template easier.
 *
 * @param int $attachment_ids An array of Attachment IDs.
 * @param string $size Optional. Size to display (though <img> tag will use srcset)
 * @param mixed $attr Optional. Additional attributes to pass into wp_get_attachment_image().
 *
 * @return WP_Post[]
 */
function busch_get_gallery_images( array $attachment_ids, string $size = '2048x2048', $attr = '' ) {

	$images = get_posts( [
		'post__in'    => $attachment_ids,
		'post_type'   => 'attachment',
		'numberposts' => - 1,
		'orderby'     => 'post__in',
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
		$images[ $key ]->_lightbox         = wp_get_attachment_image_url( $image->ID, '2048x2048' );
		$images[ $key ]->_prev_orientation = null;
		$images[ $key ]->_next_orientation = null;
	}

	// Set previous and next image orientation.
	foreach ( $images as $key => $image ) {
		$next_key = $key < ( $total_images - 1 ) ? $key + 1 : 0;
		$prev_key = $key === 0 ? $total_images - 1 : $key - 1;

		$images[ $key ]->_prev_orientation = $images[ $prev_key ]->_orientation;
		$images[ $key ]->_next_orientation = $images[ $next_key ]->_orientation;
	}

	return $images;
}

/**
 * Returns an array of images (as WP_Post objects) but with some additional properties
 * available to make formatting in template easier.
 *
 * @param int $post_id Collection ID
 * @param string $size Optional. Size to display (though <img> tag will use srcset)
 * @param mixed $attr Optional. Additional attributes to pass into wp_get_attachment_image().
 *
 * @return WP_Post[]
 */
function busch_get_formatted_images_for_collection( int $post_id, string $size = '2048x2048', $attr = '' ): array {

	$images = busch_get_collection_images( $post_id );

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
		$images[ $key ]->_lightbox         = wp_get_attachment_image_url( $image->ID, '2048x2048' );
		$images[ $key ]->_prev_orientation = null;
		$images[ $key ]->_next_orientation = null;
	}

	// Set previous and next image orientation.
	foreach ( $images as $key => $image ) {
		$next_key = $key < ( $total_images - 1 ) ? $key + 1 : 0;
		$prev_key = $key === 0 ? $total_images - 1 : $key - 1;

		$images[ $key ]->_prev_orientation = $images[ $prev_key ]->_orientation;
		$images[ $key ]->_next_orientation = $images[ $next_key ]->_orientation;
	}

	return $images;
}

/**
 * This changes the relationship field for Collections.
 *
 * It changes the image from "thumbnail" to "medium".
 * It also wraps the image title in a <span> so that it can be styled via flex.
 */
add_filter( 'acf/fields/relationship/result', function ( string $title, WP_Post $post, array $field, int $post_id ) {

	$post_type = get_post_type( $post_id );

	if ( $post_type !== 'collection' ) {
		return $title;
	}

	$med_img_src = wp_get_attachment_image_url( $post->ID, 'medium' );
	$thm_img_src = wp_get_attachment_image_url( $post->ID );

	return str_replace(
		[
			$thm_img_src,
			'</div>' . $post->post_title,
		],
		[
			$med_img_src,
			'</div><span>' . $post->post_title . '</span>',
		],
		$title
	);

}, 10, 4 );


/**
 * Get the next object in a set of objects.
 *
 * @param WP_Post[] $objects An array of WP_Post objects.
 * @param int $current_object_id The ID of the current object that we will base the "next" object off of.
 *
 * @return WP_Post|null The next WP_Post or null if none found.
 */
function busch_get_next_object( array $objects, int $current_object_id ): ?WP_Post {

	if ( empty( $objects ) ) {
		return null;
	}

	$count = count( $objects );

	foreach ( $objects as $key => $object ) {
		if ( $object->ID === $current_object_id ) {
			return $objects[ $key + 1 ] ?? $objects[0];
		}
	}

	return $objects[ $count - 1 ];
}

/**
 * Get the previous object in a set of objects.
 *
 * @param array $objects
 * @param int $current_object_id
 *
 * @return WP_Post|null
 */
function busch_get_prev_object( array $objects, int $current_object_id ): ?WP_Post {

	if ( empty( $objects ) ) {
		return null;
	}

	$count = count( $objects );

	foreach ( $objects as $key => $object ) {
		if ( $object->ID === $current_object_id ) {
			$index = $key - 1;
			$index = $index < 0 ? $count - 1 : $index;

			return $objects[ $index ];
		}
	}

	return $objects[0];
}

/**
 * Get previous collection.
 *
 * @param int $current_collection_id Current Collection ID.
 *
 * @return WP_Post|null
 */
function busch_get_prev_collection( int $current_collection_id ): ?WP_Post {
	$treatments = busch_get_collections();

	return busch_get_prev_object( $treatments, $current_collection_id );
}

/**
 * Get next collection.
 *
 * @param int $current_collection_id Current Collection ID.
 *
 * @return WP_Post|null
 */
function busch_get_next_collection( int $current_collection_id ): ?WP_Post {
	$treatments = busch_get_collections();

	return busch_get_next_object( $treatments, $current_collection_id );
}
