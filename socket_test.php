<?php
// refer to https://www.cuelogic.com/blog/php-and-html5-websocket-server-and-client-communication
// set some variables
$host = "0.0.0.0";
$port = 7002;

// don't timeout!
set_time_limit(0);

// create socket
$socket = socket_create(AF_INET, SOCK_STREAM, 0)or die("Could not create socket\n");

// bind socket to port
$result = socket_bind($socket, $host, $port)or die("Could not bind to socket\n");

// start listening for connections
$result = socket_listen($socket, 20)or die("Could not set up socket listener\n");

$flag_handshake = false;
$client = null;
do
{
	if (!$client)
	{
		// accept incoming connections
		// client another socket to handle communication
		$client = socket_accept($socket)or die("Could not accept incoming connection\n");
	}

	$bytes =  @socket_recv($client, $data, 2048, 0);
	if ($flag_handshake == false)
	{
		if ((int)$bytes == 0)
			continue;
		//print("Handshaking headers from client: ".$data."\n");
		if (handshake($client, $data, $socket))
		{
			$flag_handshake = true;
		}
	}
	elseif($flag_handshake == true)
	{
		if ($data != "")
		{
			$decoded_data = unmask($data);
			print("< ".$decoded_data."\n");
			$response = strrev($decoded_data);
			socket_write($client, encode($response));
			print("> ".$response."\n");
			socket_close($client);
			$client = null;
			$flag_handshake = false;
		}
	}
} while (true);

// close sockets
socket_close($client);
socket_close($socket);

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

function unmask($payload)
{
	$length = ord($payload[1]) & 127;

	if ($length == 126)
	{
		$masks = substr($payload, 4, 4);
		$data = substr($payload, 8);
	}
	elseif($length == 127)
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
	elseif($length > 125 && $length < 65536)
		$header = pack('CCS', $b1, 126, $length);
	elseif($length >= 65536)
		$header = pack('CCN', $b1, 127, $length);

	return $header.$text;
}