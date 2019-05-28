<?php

// This invokes Composer's autoloader so that we'll be able to use Guzzle and any other 3rd party libraries we need.
require __DIR__ . '/vendor/autoload.php';
// Obtain the function name from the _HANDLER environment variable and ensure the function's code is available.
list($handlerFile , $handlerFunction) = explode('.', $_ENV['_HANDLER']);
require_once $_ENV['LAMBDA_TASK_ROOT'] . '/' . $handlerFile . '.php';
// This is the request processing loop. Barring unrecoverable failure, this loop runs until the environment shuts down.
do {
    // Ask the runtime API for a request to handle.
    $request = getNextRequest();
    // Execute the desired function and obtain the response.
    $response = $handlerFunction($request['payload']);
    // Submit the response back to the runtime API.
    sendResponse($request['invocationId'], $response);
} while (true);

function getNextRequest()
{
    $client = new \GuzzleHttp\Client();
    $response = $client->get('http://' . $_ENV['AWS_LAMBDA_RUNTIME_API'] . '/2018-06-01/runtime/invocation/next');

    return [
        'invocationId'      => $response->getHeader('Lambda-Runtime-Aws-Request-Id')[0],
        //'deadline'          => $response->getHeader('Lambda-Runtime-Deadline-Ms')[0],
        //'functionArn'       => $response->getHeader('Lambda-Runtime-Invoked-Function-Arn')[0],
        //'traceId'           => $response->getHeader('Lambda-Runtime-Trace-Id')[0],
        //'clientContext'     => $response->getHeader('Lambda-Runtime-Client-Context')[0],
        //'cognitoIdentity'   => $response->getHeader('Lambda-Runtime-Cognito-Identity')[0],
        'payload' => json_decode((string) $response->getBody(), true)
    ];
}

function sendResponse($invocationId, $response)
{
    $client = new \GuzzleHttp\Client();
    $client->post(
        'http://' . $_ENV['AWS_LAMBDA_RUNTIME_API'] . '/2018-06-01/runtime/invocation/' . $invocationId . '/response',
        ['body' => $response]
    );
}