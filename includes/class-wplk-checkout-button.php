<?php

class WPLKCheckoutButton {

/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */

  public function __construct() {

  	  add_filter( 'woocommerce_order_button_text', array ($this, 'custom_message'), 9999 ); // higher priority to override other plugins using same filter
   
  }



/**
   * Retrieves the value of the LaunchFlows settings menu global checkout button option
   *
   * @since 1.0.0
   *
   * @return void
   */

  public function custom_message($content) {

      // if no global then default to "Place Order"
      
      $options = get_option( 'wplk_settings' );

      if( isset($options['wplk_text_field_1']) && !empty($options['wplk_text_field_1']) ) {     

        return $options['wplk_text_field_1']; // applies new text from plugin admin panel
  
      } // else
  
        return esc_html__( 'Place Order', 'woocommerce' ); // default button text to use on all other product
      
      }



} // leave in place

/**
 * Finally, instantiate the class
 */

new WPLKCheckoutButton;