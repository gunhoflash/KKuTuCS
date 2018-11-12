<?php

class KKuTuCSRequest
{
	private static $VALID_START = "KKuTuCS";
	private static $VALID_METHOD = array(
		"JOIN",

		"SEND",
		"READY",
		"START"
	);
	private $requestMessage;
	private $result = [
		"validity"  => FALSE,
		"method"    => NULL,
		"parameter" => NULL,
		"else"      => NULL
	];
	
	/**
	 * Constructor
	 */
	public function __construct(string $requestMessage)
	{
		$this->requestMessage = $requestMessage;
		$this->parse();
	}

	private function parse()
	{
		// Split the message.
		$messages = preg_split("/(\r\n|\n)/", $this->requestMessage, 4);

		// Trim the message.
		array_walk($messages, function (&$m) {
			$m = trim($m);
		});

		// Message is incomplete.
		if (count($messages) < 2)
		{
			$this->setValidity(FALSE);
			return;
		}

		// Invalid start. (But... is it needed?)
		if ($messages[0] != self::$VALID_START)
		{
			$this->setValidity(FALSE);
			return;
		}
		
		// Invalid method.
		if (!in_array($messages[1], self::$VALID_METHOD))
		{
			$this->setValidity(FALSE);
			return;
		}

		$this->result["method"] = $messages[1];
		if (count($messages) > 2)
			$this->result["parameter"] = $messages[2];
		if (count($messages) > 3)
			$this->result["else"] = $messages[3];

		$this->setValidity(TRUE);
	}

	/**
	 * Setter
	 */
	private function setValidity(bool $b) { $this->result["validity"] = $b; }

	/**
	 * Getter
	 */
	public function getValidity()  { return $this->result["validity"];  }
	public function getMethod()    { return $this->result["method"];    }
	public function getParameter() { return $this->result["parameter"]; }
	public function getElse()      { return $this->result["else"];      }
}

?>