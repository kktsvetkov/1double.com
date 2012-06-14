<?php /**/ ?><?
//
// Collect Google referers
//
$log = "logs/log.GOOGLE";

$u = $HTTP_SERVER_VARS[HTTP_REFERER];
$U = parse_url($u);
$q = $U[query];

if (strstr($U[host], 'google.com')) {
	parse_str($q, $Q);
	$s = stripSlashes($Q[q]);

	$log = "$log.".strToUpper($Q[ie]).".TXT";

	if ($fp = @fopen($log, 'a+')) {
		@fwrite($fp, "$s\n");
		fclose($fp);
		}
	}
?>
