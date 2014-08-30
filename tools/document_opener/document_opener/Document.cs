using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading;
using System.Windows.Forms;
using System.Net;
using System.Diagnostics;

namespace document_opener
{
    class Document
    {
        public Document(string host, int port, string session_name, string session_id, string pn_version, string document_id, string document_version_id, string storage_id, string storage_revision, string filename, bool readOnly)
        {
            this.host = host;
            this.port = port;
            this.session_name = session_name;
            this.session_id = session_id;
            this.pn_version = pn_version;
            this.document_id = document_id;
            this.document_version_id = document_version_id;
            this.storage_id = storage_id;
            this.storage_revision = storage_revision;
            this.filename = filename;
            this.readOnly = readOnly;
            this.thread = new Thread(new ThreadStart(this.run));
            Console.Out.WriteLine("Starting document thread");
            this.thread.Name = "Document";
            this.thread.Start();
            // TODO do not start if we are already downloading a file...
        }

        public string host;
        public int port;
        public string session_name;
        public string session_id;
        public string pn_version;
        public string document_id;
        public string document_version_id;
        public string storage_id;
        public string storage_revision;
        public string filename;
        public bool readOnly;
        public Thread thread;

        public string file_path;
        public bool failed = false;
        public Process process = null;
        public bool opened = false;
        public bool closed = false;
        public System.IO.FileSystemWatcher monitor = null;

        public void run()
        {
            if (System.IO.Directory.Exists(DocumentOpener.app_path + "/" + this.storage_id))
            {
                MessageBox.Show("The file "+this.filename+" is already open.", "File already open", MessageBoxButtons.OK, MessageBoxIcon.Exclamation, MessageBoxDefaultButton.Button1, MessageBoxOptions.DefaultDesktopOnly);
                return;
            }
            DocumentOpener.op_win.Invoke((MethodInvoker)delegate
            {
                DocumentOpener.op_win.text.Text = "Downloading " + this.filename;
                DocumentOpener.op_win.progressBar.Visible = true;
                DocumentOpener.op_win.progressBar.Value = 0;
                DocumentOpener.op_win.Show();
            });
            System.IO.Directory.CreateDirectory(DocumentOpener.app_path + "/" + this.storage_id);
            file_path = DocumentOpener.app_path + "/" + this.storage_id + "/" + this.filename;
            System.IO.File.Create(file_path).Close();
            new DocumentDownload(this);
            while (!failed)
            {
                if (!opened)
                    Thread.Sleep(250);
                else
                {
                    Thread.Sleep(250);
                    break;
                }
            }
            DocumentOpener.op_win.Invoke((MethodInvoker)delegate
            {
                DocumentOpener.op_win.Hide();
            });
            if (failed) return;
            monitor = new System.IO.FileSystemWatcher(DocumentOpener.app_path + "/" + this.storage_id);
            // TODO monitor
            if (process != null)
            {
                process.Exited += app_closed;
                try
                {
                    int code = process.ExitCode;
                    app_closed(null, null);
                }
                catch (InvalidOperationException e)
                {
                    // application still runngin
                    Thread.Sleep(250);
                }
            }
            while (!closed)
            {
                Thread.Sleep(1000);
                if (!closed)
                {
                    try
                    {
                        System.IO.File.Open(file_path, System.IO.FileMode.Open, System.IO.FileAccess.Read, System.IO.FileShare.None).Close();
                        app_closed(null,null);
                    }
                    catch (Exception e) { }
                }
            }
            // TODO watch to see when modified/closed + check if someone wants also to access the document if !readonly, + update lock
        }
        public void app_closed(object sender, System.EventArgs e)
        {
            DocumentOpener.RemoveDirectory(DocumentOpener.app_path + "/" + this.storage_id);
            closed = true;
        }
    }

    class DocumentDownload
    {
        public DocumentDownload(Document doc) {
            this.doc = doc;
            try
            {
                this.file = System.IO.File.OpenWrite(doc.file_path);
                download_request = (HttpWebRequest)WebRequest.Create("http://" + doc.host + ":" + doc.port + "/dynamic/storage/service/get?id=" + doc.storage_id + (doc.storage_revision != null ? "&revision=" + doc.storage_revision : ""));
                download_request.Headers.Add("Cookie: " + doc.session_name + "=" + doc.session_id + "; pnversion=" + doc.pn_version);
                download_request.BeginGetResponse(startReceiveDocument, this);
            }
            catch (Exception e)
            {
                Console.Out.WriteLine("Error starting download: " + e.Message + ": " + e.StackTrace);
                doc.failed = true;
            }
        }

        private Document doc;
        private System.IO.FileStream file;
        private HttpWebRequest download_request;
        private HttpWebResponse download_response;
        private System.IO.Stream stream;
        private byte[] buffer = new byte[65536];
        private int downloaded = 0;

        public void startReceiveDocument(IAsyncResult res)
        {
            try
            {
                download_response = (HttpWebResponse)download_request.EndGetResponse(res);
                DocumentOpener.op_win.Invoke((MethodInvoker)delegate
                {
                    DocumentOpener.op_win.progressBar.Maximum = (int)download_response.ContentLength;
                });
                stream = download_response.GetResponseStream();
                stream.BeginRead(buffer, 0, 65536, downloading, this);
            }
            catch (Exception e)
            {
                Console.Out.WriteLine("Error downloading: " + e.Message + ": " + e.StackTrace);
                doc.failed = true;
            }
        }
        public void downloading(IAsyncResult res)
        {
            try
            {
                int read = stream.EndRead(res);
                if (read > 0)
                {
                    downloaded += read;
                    DocumentOpener.op_win.Invoke((MethodInvoker)delegate
                    {
                        DocumentOpener.op_win.progressBar.Value = downloaded;
                    });
                    file.Write(buffer, 0, read);
                    stream.BeginRead(buffer, 0, 65536, downloading, this);
                }
                else
                {
                    stream.Close();
                    file.Close();
                    if (doc.readOnly) System.IO.File.SetAttributes(doc.file_path, System.IO.FileAttributes.ReadOnly);
                    DocumentOpener.op_win.Invoke((MethodInvoker)delegate
                    {
                        DocumentOpener.op_win.progressBar.Visible = false;
                        DocumentOpener.op_win.text.Text = "Opening file...";
                    });
                    doc.process = Process.Start(doc.file_path);
                    if (doc.process != null)
                    {
                        for (int i = 0; i < 100; i++)
                        {
                            try
                            {
                                System.IO.File.Open(doc.file_path, System.IO.FileMode.Open, System.IO.FileAccess.Read, System.IO.FileShare.None).Close();
                                Thread.Sleep(200);
                            }
                            catch (Exception e) {
                                break;
                            }
                        }
                    }
                    doc.opened = true;
                }
            }
            catch (Exception e)
            {
                Console.Out.WriteLine("Error writing file: " + e.Message + ": " + e.StackTrace);
                doc.failed = true;
            }
        }

    }
}
