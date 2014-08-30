using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace document_opener
{
    public class DocumentOpener
    {
        public static string version = "0.0.1";
        public static bool exit = false;
        public static System.Windows.Forms.ApplicationContext app_ctx;
        public static OperationWindow op_win;
        public static string app_path;

        static int Main(string[] args)
        {
            app_path = Environment.GetFolderPath(Environment.SpecialFolder.ApplicationData)+"/pn_document_opener";
            if (System.IO.Directory.Exists(app_path))
                RemoveDirectory(app_path);
            System.IO.Directory.CreateDirectory(app_path);
            WebServer server = new WebServer();
            op_win = new OperationWindow();
            op_win.Size = new System.Drawing.Size(0,0);
            op_win.Shown += startWin;
            app_ctx = new System.Windows.Forms.ApplicationContext(op_win);
            System.Windows.Forms.Application.Run(app_ctx);
            return 0;
        }

        public static void RemoveDirectory(string path)
        {
            string[] list = System.IO.Directory.GetDirectories(path);
            for (int i = 0; i < list.Length; ++i)
                RemoveDirectory(list[i]);
            list = System.IO.Directory.GetFiles(path);
            for (int i = 0; i < list.Length; ++i)
            {
                try {
                    System.IO.File.SetAttributes(list[i], System.IO.FileAttributes.Normal);
                    System.IO.File.Delete(list[i]);
                } catch (Exception e) { }
            }
            try
            {
                System.IO.Directory.Delete(path, true);
            } catch (Exception e) { }
        }

        static void startWin(object sender, EventArgs e)
        {
            op_win.Hide();
            op_win.Shown -= startWin;
            op_win.Size = new System.Drawing.Size(425,100);
            op_win.StartPosition = System.Windows.Forms.FormStartPosition.Manual;
            op_win.Location = new System.Drawing.Point(System.Windows.Forms.Screen.PrimaryScreen.Bounds.Width / 2 - 212, System.Windows.Forms.Screen.PrimaryScreen.Bounds.Height / 2 - 50);
        }
    }
}
