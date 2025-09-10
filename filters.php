<?php

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Force ACF to load a Default Value for the "Added at" field for Images in a
 * Gallery inside the Flexible Content.
 *
 * This function sets the default value for ACF fields named "added_at" to the
 * current date in 'Ymd' format (e.g., 20250910). It is used as a filter callback
 * to automatically populate the field with today's date when the field is loaded.
 *
 * @param array $field The ACF field array containing field configuration.
 *
 * @return array The modified field array with the default_value set to current date.
 * @since 1.0.0
 */
function ebca_set_default_value( array $field ): array {
	$field['default_value'] = date_i18n( 'Ymd' );

	return $field;
}

add_filter( 'acf/load_field/name=added_at', 'ebca_set_default_value' );

/**
 * Modify the relationship field display for Collections.
 *
 * This function customizes the appearance of relationship fields specifically for
 * Collection post types by changing the image size from thumbnail to medium and
 * wrapping the image title in a span element for better CSS styling flexibility.
 *
 * @param string $title The original title/content for the relationship field.
 * @param WP_Post $post The post object being displayed in the relationship field.
 * @param array $field The ACF field configuration array.
 * @param int $post_id The ID of the post being edited.
 *
 * @return string The modified title/content for the relationship field.
 * @since 1.0.0
 */
function ebca_modify_relationship_field( string $title, WP_Post $post, array $field, int $post_id ): string {

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
}

add_filter( 'acf/fields/relationship/result', 'ebca_modify_relationship_field', 10, 4 );

/**
 * Disable the admin bar on the homepage for logged-in users.
 *
 * This function modifies the display of the WordPress admin bar by hiding it
 * specifically on the front page/homepage when a user is logged in, while
 * preserving the default behavior for all other pages.
 *
 * @param bool $show_admin_bar Whether to show the admin bar.
 *
 * @return bool True to show the admin bar, false to hide it.
 * @since 1.0.0
 */
function ebca_disable_admin_bar_on_homepage( bool $show_admin_bar ): bool {
	if ( ! is_user_logged_in() ) {
		return $show_admin_bar;
	}

	return ! is_front_page();
}

add_filter( 'show_admin_bar', 'ebca_disable_admin_bar_on_homepage' );
