<?php
defined('PROJECT_ROOT') or exit('No direct script access allowed');

/**
 * Below functions(isValid, isChained, isInDB, isUsed) return TRUE or FALSE.
 */

// Check validity of the word with regular expression.
function isValid($mode, $word)
{
	if (strlen($word) == 0)
		return FALSE;

	if ($mode == "en")
		return preg_match("/[^a-zA-Z\s]/", $word) == 0;
	else
		return preg_match("/[^가-힣\s]/", $word) == 0;
}

// Check if two words are chained.
function isChained($mode, $lastWord, $newWord)
{
	if ($mode == "en")
		return $newWord[0] == substr($lastWord, -1);

	// kr
	$newWord_first = mb_substr($newWord,   0, 1, 'utf-8');
	$lastWord_last = mb_substr($lastWord, -1, 1, 'utf-8');
	$last_u  = ucord($lastWord_last);
	$first_u = ucord($newWord_first);

	if ($last_u >= 45208 && $last_u <= 45795)
		$last2 = ucchr($last_u + 5292);
	else if ($last_u >= 46972 && $last_u <= 47559)
		$last2 = ucchr($last_u + 3528);

	return !($newWord_first != $lastWord_last && $newWord_first != $last2);
}

// Check if the word is in DB.
function isInDB($mode, $word)
{
	global $conn;

	$queryString = "SELECT * FROM kkutudb.kkutu_" . ($mode == "en" ? "en" : "ko") . " WHERE _id = '$word';";
	$result = mysqli_query($conn, $queryString);
	$row = mysqli_fetch_array($result);
	mysqli_free_result($result);

	return $row ? TRUE : FALSE;
}

// Check if the word was used before.
function isUsed($mode, $word, $wordHistory)
{
	if (is_null($wordHistory))
		return FALSE;

	return in_array(strtolower($word), $wordHistory); 
}

// Get a random word in DB.
function getRandomWord($mode)
{
	global $conn;

	$queryString = "SELECT * FROM kkutudb.kkutu_" . ($mode == "en" ? "en" : "ko") . " ORDER BY RAND() LIMIT 1;";
	$result = mysqli_query($conn, $queryString);
	$rows = mysqli_fetch_all($result);
	mysqli_free_result($result);

	return $rows[0][0];
}

// 두음법칙을 위한 유니코드 전환기
function ucord($uc) 
{
	return (((ord($uc[0]) ^ 0xe0) << 12) | ((ord($uc[1]) ^ 0x80) << 6) | (ord($uc[2]) ^ 0x80));
}

function ucchr($uc)
{
	return chr(0xe0 | ($uc >> 12)) . chr(0x80 | (($uc & 0xfc0) >> 6)) . chr(0x80 | ($uc & 0x3f));
}

function checkKorean($word)
{
	$last = ucord(mb_substr($word, -1, 1, 'utf-8'));
	if ($last >= 45208 && $last <= 45795) return '('.ucchr($last+5292).')';
	if ($last >= 46972 && $last <= 47559) return '('.ucchr($last+3528).')';
	return "";
}
?>