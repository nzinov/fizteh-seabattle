using System;
using System.Linq;
using System.Collections.Generic;
using System.Text;
using System.Net.Sockets;
using System.Net;
using System.Security.Cryptography;
using Newtonsoft.Json;
using Mono.Unix;
using Mono.Unix.Native;

namespace SeaBattleServer
{
    static class Program
    {
        /// <summary>
        /// The main entry point for the application.
        /// </summary>
        static string SECRET = "test";
        static string dump_fname, log_fname;
		[STAThread]
        static void Main()
        {
			Program.Games = new Dictionary<int, Game>();
			dump_fname = Environment.GetEnvironmentVariable("OPENSHIFT_DATA_DIR")+"games.dump";
			log_fname = Environment.GetEnvironmentVariable("OPENSHIFT_DIY_LOG_DIR") + "errors.log";
            string data_dir = Environment.GetEnvironmentVariable("OPENSHIFT_DATA_DIR");
            if (data_dir != null) {
				System.IO.TextReader file = new System.IO.StreamReader(data_dir+"secret_token.txt", true);
				SECRET = file.ReadToEnd();
				file.Close();
            }
			if (System.IO.File.Exists(dump_fname))
			{
				System.IO.TextReader file = new System.IO.StreamReader(dump_fname, true);
				string[] f = file.ReadToEnd().Split(new string[]{"\n"},StringSplitOptions.RemoveEmptyEntries);
				foreach (string el in f)
				{
					string[] s = el.Split('@');
					Program.Games.Add(int.Parse(s[0]),JsonConvert.DeserializeObject<Game>(s[1]));
				}
				file.Close();
			}
			try
			{
				WebSocket.Start();
			}
			catch (Exception e)
			{
                Log("Startup fatal error: "+e.ToString());
				System.Environment.Exit(0);
			}
			Log("Start");
			UnixSignal term = new UnixSignal(Signum.SIGTERM);
			term.WaitOne();
			Log("Terminate");
			SaveGame();
			System.Environment.Exit(0);
        }
        public static System.Collections.Generic.Dictionary<int, Game> Games;
        public static void SaveGame(Exception e = null)
        {
            if (e != null)
				Log(e.ToString());
            System.IO.TextWriter writer = new System.IO.StreamWriter(dump_fname, false);
            foreach (int i in Program.Games.Keys.Where(key => key > 0))
            {
				writer.Write(i.ToString() + "@" + JsonConvert.SerializeObject(Program.Games[i]) + "\n");
            }
            writer.Close();
        }
        public static void Log(string mes)
        {
            System.IO.TextWriter log = new System.IO.StreamWriter(log_fname, true);
            log.WriteLine(mes);
            log.Close();
        }
        public static string GetSignature(string data) {
            byte[] hash = SHA1.Create().ComputeHash(Encoding.ASCII.GetBytes(data+Program.SECRET));
            return Convert.ToBase64String(hash);
        }
        public static bool CheckSignature(string sig)
        {
            string[] parts = sig.Split(':');
            return GetSignature(parts[0]) == parts[1];
        }
    }
}
