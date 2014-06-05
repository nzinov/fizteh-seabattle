using System;
using System.Collections.Generic;
using System.Text;
using System.Net.Sockets;
using System.Net;
using Newtonsoft.Json;
namespace SeaBattleServer
{
    static class Program
    {
        /// <summary>
        /// The main entry point for the application.
        /// </summary>
        [STAThread]
        static void Main()
        {
			Program.Games = new Dictionary<int, Game>();
			System.IO.TextReader file = new System.IO.StreamReader(Environment.GetEnvironmentVariable("OPENSHIFT_DATA_DIR")+"dump.bin", true);
			string[] f = file.ReadToEnd().Split(new string[]{"№"},StringSplitOptions.RemoveEmptyEntries);
			foreach (string el in f)
			{
				string[] s = el.Split('@');
				Game cur = JsonConvert.DeserializeObject<Game>(s[1]);
				Program.Games.Add(int.Parse(s[0]),cur);
			}
			file.Close();
			try
			{
				WebSocket.Start();
			}
			catch (Exception e)
			{
                SaveGame(e);
			}
			Log("Start");
			AppDomain.CurrentDomain.ProcessExit += new EventHandler(OnExit);
        }
        public static System.Collections.Generic.Dictionary<int, Game> Games;

		public static void OnExit(object sender, EventArgs e)
		{
			Log("It works");
			SaveGame(new Exception("Handling exit"));
			Log("It works");
		}
        public static void SaveGame(Exception e)
        {
            Log(e.ToString());
            System.IO.TextWriter writer = new System.IO.StreamWriter(Environment.GetEnvironmentVariable("OPENSHIFT_DATA_DIR") + "dump.bin", false);
            string buf = "";
            foreach (int i in Program.Games.Keys)
            {
                buf += i.ToString() + "@" + JsonConvert.SerializeObject(Program.Games[i]) + "№";
            }
            writer.Write(buf);
            writer.Close();
        }
        public static void Log(string mes)
        {
            System.IO.TextWriter log = new System.IO.StreamWriter(Environment.GetEnvironmentVariable("OPENSHIFT_DIY_LOG_DIR") + "errors.log", true);
            log.WriteLine(mes);
            log.Close();
        }
    }
}
