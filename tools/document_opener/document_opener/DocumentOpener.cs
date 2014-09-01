using System;
using System.Collections.Generic;
using System.Text;

namespace document_opener
{
    public class DocumentOpener
    {
        public static string version = "0.0.4";
        private static WebServer server;
        public static System.Windows.Forms.ApplicationContext app_ctx;
        public static OperationWindow op_win;
        public static System.Threading.Mutex op_mutex;
        public static string app_path;

        static int Main(string[] args)
        {
            app_path = Environment.GetFolderPath(Environment.SpecialFolder.ApplicationData)+"/pn_document_opener";
            if (System.IO.Directory.Exists(app_path))
                RemoveDirectory(app_path);
            if (System.IO.Directory.Exists(app_path)) return 1;
            for (int i = 0; i < 10 && !System.IO.Directory.Exists(app_path); ++i)
            {
                try
                {
                    System.IO.Directory.CreateDirectory(app_path);
                }
                catch (Exception)
                {
                }
            }
            if (!System.IO.Directory.Exists(app_path))
            {
                System.Windows.Forms.MessageBox.Show("Unable to create directory " + app_path, "PN Document Opener - Error", System.Windows.Forms.MessageBoxButtons.OK, System.Windows.Forms.MessageBoxIcon.Error, System.Windows.Forms.MessageBoxDefaultButton.Button1, System.Windows.Forms.MessageBoxOptions.DefaultDesktopOnly);
                return 1;
            }
            op_mutex = new System.Threading.Mutex();
            server = new WebServer();
            op_win = new OperationWindow();
            op_win.Size = new System.Drawing.Size(0,0);
            op_win.Shown += startWin;
            app_ctx = new System.Windows.Forms.ApplicationContext(op_win);
            System.Windows.Forms.Application.Run(app_ctx);
            return 0;
        }

        public static void stop()
        {
            server.close();
            app_ctx.ExitThread();
        }

        public static void RemoveDirectory(string path, int trial = 0)
        {
            if (!System.IO.Directory.Exists(path)) return;
            string[] list;
            string error = "";
            try
            {
                list = System.IO.Directory.GetDirectories(path);
                for (int i = 0; i < list.Length; ++i)
                    RemoveDirectory(list[i]);
            }
            catch (Exception e) { error += "Unable to remove sub-directories: "+e.Message+"\r\n"; }
            bool has_setup = false;
            try
            {
                list = System.IO.Directory.GetFiles(path);
                for (int i = 0; i < list.Length; ++i)
                {
                    try
                    {
                        System.IO.File.SetAttributes(list[i], System.IO.FileAttributes.Normal);
                        System.IO.File.Delete(list[i]);
                    }
                    catch (Exception e) { if (list[i].EndsWith("setup.exe")) has_setup = true; else error += "Unable to remove file " + list[i] + ": " + e.Message + "\r\n"; }
                }
            }
            catch (Exception e) { error += "Unable to list files: " + e.Message+"\r\n";  }
            try
            {
                System.IO.Directory.Delete(path, true);
            }
            catch (Exception e) { error += "Error removing directory: " + e.Message + "\r\n"; }
            if (System.IO.Directory.Exists(path))
            {
                if (trial >= 50)
                {
                    if (!has_setup)
                        System.Windows.Forms.MessageBox.Show("Unable to remove directory " + path+"\r\nErrors encountered are:\r\n"+error, "PN Document Opener - Error", System.Windows.Forms.MessageBoxButtons.OK, System.Windows.Forms.MessageBoxIcon.Error, System.Windows.Forms.MessageBoxDefaultButton.Button1, System.Windows.Forms.MessageBoxOptions.DefaultDesktopOnly);
                    return;
                }
                System.Threading.Thread.Sleep(100);
                RemoveDirectory(path, trial + 1);
            }
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
