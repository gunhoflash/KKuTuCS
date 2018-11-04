<?php
function sec_to_string($sec)
{
	return round($sec / 86400).'d '.
	(($sec / 3600 - 15) % 24).':'.
	($sec / 60 % 60).':'.
	($sec % 60);
}
?>