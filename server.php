<?php
include './libs/time.php';
include './libs/Request.php';
defined('PROJECT_ROOT') or define('PROJECT_ROOT', __DIR__ . '/');
set_time_limit(0); 
$server = stream_socket_server("https://kkutucs.herokuapp.com/7000", $errno, $errorMessage);

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
		echo "Time: ".sec_to_string($prev_time)."\n";
	}
	$client = @stream_socket_accept($server); // wait for 1 min. why?

	if ($client)
	{
		//todo 요청(request) 처리 모듈로 분리하기
		$request = fread($client, 1024);
		//echo "\n - \n" . $request;
		$response = (new Request($request))->getResponse();
		
		fwrite($client, $response, strlen($response));
		fclose($client);
	}
}
?>