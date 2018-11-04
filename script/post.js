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

function buttonTest(str) {
	$.post(
		"../client.php",
		{ str: str },
		function (result) {
			alert(result);
		}
	);
}