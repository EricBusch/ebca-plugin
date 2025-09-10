<?php
/**
 * Plugin Name: EBCA Plugin
 * Description: My custom plugin
 * Version: 1.0.9
 * Author: eb
 * Text Domain: busch
 */

defined( 'ABSPATH' ) || exit;

const BUSCH_VERSION = '1.0.9';

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

/**
 * Disable admin bar on homepage is user is logged in.
 *
 * @param bool $show_admin_bar
 *
 * @return bool
 */
add_filter( 'show_admin_bar', function ( bool $show_admin_bar ) {
	if ( ! is_user_logged_in() ) {
		return $show_admin_bar;
	}

	return ! is_front_page();
} );

/**
 * Email shortcode handler that creates an obfuscated email link or display element.
 *
 * Creates either a clickable mailto link (when link="1") or a plain text span element
 * (when link="0") with anti-spam protection using the custom busch_antispambot() function.
 * All email addresses and text content are obfuscated to help prevent email harvesting
 * by spambots. The output is wrapped in JavaScript to provide additional obfuscation.
 *
 * See:
 *
 * https://spencermortensen.com/articles/email-obfuscation/#link-concatenation
 * https://spencermortensen.com/articles/email-obfuscation/#text-concatenation
 *
 * @param array $atts {
 *     Shortcode attributes. Default values are provided for all attributes.
 *
 * @type string $address Email address to display/link to. Default is the 'contact_email'
 *                           option field value.
 * @type int $link Whether to create a clickable mailto link (1) or plain text (0).
 *                           Default 1.
 * @type string $text Custom display text. If empty, the email address will be shown.
 *                           Default empty string.
 * @type string $class CSS class(es) to apply to the element. Default empty string.
 * @type string $title Title attribute for the element (tooltip text).
 *                           Default 'Send me an email'.
 * @type string $target Target attribute for the link (only applies when link="1").
 *                           Default '_blank'.
 * @type string $rel Rel attribute for the link (only applies when link="1").
 *                           Default 'noopener'.
 * }
 *
 * @return string JavaScript-wrapped HTML output as either an <a> or <span> element.
 *
 * @since 1.0.7
 *
 * @example Basic usage with default options:
 * [email]
 *
 * @example Custom email address with link:
 * [email address="eric@affiliate.com" text="email me" link="1" class="uppercase" title="Drop me an email"]
 *
 * @example Display email as plain text (no link):
 * [email address="contact@example.com" link="0" class="email-display"]
 *
 * @example Custom display text with styling:
 * [email text="Contact Us" class="btn btn-primary" title="Send us a message"]
 *
 * @example With custom target and rel attributes:
 * [email address="contact@example.com" target="_self" rel="nofollow noopener"]
 */
add_shortcode( 'email', function ( $atts, $content = null ) {

	$defaults = [
		'address' => get_field( 'contact_email', 'option' ),
		'link'    => 1,
		'text'    => '',
		'class'   => '',
		'title'   => 'Send me an email',
		'target'  => '_blank',
		'rel'     => 'noopener',
	];

	$attributes = shortcode_atts( $defaults, $atts );

	$is_link = boolval( $attributes['link'] );
	$email   = trim( $attributes['address'] );
	$text    = trim( $attributes['text'] );
	$class   = trim( $attributes['class'] );
	$title   = trim( $attributes['title'] );
	$target  = trim( $attributes['target'] );
	$rel     = trim( $attributes['rel'] );
	$mailto  = 'mailto:' . $email;

	if ( $is_link ) {
		$tag = 'a';
		if ( ! empty( $content ) ) {
			$anchor = $content; // UNESCAPED DATA!
		} elseif ( ! empty( $text ) ) {
			$anchor = esc_html( $text );
		} else {
			$anchor = implode( "'+'", busch_antispambot( $email ) );
		}
		$href = 'href="' . implode( "'+'", busch_antispambot( $mailto ) ) . '"';
	} else {
		$tag    = 'span';
		$anchor = implode( "'+'", busch_antispambot( $email ) );
		$href   = '';
	}

	$options   = [];
	$options[] = $href;
	$options[] = ! empty( $title ) ? 'title="' . esc_attr( $title ) . '"' : '';
	$options[] = ! empty( $class ) ? 'class="' . esc_attr( $class ) . '"' : '';
	$options[] = ! empty( $target ) ? 'target="' . esc_attr( $target ) . '"' : '';
	$options[] = ! empty( $rel ) ? 'rel="' . esc_attr( $rel ) . '"' : '';

	$format = "<script>document.write('";
	$format .= '<%1$s %2$s>%3$s</%1$s>';
	$format .= "');</script>";

	return sprintf(
		$format,
		$tag,
		trim( implode( ' ', array_filter( $options ) ) ),
		$anchor
	);
} );

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
 * $obfuscated = busch_antispambot('test@example.com');
 * // Returns something like: ['t', '&#101;', 's', '&#116;', '&#64;', 'e', '&#120;', ...]
 */
function busch_antispambot( $email_address ): array {

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
