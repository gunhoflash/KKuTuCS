<?php

class GameRoom
{
	private $state = "Ready";
	private $name = "KKuTuCS";
	private $password = NULL;
	private $maximumClients = 4;
	private $clientSockets = array();
	private $wordHistory = array();
	
	/**
	 * Constructor
	 */
	public function __construct(string $name, string $password, int $maximumClients)
	{
		$this->name = $name;
		$this->password = $password;
		$this->maximumClients = $maximumClients;
	}

	public function checkWord()
	{
		// TODO: Do something
	}

	public function startGame()
	{
		// TODO: Do something
	}

	/**
	 * Getter
	 */
	public function getState()          { return $this->state;           }
	public function getName()           { return $this->name;            }
	public function getPassword()       { return $this->password;        }
	public function getMaximumClients() { return $this->$maximumClients; }
}

?>