jQuery(document).ready(function() {

	if ( jQuery( '#fdm-ordering-sidescreen-items > div' ).length ) {

		jQuery( '.fdm-ordering-sidescreen' ).removeClass( 'fdm-hidden' );
		jQuery( '.fdm-ordering-sidescreen' ).css( 'right', '0px' );
		fdm_update_cart_total();
	}

	jQuery( '#fdm-ordering-sidescreen-close, .fdm-continue-shopping-button' ).on( 'click', function() {
		jQuery( '.fdm-ordering-sidescreen' ).animate( { 'right':'-400px' }, {duration:750} ).promise().done(function () {
			jQuery( '.fdm-ordering-sidescreen' ).addClass( 'fdm-hidden' );
			jQuery( '#fdm-ordering-sidescreen-tab' ).removeClass( 'fdm-hidden' );
		});
	});

	jQuery( '#fdm-ordering-sidescreen-tab' ).on( 'click', function() {
		jQuery( '#fdm-ordering-sidescreen-tab' ).addClass( 'fdm-hidden' );
		jQuery( '.fdm-ordering-sidescreen' ).removeClass( 'fdm-hidden' );
		jQuery( '.fdm-ordering-sidescreen' ).animate( { 'right':'0px' }, {duration:750} );
	});

	jQuery( '.fdm-add-to-cart-button' ).on( 'click', function() {

		var post_id = jQuery( this ).data( 'postid' );

		fdm_add_to_cart( post_id );
	});

	jQuery( '.fdm-options-add-to-cart-button' ).on( 'click', function() {

		var post_id = jQuery( this ).data( 'postid' );

		fdm_display_order_details_popup( post_id );
	});

	jQuery( '#fdm-ordering-popup-submit button' ).on( 'click', function() {
		var post_id = jQuery( this ).data( 'post_id' );

		fdm_add_to_cart( post_id );
	});

	jQuery(document).on('change', '.fdm-item-quantity-wrapper input', function(ev) {
		fdm_update_cart_quantities();
	});

	jQuery( '.fdm-clear-cart-button' ).on( 'click', function() {

		jQuery( '#fdm-ordering-sidescreen-items' ).html( '' );
		jQuery( '.fdm-ordering-sidescreen' ).animate( { 'right':'-400px' }, {duration:750} ).promise().done(function () {
			jQuery( '.fdm-ordering-sidescreen' ).addClass( 'fdm-hidden' );
			jQuery( '#fdm-ordering-sidescreen-tab' ).addClass( 'fdm-hidden' );
		});

		fdm_update_cart_total();

		var data = '&action=fdm_clear_cart';
		jQuery.post( ajaxurl, data, function( response ) {});
	});

	jQuery( '#fdm-order-submit-button' ).on( 'click', function() {

		// disable the submit button to prevent repeated clicks
		jQuery( this ).prop( "disabled", true );

		var permalink = jQuery( this ).data( 'permalink' );

		var name = jQuery( 'input[name="fdm_ordering_name"]' ).val();
		var email = jQuery( 'input[name="fdm_ordering_email"]' ).val();
		var phone = jQuery( 'input[name="fdm_ordering_phone"]' ).val();
		var note = jQuery( 'textarea[name="fdm_ordering_note"]' ).val();

		var custom_fields = {};

		jQuery( '.fdm-ordering-custom-fields' ).find( 'input:not([type="checkbox"]), textarea, select' ).each( function() {
			custom_fields[ this.name ] = jQuery( this ).val();
		} );
		jQuery( '.fdm-ordering-custom-fields' ).find( 'input:checked' ).each( function() {
			let index = jQuery( this ).data( 'slug' );
			custom_fields[ index ] = Array.isArray( custom_fields[ index ] ) ? custom_fields[ index ] : [];
			custom_fields[ index ].push( jQuery( this ).val() );
		}).get();

		var valid = true;

		// make sure the name field validates
		if ( ! jQuery( 'input[name="fdm_ordering_name"]' )[0].checkValidity() ){

			jQuery( '#fdm-order-submit-button' ).before( '<p class="fdm-message">Name field is required.</p>' ).parent().find( '.fdm-message' ).delay( 6000 ).fadeOut();

			valid = false;
		}

		// make sure the email field validates
		if ( ! jQuery( 'input[name="fdm_ordering_email"]' )[0].checkValidity() ){

			jQuery( '#fdm-order-submit-button' ).before( '<p class="fdm-message">Email field is required and must be valid.</p>' ).parent().find( '.fdm-message' ).delay( 6000 ).fadeOut();

			valid = false;
		}

		// make sure the phone field validates
		if ( ! jQuery( 'input[name="fdm_ordering_phone"]' )[0].checkValidity() ){

			jQuery( '#fdm-order-submit-button' ).before( '<p class="fdm-message">Phone field is required and must be valid.</p>' ).parent().find( '.fdm-message' ).delay( 6000 ).fadeOut();

			valid = false;
		}

		// make sure the order minimum is met
		if ( fdm_ordering_data.minimum_order && parseFloat( jQuery( '#fdm-ordering-sidescreen-total-value' ).html() ) < fdm_ordering_data.minimum_order ) {

			jQuery( '#fdm-order-submit-button' ).before( '<p class="fdm-message">There is a minimum of ' + fdm_ordering_data.price_prefix + fdm_ordering_data.minimum_order + fdm_ordering_data.price_suffix + ' to place an order.</p>' ).parent().find( '.fdm-message' ).delay( 6000 ).fadeOut();

			valid = false;
		}

		// If any fields fail to validate, don't submit
		if ( ! valid ) {

			jQuery( '#fdm-order-submit-button' ).prop( "disabled", false );

			return false;
		}

		var data = jQuery.param({
			permalink: permalink,
			name: name,
			email: email,
			phone: phone,
			note: note,
			custom_fields: custom_fields,
			action: 'fdm_submit_order'
		});
		jQuery.post( ajaxurl, data, function( response ) {
			
			if ( ! response.success ) { 
				jQuery( '#fdm-order-submit-button' ).prop( "disabled", false );

				jQuery( '#fdm-order-submit-button' ).before( '<p class="fdm-message">Order could not be processed. Please contact the site administrator.</p>' );

				return;
			}

			var url = new URL( window.location.href );

			url.searchParams.append( 'order_success', true );

			window.location = url;
		});
	});

	setInterval( fdm_update_order_status, 10000 );

	fdm_handle_delete_clicks();

	jQuery(function(){
		jQuery(window).resize(function(){
			var quantity_area_height = jQuery('#fdm-ordering-sidescreen-quantity').outerHeight();
			var clear_button_height = jQuery('.fdm-clear-cart-button').outerHeight();
			var clear_button_margin = ( ( quantity_area_height - clear_button_height ) / 2 ) - 1;
			jQuery('.fdm-clear-cart-button').css('top', clear_button_margin+'px');
			var sidescreen_heading_height = jQuery('#fdm-ordering-sidescreen-header').outerHeight();
			var sidescreen_heading_margin = ( sidescreen_heading_height - 30 ) / 2;
			jQuery('#fdm-ordering-sidescreen-close').css('top', sidescreen_heading_margin+'px');
		}).resize();

		jQuery('.fdm-menu-image .fdm-item-panel').each(function(){
			var thisPanel = jQuery(this);
			var thisPanelWidth = thisPanel.width();
			var thisAddToCartWidth = thisPanel.find('.fdm-add-to-cart-button').outerWidth();
			var thisOptionsAddToCartWidth = thisPanel.find('.fdm-options-add-to-cart-button').outerWidth();
			var thisAddToCartMargin = ( thisPanelWidth - thisAddToCartWidth ) / 2;
			var thisOptionsAddToCartMargin = ( thisPanelWidth - thisOptionsAddToCartWidth ) / 2;
			thisPanel.find('.fdm-add-to-cart-button').css('margin-left', thisAddToCartMargin+'px');
			thisPanel.find('.fdm-options-add-to-cart-button').css('margin-left', thisOptionsAddToCartMargin+'px');
		});
	});

	jQuery( '.fdm-ordering-popup-close, .fdm-ordering-popup-background' ).on( 'click', function() {
		
		jQuery( '.fdm-ordering-popup-background' ).addClass( 'fdm-hidden' );
		jQuery( '.fdm-ordering-popup' ).addClass( 'fdm-hidden' );

		jQuery( '#fdm-ordering-popup-options > div' ).remove();
	});

	jQuery( '.fdm-payment-type-toggle' ).on( 'click', function() {

		if ( jQuery( this ).val() == 'pay-in-store' ) {
			jQuery( '#fdm-order-submit' ).removeClass( 'fdm-hidden' );
			jQuery( '#fdm-order-payment-form-div' ).addClass( 'fdm-hidden' );
		}
		else {
			jQuery( '#fdm-order-submit' ).addClass( 'fdm-hidden' );
			jQuery( '#fdm-order-payment-form-div' ).removeClass( 'fdm-hidden' );
		}
	});

});

