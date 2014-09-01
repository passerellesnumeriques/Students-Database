using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading;
using System.Windows.Forms;
using System.Net;

namespace document_opener
{
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
                failure("Error " + action, e);
            }
        }
        public void downloading(IAsyncResult res)
        {
            int read;
            try { read = stream.EndRead(res); }
            catch (Exception e) { failure("Error " + action, e); return; }
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
            }
            catch (Exception) { }
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
}
