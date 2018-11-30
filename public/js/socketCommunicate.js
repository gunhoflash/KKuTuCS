var socketLink = "ws://"+window.location.hostname+":7002";
var socket;

var responseTime = 0;
var uriQueries = [];

var audio = document.createElement("audio");
audio.prop("volume", "0.5");

function initializeSocketAndObject()
{
	// Initialize views and variable
	$("*[data-ismain]").attr("data-ismain", "true");
	$("#chatArea").html("").trigger("create");
	processROOMLIST("");

	socket = new WebSocket(socketLink);
	$("#Roomname").text("(Connecting..)");

	// When the socket open
	socket.onopen = function(event)
	{
		console.log("Socket opened.", event);
		$("#Roomname").text("Main");
		
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
		initializeSocketAndObject();
		//alert("Socket closed.\nPlease refresh this page to reconnect.");
	};
}

function sendMessage(method, parameter1, parameter2)
{
	/*
	socket.readyState

	Constant    Value   Description
	CONNECTING  0       연결이 수립되지 않은 상태입니다.
	OPEN        1       연결이 수립되어 데이터가 오고갈 수 있는 상태입니다.
	CLOSING     2       연결이 닫히는 중 입니다.
	CLOSED      3       연결이 종료되었거나, 연결에 실패한 경우입니다.
	*/
	
	if (parameter1 == undefined) parameter1 = "";
	if (parameter2 == undefined) parameter2 = "";

	if (socket.readyState == socket.OPEN)
	{
		var str = "KKuTuCS\n" + method + "\n" + parameter1 + "\n" + parameter2;
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

	switch (method)
	{
		case "SEND": // (Syntax: SEND nickname message)
			processSEND(parameter1, parameter2);
			break;

		case "JOIN":
			// JOIN [1/0] [roonname/error_message]
			processJOIN(parameter1, parameter2);
			break;

		case "DISCONNECTED":
			processSEND(parameter1, "disconnected");
			break;

		case "CONNECTED":
			processSEND(parameter1, "connected");
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

		case "GAMESTART":
			// TODO: Edit here.
			audio.pause();
			$("#chatArea").empty();
			showTurnTimer(parameter1);
			showRoundTimer(parameter2);
			processBGM("T80");
			break;

		case "CORRECT":
			clearInterval(turnInterval);
			clearInterval(roundInterval);
			$("#wordArea").text(parameter1);
			break;

		case "PLAYBGM":
			processBGM(parameter1, parameter2);
			break;

		case "TURNSTART":
			showTurnTimer(parameter1);
			showRoundTimer(parameter2);
			break;

		case "QUITTED":
			// TODO: Edit here.
			processSEND(parameter1, "quitted");
			break;

		case "PLAYERLIST":
			processPLAYERLIST(parameter1);
			break;

		case "RESULT":
			processPLAYERLIST(parameter1);
			processRESULT(parameter1);
			break;

		default:
			alert("[Unexpected Method Error] " + method);
			break;
	}
}

function processSEND(nickname, message)
{
	/**
	 * TODO: Convert some characters.
	 * \n      => <br>
	 * (space) => &nbsp;
	 */
	nickname = (nickname == null || nickname.length == 0) ? "" : "<font color=DeepSkyBlue>" + nickname + "</font>&nbsp;:&nbsp;";

	$("#chatArea").append("<p class='mb-1'>" + nickname + message + "</p>");
	$("#wordchatArea").scrollTop($("#wordchatArea").prop("scrollHeight"));
}

function processJOIN(success, message)
{
	if (success != 1)
	{
		alert("Cannot join!\n" + message);
		return;
	}

	// Join the game
	$("#Roomname").text(message);
	$("*[data-ismain]").attr("data-ismain", "false");

	$("#chatArea").html("").trigger("create");
	processSEND(null, "Welcome to " + message + "!");
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
		if (room.length < 5) continue;
		
		str +=
		"<div class='gameroom border shadow-sm px-3 py-2 mb-2' data-index="+room[0]+" data-pw="+room[4]+">"+
			"<span class='font-weight-bold'><span class='pr-1 text-primary'>#"+room[0]+"</span>"+room[1]+"</span>"+
			"<div class='d-flex small'>"+
				(room[2] == '0' ? "<span class='text-success'>Ready" : "<span class='text-warning'>Playing")+"</span>"+
				"<span class='text-black px-1'>"+room[3]+"</span>"+
				"<span class='text-muted ml-auto'>"+(room[4] == '0' ? "" : "PW")+"</span>"+
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

		str +=
		player[0] + "'s score : " +player[1] + "<br>";
	}

	$("#resultScreen").modal('show');
	$("#resultScreenBody").html(str).trigger("create");

}

function processBGM(BGMtitle, playSpeed = 0)
{
	audio.pause();
	if(playSpeed!=0) BGMtitle += playSpeed;
	audio.src = "libs/media/"+BGMtitle+".mp3";
	if(BGMtitle == "LobbyBGM")
	{
	audio.addEventListener('ended', function () {
		setTimeout(function () { audio.play(); }, 500);
	}, false);       
	}

	audio.play();
}