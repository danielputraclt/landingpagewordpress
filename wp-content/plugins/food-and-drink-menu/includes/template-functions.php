<?php
/**
 * Template tags and shortcodes for use with Food and Drink Menu
 */


/**
 * Create a shortcode to display a menu
 * @since 1.0
 */
function fdm_menu_shortcode( $atts ) {
	global $fdm_controller;

	// Define shortcode attributes
	$menu_atts = array(
		'id' => null,
		'layout' => 'classic',
		'show_title' => false,
		'show_content' => false,
	);

	// Create filter so addons can modify the accepted attributes
	$menu_atts = apply_filters( 'fdm_shortcode_menu_atts', $menu_atts );

	// Extract the shortcode attributes
	$args = shortcode_atts( $menu_atts, $atts, 'fdm-menu' );

	if ( isset( $_POST['stripeToken'] ) ) { fdm_process_stripe_payment(); }

	if ( ! empty( $_GET['order_success'] ) ) {

		echo '<div class="fdm-order-payment-message fdm-order-payment-successful">' . esc_html( $fdm_controller->settings->get_setting( 'label-order-success' ) ) . '</div>';
	}

	fdm_possible_order_status_update();

	fdm_possible_payment_enqueues();

	// Render menu
	fdm_load_view_files();
	$menu = new fdmViewMenu( $args );

	return $menu->render();
}
add_shortcode( 'fdm-menu', 'fdm_menu_shortcode' );

/**
 * Create a shortcode to display a menu section
 * @since 1.0
 */
function fdm_menu_section_shortcode( $atts ) {

	// Define shortcode attributes
	$menu_section_atts = array(
		'id' => null,
	);

	// Create filter so addons can modify the accepted attributes
	$menu_section_atts = apply_filters( 'fdm_shortcode_menu_section_atts', $menu_section_atts );

	// Extract the shortcode attributes
	$args = shortcode_atts( $menu_section_atts, $atts, 'fdm-menu-section' );

	// Render menu
	fdm_load_view_files();
	$menu = new fdmViewSection( $args );

	return $menu->render();
}
add_shortcode( 'fdm-menu-section', 'fdm_menu_section_shortcode' );

/**
 * Create a shortcode to display a menu item
 * @since 1.1
 */
function fdm_menu_item_shortcode( $atts ) {

	// Define shortcode attributes
	$menu_item_atts = array(
		'id' => null,
		'layout' => 'classic',
		'singular' => true
	);

	// Create filter so addons can modify the accepted attributes
	$menu_item_atts = apply_filters( 'fdm_shortcode_menu_item_atts', $menu_item_atts );

	// Extract the shortcode attributes
	$args = shortcode_atts( $menu_item_atts, $atts, 'fdm-menu-item' );

	// Render menu
	fdm_load_view_files();
	$menuitem = new fdmViewItem( $args );

	return $menuitem->render();
}
add_shortcode( 'fdm-menu-item', 'fdm_menu_item_shortcode' );

/**
 * Load files needed for views
 * @since 1.1
 * @note Can be filtered to add new classes as needed
 */
function fdm_load_view_files() {

	$files = array(
		FDM_PLUGIN_DIR . '/views/Base.class.php' // This will load all default classes
	);

	$files = apply_filters( 'fdm_load_view_files', $files );

	foreach( $files as $file ) {
		require_once( $file );
	}

}

/*
 * Assign a globally unique id for each displayed menu
 */
$globally_unique_id = 0;
function fdm_global_unique_id() {
	global $globally_unique_id;
	$globally_unique_id++;
	return 'fdm-menu-' . $globally_unique_id;
}

/**
 * Transform an array of CSS classes into an HTML attribute
 * @since 1.0
 */
function fdm_format_classes($classes) {
	if (count($classes)) {
		return ' class="' . join(" ", $classes) . '"';
	}
}

/**
 * Format the item prices based on the currency symbol settings
 * @since 2.1
 */
function fdm_format_price( $price ) {
	global $fdm_controller;

	$prefix = ( $fdm_controller->settings->get_setting('fdm-currency-symbol-location') == 'before' ? $fdm_controller->settings->get_setting('fdm-currency-symbol') : '' );
	$suffix = ( $fdm_controller->settings->get_setting('fdm-currency-symbol-location') == 'after' ? $fdm_controller->settings->get_setting('fdm-currency-symbol') : '' );

	$price = $prefix . $price . $suffix;

	return $price;
}

