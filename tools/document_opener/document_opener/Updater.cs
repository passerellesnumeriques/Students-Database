using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading;
using System.Windows.Forms;
using System.Diagnostics;

namespace document_opener
{
    class Updater
    {
        public static void update(string host, int port, string session_name, string session_id, string pn_version)
        {
            DocumentOpener.op_mutex.WaitOne();
            DocumentOpener.op_win.Invoke((MethodInvoker)delegate
            {
                DocumentOpener.op_win.text.Text = "Downloading latest version...";
                DocumentOpener.op_win.progressBar.Visible = true;
                DocumentOpener.op_win.progressBar.Value = 0;
                DocumentOpener.op_win.TopLevel = true;
                DocumentOpener.op_win.Show();
            });
            DownloadUpdate download = new DownloadUpdate(host, port, session_name, session_id, pn_version);
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
            DocumentOpener.stop();
        }
    }

    class DownloadUpdate
    {
        public DownloadUpdate(string host, int port, string session_name, string session_id, string pn_version)
        {
            if (System.IO.File.Exists(DocumentOpener.app_path + "/setup.exe"))
                System.IO.File.Delete(DocumentOpener.app_path + "/setup.exe");
            System.IO.File.Create(DocumentOpener.app_path + "/setup.exe").Close();

            try
            {
                this.file = System.IO.File.OpenWrite(DocumentOpener.app_path + "/setup.exe");
            }
            catch (Exception e)
            {
                failure("Error writing in file", e);
                return;
            }
            request = new ServerRequest(
                host,
                port,
                "/dynamic/documents/service/download_document_opener",
                session_name,
                session_id,
                pn_version,
                "downloading update",
                download_progress,
                download_succeed,
                download_failed
            );
            while (error == null && !launched)
                Thread.Sleep(250);
            request = null;
        }

        public string error = null;
        public bool launched = false;
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
            DocumentOpener.op_win.Invoke((MethodInvoker)delegate
            {
                DocumentOpener.op_win.progressBar.Visible = false;
                DocumentOpener.op_win.text.Text = "Launching update...";
            });
            try { Process.Start(DocumentOpener.app_path + "/setup.exe", "/silent"); }
            catch (Exception) { /* TODO ? */ }
            launched = true;
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
            try { System.IO.File.Delete(DocumentOpener.app_path + "/setup.exe"); }
            catch (Exception) { }
        }

    }

}
