<?php

namespace WrenchPusher;

use Wrench\Client;
use WrenchPusher\PusherMessage;

/**
 * Description of PusherClient
 *
 * @author Westin Pigott
 */
class PusherClient extends Client {

    const CLIENT_NAME = 'PHPPusherClient';
    const VERSION = '0.1';
    const PROTOCOL_ID = '5';
    const TRANSPORT_NORMAL = 'ws';
    const TRANSPORT_SECURE = 'wss';
    const PUSHER_URL = 'ws.pusherapp.com';
    const PUSHER_PORT_NORMAL = '80';
    const PUSHER_PORT_SECURE = '443';
    const SUBSCRIBE_EVENT = 'pusher:subscribe';

    protected $isSecure = FALSE;
    private $lastConnectionTime;
    private $keepAliveDuration = 120;

    public function __construct($appId, array $options = array()) {
        $this->handleLocalOptions($options);

        $uri = $this->buildURI();
        $connectionString = $uri . $this->buildConnectionString($appId);

        parent::__construct($connectionString, $uri, $options);
    }

    protected function handleLocalOptions(array $options) {
        $this->isSecure = (array_key_exists('isSecure', $options) && ($options['isSecure']));

        if (array_key_exists('keepAliveDuration', $options)) {
            $this->keepAliveDuration = $options['keepAliveDuration'];
        }
    }

    protected function buildURI() {
        if ($this->isSecure) {
            return self::TRANSPORT_SECURE . '://' . self::PUSHER_URL . ':' . self::PUSHER_PORT_SECURE;
        }
        return self::TRANSPORT_NORMAL . '://' . self::PUSHER_URL . ':' . self::PUSHER_PORT_NORMAL;
    }

    protected function buildConnectionString($appId) {
        return '/app/' . $appId . '?client=' . self::CLIENT_NAME . '&version=' . self::VERSION . '&protocol=' . self::PROTOCOL_ID;
    }

    public function connect() {
        return parent::connect();
    }

    /**
     * Receives data sent by the server
     *
     * @param callable $callback
     * @return array<Payload> Payload received since the last call to receive()
     */
    public function receive() {
        if (!$this->isConnected()) {
            return false;
        }

        $data = $this->socket->receive();

        if (!$data) {
            return NULL;
        }

        $this->payloadHandler->handle($data);

        $messages = array();

        foreach ($this->received as $payload) {
            $message = new PusherMessage();
            $message->setFromPayload($payload);
            $messages[] = $message;
            unset($message);
        }

        $this->received = array();

        return $messages;
    }

    public function subscribeToChannel($channelName) {
        return $this->sendData(json_encode(array(
                            'event' => self::SUBSCRIBE_EVENT,
                            'data' => array(
                                'channel' => $channelName,
                            ),
                        )));
    }

    protected function resetTime() {
        $this->lastConnectionTime = microtime(true);
    }

    protected function isTimeToKeepAlive() {
        return ((microtime(true) - $this->lastConnectionTime) > $this->keepAliveDuration);
    }

    public function keepAlive() {
        if ($this->isTimeToKeepAlive()) {
            $this->sendData(json_encode(array(
                        'event' => 'pusher:ping',
                    )));
            $this->resetTime();
        }
    }

}

?>