/**
 * Return the price total based on the size and options selected
 * @since 2.1
 */
function fdm_calculate_cart_price( $menu_item ) {

	$ordering_options = get_post_meta( $menu_item->id, '_fdm_ordering_options', true );
	if ( ! is_array( $ordering_options ) ) { $ordering_options = array(); }

	$selected_options = is_array( $menu_item->selected_options ) ? $menu_item->selected_options : array();

	$price = str_replace( ',', '.', preg_replace( '/[^0-9,.]+/', '', $menu_item->order_price ) );

	foreach ( $selected_options as $selected_option ) { 

		$option_price = is_numeric( str_replace( ',', '.', $ordering_options[ $selected_option ]['cost'] ) ) ? str_replace( ',', '.', $ordering_options[ $selected_option ]['cost'] ) : 0;
		$price += $option_price; 
	}

	return apply_filters( 'fdm_cart_price_value', $price, $menu_item );
}

/**
 * Return the price total based on the size and options selected for the admin table
 * @since 2.1.11
 */
function fdm_calculate_admin_price( $order_item ) {

	$ordering_options = get_post_meta( $order_item->id, '_fdm_ordering_options', true );
	if ( ! is_array( $ordering_options ) ) { $ordering_options = array(); }
	
	$selected_options = is_array( $order_item->selected_options ) ? $order_item->selected_options : array();

	$price = str_replace( ',', '.', preg_replace( '/[^0-9,.]+/', '', $order_item->selected_price ) ) * ( isset( $order_item->quantity ) ? intval( $order_item->quantity) : 1 );

	foreach ( $selected_options as $selected_option ) { 

		$option_price = is_numeric( str_replace( ',', '.', $ordering_options[ $selected_option ]['cost'] ) ) ? str_replace( ',', '.', $ordering_options[ $selected_option ]['cost'] ) : 0;
		$price += $option_price * ( isset( $order_item->quantity ) ? intval( $order_item->quantity) : 1 ); 
	}

	return apply_filters( 'fdm_admin_price_value', $price, $order_item );
}


/**
 * Check to see whether an order's status should be updated when the shortcode loads
 * @since 2.1
 */
function fdm_possible_order_status_update() {

	if ( isset( $_GET['fdm_action'] ) and $_GET['fdm_action'] == 'update_status' and current_user_can( 'manage_fdm_orders' ) ) {
		
		$id 		= intval( $_GET['order_id'] );
		$status 	= sanitize_text_field( $_GET['status'] );

		$order_statuses = fdm_get_order_statuses();

		$order_data = get_post_meta( $id, 'order_data', true );

		if ( array_key_exists( $status, $order_statuses ) ) {
			$post_id = wp_update_post( array( 'ID' => $id, 'post_status' => $status ) );

			if ( $post_id ) {
				echo '<div class="fdm-post-status-update">';
				echo __( 'Order status has been set to ', 'food-and-drink-menu' ) . $order_statuses[ $status ]['label'];
				echo '</div>';
			}
		}
		else {
			echo '<div class="fdm-post-status-update">';
			echo __( 'Order status could not be updated. Please make sure you\'re logged in and that the status exists.', 'food-and-drink-menu' );
			echo '</div>';
		}	
	}
	elseif ( isset( $_GET['fdm_action'] ) and $_GET['fdm_action'] == 'update_status' ) {
		echo '<div class="fdm-post-status-update">';
		echo __( 'You do not have permission to update the order\'s status. Please make sure you\'re logged in.', 'food-and-drink-menu' );
		echo '</div>';
	}
}

function fdm_possible_payment_enqueues() {
	global $fdm_controller;

	if ( ! $fdm_controller->settings->get_setting( 'enable-payment' ) ) { return; }

	if ( $fdm_controller->settings->get_setting( 'ordering-payment-gateway' ) == 'paypal' ) {
		wp_enqueue_script( 'fdm-paypal-payment', FDM_PLUGIN_URL . '/assets/js/paypal-payment.js', array( 'jquery' ), FDM_VERSION, true );
	} 
	else {

		wp_enqueue_script( 'fdm-stripe', 'https://js.stripe.com/v2/', array( 'jquery' ), FDM_VERSION, true );
		wp_enqueue_script( 'fdm-stripe-payment', FDM_PLUGIN_URL . '/assets/js/stripe-payment.js', array( 'jquery', 'fdm-stripe' ), FDM_VERSION, true );

		wp_localize_script(
			'fdm-stripe-payment',
			'fdm_stripe_payment',
			array(
				'stripe_mode' => $fdm_controller->settings->get_setting( 'ordering-payment-mode' ),
				'live_publishable_key' => $fdm_controller->settings->get_setting( 'stripe-live-publishable' ),
				'test_publishable_key' => $fdm_controller->settings->get_setting( 'stripe-test-publishable' ),
			)
		);
	}
}

