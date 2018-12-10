var socketLink = "ws://"+window.location.hostname+":7002";
var socket;
var roomList = [];
var resultList = [];
var playerList = [];
var responseTime = 0;
var uriQueries = [];

var audio = document.createElement("audio");

function initializeSocketAndObject()
{
	// Initialize views and variable
	$("#btn_ready").removeClass("d-none");
	$("*[data-ismain]").attr("data-ismain", "true");
	showWord("");
	$("#chatArea").text("").trigger("create");
	processROOMLIST();

	socket = new WebSocket(socketLink);
	$("#Mainname").text("(Connecting..)");

	// When the socket open
	socket.onopen = function(event)
	{
		console.log("Socket opened.", event);
		$("#Mainname").text("KKuTuCS");
		
		// Decode url to get the nickname.
		decodeURI(window.location.search).substr(1).split("&").forEach(function (str)
		{
			var ar = str.split("=");
			if (ar.length == 2)
				uriQueries[ar[0]] = ar[1]; // Save the datas to 'uriQueries'.
		});
		if (uriQueries["nickname"] == undefined || uriQueries["nickname"] == "")
		{
			alert("Error: Nickname not found.");
			window.location.href = "http://" + window.location.host;
		}

		$("#title").text("KKuTuCS(" + uriQueries["nickname"] + ")");
		sendMessage("ROOMLIST", uriQueries["nickname"]);
	};

	// When the socket receive a message
	socket.onmessage = function(event)
	{
		console.log("Socket received a message.", event.data);
		parseMessage(event.data);
	};

	// When the socket close
	socket.onclose = function(event)
	{
		console.log("Socket closed.", event);
		initializeSocketAndObject(); // Try to reconnect
	};
}

// TODO: If the number of parameters will be more than 3, then replace these parameters to an one array.
function sendMessage(method, parameter1, parameter2, parameter3)
{
	/*
	socket.readyState

	Constant    Value   Description
	CONNECTING  0       연결이 수립되지 않은 상태입니다.
	OPEN        1       연결이 수립되어 데이터가 오고갈 수 있는 상태입니다.
	CLOSING     2       연결이 닫히는 중 입니다.
	CLOSED      3       연결이 종료되었거나, 연결에 실패한 경우입니다.
	*/
	
	if (!parameter1) parameter1 = "";
	if (!parameter2) parameter2 = "";
	if (!parameter3) parameter3 = "";

	if (socket.readyState == socket.OPEN)
	{
		var str = "KKuTuCS\n" + method + "\n" + parameter1 + "\n" + parameter2 + "\n" + parameter3;
		console.log(str);
		socket.send(str);
	}
	else
		alert("Cannot send!\nsocket.readyState: " + socket.readyState);
}

function parseMessage(data)
{
	var datas = xssFilter(data).split("\n");
	var method = datas[0];
	var parameter1 = datas[1];
	var parameter2 = datas[2];
	var parameter3 = datas[3];

	switch (method)
	{
		case "SEND": // (Syntax: SEND nickname message)
			showChat(parameter1, parameter2, false);
			break;

		case "SYSTEMSEND": // (Syntax: SYSTEMSEND nickname message)
			showChat(parameter1, parameter2, true);
			break;

		case "WORD": // (Syntax: WORD nickname message result)
			processWORD(parameter1, parameter2, parameter3);
			break;

		case "JOIN": // (Syntax: JOIN [1/0] [roonname/error_message] roomIndex)
			processJOIN(parameter1, parameter2, parameter3);
			break;

		case "DISCONNECTED": // (Syntax: DISCONNECTED nickname)
			showChat(parameter1, "is disconnected.", true);
			break;

		case "CONNECTED": // (Syntax: CONNECTED nickname)
			showChat(parameter1, "is connected.", true);
			break;

		case "ROOMLISTSTART"    : roomList = [];               break; // (Syntax: ROOMLISTSTART)
		case "ROOMLISTMIDDLE"   : roomList.push(parameter1);   break; // (Syntax: ROOMLISTMIDDLE roomString)
		case "ROOMLISTEND"      : processROOMLIST();           break; // (Syntax: ROOMLISTEND)

		case "RESULTLISTSTART"  : resultList = [];             break; // (Syntax: RESULTLISTSTART)
		case "RESULTLISTMIDDLE" : resultList.push(parameter1); break; // (Syntax: RESULTLISTMIDDLE resultString)
		case "RESULTLISTEND"    : processRESULT();             break; // (Syntax: RESULTLISTEND)

		case "PLAYERLISTSTART"  : playerList = [];             break; // (Syntax: PLAYERLISTSTART)
		case "PLAYERLISTMIDDLE" : playerList.push(parameter1); break; // (Syntax: PLAYERLISTMIDDLE playerString)
		case "PLAYERLISTEND"    : processPLAYERLIST();         break; // (Syntax: PLAYERLISTEND)

		case "NUMBEROFPEOPLE" : // (Syntax: NUMBEROFPEOPLE number)
			$("#numberOfPeople").text(parameter1 + " 명이 메인에서 노닥거리는 중");
			break;

		case "TIMETEST": // (Syntax: TIMETEST)
			console.log("response time: " + ((new Date()).getTime() - responseTime) + "ms");
			break;

		case "ERROR": // (Syntax: ERROR errorMessage)
			alert("[Server Error] " + parameter1);
			break;

		case "CORRECT": // (Syntax: ERROR word)
			removeInterval(0);
			removeInterval(1);
			showWord(parameter1);
			break;

		case "PLAYBGM": // (Syntax: PLAYBGM name playspeed)
			processBGM(parameter1, parameter2, true);
			break;

		case "GAMESTART": // (Syntax: GAMESTART)
			removeInterval(0);
			removeInterval(1);
			$("#btn_ready").addClass("d-none");
			break;

		case "ROUNDSTART": // (Syntax: ROUNDSTART roundTime)
			showRoundTimer(parameter1, parameter1);
			removeInterval(0);
			break;
		
		case "ROUNDOVER": // (Syntax: ROUNDOVER)
			removeInterval(0);
			break;

		case "TURNSTART": // (Syntax: TURNSTART turnTime roundTime)
			audio.pause();
			showTurnTimer(parameter1);
			showRoundTimer(parameter2, null);
			processBGM("T", parameter1*10, true);
			break;

		case "QUITTED": // (Syntax: QUITTED nickname)
			showChat(parameter1, "quitted.", true);
			break;

		case "ANIMATION": // (Syntax: ANIMATION turnspeed word)
			processANIMATION(parameter1, parameter2);
			break;

		default:
			alert("[Unexpected Method Error] " + method);
			break;
	}
}

