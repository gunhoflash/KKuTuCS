<?php
$VALID_START = "KKuTuCS";
$VALID_METHOD = array(
	"NUMBER_OF_CLIENT",
	"SEND_WORD",
	"READY",
	"START"
);

class KKuTuCSRequest
{
	private $requestMessage;
	private $result = [
		"validity" => false,
		"method" => NULL,
		"parameter" => NULL,
		"else" => NULL
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
		$messages = preg_split("/(\r\n|\n)/", $this->requestMessage, 4);

		// Trim
		array_walk($messages, function (&$m) {
			$m = trim($m);
		});

		if (count($messages) == 0)
		{
			setValidity(false);
			return;
		}

		if ($messages[0] != "KKuTuCS")
		{
			setValidity(false);
			return;
		}

		if (count($messages) > 1) $this->result["method"] = $messages[1];
		if (count($messages) > 2) $this->result["parameter"] = $messages[2];
		if (count($messages) > 3) $this->result["else"] = $messages[3];
	}

	/**
	 * Setter ( private )
	 */
	private function setValidity(boolan $b)
	{
		$this->result["validity"] = $b;
	}

	/**
	 * Getter ( public )
	 */
	public function getValidity()
	{
		return $this->result["validity"];
	}
	public function getMathod()
	{
		return $this->result["method"];
	}
	public function getParameter()
	{
		return $this->result["parameter"];
	}
	public function getElse()
	{
		return $this->result["else"];
	}
}

?>