function fdm_display_order_details_popup( post_id ) {

	jQuery( '.fdm-ordering-popup-background' ).removeClass( 'fdm-hidden' );
	jQuery( '.fdm-ordering-popup' ).removeClass( 'fdm-hidden' );

	jQuery( '#fdm-ordering-popup-submit button' ).data( 'post_id', post_id );

	var options = jQuery( '.fdm-options-add-to-cart-button[data-postid="' + post_id + '"]' ).data( 'options' ); 
	var prices = jQuery( '.fdm-options-add-to-cart-button[data-postid="' + post_id + '"]' ).data( 'prices' );

	Object.keys( options ).forEach(function( index ) {
		var el = options[index];
		var checked = ( el.default != null && el.default != '' ) ? 'checked' : '';

		var html = '<div class="fdm-ordering-popup-option">';
		html += '<input type="checkbox" value="' + index + '" ' + checked + '/> ';
		html += el.name;
		if ( el.cost != null && el.cost != 0 ) { html += ' ( ' + fdm_ordering_data.price_prefix + el.cost + fdm_ordering_data.price_suffix + ' )'; }
		html += '</div>';

		jQuery( '#fdm-ordering-popup-options' ).append( html );
	});

	if ( jQuery.isArray( prices ) && fdm_ordering_popup_data.additional_prices ) {

		var html = '<div class="fdm-ordering-popup-option">'+fdm_ordering_popup_data.price_text+' <select>';
		jQuery( prices ).each( function( index, element ) {
	
			html += '<option value="' + element + '">' + element + '</option>';
		});
		html += '</select></div>';
		jQuery( '#fdm-ordering-popup-options' ).append( html );
	}

}

