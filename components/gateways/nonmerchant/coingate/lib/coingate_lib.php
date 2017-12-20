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

require_once('coingate/init.php');

class CoingateApi extends Coingate
{

    public function __construct($app_id, $api_key, $api_secret, $test_mode = false)
    {
        $this->app_id = $app_id;
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        $this->test_mode = $test_mode;
    }

    
}