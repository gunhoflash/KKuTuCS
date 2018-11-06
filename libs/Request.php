<?php

class Request
{
	private $result;

	public function __construct(string $requestMessage)
	{
		$this->result = (new RequestParser($requestMessage))->getResult();
	}
	
	public function getRequestUri()
	{
		return $this->result['uri'];
	}

	public function getRequestBody()
	{
		return $this->result['body'];
	}

	public function getRequestMethod()
	{
		return $this->result['method'];
	}

	public function getResponse()
	{
		$response = "bad request" . PHP_EOL;

		if ($this->result['validity'])
		{
			if ($this->result['method'] == 'KKUTUCS')
				$response = $this->getKKuTuCSResponse();
			else
				$response = $this->getHttpResponse();
		}

		return $response;
	}

	private function getHttpResponse()
	{
		if ($this->result['uri'] == "/" || $this->result['uri'] == "//" || $this->result['uri'] == "\\")
			$this->result['uri'] = "views/index.html";

		$filename = PROJECT_ROOT . $this->result['uri'];
		if (!($fp = fopen($filename, 'r')))
			return 'HTTP/1.0 404 파일 없음' . PHP_EOL;
			
		$responseBody = fread($fp, filesize($filename));

		$response = 'HTTP/1.0 200 OK' . PHP_EOL;
		$response .= 'Content-Type: text/html' . PHP_EOL;
		$response .= PHP_EOL;
		$response .= $responseBody . PHP_EOL;

		return $response;
	}

	private function getKKuTuCSResponse()
	{
		return var_dump($this->result['KKuTuCS']) . PHP_EOL;
	}
}

class RequestParser
{
	private $message = '';
	private $messageLines = [];
	private $result = [
		'validity' => true,

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
		$this->message = $message;
		$this->parse();
	}

	public function getResult(): array
	{
		return $this->result;
	}

	private function false(): void
	{
		$this->result['validity'] = false;
		// throwInvalidity();
	}

	// private function throwInvalidity(): void
	// {
	// 	throw new Exception('Invalid message format.');
	// }

	private function parse(): void
	{
		$this->splitMessage();
		$this->verifyStartLine();
	}

	// Split the message with ( \r\n or \n ).
	private function splitMessage(): void
	{
		$this->messageLines = preg_split('/(\r\n|\n)/', $this->message);

		// Trim
		array_walk($this->messageLines, function (&$item) {
			$item = trim($item);
		});
	}

	private function verifyStartLine(): void
	{
		$this->splitStartLine();

		if ($this->result['method'] == 'KKUTUCS')
		{
			$this->result['KKuTuCS'] = $this->messageLines;
		}
		else
		{
			$this->verifyStartLineMethod();
			$this->verifyStartLineURI();
			$this->verifyStartLineVersion();
		}
	}

	private function splitStartLine(): void
	{
		// If no lines, return false.
		if (empty($this->messageLines) || empty($this->messageLines[0]))
		{
			$this->false();
			return;
		}

		// Split lines with whitespace.
		$startLine = $this->messageLines[0];
		$startLineSplit = preg_split('/\s/', $startLine);

		// The lines should be divided into 3 pieces.
		if (empty($startLineSplit) || count($startLineSplit) !== 3)
		{
			$this->false();
			return;
		}

		$this->result['method'] = $startLineSplit[0];
		$this->result['uri'] = $startLineSplit[1];
		$this->result['version'] = $startLineSplit[2];
	}

	private function verifyStartLineMethod(): void
	{
		if (empty($this->result['method']) || !in_array($this->result['method'], $this->supportMethods))
			$this->false();
	}

	private function verifyStartLineURI(): void
	{
		if ($this->result['uri'] == "/" || $this->result['uri'] == "//" || $this->result['uri'] == "\\")
			$this->result['uri'] = "views/index.html";

		if (empty($this->result['uri']) || strpos($this->result['uri'], '/') !== 0)
			$this->false();
	}

	private function verifyStartLineVersion(): void
	{
		if (empty($this->result['version']) || !preg_match('/HTTP\/\d+.\d+/', $this->result['version']))
			$this->false();
	}
}
?>