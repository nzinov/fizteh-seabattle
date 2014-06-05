<?
session_start();
$admin = $_SESSION['login'] == "Коля";
$tegs = array("Никак не сломаешь мозги?", "Выучил все дебюты?", "С детства любишь море?", "Океан: всегда огромен", "Джва года мечтаешь грабить корованы?");
$rand = $tegs[rand(0,count($tegs)-1)];
if (isset($_GET['page']))
$page = $_GET['page'];
else  $page = "main";
include("functions.php");
$colors = array('default', 'success', 'info', 'warning', 'danger');
$s = array("Открыт","Некорректное сообщение","Идет обсуждение","Требует изучения","Принят","Идет работа","Закрыт");
$s_l = array("<span class=\"label label-danger\">Открыт</span>","<span class=\"label label-danger\">Некорректное сообщение</span>","<span class=\"label label-warning\">Идет обсуждение</span>","<span class=\"label label-warning\">Требует изучения</span>","<span class=\"label label-default\">Принят</span>","<span class=\"label label-info\">Идет работа</span>","<span class=\"label label-success\">Закрыт</span>");
$t = array("Ошибка","Изменение");
$p = array("Низкий","Средний","Высокий","Критический");
$p_l = array("<span class=\"label label-default\">Низкий</span>","<span class=\"label label-info\">Средний</span>","<span class=\"label label-warning\">Высокий</span>","<span class=\"label label-danger\">Критический</span>");
include("constants.php");
$link_id = mysql_connect($host, $username, $password);
mysql_select_db($dbase,$link_id);
mysql_query("set names 'utf8'");
if ($page == "addticket" and isset($_POST['theme']))
{
if (isset($_POST['id']))
{
$sql = "UPDATE `tickets` SET `theme` = '".htmlspecialchars($_POST['theme'])."', `type` = ".$_POST['type'].",`status` = ".$_POST['status'].",`priority` =  ".$_POST['priority']." WHERE `id`='".$_POST['id']."'";
mysql_query($sql);
$alert = "Изменения сохранены";
$alert_type = "info";
}
else
{
$sql="INSERT INTO `tickets` (`theme`, `type`,`userposted`) VALUES ('".htmlspecialchars($_POST['theme'])."', '".$_POST['type']."','".$_SESSION['id']."');";
mysql_query($sql);
$sql="INSERT INTO `discussion` (`user`, `message`, `ticket`) VALUES ('".$_SESSION['id']."', '".htmlspecialchars($_POST['message'])."', '".mysql_insert_id() ."');";
mysql_query($sql);
$alert = "Твое сообщение успешно добавлено. Спасибо.";
$alert_type = "success"; 
}
$page = "bugreport"; 
}
else if ($page == "logout")
{
mysql_query("UPDATE `users` SET `online`=SUBTIME(NOW(),'0 0:10:0') WHERE `id` = '".$_SESSION['id']."';");
$alert = "До свидания, ".$_SESSION['login']."!";
$alert_type = "info";
$page = $_GET['reverse'];
session_unset();
session_destroy();
}
else if ($page == "post" and isset($_POST['message']))
{
$sql="INSERT INTO `discussion` (`user`, `message`, `ticket`) VALUES ('".$_SESSION['id']."', '".nl2br(htmlspecialchars($_POST['message']))."', '".$_POST['id']."');";
mysql_query($sql);
$sql = "UPDATE `tickets` SET `status` = 0 WHERE `id` = '".$_POST['id']."'";
mysql_query($sql);
$page = "ticket";
$_GET['id'] = $_POST['id'];
}
else if ($page == "addnews" and isset($_POST['content']) and $admin)
{
$sql="INSERT INTO `news` (`title`, `content`, `color`) VALUES ('{$_POST['title']}', '".nl2br($_POST['content'])."', '".$_POST['color']."');";
mysql_query($sql);
$page = "main";
}
else if ($page == "login" and isset($_POST['login']))
{
$sql = "SELECT * FROM `users` WHERE `login` = '".$_POST['login']."';";
$res = mysql_query($sql);
if (mysql_num_rows($res)==0)
{
$alert = "Я тебя не знаю, ".$_POST['login'].". Хочешь я тебя запомню? <form action=\"index.php?page=register\" class=\"form-inline\" method=\"post\">".input_hidden("login",$_POST['login']).input_hidden("password",$_POST['password'])."<input type=\"submit\" class=\"btn btn-success\" value=\"Да, конечно\">";
$alert_type = "info";
}
else
{
$password=mysql_result($res,0,'password');
$rate=mysql_result($res,0,'rate');
$id=mysql_result($res,0,'id');
if ($password == md5($_POST['password']))
{
$_SESSION['login'] = $_POST['login'];
$_SESSION['id'] = $id;
$alert = "Приветствую тебя, ".$_SESSION['login'];
$alert_type = "success";
}
else
{
$alert = "Неверный пароль";
$alert_type = "error";
}
}
$page = "main";
}
else if ($page == "register" and isset($_POST['login']))
{
$sql = "SELECT * FROM `users` WHERE `login` = '".$_POST['login']."';";
$res = mysql_query($sql);
if (mysql_num_rows($res)==0)
{
$sql = "INSERT INTO `users` (`login`, `password`, `rate`) VALUES ('".$_POST['login']."','".md5($_POST['password'])."',22000);";
mysql_query($sql);
$res = mysql_query("SELECT `id` FROM  `users` WHERE `login` = '".$_POST['login']."'");
$alert_type = "success";
$alert = "Будем знакомы, ".$_POST['login'].". Теперь представься еще раз, для проверки.";
}
else
{
$alert_type = "warning";
$alert = "К сожалению, у меня уже есть знакомый, которого зовут точь в точь, как тебя.";
}
$page = "main";
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Морской бой по-физтеховски</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
	<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
    <!-- Le styles -->
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css" rel="stylesheet">
    <style type="text/css">
      body {
        padding-bottom: 40px;
      }
	  .isl_fixed {
		position: fixed;
		top: 50px;
		right: 50px;
	  }
    </style>
    <link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
    <link rel="shortcut icon" href="SBpic/favicon.png">
  </head>

  <body data-spy="scroll"> 

    <div class="navbar navbar-default">
          <a class="navbar-brand" href="index.php">Морской бой по-физтеховски</a>
            <ul class="nav navbar-nav">
              <li id="map"><a href="index.php?page=map">Карта</a></li>
              <li id="about"><a href="index.php?page=about">Об игре</a></li>
              <li id="rules"><a href="index.php?page=rules">Правила</a></li>
			  <li id="rate"><a href="index.php?page=rate">Рейтинг</a></li>
			  <li id="bugreport"><a href="index.php?page=bugreport">Сообщить об ошибке</a></li>
			  <li id="ai"><a class="indevelop" href="#" rel="tooltip" title="Разрабатывается">Бой программ</a></li>
            </ul>
<?
if (!isset($_SESSION['login']))
{
     echo "<button id=\"log\"class=\"btn btn-primary navbar-btn\">Представиться</button>";
	 echo "<script> $('#log').popover({html: true, placement: 'bottom', trigger: 'click', title: 'Представьтесь пожалуйста:', content: '<form class=\"form-horizontal\" style=\"margin: 5px;\" action=\"index.php?page=login\" method=\"POST\" ><div class=\"form-group\"><label class=\"control-label\" for=\"login\">Ваше имя</label><input class=\"col-md-2 form-control\" type=\"text\" id=\"login\" name=\"login\" placeholder=\"Имя\" value=\"\"></div><div class=\"form-group\"><label class=\"control-label\" for=\"password\">Пароль</label><input class=\"col-md-2 form-control\" type=\"password\" id=\"password\" name=\"password\" placeholder=\"Пароль\" value=\"\"></div><div class=\"form-group\"><button type=\"submit\" class=\"btn btn-primary btn-sm\">Готово</button><button type=\"button\" class=\"btn btn-sm btn-default\" href=\"index.php?page=bugreport\" onclick=\"$(\'#log\').popover(\'hide\');\">Отмена</button></div></form>'});</script>";
}
else
{
     $res = mysql_query("SELECT `id` FROM `games` WHERE (`first`='".$_SESSION['id']."' or `second`='".$_SESSION['id']."') AND `type` IN (2, 3) LIMIT 1;");
	 if (mysql_num_rows($res) > 0)
	 {
		echo "<a href=\"WSTest.php?id=".mysql_result($res,0,'id')."\" class=\"btn btn-success\">В игру</a>";
		$alert_type = 'warning';
		$alert = "У вас есть активная игра: нажмите на кнопку в верхнем меню, чтобы начать её.";
	 }
	 $res = mysql_query("SELECT * FROM `users` WHERE `id` = ".$_SESSION['id']);
	 $rate = mysql_result($res,0,'rate');
     echo "<button id=\"user\"class=\"btn btn-primary navbar-btn\"><span class=\"glyphicon glyphicon-user\"></span> ".$_SESSION['login']."</button>";
	 $content = '<p>Рейтинг: '.$rate.'</p><a class="btn btn-danger" href="index.php?page=logout&reverse=$page">Выход</a>';
	 echo "<script> $('#user').popover({placement: 'bottom', trigger: 'click', title: '".$_SESSION['login']."', content: '$content', html: true});</script>";
	 mysql_query("UPDATE `users` SET `online`=NOW() WHERE `id` = '".$_SESSION['id']."';");
}
$res = mysql_query("SELECT `login` FROM `users` WHERE  `online`>SUBTIME(NOW(),'0 0:10:0')");
$count = mysql_num_rows($res);
$content = "<ul>";
for ($i = 0; $i < $count; $i++)
{
$content .= "<li>".mysql_result($res,$i,'login')."</li>";
}
$content .= "</ul>";
echo "<button id=\"online\" class=\"btn btn-info navbar-btn\">$count онлайн</button>";
echo "<script> $('#online').popover({placement: 'bottom', trigger: 'hover', title: 'Пользователи онлайн', content: '$content', html: true});</script>";
?>
      </div>
    </div>
</div>
<div class="container">
<?
if (isset($alert))
{
    echo "<div class=\"alert alert-$alert_type\">";
	echo $alert;
	echo "<button class=\"close pull-right\" data-dismiss=\"alert\">&times;</button>";
    echo "</div>";
	echo "<script>$('.alert').alert()</script>";
}
	  if ($page == "main")
	  {
	  ?>
	<div class="jumbotron">
        <h1>Добро пожаловать</h1>
        <p><? echo $rand;?> - Морской бой по-физтеховски!</p>
        <p><a class="btn btn-primary btn-large" href="index.php?page=about">Узнать больше &raquo;</a></p>
     </div>
<div class="row">
<div class="col-md-12">
<?php
$res = mysql_query("SELECT * FROM `news` ORDER BY `added` DESC LIMIT 10");
while ($news = mysql_fetch_array($res))
{
echo "
<div class='panel panel-{$colors[$news['color']]}'>
  <div class='panel-heading'>{$news['title']}</div>
  <div class='panel-body'>{$news['content']}</div>
  <div class='panel-footer'>{$news['added']}</div>
</div>";
}
if ($admin)
{
echo form(array("action" => "addnews", "reverse" => "index.php"),
input_text("title","Заголовок"),
input_textarea("content","Текст новости"),
input_select("color", "Цвет", $colors));
}
echo "</div></div>";
}
else if ($page == "map")
{
?>
	  <div class="row">
	  <div class="col-md-9">
	  <?php
	  include("control.php");
	  ?>
	  </div>
	  <div class="col-md-3">
	  <form action="index.php?page=map" method="POST">
	  <div class="list-group">
    <div class="list-group-item">
    Ваши острова
    </div>
	<div class="list-group-item" >
	<select class="form-control" name="obj_isl" onchange="this.form.submit()">
	<option selected value='-1'></option>
	<?php
	$res = mysql_query("SELECT `id`, `name` FROM `islands` WHERE `owner` = ".$_SESSION['id']);
	while ($row =  mysql_fetch_array($res))
	{
    echo '<option value="'.$row['id'].'">'.$row['name'].'</option>';
	}
	?>
	</select>
	</div>
    <div class="list-group-item">
    Ваши флоты
    </div>
	<div class="list-group-item">
	<select class="form-control" name="obj_navy" onchange="this.form.submit()">
	<option selected value='-1'></option>
	<?php
	$res = mysql_query("SELECT navys.id, islands.name FROM `navys` INNER JOIN `islands` ON navys.island=islands.id WHERE navys.strength > 0 AND navys.`owner` = ".$_SESSION['id']);
	while ($row =  mysql_fetch_array($res))
	{
    echo "<option value='{$row['id']}'>{$row['name']}</option>";
	}
	?>
	</select>
    </div>
	</div>
	  </form>
	  </div>
	  </div>
	  	  <div class="row">
	  <div class="col-md-10">
	  <object data="map.svg" type="image/svg+xml" id="imap" width='100%'></object>
	  </div>
	  <div class="col-md-2" id="isldescr">
	  </div>
	  <script>
	  islands = {
	  <?php
	  $res = mysql_query("SELECT islands.*, users.login FROM `islands` LEFT JOIN users ON islands.owner=users.id");
	  while ($row =  mysql_fetch_array($res))
	  {
	  echo $row['id'].': ["'.$row['name'].'",'.$row['owner'].','.$row['buildings'].','.$row['res'].','.$row['res_mine'].','.$row['res_coef'].',';
	  echo $row['res_max'].','.$row['max_mine'].',"'.$row['login'].'"],';
	  }
	  ?>
	  }
	  function update()
	  {
	  $("#isldescr").html($(this).data('toolt'));
	  }
	  $(window).load(function () {
        var a = document.getElementById("imap");
        var svgDoc = a.contentDocument;
		for (id in islands)
		{
		$(svgDoc.getElementById("isl"+id)).mouseenter(update).data('toolt', '<h2>'+islands[id][0]+'</h2>Владелец: '+islands[id][8]+'<br>Запасы ресурсов: '+islands[id][3]+
		' ед<br>Природные ресурсы: '+islands[id][4]+' ед<br>Скорость роста: '+islands[id][5]+' раз/ нед<br>Ресурсоемкость: '+islands[id][6]+
		' ед<br>Скорость добычи: '+islands[id][7]+' ед/ нед<br>'+(islands[id][2]&1 ? 'Есть порт' : 'Нет порта'));
		}
	  });
	  $(document).scroll(function(){
		var elem = $('#isldescr');
		if (!elem.attr('data-top')) {
			if (elem.hasClass('isl_fixed'))
				return;
			var offset = elem.offset()
			elem.attr('data-top', offset.top);
		}
		if (elem.attr('data-top') <= $(this).scrollTop())
			elem.addClass('isl_fixed');
		else
			elem.removeClass('isl_fixed');
	  });
	  </script>
	  </div>
	  <?php
	  }
	  else if ($page == "about")
	  {
	  ?>
	  <ul id="subnav" class="nav nav-pills">
      <li class="active"><a href="#a-history">История игры</a></li>
      <li><a href="#a-descr">Описание</a></li>
      <li><a href="#a-rate">Рейтинг</a></li>
	  <li><a href="#a-tech">Технические подробности</a></li>
      </ul>
	  <div class="row">
	  <div class="col-md-12">
	  <div id="a-history">
	  <h2>История игры</h2>
	  <p>
	  Эта игра, скорее всего, появилась в конце 80-х годов прошлого столетия, но не в МФТИ, как можно было бы подумать по названию, а на физфаке МГУ. 
	  Название объясняется тривиально: на этом самом факультете бытует легенда, что Морской бой пришел с физтеха.)
	  </p>
	  <p>
	  Потом эта игра распространилась по другим факультетам и институтам. Четкого набора правил нет: наа каждом из них формировался свой тип правил. 
	  На физфаке они тоже со временем менялись, в основном добавлялись новые фишки.
	  </p>
	  <p>
	  К началу 90-х на многих факультетах существовали рейтинги, проводились турниры. Дальше следы теряются...
	  </p>
	  <p>
	  ***
	  </p>
	  <p>Когда я расказал правила своим родственникам, оказалось, что дядя играл в детстве в похожую игру, называвшуюся "Разведка боем". 
	  В ней вместо кораблей были солдаты и не было блоков и специальных фишек, вроде Торпед.</p>
	  <p>
	  Несмотря на такую известность, на широких просторах интернета удалось найти только одно внятное упоминание Морского боя по-физтеховски: 
	  Статью Ю.Шихова на сайте http://www.fieldofbattle.ru/.
	  Кроме приведенной выше истории игры и правил, в ней было написано, что студенты делали многочисленные безуспешные попытки 
	  воплотить эту игру на компьютере. Прочтя это я решил, что вот отличный вариант сделать то, чего еще никто до тебя не сделал, и принялся за работу.
	  <p/>
	  <p>
	  То, что вы видите на своем экране, уже четвертый и, я уверен, последний вариант программы. 
	  Первые были абсолютно нежизнеспособными и полностью выкидывались. 
	  Немного о том, как сделан этот морской бой можно прочести в разделе Технические подробности.
	  </p>
	  </div>
	  <div id="a-descr">
	  <!--<h2>Описание</h2>
	  <p>
	  
	  </p>-->
	  </div>
	  <div id="a-feild">
	  </div>
	  <div id="a-rate">
	  <h2>Рейтинг</h2>
	  Используется модифицированный рейтинг Эло.
	  Начальный рейтинг равен 22000.
	  Новый рейтинг высчитывается по формуле R' = R + (S - E), где R - старый рейтинг, S - фактически набранные очки (100 - победа, -100 - поражение), E - ожидаемые очки, взятые из стандартной таблицы.
	  </div>
	  <div id="a-tech">
	  <h2>Технические подробности</h2>
	  <p>
	  Как и любое похожее приложение, Морской бой по-физтеховски состоит из клиентской и серверной части. 
	  Клиентская часть запускается в вашем браузере, а серверная на платформе облачного хостинга <a href="openshift.com">openshift.com</a>.
	  </p>
	  <p>
	  Сервер разделен на два модуля (которые даже работают на разных машинах): часть, отвечающая за отображение сайта 
	  (использующая обычную связку apache + php + mysql) и часть, написанная на C#, обрабатывающая логику самой игры, 
	  которая содержит самописный сервер протокола WebSockets.
	  </p>
	  <p>
	  Клиентская часть написана на html + javascript. Для отображения сайта используется framework <a href="http://getbootstrap.com">Twitter Bootstrap</a>
	  </p>
	  <p>
	  Взаимодействия клиента и сервера во время игры происходит посредством обмена объектами JSON по протоколу WebSocket.
	  </p>
	  <p>
	  Для обработки JSON на сервере используется <a href="http://json.codeplex.com/">Json.NET</a>, а на клиенте - <a href="http://www.json.org/js.html">json.js</a>;
	  Наполнение DIY катриджа openshift, поддерживающего .NET часть сервера 
	  частично заимствовано <a href="https://github.com/wshearn/openshift-community-cartridge-mono">отсюда</a>
	  </p>
	  </div>
	  </div>
	  </div>
	  <?php
	  }
	  else if ($page == "rules")
	  {
		include("rules.php");
	  }
	  else if ($page == "rate")
	  {
$sql = "SELECT `login`,`rate` FROM `users` ORDER BY `rate` DESC";
$res=mysql_query($sql);
echo "<div class=\"row\"><div class=\"span12\"><table class=\"table table-striped\"><thead><tr><th>#</th><th>Имя</th><th>Рейтинг</th></tr></thead><tbody>";
$last = 0;
for ($n=0;$n<mysql_num_rows($res);$n++) {
	$login=mysql_result($res,$n,'login');
	$rate=mysql_result($res,$n,'rate');
	if ($rate <> $last)
	{
		$last = $rate;
		$count = $n+1;
	}
	if ($login == $_SESSION['login'])
		$output .= "<tr class='success'>";
	else
		$output .= "<tr>";
	$output =  $output."<td>$count</td><td>$login</td><td>$rate</td></tr>";
}
echo $output."</tbody></table></div></div>";
}
	  else if ($page == "bugreport")
	  {
echo "<a class=\"btn btn-primary\" href=\"index.php?page=report\">Сообщить об ошибке</a><br>";
echo "<div class=\"row\"><div class=\"span12\"><table class=\"table table-striped\"><thead><tr><th>#</th><th>Тип</th><th>Описание</th><th>Статус</th><th>Приоритет</th></tr></thead><tbody>";
$sql = "SELECT `type`,`theme`,`status`,`priority`,`id` FROM `tickets` ORDER BY `status`, `priority` DESC";
$res=mysql_query($sql);
for ($n=0;$n<mysql_num_rows($res);$n++) {
$theme=mysql_result($res,$n,'theme');
$type=mysql_result($res,$n,'type');
$status=mysql_result($res,$n,'status');
$priority=mysql_result($res,$n,'priority');
$id=mysql_result($res,$n,'id');
$output =  $output."<tr ><td>".($n+1)."</td><td>".$t[$type]."</td><td><a href=\"index.php?page=ticket&id=$id\">".$theme."</a></td><td>".$s_l[$status]."</td><td>".$p_l[$priority]."</td></tr>";
}
echo $output."</tbody></table></div></div>";
}
	  else if ($page == "ai")
	  {
	  ?>
	  <?php
	  }
	  else if ($page == "report")
	  {
echo form(array("action" => "addticket", "reverse" => "index.php?page=bugreport"),
input_text("theme","Краткое описание"),
input_textarea("message","Ваше сообщение"),
input_select("type","Тип",$t));
	  }
	  else if ($page == "ticket")
	  {
	  include("constants.php");
$link_id = mysql_connect($host, $username, $password);
mysql_select_db($dbase,$link_id);
mysql_query("set names 'utf8'");
$id = $_GET['id'];
$sql = "SELECT `type`,`theme`,`status`,`priority` FROM `tickets` WHERE `id`='$id';";
$res=mysql_query($sql);
$theme=mysql_result($res,0,'theme');
$type=mysql_result($res,0,'type');
$status=mysql_result($res,0,'status');
$priority=mysql_result($res,0,'priority');
echo form(array("action" => "addticket", "reverse" => "index.php?page=bugreport"),
input_hidden("id",$id),
input_text("theme","Тема сообщения",$theme,$admin),
input_select("type","Тип",$t,$type,$admin),
input_select("status","Статус",$s,$status,$admin),
input_select("priority","Приоритет",$p,$priority,$admin));
echo "<div class=\"row\"><div class=\"span12\"><table class=\"table table-striped\"><thead><tr><th>Пользователь</th><th>Сообщение</th><th>Опубликовано</th></tr></thead><tbody>";
$sql = "SELECT `users`.`login`,`discussion`.`message`,`discussion`.`published` FROM `discussion`,`users` WHERE `users`.`id` = `discussion`.`user` AND `discussion`.`ticket` = '$id' ORDER BY `discussion`.`published` DESC";
$res=mysql_query($sql);
for ($n=0;$n<mysql_num_rows($res);$n++) {
$user=mysql_result($res,$n,'login');
$message=mysql_result($res,$n,'message');
$published=mysql_result($res,$n,'published');
$output =  $output."<tr><td>$user</td><td>$message</td><td>$published</td></tr>";
}
echo $output."</tbody></table>";
echo form(array("action" => "post", "reverse" => "index.php?page=bugreport"),input_textarea("message","Ваше сообщение"),input_hidden("id",$id));
}	  ?>
      <footer>
        <p>&copy; Зинов Николай 2013</p>
      </footer>

    </div> <!-- /container -->
    <!-- Placed at the end of the document so the pages load faster -->
<script language="javascript" type="text/javascript">
$('.indevelop').tooltip({placement : 'bottom'})
$('#<? echo $page?>').addClass('active');
</script>
  </body>
</html>
