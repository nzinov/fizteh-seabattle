var EventType = {Pass: 0, Move: 1, Destroy: 2, Explode: 3, Battle: 4, Shot: 5, Capture: 6, Answer: 7, End: 8}
FigName = [ "Unknown ship", "Atomic bomb", "Aircraft carrier", "Fire ship", "Destroyer", "Fort", "Cruiser", "Cruising submarine", "Battleship", "Mine", "Neutron bomb", "Submarine", "Raider", "Rocket", "Aircraft", "Guard ship", "Torpedo", "Torpedo boat", "Transport", "Minesweeper"];
FigNameM = [ "Unknown ships", "Atomic bombs", "Aircraft carriers", "Fire ships", "Destroyers", "Forts", "Cruisers", "Cruising submarines", "Battleships", "Mines", "Neutron bombs", "Submarines", "Raiders", "Rockets", "Aircrafts", "Guard ships", "Torpedos", "Torpedo boats", "Transports", "Minesweepers"];
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
function LogParse(log)
{
    active = log.player == you;
	suc = (log.win ? "succsesfully" : "without success");
	col = (active && log.win) || (!active && !log.win) ? "green" : "red";
	switch (log.type)
	{
		case EventType.End:
			if (log.win && !active) $("#draw").html("Принять ничью");
			Mes((active ? "You" : "Your opponent")+" "+(log.win ? "offered a draw game" : "yeilded"),(log.win ? "blue" : (active ? "red": "green")));
			break;
		case EventType.Move:
			if (active) MesSpot("You moved","gray",[log.from.x,log.to.x],[log.from.y,log.to.y],["#99ff99","#3caa3c"]);
			else MesSpot("The adversary moved","blue",[log.from.x,log.to.x],[log.from.y,log.to.y],["#99ff99","#3caa3c"]);
			break;
		case EventType.Destroy:
			if (active) MesSpot("You lost a "+FigName[log.agr],"red",[log.from.x],[log.from.y],["#e32636"]);
			else MesSpot("Your enemy lost his "+FigName[log.agr],"green",[log.from.x],[log.from.y],["#e32636"]);
			break;
		case EventType.Explode:
			if (active) MesSpot("Your "+FigName[log.agr]+" exploded","blue",[log.from.x],[log.from.y],["#ff4d00"]);
			else MesSpot(FigName[log.agr]+" of your adversary exploded","blue",[log.from.x],[log.from.y],["#ff4d00"]);
			break;
		case EventType.Battle:
			MesSpot((active ? "You" : "Your enemy")+" attacked a "+(log.tar_str > 1 ? "block of "+log.tar_str+" "+FigNameM[log.tar] : FigName[log.tar])+" with a "+(log.agr > 0 ? (log.agr_str > 1 ? "block of "+log.agr_str+" "+FigNameM[log.agr]: FigName[log.agr]) : "")+" "+suc,col,[log.from.x,log.to.x],[log.from.y,log.to.y],["#2a52be","#ff0033"]);
			break;
		case EventType.Shot:
			MesSpot((active ? "You" : "The adversary")+" shoted "+suc,col,[log.from.x,log.to.x],[log.from.y,log.to.y],["#2a52be","#ff0033"]);
			break;
		case EventType.Capture:
			MesSpot((active ? "You" : "The opponent of you")+" captured a ship",active ? "green" : "red",[log.from.x,log.to.x],[log.from.y,log.to.y],["blue","green"]);
			break;
		case EventType.Answer:
			MesToChat("<h5>"+(active ? "You" : "Your enemy")+" declared "+(log.agr_str > 1 ? "block of "+log.agr_str+" "+FigNameM[log.agr] : FigName[log.agr])+"</h5>");
			if (!active)
			{
			      fig = field[agr[0]][agr[1]];
				  if (fig != fignum.Pl && fig != fignum.KrPl&& fig != fignum.Tp)
				  {
				  $("[id='"+agr[0]+':'+agr[1]+"']").css("background-color","#2a52be");
				  $("[id='"+tar[0]+':'+tar[1]+"']").css("background-color","#ff0033");
				  block[agr[0]] = [];
				  block[agr[0]][agr[1]] = true;
				  asktype = "agr";
				  AddBlock(1,field[agr[0]][agr[1]],[agr[0]],[agr[1]]);
				  $("#answer td").live("click",b_click);
				  $(".square").click(FindBlock);
				  $("#answer").show();
				  }
			}
			break;
	}
    $("#output").scrollTop($("#output > div").height());
}