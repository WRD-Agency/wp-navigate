<?php
/**
 * Breadcrumbs functions.
 *
 * @package wrd\wp-navigate
 */

/**
 * Gets the post breadcrumbs.
 *
 * @return array[]
 */
function get_the_breadcrumbs(): array {
	$crumbs = array();

	if ( is_singular() ) {
		if ( ! is_front_page() ) {
			$crumbs[] = array(
				'type'  => 'singular',
				'url'   => get_the_permalink(),
				'label' => get_the_title(),
			);
		}

		if ( is_post_type_hierarchical( get_post_type() ) ) {
			$ancestor_ids = get_post_ancestors( get_post() );

			foreach ( $ancestor_ids as $id ) {
				$crumbs[] = array(
					'type'  => 'ancestor',
					'url'   => get_the_permalink( $id ),
					'label' => get_the_title( $id ),
				);
			}
		}
	} else {
		if ( is_tax() || is_category() || is_tag() ) {
			$queried = get_queried_object();

			$crumbs[] = array(
				'type'  => 'term',
				'url'   => get_term_link( $queried ),
				'label' => $queried->name,
			);
		}
	}

	if ( get_post_type() === 'post' ) {
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$posts_page_id = get_option( 'page_for_posts' );

			if ( get_post( $posts_page_id ) ) {
				$crumbs[] = array(
					'type'  => 'blog',
					'url'   => get_permalink( $posts_page_id ),
					'label' => get_the_title( $posts_page_id ),
				);
			}
		} else {
			$blog_label = apply_filters( 'wrd\wp_navigate\get_the_breadcrumbs\blog_home_label', __( 'Blog', 'wrd' ) );

			$crumbs[] = array(
				'type'  => 'blog',
				'url'   => get_home_url(),
				'label' => $blog_label,
			);
		}
	} elseif ( get_post_type() !== 'page' ) {
		$post_type_object = get_post_type_object( get_post_type() );

		if ( $post_type_object && $post_type_object->has_archive ) {
			$crumbs[] = array(
				'url'   => get_post_type_archive_link( get_post_type() ),
				'label' => $post_type_object->labels->archives,
			);
		}
	}

	if ( is_search() ) {
		$search_label = apply_filters( 'wrd\wp_navigate\get_the_breadcrumbs\search_label', __( 'Search', 'wrd' ) );

		$crumbs[] = array(
			'type'  => 'search',
			'url'   => get_search_link(),
			'label' => $search_label,
		);
	}

	$home_label = apply_filters( 'wrd\wp_navigate\get_the_breadcrumbs\home_label', __( 'Home', 'wrd' ) );

	$crumbs[] = array(
		'type'  => 'home',
		'url'   => get_home_url(),
		'label' => $home_label,
	);

	$crumbs = array_reverse( $crumbs );
	$crumbs = apply_filters( 'wrd\wp_navigate\get_the_breadcrumbs', $crumbs );

	return $crumbs;
}

/**
 * Displays the breadcrumb items.
 *
 * @param string $class Classes to add.
 */
function the_breadcrumbs( $class = '' ): void {
	$crumbs = get_the_breadcrumbs();

	echo "<ol class='breadcrumbs " . esc_attr( $class ) . "'>";

	foreach ( $crumbs as $i => $crumb ) {
		the_breadcrumb_item( $crumb, $i > 0 );
	}

	echo '</ol>';
}

/**
 * Trims the breadcrumb label.
 *
 * @param string $label The label to trim.
 *
 * @return string
 */
function trim_breadcrumb( $label ): string {
	$max_length = apply_filters( 'wrd\wp_navigate\trim_breadcrumbs\max_length', 40, $label );
	$append     = apply_filters( 'wrd\wp_navigate\trim_breadcrumbs\max_length', '&hellip;', $label );

	$label = trim( $label );

	if ( strlen( $label ) > $max_length ) {
		$label = wordwrap( $label, $max_length );
		$label = explode( "\n", $label, 2 );
		$label = $label[0] . $append;
	}

	return $label;
}

/**
 * Displays a breadcrumb item.
 *
 * @param array $crumb The crumb. Array containing 'url' and 'label' items.
 *
 * @param bool  $with_separator True to show separator, false to not.
 */
function the_breadcrumb_item( $crumb, $with_separator ): void {
	$label = trim_breadcrumb( $crumb['label'] );

	$default_separator_icon = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="m517.85-480-184-184L376-706.15 602.15-480 376-253.85 333.85-296l184-184Z"/></svg>';
	$separator_icon         = apply_filters( 'wrd\wp_navigate\the_breadcrumb_item\separator_icon', $default_separator_icon, $crumb );

	?>

	<li class="breadcrumbs__item">
		<?php if ( $with_separator ) : ?>
			<div class="breadcrumbs__separator" aria-hidden="false">
				<?php echo $separator_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted ?>
			</div>
		<?php endif; ?>

		<a class="breadcrumbs__link" href="<?php echo esc_url( $crumb['url'] ); ?>">
			<?php echo esc_html( $label ); ?>
		</a>
	</li>

	<?php
}
