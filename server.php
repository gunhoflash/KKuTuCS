<?php
/**
 * 
 * This file will be deleted.
 * 
 */
include './libs/time.php';
include './libs/Request.php';
defined('PROJECT_ROOT') or define('PROJECT_ROOT', __DIR__ . '/');
set_time_limit(0); 
$server = stream_socket_server("tcp://0.0.0.0:7000", $errno, $errorMessage);

if ($server === false)
{
	throw new UnexpectedValueException("Could not bind to socket: $errorMessage");
}
$prev_time = 0;
while (true)
{
	$now_time = time();
	if ($now_time > $prev_time)
	{
		$prev_time = $now_time;
		echo "[Time: ".sec_to_string($prev_time)."]\n";
	}
	$client = @stream_socket_accept($server); // wait for 1 min. why?

	if ($client)
	{
		// Modulizing it is better.
		$requestMessage = fread($client, 2048);
		//echo "Request Message:\n".$request."\n";
		$request = new Request($requestMessage);

		// Get response.
		$response = $request->getResponse();
		echo " Body: ".$request->getRequestBody()."\n";
		echo " Uri: ".$request->getRequestUri()."\n";
		echo " Method: ".$request->getRequestMethod()."\n\n";

		if ($request->getRequestMethod() == "KKUTUCS")
		{
			$clientKKuTuCS = stream_socket_client("tcp://127.0.0.1:7001", $errno, $errorMessage);
			if ($clientKKuTuCS === false)
			{
				echo "Failed to connect: $errno - $errorMessage";
				fclose($clientKKuTuCS);
				//throw new UnexpectedValueException("Failed to connect: $errno - $errorMessage");
			}
			else
			{
				fwrite($clientKKuTuCS, "KKuTuCS ?");

				$res = '';
				while (true)
				{
					$responseKKuTuCS = stream_get_contents($clientKKuTuCS, 1);
					$res .= $responseKKuTuCS;
					$info = stream_get_meta_data($clientKKuTuCS);

					if ($info['eof'] || $info['unread_bytes'] === 0)
						break;
				}
				//echo "??";
				echo $res;

				/*
				* bye bye
				*/
				fclose($clientKKuTuCS);
			}
		}
		fwrite($client, $response, strlen($response));
		fclose($client);
	}
}
?>