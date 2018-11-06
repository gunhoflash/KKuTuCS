<?php
include './libs/time.php';
include './libs/Request.php';
defined('PROJECT_ROOT') or define('PROJECT_ROOT', __DIR__ . '/');
set_time_limit(0); 

$prev_time = 0;
while (true)
{
	$now_time = time();
	if ($now_time > $prev_time)
	{
		$prev_time = $now_time;
		// echo "Time: ".sec_to_string($prev_time)."\n";
	}
	$client = ($server); // wait for 1 min. why?

	if ($client)
	{
		//todo 요청(request) 처리 모듈로 분리하기
		$request = fread($client, 1024);
		//echo "\n - \n" . $request;
		//$response = (new Request($request))->getResponse();
		$response = "response here!";
		
		fwrite($client, $response, strlen($response));
		fclose($client);
	}
}
?>