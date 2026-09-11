<?php
/**
 * Functionality for table of contents.
 *
 * @package wrd\wp-navigate
 */

namespace wrd\wp_navigate;

use WP_HTML_Tag_Processor;
use WP_Post;

/**
 * Enable functionality to add IDs to all core heading blocks.
 *
 * @return void
 */
function add_ids_to_headings_in_content() {
	add_filter( 'render_block', __NAMESPACE__ . '\\_add_id_to_core_heading', 10, 2 );
}

/**
 * Add ID to all core heading blocks.
 *
 * @param string $block_content The block content.
 *
 * @param array  $block The full block, including name and attributes.
 *
 * @return string
 */
function _add_id_to_core_heading( $block_content, $block ) {
	if ( 'core/heading' !== $block['blockName'] ) {
		return $block_content;
	}

	$tags = new WP_HTML_Tag_Processor( $block_content );
	$tags->next_tag();

	if ( $tags->get_attribute( 'id' ) ) {
		return $block_content;
	}

	$tags->set_bookmark( 'heading' );
	$tags->next_token();

	$text = $tags->get_modifiable_text();
	$id   = apply_filters( 'wrd\wp_navigate\_add_id_to_core_heading\id', sanitize_title( $text ) );

	$tags->seek( 'heading' );

	$tags->set_attribute( 'id', $id );

	$block_content = $tags->get_updated_html();

	return $block_content;
}

/**
 * Get all headings from a post's content.
 *
 * @param WP_Post|int|null $post The post. Defaults to global post.
 *
 * @return array
 */
function get_post_headings( WP_Post|int|null $post = null ): array {
	static $is_parsing;

	if ( $is_parsing ) {
		return array();
	}

	$is_parsing = true;

	$post     = get_post( $post );
	$content  = apply_filters( 'the_content', get_the_content( null, null, $post ) );
	$tags     = new WP_HTML_Tag_Processor( $content );
	$headings = array();

	while ( $tags->next_tag( array( 'H2', 'H3', 'H4', 'H5', 'H6' ) ) ) {
		$id = $tags->get_attribute( 'id' );

		if ( ! $id ) {
			continue;
		}

		$tag   = $tags->get_tag();
		$level = intval( substr( $tag, 1, 1 ) );

		$tags->next_token();
		$text = trim( $tags->get_modifiable_text() );

		if ( ! $text ) {
			continue;
		}

		$headings[] = array(
			'id'    => $id,
			'tag'   => $tag,
			'level' => $level,
			'text'  => $text,
		);
	}

	$headings = apply_filters( 'wrd\wp_navigate\get_post_headings', $headings );

	return $headings;
}

/**
 * Check if the post's content has any headings.
 *
 * @param WP_Post|int|null $post The post. Defaults to global post.
 *
 * @return bool
 */
function has_post_headings( WP_Post|int|null $post = null ): bool {
	$post    = get_post( $post );
	$content = apply_filters( 'the_content', get_the_content( null, null, $post ) );
	$tags    = new WP_HTML_Tag_Processor( $content );

	return $tags->next_tag( array( 'H2', 'H3', 'H4', 'H5', 'H6' ) );
}

/**
 * Retrieve the table of contents.
 *
 * @param WP_Post|int|null $post The post to get contents for.
 *
 * @param array            $opts Options for the table.
 *
 * @return string|false The table of contents markup or false if no headers are found.
 */
function get_the_table_of_contents( WP_Post|int|null $post = null, array $opts = array() ): string|false {
	$opts = wp_parse_args(
		$opts,
		array(
			'title'       => __( 'On this Page', 'ecs' ),
			'wrap_class'  => 'table-of-contents',
			'title_class' => 'table-of-contents__title',
			'list_class'  => 'table-of-contents__list',
			'item_class'  => 'table-of-contents__item',
			'link_class'  => 'table-of-contents__link',
			'max_depth'   => 3,
		)
	);

	$post     = get_post( $post );
	$headings = get_post_headings( $post );
	$output   = '';

	if ( ! $headings ) {
		return false;
	}

	foreach ( $headings as $heading ) {
		if ( $heading['level'] > $opts['max_depth'] ) {
			continue;
		}

		$output .= sprintf( '<li class="%s" data-level="%s"><a class="%s" href="#%s">%s</a></li>', esc_attr( $opts['item_class'] ), esc_attr( $heading['level'] ), esc_attr( $opts['link_class'] ), esc_html( $heading['id'] ), esc_html( $heading['text'] ) );
	}

	$title  = $opts['title'] ? sprintf( '<summary class="%s">%s</summary>', esc_attr( $opts['title_class'] ), esc_html( $opts['title'] ) ) : '';
	$output = sprintf( '<details class="%s" open>%s<ol class="%s">', esc_attr( $opts['wrap_class'] ), $title, esc_attr( $opts['list_class'] ) ) . $output . '</ol></details>';

	return $output;
}

/**
 * Displays the table of contents.
 *
 * @param WP_Post|int|null $post The post to get contents for.
 *
 * @param array            $opts Options for the table.
 *
 * @return void
 */
function the_table_of_contents( WP_Post|int|null $post = null, array $opts = array() ): void {
	echo wp_kses_post( get_the_table_of_contents( $post, $opts ) );
}
