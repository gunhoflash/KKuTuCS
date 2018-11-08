$(document).ready(function()
{
	$("#btn_test").on("click", function()
	{
		sendMessage("Button Test");
	});
	$("#btn_send").on("click", function()
	{
		sendMessage($("#wordInput").val());
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

function initializeSocket()
{
	socket = new WebSocket(socketLink);

	// When the socket open
	socket.onopen = function(event)
	{
		console.log("Socket opened.", event);
	};

	// When the socket receive a message
	socket.onmessage = function(event)
	{
		console.log("Socket received a message.", event.data);
		$("#chatArea").append("<p class='mb-1'>" + event.data + "</p>");
		$("#chatArea").scrollTop($("#chatArea").prop("scrollHeight"));
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

function sendMessage(str)
{
	/*
	socket.readyState

	Constant    Value   Description
	CONNECTING  0       연결이 수립되지 않은 상태입니다.
	OPEN        1       연결이 수립되어 데이터가 오고갈 수 있는 상태입니다.
	CLOSING     2       연결이 닫히는 중 입니다.
	CLOSED      3       연결이 종료되었거나, 연결에 실패한 경우입니다.
	*/
	if (socket.readyState == socket.OPEN)
	{
		str = "KKuTuCS\n" + str;
		socket.send(str);
	}
	else
		alert("Cannot send!\nsocket.readyState: " + socket.readyState);
}