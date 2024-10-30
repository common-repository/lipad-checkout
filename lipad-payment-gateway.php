<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * Plugin Name: Lipad Checkout
 * Plugin URI:  https://wordpress.org/plugins/lipad-checkout
 * Description: Collect payments from your customers with an express self-checkout interface.
 * Version:     1.0.6
 * Author:      Lipad
 * Author URI:  https://lipad.io
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

/*
 * Required files
 */
require('includes/LipadPaymentGatewayConstants.php');
require('includes/LipadPaymentGatewayUtils.php');

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'lipad_add_gateway_class');
function lipad_add_gateway_class($gateways)
{
    $gateways[] = 'WC_Gateway_Lipad';
    return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'lipad_init_gateway_class');
function lipad_init_gateway_class()
{

    class WC_Gateway_Lipad extends WC_Payment_Gateway
    {
        //WC_Payment_Gateway
        public $id;
        public $icon;
        public $supports;
        public $has_fields;
        public $method_title;
        public $method_description;
        public $base_country;


        //Payment gateway configurations
        public $title;
        public $iv_key;
        public $enabled;
        public $access_key;
        public $secret_key;
        public $payment_period;
        public $client_code;
        public $checkout_url;

        public function __construct()
        {
            $countries = new WC_Countries;

            $this->icon = LipadWordPressConstants::LIPAD_ICON;
            $this->has_fields = true;
            $this->id = LipadWordPressConstants::PAYMENT_GATEWAY;
            $this->method_title = ucfirst(LipadWordPressConstants::BRAND_NAME);
            $this->method_description = ucfirst(LipadWordPressConstants::PAYMENT_GATEWAY_DESCRIPTION);
            $this->base_country = $countries->get_base_country();

            $this->supports = array(
                'products'
            );

            $this->init_form_fields();

            // Settings
            $this->init_settings();
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');

            $this->enabled = $this->get_option('enabled');

            // Check test mode
            $this->test_mode = $this->get_option('test_mode', 'yes') === 'yes';

            // Set credentials and URLs based on the mode
            $this->iv_key = $this->test_mode ? $this->get_option('test_iv_key') : $this->get_option('iv_key');
            $this->secret_key = $this->test_mode ? $this->get_option('test_secret_key') : $this->get_option('secret_key');
            $this->access_key = $this->test_mode ? $this->get_option('test_access_key') : $this->get_option('access_key');
            $this->checkout_url = $this->test_mode ? LipadWordPressConstants::CHECKOUT_REDIRECT_LINKS['uat'] : LipadWordPressConstants::CHECKOUT_REDIRECT_LINKS['live'];
            $this->payment_period = $this->test_mode ? $this->get_option('test_payment_period') : $this->get_option('payment_period');
//            $this->service_code = $this->test_mode ? $this->get_option('test_service_code') : $this->get_option('service_code');
            $this->client_code = $this->test_mode ? $this->get_option('test_client_code') : $this->get_option('client_code');
            $this->client_secret = $this->test_mode ? $this->get_option('test_client_secret') : $this->get_option('client_secret');

            // Action hook to save the settings
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            // Custom JavaScript to obtain a token
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

            // Payment gateway webhook
            add_action('woocommerce_api_lipad_payment_webhook', array($this, 'webhook'));
        }

        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Enable/Disable',
                    'label' => 'Enable Payment Gateway',
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                'test_mode' => array(
                    'title' => 'Test mode',
                    'label' => 'Enable Test Mode',
                    'type' => 'checkbox',
                    'description' => 'Place the payment gateway in test mode using UAT API keys. Disabling this means you are using the production environment.',
                    'default' => 'yes',
                    'desc_tip' => true,
                ),
                 'title' => array(
                    'title' => 'Title',
                    'type' => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default' => ucfirst(LipadWordPressConstants::BRAND_NAME),
                    'custom_attributes' => array(
                        'readonly' => 'readonly',
                    ),
                ),
                'description' => array(
                    'title' => 'Description',
                    'type' => 'textarea',
                    'description' => 'This is the description which the user sees during checkout.',
                    'default' => 'Lipad allows you to make payments with Mobile money, mobile banking and cards in Africa from a single integration',
                    'custom_attributes' => array(
                        'readonly' => 'readonly',
                    ),
                ),
                'test_payment_period' => array(
                    'title' => 'Test Payment period in minutes',
                    'type' => 'text',
                    'description' => 'This sets the amount of time in minutes before a checkout request on an order expires',
                    'default' => '30',
                    'desc_tip' => true,
                ),
                'test_client_code' => array(
                    'title' => 'Test Client Code',
                    'type' => 'text',
                ),
                'test_service_code' => array(
                    'title' => 'Test Service Code',
                    'type' => 'text'
                ),
                'test_iv_key' => array(
                    'title' => 'Test IV Key',
                    'type' => 'text'
                ),
                'test_secret_key' => array(
                    'title' => 'Test Secret Key',
                    'type' => 'text'
                ),
                'test_access_key' => array(
                    'title' => 'Test Access Key',
                    'type' => 'text',
                ),
                'payment_period' => array(
                    'title' => 'Live Payment period in minutes',
                    'type' => 'text',
                    'description' => 'This sets the amount of time in minutes before a checkout request on an order expires',
                    'default' => '30',
                    'desc_tip' => true,
                ),
                'client_code' => array(
                    'title' => 'Live Client Code',
                    'type' => 'text',
                ),
                'service_code' => array(
                    'title' => 'Live Service Code',
                    'type' => 'text'
                ),
                'iv_key' => array(
                    'title' => 'Live IV Key',
                    'type' => 'text'
                ),
                'secret_key' => array(
                    'title' => 'Live Secret Key',
                    'type' => 'text'
                ),
                'access_key' => array(
                    'title' => 'Live Access Key',
                    'type' => 'text',
                ),
            );

        }

        public function payment_scripts()
        {
            // we need JavaScript to process a token only on cart/checkout pages
            if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
                return;
            }

            // if our payment gateway is disabled, we do not have to enqueue JS too
            if ($this->enabled === 'no') {
                return;
            }
        }

        /*
          * Fields validation
         */
        public function validate_fields(): bool
        {
            $billing_country = sanitize_text_field($_POST['billing_country']);

            $supported_countries = array_map(function ($value) {
                return $value['country_code'];
            }, LipadWordPressConstants::COUNTRIES);

            if (!in_array($this->base_country, $supported_countries)) {
                if (!in_array($billing_country, $supported_countries)) {
                    wc_add_notice('Country is not supported on Lipad platform!', 'error');
                    return false;
                }
                $this->base_country = $billing_country;
            }

            return true;
        }


        private function get_iso_code(string $code)
        {
            foreach (LipadWordPressConstants::COUNTRIES as $country) {
                if ($country['country_code'] == strtoupper($code)) {
                    return $country['iso3_country_code'];
                }
            }
            return 'UNKNOWN';
        }
        private function write_log($log) {
            if (true === WP_DEBUG) {
                if (is_array($log) || is_object($log)) {
                    error_log(print_r($log, true));
                } else {
                    error_log($log);
                }
            }
        }
        public function process_payment($order_id)
        {
            $order = wc_get_order($order_id);

            // Get the billing phone number
            $msisdn = preg_replace('/^(?:\+?0?(?:254)?)?/', '254', $order->get_billing_phone());
            if (preg_match(LipadWordPressConstants::AIRTEL_REGEX, $msisdn)) {
                $service_code = $this->test_mode ? $this->get_option('test_service_code') : $this->get_option('service_code');
            } elseif (preg_match(LipadWordPressConstants::MPESA_REGEX, $msisdn)) {
                $service_code = $this->test_mode ? $this->get_option('test_service_code') : $this->get_option('service_code');
            } else {
                wc_add_notice('The phone number is not valid for the supported operators!', 'error');
                return array(
                    'result' => 'failure'
                );
            }
            // checkout transaction description
            $order_excerpt = array_reduce($order->get_items(), function ($carry, $item) {
                $format = '%d x %s, ';

                $quantity = $item->get_quantity();

                $product = $item->get_product();
                $product_name = $product->get_name();
                return $carry . sprintf($format, $quantity, $product_name);
            });
            $currency_code = strtoupper(get_woocommerce_currency());
            $request_amount = $order->get_total();
            $due_date = date("c", strtotime("+" . $this->payment_period . " minutes"));
            // array with parameters for API interaction
            $payload = array(
                "msisdn" => $msisdn,
                "account_number"=> $msisdn,
                "country_code" => !empty($country_code) ? $country_code : 'KEN',
                "currency_code" => !empty($currency_code) ? $currency_code : 'KES',
                "due_date" => $due_date,
                "customer_email" => $order->get_billing_email(),
                "customer_first_name" => $order->get_billing_first_name(),
                "customer_last_name" => $order->get_billing_last_name(),
                "merchant_transaction_id" => $order->get_id(),
                "callback_url" => get_site_url() . '/wc-api/lipad_payment_webhook',
                "request_amount" => !empty($request_amount) ? $request_amount : 0,
                "request_description" => rtrim(trim($order_excerpt), ','),
                "success_redirect_url" => $order->get_checkout_order_received_url(),
                "fail_redirect_url" => get_permalink(get_page_by_path('shop')),
                "service_code" => $service_code,
                "client_code" => $this->client_code,
                "language_code" => 'en'
            );
            $this->write_log($payload);
            $checkout_payment_url = sprintf(
                $this->checkout_url . "?access_key=%s&payload=%s",
                $this->access_key,
                LipadPaymentGatewayUtils::encryptCheckoutRequest($this->iv_key, $this->secret_key, $payload)
            );

            return array(
                'result' => 'success',
                'redirect' => $checkout_payment_url
            );
        }

        public function webhook()
        {
            $callback_json_payload = file_get_contents('php://input');
            $payload = json_decode($callback_json_payload, true);
            $checkout_request_id = $payload["checkout_request_id"];
            $merchant_transaction_id = $payload["merchant_transaction_id"];
            $request_payment_status = $payload["overall_payment_status"];

            // Determine the acknowledgement code and description
            if (!empty($callback_json_payload)) {
                $acknowledgement_code = 900;
                $acknowledgement_description = "Accepted";
            } else {
                $acknowledgement_code = 901;
                $acknowledgement_description = "Rejected";
            }
            // Prepare the acknowledgement data
            $acknowledgement_data = [
                "checkout_request_id" => $checkout_request_id,
                "merchant_transaction_id" => $merchant_transaction_id,
                "acknowledgement_code" => $acknowledgement_code,
                "acknowledgement_description" => $acknowledgement_description,
                "merchant_ack_id" => uniqid()
            ];

            // Convert the acknowledgement data to JSON
            $acknowledgement_json = json_encode($acknowledgement_data);

            // Send the acknowledgement response
            header("Content-Type: application/json");
            echo wp_json_encode($acknowledgement_json);

            $order = wc_get_order($merchant_transaction_id);
            //successful payments
            if (in_array($request_payment_status, [801, 803, 802, 820, 851, 841])) {
                $note = '';
            if ($request_payment_status == 801) {
                $order->payment_complete();
                $note .= sprintf("Order #%s has been paid in full", $merchant_transaction_id);
            }
            elseif ($request_payment_status == 803) {
                $order->update_status('pending-payment', __('Pending payment.', 'woocommerce'));
                    $note .= sprintf("Order #%s prompt has been cancelled by user", $merchant_transaction_id);
            }
            elseif ($request_payment_status == 802) {
                $order->update_status('on-hold', __('Order is partially paid. Payment needs to be confirmed.', 'woocommerce'));
                $note .= sprintf("Order #%s has been put on hold due to partial payment.", $merchant_transaction_id);
            } elseif ($request_payment_status == 820) {
                $order->update_status('failed', __('Order has expired with no payments.', 'woocommerce'));
                $note .= sprintf("Order #%s has expired with no payments", $merchant_transaction_id);
            }
            elseif ($request_payment_status == 851) {
                    $note .= sprintf("Order #%s payment is pending", $merchant_transaction_id);
            } elseif ($request_payment_status == 841) {
                $order->refund_order("Partial refund for order #" . $merchant_transaction_id);
                $note .= sprintf("Partial refund initiated for order #%s", $merchant_transaction_id);
            }
            $order->add_order_note($note);
            }
            exit();
        }
    }
}
