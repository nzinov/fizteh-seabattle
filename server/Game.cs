using System;
using System.Collections.Generic;
using System.Text;
using Newtonsoft.Json;

namespace SeaBattleServer
{
    class IllegalActionException : Exception
    {
        public IllegalActionException(string s)
            : base(s)
        {
        }
    }
    class Game
    {
        class FromClient
        {
            public byte action;
            public string message;
            public PhaseType phase;
            public bool pass;
            public bool isshot;
            public bool isaoe;
            public byte fromx;
            public byte fromy;
            public byte tox;
            public byte toy;
            public byte[] blockx;
            public byte[] blocky;
            public FigType blocktype;
            public byte blockstrength;
            public byte[,] field;
        }
        class ToClient
        {
            public byte you;
            public byte action;
            public byte player;
            public PhaseType phase;
            public List<int> changex, changey, changefig;
            public string message;
            public byte targetx, targety, agressorx, agressory;
            public Event log;
        }
        public enum FigType
        {
            Null,
            AB,
            Av,
            Br,
            Es,
            F,
            Kr,
            KrPl,
            Lk,
            Mn,
            NB,
            Pl,
            Rd,
            Rk,
            Sm,
            St,
            T,
            Tk,
            Tp,
            Tr,
            Unknown,
            Sinking
        }
        static int[] FigStrength = { 0, 0, 729, 0, 216, 0, 324, 0, 486, 0, 0, 0, 288, 0, 0, 144, 0, 96, 0, 64 };
        public struct Fig
        {
            public FigType fig;
            public byte player;
            public Fig(FigType f, byte p)
            {
                fig = f;
                player = p;
            }
            static public bool operator ==(Fig a, Fig b)
            {
                return a.fig == b.fig && (a.fig == FigType.Null || a.player == b.player);
            }
            static public bool operator !=(Fig a, Fig b)
            {
                return a.fig != b.fig || (a.fig != FigType.Null && a.player != b.player);
            }
            public override bool Equals(object o)
            {
                Fig a = (Fig)o;
                return this.fig == a.fig && (this.fig == FigType.Null || this.player == a.player);
            }
            public override int GetHashCode()
            {
                return this.fig.GetHashCode() + this.player.GetHashCode();
            }
        }
        public enum EventType
        {
            Pass,
            Move,
            Destroy,
            Explode,
            Battle,
            Shot,
            Capture,
            Answer,
            End,
        }
        public class Event
        {
            public EventType type;
            public byte player;
            public Cord from;
            public Cord to;
            public FigType agr;
            public FigType tar;
            public byte agr_str = 1;
            public byte tar_str = 1;
            public bool win;

			public override string ToString()
			{
				return "<"+String.Join(":", (int)type, player, from, to, (int)agr, (int)tar, agr_str, tar_str, win);
			}
        }
		public List<Event> event_log = new List<Event>();
        public bool started;
        public int game_id;
        bool movepassed;
        public bool inverted = false;
        WebSocket[] players;
		List<WebSocket> viewers;
		public struct Cord
        {
            public byte x;
            public byte y;
            public Cord(byte x, byte y)
            {
                this.x = x;
                this.y = y;
            }
			public override string ToString()
			{
				return String.Join(":", this.x, this.y);
			}
        }

		public class Block
		{
			public byte str;
			public FigType type;
			public byte[] xs;
			public byte[] ys;
			public Block(byte str, FigType type, byte[] xs, byte[] ys)
			{
				this.str = str;
				this.type = type;
				this.xs = xs;
				this.ys = ys;
			}
		}


