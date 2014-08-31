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
            this.thread.Name = "Document";
            this.thread.Start();
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
        public DocumentLock doc_lock = null;
        public Process process = null;
        public bool opened = false;
        public bool closed = false;
        public System.IO.FileSystemWatcher monitor = null;
        public long last_change = 0;

        public void run()
        {
            if (System.IO.Directory.Exists(DocumentOpener.app_path + "/" + this.storage_id))
            {
                MessageBox.Show("The file "+this.filename+" is already open.", "File already open", MessageBoxButtons.OK, MessageBoxIcon.Exclamation, MessageBoxDefaultButton.Button1, MessageBoxOptions.DefaultDesktopOnly);
                return;
            }
            DocumentOpener.op_mutex.WaitOne();
            DocumentOpener.op_win.Invoke((MethodInvoker)delegate
            {
                DocumentOpener.op_win.text.Text = "";
                DocumentOpener.op_win.progressBar.Visible = false;
                DocumentOpener.op_win.TopLevel = true;
                DocumentOpener.op_win.Show();
            });
            if (!readOnly)
            {
                DocumentOpener.op_win.Invoke((MethodInvoker)delegate
                {
                    DocumentOpener.op_win.text.Text = "Asking to edit "+filename;
                });
                doc_lock = new DocumentLock(this);
                if (doc_lock.error != null)
                {
                    DocumentOpener.op_win.Invoke((MethodInvoker)delegate
                    {
                        DocumentOpener.op_win.Hide();
                    });
                    DocumentOpener.op_mutex.ReleaseMutex();
                    MessageBox.Show(doc_lock.error, "Error", MessageBoxButtons.OK, MessageBoxIcon.Error, MessageBoxDefaultButton.Button1, MessageBoxOptions.DefaultDesktopOnly);
                    return;
                }
            }
            DocumentDownload download = new DocumentDownload(this);
            last_change = System.IO.File.GetLastWriteTime(file_path).Ticks;
            DocumentOpener.op_win.Invoke((MethodInvoker)delegate
            {
                DocumentOpener.op_win.Hide();
            });
            DocumentOpener.op_mutex.ReleaseMutex();
            if (download.error != null)
            {
                MessageBox.Show(download.error, "Error", MessageBoxButtons.OK, MessageBoxIcon.Error, MessageBoxDefaultButton.Button1, MessageBoxOptions.DefaultDesktopOnly);
                return;
            }
            download = null;
            monitor = new System.IO.FileSystemWatcher();
            monitor.Path = DocumentOpener.app_path + "/" + this.storage_id;
            monitor.NotifyFilter = System.IO.NotifyFilters.CreationTime | System.IO.NotifyFilters.LastAccess | System.IO.NotifyFilters.LastWrite | System.IO.NotifyFilters.Size | System.IO.NotifyFilters.FileName;
            //monitor.NotifyFilter = System.IO.NotifyFilters.LastWrite;
            //monitor.Filter = this.filename;
            monitor.Changed += docChanged;
            monitor.Renamed += docChanged;
            monitor.Created += docChanged;
            monitor.Deleted += docChanged;
            monitor.EnableRaisingEvents = true;
            if (process != null)
            {
                process.Exited += app_closed;
                try
                {
                    int code = process.ExitCode;
                    app_closed(null, null);
                }
                catch (InvalidOperationException)
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
                        break;
                    }
                    catch (Exception) {
                        if (doc_lock != null && doc_lock.updater.error != null)
                        {
                            readOnly = true;
                            monitor.Changed -= docChanged;
                            monitor.EnableRaisingEvents = false;
                            monitor.Dispose();
                            if (process != null && !process.HasExited)
                            {
                                for (int i = 0; i < 10 && !process.HasExited; i++)
                                {
                                    try { process.Kill(); }
                                    catch (Exception) { }
                                    Thread.Sleep(500);
                                }
                            }
                            app_closed(null, null);
                            break;
                        }
                    }
                }
            }
            while (!closed) Thread.Sleep(100);
            if (doc_lock != null)
                doc_lock.unlock();
        }
        public void docChanged(object source, System.IO.FileSystemEventArgs e)
        {
            //Console.Out.WriteLine("Change: " + e.Name + " / " + e.ChangeType.ToString());
            if (e.Name == this.filename && e.ChangeType != System.IO.WatcherChangeTypes.Deleted)
            {
                long time = System.IO.File.GetLastWriteTime(file_path).Ticks;
                if (time > last_change)
                    saveFile();
            }
            //else
            //    Console.Out.WriteLine("Ignore change of " + e.Name);
        }
        public void app_closed(object sender, System.EventArgs e)
        {
            if (!readOnly)
            {
                monitor.Changed -= docChanged;
                monitor.EnableRaisingEvents = false;
                monitor.Dispose();
                long time = System.IO.File.GetLastWriteTime(file_path).Ticks;
                if (time > last_change)
                    saveFile();
            }
            DocumentOpener.RemoveDirectory(DocumentOpener.app_path + "/" + this.storage_id);
            closed = true;
        }
        private void saveFile()
        {
            DocumentOpener.op_mutex.WaitOne();
            DocumentOpener.op_win.Invoke((MethodInvoker)delegate
            {
                DocumentOpener.op_win.text.Text = "Saving file " + filename;
                DocumentOpener.op_win.progressBar.Value = 0;
                DocumentOpener.op_win.progressBar.Visible = true;
                DocumentOpener.op_win.TopLevel = true;
                DocumentOpener.op_win.Show();
            });
            string tmp_path = DocumentOpener.app_path + "/" + this.storage_id + "/tmp_save";
            System.IO.FileInfo tmp_info;
            long time;
            try
            {
                time = System.IO.File.GetLastWriteTime(file_path).Ticks;
                for (int i = 0; i < 10; i++)
                {
                    try { System.IO.File.Copy(file_path, tmp_path, true); break; }
                    catch (Exception e)
                    {
                        if (i == 9) throw e;
                        Thread.Sleep(100);
                    }
                }
                tmp_info = new System.IO.FileInfo(tmp_path);
            }
            catch (Exception e)
            {
                DocumentOpener.op_win.Invoke((MethodInvoker)delegate
                {
                    DocumentOpener.op_win.Hide();
                });
                DocumentOpener.op_mutex.ReleaseMutex();
                MessageBox.Show("Error saving file: unable to read content of the file (" + e.Message + ")", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error, MessageBoxDefaultButton.Button1, MessageBoxOptions.DefaultDesktopOnly);
                return;
            }
            DocumentOpener.op_win.Invoke((MethodInvoker)delegate
            {
                DocumentOpener.op_win.progressBar.Maximum = (int)tmp_info.Length;
            });
            last_change = time;
            DocumentUpload up = new DocumentUpload(this, tmp_path, tmp_info);
            try
            {
                System.IO.File.Delete(tmp_path);
            }
            catch (Exception) { }
            DocumentOpener.op_win.Invoke((MethodInvoker)delegate
            {
                DocumentOpener.op_win.Hide();
            });
            DocumentOpener.op_mutex.ReleaseMutex();
            if (up.error != null)
                MessageBox.Show(up.error, "PN Document Opener - Error while saving to server", MessageBoxButtons.OK, MessageBoxIcon.Error, MessageBoxDefaultButton.Button1, MessageBoxOptions.DefaultDesktopOnly);
            else
            {
                DocumentOpener.op_win.Invoke((MethodInvoker)delegate
                {
                    var item = new NotifyIcon();
                    item.Visible = true;
                    item.Icon = System.Drawing.SystemIcons.Information;
                    item.ShowBalloonTip(3000, "PN Documents", "File "+filename+" successfully saved to the server.", ToolTipIcon.Info);
                    new System.Threading.Timer((TimerCallback)delegate
                    {
                        DocumentOpener.op_win.Invoke((MethodInvoker)delegate
                        {
                            item.Visible = false;
                            item.Dispose();
                        });
                    }, null, 3000, System.Threading.Timeout.Infinite);
                });
            }
        }
    }

    class ServerRequest
    {
        public ServerRequest(string host, int port, string url, string session_name, string session_id, string pn_version, string action, progress_callback onprogress, success_callback onsuccess, error_callback onerror)
        {
            this.action = action;
            this.onprogress = onprogress;
            this.onsuccess = onsuccess;
            this.onerror = onerror;
            try
            {
                download_request = (HttpWebRequest)WebRequest.Create("http://" + host + ":" + port + url);
                download_request.Headers.Add("Cookie: " + session_name + "=" + session_id + "; pnversion=" + pn_version);
                download_request.BeginGetResponse(startReceive, this);
            }
            catch (Exception e)
            {
                failure("Error connecting to http://" + host, e);
                return;
            }
        }

        public delegate void progress_callback(byte[] buffer, int len, int total, int pos);
        public delegate void success_callback();
        public delegate void error_callback(string error);

        private progress_callback onprogress;
        private success_callback onsuccess;
        private error_callback onerror;
        private string action;
        private HttpWebRequest download_request;
        private HttpWebResponse download_response;
        private System.IO.Stream stream;
        private byte[] buffer = new byte[65536];
        private int total;
        private int downloaded = 0;

        public void startReceive(IAsyncResult res)
        {
            try
            {
                download_response = (HttpWebResponse)download_request.EndGetResponse(res);
                total = (int)download_response.ContentLength;
                downloaded = 0;
                onprogress(null, 0, total, 0);
                stream = download_response.GetResponseStream();
                stream.BeginRead(buffer, 0, 65536, downloading, this);
            }
            catch (Exception e)
            {
                failure("Error "+action, e);
            }
        }
        public void downloading(IAsyncResult res)
        {
            int read;
            try { read = stream.EndRead(res); }
            catch (Exception e) { failure("Error "+action, e); return; }
            if (read > 0)
            {
                downloaded += read;
                onprogress(buffer, read, total, downloaded);
                try { stream.BeginRead(buffer, 0, 65536, downloading, this); }
                catch (Exception e) { failure("Error " + action, e); }
                return;
            }
            try
            {
                stream.Close(); stream = null;
            } catch (Exception) {}
            onsuccess();
        }

        public void cancel()
        {
            if (stream != null)
                try { stream.Close(); }
                catch (Exception) { }
            if (download_request != null)
                try { download_request.Abort(); }
                catch (Exception) { }
        }

        private void failure(string message, Exception e)
        {
            cancel();
            onerror(message + ": " + e.Message);
        }
    }

    class DocumentDownload
    {
        public DocumentDownload(Document doc) {
            DocumentOpener.op_win.Invoke((MethodInvoker)delegate
            {
                DocumentOpener.op_win.text.Text = "Downloading " + doc.filename;
                DocumentOpener.op_win.progressBar.Visible = true;
                DocumentOpener.op_win.progressBar.Value = 0;
            });
            System.IO.Directory.CreateDirectory(DocumentOpener.app_path + "/" + doc.storage_id);
            doc.file_path = DocumentOpener.app_path + "/" + doc.storage_id + "/" + doc.filename;
            System.IO.File.Create(doc.file_path).Close();

            this.doc = doc;
            try
            {
                this.file = System.IO.File.OpenWrite(doc.file_path);
            }
            catch (Exception e)
            {
                failure("Error writing in file", e);
                return;
            }
            request = new ServerRequest(
                doc.host,
                doc.port,
                "/dynamic/storage/service/get?id=" + doc.storage_id + (doc.storage_revision != null ? "&revision=" + doc.storage_revision : ""),
                doc.session_name,
                doc.session_id,
                doc.pn_version,
                "downloading " + doc.filename,
                download_progress,
                download_succeed,
                download_failed
            );
            while (error == null)
            {
                if (!doc.opened)
                    Thread.Sleep(250);
                else
                {
                    Thread.Sleep(250);
                    break;
                }
            }
            request = null;
        }

        public string error = null;
        private Document doc;
        private System.IO.FileStream file;
        private ServerRequest request;

        public void download_progress(byte[] buffer, int len, int total, int pos)
        {
            DocumentOpener.op_win.Invoke((MethodInvoker)delegate
            {
                DocumentOpener.op_win.progressBar.Maximum = total;
                DocumentOpener.op_win.progressBar.Value = pos;
            });
            if (len > 0)
            {
                try
                {
                    file.Write(buffer, 0, len);
                }
                catch (Exception e)
                {
                    failure("Error writing to file", e);
                    return;
                }
            }
        }

        public void download_succeed()
        {
            try
            {
                file.Close(); file = null;
            }
            catch (Exception) { }
            if (doc.readOnly)
                try { System.IO.File.SetAttributes(doc.file_path, System.IO.FileAttributes.ReadOnly); }
                catch (Exception) { }
            DocumentOpener.op_win.Invoke((MethodInvoker)delegate
            {
                DocumentOpener.op_win.progressBar.Visible = false;
                DocumentOpener.op_win.text.Text = "Opening file...";
            });
            try { doc.process = Process.Start(doc.file_path); }
            catch (Exception) { /* TODO ? */ }
            if (doc.process != null)
            {
                for (int i = 0; i < 100; i++)
                {
                    try
                    {
                        System.IO.File.Open(doc.file_path, System.IO.FileMode.Open, System.IO.FileAccess.Read, System.IO.FileShare.None).Close();
                        Thread.Sleep(200);
                    }
                    catch (Exception)
                    {
                        break;
                    }
                }
            }
            doc.opened = true;
        }

        public void download_failed(string error)
        {
            failure(error, null);
        }

        private void failure(string message, Exception e)
        {
            error = message + (e != null ? ": " + e.Message : "");
            if (request != null) request.cancel();
            if (file != null)
                try { file.Close(); }
                catch (Exception) { }
            try { DocumentOpener.RemoveDirectory(DocumentOpener.app_path + "/" + doc.storage_id); }
            catch (Exception) { }
        }

    }

    class DocumentLock
    {
        public DocumentLock(Document doc)
        {
            this.doc = doc;
            new ServerRequest(
                doc.host,
                doc.port,
                "/dynamic/documents/service/lock?id=" + doc.document_id+"&format=raw",
                doc.session_name,
                doc.session_id,
                doc.pn_version,
                "locking file " + doc.filename,
                request_progress,
                request_succeed,
                request_failed
            );
            while (error == null && !locked) Thread.Sleep(50);
            if (error == null)
                updater = new DocumentLockUpdater(doc);
        }

        private Document doc;
        private byte[] response = null;
        private bool locked = false;
        public string error = null;
        public DocumentLockUpdater updater;

        public void request_progress(byte[] buffer, int len, int total, int pos)
        {
            if (response == null) response = new byte[total];
            if (len > 0) Buffer.BlockCopy(buffer, 0, response, pos - len, len);
        }
        public void request_succeed()
        {
            string resp = System.Text.Encoding.UTF8.GetString(response);
            response = null;
            if (resp.Equals("OK"))
                locked = true;
            else
                error = "The file is already being edited by " + resp; // TODO ask for notification
        }
        public void request_failed(string error)
        {
            this.error = error;
        }

        public void unlock()
        {
            updater.stop();
            new ServerRequest(
                doc.host,
                doc.port,
                "/dynamic/documents/service/unlock?id=" + doc.document_id,
                doc.session_name,
                doc.session_id,
                doc.pn_version,
                "unlocking file",
                unlock_progress,
                unlock_succeed,
                unlock_failed
            );
        }
        public void unlock_progress(byte[] buffer, int len, int total, int pos) { }
        public void unlock_succeed() { }
        public void unlock_failed(string error) { }
    }

    class DocumentLockUpdater
    {
        public DocumentLockUpdater(Document doc)
        {
            this.doc = doc;
            new Thread(new ThreadStart(run)).Start();
        }

        private Document doc;
        private bool exit = false;
        public string error = null;

        public void run()
        {
            while (!exit)
            {
                new ServerRequest(
                    doc.host,
                    doc.port,
                    "/dynamic/documents/service/update_lock?id=" + doc.document_id+"&format=raw",
                    doc.session_name,
                    doc.session_id,
                    doc.pn_version,
                    "updating lock",
                    request_progress,
                    request_succeed,
                    request_failed
                );
                for (int i = 0; i < 30; i++)
                    if (exit) break;
                    else Thread.Sleep(1000);
            }
        }

        public void stop()
        {
            exit = true;
        }

        private byte[] response = null;
        public void request_progress(byte[] buffer, int len, int total, int pos)
        {
            if (len == 0) response = new byte[total];
            else Buffer.BlockCopy(buffer, 0, response, pos - len, len);
        }
        public void request_succeed() {
            string resp = System.Text.Encoding.UTF8.GetString(response);
            response = null;
            if (!resp.Equals("OK")) request_failed(resp);
        }
        public void request_failed(string error) {
            this.error = error;
            stop();
            MessageBox.Show("Error keeping ownership of file "+doc.filename+": "+error+"\r\n\r\nYou won't be able to save it anymore, please close it and re-open it.", "PN Document Opener - Error", MessageBoxButtons.OK, MessageBoxIcon.Error, MessageBoxDefaultButton.Button1, MessageBoxOptions.DefaultDesktopOnly);
        }
    }

    class DocumentUpload
    {
        public DocumentUpload(Document doc, string tmp_filename, System.IO.FileInfo tmp_file)
        {
            try
            {
                file_stream = System.IO.File.OpenRead(tmp_filename);
            }
            catch (Exception e)
            {
                failure("Unable to open file to save it to the server", e);
                return;
            }
            try
            {
                request = (HttpWebRequest)WebRequest.Create("http://" + doc.host + ":" + doc.port + "/dynamic/documents/service/save_file?id="+doc.document_id);
                request.Headers.Add("Cookie: " + doc.session_name + "=" + doc.session_id + "; pnversion=" + doc.pn_version);
                request.ContentLength = tmp_file.Length;
                request.Method = "POST";
                request.ContentType = "application/octet-stream";
                upload_stream = request.GetRequestStream();
            }
            catch (Exception e)
            {
                failure("Error connecting to http://" + doc.host, e);
                return;
            }
            upload();
            try
            {
                request.BeginGetResponse(startReceive, this);
            }
            catch (Exception e)
            {
                failure("Error after uploading file", e);
                return;
            }
            while (error == null && !saved) Thread.Sleep(100);
        }

        private HttpWebRequest request;
        private System.IO.Stream file_stream;
        private System.IO.Stream upload_stream;
        private HttpWebResponse response;
        private System.IO.Stream response_stream;
        private byte[] buffer = new byte[4096];
        private int downloaded = 0;

        public string error = null;
        public bool saved = false;

        public void upload()
        {
            byte[] buffer = new byte[65536];
            int done = 0;
            do
            {
                int read;
                try { read = file_stream.Read(buffer, 0, buffer.Length); }
                catch (Exception e)
                {
                    failure("Error reading file", e);
                    return;
                }
                if (read == 0) break;
                try
                {
                    upload_stream.Write(buffer, 0, read);
                }
                catch (Exception e)
                {
                    failure("Error uploading file", e);
                    return;
                }
                done += read;
                DocumentOpener.op_win.Invoke((MethodInvoker)delegate
                {
                    DocumentOpener.op_win.progressBar.Value = done;
                });
            } while (true);
            try { file_stream.Close(); }
            catch (Exception) { }
            file_stream = null;
            try { upload_stream.Close(); }
            catch (Exception) { }
            upload_stream = null;
        }

        public void startReceive(IAsyncResult res)
        {
            try
            {
                response = (HttpWebResponse)request.EndGetResponse(res);
                response_stream = response.GetResponseStream();
                response_stream.BeginRead(buffer, 0, buffer.Length, downloading, this);
            }
            catch (Exception e)
            {
                failure("Error receiving response from server after upload", e);
            }
        }
        public void downloading(IAsyncResult res)
        {
            int read = 0;
            try { read = response_stream.EndRead(res); }
            catch (Exception e) { failure("Error receving response from server after saving file", e); return; }
            if (read > 0)
            {
                downloaded += read;
                if (downloaded < buffer.Length)
                {
                    try { response_stream.BeginRead(buffer, downloaded, buffer.Length - downloaded, downloading, this); }
                    catch (Exception e) { failure("Error receving response from server after saving file", e); }
                    return;
                }
            }
            try
            {
                response_stream.Close();
                response_stream = null;
            }
            catch (Exception) { }

            string resp = System.Text.Encoding.UTF8.GetString(buffer, 0, downloaded);
            if (resp.Equals("OK"))
                saved = true;
            else
                failure("Error saving file: "+resp, null);
        }

        private void failure(string message, Exception e)
        {
            if (request != null)
                try { request.Abort(); } catch (Exception) {}
            if (file_stream != null)
                try { file_stream.Close(); } catch (Exception) {}
            if (upload_stream != null)
                try { upload_stream.Close(); } catch (Exception) {}
            if (response != null)
                try { response.Close(); } catch (Exception) {}
            if (response_stream != null)
                try { response_stream.Close(); } catch (Exception) {}
            error = message + (e != null ? ": " + e.Message : "");
        }
    }
}
