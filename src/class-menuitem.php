<?php
/**
 * Contains the menu item class.
 *
 * @package wrd\wp-navigate
 */

namespace wrd\wp_navigate;

use JsonSerializable;
use WP_Post;
use WP_Term;

/**
 * Stores information & helpers for a menu item.
 */
class MenuItem implements JsonSerializable {
	/**
	 * The WordPress post for this menu item.
	 *
	 * @var WP_Post $post The post related to this menu item.
	 */
	public WP_Post $post;

	/**
	 * Creates a MenuItem object.
	 *
	 * @param WP_Post|int|null $post The post. Defaults to global post.
	 */
	public function __construct( $post = null ) {
		$this->post = get_post( $post );
	}

	/**
	 * Get the label of the item.
	 *
	 * @return string
	 */
	public function get_the_title(): string {
		$title = apply_filters( 'the_title', $this->post->title, $this->post->ID );
		return $title;
	}

	/**
	 * Display the label.
	 *
	 * @return void
	 */
	public function the_title(): void {
		echo esc_html( $this->get_the_title() );
	}

	/**
	 * Get all of the attributes of the link.
	 *
	 * @param bool $flatten Whether to flatten the attributes into a string. Defaults to true.
	 *
	 * @return string[]|string
	 */
	public function get_the_atts( $flatten = true ): string|array {
		$atts           = array();
		$atts['title']  = ! empty( $this->post->attr_title ) ? $this->post->attr_title : '';
		$atts['target'] = ! empty( $this->post->target ) ? $this->post->target : '';
		$atts['href']   = $this->post->url;

		if ( '_blank' === $this->post->target && empty( $this->post->xfn ) ) {
			$atts['rel'] = 'noopener';
		} else {
			$atts['rel'] = $this->post->xfn;
		}

		if ( $this->is_current() ) {
			$atts['aria-current'] = 'page';
		}

		if ( ! $flatten ) {
			return $atts;
		}

		$attributes = '';

		foreach ( $atts as $attr => $value ) {
			if ( is_scalar( $value ) && '' !== $value && false !== $value ) {
				$attr        = esc_html( $attr );
				$value       = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}

		return $attributes;
	}

	/**
	 * Display the link's attributes.
	 *
	 * @return void
	 */
	public function the_atts(): void {
		echo $this->get_the_atts( true ); // phpcs:ignore -- $value is escaped. $attr is string literal.
	}

	/**
	 * Display a menu item, with a `li` and `a` tags.
	 *
	 * @param string $link_class Optional class to add to the `a` tag.
	 *
	 * @param string $list_item_class Optional class to add to the `li` tag.
	 *
	 * @return void
	 */
	public function render( $link_class = '', $list_item_class = '' ): void {
		echo '<li class="' . esc_attr( $list_item_class ) . '">';
		echo '<a ' . $this->get_the_atts() . ' class="' . esc_attr( $link_class ) . '">' . esc_html( $this->get_the_title() ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped - Trusted values
		echo '</li>';
	}

	/**
	 * Get the ID of the item this link is targeting.
	 *
	 * @return int
	 */
	public function get_target_id(): int {
		return intval( get_post_meta( $this->post->ID, '_menu_item_object_id', true ) );
	}

	/**
	 * Checks if the link is for the currently requested post.
	 *
	 * @return bool
	 */
	public function is_current() {
		$is_current = get_the_ID() === $this->get_target_id();

		if ( is_search() ) {
			$is_current = false;
		}

		return apply_filters( 'wrd\wp_navigate\menuitem\is_current', $is_current, $this );
	}

	/**
	 * Get the menu this item is from.
	 *
	 * @return WP_Term|null
	 */
	public function get_menu(): WP_Term|null {
		$menus = get_the_terms( $this->post, 'nav_menu' );

		if ( count( $menus ) > 0 ) {
			return $menus[0];
		}

		return null;
	}

	/**
	 * Get all this items children.
	 *
	 * @return MenuItem[]
	 */
	public function get_children(): array {
		return static::get_menu_items( $this->get_menu(), $this->post->ID );
	}

	/**
	 * Check if this item has children.
	 *
	 * @return bool
	 */
	public function has_children(): bool {
		return count( $this->get_children() ) > 0;
	}

	/**
	 * Convert to a JSON compatible object.
	 *
	 * @return array
	 */
	public function jsonSerialize(): array {
		$children = $this->has_children() ? $this->get_children() : array();

		if ( function_exists( 'get_fields' ) ) {
			$fields = get_fields( $this->post->ID );
		}

		return array(
			'id'       => $this->post->ID,
			'title'    => $this->get_the_title(),
			'atts'     => $this->get_the_atts( false ),
			'children' => self::serialize_items( $children ),
			'meta'     => $fields ? $fields : array(),
		);
	}

	/**
	 * Display a list of menu items.
	 *
	 * This function does not recurse to display the item's children.
	 *
	 * @param MenuItem[] $menu_items The menu items to render.
	 *
	 * @param string     $link_class Optional classes to add to the `a` elements.
	 *
	 * @param string     $list_item_class Optional classes to add to the `li` elements.
	 *
	 * @param string     $list_class Optional classes to add to the root `ul` element.
	 *
	 * @return void
	 */
	public static function render_list( $menu_items, $link_class = '', $list_item_class = '', $list_class = '' ): void {
		echo '<ul class="' . esc_attr( $list_class ) . '">';
		foreach ( $menu_items as $item ) {
			$item->render( $link_class, $list_item_class );
		}
		echo '</ul>';
	}

	/**
	 * Gets an array of menu items for a menu.
	 *
	 * @param int|string|WP_Term $menu — Menu ID, slug, name, or object.
	 *
	 * @param int                $parent_id The ID of the parent menu item to get.
	 *
	 * @return MenuItem[] Array of menu items for this location.
	 */
	public static function get_menu_items( $menu, $parent_id = 0 ) {
		$items      = wp_get_nav_menu_items( $menu );
		$menu_items = array();

		foreach ( $items as $item ) {
			if ( $parent_id === (int) $item->menu_item_parent ) {
				$menu_items[] = new self( $item );
			}
		}

		return $menu_items;
	}

	/**
	 * Gets an array of menu items for a menu from it's location.
	 *
	 * @param string $theme_location The theme location to get the menu for.
	 *
	 * @param int    $parent_id The ID of the parent menu item to get.
	 *
	 * @return MenuItem[] Array of menu items for this location.
	 */
	public static function get_location_items( $theme_location, $parent_id = 0 ) {
		$locations = get_nav_menu_locations();

		if ( ! array_key_exists( $theme_location, $locations ) ) {
			return array();
		}

		return static::get_menu_items( $locations[ $theme_location ], $parent_id );
	}

	/**
	 * Serializes an array of menu items to a JSON compatible object.
	 *
	 * @param MenuItem[] $items Array of menu items to convert.
	 *
	 * @return array[]
	 */
	public static function serialize_items( $items ): array {
		$obj = array();

		foreach ( $items as $item ) {
			$obj[] = $item->jsonSerialize();
		}

		return $obj;
	}
}
