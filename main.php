<?php

class Helper {
    private static $base_url = "https://cryptapi.io/api";
    private $valid_coins = ['btc', 'bch', 'eth', 'ltc', 'xmr', 'iota'];
    private $own_address = null;
    private $callback_url = null;
    private $coin = null;
    private $pending = false;
    private $parameters = [];

    public static $COIN_MULTIPLIERS = [
        'btc' => 100000000,
        'bch' => 100000000,
        'ltc' => 100000000,
        'eth' => 1000000000000000000,
        'iota' => 1000000,
        'xmr' => 1000000000000,
    ];

    public function __construct($coin, $own_address, $callback_url, $parameters=[], $pending=false) {

        if (!in_array($coin, $this->valid_coins)) {
            $vc = print_r($this->valid_coins, true);
            throw new Exception("Unsupported Coin: {$coin}, Valid options are: {$vc}");
        }

        $this->own_address = $own_address;
        $this->callback_url = $callback_url;
        $this->coin = $coin;
        $this->pending = $pending ? 1 : 0;
        $this->parameters = $parameters;

    }

    public function get_address() {

        if (empty($this->own_address) || empty($this->coin) || empty($this->callback_url)) return null;

        $callback_url = $this->callback_url;
        if (!empty($this->parameters)) {
            $req_parameters = http_build_query($this->parameters);
            $callback_url = "{$this->callback_url}?{$req_parameters}";
        }

        $ca_params = [
            'callback' => $callback_url,
            'address' => $this->own_address,
            'pending' => $this->pending,
        ];

        $response = Helper::_request($this->coin, 'create', $ca_params);

        if ($response->status == 'success') {
            return $response->address_in;
        }

        return null;
    }

    public static function generate_nonce($len = 32)
    {
        $data = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');

        $nonce = [];
        for ($i = 0; $i < $len; $i++) {
            $nonce[] = $data[mt_rand(0, sizeof($data) - 1)];
        }

        return implode('', $nonce);
    }

    private static function _request($coin, $endpoint, $params=[]) {
        $base_url = Helper::$base_url;

        if (!empty($params)) $data = http_build_query($params);

        $url = "{$base_url}/{$coin}/{$endpoint}/";

        if (!empty($data)) $url .= "?{$data}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response);
    }
}

function main() {
    $my_order_id = 1234;
    $nonce = Helper::generate_nonce();
    $callback_base_url = 'http://webhook.site/7ed2757f-ae67-49eb-a816-9615680871e3';  # Add your own here
    $my_btc_address = '1PE5U4temq1rFzseHHGE2L8smwHCyRbkx3';

    $params = [
        'order_id' => $my_order_id,
        'nonce' => $nonce,
    ];

    $cryptapi = new Helper('btc', $my_btc_address, $callback_base_url, $params, true);
    $payment_address = $cryptapi->get_address();

    if (!empty($payment_address))  {
        # Show the address to your customer or create a QR Code with it.
    }
}