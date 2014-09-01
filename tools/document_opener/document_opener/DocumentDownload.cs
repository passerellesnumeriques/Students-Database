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
    class DocumentDownload
    {
        public DocumentDownload(Document doc)
        {
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
                for (int i = 0; i < 15; i++)
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
}
