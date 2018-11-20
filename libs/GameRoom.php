<?php
defined('PROJECT_ROOT') or exit('No direct script access allowed');

include './libs/socketHandle.php';
include './libs/wordCheck.php';

class GameRoom
{
	private $roomType = NULL; // "main" or "game"
	private $state = "Ready"; // "Ready" or "Playing"
	private $name = "KKuTuCS";
	private $password = ""; // "": no password
	private $maximumClients = 4; // 0: no limit
	
	// Array for Clients
	private $clientSockets = array();
	private $clientReady = array(); // 0: not ready, 1: ready

	private $wordHistory = array(); // String과 _로 엮어서 하나의 string으로 만드는게 더 빠를까?
	private $lastWord = "";
	private $nowTurn = 0; // Index of the client who has to say a word.
	
	/**
	 * Constructor
	 */
	public function __construct(string $roomType, string $name, string $password, int $maximumClients)
	{
		$this->roomType = $roomType;
		$this->name = $name;
		$this->password = $password;
		$this->maximumClients = $maximumClients;
	}

	public function clientEntered(&$socket)
	{
		$socketString = socketToString($socket);
		sendToSocketAll($this->clientSockets, "CONNECTED", $socketString);
		$this->clientSockets[] = $socket;
		$this->clientReady[] = 0;
	}

	public function clientQuitted(&$socket)
	{
		$index = array_search($socket, $this->clientSockets);
		if ($index === FALSE)
		{
			echo "  Fatal error on clientQuitted()\n";
			return;
		}

		// Log
		$socketString = socketToString($socket);
		echo "  QUITTED: $socketString\n";

		// Unset from arrays and close the socket.
		unsetFromArray($NULL, $this->clientSockets, $index);
		unsetFromArray($NULL, $this->clientReady, $index);

		// Send the information to other clients.
		sendToSocketAll($this->clientSockets, "QUITTED", $socketString);
	}

	public function clientDisconnected(&$socket)
	{
		$this->clientQuitted($socket);

		// Log
		$socketString = socketToString($socket);
		echo "  DISCONNECTED: $socketString\n";
		
		socket_close($socket);

		// Send the information to other clients.
		sendToSocketAll($this->clientSockets, "DISCONNECTED", $socketString);
	}

	private function checkWord($word)
	{
		global $conn;

		if(isValid($word)&&isChained($this->lastWord, $word)&&isInDB($conn, $word)&&!isUsed($word, $this->wordHistory)) {
			$lowerword = strtolower($word);
			$this->wordHistory[] = $lowerword;
			$this->lastWord = $lowerword;
			return TRUE;
		}
		else return FALSE;
	}

	private function startGame()
	{
		$n = 0;
		$this->state="Playing";

		sendToSocketAll($this->clientSockets, "GAMESTART");
		while($this->clientReady[$n]==NULL) {
			$this->clientReady[$n] = 0;
			$n++;
		}
	}

	private function startTurn()
	{
		$this->nowTurn++;
		if($this->nowTurn >= count($this->clientSockets)) {
			$this->nowTurn=0;
		}
		sendToSocketAll($this->clientSockets, "SEND", "Now, ".socketToString($this->clientSockets[$this->nowTurn])."'s Turn\n");
		// TODO: Send a MYTURN message to the client to tell that it is your turn.
	}

	public function checkPassword($password)
	{
		return ($this->password == "") || ($password == $this->password);
	}

	// Process data (at game room)
	public function processData(&$socket, $method, $parameter1, $parameter2)
	{
		switch ($method)
		{
			case "SEND":
				$this->processSEND($socket, $parameter1);
				break;
			case "READY":
				$this->processREADY($socket, $parameter1);
				break;
			case "QUIT":
				// TODO: Send the result so that the client can go back to the main (by refreshing the page).
				break;
			case "TIMETEST":
				sendToSocket($socket, $method);
				break;
			default:
				echo "  Gameroom can't handle the new method: $method\n";
				break;
		}
	}

	private function processSEND(&$socket, $message)
	{
		$socketString = socketToString($socket);
		sendToSocketAll($this->clientSockets, "SEND", "$socketString : $message\n");

		// If the $message is a word, checkWord().
		if ($this->state == "Playing")
		{
			if ($this->nowTurn == array_search($socket, $this->clientSockets))
			{
				if($this->checkWord($message)) {
					// TODO: Calculate client's score.
					// TODO: Send a 'success' message to the client.
					sendToSocketAll($this->clientSockets, "SEND", "$socketString typed $message");
					$this->startTurn();
				}
			}
		}
	}

	private function processREADY(&$socket, $flag)
	{
		$index = array_search($socket, $this->clientSockets);
		if ($index === FALSE)
		{
			echo "  Fatal error on processREADY()\n";
			return;
		}

		// 0: not ready, 1: ready
		if ($flag == 1 || $flag == 0)
		{
			$this->clientReady[$index] = $flag;

			// If all ready, then start the game!
			if (!in_array(0, $this->clientReady))
			{
				// TODO: Initialize all clients's score.
				$this->startGame();
			}
		}
	}

	/**
	 * Getter
	 */
	public function getRoomType()       { return $this->roomType;                                            }
	public function getState()          { return $this->state;                                               }
	public function getName()           { return $this->name;                                                }
	// public function getPassword()       { return $this->password;                                            }
	public function getMaximumClients() { return $this->maximumClients;                                      }
	public function getClientSockets()  { return $this->clientSockets;                                       }
	public function getNumberOfClient() { return sizeof($this->clientSockets);                               }
	public function isFull()            { return ($this->getNumberOfClient() >= $this->getMaximumClients()); }
	public function isPlaying()         { return $this->state == "Playing";                                  }
}

?>