function showChat(nickname, message, isSystem)
{
	if (nickname == null || nickname == undefined) nickname = "";
	if (message == null || message == undefined) message = "";

	if (nickname.length > 0)
		nickname = "<span class='text-primary'>[" + nickname + "]</span>&nbsp;";
	if (!isSystem)
		nickname += ":&nbsp;";

	$("#chatArea").append("<p class='mb-1"+(isSystem ? " text-muted" : "")+"'>" + nickname + message + "</p>");
	$("#chatArea").scrollTop($("#chatArea").prop("scrollHeight"));
}

function processWORD(nickname, message, result)
{
	switch (result)
	{
		case "VALID":
		case "CHAIN":
			showChat(nickname, message, false);
			break;

		case "DB":
			showChat(nickname, "<span class='text-danger'>" + message + "</span><span class='text-muted'>(없는 단어)</span>", false);
			break;

		case "USED":
			showChat(nickname, "<span class='text-danger'>" + message + "</span><span class='text-muted'>(이미 쓰인 단어)</span>", false);
			break;

		default:
			if (result == parseInt(result))
				showChat(nickname, "<span class='text-success'>" + message + "</span><span class='ml-1 text-muted'>+ " + result + "</span>", false);
			break;
	}
}

function processJOIN(success, message, roomIndex)
{
	if (success != 1)
	{
		alert("Cannot join!\n" + message);
		return;
	}

	// Join the game
	$("#Roomindex").text("#" + roomIndex);
	$("#Roomname").text(message);
	$("#btn_ready").removeClass("d-none").attr("data-ready", "0");
	$("*[data-ismain]").attr("data-ismain", "false");
	$("#chatArea").text("").trigger("create");
	showChat(null, "Welcome to " + message + "!", true);
}

function processROOMLIST()
{
	/**
	 * (roomString): roomIndex`roomname`mode`isPlaying`now/max`needPassword
	 * ex) 3`Come on!`en`0`2/4`0
	 */

	var room, i, str = "";
	for (i = 0; i < roomList.length; i++)
	{
		room = roomList[i].split('`');
		if (room.length < 6) continue;

		str +=
		"<div class='gameroom border shadow-hoverable-sm px-3 py-2 mb-2 text-truncate bg-white' data-index="+room[0]+" data-pw="+room[5]+">"+
			"<span class='font-weight-bold'><span class='pr-1 text-primary'>#"+room[0]+"</span>"+room[1]+"</span>"+
			"<div class='d-flex small'>"+
				"<span class='text-muted'>"+(room[2] == 'en' ? "En" : "한")+"</span>"+
				(room[3] == '0' ? "<span class='text-success px-1'>Ready" : "<span class='text-warning px-1'>Playing")+"</span>"+
				"<span class='text-black'>"+room[4]+"</span>"+
				"<span class='text-muted ml-auto'>"+(room[5] == '0' ? "" : "PW")+"</span>"+
			"</div>"+
		"</div>";
	}

	// If there is no room, show 'No room'.
	if (str == "") str = "<p class='py-3 text-center text-muted'>No room</p>";

	$("#roomlistArea").html(str).trigger("create");
}

