$(document).ready(function()
{
	$("#btn_test").on("click", function()
	{
		sendMessage("TIMETEST", "", "");
		responseTime = (new Date()).getTime();
	});
	$("#btn_send").on("click", function()
	{
		sendMessage("SEND", $("#wordInput").val());
		$("#wordInput").val("");
	});
	$("#wordInput").keypress(function(event)
	{
		var keycode = (event.keyCode ? event.keyCode : event.which);
		if (keycode == '13')
		{
			event.preventDefault();
			$("#btn_send").click();
		}
	});

	initializeSocket();
});

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
		//socket.close();
	};
}

// To close the socket,
//socket.close()

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
			break;

		case "CONNECTED":
			// TODO: Show that someone is connected with this room.
			break;

		case "ROOMLIST":
			// TODO: Show the list of all rooms.
			break;

		case "TIMETEST":
			console.log("response time: " + ((new Date()).getTime() - responseTime) + "ms");
			break;

		case "ERROR":
			alert("[Server Error] " + parameter1);
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