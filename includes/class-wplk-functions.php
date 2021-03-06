<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}


/**
     * Hides the "product added to cart" message from all checkouts
     *
     * @since 1.0.0
     *
     * @return void
     */

    function wplk_woo_custom_add_to_cart( $cart_item_data ) {
        
        global $woocommerce;

        $woocommerce->cart->empty_cart();

        // Do nothing with the data and return
        return $cart_item_data;
    }


/**
     * Global Allow Only One Product In Cart At A Time
     *
     * @since 1.0.0
     *
     * @return void
     */
  
    function wplk_only_one_product_in_cart( $passed, $added_product_id ) {

       wc_empty_cart();
       
       return $passed;
    
    }


/**
     * Add Product Removal Link To Checkout Order Review
     *
     * @since 1.0.0
     *
     * @return void
     */

    // Concatenate remove link after item qty
    function wplk_filter_woocommerce_checkout_cart_item_quantity( $item_qty, $cart_item, $cart_item_key ) {

        $remove_link = apply_filters('woocommerce_cart_item_remove_link',
        
            sprintf(
                '<a href="#" class="remove" style="float:left; margin-right:5px;" aria-label="%s" data-product_id="%s" data-product_sku="%s" data-cart_item_key="%s">&times;</a>',
                esc_html__( 'Remove this item', 'woocommerce' ),
                esc_attr( $cart_item['product_id'] ),
                esc_attr( $cart_item['data']->get_sku() ),
                esc_attr( $cart_item_key )
        ),
        $cart_item_key );

        // Return
        return $item_qty . $remove_link;
    }

    // jQuery - Ajax script
    function wplk_action_wp_footer() {
        // Only checkout page
        if ( ! is_checkout() )
            return;
        ?>
        <script type="text/javascript">
        jQuery( function($) {
            $( 'form.checkout' ).on( 'click', '.cart_item a.remove', function( e ) {
                e.preventDefault();
                
                var cart_item_key = $( this ).attr( "data-cart_item_key" );
                
                $.ajax({
                    type: 'POST',
                    url: wc_checkout_params.ajax_url,
                    data: {
                        'action': 'woo_product_remove',
                        'cart_item_key': cart_item_key,
                    },
                    success: function ( result ) {
                        $( 'body' ).trigger( 'update_checkout' );
                        //console.log( 'response: ' + result );
                    },
                    error: function( error ) {
                        //console.log( error );
                    }
                });
            });
        });
        </script>
        <?php

    }

    // php Ajax
    function wplk_product_remove() { 

        if ( isset( $_POST['cart_item_key'] ) ) {

            // Get cart item key and sanitize
            $cart_item_key = sanitize_key( $_POST['cart_item_key'] );
            
            // Remove cart item
            WC()->cart->remove_cart_item( $cart_item_key );
        }
        
        // Alway at the end (to avoid server error 500)
        die();
    }


/**
     * Adds An Empty WC Cartsyntax to be used on any url (see also shortcode button in class-wplk-core)
     * example: ?emptycart=yes will empty any url
     *
     * @since 1.0.0
     *
     * @return void
     */

    add_action( 'init', 'wplk_empty_cart_action', 20 );
    function wplk_empty_cart_action() { // applies on any page where string is present ?emptycart=yes

      if (! is_admin() ) {

        if ( isset($_GET['emptycart'] ) ) {
        
                // Sanitize empty cart string
                $str = sanitize_text_field($_GET['emptycart']);

                if ( isset( $str ) && 'yes' === esc_html( $str ) ) {

                    WC()->cart->empty_cart(true);
                    
                    $referer  = wp_get_referer() ? esc_url( remove_query_arg( 'emptycart' ) ) : wc_get_cart_url();
                    
                    wp_safe_redirect( $referer );
                
                }

        }        
      
      } // end admin check  
    
    }



/**
     * Removes product from cart based on ID (see also shortcode button in class-wplk-core)
     * example: ?remove-product=1234 will remove product with ID of 1234
     *
     * @since 1.0.0
     *
     * @return void
     */

    add_action( 'wp_head', 'wplk_remove_product_action');
    function wplk_remove_product_action() { // applies on any page where string is present ?remove-product=1234

      if (! is_admin() ) {

        if( isset( $_GET['remove-product'] ) ){
        
            // Sanitize remove product string
            $str = sanitize_text_field($_GET['remove-product']);
                
            $product_id = esc_attr( $str );

            $product_cart_id = WC()->cart->generate_cart_id( $product_id );

            $cart_item_key = WC()->cart->find_product_in_cart( $product_cart_id );

            if ( $cart_item_key ) WC()->cart->remove_cart_item( $cart_item_key );
        
        }

      } // end admin check  

    }

