<?php
// Server for listen KKuTuCS request.
// refer to https://www.cuelogic.com/blog/php-and-html5-websocket-server-and-client-communication

include './libs/time.php';
include './libs/KKuTuCSRequest.php';
defined('PROJECT_ROOT') or define('PROJECT_ROOT', __DIR__ . '/');

// Don't stop, server!
set_time_limit(0); 

// set some variables.
$host = "0.0.0.0";
$port = 7002;

// Create a socket, bind to port, and start listening for connections.
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("Could not create socket.\n");
$result = socket_bind($socket, $host, $port) or die("Could not bind to socket.\n");
$result = socket_listen($socket, 20) or die("Could not set up socket listener.\n");


$client = array($socket);
$tWrite = NULL;
$tExcept = NULL;
$timeout = 0;
while(1)
{
	$read = $client;
	$num_changed_sockets = socket_select($read, $tWrite, $tExcept, 0);

	if($num_changed_sockets === false)
		// Error
		continue;
	else if ($num_changed_sockets == 0)
		// nothing new
		continue;

	if (in_array($socket, $read))
	{
		$client[] = $newSocket = socket_accept($socket);
		$bytes = @socket_recv($newSocket, $data, 2048, 0);
		if ($bytes == 0) continue;
		handshake($newSocket, $data, $socket);
		echo "Connected: ".socketToString($newSocket)."\n";
		$key = array_search($socket, $read);
		unset($read[$key]);
	}

	foreach ($read as $readSocket)
	{
		$data = @socket_read($readSocket, 4096, PHP_BINARY_READ);
		$socketString = socketToString($readSocket);

		if ($data === false)
		{
			$key = array_search($readSocket, $client);
			unset($client[$key]);
			echo "Disconnected: $socketString\n";
			socket_close($readSocket);
			continue;
		}

		//$data = trim($data);

		$decoded_data = unmask($data);
		$response = "$socketString: $decoded_data\n";

		echo "$socketString < $decoded_data\n";
		echo "$socketString > $response\n";

		try
		{
			$response = encode($response);
		}
		catch (Exception $e)
		{
			$response = NULL;
			echo "Exception on encode: ".$e->getMessage()."\n";
		}

		if ($response == NULL) continue;

		socket_write($readSocket, $response);

		//socket_close($client);
		foreach ($client as $sendSocket)
		{
			if ($sendSocket == $readSocket || $sendSocket == $socket) continue;
			socket_write($sendSocket, $response);
		}
	}
}

function msg($msg)
{
	echo "SERVER >> ".$msg;
}

function socketToString($socket)
{
	socket_getpeername($socket, $IP, $PORT);
	return "{".$IP.":".$PORT."}";
}
//

//
/*
$flag_handshake = false;
$client = null;
do
{
	// Accept incoming connections to handle communication.
	echo "[do]\n";

	if (!$client)
	{
		echo "[client false 1]\n";
		$client = socket_accept($socket) or die("Could not accept incoming connection.\n");
		echo "[client false 2]\n";
	}

	// Receive data with maximum length of 2048.
	$bytes = @socket_recv($client, $data, 2048, 0);

	// Do handshake or communicate.
	if ($flag_handshake == false)
	{
		// Handle Exception
		if ((int)$bytes == 0) continue;

		echo "Handshaking with: ".$data."\n";
		if (handshake($client, $data, $socket))
			$flag_handshake = true;
	}
	else if ($flag_handshake == true)
	{
		if ($data != "")
		{
			$decoded_data = unmask($data);
			echo "< ".$decoded_data."\n";
			$response = strrev($decoded_data);
			socket_write($client, encode($response));
			echo "> ".$response."\n";
			//socket_close($client);
			$client = null;
			$flag_handshake = false;
		}
	}
} while (true);

// close sockets
socket_close($client);
socket_close($socket);

*/

function unmask($payload)
{
	$length = ord($payload[1]) & 127;

	if ($length == 126)
	{
		$masks = substr($payload, 4, 4);
		$data = substr($payload, 8);
	}
	else if ($length == 127)
	{
		$masks = substr($payload, 10, 4);
		$data = substr($payload, 14);
	}
	else
	{
		$masks = substr($payload, 2, 4);
		$data = substr($payload, 6);
	}

	$text = '';
	for ($i = 0; $i < strlen($data); ++$i)
		$text .= $data[$i] ^ $masks[$i % 4];

	return $text;
}

function encode($text)
{
	// 0x1 text frame (FIN + opcode)
	$b1 = 0x80 | (0x1 & 0x0f);
	$length = strlen($text);

	if ($length <= 125)
		$header = pack('CC', $b1, $length);
	else if ($length > 125 && $length < 65536)
		$header = pack('CCS', $b1, 126, $length);
	else if ($length >= 65536)
		$header = pack('CCN', $b1, 127, $length);

	return $header.$text;
}

function handshake($client, $headers, $socket)
{
	if (preg_match("/Sec-WebSocket-Version: (.*)\r\n/", $headers, $match))
		$version = $match[1];
	else
	{
		print("The client doesn't support WebSocket");
		return false;
	}

	if ($version == 13)
	{
		// Extract header variables
		if (preg_match("/GET (.*) HTTP/", $headers, $match))
			$root = $match[1];
		if (preg_match("/Host: (.*)\r\n/", $headers, $match))
			$host = $match[1];
		if (preg_match("/Origin: (.*)\r\n/", $headers, $match))
			$origin = $match[1];
		if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $headers, $match))
			$key = $match[1];

		$acceptKey = $key.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
		$acceptKey = base64_encode(sha1($acceptKey, true));

		$upgrade = "HTTP/1.1 101 Switching Protocols\r\n".
			"Upgrade: websocket\r\n".
			"Connection: Upgrade\r\n".
			"Sec-WebSocket-Accept: $acceptKey".
			"\r\n\r\n";

		socket_write($client, $upgrade);
		return true;
	}
	else
	{
		print("WebSocket version 13 required (the client supports version {$version})");
		return false;
	}
}