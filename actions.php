<?php

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue JavaScript files for FSLightbox functionality.
 *
 * This function loads the FSLightbox library and custom JavaScript files
 * required for lightbox functionality on the frontend.
 *
 * @return void
 */
function ebca_load_scripts(): void {
	wp_enqueue_script( 'fslb-js', EBCA_URL . 'js/fslightbox/fslightbox.js', [], EBCA_VERSION, true );
	wp_enqueue_script( 'busch-fslb-js', EBCA_URL . 'js/fslightbox/custom.js', [ 'fslb-js' ], EBCA_VERSION, true );
	wp_enqueue_script( 'ebca-email-obfuscation', EBCA_URL . 'js/ebca-email-obfuscation.js', [], EBCA_VERSION, true );
	wp_enqueue_script( 'ebca-lazy', EBCA_URL . 'js/lazy.js', [], EBCA_VERSION, true );
}

add_action( 'wp_enqueue_scripts', 'ebca_load_scripts' );


/**
 * Output custom CSS styles for ACF relationship fields in the admin.
 *
 * This function adds custom styling to improve the appearance and usability
 * of ACF relationship fields, specifically for collection images. It increases
 * the list height and adds styling for thumbnails and layout.
 *
 * @return void
 */
function ebca_load_custom_admin_css(): void {
	echo '<style>
	#busch-acf-collection-images .acf-relationship .list {height:850px;}
	#busch-acf-collection-images .acf-relationship .list .acf-rel-item {display:flex;}	
	#busch-acf-collection-images .acf-relationship .list .acf-rel-item .thumbnail {width:75px;height:75px;background:white;}	
	#busch-acf-collection-images .acf-relationship .list .acf-rel-item .thumbnail img {max-width:75px;max-height:75px;}
</style>';
}

add_action( 'admin_head', 'ebca_load_custom_admin_css' );