/**
     * Adds A WooCommerce Coupon Code To URL
     * example: ?wplkcoupon=123456 will apply coupon via URL string
     *
     * @since 1.0.0
     *
     * @return void
     */
    
    add_action('init', 'wplk_get_custom_coupon_code_to_session');
    function wplk_get_custom_coupon_code_to_session(){

        if (! is_admin() ) {

            if( isset( $_GET['wplkcoupon'] ) ){

                // Sanitize coupon code string
                $str = sanitize_text_field($_GET['wplkcoupon']);
                
                // Ensure that customer session is started
                if( !WC()->session->has_session() )

                    WC()->session->set_customer_session_cookie(true);

                // Check, sanitize and register coupon code in a custom session variable
                $coupon_code = sanitize_text_field( WC()->session->get('wplkcoupon'));

                if(empty($coupon_code)){
                    
                    $coupon_code = esc_attr( $str );
                    
                    WC()->session->set( 'wplkcoupon', $coupon_code ); // Set the coupon code in session
                
                }
            }
        }
    }

    add_action( 'woocommerce_before_checkout_form', 'wplk_add_discout_to_checkout', 10, 0 );
    function wplk_add_discout_to_checkout( ) {

        // not on admin
        if ( !is_admin() ) {	

            if( isset( $_GET['wplkcoupon'] ) ){
    
                // Set coupon code and sanitize
                $coupon_code = sanitize_text_field( WC()->session->get('wplkcoupon') );
                
                if ( ! empty( $coupon_code ) && ! WC()->cart->has_discount( $coupon_code ) ){

                    WC()->cart->add_discount( $coupon_code ); // apply the coupon discount
                    
                    WC()->session->__unset('wplkcoupon'); // remove coupon code from session
                
                }
    
            }
              
        } 
    
    }



/**
    * Add multiple simple products for a "quick bundle"
    *
    *
    * @since 1.0.0
    * @access public
    */

    // stackoverflow.com/questions/42570982/adding-multiple-items-to-woocommerce-cart-at-once
    function wplk_add_multiple_simple_products_to_cart() {

        if (! is_admin() ) {
        
            if (isset($_REQUEST['add-to-cart']) ){

                // Sanitize empty cart string
                $str = sanitize_text_field($_REQUEST['add-to-cart']);


                // make sure WC is installed, and add-to-cart query arg exists, and contains at least one comma.
                if ( ! class_exists( 'WC_Form_Handler' ) || empty( $str ) || false === strpos( $str, ',' ) ) {
                    return;
                }

                // remove WooCommerce's hook, as it's useless (doesn't handle multiple products).
                remove_action( 'wp_loaded', array( 'WC_Form_Handler', 'add_to_cart_action' ), 20 );

                $product_ids = explode( ',', $str );

                $count       = count( $product_ids );
                
                $number      = 0;

                foreach ( $product_ids as $product_id ) {

                    if ( ++$number === $count ) {

                        // Ok, final item, let's send it back to woocommerce's add_to_cart_action method for handling
                        // note: this does not require sanitizing as it internal from our function
                        $_REQUEST['add-to-cart'] = $product_id;

                        return WC_Form_Handler::add_to_cart_action();
                    }

                    $product_id        = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $product_id ) );

                    $was_added_to_cart = false;
                    
                    $adding_to_cart    = wc_get_product( $product_id );

                    if ( ! $adding_to_cart ) {

                        continue;
                    
                    }

                    $add_to_cart_handler = apply_filters( 'woocommerce_add_to_cart_handler', $adding_to_cart->product_type, $adding_to_cart );

                    // works only with simple products add_to_cart_handler
                    if ( 'simple' !== $add_to_cart_handler ) {

                        continue;
                    
                    }

                    // Sanitize and sets default quantity of all products to 1
                    $qty = sanitize_text_field( $_REQUEST['quantity'] ); 

                    $quantity          = empty( $qty ) ? 1 : wc_stock_amount( $qty );
                    
                    $passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );

                    if ( $passed_validation && false !== WC()->cart->add_to_cart( $product_id, $quantity ) ) {

                        wc_add_to_cart_message( array( $product_id => $quantity ), true );
                    
                    }

                }
            }
        }          
    }

     // fire before the WC_Form_Handler::add_to_cart_action callback.
     add_action( 'wp_loaded', 'wplk_add_multiple_simple_products_to_cart', 15 );


