<?php
error_reporting(E_ERROR);
ini_set('ignore_repeated_errors', TRUE);
ini_set('display_errors', FALSE);
ini_set('log_errors', TRUE);
ini_set('error_log', dirname(__FILE__).'/errors');


global $settings;
include_once('app/Kernel.php');

use App\Services\Nobitex;
use App\Services\TelegramBot;

$telegram = new TelegramBot($_ENV['TELEGRAM_BOT_TOKEN'] ?? '');
$telegram->parse_message();

$nobitex = new Nobitex($_ENV['NOBITEX_TOKEN'] ?? '');

$array_text_message = explode(' ', $telegram->message['message']['text'] ?? '');
$responseTxt = null;
$admins = explode(',', str_replace([' ', '@'],'',strtolower($_ENV['BOT_ADMINS'])));

if (!in_array(strtolower($telegram->message['message']['chat']['username'] ?? ''), $admins) && ($_ENV['APP_RUN_IN_TERMINAL'] ?? false) == false) {
    $responseTxt = '❌ شما دسترسی به این ربات را ندارید.';
    goto send_message_step;
}

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
                    unset($wallets[$symbol]);
                    continue;
                }
                $symbols[] = strtolower($symbol);
                $walletsIds[] = $wallet['id'];
            }

            $marketStats = $nobitex->marketStats(implode(',', $symbols), $dstSymbol);
            $nobitexMarketStats = $marketStats['stats'] ?? null;
            $globalMarketStats = $marketStats['global'] ?? null;
            $totalDestAmount = 0;
            $totalDestPNLPercent = 0;
            $totalDestPNLAmount = 0;

            if ($nobitexMarketStats) {
                foreach ($wallets as $symbol => &$wallet) {
                    $wallet['price'] = floatval($nobitexMarketStats[$wallet['symbol'] .'-'.$dstSymbol]['latest'] ?? 0);

                    if ($wallet['price'] == 0) {
                        unset($wallets[$symbol]);
                        continue;
                    }

                    $wallet[$dstSymbol.'-balance'] = $wallet['price'] * $wallet['balance'];
                    $wallet[$dstSymbol.'-balance'] = number_format($wallet[$dstSymbol.'-balance'] ?? 0, 2);
                    if ($wallet[$dstSymbol.'-balance'] > 0.1) {
                        $walletTransactions = $nobitex->userWalletTransactions($wallet['id'])['transactions'];
                        if ($walletTransactions) {
                            $sumAmount = 0;
                            $wallet['buy_prices'] = null;
                            $wallet['avg_buy_price'] = 0;
                            $wallet['avg_buy_balance'] = 0;
                            $wallet['pnl_percent'] = 0;
                            $wallet['pnl_price'] = 0;

                            foreach ($walletTransactions as $walletTransaction) {
                                $walletTransaction['description'] = str_replace([','], '', $walletTransaction['description']);
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

//                            if ($wallet['symbol'] == 'mkr') {
//                                print_r($wallet['buy_prices']);
//                                exit();
//                            }

                            $dstSymbolBalance = floatval($wallet[$dstSymbol.'-balance'] ?? 0);
//                            $dstSymbolBalance = floatval($wallet['balance']) * floatval($wallet['price']);

                            if ($wallet['buy_prices']) {
                                $wallet['avg_buy_price'] = floatval(array_sum($wallet['buy_prices']) / count($wallet['buy_prices']));
                                $wallet['pnl_percent'] = number_format((($wallet['price'] - $wallet['avg_buy_price']) / $wallet['avg_buy_price']) * 100, 2);
                            }

                            $wallet['avg_buy_balance'] = number_format($wallet['avg_buy_price'] * $wallet['balance'], 2);
                            $wallet['pnl_price'] = number_format(floatval($wallet['avg_buy_balance']) * ($wallet['pnl_percent']/100), 2);


                            $totalDestAmount += $dstSymbolBalance;
                            $totalDestPNLPercent += floatval($wallet['pnl_percent']);
                            $totalDestPNLAmount += floatval($wallet['pnl_price']);
                        }

//                        $responseTxt .= "💠 <b>{$symbol}</b>\n balance: {$wallet['balance']} \n price: {$wallet['price']} \n {$dstSymbol} balance: {$wallet[$dstSymbol.'-balance']} \n avg buy price: {$wallet['avg_buy_price']} \n PNL(%): {$wallet['pnl_percent']} \n PNL($): {$wallet['pnl_price']}\n---------------------\n";
                    } else {
                        unset($wallets[$symbol]);
                    }
                }

                unset($wallet);

                usort($wallets, function ($item1, $item2) {
                    return intval($item1['pnl_percent']) <=> intval($item2['pnl_percent']);
                });

                $avgDestPNLPercent = number_format($totalDestPNLPercent/count($wallets), 2);

                foreach ($wallets as $wallet) {
                    $symbol = strtoupper($wallet['symbol']);
                    if ($_ENV['APP_RUN_IN_TERMINAL'] ?? false) {
                        echo "{$symbol}: balance: {$wallet['balance']} | current price: {$wallet['price']} | avg buy price: {$wallet['avg_buy_price']} | {$dstSymbol} balance: {$wallet[$dstSymbol.'-balance']} | before {$dstSymbol} balance: {$wallet['avg_buy_balance']} | PNL: %{$wallet['pnl_percent']} ({$wallet['pnl_price']}$)\n";
                    }
                    $responseTxt .= "💠 <b>{$symbol}</b>\n balance: {$wallet['balance']} \n current price: {$wallet['price']} \n avg buy price: {$wallet['avg_buy_price']} \n current {$dstSymbol} balance: {$wallet[$dstSymbol.'-balance']} \n  before {$dstSymbol} balance: {$wallet['avg_buy_balance']} \n PNL(%): {$wallet['pnl_percent']} \n PNL($): {$wallet['pnl_price']}\n---------------------\n";
                }
                $totalDestAmount = number_format($totalDestAmount, 2);

                if ($_ENV['APP_RUN_IN_TERMINAL'] ?? false) {
                    echo "Total {$dstSymbol}: {$totalDestAmount} | {$avgDestPNLPercent}% | {$totalDestPNLAmount}$ \n";
                }

                $responseTxt .= "<b>Total {$dstSymbol}:</b> {$totalDestAmount} | avg: {$avgDestPNLPercent}% ({$totalDestPNLAmount}$)";
            }
        }

        break;

}

send_message_step:
if ($responseTxt) {
    $result = $telegram->sendMessage($telegram->message['message']['chat']['id'] ?? '@'.current($admins), $responseTxt);
}


