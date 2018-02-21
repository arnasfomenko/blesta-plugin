<?php
/**
 * Coingate Payment Gateway
 *
 * Allows users to pay with Bitcoins and Altcoins
 *
 * @package blesta
 * @subpackage blesta.components.gateways.nonmerchant.coingate
 * @author CoinGate
 * @copyright Copyright (c) 2018, CoinGate
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 * @link https://coingate.com Coingate
 */
class Coingate extends NonmerchantGateway
{
    /**
     * @var string The version of this gateway
     */
    private static $version = "1.0.0";
    /**
     * @var string The authors of this gateway
     */
    private static $authors = [
        ['name' => 'Coingate', 'url' => 'https://coingate.com'],
    ];
    /**
     * @var array An array of meta data for this gateway
     */
    private $meta;

    /**
     * Construct a new merchant gateway
     */
    public function __construct()
    {

        // Load components required by this gateway
        Loader::loadComponents($this, array("Input"));

        // Load components required by this gateway
        Loader::loadModels($this, ['Clients']);

        // Load the language required by this gateway
        Language::loadLang("coingate", null, dirname(__FILE__) . DS . "language" . DS);
    }

    /**
     * Returns the name of this gateway
     *
     * @return string The common name of this gateway
     */
    public function getName()
    {
        return Language::_("Coingate.name", true);
    }

    /**
     * Returns the version of this gateway
     *
     * @return string The current version of this gateway
     */
    public function getVersion()
    {
        return self::$version;
    }

    /**
     * Returns the name and URL for the authors of this gateway
     *
     * @return array The name and URL of the authors of this gateway
     */
    public function getAuthors()
    {
        return self::$authors;
    }

    /**
     * Return all currencies supported by this gateway
     *
     * @return array A numerically indexed array containing all currency codes (ISO 4217 format) this gateway supports
     */
    public function getCurrencies()
    {
        return array("EUR", "GBP", "USD", "BTC", "PLN", "CZK", "SEK", "NOK", "DKK", "CHF", "ZAR", "AUD", "JPY", "BRL", "CAD", "CNY", "HKD", "HUF", "INR", "RUB", "ILS", "MYR", "MXN", "SGD", "RON", "VEF", "IDR", "PHP", "ARS", "THB", "NGN", "COP", "PKR", "AED", "UAH", "BGN");
    }

    /**
     * Sets the currency code to be used for all subsequent payments
     *
     * @param string $currency The ISO 4217 currency code to be used for subsequent payments
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * Create and return the view content required to modify the settings of this gateway
     *
     * @param array $meta An array of meta (settings) data belonging to this gateway
     * @return string HTML content containing the fields to update the meta data for this gateway
     */
    public function getSettings(array $meta = null)
    {
        $this->view = $this->makeView("settings", "default", str_replace(ROOTWEBDIR, "", dirname(__FILE__) . DS));

        // Load the helpers required for this view
        Loader::loadHelpers($this, array("Form", "Html"));
        $receive_currency = [
            'BTC' => Language::_('Coingate.receive_currency.btc', true),
            'EUR' => Language::_('Coingate.receive_currency.eur', true),
            'USD' => Language::_('Coingate.receive_currency.usd', true),
        ];
        $coingate_environment = [
            'sandbox' => Language::_('Coingate.environment.sandbox', true),
            'live' => Language::_('Coingate.environment.live', true),
        ];
        $this->view->set('meta', $meta);
        $this->view->set('receive_currency', $receive_currency);
        $this->view->set('coingate_environment', $coingate_environment);

        return $this->view->fetch();
    }

