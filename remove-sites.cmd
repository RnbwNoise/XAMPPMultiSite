@echo off

REM  XAMPPMultiSite
REM  Copyright (c) 2014 Vladimir P.
REM  
REM  Permission is hereby granted, free of charge, to any person obtaining a copy
REM  of this software and associated documentation files (the "Software"), to deal
REM  in the Software without restriction, including without limitation the rights
REM  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
REM  copies of the Software, and to permit persons to whom the Software is
REM  furnished to do so, subject to the following conditions:
REM  
REM  The above copyright notice and this permission notice shall be included in
REM  all copies or substantial portions of the Software.
REM  
REM  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
REM  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
REM  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
REM  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
REM  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
REM  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
REM  THE SOFTWARE.

call usr\config.cmd
%XAMPP_DIR%\php\php.exe usr\XAMPPMultiSite.php --remove --localhost="%XAMPP_DIR%\htdocs" --sites="%~dp0\" --ignore="usr" --vhosts="%XAMPP_DIR%\apache\conf\extra\httpd-vhosts.conf"

echo.
echo Press any key to exit...
pause > nul