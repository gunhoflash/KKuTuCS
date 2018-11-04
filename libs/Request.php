<?php

class Request
{
	private $requestMessage;

	public function __construct(string $requestMessage)
	{
		$this->requestMessage = $requestMessage;
	}

	public function getResponse()
	{
		$result = (new RequestParser($this->requestMessage))->getResult();
		
		if (!$result['success'])
			return 'HTTP/1.0 404 파일 없음' . PHP_EOL;

		switch ($result['type'])
		{
			case 'Http':
				if ($result['uri'] == "/" || $result['uri'] == "//" || $result['uri'] == "\\")
					$result['uri'] = "views/index.html";

				$filename = PROJECT_ROOT . $result['uri'];
				if (!($fp = fopen($filename, 'r')))
					return 'HTTP/1.0 404 파일 없음' . PHP_EOL;
					
				$responseBody = fread($fp, filesize($filename));

				$response = 'HTTP/1.0 200 OK' . PHP_EOL;
				$response .= 'Content-Type: text/html' . PHP_EOL;
				$response .= PHP_EOL;
				$response .= $responseBody . PHP_EOL;

				return $response;
			break;

			case 'KKuTuCS':
				return "KKuTuCS";
			break;

			default:
				return "bad request";
			break;
		}
		
		
	}
}

class RequestParser
{
	private $message = '';
	private $messageLines = [];
	private $result = [
		'success' => true,
		'type' => '',

		'start_line' => '',
		'method' => '',
		'uri' => '',
		'version' => '',
		'headers' => '',
		'body' => '',

		'KKuTuCS' => ''
	];
	private $supportMethods = ['GET', 'HEAD', 'POST'];
	// private $HTTPMethods = ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE'];
	// Not used now. Please refer to https://tools.ietf.org/html/rfc7231#section-4

	public function __construct(string $message)
	{
		if (strlen($message) < 6)
		{
			false();
			return;
		}

		$this->message = $message;
		if ($this->isKKuTuCSrequest())
		{
			$this->result['type'] = 'KKuTuCS';
			$this->parseKKuTuCS();
		}
		else
		{
			$this->result['type'] = 'Http';
			$this->parseHttp();
		}
	}


	/**
	 * Commons
	 */

	public function getResult(): array
	{
		return $this->result;
	}

	private function isKKuTuCSrequest(): bool
	{
		return (substr($this->message, 0, 7) == "KKuTuCS");
	}
	
	private function false(): void
	{
		$this->result['success'] = false;
		// throwInvalidity();
	}

	// private function throwInvalidity(): void
	// {
	// 	throw new Exception('Invalid message format.');
	// }

	// Split the message with ( \r\n or \n ).
	private function splitMessage(): void
	{
		$this->messageLines = preg_split('/(\r\n|\n)/', $this->message);

		// Trim
		array_walk($this->messageLines, function (&$item) {
			$item = trim($item);
		});
	}



	/**
	 * KKuTuCS
	 */

	private function parseKKuTuCS(): void
	{
		$this->splitMessage();
		if (sizeof($this->messageLines) < 2) 
			$this->messageLines[1] = NULL;
		switch ($this->messageLines[1])
		{
			case "Hello":
			$this->result['KKuTuCS'] = "World!";
			break;

			default:
			$this->result['KKuTuCS'] = "What?";
			break;
		}
	}



	/**
	 * Http
	 */

	private function parseHttp(): void
	{
		$this->splitMessage();
		$this->verifyStartLine();
	}

	private function verifyStartLine(): void
	{
		$this->splitStartLine();

		$this->verifyStartLineMethod();
		$this->verifyStartLineURI();
		$this->verifyStartLineVersion();
	}

	private function splitStartLine(): void
	{
		// If no lines, return false.
		if (empty($this->messageLines) || empty($this->messageLines[0]))
		{
			false();
			return;
		}

		// Split lines with whitespace.
		$startLine = $this->messageLines[0];
		$startLineSplit = preg_split('/\s/', $startLine);

		// The lines should be divided into 3 pieces.
		if (empty($startLineSplit) || count($startLineSplit) !== 3)
		{
			false();
			return;
		}

		$this->result['method'] = $startLineSplit[0];
		$this->result['uri'] = $startLineSplit[1];
		$this->result['version'] = $startLineSplit[2];
	}

	private function verifyStartLineMethod(): void
	{
		$method = $this->result['method'];
		if (empty($method) || !in_array($method, $this->supportMethods))
		{
			false();
			return;
		}
	}

	private function verifyStartLineURI(): void
	{
		$uri = $this->result['uri'];
		if (empty($uri) || strpos($uri, '/') !== 0)
		{
			false();
			return;
		}
	}

	private function verifyStartLineVersion(): void
	{
		$version = $this->result['version'];
		if (empty($version) || !preg_match('/HTTP\/\d+.\d+/', $version))
		{
			false();
			return;
		}
	}
}
?>