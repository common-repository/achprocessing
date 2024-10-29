<?php

require('plugin-start.php');


if (!defined('ABSPATH')) exit; // Exit if accessed directly
class Woo_ACH_Processing extends WC_Payment_Gateway
{
    public $id = '';
    public $testmode;
    public $user_name;
    public $password;
    public $api_key;
    public $api_card_key;
    public $api_endpoint;
    public $status_check_api_endpoint; //addcode

    public function __construct()
    {
        $this->id = 'ach_processing';
        $this->method_title = __('ACH Processing', ACHP_TEXT_DOMAIN);
        $this->method_description = __('ACH Processing works by adding payment fields on the checkout and then sending the details to API for verification.', ACHP_TEXT_DOMAIN);
        $this->supports = [
            'products',
            'refunds',
            'tokenization',
            'add_payment_method',
            'subscriptions',
            'subscription_cancellation',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_amount_changes',
            'subscription_date_changes',
            'subscription_payment_method_change',
            'subscription_payment_method_change_customer',
            'subscription_payment_method_change_admin',
            'multiple_subscriptions',
            'pre-orders',
        ];
        $this->init_form_fields();
        $this->init_settings();
        ACHP_Main_Class::set_credentials($this->api_key, $this->api_card_key, $this->user_name, $this->password, $this->api_endpoint);
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->creditcard_enabled = $this->get_option('creditcard_enabled');
        $this->bankaccount_enabled = $this->get_option('bankaccount_enabled');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->api_endpoint = $this->testmode ? $this->get_option('test_api_endpoint') : $this->get_option('api_endpoint');
        $this->api_key = $this->testmode ? $this->get_option('test_api_key') : $this->get_option('api_key');
        $this->api_card_key = $this->testmode ? $this->get_option('test_api_card_key') : $this->get_option('api_card_key');
        $this->user_name = $this->testmode ? $this->get_option('test_user_name') : $this->get_option('user_name');
        $this->password = $this->testmode ? $this->get_option('test_password') : $this->get_option('password');
        $this->bank_pay_text = $this->get_option('bank_pay_text');
        $this->credit_card_pay_text = $this->get_option('credit_card_pay_text');
        $this->title = $this->get_option('title');
        $this->status_check_api_endpoint = $this->get_option('status_check_api_endpoint'); //addcode

        add_action('wp_enqueue_scripts', [$this, 'payment_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }


    public function payment_fields()
    {
        ?>
        <?php echo wpautop(wp_kses_post($this->description)) ?>

        <div id="achp_payement_cotainer">
            <?php if ($this->bankaccount_enabled == "yes"): ?>
                <header data-type="bank"
                        class="accrdion-header active"><?php echo sanitize_text_field($this->bank_pay_text) ?></header>
                <div class="accrdion-content open">
                    <?php echo $this->get_bank_account_fields(); ?>
                </div>
            <?php endif; ?>
            <?php if ($this->creditcard_enabled == "yes"): ?>
                <header data-type="card"
                        class="accrdion-header"><?php echo sanitize_text_field($this->credit_card_pay_text) ?></header>
                <div class="accrdion-content">
                    <?php echo $this->get_credit_card_fields() ?>
                </div>
            <?php endif; ?>
            <div class="achp_error_container"></div>
            <input type="hidden" name="<?php echo esc_attr($this->id) ?>-payment-type"
                   id="<?php echo esc_attr($this->id) ?>-payment-type" value="bank"/>
        </div>
        <?php
    }

    public function get_bank_account_fields()
    {
        ob_start();
        ?>
        <p class="form-row form-row-wide">
            <label for="<?php echo esc_attr($this->id) ?>-holder-name"><?php echo __('Account holder name', ACHP_TEXT_DOMAIN) ?>
                '&nbsp;<span class="required">*</span></label>
            <input placeholder="<?php echo __('Name', ACHP_TEXT_DOMAIN) ?>"
                   name="<?php echo esc_attr($this->id) ?>-holder-name"
                   id="<?php echo esc_attr($this->id) ?>-holder-name" class="input-text"/>
        </p>
        <p class="form-row form-row-first">
            <label for="<?php echo esc_attr($this->id) ?>-account-number"><?php echo __('Account number', ACHP_TEXT_DOMAIN) ?>
                '&nbsp;<span class="required">*</span></label>
            <input placeholder="<?php echo __('Account No.', ACHP_TEXT_DOMAIN) ?>"
                   name="<?php echo esc_attr($this->id) ?>-account-number"
                   id="<?php echo esc_attr($this->id) ?>-account-number" class="input-text"/>
        </p>
        <p class="form-row form-row-last">
            <label for="<?php echo esc_attr($this->id) ?>-routing-number"><?php echo __('Routing number', ACHP_TEXT_DOMAIN) ?>
                '&nbsp;<span class="required">*</span></label>
            <input placeholder="<?php echo __('Routing No.', ACHP_TEXT_DOMAIN) ?>"
                   name="<?php echo esc_attr($this->id) ?>-routing-number"
                   id="<?php echo esc_attr($this->id) ?>-routing-number" class="input-text"/>
        </p>
        <div class="clearfix"></div>
        <p class="form-row form-row-wide">
            <label for="<?php echo esc_attr($this->id) ?>-account-type"><?php echo __('Account type', ACHP_TEXT_DOMAIN) ?>
                '&nbsp;<span class="required">*</span></label>
            <select class="" name="<?php echo esc_attr($this->id) ?>-account-type"
                    id="<?php echo esc_attr($this->id) ?>-account-type">
                <option value="1"><?php echo __('Checking', ACHP_TEXT_DOMAIN) ?></option>
                <option value="2"><?php echo __('Saving', ACHP_TEXT_DOMAIN) ?></option>
            </select>
        </p>
        <div class="achp_error_container_bank"></div>

        <?php
        return ob_get_clean();
    }

    public function get_credit_card_fields()
    {
        ob_start();
        ?>
        <p class="form-row form-row-wide card-field card-name-validate">
            <label for="<?php echo esc_attr($this->id) ?>-card-name"><?php echo esc_html__('Card holder name', ACHP_TEXT_DOMAIN) ?>
                '&nbsp;<span class="required">*</span></label>
            <input id="<?php echo esc_attr($this->id) ?>-card-name" name="<?php echo esc_attr($this->id) ?>-card-name"
                   data-type="name" class="input-text achp_card_element"
                   placeholder="<?php echo __('Name', ACHP_TEXT_DOMAIN) ?>"/>
        </p>
        <p class="form-row form-row-wide">
            <label for="<?php echo esc_attr($this->id) ?>-card-number"><?php echo esc_html__('Card number', 'woocommerce') ?>
                '&nbsp;<span class="required">*</span></label>
            <input id="<?php echo esc_attr($this->id) ?>-card-number"
                   name="<?php echo esc_attr($this->id) ?>-card-number" data-type="number"
                   class="input-text achp_card_element wc-credit-card-form-card-number" inputmode="numeric"
                   autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel"
                   placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;"/>
        </p>
        <p class="form-row form-row-first">
            <label for="<?php echo esc_attr($this->id) ?>-card-expiry"><?php echo esc_html__('Expiry (MM/YY)', 'woocommerce') ?>
                '&nbsp;<span class="required">*</span></label>
            <input id="<?php echo esc_attr($this->id) ?>-card-expiry"
                   name="<?php echo esc_attr($this->id) ?>-card-expiry" data-type="expiry"
                   class="input-text achp_card_element wc-credit-card-form-card-expiry" inputmode="numeric"
                   autocomplete="cc-exp" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel"
                   placeholder="<?php echo esc_attr__('MM / YY', 'woocommerce') ?>"/>
        </p>
        <p class="form-row form-row-last">
            <label for="<?php echo esc_attr($this->id) ?>-card-cvc"><?php echo esc_html__('Card code', 'woocommerce') ?>
                '&nbsp;<span class="required">*</span></label>
            <input id="<?php echo esc_attr($this->id) ?>-card-cvc" name="<?php echo esc_attr($this->id) ?>-card-cvc"
                   data-type="cvv" class="input-text achp_card_element wc-credit-card-form-card-cvc" inputmode="numeric"
                   autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4"
                   placeholder="<?php echo esc_attr__('CVC', 'woocommerce') ?>" style="width:100px"/>
        </p>
        <div class="clearfix"></div>
        <div class="achp_error_container_card"></div>
        <?php
        return ob_get_clean();
    }

    public function payment_scripts()
    {
        global $wp;
        if (
            !is_product()
            && !is_cart()
            && !is_checkout()
            && !isset($_GET['pay_for_order'])
            && !is_add_payment_method_page()
            && !isset($_GET['change_payment_method'])
            && !(!empty(get_query_var('view-subscription')) && is_callable('WCS_Early_Renewal_Manager::is_early_renewal_via_modal_enabled') && WCS_Early_Renewal_Manager::is_early_renewal_via_modal_enabled())
            || (is_order_received_page())
        ) {
            return;
        }

        if ('no' === $this->enabled) {
            return;
        }
        wp_enqueue_style('achp_main_style', ACHP_PLUGIN_ABSOLUTE_PATH . 'assets/css/achp.main.css');
        wp_register_script('achp_frontend_main', ACHP_PLUGIN_ABSOLUTE_PATH . 'assets/js/frontend.js', ['jquery-payment'], null, true);
        wp_enqueue_script('wc-credit-card-form');
        wp_localize_script('achp_frontend_main', 'achp_params', [
            'account_error_label' => __('Account number invalid', ACHP_TEXT_DOMAIN),
            'routing_error_label' => __('Routing number invalid', ACHP_TEXT_DOMAIN),
            'number_error_label' => __('Invalid card number', ACHP_TEXT_DOMAIN),
            'name_error_label' => __('Invalid holder name', ACHP_TEXT_DOMAIN),
            'expiry_error_label' => __('Invalid expiry date', ACHP_TEXT_DOMAIN),
            'cvv_error_label' => __('Invalid CVC', ACHP_TEXT_DOMAIN)

        ]);
        wp_enqueue_script('achp_frontend_main');

    }

    public function admin_scripts()
    {
        if ('woocommerce_page_wc-settings' !== get_current_screen()->id) {
            return;
        }
        wp_register_script('achp_admin', ACHP_PLUGIN_ABSOLUTE_PATH . 'assets/js/admin.js', [], time(), true);
        wp_enqueue_script('achp_admin');
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $paymentType = sanitize_text_field($_POST['ach_processing-payment-type']);

        // Create Bank Account if Bank type
        if ($paymentType == 'bank') {
            $account_number = sanitize_text_field($_POST[$this->id . '-account-number']);
            $routingNumber = sanitize_text_field($_POST[$this->id . '-routing-number']);
            $accountType = sanitize_text_field($_POST[$this->id . '-account-type']);
            $bankAccountName = sanitize_text_field($_POST[$this->id . '-holder-name']);

            $createBankAccount = $this->create_bank_account($order_id, $account_number, $routingNumber, $accountType, $bankAccountName);

            if ($createBankAccount['status']) {
                $paymentMethodId = $createBankAccount['payment_method_id'];
            } else {
                wc_add_notice($createBankAccount['message'], 'error');
                return false;
            }
        }
        // Create payment
        $amount = $order->get_total();
        if ($paymentType == 'bank') {
            if (!empty($paymentMethodId)) {
                $paymentRequestArray = [
                    'paymentMethodObjectId' => $paymentMethodId,
                    'Amount' => $amount,
                    'actionType' => '2'
                ];

                // Create bank payment
                $remoteDataPay = ACHP_Main_Class::curlPost('Ensemble/CreatePayment', $paymentRequestArray);
                if (!empty($remoteDataPay['status']['ResponseCode'])) {
                    if ($remoteDataPay['status']['ResponseCode'] == 'Ok') {
                        if (isset(WC()->cart)) {
                            WC()->cart->empty_cart();
                        }
                        $order->update_meta_data('_paymentRefID', $remoteDataPay['paymentRefID']);
                        update_post_meta($order->get_id(), 'custom_transaction_id', $remoteDataPay['paymentRefID']);
                        if (function_exists('wcs_get_subscriptions_for_order')) {
                            $subscriptions = wcs_get_subscriptions_for_order($order_id);

                            if (!empty($subscriptions)) {
                                foreach ($subscriptions as $subscription) {
                                    $subscription_id = $subscription->get_id();
                                    if ($paymentType == 'bank') {
                                        update_post_meta($subscription_id, '_achp_account_type', sanitize_text_field($_POST[$this->id . '-account-type']));
                                    }
                                    update_post_meta($subscription_id, '_achp_payment_source', $paymentMethodId);
                                }
                            }
                        }
                        $order->update_status('wc-pending');
                        return [
                            'result' => 'success',
                            'redirect' => $this->get_return_url($order),
                        ];
                    } else {
                        wc_add_notice($remoteDataPay['status']['Message'], 'error');
                        return false;
                    }
                } else {
                    wc_add_notice(__('Payment Declined. Please try again', ACHP_TEXT_DOMAIN), 'error');
                    return false;
                }
            }
        } else {
            $expiryValue= sanitize_text_field($_POST[$this->id.'-card-expiry']);
            $expiryValueArray=explode('/',$expiryValue);
            $expiryMonth=str_pad(trim($expiryValueArray[0]),2,'0',STR_PAD_LEFT);
            $expiryYear=trim($expiryValueArray[1]);
            $cardRequestArray = [
                'account' => sanitize_text_field(str_replace(' ', '', $_POST[$this->id . '-card-number'])),
                'expiry' => $expiryMonth.$expiryYear,
                'amount' => $amount,
                'orderid' => (string)$order->ID,
                'currency' => $order->get_currency(),
                'name' => sanitize_text_field($_POST[$this->id . '-card-name']),
                'capture' => 'Y',
                'receipt' => 'Y',
                'postalCode' => $order->get_billing_postcode(),
                'cvv2' => sanitize_text_field($_POST[$this->id . '-card-cvc'])
            ];

            $remoteDataPay = ACHP_Main_Class::curlPost('WalletCardAcq/authorization', $cardRequestArray, true);
            if (!empty($remoteDataPay['respStatus'])) {
                if ($remoteDataPay['respStatus'] == 'A') {
                    if (isset(WC()->cart)) {
                        WC()->cart->empty_cart();
                    }
                    $order->update_meta_data('_referenceNumber', $remoteDataPay['referenceNumber']);
                    update_post_meta($order->get_id(), 'custom_transaction_id', $remoteDataPay['referenceNumber']);
                    $order->update_status('wc-processing');
                    return [
                        'result' => 'success',
                        'redirect' => $this->get_return_url($order),
                    ];
                } elseif ($remoteDataPay['respStatus'] == 'R') {
                    $i = 1;
                    while ($i < 3) {
                        sleep(3);
                        $i++;
                        $remoteDataPay = ACHP_Main_Class::curlPost('WalletCardAcq/authorization', $cardRequestArray, true);
                        if (!empty($remoteDataPay['respStatus'])) {
                            if ($remoteDataPay['respStatus'] == 'A') {
                                if (isset(WC()->cart)) {
                                    WC()->cart->empty_cart();
                                }
                                $order->update_meta_data('_referenceNumber', $remoteDataPay['referenceNumber']);
                                update_post_meta($order->get_id(), 'custom_transaction_id', $remoteDataPay['referenceNumber']);
                                $order->update_status('wc-processing');
                                return [
                                    'result' => 'success',
                                    'redirect' => $this->get_return_url($order),
                                ];
                            } elseif ($remoteDataPay['respStatus'] == 'C') {
                                wc_add_notice(__('Payment Declined. Please try again', ACHP_TEXT_DOMAIN), 'error');
                                return false;
                            }
                        }
                    }
                } elseif ($remoteDataPay['respStatus'] == 'C') {
                    wc_add_notice(__('Payment Declined. Please try again', ACHP_TEXT_DOMAIN), 'error');
                    return false;
                }
            } else {
                wc_add_notice(__('Payment Declined. Please try again', ACHP_TEXT_DOMAIN), 'error');
                return false;
            }
        }

    }

    public function create_bank_account($order_id, $account_number, $routingNumber, $accountType, $bankAccountName)
    {
        $bankReqestArray = [
            'AccountNumber' => $account_number,
            'RoutingNumber' => $routingNumber,
            'AccountType' => $accountType,
            'BankAccountName' => $bankAccountName
        ];
        $remoteData = ACHP_Main_Class::curlPost('Ensemble/CreateBankAccount', $bankReqestArray);

        if (!empty($remoteData['status']['ResponseCode'])) {
            if ($remoteData['status']['ResponseCode'] == 'Ok') {
                $paymentMethodId = $remoteData['paymentMethodRefID'];
                update_post_meta($order_id, '_achp_payment_source', $paymentMethodId);
                update_post_meta($order_id, '_achp_account_type', $accountType);
                return [
                    'status' => true,
                    'payment_method_id' => $paymentMethodId
                ];
            } else {
                return [
                    'status' => false,
                    'message' => $remoteData['status']['Message']
                ];
            }
        } else {
            return [
                'status' => false,
                'message' => __('Payment Declined. Please try again', ACHP_TEXT_DOMAIN)
            ];
        }
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', ACHP_TEXT_DOMAIN),
                'label' => __('Enable ACH Processing', ACHP_TEXT_DOMAIN),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
            ],
            'title' => [
                'title' => __('Title', ACHP_TEXT_DOMAIN),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', ACHP_TEXT_DOMAIN),
                'default' => __('ACH Processing', ACHP_TEXT_DOMAIN),
                'desc_tip' => true,
            ],
            'description' => [
                'title' => __('Description', ACHP_TEXT_DOMAIN),
                'type' => 'text',
                'description' => __('This controls the description which the user sees during checkout.', ACHP_TEXT_DOMAIN),
                'default' => __('Pay with your credit card or bank account.', ACHP_TEXT_DOMAIN),
                'desc_tip' => true,
            ],
            'testmode' => [
                'title' => __('Test mode', ACHP_TEXT_DOMAIN),
                'label' => __('Enable Test Mode', ACHP_TEXT_DOMAIN),
                'type' => 'checkbox',
                'description' => __('Place the payment gateway in test mode using test Credentials.', ACHP_TEXT_DOMAIN),
                'default' => 'yes',
                'desc_tip' => true,
            ],
            'creditcard_enabled' => [
                'title' => __('Credit Card Payment', ACHP_TEXT_DOMAIN),
                'label' => __('', ACHP_TEXT_DOMAIN),
                'type' => 'checkbox',
                'description' => 'Enable Credit Card Payment',
                'default' => 'no',
                'desc_tip' => true,
            ],
            'bankaccount_enabled' => [
                'title' => __('Bank Account  Payment', ACHP_TEXT_DOMAIN),
                'label' => __('', ACHP_TEXT_DOMAIN),
                'type' => 'checkbox',
                'description' => 'Enable Bank Account PAyment',
                'default' => 'no',
                'desc_tip' => true,
            ],
            'test_api_endpoint' => [
                'title' => __('Test API Endpoint', ACHP_TEXT_DOMAIN),
                'type' => 'text',
                'description' => __('Get your API keys from your ACH Processing account.', ACHP_TEXT_DOMAIN),
                'default' => '',
                'desc_tip' => true,
            ],
            'api_endpoint' => [
                'title' => __('Live API Endpoint', ACHP_TEXT_DOMAIN),
                'type' => 'text',
                'description' => __('Get your API keys from your ACH Processing account.', ACHP_TEXT_DOMAIN),
                'default' => '',
                'desc_tip' => true,
            ],
            'test_api_card_key' => [
                'title' => __('Test API Card Key', ACHP_TEXT_DOMAIN),
                'type' => 'text',
                'description' => __('Get your API keys from your ACH Processing account.', ACHP_TEXT_DOMAIN),
                'default' => '',
                'desc_tip' => true,
            ],
            'api_card_key' => [
                'title' => __('Live API Card Key', ACHP_TEXT_DOMAIN),
                'type' => 'text',
                'description' => __('Get your API keys from your ACH Processing account.', ACHP_TEXT_DOMAIN),
                'default' => '',
                'desc_tip' => true,
            ],
            'test_api_key' => [
                'title' => __('Test API Key', ACHP_TEXT_DOMAIN),
                'type' => 'text',
                'description' => __('Get your API keys from your ACH Processing account.', ACHP_TEXT_DOMAIN),
                'default' => '',
                'desc_tip' => true,
            ],
            'api_key' => [
                'title' => __('Live API Key', ACHP_TEXT_DOMAIN),
                'type' => 'text',
                'description' => __('Get your API keys from your ACH Processing account.', ACHP_TEXT_DOMAIN),
                'default' => '',
                'desc_tip' => true,
            ],
            'test_user_name' => [
                'title' => __('Test User Name', ACHP_TEXT_DOMAIN),
                'type' => 'text',
                'description' => __('Get your User Name from your ACH Processing account.', ACHP_TEXT_DOMAIN),
                'default' => '',
                'desc_tip' => true,
            ],
            'user_name' => [
                'title' => __('Live User Name', ACHP_TEXT_DOMAIN),
                'type' => 'text',
                'description' => __('Get your User Name from your ACH Processing account.', ACHP_TEXT_DOMAIN),
                'default' => '',
                'desc_tip' => true,
            ],
            'test_password' => [
                'title' => __('Test Password', ACHP_TEXT_DOMAIN),
                'type' => 'password',
                'description' => __('Get your User Name from your ACH Processing account.', ACHP_TEXT_DOMAIN),
                'default' => '',
                'desc_tip' => true,
            ],
            'password' => [
                'title' => __('Live Password', ACHP_TEXT_DOMAIN),
                'type' => 'password',
                'description' => __('Get your User Name from your ACH Processing account.', ACHP_TEXT_DOMAIN),
                'default' => '',
                'desc_tip' => true,
            ],
            'bank_pay_text' => [
                'title' => __('Label for Bank Account', ACHP_TEXT_DOMAIN),
                'type' => 'text',
                'description' => __('This is heading of Bank Account payment', ACHP_TEXT_DOMAIN),
                'default' => 'Pay with Bank Account',
                'desc_tip' => true,
            ],
            'credit_card_pay_text' => [
                'title' => __('Label for Credit Card', ACHP_TEXT_DOMAIN),
                'type' => 'text',
                'description' => __('This is heading of Credit Card payment', ACHP_TEXT_DOMAIN),
                'default' => 'Pay with Credit Card',
                'desc_tip' => true,
            ],
            'webhook_endpoint' => [
                'title' => __('Webhook Endpoint', ACHP_TEXT_DOMAIN),
                'type' => 'text',
                'description' => __('Payment response will be posted here', ACHP_TEXT_DOMAIN),
                'default' => $this->getWebhookUrl(),
                'desc_tip' => true,
            ],
            'status_check_api_endpoint' => [
                'title' => __('Status Check API Endpoint', ACHP_TEXT_DOMAIN),
                'type' => 'text',
                'description' => __('To update status of orders add api endpoint here', ACHP_TEXT_DOMAIN),
                'default' => '',
                'desc_tip' => true,
            ],
            'check_order_status' => [
                'title' => __('Check Payment Status', ACHP_TEXT_DOMAIN),
                'type' => 'button',
                'description' => __('To update status of orders Click here', ACHP_TEXT_DOMAIN),
                'default' => 'Check Status',
                'desc_tip' => true,
                'css' => 'width:100px; height:40px; background-color:#1D5EA3; border-radius:6px; color:#ffffff; cursor:pointer; ',
            ],

        ];
    }

    public function getWebhookUrl()
    {
        return str_replace('https:', 'http:', add_query_arg('wc-api', $this->id, home_url('/')));
    }
}
