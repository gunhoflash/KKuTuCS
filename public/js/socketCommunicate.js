var socketLink = "ws://"+window.location.hostname+":7002";
var socket;

var responseTime = 0;
var uriQueries = [];

var audio = document.createElement("audio");

function initializeSocketAndObject()
{
	// Initialize views and variable
	$("#btn_ready").removeClass("d-none");
	$("*[data-ismain]").attr("data-ismain", "true");
	showWord("");
	$("#chatArea").html("").trigger("create");
	processROOMLIST("");

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
	var datas = data.split("\n");
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

		case "DISCONNECTED":
			showChat(parameter1, "is disconnected.", true);
			break;

		case "CONNECTED":
			showChat(parameter1, "is connected.", true);
			break;

		case "ROOMLIST":
			processROOMLIST(parameter1);
			break;

		case "TIMETEST":
			console.log("response time: " + ((new Date()).getTime() - responseTime) + "ms");
			break;

		case "ERROR":
			alert("[Server Error] " + parameter1);
			break;

		case "CORRECT":
			removeInterval(0);
			removeInterval(1);
			showWord(parameter1);
			break;

		case "PLAYBGM":
			processBGM(parameter1, parameter2);
			break;

		case "GAMESTART": // (Syntax: GAMESTART)
			removeInterval(0);
			removeInterval(1);
			$("#btn_ready").addClass("d-none");
			break;

		case "ROUNDSTART":
			showRoundTimer(parameter1, parameter1);
			removeInterval(0);
			break;
		
		case "ROUNDOVER":
			removeInterval(0);
			break;

		case "TURNSTART": // (Syntax: TURNSTART turn_time round_time)
			audio.pause();
			showTurnTimer(parameter1);
			showRoundTimer(parameter2, null);
			processBGM("T", parameter1*10);
			break;

		case "QUITTED":
			showChat(parameter1, "quitted.", true);
			break;

		case "PLAYERLIST":
			processPLAYERLIST(parameter1);
			break;

		case "RESULT":
			$("#btn_ready").removeClass("d-none").attr("data-ready", "0");
			removeInterval(0);
			removeInterval(1);
			showChat(null, "Game over.", true);
			processPLAYERLIST(parameter1);
			processRESULT(parameter1);
			break;

		case "ANIMATION":
			processANIMATION(parameter1, parameter2);
			break;

		default:
			alert("[Unexpected Method Error] " + method);
			break;
	}
}

function showChat(nickname, message, isSystem)
{
	// Replace all space characters to &nbsp;
	nickname = (nickname == null) ? "" : nickname.replace(/ /g, "&nbsp;");
	message = (message == null) ? "" : message.replace(/ /g, "&nbsp;");

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
		case "OK":
			showChat(nickname, "<span class='text-primary'>" + message + "</span>", false);
			break;

		case "VALID":
		case "CHAIN":
			showChat(nickname, message, false);
			break;

		case "DB":
		case "USED":
			showChat(nickname, "<span class='text-danger'>" + message + "</span>", false);
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
	$("*[data-ismain]").attr("data-ismain", "false");
	$("#chatArea").html("").trigger("create");
	showChat(null, "Welcome to " + message + "!", true);
}

function processROOMLIST(roomlistString)
{
	var no_rooms = "<p class='py-3 text-center text-muted'>No rooms</p>";
	/**
	 * (roomString): index`roomname`isPlaying`now/max`needPassword
	 * ex) 133`Come on!`0`2/4`0
	 * 
	 * roomlistString = (roomString)``(roomString)``(roomString) ...
	 */

	// No rooms.
	
	if (roomlistString.length == 0)
		$("#roomlistArea").html(no_rooms).trigger("create");

	var i, str = "", room, roomlist = roomlistString.split('``');
	for (i = 0; i < roomlist.length; i++)
	{
		room = roomlist[i].split('`');

		// Handle wrong string.
		if (room.length < 6) continue;

		str +=
		"<div class='gameroom border shadow-sm px-3 py-2 mb-2' data-index="+room[0]+" data-pw="+room[5]+">"+
			"<span class='font-weight-bold'><span class='pr-1 text-primary'>#"+room[0]+"</span>"+room[1]+"</span>"+
			"<div class='d-flex small'>"+
				"<span class='text-muted'>"+(room[2] == 'en' ? "En" : "한")+"</span>"+
				(room[3] == '0' ? "<span class='text-success px-1'>Ready" : "<span class='text-warning px-1'>Playing")+"</span>"+
				"<span class='text-black'>"+room[4]+"</span>"+
				"<span class='text-muted ml-auto'>"+(room[5] == '0' ? "" : "PW")+"</span>"+
			"</div>"+
		"</div>";
	}

	// Show the roomlist.
	if (str.length)
		$("#roomlistArea").html(str).trigger("create");
	else
		$("#roomlistArea").html(no_rooms).trigger("create");
}

function processPLAYERLIST(playerlistString)
{
	//clients`scores`ready``clients`scores`ready``...``nowTurn;
	$("#roomlistArea").html("").trigger("create");

	var i, str = "", playerlist = playerlistString.split("``");
	var nowTurn = parseInt(playerlist.slice(-1), 10);

	for(i = 0; i < playerlist.length-1; i++)
	{
		player = playerlist[i].split("`");

		str +=
		"<div class='gameroom border shadow-sm px-3 py-2 mb-2"+(i == nowTurn ? " bg-teal" : "")+"'>"+
			"<h6>"+player[0]+"</h6>"+
			"<div class='d-flex'>"+
				(player[2] == '1' ? "<span class='text-success'>Ready" : "<span class='text-warning'>Not Ready")+"</span>"+
				"<span class='text-black px-1'>"+player[1]+"</span>"+
			"</div>"+
		"</div>";
	}
	
	$("#roomlistArea").html(str).trigger("create");
}

function processRESULT(playerlistString)
{
	//clients`scores`ready``clients`scores`ready``...``nowTurn;
	var i, str = "", playerlist = playerlistString.split("``");

	for(i = 0; i < playerlist.length-1; i++)
	{
		player = playerlist[i].split("`");

		str += player[0] + "'s score : " +player[1] + "<br>";
	}
	$('#round_timer').css("width", "0%");
	$('#turn_timer').css("width", "0%");
	$("#resultScreen").modal('show');
	$("#resultScreenBody").html(str).trigger("create");

}

function processBGM(BGMtitle, playSpeed)
{
	audio.pause();
	if (playSpeed != 0) BGMtitle += playSpeed;
	audio.src = "public/media/"+BGMtitle+".mp3";
	if (BGMtitle == "LobbyBGM")
	{
		audio.addEventListener('ended', function ()
		{
			setTimeout(function () { audio.play(); }, 500);
		}, false);
	}

	audio.play();
}

function processANIMATION(turnSpeed, word)
{
	var ani;
	var message = "";
	var i = 0;
	var astime, ktime;
	var tspeed = parseFloat(turnSpeed,10);
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
	ani = setInterval(function(){
		message += word.substr(i, 1);
		showWord(message);
		processBGM("As", tspeed*10);
		if(i==word.length) {
			setTimeout(function(){
				processBGM("K", tspeed*10);
			},10);
			clearInterval(ani);
		}
		i++;
	}, astime);
		// TODO: when doing usleep and timer < 0, don't end the game
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

function stringToHTML(string)
{
	if (string == null) return "";
	return string.replace(" ", "&nbsp;");
}

// This function is for test. You can call it only on the console, which is in the developer mode of your web browser.
function TIMETEST()
{
	sendMessage("TIMETEST", null, null, null);
	responseTime = (new Date()).getTime();
}