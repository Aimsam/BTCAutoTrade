<?php

namespace WrenchPusher;

use Wrench\Payload\HybiPayload;

/**
 * Description of PusherMessage
 *
 * @author Westin Pigott
 */
class PusherMessage {

    protected $event;
    protected $channel;
    protected $data;

    public function getEvent() {
        return $this->event;
    }

    public function setEvent($event) {
        $this->event = $event;
    }

    public function getChannel() {
        return $this->channel;
    }

    public function setChannel($channel) {
        $this->channel = $channel;
    }

    public function getData() {
        return $this->data;
    }

    public function setData($data) {
        $this->data = $data;
    }

    public function setFromPayload(HybiPayload $payload) {
        if ($payload->isComplete()) {
            $decoded = json_decode($payload->getPayload());
            if (isset($decoded->event)) {
                $this->setEvent($decoded->event);
            }
            if (isset($decoded->channel)) {
                $this->setChannel($decoded->channel);
            }
            if (isset($decoded->data)) {
                //try to break down json if possible
                $data = json_decode($decoded->data);
                if ($data) {
                    $this->setData($data);
                } else {
                    $this->setData($decoded->data);
                }
            }
        }
    }

}

?>