function fdm_add_to_cart( post_id ) {

	jQuery( '.fdm-ordering-sidescreen' ).removeClass( 'fdm-hidden' );
	jQuery( '#fdm-ordering-sidescreen-tab' ).addClass( 'fdm-hidden' );
	jQuery( '.fdm-ordering-sidescreen' ).animate( {'right':'0px'}, {duration:750} );

	var quantity_area_height1 = jQuery('#fdm-ordering-sidescreen-quantity').outerHeight();
	var clear_button_height1 = jQuery('.fdm-clear-cart-button').outerHeight();
	var clear_button_margin1 = ( ( quantity_area_height1 - clear_button_height1 ) / 2 ) - 1;
	jQuery('.fdm-clear-cart-button').css('top', clear_button_margin1+'px');

	if( ! jQuery('.fdm-ordering-sidescreen').hasClass('fdm-hidden') ) {
		var sidescreen_heading_height1 = jQuery('#fdm-ordering-sidescreen-header').outerHeight();
		var sidescreen_heading_margin1 = ( sidescreen_heading_height1 - 30 ) / 2;
		jQuery('#fdm-ordering-sidescreen-close').css('top', sidescreen_heading_margin1+'px');
	}

	var item_identifier = fdm_create_unique_item_identifier( post_id ); 
	var selected_options = [];
	var selected_price = '';
	var note = jQuery( '#fdm-ordering-popup-note textarea' ).val();

	jQuery( '#fdm-ordering-popup-options .fdm-ordering-popup-option input[type="checkbox"]:checked' ).each( function() {
		selected_options.push( jQuery( this ).val() );
	});

	if ( jQuery( '#fdm-ordering-popup-options .fdm-ordering-popup-option select' ).length ) {
		selected_price = jQuery( '#fdm-ordering-popup-options .fdm-ordering-popup-option select' ).val();
	}

	jQuery( '.fdm-ordering-popup-background' ).addClass( 'fdm-hidden' );
	jQuery( '.fdm-ordering-popup' ).addClass( 'fdm-hidden' );

	jQuery( '#fdm-ordering-popup-options > div' ).remove();

	jQuery( '#fdm-ordering-popup-note textarea' ).val( '' );
	
	var data = 'post_id=' + post_id + '&item_identifier=' + item_identifier + '&selected_options=' + selected_options + '&selected_price=' + selected_price + '&note=' + note + '&action=fdm_add_to_cart';
	jQuery.post( ajaxurl, data, function( response ) {
		if ( ! response ) { return; }
 
		jQuery( '#fdm-ordering-sidescreen-items' ).append( response ); 

		fdm_update_cart_total();

		fdm_handle_delete_clicks();
	});
}

