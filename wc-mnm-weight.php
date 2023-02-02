<?php
/**
 * Plugin URI: http://www.woocommerce.com/products/woocommerce-mix-and-match-products/
 * Plugin Name: WooCommerce Mix and Match - By Weight
 * Version: 2.0.0-beta.1
 * Description: Validate container by weight, requires MNM 1.10.5
 * Author: Kathy Darling
 * Author URI: http://kathyisawesome.com/
 * Developer: Kathy Darling
 * Developer URI: http://kathyisawesome.com/
 * Text Domain: wc-mnm-weight
 * Domain Path: /languages
 * 
 * GitHub Plugin URI: https://github.com/kathyisawesome/wc-mnm-weight
 * Primary Branch: trunk
 * Release Asset: true
 * 
 * Copyright: © 2020 - 2023 Kathy Darling
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * The Main WC_MNM_Weight class
 **/
if ( ! class_exists( 'WC_MNM_Weight' ) ) :

class WC_MNM_Weight {

	/**
	 * constants
	 */
	const VERSION = '2.0.0-beta.1';
	const REQ_MNM_VERSION = '2.4.0-beta.3';

	/**
	 * WC_MNM_Weight Constructor
	 *
	 * @access 	public
     * @return 	WC_MNM_Weight
	 */
	public static function init() {

		// Quietly quit if Mix and Match is not active or below 2.0.
		if ( ! function_exists( 'wc_mix_and_match' ) || version_compare( wc_mix_and_match()->version, self::REQ_MNM_VERSION ) < 0 ) {
			return false;
		}

		// Load translation files.
		add_action( 'init', array( __CLASS__, 'load_plugin_textdomain' ) );

		// Add extra meta.
		add_action( 'wc_mnm_admin_product_options', array( __CLASS__, 'container_weight_size_options') , 10, 2 );
		add_filter( 'wc_mnm_validation_options', array( __CLASS__, 'validation_options' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'process_meta' ), 20 );
		
		// Display the weight on the front end.
		add_action( 'wc_mnm_child_item_details', array( __CLASS__, 'display_weight' ), 67, 2 );

		// Unset any quantity restrictions on the container since we don't use it for validation.
		add_filter( 'woocommerce_product_get_max_container_size', array( __CLASS__, 'min_container_size' ), 10, 2 );
		add_filter( 'woocommerce_product_get_max_container_size', array( __CLASS__, 'max_container_size' ), 10, 2 );

		// Hide too heavy items.
		add_filter( 'wc_mnm_child_item_is_visible', array( __CLASS__, 'exclude_items' ), 10, 2 );

		// Restrict child maximums by their weight limitations instead of quantity.
		add_filter( 'wc_mnm_child_item_quantity_input_max', array( __CLASS__, 'child_item_max' ), 10, 2 );

		// Register Scripts.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_scripts' ) );
		add_filter( 'wc_mnm_add_to_cart_script_parameters', array( __CLASS__, 'script_params' ) );
		add_filter( 'wc_mnm_container_data_attributes', array( __CLASS__, 'add_data_attributes' ), 10, 2 );

		// Display Scripts.
		add_action( 'woocommerce_mix-and-match_add_to_cart', array( __CLASS__, 'load_scripts' ) );
        add_action( 'woocommerce_grouped-mnm_add_to_cart', array( __CLASS__, 'load_scripts' ) );

		// QuickView support.
		add_action( 'wc_quick_view_enqueue_scripts', array( __CLASS__, 'load_scripts' ) );

		// Validation.
		add_filter( 'wc_mnm_add_to_cart_container_validation', array( __CLASS__, 'add_to_cart_validation' ), 10, 3 );
		add_filter( 'wc_mnm_add_to_order_container_validation', array( __CLASS__, 'add_to_order_validation' ), 10, 3 );

    }


	/*-----------------------------------------------------------------------------------*/
	/* Localization */
	/*-----------------------------------------------------------------------------------*/


