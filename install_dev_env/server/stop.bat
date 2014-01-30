@echo off
call versions.bat
server\mysql_%mysql_version%\exe\mysqladmin -u root shutdown
taskkill /IM apache.exe
