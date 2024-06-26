<?php


namespace App\Services;


use Http;

class TelegramBot
{
    private $token;
    public $message;
    private $apiBaseUrl = 'https://api.telegram.org/bot';
    private $apiUrl = '';

    public function __construct($token) {
        $this->token = $token;
        $this->apiUrl = $this->apiBaseUrl.$this->token;
    }

    public function parse_message() {
        global $argv;
        $this->message = json_decode(file_get_contents("php://input"), true);

        if (isset($argv) && $argv && sizeof($argv) > 1) {
            unset($argv[0]);
            if (!isset($this->message['message'])) {
                $this->message['message'] = [];
            }
            $this->message['message']['text'] = trim(implode(' ',$argv));
        }

    }

    public function sendMessage($chatId, $text) {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
//            'disable_web_page_preview' => urlencode($disable_web_page_preview),
//            'reply_to_message_id' => urlencode($reply_to_message_id),
//            'reply_markup' => ($reply_markup),
            'parse_mode' => "HTML",
        ];

        return Http::request($this->apiUrl.'/sendMessage', json_encode($params), [], 'POST');
    }
}