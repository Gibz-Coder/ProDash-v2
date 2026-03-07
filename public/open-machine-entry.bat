@echo off
REM Machine Lot Entry Launcher
REM This batch file opens the machine entry page in a way that allows it to close automatically

REM Get the current directory
set SCRIPT_DIR=%~dp0

REM Open Internet Explorer with the machine entry page
REM The -new flag opens in a new window that can be closed by JavaScript
start "" "C:\Program Files\Internet Explorer\iexplore.exe" "http://localhost:3000/machine-entry"

REM Alternative for modern browsers (uncomment if needed):
REM start "" "chrome.exe" --new-window "http://localhost:3000/machine-entry"
REM start "" "firefox.exe" -new-window "http://localhost:3000/machine-entry"

exit
