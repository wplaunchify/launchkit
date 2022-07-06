<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
    * LaunchKitCore Class
    *
    *
    * @since 1.0.0
    */

class WPLaunchKitRegistration {


/**
   * Constructor
   *
   * @since 1.0.0
   * @access public
   */

   public function __construct() {

    // registration form

    add_shortcode( 'wplk-registration', array ($this, 'wplk_registration' ) );

   } // end construct


/**
   * Registration Form
   * 
   * @since 1.0.0
   * @access public
   */

   function wplk_registration($redirection_url) {
      
      if ( is_admin() ) return;

      if (is_user_logged_in() ){ 
      
         echo _e('You are already registered.', 'lk');
         
         exit;
      
      }
       
      ob_start();

      /* Start WooCommmerce Form */    

         do_action( 'woocommerce_before_customer_login_form' );    
         ?>
               <div class="woocommerce">
               <style>.woocommerce-privacy-policy-text {margin: 2em auto;}</style>
                     <form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?> >

                        <?php do_action( 'woocommerce_register_form_start' ); ?>
                
                        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                           <label for="reg_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?> <span class="required">*</span></label>
                           <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" /><?php // @codingStandardsIgnoreLine ?>
                        </p>
                
                        <?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
                
                           <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                              <label for="reg_username"><?php esc_html_e( 'Username', 'woocommerce' ); ?> <span class="required">*</span></label>
                              <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" /><?php // @codingStandardsIgnoreLine ?>
                           </p>
                
                        <?php endif; ?>
                
                        <?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
                
                           <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                              <label for="reg_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?> <span class="required">*</span></label>
                              <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" />
                           </p>
                
                        <?php else : ?>
                
                           <p><?php esc_html_e( 'A password will be sent to your email address.', 'woocommerce' ); ?></p>
                
                        <?php endif; ?>
                
                

                       <?php do_action( 'woocommerce_register_form' ); ?>

                        <p class="woocommerce-FormRow form-row">
                           <?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
                           <button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
                        </p>
                
                        <?php do_action( 'woocommerce_register_form_end' ); ?>
                
                     </form>
               </div>
         <?php
      /* End WooCommmerce Form */    
          $output = ob_get_contents();   
          ob_end_clean();   
          return $output;
   }

// save
} // instantiates class
new WPLaunchKitRegistration;