using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Net;
using System.Threading;
using System.Windows.Forms;

namespace document_opener
{
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
                request = (HttpWebRequest)WebRequest.Create("http://" + doc.host + ":" + doc.port + "/dynamic/documents/service/save_file?id=" + doc.document_id);
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
                failure("Error saving file: " + resp, null);
        }

        private void failure(string message, Exception e)
        {
            if (request != null)
                try { request.Abort(); }
                catch (Exception) { }
            if (file_stream != null)
                try { file_stream.Close(); }
                catch (Exception) { }
            if (upload_stream != null)
                try { upload_stream.Close(); }
                catch (Exception) { }
            if (response != null)
                try { response.Close(); }
                catch (Exception) { }
            if (response_stream != null)
                try { response_stream.Close(); }
                catch (Exception) { }
            error = message + (e != null ? ": " + e.Message : "");
        }
    }
}
