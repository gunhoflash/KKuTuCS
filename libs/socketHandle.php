<?php

// Functions for socket

function socketToString($socket)
{
	socket_getpeername($socket, $IP, $PORT);
	return "{".$IP.":".$PORT."}";
}

function unmask($payload)
{
	$length = ord($payload[1]) & 127;

	if ($length == 126)
	{
		$masks = substr($payload, 4, 4);
		$data = substr($payload, 8);
	}
	else if ($length == 127)
	{
		$masks = substr($payload, 10, 4);
		$data = substr($payload, 14);
	}
	else
	{
		$masks = substr($payload, 2, 4);
		$data = substr($payload, 6);
	}

	$text = '';
	for ($i = 0; $i < strlen($data); ++$i)
		$text .= $data[$i] ^ $masks[$i % 4];

	return $text;
}

function encode($text)
{
	// 0x1 text frame (FIN + opcode)
	$b1 = 0x80 | (0x1 & 0x0f);
	$length = strlen($text);

	if ($length <= 125)
		$header = pack('CC', $b1, $length);
	else if ($length > 125 && $length < 65536)
		$header = pack('CCS', $b1, 126, $length);
	else if ($length >= 65536)
		$header = pack('CCN', $b1, 127, $length);

	return $header.$text;
}

?>