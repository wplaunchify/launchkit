<?php
/**
 * Plugin Name:	LaunchKit
 * Plugin URI:	https://wplaunchify.com
 * Description:	Launch Your WooCommerce Business Solution In Minutes!
 * Version:		1.0.1
 * Author:		1WD LLC
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wplk
 * Tested up to: 6.0
 * WC requires at least: 6.0
 * WC tested up to: 6.6
 * 
 * @package LaunchKit
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
	 * LaunchKit Class
	 *
	 *
	 * @since 1.0.0
	 */

	class LaunchKit {

/**
	 * Plugin Version
	 *
	 * @since 1.0.0
	 * @var string The plugin version.
	 */
	const VERSION = '1.0.0';

/**
	 * Minimum PHP Version
	 *
	 * @since 1.0.0
	 * @var string Minimum PHP version required to run the plugin.
	 */
	const MINIMUM_PHP_VERSION = '7.0';



/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @access public
	 */

	public function __construct() {

		// Load textdomain for translation
		add_action( 'init', array( $this, 'wplk' ) );

		// Load constants and includes
		add_action( 'init', array($this, 'setup_constants' ));

		// Initialize the Plugin init method
		add_action( 'plugins_loaded', array( $this, 'init' ) );

		add_action( 'plugins_loaded', array($this, 'includes' ));

		// Adds Admin Panel and Content
		add_action( 'admin_menu', array( $this, 'wplk_add_admin_menu' ));
		
		add_action( 'admin_init', array ($this , 'wplk_settings_init' ));


		// Only if LaunchFlows is not active
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( ! is_plugin_active( 'launchflows/launchflows.php' ) ) {

			// Apply Settings
			add_action( 'init', array( $this, 'wplk_apply_settings' ) );

			// Apply Public Style
			add_action('wp_enqueue_scripts', array($this, 'wplk_add_public_style' ), 999); // loads last with high priority

			// Apply Admin Style
			add_action( 'admin_enqueue_scripts', array( $this, 'wplk_add_script_to_menu_page' ) );

		}

	}

/**
	 * Load Textdomain
	 *
	 * Load plugin localization files.
	 * Fired by `init` action hook.
	 *
	 * @since 1.0.0
	 * @access public
	 */

	public function wplk() {

		load_plugin_textdomain( 'wplk' );

	}

/**
	 * Initialize LaunchKit Plugin
	 *
	 * Validates that Woocommerce already loaded.
	 *
	 * Fired by `plugins_loaded` action hook.
	 *
	 * @since 1.0.0
	 * @access public
	 */

	public function init() {

        // Check if WooCommerce is installed and activated
        if ( ! function_exists( 'WC' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_fails_to_load' ) ); // error, offer to install
		}

		// Once we get here, We have passed the WooCommerce validation check so we can safely include our core features


		// Check if LaunchFlows is installed and activated before adding thank you redirect
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		
		if ( ! is_plugin_active( 'launchflows/launchflows.php' ) ) {

			require_once( 'includes/class-wplk-thankyou.php' ); // custom thank you pages per WC product
			require_once( 'includes/class-wplk-functions.php'); 	// primary functions
   	        require_once( 'includes/class-wplk-registration.php' );		// core plugin logic
   	        require_once( 'includes/class-wplk-checkout-button.php' );		// custom checkout button    

		}


	}




/**
	 * Fires admin notice when WooCommerce is not installed and activated.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */

	public function woocommerce_fails_to_load() {

    if (!current_user_can('activate_plugins')) {
        return;
    }

	if ( ! function_exists( '_is_woocommerce_installed' ) ) {

		/**
		 * Is WooCommerce plugin installed.
		 *
		 * @since 1.0.0
		 *
		 * @access public
		 */
		function _is_woocommerce_installed() {

			$path    = 'woocommerce/woocommerce.php';

			$plugins = get_plugins();

			return isset( $plugins[ $path ] );
		}
	}

		$screen = get_current_screen();

		if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {

			return;
		
		}

		$plugin = 'woocommerce/woocommerce.php';

		if ( _is_woocommerce_installed() ) {
		
			if ( ! current_user_can( 'activate_plugins' ) ) {
		
				return;
		
			}

			$activation_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin );

			$message = '<p>' . esc_html__( 'LaunchKit requires WooCommerce to be installed and activated', 'wplk' ) . '</p>';
	
			$message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $activation_url, esc_html__( 'Activate WooCommerce Now', 'wplk' ) ) . '</p>';
	
		} else {
	
			if ( ! current_user_can( 'install_plugins' ) ) {
	
				return;
			}

			$install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' );

			$message = '<p>' . esc_html__( 'LaunchKit requires WooCommerce to be installed and activated', 'wplk' ) . '</p>';
	
			$message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', esc_url( $install_url) , esc_html__( 'Install WooCommerce Now', 'wplk' ) ) . '</p>';
		}

		// All content is now santized and escaped. Output to screen.
		echo'<div class="error LaunchKit"><p>' . wp_kses_post( $message ) . '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__('Dismiss this notice','wplk') .'</span></button></div>';

	}



