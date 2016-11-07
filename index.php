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
        $responseIntent = new SmsMyBus\Intents\AnswerIntent($response, $rawRequest);
    }

    if ($intent == "ThankYouIntent") {
        $response->respond('Hmmmm, not something I know how to do. Say, "help" for instructions.');
    }
    if ($intent == "AMAZON.StartOverIntent") {
        $response->respond('Ask for bus and route number information.');
    }
    if ($intent == "AMAZON.HelpIntent") {
        $responseIntent = new SmsMyBus\Intents\HelpIntent($response, $rawRequest);
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
