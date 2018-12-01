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
	private $mode           = "";        // "en" or "kr"
	
	private $roundTime      = 625;        // unit: 0.1sec
	private $tv;
	private $counter        = 0;
	private $currentRound   = 0;

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
	public function __construct(string $roomType, string $name, string $password, string $mode, int $maximumClients)
	{
		$this->roomType       = $roomType;
		$this->name           = $name;
		$this->password       = $password;
		$this->mode           = $mode;
		$this->maximumClients = $maximumClients;
		$this->index          = self::$room_index++;
	}

	public function clientEntered(&$socket)
	{
		$socketString = socketToString($socket);
		sendToSocketAll($this->clientSockets, "CONNECTED", $socketString);
		if (TRUE)sendToSocket($socket, "PLAYBGM", "lobbyBGM");
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

	private function checkWord($word)
	{
		//$word = strtolower($typed_word);
		// TODO: To allow for words with spaces, this code must be modified.
		
		if (($this->mode=="en" && isValid($word) && isChained($this->lastWord, $word) && isInDB($word) && !isUsed($word, $this->wordHistory))
			||($this->mode=="kr" && isValid_K($word) && isChained_K($this->lastWord, $word) && isInDB_K($word) && !isUsed($word, $this->wordHistory)))
		{
			$message = "";
			$this->wordHistory[] = $this->lastWord = $word;	
			sendToSocketAll($this->clientSockets, "CORRECT", "");
			$tspeed = $this->getTurnSpeed($this->roundTime);
			switch($tspeed)
			{
				case 21: $ktime = 0.23; break;
				case 32: $ktime = 0.36; break;
				case 51: $ktime = 0.46; break;
				case 62: $ktime = 0.57; break;
				case 80: $ktime = 0.70; break;
			}
			$astime = ($ktime * 1000000 / mb_strlen($word, "utf-8")) + 1000;//2.5s = 2500000 = 2.5 * 10^6
			for($i=0;$i<mb_strlen($word, "utf-8");$i++) {
				$message .= mb_substr($word, $i, 1, "utf-8");
				sendToSocketAll($this->clientSockets, "CORRECT", "$message");
				sendToSocketAll($this->clientSockets, "PLAYBGM", "As", $tspeed);
				usleep($astime);
				if($i==mb_strlen($word, "utf-8")-1) {
					usleep(10000);
					sendToSocketAll($this->clientSockets, "PLAYBGM", "K", $tspeed);
					usleep($ktime*1000000 + 1000);
				}
				// TODO: when doing usleep and timer < 0, don't end the game
			}
			if($this->mode == "kr") sendToSocketAll($this->clientSockets, "CORRECT", "$word".checkKorean($word));
			else sendToSocketAll($this->clientSockets, "CORRECT", "$word");
			return TRUE;
		}
		
		return FALSE;
	}

	private function startGame()
	{
		//방과 클라이언트 설정
		$n = 0;
		if($this->mode == "kr") $word = getRandomWord_K();
		else $word = getRandomWord();
		$this->nowTurn = 0;
		$this->state="Playing";
		$this->lastWord=$word;
		sendToSocketAll($this->clientSockets, "CORRECT", $this->lastWord);
		if($this->currentRound==0)
		{
			sendToSocketAll($this->clientSockets, "PLAYBGM", "game_start");
			usleep(2500000);
		}
		sendToSocketAll($this->clientSockets, "PLAYBGM", "round_start");
		usleep(2500000);
		sendToSocketAll($this->clientSockets, "GAMESTART", $this->getTurnSpeed($this->roundTime), $this->roundTime);
		while ($this->clientReady[$n] != NULL)
		{
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
			$this->roundTime = 625;
			$this->currentRound++;

			sendToSocketAll($this->clientSockets, "SEND", "", "Round is over. ".socketToString($this->clientSockets[$this->nowTurn])." has failed to type.\n");
			sendToSocketAll($this->clientSockets, "SEND", "", socketToString($this->clientSockets[$this->nowTurn])." will lose score 100.\n");
			sendToSocketAll($this->clientSockets, "SEND", "", "Push Ready to play next Round.\n");
			($this->clientScores[$this->nowTurn] < 100) ? $this->clientScores[$this->nowTurn] = 0 : $this->clientScores[$this->nowTurn] -= 100;
			
			for($n = 0; $n <= count($this->clientSockets)-1; $n++)
			{
				$this->clientReady[$n] = 0;
			}
			
			if($this->currentRound === 3) $this->endGame();
			else $this->refreshList();
			usleep(50000);
			sendToSocketAll($this->clientSockets, "PLAYBGM", "horror");
		}
	}

	private function endGame()
	{
		sendToSocketAll($this->clientSockets, "RESULT", $this->makePlayerList());
		$this->currentRound = 0;
	}

	private function startTurn()
	{
		$this->nowTurn++;
		if($this->nowTurn >= count($this->clientSockets)) {
			$this->nowTurn=0;
		}
		sendToSocketAll($this->clientSockets, "TURNSTART", $this->getTurnSpeed($this->roundTime), $this->roundTime);
		sendToSocketAll($this->clientSockets, "PLAYBGM", "T", $this->getTurnSpeed($this->roundTime));
		$this->tv = time();
	}

	private function getTurnSpeed($rt)
	{
		if($rt < 100) return 21;

		else if($rt < 200) return 32;

		else if($rt < 300) return 51;

		else if($rt < 400) return 62;

		else if($rt <= 650) return 80;

		else return 0;
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

	private function checkKorean($word)
	{
		$last = ucord(mb_substr($word, -1, 1, 'utf-8'));
		if($last>=45208 && $last<=45795) return '('.ucchr($last+5292).')';
		if($last>=46972 && $last<=47559) return '('.ucchr($last+3528).')';
		else return;
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
	public function processData(&$socket, $method, $parameter1, $parameter2, $parameter3)
	{
		switch ($method)
		{
			case "SEND":
				$this->processSEND($socket, $parameter1);
				break;
			case "READY":
				if ($this->state == "Playing") break; // Ignore it because the game has already started.
				$this->processREADY($socket, $parameter1);
				sendToSocketAll($this->clientSockets, "SEND", "", "Current Round is { ".($this->currentRound+1)." / 3 }");
				break;
			case "QUIT":
				// Client will re-open its socket.
				$this->clientDisconnected($socket);
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
		sendToSocketAll($this->clientSockets, "SEND", $socketString, $message);

		// If the $message is a word, checkWord().
		if ($this->state == "Playing")
		{
			if ($this->nowTurn == array_search($socket, $this->clientSockets))
			{
				if($this->checkWord($message)) {
					// TODO: Calculate client's score.
					// TODO: Send a 'success' message to the client.
					$score = $this->getScore($message);
					sendToSocketAll($this->clientSockets, "SEND", "", "$socketString get $score");
					$this->clientScores[$this->nowTurn] += $score;
					sendToSocketAll($this->clientSockets, "SEND", "", "$socketString type $message");
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
	public function getMode()           { return $this->mode;                                                }
	public function getMaximumClients() { return $this->maximumClients;                                      }
	public function getClientSockets()  { return $this->clientSockets;                                       }
	public function getNumberOfClient() { return sizeof($this->clientSockets);                               }
	public function isFull()            { return ($this->getNumberOfClient() >= $this->getMaximumClients()); }
	public function isPlaying()         { return $this->state == "Playing";                                  }
}

?>