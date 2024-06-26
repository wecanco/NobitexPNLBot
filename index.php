<?php
global $settings;

include_once('app/Kernel.php');

use App\Services\Nobitex;
use App\Services\TelegramBot;

$telegram = new TelegramBot($_ENV['TELEGRAM_BOT_TOKEN'] ?? '');
$telegram->parse_message();

$nobitex = new Nobitex($_ENV['NOBITEX_TOKEN'] ?? '');

$array_text_message = explode(' ', $telegram->message['message']['text'] ?? '');
$responseTxt = null;

switch ($array_text_message[0]) {
    case "/start":
        $dstSymbol = 'usdt';
        $wallets = $nobitex->userWallets2()['wallets'] ?? null;
        if ($wallets) {
            $symbols = null;
            $walletsIds = null;
            foreach ($wallets as $symbol => &$wallet) {
                $wallet['symbol'] = strtolower($symbol);
                $wallet['balance'] = floatval($wallet['balance']);
                if ($wallet['balance'] <= 0) {
                    continue;
                }
                $symbols[] = strtolower($symbol);
                $walletsIds[] = $wallet['id'];
            }

            $marketStats = $nobitex->marketStats(implode(',', $symbols), $dstSymbol);
            $nobitexMarketStats = $marketStats['stats'] ?? null;
            $globalMarketStats = $marketStats['global'] ?? null;

            if ($nobitexMarketStats) {
                foreach ($wallets as $symbol => &$wallet) {
                    $wallet['price'] = floatval($nobitexMarketStats[$wallet['symbol'] .'-'.$dstSymbol]['latest'] ?? 0);
                    $wallet[$dstSymbol.'-balance'] = $wallet['price'] * $wallet['balance'];
                    $dstSymbolBalance = $wallet[$dstSymbol.'-balance'] ?? 0;
                    if ($dstSymbolBalance > 0.01) {
                        $walletTransactions = $nobitex->userWalletTransactions($wallet['id'])['transactions'];
                        if ($walletTransactions) {
                            $sumAmount = 0;
                            $wallet['buy_prices'] = null;
                            $wallet['avg_price'] = 0;
                            $wallet['pnl_percent'] = null;
                            $wallet['pnl_price'] = 0;

                            foreach ($walletTransactions as $walletTransaction) {
                                if ($walletTransaction['currency'] == $wallet['symbol'] && strpos($walletTransaction['description'],'خرید') !== false) {
                                    if (preg_match('/^خرید\s+([\d.]+)\s+(.*?)\s+([\d.]+)\s+تتر$/', $walletTransaction['description'], $matches)) {
                                        $amount = $matches[1];
                                        $unitPrice = $matches[3];

                                        $sumAmount += $walletTransaction['amount'];
                                        $wallet['buy_prices'][] = floatval($unitPrice);
                                        if ($sumAmount >= $wallet['balance']) {
                                            break;
                                        }
                                    }

                                }

                            }

//                            if ($wallet['symbol'] == 'imx') {
//                                print_r($walletTransactions);
//                                exit();
//                            }

                            if ($wallet['buy_prices']) {
                                $wallet['avg_price'] = floatval(array_sum($wallet['buy_prices']) / count($wallet['buy_prices']));
                                $wallet['pnl_percent'] = (($wallet['price'] - $wallet['avg_price']) / $wallet['avg_price']) * 100;
                                $wallet['pnl_price'] = ($wallet['avg_price'] * $wallet['balance']) * ($wallet['pnl_percent']/100);
                            }
                        }

                        $responseTxt .= "💠 **{$symbol}**: balance: {$wallet['balance']} | price: {$wallet['price']} | {$dstSymbol} balance: {$dstSymbolBalance} | avg price: {$wallet['avg_price']} | PNL: %{$wallet['pnl_percent']} ({$wallet['pnl_price']}$)\n\n";
                    }
                }

            }
        }


        if ($responseTxt) {
            $result = $telegram->sendMessage($telegram->message['chat']['id'] ?? '395943421', $responseTxt);
        }

        break;

}

