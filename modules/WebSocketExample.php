<?php
namespace phpBrute\Module;

use \phpBrute\Helper;

class WebSocketExample extends \phpBrute\Module
{
    public $info = [
        'name' => 'WebSocket Example',
        'author' => 'Yani',
        'version' => 1,
        'info' => 'Uses websockets. No proxy support',
    ];

    public function run($data, $proxy, $useragent, array $settings = [], array $run_once_data = [])
    {
        $string = 'Hello WebSocket.org!';

        // https://github.com/Textalk/websocket-php
        $client = new \WebSocket\Client("wss://echo.websocket.org/");

        $client->send($string);

        if ($client->receive() === $string) {
            return $this->return(self::SUCCESS);
        }

        return $this->return(self::INVALID);
    }
}
