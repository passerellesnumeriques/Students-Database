@echo off
call versions.bat
cd database
start ..\server\mysql_%mysql_version%\exe\mysqld.exe --datadir="%CD%"
cd ..
cscript start_apache.vbs
