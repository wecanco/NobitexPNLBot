<?php


namespace App\Services;


use Http;

class Nobitex
{
    public $apiUrl = 'https://api.nobitex.ir';
    private $headers = [];

    public function __construct($token = null)
    {
        $this->token = $token;
        $this->set_headers();
    }

    private function set_headers() {
        $this->headers['Authorization'] = 'Token '.trim($this->token);
    }

    function orderBook($symbol) {

        return Http::request($this->apiUrl."/v2/orderbook/".strtoupper($symbol),null, $this->headers,'GET');
    }

    function depth($symbol) {

        return Http::request($this->apiUrl."/v2/depth/".strtoupper($symbol),null, $this->headers,'GET');
    }

    function trades($symbol) {

        return Http::request($this->apiUrl."/v2/trades/".strtoupper($symbol),null, $this->headers,'GET');
    }

    function marketStats($srcSymbol, $dstSymbol='usdt') {

        return Http::request($this->apiUrl."/market/stats?srcCurrency=".strtolower($srcSymbol)."&dstCurrency=".strtolower($dstSymbol),null, $this->headers,'GET');
    }

    function marketOHLC($symbol, $from, $to, $resolution='H', $page=1) {

        return Http::request($this->apiUrl."/market/udf/history?symbol=".strtoupper($symbol)."&resolution=".strtoupper($resolution)."&from=$from&to=$to&page=$page",null, $this->headers,'GET');
    }

    function marketGlobalStats() {
        $params = [];

        return Http::request($this->apiUrl."/market/global-stats",$params, $this->headers,'POST');
    }

    function userProfile() {

        return Http::request($this->apiUrl."/users/profile",null, $this->headers,'GET');
    }

    function userWalletGenerateAddress($currency, $wallet =null) {
        $params = [
            'currency' => $currency,
            'wallet' => $wallet,
        ];

        return Http::request($this->apiUrl."/users/wallets/generate-address",$params, $this->headers,'POST');
    }

    function userAddCard($cardNumber, $wallet = 'رسالت') {
        $params = [
            'number' => $cardNumber,
            'bank' => $wallet,
        ];

        return Http::request($this->apiUrl."/users/cards-add",$params, $this->headers,'POST');
    }

    function userAddBankAccount($bankNumber,$shaba, $wallet = 'رسالت') {
        $params = [
            'number' => $bankNumber,
            'shaba' => $shaba,
            'bank' => $wallet,
        ];

        return Http::request($this->apiUrl."/users/accounts-add",$params, $this->headers,'POST');
    }

    function userLimitations() {
        $params = [];

        return Http::request($this->apiUrl."/users/limitations",$params, $this->headers,'POST');
    }

    function userWallets() {

        return Http::request($this->apiUrl."/users/wallets/list",null, $this->headers,'GET');
    }

    function userWallets2($currencies = null, $type='spot') {
        $params = [
            'currencies' => $currencies, // comma,comma
            'type' => $type // spot | margin
        ];

        return Http::request($this->apiUrl."/v2/wallets?".http_build_query($params),null, $this->headers,'GET');
    }

    function userWalletBalance($currency) {
        $params = [
            'currency' => $currency
        ];

        return Http::request($this->apiUrl."/users/wallets/balance",$params, $this->headers,'POST');
    }

    function userWalletTransactions(int $wallet, $page=1, $pageSize=50) {
        $params = [
            'wallet' => $wallet,
            'page' => $page,
            'pageSize' => $pageSize,
        ];

        return Http::request($this->apiUrl."/users/wallets/transactions/list?".http_build_query($params),null, $this->headers,'GET');
    }

    function userWalletDeposits(int $wallet, $page=1, $pageSize=50,$from=null, $to=null) {
        $params = [
            'wallet' => $wallet,
            'page' => $page,
            'pageSize' => $pageSize,
            'from' => $from, //2022-05-12
            'to' => $to, //2022-07-22
        ];

        return Http::request($this->apiUrl."/users/wallets/deposits/list?".http_build_query($params),null, $this->headers,'GET');
    }