    /**
     * Validates the given meta (settings) data to be updated for this gateway
     *
     * @param array $meta An array of meta (settings) data to be updated for this gateway
     * @return array The meta data to be updated in the database for this gateway, or reset into the form on failure
     */
    public function editSettings(array $meta)
    {
        // Verify meta data is valid
        $rules = [
            'app_id'     => [
                'valid' => [
                    'rule'    => "isEmpty",
                    'negate'  => true,
                    'message' => Language::_("Coingate.!error.api.id.valid", true),
                ],
            ],
            'api_key'    => [
                'valid' => [
                    'rule'    => "isEmpty",
                    'negate'  => true,
                    'message' => Language::_("Coingate.!error.api.key.valid", true),
                ],
            ],
            'api_secret' => [
                'valid' => [
                    'rule'    => "isEmpty",
                    'negate'  => true,
                    'message' => Language::_("Coingate.!error.api.secret.valid", true),
                ],
            ],
        ];

        $this->Input->setRules($rules);

        // Validate the given meta data to ensure it meets the requirements
        $this->Input->validates($meta);
        // Return the meta data, no changes required regardless of success or failure for this gateway
        return $meta;
    }

    /**
     * Returns an array of all fields to encrypt when storing in the database
     *
     * @return array An array of the field names to encrypt when storing in the database
     */
    public function encryptableFields()
    {
        return ['app_id', 'api_key', 'api_secret'];
    }

    /**
     * Sets the meta data for this particular gateway
     *
     * @param array $meta An array of meta data to set for this gateway
     */
    public function setMeta(array $meta = null)
    {
        $this->meta = $meta;
    }

    /**
     * Returns all HTML markup required to render an authorization and capture payment form
     *
     * @param array $contact_info An array of contact info including:
     *     - id The contact ID
     *     - client_id The ID of the client this contact belongs to
     *     - user_id The user ID this contact belongs to (if any)
     *     - contact_type The type of contact
     *     - contact_type_id The ID of the contact type
     *     - first_name The first name on the contact
     *     - last_name The last name on the contact
     *     - title The title of the contact
     *     - company The company name of the contact
     *     - address1 The address 1 line of the contact
     *     - address2 The address 2 line of the contact
     *     - city The city of the contact
     *     - state An array of state info including:
     *         - code The 2 or 3-character state code
     *         - name The local name of the country
     *     - country An array of country info including:
     *         - alpha2 The 2-character country code
     *         - alpha3 The 3-cahracter country code
     *         - name The english name of the country
     *         - alt_name The local name of the country
     *     - zip The zip/postal code of the contact
     * @param float $amount The amount to charge this contact
     * @param array $invoice_amounts An array of invoices, each containing:
     *     - id The ID of the invoice being processed
     *     - amount The amount being processed for this invoice (which is included in $amount)
     * @param array $options An array of options including:
     *     - description The Description of the charge
     *     - return_url The URL to redirect users to after a successful payment
     *     - recur An array of recurring info including:
     *         - amount The amount to recur
     *         - term The term to recur
     *         - period The recurring period (day, week, month, year, onetime) used in conjunction with term in order to determine the next recurring payment
     * @return string HTML markup required to render an authorization and capture payment form
     */
    public function buildProcess(array $contact_info, $amount, array $invoice_amounts = null, array $options = null)
    {
        Loader::load(dirname(__FILE__) . DS . 'coingate-php' . DS . 'init.php');

        $client_id = $this->ifSet($contact_info['client_id']);

        if (isset($invoice_amounts) && is_array($invoice_amounts)) {
            $invoices = $this->serializeInvoices($invoice_amounts);
        }

        $orderId = $client_id . '@' . (!empty($invoices) ? $invoices : time());
        $token = md5($orderId);

        $callbackURL = Configure::get('Blesta.gw_callback_url')
        . Configure::get('Blesta.company_id') . '/coingate/?client_id='
        . $this->ifSet($contact_info['client_id']);

        $test_mode = $this->coingateEnvironment();

        $post_params = array(
            'order_id'         => $orderId,
            'price'            => $this->ifSet($amount),
            'description'      => $this->ifSet($options['description']),
            'title'            => 'CoinGate\'s Order',
            'token'            => $token,
            'currency'         => $this->ifSet($this->currency),
            'receive_currency' => $this->meta['receive_currency'],
            'callback_url'     => $callbackURL,
            'cancel_url'       => $this->ifSet($options['return_url']),
            'success_url'      => $this->ifSet($options['return_url']),
        );

        $order = \CoinGate\Merchant\Order::create($post_params, array(), array(
            'environment' => $test_mode,
            'app_id'      => $this->meta['app_id'],
            'api_key'     => $this->meta['api_key'],
            'api_secret'  => $this->meta['api_secret'],
        ));

        if ($order && $order->payment_url) {
            header("Location: " . $order->payment_url);
        } else {
            print_r($order);
        }
    }