        class ChangesBuffer
        {
            private Game game;
            private List<int> bufferx;
            private List<int> buffery;
            private List<int>[] bufferfig = new List<int>[3];
            public void Add(int x, int y, Fig fig)
            {
                bufferx.Add(x);
                buffery.Add(y);
				bufferfig[0].Add((int)fig.fig+fig.player*100);
                if (fig.fig == FigType.Null)
                {
                    bufferfig[1].Add((int)FigType.Null);
                    bufferfig[2].Add((int)FigType.Null);
                }
                else if (fig.player == 0)
                {
                    bufferfig[1].Add((int)FigType.Sinking);
                    bufferfig[2].Add((int)FigType.Sinking);
                }
                else
                {
                    bufferfig[fig.player].Add((int)fig.fig);
                    bufferfig[fig.player == 1 ? 2 : 1].Add((int)FigType.Unknown);
                }
				game.WriteHistory(String.Join(":", x, y, (int)fig.fig, fig.player));
            }
            public ChangesBuffer(Game g)
            {
                game = g;
                bufferx = new List<int>();
                buffery = new List<int>();
                bufferfig[0] = new List<int>();
                bufferfig[1] = new List<int>();
				bufferfig[2] = new List<int>();
            }
            public void Flush()
            {
                game.Send(new ToClient
                {
                    action = 3,
                    changex = bufferx,
                    changey = buffery,
                    changefig = bufferfig[1]
                }, 1);
                game.Send(new ToClient
                {
                    action = 3,
                    changex = bufferx,
                    changey = buffery,
                    changefig = bufferfig[2]
                }, 2);
				game.Send(new ToClient
                {
                    action = 3,
                    changex = bufferx,
                    changey = buffery,
                    changefig = bufferfig[0]
                }, 3);
				bufferx.Clear();
				buffery.Clear();
				bufferfig[0].Clear();
				bufferfig[1].Clear();
				bufferfig[2].Clear();
            }
        }
        ChangesBuffer buffer;


        public class Field
        {
            Game game;
            public Fig[,] f = new Fig[14, 14];
            public Fig this[int x, int y]
            {
                get
                {
                    return f[x, y];
                }
                set
                {
                    if (f[x, y] != value)
                    {
						game.buffer.Add(x, y, value);
                        if (f[x, y].fig == FigType.F)
                        {
                            if (f[x, y].player != 0)
                                game.Forts[f[x, y].player - 1]--;
                            if (value.player != 0)
                                game.Forts[value.player - 1]++;
                            if (f[x, y].player != 0 && game.Forts[f[x, y].player - 1] == 0)
                                game.Loose(f[x, y].player);
                        }
                    }
                    f[x, y] = value;
                }
            }
            public Field(Game g)
            {
                game = g;
            }
        }
        public Field field;


        public enum PhaseType
        {
            Displacing,
            Move,
            Finished,
            Asking,
            Attack
        }
        public PhaseType phase;
        public byte player;
		public Cord[] inask;
		public Block[] blocks;
        public byte[] Forts;
        public int player_offered_draw = 0;
        public Game()
        {
            started = false;
            players = new WebSocket[2];
			viewers = new List<WebSocket>();
            field = new Field(this);
            buffer = new ChangesBuffer(this);
			Forts = new byte[2]{ 2, 2 };
			blocks = new Block[2]{ null, null };
			inask = new Cord[2]{ new Cord(), new Cord() };
			phase = PhaseType.Displacing;
			player = 0;
        }
		public Game(int i) : this()
        {
            this.game_id = i;
			this.history_fname = Environment.GetEnvironmentVariable("OPENSHIFT_DATA_DIR") + "games/" + this.game_id + ".hist";
        }
        public void AddPlayer(WebSocket player, byte p)
        {
            players[p - 1] = player;
            Send(new ToClient
            {
                action = 0,
                you = p
            }, p);
            player.p = (byte)(p - 1);
            player.onmessage = new WebSocket.OnMessage(ProcessRequest);
            player.onerror = new WebSocket.OnError(ConnectionError);
			SendState(players[p-1], p);
        }

		public void AddViewer(WebSocket viewer)
		{
			viewers.Add(viewer);
			viewer.onerror = new WebSocket.OnError(ConnectionError);
			viewer.onmessage = new WebSocket.OnMessage(ProcessViewerRequest);
			Send(new ToClient
            {
                action = 0,
                you = 0
            }, viewer);
			SendState(viewer, 0);
		}

        public void Start()
        {
            started = true;
            new System.Net.WebClient().DownloadString("http://fizteh-seabattle.rhcloud.com/support.php?code=zekfor2967&page=start&id="+game_id);

        }

        public void Displace(byte[,] f, byte p)
        {
            if (phase == PhaseType.Displacing && player != p)
            {
                for (int i = 0; i < 5; i++)
                {
                    for (int j = 0; j < 14; j++)
                    {
                        field[i + (p == 1 ? 0 : 9), j] = new Fig((FigType)f[i, j], p);
                    }
                }
                if (player == 0)
                {
                    PhaseChange(PhaseType.Displacing, p);
                }
                else
                {
					Start();
                    PhaseChange(PhaseType.Move, 1);
                }
            }
            else
                throw new IllegalActionException("Вы уже не можете расставлять корабли");
        }

