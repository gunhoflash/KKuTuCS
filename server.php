<?php
// Server for listen KKuTuCS request.

include './libs/GameRoom.php';
include './libs/KKuTuCSRequest.php';
defined('PROJECT_ROOT') or define('PROJECT_ROOT', __DIR__.'/');

// Don't stop, server!
set_time_limit(0); 

// set some variables.
$NULL = NULL;

// Create a socket, bind to port, and start listening for connections.
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("Could not create socket.\n");
$result = socket_bind($socket, "0.0.0.0", 7002) or die("Could not bind to socket.\n");
$result = socket_listen($socket, 20) or die("Could not set up socket listener.\n");

$client_unknown = array($socket); // New client here.  
$client_room = array(
	new GameRoom("main", "main room", "", 0)
); // Array of GameRooms.

while(TRUE)
{
	$read = $client_unknown;

	// Socket is only to catch read-event. Write and except is always NULL.
	$num_changed_sockets = socket_select($read, $NULL, $NULL, 0);

	// Error
	if ($num_changed_sockets === FALSE) continue;

	// Something new (maybe an new client)
	if ($num_changed_sockets != 0)
		if (in_array($socket, $read))
		{
			$newSocket = socket_accept($socket);
			if (@socket_recv($newSocket, $data, 2048, 0) == 0) continue;
			handshake($newSocket, $data, $socket);
			$client_room[0]->clientEntered($newSocket);
			echo "Connected: ".socketToString($newSocket)."\n";
			$key = array_search($socket, $read);
			unset($read[$key]);
		}

	foreach ($client_room as &$room)
	{
		socket_read_GameRoom($room);
	}
}

function socket_read_GameRoom(&$room)
{
	// $room must be a instance of GameRoom.
	if (!($room instanceof GameRoom))
		return;

	if ($room->getNumberOfClient() == 0) return;
	$roomType = $room->getRoomType();
	$read = $room->getClientSockets();

	// Socket is only to catch read-event. Write and except is always NULL.
	$num_changed_sockets = socket_select($read, $NULL, $NULL, 0);
	// Warning: $read is modified.

	// Error
	if ($num_changed_sockets === FALSE) return;

	// Nothing new
	if ($num_changed_sockets == 0) return;

	// Read some new datas from clients in main.
	foreach ($read as &$readSocket)
	{
		echo "$roomType(".$room->getNumberOfClient().")\n";
		$data = @socket_read($readSocket, 4096, PHP_BINARY_READ);
		$socketString = socketToString($readSocket);

		if ($data === FALSE)
		{
			echo "  Disconnected: $socketString\n";
			$room->clientDisconnected($readSocket);
			continue;
		}

		// Decode the data.
		$decoded_data = @unmask($data);

		// Parse the data.
		$request = new KKuTuCSRequest($decoded_data);
		$method = $request->getMethod();
		$parameter = $request->getParameter();

		// If the given data is invalid, diconnect it.
		if ($request->getValidity() == FALSE)
		{
			echo "  Disconnected: $socketString\n";
			$room->clientDisconnected($readSocket);
			continue;
		}

		echo "  $socketString < $parameter\n";

		try
		{
			$response = encode("$socketString : $parameter\n");
		}
		catch (Exception $e)
		{
			$response = NULL;
			echo "  Exception on encode: ".$e->getMessage()."\n";
			continue;
		}

		socket_write($readSocket, $response);

		foreach ($room->getClientSockets() as &$sendSocket)
		{
			if ($sendSocket == $readSocket) continue;
			socket_write($sendSocket, $response);
		}
	}
}

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