/**
	 * Setup plugin constants
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 * @return void
	 */

	public function setup_constants() {

		if ( ! defined( 'WPLK_DIR_PATH' ) ) {

			define( 'WPLK_DIR_PATH', plugin_dir_path( __FILE__ ) );
		
		}

		if ( ! defined( 'WPLK_PLUGIN_PATH' ) ) {
			
			define( 'WPLK_PLUGIN_PATH', plugin_basename( __FILE__ ) );
		
		}

		if ( ! defined( 'WPLK_DIR_URL' ) ) {
		
			define( 'WPLK_DIR_URL', plugin_dir_url( __FILE__ ) );
		
		}

	}



/**
	 * Adds WPLaunchKit Page Styles & Scripts
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */

	public function wplk_add_public_style() {

	// only add to checkout page

  	if ( is_checkout() )  {

		// load public facing css (minified)

	    wp_register_style( 'wplk-public', WPLK_DIR_URL . 'assets/css/wplk-public.min.css', false, '1.0.0' );

	    wp_enqueue_style( 'wplk-public' );


	} 
}	


/**
	 * Load testing environment
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 * @return void
	 */

	public function includes() {

	// require_once 'includes/class-wplk-experimental.php'; 		// for testing new functions

	}

/**
	 * Adds Admin Page
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */

	public function wplk_add_admin_menu( ) { 

	add_menu_page( 'LaunchKit', 'LaunchKit', 'manage_options', 'wplk', array ($this, 'wplk_options_page' ), 'dashicons-rest-api');

	}




/**
	 * Adds Admin Page Style
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */

	public function wplk_add_script_to_menu_page() {

    $screen = get_current_screen();

	    if (is_object($screen) && $screen->id == 'toplevel_page_wplk') {
		    
		    // load admin page css
		    
		    wp_register_style( 'wplk-admin', WPLK_DIR_URL . 'assets/css/wplk-admin.css', false, '1.0' );
		    
		    wp_enqueue_style( 'wplk-admin' );
		     
		 }   
}



