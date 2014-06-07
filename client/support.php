<?php
if ($_GET['code'] <> "zekfor2967") die("404 - Not found on this server");
include("constants.php");
$link_id = mysql_connect($host, $username, $password);
mysql_select_db($dbase,$link_id);
mysql_query("set names 'utf8'");
$page = $_GET['page'];
echo "connect";
$id = $_GET['id'];
if ($page == "win" or $page == "draw")
{
$winner = ($page == "draw" ? -1 : $_GET['winner']-1);
$looser = ($winner == 0 ? 1 : 0);
$game = mysql_query("SELECT `first`,`second`,`island` FROM `games` WHERE `id` = '$id'");
$island = mysql_result($game,0,'island');
$u =  array(mysql_result($game,0,'first'), mysql_result($game,0,'second'));
$rate = array();
for ($i = 0; $i < 2; $i++)
{
	$rate[] = mysql_result(mysql_query("SELECT `rate` FROM `users` WHERE `id` = {$u[$i]}"),0,'rate');
}
if (!is_null($island))
{
	$navy = array();
	for ($i = 0; $i < 2; $i++)
	{
		$navy[] = mysql_fetch_array(mysql_query("SELECT `id`, `strength`, `impact` FROM `navys` WHERE `island` = $island AND `owner` = {$u[$i]}"));
	}
	$sum_st = $navy[0]['strength'] + $navy[1]['strength'];
	$all_st = mysql_result(mysql_query("SELECT SUM(strength) AS sum FROM `navys` WHERE `island` = $island"), 0, 'sum');
}
$d = abs($rate1-$rate2);
$next = array(30,100,170,250,320,390,460,530,610,680,760,830,910,980,1060,1130,1210,1290,1370,1450,1530,1620,1700,1790,1880,1970,2060,2150,2250,2350,2450,2560,2670,2780,2900,3020,3150,3280,3440,3570,3740,3910,4110,4320,4560,4840,5170,5590,6190,7350);
$i = 0;
while ($i < count($next) && $d > $next[$i])
{ 
	$i++;
}

if ($rate[0] > $rate[1])
{
	$expected = array(50 + $i, 50 - $i);
}
else
{
	$expected = array(50 + $i, 50 - $i);
}
if ($winner != -1)
{
	$rate[$winner] += 100 - $expected[$winner];
	$rate[$looser] += 0 - $expected[$looser];
}
else
{
	$rate[0] += 50 - $expected[0];
	$rate[1] += 50 - $expected[1];
}
if (!is_null($island))
{
	if ($winner != -1)
	{
		$dimp = round(40*$navy[$winner]['strength']/$all_st);
		$dimp = min($dimp, 100-$navy[$winner]['impact'], $navy[$looser]['impact']);
		$navy[$winner]['impact'] += $dimp;
		$navy[$looser]['impact'] -= $dimp;
		$navy[$winner]['strength'] -= round(0.05*$sum_st);
		$navy[$looser]['strength'] -= round(0.3*$sum_st);
	}
	else
	{
		$navy[0]['strength'] -= round(0.15*$sum_st);
		$navy[1]['strength'] -= round(0.15*$sum_st);
	}
	$navy[$winner]['strength'] = max(0, $navy[$winner]['strength']);
	$navy[$looser]['strength'] = max(0, $navy[$looser]['strength']);
}
if ($winner != -1 and !is_null($island) and $navy[$winner]['impact'] >= 70)
{
	mysql_query("UPDATE `islands` SET `owner` = {$u[$winner]} WHERE `id` = $island");
}
$winner++;
mysql_query("UPDATE `games` SET `type` = 4,`winner` = $winner WHERE `id` = $id");
for ($i = 0; $i < 2; $i++)
{
	mysql_query("UPDATE `users` SET `rate`={$rate[$i]} ,`isplaying` = 0 WHERE `id`={$u[$i]}");
	if (!is_null($island))
		mysql_query("UPDATE `navys` SET `strength`={$navy[$i]['strength']}, `impact`={$navy[$i]['impact']}  WHERE `id`={$navy[$i]['id']}");
}
}
else if ($page == "start")
{
    mysql_query("UPDATE `games` SET `type` = 3 WHERE `id` = '$id'");
}
else if ($page == "history")
{
    foreach ($_FILES as $key => $file)
    {
        move_uploaded_file($file['tmp_name'], $history_dir."/".$file['name']);
    }
}
echo "OK";
?>