    function userMarketFavorites($markets) {
        $params = [
            'market' => $markets, //BTCIRT,DOGEUSDT
        ];

        return Http::request($this->apiUrl."/users/markets/favorite?".http_build_query($params),null, $this->headers,'GET');
    }

    function userDeleteMarketFavorite($markets) {
        $params = [
            'market' => $markets, //All or BTCIRT
        ];

        return Http::request($this->apiUrl."/users/markets/favorite",$params, $this->headers,'DELETE');
    }

    function userMarketAddSpotOrder($type, $srcCurrency, $amount, $price, $dstCurrency='usdt', $execution='market', $mode=null, $stopPrice=null, $stopLimitPrice=null, $clientOrderId=null) {
        $params = [
            'type' => $type, // buy | sell
            'mode' => $mode, // oco
            'execution' => $execution, // market | limit | stop_market | stop_limit
            'srcCurrency' => $srcCurrency, // btc
            'dstCurrency' => $dstCurrency, // rls or usdt
            'amount' => $amount, // in symbol
            'price' => $price, //
            'stopPrice' => $stopPrice, //
            'stopLimitPrice' => $stopLimitPrice, //
            'clientOrderId' => $clientOrderId, //order1
        ];

        return Http::request($this->apiUrl."/market/orders/add",$params, $this->headers,'POST');
    }

    function userOrderStatus($id, $clientOrderId=null) {
        $params = [
            'id' => $id,
            'clientOrderId' => $clientOrderId,
        ];

        return Http::request($this->apiUrl."/market/orders/status",$params, $this->headers,'POST');
    }

    function userOrders($type, $page=1, $pageSize=50, $status='open', $execution=null, $tradeType=null, $srcCurrency=null, $dstCurrency=null, $details=2, $fromId=null) {
        $params = [
            'status' => $status, // all | open | done | close
            'type' => $type, // buy || sell
            'execution' => $execution, // limit | market | stop_limit | stop_market
            'tradeType' => $tradeType, // spot | margin
            'srcCurrency' => $srcCurrency, // btc
            'dstCurrency' => $dstCurrency, // usdt
            'details' => $details, // 1 | 2
            'fromId' => $fromId, //

            'page' => $page,
            'pageSize' => $pageSize,
        ];

        return Http::request($this->apiUrl."/market/orders/list?".http_build_query($params),null, $this->headers,'GET');
    }

    function userUpdateOrderStatus($orderId, $status, $clientOrderId=null) {
        $params = [
            'order' => $orderId,
            'status' => $status, // active | canceled
            'clientOrderId' => $clientOrderId,
        ];

        return Http::request($this->apiUrl."/market/orders/update-status",$params, $this->headers,'POST');
    }

    function userCancelOrders($hours, $execution=null, $tradeType=null, $srcCurrency=null, $dstCurrency=null) {
        $params = [
            'hours' => $hours, // pass hours ago
            'execution' => $execution, // market | limit | stop_market | stop_limit
            'tradeType' => $tradeType, // spot | margin
            'srcCurrency' => $srcCurrency, // btc
            'dstCurrency' => $dstCurrency, // usdt | rls
        ];

        return Http::request($this->apiUrl."/market/orders/cancel-old",$params, $this->headers,'POST');
    }

    function userMarketTrades($srcCurrency, $page=1, $pageSize=50, $dstCurrency=null, $fromId=null) {
        $params = [
            'srcCurrency' => $srcCurrency,
            'dstCurrency' => $dstCurrency, // rls | usdt
            'fromId' => $fromId,
            'page' => $page,
            'pageSize' => $pageSize,
        ];

        return Http::request($this->apiUrl."/market/trades/list?".http_build_query($params),null, $this->headers,'GET');
    }

}
