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
}
