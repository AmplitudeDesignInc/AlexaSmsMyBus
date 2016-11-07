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
        $this -> responseText = 'Ask for Madison Metro bus and route number information.';
        $this -> responseText .= "\r\n";        
        $this -> responseText .= 'For example, say, "Hey Alexa, ask Madison Metro for stop number zero one eight zero route forty-seven."';
        $this -> responseText .= "\r\n";
        $this -> responseText .= "We will return upcoming schedule information.";
        $this -> response->respond($this -> responseText)->withCard("Madison Metro Help:" ,$this -> responseText);
    }
}
