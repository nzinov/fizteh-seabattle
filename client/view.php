<?php
session_start();
include("constants.php");
if (!isset($_SESSION['login']))
    $error_msg = "Представьтесь, если вы хотите посмотреть трансляцию";
else
{
    $id = $_GET['id'];
    $link_id = mysql_connect($host, $username, $password);
    mysql_select_db($dbase,$link_id);
    mysql_query("set names 'utf8'");
    $res = mysql_query("SELECT u1.login AS first, u2.login AS second, islands.name, games.type FROM `games`
        LEFT JOIN `islands` ON `islands`.id=games.island 
        JOIN `users` AS u1 ON u1.id=games.first
        JOIN `users` AS u2 ON u2.id=games.second
        WHERE games.`id`={$_GET['id']};");
    $first = mysql_result($res,0,'first');
    $second = mysql_result($res,0,'second');
    $island = mysql_result($res,0,'name');
    $status = mysql_result($res,0,'type');
    $type = "";
    if (mysql_num_rows($res) == 1)
    {
        if ($status < 3)
            $error_msg = "Эта игра еще не началась";
        else if ($status > 3)
            $error_msg = "Игра уже закончилась, что насчёт посмотреть <a href=/view_history.php/$id>запись?</a>";
        else if ($first == $_SESSION['login'] or $second == $_SESSION['login'])
            $error_msg = "Вы участвуете в этой игре, поэтому вам нельзя смотреть трансляцию";
        else
            $type = $_GET['id']." -1";
    }
    else $error_msg = "Игра не найдена. Возможно вам подсунули неправильную ссылку";
}
?>
<!DOCTYPE html>

<meta charset="utf-8" />

<title>Морской бой по-физтеховски - Трансляция игры</title>
<style>
html,body {
      position: fixed;
      overflow: hidden;
      height: 100%;
      width: 100%;
}
#info {
      position: fixed;
      right: 20px;
      top: 5px;
}
#output {
      aling: right;
      overflow: auto;
      margin-bottom: 20px;
}
#input {
      position: fixed;
      right: 20px;
      bottom: 5px;
      aling: right;
      display: none;
      width: 252px;
}
#answer{
      position: fixed;
      top: 5px;
      display: none;
      overflow: auto;
      max-height: 100%;
      left: 20px;
}
#field {
      margin-top: 5px;
      min-height: 100%;
      max-width: auto;
      overflow: auto;
      border: 0px;
}
.log {
      color: blue;
}
.square {
      outline: 1px solid black;
      border: 0px;
      padding: 0px;
      margin: 0px;
      background-size:100% 100%;
}
}
</style>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
    <!-- Le styles -->
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css" rel="stylesheet">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
<link rel="shortcut icon" href="SBpic/favicon.png">
<script type="text/javascript" src="json.js"></script>
<script type="text/javascript" src="log_view.js"></script>
<?php
if ($type != "")
{
?>
<script language="javascript" type="text/javascript">
var debug = true;
var showlog = false;
var nolimit = false;


var wsUri = "ws://server-seabattle.rhcloud.com:8000/";
//var wsUri = "ws://10.0.1.6:8080/";
var websocket;
var output;
var me = "<?=$_SESSION['login']?>";
var first = "<?=$first?>";
var second = "<?=$second?>";
var inputshown = false;
var fignum = 
{
    AB : 1,
        Av : 2,
        Br : 3,
        Es : 4,
        F : 5,
        Kr : 6,
        KrPl : 7,
        Lk : 8,
        Mn : 9,
        NB : 10,
        Pl : 11,
        Rd : 12,
        Rk : 13,
        Sm : 14,
        St : 15,
        T : 16,
        Tk : 17,
        Tp : 18,
        Tr : 19,
        Unknown : 20,
        Sinking : 21
}
var figname = ['Null', 'AB', 'Av', 'Br', 'Es', 'F', 'Kr', 'KrPl', 'Lk', 'Mn', 'NB', 'Pl', 'Rd', 'Rk', 'Sm', 'St', 'T', 'Tk', 'Tp', 'Tr', 'Unknown', 'Sinking'];
var info = [ "Пустая клетка",
    "Атомная бомба<br> Щелкнув в фазу атаки можно взорвать - уничтожить все корабли в квадрате 3х3 клетки. Если ее атакуют - взрывается.",
    "Авианосец<br> Образует блоки. Может нести Самолет. 729 е.с.",
    "Брандер.<br>Можно выстрелить в соседний (не по диагонали корабль) противника и захватить его. Захват только один раз подряд. При атаке взрывается как Мина.",
    "Эсминец.<br>Образует блоки. Может нести мины. 216 е.с.",
    "Форт<br> Абсолютно неподвижен. Если противник захватил все ваши Форты - вы проиграли. Выстрелы Торпед, Самолетов и Ракет не наносят вреда Фортам.",
    "Крейсер.<br>Образует блоки. 324 е.с.",
    "Крейсерская подводная лодка<br>Побеждает всех кроме Крейсера, Рейдера и Эсминца. Не образует и не учитывает блоки",
    "Линкор.<br>Образует блоки. 486 е.с.",
    "Мина<br> Ходит только рядом с эсминцем. Не атакует. При атаке уничтожается вместе с атаковавшим кораблем. Уничтожается Тральщиком.",
    "Нейтронная бомба<br> Щелкнув в фазу атаки можно взорвать - сделать все корабли в квадрате 3х3 клетки ничейными. Если ее атакуют - взрывается.",
    "Подводная лодка<br>Побеждает Линкор. Не образует и не учитывает блоки",
    "Рейдер<br> Образует блоки. Может входить в блоки с более слабой фишкой. Блок Рд+Ст может называться 2 Эсминца, а Рд+Ст+Эс - 3 Эсминца. 288 е.с.",
    "Ракета<br>Ходит только рядом с Крейсерской подводной лодкой. Можно выстрелить по прямой над кораблями двумя способами <b>Смотрите подсказку переключения способов внизу экрана, когда вы перетаскиваете ракету</b>: 1)не более чем на 2 клетки и уничтожить все корабли в квадрате 3х3 клетки. 2)не более чем на 3 клетки и уничтожить 1 корабль. Не наносит вреда Фортам. Уничтожается при выстреле.",
    "Самолет<br>Можно выстрелить (если рядом Авианосец) по прямой над кораблями на любое расстояние. Уничтожает любой корабль, кроме Форта. Уничтожается при выстреле.",
    "Сторож.<br>Образует блоки. 144 е.с.",
    "Торпеда<br>Ходит только рядом с Торпедным катером. Может ходить на 2 клетки. Можно выстрелить по прямой не более чем на 4 клетки. Не может стрелять сквозь корабли. Не наносит вреда Фортам.",
    "Торпедный катер<br> Образует блоки. Может ходить на 2 клетки. Переносит Торпеды. 96 е.с.",
    "Транспорт<br> Не образует блоки. Может переносить и запускать любые фишки. Уничтожается любым кораблем.",
    "Тральщик<br> Может снимать мины, торпеды и брандеры (атакуя их). Образует блоки. 64 е.с.",
    "Корабль противника",
    "Ничейный корабль<br>Атакуйте его любым кораблем, чтобы забрать себе. Ход перейдет к противнику."];
var sum = 30
var dragging;
var isshot;
var movepassed = false;
var isaoe;
var agr;
var tar;
var asktype;
var block = [];
var b_str = [];
var b_fig = [];
var b_x = [];
var b_y = [];
function init()
{
    $(window).resize(function (){
        sq = ($("#field").height()-10)/14;
        $(".square").width(sq);
        $(".square").height(sq);
        margin = ($("body").width()-sq*14);
        $("#info").width(margin-40);
        $("#output").height($(window).height()-$("#header").height()-65);
        $("#output").scrollTop($("#output > div").height());
    }).resize();
    $("body").keydown(function (evt) {
        //alert (evt.which)
        if (evt.which == 13)
        {
            if (!inputshown)
            {
                $("#input").fadeIn(1000);
                inputshown = true;
                $("#text").focus();
            }
            else
            {
                $("#input").fadeOut(1000);
                inputshown = false;
                if ($("#text").val() != "")
                {
                    doSend(me+": "+$("#text").val());
                    $("#text").val("");
                }
            }
            return false;
        }
    });
    output = document.getElementById("output");
    testWebSocket();
        $(".square").add(".prototype").popover({trigger: 'hover', placement: 'auto bottom', delay: {show: 1000}, html: true, container: 'body', content: function() { return info[$(this).attr("fig")];}});
    $(".square").bind( "touchmove", function(e){
        e.preventDefault();
    });
    $(".prototype").bind( "touchmove", function(e){
        e.preventDefault();
    });
    $(".square").bind( "touchstart", function(e){
        $(this).mousedown();
        e.preventDefault();
    });
    $(".prototype").bind( "touchstart", function(e){
        $(this).mousedown();
        e.preventDefault();
    });
    $(".square").bind( "touchend", function(e){
        $(document.elementFromPoint(e.changedTouches[0].clientX, e.changedTouches[0].clientY)).mouseup();
        e.preventDefault();
    });
    $(".prototype").bind( "touchend", function(e){
        $(document.elementFromPoint(e.changedTouches[0].clientX, e.changedTouches[0].clientY)).mouseup();
        e.preventDefault();
    });
}
function testWebSocket()
{
    websocket = new WebSocket(wsUri);
    websocket.onopen = function(evt) { onOpen(evt) };
    websocket.onclose = function(evt) { onClose(evt) };
    websocket.onmessage = function(evt) { onMessage(evt) };
    websocket.onerror = function(evt) { onError(evt) };
}
function onOpen(evt)
{
    doSend("<?echo $type?>");
    writeToScreen("Подключено к серверу");
}
function onClose(evt)
{
    writeToScreen("Соединение закрыто");
}
c_type = ["url(displace_grab.cur), pointer", "url(displace.cur), move", "url(move.cur), move", "url(shot.cur), crosschair", "url(attack.cur), help", "url(radar.png) 15 15, wait", "pointer"];
function onstatus()
{
    $("#output").height($(window).height()-$("#header").height()-65);
    $("#output").scrollTop($("#output > div").height());
}
function onMessage(evt)
{
    writeToScreen('<span style="color: blue;">Сообщение от сервера: ' + evt.data+'</span>');
    mess = JSON.parse(evt.data)
        if (mess.action == 1)
        {
            player = (mess.player == 1 ? first : second);
            switch(mess.phase)
            {
            case 0:
                $("#main").html("<h5>Расстановка</h5>");
                break;
            case 1:
                $("#main").html("<h5>Ход игрока "+player+"</h5>");
                break;
            case 4:
                $("#main").html("<h5>Ход игрока "+player+"</h5>");
                break;
            case 2:
                if (player == 0) $("#main").html("<h5>Игра завершилась вничью.</h5>");
                else $("#main").html("<h5>Игра окончена. Победил игрок "+player+"</h5>");
                break;
            case 3:
                $("#main").html("<h5>Выбор блока</h5>");
                break;
            }
            onstatus();
        }
        else if (mess.action == 4)
        {
            onbottom = ($("#output").scrollTop() > $("#output > div").height() - $("#output").height());
            if (mess.player == 0) $("#output > div").append("<p class=\"log\">"+mess.message+"<p>");
            else if (mess.player == 3) $("#output > div").append("<p style=\"color: gray\">"+mess.message+"<p>");
            else
            {
                player = (mess.player == 1 ? first : second);
                $("#output > div").append("<p style=\"color: black\">"+player+": "+mess.message+"<p>");
            }
            if (onbottom)
                $("#output").scrollTop($("#output > div").height());
        }
        else if (mess.action == 3)
        {
            for (i = 0; i < mess.changex.length; i++)
            {
                x = mess.changex[i];
                y = mess.changey[i];
                fig = mess.changefig[i];
                fig_player = fig / 100 | 0;
                fig = fig % 100;
                pos = $("[id='"+x+":"+y+"']"); 
                if (fig_player == 0)
                    pos.css("background-color", "white");
                else if (fig_player == 1)
                    pos.css("background-color", "#98ff98");
                else if (fig_player == 2)
                    pos.css("background-color", "#7fc7ff");
                if (fig != 0)
                    pos.attr("fig",fig).css("background-image","url('SBpic/"+figname[fig]+".png')");
                else pos.attr("fig",fig).css("background-image","none");
            }
        }
        else if (mess.action == 2)
        {
            LogParse(mess.log, first, second);
        }
        else if (mess.action == 0)
        {
            $("#main").html("Соединено с сервером. Игра еще не началась");
        }
}
function onError(evt)
{
    $("#output").append('<p style="color: red;">Ошибка соединения с сервером</p>');
    writeToScreen('<span style="color: red;">Произошла ошибка.</span> ' + evt.data);
    if (!debug) return true;
}
function doSend(message)
{
    writeToScreen("Отправил сообщение: " + message); 
    websocket.send(message);
}

function writeToScreen(message)
{
    if (showlog) $("#output").append("<p>"+message+"</p>");
}
window.addEventListener("load", init, false);

</script>
<?php
}
?>
<body>
<div class="modal fade" id="modal_window" tabindex="-1" role="dialog"> 
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <h4 class="modal-title" id="ModalLabel">Просмотр невозможен</h4>
      </div>
      <div class="modal-body">
        <?=$error_msg;?>
      </div>
        <div class="modal-footer">
        <a type="button" class="btn btn-primary" href="/index.php">Вернуться</a>
      </div>
    </div>
  </div>
</div>
<div id="input"><input id="text" type="text" size=30 style="background-color: #ffcc99; opacity: 0.9; border: none"></div>
<div id="field"><table border>
<?php
if (isset($error_msg))
{
    echo "<script>$('#modal_window').modal({keyboard: false});</script>";
}
for ($i = 0; $i < 14;$i++)
{
    echo "<tr>";
    for ($j = 0; $j < 14;$j++)
    {
        echo "<td id=\"$i:$j\" x=$i y=$j class=\"square\" onclick = \"void(0)\"></td>";
    }
    echo "</tr>";
}
?>
</table>
</div>
<div id="info">
<div id="header" class="well">
<h4><?=$first?> vs. <?=$second?><br/>
<?
if (!is_null($island))
{
?>
<small>Cражение за влияние на острове <?=$island?></small>
<?
}
?>
</h4>
<div id= "main" >Если это сообщение не исчезает дольше минуты, то, вероятно, возникла проблема при подключении к серверу</div>
</div>
<div id="output" ><div></div></div>
</div>
</div>
</body>
</html> 