/**
	 * Adds Admin Page Settings
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */

	public function wplk_settings_init( ) { 

	register_setting( 'wplk_options_page', 'wplk_settings');


// Checkout Options
		add_settings_section( 
			'wplk_options_section_checkout', 
			esc_html__( 'Checkout Options', 'wplk' ), 
			array($this, 'wplk_settings_section'), 
			'wplk_options_page'
		);


					add_settings_field( // Change Default Checkout Button Text
						'wplk_text_field_1', 
						esc_html__( 'Change Checkout Button Text', 'wplk' ), 
						array($this, 'wplk_text_field_1_render'), 
						'wplk_options_page', 
						'wplk_options_section_checkout' 
					);

					add_settings_field( // Force WooCommerce Default Layout Into One Column
						'wplk_checkbox_field_11', 
						esc_html__( 'Change Checkout Layout Into One Column', 'wplk' ), 
						array($this, 'wplk_checkbox_field_11_render'), 
						'wplk_options_page', 
						'wplk_options_section_checkout' 
					);

					add_settings_field( // Show Checkout Even If Empty Cart
						'wplk_checkbox_field_15', 
						esc_html__( 'Display Checkout Even When No Products In Cart', 'wplk' ), 
						array($this, 'wplk_checkbox_field_15_render'), 
						'wplk_options_page', 
						'wplk_options_section_checkout' 
					);





			


// Product Options
		add_settings_section( 
			'wplk_options_section_product', 
			esc_html__( 'Product Options', 'wplk' ), 
			array($this, 'wplk_settings_section1'), 
			'wplk_options_page'
		);


					add_settings_field( // Redirect To Checkout After Adding Any Product To Cart
						'wplk_checkbox_field_12', 
						esc_html__( 'Redirect To Checkout After Adding Any Product To Cart', 'wplk' ), 
						array($this, 'wplk_checkbox_field_12_render'), 
						'wplk_options_page', 
						'wplk_options_section_product' 
					);

					add_settings_field( // Hide View Cart Notice After Adding Any Product To Cart
						'wplk_checkbox_field_6', 
						esc_html__( 'Hide "View Cart" Notice After Products Added To Cart', 'wplk' ), 
						array($this, 'wplk_checkbox_field_6_render'), 
						'wplk_options_page', 
						'wplk_options_section_product' 
					);

					add_settings_field( // Allow Only One Product In Cart At A Time
						'wplk_checkbox_field_1', 
						esc_html__( 'Allow Only One Product In Checkout At A Time', 'lk' ), 
						array($this, 'wplk_checkbox_field_1_render'), 
						'wplk_options_page', 
						'wplk_options_section_product' 
					);


					add_settings_field( // Add Product Removal Links To Products In Checkout
						'wplk_checkbox_field_16', 
						esc_html__( 'Add Removal Link To Any Products In Checkout', 'wplk' ), 
						array($this, 'wplk_checkbox_field_16_render'), 
						'wplk_options_page', 
						'wplk_options_section_product' 
					);

// Registration Options		
		add_settings_section( 
			'wplk_options_section_registration', 
			esc_html__( 'Registration Options', 'wplk' ), 
			array($this, 'wplk_settings_section3'), 
			'wplk_options_page'
		);


					add_settings_field( // Use Email for Username When Registrering
						'wplk_checkbox_field_5', 
						esc_html__( 'Use Email For Username With WooCommerce User Registration', 'wplk' ), 
						array($this, 'wplk_checkbox_field_5_render'), 
						'wplk_options_page', 
						'wplk_options_section_registration' 
					);	

					add_settings_field( // Add First And Last Name To WC Registration Form
						'wplk_checkbox_field_8', 
						esc_html__( 'Use First & Last Names With WooCommerce User Registration', 'wplk' ), 
						array($this, 'wplk_checkbox_field_8_render'), 
						'wplk_options_page', 
						'wplk_options_section_registration' 
					);			

// Other Options
		add_settings_section( 
			'wplk_options_section_other', 
			esc_html__( 'Other Options', 'wplk' ), 
			array($this, 'wplk_settings_section2'), 
			'wplk_options_page'
		);


					add_settings_field( // Disable Kadence Theme Field Colors In WC Checkout
						'wplk_checkbox_field_14', 
						esc_html__( 'Disable Kadence Theme Checkout Field Colors', 'wplk' ), 
						array($this, 'wplk_checkbox_field_14_render'), 
						'wplk_options_page', 
						'wplk_options_section_other' 
					);

					add_settings_field( // Link To WooCommerce Thank You Page Settings
						'wplk_setup_1', 
						esc_html__( 'Set The LaunchKit Global Thank You Page', 'wplk' ), 
						array($this, 'wplk_setup_1_render'), 
						'wplk_options_page', 
						'wplk_options_section_other' 
					);


}