function fdm_update_order_status() {
	var order_id = jQuery( '.fdm-ordering-progress' ).data( 'order_id' );

	if ( ! order_id && ! isNaN( order_id ) ) { return; }

	var data = 'order_id=' + order_id + '&action=fdm_update_order_progress';
	jQuery.post( ajaxurl, data, function( response ) {
		if ( ! response.success ) { return; }

		var status = response.data.status;
		var value  = response.data.value;
 
		jQuery( '.fdm-order-progress-current-status' ).removeClass( 'fdm-order-progress-current-status' );

		jQuery( '.fdm-order-progress-status-labels[data-status="' + status + '"]' ).addClass( 'fdm-order-progress-current-status' );

		jQuery( '.fdm-order-progress-status' ).attr( 'data-value', value );
	});
}

function fdm_handle_delete_clicks() {
	jQuery( '.fdm-cart-delete-item' ).off( 'click' );
	jQuery( '.fdm-cart-delete-item' ).on( 'click', function() {
		var item_identifier = jQuery( this ).data( 'itemidentifier' );

		jQuery( this ).parent().parent().parent().remove();

		fdm_update_cart_total();

		fdm_delete_from_cart( item_identifier );
	} );
}

function fdm_delete_from_cart( item_identifier ) {

	var data = 'item_identifier=' + item_identifier + '&action=fdm_delete_from_cart';
	jQuery.post( ajaxurl, data, function( response ) {

	});
}

function fdm_update_cart_total() {

	var total_price = 0;

	jQuery( '.fdm-cart-item-price' ).each( function() {
		let qty = jQuery(this)
			.parents( '.fdm-cart-menu-item' )
			.find( '.fdm-item-quantity-wrapper input' )
			.val();
		qty = parseInt( qty );
		qty = 'number' == typeof qty ? (qty > 0 && qty < 1001 ? qty : 1) : 1;
		total_price += parseFloat( jQuery( this ).data( 'price' ) ) * qty;
	});

	jQuery( '#fdm-ordering-sidescreen-total-value' ).html( ( Math.round( total_price * 100 ) / 100 ).toFixed( 2 ) );
	jQuery( '#fdm-ordering-sidescreen-paypal-total' ).val( ( Math.round( total_price * 100 ) / 100 ).toFixed( 2 ) );
	jQuery( '#fdm-ordering-sidescreen-stripe-total' ).val(  Math.round( total_price * 100 ).toFixed( 0 ) );

	var number_of_items = 0;
	jQuery( '.fdm-item-quantity-wrapper input' ).each( function() {
		let qty = parseInt( jQuery(this).val() );
		qty = 'number' == typeof qty ? (qty > 0 && qty < 1001 ? qty : 1) : 1;
		number_of_items += qty;
	});
	jQuery( '#fdm-ordering-sidescreen-quantity-number' ).html( number_of_items );
	jQuery( '#fdm-ordering-sidescreen-tab-count' ).html( number_of_items );
	if ( number_of_items < 2 ) {
		var quantity_text = fdm_ordering_data.singular_text;
	}
	else {
		var quantity_text = fdm_ordering_data.plural_text;
	}
	jQuery( '#fdm-ordering-sidescreen-quantity-text' ).html( quantity_text );

}

function fdm_update_cart_quantities() {
	var data = {};
	data['action'] = 'fdm_update_cart_qty';
	data['quantity'] = {};

	jQuery( '.fdm-cart-menu-item' ).each(function () {
		data['quantity'][jQuery(this).data( 'item_identifier' )] = jQuery(this).find('.fdm-item-quantity-wrapper input').val();
	});

	jQuery.post( ajaxurl, data, function( response ) {
		fdm_update_cart_total();
	});
}

function fdm_create_unique_item_identifier ( post_id ) {

	var item_identifier = post_id + '_' + fdm_return_random_string();

	while ( jQuery( '#fdm-cart-item-' + item_identifier).length ) {
		item_identifier = post_id + '_' + fdm_return_random_string();
	}

	return item_identifier;
}

function fdm_return_random_string( length = 4 ) {
   
   var result           = '';
   var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
   var charactersLength = characters.length;

   for ( var i = 0; i < length; i++ ) {
      result += characters.charAt(Math.floor(Math.random() * charactersLength));
   }

   return result;
}

