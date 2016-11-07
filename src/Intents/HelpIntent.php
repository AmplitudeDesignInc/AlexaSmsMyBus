<?php

namespace SmsMyBus\Intents;

class HelpIntent
{
    private $response;

    private $rawRequest = array();

    public $responseText = '';

    public $responseCardText = '';

    public function __construct(\Alexa\Response\Response &$response, $rawRequest)
    {
        $this -> response = $response;
        $this -> rawRequest = $rawRequest;
        $this -> setResponse();
    }

    public function setResponse()
    {
        $this -> responseText = 'Ask for Madison Metro bus and route number information. For example, say, "Ask Alexa for stop nummber one one zero one route seventy."';
        $this -> response->respond($this -> responseText)->withCard("Madison Metro Help:" ,$this -> responseText);
    }
}
