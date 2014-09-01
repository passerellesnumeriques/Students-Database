using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Windows.Forms;
using System.Threading;

namespace document_opener
{
    class DocumentLock
    {
        public DocumentLock(Document doc)
        {
            this.doc = doc;
            new ServerRequest(
                doc.host,
                doc.port,
                "/dynamic/documents/service/lock?id=" + doc.document_id + "&format=raw",
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
            this.doc = null;
        }
        public void unlock_progress(byte[] buffer, int len, int total, int pos) { }
        public void unlock_succeed() { }
        public void unlock_failed(string error) { }
    }
}
