<?php
defined('PROJECT_ROOT') or exit('No direct script access allowed');

/**
 * Check the given word with regular expression.
 * return: TRUE or FALSE
 */
function isValid($word)
{
	return (strlen($word) > 0) && (preg_match("/[^a-zA-Z\s]/",$word) == 0);
}

/**
 * Check if two words are chained.
 * return: TRUE or FALSE
 */
function isChained($lastWord, $newWord)
{
	return $newWord[0] == substr($lastWord, -1);
}

// TODO: Edit below functions

//사전DB내에 있는지 검사
//있을 경우 TRUE 아니면 FALSE
function isInDB($word)
{
	global $conn;

	$re = mysqli_query($conn, "SELECT * FROM kkutudb.kkutu_en WHERE _id = '$word'");
	while ($row = mysqli_fetch_array($re))
	{
		if ($row[0] != NULL)
			return TRUE;
	}
	mysqli_free_result($re);
	return FALSE;
}

//사용됐던 단어인지 확인
//사용된 경우 TRUE 아니면 FALSE
//array_search가 대소문자를 구별하기때문에, 모든 단어가 소문자로 들어감.
function isUsed($Word, $WordHistory)
{
	if ($WordHistory == NULL) return FALSE;
	$lowerword = strtolower($Word);
	return in_array($lowerword, $WordHistory); 
}

// Get a random word in DB.
function getRandomWord()
{
	global $conn;

	$re = mysqli_query($conn, "SELECT _id FROM kkutudb.kkutu_en");
	$rows = mysqli_fetch_all($re);
	mysqli_free_result($re);
	return $rows[rand(0, sizeof($rows))][0];
}

//이하로는, 한국어 끄투를 위해 사용되는 함수들입니다.

function isValid_K($word)
{
	return (strlen($word) > 0) && (preg_match("/[^가-힣\s]/",$word) == 0);
}

/**
 * Check if two words are chained.
 * return: TRUE or FALSE
 */
function isChained_K($lastWord, $newWord)
{
	$newWord_first = mb_substr($newWord, 0, 1, 'utf-8');
	$lastWord_last = mb_substr($lastWord, -1, 1,'utf-8');
	$last_u = ucord($lastWord_last);
	$first_u = ucord($newWord_first);

	if($last_u>=45208 && $last_u<=45795) $last2=ucchr($last_u+5292);
	if($last_u>=46972 && $last_u<=47559) $last2=ucchr($last_u+3528);

	if($newWord_first!=$lastWord_last && $newWord_first!=$last2) return FALSE;
	else return TRUE;
}


//사전DB내에 있는지 검사
//있을 경우 TRUE 아니면 FALSE
function isInDB_K($word)
{
	global $conn;

	$re = mysqli_query($conn, "SELECT * FROM kkutudb.kkutu_ko WHERE _id = '$word'");
	while ($row = mysqli_fetch_array($re))
	{
		if ($row[0] != NULL)
			return TRUE;
	}
	mysqli_free_result($re);
	return FALSE;
}

//사용됐던 단어인지 확인
//사용된 경우 TRUE 아니면 FALSE
//array_search가 대소문자를 구별하기때문에, 모든 단어가 소문자로 들어감.
function isUsed_K($Word, $WordHistory)
{
	if ($WordHistory == NULL) return FALSE;
	return in_array($Word, $WordHistory); 
}

// Get a random word in DB.
function getRandomWord_K()
{
	global $conn;

	$re = mysqli_query($conn, "SELECT _id FROM kkutudb.kkutu_ko ORDER BY RAND() LIMIT 1");
	$rows = mysqli_fetch_all($re);
	mysqli_free_result($re);
	return $rows[0][0];
}
//두음법칙을 위한 유니코드 전환기
function ucord($uc) 
{
	return (((ord($uc[0]) ^ 0xe0) << 12) | ((ord($uc[1]) ^ 0x80) << 6) | (ord($uc[2]) ^ 0x80));
}

function ucchr($uc){
	return chr(0xe0 | ($uc >> 12)) . chr(0x80 | (($uc & 0xfc0) >> 6)) . chr(0x80 | ($uc & 0x3f));
}

function checkKorean($word)
{
	$last = ucord(mb_substr($word, -1, 1, 'utf-8'));
	if($last>=45208 && $last<=45795) return '('.ucchr($last+5292).')';
	if($last>=46972 && $last<=47559) return '('.ucchr($last+3528).')';
	else return;
}

/*
//이하 주석처리된 코드들은 모두 SQL 이용
//사용됐던 단어인지 확인
function isUsed ($conn, $word) {
	$re = mysqli_query($conn, "SELECT * FROM entries.used WHERE used = '$word'");
	while($row = mysqli_fetch_array($re))
	{
		if($row[0]!=NULL) {
			return TRUE;
		}
	}
	return FALSE;
	mysqli_free_result($re);
}
//DB내로 단어 집어넣기
function push_sql($conn, $word) {
	$sql = "INSERT INTO entries.used VALUES (DEFAULT, '$word')";
	$re = mysqli_query($conn, $sql);
	mysqli_free_result($re);
}

//사용됐던 단어들 출력
function LastWord ($conn) {
	echo "<br>";
	$sql = "SELECT * FROM entries.used ORDER BY id DESC LIMIT 5";
	$re = mysqli_query($conn, $sql);
	while ($row = mysqli_fetch_array($re)) {
		echo "<- {$row['used']} ";
	} 
	echo "<br>";
	mysqli_free_result($re);
}
*/
?>