function processRESULT()
{
	$("#btn_ready").removeClass("d-none").attr("data-ready", "0");
	removeInterval(0);
	removeInterval(1);
	showChat(null, "Game over.", true);

	// need for sort
	function descending ( a, b ) {  return ( b < a ) ? -1 : ( b == a ) ? 0 : 1; } 

	/**
	 * (resultString): nickname`score
	 * ex) gunhoflash`9122
	 */
	var result, j, i, str = "";
	var score = [];
	for (i = 0; i < resultList.length; i++)
	{
		result = resultList[i].split('`');
		if (result.length < 2) continue;
		score[i] = parseInt(result[1], 10)*10 + i;
	}
	/** 
	 * score[] = (player's score)(index)
	 * ex) 4752 == score of index 2 is 475.
	 */
	score = score.sort(descending);

	for (i = 0; i < resultList.length; i++)
	{
		j = score[i] % 10;
		result = resultList[j].split('`');
		if (result.length < 2) continue;
		str += (i+1) + ". " + result[0] + "'s score : " + result[1] + "<br>";
	}

	$("#round_timer").css("width", "0%");
	$("#turn_timer").css("width", "0%");
	$("#resultScreen").modal('show');
	$("#resultScreenBody").html(str).trigger("create");

	playerList = resultList;
	processPLAYERLIST();
}

function processPLAYERLIST()
{
	/**
	 * (playerString): nickname`score`[ready(0/1)/nowTurn(2)]
	 * ex) gunhoflash`9122`2
	 */
	var player, i, str = "";
	for (i = 0; i < playerList.length; i++)
	{
		player = playerList[i].split('`');
		if (player.length < 3) continue;
		str += 
		"<div class='gameroom d-flex border shadow-hoverable-sm px-3 py-2 mb-2 text-truncate"+(player[2] == "2" ? " bg-teal" : "")+" bg-white'>"+
			"<img src='./public/img/kkutucs_char.png' class='mr-2 my-auto' style='height: 2.25rem;'>"+
			"<div>"+
				"<h6>"+player[0]+"</h6>"+
				"<div class='d-flex'>"+
					(player[2] == "0" ? "<span class='text-warning'>Not Ready" : "<span class='text-success'>Ready")+"</span>"+
					"<span class='text-black px-1'>"+player[1]+"</span>"+
				"</div>"+
			"</div>"+
		"</div>";
	}

	$("#roomlistArea").html(str).trigger("create");
}

function processBGM(BGMtitle, playSpeed, isBackground)
{
	var temp_audio = document.createElement('audio');
	if (playSpeed != 0) BGMtitle += playSpeed;
	temp_audio.src = "public/media/"+BGMtitle+".mp3";
	
	// Pause audio.
	audio.pause();
	if (BGMtitle == "lobbyBGM")
	{
		// The audio 'lobbyBGM' should be replayed over and over.
		temp_audio.addEventListener('ended', function ()
		{
			setTimeout(function () { audio.play(); }, 500);
		}, false);
		audio = temp_audio;
	}
	else if (isBackground)
		audio = temp_audio;

	// Play audio.
	temp_audio.play();
}

function processANIMATION(turnSpeed, word)
{
	var ani;
	var message = "";
	var i = 0;
	var astime, ktime;
	var tspeed = parseFloat(turnSpeed, 10);
	switch (tspeed)
	{
		case 2.1: ktime = 0.23; break;
		case 3.2: ktime = 0.36; break;
		case 5.1: ktime = 0.46; break;
		case 6.2: ktime = 0.57; break;
		case 8.0: ktime = 0.70; break;
		default : ktime = 0.23; break;
	}
	astime = (ktime * 1000 / word.length) + 10;//2.5s = 2500 = 2.5 * 10^3
	ani = setInterval(function()
	{
		message += word[i];
		showWord(message);
		processBGM("As", tspeed*10, false);
		if (++i == word.length)
		{
			processBGM("K", tspeed*10, false);
			clearInterval(ani);
		}
	}, astime);
}

// Show the word with responsive font-size.
function showWord(word)
{
	var fontsize;

	     if (word.length <  6) fontsize = "2.50rem";
	else if (word.length < 12) fontsize = "2.25rem";
	else if (word.length < 18) fontsize = "2.00rem";
	else if (word.length < 24) fontsize = "1.75rem";
	else                       fontsize = "1.50rem";

	$("#wordArea").css("font-size", fontsize).text(word);
}

function xssFilter(string)
{
	if (string == null || string == undefined) return "";
	return string
		.replace(/  /g, "&nbsp;&nbsp;")
		.replace(/</g, "&lt;")
		.replace(/>/g, "&gt;")
		.replace(/\"/g, "&quot;")
		.replace(/\'/g, "&#39");
}

// This function is for test. You can call it only on the console, which is in the developer mode of your web browser.
function TIMETEST()
{
	sendMessage("TIMETEST", null, null, null);
	responseTime = (new Date()).getTime();
}