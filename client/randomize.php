<?php
include("constants.php");
$link_id = mysql_connect($host, $username, $password);
mysql_select_db($dbase,$link_id);
mysql_query("set names 'utf8'");
echo "graph sea {<br>";
$min = $_GET['min'];
$max = $_GET['max'];
$res = mysql_query("SELECT `id`, `name` FROM `islands` ORDER BY `id`");
$isls = array();
$adj = array();
while ($i = mysql_fetch_array($res))
{
$isls[$i['id']] = $i['name'];
$adj[$i['id']] = array();
}
for ($i = $min; $i <= $max; $i += 1)
{
$cur = ($i == $min ? $max : $i-1);
$adj[$i][] = $cur;
$adj[$cur][] = $i;
echo "{$isls[$i]} [id=isl$i];<br>";
echo "{$isls[$i]} -- {$isls[$cur]};<br>";
while (rand(0, 100) > 40)
{
$cur = rand($min, $max);
if (!in_array($cur, $adj[$i]))
{
echo "{$isls[$i]} -- {$isls[$cur]};<br>";
$adj[$i][] = $cur;
$adj[$cur][] = $i;
}
}
}
echo "}";
for ($i = $min; $i <= $max; $i += 1)
{
$adj[$i] = array_unique($adj[$i]);
$adjacent = implode(" ", $adj[$i]);
echo $adjacent."<br>";
mysql_query("UPDATE `islands` SET `adjacent` = '$adjacent' WHERE `id` = $i");
}
?>