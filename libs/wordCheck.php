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
	return ($newWord[0] == substr($lastWord, -1));
}

// TODO: Edit below functions

//사전DB내에 있는지 검사
function isInDB ($conn, $word) {
	$re = mysqli_query($conn, "SELECT * FROM entries.entries WHERE word = '$word'");
	while($row = mysqli_fetch_array($re))
	{
		if($row[0]!=NULL) {
			return TRUE;
		}
	}
	return FALSE;
	mysqli_free_result($re);
}

//사용된 단어 DB내에 있는지 검사
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
?>