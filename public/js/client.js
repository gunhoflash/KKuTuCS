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
	// Make a new game room.
	$("#make").on("submit", function(e)
	{
		e.preventDefault();
		sendMessage("MAKE",
			$(this).find("input[name=roomname]").val(),
			$(this).find("input[name=password]").val(),
			$(this).find("input[name=mode]:checked").val()
		);
		$(this).find("input:not([type=radio])").val("");
		$('#createRoom').modal("hide");
	});

	// Quit the game and go back to the main.
	$("#btn_quit").on("click", function()
	{
		removeInterval(0);
		removeInterval(1);
		sendMessage("QUIT", null, null, null);
	});

	// Get ready to the game.
	$("#btn_ready").on("click", function()
	{
		var ready = $("#btn_ready").attr("data-ready");
		ready = (ready == "1") ? "0" : "1";
		$("#btn_ready").attr("data-ready", ready);
		
		sendMessage("READY", ready, null, null);
	});

	// Do chatting.
	$("#btn_send").on("click", function()
	{
		if ($("#wordInput").val().trim() != "")
			sendMessage("SEND", $("#wordInput").val().substring(0, 31), null, null);
		$("#wordInput").val("");
	});

	// Try to enter the game.
	$("#roomlistArea").on("click", ".gameroom", function()
	{
		if ($(this).attr("data-pw") == undefined) return;

		var pw = "";
		if ($(this).attr("data-pw") == "1")
			pw = prompt("비밀번호를 입력하세요","");
		sendMessage("JOIN", $(this).attr("data-index"), pw, null);
	});

	// Do chatting.
	$("#wordInput").keypress(function(event)
	{
		var keycode = (event.keyCode ? event.keyCode : event.which);
		if (keycode == '13')
		{
			event.preventDefault();
			$("#btn_send").click();
		}
	});

	$("#btn_expand").on("click", function()
	{
		var rotate;
		if ($("#chatArea").attr("data-expand") == "1")
		{
			rotate = "180";
			$("#chatArea").attr("data-expand", "0");
		}
		else
		{
			rotate = "0";
			$("#chatArea").attr("data-expand", "1");
		}
		$(this).find("svg").css("transform", "rotate("+rotate+"deg)");
	});
}

var ar_interval = [
	null, // round interval
	null  // turn interval
];

function removeInterval(index)
{
	if (ar_interval[index] != null)
	{
		clearInterval(ar_interval[index]);
		ar_interval[index] = null;
	}
}

function showRoundTimer(value, valueMax)
{
	value *= 10;

	if (valueMax != null)
		$("#round_timer").attr("aria-valuemax", valueMax);

	removeInterval(0);
	ar_interval[0] = setInterval(function()
	{
		// TODO: Check if the client quitted game already.

		if (--value < 0)
		{
			value = 0;
			removeInterval(0);
		}
		$('#round_timer').width(100*value/($("#round_timer").attr("aria-valuemax")*10)+'%');
	}, 100);
}
function showTurnTimer(valueMax)
{
	valueMax *= 10;
	var value = valueMax;

	removeInterval(1);
	ar_interval[1] = setInterval(function()
	{
		// TODO: Check if the client quitted game already.

		if (--value < 0)
		{
			value = 0;
			removeInterval(1);
		}
		$('#turn_timer').width(100*value/(valueMax)+'%');
	}, 100);
}