    /**
     * Validates the incoming POST/GET response from the gateway to ensure it is
     * legitimate and can be trusted.
     *
     * @param array $get The GET data for this request
     * @param array $post The POST data for this request
     * @return array An array of transaction data, sets any errors using Input if the data fails to validate
     *  - client_id The ID of the client that attempted the payment
     *  - amount The amount of the payment
     *  - currency The currency of the payment
     *  - invoices An array of invoices and the amount the payment should be applied to (if any) including:
     *      - id The ID of the invoice to apply to
     *      - amount The amount to apply to the invoice
     *     - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *     - reference_id The reference ID for gateway-only use with this transaction (optional)
     *     - transaction_id The ID returned by the gateway to identify this transaction
     */
    public function validate(array $get, array $post)
    {

        $cgOrder = $this->coingateCallback($this->ifSet($post['id']));

        $data_parts = explode('@', $this->ifSet($post['order_id']), 2);

        $client_id = $data_parts[0];

        $invoices = $this->ifSet($data_parts[1]);

        if (is_numeric($invoices)) {
            $invoices = null;
        }

        $status = 'error';

        if (isset($post['status'])) {
            switch ($post['status']) {
                case 'pending':
                    $status = 'pending';
                    break;
                case 'confirming':
                    $status = 'pending';
                    break;
                case 'paid':
                    $status = 'approved';
                    break;
                case 'invalid':
                    $status = 'declined';
                    break;
                case 'canceled':
                    $status = 'declined';
                    break;
                case 'expired':
                    $status = 'declined';
                    break;
                case 'refunded':
                    $status = 'refunded';
                    break;
                default:
                    $status = 'pending';
            }
        }

        $this->log($this->ifSet($_SERVER['REQUEST_URI']), serialize($post), "output", true);

        return [
            'client_id'      => $client_id,
            'amount'         => $this->ifSet($post['price']),
            'currency'       => $this->ifSet($post['currency']),
            'status'         => $status,
            'reference_id'   => null,
            'transaction_id' => $this->ifSet($post['id']),
            'invoices'       => $this->unserializeInvoices($invoices),
        ];
    }

    /**
     * Returns data regarding a success transaction. This method is invoked when
     * a client returns from the non-merchant gateway's web site back to Blesta.
     *
     * @param array $get The GET data for this request
     * @param array $post The POST data for this request
     * @return array An array of transaction data, may set errors using Input if the data appears invalid
     *  - client_id The ID of the client that attempted the payment
     *  - amount The amount of the payment
     *  - currency The currency of the payment
     *  - invoices An array of invoices and the amount the payment should be applied to (if any) including:
     *      - id The ID of the invoice to apply to
     *      - amount The amount to apply to the invoice
     *     - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *     - transaction_id The ID returned by the gateway to identify this transaction
     */
    public function success(array $get, array $post)
    {

        $data_parts = explode('@', $this->ifSet($post['order_id']), 2);

        $client_id = $data_parts[0];

        $invoices = $this->ifSet($data_parts[1]);

        if (is_numeric($invoices)) {
            $invoices = null;
        }

        $status = 'error';

        if (isset($post['status'])) {
            switch ($post['status']) {
                case 'pending':
                    $status = 'pending';
                    break;
                case 'confirming':
                    $status = 'pending';
                    break;
                case 'paid':
                    $status = 'approved';
                    break;
                case 'invalid':
                    $status = 'declined';
                    break;
                case 'canceled':
                    $status = 'declined';
                    break;
                case 'expired':
                    $status = 'declined';
                    break;
                case 'refunded':
                    $status = 'refunded';
                    break;
                default:
                    $status = 'pending';
            }
        }

        return [
            'client_id'      => $client_id,
            'amount'         => $this->ifSet($post['price']),
            'currency'       => $this->ifSet($post['currency']),
            'status'         => $status,
            'transaction_id' => $this->ifSet($post['id']),
            'invoices'       => $this->unserializeInvoices($invoices),
        ];
    }

