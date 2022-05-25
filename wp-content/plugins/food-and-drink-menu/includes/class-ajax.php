<?php

/**
 * Class to handle everything related to the walk-through that runs on plugin activation
 */

if ( !defined( 'ABSPATH' ) )
	exit;

class fdmAjax {

	public function __construct() {
		
		add_action('wp_head',array( $this, 'frontend_ajax_url' ) );

		add_action( 'wp_ajax_fdm_menu_item_details', array( $this, 'display_menu_item_details' ) );
		add_action( 'wp_ajax_nopriv_fdm_menu_item_details', array( $this, 'display_menu_item_details' ) );

		add_action( 'wp_ajax_fdm_add_to_cart', array( $this, 'add_item_to_cart' ) );
		add_action( 'wp_ajax_nopriv_fdm_add_to_cart', array( $this, 'add_item_to_cart' ) );

		add_action( 'wp_ajax_fdm_update_cart_qty', array( $this, 'update_cart_quantities' ) );
		add_action( 'wp_ajax_nopriv_fdm_update_cart_qty', array( $this, 'update_cart_quantities' ) );

		add_action( 'wp_ajax_fdm_update_cart_item', array( $this, 'update_cart_item' ) );
		add_action( 'wp_ajax_nopriv_fdm_update_cart_item', array( $this, 'update_cart_item' ) );

		add_action( 'wp_ajax_fdm_delete_from_cart', array( $this, 'delete_item_from_cart' ) );
		add_action( 'wp_ajax_nopriv_fdm_delete_from_cart', array( $this, 'delete_item_from_cart' ) );

		add_action( 'wp_ajax_fdm_clear_cart', array( $this, 'clear_cart' ) );
		add_action( 'wp_ajax_nopriv_fdm_clear_cart', array( $this, 'clear_cart' ) );

		add_action( 'wp_ajax_fdm_submit_order', array( $this, 'submit_order' ) );
		add_action( 'wp_ajax_nopriv_fdm_submit_order', array( $this, 'submit_order' ) );

		add_action( 'wp_ajax_fdm_update_order_progress', array( $this, 'update_order_progress' ) );
		add_action( 'wp_ajax_nopriv_fdm_update_order_progress', array( $this, 'update_order_progress' ) );
	}

	public function frontend_ajax_url() {
		
		?>
	    	<script type="text/javascript">
	    	    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
	    	</script>
		<?php

	}

	public function display_menu_item_details() {
		
		$post_id = intval( $_POST['post_id'] );

		$item = new fdmViewItem( array( 'id' => $post_id ) );
		$item->set_singular(true);
		$output = $item->render();

		/*// Define the order to print the elements' HTML
		$elements = array(
			'image',
			'title',
			'price',
			'content',
			'custom_fields',
			'related_items'
		);

		foreach ($elements as $element) {
			$template = $item->find_template( $item->content_map[$element] );

			if ( $template ) {
				include( $template );
			}
		}*/

		echo $output;

		die();
	}

	public function add_item_to_cart() {		
		global $fdm_controller;

		$args = array(
			'item_identifier' 	=> sanitize_text_field( $_POST['item_identifier'] ),
			'id' 				=> intval( $_POST['post_id'] ),
			'selected_options'	=> ( isset( $_POST['selected_options'] ) and $_POST['selected_options'] != '' and is_array( explode(',', $_POST['selected_options'] ) ) ) ?  explode(',', sanitize_text_field( $_POST['selected_options'] ) ) : array() ,
			'note'				=> isset( $_POST['note'] ) ? sanitize_text_field( $_POST['note'] ) : '',
			'selected_price'	=> isset( $_POST['selected_price'] ) ? sanitize_text_field( $_POST['selected_price'] ) : '',
			'quantity' 			=> isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : 1 
		);

		$fdm_controller->cart->add_item( $args );

		$item = new fdmViewItem( $args );
		$output = $item->cart_render();

		echo $output;

		die();
	}

	public function update_cart_item() {		
		global $fdm_controller;

		$args = array();

		if ( isset( $_POST['item_identifier'] ) ) 	{ $args['item_identifier'] = sanitize_text_field( $_POST['item_identifier'] ); }
		if ( isset( $_POST['id'] ) ) 				{ $args['id'] = intval( $_POST['post_id'] ); }
		if ( isset( $_POST['options'] ) ) 			{ $args['options'] = is_array( unserialize( $_POST['options'] ) ) ? unserialize( sanitize_text_field( $_POST['options'] ) ) : array(); }
		if ( isset( $_POST['note'] ) ) 				{ $args['note'] = sanitize_text_field( $_POST['note'] ); }

		$fdm_controller->cart->update_item( $args );

		die();
	}

	public function delete_item_from_cart() {
		global $fdm_controller;

		$item_identifier = sanitize_text_field( $_POST['item_identifier'] );

		$fdm_controller->cart->delete_item( $item_identifier );

		die();
	}

	public function clear_cart() {
		global $fdm_controller;

		$fdm_controller->cart->clear_cart();
	}

	public function submit_order() {
		global $fdm_controller;

		$args = array(
			'permalink' => sanitize_url( $_POST['permalink'] ),
			'name' 			=> sanitize_text_field( $_POST['name'] ),
			'email'			=> sanitize_text_field( $_POST['email'] ),
			'phone'			=> sanitize_text_field( $_POST['phone'] ),
			'note' 			=> sanitize_text_field( $_POST['note'] ),
			'custom_fields' => array()
		);

		if( is_array( $_POST['custom_fields'] ) ) {
			$args['custom_fields'] = $_POST['custom_fields'];
			array_walk_recursive( $args['custom_fields'], function( &$item, $idx ) {
				$item = sanitize_text_field( $item );
			} );
		}

		if ( isset( $_POST['post_status'] ) ) {
			$args['post_status'] = sanitize_text_field( $_POST['post_status'] );
		}

		$order_id = $fdm_controller->orders->submit_order( $args );

		if ( $order_id ) {
			wp_send_json_success(
				array(
					'order_id'	=> $order_id
				)
			);
		}
		else {
			wp_send_json_error(
				array(
					'error'		=> 'submit_order_failed',
					'msg'		=> esc_html( $fdm_controller->settings->get_setting( 'label-order-failed' ) ),
				)
			);
		}
	}

	public function update_cart_quantities() {
		global $fdm_controller;

		if ( empty($_POST['quantity'] ) ) return;

		foreach ( $_POST['quantity'] as $item_identifier => $quantity ) {

			$fdm_controller->cart->update_item_quanity( sanitize_text_field( $item_identifier ), intval( $quantity ) );

		}

		die();
	}

	public function update_order_progress() {

		$order_statuses = fdm_get_order_statuses();

		// intval() return 0 for undefined which could be a valid post_id
		// $order_id = intval( $_POST['order_id'] );

		$order_status = get_post_status( $_POST['order_id'] );

		if ( $order_status ) {
			wp_send_json_success(
				array(
					'status'	=> $order_status,
					'value'		=> $order_statuses[ $order_status ]['value'],
				)
			);
		}
		else {
			wp_send_json_error(
				array(
					'error'		=> 'no_status',
					'msg'		=> __( 'Order does not exist', 'food-and-drink-menu' ),
				)
			);
		}
	}
}


?>