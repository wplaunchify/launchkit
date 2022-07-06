<?php
class WPLKThankYou {

	/**
	 * Method to kickstart the code.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	
	public static function start(){
		new WPLKThankYou;
	}

	/**
	 * Adds all necessary action hooks
	 */

	private function __construct(){

		// redirect to custom thank you page
		add_action('woocommerce_thankyou', array($this, 'wplk_redirect_thank_you_page'));

		// adds meta_box to wc general settings 
		add_filter( 'woocommerce_general_settings', array( $this, 'wplk_custom_thank_you_page' ) );
	}


	/**
	 * Custom "thank you" page option (fallback if two products in checkout with different thank you pages)
	 * Setting can be found under "WooCommerce > Settings > Checkout".
	 *
	 * @param array $settings Stored settings.
	 */
	
	public function wplk_custom_thank_you_page( $settings ) {

		$updated_settings = array();

		foreach ( $settings as $section ) {

			// At the bottom of the General Options section.
			if ( isset( $section['id'] ) && 'general_options' === $section['id'] &&
			isset( $section['type'] ) && 'sectionend' === $section['type'] ) {

				$updated_settings[] = array(
					'title'    => __( 'WPLaunchKit - Thank You Page', 'woocommerce' ),
					'desc'     => __( 'Add a default, fallback thank you page to redirect to after the checkout process is complete.', 'woocommerce' ),
					'id'       => 'wplk_thankyou_page_id',
					'type'     => 'single_select_page',
					'default'  => '',
					'class'    => 'wc-enhanced-select-nostd',
					'css'      => 'min-width:300px;',
					'desc_tip' => true,
				);
			}

			$updated_settings[] = $section;
		}

		// Return the settings array.
		return $updated_settings;
	}


/**
	 * Redirects to the selected thank you page, if one has been set.
	 *
	 * @param int $order_id Order ID.
	 */

	public function wplk_redirect_thank_you_page( $order_id ) {

		// get the wc order and items inside
		$order = wc_get_order( $order_id );
		$items = $order->get_items();

		// bail if no items (not likely)
		if ( empty( $items ) || 0 === count( $items ) ) {
			return;
		}

		// get our fallback global option
		$fallback = get_option( 'wplk_thankyou_page_id', false ); // fallback field id from above


		// check if option exists then redirect
		if ( ! empty( $fallback ) ) {

			// set page to redirect
			$page = get_permalink( (int) $fallback );

			// And redirect.
			wp_safe_redirect( $page ); // only page url
						
			exit;
		}

		
		// end redirection_thank_you_page
	}


} // end class wrapper

/**
 * Finally, start the class
 */

WPLKThankYou::start();