/**
   * WC Subscriptions - Strip the HTML tags of the custom price string fields
   *
   * @since 1.0.0
   *
   * @return void
   */

    remove_filter( 'wcs_custom_price_string_value', 'strip_tags_of_custom_price_value', 10, 1 );



/**
     * Name Your Price By Adding donation=xxxx to url string
     * see: stackoverflow.com/questions/24731118/woocommerce-change-price-while-a-to-cart
     * see: stackoverflow.com/questions/57505646/woocommerce-custom-cart-item-price-on-add-to-cart-via-the-url
     * adds donation on top of any product price & may be repeated for any product added to cart
     *
     * @since 1.0.0
     *
     * @return void
     */

    add_filter( 'woocommerce_add_cart_item_data', 'wplk_catch_and_save_submited_donation', 10, 2 );
    function wplk_catch_and_save_submited_donation( $cart_item_data, $product_id ){

        if (! is_admin() ) {
        
            if( isset($_REQUEST['donation']) ) {
     
                // Set donation and sanitize
                $str = sanitize_text_field($_REQUEST['donation']);

                // Get the WC_Product Object
                $product = wc_get_product( $product_id );

                // Get and set the product active price
                $cart_item_data['active_price'] = (float) $product->get_price();

                // Get the donation amount and set it
                $cart_item_data['donation'] = (float) esc_attr( $str );

                $cart_item_data['unique_key'] = md5( microtime().rand() ); // Make each item unique
            }

            return $cart_item_data;
        }    
    }



    add_action( 'woocommerce_before_calculate_totals', 'wplk_add_donation_to_item_price', 10, 1);
    function wplk_add_donation_to_item_price( $cart ) {

        if ( is_admin() && ! defined( 'DOING_AJAX' ) )
            return;

        // Avoiding hook repetition (when using price calculations for example)
        if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 )
            return;

        // Loop through cart items
        foreach ( $cart->get_cart() as $item ) {
        
            // Use either the donation, if set and at least 1, or the product price (set it to minimum required)
            if ( isset( $item['donation']) && ( $item['donation'] >= 1 ) && isset( $item['active_price']) ) {
        
                $item['data']->set_price( $item['donation'] ); // if donation is set, price equals donation
        
            } else {
        
                $item = false;
        
                if(isset($item['active_price'])){ // conditional check that active_price is set and not a null value
        
                $item['data']->set_price( $item['active_price'] ); // otherwise, price equals active price
        
                }
        
            }

        }
    }


/**
     * Registration Redirection
     *
     * Redirect to my account after registration if using registration shortcode
     * @since 1.0.0
     *
     * @return void
     */
 
    add_action( 'template_redirect', 'wplk_register_redirect', 5 );

    function wplk_register_redirect() {
        global $post;

         if ( has_shortcode( $post->post_content, 'wplk-registration' )  && is_user_logged_in() ) {

                wp_redirect( get_permalink( wc_get_page_id( 'myaccount' ) ) );

                exit;
        }

    }



/**
     * Custom Thank You Page Redirection
     *
     * Redirects Any Product With One Of Three Reserved Categories To Custom Thank You Pages
     * 
     * Setup three product categories and pages with (wplk1, wplk2, wplk3) as the slugs (Names can be anything)
     *
     * @since 1.0.1
     *
     * @return void
     */

    add_action( 'template_redirect', 'wplk_custom_cat_redirect' );
    function wplk_custom_cat_redirect() {

        // Only on "order received" page
        if( is_wc_endpoint_url('order-received') ) {
            global $wp;
            $order = wc_get_order( absint($wp->query_vars['order-received']) ); // Get the Order Object
            $category_found = false;

            // Loop through order items
            foreach( $order->get_items() as $item ){
                if( has_term( 'wplk1', 'product_cat', $item->get_product_id()  ) ) {
                    $category_found = wplk1;
                    break;
                }
                if( has_term( 'wplk2', 'product_cat', $item->get_product_id()  ) ) {
                    $category_found = wplk2;
                    break;
                }
                if( has_term( 'wplk3', 'product_cat', $item->get_product_id()  ) ) {
                    $category_found = wplk3;
                    break;
                }
            }

            if( $category_found === wplk1 ) {
                // Redirect For wplk1
                wp_redirect( get_site_url(null, '/wplk1/', 'https') );
                exit(); // Always exit
            }
            if( $category_found === wplk2 ) {
                //  Redirect For wplk2
                wp_redirect( get_site_url(null, '/wplk2/', 'https') );
                exit(); // Always exit
            }
                if( $category_found === wplk3 ) {
                // Redirect For wplk3
                wp_redirect( get_site_url(null, '/wplk3/', 'https') );
                exit(); // Always exit
            }
        }
    }