/**
	 * Adds Admin Page Fields
	 *
	 * @since 4.0.8
	 *
	 * @return void
	 */


	public function wplk_checkbox_field_1_render(  ) { // One Proudct In Checkout

		$options = get_option( 'wplk_settings' );
		?>
		<input type='checkbox' name='wplk_settings[wplk_checkbox_field_1]' <?php checked( isset($options['wplk_checkbox_field_1']), 1 ); ?> value='1'>
		<?php

	}

	public function wplk_checkbox_field_2_render(  ) { // Disable Order Emails When Cart Value Is Zero
		$options = get_option( 'wplk_settings' );
		?>
		<input type='checkbox' name='wplk_settings[wplk_checkbox_field_2]' <?php checked( isset($options['wplk_checkbox_field_2']), 1 ); ?> value='1'>
		<?php

	}


	public function wplk_checkbox_field_3_render(  ) { // Hide Free Orders In My Account and Admin Edit Order
		$options = get_option( 'wplk_settings' );
		?>
		<input type='checkbox' name='wplk_settings[wplk_checkbox_field_3]' <?php checked( isset($options['wplk_checkbox_field_3']), 1 ); ?> value='1'>
		<?php

	}

	public function wplk_checkbox_field_4_render(  ) { // Disable links to product details pages
		$options = get_option( 'wplk_settings' );
		?>
		<input type='checkbox' name='wplk_settings[wplk_checkbox_field_4]' <?php checked( isset($options['wplk_checkbox_field_4']), 1 ); ?> value='1'>
		<?php

	}

	public function wplk_checkbox_field_5_render(  ) { // User Email For Username When Registering With WooCommerce
		$options = get_option( 'wplk_settings' );
		?>
		<input type='checkbox' name='wplk_settings[wplk_checkbox_field_5]' <?php checked( isset($options['wplk_checkbox_field_5']), 1 ); ?> value='1'>
		<?php

	}

	public function wplk_checkbox_field_6_render(  ) { // Hide View Cart Message After Adding Any Product To Cart
		$options = get_option( 'wplk_settings' );
		?>
		<input type='checkbox' name='wplk_settings[wplk_checkbox_field_6]' <?php checked( isset($options['wplk_checkbox_field_6']), 1 ); ?> value='1'>
		<?php

	}

	public function wplk_checkbox_field_7_render(  ) { // Remove Product From Slug For All WC Products
		$options = get_option( 'wplk_settings' );
		?>
		<input type='checkbox' name='wplk_settings[wplk_checkbox_field_7]' <?php checked( isset($options['wplk_checkbox_field_7']), 1 ); ?> value='1'>
		<?php

	}

	public function wplk_checkbox_field_8_render(  ) { // Add First And Last Name To WC Registration Form
		$options = get_option( 'wplk_settings' );
		?>
		<input type='checkbox' name='wplk_settings[wplk_checkbox_field_8]' <?php checked( isset($options['wplk_checkbox_field_8']), 1 ); ?> value='1'>
		<?php

	}

	public function wplk_text_field_1_render(  ) {  // Change Checkout Button Text (Global) When Cart Value Is Zero

		$options = get_option( 'wplk_settings' );
		?>
		<input type='text' name='wplk_settings[wplk_text_field_1]' value='<?php esc_html_e( $options['wplk_text_field_1'] );?>'>
		<?php

	}


	public function wplk_text_field_20_render(  ) {  // Set Default Width in px for LaunchKit Container Template
		$options = get_option( 'wplk_settings' );
		?>
		<input type='text' name='wplk_settings[wplk_text_field_20]' value='<?php esc_html_e( $options['wplk_text_field_20'] ); ?>'>
		<?php

	}

	public function wplk_checkbox_field_11_render(  ) { // Force WooCommerce Layout Into One Column
		$options = get_option( 'wplk_settings' );
		?>
		<input type='checkbox' name='wplk_settings[wplk_checkbox_field_11]' <?php checked( isset($options['wplk_checkbox_field_11']), 1 ); ?> value='1'>
		<?php

	}

	public function wplk_checkbox_field_12_render(  ) { // Redirect To Checkout After Adding Any Product To Cart
		$options = get_option( 'wplk_settings' );
		?>
		<input type='checkbox' name='wplk_settings[wplk_checkbox_field_12]' <?php checked( isset($options['wplk_checkbox_field_12']), 1 ); ?> value='1'>
		<?php

	}

	public function wplk_checkbox_field_13_render(  ) { // Disable WooCommerce Product Page Layout Functionality
		$options = get_option( 'wplk_settings' );
		?>
		<input type='checkbox' name='wplk_settings[wplk_checkbox_field_13]' <?php checked( isset($options['wplk_checkbox_field_13']), 1 ); ?> value='1'>
		<?php

	}


	public function wplk_checkbox_field_14_render(  ) { // Kadence Theme Field Colors
		$options = get_option( 'wplk_settings' );
		?>
		<input type='checkbox' name='wplk_settings[wplk_checkbox_field_14]' <?php checked( isset($options['wplk_checkbox_field_14']), 1 ); ?> value='1'>
		<?php

	}

	public function wplk_checkbox_field_15_render(  ) { // Show Checkout Page Even If No Products In Cart
		$options = get_option( 'wplk_settings' );
		?>
		<input type='checkbox' name='wplk_settings[wplk_checkbox_field_15]' <?php checked( isset($options['wplk_checkbox_field_15']), 1 ); ?> value='1'>
		<?php

	}

	public function wplk_checkbox_field_16_render(  ) { // Add Removal Link To Products In Checkout
		$options = get_option( 'wplk_settings' );
		?>
		<input type='checkbox' name='wplk_settings[wplk_checkbox_field_16]' <?php checked( isset($options['wplk_checkbox_field_16']), 1 ); ?> value='1'>
		<?php

	}

	public function wplk_setup_1_render(  ) { // Setup Global Thank You
		$options = get_option( 'wplk_settings' );
		?>
	<span class="wplk-setup-button"><a class="button" href="/wp-admin/admin.php?page=wc-settings" target=_blank">&#9881;</a></span>
		<?php

	}

	public function wplk_settings_section (  ) { 
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if (  is_plugin_active( 'launchflows/launchflows.php' ) ) {

			esc_html_e( 'LaunchKit is disabed when you have LaunchFlows enabled.', 'wplk' );

		}
	
	}


	public function wplk_options_page(  ) { //outputs the page contents
			?>
			<form id="LaunchKit" action='options.php' method='post'>

				<h1>LaunchKit</h1>
				<h4><a href="https://wplaunchify.com/launchkit" target="_blank">View Documentation</a></h4>
				<br/>

				<?php
		// only show section if LaunchFlows plugin is not enabled
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( ! is_plugin_active( 'launchflows/launchflows.php' ) ) {

				settings_fields( 'wplk_options_page' );
				do_settings_sections( 'wplk_options_page' );
				submit_button();

		} else {

			esc_html_e('LaunchKit is disabled when LaunchFlows is enabled.', 'wplk'); 

		}		
				?>

			</form>
			<?php
	}