	/**
	 * Make the plugin translation ready
	 *
	 * @return void
	 */
	public static function load_plugin_textdomain() {
		load_plugin_textdomain( 'wc-mnm-weight' , false , dirname( plugin_basename( __FILE__ ) ) .  '/languages/' );
	}

	/*-----------------------------------------------------------------------------------*/
	/* Admin */
	/*-----------------------------------------------------------------------------------*/


	/**
	 * Adds the container max weight option writepanel options.
	 *
	 * @param int $post_id
	 * @param  WC_Product_Mix_and_Match  $mnm_product_object
	 */
	public static function container_weight_size_options( $post_id, $mnm_product_object ) {

		if ( apply_filters( 'wc_mnm_admin_show_validation_mode_option', true ) ) {

			$allowed_options = self::get_validation_options();
			$value = $mnm_product_object->get_meta( '_mnm_validation_mode' );
			$value = array_key_exists( $value, $allowed_options ) ? $value : '';

			woocommerce_wp_radio( 
				array(
					'id'      => '_mnm_validation_mode',
					'class'   => 'select short mnm_validation_mode',
					'label'   => esc_html__( 'Validation mode', 'wc-mnm-weight' ),
					'value'	  => $value,
					'options' => $allowed_options,
				)
			);
		}

		woocommerce_wp_text_input( array(
			'id'            => '_mnm_min_container_weight',
			'label'         => esc_html__( 'Min Container Weight', 'wc-mnm-weight' ) . ' (' . get_option( 'woocommerce_weight_unit' ) . ')',
			'desc_tip'      => true,
			'description'   => esc_html__( 'Min weight of containers in decimal form', 'wc-mnm-weight' ),
			'type'          => 'text',
			'data_type'     => 'decimal',
			'value'			=> self::get_min_container_weight( $mnm_product_object ),
			'desc_tip'      => true,
			'wrapper_class' => 'show_if_weight_validation_mode',
		) );

		woocommerce_wp_text_input( array(
			'id'            => '_mnm_max_container_weight',
			'label'         => esc_html__( 'Max Container Weight', 'wc-mnm-weight' ) . ' (' . get_option( 'woocommerce_weight_unit' ) . ')',
			'desc_tip'      => true,
			'description'   => esc_html__( 'Maximum weight of containers in decimal form', 'wc-mnm-weight' ),
			'type'          => 'text',
			'data_type'     => 'decimal',
			'value'			=> self::get_max_container_weight( $mnm_product_object ),
			'desc_tip'      => true,
			'wrapper_class' => 'show_if_weight_validation_mode'
		) );

		?>
		<script>
			jQuery( document ).ready( function( $ ) {

				$( "#mnm_product_data input.mnm_validation_mode" ).change( function() {

					var value = $( this ).val();
					
					if( '' === value ) {
						$( "#mnm_product_data .mnm_container_size_options" ).show();
						$( "#mnm_product_data .show_if_weight_validation_mode" ).hide();
					} else {
						$( "#mnm_product_data .mnm_container_size_options" ).hide();
						if( 'weight' === value ) {
							$( "#mnm_product_data .show_if_weight_validation_mode" ).show();
						} else {
							$( "#mnm_product_data .show_if_weight_validation_mode" ).hide();
						}
					}
				} );

				$( "#mnm_product_data input.mnm_validation_mode:checked" ).change();

			} );

		</script>

		<?php

	}

	/**
	 * Add the options via filter, so we can work with other validation mini-extensions.
	 *
	 * @param  array $options Validation options
	 * @return array
	 */
	public static function validation_options( $options ) {
		$options[ '' ]       = esc_html__( 'Use default', 'wc-mnm-weight' );
		$options[ 'weight' ] = esc_html__( 'Validate by weight', 'wc-mnm-weight' );
		return $options;
	}