        private void PhaseChange(PhaseType phaseType, byte p, byte channel = 0)
        {
            phase = phaseType;
            player = p;
			ToClient mes = new ToClient
			{
				action = 1,
				player = p,
				phase = phaseType
			};
			if (phase == PhaseType.Asking)
			{
				mes.agressorx = inask[p - 1].x;
				mes.agressory = inask[p - 1].y;
				mes.targetx = inask[Opponent(p) - 1].x;
				mes.targety = inask[Opponent(p) - 1].y;
			}
			Send(mes, channel);
            if (movepassed && phase != PhaseType.Attack)
                movepassed = false;
            inverted = false;
        }


        public void Move(byte x, byte y, byte nx, byte ny, byte p)
        {
            int dist;
            if (phase == PhaseType.Move && player == p)
            {
                if (IsCord(x) && IsCord(y) && IsCord(nx) && IsCord(ny))
                {
                    if (field[x, y].player == p)
                    {
                        if (field[nx, ny].fig == FigType.Null)
                        {
                            if (field[x, y].fig != FigType.F)
                            {
                                dist = Math.Abs(nx - x) + Math.Abs(ny - y);
                                if (dist != 1)
                                {
									if (!(field[x, y].fig == FigType.T || field[x, y].fig == FigType.Tk))
										throw new IllegalActionException("Слишком далеко");
									bool flag = false;
									if (nx == x)
									{
										if (ny == y+2 && field[x, y+1].fig == FigType.Null)
											flag = true;
										else if (ny == y-2 && field[x, y-1].fig == FigType.Null)
											flag = true;
									}
									if (ny == y)
									{
										if (nx == x+2 && field[x+1, y].fig == FigType.Null)
											flag = true;
										else if (nx == x-2 && field[x-1, y].fig == FigType.Null)
											flag = true;
									}
									if (Math.Abs(nx - x) == 1 && Math.Abs(ny - y) == 1)
										if (field[x, ny].fig == FigType.Null || field[nx, y].fig == FigType.Null)
											flag = true;
									if (!flag)
										throw new IllegalActionException("Невозожно перейти в эту клетку");
								}
                                if (PatronNear(x, y, field[x, y].fig, p))
                                {
                                    if (PatronNear(nx, ny, field[x, y].fig, p))
                                    {
                                        field[nx, ny] = new Fig(field[x, y].fig, p);
                                        field[x, y] = new Fig();
                                        Log(new Event
                                        {
                                            type = EventType.Move,
                                            from = new Cord(x, y),
                                            to = new Cord(nx, ny),
                                            player = p
                                        });
                                        PhaseChange(PhaseType.Attack, p);
                                    }
                                    else
                                        throw new IllegalActionException("Нельзя отходить от патрона");
                                }
                                else
                                    throw new IllegalActionException("Нет патрона рядом");
                            }
                            else
                                throw new IllegalActionException("Вы пытаетесь передвинуть неподвижную фишку");
                        }
                        else
                            throw new IllegalActionException("Нельзя ходить на занятую клетку");
                    }
                    else
                        throw new IllegalActionException("Вы пытаетесь переместить корабль противника");
                }
                else
                    throw new IllegalActionException("Недопустимые координаты");
            }
            else
                throw new IllegalActionException("Вы сейчас не можете перемещаться");
        }
        public void Attack(byte x, byte y, byte nx, byte ny, byte p)
        {
            if (phase == PhaseType.Attack && player == p)
            {
                if (field[x, y].player == p)
                {
                    if (x == nx && y == ny && (field[x, y].fig == FigType.AB || field[x, y].fig == FigType.NB))
                    {
                        ExplodeBomb(x, y, field[x, y].fig == FigType.AB);
                        PhaseChange(PhaseType.Move, p);
                    }
                    else if (Math.Abs(nx - x) + Math.Abs(ny - y) == 1)
                    {
                        if (field[nx, ny].player == 0 && field[nx, ny].fig != FigType.Null)
                        {
                            field[nx, ny] = new Fig(field[nx, ny].fig, p);
                            Log(new Event
                            {
                                type = EventType.Capture,
                                from = new Cord(x, y),
                                to = new Cord(nx, ny),
                                player = p
                            });
                            PhaseChange(PhaseType.Move, Opponent(p));
                        }
                        else if (field[nx, ny].player == Opponent(p))
                        {
                            FigType oppfig = field[nx, ny].fig;
                            FigType fig = field[x, y].fig;

                            if (fig == FigType.Av || fig == FigType.Es || fig == FigType.Kr || fig == FigType.KrPl || fig == FigType.Lk || fig == FigType.Pl || fig == FigType.Rd || fig == FigType.St || fig == FigType.Tk || fig == FigType.Tp || fig == FigType.Tr)
                            {
                                if (oppfig == FigType.F)
                                {
                                    Destroy(nx, ny);
                                    PhaseChange(PhaseType.Move, p);
                                }
                                else if (oppfig == FigType.Rk || oppfig == FigType.Sm)
                                {
                                    Destroy(x, y);
                                    Destroy(nx, ny);
                                    PhaseChange(PhaseType.Move, Opponent(p));
                                }
                                else if (oppfig == FigType.Mn || oppfig == FigType.T || oppfig == FigType.Br)
                                {
                                    if (fig == FigType.Tr)
                                    {
                                        Destroy(nx, ny);
                                        PhaseChange(PhaseType.Move, p);
                                    }
                                    else
                                    {
                                        Destroy(x, y);
                                        Destroy(nx, ny);
                                        PhaseChange(PhaseType.Move, Opponent(p));
                                    }
                                }
                                else if (oppfig == FigType.AB || oppfig == FigType.NB)
                                {
                                    ExplodeBomb(nx, ny, oppfig == FigType.AB);
                                    PhaseChange(PhaseType.Move, Opponent(p));
                                }
                                else
                                {
                                    if (oppfig == FigType.KrPl || oppfig == FigType.Pl || oppfig == FigType.Tp)
                                    {
										if (fig == oppfig)
										{
											EventAttack(x, y, nx, ny, false, p);
											Destroy(x, y);
                                            Destroy(nx, ny);
											PhaseChange(PhaseType.Move, Opponent(p));
										}
										if (oppfig == FigType.Tp || (oppfig == FigType.Pl && fig != FigType.Lk) || (oppfig == FigType.KrPl && (fig == FigType.Kr || fig == FigType.Es || fig == FigType.Rd)))
                                        {
                                            EventAttack(x, y, nx, ny, true, p);
                                            Destroy(nx, ny);
                                            PhaseChange(PhaseType.Move, p);
                                        }
                                        else
                                        {
                                            EventAttack(x, y, nx, ny, false, p);
                                            Destroy(x, y);
                                            PhaseChange(PhaseType.Move, Opponent(p));
                                        }
                                    }
                                    else
                                    {
										blocks[0] = null;
										blocks[1] = null;
										inask[p-1] = new Cord(x, y);
										inask[Opponent(p)-1] = new Cord(nx, ny);
										PhaseChange(PhaseType.Asking, p);
                                    }
                                }
                            }
                            else
                                throw new IllegalActionException("Этим кораблем нельзя атаковать");
                        }
                        else
                            throw new IllegalActionException("Атаковать можно только корабль противника");
                    }
                    else
                        throw new IllegalActionException("Слишком далеко");
                }
                else
                    throw new IllegalActionException("Вы пытаетесь атаковать кораблем противника");
            }
            else
                throw new IllegalActionException("Вы сейчас не можете атаковать");
        }
        public void Shot(byte x, byte y, byte nx, byte ny, byte p, bool isaoe)
        {
            if (phase == PhaseType.Move && player == p)
            {
                if (IsCord(x) && IsCord(y) && IsCord(nx) && IsCord(ny))
                {
                    if (field[x, y].player == p)
                    {
                        if (field[nx, ny].player == Opponent(p) || (isaoe && field[x, y].fig == FigType.Rk && field[nx, ny].fig == FigType.Null))
                        {
                            FigType target = field[nx, ny].fig;
                            FigType agressor = field[x, y].fig;
							if (!PatronNear(x, y, agressor, p))
								throw new IllegalActionException("Нет патрона рядом");
                            if (isaoe && agressor == FigType.Rk && (nx == x || ny == y) && Math.Abs(nx - x + ny - y) <= 2)
                            {
                                Log(new Event
                                {
                                    type = EventType.Explode,
                                    from = new Cord(nx, ny),
                                    agr = FigType.Rk,
                                    player = p
                                });
                                Destroy(x, y);
                                ExplodeRocket(nx, ny, p);
								PhaseChange(PhaseType.Move, p);
                            }
                            else if (agressor == FigType.Br)
                            {
                                if (inverted)
                                    throw new IllegalActionException("Вы уже захватывали корабль в этот ход");
                                field[nx, ny] = new Fig(field[nx, ny].fig, p);
                                Log(new Event
                                {
                                    type = EventType.Shot,
                                    player = p,
                                    win = true,
                                    from = new Cord(x, y),
                                    to = new Cord(nx, ny),
                                    agr = FigType.Br,
                                    tar = target
                                });
                                inverted = true;
								PhaseChange(PhaseType.Move, p);
                            }
                            else if ((agressor == FigType.Sm && (x == nx || y == ny || Math.Abs(x - nx) == Math.Abs(y - ny))) || (agressor == FigType.T && Torpedo(x, y, nx, ny)) || (agressor == FigType.Rk && target != FigType.Null && (nx == x || ny == y) && Math.Abs(nx - x + ny - y) <= 3))
                            {
                                Log(new Event
                                {
                                    type = EventType.Shot,
                                    agr = agressor,
                                    tar = target,
                                    from = new Cord(x, y),
                                    to = new Cord(nx, ny),
                                    win = target != FigType.F,
                                    player = p
                                });
                                if (target == FigType.F)
                                {
                                    PhaseChange(PhaseType.Move, Opponent(p));
                                }
                                else
                                {
									Destroy(nx, ny);
									PhaseChange(PhaseType.Move, p);
                                }
                                Destroy(x, y);
                            }
                            else
                                throw new IllegalActionException("Невозможно попасть в эту клетку");
                        }
                        else
                            throw new IllegalActionException("Стрелять можно только по противнику");
                    }
                    else
                        throw new IllegalActionException("Нельзя стрелять чужим кораблем");
                }
                else
                    throw new IllegalActionException("Неверные координаты");
            }
            else
                throw new IllegalActionException("Вы сейчас не можете стрелять");
        }
        private void AttackAnswering(byte str, FigType type, byte[] b_x, byte[] b_y, byte p)
        {
			if (blocks[p-1] == null && IsBlock(b_x, b_y, str, type, p, inask[p-1].x, inask[p-1].y))
            {
				blocks[p - 1] = new Block(str, type, b_x, b_y);
				if (p != player)
				{
					Cord agr = inask[player - 1];
					Cord tar = inask[Opponent(player) - 1];
					FigType fig = field[agr.x, agr.y].fig;
					FigType oppfig = field[tar.x, tar.y].fig;
					if (fig == FigType.Pl || fig == FigType.Tp || fig == FigType.KrPl)
					{
						if (fig == FigType.Tp || (fig == FigType.Pl && oppfig != FigType.Lk) || (fig == FigType.KrPl && (oppfig == FigType.Kr || oppfig == FigType.Es || oppfig == FigType.Rd)))
						{
							EventBattle(agr.x, agr.y, tar.x, tar.y, fig, type, 1, str, false, player);
							Destroy(agr.x, agr.y);
							PhaseChange(PhaseType.Move, Opponent(player));
						}
						else
						{
							EventBattle(agr.x, agr.y, tar.x, tar.y, 0, type, 0, str, true, player);
							Destroy(tar.x, tar.y);
							PhaseChange(PhaseType.Move, player);
						}
						blocks[0] = null;
						blocks[1] = null;
					}
					else
					{
						Log(new Event
						{
							type = EventType.Answer,
							player = p,
							agr = type,
							agr_str = str
						});
					}
				}
				if (blocks[0] != null && blocks[1] != null)
				{
					AttackBattle();
				}
            }
            else
            {
                throw new IllegalActionException("Неверный блок");
            }
        }
        private void AttackBattle()
        {
			Cord agr = inask[player - 1];
			Cord tar = inask[Opponent(player) - 1];
			Block agrblock = blocks[player - 1];
			Block tarblock = blocks[Opponent(player) - 1];
			int agrstrength = agrblock.str * FigStrength[(int)agrblock.type];
			int tarstrength = tarblock.str * FigStrength[(int)tarblock.type];
			if (agrstrength == tarstrength)
            {
                //ничья
				EventBattle(agr.x, agr.y, tar.x, tar.y, agrblock.type, tarblock.type, agrblock.str, tarblock.str, false, player);
				for (byte j = 0; j < 2; j++)
				{
					for (int i = 0; i < blocks[j].xs.Length; i++)
					{
						Destroy(blocks[j].xs[i], blocks[j].ys[i]);
					}
				}
				PhaseChange(PhaseType.Move, Opponent(player));
            }
			else if (agrstrength > tarstrength)
            {
                //победил спрашивающий
				EventBattle(agr.x, agr.y, tar.x, tar.y, 0, tarblock.type,0, tarblock.str, true, player);
				Destroy(tar.x, tar.y);
				PhaseChange(PhaseType.Move, player);
            }
            else
            {
                //победил отвечающий
				EventBattle(agr.x, agr.y, tar.x, tar.y, agrblock.type, tarblock.type, agrblock.str, tarblock.str, false, player);
				Destroy(agr.x, agr.y);
				PhaseChange(PhaseType.Move, Opponent(player));
            }
			blocks[0] = null;
			blocks[1] = null;
        }

