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
if( function_exists('acf_add_options_page') ) {
	acf_add_options_page();
}

