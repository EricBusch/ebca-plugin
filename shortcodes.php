<?php

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
function ebca_email_shortcode( $atts, $content = null ): string {

	$defaults = [
		'address' => get_field( 'contact_email', 'option' ),
		'link'    => 1,
		'text'    => '',
		'class'   => '',
		'title'   => 'Send me an email',
		'target'  => '',
		'rel'     => 'nofollow noindex',
	];

	$attributes = shortcode_atts( $defaults, $atts );

	$is_link = boolval( $attributes['link'] );
	$email   = trim( $attributes['address'] );
	$text    = trim( $attributes['text'] );
	$class   = trim( $attributes['class'] );
	$title   = trim( $attributes['title'] );
	$target  = trim( $attributes['target'] );
	$rel     = trim( $attributes['rel'] );

	if ( $is_link ) {
		$tag = 'a';
		if ( ! empty( $content ) ) {
			$anchor = $content; // UNESCAPED DATA!
		} elseif ( ! empty( $text ) ) {
			$anchor = esc_html( $text );
		} else {
			$anchor = esc_html( $email );
		}
	} else {
		$tag    = 'span';
		$anchor = esc_html( $email );
	}

	$options   = [];
	$options[] = ! empty( $title ) ? 'title="' . esc_attr( $title ) . '"' : '';
	$options[] = ! empty( $class ) ? 'class="ebca-eml ' . esc_attr( $class ) . '"' : 'class="ebca-eml"';
	$options[] = ! empty( $target ) ? 'target="' . esc_attr( $target ) . '"' : '';
	$options[] = ! empty( $rel ) ? 'rel="' . esc_attr( $rel ) . '"' : '';
	$options[] = 'data-eml="' . esc_attr( ebca_convert_email_to_url( $email ) ) . '"';

	$format = '<%1$s %2$s>%3$s</%1$s>';

	return sprintf(
		$format,
		$tag,
		trim( implode( ' ', array_filter( $options ) ) ),
		$anchor
	);
}

add_shortcode( 'email', 'ebca_email_shortcode' );
