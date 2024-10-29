<?php
/*
Plugin Name:ACH Processing
Plugin URI:https://extest.achprocessing.com/ach/login.aspx
Description: ACH Processing
Version: 1.1.2
Requires at least: 4.5
Tested up to: 5.7.1
WC requires at least: 3.5
WC tested up to: 5.2.2
Author: ACH Processing
Author URI: https://achprocessing.com/
Text Domain: achp
Domain Path: /languages/
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if (!defined('ACHP_PLUGIN_DIR'))
    define( 'ACHP_PLUGIN_DIR', dirname(__FILE__) );
if (!defined('ACHP_PLUGIN_ROOT_PHP'))
    define( 'ACHP_PLUGIN_ROOT_PHP', dirname(__FILE__).'/'.basename(__FILE__)  );
if(!defined('ACHP_PLUGIN_ABSOLUTE_PATH'))
    define('ACHP_PLUGIN_ABSOLUTE_PATH',plugin_dir_url(__FILE__));
if (!defined('ACHP_PLUGIN_ADMIN_DIR'))
    define( 'ACHP_PLUGIN_ADMIN_DIR', dirname(__FILE__) . '/admin' );
if (!defined('ACHP_TEXT_DOMAIN'))
    define( 'ACHP_TEXT_DOMAIN', 'achp' );

    if( !class_exists('ACHP_Main_Class') ) {
        class ACHP_Main_Class{
            public static $url='https://extest.achprocessing.com/ACHEnsemble/api/Ensemble/';
            public static $checkStatusUrl = 'https://localhost:44320/api/Ensemble/PollAchPayments'; //addcode
            private static $apiKey='';
            private static $apiCardKey='';
            private static $userName='';
            private static $passWord='';
            private static $api_endpoint='';
            public function __construct() {
                require_once( ACHP_PLUGIN_DIR. '/libraries/action-scheduler/action-scheduler.php' );
                if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
                    add_filter('plugin_action_links_'.plugin_basename(__FILE__), [&$this,'add_setting_link']);
                    add_action('plugin_loaded',[&$this,'load_main_gateway_class'],0);
                    require_once( ACHP_PLUGIN_DIR.'/inc/class-frontend.php' );
                }else{
                    add_action('admin_notices', [&$this, 'add_notice_for_woocommerce']);
                }

            }
            public function add_setting_link($links){

                array_unshift($links, '<a href="' .
                    admin_url( 'admin.php?page=wc-settings&tab=checkout&section=ach_processing' ) .
                    '">' . __('Settings',ACHP_TEXT_DOMAIN) . '</a>');
                return $links;
            }
            public function add_notice_for_woocommerce(){
                ?>
                <div class="notice notice-error">
                <p><strong><?php _e( 'ACH Processing requires the WooCommerce plugin to be installed and active.', ACHP_TEXT_DOMAIN ); ?></strong></p>

            </div>
                <?php
            }
            public function load_main_gateway_class(){
                require_once( ACHP_PLUGIN_ADMIN_DIR.'/class-admin.php' );
                if (!class_exists('WC_Payment_Gateway')) return;
                require_once( ACHP_PLUGIN_DIR.'/inc/wc-ach-processing.php' );
                add_filter('woocommerce_payment_gateways',[&$this,'add_ach_processing_gateway']);
            }
            public function add_ach_processing_gateway($methods){
                $methods[] = 'Woo_ACH_Processing';
                return $methods;
            }

            public static function set_credentials(){
                $options = get_option( 'woocommerce_ach_processing_settings' );
                $testmode=false;
                if(isset($options['testmode'],
                $options['test_api_key'],
                $options['api_key'],
                $options['test_api_card_key'],
                $options['api_card_key'],
                $options['test_user_name'],
                $options['user_name'],
                $options['test_password'],
                $options['password'],
                $options['test_api_endpoint'],
                $options['api_endpoint']

                )){
                    if('yes'===$options['testmode']){$testmode=true;}
                    self::$apiKey=$testmode?$options['test_api_key']:$options['api_key'];
                    self::$apiCardKey=$testmode?$options['test_api_card_key']:$options['api_card_key'];
                    self::$userName=$testmode?$options['test_user_name']:$options['user_name'];
                    self::$passWord=$passWord=$testmode?$options['test_password']:$options['password'];
                    self::$api_endpoint=$api_endpoint=$testmode?$options['test_api_endpoint']:$options['api_endpoint'];
                    self::$url = $api_endpoint=$testmode?$options['test_api_endpoint']:$options['api_endpoint'];
                }else{
                    self::$apiKey='';
                    self::$apiCardKey='';
                    self::$userName='';
                    self::$passWord='';
                    self::$api_endpoint='';
                }
            }

            public static function generateAccessToken($isCard = false){
                $url=self::$url.'Ensemble/EnsembleLogin';

                if(empty(self::$userName)||empty(self::$passWord)||empty(self::$apiKey))return false;
                $remoteResponse = wp_remote_post($url, [
                    'headers'     => ['Content-Type' => 'application/json; charset=utf-8'],
                    'body'        => json_encode([
                        'Apikey'=> $isCard ? self::$apiCardKey : self::$apiKey,
                        'Username'=>self::$userName,
                        'Password'=>self::$passWord
                    ]),
                    'method'      => 'POST',
                    'data_format' => 'body',
                ]);
                $remoteResponseBody=wp_remote_retrieve_body($remoteResponse);
                $remoteResponseArray=json_decode($remoteResponseBody,true);
                if(!empty($remoteResponseArray['AuthenticationToken'])){
                    return [
                        'token'=>$remoteResponseArray['AuthenticationToken'],
                        'expire'=>$remoteResponseArray['expireDate']
                    ];
                }
                return false;
            }
            public static function curlPost($methd,$postedValues=[],$isCard = false){
                $url=self::$url.$methd;
                if(empty($postedValues))return false;
                $remoteResponse = wp_remote_post($url, [
                    'headers'     => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.self::generateAccessToken($isCard)['token']
                    ],
                    'body'        => json_encode($postedValues),
                    'method'      => 'POST',
                    'data_format' => 'body',
                ]);

                $remoteResponseBody=wp_remote_retrieve_body($remoteResponse);
                wc_get_logger()->debug(print_r($remoteResponseBody,true),['source'=>'Debug Response']);
                return json_decode($remoteResponseBody,true);
            }

            public static function curlStatusPost($transactionIds)
            {
                $default_url = self::$checkStatusUrl;
                $options = get_option( 'woocommerce_ach_processing_settings' );
                $url = $options['status_check_api_endpoint'] ?  $options['status_check_api_endpoint'] : $default_url ;

                $remoteResponse = wp_remote_post($url, [
                    'headers'     => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.self::generateAccessToken()['token'],
                    ],
                    'body'        => json_encode($transactionIds),
                    'method'      => 'POST',
                    'data_format' => 'json',
                ]);

                $remoteResponseBody=wp_remote_retrieve_body($remoteResponse);

                return json_decode($remoteResponseBody,true);

            }
        }
        new ACHP_Main_Class();
    }
