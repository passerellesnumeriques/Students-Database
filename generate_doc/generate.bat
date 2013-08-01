@echo off
call ../install_dev_env/server/versions.bat
SET PHP_PATH=%CD%\..\install_dev_env\server\php_%php_version%
SET PATH=%PATH%;%PHP_PATH%

echo %PHP_PATH%> %TEMP%\php_path
"%CD%\..\install_dev_env\tools\sed" "s|\\\|\\\/|g" %TEMP%\php_path > %TEMP%\php_path_unix
set /p PHP_PATH_UNIX=<%TEMP%\php_path_unix
del %TEMP%\php_path
del %TEMP%\php_path_unix

rmdir /S /Q "%CD%\..\generated_doc"
mkdir "%CD%\..\generated_doc"

"%CD%\..\install_dev_env\tools\sed" "s/%%PHP_PATH%%/%PHP_PATH_UNIX%/g" %PHP_PATH%\php.ini > "%CD%\..\generated_doc\php.ini"

%PHP_PATH%\php.exe -c "%CD%\..\generated_doc\php.ini" "%CD%\generate.php"

del "%CD%\..\generated_doc\php.ini"

REM pause
