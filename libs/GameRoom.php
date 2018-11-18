<?php
defined('PROJECT_ROOT') or exit('No direct script access allowed');

include './libs/socketHandle.php';

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

	private function checkWord()
	{
		// TODO: You should edit wordCheck.php first.
		// TODO: Do something
	}

	private function startGame()
	{
		// TODO: Do something
	}

	private function startTurn()
	{
		// TODO: Do something
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
			if ($nowTurn == array_search($socket, $this->clientSockets))
			{
				// TODO: If it is a word, checkWord();
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

		if ($flag == 1 || $flag == 0)
		{
			$this->clientReady[$index] = $flag;
			// TODO: Check if all clients ready.
			// TODO: If yes, send information to all and let's start game!
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