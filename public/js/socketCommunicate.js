var socketLink = "ws://"+window.location.hostname+":7002";
var socket;

var responseTime = 0;

function initializeSocket()
{
	socket = new WebSocket(socketLink);
	$("#Roomname").text("(Now connecting...)");
	// When the socket open
	socket.onopen = function(event)
	{
		console.log("Socket opened.", event);
		$("#Roomname").text("Main room");
		sendMessage("ROOMLIST", "");
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
		alert("Socket closed.\nPlease refresh this page to reconnect.");
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
		case "SEND":
			processSEND(parameter1);
			break;

		case "JOIN":
			// JOIN [1/0] [roonname/error_message]
			if (parameter1 == 1)
				$("#Roomname").text(parameter2);
			else
				alert("Cannot join!\n" + parameter2);
			// TODO: Do something according to the result of the JOIN request.
			break;

		case "DISCONNECTED":
			// TODO: Show that someone is disconnected with this room.
			processSEND(parameter1 + " disconnected");
			break;

		case "CONNECTED":
			// TODO: Show that someone is connected with this room.
			processSEND(parameter1 + " connected");
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
			$("#chatArea").empty();
			showTurnTimer(parameter1);
			showRoundTimer(parameter2);
			alert("Everyone is Ready. Game is just begun!");
			break;

		case "CORRECT":
			clearInterval(turnInterval);
			clearInterval(roundInterval);
			$("#wordArea").text(parameter1);
			break;

		case "TURNSTART":
			showTurnTimer(parameter1);
			showRoundTimer(parameter2);
			break;

		case "QUITTED":
			// TODO: Edit here.
			processSEND(parameter1 + " quitted");
			break;

		case "PLAYERLIST":
			ProcessPLAYERLIST(parameter1);
			break;

		default:
			alert("[Unexpected Method Error] " + method);
			break;
	}
}

function processSEND(message)
{
	/**
	 * TODO: Convert some characters.
	 * \n      => <br>
	 * (space) => &nbsp;
	 */
	$("#chatArea").append("<p class='mb-1'>" + message + "</p>");
	$("#chatArea").scrollTop($("#chatArea").prop("scrollHeight"));
}

function processROOMLIST(roomlistString)
{
	/**
	 * (roomString): index`roomname`isPlaying`now/max`needPassword
	 * ex) 133`Come on!`0`2/4`0
	 * 
	 * roomlistString = (roomString)``(roomString)``(roomString) ...
	 */

	// Initialize roomlist area.
	$("#roomlistArea").html("").trigger("create");

	// No rooms.
	if (roomlistString.length == 0) return;

	var i, str = "", room, roomlist = roomlistString.split('``');
	for (i = 0; i < roomlist.length; i++)
	{
		room = roomlist[i].split('`');

		// Handle wrong string.
		if (room.length < 5) continue;
		
		str +=
		"<div class='gameroom border shadow-sm px-3 py-2 mb-2' data-index="+room[0]+">"+
			"<h6>"+room[1]+"</h6>"+
			"<div class='d-flex'>"+
				(room[2] == '0' ? "<span class='text-success'>Ready" : "<span class='text-warning'>Playing")+"</span>"+
				"<span class='text-black px-1'>"+room[3]+"</span>"+
				"<span class='text-muted ml-auto'>"+(room[4] == '0' ? "" : "PW")+"</span>"+
			"</div>"+
		"</div>";
	}

	// Show the roomlist.
	$("#roomlistArea").html(str).trigger("create");
}

function ProcessPLAYERLIST(playerlistString)
{
	//clients`scores`ready``clients`scores`ready``...``nowTurn;
	$("#roomlistArea").html("").trigger("create");

	var i, str, playerlist = playerlistString.split("``");
	var nowTurn = parseInt(playerlist.slice(-1), 10);

	for(i = 0; i < playerlist.length-1; i++)
	{
		player = playerlist[i].split("`");

		if(i== nowTurn)
		str +=
		"<div class='gameroom border bg-primary shadow-sm px-3 py-2 mb-2'>";
		else
		str +=
		"<div class='gameroom border shadow-sm px-3 py-2 mb-2'>";
		str +=
			"<h6>"+player[0]+"</h6>"+
			"<div class='d-flex'>"+
				(player[2] == '1' ? "<span class='text-success'>Ready" : "<span class='text-warning'>Not Ready")+"</span>"+
				"<span class='text-black px-1'>"+player[1]+"</span>";
		if(i == nowTurn) 
		str += 
				"<span class='text-black px-1'> Now Turn </span>";
		else
		str +=
				"<span class='text-black px-1'> </span>";
		str +=
			"</div>"+
		"</div>";
	}
	
	$("#roomlistArea").html(str).trigger("create");
}