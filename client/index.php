<?
error_reporting(E_ALL);
session_start();
$state = $_SESSION['state'] = md5(rand());
$signed_in = isset($_SESSION['id']);
$admin = $signed_in && $_SESSION['id'] == "3";
$tags = array("Никак не сломаешь мозги?", "Выучил все дебюты?", "С детства любишь море?", "Океан: всегда огромен", "Джва года мечтаешь грабить корованы?");
$rand = $tags[rand(0,count($tags)-1)];
if (isset($_GET['page']))
    $page = $_GET['page'];
else  $page = "main";
include("functions.php");
$colors = array('default', 'success', 'info', 'warning', 'danger');
include("constants.php");
$link_id = mysql_connect($host, $username, $password);
mysql_select_db($dbase,$link_id);
mysql_query("set names 'utf8'");
if ($page == "addnews" and isset($_POST['content']) and $admin)
{
$sql="INSERT INTO `news` (`title`, `content`, `color`) VALUES ('{$_POST['title']}', '".nl2br($_POST['content'])."', '".$_POST['color']."');";
mysql_query($sql);
$page = "main";
//TODO: Tempotary
}
else if ($page == "addgame" and $admin)
{
	mysql_query("INSERT INTO `games` (`first`, `second`, `type`) VALUES ({$_REQUEST['first']}, {$_REQUEST['second']}, 2)");
	$page = "admin";
}
if ($page == "map")
    $page = "main";
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Морской бой по-физтеховски</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="google-signin-clientid" content="220267231332-46hns53sk33pkpbqc4ohd1iu913nreu0.apps.googleusercontent.com" />
    <meta name="google-signin-cookiepolicy" content="single_host_origin" />
    <meta name="google-signin-requestvisibleactions" content="https://schemas.google.com/AddActivity" />
    <meta name="google-signin-scope" content="https://www.googleapis.com/auth/plus.login" />
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
              <li id="map"><a class="indevelop" rel="tooltip" title="Временно недоступно" data_href="index.php?page=map">Карта</a></li>
              <li id="about"><a href="index.php?page=about">Об игре</a></li>
              <li id="rules"><a href="index.php?page=rules">Правила</a></li>
			  <li id="rate"><a href="index.php?page=rate">Рейтинг</a></li>
			  <li id="bugreport"><a target="blank" href="https://github.com/nzinov/fizteh-seabattle/issues?state=open">Сообщить об ошибке</a></li>
			  <li id="ai"><a class="indevelop" href="#" rel="tooltip" title="Разрабатывается">Бой программ</a></li>
            </ul>
<?
if ($signed_in)
{
    $id = $_SESSION['id'];
     $res = mysql_query("SELECT `id` FROM `games` WHERE (`first`='$id' or `second`='$id') AND `type` IN (2, 3) LIMIT 1;");
	 if (mysql_num_rows($res) > 0)
	 {
		echo "<a href=\"game.php/".mysql_result($res,0,'id')."\" class=\"btn btn-success navbar-btn\">В игру</a>";
		$alert_type = 'warning';
		$alert = "У вас есть активная игра: нажмите на кнопку в верхнем меню, чтобы начать её.";
	 }
	 mysql_query("UPDATE `users` SET `online`=NOW() WHERE `id` = '$id';");
     echo "<img class='pull-left img-circle' height=45 src='{$_SESSION['image_url']}' /><p class=navbar-text>{$_SESSION['name']}</p>";
}
else
{
?>
    <button id='signinButton' class='btn btn-primary navbar-btn'>Войти через Google</button>
    <script type="text/javascript" src="https://apis.google.com/js/client:plusone.js"></script>
    <script type="text/javascript">
       function onload()
       {
           var signinButton = document.getElementById('signinButton');
           signinButton.addEventListener('click', function() {
               gapi.auth.signIn({
                'callback': signinCallback,
                'accesstype': 'ofline'});
           });
       }
       function signinCallback(authResult)
       {
           if (authResult['status']['signed_in'])
           {
               $.post("/signin.php?act=connect&state=<?=$state?>&code="+authResult['code'], location.reload);
           }
       }
       window.addEventListener('load', onload);
    </script>
<?
}
$res = mysql_query("SELECT `name` FROM `users` WHERE  `online`>SUBTIME(NOW(),'0 0:10:0')");
$count = mysql_num_rows($res);
$content = "<ul>";
for ($i = 0; $i < $count; $i++)
{
$content .= "<li>".mysql_result($res,$i,'name')."</li>";
}
$content .= "</ul>";
echo "<button id=\"online\" class=\"btn btn-info navbar-btn pull-left\">$count онлайн</button>";
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
	  $res = mysql_query("SELECT islands.*, users.name FROM `islands` LEFT JOIN users ON islands.owner=users.id");
	  while ($row =  mysql_fetch_array($res))
	  {
	  echo $row['id'].': ["'.$row['name'].'",'.$row['owner'].','.$row['buildings'].','.$row['res'].','.$row['res_mine'].','.$row['res_coef'].',';
	  echo $row['res_max'].','.$row['max_mine'].',"'.$row['name'].'"],';
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
$sql = "SELECT `name`,`rate` FROM `users` ORDER BY `rate` DESC";
$res=mysql_query($sql);
echo "<div class=\"row\"><div class=\"span12\"><table class=\"table table-striped\"><thead><tr><th>#</th><th>Имя</th><th>Рейтинг</th></tr></thead><tbody>";
$last = 0;
for ($n=0;$n<mysql_num_rows($res);$n++) {
	$name=mysql_result($res,$n,'name');
	$rate=mysql_result($res,$n,'rate');
	if ($rate <> $last)
	{
		$last = $rate;
		$count = $n+1;
	}
	if ($name == $_SESSION['name'])
		echo "<tr class='success'>";
	else
		echo "<tr>";
	echo "<td>$count</td><td>$name</td><td>$rate</td></tr>";
}
echo "</tbody></table></div></div>";
}
else if ($page == "admin")
{
	if ($admin)
	{
		$res = mysql_query("SELECT `id`, `name` FROM `users`");
		$players = array();
		while ($row = mysql_fetch_array($res))
		{
			$players[$row['id']] = $row['name'];
		}
		echo form(array("action" => "addgame", "reverse" => "index.php", "method" => "POST"),
		input_select_key("first", "Первый", $players),
		input_select_key("second", "Второй", $players));
	}
	else
	{
		echo "Доступ запрещён";
	}
}
?>
      <footer>
        <p>&copy; Зинов Николай 2014</p>
        <a href="https://plus.google.com/109229859109198130360" rel="publisher">Google+</a>
      </footer>

    </div> <!-- /container -->
    <!-- Placed at the end of the document so the pages load faster -->
<script language="javascript" type="text/javascript">
$('.indevelop').tooltip({placement : 'bottom'})
$('#<? echo $page?>').addClass('active');
</script>
  </body>
</html>