	/**
	 * Saves the new meta field.
	 *
	 * @param  WC_Product_Mix_and_Match  $mnm_product_object
	 */
	public static function process_meta( $product ) {

		if ( $product->is_type( 'mix-and-match' ) ) {

			$allowed_options = self::get_validation_options();

			if( ! empty( $_POST[ '_mnm_validation_mode' ] ) && array_key_exists( wc_clean( $_POST[ '_mnm_validation_mode' ] ), $allowed_options ) ) {
				$product->update_meta_data( '_mnm_validation_mode', wc_clean( $_POST[ '_mnm_validation_mode' ] ) );
			} else {
				$product->delete_meta_data( '_mnm_validation_mode' );
			}

			if ( ! empty( $_POST[ '_mnm_max_container_weight' ] ) ) {
				$product->update_meta_data( '_mnm_max_container_weight', wc_clean( wp_unslash( $_POST[ '_mnm_max_container_weight' ] ) ) );
			} else {
				$product->delete_meta_data( '_mnm_max_container_weight' );
			}

			if ( ! empty( $_POST[ '_mnm_min_container_weight' ] ) ) {
				$product->update_meta_data( '_mnm_min_container_weight', wc_clean( wp_unslash( $_POST[ '_mnm_min_container_weight' ] ) ) );
			}	else {
				$product->delete_meta_data( '_mnm_min_container_weight' );
			}

		}

	}

	/*-----------------------------------------------------------------------------------*/
	/* Front End Display */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Add the weight attribute
	 *
	 * @param WC_MNM_Child_Item $child_item
	 * @param WC_Product_Mix_and_Match
	 */
	public static function display_weight( $child_item, $parent_product ) {

		if ( self::is_weight_validation_mode( $parent_product ) ) {
			$child_product = $child_item->get_product();
			if ( $child_product && $child_product->has_weight() ) {
				printf( '<p class="product-weight" data-mnm-id="%d" data-weight="%s">%s</p>', esc_attr( $child_product->get_id() ), esc_attr( $child_product->get_weight() ), wc_format_weight( $child_product->get_weight() ) );
			}
		}

	}

	/**
	 * Remove any accidental min quantity limitations when validating by weight
	 *
	 * @param int $min
	 * @param WC_Product_Mix_and_Match $container
	 * @return string
	 */
	public static function min_container_size( $min, $container ) {

		if ( self::is_weight_validation_mode( $container ) ) {
			$min = '';
		}

		return $min;

	}

	/**
	 * Remove any accidental max quantity limitations when validating by weight
	 *
	 * @param string|int $max
	 * @param WC_Product_Mix_and_Match $container
	 * @return string
	 */
	public static function max_container_size( $max, $container ) {

		if ( self::is_weight_validation_mode( $container ) ) {
			$max = '';
		}

		return $max;

	}


	/**
	 * Limit child item by weight, not quantity
	 *
	 * @param bool $is_visible
	 * @param WC_MNM_Child_Item $child_item

	 * @return bool
	 */
	public static function exclude_items( $is_visible, $child_item ) {
		if ( self::is_weight_validation_mode( $child_item->get_container() ) && $child_item->get_product() ) {
			$child_product = $child_item->get_product();
			$container_max = self::get_max_container_weight( $child_item->get_container() );

			if ( $child_product->has_weight() && $child_product->get_weight() > $container_max ) {
				$is_visible = false;
			}

		}
		return $is_visible;
	}
	

	/**
	 * Limit child item by weight, not quantity
	 *
	 * @param string|int $max
	 * @param WC_MNM_Child_Item $child_item
	 * @return int
	 */
	public static function child_item_max( $max, $child_item ) {

		if ( self::is_weight_validation_mode( $child_item->get_container() ) && $child_item->get_product() ) {

			$child_product = $child_item->get_product();
			$container_max = self::get_max_container_weight( $child_item->get_container() );

			if ( $child_product->has_weight() && $container_max ) {

				// Should get excluded on exclude_items() but just in case.
				if ( $child_product->get_weight() > $container_max ) {
					$max = 0;
				} else {
					// If product has weight, calculate how much weight can fit into the container. This is the quantitiy limit.
					$weight_limit = floor( $container_max / $child_product->get_weight() );

					// Double check weight limit against stock limit.
					$max          = $child_product->get_max_purchase_quantity() > 0 ? min( $weight_limit, $child_product->get_max_purchase_quantity() ) : $weight_limit;

				}

			}

		}

		return $max;

	}

