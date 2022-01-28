<?php
/**
 * Plugin URI: http://www.woocommerce.com/products/woocommerce-mix-and-match-products/
 * Plugin Name: WooCommerce Mix and Match - By Weight
 * Version: 1.2.0
 * Description: Validate container by weight, requires MNM 1.10.5
 * Author: Kathy Darling
 * Author URI: http://kathyisawesome.com/
 * Developer: Kathy Darling
 * Developer URI: http://kathyisawesome.com/
 * Text Domain: wc-mnm-weight
 * Domain Path: /languages
 * 
 * GitHub Plugin URI: kathyisawesome/wc-mnm-weight
 * GitHub Plugin URI: https://github.com/kathyisawesome/wc-mnm-weight
 *
 * Copyright: © 2020 Kathy Darling
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
	CONST VERSION = '1.1.0';
	CONST REQUIRED_WOO = '4.0.0';

	/**
	 * WC_MNM_Weight Constructor
	 *
	 * @access 	public
     * @return 	WC_MNM_Weight
	 */
	public static function init() {

		// Load translation files.
		add_action( 'init', array( __CLASS__, 'load_plugin_textdomain' ) );

		// Add extra meta.
		add_action( 'woocommerce_mnm_product_options', array( __CLASS__, 'container_weight_size_options') , 10, 2 );
		add_filter( 'wc_mnm_validation_options', array( __CLASS__, 'validation_options' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'process_meta' ), 20 );
		
		// Display the weight on the front end.
		add_action( 'woocommerce_mnm_child_item_details', array( __CLASS__, 'display_weight' ), 67, 2 );

		// Register Scripts.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_scripts' ) );
		add_filter( 'woocommerce_mix_and_match_data_attributes', array( __CLASS__, 'add_data_attributes' ), 10, 2 );

		// Display Scripts.
		add_action( 'woocommerce_mix-and-match_add_to_cart', array( __CLASS__, 'load_scripts' ) );

		// QuickView support.
		add_action( 'wc_quick_view_enqueue_scripts', array( __CLASS__, 'load_scripts' ) );

		// Validation.
		add_filter( 'woocommerce_mnm_add_to_cart_container_validation', array( __CLASS__, 'weight_validation' ), 10, 3 );
		add_filter( 'woocommerce_mnm_cart_container_validation', array( __CLASS__, 'weight_validation' ), 10, 3 );
		add_filter( 'woocommerce_mnm_add_to_order_container_validation', array( __CLASS__, 'weight_validation' ), 10, 3 );

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
					'label'   => __( 'Validation mode', 'wc-mnm-weight' ),
					'value'	  => $value,
					'options' => $allowed_options,
				)
			);
		}

		woocommerce_wp_text_input( array(
			'id'            => '_mnm_min_container_weight',
			'label'       => __( 'Min Container Weight', 'wc-mnm-weight' ) . ' (' . get_option( 'woocommerce_weight_unit' ) . ')',
			'desc_tip'    => true,
			'description' => __( 'Min weight of containers in decimal form', 'woocommerce-mix-and-match-products', 'wc-mnm-weight', 'wc-mnm-by-weight' ),
			'type'        => 'text',
			'data_type'   => 'decimal',
			'value'			=> $mnm_product_object->get_meta( '_mnm_min_container_weight', true, 'edit' ),
			'desc_tip'      => true,
			'wrapper_class' => 'show_if_validate_by_weight'
		) );

		woocommerce_wp_text_input( array(
			'id'            => '_mnm_max_container_weight',
			'label'       => __( 'Max Container Weight', 'wc-mnm-weight' ) . ' (' . get_option( 'woocommerce_weight_unit' ) . ')',
			'desc_tip'    => true,
			'description' => __( 'Maximum weight of containers in decimal form', 'woocommerce-mix-and-match-products', 'wc-mnm-weight', 'wc-mnm-by-weight' ),
			'type'        => 'text',
			'data_type'   => 'decimal',
			'value'			=> $mnm_product_object->get_meta( '_mnm_max_container_weight', true, 'edit' ),
			'desc_tip'      => true,
			'wrapper_class' => 'show_if_validate_by_weight'
		) );

		?>
		<script>
			jQuery( document ).ready( function( $ ) {

				$( "#mnm_product_data input.mnm_validation_mode" ).change( function() {

					var value = $( this ).val();
					
					if( '' === value ) {
						$( "#mnm_product_data .mnm_container_size_options" ).show();
						$( "#mnm_product_data .show_if_validate_by_weight" ).hide();
					} else {
						$( "#mnm_product_data .mnm_container_size_options" ).hide();
						if( 'weight' === value ) {
							$( "#mnm_product_data .show_if_validate_by_weight" ).show();
						} else {
							$( "#mnm_product_data .show_if_validate_by_weight" ).hide();
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

			if( ! empty( $_POST[ '_mnm_validation_mode' ] ) && array_key_exists( $_POST[ '_mnm_validation_mode' ], $allowed_options ) ) {
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
	 * Add the min/max attribute
	 *
	 * @param obj $mnm_product
	 * @param obj $parent_product - the container product
	 */
	public static function display_weight( $mnm_product, $parent_product ){

		if( $mnm_product->has_weight() ) {
			printf( '<p class="product-weight" data-mnm-id="%d" data-weight="%s">%s</p>', esc_attr( $mnm_product->get_id() ), esc_attr( $mnm_product->get_weight() ), wc_format_weight( $mnm_product->get_weight() ) );
		}

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
	public static function weight_validation( $valid, $product, $mnm_stock ) {

		if( self::validate_by_weight( $product ) ) {		

			$managed_items = $mnm_stock->get_managed_items();

			$total_weight = 0;

			foreach ( $managed_items as $managed_item_id => $managed_item ) {
				$managed_product       = wc_get_product( $managed_item_id );
				$item_title            = $managed_product->get_title();
				$total_weight 		  += $managed_product->get_weight() * $managed_item[ 'quantity' ];
			}

			// Validate the total weight.
			if ( $total_weight < $product->get_meta( '_mnm_min_container_weight' ) ) {
				$error_message = sprintf( __( 'Your &quot;%s&quot; is too light.', 'wc-mnm-weight' ), $product->get_title() );
				wc_add_notice( $error_message, 'error' );
				$valid = false;
			} elseif ( $total_weight > $product->get_meta( '_mnm_max_container_weight' ) ) {
				$error_message = sprintf( __( 'Your &quot;%s&quot; is too heavy.', 'wc-mnm-weight' ), $product->get_title() );
				wc_add_notice( $error_message, 'error' );
				$valid = false;
			}

			$valid = true;

		}

		return $valid;
	}

	/*-----------------------------------------------------------------------------------*/
	/* Scripts and Styles */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Register scripts
	 *
	 * @return void
	 */
	public static function register_scripts() {

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_script( 'wc-add-to-cart-mnm-weight-validation', plugins_url( '/assets/js/frontend/wc-add-to-cart-mnm-weight-validation' .  $suffix . '.js', __FILE__ ), array( 'wc-add-to-cart-mnm' ), self::VERSION, true );

		$params = array(
			'weight_unit' 			=> get_option( 'woocommerce_weight_unit' ),
			'trim_zeros'            => false === apply_filters( 'woocommerce_price_trim_zeros', true ) ? 'no' : 'yes',
			'i18n_weight_format'    => esc_html_x( '%w %u', 'Weight followed by weight unit', 'wc-mnm-weight' ),
			'i18n_total'            => __( 'Total Weight: ', 'wc-mnm-weight' ),

			// translators: %s is current selected weight
			'i18n_qty_message'                   => __( 'You have selected %s. ', 'wc-mnm-weight' ),

			// translators: %v is the error message. %s is weight left to be selected.
			'i18n_qty_error'                     => __( '%vPlease select %s to continue&hellip;', 'wc-mnm-weight' ),

			// translators: %v is the error message. %min is the script placeholder for formatted min weight. %max is script placeholder for formatted max weight.
			'i18n_min_max_qty_error'             => __( '%vPlease choose between %min and %max to continue&hellip;', 'wc-mnm-weight' ),
			
			// translators: %v is the error message. %min is the script placeholder for formatted min weight. %max is script placeholder for formatted max weight.
			'i18n_min_qty_error'                 => __( '%vPlease choose at least %min to continue&hellip;', 'wc-mnm-weight' ),
			
			// translators: %v is the error message. %min is the script placeholder for formatted min weight. %max is script placeholder for formatted max weight.
			'i18n_max_qty_error'                 => __( '%vPlease choose fewer than %max to continue&hellip;', 'wc-mnm-weight' ),

		);

		wp_localize_script( 'wc-add-to-cart-mnm-weight-validation', 'wc_mnm_weight_params', $params );

	}

	/**
	 * Script parameters
	 *
	 * @param  array $params
	 * @param  obj WC_Mix_and_Match_Product
	 * @return array
	 */
	public static function add_data_attributes( $params, $product ) {

		if( self::validate_by_weight( $product ) ) {

			$new_params = array(
				'validation_mode'       => $product->get_meta( '_mnm_validation_mode', true ),
			    'min_weight'            => $product->get_meta( '_mnm_min_container_weight', true ),
				'max_weight'			=> $product->get_meta( '_mnm_max_container_weight', true ),
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
	public static function validate_by_weight( $product ) {
		return 'weight' === $product->get_meta( '_mnm_validation_mode', true );
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

} // End class: do not remove or there will be no more guacamole for you

endif; // end class_exists check

// Launch the whole plugin.
add_action( 'woocommerce_mnm_loaded', array( 'WC_MNM_Weight', 'init' ) );
