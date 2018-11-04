<?php
$str = $_POST['str'];
$client = stream_socket_client("https://kkutucs.herokuapp.com/7000", $errno, $errorMessage);
if ($client === false)
{
	echo "Failed to connect: $errno - $errorMessage";
	fclose($client);
	return;
	//throw new UnexpectedValueException("Failed to connect: $errno - $errorMessage");
}
else
{
	/*
	* generate a message
	*/
	$message = "KKuTuCS".PHP_EOL.$str;
	fwrite($client, $message);

	/*
	* response
	*/
	while (true) {
		$response = stream_get_contents($client, 1);
		echo $response;
		$info = stream_get_meta_data($client);

		if ($info['eof'] || $info['unread_bytes'] === 0)
			break;
	}

	/*
	* bye bye
	*/
	fclose($client);
}


?>