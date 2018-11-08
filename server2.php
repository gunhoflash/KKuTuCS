<?php
/**
 * 
 * This file will be deleted.
 * 
 */
include './libs/time.php';
include './libs/KKuTuCSRequest.php';
defined('PROJECT_ROOT') or define('PROJECT_ROOT', __DIR__ . '/');

// Don't stop, server!
set_time_limit(0); 

// Server for listen KKuTuCS request.
$server = stream_socket_server("tcp://0.0.0.0:7001", $errno, $errorMessage);

// If error, throw Exception.
if ($server === false) throw new UnexpectedValueException("Could not bind to socket: $errorMessage");
// if (!stream_set_blocking($server, false)) echo "non-blocking fail\n";

/**
 * Variables
 */
$array_client = array();

echo "Server started.\n";
while (true)
{
	// Wait for 1 min. why?
	$client = @stream_socket_accept($server);

	if ($client)
	{
		// log
		echo "[Time: ".sec_to_string(time())."]\n";

		// Modulizing it is better.
		$requestMessage = fread($client, 2048);
		//echo "Request Message:\n".$request."\n";
		//$request = new Request($requestMessage);

		// Get response.
		//$response = $request->getResponse();
		//echo " Body: ".$request->getRequestBody()."\n";
		//echo " Uri: ".$request->getRequestUri()."\n";
		//echo " Method: ".$request->getRequestMethod()."\n\n";

		//if ($request->getRequestMethod() == "KKUTUCS")
		$response = $requestMessage . " --- ";
			//echo $response . "\n";
		fwrite($client, $response, strlen($response));
		fclose($client);
	}
}
?>