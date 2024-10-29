<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if( !class_exists('ACHP_Admin') ) {
    class ACHP_Admin{
        public function __construct(){
            add_action('init',[&$this,'intilize_cron_task']);
            add_action('achp_status_update',[&$this,'schedule_status_update']);
            add_action('woocommerce_api_ach_processing',[&$this,'webhook_callback']);
        }
        public function webhook_callback(){
            $request_body    = file_get_contents( 'php://input' );
            wc_get_logger()->debug(print_r($request_body,true),['source'=>'Webhook Response']);
        }
        public function schedule_status_update(){
            $statuses = ['wc-pending', 'wc-on-hold'];
            $orders = wc_get_orders(array('numberposts' => -1, 'status' => $statuses));
            $transaction_id = [];
            foreach ($orders as $order) {
                $id = $order->get_id();
                $fValue = get_post_meta($id, 'custom_transaction_id', true);
                if ($fValue != '') {
                    array_push($transaction_id, $fValue);
                }
            }
            $data = array_chunk($transaction_id, 10);
            $ACHP = new ACHP_Main_Class();
            $ACHP::set_credentials();
            foreach ($data as $d) {
                $remoteData = $ACHP::curlStatusPost($d);

                foreach ($remoteData as $row) {
                    foreach ($orders as $order) {
                        if (get_post_meta($order->get_id(), 'custom_transaction_id', true) == $row['paymentRefID']) {
                            if (!empty($row['status']['Message'])) {
                                if ($row['status']['Message'] == 'Pending') {
                                    $order->update_status('wc-on-hold');
                                } elseif ($row['status']['Message'] == 'New') {
                                    $order->update_status('wc-pending');
                                } elseif ($row['status']['Message'] == 'At SB') {
                                    $order->update_status('wc-processing');
                                } elseif ($row['status']['Message'] == 'File Transmitted') {
                                    $order->update_status('wc-processing');
                                } elseif ($row['status']['Message'] == 'SB Remit') {
                                    $order->update_status('wc-processing');
                                } elseif ($row['status']['Message'] == 'Returned') {
                                    $order->update_status('wc-failed');
                                } elseif ($row['status']['Message'] == 'SB Hold') {
                                    $order->update_status('wc-on-hold');
                                } elseif ($row['status']['Message'] == 'Hold') {
                                    $order->update_status('wc-on-hold');
                                } elseif ($row['status']['Message'] == 'Deleted') {
                                    $order->update_status('wc-cancelled');
                                } elseif ($row['status']['Message'] == 'SB Void') {
                                    $order->update_status('wc-cancelled');
                                } else {
                                    $order->update_status('wc-on-hold');
                                }
                            } else {
                                $order->update_status('wc-on-hold');
                            }
                        }
                    }
                }
            }

        }
        public function intilize_cron_task(){
            if ( false === as_next_scheduled_action( 'achp_status_update' ) ) {
                as_schedule_recurring_action( strtotime( '+1 minutes' ),  60,'achp_status_update' ,[],__('ACHP status update','sbh') );
            }
        }
    }
    new ACHP_Admin();
}