    /**
     * Captures a previously authorized payment
     *
     * @param string $reference_id The reference ID for the previously authorized transaction
     * @param string $transaction_id The transaction ID for the previously authorized transaction
     * @return array An array of transaction data including:
     *     - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *     - reference_id The reference ID for gateway-only use with this transaction (optional)
     *     - transaction_id The ID returned by the remote gateway to identify this transaction
     *     - message The message to be displayed in the interface in addition to the standard message for this transaction status (optional)
     */
    public function capture($reference_id, $transaction_id, $amount, array $invoice_amounts = null)
    {
        $this->Input->setErrors($this->getCommonError("unsupported"));
    }

    /**
     * Void a payment or authorization
     *
     * @param string $reference_id The reference ID for the previously submitted transaction
     * @param string $transaction_id The transaction ID for the previously submitted transaction
     * @param string $notes Notes about the void that may be sent to the client by the gateway
     * @return array An array of transaction data including:
     *     - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *     - reference_id The reference ID for gateway-only use with this transaction (optional)
     *     - transaction_id The ID returned by the remote gateway to identify this transaction
     *     - message The message to be displayed in the interface in addition to the standard message for this transaction status (optional)
     */
    public function void($reference_id, $transaction_id, $notes = null)
    {
        $this->Input->setErrors($this->getCommonError("unsupported"));
    }

    /**
     * Refund a payment
     *
     * @param string $reference_id The reference ID for the previously submitted transaction
     * @param string $transaction_id The transaction ID for the previously submitted transaction
     * @param float $amount The amount to refund this card
     * @param string $notes Notes about the refund that may be sent to the client by the gateway
     * @return array An array of transaction data including:
     *     - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *     - reference_id The reference ID for gateway-only use with this transaction (optional)
     *     - transaction_id The ID returned by the remote gateway to identify this transaction
     *     - message The message to be displayed in the interface in addition to the standard message for this transaction status (optional)
     */
    public function refund($reference_id, $transaction_id, $amount, $notes = null)
    {
        $this->Input->setErrors($this->getCommonError("unsupported"));
    }

    /**
     * Serializes an array of invoice info into a string.
     *
     * @param array A numerically indexed array invoices info including:
     *  - id The ID of the invoice
     *  - amount The amount relating to the invoice
     * @return string A serialized string of invoice info in the format of key1=value1|key2=value2
     */
    private function serializeInvoices(array $invoices)
    {
        $str = '';
        foreach ($invoices as $i => $invoice) {
            $str .= ($i > 0 ? '|' : '') . $invoice['id'] . '=' . $invoice['amount'];
        }

        return $str;
    }

    /**
     * Unserializes a string of invoice info into an array.
     *
     * @param string $str A serialized string of invoice info in the format of key1=value1|key2=value2
     * @return array A numerically indexed array invoices info including:
     *  - id The ID of the invoice
     *  - amount The amount relating to the invoice
     */
    private function unserializeInvoices($str)
    {
        $invoices = [];
        $temp = explode('|', $str);
        foreach ($temp as $pair) {
            $pairs = explode('=', $pair, 2);
            if (count($pairs) != 2) {
                continue;
            }
            $invoices[] = ['id' => $pairs[0], 'amount' => $pairs[1]];
        }

        return $invoices;
    }

    private function coingateEnvironment()
    {

        if ($this->meta['coingate_environment'] == 'sandbox') {
            $test_mode = 'sandbox';
        } else {
            $test_mode = 'live';
        }

        return $test_mode;
    }

    private function coingateCallback($id)
    {

        Loader::load(dirname(__FILE__) . DS . 'coingate-php' . DS . 'init.php');

        $test_mode = $this->coingateEnvironment();

        $order = \CoinGate\Merchant\Order::find($id, array(), array(
            'environment' => $test_mode,
            'app_id'      => $this->meta['app_id'],
            'api_key'     => $this->meta['api_key'],
            'api_secret'  => $this->meta['api_secret'],
        ));

        return $order;
    }
}
