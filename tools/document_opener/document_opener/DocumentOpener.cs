using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace document_opener
{
    public class DocumentOpener
    {
        public static string version = "0.0.1";

        static int Main(string[] args)
        {
            WebServer server = new WebServer();
            while (true) {
                System.Threading.Thread.Sleep(10000);
            };
            return 0;
        }
    }
}
