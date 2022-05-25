<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'fdmBlocks' ) ) {
/**
 * Class to create, edit and display blocks for the Gutenberg editor
 *
 * @since 0.0.1
 */
class fdmBlocks {

	/**
	 * Add hooks
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'register' ) );

		add_filter( 'block_categories_all', array( $this, 'add_block_category' ) );
	}

	/**
	 * Register blocks
	 */
	public function register() {
		global $fdm_controller;

		if ( !function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'food-and-drink-menu-blocks',
			FDM_PLUGIN_URL . '/assets/js/blocks.build.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' )
		);

		$selected_style = !empty( $fdm_controller->settings->get_setting('fdm-style') ) ? $fdm_controller->settings->get_setting('fdm-style') : 'base';
		$load_styles = array();
		foreach( $fdm_controller->styles as $style ) {
			if ( $style->id === $selected_style ) {
				foreach( $style->css as $css ) {
					$load_styles[] = 'food-and-drink-menu-' . $style->id;
					wp_register_style(
						'food-and-drink-menu-' . $style->id,
						$css,
						array()
					);
				}
			}
		}

		register_block_type( 'food-and-drink-menu/menu', array(
			'editor_script' => 'food-and-drink-menu-blocks',
			'editor_style' => $load_styles,
			'render_callback' => array( $this, 'render_menu' ),
			'attributes' => array(
				'id' => array(
					'type' => 'number',
					'minimum' => '0',
				),
			),
		) );

		register_block_type( 'food-and-drink-menu/menu-section', array(
			'editor_script' => 'food-and-drink-menu-blocks',
			'editor_style' => $load_styles,
			'render_callback' => array( $this, 'render_menu_section' ),
			'attributes' => array(
				'id' => array(
					'type' => 'number',
					'minimum' => '0',
				),
			),
		) );

		register_block_type( 'food-and-drink-menu/menu-item', array(
			'editor_script' => 'food-and-drink-menu-blocks',
			'editor_style' => $load_styles,
			'render_callback' => array( $this, 'render_menu_item' ),
			'attributes' => array(
				'id' => array(
					'type' => 'number',
					'minimum' => '0',
				),
			),
		) );

		add_action( 'current_screen', array( $this, 'register_admin' ) );
	}

	/**
	 * Register admin-only assets for block handling
	 */
	public function register_admin() {
		$screen = get_current_screen();

		if ( ! $screen->is_block_editor and $screen->id != 'widgets' ) { return; }

		$menus = new WP_Query( array(
			'post_type' => FDM_MENU_POST_TYPE,
			'posts_per_page' => 1000,
			'post_status' => 'publish',
		) );

		$menu_options = array( array( 'value' => 0, 'label' => '' ) );
		while ( $menus->have_posts() ) {
			$menus->the_post();
			$menu_options[] = array(
				'value' => get_the_ID(),
				'label' => get_the_title(),
			);
		}
		wp_reset_postdata();

		$menu_items = new WP_Query( array(
			'post_type' => FDM_MENUITEM_POST_TYPE,
			'posts_per_page' => 1000,
			'post_status' => 'publish',
		) );

		$menu_item_options = array( array( 'value' => 0, 'label' => '' ) );
		while ( $menu_items->have_posts() ) {
			$menu_items->the_post();
			$menu_item_options[] = array(
				'value' => get_the_ID(),
				'label' => get_the_title(),
			);
		}
		wp_reset_postdata();


		$menu_sections = get_terms( 'fdm-menu-section', array( 'hide_empty' => true ) );
		$menu_section_options = array( array( 'value' => 0, 'label' => '' ) );
		foreach( $menu_sections as $menu_section ) {
			$menu_section_options[] = array(
				'value' => $menu_section->term_id,
				'label' => $menu_section->name,
			);
		}

		wp_add_inline_script(
			'food-and-drink-menu-blocks',
			sprintf(
				'var fdm_blocks = %s;',
				json_encode( array(
					'menuOptions' => $menu_options,
					'menuItemOptions' => $menu_item_options,
					'menuSectionOptions' => $menu_section_options,
				) )
			),
			'before'
		);
	}

	/**
	 * Render a single menu
	 *
	 * @param array $attributes The block attributes
	 * @return string
	 */
	public function render_menu( $attributes ) {
		$id = !empty( $attributes['id'] ) ? absint( $attributes['id'] ) : 0;
		return !$id ? ' ' : do_shortcode('[fdm-menu id='  . $id . ']');
	}

	/**
	 * Render a single menu section
	 *
	 * @param array $attributes The block attributes
	 * @return string
	 */
	public function render_menu_section( $attributes ) {
		$id = !empty( $attributes['id'] ) ? absint( $attributes['id'] ) : 0;
		return !$id ? ' ' : do_shortcode('[fdm-menu-section id='  . $id . ']');
	}

	/**
	 * Render a single menu item
	 *
	 * @param array $attributes The block attributes
	 * @return string
	 */
	public function render_menu_item( $attributes ) {
		$id = !empty( $attributes['id'] ) ? absint( $attributes['id'] ) : 0;
		return !$id ? ' ' : do_shortcode('[fdm-menu-item id='  . $id . ']');
	}

	/**
	 * Create a new category of blocks to hold our block
	 * @since 2.0.0
	 */
	public function add_block_category( $categories ) {
		
		$categories[] = array(
			'slug'  => 'fdm-blocks',
			'title' => __( 'Five Star Restaurant Menu', 'food-and-drink-menu' ),
		);

		return $categories;
	}
}
} // endif
