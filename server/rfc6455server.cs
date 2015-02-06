using System;
using System.Collections.Generic;
using System.Text;
using System.Linq;
using System.Net.Sockets;
using System.Net;
using System.IO;
using System.Web;
using System.Collections.Specialized;
using System.Text.RegularExpressions;
using System.Threading;
using System.Security.Cryptography;
using Newtonsoft.Json;

namespace SeaBattleServer
{
    class WebSocket
    {
        /// <summary>
        /// Port number to listen on
        /// </summary>
        private const int PortNumber = 8080;
        private const string newline = "\r\n";
        /// <summary>
        /// Socket which awaits connections
        /// </summary>
        private static Socket ListenerSocket;

        /// <summary>
        /// Thread in which we await for incomming connections.
        /// </summary>
        static System.Threading.Thread _serverThread;
        static bool server_active = true;
        /// <summary>
        /// Starts thread with listening socket.
        /// </summary>
        public static void Start()
        {
            System.Threading.ThreadStart ts = new System.Threading.ThreadStart(Listen);
            _serverThread = new System.Threading.Thread(ts);
            _serverThread.Start();
        }

        /// <summary>
        /// Stops listening for connections.
        /// </summary>
        public static void End()
        {
            server_active = false;
            _serverThread.Join();
            ListenerSocket.Close();
            ListenerSocket = null;
        }

        public static void Listen()
        {
            //Start listening
            ListenerSocket = new Socket(AddressFamily.InterNetwork, SocketType.Stream, ProtocolType.Tcp);
            int port = PortNumber;
            string s = Environment.GetEnvironmentVariable("OPENSHIFT_DIY_IP");
            if (s == null)
            {
                port = 8000;
				s = "127.0.0.1";
            }
			EndPoint ep = new IPEndPoint(IPAddress.Parse(s), port);
            ListenerSocket.Bind(ep);
            ListenerSocket.Listen(5);
            while (server_active)
            {
                try
                {
                    Socket client = ListenerSocket.Accept();
                    Thread.Sleep(100);
                    //Receiving clientHandshake
                    if (client.Available == 0)
                    {
                        continue;
                    }
                    string clientHandshake = String.Empty;
                    byte[] buffer = null;
                    int readBytes = 0;
                    do
                    {
                        buffer = new byte[client.Available];
                        readBytes = client.Receive(buffer);
                        clientHandshake += Encoding.UTF8.GetString(buffer);
                    }
                    while (client.Available > 0);

                    string secKey = String.Empty;
                    //Extracting values from headers (key:value)
                    string[] clientHandshakeLines = Regex.Split(clientHandshake, newline);
                    foreach (string hline in clientHandshakeLines)
                    {
                        int valuestart = hline.IndexOf(':') + 2;
                        if (valuestart > 0)
                        {
                            if (hline.StartsWith("Sec-WebSocket-Version"))
                            {
                                if (!hline.Contains("13"))
                                {
                                    client.Send(Encoding.UTF8.GetBytes("HTTP/1.1 400 Bad Request" + newline));
                                    client.Send(Encoding.UTF8.GetBytes("Sec-WebSocket-Version: 13"));
                                    break;
                                }
                            }
                            else if (hline.StartsWith("Sec-WebSocket-Key"))
                                secKey = hline.Substring(valuestart);
                        }
                    }
                    client.Send(Encoding.UTF8.GetBytes("HTTP/1.1 101 Switching Protocols\n"));
                    client.Send(Encoding.UTF8.GetBytes("Upgrade: websocket\n"));
                    client.Send(Encoding.UTF8.GetBytes("Connection: Upgrade\n" ));
                    if (!String.IsNullOrEmpty(secKey))
                    {
                        client.Send(Encoding.UTF8.GetBytes("Sec-WebSocket-Accept: " + CalculateSecurityBody(secKey) + "\n"));
                        client.Send(Encoding.UTF8.GetBytes(newline));
						new WebSocket(client);
                    }
					else
					{
						client.Send(Encoding.UTF8.GetBytes("HTTP/1.1 400 Bad Request" + newline));
                        client.Send(Encoding.UTF8.GetBytes("Sec-WebSocket-Version: 13"));
					}
                }
                catch (Exception e)
                {
                    Program.SaveGame(e);
                }
            }
        }

        public const string solt = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
        public static string CalculateSecurityBody(string Key)
        {
            byte[] hash = SHA1.Create().ComputeHash(Encoding.ASCII.GetBytes(Key + solt));
            return Convert.ToBase64String(hash);
        }
        public Socket client;
        public int version;
        public WebSocket(Socket socket)
        {
            client = socket;
            System.Threading.ThreadStart ts;
            ts = new System.Threading.ThreadStart(ListenSocket);
            _clientThread = new System.Threading.Thread(ts);
            _clientThread.Start();
            onmessage = GameChoose;
        }

