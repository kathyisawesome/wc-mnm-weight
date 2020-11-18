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
				this.add_counter_div();	
				this.bind_event_handlers();		
			}

		};

		/**
		 * Add counter div.
		 */
		this.add_counter_div = function() {
			if ( ! this.container.$mnm_cart.find( '.wc-mnm-weight-counter' ).length ){
				$('<div class="wc-mnm-weight-counter"></div>').insertBefore( this.container.$mnm_price );
			}

			this.$counter  = this.container.$mnm_cart.find( '.wc-mnm-weight-counter' );
		};

		/**
		 * Container-Level Event Handlers.
		 */
		this.bind_event_handlers = function() {
			this.$form.on( 'wc-mnm-container-quantities-updated', this.update_totals );
			this.$form.on( 'wc-mnm-validation',     this.validate );
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

		};

		/**
		 * Validate Weight.
		 */
		this.validate = function( event, container ) {

			container.reset_messages();

			var total_weight = container.$mnm_cart.data( 'total_weight' );

			if( typeof total_weight === 'undefined' ) {
				total_weight = 0;
			}

			var min_weight = container.$mnm_cart.data( 'min_weight' );
			var max_weight = container.$mnm_cart.data( 'max_weight' );
			var error_message = '';

			// Validation.
			if( min_weight === max_weight && total_weight !== min_weight ) {
				error_message = wc_mnm_weight_params.i18n_qty_error.replace( '%s', self.get_formatted_weight( min_weight ) );
			}
			// Validate a range.
			else if( max_weight > 0 && min_weight > 0 && ( total_weight < min_weight || total_weight > max_weight ) ) {
				error_message = wc_mnm_weight_params.i18n_min_max_qty_error.replace( '%max', self.get_formatted_weight( max_weight ) ).replace( '%min', self.get_formatted_weight( min_weight ) );
			}
			// Validate that a container has minimum weight.
			else if( min_weight > 0 && total_weight < min_weight ) {
				error_message = wc_mnm_weight_params.i18n_min_weight_error.replace( '%min', self.get_formatted_weight( min_weight ) );
			// Validate that a container has less than the maximum weight.
			} else if ( max_weight > 0 && total_weight > max_weight ) {
				error_message = wc_mnm_weight_params.i18n_max_weight_error.replace( '%max', self.get_formatted_weight( max_weight ) );
			}

			// Add error message.
			if ( error_message !== '' ) {
				// "Selected Xunit".
				var selected_weight_message = self.selected_weight_message( total_weight );

				// Add error message, replacing placeholders with current values.
				container.add_message( error_message.replace( '%v', selected_weight_message ), 'error' );

			// Add selected qty status message if there are no error messages and infinite container is used.
			} else if ( container.api.get_max_container_size() === false ) {
				container.add_message( self.selected_weight_message( total_weight ) );
			}

		};

		/**
		 * Build the weight html component.
		 */
		this.get_formatted_weight = function( weight ) {
			var unit             = wc_mnm_weight_params.weight_unit;
			return wc_mnm_weight_params.i18n_weight_format.replace( '%w', parseFloat( weight ) ).replace( '%u', unit );
		};


		/**
		 * Selected total message builder.
		 */
		this.selected_weight_message = function( qty ) {
			return wc_mnm_weight_params.i18n_qty_message.replace( '%s', self.get_formatted_weight( qty ) );
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