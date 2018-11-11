<?php

class Client
{
	private $nickname = NULL;
	private $socket = NULL;
	private $score = 0;
	
	/**
	 * Constructor
	 */
	public function __construct(string $nickname, string $socket)
	{
		$this->nickname = $nickname;
		$this->socket = $socket;
	}

	public function addScore(int $n)
	{
		$this->score += $n;
	}

	public function resetScore()
	{
		$this->score = 0;
	}

	/**
	 * Getter
	 */
	public function getNickname() { return $this->$nickname; }
	public function getSocket()   { return $this->socket;    }
	public function getScore()    { return $this->score;     }
}

?>