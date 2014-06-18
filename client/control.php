<?php
//ini_set('display_errors','On'); ini_set('error_reporting','E_ALL'); error_reporting(E_ALL);
include_once("functions.php");
$link_id = mysql_connect($host, $username, $password);
mysql_select_db($dbase,$link_id);
mysql_query("set names 'utf8'");
$types = array("Порт: дает возможность строить корабли", "Крепость: усложняет захват острова (необходимо набрать 90 очков влияния, вместо 70)");
$build_res = array(200, 1000);
$navy_cost = 1;
if ($_POST['obj_isl'] > -1)
{
	$isl = mysql_fetch_array(mysql_query("SELECT * FROM `islands` WHERE `id` = {$_POST['obj_isl']}"));
	if ($isl['owner'] != $_SESSION['id'])
		die("<h4>Это не ваш остров</h4>");
	if ($_POST['action'] == 'build')
	{
		if ($isl['res'] >= $build_res[$_POST['type']])
		{
			alert("Строим {$types[$_POST['type']]}",'info');
			$isl['buildings'] = $isl['buildings'] | pow(2,$_POST['type']);
			$isl['res'] -= $build_res[$_POST['type']];
			mysql_query("UPDATE `islands` SET `buildings` = {$isl['buildings']}, `res` = {$isl['res']} WHERE `id` = {$_POST['obj_isl']}");
		}
		else
		{
			alert("Недостаточно ресурсов для постройки {$types[$_POST['type']]}",'danger');
		}
	}
	elseif ($_POST['action'] == 'destroy')
	{
		if ($isl['buildings'] & pow(2,$_POST['type']))
		{
			alert("Уничтожаем {$types[$_POST['type']]}",'info');
			$isl['buildings'] = $isl['buildings'] & ~pow(2,$_POST['type']);
			$isl['res'] += $build_res[$_POST['type']] / 2;
			mysql_query("UPDATE `islands` SET `buildings` = {$isl['buildings']}, `res` = {$isl['res']} WHERE `id` = {$_POST['obj_isl']}");
		}
	}
	elseif ($_POST['action'] == 'build_navy')
	{
		$c = $_POST['navy_nbr'];
		if (is_numeric($c) and $c > 0 and $c * $navy_cost <= $isl['res'])
		{
			alert("Построено $c кораблей", 'success');
			$isl['res'] -= $c * $navy_cost;
			mysql_query("UPDATE `islands` SET `res` = {$isl['res']} WHERE `id` = {$_POST['obj_isl']}");
			$res = mysql_query("SELECT `id`, `strength` FROM `navys` WHERE `owner` = {$isl['owner']} AND `island` = {$isl['id']}");
			if (mysql_num_rows($res) < 1)
			{
				mysql_query("INSERT INTO `navys` (`owner`, `island`, `strength`) VALUES ({$isl['owner']}, {$isl['id']}, $c)");
			}
			else
			{
				$id = mysql_result($res, 0, 'id');
				$c += mysql_result($res, 0, 'strength');
				mysql_query("UPDATE `navys` SET `strength` = $c WHERE `id` = $id");
			}
		}
		else
		{
			alert("Неправильное число кораблей. Возможно недостаточно ресурсов для постройки",'danger');
		}
	}
	elseif ($_POST['action'] == 'mine')
	{
		$c = $_POST['mine_nbr'];
		alert("Добыто $c ресурсов", 'success');
		if (is_numeric($c) and $c > 0 and $c <= $isl['max_mine'] and $c <= $isl['res_mine'] and !$isl['last_mine'])
		{
			$isl['res_mine'] -= $c;
			$isl['res'] += $c;
			$isl['last_mine'] = 1;
			mysql_query("UPDATE `islands` SET `res` = {$isl['res']}, `res_mine` = {$isl['res_mine']} , `last_mine`=1 WHERE `id` = {$_POST['obj_isl']}");
		}
		else
		{
			alert("Невозможно добыть такое число ресурсов",'danger');
		}
	}
	$p = 1;
	echo "<h1>Остров {$isl['name']}</h1>
	<form action='index.php?page=map' method='POST' class='form-inline'>
	<input type='hidden' name='obj_isl' value='{$_POST['obj_isl']}'>
	<input type='hidden' name='type'>
	<input type='hidden' name='action'>";
	echo "<div class='panel panel-default'>
	  <div class='panel-heading'>Ресурсы</div>
	  <div class='panel-body'>";
	echo "Ресурсов в хранилище {$isl['res']}<br>";
	echo "Ресурсов доступно для добычи {$isl['res_mine']}<br>";
	echo "Коэффициент изменения ресурсов для добычи в неделю {$isl['res_coef']}<br>";
	echo "Максимальное колличество ресурсов для добычи {$isl['res_max']}<br>";
	echo "Максимальная скорость добычи в неделю {$isl['max_mine']}<br>";
	if ($isl['last_mine'])
	{
		echo "Вы уже добывали ресурсы на этой неделе";
	}
	else
	{
		echo "<div class='form-group'><input class='form-control col-md-2' type='text' name='mine_nbr'></div>";
		echo "<button class='btn btn-primary' onclick='this.form.elements[2].value = \"mine\"; this.form.submit()' placeholder='Кол-во ресурсов'>Добыть</button>";
	}
	echo "</div></div>";
	echo "<div class='panel panel-default'>
	  <div class='panel-heading'>Постройки</div>
	  <div class='panel-body'><div class='list-group'>
	  ";
	for ($i = 0; $i < count($types); $i++)
	{
		$c = $build_res[$i];
		echo "<div class='list-group-item'>{$types[$i]} - ";
		if ($isl['buildings'] & $p)
		{
			$c /= 2;
			echo "<button class='btn btn-danger btn-xs' onclick='this.form.elements[2].value = \"destroy\"; this.form.elements[1].value = $i; this.form.submit()'>Разрушить (+$c ресурсов)</button>";
		}
		else
		{
			echo "<button class='btn btn-success btn-xs' onclick='this.form.elements[2].value = \"build\"; this.form.elements[1].value = $i; this.form.submit()'>Построить ($c ресурсов)</button>";
		}
		echo "</div>";
		$p *= 2;
	}
	echo "</div></div></div>";
	if ($isl['buildings']&1)
	{
		echo "<div class='panel panel-default'>
		<div class='panel-heading'>Порт</div>
		<div class='panel-body'>";
		echo "<div class='form-group'><input class='form-control col-md-2' type='text' name='navy_nbr' placeholder='Число кораблей'></div>";
		echo "<button class='btn btn-primary' onclick='this.form.elements[2].value = \"build_navy\"; this.form.submit()'>Построить корабли</button></div></div>";
	}
	echo "</form>";
}
else if ($_POST['obj_navy'] > -1)
{
	$navy = mysql_fetch_array(mysql_query("SELECT * FROM `navys` WHERE `id` = {$_POST['obj_navy']}"));
	if ($navy['owner'] != $_SESSION['id'])
		die("<h4>Это не ваш флот</h4>");
	$isl = mysql_fetch_array(mysql_query("SELECT * FROM `islands` WHERE `id` = {$navy['island']}"));
	if ($_POST['action'] == 'load')
	{
		$c = $_POST['res'];
		if ($navy['owner'] == $isl['owner'] and is_numeric($c) and $c != 0 and $c >= -$navy['res'] and $c <= $isl['res'])
		{
			$isl['res'] -= $c;
			$navy['res'] += $c;
			$c = abs($c);
			alert(($c > 0 ? 'Погружено' : 'Разгружено')." $c единиц ресурсов", 'info');
			mysql_query("UPDATE `islands` SET `res` = {$isl['res']} WHERE `id` = {$isl['id']}");
			mysql_query("UPDATE `navys` SET `res` = {$navy['res']} WHERE `id` = {$navy['id']}");
		}
		else
		{
			alert("Невозможно погрузить такое число ресурсов", 'danger');
		}
	}
	elseif ($_POST['action'] == 'move')
	{
		$c = $_POST['navy_nbr'];
		$r = $_POST['res_trans'];
		$t = $_POST['target'];
		$all = $_POST['all'] == 1;
		if ($all)
		{
			$c = $navy['strength'];
			$r = $navy['res'];
		}
		if ($r == '')
			$r = 0;
		if (strtotime($navy['last_move']) < strtotime("-24 hours") and strpos($isl['adjacent'], $t) !== false and ($all or (is_numeric($c) and $c > 0 and $c < $navy['strength'] and is_numeric($r) and $r >= 0 and $r <= $navy['res'])))
		{
			alert("Перемещение $c кораблей совершено", 'success');
			$navy['strength'] -= $c;
			$navy['res'] -= $r;
			mysql_query("UPDATE `navys` SET `strength` = {$navy['strength']}, `res` = {$navy['res']} WHERE `id` = {$navy['id']}");
			$res = mysql_query("SELECT `id`, `strength`, `res` FROM `navys` WHERE `owner` = {$navy['owner']} AND `island` = $t");
			if (mysql_num_rows($res) < 1)
			{
				$res = mysql_query("INSERT INTO `navys` (`owner`, `island`, `strength`, `res`) VALUES ({$navy['owner']}, $t, $c, $r)");
				$id = mysql_insert_id($res);
			}
			else
			{
				$id = mysql_result($res, 0, 'id');
				$c += mysql_result($res, 0, 'strength');
				$r += mysql_result($res, 0, 'res');
				mysql_query("UPDATE `navys` SET `strength` = $c, `res` = $r, `last_move` = NOW() WHERE `id` = $id");
			}
			if ($navy['strength'] <= 0)
			{
				$_POST['obj_navy'] = $id;
				$navy = mysql_fetch_array(mysql_query("SELECT * FROM `navys` WHERE `id` = {$_POST['obj_navy']}"));
				$isl = mysql_fetch_array(mysql_query("SELECT * FROM `islands` WHERE `id` = {$navy['island']}"));
			}
		}
		else
		{
			alert("Желаемое перемещение невозможно: недоступный остров, либо неверное число кораблей", 'danger');
		}
	}
	elseif ($_POST['action'] == 'attack')
	{
		$res = mysql_query("INSERT INTO `games` (`first`, `second`, `type`, `island`) VALUES ({$_SESSION['id']}, {$_POST['type']}, 2, {$navy['island']})");
		alert("Вы атаковали флот. <a href=\"game.php?id=".mysql_insert_id($res)."\" class=\"btn btn-success\">В игру</a>", 'info');
		mysql_query("UPDATE `users` SET `isplaying` = 1 WHERE `id` IN ({$_SESSION['id']}, {$_POST['type']})");
	}
	echo "<h1>Ваш флот на острове {$isl['name']}</h1>
	<form action='index.php?page=map' method='POST' class='form-inline'>
	<input type='hidden' name='obj_navy' value='{$_POST['obj_navy']}'>
	<input type='hidden' name='type'>
	<input type='hidden' name='action'>";
	echo "<div class='panel panel-default'>
	  <div class='panel-heading'>Влияние</div>
	  <div class='panel-body'>";
	$res = mysql_query("SELECT navys.*, users.login, (users.isplaying or users.online < SUBTIME(NOW(), '0 0:5:0')) as cannotplay FROM `navys` LEFT JOIN `users` ON navys.owner=users.id WHERE navys.`island` = {$navy['island']}");
	$cs = array('', 'success', 'info', 'warning', 'danger');
	$toolt = array();
	$impact = array();
	echo "<ol>";
	while ($n = mysql_fetch_array($res))
	{
		$cur = "{$n['login']} - Сила: {$n['strength']}, Влияние: {$n['impact']}";
		$toolt[] = $cur;
		$impact[] = $n['impact'];
		if ($n['strength'] > 0)
		{
			$cannotplay = ($n['cannotplay'] or $n['owner'] == $navy['owner']);
			echo "<li>$cur".($cannotplay ? "" : " - <button class='btn btn-success btn-xs' onclick='this.form.elements[2].value = \"attack\"; this.form.elements[1].value = {$n['owner']}; this.form.submit()'>Атаковать</button>")."</li>";
		}
	}
	$c = 0;
	echo "</ol><div class='progress'>";
	for ($i = 0; $i < count($toolt); $i += 1)
	{
		if ($impact[$i] > 0)
		{
			echo "<div class='impact progress-bar progress-bar-{$cs[$c]}' title='{$toolt[$i]}' style='width: {$impact[$i]}%'></div>";
			$c = ($c + 1)%5;
		}
	}
	echo "
	<script>
	$('.impact').tooltip();
	</script>
	";
	echo "</div></div></div>";
	echo "<div class='panel panel-default'>
	  <div class='panel-heading'>Перемещение</div>
	  <div class='panel-body'>";
	if (strtotime($navy['last_move']) < strtotime("-24 hours"))
	{
		echo "<div class='checkbox'><label><input type='checkbox' name='all' value=1>весь флот</label></div>";
		echo "<div class='form-group'><input class='form-control' type='text' name='navy_nbr' placeholder='Число кораблей'></div>";
		echo "<div class='form-group'><input class='form-control' type='text' name='res_trans' placeholder='Кол-во ресурсов'></div>";
		echo "<div class='form-group'><select class='form-control' name='target'>";
		$ts = explode(" ", $isl['adjacent']);
		for ($i = 0; $i < count($ts); $i++)
		{
			$name = mysql_result(mysql_query("SELECT `name` FROM `islands` WHERE `id` = {$ts[$i]}"), 0, 'name');
			echo "<option value='{$ts[$i]}'>$name</option>";
		}
		echo "</select></div>";
		echo "<button class='btn btn-primary' onclick='this.form.elements[2].value = \"move\"; this.form.submit()'>Переместить</button>";
	}
	else
	{
		echo "До следующего перемещения осталось ".round((strtotime($navy['last_move']) - strtotime("-24 hours"))/3600, 1)." часов";
	}
	echo "</div></div>";
	if ($navy['owner'] == $isl['owner'])
	{
		echo "<div class='panel panel-default'>
		<div class='panel-heading'>Погрузка ресурсов</div>
		<div class='panel-body'>";
		echo "<div class='form-group'><input class='form-control' type='text' name='res' placeholder='отрицательное для разгрузки'></div>";
		echo "<button class='btn btn-primary' onclick='this.form.elements[2].value = \"load\"; this.form.submit()'>Погрузить/Разгрузить</button>";
		echo "</div></div>";
	}
	echo "</form>";
}
else
{
	echo "<h4>Выберите объект, которым вы хотите управлять, в списке справа</h4>";
}
?>
