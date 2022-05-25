<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'fdmAdminOrders' ) ) {
/**
 * Class to handle the admin orders page for Five-Star Restaurant Menu
 *
 * @since 2.1.0
 */
class fdmAdminOrders {

	/**
	 * The orders table
	 *
	 * This is only instantiated on the orders admin page at the moment when
	 * it is generated.
	 *
	 * @see self::show_admin_orders_page()
	 * @see WP_List_table.OrdersTable.class.php
	 * @since 2.1.0
	 */
	public $orders_table;

	public function __construct() {

		// Add the admin menu
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );

		// Print the modals
		add_action( 'admin_footer-menu-posts-fdm-menu', array( $this, 'print_modals' ) );

		// Receive Ajax requests
		add_action( 'wp_ajax_nopriv_fdm-admin-orders-modal' , array( $this , 'nopriv_ajax' ) );
		add_action( 'wp_ajax_fdm-admin-orders-modal', array( $this, 'booking_modal_ajax' ) );
		add_action( 'wp_ajax_nopriv_fdm-admin-trash-order' , array( $this , 'nopriv_ajax' ) );
		add_action( 'wp_ajax_fdm-admin-trash-order', array( $this, 'trash_booking_ajax' ) );
		add_action( 'wp_ajax_nopriv_fdm-admin-email-modal' , array( $this , 'nopriv_ajax' ) );
		add_action( 'wp_ajax_fdm-admin-email-modal', array( $this, 'email_modal_ajax' ) );
		add_action( 'wp_ajax_nopriv_fdm-admin-column-modal' , array( $this , 'nopriv_ajax' ) );
		add_action( 'wp_ajax_fdm-admin-column-modal', array( $this, 'column_modal_ajax' ) );
		add_action( 'wp_ajax_nopriv_fdm-admin-ban-modal' , array( $this , 'nopriv_ajax' ) );
		add_action( 'wp_ajax_fdm-admin-ban-modal', array( $this, 'ban_modal_ajax' ) );
		add_action( 'wp_ajax_nopriv_fdm-admin-delete-modal' , array( $this , 'nopriv_ajax' ) );
		add_action( 'wp_ajax_fdm-admin-delete-modal', array( $this, 'delete_modal_ajax' ) );

		// Validate post status and notification fields
		add_action( 'fdm_validate_order_submission', array( $this, 'validate_admin_fields' ) );

		// Set post status when adding to the database
		add_filter( 'fdm_insert_order_data', array( $this, 'insert_order_data' ), 10, 2 );

		// Add the columns configuration button to the table
		add_action( 'fdm_orders_table_actions', array( $this, 'print_columns_config_button' ), 9 );

	}

	/**
	 * Add the top-level admin menu page
	 * @since 2.1.0
	 */
	public function add_menu_page() {

		add_submenu_page(
			'edit.php?post_type=fdm-menu',
			_x( 'Orders', 'Title of admin page that lists orders', 'food-and-drink-menu' ),
			_x( 'Orders', 'Title of orders admin menu item', 'food-and-drink-menu' ),
			'manage_options',
			'fdm-orders',
			array( $this, 'show_admin_orders_page' )
		);

	}

	/**
	 * Display the admin orders page
	 * @since 2.1.0
	 */
	public function show_admin_orders_page() {

		require_once( FDM_PLUGIN_DIR . '/includes/WP_List_Table.OrdersTable.class.php' );
		$this->orders_table = new fdmOrdersTable();
		$this->orders_table->prepare_items();
		?>

		<div class="wrap">
			<h1>
				<?php _e( 'Restaurant Orders', 'food-and-drink-menu' ); ?>
				<!-- <a href="#" class="add-new-h2 page-title-action add-order"><?php _e( 'Add New', 'food-and-drink-menu' ); ?></a> -->
			</h1>

			<?php do_action( 'fdm_orders_table_top' ); ?>
			<form id="fdm-orders-table" method="POST" action="">
				<input type="hidden" name="post_type" value="<?php echo FDM_ORDER_POST_TYPE; ?>" />
				<input type="hidden" name="page" value="fdm-orders">

				<div class="fdm-primary-controls clearfix">
					<div class="fdm-views">
						<?php $this->orders_table->views(); ?>
					</div>
					<?php $this->orders_table->advanced_filters(); ?>
				</div>

				<?php $this->orders_table->display(); ?>
			</form>
			<?php do_action( 'fdm_orders_table_btm' ); ?>
		</div>

		<?php
	}

	/**
	 * Print button for configuring columns
	 *
	 * @param string pos Position of this tablenav: top|btm
	 * @since 0.1
	 */
	public function print_columns_config_button( $pos ) {
		if ( $pos != 'top' ) {
			return;
		} /* @to-do: Let columns be selected for display
		?>

		<div class="alignleft actions rtb-actions">
			<a href="#" class="button rtb-columns-button">
				<span class="dashicons dashicons-admin-settings"></span>
				<?php esc_html_e( 'Columns', 'food-and-drink-menu' ); ?>
			</a>
		</div>

		<?php */
	}

	/**
	 * Print the modal containers
	 *
	 * New/edit orders, send email, configure columns, errors.
	 *
	 * @since 2.1.0
	 */
/*	public function print_modals() {

		global $fdm_controller;
		?>

		<!-- Five-Star Restaurant Menu add/edit booking modal --> 
		<div id="fdm-order-modal" class="fdm-admin-modal">
			<div class="fdm-order-form fdm-container">
				<form method="POST">
					<input type="hidden" name="action" value="admin_order_request">
					<input type="hidden" name="ID" value="">

					<?php
					/**
					 * The generated fields are wrapped in a div so we can
					 * replace its contents with an HTML blob passed back from
					 * an Ajax request. This way the field data and error
					 * messages are always populated from the same server-side
					 * code.
					 */
/*					?>
					<div id="fdm-order-form-fields">
						<?php echo $this->print_order_form_fields(); ?>
					</div>

					<button type="submit" class="button button-primary">
						<?php _e( 'Add Order', 'food-and-drink-menu' ); ?>
					</button>
					<a href="#" class="button" id="fdm-cancel-order-modal">
						<?php _e( 'Cancel', 'food-and-drink-menu' ); ?>
					</a>
					<div class="action-status">
						<span class="spinner loading"></span>
						<span class="dashicons dashicons-no-alt error"></span>
						<span class="dashicons dashicons-yes success"></span>
					</div>
				</form>
			</div>
		</div>

		<!-- Five-Star Restaurant Menu send email modal -->
		<div id="fdm-email-modal" class="fdm-admin-modal">
			<div class="fdm-email-form fdm-container">
				<form method="POST">
					<input type="hidden" name="action" value="admin_send_email">
					<input type="hidden" name="ID" value="">
					<input type="hidden" name="name" value="">
					<input type="hidden" name="email" value="">

					<fieldset>
						<legend><?php _e( 'Send Email', 'food-and-drink-menu' ); ?></legend>

						<div class="to">
							<label for="fdm-email-to"><?php _ex( 'To', 'Label next to the email address to which an email will be sent', 'food-and-drink-menu' ); ?></label>
							<span class="fdm-email-to"></span>
						</div>
						<div class="subject">
							<label for="fdm-email-subject"><?php _e( 'Subject', 'food-and-drink-menu' ); ?></label>
							<input type="text" name="fdm-email-subject" placeholder="<?php echo $fdm_controller->settings->get_setting( 'subject-admin-notice'); ?>">
						</div>
						<div class="message">
							<label for="rtb-email-message"><?php _e( 'Message', 'food-and-drink-menu' ); ?></label>
							<textarea name="rtb-email-message" id="rtb-email-message"></textarea>
						</div>
					</fieldset>

					<button type="submit" class="button button-primary">
						<?php _e( 'Send Email', 'food-and-drink-menu' ); ?>
					</button>
					<a href="#" class="button" id="rtb-cancel-email-modal">
						<?php _e( 'Cancel', 'food-and-drink-menu' ); ?>
					</a>
					<div class="action-status">
						<span class="spinner loading"></span>
						<span class="dashicons dashicons-no-alt error"></span>
						<span class="dashicons dashicons-yes success"></span>
					</div>
				</form>
			</div>
		</div>

		<!-- Restaurant Reservations column configuration modal -->
		<div id="rtb-column-modal" class="rtb-admin-modal">
			<div class="rtb-column-form rtb-container">
				<form method="POST">
					<input type="hidden" name="action" value="admin_column_config">

					<fieldset>
						<legend><?php esc_html_e( 'Columns', 'food-and-drink-menu' ); ?></legend>
						<ul>
							<?php
								$columns = $this->bookings_table->get_all_columns();
								$visible = $this->bookings_table->get_columns();
								foreach( $columns as $column => $label ) :
									// Don't allow these columns to be hidden
									if ( $column == 'cb' || $column == 'details' || $column == 'date' ) {
										continue;
									}
									?>
										<li>
											<label>
												<input type="checkbox" name="rtb-columns-config" value="<?php esc_attr_e( $column ); ?>"<?php if ( array_key_exists( $column, $visible ) ) : ?> checked<?php endif; ?>>
												<?php esc_html_e( $label ); ?>
											</label>
										</li>
									<?php
								endforeach;
							?>
						</ul>
					</fieldset>

					<button type="submit" class="button button-primary">
						<?php _e( 'Update', 'food-and-drink-menu' ); ?>
					</button>
					<a href="#" class="button" id="rtb-cancel-column-modal">
						<?php _e( 'Cancel', 'food-and-drink-menu' ); ?>
					</a>
					<div class="action-status">
						<span class="spinner loading"></span>
						<span class="dashicons dashicons-no-alt error"></span>
						<span class="dashicons dashicons-yes success"></span>
					</div>
				</form>
			</div>
		</div>

		<!-- Restaurant Reservations details modal -->
		<div id="rtb-details-modal" class="rtb-admin-modal">
			<div class="rtb-details-form rtb-container">
				<div class="rtb-details-data"></div>
				<a href="#" class="button" id="rtb-cancel-details-modal">
					<?php _e( 'Close', 'food-and-drink-menu' ); ?>
				</a>
			</div>
		</div>

		<!-- Restaurant Reservations ban email/ip modal -->
		<div id="rtb-ban-modal" class="rtb-admin-modal">
			<div class="rtb-ban-form rtb-container">
				<div class="rtb-ban-msg">
					<p class="intro">
						<?php
							printf(
								__( 'Ban future bookings from the email address %s or the IP address %s?', 'food-and-drink-menu' ),
								'<span id="rtb-ban-modal-email"></span>',
								'<span id="rtb-ban-modal-ip"></span>'
							);
						?>
					</p>
					<p>
						<?php
							esc_html_e( 'It is recommended to ban by email address instead of IP. Only ban by IP address to block a malicious user who is using different email addresses to avoid a previous ban.', 'food-and-drink-menu' );
						?>
					</p>
				</div>
				<button class="button button-primary" id="rtb-ban-modal-email-btn">Ban Email</button>
				<button class="button button-primary" id="rtb-ban-modal-ip-btn">Ban IP</button>
				<a href="#" id="rtb-cancel-ban-modal" class="button"><?php _e( 'Close', 'food-and-drink-menu' ); ?></a>
				<a class="button-link" href="<?php echo esc_url( admin_url( '/admin.php?page=rtb-settings' ) ); ?>" target="_blank">
					<?php esc_html_e( 'View all bans', 'food-and-drink-menu' ); ?>
				</a>
				<div class="action-status">
					<span class="spinner loading"></span>
					<span class="dashicons dashicons-no-alt error"></span>
					<span class="dashicons dashicons-yes success"></span>
				</div>
			</div>
		</div>

		<!-- Restaurant Reservations delete customer modal -->
		<div id="rtb-delete-modal" class="rtb-admin-modal">
			<div class="rtb-delete-form rtb-container">
				<div class="rtb-delete-msg">
					<?php
						printf(
							__( 'Delete all booking records related to email address %s? This action can not be undone.', 'food-and-drink-menu' ),
							'<span id="rtb-delete-modal-email"></span>'
						);
					?>
				</div>
				<div id="rtb-delete-status">
					<span class="rtb-delete-status-total">
						<span id="rtb-delete-status-progress" class="rtb-delete-status-progress"></span>
					</span>
					<div id="rtb-delete-status-deleted"></div>
				</div>
				<button class="button button-primary" id="rtb-delete-modal-btn">Delete Bookings</button>
				<button id="rtb-cancel-delete-modal" class="button"><?php _e( 'Close', 'food-and-drink-menu' ); ?></button>
				<div class="action-status">
					<span class="spinner loading"></span>
					<span class="dashicons dashicons-no-alt error"></span>
					<span class="dashicons dashicons-yes success"></span>
				</div>
			</div>
		</div>

		<!-- Restaurant Reservations error message modal -->
		<div id="rtb-error-modal" class="rtb-admin-modal">
			<div class="rtb-error rtb-container">
				<div class="rtb-error-msg"></div>
				<a href="#" class="button"><?php _e( 'Close', 'food-and-drink-menu' ); ?></a>
			</div>
		</div>

		<?php
	}
*/
	/**
	 * Retrieve order form fields used in the admin order modal. These
	 * fields are also passed back with ajax requests since they render error
	 * messages and populate fields with validated data.
	 * @since 2.1.0
	 */
/*	public function print_order_form_fields() {

		global $fdm_controller;

		// Add post status and notification fields to admin order form
		add_filter( 'fdm_order_form_fields', array( $this, 'add_admin_fields' ), 20, 2 );

		// Retrieve the form fields
		$fields = $fdm_controller->settings->get_order_form_fields( $fdm_controller->request );

		ob_start();
		?>

			<?php foreach( $fields as $fieldset => $contents ) : ?>
			<fieldset class="<?php echo $fieldset; ?>">
				<?php
					foreach( $contents['fields'] as $slug => $field ) {

						$args = empty( $field['callback_args'] ) ? null : $field['callback_args'];

						call_user_func( $field['callback'], $slug, $field['title'], $field['request_input'], $args );
					}
				?>
			</fieldset>
			<?php endforeach;

		// Remove the admin fields filter
		remove_filter( 'rtb_booking_form_fields', array( $this, 'add_admin_fields' ) );

		return ob_get_clean();
	}
*/
	/**
	 * Add the post status and notifications fields to the booking form fields
	 * @since 2.1.0
	 */
/*	public function add_admin_fields( $fields, $request ) {

		if ( !is_admin() || !current_user_can( 'manage_bookings' ) ) {
			return $fields;
		}

		global $rtb_controller;

		// Get all valid booking statuses
		$booking_statuses = array();
		foreach( $rtb_controller->cpts->booking_statuses as $status => $args ) {
			$booking_statuses[ $status ] = $args['label'];
		}

		$fields['admin'] = array(
			'fields'	=> array(
				'post-status'	=> array(
					'title'			=> __( 'Booking Status', 'food-and-drink-menu' ),
					'request_input'	=> empty( $request->post_status ) ? '' : $request->post_status,
					'callback'		=> 'rtb_print_form_select_field',
					'callback_args'	=> array(
						'options'		=> $booking_statuses,
					)
				),
				'notifications'	=> array(
					'title'			=> __( 'Send notifications', 'food-and-drink-menu' ),
					'request_input'	=> empty( $request->send_notifications ) ? false : $request->send_notifications,
					'callback'		=> array( $this, 'print_form_send_notifications_field' ),
					'callback_args'	=> array(
						'description'	=> array(
							'prompt'		=> __( 'Learn more', 'food-and-drink-menu' ),
							'text'			=> __( "When adding a booking or changing a booking's status with this form, no email notifications will be sent. Check this option if you want to send email notifications.", 'food-and-drink-menu' ),
						),
					),
				),
			),
		);

		return $fields;
	}
*/

	/**
	 * Print a field to toggle notifications when adding/editing a booking from
	 * the admin
	 * @since 2.1.0
	 */
/*	function print_form_send_notifications_field( $slug, $title, $value, $args ) {

		$slug = esc_attr( $slug );
		$title = esc_attr( $title );
		$value = (bool) $value;
		$description = empty( $args['description'] ) || empty( $args['description']['prompt'] ) || empty( $args['description']['text'] ) ? null : $args['description'];
		?>

		<div class="<?php echo $slug; ?>">
			<?php echo rtb_print_form_error( $slug ); ?>
			<label>
				<input type="checkbox" name="rtb-<?php echo esc_attr( $slug ); ?>" value="1"<?php checked( $value ); ?>>
				<?php echo $title; ?>
				<?php if ( !empty( $description ) ) : ?>
				<a href="#" class="rtb-description-prompt">
					<?php echo $description['prompt']; ?>
				</a>
				<?php endif; ?>
			</label>
			<?php if ( !empty( $description ) ) : ?>
			<div class="rtb-description">
				<?php echo $description['text']; ?>
			</div>
			<?php endif; ?>
		</div>

		<?php
	}
*/
	/**
	 * Handle ajax requests from the admin bookings area from logged out users
	 * @since 2.1.0
	 */
	public function nopriv_ajax() {

		wp_send_json_error(
			array(
				'error' => 'loggedout',
				'msg' => sprintf( __( 'You have been logged out. Please %slogin again%s.', 'food-and-drink-menu' ), '<a href="' . wp_login_url( admin_url( 'admin.php?page=fdm-orders&status=received' ) ) . '">', '</a>' ),
			)
		);
	}
}
} // endif;
