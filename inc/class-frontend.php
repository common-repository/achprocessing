<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if( !class_exists('ACHP_FrontEnd') ) {
    class ACHP_FrontEnd{
        public function __construct(){
            if(class_exists('WC_Subscriptions_Order')){
            require_once( ACHP_PLUGIN_DIR.'/inc/class-subscription.php' );
            }
            add_action('woocommerce_after_checkout_validation',[&$this,'achp_validation'],10,2);
        }
        public function achp_validation( $fields, $errors ){
            $paymentMethod=$fields['payment_method'];
            if($paymentMethod=='ach_processing'){
                $paymentType=$_POST['ach_processing-payment-type'];
                if($paymentType=='bank'){
                    if(empty($_POST['ach_processing-holder-name'])){
                        $errors->add( 'validation', __('Account holder name is required',ACHP_TEXT_DOMAIN),'ach_processing-holder-name' );
                    }elseif(empty($_POST['ach_processing-account-number'])){
                        $errors->add( 'validation', __('Account number is required',ACHP_TEXT_DOMAIN) );  
                    }elseif(empty($_POST['ach_processing-routing-number'])){
                        $errors->add( 'validation', __('Routing number is required',ACHP_TEXT_DOMAIN) );  
                    }
                }else{
                    if(empty($_POST['ach_processing-card-name'])){
                        $errors->add( 'validation', __('Card holder name is required',ACHP_TEXT_DOMAIN) );
                    }elseif(empty($_POST['ach_processing-card-number'])){
                        $errors->add( 'validation', __('Card number is required',ACHP_TEXT_DOMAIN) );  
                    }elseif(empty($_POST['ach_processing-card-expiry'])){
                        $errors->add( 'validation', __('Card expiry is required',ACHP_TEXT_DOMAIN) );  
                    }elseif(empty($_POST['ach_processing-card-cvc'])){
                        $errors->add( 'validation', __('Card CVC is required',ACHP_TEXT_DOMAIN) );  
                    }
                }
               // print_r()
            }
            
        }
    }
    new ACHP_FrontEnd();
}