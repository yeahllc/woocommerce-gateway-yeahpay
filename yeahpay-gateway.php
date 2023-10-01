<?php
/*
Plugin Name: WooCommerce Yeah!Pay Gateway
Description: Custom payment gateway for Yeah!Pay.
Version: 1.0
Author: The Web Founders
*/

add_action('plugins_loaded', 'init_yeahpay_gateway');

function init_yeahpay_gateway() {
    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_YeahPay_Gateway extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'yeahpay';
            $this->icon = ''; // Add an icon URL if needed.
            $this->has_fields = true;
            $this->method_title = 'Yeah!Pay';
            $this->method_description = 'Pay with Yeah!Pay';

            // Load settings
            $this->init_form_fields();
            $this->init_settings();

            // Define settings
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');

            // Add the new settings fields
            $this->account_id = $this->get_option('account_id');
            $this->api_key = $this->get_option('api_key');
            $this->test_public_key = $this->get_option('test_public_key');
            $this->test_private_key = $this->get_option('test_private_key');
            $this->callback_url = $this->get_option('callback_url');
            $this->live_public_key = $this->get_option('live_public_key');
            $this->live_private_key = $this->get_option('live_private_key');
            $this->mode = $this->get_option('mode');

            // Hooks
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
            add_action('woocommerce_credit_card_form_start', array($this, 'yeahpay_credit_card_form_start'));
            add_action('woocommerce_credit_card_form_end', array($this, 'yeahpay_credit_card_form_end'));
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Enable/Disable',
                    'type' => 'checkbox',
                    'label' => 'Enable Yeah!Pay Gateway',
                    'default' => 'yes',
                ),
                'title' => array(
                    'title' => 'Title',
                    'type' => 'text',
                    'description' => 'This controls the title for the payment method the customer sees during checkout.',
                    'default' => 'Yeah!Pay',
                ),
                'description' => array(
                    'title' => 'Description',
                    'type' => 'textarea',
                    'description' => 'Payment method description that the customer will see during checkout.',
                    'default' => 'Pay with Yeah!Pay.',
                ),
                'account_id' => array(
                    'title' => 'Account ID (Login)',
                    'type' => 'text',
                    'description' => 'Enter your Account ID (Login).',
                    'default' => '',
                ),
                'api_key' => array(
                    'title' => 'API Key (Password)',
                    'type' => 'text',
                    'description' => 'Enter your API Key (Password).',
                    'default' => '',
                ),
                'test_public_key' => array(
                    'title' => 'Test Public Key',
                    'type' => 'text',
                    'description' => 'Enter your Test Public Key.',
                    'default' => '',
                ),
                'test_private_key' => array(
                    'title' => 'Test Private Key',
                    'type' => 'text',
                    'description' => 'Enter your Test Private Key.',
                    'default' => '',
                ),
                'callback_url' => array(
                    'title' => 'Callback URL',
                    'type' => 'text',
                    'description' => 'Enter your Callback URL.',
                    'default' => '',
                ),
                'live_public_key' => array(
                    'title' => 'Live Public Key',
                    'type' => 'text',
                    'description' => 'Enter your Live Public Key.',
                    'default' => '',
                ),
                'live_private_key' => array(
                    'title' => 'Live Private Key',
                    'type' => 'text',
                    'description' => 'Enter your Live Private Key.',
                    'default' => '',
                ),
                'mode' => array(
                    'title' => 'Mode (Live/Test)',
                    'type' => 'select',
                    'options' => array(
                        'live' => 'Live',
                        'test' => 'Test',
                    ),
                    'description' => 'Select the payment mode (Live or Test).',
                    'default' => 'test',
                ),
            );
        }

        public function process_admin_options() {
            parent::process_admin_options();
        
            // Save the settings
            update_option('woocommerce_' . $this->id . '_title', $this->title);
            update_option('woocommerce_' . $this->id . '_description', $this->description);
            update_option('woocommerce_' . $this->id . '_account_id', $this->account_id);
            update_option('woocommerce_' . $this->id . '_api_key', $this->api_key);
            update_option('woocommerce_' . $this->id . '_test_public_key', $this->test_public_key);
            update_option('woocommerce_' . $this->id . '_test_private_key', $this->test_private_key);
            update_option('woocommerce_' . $this->id . '_callback_url', $this->callback_url);
            update_option('woocommerce_' . $this->id . '_live_public_key', $this->live_public_key);
            update_option('woocommerce_' . $this->id . '_live_private_key', $this->live_private_key);
            update_option('woocommerce_' . $this->id . '_mode', $this->mode);
        }

        public function receipt_page($order_id) {
            // Display any additional information on the receipt page if needed.
        }

        public function process_payment($order_id) {
            global $woocommerce;
            
            // Get the order
            $order = wc_get_order($order_id);
            
            // Get the payment form data
            $cc_number = sanitize_text_field($_POST['ecard_ccNo']);
            $cc_holder_name = sanitize_text_field($_POST['ecard_holder_name']);
            $cc_month = sanitize_text_field($_POST['exp_month']);
            $cc_year = sanitize_text_field($_POST['exp_year']);
            $cc_cvc = sanitize_text_field($_POST['ecard_cvv']);
            



            // Get additional customer information
            $customer_email = $order->get_billing_email();
            $customer_phone = $order->get_billing_phone();
            $order_id = $order->get_order_number();
            

            $cc_exp = sanitize_text_field("$cc_month"."/"."$cc_year");
            // Prepare the data to send to your API
            $payment_data = array(
                'order_id' => $order_id,
                'amount' => $order->get_total(),
                'cc_number' => $cc_number,
                'card_holder' => $cc_holder_name,
                'cc_month' => $cc_month,
                'cc_year' => $cc_year,
                'cc_cvc' => $cc_cvc,
                'customer_email' => $customer_email,
                'customer_phone' => $customer_phone,
                'order_id' => $order_id,
                // Add other payment data as needed
            );
            
            
            // Send the data to your API and process the payment
            $response = $this->send_payment_request($payment_data);
            
            if ($response === 'success') {
                // Payment was successful
                $order->payment_complete();
                $woocommerce->cart->empty_cart();
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order),
                );
            } 
            elseif($response === 'process_pending'){
                $order->update_status('pending', __('Payment pending.', 'woocommerce'));
                $woocommerce->cart->empty_cart();
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order),
                );
            }
            else{
                // Payment failed
                wc_add_notice('Payment failed: ' . $response, 'error');
                return;
            }
        }
        
        
        // Function to send the payment data to your API
        private function send_payment_request($payment_data) {

         
            // Define the API endpoint
            $api_endpoint = 'https://papi.yeah.store/payment-invoices';
        
            // Retrieve Account ID (Login) and API Key (Password) from admin options
            $account_id = $this->account_id; // Use the admin option for Account ID
            $api_key = $this->api_key; // Use the admin option for API Key
        
            // Prepare the request data
            $request_data = array(
                'data' => array(
                    'type' => 'payment-invoices',
                    'attributes' => array(
                        'test_mode' => true,
                        'reference_id' => $payment_data['order_id'], // Use the order ID as the reference
                        'currency' => 'USD', // Modify as needed
                        'amount' => $payment_data['amount'],
                        'service' => 'payment_card_usd_hpp', // Modify as needed
                        'customer' => array(
                            'reference_id' => $payment_data['customer_email'], // Modify as needed
                        ),
                        'gateway_options' => array(
                            'cardgate' => array(
                                'instant' => true,
                            ),
                        ),
                    ),
                ),
            );
        
            // Set up the request headers
            $headers = array(
                'Authorization' => 'Basic ' . base64_encode($account_id . ':' . $api_key),
                'Content-Type' => 'application/json',
            );
        
            // Create the API request
            $response = wp_safe_remote_post($api_endpoint, array(
                'headers' => $headers,
                'body' => wp_json_encode($request_data),
            ));
        
            // Check for API request errors
            if (is_wp_error($response)) {
                return 'API request failed: ' . $response->get_error_message();
            }
        
            // Parse the API response
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            
            // return json_encode($data['data']['attributes']['status']);
            // return json_encode($data['data']['attributes']['flow_data']['metadata']['token']);
 
            if ($data && isset($data['data']['attributes']['status'])) {
                // Payment was successful 
                $token = $data['data']['attributes']['flow_data']['metadata']['token'];

                // Use the token to make the second API request
                $second_api_endpoint = 'https://checkout.yeah.store/payment/sale';
        
                // Set up the request headers with Bearer Token
                $second_headers = array(
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                );
        
                // Prepare the data for the second API request
                $second_request_data = array(
                    'data' => array(
                        'type' => 'sale-operation',
                        'attributes' => array(
                            'card_number' => $payment_data['cc_number'], // Use the card number from the form
                            'card_holder' => $payment_data['card_holder'], // Use the card holder name from the form
                            'cvv' => $payment_data['cc_cvc'], // Use the CVV from the form
                            'exp_month' => $payment_data['cc_month'], // Use the expiration month from the form
                            'exp_year' => $payment_data['cc_year'], // Use the expiration year from the form
                            'browser_info' => array(
                                'browser_tz' => '-60', // Modify as needed
                                'browser_screen_width' => '1920', // Modify as needed
                            ),
                        ),
                    ),
                );
              

                
                // Create the second API request
                $second_response = wp_safe_remote_post($second_api_endpoint, array(
                    'headers' => $second_headers,
                    'body' => wp_json_encode($second_request_data),
                ));
        
                // Check for errors in the second API request and handle the response
                if (is_wp_error($second_response)) {
                    return 'Second API request failed: ' . $second_response->get_error_message();
                }
        
                // Parse the second API response and handle it accordingly
                $second_body = wp_remote_retrieve_body($second_response);
                $second_data = json_decode($second_body, true);
        
                // Handle the second API response here
                // You can return a success message or an error message based on the response
        
                // Example:
                
                if ($second_data['status'] == "processed" ) {
                    return 'success';
                }
                elseif($second_data['status'] == "process_pending"){
                    return 'process_pending';
                }
                else {
                    return 'Second API response indicates failure: ' . json_encode($second_data);
                }
            } else {
                // Payment failed
                return 'API response indicates payment failure: ' . json_encode($data);
            }
        }
        
        

        public function yeahpay_credit_card_form_start() {
            echo '<div id="yeahpay-card-details">';
        }

        public function yeahpay_credit_card_form_end() {
            echo '</div>';
        }

        public function payment_fields() {
            // Add the credit card form fields here
            echo '
            <div class="form-row form-row-wide" style="margin:10px;padding:0px"><label>Card Holder Name <span class="required">*</span></label>
                <input name="ecard_holder_name" type="text" autocomplete="off">
                </div>
            <div class="form-row form-row-wide" style="margin:10px;padding:0px"><label>Card Number <span class="required">*</span></label>
                <input name="ecard_ccNo" type="text" autocomplete="off">
                </div>
                <div class="form-row form-row-wide" style="margin:10px;padding:0px">
                <div class="row" style="width:100%;display:flex;gap:10px;justify-content: flex-end;">
                <div class="col-6" style="width:50%">
                    <select class="form-control" name="exp_month" style="width:100%">
                        <option value="01">Jan</option>
                        <option value="02">Feb</option>
                        <option value="03">Mar</option>
                        <option value="04">Apr</option>
                        <option value="05">May</option>
                        <option value="06">Jun</option>
                        <option value="07">Jul</option>
                        <option value="08">Aug</option>
                        <option value="09">Sep</option>
                        <option value="10">Oct</option>
                        <option value="11">Nov</option>
                        <option value="12">Dec</option>
                    </select>
                </div>
                <div class="col-6" style="width:50%">
                    <select class="form-control" name="exp_year" style="width:100%">
                        <option value="23">2023</option>
                        <option value="24">2024</option>
                        <option value="25">2025</option>
                        <option value="26">2026</option>
                        <option value="27">2027</option>
                        <option value="28">2028</option>
                        <option value="29">2029</option>
                        <option value="30">2030</option>
                        <option value="31">2031</option>
                        <option value="32">2032</option>
                        <option value="33">2033</option>
                        <option value="34">2034</option>
                        <option value="35">2035</option>
                        <option value="36">2036</option>
                        <option value="37">2037</option>
                        <option value="38">2038</option>
                        <option value="39">2039</option>
                        <option value="40">2040</option>
                        <option value="41">2041</option>
                        <option value="42">2042</option>
                        <option value="43">2043</option>
                    </select>
                </div>
            </div>
                </div>
                
                <div class="form-row form-row" style="margin:10px;padding:0px">
                    <label>Card Code (CVC) <span class="required">*</span></label>
                    <input name="ecard_cvv" type="password" autocomplete="off" placeholder="CVC">
                </div>
                <div class="clear"></div>';
        }
    }

    // Register the payment gateway with WooCommerce
    function add_yeahpay_gateway($methods) {
        $methods[] = 'WC_YeahPay_Gateway';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_yeahpay_gateway');
}
