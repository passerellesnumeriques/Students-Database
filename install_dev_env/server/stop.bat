@echo off
call versions.bat
server\mysql_%mysql_version%\exe\mysqladmin -u root shutdown
for /f "delims=" %%i in (log\httpd.pid) do set apache_pid=%%i
taskkill /PID %apache_pid% /F
