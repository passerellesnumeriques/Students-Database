using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading;
using System.Windows.Forms;

namespace document_opener
{
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
                    "/dynamic/documents/service/update_lock?id=" + doc.document_id + "&format=raw",
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
            this.doc = null;
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
        public void request_succeed()
        {
            string resp = System.Text.Encoding.UTF8.GetString(response);
            response = null;
            if (!resp.Equals("OK")) request_failed(resp);
        }
        public void request_failed(string error)
        {
            this.error = error;
            stop();
            MessageBox.Show("Error keeping ownership of file " + doc.filename + ": " + error + "\r\n\r\nYou won't be able to save it anymore, please close it and re-open it.", "PN Document Opener - Error", MessageBoxButtons.OK, MessageBoxIcon.Error, MessageBoxDefaultButton.Button1, MessageBoxOptions.DefaultDesktopOnly);
        }
    }
}
