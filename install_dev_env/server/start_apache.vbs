Set sh = CreateObject("WScript.Shell")
sh.Run """%APACHE_PATH%\exe\apache.exe"" -f ""%APACHE_PATH%\conf\httpd.conf""", 0, false
