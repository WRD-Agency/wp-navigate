<?php
/**
 * Template tag menu functions.
 *
 * @package wrd\wp-navigate
 */

namespace wrd\wp_navigate;

use WP_Term;

/**
 * Parse HTML element attributes from an array to a string.
 *
 * @param array $attrs The attributes array to convert.
 *
 * @return string
 *
 * @internal
 */
function _get_the_attrs( array $attrs ): string {
	if ( is_string( $attrs ) ) {
		return $attrs;
	}

	$output = array();

	foreach ( $attrs as $attr => $value ) {
		if ( null === $value ) {
			continue;
		}

		if ( is_bool( $value ) ) {
			$value = $value ? 'true' : 'false';
		}

		$output[] = esc_attr( $attr ) . '="' . esc_attr( $value ) . '"';
	}

	return implode( ' ', $output );
}

/**
 * Display a menu as a simple navigation list.
 *
 * @param int|string|WP_Term $menu $menu Menu ID, slug, name, or object.
 *
 * @param string             $title The menu title to display.
 *
 * @param string             $classes Classes to add to the nav wrapper.
 *
 * @return void
 */
function the_menu( int|string|WP_Term $menu, string $title, string $classes = '' ): void {
	$items = wp_get_nav_menu_items( $menu );

	if ( ! $items || is_wp_error( $items ) || empty( $items ) ) {
		return;
	}

	_wp_menu_item_classes_by_context( $items );

	printf( '<details class="menu %s" open>', esc_attr( $classes ) );

	if ( $title ) {
		printf( '<summary class="menu__title">%s</summary>', esc_html( $title ) );
	}

	echo '<ul class="menu__items">';

	foreach ( $items as $item ) {
		$label = apply_filters( 'the_title', $item->title, $item->ID );

		$attrs = array(
			'class' => 'menu__link',
			'href'  => $item->url,
		);

		if ( ! empty( $item->attr_title ) ) {
			$attrs['title'] = $item->attr_title;
		}

		if ( ! empty( $item->target ) ) {
			$attrs['target'] = $item->target;
		}

		if ( ! empty( $item->xfn ) ) {
			$attrs['rel'] = $item->xfn;
		}

		if ( ! empty( $item->current ) && $item->current ) {
			$attrs['aria-current'] = 'page';
		}

		if ( ! $label ) {
			continue;
		}

		echo '<li class="menu__item">';
		echo '<a ' . _get_the_attrs( $attrs ) . '>' . esc_html( $label ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</li>';
	}

	echo '</ul>';
	echo '</details>';
}
