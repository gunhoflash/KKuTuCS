alert("Hello, JS!");
$(document).ready(function()
{
	$("#A").on("click", function()
	{
		buttonTest("A");
	});
	$("#B").on("click", function()
	{
		buttonTest("B");
	});
	$("#C").on("click", function()
	{
		buttonTest("C");
	});
	alert("Hello, jQuery!");
});

/*function buttonTest(str) {
	$.ajax({
		url: "../client.php",
		data: { str: str },
		type: "POST",
		success: function (result) {
			alert(result);
		}
	 });
}*/

function buttonTest(str)
{
	if (socket.readyState == socket.OPEN)
		socket.send(str);
	else
		alert("Cannot send!\nsocket.readyState: " + socket.readyState);
	/*
	socket.readyState

	Constant    Value   Description
	CONNECTING  0       연결이 수립되지 않은 상태입니다.
	OPEN        1       연결이 수립되어 데이터가 오고갈 수 있는 상태입니다.
	CLOSING     2       연결이 닫히는 중 입니다.
	CLOSED      3       연결이 종료되었거나, 연결에 실패한 경우입니다.
	*/
}


/**
 * Code by: Nabi KAZ <www.nabi.ir>
 */

var socket = new WebSocket('ws://121.130.151.64:7002');

// Open the socket
socket.onopen = function(event) {
	var msg = "KKuTuCS GF!";

	// Send an initial message
	socket.send(msg);

	// Listen for messages
	socket.onmessage = function(event) {
		console.log('< ' + event.data);
	};

	// Listen for socket closes
	socket.onclose = function(event) {
		console.log('Client notified socket has closed', event);
	};

	// To close the socket,
	//socket.close()
};