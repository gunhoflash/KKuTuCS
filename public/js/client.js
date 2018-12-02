$(document).ready(function()
{
	$.getScript("public/js/socketCommunicate.js")
	.done(function() {
		initializeButton();
		initializeSocketAndObject();
	})
	.fail(function() {
		alert("Cannot load other js file!");
	});
});

function initializeButton()
{
	$("#make").on("submit", function(e)
	{
		e.preventDefault();
		sendMessage("MAKE",
			$(this).find("input[name=roomname]").val(),
			$(this).find("input[name=password]").val(),
			$(this).find("input[name=mode]:checked").val()
		);
		$(this).find("input").val("");
		$('#createRoom').modal("hide");
	});
	$("#btn_quit").on("click", function()
	{
		clearInterval(turnInterval);
		clearInterval(roundInterval);
		sendMessage("QUIT", null, null, null);
	});
	$("#btn_test").on("click", function()
	{
		sendMessage("TIMETEST", null, null, null);
		responseTime = (new Date()).getTime();
	});
	$("#btn_ready").on("click", function()
	{
		sendMessage("READY", "1", null, null);
	});
	$("#btn_send").on("click", function()
	{
		sendMessage("SEND", $("#wordInput").val(), null, null);
		$("#wordInput").val("");
	});
	$("#roomlistArea").on("click", ".gameroom", function()
	{
		var pw = "";
		if ($(this).data("pw") == "1")
			pw = prompt("비밀번호를 입력하세요","");
		sendMessage("JOIN", $(this).data("index"), pw, null);
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
}

var roundInterval;
var turnInterval;

function showRoundTimer(duration)
{
	var timer = duration * 10;
	var seconds, msec;

	roundInterval = setInterval(function()
	{
		// TODO: Check if the client quitted game already.
		msec = parseInt(timer%10, 10);
		seconds = parseInt(timer/10, 10);
		seconds = seconds < 10 ? "0" + seconds : seconds;

		$('#btn_timer').text(seconds+'.'+msec);

		if (--timer < 0)
		{
			timer = 0;
			clearInterval(roundInterval);
			clearInterval(turnInterval);
		}
	}, 100);
}
function showTurnTimer(duration)
{
	var timer = duration * 10;
	var seconds, msec;

	turnInterval = setInterval(function()
	{
		// TODO: Check if the client quitted game already.
		msec = parseInt(timer%10, 10);
		seconds = parseInt(timer/10, 10);
		seconds = seconds < 10 ? "0" + seconds : seconds;

		$('#btn_Ttimer').text(seconds+'.'+msec);

		if (--timer < 0)
		{
			timer = 0;
			clearInterval(turnInterval);
			clearInterval(roundInterval);
		}
	}, 100);
}
