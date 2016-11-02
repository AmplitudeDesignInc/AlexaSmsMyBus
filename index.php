<?php 

session_start();
require_once(__DIR__.'/vendor/autoload.php');
require_once(__DIR__.'/config.php');
ini_set('display_errors', 'On');
ini_set('log_errors', 'Off');
ini_set("error_log", __DIR__."/app-request.log");
ini_set("session.save_path", "/tmp/");

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Alexa\Request\IntentRequest as IntentRequest;
use Alexa\Request\SessionEndedRequest as SessionEndedRequest;
use RSSAlexa\CurlResponseDecorator;
use SmsMyBus\ExtendedRequest as ExtendedRequest;
use Alexa\Request\LaunchRequest as LaunchRequest;

// The Symfony request object.
$request = SymfonyRequest::createFromGlobals();
// See developer.amazon.com and your Application. Will start with "amzn1.echo-sdk-ams.app."
$applicationId = AMAZON_APPLICATION_ID;
// Use Symfony to get the request body.
$rawRequest = $request -> getContent();
if (APP_DEBUG === true) {
    error_log($rawRequest);
}
//json_decode the request.
$rawRequest = json_decode($rawRequest, true);

$now = DateTime::createFromFormat('U.u', microtime(true));
$rawRequest['insert_time'] = $now->format("Y-m-d H:i:s.u");

if ($rawRequest['session']['application']['applicationId'] != AMAZON_APPLICATION_ID) {
    exit;
}

//var_dump($cursor['insert_time']);

//session_id($rawRequest['session']['sessionId']);

$alexa = new ExtendedRequest($rawRequest, $applicationId);
$alexaRequest = $alexa -> fromData($rawRequest);

$response = new \Alexa\Response\Response;

// Handle the intent for an IntentRequest
if ($alexaRequest instanceof IntentRequest) {

    $intent = $rawRequest['request']['intent']['name'];
    if ($intent == 'AnswerIntent') {
        $stopNumber = $rawRequest['request']['intent']['slots']['stopNumber']['value'];
        $stopNumber = str_replace(array(",", "."), array("",""), $stopNumber);
        $stopNumber = (int)$stopNumber;

        $responseText = 'You are looking for stop '.$stopNumber;

        if (isset($rawRequest['request']['intent']['slots']['routeNumber']['value'])) {
            $routeNumber = $rawRequest['request']['intent']['slots']['routeNumber']['value'];
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
        $responseText = 'Here are the upcoming arrival times for stop '.$stopNumber.'. ';
        foreach ($replyArr['stop']['route'] as $key => $data) {
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
            
            $responseText .= "Route ".$routeNumber." toward ".$data['destination'];
            $responseText .= " arrives at ".$data['arrivalTime'].". ";
        }
        $response->respond($responseText);
    }

    if ($intent == "ThankYouIntent") {
        $response->respond('Hmmmm, that is not something I know how to do. Say, "help" for instructions.');
    }
    if ($intent == "AMAZON.StartOverIntent") {
        $response->respond('Ask for bus and route number information.');
    }
    if ($intent == "AMAZON.HelpIntent") {
        $response->respond('Ask for bus and route number information.');
    }

    if ($intent == "AMAZON.StopIntent" || $intent == "AMAZON.CancelIntent") {
        $response->respond('Goodbye ');
        //session_destroy();
        $response -> shouldEndSession = true;
    }
}

if ($alexaRequest instanceof LaunchRequest) {
    $response -> respond("Ask for bus and route number information.");
}

// Handle the session end intent.
if ($alexaRequest instanceof SessionEndedRequest) {
    $response->respond('Goodbye');
    session_destroy();
    $response -> shouldEndSession = true;
}
header('Content-Type: application/json');
echo json_encode($response->render());
