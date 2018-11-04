<?php
$str = $_POST['str'];
$client = stream_socket_client("tcp://127.0.0.1:7000", $errno, $errorMessage);
if ($client === false)
{
	throw new UnexpectedValueException("Failed to connect: $errno - $errorMessage");
}

/*
 * generate a message
 */
// $startLine = 'GET / HTTP/1.0';

// $headers = [
// 	'Host: localhost',
// 	'Accept: */*',
// ];
// $header = implode(PHP_EOL, $headers);

// $emptyLine = PHP_EOL;

// $body = $str;

/*
 * request
 */
/*$message = implode(PHP_EOL, [
	$startLine,
	$header,
	$emptyLine,
	$body,
]);*/
$message = "KKuTuCS".PHP_EOL.$str;
fwrite($client, $message);
echo $message;
/*
 * response
 */
while (true) {
	$response = stream_get_contents($client, 1);
	//echo "HELLO";
	echo $response;
	$info = stream_get_meta_data($client);
	if ($info['eof'] || $info['unread_bytes'] === 0) {
		break;
	}
}

/*
 * bye bye
 */
fclose($client);
?>