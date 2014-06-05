<!DOCTYPE html>

<meta charset="utf-8" />

<title>Морской бой по-физтеховски -- Обучение</title>
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
#displacing, #answer{
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
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
<script src="../bootstrap/js/bootstrap.js"></script>
    <!-- Le styles -->
    <link href="../bootstrap/css/bootstrap.css" rel="stylesheet">
<link href="../bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
<link rel="shortcut icon" href="SBpic/favicon.png">
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
<script type="text/javascript" src="json.js"></script>
<script type="text/javascript" src="log.js"></script>
<script language="javascript" type="text/javascript">
var count = [ 0,1,1,2,6,2,6,1,2,7,1,4,2,1,1,7,4,6,2,7];
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
	sq = ($("#field").height()-10)/14;
	$(".square").width(sq);
	$(".square").height(sq);
	margin = ($("body").width()-sq*14)/2;
	$("#field").css("margin-left",margin);
	$("#displacing").css("margin-left",margin/2);
	$("#info").width(margin-40);
	$("#output").height($(window).height()-$("#header").height()-65);
	$("#output").scrollTop($("#output > div").height());
	}).resize();
    output = document.getElementById("output");
    testWebSocket();
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
				   if ($("#text").attr("value") != "")
				   {
				       SendJSON({action: 0, message: $("#text").attr("value")});
					   $("#text").attr("value","");
				   }
				   }
			  }
			  else if (evt.which == 82)
			  {
			      isaoe = !isaoe;
				  $("#aoeon").html(isaoe ? "<span style=\"color: green\">включен</span>" : "<span style=\"color: red\">выключен</span>");
			  }
	})
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
function displace()
{
if (sum == 0 || nolimit)
{
    d = you == 1 ? 0 : 9;
	f = field.slice(0+d,5+d);
    SendJSON({action: 1, phase: 0, field: f});
	$("#displacing").fadeOut(1000);
	draggable($(".prototype"),false);
	if (you == 1) droppable($("[id^='0:'],[id^='1:'],[id^='2:'],[id^='3:'],[id^='4:']"),false);
	else droppable($("[id^='9:'],[id^='10:'],[id^='11:'],[id^='12:'],[id^='13:']"),false);
}
else alert("Расставьте все корабли");
}
function onstatus()
{
$("#output").height($(window).height()-$("#header").height()-65);
$("#output").scrollTop($("#output > div").height());
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
    isshot = !isshot;
	$("#shot").attr("value","Сделать "+(isshot ? "Ход" : "Выстрел"))
}
function ondrop(evt)
{
    if (dragging == null) return;
    if (phase == 0)
	{
    x = $(this).attr("x");
	y = $(this).attr("y");
    fig = field[x][y];
    if (fig != 0)
    {
		count[fig]++;
        draggable($("#prot"+fig).css("background-color","#98ff98").html(count[fig]),true)
		sum++;
    }
    drag = $(dragging);
	id = drag.attr("id");
	if (id.substring(0,4) != "prot")
	{
	sx = drag.attr("x");
	sy = drag.attr("y");
	if (field[sx][sy] != 0)
	{
    field[x][y] = field[sx][sy];
	field[sx][sy] = 0;
	$(this).attr("fig",field[x][y]);
	drag.attr("fig",0);
    drag.css("background-image","none");
	$(this).css("background-image","url('SBpic/"+figname[field[x][y]]+".png')");
	}
	}
	else
	{
	id = id.substring(4)*1;
    count[id]--;
	$("#prot"+id).html(count[id]);
    if (count[id] <= 0) draggable($("#prot"+id).css("background-color","#FFFFFF"),false);
    field[x][y] = id;
	$(this).attr("fig",id);
    $(this).css("background-image","url('SBpic/"+figname[id]+".png')");
	sum--;
	}
	}
    else if (phase == 1)
	{
	   SendJSON({action: 1, phase: 1, fromx: $(dragging).attr("x"), fromy: $(dragging).attr("y"), tox: $(this).attr("x"), toy: $(this).attr("y"), isshot: isshot, isaoe: isaoe});
	   isshot = false;
	   $("#shot").attr("value","Сделать Выстрел")
	   $("#aoe").hide();
	}
	else if (phase == 4)
	{
	   SendJSON({action: 1, phase: 4, fromx: $(dragging).attr("x"), fromy: $(dragging).attr("y"), tox: $(this).attr("x"), toy: $(this).attr("y")});
	}
	dragging = null;
}
function ondrag(evt)
{
if (isshot && $(this).attr("fig") == fignum.Rk)
{
   $("#aoe").show();
   isaoe = false;
   $("#aoeon").html("<span style=\"color: red\">выключен</span>");
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
function onMessage(evt)
{
    writeToScreen('<span style="color: blue;">Сообщение от сервера: ' + evt.data+'</span>');
	mess = JSON.parse(evt.data)
	if (mess.action == 1)
	{
	     phase = mess.phase;
		 player = mess.player;
	     switch(mess.phase)
		 {
		     case 0:
			      if (mess.player != you) 
				  {
				      $("#main").html('Расставьте корабли <input type="button" value="Готово" onclick="displace()">');
				      $("#displacing").fadeIn(1000);
                      draggable($(".prototype"),true);
					  zone = you == 1 ? $("[id^='0:'],[id^='1:'],[id^='2:'],[id^='3:'],[id^='4:']") : $("[id^='9:'],[id^='10:'],[id^='11:'],[id^='12:'],[id^='13:']");
				      draggable(zone,true);
					  droppable(zone,true);
					  zone.css("background-color","#98ff98");
				  }
				  else $("#main").html("Подождите, пока Ваш оппонент расставит корабли");
				  break;
		     case 1:
			      $(".square").css("background-color","#FFFFFF");
			      draggable($(".square"),true);
				  droppable($(".square"),true);
			      if (player == you) 
				  {
				      movepassed = false;
				      isshot = false;
				      $("#main").html('Ваш ход. Передвиньте корабль. <br><input type="button" value="Пропуск" onclick="movepass()"><input id="shot" type="button" value="Сделать Выстрел" onclick="shottrig()">');
					  
				  }
				  else $("#main").html("Подождите. Ход противника.");
			      break;
		     case 4:
			      draggable($(".square"),true);
				  droppable($(".square"),true);
			      if (player == you) $("#main").html('Атакуйте корабль противника.<input type="button" value="'+(movepassed ? 'Сделать ход' : 'Пропуск')+'" onclick="attackpass()">');
				  else $("#main").html("Подождите. Противник атакует.");
			      break;
		     case 2:
			      if (player == you) $("#main").html("Игра окончена. Вы победили. Поздравляем!!!");
				  else $("#main").html("Игра окончена. Вы проиграли. Обидно Вам наверное...");
			      break;
			case 3:
				  tar = [mess.targetx,mess.targety]
				  agr=[mess.agressorx,mess.agressory]
			      $("[id='"+agr[0]+':'+agr[1]+"']").css("background-color","#2a52be");
				  $("[id='"+tar[0]+':'+tar[1]+"']").css("background-color","#ff0033");
			      if (player == you) 
				  {
				  $("#main").html("Подождите ответа противника. Он появится в чате. После этого укажите свой блок.");
				  }
				  else 
				  {
				  $("#main").html("Вас атаковали: выберите блок");
				  block[mess.targetx] = [];
				  block[mess.targetx][mess.targety] = true;
				  asktype = "tar";
				  AddBlock(1,field[tar[0]][tar[1]],[tar[0]],[tar[1]]);
				  $("#answer td").live("click",b_click);
				  $(".square").click(FindBlock);
				  $("#answer").show();
				  break;
				  }
		 }
		onstatus();
	}
	else if (mess.action == 4)
	{
	     if (mess.player == 0) $("#output > div").append("<p class=\"log\">"+mess.message+"<p>");
	     else $("#output > div").append("<p style=\"color: "+(mess.player == you ? "gray" : "black")+"\">"+mess.message+"<p>");
		 $("#output").scrollTop($("#output > div").height());
	}
	else if (mess.action == 3)
	{
	     for (i = 0; i < mess.changex.length; i++)
		 {
		     x = mess.changex[i];
			 y = mess.changey[i];
			 fig = mess.changefig[i];
			 field[x][y] = fig;
			 if (fig != 0)
			 $("[id='"+x+":"+y+"']").attr("fig",fig).css("background-image","url('SBpic/"+figname[fig]+".png')");
			 else $("[id='"+x+":"+y+"']").attr("fig",fig).css("background-image","none");
		 }
	}
	else if (mess.action == 2)
	{
		LogParse(mess.log);
	}
	else if (mess.action == 0)
	{
	     you = mess.you;
	     $("#main").html("Соединено с сервером. Ожидание второго игрока.");
		 SendJSON({action: 2, message: "<? echo $_SESSION['login'];?>"});
	}
}
function onError(evt)
{
    $("#output").append('<p style="color: red;">Ошибка соединения с сервером</p>');
    writeToScreen('<span style="color: red;">Произошла ошибка.</span> ' + evt.data);
	if (!debug) return true;
  }
function SendJSON(mess)
{
    doSend(JSON.stringify(mess));
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
if (isblock(block))
{
$("[id='"+x+":"+y+"']").css("background-color",asktype == "agr" ? "#2a52be" : "#ff0035");
$("#answer td").click(b_click);
}
else
{
block[x][y] = undefined;
isblock(block);
}
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
$("#answer").append("<tr><td height=64 width=64 style=\"background-image: url('SBpic/"+figname[fig]+".png'); text-align: center; color: Aqua;\" n=\""+n+"\"><h2>"+str+"</h2></td></tr>");

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
$("#answer td").die("click");
$(".square").unbind("click");
$("#answer").html("");
$("#answer").hide();
}
window.addEventListener("load", init, false);

</script>
<body>
<div id="input"><input id="text" type="text" size=30 style="background-color: #ffcc99; opacity: 0.9; border: none"></div>
<div id="aoe" style="position: fixed; left: 5px; bottom: 5px; display: none">Площадной эффект ракеты <span id="aoeon">выключен</span>.<br>Нажмите "R" для переключения</div>
<div id="field"><table border>
<?php
for ($i = 0; $i < 14;$i++)
{
echo "<tr>";
for ($j = 0; $j < 14;$j++)
{
echo "<td id=\"$i:$j\" x=$i y=$j class=\"square\"></td>";
}
echo "</tr>";
}
?>
</table>
</div>
<div id="info">
<div id="header" class="well">
<h2>Морской бой</h2>
<div id= "main" >Ожидание соединения с сервером</div>
</div>
<div id="output" ><div></div></div>
</div>
</div>
<div id="displacing"><table>
<?
$name = array('Null', 'AB', 'Av', 'Br', 'Es', 'F', 'Kr', 'KrPl', 'Lk', 'Mn', 'NB', 'Pl', 'Rd', 'Rk', 'Sm', 'St', 'T', 'Tk', 'Tp', 'Tr', 'Unknown', 'Sinking');
$count = array(0,1,1,2,6,2,6,1,2,7,1,4,2,1,1,7,4,6,2,7);
for ($i = 1; $i < 20;$i++)
{
    echo ($i % 2 == 1 ? "<tr>" : "")."<td id=\"prot$i\" width=50 height=50 class=\"prototype\" style = \"background-image:  url('SBpic/".$name[$i].".png'); background-color: #98ff98; background-size:50px 50px; text-align: center; color: white; font-size: 180%; font-weight: bold;\">".$count[$i]."</td>".($i % 2 == 0 ? "</tr>" : "");
}
?>
</table>
</div>
<table id="answer" class="well btn-success"></table>
</body>
</html> 