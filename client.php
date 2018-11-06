<?php
$str = "A";//$_POST['str'];

$client = stream_socket_client("tcp://127.0.0.1:5000", $errno, $errorMessage);
if ($client === false)
{
	echo "Failed to connect: $errno - $errorMessage";
	fclose($client);
	//throw new UnexpectedValueException("Failed to connect: $errno - $errorMessage");
}
else
{
	/*
	* generate a message
	*/
	$startLine = 'KKUTUCS / HTTP/1.0';
	$headers = ['Host: localhost', 'Accept: */*'];
	$header = implode(PHP_EOL, $headers);
	$emptyLine = PHP_EOL;
	$body = "KKuTuCS " . $str;
	$message = implode(PHP_EOL, [
		$startLine,
		$header,
		$emptyLine,
		$body
	]);

	/*
	* request
	*/
	fwrite($client, $message);

	/*
	* response
	*/
	$res = '';
	while (true)
	{
		$response = stream_get_contents($client, 1);
		$res .= $response;
		$info = stream_get_meta_data($client);

		if ($info['eof'] || $info['unread_bytes'] === 0)
			break;
	}
	//echo "??";
	echo $res;

	/*
	* bye bye
	*/
	fclose($client);
}
?>