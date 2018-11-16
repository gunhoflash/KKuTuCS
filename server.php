<?php
// Server for listen KKuTuCS request.
defined('PROJECT_ROOT') or define('PROJECT_ROOT', __DIR__.'/');

include './libs/GameRoom.php';
include './libs/KKuTuCSRequest.php';

// Don't stop, server!
set_time_limit(0); 

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
			unsetFromArray($socket, $read);
		}

	foreach ($client_room as &$room)
	{
		if ($room->getNumberOfClient() == 0)
		{
			// Delete empty room.
			if ($room->getRoomType() == "game")
				unsetFromArray($room, $client_room);
		}
		else
			socket_read_GameRoom($room);
	}
}

function socket_read_GameRoom(&$room)
{
	$roomType = $room->getRoomType();
	$read = $room->getClientSockets();

	// Socket is only to catch read-event. Write and except is always NULL.
	$num_changed_sockets = socket_select($read, $NULL, $NULL, 0);
	// Warning: $read is modified.
	
	if ($num_changed_sockets === FALSE) return; // Error
	if ($num_changed_sockets == 0) return; // Nothing new

	echo "$roomType(".$room->getNumberOfClient().")\n";

	// Read some new datas from clients.
	foreach ($read as &$readSocket)
	{
		$data = @socket_read($readSocket, 2048);
		if ($data === FALSE || strlen($data) == 0)
			$room->clientDisconnected($readSocket);
		else
		{
			// Decode & Parse the data. If the data is invalid, diconnect it.
			$request = new KKuTuCSRequest(@unmask($data));
			if ($request->getValidity() == FALSE) continue; // Invalid Data
			$method = $request->getMethod();
			$parameter1 = $request->getParameter(1);
			$parameter2 = $request->getParameter(2);

			if ($roomType == "main")
				processData($room, $readSocket, $method, $parameter1, $parameter2);
			else
				$room->processData($readSocket, $method, $parameter1, $parameter2);
		}
	}
}

// Process data (at main room)
function processData(&$mainroom, &$socket, $method, $parameter1, $parameter2)
{
	switch ($method)
	{
		case "JOIN":
			// Check the room index.
			if ($parameter1 < 1 || $parameter1 > sizeof($client_room) - 1)
				sendToSocket($socket, $method, "Invalid room index.");
			else
			{
				$room = $client_room[$parameter1];
				// Check the password and the number of users.
				if ($room->isPlaying())
					sendToSocket($socket, $method, "This room is now playing the game.");
				else if ($room->checkPassword($parameter2) == FALSE)
					sendToSocket($socket, $method, "Password is incorrect.");
				else if ($room->isFull())
					sendToSocket($socket, $method, "This room is full!");
				else
				{
					$room->clientEntered($socket);
					// TODO: Remove client from the main room.
					sendToSocket($socket, $method, "Success.");
				}
			}
			break;

		case "MAKE":
			// TODO: Make a new gameroom with given name and password.
			// TODO: Join the client to the gameroom.
			// TODO: Send a ROOMLIST message to all clients in the main room.
			// TODO: Send a JOIN success message to the client.
			break;

		case "ROOMLIST":
			// TODO: Send a information of roomlist to all clients in the main room.
			break;

		case "SEND":
			$mainroom->processData($socket, $method, $parameter1, $parameter2);
			break;

		default:
			sendToSocket($socket, "ERROR", "Main can't handle the new method: $method");
			echo "  Main can't handle the new method: $method\n";
			break;
	}
}