        private bool Torpedo(byte x, byte y, byte nx, byte ny)
        {
            if (!PatronNear(x, y, FigType.T, field[x, y].player))
                throw new IllegalActionException("Нет патрона рядом");
            bool flag = false;
			if (y == ny && Math.Abs(x - nx) <= 4)
            {
                flag = true;
                for (int j = Math.Min(x, nx) + 1; j < Math.Max(x, nx); j++) if (field[j, y].fig != FigType.Null)
                    {
                        flag = false;
                        break;
                    }
            }
			if (x == nx && Math.Abs(y - ny) <= 4)
            {
                flag = true;
                for (int j = Math.Min(y, ny) + 1; j < Math.Max(y, ny); j++) if (field[x, j].fig != FigType.Null)
                    {
                        flag = false;
                        break;
                    }
            }
            if (flag)
                return true;
			return false;
        }

        private void EventShot(byte x, byte y, byte nx, byte ny, bool w, byte p)
        {
            Log(new Event
            {
                type = EventType.Shot,
                from = new Cord(x, y),
                to = new Cord(nx, ny),
                agr = field[x, y].fig,
                tar = field[nx, ny].fig,
                player = p,
                win = w
            });
        }
        private void EventAttack(byte x, byte y, byte nx, byte ny, bool w, byte p)
        {
            Log(new Event
            {
                type = EventType.Battle,
                from = new Cord(x, y),
                to = new Cord(nx, ny),
                agr = (w ? 0 : field[x, y].fig),
                tar = field[nx, ny].fig,
                player = p,
                win = w
            });
        }
        private void EventBattle(byte x, byte y, byte nx, byte ny, FigType a, FigType t, byte a_s, byte t_s, bool w, byte p)
        {
            Log(new Event
            {
                type = EventType.Battle,
                from = new Cord(x, y),
                to = new Cord(nx, ny),
                agr = a,
                tar = t,
                agr_str = a_s,
                tar_str = t_s,
                player = p,
                win = w
            });
        }
        private void Destroy(byte x, byte y)
        {
            Log(new Event
            {
                type = EventType.Destroy,
                from = new Cord(x, y),
                agr = field[x, y].fig,
                player = field[x, y].player
            });
            field[x, y] = new Fig();
        }
        private bool IsBlock(byte[] x, byte[] y, byte str, FigType type, byte p, byte nx, byte ny)
        {
            if (str < 1 || str > 3)
                return false;
            if ((byte)type < 0 || (byte)type > 19)
                return false;
            if (x.Length != str || y.Length != str)
                return false;
            bool Rd = false;
			bool target_in_block = false;
			bool has_enforsed_St = false;
            for (int i = 0; i < str; i++)
            {
                if (x[i] == nx && y[i] == ny)
					target_in_block = true;
                FigType fig = field[x[i], y[i]].fig;
                if ((byte)fig < 0 || (byte)fig > 19)
                    return false;
                if (field[x[i], y[i]].player != p)
                    return false;
                Rd = Rd || fig == FigType.Rd;
            }
			if (!target_in_block)
                return false;
            for (int i = 0; i < str; i++)
            {
				bool is_near = false;
                FigType fig = field[x[i], y[i]].fig;
				if (type != fig)
				{
					if (fig == FigType.St && Rd && type == FigType.Es && !has_enforsed_St)
						has_enforsed_St = true;
					else if (fig == FigType.Rd && (type == FigType.Es || type == FigType.St || type == FigType.Tk || type == FigType.Tr))
						Rd = true;
					else
						return false;

				}
                for (int j = 0; j < str; j++)
                {
					if (Math.Abs(x[i] - x[j])+Math.Abs(y[i] - y[j]) == 1)
                    {
						is_near = true;
                        break;
                    }
                }
				if (!is_near && str != 1)
                    return false;
            }
            return true;
        }
        bool IsCord(int x)
        {
            return x >= 0 && x < 14;
        }
        byte Opponent(byte p)
        {
            return p == 1 ? (byte)2 : (byte)1;
        }
        void Log(string mes)
        {
            Send(new ToClient
            {
                action = 4,
                message = mes
            }, 0);
        }
        void Log(Event evt)
        {
            Send(new ToClient
            {
                action = 2,
                log = evt
            }, 0);
			event_log.Add(evt);
			WriteHistory(evt.ToString());
        }


