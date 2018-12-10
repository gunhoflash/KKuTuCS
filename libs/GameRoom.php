<?php
defined('PROJECT_ROOT') or exit('No direct script access allowed');

include './libs/socketHandle.php';
include './libs/wordCheck.php';

class GameRoom
{
	// Constant
	private const ROUND_TIME    = 62.5;
	private const LAST_ROUND    = 3;

	// Static
	private static $room_index  = 0;

	// Informations
	private $index;          // Room Index
	private $roomType;       // "main" or "game"
	private $state;          // "Ready" or "Playing"
	private $name;           // Room Name
	private $password;       // "": no password
	private $maximumClients; // 0: no limit
	private $mode;           // "en" or "kr"
	
	// For Clients
	private $clientSockets;
	private $clientReady;    // 0: not ready, 1: ready
	private $clientScores;

	// For game
	private $gameState;      // "before game" -> ["before round" -> "on round" -> "after round"]
	private $time_roundTime;
	private $time_forTurn;
	private $time_temp;
	private $time_forAni;
	private $astime;
	private $wordHistory;
	private $lastWord;
	private $nowTurn;        // Index of the client who has to say a word.
	private $currentRound= 1;// 1, 2, 3 available.

	/**
	 * Constructor
	 */
	public function __construct(string $roomType, string $name, string $password, string $mode, int $maximumClients = 4)
	{
		$this->index          = self::$room_index++;
		$this->roomType       = $roomType;
		$this->state          = "Ready";
		$this->name           = $name;
		$this->password       = $password;
		$this->maximumClients = $maximumClients;
		$this->mode           = $mode;

		$this->clientSockets  = array();
		$this->clientReady    = array();
		$this->clientScores   = array();
	}

