<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'rtbPaymentGatewayStripe' ) ) {
/**
 * This class is responsible for payment processing via Stripe
 * 
 * @since 2.3.0
 */
class rtbPaymentGatewayStripe implements rtbPaymentGateway {

  private static $_instance;

  private final function __construct() {

    $this->HOLD = [
      'not-placed'    => __( 'Not Placed', 'restaurant-reservations' ),
      'hold-placed'   => __( 'Hold Placed', 'restaurant-reservations' ),
      'hold-captured' => __( 'Hold Captured', 'restaurant-reservations' )
    ];

    $this->register_hooks();

  }

  public static function register_gateway (array $gateway_list )
  {
    return array_merge(
      $gateway_list,
      [
        'stripe' => [
          'label'    => __( 'Stripe', 'restaurant-reservations' ),
          'instance' => self::get_instance()
        ]
      ]
    );
  }

  /**
   * Get singleton instance of the class
   * 
   * @return rtbPaymentGatewayStripe instance
   */
  public static function get_instance()
  {
    if( ! isset( self::$_instance ) ) {
      self::$_instance = new rtbPaymentGatewayStripe();
    }
    
    return self::$_instance;
  }

  public function register_hooks()
  {
    add_action( 'rtb_booking_form_init', [$this, 'process_payment'] );

    add_action( 'wp_ajax_rtb_stripe_get_intent', array( $this, 'create_stripe_pmtIntnt' ) );
    add_action( 'wp_ajax_nopriv_rtb_stripe_get_intent', array( $this, 'create_stripe_pmtIntnt' ) );

    add_action( 'wp_ajax_rtb_stripe_pmt_succeed', array( $this, 'stripe_sca_succeed' ) );
    add_action( 'wp_ajax_nopriv_rtb_stripe_pmt_succeed', array( $this, 'stripe_sca_succeed' ) );

    add_filter( 'rtb_booking_metadata_defaults', [$this, 'default_booking_stripe_info'], 30, 1 );
    add_action( 'rtb_booking_load_post_data', [$this, 'populate_booking_stripe_info'], 30, 2 );
    add_filter( 'rtb_insert_booking_metadata', [$this, 'save_booking_gateway_info'], 30, 2 );

    /**
     * Adding info and capability to cahrge the hold manually in the bookings table for admin
     */
    add_filter( 'rtb_admin_bookings_list_row_classes', [$this, 'add_hold_class'], 30, 2 );
    add_filter( 'rtb_bookings_table_column_details', [$this, 'add_hold_detail'], 30, 2 );
    add_filter( 'rtb_bookings_table_bulk_actions', [$this, 'add_bulk_action'], 30, 1 );
    add_filter( 'rtb_bookings_table_bulk_action', [$this, 'charge_the_hold'], 30, 3 );
    add_action( 'rtb_payment_summary', [$this, 'payment_summary'] );
  }

  public function print_payment_form( $booking )
  {
    global $rtb_controller;

    // Function alias
    $_gs = [$rtb_controller->settings, 'get_setting'];
    $SCA = $_gs( 'rtb-stripe-sca' );

    $btn_disabled = '';
    $stripe_lib_version = 'v2';

    if( $SCA )
    {
      $btn_disabled = "disabled='disabled'";
      $stripe_lib_version = 'v3';
    }

    // Stripe Lib
    wp_enqueue_script(
      'rtb-stripe', 
      "https://js.stripe.com/{$stripe_lib_version}/", 
      array( 'jquery' ), 
      RTB_VERSION, 
      true
    );

    // Stripe-JS processing logic
    wp_enqueue_script(
      'rtb-stripe-payment', 
      RTB_PLUGIN_URL . '/assets/js/stripe-payment.js', 
      array( 'jquery', 'rtb-stripe' ), 
      RTB_VERSION, 
      true
    );

    wp_localize_script(
      'rtb-stripe-payment',
      'rtb_stripe_payment',
      array(
        'nonce' => wp_create_nonce( 'rtb-stripe-payment' ),
        'stripe_mode' => $_gs( 'rtb-stripe-mode' ),
        'stripe_sca'  => $SCA,
        'live_publishable_key' => $_gs( 'rtb-stripe-live-publishable' ),
        'test_publishable_key' => $_gs( 'rtb-stripe-test-publishable' ),
      )
    );

    $cc_exp_single_field = null != $_gs( 'rtb-expiration-field-single' )
      ? "<input type='text' data-stripe='exp_month_year' class='single-masked'>"
      : "<input type='text' size='2' data-stripe='exp_month'>
        <span> / </span>
        <input type='text' size='4' data-stripe='exp_year'>";
    ?>

    <div class='payment-errors'></div>

    <form 
      action='#' 
      method='POST' 
      id='stripe-payment-form' 
      data-booking_id='<?php echo esc_attr( $booking->ID ) ;?>'>

      <?php wp_nonce_field( 'rtb-stripe-payment', 'nonce' ) ?>

      <?php if( $SCA ) { ?>

        <div class='form-row'>
          <label>
            <?php echo esc_html( $rtb_controller->settings->get_setting( 'label-card-detail' ) ); ?>
          </label>
          <span id="cardElement"></span>
        </div>

      <?php } else { ?>

        <div class='form-row'>
          <label>
            <?php echo esc_html( $rtb_controller->settings->get_setting( 'label-card-number' ) ); ?>
          </label>
          <input type='text' size='20' autocomplete='off' data-stripe='card_number'/>
        </div>
        <div class='form-row'>
          <label>
            <?php echo esc_html( $rtb_controller->settings->get_setting( 'label-cvc' ) ); ?>
          </label>
          <input type='text' size='4' autocomplete='off' data-stripe='card_cvc'/>
        </div>
        <div class='form-row'>
          <label>
            <?php echo esc_html( $rtb_controller->settings->get_setting( 'label-expiration' ) ); ?>
          </label>
          <?php echo $cc_exp_single_field; ?>
        </div>
        <input type='hidden' name='action' value='rtb_stripe_booking_payment'/>
        <input type='hidden' name='currency' value='<?php echo esc_attr( $_gs( 'rtb-currency' ) ); ?>' data-stripe='currency' />
        <input type='hidden' name='payment_amount' value='<?php echo esc_attr( $booking->calculate_deposit() ); ?>' />
        <input type='hidden' name='booking_id' value='<?php echo esc_attr( $booking->ID ); ?>' />

      <?php } ?>
      
      <p class="stripe-payment-help-text">
        <?php echo esc_html( $rtb_controller->settings->get_setting( 'label-please-wait' ) ); ?>
      </p>

      <button type='submit' id='stripe-submit' <?php echo $btn_disabled; ?>>
        <?php echo esc_html( $rtb_controller->settings->get_setting( 'label-make-deposit' ) ); ?>
      </button>

    </form>
    <?php
  }

  /**
   * Maybe process payment, if coming from Stripe payment form
   * 
   * @return void Redirect and exit or return
   */
  public function process_payment()
  {
    global $rtb_controller;

    if ( ! isset( $_POST['stripeToken'] ) ||  ! isset( $_POST['booking_id'] ) ) {
      return;
    }

    // Function alias
    $_gs = [$rtb_controller->settings, 'get_setting'];
    $booking_id = intval( $_POST['booking_id'] );

    // Define the form's action parameter
    $booking_page = $_gs( 'booking-page' );
    if ( ! empty( $booking_page ) ) {
      $booking_page = get_permalink( $booking_page );
    }
    else {
     $booking_page = get_permalink();
    }

    if ( !wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'rtb-stripe-payment' ) ) {
      // Invalid request
      $redirect = add_query_arg(
        array(
          'payment' => 'failed',
          'booking_id' => $booking_id,
          'error_code' => urlencode( __( 'The request has been rejected because it does not appear to have come from this site.', 'restaurant-reservations' ) )
        ),
        $booking_page
      );

      // redirect back to our previous page with the added query variable
      wp_redirect($redirect); exit;
    }

    // load the stripe libraries
    require_once(RTB_PLUGIN_DIR . '/lib/stripe/init.php');

    // retrieve the token generated by stripe.js
    $token = sanitize_text_field( $_POST['stripeToken'] );

    // JPY currency does not have any decimal palces
    $is_JPY = "JPY" != $_gs( 'rtb-currency' );
    $payment_amount = $is_JPY ? ( intval( $_POST['payment_amount'] ) * 100 ) : intval( $_POST['payment_amount'] );
   
    require_once( RTB_PLUGIN_DIR . '/includes/Booking.class.php' );
    $booking = new rtbBooking();
    $booking->load_post( $booking_id );

    try {

      $metadata = array_filter([
        'Booking ID' => $booking->ID,
        'Email'      => $booking->email,
        'Name'       => $booking->name,
        'Date'       => $booking->date,
        'Party'      => $booking->party
      ]);

      \Stripe\Stripe::setApiKey( $this->get_secret() );
      $charge = \Stripe\Charge::create(array(
          'amount'    => $payment_amount, 
          'currency'  => strtolower( $_gs( 'rtb-currency' ) ),
          'card'      => $token,
          'metadata'  => $metadata
        )
      );

      $booking->deposit = $is_JPY ? $payment_amount / 100 : $payment_amount;
      $booking->receipt_id = $charge->id;

      $booking->determine_status( true );

      $booking->insert_post_data();

      do_action( 'rtb_booking_paid', $booking );

      // redirect on successful payment
      $redirect = add_query_arg(
        array(
          'payment' => 'paid',
          'booking_id' => $booking_id
        ),
        $booking_page
      );

    }
    catch ( Exception $ex ) {

      $booking->payment_failed( $ex->getMessage() );

      // redirect on failed payment
      $redirect = add_query_arg(
        array(
          'payment' => 'failed',
          'booking_id' => $booking_id,
          'error_code' => urlencode( $ex->getMessage() )
        ),
        $booking_page
      );
    }

    // redirect back to our previous page with the added query variable
    wp_redirect($redirect); exit;
  }

  /**
   * Create Stripe payment intent for reservation deposits
   * Respond to AJAX/XHR request
   * 
   * @since 2.2.8
   */
  public function create_stripe_pmtIntnt()
  {
    global $rtb_controller;

    $response = function ($success, $msg, $data = []) {
      echo json_encode(
        array_merge(
          [
            'success' => $success,
            'message' => $msg
          ], 
          $data
        )
      );

      exit(0);
    };

    if ( !check_ajax_referer( 'rtb-stripe-payment', 'nonce' ) ) {
      $response(false, __( 'The request has been rejected because it does not appear to have come from this site.', 'restaurant-reservations' ) );
    }

    if( ! isset( $_POST['booking_id'] ) ) {
      $response(false, 'Invalid booking.');
    }

    require_once( RTB_PLUGIN_DIR . '/includes/Booking.class.php' );
    $booking = new rtbBooking();
    $booking->load_post( intval( $_POST['booking_id'] ) );

    $payment_amount = "JPY" != $rtb_controller->settings->get_setting( 'rtb-currency' ) 
      ? $booking->calculate_deposit() * 100 
      : $booking->calculate_deposit();

    // load the stripe libraries
    require_once(RTB_PLUGIN_DIR . '/lib/stripe/init.php');

    try {
      \Stripe\Stripe::setApiKey( $this->get_secret() );

      // $customer = \Stripe\Customer::create([
      //  'email' => $booking->email,
      //  'name' => $booking->name
      // ]);

      // $booking->stripe_customer_id = $customer->id;
      // $booking->insert_post_data();

      $metadata = array_filter([
        'Booking ID' => $booking->ID,
        'Email'      => $booking->email,
        'Name'       => $booking->name,
        'Date'       => $booking->date,
        'Party'      => $booking->party
      ]);

      if( is_array( $booking->table ) && ! empty( $booking->table ) ) {
        $metadata['Table'] = implode('+', $booking->table);
      }

      $desc = implode(', ', $metadata );
      $stmt_desc = substr( implode( ';', $metadata ), 0, 22 );

      $intentData = [
        'amount' => $payment_amount,
        'currency' => $rtb_controller->settings->get_setting( 'rtb-currency' ),
        'payment_method_types' => ['card'],
        'receipt_email' => $booking->email,
        'description' => apply_filters( 'rtb-stripe-payment-desc', $desc ),
        'statement_descriptor' => apply_filters( 'rtb-stripe-payment-stmnt-desc', $stmt_desc ),
        'metadata' => $metadata
      ];

      $booking->stripe_payment_hold_status = 'not-placed';
      if( $rtb_controller->settings->get_setting( 'rtb-stripe-hold' ) ) {
        $booking->stripe_payment_hold_status = 'hold-placed';
        $intentData['capture_method'] = 'manual';
      }

      $intent = \Stripe\PaymentIntent::create( $intentData );

      // Used this for verification of two step payment processing under SCA
      $booking->stripe_payment_intent_id = $intent->id;

      $booking->insert_post_data();

      $response(
        true, 
        'Payment Intent generated succsssfully', 
        [
          'clientSecret' => $intent->client_secret,
          'name' => $booking->name,
          'email' => $booking->email,
        ]
      );
    }
    catch(Exception $ex) {
      $response( false, 'Please try again.', ['error' => $ex->getError()] );
    }
  }

  /**
   * Stripe SCA payment's final status for reservation deposits
   * Respond to AJAX/XHR request
   * 
   * @since 2.2.8
   */
  public function stripe_sca_succeed()
  {
    global $rtb_controller;

    if ( !check_ajax_referer( 'rtb-stripe-payment', 'nonce' ) ) {
      $response(false, __( 'The request has been rejected because it does not appear to have come from this site.', 'restaurant-reservations' ) );
    }

    $response = function ($success, $urlParams, $data = []) {
      echo json_encode(
        array_merge(
          [
            'success' => $success,
            'urlParams' => $urlParams
          ], 
          $data
        )
      );

      exit(0);
    };

    // Response variables with fallback defaults
    $success = false;
    $urlParams = '';
    $data = [];

    if( ! isset( $_POST['booking_id'] ) ) {
      $response();
    }

    require_once( RTB_PLUGIN_DIR . '/includes/Booking.class.php' );
    $booking = new rtbBooking();
    $loaded = $booking->load_post( intval( $_POST['booking_id'] ) );


    try {

      if( isset( $_POST['success'] ) && 'false' != sanitize_text_field( $_POST['success'] ) ) {

        if( ! $loaded || ! $this->valid_payment( $booking ) ) {
          throw new Exception( __( 'Invalid submission. Please contact admin', 'restaurant-reservations' ) );
        }

        $booking->deposit = "JPY" != $rtb_controller->settings->get_setting( 'rtb-currency' ) 
          ? intval( $_POST['payment_amount'] ) / 100 
          : intval( $_POST['payment_amount'] );

        $booking->receipt_id = sanitize_text_field( $_POST['payment_id'] );

        // Not needed anymore
        unset( $booking->stripe_payment_intent_id );
        $booking->payment_paid();

        // urlParams on successful payment
        $success = true;

        $urlParams = array(
          'payment'    => 'paid',
          'booking_id' => intval( $booking->ID )
        );

      }
      else {
        $payment_failure_message = ! empty( $_POST['message'] ) 
          ? sanitize_text_field( $_POST['message'] ) 
          : __( 'Payment charge failed. Please try again', 'restaurant-reservations' );

        throw new Exception( $payment_failure_message );
      }
    }
    catch(Exception $ex) {

      $loaded && $booking->payment_failed( $ex->getMessage() );
      $data['message'] = $ex->getMessage();
    }

    $response($success, $urlParams, $data);
  }

  /**
   * Validate the payment success request by verifing the payment_intent ID
   * 
   * @return bool true on valid else false
   */
  public function valid_payment( $booking )
  {
    return sanitize_text_field( $_POST['payment_id'] ) == $booking->stripe_payment_intent_id;
  }

  /**
   * Repopulate $booking with stripe meta information
   * 
   * @param  rtbBooking $booking
   * @param  WP_Post $wp_post
   */
  public function populate_booking_stripe_info( $booking, $wp_post )
  {
    $meta = get_post_meta( $booking->ID, 'rtb', true );

    if ( is_array( $meta ) && isset( $meta['stripe_customer_id'] ) ) {
      $booking->stripe_customer_id = $meta['stripe_customer_id'];
    }

    if ( is_array( $meta ) && isset( $meta['stripe_payment_intent_id'] ) ) {
      $booking->stripe_payment_intent_id = $meta['stripe_payment_intent_id'];
    }

    if ( is_array( $meta ) && isset( $meta['stripe_payment_hold_status'] ) ) {
      $booking->stripe_payment_hold_status = $meta['stripe_payment_hold_status'];
    }


  }

  /**
   * Set $booking's default stripe meta information
   * 
   * @param  rtbBooking $booking
   * @param  WP_Post $wp_post
   */
  public function default_booking_stripe_info( $info_list )
  {
    $info_list['stripe_customer_id'] = '';

    return $info_list;
  }

  public function save_booking_gateway_info ( $meta, $booking )
  {
    if ( isset( $booking->stripe_customer_id ) && !empty( $booking->stripe_customer_id ) ) {
      $meta['stripe_customer_id'] = $booking->stripe_customer_id;
    }

    if ( isset( $booking->stripe_payment_intent_id ) && !empty( $booking->stripe_payment_intent_id ) ) {
      $meta['stripe_payment_intent_id'] = $booking->stripe_payment_intent_id;
    }

    if ( isset( $booking->stripe_payment_hold_status ) ) {
      $meta['stripe_payment_hold_status'] = $booking->stripe_payment_hold_status;
    }

    return $meta;
  }

  /**
   * Add the CSS class to admin booking listing
   * @param array $row_class_list css classes for the row
   * @param rtbBooking $booking
   */
  public function add_hold_class($row_class_list, $booking)
  {
    if( $this->is_payment_on_hold($booking) ) {
      $row_class_list[] = 'payment-on-hold';
    }
    return $row_class_list;
  }

  /**
   * Add hold information in the details popup og the booking for admin
   * @param array $details Lable/value item array
   * @param rtbBooking $booking
   */
  public function add_hold_detail($details, $booking)
  {
    if ( $this->is_payment_on_hold($booking) ) {
      $details[] = array(
        'label' => __('Payment on Hold', 'restaurant-reservations'),
        'value' => __('Payment has been held on the card, but not charged yet.', 'restaurant-reservations')
      );
    }
    else if ( $this->is_payment_hold_captured($booking) ) {
      $details[] = array(
        'label' => __('Held Payment Captured', 'restaurant-reservations'),
        'value' => __('Payment has been captured.', 'restaurant-reservations')
      );
    }

    return $details;
  }

  /**
   * Add Bulk action to booking page for admin to charge the hold manually
   * @param array $actions
   */
  public function add_bulk_action($actions)
  {
    $actions['capture-payment'] = __( 'Charge Payment on Hold',  'restaurant-reservations' );

    return $actions;
  }

  /**
   * Complete the hold and charge the customer
   * @param  array $result Array with booking ID as key and value as message
   * @param  int $id booking-id
   * @param  string $action bulk action
   * @return $result result array with result if we processed the booking
   */
  public function charge_the_hold($result, $id, $action)
  {
    if ( 'capture-payment' !== $action ) {
      return $result;
    }

    require_once( RTB_PLUGIN_DIR . '/includes/Booking.class.php' );
    $booking = new rtbBooking();
    if( $booking->load_post( intval( $id ) ) ) {
      if( $this->is_payment_on_hold( $booking ) ) {

        try {
          // load the stripe libraries
          require_once(RTB_PLUGIN_DIR . '/lib/stripe/init.php');

          \Stripe\Stripe::setApiKey( $this->get_secret() );

          $intent = \Stripe\PaymentIntent::retrieve( $booking->receipt_id );
          $intent->capture();

          if( 'succeeded' == $intent->status ) {
            $this->hold_captured( $booking );
            // Payment has been captured successfully
            $result[$id] = true;
          }
          else {
            $result[$id] = false;
            if( defined('WP_DEBUG') and WP_DEBUG ) {
              error_log(sprintf( __( 'Five Star RTB: Stripe Payment capture failed. Reason: %s', 'restaurant-reservations' ), $intent->status ));
            }
          }
        }
        catch(Exception $ex) {
          $result[$id] = false;
          if( defined('WP_DEBUG') and WP_DEBUG ) {
            error_log( sprintf( __( 'Five Star RTB: Stripe Payment capture failed. Reason: %s', 'restaurant-reservations' ), $ex->getMessage() ) );
          }
        }
      }
      else {
        // We do not have a hold for this Booking
        // $result[$id] = true;
      }
    }
    else {
      $result[$id] = false;
      if( defined('WP_DEBUG') and WP_DEBUG ) {
        error_log( sprintf( __( 'Unable to find the Booking for ID %s', 'restaurant-reservations' ), $id ) );
      }
    }

    return $result;
  }

  /**
   * Check whether the payment is on hold or not
   * @param  rtbBooking  $booking
   * @return boolean
   */
  public function is_payment_on_hold($booking)
  {
    return isset( $booking->stripe_payment_hold_status ) && 'hold-placed' == $booking->stripe_payment_hold_status;
  }

  /**
   * Check whether the payment is on hold or not
   * @param  rtbBooking  $booking
   * @return boolean
   */
  public function is_payment_hold_captured($booking)
  {
    return isset( $booking->stripe_payment_hold_status ) && 'hold-captured' == $booking->stripe_payment_hold_status;
  }

  /**
   * Mark the Payment Hold for the booking as Captured
   * @param  rtbBooking $booking
   * @return rtbPaymentGatewayStripe
   */
  public function hold_captured($booking)
  {
    $booking->stripe_payment_hold_status = 'hold-captured';
    $booking->insert_post_data();

    return $this;
  }

  /**
   * Get Stripe secret
   * @return string
   */
  public function get_secret() {
    global $rtb_controller;

    return 'test' == $rtb_controller->settings->get_setting( 'rtb-stripe-mode' ) 
      ? $rtb_controller->settings->get_setting( 'rtb-stripe-test-secret' ) 
      : $rtb_controller->settings->get_setting( 'rtb-stripe-live-secret' );
  }

  public function payment_summary() {
    global $rtb_controller;

    if ( $rtb_controller->settings->get_setting('rtb-stripe-sca') && $rtb_controller->settings->get_setting( 'rtb-stripe-hold' ) ) {
      echo '<p class="stripe-payment-hold-msg">' . esc_html( $rtb_controller->settings->get_setting( 'label-deposit-placing-hold'  ) ) . '</p>';
    }
  }
}

}

/**
 * Gateway has to register itself
 */
add_filter(
  'rtb-payment-gateway-register', 
  ['rtbPaymentGatewayStripe', 'register_gateway']
);