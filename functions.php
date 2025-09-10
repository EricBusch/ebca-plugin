<?php

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get a background image for homepage.
 *
 * @param string $size
 *
 * @return string|null
 */
function ebca_get_homepage_bg_image( string $size = 'full' ): ?string {

	$ids = ebca_get_homepage_background_image_ids();

	if ( ! $ids ) {
		return null;
	}

	shuffle( $ids );

	return wp_get_attachment_image_src( $ids[0], $size )[0] ?? null;
}

/**
 * Get an array of background image IDs for homepage.
 *
 * @return array
 */
function ebca_get_homepage_background_image_ids(): array {

	$rows = get_field( 'background_images', absint( get_option( 'page_on_front' ) ) );

	$ids = [];

	foreach ( $rows as $key => $value ) {
		if ( isset( $value['image']['ID'] ) ) {
			$ids[] = absint( $value['image']['ID'] );
		}
	}

	return array_unique( array_filter( $ids ) );
}

/**
 * Return an array of WP_Post objects for post_type 'collection'.
 *
 * @param array $overrides Optional. Array of arguments to override the default query parameters.
 *                         Accepts 'orderby', 'order', 'numberposts', and 'post_status'. Default empty array.
 *
 * @return WP_Post[] Array of collection posts.
 */
function ebca_get_collections( array $overrides = [] ): array {
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
function ebca_get_all_attachment_ids_for_collection( int $post_id ): array {

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
 * Returns an array of Attachment IDs associated with a Collection that were added within a specified number of days.
 *
 * @param int $post_id Collection ID.
 * @param int $days Optional. Number of days to look back for new attachments. Default 30.
 *
 * @return int[] Array of attachment IDs.
 */
function ebca_get_newest_attachment_ids_for_collection( int $post_id, int $days = 30 ): array {

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
 * Return the number of images the Collection contains.
 *
 * @param int $post_id Collection ID.
 *
 * @return int Number of images in the collection.
 */
function ebca_get_image_count_for_collection( int $post_id ): int {
	return count( ebca_get_all_attachment_ids_for_collection( $post_id ) );
}

/**
 * Return an array of images for a given Collection.
 *
 * @param int $post_id Collection ID.
 *
 * @return WP_Post[]
 */
function ebca_get_collection_images( int $post_id ): array {

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
 * @param array $attachment_ids Array of Attachment IDs.
 * @param string $size Optional. Size to display (though <img> tag will use srcset). Default '2048x2048'.
 * @param string|array $attr Optional. Additional attributes to pass into wp_get_attachment_image(). Default empty string.
 *
 * @return WP_Post[] Array of WP_Post objects with additional properties for template formatting.
 */
function ebca_get_gallery_images( array $attachment_ids, string $size = '2048x2048', string|array $attr = '' ) {

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
 * @param int $post_id Collection ID.
 * @param string $size Optional. Size to display (though <img> tag will use srcset). Default '2048x2048'.
 * @param string|array $attr Optional. Additional attributes to pass into wp_get_attachment_image(). Default empty string.
 *
 * @return WP_Post[] Array of WP_Post objects with additional properties for template formatting.
 */
function ebca_get_formatted_images_for_collection( int $post_id, string $size = '2048x2048', string|array $attr = '' ): array {

	$images = ebca_get_collection_images( $post_id );

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
 * Get the next object in a set of objects.
 *
 * @param WP_Post[] $objects Array of WP_Post objects.
 * @param int $current_object_id The ID of the current object that we will base the "next" object off of.
 *
 * @return WP_Post|null The next WP_Post or null if none found.
 */
function ebca_get_next_object( array $objects, int $current_object_id ): ?WP_Post {

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
 * @param WP_Post[] $objects Array of WP_Post objects.
 * @param int $current_object_id The ID of the current object that we will base the "previous" object off of.
 *
 * @return WP_Post|null The previous WP_Post or null if none found.
 */
function ebca_get_prev_object( array $objects, int $current_object_id ): ?WP_Post {

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
 * @return WP_Post|null The previous collection post or null if none found.
 */
function ebca_get_prev_collection( int $current_collection_id ): ?WP_Post {
	$treatments = ebca_get_collections();

	return ebca_get_prev_object( $treatments, $current_collection_id );
}

/**
 * Get next collection.
 *
 * @param int $current_collection_id Current Collection ID.
 *
 * @return WP_Post|null The next collection post or null if none found.
 */
function ebca_get_next_collection( int $current_collection_id ): ?WP_Post {
	$treatments = ebca_get_collections();

	return ebca_get_next_object( $treatments, $current_collection_id );
}

/**
 * Convert email addresses to anti-spam format using HTML entity encoding.
 *
 * This function randomly converts each character in an email address to either
 * its HTML entity equivalent (&#xxx;) or leaves it as-is. The @ symbol is always
 * converted to &#64; for additional protection. This obfuscation technique helps
 * prevent email harvesting by automated spambots while keeping the email readable
 * in browsers.
 *
 * The function is used by the email shortcode to create obfuscated email addresses
 * that are then concatenated with JavaScript to create functional mailto links or
 * display elements.
 *
 * @param string $email_address The email address to obfuscate.
 *
 * @return array An array of characters and HTML entities representing the obfuscated email.
 *               Each element is either the original character or its HTML entity equivalent.
 *
 * @since 1.0.8
 *
 * @example
 * $obfuscated = ebca_antispambot('test@example.com');
 * // Returns something like: ['t', '&#101;', 's', '&#116;', '&#64;', 'e', '&#120;', ...]
 */
function ebca_antispambot( string $email_address ): array {

	$email_no_spam_address = [];

	for ( $i = 0, $len = strlen( $email_address ); $i < $len; $i ++ ) {

		$j = rand( 0, 1 );

		if ( 0 === $j ) {
			$email_no_spam_address[] = '&#' . ord( $email_address[ $i ] ) . ';';
		} elseif ( 1 === $j ) {
			$email_no_spam_address[] = $email_address[ $i ];
		}
	}

	return str_replace( '@', '&#64;', $email_no_spam_address );
}


/**
 * Convert an email address to a URL format.
 *
 * Takes an email address and converts it to a URL by extracting the username
 * and domain parts, then formatting them as https://{domain}/{username}.
 *
 * @param string $email The email address to convert to URL format.
 *
 * @return string The formatted URL string in the format https://{domain}/{username}.
 *
 * @since 1.0.11
 *
 * @example
 * $url = ebca_convert_email_to_url('john@example.com');
 * // Returns: 'https://example.com/john'
 */
function ebca_convert_email_to_url( string $email ): string {
	list( $username, $domain ) = explode( '@', $email, 2 );

	return sprintf( 'https://%s/%s', $domain, $username );
}