	/*-----------------------------------------------------------------------------------*/
	/* Cart Functions */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Server-side weight validation
	 * 
	 * @param bool $is_valid
	 * @param obj WC_Product_Mix_and_Match $product
	 * @param obj WC_Mix_and_Match_Stock_Manager $mnm_stock
	 * @return  bool 
	 */
	public static function add_to_cart_validation( $is_valid, $product, $mnm_stock ) {

		// Skip legacy validation on Store API requests.
		if ( ! $is_valid || wc()->is_rest_api_request() ) {
			return $is_valid;
		}

		if ( self::is_weight_validation_mode( $product ) ) {
			
			$is_valid = self::validate();

			if ( is_wp_error( $is_valid ) ) {

				// translators: %1$s is the product title. %2$s is the reason it cannot be added to the cart.
				$error_message = sprintf( esc_html__( '&quot;%1$s&quot; could not be added to the cart. %2$s', 'wc-mnm-weight' ), $product->get_title(), $is_valid->get_error_message() );

				wc_add_notice( $error_message, 'error' );

				$is_valid = false;

			}

		}

		return $is_valid;

	}

	/**
	 * Server-side weight validation
	 * 
	 * @param obj WC_Product_Mix_and_Match $product
	 * @param obj WC_Mix_and_Match_Stock_Manager $mnm_stock
	 * @return  bool|WP_Error 
	 */
	public static function validate( $product, $mnm_stock ) {

		$selected_items = $mnm_stock->get_items();

		$total_weight = 0;

		// The weight of items allowed to be in the container. NB: wc_format_decimal() defaults to the number of decimal places in currency settings.
		$min_weight = self::get_min_container_weight( $product );
		$max_weight = self::get_max_container_weight( $product );
		
		// Sum up the total container weight based on quantities selected.
		foreach ( $selected_items as $selected_item ) {
			$selected_product      = $selected_item->get_product();
			$total_weight 		  += $selected_product ? $selected_product->get_weight() * $managed_item[ 'quantity' ] : 0;
		}

		// @todo - Need handle weight rounding/precision.

		// Validate the total weight.
		if ( $min_weight && $min_weight === $max_weight && $total_weight !== $min_weight ) {
			
			// translators: %s is the formatted min weight.
			$error_message = sprintf( esc_html_x( 'You have selected an invalid amount of product. Please choose exactly %s worth of product.', '[Frontend]', 'wc-mnm-weight' ), wc_format_weight( $min_weight ) );

			$valid = new WP_Error( 'wc_mnm_container_min_weight', $error_message );
		} elseif ( $min_weight && $total_weight < $min_weight ) {
			
			// translators: %s is the formatted min weight.
			$error_message = sprintf( esc_html_x( 'You have not selected enough product. Please choose at least %s worth of product.', '[Frontend]', 'wc-mnm-weight' ), wc_format_weight( $min_weight ) );

			$valid = new WP_Error( 'wc_mnm_container_min_weight', $error_message );
		} elseif ( $max_weight && $total_weight > $max_weight ) {
			
			// translators: %s is the formatted max weight.
			$error_message = sprintf( esc_html_x( 'You have selected too much product. Please choose less than %s worth of product.', '[Frontend]', 'wc-mnm-weight' ), wc_format_weight( $max_weight ) );

			$valid = new WP_Error( 'wc_mnm_container_max_weight', $error_message );
		}

		return $valid;
	}

