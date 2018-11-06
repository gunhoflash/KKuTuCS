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
	/*$.post(
		"../client.php",
		{ KKuTuCS_str: str },
		function (result) {
			alert(result);
		}
	);*/
	$.ajax({
		url: "../client.php",
		data: { KKuTuCS_str: str },
		type: "KKUTUCS",
		success: function (result) {
			alert(result);
		}
	 });
}