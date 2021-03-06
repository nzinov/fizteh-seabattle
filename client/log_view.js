﻿var EventType = {Pass: 0, Move: 1, Destroy: 2, Explode: 3, Battle: 4, Shot: 5, Capture: 6, Answer: 7, End: 8}
FigName = [ "Неизвестный корабль", "Атомная бомба", "Авианосец", "Брандер", "Эсминец", "Форт", "Крейсер", "Крейсерская подводная лодка", "Линкор", "Мина", "Нейтронная бомба", "Подводная лодка", "Рейдер", "Ракета", "Самолет", "Сторож", "Торпеда", "Торпедный катер", "Транспорт", "Тральщик"];
FigNameR = [ "Неизвестного корабля", "Атомной бомбы", "Авианосца", "Брандера", "Эсминца", "Форта", "Крейсера", "Крейсерской подводной лодки", "Линкора", "Мины", "Нейтронной бомбы", "Подводной лодки", "Рейдера", "Ракетой", "Самолете", "Сторожа", "Торпеды", "Торпедного катера", "Транспорта", "Тральщика" ];
FigNameD = [ "Неизвестному кораблю", "Атомной бомбе", "Авианосцу", "Брандеру", "Эсминцу", "Форту", "Крейсеру", "Крейсерской подводной лодке", "Линкору", "Мине", "Нейтронной бомбе", "Подводной лодке", "Рейдеру", "Ракете", "Самолету", "Сторожу", "Торпеде", "Торпедному катеру", "Транспорту", "Тральщику" ];
FigNameV = [ "Неизвестный корабль", "Атомную бомбу", "Авианосец", "Брандер", "Эсминец", "Форт", "Крейсер", "Крейсерскую подводную лодку", "Линкор", "Мину", "Нейтронную бомбу", "Подводную лодку", "Рейдер", "Ракету", "Самолет", "Сторож", "Торпеду", "Торпедный катер", "Транспорт", "Тральщик" ];
FigNameT = [ "Неизвестным кораблем", "Атомной бомбой", "Авианосцем", "Брандером", "Эсминцем", "Фортом", "Крейсером", "Крейсерской подводной лодкой", "Линкором", "Миной", "Нейтронной бомбой", "Подводной лодкой", "Рейдером", "Ракетой", "Самолетом", "Сторожем", "Торпедой", "Торпедным катером", "Транспортом", "Тральщиком" ];
FigNameP = [ "Неизвестном корабле", "Атомной бомбе", "Авианосце", "Брандере", "Эсминце", "Форте", "Крейсере", "Крейсерской подводной лодке", "Линкоре", "Мине", "Нейтронной бомбе", "Подводной лодке", "Рейдере", "Ракете", "Самолете", "Стороже", "Торпеде", "Торпедном катере", "Транспорте", "Тральщике" ];
FigNameM = [ "Неизвестных кораблей", "Атомных бомб", "Авианосцев", "Брандеров", "Эсминцев", "Фортов", "Крейсеров", "Крейсерских подводных лодок", "Линкоров", "Мин", "Нейтронных бомб", "Подводных лодок", "Рейдеров", "Ракет", "Самолетов", "Сторожей", "Торпед", "Торпедных катеров", "Транспортов", "Тральщиков" ];
function MesToChat(message)
{
$("#output > div").append("<p class=\"log\">"+message+"<p>");
}
function Mes(message,color)
{
MesToChat("<span style=\"color: "+color+"\">"+message+"</span>");
}
function MesSpot(message,color,a,b,c)
{
$("<p class=\"log\"><span style=\"color: "+color+"\">"+message+"</span></p>").appendTo($("#output > div")).mouseenter(function() {spotlight(this,[a,b,c]);});;
}
function spotlight(that,arg)
{
old = []
for(i = 0; i < arg[0].length; i++)
{
old[i] = $("[id='"+arg[0][i]+":"+arg[1][i]+"']").css("background-color");
$("[id='"+arg[0][i]+":"+arg[1][i]+"']").css("background-color",arg[2][i]);
}
$(that).mouseleave(function() {reverse([arg[0],arg[1],old]);});
}
function reverse(arg)
{
for(i = 0; i < arg[0].length; i++)
{
$("[id='"+arg[0][i]+":"+arg[1][i]+"']").css("background-color",arg[2][i]);
}
}
function LogParse(log, first, second)
{
    player = (log.player == 1 ? first : second);
	suc = (log.win ? "успешно" : "безуспешно");
	col = (log.win ?  "green" : "red");
	switch (log.type)
	{
		case EventType.End:
			Mes(player+" "+(log.win ? "предложил ничью" :  "сдался"),"blue");
			break;
		case EventType.Move:
			MesSpot(player+" сходил","gray",[log.from.x,log.to.x],[log.from.y,log.to.y],["#99ff99","#3caa3c"]);
			break;
		case EventType.Destroy:
			MesSpot(player+" потерял "+FigNameV[log.agr],"red",[log.from.x],[log.from.y],["#e32636"]);
			break;
		case EventType.Explode:
			MesSpot(FigName[log.agr]+" игрока "+player+" взорвалась","blue",[log.from.x],[log.from.y],["#ff4d00"]);
			break;
		case EventType.Battle:
			MesSpot(player+" "+suc+" атаковал "+(log.agr > 0 ? (log.agr_str > 1 ? "блоком из "+log.agr_str+"-х "+FigNameM[log.agr]: FigNameT[log.agr]) : "")+" "+(log.tar_str > 1 ? "блок из "+log.tar_str+"-х "+FigNameM[log.tar] : FigNameV[log.tar]),col,[log.from.x,log.to.x],[log.from.y,log.to.y],["#2a52be","#ff0033"]);
			break;
		case EventType.Shot:
			MesSpot(player+" "+suc+" произвел выстрел",col,[log.from.x,log.to.x],[log.from.y,log.to.y],["#2a52be","#ff0033"]);
			break;
		case EventType.Capture:
			MesSpot(player+" захватил корабль", col, [log.from.x,log.to.x],[log.from.y,log.to.y],["blue","green"]);
			break;
		case EventType.Answer:
			MesToChat("<h5>"+player+" заявил "+(log.agr_str > 1 ? "блок из "+log.agr_str+"-х "+FigNameM[log.agr] : FigNameV[log.agr])+"</h5>");
			break;
	}
    $("#output").scrollTop($("#output > div").height());
}
