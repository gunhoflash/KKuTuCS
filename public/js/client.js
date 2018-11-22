$(document).ready(function()
{
	$.getScript("public/js/socketCommunicate.js")
	.done(function() {
		initialize();
	})
	.fail(function() {
		alert("Cannot load other js file!");
	});
});

function initialize()
{
	$("#btn_test").on("click", function()
	{
		sendMessage("TIMETEST", "", "");
		responseTime = (new Date()).getTime();
	});
	$("#btn_ready").on("click", function()
	{
		sendMessage("READY", "1");
	});
	$("#btn_join").on("click", function()
	{
		sendMessage("JOIN", "1", "");
	});
	$("#btn_send").on("click", function()
	{
		sendMessage("SEND", $("#wordInput").val());
		$("#wordInput").val("");
	});
	$("#wordInput").keypress(function(event)
	{
		var keycode = (event.keyCode ? event.keyCode : event.which);
		// TODO: Check the type of keycode: string or number.
		if (keycode == '13')
		{
			event.preventDefault();
			$("#btn_send").click();
		}
	});

	initializeSocket();
	initializeVariable();
}

var roundInterval;
var turnInterval;
var uriQueries = [];

function initializeVariable()
{
	var queries = decodeURI(window.location.search).substr(1).split("&");
	queries.forEach(str => {
		var ar = str.split("=");
		if (ar.length == 2)
			uriQueries[ar[0]] = ar[1];
	});
	$("#title").text("KKuTuCS(" + uriQueries["nickname"] + ")");
}

function showRoundTimer(duration) {
    
    var timer = duration;
    var seconds;
    
    roundInterval = setInterval(function(){
        seconds = parseInt(timer % 60, 10);
		
        seconds = seconds < 10 ? "0" + seconds : seconds;

        $('#btn_timer').text(seconds);

        if (--timer < 0) {
			timer = 0;
			clearInterval(roundInterval);
			clearInterval(turnInterval);
			sendMessage("ROUNDOVER","","");
        }
    }, 1000);
}
function showTurnTimer(duration) {
    
    var timer = duration;
    var seconds;
    
    turnInterval = setInterval(function(){
        seconds = parseInt(timer % 60, 10);
		
        seconds = seconds < 10 ? "0" + seconds : seconds;

        $('#btn_Ttimer').text(seconds);

        if (--timer < 0) {
			timer = 0;
			clearInterval(turnInterval);
			clearInterval(roundInterval);
			sendMessage("ROUNDOVER","","");
        }
    }, 1000);
}