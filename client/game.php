<?php
session_start();
include("constants.php");
$id = $_GET['id'];
$opponent = "";
if ($id == "test" && $_SESSION['id'] == 3)
{
    $type = "prompt";
    $first = "Тестовая игра";
    $second = "";
    $island = null;
}
else
{
    $link_id = mysql_connect($host, $username, $password);
    mysql_select_db($dbase,$link_id);
    mysql_query("set names 'utf8'");
    $res = mysql_query("SELECT u1.name AS first, u2.name AS second, islands.name, games.type FROM `games`
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
        if ($status > 3)
        {
            $error_title = "Это матч завершен";
            $error_msg = "Хотите посмотреть его в <a href=/view_history.php/$id>записи</a>?";
        }
        else if ($first == $_SESSION['name'])
        {
            $type = $_GET['id']." 1";
            $opponent = $second;
        }
        else if ($second == $_SESSION['name'])
        {
            $type = $_GET['id']." 2";
            $opponent = $first;
        }
        else 
        {
            $error_title = "Вы не участвуете в этой игре";
            $error_msg = "Это игра между $first и $second ".(is_null($island) ? "" : "за влияние на острове $island ")."<br/> Может быть вы хотите посмотреть <a href=\"/view.php/{$_GET['id']}\">трансляцию</a>?";
        }
    }
    else
    {
       $error_title = "Игра не найдена";
       $error_msg = "Возможно вам подсунули неправильную ссылку";
    }
}
?>
<!DOCTYPE html>

<meta charset="utf-8" />

