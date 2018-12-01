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
	new GameRoom("main", "main room", "", "", 0)
); // Array of GameRooms.

// Connecting to MySQL database.
$conn = mysqli_connect("p:localhost", "root", "111111", "kkutudb");
if (!$conn)
	echo 'Connect Error ('.mysqli_connect_errno().') '.mysqli_connect_error().'\n';
else
	echo "Successful connect\n";

while(TRUE)
{
	$read = $client_unknown;

	// Socket is only to catch read-event. Write and except is always NULL.
	$num_changed_sockets = socket_select($read, $NULL, $NULL, 0);

	// Handle the read-event.
	if ($num_changed_sockets !== FALSE)
	{
		// There is something to read(maybe an new client).
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
				{
					unsetFromArray($room, $client_room);

					// Send ROOMLIST message to all clients in the main.
					processROOMLIST($client_room[0]->getClientSockets());
				}
			}
			else
				socket_read_GameRoom($room);
		}
	}

	// Process Game Round
	foreach ($client_room as &$room)
	{
		if ($room->getRoomType() == "main") continue;

		$room->processGameRound();
	}
}

function socket_read_GameRoom(&$room)
{
	global $client_room;

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
		{
			$room->clientDisconnected($readSocket);
			processROOMLIST($client_room[0]->getClientSockets());
		}
		else
		{
			// Decode & Parse the data. If the data is invalid, diconnect it.
			$request = new KKuTuCSRequest(@unmask($data));
			if ($request->getValidity() == FALSE) continue; // Invalid Data
			$method = $request->getMethod();
			$parameter1 = $request->getParameter(1);
			$parameter2 = $request->getParameter(2);
			$parameter3 = $request->getParameter(3);
			if ($parameter1 == NULL) $parameter1 == "";
			if ($parameter2 == NULL) $parameter2 == "";
			if ($parameter3 == NULL) $parameter3 == "";

			echo "  processData: $method\n$parameter1\n$parameter2\n$parameter3\n";

			if ($roomType == "main")
				processData($readSocket, $method, $parameter1, $parameter2, $parameter3);
			else
				$room->processData($readSocket, $method, $parameter1, $parameter2, $parameter3);
		}
	}
}

// Process data (at main room)
function processData(&$socket, $method, $parameter1, $parameter2, $parameter3)
{
	global $client_room;
	
	switch ($method)
	{
		case "JOIN":
			processJOIN($socket, $parameter1, $parameter2);
			break;

		case "MAKE":
			processMAKE($socket, $parameter1, $parameter2, $parameter3);
			break;

		case "ROOMLIST":
			processROOMLIST([$socket], $parameter1);
			break;

		case "SEND":
		case "TIMETEST":
			$client_room[0]->processData($socket, $method, $parameter1, $parameter2, $parameter3);
			break;

		default:
			sendToSocket($socket, "ERROR", "Main can't handle the new method: $method");
			echo "  Main can't handle the new method: $method\n";
			break;
	}
}

function processJOIN(&$socket, $parameter1, $parameter2)
{
	global $client_room;

	// Check the room index.
	if ($parameter1 > 0)
	{
		foreach ($client_room as $room)
		{
			if ($room->getIndex() == $parameter1)
			{
				// Check the password and the number of users.
				if ($room->isPlaying())
					sendToSocket($socket, "JOIN", "0", "This room is now playing the game.");
				else if ($room->checkPassword($parameter2) == FALSE)
					sendToSocket($socket, "JOIN", "0", "Password is incorrect.");
				else if ($room->isFull())
					sendToSocket($socket, "JOIN", "0", "This room is full!");
				else
				{
					$room->clientEntered($socket);
					$client_room[0]->clientQuitted($socket);
					sendToSocket($socket, "JOIN", "1", $room->getName());
					processROOMLIST($client_room[0]->getClientSockets());
				}
				return;
			}
		}
	}
	sendToSocket($socket, "JOIN", "0", "Invalid room index.");
}

function processMAKE(&$socket, $roomname, $password, $mode)
{
	global $client_room;

	$new_room = new GameRoom("game", $roomname, $password, $mode, 4);
	$client_room[] = $new_room;

	// Move the client from main to the new room.
	$new_room->clientEntered($socket);
	$client_room[0]->clientQuitted($socket);

	// Send a information of roomlist to all clients in the main room.
	processROOMLIST($client_room[0]->getClientSockets());
	
	// Send a JOIN success message to the client.
	sendToSocket($socket, "JOIN", "1", $new_room->getName());
}

// Send a information of roomlist to the client in the main room.
function processROOMLIST($socketList,$str = '')
{
	/**
	 * (roomString): roomname`isPlaying`now/max`needPassword
	 * ex) Come on!`0`2/4`0
	 * 
	 * $str = (roomString)``(roomString)``(roomString) ...
	 */
	global $client_room;
	
	if($str!=''&&sizeof($socketList)==1) 
    { 
        socket_getpeername($socketList[0], $IP, $PORT); 
        $IPPORT = $IP.":".$PORT; 
        print $IPPORT; 
        socketToString('',$IPPORT,$str); 
	} 
	
	// Convert $client_room to string.
	foreach ($client_room as $room)
	{
		if ($room->getRoomType() == "main") continue;

		if (strlen($str)) $str .= '``';
		$str .= $room->getIndex().'`'
		.$room->getName().'`'
		.$room->getMode().'`'
		.($room->isPlaying() ? '1' : '0').'`'
		.$room->getNumberOfClient().'/'.$room->getMaximumClients().'`'
		.($room->checkPassword("") ? '0' : '1');
	}

	sendToSocketAll($socketList, "ROOMLIST", $str);
}