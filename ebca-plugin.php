<?php
/**
 * Plugin Name: EBCA Plugin
 * Description: My custom plugin
 * Version: 1.0.10
 * Author: eb
 * Text Domain: ebca
 */

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define Version and Paths
 */
const EBCA_VERSION     = '1.0.10';
const EBCA_PLUGIN_FILE = __FILE__; // /absolute/path/to/wp-content/plugins/ebca-plugin/ebca-plugin.php
define( 'EBCA_URL', plugin_dir_url( __FILE__ ) ); // https://example.com/wp-content/plugins/ebca-plugin/
define( 'EBCA_PATH', plugin_dir_path( __FILE__ ) ); // /absolute/path/to/wp-content/plugins/ebca-plugin/
define( 'EBCA_BASENAME', plugin_basename( __FILE__ ) ); // ebca-plugin/ebca-plugin.php

/**
 * Require Files
 */
require_once dirname( EBCA_PLUGIN_FILE ) . '/functions.php';
require_once dirname( EBCA_PLUGIN_FILE ) . '/shortcodes.php';
require_once dirname( EBCA_PLUGIN_FILE ) . '/filters.php';
require_once dirname( EBCA_PLUGIN_FILE ) . '/actions.php';

/**
 * Enable ACF Options page.
 */
if ( function_exists( 'acf_add_options_page' ) ) {
	acf_add_options_page();
}
