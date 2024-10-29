<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class ACHP_Subscription_Compact extends Woo_ACH_Processing {
    public function __construct() {
        parent::__construct();
        add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, [ $this, 'scheduled_subscription_payment' ], 10, 2 );
			add_action( 'wcs_resubscribe_order_created', [ $this, 'delete_resubscribe_meta' ], 10 );
    }
    public function scheduled_subscription_payment( $amount_to_charge, $renewal_order ) {
            $order_id=$renewal_order->get_id();
            $paymentSource=get_post_meta($order_id,'_achp_payment_source',true);
            if(!empty($paymentSource)){
                $paymentRequestArray=[];
                $paymentRequestArray['paymentMethodobjectId']=$paymentSource;
                $paymentRequestArray['Amount']=$amount_to_charge;
                $accountType=get_post_meta($order_id,'_achp_account_type',true);
                if(!empty($accountType)){
                //    $paymentRequestArray['actionType']=$accountType;
                $paymentRequestArray['actionType']='2';
                }

                $remoteDataPay=ACHP_Main_Class::curlPost('Ensemble/CreatePayment',$paymentRequestArray);
                if(!empty($remoteDataPay['status']['ResponseCode']) && $remoteDataPay['status']['ResponseCode']=='Ok'){
                    $renewal_order->payment_complete();
                }else{
                    $renewal_order->update_status( 'failed' );
                }
            }
    }
    public function delete_resubscribe_meta( $resubscribe_order ) {
		delete_post_meta( $resubscribe_order->get_id(), '_achp_payment_source' );
		delete_post_meta( $resubscribe_order->get_id(), '_achp_account_type' );
	}

}
new ACHP_Subscription_Compact();
