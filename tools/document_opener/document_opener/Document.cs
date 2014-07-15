using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading;

namespace document_opener
{
    class Document
    {
        public Document(string host, int port, string session_id, string document_id)
        {
            this.host = host;
            this.port = port;
            this.session_id = session_id;
            this.document_id = document_id;
            this.read_only = read_only;
            this.thread = new Thread(new ThreadStart(this.run));
        }

        private string host;
        private int port;
        private string session_id;
        private string document_id;
        private Thread thread;

        public void run()
        {
            // TODO retrieve document info / ask if read only / ask which version...
            // TODO download document
            // TODO start application/open document
            // TODO watch to see when modified/closed + check if someone wants also to access the document if !readonly
        }
    }
}
