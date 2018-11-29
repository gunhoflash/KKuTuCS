<?php
defined('PROJECT_ROOT') or exit('No direct script access allowed');
$NULL = NULL;
// Functions for socket

// Send a message to the client.
function sendToSocket($socket, $method, $parameter1 = "", $parameter2 = "")
{
	sendToSocketAll([$socket], $method, $parameter1, $parameter2);
}

// Send a message to all clients in the list.
function sendToSocketAll($socketList, $method, $parameter1 = "", $parameter2 = "")
{
	$message = encode("$method\n$parameter1\n$parameter2");
	if ($message == "") return;

	foreach ($socketList as $socket)
		socket_write($socket, $message);
}

// Unset the object from the array. Index is option that indicates the index of object.
function unsetFromArray(&$object, &$array, $index = -1)
{
	if ($index == -1)
		$index = array_search($object, $array);
	if ($index !== FALSE)
		unset($array[$index]);
	$array = array_values($array);
}

// Convert the socket to a string. 
$client_name = array("TESTIPPORT" => "TESTNAME"); // nickname
function socketToString($socket, $IPPORT='', $nickname = '')
{

    global $client_name;
    // case that can't find nickname 
    if($socket!='') {
        socket_getpeername($socket, $IP, $PORT);
        $IPPORT = $IP.":".$PORT;
    }

    // insert nickname
    if($nickname != '') {
        $client_name[$IPPORT] = $nickname;
        
    }
	
    // check nickname                                                                                                                                                                                                                                                                                                                                                                        
    if(array_key_exists($IPPORT,$client_name))
        return $client_name[$IPPORT];

    else
        return "{".$IPPORT."}";
}

// Edcode the text.
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

// Encode the text. Return "" when it failed.
function encode($text)
{
	try
	{
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
	catch (Exception $e)
	{
		echo "Exception on encode: ".$e->getMessage()."\n";
		return "";
	}
}

// Handshake
function handshake($client, $headers, $socket)
{
	if (preg_match("/Sec-WebSocket-Version: (.*)\r\n/", $headers, $match))
		$version = $match[1];
	else
	{
		print("The client doesn't support WebSocket");
		return FALSE;
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
		$acceptKey = base64_encode(sha1($acceptKey, TRUE));

		$upgrade = "HTTP/1.1 101 Switching Protocols\r\n".
			"Upgrade: websocket\r\n".
			"Connection: Upgrade\r\n".
			"Sec-WebSocket-Accept: $acceptKey".
			"\r\n\r\n";

		socket_write($client, $upgrade);
		return TRUE;
	}
	else
	{
		print("WebSocket version 13 required (the client supports version {$version})");
		return FALSE;
	}
}

?>