/**
	 * Adds Admin Page Callback Logic
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */

	public function wplk_apply_settings() {
		
	  $options = get_option( 'wplk_settings' );



	// Only Allow One Prouduct In Cart At A Time
	  	if( isset($options['wplk_checkbox_field_1']) && $options['wplk_checkbox_field_1'] == '1' ) { 

	    add_filter( 'woocommerce_add_to_cart_validation', 'wplk_only_one_product_in_cart', 99, 2 );

	  	} 

	// Hide View Cart Notice After Product Is Added (useful for shop pages)
	  	if( isset($options['wplk_checkbox_field_6']) && $options['wplk_checkbox_field_6'] == '1' ) { 

	    add_filter( 'wc_add_to_cart_message_html', '__return_null' );  	

	 	}

	// Disable Product Links In Cart, Thank You Pages & Emails
		if( isset($options['wplk_checkbox_field_4']) && $options['wplk_checkbox_field_4'] == '1' ) { 

		// remove the filter 
		add_filter( 'woocommerce_order_item_permalink', '__return_false' ); // thank you pages and emails

		add_filter( 'woocommerce_cart_item_permalink', '__return_null' ); // in the cart

		}

	// Use Email For Username When Registering With WooCommerce
		if( isset($options['wplk_checkbox_field_5']) && $options['wplk_checkbox_field_5'] == '1' ) { 

		add_filter( 'woocommerce_new_customer_data', function( $data ) {
			
			$data['user_login'] = $data['user_email'];

			return $data;

			} );

		}

	// Add First And Last Name To WC Registration Form

	  	if( isset($options['wplk_checkbox_field_8']) && $options['wplk_checkbox_field_8'] == '1' ) { 

			add_action( 'woocommerce_register_form_start', 'wplk_extra_registration_fields' );
	 	}

			function wplk_extra_registration_fields() {
			?>
				  <style>.woocommerce-privacy-policy-text {display: inline-flex; margin-bottom: 1em;}</style>
			      <p class="form-row form-row-first">

			      <label for="reg_billing_first_name"><?php _e( 'First name', 'woocommerce' ); ?><span class="required">*</span></label><input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" value="<?php if ( ! empty( $_POST['billing_first_name'] ) ) esc_attr_e( $_POST['billing_first_name'] ); ?>" /></p>

			      <p class="form-row form-row-last">

			      <label for="reg_billing_last_name"><?php _e( 'Last name', 'woocommerce' ); ?><span class="required">*</span></label><input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" value="<?php if ( ! empty( $_POST['billing_last_name'] ) ) esc_attr_e( $_POST['billing_last_name'] ); ?>" /></p>

			<?php
			}



	// Force WooCommerce Default Layout To One Column

	  	if( isset($options['wplk_checkbox_field_11']) && $options['wplk_checkbox_field_11'] == '1' ) { 

			//apply css if wc checkout page 
	  		add_action('woocommerce_before_checkout_form','wplk_force_full_width_checkout', 10);
	  		function wplk_force_full_width_checkout(){
	  			$classes = get_body_class();
				if (in_array('woocommerce-checkout',$classes)) {
		?>

			  	<style>
			  	#customer_details, #order_review {
			    	width: 100%;
			   		float: none;
				}

				form.checkout #order_review_heading, 
				form.checkout .woocommerce-checkout-review-order {
				    padding-left: 0;
				}

				.theme-blocksy form.checkout.woocommerce-checkout {
				    grid-template-columns: repeat(1,1fr);
				}
				</style>
	<?php
	 			}
			}
	 	}


	// Add Option To Avoid WC Redirecting Empty Checkouts To Cart Page. Show checkout page even if cart is empty

		if( isset($options['wplk_checkbox_field_15']) && $options['wplk_checkbox_field_15'] == '1' ) { 

			add_filter( 'woocommerce_checkout_redirect_empty_cart', '__return_false' );

			add_filter( 'woocommerce_checkout_update_order_review_expired', '__return_false' ); // allows showing checkout even with empty cart
		}

	// Redirect To Checkout After Adding Any Product To Cart
		if( isset($options['wplk_checkbox_field_12']) && $options['wplk_checkbox_field_12'] == '1' ) { 

		add_filter( 'woocommerce_add_to_cart_redirect', 'wplk_add_to_cart_redirect' );

			function wplk_add_to_cart_redirect() {

	   			return wc_get_checkout_url();
			}

		}