	public function clientEntered(&$socket)
	{
		$nickname = getNicknameBySocket($socket);
		sendToSocket($socket, "PLAYBGM", "lobbyBGM");

		$this->clientSockets[] = $socket;
		$this->clientReady[] = 0;
		$this->clientScores[] = 0;

		if ($this->roomType == "game")
		{
			$this->refreshList();
			sendToSocketAll($this->clientSockets, "SYSTEMSEND", $nickname, "joined.");
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
		$nickname = getNicknameBySocket($socket);
		echo "  QUITTED: $nickname\n";

		// Unset from arrays and close the socket.
		unsetFromArray($NULL, $this->clientSockets, $index);
		unsetFromArray($NULL, $this->clientReady, $index);
		unsetFromArray($NULL, $this->clientScores, $index);

		// Send the information to other clients.
		sendToSocketAll($this->clientSockets, "QUITTED", $nickname);
	}

	public function clientDisconnected(&$socket)
	{
		global $room_state_changed;

		$this->clientQuitted($socket);

		// Log
		$nickname = getNicknameBySocket($socket);
		echo "  DISCONNECTED: $nickname\n";
		
		socket_close($socket);

		// Send the information to other clients.
		sendToSocketAll($this->clientSockets, "DISCONNECTED", $nickname);
		if ($this->roomType == "game")
		{
			$this->processPLAYERLIST($this->clientSockets, "PLAYERLIST");
			$room_state_changed = TRUE;
		}
	}

	// Process Game Round
	public function processGameRound()
	{
		// Do nothing when the state is 'Ready'.
		if ($this->state == "Ready") return;

		switch ($this->gameState)
		{
			case "before game":
				$time_temp = microtime(true);
				// After enough time, prepare the first round. If not, do nothing and just wait.
				if ($time_temp - $this->time_temp >= 2.5)
				{
					// Initialize variable
					$this->nowTurn = 0;
					$this->currentRound = 1;

					// Reset Score
					foreach ($this->clientScores as &$score)
						$score = 0;

					$this->refreshList();
					$this->lastWord = getRandomWord($this->mode);
					sendToSocketAll($this->clientSockets, "CORRECT", $this->lastWord);
					sendToSocketAll($this->clientSockets, "PLAYBGM", "round_start");
					sendToSocketAll($this->clientSockets, "SYSTEMSEND", "", "Current Round is { ".($this->currentRound)." / 3 }");

					$this->time_roundTime = self::ROUND_TIME;
					sendToSocketAll($this->clientSockets, "ROUNDSTART", $this->time_roundTime);

					$this->time_temp = $time_temp + 0.6;
					$this->gameState = "before round";
				}
				break;
			
			case "before round":
				$time_temp = microtime(true);
				// After enough time, start the first round. If not, do nothing and just wait.
				if ($time_temp - $this->time_temp >= 2.5)
				{
					// Initialize variable
					$this->wordHistory = array();

					$this->gameState = "on round";
					$this->startTurn();
				}
				break;

			case "on round":
				$time_temp = microtime(true);
				// End round when timeover
				if ($time_temp - $this->time_temp >= $this->time_forTurn)
				{
					sendToSocketAll($this->clientSockets, "SYSTEMSEND", "", "Round is over. ".getNicknameBySocket($this->clientSockets[$this->nowTurn])." has failed to type.\n");
					sendToSocketAll($this->clientSockets, "ROUNDOVER");
					
					// Down score.
					$this->clientScores[$this->nowTurn] = ($this->clientScores[$this->nowTurn] < 1000) ? 0 : $this->clientScores[$this->nowTurn] - 1000;
					
					sendToSocketAll($this->clientSockets, "PLAYBGM", "horror");
					$this->time_temp = $time_temp;
					$this->gameState = "after round";
				}
				break;

			case "after round":
				$time_temp = microtime(true);
				// After enough time, prepare next round or end game.
				if ($time_temp - $this->time_temp >= 3)
				{
					if ($this->currentRound == self::LAST_ROUND)
					{
						// End game.
						$this->endGame();
					}
					else
					{
						$this->currentRound++;
						// Prepare next round.
						sendToSocketAll($this->clientSockets, "SYSTEMSEND", "", "Ready to play next Round.\n");

						$this->refreshList();
						$this->lastWord = getRandomWord($this->mode);
						sendToSocketAll($this->clientSockets, "CORRECT", $this->lastWord);
						sendToSocketAll($this->clientSockets, "PLAYBGM", "round_start");
						sendToSocketAll($this->clientSockets, "SYSTEMSEND", "", "Current Round is { ".($this->currentRound)." / 3 }");
						
						$this->time_roundTime = self::ROUND_TIME;
						sendToSocketAll($this->clientSockets, "ROUNDSTART", $this->time_roundTime);
						
						$this->time_temp = $time_temp;
						$this->gameState = "before round";
					}
				}
				break;
			
			case "in animation":
				$time_temp = microtime(true);
				if($time_temp - $this->time_forAni > $this->astime) {
					$this->gameState = "on round";
					($this->mode == "kr") ? $add = checkKorean($this->lastWord) : $add = "";
					sendToSocketAll($this->clientSockets, "CORRECT", $this->lastWord.$add);
					$this->refreshList();
					$this->startTurn();
				}
				break;
		}
	}

	private function checkWord($word)
	{
		//$word = strtolower($typed_word);

		if (!isValid($this->mode, $word))
			return "VALID";
		if (!isChained($this->mode, $this->lastWord, $word))
			return "CHAIN";
		if (!isInDB($this->mode, $word))
			return "DB";
		if (isUsed($this->mode, $word, $this->wordHistory))
			return "USED";

		$this->time_forAni = microtime(TRUE);
		sendToSocketAll($this->clientSockets, "CORRECT", "");
		sendToSocketAll($this->clientSockets, "ANIMATION", $this->getTurnSpeed(), $word);
		switch ($this->getTurnSpeed())
		{
			case 2.1: $ktime = 0.30; break;
			case 3.2: $ktime = 0.47; break;
			case 5.1: $ktime = 0.60; break;
			case 6.2: $ktime = 0.72; break;
			case 8.0: $ktime = 0.93; break;
			default : $ktime = 0.23; break;
		}
		$this->astime = $ktime + (mb_strlen($word, "utf-8")/100) + 0.8;
		$this->wordHistory[] = $this->lastWord = $word;
		return "OK";
	}

	private function getScore($text) 
	{
		$delay = microtime(true) - $this->time_temp;
		$score = ( 20 - $delay / 2 ) * (pow(5 + 7 * strlen($text), 0.74));
		$this->time_roundTime -= $delay; // Is it needed?
		return round($score);
	}

	// End game.
	private function endGame()
	{
		global $room_state_changed;

		$this->state = "Ready";
		foreach ($this->clientReady as &$ready)
			$ready = 0;

		$this->processPLAYERLIST($this->clientSockets, "RESULTLIST");

		foreach ($this->clientScores as &$score)
			$score = 0;
		$room_state_changed = TRUE;
	}

	private function startTurn()
	{
		$this->time_forTurn = $this->getTurnSpeed();
		$this->time_temp = microtime(true);
		sendToSocketAll($this->clientSockets, "TURNSTART", $this->time_forTurn, $this->time_roundTime);
	}

	private function getTurnSpeed()
	{
		$rt = $this->time_roundTime;

		if($rt < 1.2) return $rt;
		if($rt <  10) return 2.1;
		if($rt <  20) return 3.2;
		if($rt <  30) return 5.1;
		if($rt <  40) return 6.2;
		if($rt <= 65) return 8.0;
		return 0;
	}

	// Refresh PlayerList
	private function refreshList()
	{
		$this->processPLAYERLIST($this->clientSockets, "PLAYERLIST");
	}

	public function checkPassword($password)
	{
		return ($this->password == "") || ($password == $this->password);
	}

	public function processData(&$socket, $method, $parameter1, $parameter2, $parameter3)
	{
		switch ($method)
		{
			case "SEND":
				$this->processSEND($socket, $parameter1);
				break;

			case "READY":
				if ($this->state == "Ready")
					$this->processREADY($socket, $parameter1);
				break;

			case "QUIT":
				// Client will re-open its socket.
				$this->clientDisconnected($socket);
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
		$nickname = getNicknameBySocket($socket);

		if ($this->state == "Playing" && $this->gameState == "on round")
			if ($this->nowTurn == array_search($socket, $this->clientSockets))
			{
				// It is a word. Check its validity.
				$checkResult = $this->checkWord($message);
				if ($checkResult == "OK")
				{
					// The word is valid.

					$score = $this->getScore($message);
					$this->clientScores[$this->nowTurn] += $score;

					sendToSocketAll($this->clientSockets, "WORD", $nickname, $message, $score);
					//sendToSocketAll($this->clientSockets, "SYSTEMSEND", $nickname, "get score $score.");

					$this->nowTurn = ($this->nowTurn + 1) % sizeof($this->clientSockets);
					$this->gameState = "in animation";
				}
				else // The word is invalid.
					sendToSocketAll($this->clientSockets, "WORD", $nickname, $message, $checkResult);
				return;
			}

		// It is a chat.
		sendToSocketAll($this->clientSockets, "SEND", $nickname, $message);
	}

	private function processREADY(&$socket, $flag)
	{
		global $room_state_changed;
		$index = array_search($socket, $this->clientSockets);
		if ($index === FALSE)
		{
			echo "  Fatal error on processREADY()\n";
			return;
		}

		if (($flag == 1 || $flag == 0) && $this->state == "Ready")
		{
			// 0: not ready, 1: ready
			$this->clientReady[$index] = $flag;
			$this->processPLAYERLIST($this->clientSockets, "PLAYERLIST");

			// Start the game when all ready!
			if (!in_array(0, $this->clientReady))
			{
				$this->state = "Playing";
				$this->gameState = "before game";
				$this->time_temp = microtime(true);

				sendToSocketAll($this->clientSockets, "GAMESTART");
				sendToSocketAll($this->clientSockets, "PLAYBGM", "game_start");

				$room_state_changed = TRUE;
			}
		}
	}

	private function processPLAYERLIST($socketList, $method)
	{
		/**
		 * (playerString): nickname`score`[ready(0/1)/nowTurn(2)]
		 * ex) gunhoflash`9122`2
		 */

		global $client_room;

		$ar = array();

		// Convert arraies to string.
		for ($i = 0; $i < count($this->clientSockets); $i++)
		{
			$ar[] = getNicknameBySocket($this->clientSockets[$i])."`"
			.$this->clientScores[$i]."`"
			.(($this->state == "Playing" && $i == $this->nowTurn) ? "2" : $this->clientReady[$i]);
		}

		sendLongToSocketAll($socketList, $method, $ar);
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