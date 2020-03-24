<?php
/**
 * Plugin Name: WooCommerce Mix and Match: By Weight
 * Plugin URI: http://www.woocommerce.com/products/woocommerce-mix-and-match-products/
 * Description: Validate container by weight
 * Version: 1.0.0
 * Author: Kathy Darling
 * Author URI: http://kathyisawesome.com/
 * Developer: Kathy Darling, Manos Psychogyiopoulos
 * Developer URI: http://kathyisawesome.com/
 * Text Domain: wc-mnm-weight
 * Domain Path: /languages
 *
 * Copyright: © 2018 Kathy Darling and Manos Psychogyiopoulos
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
	CONST VERSION = '1.0.0';
	CONST REQUIRED_WOO = '3.3.0';

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

		// Add to cart validation.
		add_filter( 'woocommerce_mnm_add_to_cart_container_validation', array( __CLASS__, 'weight_validation' ), 10, 3 );

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
		woocommerce_wp_text_input( array(
			'id'            => '_mnm_max_container_weight',
			'label'       => __( 'Max Container Weight', 'wc-mnm-max-weight' ) . ' (' . get_option( 'woocommerce_weight_unit' ) . ')',
			'desc_tip'    => true,
			'description' => __( 'Maximum weight of containers in decimal form', 'woocommerce' ),
			'type'        => 'text',
			'data_type'   => 'decimal',
			'value'			=> $mnm_product_object->get_meta( '_mnm_max_container_weight', true, 'edit' ),
			'desc_tip'      => true
		) );
	}

	/**
	 * Saves the new meta field.
	 *
	 * @param  WC_Product_Mix_and_Match  $mnm_product_object
	 */
	public static function process_meta( $product ) {
		if( ! empty( $_POST[ '_mnm_max_container_weight' ] ) ) {
			$product->update_meta_data( '_mnm_max_container_weight', wc_clean( wp_unslash( $_POST[ '_mnm_max_container_weight' ] ) ) );
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
			printf( '<p class="product-weight" data-mnm-id="%d" data-weight="%s">%s</p>', $mnm_product->get_id(), $mnm_product->get_weight(), wc_format_weight( $mnm_product->get_weight() ) );
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

			$managed_items = $mnm_stock->get_managed_items();

			$total_weight = 0;

			foreach ( $managed_items as $managed_item_id => $managed_item ) {
				$managed_product       = wc_get_product( $managed_item_id );
				$item_title            = $managed_product->get_title();
				$total_weight 		  += $managed_product->get_weight() * $managed_item[ 'quantity' ];
			}

			// Validate the total weight.
			if ( $total_weight > $product->get_meta( '_mnm_max_container_weight' ) ) {
				$error_message = sprintf( __( 'You &quot;%s&quot; is too heavy.', 'wc-mnm-max-weight' ), $product->get_title() );
				wc_add_notice( $error_message, 'error' );
				return false;
			}

			return true;
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

		wp_register_script( 'wc-add-to-cart-mnm-weight-max', plugins_url( 'js/wc-add-to-cart-mnm-weight-max.js', __FILE__ ), array( 'wc-add-to-cart-mnm' ), WC_MNM_Weight::VERSION, true );

		$params = array(
			'i18n_max_weight_error' => __( 'Your configuration is too heavy. Please choose less than %max to continue&hellip;', 'wc-mnm-weight' ),
			'i18n_weight_format'    => sprintf( _x( '%1$s%2$s%3$s', '"Total Weight" string followed by weight followed by weight unit', 'woocommerce-mix-and-match-products' ), '%t', '%w', '%u' ),
			'i18n_total'            => __( 'Total Weight: ', 'wc-mnm-weight' )
		);

		wp_localize_script( 'wc-add-to-cart-mnm-weight-max', 'wc_mnm_weight_params', $params );

	}

	/**
	 * Script parameters
	 *
	 * @param  array $params
	 * @param  obj WC_Mix_and_Match_Product
	 * @return array
	 */
	public static function add_data_attributes( $params, $product ) {

		if( $product->get_meta( '_mnm_max_container_weight' ) ) {

			$new_params = array(
				'max_weight'			=> $product->get_meta( '_mnm_max_container_weight', true, 'edit' ),
				'weight_unit' 			=> get_option( 'woocommerce_weight_unit' )
			);

			$params = array_merge( $params, $new_params );

		}

		return $params;

	}


	/**
	 * Load the script anywhere the MNN add to cart button is displayed
	 * @return void
	 */
	public static function load_scripts(){
		wp_enqueue_script( 'wc-add-to-cart-mnm-weight-max' );
	}


} //end class: do not remove or there will be no more guacamole for you

endif; // end class_exists check

// Launch the whole plugin.
add_action( 'woocommerce_mnm_loaded', array( 'WC_MNM_Weight', 'init' ) );
