<?php
defined('PROJECT_ROOT') or exit('No direct script access allowed');

include './libs/socketHandle.php';
include './libs/wordCheck.php';

class GameRoom
{
	private static $room_index = 1;
	private $index;
	private $roomType       = NULL;      // "main" or "game"
	private $state          = "Ready";   // "Ready" or "Playing"
	private $name           = "KKuTuCS";
	private $password       = "";        // "": no password
	private $maximumClients = 4;         // 0: no limit
	
	private $roundTime      = 600;        // unit: 0.1sec
	private $tv;
	private $counter        = 0;

	// Array for Clients
	private $clientSockets  = array();
	private $clientReady    = array();   // 0: not ready, 1: ready
	private $clientScores   = array();

	private $wordHistory    = array();   // String과 _로 엮어서 하나의 string으로 만드는게 더 빠를까?
	private $lastWord       = "";
	private $nowTurn        = 0;         // Index of the client who has to say a word.
	
	/**
	 * Constructor
	 */
	public function __construct(string $roomType, string $name, string $password, int $maximumClients)
	{
		$this->roomType       = $roomType;
		$this->name           = $name;
		$this->password       = $password;
		$this->maximumClients = $maximumClients;
		$this->index          = self::$room_index++;
	}

	public function clientEntered(&$socket)
	{
		$socketString = socketToString($socket);
		sendToSocketAll($this->clientSockets, "CONNECTED", $socketString);
		$this->clientSockets[] = $socket;
		$this->clientReady[] = 0;
		$this->clientScores[] = 0;

		if($this->roomType == "game")
		{
			$str = $this->makePlayerList();
			$this->refreshList();
		}
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
		$this->refreshList();
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

	function unsetFromArray(&$sockets, $index) 
	{
		$newArray = array ();

		for($i = $index; $i <= count($sockets); $i++)
		{
			$newArray[] = $sockets[$index];
		}
	}

	private function checkWord($word)
	{
		// TODO: To allow for words with spaces, this code must be modified.
		if (isValid($word) && isChained($this->lastWord, $word) && isInDB($word) && !isUsed($word, $this->wordHistory))
		{
			$lowerword = strtolower($word);
			$this->wordHistory[] = $this->lastWord = $lowerword;
			return TRUE;
		}
		
		return FALSE;
	}

	private function startGame()
	{
		//방과 클라이언트 설정
		$n = 0;
		$this->nowTurn = 0;
		$this->state="Playing";

		sendToSocketAll($this->clientSockets, "GAMESTART", $this->getTurnSpeed($this->roundTime), $this->roundTime);
		while($this->clientReady[$n]!=NULL) {
			$this->clientScores[$n] = 0;
			$n++;
		}
		$this->refreshList();
	}

	private function getScore($text) 
	{
		//tv = 턴 시작 시점의 unix time
		//t = 점수 계산 시점의 unix time
		$t = time();
		$delay = $t-$this->tv;
		$score = ( 2 - 0.5 * ($delay/10) ) * (pow(5 + 7 * strlen($text), 0.74));
		$this->roundTime -= $delay*10;
		return round($score);
	}

	private function endRound()
	{
		$this->counter ++;
		//접속중인 모든 소켓에게 endRound를 받았을 때,
		if($this->counter == count($this->clientSockets))
		{
			$n = 0;
			$this->state = "Ready";
			$this->lastWord = "";
			$this->wordHistory = array();
			$this->counter = 0;
			$this->roundTime = 600;
			sendToSocketAll($this->clientSockets, "SEND", "Round is over. ".socketToString($this->clientSockets[$this->nowTurn])." has failed to type.\n");
			sendToSocketAll($this->clientSockets, "SEND", socketToString($this->clientSockets[$this->nowTurn])." will lose score 100.\n");
			$this->clientScores[$this->nowTurn] -= 100;
			while($this->clientSockets[$n]!=NULL) {
				sendToSocketAll($this->clientSockets, "SEND", socketToString($this->clientSockets[$n])."'s score is ".$this->clientScores[$n]."\n");
				$this->clientReady[$n] = 0;
				$n ++;
			}
		}

		$this->refreshList();
	}

	private function startTurn()
	{
		$this->nowTurn++;
		if($this->nowTurn >= count($this->clientSockets)) {
			$this->nowTurn=0;
		}
		sendToSocketAll($this->clientSockets, "TURNSTART", $this->getTurnSpeed($this->roundTime), $this->roundTime);
		$this->tv = time();
		sendToSocketAll($this->clientSockets, "SEND", "Now, ".socketToString($this->clientSockets[$this->nowTurn])."'s Turn\n");
		// TODO: Send a MYTURN message to the client to tell that it is your turn.
	}

	private function getTurnSpeed($rt)
	{
		if ($rt > 600) return 0;
		return floor($rt / 50) * 5 + 15;
	}

	// Make String for JavaScript process
	// ex) clientSockets`clientScores`clientReady``sockets`...``nowTurn
	private function makePlayerList()
	{
		$str = "";
		$i = 0;
		for($i = 0; $i <= count($this->clientSockets)-1; $i++)
		{
			if (strlen($str)) $str .= "``";
			$str .= socketToString($this->clientSockets[$i])."`";
			$str .= $this->clientScores[$i]."`";
			$str .= $this->clientReady[$i];
		}
		$str = $str."``".$this->nowTurn;

		return $str;
	}

	// Refresh PlayerList
	private function refreshList()
	{
		sendToSocketAll($this->clientSockets, "PLAYERLIST", $this->makePlayerList());
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
			case "ROUNDOVER":
				$this->endRound();
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
					$score = $this->getScore($message);
					sendToSocketAll($this->clientSockets, "CORRECT", "$message");
					sendToSocketAll($this->clientSockets, "SEND", "$socketString get $score");
					$this->clientScores[$this->nowTurn] += $score;
					sendToSocketAll($this->clientSockets, "SEND", "$socketString type $message");
					$this->startTurn();
					$this->refreshList();
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
		if (($flag == 1 || $flag == 0) && $this->state="Ready")
		{
			$this->clientReady[$index] = $flag;
			sendToSocketAll($this->clientSockets, "PLAYERLIST", $this->makePlayerList());

			// If all ready, then start the game!
			if (!in_array(0, $this->clientReady))
			{
				// TODO: Initialize all clients's score.
				$this->startGame();
				$this->tv = time();
			}
		}
	}

	/**
	 * Getter
	 */
	public function getIndex()          { return $this->index;                                               }
	public function getRoomType()       { return $this->roomType;                                            }
	public function getName()           { return $this->name;                                                }
	public function getMaximumClients() { return $this->maximumClients;                                      }
	public function getClientSockets()  { return $this->clientSockets;                                       }
	public function getNumberOfClient() { return sizeof($this->clientSockets);                               }
	public function isFull()            { return ($this->getNumberOfClient() >= $this->getMaximumClients()); }
	public function isPlaying()         { return $this->state == "Playing";                                  }
}

?>