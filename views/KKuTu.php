<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>KKuTu CS</title>
</head>
<body>
    <h1>SQL 연습</h1>
    <form action="KKuTu.php" method="post">
        <p><input type="text" name="type" size="20" value="Type word here" maxlength="30"></p>
        <p><input type="submit" /></p>
       </form>

</body>
</html>

<?php 

header("Pragma: no-cache");   
header("Cache-Control: no-cache,must-revalidate");   

// MYSQL 설치가 필요합니다.

$UsedWord = array ("Hello");
$word = $_POST['type'];

$conn = mysqli_connect("p:localhost", "root", "your password", "entries");

//연결 확인
if($conn) {
    echo "connecteD<br>";
}
else {
    echo "failed";
}

//정규표현식 검사
function isRegExp ($word) {
    $check_word = preg_match("/[a-zA-Z]/",$word);
    if($check_word == 0) {
        return FALSE;
    }
    else {
        return TRUE;
    }
}

//끝말이 이어지는지 검사
function isChained ($conn, $word) {
    $re = mysqli_query($conn, "SELECT RIGHT(used, 1) FROM entries.used ORDER BY id DESC LIMIT 1");
    while ($row = mysqli_fetch_row($re)) {
        $lastchr = substr($word, 0, 1);
        if($lastchr == $row[0]) {
            return TRUE;
        }
    }
    return FALSE;
    mysqli_free_result($re);
}

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
if(isRegEXP($word)) {
    if(isChained($conn, $word)) {
        if(isInDB($conn, $word)) {
            if(!isUsed($conn,$word)) {
                if(push_sql($conn, $word)) {
                    echo "error";
                }
                else echo "Success!";
            }
            else {
                echo "That word is already used";
            }
        }
        else {
            echo "There's no word in DB";
        }
    }
    else{
        echo "Not Chained";
    }
}
else {
    echo "Type English Word";
}
LastWord ($conn);

?>