		System.IO.TextWriter history;
		public string history_fname;
		void WriteHistory(string msg)
		{
			if (history == null)
				history = new System.IO.StreamWriter(history_fname, true);
			history.Write(msg);
			history.Write('\n');
		}

        bool FindSquare(byte x, byte y, FigType f, byte p)
        {
            for (int i = Math.Max(0, x - 1); i < Math.Min(14, x + 2); i++)
            {
                for (int j = Math.Max(0, y - 1); j < Math.Min(14, y + 2); j++)
                {
                    if (field[i, j].player == p && field[i, j].fig == f)
                        return true;
                }
            }
            return false;
        }
        bool PatronNear(byte x, byte y, FigType fig, byte p)
        {
            FigType patron;
            switch (fig)
            {
                case FigType.Mn:
                    patron = FigType.Es;
                    break;
                case FigType.Sm:
                    patron = FigType.Av;
                    break;
                case FigType.Rk:
                    patron = FigType.KrPl;
                    break;
                case FigType.T:
                    patron = FigType.Tk;
                    break;
                default:
                    return true;
            }
            return FindSquare(x, y, patron, p) || FindSquare(x, y, FigType.Tp, p);
        }
        void ExplodeBomb(byte x, byte y, bool isatomic)
        {
            Log(new Event
            {
                type = EventType.Explode,
                from = new Cord(x, y),
                agr = field[x, y].fig,
                player = field[x, y].player
            });
            field[x, y] = new Fig();
            for (byte i = (byte)Math.Max(0, x - 2); i < Math.Min(14, x + 3); i++)
            {
                for (byte j = (byte)Math.Max(0, y - 2); j < Math.Min(14, y + 3); j++)
                {
                    if (field[i, j].fig != FigType.Null)
                    {
                        if (isatomic)
                        {
                            Destroy(i, j);
                        }
                        else
                        {
                            Log(new Event
                            {
                                type = EventType.Destroy,
                                from = new Cord(i, j),
                                agr = FigType.Null,
                                player = field[i, j].player
                            });
                            field[i, j] = new Fig(field[i, j].fig, 0);
                        }
                    }
                }
            }
        }
        private void ExplodeRocket(byte x, byte y, byte p)
		{
			for (byte i = (byte)Math.Max(0, x - 1); i < Math.Min(14, x + 2); i++)
			{
				for (byte j = (byte)Math.Max(0, y - 1); j < Math.Min(14, y + 2); j++)
				{
					if (field[i, j].fig != FigType.Null && field[i, j].fig != FigType.F)
					{
						Destroy(i, j);
					}
				}
			}
        }

