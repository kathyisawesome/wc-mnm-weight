/* global wc_mnm_params */

( function( $ ) {

	/**
	 * Main container object.
	 */
	function WC_MNM_Weight( container ) {

		var self       = this;
		this.container = container;
		this.$form     = container.$mnm_form;

		/**
		 * Init.
		 */
		this.initialize = function() {
			if( 'weight' === container.$mnm_cart.data( 'validation_mode' ) ) {
				this.bind_event_handlers();		
			}

		};

		/**
		 * Container-Level Event Handlers.
		 */
		this.bind_event_handlers = function() {
			this.$form.on( 'wc-mnm-container-quantities-updated', this.update_totals );
			this.$form.on( 'wc-mnm-validation',                   this.validate );
		};

		/**
		 * Get Max Weight.
		 */
		this.get_max_container_weight = function() {
			return 'undefined' !== typeof container.$mnm_cart.data( 'max_weight' )   ? wc_mnm_number_round( container.$mnm_cart.data( 'max_weight' ), wc_mnm_params.rounding_precision )   : '';
		}

		/**
		 * Get Min Weight.
		 */
		this.get_min_container_weight = function() {
			return 'undefined' !== typeof container.$mnm_cart.data( 'min_weight' )   ? wc_mnm_number_round( container.$mnm_cart.data( 'min_weight' ), wc_mnm_params.rounding_precision )   : 0;
		}

		/**
		 * Get Current Weight.
		 */
		this.get_container_weight = function() {
			return 'undefined' !== typeof container.$mnm_cart.data( 'total_weight' ) ? wc_mnm_number_round( container.$mnm_cart.data( 'total_weight' ), wc_mnm_params.rounding_precision ) : 0;
		}

		/**
		 * Update Totals.
		 */
		this.update_totals = function( event, container ) {
			var total_weight  = 0;

			$.each( container.child_items, function( index, child_item ) {

				var item_weight = child_item.$self.find( '.product-weight' ).data( 'weight' );

				if( 'undefined' === typeof item_weight ) { 
					item_weight = 0;
				}

				item_weight = parseFloat( item_weight );

				total_weight += child_item.get_quantity() * item_weight;
			} );

			// Update the data attribute.
			container.$mnm_cart.data( 'total_weight', total_weight );

			// Update the UI with a formatted total.
			var max_weight      = 'undefined' !== typeof container.$mnm_cart.data( 'max_weight' )  ? wc_mnm_number_round( container.$mnm_cart.data( 'max_weight' ) ) : 0;
			var formatted_total = '';

			if (max_weight) {
				formatted_total = wc_mnm_params.i18n_weight_format_counter;
				formatted_total = formatted_total.replace('%s', total_weight).replace('%max', self.get_formatted_weight( max_weight ));
			} else {
				formatted_total = self.get_formatted_weight( total_weight );
			}
	
			container.$mnm_cart.data( 'formatted_total_weight', formatted_total );

		};

		/**
		 * Validate Weight.
		 */
		this.validate = function( event, container ) {

			if ( 'weight' === container.validation_mode ) {

				// Reset validation errors.
				container.reset_messages();

				var precision      = wc_mnm_params.rounding_precision;
				var total_weight   = self.get_container_weight();
				var min_weight     = self.get_min_container_weight();
				var max_weight     = self.get_max_container_weight();
				var status_message = self.selected_weight_message(total_weight); // "Selected 99kg".
				var error_message  = '';
				var valid_message  = '';

				// Validation.
				switch (true) {
					// Validate a fixed weight container.
					case min_weight > 0 && min_weight === max_weight:

						valid_message = wc_mnm_params.i18n_valid_fixed_message;

						if (total_weight !== min_weight) {
							error_message = wc_mnm_params.i18n_weight_qty_error.replace('%s', self.get_formatted_weight( min_weight ) );
							container.add_message(error_message.replace('%v', status_message), 'error');
						}

						break;

					// Validate that a container has less than the maximum weight.
					case max_weight > 0 && min_weight === 0:

						valid_message = wc_mnm_params.i18n_valid_max_message;

						if (total_weight > max_weight) {
							error_message = wc_mnm_params.i18n_weight_max_weight_error.replace( '%max', self.get_formatted_weight( max_weight ) );
							container.add_message(error_message.replace('%v', status_message), 'error');
						}

						break;

					// Validate a range.
					case max_weight > 0 && min_weight > 0:

						valid_message = wc_mnm_params.i18n_valid_range_message;

						if (total_weight < min_weight || total_weight > max_weight) {
							error_message = wc_mnm_params.i18n_weight_min_max_qty_error.replace( '%max', self.get_formatted_weight( max_weight ) ).replace( '%min', self.get_formatted_weight( min_weight ) );
							container.add_message(error_message.replace('%v', status_message), 'error');
						}
						break;

					// Validate that a container has minimum number of items.
					case min_weight >= 0:

						valid_message = wc_mnm_params.i18n_valid_min_message;

						if (total_weight < min_weight) {
							error_message = wc_mnm_params.i18n_weight_min_weight_error.replace( '%min', self.get_formatted_weight( min_weight ) );
							container.add_message(error_message.replace('%v', status_message), 'error');
						}

						break;

				}

				// Add selected qty status message if there are no error messages.
				if (container.passes_validation() && valid_message !== '') {
					valid_message = valid_message.replace('%max', max_weight).replace('%min', min_weight);
					container.add_message(valid_message.replace('%v', status_message));
				}

			}

		};

		/**
		 * Build the weight html component.
		 */
		this.get_formatted_weight = function( weight ) {
			return wc_mnm_params.i18n_weight_format.replace( '%w', wc_mnm_number_format( weight ) ).replace( '%u', wc_mnm_params.weight_unit );
		};


		/**
		 * Selected total message builder.
		 */
		this.selected_weight_message = function( weight ) {
			return wc_mnm_params.i18n_weight_qty_message.replace( '%s', self.get_formatted_weight( weight ) );
		};

	} // End WC_MNM_Weight.

	/*-----------------------------------------------------------------*/
	/*  Initialization.                                                */
	/*-----------------------------------------------------------------*/

	$( 'body' ).on( 'wc-mnm-initializing', function( e, container ) {
		var weight = new WC_MNM_Weight( container );
		weight.initialize();
	});

} ) ( jQuery );