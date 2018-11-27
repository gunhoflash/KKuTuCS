<?php
defined('PROJECT_ROOT') or exit('No direct script access allowed');

/**
 * Check the given word with regular expression.
 * return: TRUE or FALSE
 */
function isValid($word)
{
	return (strlen($word) > 0) && (preg_match("/[^a-zA-Z]/",$word) == 0);
}

/**
 * Check if two words are chained.
 * return: TRUE or FALSE
 */
function isChained($lastWord, $newWord)
{
	if($lastWord=="") return TRUE;
	return ($newWord[0] == substr($lastWord, -1));
}

// TODO: Edit below functions

//사전DB내에 있는지 검사
//있을 경우 TRUE 아니면 FALSE
function isInDB ($word)
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
function isUsed ($Word, $WordHistory)
{
	if ($WordHistory == NULL) return FALSE;
	$lowerword = strtolower($Word);
	return in_array($lowerword, $WordHistory); 
}

// TODO: Edit here.
function getRandomWord()
{
	global $conn;

	$re = mysqli_query($conn, "SELECT COUNT(*) FROM kkutudb.kkutu_en");
	$row = mysqli_fetch_all($re);
	var_dump($row);
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