// Kadence Checkout Colors
  	if( isset($options['wplk_checkbox_field_14']) && $options['wplk_checkbox_field_14'] == '1' ) { 

		//apply css if wc checkout page 
  		add_action('wplk_content','wplk_disable_kadence_checkout_colors');
  		function wplk_disable_kadence_checkout_colors(){
  			$classes = get_body_class();
			if (in_array('theme-kadence',$classes)) {
		?>

		  	<style>
				.wplk-form .woocommerce form .form-row.woocommerce-invalid .select2-container, 
				.wplk-form .woocommerce form .form-row.woocommerce-invalid input.input-text, 
				.wplk-form .woocommerce form .form-row.woocommerce-invalid select {
				    border-color: var(--global-gray-400)!important;
				}

				.wplk-form .woocommerce form .form-row.woocommerce-invalid label {
				    color: var(--global-palette5)!important;
				}

				.wplk-form .woocommerce form .form-row.woocommerce-validated .select2-container, 
				.wplk-form .woocommerce form .form-row.woocommerce-validated input.input-text, 
				.wplk-form .woocommerce form .form-row.woocommerce-validated select {
				   border-color: var(--global-gray-400)!important;	
				}

			</style>
		<?php
 			}
		}
 	}

// Add Removal Link To Products In Checkout
	if( isset($options['wplk_checkbox_field_16']) && $options['wplk_checkbox_field_16'] == '1' ) { 

		add_filter( 'woocommerce_checkout_cart_item_quantity', 'wplk_filter_woocommerce_checkout_cart_item_quantity', 10, 3 );
		add_action( 'wp_footer', 'wplk_action_wp_footer', 10, 0 );
		add_action( 'wp_ajax_woo_product_remove', 'wplk_product_remove' );
		add_action( 'wp_ajax_nopriv_woo_product_remove', 'wplk_product_remove' );

	}

// end wplk_apply_settings
return;
} // end wplk_apply_settings



} // end of LaunchKit Class
// Instantiate LaunchKit Class
new LaunchKit();