/**
 * Creates a set of filterable order statuses for orders created by the plugin
 * @since 2.1
 */
function fdm_get_order_statuses() {

	$order_statuses = array( 
		'fdm_order_received' => array(
			'label' => __( 'Received', 'food-and-drink-menu' ),
			'value' => 25,
		),
		'fdm_order_accepted' => array(
			'label' => __( 'Accepted', 'food-and-drink-menu' ),
			'value' => 50,
		),
		'fdm_order_preparing' => array(
			'label' => __( 'Preparing', 'food-and-drink-menu' ),
			'value' => 75,
		),
		'fdm_order_ready' => array(
			'label' => __( 'Ready', 'food-and-drink-menu' ),
			'value' => 100,
		)
	);

	return apply_filters( 'fdm_order_statuses', $order_statuses );
}

/**
 * Process Stripe payments for restaurant orders
 * @since 2.1.4
 */
if ( !function_exists( 'fdm_process_stripe_payment' ) ) {
function fdm_process_stripe_payment() {
	global $fdm_controller;

	$order_id = isset($_POST['order_id']) ? absint( $_POST['order_id'] ) : 0;

	if ( ! $order_id ) { return; }		
 
	// load the stripe libraries
	require_once( FDM_PLUGIN_DIR . '/lib/stripe/init.php');
		
	// retrieve the token generated by stripe.js
	$token = $_POST['stripeToken'];

	$payment_amount = ( $fdm_controller->settings->get_setting( 'ordering-currency' ) != "JPY" ? intval( $_POST['payment_amount'] ) : intval( $_POST['payment_amount'] ) / 100 );

	$stripe_secret = $fdm_controller->settings->get_setting( 'ordering-payment-mode' ) == 'test' ? $fdm_controller->settings->get_setting( 'stripe-test-secret' ) : $fdm_controller->settings->get_setting( 'stripe-live-secret' );

	try {
		
		$order = new fdmOrderItem();
		$order->load( $order_id );

		\Stripe\Stripe::setApiKey( $stripe_secret );
		$charge = \Stripe\Charge::create(array(
				'amount' 	=> $payment_amount, 
				'currency' 	=> strtolower( $fdm_controller->settings->get_setting( 'ordering-currency' ) ),
				'card' 		=> $token,
				'metadata' 	=> array(
					'Name'	=> $order->name,
					'Email'	=> $order->email,
					'Phone'	=> $order->phone,
					'Note'	=> $order->note
				)
			)
		);

		$order->post_status = 'fdm_order_received';
		$order->payment_amount = ( $fdm_controller->settings->get_setting( 'ordering-currency' ) != "JPY" ? $payment_amount / 100 : $payment_amount );
		$order->receipt_id = $charge->id;

		$order->save_order_post();

		echo '<div class="fdm-order-payment-message fdm-order-payment-successful">' . sprintf( $fdm_controller->settings->get_setting( 'label-order-payment-success' ), fdm_format_price( $order->payment_amount ) ) . '</div>';
	 
	} catch (Exception $e) {

		echo '<div class="fdm-order-payment-message fdm-order-payment-failed">' . sprintf( $fdm_controller->settings->get_setting( 'label-order-payment-failed' ), $e->getDeclineCode() ) . '</div>';
	}
}
} // endif;

/**
 * Process Paypal payments for restaurant orders
 * @since 2.1.4
 */
// If there's an IPN request, add our setup function to potentially handle it
if ( isset($_POST['ipn_track_id']) ) { add_action( 'init', 'fdm_setup_paypal_ipn', 1); }
function fdm_setup_paypal_ipn() {
	global $fdm_controller;

	add_action(	'init', 'fdm_add_ob_start' );
	add_action(	'shutdown', 'fdm_flush_ob_end' );

	if ( ! $fdm_controller->settings->get_setting( 'enable-payment' ) ) { return; }

	fdm_handle_paypal_ipn();
}

