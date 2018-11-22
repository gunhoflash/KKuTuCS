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
			showRoundTimer(60);
			$("#chatArea").empty();
			alert("Everyone is Ready. Game is just begun!");
			break;

		case "CORRECT":
			clearInterval(turnInterval);
			break;

		case "TURNSTART":
			showTurnTimer(parameter1);
			alert("It's your turn.");
			break;

		case "QUITTED":
			// TODO: Edit here.
			processSEND(parameter1 + " quitted");
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
	 * (roomString): roomname`isPlaying`now/max`needPassword
	 * ex) Come on!`0`2/4`0
	 * 
	 * roomlistString = (roomString)``(roomString)``(roomString) ...
	 */

	// No rooms.
	if (roomlistString.length == 0) return;

	var i, room, roomlist = roomlistString.split('``');
	for (i = 0; i < roomlist.length; i++)
	{
		room = roomlist[0].split('`');

		// Handle wrong string.
		if (room.length < 4) continue;

		console.log(
			"Room name: " + room[0] + "\n" +
			"State: " + (room[1] == '0' ? "Ready" : "Playing") + "\n" +
			"Number of users: " + room[2] + "\n" +
			"Need password: " + (room[3] == '0' ? "No" : "Yes")
			);
	}

	// TODO: Show the list of all rooms.
}