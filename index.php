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
        $response->respond('You are looking for stop '.$stopNumber);
    }

    if ($intent == "ThankYouIntent") {
        $response->respond('Hmmmm, that is not something I know how to do. Say, "help" for instructions.');
    }
    if ($intent == "AMAZON.StartOverIntent") {
        $response->respond('Begin by asking,  "get buoy number or name." Say help for more information.');
    }
    if ($intent == "AMAZON.HelpIntent") {
        $response->respond('Begin by asking, "Hey Alexa, ask Buoy Reading to get four four zero zero seven. See N O A A website to get a buoy names and numbers.');
    }

    if ($intent == "AMAZON.StopIntent" || $intent == "AMAZON.CancelIntent") {
        $response->respond('Goodbye ');
        //session_destroy();
        $response -> shouldEndSession = true;
    }
}

if ($alexaRequest instanceof LaunchRequest) {
    $response -> respond("Hello. Begin by saying, 'get buoy number or name. You can also say read my list.");
}

// Handle the session end intent.
if ($alexaRequest instanceof SessionEndedRequest) {
    $response->respond('Goodbye');
    session_destroy();
    $response -> shouldEndSession = true;
}
header('Content-Type: application/json');
echo json_encode($response->render());