        private void GameChoose(string message, byte p)
        {
            if (!Program.CheckSignature(message)) {
                Send("corrupted signature");
                Finish();
                Close();
                return;
            }
            int[] words = message.Split(':')[0].Split('x').Select(x => int.Parse(x)).ToArray();
            if (!Program.Games.ContainsKey(words[0]))
            {
                Program.Games.Add(words[0],new Game(words[0]));
            }
			if (words[1] == -1)
				Program.Games[words[0]].AddViewer(this);
			else
            	Program.Games[words[0]].AddPlayer(this,(byte)words[1]);
        }
        string buff = "";
        public void ListenSocket()
        {
            while (active)
            {
                try
                {
                    byte[] buf = new byte[2];
                    client.Receive(buf, 2, SocketFlags.None);
                    bool fin = (buf[0] & 0x80) == 0x80;
                    if ((buf[0] & 0x70) != 0)
                    {
                        CloseSocket(1002, false);
                    }
                    int opcode = buf[0] & 0x0F;
                    bool masked = (buf[1] & 0x80) == 0x80;
                    ulong length = (ulong)buf[1] & 0x7F;
                    if (length == 126)
                    {
                        buf = new byte[2];
                        client.Receive(buf, 2, SocketFlags.None);
                        Array.Reverse(buf);
                        length = BitConverter.ToUInt16(buf, 0);
                    }
                    else if (length == 127)
                    {
                        buf = new byte[8];
                        client.Receive(buf, 8, SocketFlags.None);
                        Array.Reverse(buf);
                        length = BitConverter.ToUInt64(buf, 0);
                    }
                    byte[] mask = new byte[4];
                    if (masked)
                    {
                        client.Receive(mask, 4, SocketFlags.None);
                    }
                    buf = new byte[length];
                    client.Receive(buf);
                    if (masked)
                    {
                        for (ulong i = 0; i < length; i++)
                        {
                            buf[i] = (byte)(buf[i] ^ mask[i % 4]);
                        }
                    }
                    string rectext;
                    switch (opcode)
                    {
                        case 0x0: //continuation frame
                            rectext = Encoding.UTF8.GetString(buf);
                            if (fin)
                            {
                                onmessage(buff + rectext, p);
                                buff = "";
                            }
                            else
                            {
                                buff += rectext;
                            }
                            break;
                        case 0x1: //text frame
                            rectext = Encoding.UTF8.GetString(buf);
                            if (fin)
                            {
                                onmessage(buff + rectext, p);
                                buff = "";
                            }
                            else
                            {
                                buff += rectext;
                            }
                            break;
                        case 0x2: //binary frame
                            CloseSocket(1003, false);
                            break;
                        case 0x8: //connection close
                            CloseSocket(BitConverter.ToUInt16(buf.Take(2).Reverse().ToArray(), 0), true);
                            break;
                        case 0x9: //ping
                            SendMessage(0xA, buf);
                            break;
                        case 0xA: //pong
                            break;
                    }
                }
                catch (System.Net.Sockets.SocketException)
                {
                    if (onerror != null)
                        onerror(1, p);
                }
                catch (Exception e)
                {
                    Program.SaveGame(e);
                }
            }
        }

        private void CloseSocket(ushort errorcode, bool closedbyclient)
        {
            if (!closedbyclient)
                SendMessage(0x8, BitConverter.GetBytes(errorcode).Reverse().ToArray());
            else
            {
                if (onerror != null) onerror(errorcode, p);
                Close();
            }
        }
        public void Finish()
        {
            CloseSocket(1000, false);
        }
        private void Close()
        {
            active = false;
            _clientThread.Join();
            client.Disconnect(false);
            client.Close();
            client = null;
        }
        public void SendMessage(int oppcode, byte[] data)
        {
            try
            {
                byte[] buf = new byte[2];
                buf[0] = (byte)(0x80 | oppcode);
                if (data.Length < 126)
                {
                    buf[1] = (byte)data.Length;
                    client.Send(buf);
                }
                else if (data.LongLength <= ushort.MaxValue)
                {
                    buf[1] = 126;
                    client.Send(buf);
                    client.Send(BitConverter.GetBytes((ushort)data.LongLength).Reverse().ToArray());
                }
                else
                {
                    buf[1] = 127;
                    client.Send(buf);
                    client.Send(BitConverter.GetBytes((ulong)data.LongLength).Reverse().ToArray());
                }
                client.Send(data);
            }
            catch (System.Net.Sockets.SocketException)
            {
            }
        }
        public void Send(string mes)
        {
        	SendMessage(0x1, Encoding.UTF8.GetBytes(mes));
        }

        System.Threading.Thread _clientThread;
        bool active = true;
        public delegate void OnMessage(string message, byte p);
        public OnMessage onmessage;
        public delegate void OnError(ushort code, byte p);
        public OnError onerror;
        public byte p;
    }
}
