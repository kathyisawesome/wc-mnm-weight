( function( $ ) {

	/**
	 * Main container object.
	 */
	function WC_MNM_Weight( container ) {

		var self       = this;
		this.container = container;

		/**
		 * Init.
		 */

		this.initialize = function() {
			
			if( container.$mnm_cart.data( 'max_weight' ) !== 'undefined' ) {
				this.bind_event_handlers();
				this.add_error_div();			
			}

		};

		/**
		 * Add error div.
		 */
		this.add_error_div = function( container ) {
			// Add a div to hold the error message.
			var $error = this.container.$mnm_cart.find( '.wc-mnm-weight-counter' );

			if ( ! $error.length ){
				$('<div class="wc-mnm-weight-counter"></div>').insertBefore( this.container.$mnm_price );
			}
		};

		/**
		 * Container-Level Event Handlers.
		 */
		this.bind_event_handlers = function() {
			$( '.mnm_form' ).on( 'wc-mnm-updated-totals', this.update_totals );
			$( '.mnm_form' ).on( 'wc-mnm-validation',     this.validate );
		};

		/**
		 * Update Totals.
		 */
		this.update_totals = function( event, container ) {
			var total_weight  = 0;

			$.each( container.child_items, function( index, child_item ) {

				item_weight = child_item.$self.find( '.product-weight' ).data( 'weight' );

				if( 'undefined' === typeof item_weight ) { 
					item_weight = 0;
				}

				item_weight = parseFloat( item_weight );

				total_weight += child_item.get_quantity() * item_weight;
			} );

			container.$mnm_cart.data( 'total_weight', total_weight );
			container.$mnm_cart.find( '.wc-mnm-weight-counter' ).html( self.get_weight_html( true ) );
		};

		/**
		 * Validate Weight.
		 */
		this.validate = function( event, container ) {

			container.reset_messages();

			var total_weight = container.$mnm_cart.data( 'total_weight' );
			if( typeof total_weight === 'undefined' ){
				total_weight = 0;
			}
			var min_weight = container.$mnm_cart.data( 'min_weight' );
			var max_weight = container.$mnm_cart.data( 'max_weight' );

			// Check if the total weight is within the required threshold, if not error.
			if( total_weight > max_weight ) {
				var message = wc_mnm_weight_params.i18n_max_weight_error.replace( '%max', self.get_formatted_weight( max_weight ) );
				message = message.replace( '%difference', self.get_formatted_weight( total_weight - max_weight ) );
				container.add_message( message, 'error' );
			} else if (total_weight < min_weight) {
				var message = wc_mnm_weight_params.i18n_min_weight_error.replace( '%difference', self.get_formatted_weight( min_weight - total_weight ) );
				container.add_message( message, 'error' );
			}
		};

		/**
		 * Build the weight html component.
		 */
		this.get_formatted_weight = function( weight ) {
			var unit  = self.container.$mnm_cart.data( 'weight_unit' );
			return wc_mnm_weight_params.i18n_weight_format.replace( '%t', '' ).replace( '%w', wc_mnm_number_format( weight ) ).replace( '%u', unit );
		};


		/**
		 * Build the weight html component.
		 */
		this.get_weight_html = function( show_total ) {

			if( typeof( show_total ) === 'undefined' ) {
				show_total = false;
			}

			var	weight_html  = '',
				total_string = show_total ? '<span class="total">' + wc_mnm_weight_params.i18n_total + '</span>' : '',
				formatted_weight = self.get_formatted_weight( container.$mnm_cart.data( 'total_weight' ) );

			weight_html = wc_mnm_weight_params.i18n_weight_format.replace( '%t', total_string ).replace( '%w', formatted_weight ).replace( '%u', '' );
			weight_html = '<p class="weight">' + weight_html + '</p>';

			return weight_html;
		};

	} // End WC_MNM_Weight.

	/*-----------------------------------------------------------------*/
	/*  Initialization.                                                */
	/*-----------------------------------------------------------------*/

	$( '.mnm_form' ).on( 'wc-mnm-initializing', function( e, container ) {
		var weight = new WC_MNM_Weight( container );
		weight.initialize();
	});

} ) ( jQuery );