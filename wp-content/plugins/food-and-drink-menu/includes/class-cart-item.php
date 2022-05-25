<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'fdmCartItem' ) ) {
/**
 * Class for any item added to the ordering cart
 *
 * @since 2.1.0
 */
class fdmCartItem {

	// The ID for the post corresponding to this item
	public $id;

	// The options (cheese, lettuce, tomato, etc.) for this item, w/ price of option
	public $selected_options = array();

	// The customer's note about this item
	public $note;

	// Item quantity
	public $quantity;

	public function __construct( $args ) {
		
		// Parse the values passed
		$this->parse_args( $args );
	}

	public function update( $args ) {

		if ( ! is_array( $args ) ) { return; }

		foreach ( $args as $key => $value ) {
			$this->$key = $value;
		}
	}

	public function get_item_price() {

		$price = 0;

		foreach ( $this->selected_options as $selected_option ) {
			if ( isset( $selected_option['price'] ) ) { $price += $selected_option['price']; }
		}

		return $price;
	}

	/**
	 * Parse the arguments passed in the construction and assign them to
	 * internal variables.
	 * @since 2.1
	 */
	public function parse_args( $args ) {
		foreach ( $args as $key => $val ) {
			switch ( $key ) {

				case 'id' :
					$this->{$key} = esc_attr( $val );

				default :
					$this->{$key} = $val;

			}
		}
	}

}
}

?>