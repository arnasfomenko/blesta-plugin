<?php
/**
 * Coingate NonMerchant Gateway Library
 *
 * This library define some of the core functionality
 * for the bitpay
 *
 * @package blesta
 * @subpackage blesta.components.gateways.nonmerchant.coingate.lib
 * @author Phillips Data, Inc.
 * @author Coingate
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 * @link https://coignate.com/ Nirays
 */

class CoingateApi extends Coingate
{

    public function __construct($app_id, $api_key, $api_secret, $test_mode = false)
    {
        $this->app_id = $app_id;
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        $this->test_mode = $test_mode;
    }

    public function getUrl()
    {
        if ($this->test_mode) {
            $url = "https://api-sandbox.coingate.com/v1/orders/1";
        } else {
            $url = "https://api.coingate.com/v1/orders";
        }

        return $url;
    }

    public function apiRequest($url, $method = 'GET', $params = array())
    {
        $nonce = time();
        $message = $nonce . $this->app_id . $this->api_key;
        $signature = hash_hmac('sha256', $message, $this->api_secret);

        $headers = array();
        $headers[] = 'Access-Key: ' . $this->api_key;
        $headers[] = 'Access-Nonce: ' . $nonce;
        $headers[] = 'Access-Signature: ' . $signature;

        $curl = curl_init();

        $curl_options = array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL            => $url,
        );

        if ($method == 'POST') {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';

            array_merge($curl_options, array(CURLOPT_POST => 1));
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        curl_setopt_array($curl, $curl_options);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        return array('status_code' => $http_status, 'response_body' => $response);

    }

    public function coingateCallback()
    {
        return $this->apiRequest('https://api-sandbox.coingate.com/v1/orders/1');
    }

    public function requestPayment($params)
    {
        return $this->apiRequest('https://api-sandbox.coingate.com/v1/orders', 'POST', $params);
    }

}
