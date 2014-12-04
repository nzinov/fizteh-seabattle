<?php
session_start();
include("constants.php");
$link_id = mysql_connect($host, $username, $password);
mysql_select_db($dbase,$link_id);
mysql_query("set names 'utf8'");
$id = $_GET['id'];
$res = mysql_query("SELECT u1.name AS first, u2.name AS second, islands.name, games.type FROM `games`
    LEFT JOIN `islands` ON `islands`.id=games.island 
    JOIN `users` AS u1 ON u1.id=games.first
    JOIN `users` AS u2 ON u2.id=games.second
    WHERE games.`id`=$id;");
$first = mysql_result($res,0,'first');
$second = mysql_result($res,0,'second');
$island = mysql_result($res,0,'name');
$status = mysql_result($res,0,'type');
if (mysql_num_rows($res) == 1)
{
    if ($status < 3)
        $error_msg = "Эта игра еще не началась";
    else if ($status == 3)
        $error_msg = "Эта игра сейчас идет, вы можете посмотреть ее <a href=/view.php/$id>трансляцию</a>";
    else
    {
        $fname = "$history_dir/$id.hist";
        $lines = file($fname, FILE_IGNORE_NEW_LINES);
        if (!$lines)
            $error_msg = "К сожалению, нам не удалось записать эту игру";
    }
}
else $error_msg = "Игра не найдена. Возможно вам подсунули неправильную ссылку";
?>
<!DOCTYPE html>

<meta charset="utf-8" />

<title>Морской бой по-физтеховски - Запись игры</title>
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
<link rel="shortcut icon" href="/SBpic/favicon.png">
<script type="text/javascript" src="/json.js"></script>
<script type="text/javascript" src="/log_view.js"></script>
<?php
if ($lines != false)
{
    echo "<script>";
    echo "var game_history = [[";
    foreach ($lines as $line_num => $line)
    {
        if ($line == '|')
            echo "], [";
        else
            echo '"'.$line.'",';
    }
    echo "]];";
    echo "</script>";
?>
<script language="javascript" type="application/javascript">
var output;
var first = "<?=$first?>";
var second = "<?=$second?>";
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
    output = document.getElementById("output");
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
    var hash = (window.location.hash ? to_int(window.location.hash.substring(1)) : 0);
    set_move(hash);
}
function onstatus()
{
    $("#output").height($(window).height()-$("#header").height()-65);
    $("#output").scrollTop($("#output > div").height());
}
var move = -1;
var restore = [];
var field = [];
for (var i = 0; i < 14; i++)
{
    field[i] = [];
    for (var j = 0; j < 14; j++)
    {
        field[i][j] = [0, 0];
    }
}
function to_int(x)
{
    return parseInt(x, 10)
}
function set_fig(x, y, fig, player)
{
    field[x][y] = [fig, player];
    var pos = $("[id='"+x+":"+y+"']"); 
    if (player == 0)
        pos.css("background-color", "white");
    else if (player == 1)
        pos.css("background-color", "#98ff98");
    else if (player == 2)
        pos.css("background-color", "#7fc7ff");
    if (fig != 0)
        pos.attr("fig",fig).css("background-image","url('/SBpic/"+figname[fig]+".png')");
    else pos.attr("fig",fig).css("background-image","none");
}
function update()
{
    $('#move').val(move); 
    document.location.hash = move;
}
function prev()
{
    if (move == 0)
    {
        pause();
        return;
    }
    for (var i in restore[move])
    {
        act = restore[move][i];
        field[act[0]][act[1]] = act[2];
        set_fig(act[0], act[1], act[2][0], act[2][1]);
    }
    move--;
    update();
}
function next()
{
    onstatus();
    if (move == game_history.length)
    {
        pause();
        return;
    }
    move++;
    var cur = game_history[move];
    restore[move] = [];
    for (var i = 0; i < cur.length; i++)
    {
        var act = cur[i];
        if (act[0] == '<')
        {
            var prop = act.slice(1).split(':');
            var win = prop.pop() == 'True';
            prop = prop.map(to_int);
            var log = {
                type: prop[0],
                player: prop[1],
                from: {x: prop[2], y: prop[3]},
                to: {x: prop[4], y: prop[5]},
                agr: prop[6],
                tar: prop[7],
                agr_str: prop[8],
                tar_str: prop[9],
                win: win
            };
            LogParse(log, first, second);
        }
        else
        {
            var prop = act.split(':').map(to_int);
            var x = prop[0];
            var y = prop[1];
            var fig = prop[2];
            var player = prop[3];
            restore[move].push([x, y, field[x][y]]);
            set_fig(x, y, fig, player);
        }
    }
    update();
}
function set_move(m)
{
    while (move < m)
        next();
    while (move > m)
        prev();
}
function onmove()
{
    set_move($('#move').val());
}
var playback;
var delay = 512;
var is_fast = false;
var direction = next;
function pl(dir, delay)
{
    $('#play-btn').prop('disabled',true);
    $('#pause-btn').prop('disabled',false);
    window.clearInterval(playback);
    playback = window.setInterval(dir, delay);
}
function fast(dir)
{
    if (is_fast && dir == direction)
    {
        if (delay > 1)
          delay /= 2;
    }
    else
        delay = 512;
    pl(dir, delay);
    is_fast = true;
    direction = dir;
}
function play()
{
    pl(next, 1000);
}
function pause()
{
    $('#play-btn').prop('disabled',false);
    $('#pause-btn').prop('disabled',true);
    window.clearInterval(playback);
    is_fast = false;
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
</h4>
<div class="center-block">
<button class="btn btn-primary" onclick=prev()>
    <span class="glyphicon glyphicon-chevron-left">
</button>
<button class="btn" onclick=fast(prev) id="fast-btn">
    <span class="glyphicon glyphicon-fast-backward">
</button>
<input class="form-control" onchange=onmove() style="display: inline; width: 70px;" type="number" min=1 value=1 id="move">
<button class="btn btn-success" onclick=pause() disabled id="pause-btn">
    <span class="glyphicon glyphicon-pause">
</button>
<button class="btn btn-success" onclick=play() id="play-btn">
    <span class="glyphicon glyphicon-play">
</button>
<button class="btn" onclick=fast(next) id="fast-btn">
    <span class="glyphicon glyphicon-fast-forward">
</button>
<button class="btn btn-primary" onclick=next()>
    <span class="glyphicon glyphicon-chevron-right">
</button>
</div>
</div>
<div id="output" ><div></div></div>
</div>
</div>
</body>
</html> 