        private void Flush()
        {
            buffer.Flush();
			WriteHistory("|");
        }

        private void Send(ToClient mess, byte p)
		{
			if (p == 0 || p == 3)
			{
				foreach (WebSocket el in viewers)
					Send(mess, el);
			}
            if (p == 0)
            {
                for (int i = 0; i < 2; i++)
                {
                    if (players[i] != null)
                        Send(mess, players[i]);
                }
            }
            else if (p == 1 || p == 2)
                if (players[p - 1] != null)
                    Send(mess, players[p - 1]);
        }

		private void Send(ToClient mess, WebSocket target)
        {
            string text = JsonConvert.SerializeObject(mess, Formatting.Indented, new JsonSerializerSettings
            {
                DefaultValueHandling = DefaultValueHandling.Ignore
            });
            target.Send(text);
        }

		private object this_lock = new object();

        public void ProcessRequest(string text, byte p)
		{
			FromClient mess = JsonConvert.DeserializeObject<FromClient>(text);
			lock (this_lock) {
				try {
					p++;
					switch (mess.action) {
					case 0:
						ToClient chat = new ToClient();
						chat.action = 4;
						chat.player = p;
						chat.message = mess.message;
						Send(chat, 0);
						break;
					case 1:
						if (mess.phase != phase)
							throw new IllegalActionException("Это действие невозможно в данный момент");
						switch (mess.phase) {
						case PhaseType.Displacing:
							Displace(mess.field, p);
							break;
						case PhaseType.Move:
							if (mess.pass) {
								if (player == p) {
									movepassed = true;
									Log(new Event
                                        {
                                            type = EventType.Pass,
                                            player = p
                                        }
									);
									PhaseChange(PhaseType.Attack, p);
								}
							} else if (mess.isshot) {
								Shot(mess.fromx, mess.fromy, mess.tox, mess.toy, p, mess.isaoe);
							} else
								Move(mess.fromx, mess.fromy, mess.tox, mess.toy, p);
							break;
						case PhaseType.Attack:
							if (mess.pass) {
								if (player == p) {
									if (movepassed)
										PhaseChange(PhaseType.Move, p);
									else {
										Log(new Event
                                            {
                                                type = EventType.Pass,
                                                player = p
                                            }
										);
										PhaseChange(PhaseType.Move, Opponent(p));
									}
								}

							} else
								Attack(mess.fromx, mess.fromy, mess.tox, mess.toy, p);
							break;
						case PhaseType.Asking:
							AttackAnswering(mess.blockstrength, mess.blocktype, mess.blockx, mess.blocky, p);
							break;
						}
						if (phase != PhaseType.Asking)
							Flush();
						break;
					case 3:
						if (!started)
							break;
						Log(new Event
                        {
                            type = EventType.End,
                            player = p,
                            win = false
                        }
						);
						Loose(p);
						break;
					case 4:
						if (!started)
							break;
						if (player_offered_draw == Opponent(p)) {
							PhaseChange(PhaseType.Finished, 0);
							new System.Net.WebClient().DownloadString("http://fizteh-seabattle.rhcloud.com/support.php?code=zekfor2967&page=draw&id=" + game_id);
							Finish();
						} else
							player_offered_draw = p;
						Log(new Event
                        {
                            type = EventType.End,
                            player = p,
                            win = true
                        }
						);
						break;
					}
				} catch (IllegalActionException ex) {
					Send(new ToClient
                {
                    action = 4,
                    message = "<span style=\"color: red;\">Действие невозможно: </span>" + ex.Message,
                    player = 0
                }, p);
				}
			}
        }