	/*-----------------------------------------------------------------------------------*/
	/* Order Functions */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Server-side weight validation.
	 * 
	 * @param bool $is_valid
	 * @param obj WC_Product_Mix_and_Match $product
	 * @param obj WC_Mix_and_Match_Stock_Manager $mnm_stock
	 * @return  bool 
	 */
	public static function add_to_order_validation( $is_valid, $product, $mnm_stock ) {

		// Skip legacy validation on Store API requests.
		if ( ! $is_valid || wc()->is_rest_api_request() ) {
			return $is_valid;
		}

		if ( self::is_weight_validation_mode( $product ) ) {
			
			$is_valid = self::validate();

			if ( is_wp_error( $is_valid ) ) {

				// translators: %1$s is the product title. %2$s is the reason it cannot be added to the cart.
				$error_message = sprintf( esc_html__( '&quot;%1$s&quot; could not be added to the order. %2$s', 'wc-mnm-weight' ), $product->get_title(), $is_valid->get_error_message() );

				wc_add_notice( $error_message, 'error' );

				$is_valid = false;

			}

		}

		return $is_valid;

	}

	/*-----------------------------------------------------------------------------------*/
	/* Scripts and Styles */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Register scripts
	 */
	public static function register_scripts() {

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'wc-add-to-cart-mnm-weight-validation', plugins_url( '/assets/js/frontend/wc-add-to-cart-mnm-weight-validation' .  $suffix . '.js', __FILE__ ), array( 'wc-add-to-cart-mnm' ), time(), true );

	}


	/**
	 * Localize script params.
	 * 
	 * @since 2.0.0
	 * @param array $params
	 * @return array
	 */
	public static function script_params( $params ) {

		$params = array_merge( $params, array(

			// Store unit of weight.
			'weight_unit'                     => get_option( 'woocommerce_weight_unit' ),
		
			// translators:  %w is current selected weight. %u is the unit of weight, ie: kg.
			'i18n_weight_format'              => esc_html_x( '%w %u', 'Weight followed by weight unit', '[Frontend]', 'wc-mnm-weight' ),

			// translators:  %s is current selected weight
			'i18n_weight_qty_message'         => esc_html_x( 'You have selected %s.', '[Frontend]', 'wc-mnm-weight' ),

			// translators:  %v is the error message. %s is weight left to be selected.
			'i18n_weight_qty_error'           => esc_html_x( '%v Please select %s to continue&hellip;', '[Frontend]', 'wc-mnm-weight' ),

			// translators:  %v is the error message. %min is the script placeholder for formatted min weight. %max is script placeholder for formatted max weight.
			'i18n_weight_min_max_qty_error'   => esc_html_x( '%v Please choose between %min and %max to continue&hellip;', '[Frontend]', 'wc-mnm-weight' ),
			
			// translators:  %v is the error message. %min is the script placeholder for formatted min weight. %min is script placeholder for formatted min weight.
			'i18n_weight_min_qty_error'       => esc_html_x( '%v Please choose at least %min to continue&hellip;', '[Frontend]', 'wc-mnm-weight' ),
			
			// translators:  %v is the error message. %min is the script placeholder for formatted min weight. %max is script placeholder for formatted max weight.
			'i18n_weight_max_qty_error'       => esc_html_x( '%v Please choose less than %max to continue&hellip;', '[Frontend]', 'wc-mnm-weight' ),

			// translators:  %v is the current status message.
			'i18n_weight_valid_fixed_message' => esc_html_x( '%v Add to cart to continue&hellip;', '[Frontend]', 'wc-mnm-weight' ),

			// translators:  %v is the current status message.
			'i18n_weight_valid_min_message'   => esc_html_x( '%v You can select more or add to cart to continue&hellip;', '[Frontend]', 'wc-mnm-weight' ),

			// translators:  %v is the current status message. %max is the container maximum.
			'i18n_weight_valid_max_message'   => esc_html_x( '%v You can select up to %max or add to cart to continue&hellip;', '[Frontend]', 'wc-mnm-weight' ),

			// translators:  %v is the current status message. %min is the container minimum. %max is the container maximum.
			'i18n_weight_valid_range_message' => esc_html_x( '%v You may select between %min and %max items or add to cart to continue&hellip;', '[Frontend]', 'wc-mnm-weight' ),

		) );

		return $params;

	}

	/**
	 * Script parameters
	 *
	 * @param  array $params
	 * @param  obj WC_Mix_and_Match_Product
	 * @return array
	 */
	public static function add_data_attributes( $params, $product ) {

		if( self::is_weight_validation_mode( $product ) ) {

			$new_params = array(
				'validation_mode'       => 'weight',
			    'min_weight'            => self::get_min_container_weight( $product ),
				'max_weight'			=> self::get_max_container_weight( $product ),
			);

			$params = array_merge( $params, $new_params );

		}

		return $params;

	}


	/**
	 * Load the script anywhere the MNN add to cart button is displayed
	 * @return void
	 */
	public static function load_scripts() {
		wp_enqueue_script( 'wc-add-to-cart-mnm-weight-validation' );
	}

	/*-----------------------------------------------------------------------------------*/
	/* Helpers                                                                           */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Does this product validate by weight.
	 * @param  WC_Product
	 * @return bool
	 */
	public static function is_weight_validation_mode( $product ) {
		return $product && 'weight' === $product->get_meta( '_mnm_validation_mode', true );
	}

	/**
	 * Does this product validate by weight.
	 * @param  WC_Product
	 * @return string|float
	 */
	public static function get_min_container_weight( $product ) {
		return $product && '' !== $product->get_meta( '_mnm_min_container_weight', true ) ? wc_format_decimal( $product->get_meta( '_mnm_min_container_weight', true ) ) : 0;
	}

	/**
	 * Does this product validate by weight.
	 * @param  WC_Product
	 * @return string
	 */
	public static function get_max_container_weight( $product ) {
		return '' !== $product && $product->get_meta( '_mnm_max_container_weight', true ) ? wc_format_decimal( $product->get_meta( '_mnm_max_container_weight', true ) ) : '';
	}

	/**
	 * Get allowed validation options
	 * 
	 * @since 1.3.0
	 *
	 * @return array
	 */
	public static function get_validation_options() {
		return array_unique( (array) apply_filters( 'wc_mnm_validation_options', array() ) );
	}


	/*-----------------------------------------------------------------------------------*/
	/* Deprecated                                                                        */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Does this product validate by weight.
	 * 
	 * @deprecated 2.0.0
	 * 
	 * @param  WC_Product
	 * @return bool
	 */
	public static function validate_by_weight( $product ) {
		wc_deprecated_function( 'WC_MNM_Weight::validate_by_weight', '2.0.0', 'See: WC_MNM_Weight::is_weight_validation_mode() instead.' );
		return self::is_weight_validation_mode( $product );
	}

	/**
	 * Server-side weight validation
	 * 
	 * @deprecated 2.0.0
	 * 
	 * @param bool $is_valid
	 * @param obj WC_Product_Mix_and_Match $product
	 * @param obj WC_Mix_and_Match_Stock_Manager $mnm_stock
	 * @return  bool|WP_Error 
	 */
	public static function weight_validation( $valid, $product, $mnm_stock ) {

		wc_deprecated_function( 'WC_MNM_Weight::weight_validation', '2.0.0', 'See: WC_MNM_Weight::validate instead.' );

		$is_valid = self::validate( $product, $mnm_stock );

		if ( is_wp_error( $is_valid ) ) {
			$is_valid = false;
		}
		
		return $is_valid;
	}

} // End class: do not remove or there will be no more guacamole for you

endif; // end class_exists check

// Launch the whole plugin.
add_action( 'plugins_loaded', array( 'WC_MNM_Weight', 'init' ), 20 );
