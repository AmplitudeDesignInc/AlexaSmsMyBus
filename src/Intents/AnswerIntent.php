<?php

namespace SmsMyBus\Intents;

class AnswerIntent
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
        $stopNumber = $this -> rawRequest['request']['intent']['slots']['stopNumber']['value'];
        $stopNumber = str_replace(array(",", "."), array("",""), $stopNumber);
        $stopNumber = (int)$stopNumber;

        $responseText = 'You are looking for stop '.$stopNumber;

        if (isset($this -> rawRequest['request']['intent']['slots']['routeNumber']['value'])) {
            $routeNumber = $this -> rawRequest['request']['intent']['slots']['routeNumber']['value'];
            $routeNumber = str_replace(array(",", "."), array("",""), $routeNumber);
            $routeNumber = (int)$routeNumber;
            $responseText .= " and route number ".$routeNumber;
        }

        $queryArr['key'] = 'amplitude';
        $queryArr['stopID'] = $stopNumber;
        $queryArr['routeID'] = $routeNumber;

        $url = "http://api.smsmybus.com/v1/getarrivals?".http_build_query($queryArr);
        $reply = file_get_contents($url);
        $replyArr = json_decode($reply, true);
        if (APP_DEBUG === true) {
            error_log($reply);
        }

        if (is_array($replyArr['stop']['route'])) {
            $responseText = $this -> stopHasRoutes($replyArr);
        } elseif (!empty($stopNumber)) {
            $responseText = "We could not find any routes for stop ".$stopNumber;
        } elseif ($replyArr['status'] == 0) {
            $responseText = $replyArr['info'];
        }

        $this -> response->respond($this -> responseText)->withCard("Madison Metro Stop: ".$stopNumber, $this -> responseCardText);
    }

    private function stopHasRoutes($replyArr)
    {
        $this -> responseText = 'Here are the upcoming arrival times for stop '.$replyArr['stop']['stopID'].'. ';

        foreach ($replyArr['stop']['route'] as $key => $data) {
            $text = null;
            $data['destination'] = str_replace("CAP SQR", "Capital Square", $data['destination']);
            $data['destination'] = str_replace("DUNNS MSH", "Dunna Marsh", $data['destination']);
            $data['destination'] = str_replace("CAP SQR", "Capital Square", $data['destination']);
            $data['destination'] = str_replace("NORTH TP", "North Transfer Point", $data['destination']);
            $data['destination'] = str_replace("EAST TP", "East Transfer Point", $data['destination']);
            $data['destination'] = str_replace("WEST TP", "West Transfer Point", $data['destination']);
            $data['destination'] = str_replace("SOUTH TP", "South Transfer Point", $data['destination']);
            $data['destination'] = str_replace("E TOWNE", "East Town", $data['destination']);
            $data['destination'] = str_replace("WEXFD RG", "Wexford Ridge", $data['destination']);
            $data['destination'] = str_replace("WESTFLD", "Westfield", $data['destination']);
            
            $text .= "Route ".$data['routeID']." toward ".$data['destination'];
            $text .= " arrives at ".$data['arrivalTime'].". ";
            $routes[] = $text;
        }
        $this -> responseText = implode(" ", $routes);
        $this -> responseCardText = implode("\r\n", $routes);

    }
}