        internal void Loose(byte p)
        {
            PhaseChange(PhaseType.Finished, Opponent(p));
            new System.Net.WebClient().DownloadString("http://fizteh-seabattle.rhcloud.com/support.php?code=zekfor2967&page=win&winner="+Opponent(p)+"&id="+game_id);
			Finish();
        }

		internal void Finish()
		{
			Flush();
			foreach (WebSocket el in players)
				if (el != null)
					el.Finish();
			foreach (WebSocket el in viewers)
				if (el != null)
					el.Finish();
			if (history != null) 
			{
				history.Close();
				history = null;
			}
			new System.Net.WebClient().UploadFile("http://fizteh-seabattle.rhcloud.com/support.php?code=zekfor2967&page=history&id="+game_id, history_fname);
			Program.Games.Remove(game_id);
		}

		internal void SendState(WebSocket client, byte p)
		{
			ToClient mess = new ToClient();
			mess.action = 3;
			mess.changex = new List<int>();
			mess.changey = new List<int>();
			mess.changefig = new List<int>();
			for (int i = 0; i < 14; i++) {
				for (int j = 0; j < 14; j++) {
					Fig fig = field[i, j];
					if (fig.fig != FigType.Null) {
						mess.changex.Add(i);
						mess.changey.Add(j);
						if (p == 0)
							mess.changefig.Add((int)fig.fig + fig.player * 100);
						else {
							if (fig.player == p) {
								mess.changefig.Add((int)fig.fig);
							} else if (fig.player == 0) {
								mess.changefig.Add((int)FigType.Sinking);
							} else {
								mess.changefig.Add((int)FigType.Unknown);
							}
						}
					}
				}
			}
			Send(mess, client);
			PhaseChange(phase, player, p);
			lock (event_log)
			{
				foreach (Event evt in event_log)
					Send(new ToClient
	            {
	                action = 2,
	                log = evt
	            }, client);
			}
		}

		public void ProcessViewerRequest(string text, byte p)
		{
			lock (this_lock) {
				ToClient chat = new ToClient();
				chat.action = 4;
				chat.player = 3;
				chat.message = text;
				Send(chat, 3);
			}
		}

        public void ConnectionError(ushort code, byte p)
		{
			lock (this_lock) {
				if (players[p] == null)
					return;
				players[p].Finish();
				players[p] = null;
				Log("<span style=\"color: red;\">Связь с одним из игроков потеряна.</span> Придется его терпеливо ждать");
			}
        }
    }
}
