<?php
ini_set('display_errors','On'); ini_set('error_reporting','E_ALL'); error_reporting(E_ALL);
include("constants.php");
$link_id = mysql_connect($host, $username, $password);
mysql_select_db($dbase,$link_id);
mysql_query("set names 'utf8'");
$min = $_GET['min'];
$max = $_GET['max'];
for ($i = $min; $i <= $max; $i += 1)
{
	$sum = 400;
	$n = rand(0, $sum);
	$sum -= $n;
	$m = 1000+$n*10;
	$n = rand(0, $sum);
	$sum -= $n;
	$c = 1.3 + $n/200.0;
	$n = rand(0, $sum);
	$sum -= $n;
	$x = 50 + round($n/1.5);
	$n = $sum;
	$r = round($m*(0.2+$n/500));
	mysql_query("UPDATE `islands` SET `res_mine` = $r, `res_coef` = $c, `res_max` = $m, `max_mine` = $x WHERE `id` = $i");
}
echo "resources randomized";
?>