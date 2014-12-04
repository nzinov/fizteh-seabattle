<?php
//ini_set('display_errors','On'); ini_set('error_reporting','E_ALL'); error_reporting(E_ALL);
include("constants.php");
$link_id = mysql_connect($host, $username, $password);
mysql_select_db($dbase,$link_id);
mysql_query("set names 'utf8'");
$res = mysql_query("SELECT * FROM `islands`");
while ($i = mysql_fetch_array($res))
{
	$n = min(round($i['res_mine']*$i['res_coef']), $i['res_max']);
	mysql_query("UPDATE `islands` SET `res_mine` = $n, `last_mine` = 0 WHERE `id` = {$i['id']}");
}