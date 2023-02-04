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

			// Add the child item handlers.
			if ( container.child_items.length ) {

				container.child_items.forEach(function(child_item) {
					child_item.$self.on( 'wc-mnm-child-item-valid-quantity', self.validate_child );
				});
				  
			}

		};

		/**
		 * Get Max Weight.
		 */
		this.get_max_container_weight = function() {
			return 'undefined' !== typeof container.$mnm_cart.data( 'max_weight' )   ? wc_mnm_number_round( container.$mnm_cart.data( 'max_weight' ), wc_mnm_params.rounding_precision )   : '';
		};

		/**
		 * Get Min Weight.
		 */
		this.get_min_container_weight = function() {
			return 'undefined' !== typeof container.$mnm_cart.data( 'min_weight' )   ? wc_mnm_number_round( container.$mnm_cart.data( 'min_weight' ), wc_mnm_params.rounding_precision )   : 0;
		};

		/**
		 * Get Current Weight.
		 */
		this.get_container_weight = function() {
			return 'undefined' !== typeof container.$mnm_cart.data( 'total_weight' ) ? wc_mnm_number_round( container.$mnm_cart.data( 'total_weight' ), wc_mnm_params.rounding_precision ) : 0;
		};

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
		 * Validate child item quantity.
		 */
		this.validate_child = function( event, child_item, new_qty, current_qty, prev_qty ) {

			// Get this item's weight.
			var item_weight = child_item.$self.find( '.product-weight' ).data( 'weight' );

			if( 'undefined' === typeof item_weight ) { 
				item_weight = 0;
			}

			item_weight          = parseFloat( item_weight );

			var prev_weight      = prev_qty * item_weight;
			var current_weight   = current_qty * item_weight;
		
			// Restrict to max limits.
			var max              = parseFloat(child_item.$mnm_item_qty.attr('max'));
			var max_weight       = self.get_max_container_weight();
			var container_weight = self.get_container_weight();
			var potential_weight = container_weight + (current_weight - prev_weight);
			var remaining_weight = max_weight - container_weight;

			// Prevent over-filling container.
			if ( max_weight > 0 && potential_weight > max_weight ) {

				// Clear existing errors.
				child_item.reset_messages();

				// Space left to fill if item will fit in remaining space.
				if ( container_weight < max_weight && item_weight < remaining_weight ) {
	
					new_qty = Math.min( Math.floor(remaining_weight/item_weight), max);

					// No space left in container, or item is too heavy to fit in remaining space, reset to previous.
				} else {
					new_qty = prev_qty;
				}

				// If the new quantity is the individual max, re-use the item-specific error message.
				if (max === new_qty) {
					child_item.add_message(wc_mnm_params.i18n_child_item_max_qty_message.replace('%d', max));
				} else {
					child_item.add_message(wc_mnm_params.i18n_child_item_max_container_qty_message.replace('%d', self.get_formatted_weight(max_weight)));
				}
					
			}

			return new_qty;

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