/**
 * Handle PayPal IPN requests
 * @since 2.1.4
 */
if ( !function_exists( 'fdm_handle_paypal_ipn' ) ) {
function fdm_handle_paypal_ipn() {
	global $fdm_controller;
	
	// CONFIG: Enable debug mode. This means we'll log requests into 'ipn.log' in the same directory.
	// Especially useful if you encounter network errors or other intermittent problems with IPN (validation).
	// Set this to 0 once you go live or don't require logging.
	define("FDM_DEBUG", 0);
	// Set to 0 once you're ready to go live
	define("FDM_USE_SANDBOX", $fdm_controller->settings->get_setting( 'ordering-payment-mode' ) == 'test' ? true : 0 );
	define("FDM_LOG_FILE", "./ipn.log");
	// Read POST data
	// reading posted data directly from $_POST causes serialization
	// issues with array data in POST. Reading raw POST data from input stream instead.
	$raw_post_data = file_get_contents('php://input');
	$raw_post_array = explode('&', $raw_post_data);
	$myPost = array();
	foreach ($raw_post_array as $keyval) {
		$keyval = explode ('=', $keyval);
		if (count($keyval) == 2)
			$myPost[$keyval[0]] = urldecode($keyval[1]);
	}
	// read the post from PayPal system and add 'cmd'
	$req = 'cmd=_notify-validate';
	if(function_exists('get_magic_quotes_gpc')) {
		$get_magic_quotes_exists = true;
	}
	foreach ($myPost as $key => $value) {
		if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
			$value = urlencode(stripslashes($value));
		} else {
			$value = urlencode($value);
		}
		$req .= "&$key=$value";
	}
	// Post IPN data back to PayPal to validate the IPN data is genuine
	// Without this step anyone can fake IPN data
	if(FDM_USE_SANDBOX == true) {
		$paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
	} else {
		$paypal_url = "https://www.paypal.com/cgi-bin/webscr";
	}

	$response = wp_remote_post($paypal_url, array(
		'method' => 'POST',
		'body' => $req,
		'timeout' => 30
	));

	// Inspect IPN validation result and act accordingly
	// Split response headers and payload, a better way for strcmp
	$tokens = explode("\r\n\r\n", trim($response['body']));
	$res = trim(end($tokens));
	if (strcmp ($res, "VERIFIED") == 0) {
		
		$paypal_receipt_number = sanitize_text_field( $_POST['txn_id'] );
		$payment_amount = sanitize_text_field( $_POST['mc_gross'] );
		
		parse_str($_POST['custom'], $custom_vars); 
		$order_id = intval( $custom_vars['order_id'] );

		$order = new fdmOrderItem();
		$order->load( $order_id );

		if ( ! $order ) { return; }

		$order->receipt_id = sanitize_text_field( $paypal_receipt_number );
		$order->payment_amount = sanitize_text_field( $payment_amount );
		$order->post_status = 'fdm_order_received';

		$order->save_order_post();
		
		if ( FDM_DEBUG == true ) {
			error_log(date('[Y-m-d H:i e] '). "Verified IPN: $req ". PHP_EOL, 3, FDM_LOG_FILE);
		}
	}
}
} // endif;

/**
 * Opens a buffer when handling PayPal IPN requests
 * @since 2.1.4
 */
if ( !function_exists( 'rtb_add_ob_start' ) ) {
function rtb_add_ob_start() { 
    ob_start();
}
} // endif;

/**
 * Closes a buffer when handling PayPal IPN requests
 * @since 2.1.4
 */
if ( !function_exists( 'rtb_flush_ob_end' ) ) {
function rtb_flush_ob_end() {
    if ( ob_get_length() ) { ob_end_clean(); }
}
} // endif;


// Temporary addition, so that versions of WP before 5.3.0 are supported
if ( ! function_exists( 'wp_timezone') ) {
	function wp_timezone() {
		$timezone_string = get_option( 'timezone_string' );
 
    	if ( ! $timezone_string ) {
        	$offset  = (float) get_option( 'gmt_offset' );
    		$hours   = (int) $offset;
    		$minutes = ( $offset - $hours );

    		$sign      = ( $offset < 0 ) ? '-' : '+';
    		$abs_hour  = abs( $hours );
    		$abs_mins  = abs( $minutes * 60 );
    		$timezone_string = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );
    	}

    	return new DateTimeZone( $timezone_string );
	}
}