<title>Морской бой по-физтеховски</title>
<style>
@media (min-height: 600px) {
    #badge {
        display: none;
    }
    #info {
          float: left;
          width: calc(100% - 100vh - 20px);
    }
    .square {
          width: 7vh;
          height: 7vh;
    }
    #field {
      width: 100vh;
      float: left;
    }
}
@media (max-height: 600px) {
    html, body {
        overflow: auto
    }
    #badge {
        position: absolute;
        width: 20px;
        left: -20px;
        bottom: 20px;
        background-color: #9C9C9C;
        border-radius: 5px 0 0 5px;
        padding: 5px;
    }
    #info {
          position: fixed;
          width: 400px;
          top: 0px;
          right: -380px;
          padding-left: 10px;
          background-color: white;
          border-left: 2px solid blue;
          transition: right 500ms ease 0s;
    }
    #info.shown {
          right: 5px;
    }
    .square {
          width: 6.5vw;
          height: 6.5vw;
    }
    #field {
    }
}
html,body {
      overflow: hidden;
      height: 100%;
      width: 100%;
}
#info {
      margin-top: 5px;
      margin-bottom: 40px;
      height: 100%;
}
#header {
      margin-bottom: 0px;
      text-align: center;
      overflow: hidden;
      height: 25%;
}
#output {
      overflow-y: scroll;
      height: 75%;
}
#input {
      position: fixed;
      right: 20px;
      bottom: 5px;
      display: none;
      width: 252px;
}
#answer{
      position: fixed;
      display: none;
      overflow: auto;
      left: 20px;
      top: 5px;
}
#answer.bottom{
      top: auto;
      bottom: 5px;
}
#answer td{
      color: white;
      background-color: #99ff99;
      text-align: center;
      font-size: 180%;
      font-weight: bold;
}
#field {
      margin-top: 5px;
      margin-left: 10px;
      border: 0px;
}
.message {
      color: blue;
      background-color: #H0HHFF;
}
.square {
      outline: 1px solid black;
      border: 0px;
      padding: 0px;
      margin: 0px;
      background-size: 100% 100%;
}
span.nowrap {
      display: inline-block;
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
<link rel="shortcut icon" href="/SBpic/favicon.png">
<script type="text/javascript" src="/json.js"></script>
<script type="text/javascript" src="/log.js"></script>
<?php
if ($type != "")
{
?>
<script language="javascript" type="text/javascript">
var debug = true;
var showlog = false;
var type = "<?=$type?>";
var wsUri = "ws://<?=($_SERVER['HTTP_HOST'] == 'localhost' ? "localhost" : "server-seabattle.rhcloud.com")?>:8000/";
var opponent = '<?=$opponent;?>';
var websocket;
var output;
var you;
var phase;
var player;
var inputshown = false;
var figcount = 62;
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
var count = [ 0,1,1,2,6,2,6,1,2,7,1,4,2,1,1,7,4,6,1,7];
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
var field = new Array();
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
function showinput()
{
    $("#input").fadeIn(1000);
    inputshown = true;
    $("#text").focus();
}
function trigaoe()
{
    isaoe = !isaoe;
    $("#aoeon").html(isaoe ? "<span style=\"color: green\">включен</span>" : "<span style=\"color: red\">выключен</span>");
}
var page_visible = true;
const DB_KEY = 'seabattle-displacing';
function init()
{
    for (i = 0; i< 14; i++)
    {
        field[i] = Array();
        for (j = 0; j< 14; j++)
        {
            field[i][j] = 0;
        }
    }
    $(window).resize(function (){
        $("#output").scrollTop($("#output > div").height());
    }).resize();
    if (type == "prompt")
        type = prompt("Enter '<game id> <player num (1 / 2)>':");
    testWebSocket();
    $("body").keydown(function (evt) {
        //alert (evt.which)
        if (evt.which == 13)
        {
            if (!inputshown)
            {
                showinput();
            }
            else
            {
                $("#input").fadeOut(1000);
                inputshown = false;
                if ($("#text").val() != "")
                {
                    SendJSON({action: 0, message: $("#text").val()});
                    $("#text").val("");
                }
            }
            return false;
        }
        else if ((player == you || phase == 0) && !inputshown)
        {
            if (evt.which == 82 && phase == 1 && isshot)
            {
                trigaoe();
            }
            else if (evt.which == 32)
            {
                if (phase == 0)
                {
                    displace()
                }
                if (phase == 1)
                {
                    movepass()
                }
                else if (phase == 4)
                {
                    attackpass()
                }
            }
            else if (evt.which == 83)
            {
                if (phase == 1)
                {
                    shottrig()
                }
            }
            else
            {
                return true;
            }
            return false
        }
        return true;
    });
        $(".square").popover({trigger: 'hover', placement: 'auto bottom', delay: {show: 1000}, html: true, container: 'body', content: function() { return info[$(this).attr("fig")];}});
    $(".square").on( "touchmove", function(e){
        e.preventDefault();
    });
    $(".square").on( "touchstart", function(e){
        $(e.target).mousedown();
        e.preventDefault();
    });
    $(".square").on( "touchend", function(e){
        $(document.elementFromPoint(e.originalEvent.changedTouches[0].pageX - window.pageXOffset, e.originalEvent.changedTouches[0].pageY - window.pageYOffset)).mouseup();
        e.preventDefault();
    });
    window.onfocus = function () {
        page_visible = true;
    };
    window.onblur = function () {
        page_visible = false;
    };
    Notification.requestPermission();
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
    doSend(type);
    console.info("Подключено к серверу");
}
function onClose(evt)
{
    console.info("Соединение закрыто");
}
c_type = ["url(displace_grab.cur), pointer", "url(displace.cur), move", "url(move.cur), move", "url(shot.cur), crosschair", "url(attack.cur), help", "url(radar.png) 15 15, wait", "pointer"];
function setcursor(type)
{
    $(".square, body").css("cursor", c_type[type]);
}
function show_modal(title, msg)
{
    $('#modal_title').html(title);
    $('#modal_msg').html(msg);
    $('#modal_window').modal({keyboard: false});
}
function displace()
{
    d = you == 1 ? 0 : 9;
    f = field.slice(0+d,5+d);
    var c = 0;
    for (var i = d; i < d+5; i++)
        for (var j = 0; j < 14; j++)
            if (field[i][j])
                c++;
    if (figcount != c)
    {
        alert('Расставьте все корабли');
        return;
    }
    SendJSON({action: 1, phase: 0, field: f});
    if (you == 1) droppable($("[id^='0:'],[id^='1:'],[id^='2:'],[id^='3:'],[id^='4:']"),false);
    else droppable($("[id^='9:'],[id^='10:'],[id^='11:'],[id^='12:'],[id^='13:']"),false);
    localStorage.removeItem(DB_KEY);
}
function movepass()
{
    movepassed = true;
    SendJSON({action: 1, phase: 1, pass: true});
}
function attackpass()
{
    SendJSON({action: 1, phase: 4, pass: true});
}
function shottrig()
{
    setshot(!isshot);
}
function setshot(t)
{ 
    isshot = t;
    $("#shot").html((isshot ? "Стреляйте" : "Ходите"));
    setcursor((isshot ? 0 : 9));
}
function ondrop(evt)
{
    if (dragging == null)
        return;
    if (phase == 0)
    {
        x = $(this).attr("x");
        y = $(this).attr("y");
        fig = field[x][y];
        drag = $(dragging);
        id = drag.attr("id");
        sx = drag.attr("x");
        sy = drag.attr("y");
        if (field[sx][sy] != 0)
        {
            buf = field[x][y] 
            field[x][y] = field[sx][sy];
            field[sx][sy] = buf
            setfig(x, y, field[x][y]);
            setfig(sx, sy, field[sx][sy]);
            localStorage.setItem(DB_KEY, JSON.stringify(you == 1 ? field : field.slice().reverse()));
        }
        setcursor(0);
    }
    else if (phase == 1)
    {
        SendJSON({action: 1, phase: 1, fromx: $(dragging).attr("x"), fromy: $(dragging).attr("y"), tox: $(this).attr("x"), toy: $(this).attr("y"), isshot: isshot, isaoe: isaoe});
    }
    else if (phase == 4)
    {
        SendJSON({action: 1, phase: 4, fromx: $(dragging).attr("x"), fromy: $(dragging).attr("y"), tox: $(this).attr("x"), toy: $(this).attr("y")});
    }
    dragging = null;
}
function ondrag(evt)
{
    if (phase == 0)
    {
        setcursor(1);
    }
    dragging = this;
    return false;
}
function droppable(obj,enabled)
{
    if (enabled)
    {
        obj.mouseup(ondrop);//.css("background-color","#98ff98");
    }
    else
    {
        obj.unbind("mouseup");//.css("background-color","#FFFFFF");
    }
}
function draggable(obj,enabled)
{
    if (enabled)
    {
        obj.mousedown(ondrag);//.css("border-color","#98ff98");
    }
    else
    {
        obj.unbind("mousedown");//.css("border-color","#FFFFFF");
    }
}
function setfig(x, y, fig)
{
    field[x][y] = fig;
    if (fig != 0)
        $("[id='"+x+":"+y+"']").attr("fig",fig).css("background-image","url('/SBpic/"+figname[fig]+".png')");
    else $("[id='"+x+":"+y+"']").attr("fig",fig).css("background-image","none");
}
function onMessage(evt)
{
    console.log('<span style="color: blue;">Сообщение от сервера: ' + evt.data+'</span>');
    mess = JSON.parse(evt.data);
    onbottom = ($("#output").scrollTop() > $("#output > div").height() - $("#output").height());
    if (mess.action == 1)
    {
        phase = mess.phase;
        player = mess.player;
        if (phase == 3 || (player == you && (phase == 1 || phase == 3)))
        {
            if (!page_visible)
            {
                var n = new Notification("Ваш ход", {
                    tag : "your-move",
                    body : "Действуйте в игре с "+opponent,
                });
            }
            document.title = "<Действуйте!> Морской бой против "+opponent;
        }
        else
            document.title = "Морской бой против "+opponent;
        switch(phase)
        {
        case 0:
            if (player != you) 
            {
                setcursor(0);
                $("#main").html('<h4>Расставьте корабли</h4><a onclick=displace()>Нажмите</a> Пробел, когда будете готовы');
                zone = you == 1 ? $("[id^='0:'],[id^='1:'],[id^='2:'],[id^='3:'],[id^='4:']") : $("[id^='9:'],[id^='10:'],[id^='11:'],[id^='12:'],[id^='13:']");
                draggable($(".square"),true);
                droppable($(".square"),true);
                zone.css("background-color","#98ff98");
                var displ = localStorage.getItem(DB_KEY);
                if (displ !== null && confirm('Обнаружена незавершенная расстановка. Вы хотите ее продолжить?'))
                {
                    field = JSON.parse(displ);
                    if (you == 2)
                        field.reverse();
                    for (var i = 0; i < 14; i++)
                        for (var j = 0; j < 14; j++)
                            setfig(i, j, field[i][j]);
                }
                else
                {
                    cur = you != 1 ? 0 : 9*14;
                    for (i = 0; i < count.length; i++)
                    {
                        for (j = 0; j < count[i]; j++)
                        {
                            setfig(Math.floor(cur/14), cur%14, i);
                            cur++;
                        }
                    }
                }
            }
            else 
            {
                setcursor(5);
                $("#main").html("<h4>Ждите</h4>Противник на вашу погибель готовит подводные лодки");
            }
            break;
        case 1:
            $(".square").css("background-color","#FFFFFF");
            draggable($(".square"),true);
            droppable($(".square"),true);
            if (player == you) 
            {
                setcursor(2);
                movepassed = false;
                isshot = false;
                setshot(false);
                $("#main").html('<h4 id="shot">Ходите</h4><a onclick=movepass()>Нажмите</a> Пробел чтобы пропустить ход. <a onclick=shottrig()>Нажмите</a> S, чтобы переключить режим выстрела.<div id="aoe" style="display: none;">Площадной эффект ракеты <span id="aoeon">выключен</span>.<a onclick=trigaoe()>Нажмите</a> "R" для переключения</div>');
                trigaoe();

            }
            else 
            {
                setcursor(5);
                $("#main").html("<h4>Ждите</h4>Ход противника.");
            }
            break;
        case 4:
            draggable($(".square"),true);
            droppable($(".square"),true);
            if (player == you) 
            {
                setcursor(4);
                $("#main").html('<h4>Атакуйте</h4>Чтобы '+(movepassed ? 'вернуться к фазе хода' : 'пропустить атаку')+' <a onclick=attackpass()>нажмите</a> Пробел');
            }
            else 
            {
                setcursor(5);
                $("#main").html("<h4>Ждите</h4>Противник атакует.");
            }
            break;
        case 2:
            setcursor(6);
            $("#main").html("<h4>Игра окончена</h4>");
            if (player == 0) show_modal("Игра окончена", "Игра завершилась вничью.");
            else if (player == you) show_modal("Игра окончена","Вы победили. Поздравляем!!!");
            else show_modal("Игра окончена","Вы проиграли. Обидно Вам наверное...");
            break;
        case 3:
            $("#main").html("<h4>Выберите блок</h4>");
            tar = [mess.targetx,mess.targety]
            agr = [mess.agressorx,mess.agressory]
            if (player == you)
            {
                asktype = "agr";
                cor = agr
            }
            else
            {
                asktype = "tar";
                cor = tar
            }
            if (field[cor[0]][cor[1]] == "Pl" || field[cor[0]][cor[1]] == "KrPl")
                break;
            $("[id='"+agr[0]+':'+agr[1]+"']").css("background-color","#2a52be");
            $("[id='"+tar[0]+':'+tar[1]+"']").css("background-color","#ff0033");
            setcursor(6);
            block[cor[0]] = [];
            block[cor[0]][cor[1]] = true;
            AddBlock(1,field[cor[0]][cor[1]],[cor[0]],[cor[1]]);
            $(".square").click(FindBlock);
            if (cor[0] < 5)
                $("#answer").addClass("bottom");
            else
                $("#answer").removeClass("bottom");
            $("#answer").show();
            break;
        }
    }
    else if (mess.action == 4)
    {
        if (mess.player == 0) $("#output > div").append("<p class=\"log\">"+mess.message+"<p>");
        else $("#output > div").append("<p style=\"color: "+(mess.player == you ? "gray" : "black")+"\">"+mess.message+"<p>");
    }
    else if (mess.action == 3)
    {
        for (i = 0; i < mess.changex.length; i++)
        {
            x = mess.changex[i];
            y = mess.changey[i];
            fig = mess.changefig[i];
            setfig(x, y, fig);
        }
    }
    else if (mess.action == 2)
    {
        LogParse(mess.log);
    }
    else if (mess.action == 0)
    {
        you = mess.you;
        $("#main").html("Соединено с сервером. Ждите, пока подключится ваш противник.");
    }
    if (onbottom)
        $('#output').stop().animate({
              scrollTop: $("#output")[0].scrollHeight
        }, 800);
}
function onError(evt)
{
    $("#output").append('<p style="color: red;">Ошибка соединения с сервером</p>');
    console.error('<span style="color: red;">Произошла ошибка.</span> ' + evt.data);
    if (!debug) return true;
}
function SendJSON(mess)
{
    doSend(JSON.stringify(mess));
}
function doSend(message)
{
    console.log("Отправил сообщение: " + message); 
    websocket.send(message);
}
function FindBlock(evt)
{
    el=$(this);
    x = el.attr("x");
    y = el.attr("y");
    if (field[x][y] > 0 && field[x][y] < 20 && (x != tar[0] || y != tar[1]) && (x != agr[0] || y != agr[1]))
    {
        if (block[x] && block[x][y])
        {
            $("[id='"+x+":"+y+"']").css("background-color","#FFFFFF");
            block[x][y] = undefined;
            isblock(block);
        }
        else
        {
            if (block[x] == undefined) block[x] = [];
            block[x][y] = true;
            isblock(block);
            $("[id='"+x+":"+y+"']").css("background-color",asktype == "agr" ? "#2a52be" : "#ff0035");
        }
    }
}
function isblock(b)
{
    $("#answer").html("");
    blocktype = 0;
    str = 0;
    ox = [];
    oy = [];
    Rd = false;
    for (i in b)
    {
        for (j in b[i])
        {
            if (b[i][j])
            {
                if (field[i][j] == fignum.Rd) Rd = true;
            }
        }
    }
    for (i in b)
    {
        for (j in b[i])
        {
            if (b[i][j])
            {
                fig = field[i][j];
                if (blocktype == 0)
                {
                    blocktype = fig;
                }
                else if (blocktype == fig) blocktype = fig;
                else if (blocktype == fignum.Rd && (fig == fignum.Es || fig == fignum.St || fig == fignum.Tk || fig == fignum.Tr)) blocktype = fig;
                else if (fig == fignum.Rd && (blocktype == fignum.Es || blocktype == fignum.St || blocktype == fignum.Tk || blocktype == fignum.Tr)) Rd = true;
                else if (fig == fignum.Es && blocktype == fignum.St && Rd) blocktype = fignum.Es;
                else if (fig == fignum.St && blocktype == fignum.Es && Rd) blocktype = fignum.Es;
                else return false;
                ox[ox.length] = i*1;
                oy[oy.length] = j*1;
                str++;	
            }
        }
    }
    AddBlock(str,blocktype,ox,oy);
    if (str == 2 && blocktype == fignum.St && Rd) AddBlock(2,fignum.Es,ox,oy);
    return true;
}
function AddBlock(str,fig,x,y)
{
    n = b_str.length;
    b_str[n] = str;
    b_fig[n] = fig;
    b_x[n] = x;
    b_y[n] = y;
    $("#answer").append("<tr><td onclick=b_click() height=64 width=64 style=\"background-image: url('/SBpic/"+figname[fig]+".png');\" n=\""+n+"\"><h2>"+str+"</h2></td></tr>");
}
function b_click()
{
    n = $(this).attr("n");
    SendJSON({action: 1, phase: 3, blockstrength: b_str[n], blocktype: b_fig[n], blockx: b_x[n], blocky: b_y[n]});
    for (i = 0; i < b_x[n].length; i++) {$("[id='"+b_x[n][i]+":"+b_y[n][i]+"']").css("background-color","#FFFFFF");}
    $("[id='"+agr[0]+":"+agr[1]+"']").css("background-color","#FFFFFF");
    $("[id='"+tar[0]+":"+tar[1]+"']").css("background-color","#FFFFFF");
    b_str = [];
    b_fig = [];
    b_x = [];
    b_y = [];
    block = [];
    $(".square").unbind("click");
    $("#answer").html("");
    $("#answer").hide();
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
      <h4 class="modal-title" id="modal_title"><?=$error_title;?></h4>
      </div>
      <div class="modal-body" id="modal_msg">
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
if (isset($error_title))
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
<div id="badge" onclick="$('#info').toggleClass('shown')"><span class="glyphicon glyphicon-align-justify"></span></div>
<div id="header" class="well center-block">
<h4><span class="nowrap"><?=$first?></span> vs <span class="nowrap"><?=$second?></span><br/>
<?
if (!is_null($island))
{
?>
<small>Cражение за влияние на острове <?=$island?></small>
<?
}
?>
</h4>
<button class="btn btn-xs btn-danger" onclick="if (confirm('Вы действительно хотите сдаться?')) {SendJSON({action: 3});}" >Сдаться</button>
<button class="btn btn-xs btn-warning" id="draw" onclick="SendJSON({action: 4});" >Предложить ничью</button>
<button class="btn btn-xs btn-default" onclick="showinput();" >Написать сообщение</button></br>
<div id= "main" >Вероятно, возникла проблема при подключении к серверу</div>
</div>
<div id="output" ><div></div></div>
</div>
</div>
<table id="answer"></table>
</body>
</html> 
