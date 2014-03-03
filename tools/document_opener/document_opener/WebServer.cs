using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Net.Sockets;
using System.Threading;
using System.Net;
using System.IO;

namespace document_opener
{
    class WebServer
    {
        private TcpListener tcpListener;
        private Thread listenThread;

        public WebServer()
        {
            this.tcpListener = new TcpListener(IPAddress.Any, 103);
            this.listenThread = new Thread(new ThreadStart(ListenForClients));
            this.listenThread.Start();
        }

        public void close()
        {
            listenThread.Abort();
            tcpListener.Stop();
        }

        private void ListenForClients()
        {
            this.tcpListener.Start();

            while (true)
            {
                try
                {
                    //blocks until a client has connected to the server
                    TcpClient client = this.tcpListener.AcceptTcpClient();

                    //create a thread to handle communication 
                    //with connected client
                    Thread clientThread = new Thread(new ParameterizedThreadStart(HandleClientComm));
                    clientThread.Start(client);
                }
                catch (Exception e)
                {
                    //Log.log("Accepting clients", e);
                }
            }
        }

        private void HandleClientComm(object client)
        {
            TcpClient tcpClient = (TcpClient)client;
            try
            {
                IPEndPoint remoteIpEndPoint = tcpClient.Client.RemoteEndPoint as IPEndPoint;
                if (remoteIpEndPoint.Address.ToString() != "127.0.0.1") { tcpClient.Close(); return; }

                NetworkStream clientStream = tcpClient.GetStream();

                StreamReader reader = new StreamReader(clientStream);
                string line = reader.ReadLine();
                if (line.StartsWith("GET "))
                {
                    int i = line.IndexOf(" ", 4);
                    string path = line.Substring(4, i - 4);
                    LinkedList<string> headers = new LinkedList<string>();
                    // read all lines
                    while ((line = reader.ReadLine()).Length > 0)
                        headers.AddLast(line);

                    StreamWriter writer = new StreamWriter(clientStream);
                    writer.WriteLine("HTTP/1.1 200 OK");
                    writer.WriteLine("Connection: close");
                    writer.WriteLine("Content-Transfer-Encoding: 8bit");

                    if (path == "/version")
                    {
                        writer.WriteLine("Content-Type: text/plain");
                        writer.WriteLine("Content-Length: " + DocumentOpener.version.Length);
                        writer.WriteLine();
                        writer.Write(DocumentOpener.version);
                    }
                    else if (path == "/javascript")
                    {
                    }
                    else if (path.StartsWith("/document?"))
                    {
                        string s = path.Substring(10);
                        string[] ss = s.Split(new char[] { '&' });
                        Dictionary<string, string> parameters = new Dictionary<string, string>();
                        for (i = 0; i < ss.Length; ++i)
                        {
                            int j = ss[i].IndexOf('=');
                            parameters.Add(ss[i].Substring(0, j), ss[i].Substring(j + 1));
                        }
                    }

                    writer.Flush();

/*
                    StreamWriter writer = new StreamWriter(clientStream);
                    writer.WriteLine("HTTP/1.1 200 OK");
                    writer.WriteLine("Connection: close");
                    writer.WriteLine("Content-Transfer-Encoding: 8bit");
                    if (path == "/screenshot.jpg")
                    {
                        writer.WriteLine("Content-Type: image/jpeg");
                        MemoryStream memoryStream = ScreenShot.take();
                        writer.WriteLine("Content-Length: " + memoryStream.Length);
                        writer.WriteLine("");
                        writer.Flush();
                        memoryStream.Position = 0;
                        memoryStream.WriteTo(clientStream);
                        memoryStream.Dispose();
                        writer.Flush();
                    }
                    else if (path == "/processes.txt")
                    {
                        writer.WriteLine("Content-Type: text/plain");
                        Process[] processes = Process.GetProcesses();
                        string s = "";
                        foreach (Process p in processes)
                        {
                            string ps = "";
                            try
                            {
                                ps += p.Id;
                                ps += " ";
                                TimeSpan span = (DateTime.Now - p.StartTime);
                                ps += (int)span.TotalSeconds;
                                ps += " ";
                                ps += p.ProcessName;
                                ps += "||";
                                try
                                {
                                    string wmiQuery = string.Format("select * from Win32_Process where ProcessId='{0}'", p.Id);
                                    ManagementObjectSearcher searcher = new ManagementObjectSearcher(wmiQuery);
                                    ManagementObjectCollection retObjectCollection = searcher.Get();
                                    foreach (ManagementObject retObject in retObjectCollection)
                                    {
                                        string[] argList = new string[] { string.Empty, string.Empty };
                                        int returnVal = Convert.ToInt32(retObject.InvokeMethod("GetOwner", argList));
                                        if (returnVal == 0)
                                        {
                                            ps += argList[1] + "\\" + argList[0];
                                        }
                                        ps += "||";
                                        ps += retObject["CommandLine"];
                                    }
                                } catch (Exception ex) {}
                                ps += "\n";
                                s += ps;
                            }
                            catch (Exception err) { }
                        }
                        writer.WriteLine("Content-Length: " + s.Length);
                        writer.WriteLine("");
                        writer.Write(s);
                        writer.Flush();
                    }
                    else if (path.StartsWith("/kill"))
                    {
                        string s = path.Substring(5);
                        int pid = Convert.ToInt32(s);
                        Process[] processes = Process.GetProcesses();
                        Boolean ok = false;
                        foreach (Process p in processes)
                        {
                            if (p.Id == pid)
                            {
                                try
                                {
                                    p.Kill();
                                    ok = true;
                                }
                                catch (Exception err) { ok = false; }
                                break;
                            }
                        }
                        writer.WriteLine("Content-Type: text/plain");
                        writer.WriteLine("Content-Length: 2");
                        writer.WriteLine("");
                        writer.Write(ok ? "OK" : "KO");
                        writer.Flush();
                    }
                    else if (path.StartsWith("/msg."))
                    {
                        string message = path.Substring(5);
                        message = Uri.UnescapeDataString(message);
                        UserMessage.show(message);
                        writer.WriteLine("Content-Type: text/plain");
                        writer.WriteLine("Content-Length: 0");
                        writer.WriteLine("");
                        writer.Flush();
                    }
                    else if (path == "/shutdown")
                    {
                        writer.WriteLine("Content-Type: text/plain");
                        writer.WriteLine("Content-Length: 0");
                        writer.WriteLine("");
                        writer.Flush();
                        //Computer.ShutdownSoft();
                        Computer.ShutdownHard();
                    }
                    else if (path == "/version")
                    {
                        writer.WriteLine("Content-Type: text/plain");
                        writer.WriteLine("Content-Length: " + PNService.Version.version.Length);
                        writer.WriteLine("");
                        writer.Write(PNService.Version.version);
                        writer.Flush();
                    }
                    else if (path == "/info")
                    {
                        string info = "";
                        info += "Drives<br/>";
                        info += "------<br/>";
                        info += Infos.getDrivesInfo();
                        writer.WriteLine("Content-Type: text/plain");
                        writer.WriteLine("Content-Length: "+info.Length);
                        writer.WriteLine("");
                        writer.Write(info);
                        writer.Flush();
                    }
                    else if (path == "/log")
                    {
                        writer.WriteLine("Content-Type: text/plain");
                        writer.WriteLine("Content-Length: " + Log.content.Length);
                        writer.WriteLine("");
                        writer.Write(Log.content);
                        writer.Flush();
                    }
                    else if (path == "/recover_pass")
                    {
                        Computer.RecoverAdminPass();
                        writer.WriteLine("Content-Type: text/plain");
                        writer.WriteLine("Content-Length: 0");
                        writer.WriteLine("");
                        writer.Flush();
                    }
                    else if (path == "/test")
                    {
                        writer.WriteLine("Content-Type: text/plain");
                        string s = "";
                        s += "IP: " + remoteIpEndPoint.Address.ToString() + "\n";
                        s += "Port: " + remoteIpEndPoint.Port + "\n";
                        foreach (string h in headers)
                            s += h + "\n";
                        writer.WriteLine("Content-Length: " + s.Length);
                        writer.WriteLine("");
                        writer.Write(s);
                        writer.Flush();
                    }
                    else
                    {
                        // unknown path
                        writer.WriteLine("Content-Length: 0");
                        writer.WriteLine("");
                        writer.Flush();
                    }*/
                }
                else
                {
                    StreamWriter writer = new StreamWriter(clientStream);
                    writer.WriteLine("Unknown command");
                    writer.Flush();
                }                
                    
                tcpClient.Close();
            }
            catch (Exception e)
            {
                //Log.log("Communicating with client", e);
                try { tcpClient.Close(); }
                catch (Exception e2) { }
            }
        }
    }
}
