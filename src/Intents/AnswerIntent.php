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
        $stopNumber = str_pad($stopNumber, 4, "0", STR_PAD_LEFT);

        $responseText = 'You are looking for stop '.$stopNumber;
        $routeNumber = null;
        if (
            isset($this -> rawRequest['request']['intent']['slots']['routeNumber']['value']) && 
            strlen($this -> rawRequest['request']['intent']['slots']['routeNumber']['value']) > 0
        ) {
            $routeNumber = $this -> rawRequest['request']['intent']['slots']['routeNumber']['value'];
            $routeNumber = str_replace(array(",", "."), array("",""), $routeNumber);
            $routeNumber = (int)$routeNumber;
            $responseText .= " and route number ".$routeNumber;
        }

        $queryArr['key'] = 'amplitude';
        $queryArr['stopID'] = $stopNumber;

        if ($routeNumber != null) {
            $queryArr['routeID'] = $routeNumber;
        }
        $url = "http://api.smsmybus.com/v1/getarrivals?".http_build_query($queryArr);
        $reply = file_get_contents($url);
        $replyArr = json_decode($reply, true);
        if (APP_DEBUG === true) {
            error_log($reply);
        }

        if (isset(is_array($replyArr['stop']) && is_array($replyArr['stop']['route']) && count($replyArr['stop']['route']) > 0) {
            $responseText = $this -> stopHasRoutes($replyArr);
        } elseif (!empty($stopNumber)) {
            $this -> responseText = "We could not find any routes for stop ".$stopNumber;
            if ($routeNumber != null) {
                $this -> responseText .= " and route number ".$routeNumber;
            }
            $this -> responseCardText = $this -> responseText;
        }

        if (isset($replyArr['info'])) {
            $this -> responseText = $replyArr['info'];
        }

        if (isset($replyArr['status']) && $replyArr['status'] == -1) {
            $this -> responseText = $replyArr['description'];
            $this -> responseCardText = $replyArr['description'];
        }

        $this -> response->respond($this -> responseText)->withCard("Madison Metro Stop: ".$stopNumber, $this -> responseCardText);
    }

    private function stopHasRoutes($replyArr)
    {
        $this -> responseText = 'Here are the upcoming arrival times for stop '.$replyArr['stop']['stopID'].'. ';

        foreach ($replyArr['stop']['route'] as $key => $data) {
            $text = null;
            $data['destination'] = str_replace("CAP SQR", "Capital Square", $data['destination']);
            $data['destination'] = str_replace("DUNNS MSH", "Dunn's Marsh", $data['destination']);
            $data['destination'] = str_replace("CAP SQR", "Capital Square", $data['destination']);
            $data['destination'] = str_replace("NORTH TP", "North Transfer Point", $data['destination']);
            $data['destination'] = str_replace("EAST TP", "East Transfer Point", $data['destination']);
            $data['destination'] = str_replace("WEST TP", "West Transfer Point", $data['destination']);
            $data['destination'] = str_replace("SOUTH TP", "South Transfer Point", $data['destination']);
            $data['destination'] = str_replace("E TOWNE", "East Town", $data['destination']);
            $data['destination'] = str_replace("WEXFD RG", "Wexford Ridge", $data['destination']);
            $data['destination'] = str_replace("WESTFLD", "Westfield", $data['destination']);
            $data['destination'] = str_replace("MIDVALE", "Midvale", $data['destination']);
            $data['destination'] = str_replace("CHALET", "Chalet", $data['destination']);
            $data['destination'] = str_replace("MOHAWK", "Mohawk", $data['destination']);
            $data['destination'] = str_replace("OLD UNIV", "Old University", $data['destination']);
            $data['destination'] = str_replace("MIDLTON", "Middleton", $data['destination']);
            $data['destination'] = str_replace("DUTCH ML", "Dutch Mill", $data['destination']);
            $data['destination'] = str_replace("GRNTREE", "Greentree", $data['destination']);
            $data['destination'] = str_replace("ARBOR HL", "Arbor Hills", $data['destination']);
            $data['destination'] = str_replace(":W WASH", ":West Washington", $data['destination']);
            $data['destination'] = str_replace("U CAMPUS", "UW Campus", $data['destination']);
            $data['destination'] = str_replace("MATC", "Madison Area Technical College", $data['destination']);
            $data['destination'] = str_replace("W CAMPUS", "West Campus", $data['destination']);
            $data['destination'] = str_replace("W TOWNE", "West Towne", $data['destination']);
            $data['destination'] = str_replace("WASHNGTN", "Washington", $data['destination']);

            $data['destination'] = str_replace("MIN PT", "Mineral Point", $data['destination']);

            
            $text .= "Route ".$data['routeID']." toward ".$data['destination'];
            $text .= " arrives at ".$data['arrivalTime'].". ";
            $routes[] = $text;
        }
        $this -> responseText = implode(" ", $routes);
        $this -> responseCardText = implode("\r\n", $routes);

    }
}
