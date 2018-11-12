<?php
include './libs/socketHandle.php';

class GameRoom
{
	private $roomType = NULL; // "main" or "game"
	private $state = "Ready";
	private $name = "KKuTuCS";
	private $password = NULL; // "": no password
	private $maximumClients = 4; // 0: no limit
	private $clientSockets = array();
	private $wordHistory = array();
	
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
		$this->sendToAll("Client {$socketString} connected.");
		$this->clientSockets[] = $socket;
	}

	public function clientDisconnected(&$socket)
	{
		$socketString = socketToString($socket);
		$key = array_search($socket, $this->clientSockets);
		unset($this->clientSockets[$key]);
		socket_close($socket);

		$this->sendToAll("Client {$socketString} disconnected.");
	}

	public function checkWord()
	{
		// TODO: Do something
	}

	public function startGame()
	{
		// TODO: Do something
	}

	private function sendToAll($message)
	{
		try
		{
			$encodedMessage = encode($message);
		}
		catch (Exception $e)
		{
			echo "Exception on encode: ".$e->getMessage()."\n";
			return;
		}

		foreach ($this->clientSockets as $client)
		{
			socket_write($client, $encodedMessage);
		}
	}

	/**
	 * Getter
	 */
	public function getRoomType()       { return $this->roomType;        }
	public function getState()          { return $this->state;           }
	public function getName()           { return $this->name;            }
	public function getPassword()       { return $this->password;        }
	public function getMaximumClients() { return $this->maximumClients;  }
	public function getClientSockets()  { return $this->clientSockets;   }
	public function getNumberOfClient() { return sizeof($this->clientSockets); }
}

?>