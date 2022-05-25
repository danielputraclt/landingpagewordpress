if ( fdm_stripe_payment.stripe_mode == 'test' ) { Stripe.setPublishableKey(fdm_stripe_payment.test_publishable_key); }
else { Stripe.setPublishableKey(fdm_stripe_payment.live_publishable_key);  }

function stripeResponseHandler(status, response) {
	if (response.error) {
		// show errors returned by Stripe
		jQuery(".payment-errors").html(response.error.message);
		// re-enable the submit button
		jQuery('#stripe-submit').attr("disabled", false);
	}
	else {
		var form$ = jQuery("#stripe-payment-form");
		// token contains id, last4, and card type
		var token = response['id'];
		// insert the token into the form so it gets submitted to the server
		form$.append("<input type='hidden' name='stripeToken' value='" + token + "'/>");

		var permalink = jQuery( '#stripe-submit' ).data( 'permalink' );

		var name = jQuery( 'input[name="fdm_ordering_name"]' ).val();
		var email = jQuery( 'input[name="fdm_ordering_email"]' ).val();
		var phone = jQuery( 'input[name="fdm_ordering_phone"]' ).val();
		var note = jQuery( 'textarea[name="fdm_ordering_note"]' ).val();

		var custom_fields = {};
		jQuery( '.fdm-ordering-custom-fields' ).find( 'input, textarea, select' ).each( function() {
			custom_fields[ this.name ] = jQuery( this ).val(); 
		});
		jQuery( '.fdm-ordering-custom-fields' ).find( 'input:checked' ).each( function() {
			let index = jQuery( this ).data( 'slug' );
			custom_fields[ index ] = Array.isArray( custom_fields[ index ] ) ? custom_fields[ index ] : [];
			custom_fields[ index ].push( jQuery( this ).val() );
		}).get();

		var data = jQuery.param({
			permalink: permalink,
			name: name,
			email: email,
			phone: phone,
			note: note,
			custom_fields: custom_fields,
			post_status: 'draft',
			action: 'fdm_submit_order'
		});
		jQuery.post( ajaxurl, data, function( response ) {

			if ( ! response.success ) {
				jQuery( '#fdm-order-submit-button' ).before( '<p>Order could not be processed. Please contact the site administrator.' );

				return;
			}

			form$.append("<input type='hidden' name='order_id' value='" + response.data.order_id + "'/>");

			// submit form
			form$.get(0).submit();
		});
	}
}

jQuery(document).ready(function($) {
	$("#stripe-payment-form").submit( function( event ) {

		// check for blank required fields
		if ( 
				(jQuery( 'input[name="fdm_ordering_name"]' ).is( '[required]') && jQuery( 'input[name="fdm_ordering_name"]' ).val() == '') || 
				(jQuery( 'input[name="fdm_ordering_email"]' ).is( '[required]') && jQuery( 'input[name="fdm_ordering_email"]' ).val() == '') || 
				(jQuery( 'input[name="fdm_ordering_phone"]' ).is( '[required]') && jQuery( 'input[name="fdm_ordering_phone"]' ).val() == '') 
			) {

			jQuery( '<p class="fdm-message">Please make sure all required fields have been filled in before submitting</p>' ).insertBefore( this ).delay( 6000 ).queue( function() { jQuery( '.fdm-message').remove(); } );

			return false;
		}

		if ( fdm_ordering_data.minimum_order && parseFloat( jQuery( '#fdm-ordering-sidescreen-total-value' ).html() ) < fdm_ordering_data.minimum_order ) {

			jQuery( '<p class="fdm-message">There is a minimum of ' + fdm_ordering_data.price_prefix + fdm_ordering_data.minimum_order + fdm_ordering_data.price_suffix + ' to place an order.</p>' ).insertBefore( this ).delay( 6000 ).queue( function() { jQuery( '.fdm-message').remove(); } );

			return false;
		}

		// disable the submit button to prevent repeated clicks
		$('#stripe-submit').attr("disabled", "disabled");

		// send the card details to Stripe
		Stripe.createToken({
			number: $('input[data-stripe="card_number"]').val(),
			cvc: $('input[data-stripe="card_cvc"]').val(),
			exp_month: $('input[data-stripe="exp_month"]').val(),
			exp_year: $('input[data-stripe="exp_year"]').val(),
			currency: $('input[data-stripe="currency"]').val()
		}, stripeResponseHandler);

		// prevent the form from submitting with the default action
		return false;
	});
});