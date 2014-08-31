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

        public static int[] possible_ports = new int[] { 127,128,129,130,131,132,133,134,270,271,272,273,274,275,466,467,468,469,470 };

        public WebServer()
        {
            for (int i = 0; i < possible_ports.Length; ++i)
            {
                try
                {
                    this.tcpListener = new TcpListener(IPAddress.Loopback, possible_ports[i]);
                    this.tcpListener.Start();
                    Console.Out.WriteLine("HTTP Server launched on " + IPAddress.Loopback.ToString() + ":" + possible_ports[i]);
                    break;
                }
                catch (System.Net.Sockets.SocketException e)
                {
                    if (e.ErrorCode == 10048) continue;
                    throw e;
                }
            }
            this.listenThread = new Thread(new ThreadStart(ListenForClients));
            this.listenThread.Name = "HTTP Server Listener";
            this.listenThread.Start();
        }

        public void close()
        {
            listenThread.Abort();
            tcpListener.Stop();
        }

        private void ListenForClients()
        {
            while (true)
            {
                try
                {
                    Console.Out.WriteLine("Waiting for clients...");
                    //blocks until a client has connected to the server
                    TcpClient client = this.tcpListener.AcceptTcpClient();
                    Console.Out.WriteLine("New client: " + client.Client.RemoteEndPoint.ToString());

                    //create a thread to handle communication 
                    //with connected client
                    Thread clientThread = new Thread(new ParameterizedThreadStart(HandleClientComm));
                    clientThread.Name = "HTTP Client";
                    clientThread.Start(client);
                }
                catch (Exception)
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
                if (remoteIpEndPoint.Address.ToString() != "127.0.0.1") {
                    Console.Out.WriteLine("Rejected client address: " + remoteIpEndPoint.Address.ToString());
                    tcpClient.Close();
                    return;
                }

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
                    writer.WriteLine("Server: PN Document Opener/"+DocumentOpener.version);

                    if (path == "/version")
                    {
                        writer.WriteLine("Content-Type: text/plain");
                        writer.WriteLine("Content-Length: " + DocumentOpener.version.Length);
                        writer.WriteLine();
                        writer.Write(DocumentOpener.version);
                    }
                    else if (path == "/javascript")
                    {
                        writer.WriteLine("Content-Type: text/javascript");
                        writer.WriteLine("Content-Length: " + JavaScript.js.Length);
                        writer.WriteLine();
                        writer.Write(JavaScript.js);
                    }
                    else if (path == "/frame")
                    {
                        writer.WriteLine("Content-Type: text/html");
                        writer.WriteLine("Content-Length: " + JavaScript.frame.Length);
                        writer.WriteLine();
                        writer.Write(JavaScript.frame);
                    }
                    else
                    {
                        writer.WriteLine("Content-Length: 0");
                        writer.WriteLine("Content-Type: text/plain");
                        writer.WriteLine();
                    }
                    writer.Flush();
                } else if (line.StartsWith("POST ")) {
                    int i = line.IndexOf(" ", 5);
                    string path = line.Substring(5, i - 5);
                    LinkedList<string> headers = new LinkedList<string>();
                    // read all lines
                    int body_size = 0;
                    while ((line = reader.ReadLine()).Length > 0)
                    {
                        if (line.ToLower().StartsWith("content-length:"))
                        {
                            string s = line.Substring(15).Trim();
                            body_size = Int32.Parse(s);
                        }
                        headers.AddLast(line);
                    }
                    
                    // read body
                    string body;
                    if (body_size == 0)
                        body = "";
                    else
                    {
                        char[] buffer = new char[body_size];
                        reader.Read(buffer, 0, body_size);
                        body = new string(buffer);
                    }
                    string[] ss = body.Split(new char[] { '&' });
                    Dictionary<string, string> parameters = new Dictionary<string, string>();
                    for (i = 0; i < ss.Length; ++i)
                    {
                        int j = ss[i].IndexOf('=');
                        string value = ss[i].Substring(j + 1);
                        value = Uri.UnescapeDataString(value);
                        parameters.Add(ss[i].Substring(0, j), value);
                    }

                    StreamWriter writer = new StreamWriter(clientStream);
                    writer.WriteLine("HTTP/1.1 200 OK");
                    writer.WriteLine("Connection: close");
                    writer.WriteLine("Server: PN Document Opener/" + DocumentOpener.version);
                    if (path == "/open_document")
                    {
                        Console.Out.WriteLine("Open Document");
                        new Document(parameters["server"], UInt16.Parse(parameters["port"]), parameters["session_name"], parameters["session_id"], parameters["pn_version"], parameters["doc"], parameters["version"], parameters["id"], parameters.ContainsKey("revision") ? parameters["revision"] : null, parameters["filename"], parameters["readonly"] != "false" ? true : false);
                        writer.WriteLine("Content-Length: 0");
                        writer.WriteLine("Content-Type: text/plain");
                        writer.WriteLine();
                    }
                    else
                    {
                        writer.WriteLine("Content-Length: 0");
                        writer.WriteLine("Content-Type: text/plain");
                        writer.WriteLine();
                    }
                    writer.Flush();
                }
                else
                {
                    StreamWriter writer = new StreamWriter(clientStream);
                    writer.WriteLine("Unknown command");
                    writer.Flush();
                }
                Console.Out.WriteLine("Close client");
                tcpClient.Close();
            }
            catch (Exception)
            {
                try { tcpClient.Close(); }
                catch (Exception) { }
            }
        }
    }
}
