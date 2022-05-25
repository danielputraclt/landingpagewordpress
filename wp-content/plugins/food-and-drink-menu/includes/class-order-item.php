<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'fdmOrderItem' ) ) {
/**
 * Class for any order made through the plugin
 *
 * @since 2.1.0
 */
class fdmOrderItem {

	// The ID for this order item
	public $id;

	// The items that were included in this order
	public $order_items = array();

	// The customer's name for this order
	public $name;

	// The customer's email for this order
	public $email;

	// The customer's phone for this order
	public $phone;

	// The customer's note about this order
	public $note;

	// The time this order was created
	public $order_time;

	// The status of this order
	public $post_status;

	// The URL that the order was received from
	public $permalink;

	// The receipt id of the online order
	public $receipt_id;

	// The amount paid online for this order
	public $payment_amount;

	// The custom fields associated with this order
	public $custom_fields = array();

	public function __construct( $args = array() ) {
		
		// Parse the values passed
		$this->parse_args( $args );
	}

	public function load( $post ) {

		if ( is_int( $post ) || is_string( $post ) ) {
			$post = get_post( $post );
		}

		if ( get_class( $post ) == 'WP_Post' && $post->post_type == FDM_ORDER_POST_TYPE ) {
			$this->load_wp_post( $post );
			return true;
		}
		else {
			return false;
		}
	}

	public function load_wp_post( $post ) {

		// Store post for access to other data if needed by extensions
		$this->post = $post;

		$this->id = $this->ID = $post->ID;
		$this->date = $post->post_date;
		$this->order_items = unserialize( $post->post_content );
		$this->post_status = $post->post_status;

		$this->load_post_metadata();

		do_action( 'fdm_order_load_post_data', $this, $post );
	}

	public function load_post_metadata() {

		$meta_defaults = array(
			'name' => '',
			'email' => '',
			'phone' => '',
			'note' => '',
			'receipt_id' => '',
			'payment_amount' => 0,
			'permalink' => get_site_url(),
			'custom_fields' => array()
		);

		$meta_defaults = apply_filters( 'fdm_order_metadata_defaults', $meta_defaults );

		if ( is_array( $meta = get_post_meta( $this->ID, 'order_data', true ) ) ) {
			$meta = array_merge( $meta_defaults, get_post_meta( $this->ID, 'order_data', true ) );
		} else {
			$meta = $meta_defaults;
		}

		$this->name = $meta['name'];
		$this->email = $meta['email'];
		$this->phone = $meta['phone'];
		$this->note = $meta['note'];
		$this->permalink = $meta['permalink'];
		$this->receipt_id = $meta['receipt_id'];
		$this->payment_amount = $meta['payment_amount'];
		$this->custom_fields = $meta['custom_fields'];
	}

	public function update( $args ) {

		if ( ! is_array( $args ) ) { return; }

		foreach ( $args as $key => $value ) {
			$this->$key = $value;
		}
	}

	public function format_date( $date ) {

		$date = mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $date);
		return apply_filters( 'get_the_date', $date );
	}

	public function save_order_post() {

		$post = array(
			'post_title'	=> $this->name,
			'post_status' 	=> 'draft',
			'post_content'	=> serialize( $this->order_items ),
			'post_type'		=> FDM_ORDER_POST_TYPE
		);

		if ( isset( $this->id ) ) { $post['ID'] = $this->id;}

		$post_id = wp_insert_post( $post );

		if ( $post_id ) { 
				
			$this->id = $post_id; 

			$postmeta = array(
				'name'           => $this->name,
				'email'          => $this->email,
				'phone'          => $this->phone,
				'note'           => $this->note,
				'receipt_id'     => $this->receipt_id,
				'permalink'      => $this->permalink,
				'payment_amount' => $this->payment_amount,
				'custom_fields'	 => $this->custom_fields
			);

			update_post_meta( $post_id, 'order_data', $postmeta );
		}

		// it is done this way, because we send email notification on post_status
		// transition hook and notificaton need post_meta information
		$args = array(
			'ID'          => $this->id,
			'post_status' => $this->post_status
		);

		wp_update_post( $args );
	}

	public function set_order_items( $items ) {

		$this->order_items = $items;
	}

	public function get_order_items() {

		return is_array( $this->order_items ) ? $this->order_items : array();
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