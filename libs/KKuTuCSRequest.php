<?php
defined('PROJECT_ROOT') or exit('No direct script access allowed');

class KKuTuCSRequest
{
	private static $VALID_START   = "KKuTuCS";
	private static $VALID_METHODS = array(
		"JOIN",       // (main)      Enter a game.                   (Syntax: JOIN roomIndex password)
		"MAKE",       // (main)      Make new room.                  (Syntax: MAKE roomname password)
		"ROOMLIST",   // (main)      Get a list of all rooms.        (Syntax: ROOMLIST roomIndex password)
		"SEND",       // (main/room) Send a word or chat.            (Syntax: SEND msg)
		"READY",      // (room)      Ready to start a game.          (Syntax: READY flag)
		"QUIT",       // (room)      Quit a game while playing.      (Syntax: QUIT)
		"TIMETEST",   // (main/room) Measure response-time.          (Syntax: TIMETEST)
		"ROUNDOVER",  // (room)      End round and calculate score.  (Syntax: ROUNDOVER)
		"PLAYERLIST", // (room)      Receive Playerlist from server. (Syntax: PLAYERLIST str)
		"RESULT"      // (room)      Popup Result Modal              (Syntax: RESULT str)
	);
	private $requestMessage;
	private $result = [
		"validity"   => FALSE,
		"method"     => NULL,
		"parameter1" => NULL,
		"parameter2" => NULL
	];
	
	/**
	 * Constructor
	 */
	public function __construct(string $requestMessage)
	{
		// Split the message.
		$messages = preg_split("/(\r\n|\n)/", $requestMessage, 4);
		$numberOfMessages = count($messages);

		// Trim the message.
		array_walk($messages, function (&$m) {
			$m = trim($m);
		});

		// Message is incomplete.
		if ($numberOfMessages < 2)
		{
			$this->setValidity(FALSE);
			return;
		}

		// Invalid start.
		if ($messages[0] != self::$VALID_START)
		{
			$this->setValidity(FALSE);
			return;
		}
		
		// Invalid method.
		if (!in_array($messages[1], self::$VALID_METHODS))
		{
			$this->setValidity(FALSE);
			return;
		}

		$this->result["method"] = $messages[1];
		if ($numberOfMessages > 2)
			$this->result["parameter1"] = $messages[2];
		if ($numberOfMessages > 3)
			$this->result["parameter2"] = $messages[3];

		$this->setValidity(TRUE);
	}

	/**
	 * Setter
	 */
	private function setValidity(bool $b) { $this->result["validity"] = $b; }

	/**
	 * Getter
	 */
	public function getValidity()        { return $this->result["validity"];     }
	public function getMethod()          { return $this->result["method"];       }
	public function getParameter(int $n) { return $this->result["parameter".$n]; }
	public function getElse()            { return $this->result["else"];         }
}

?>