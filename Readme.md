# XAMPPMultiSite

A script for XAMPP (Windows) that automatically sets up virtual hosts based on the directory structure, where a single directory represents a website. The contents of a directory is the root of a website and its name is the domain name of a website.

NOTE: This script is not intended to be used in a production environment. It is recommended to make a backup of your virtual hosts configuration file and hosts file before using it.

## Usage

### Installation
1. Place the contents of this repository into a folder on your hard drive (for example, `C:\sites`).
2. Open `usr/config.cmd` and ensure that path to your XAMPP installation is correct.
3. Run `install-sites.cmd` to set up virtual hosts. Every folder that is not `usr` yields one virtual host (for example, `example1.local`).
4. Restart Apache using XAMPP Control Panel.
5. Virtual hosts should be up and running. Visit http://example1.local/, http://example2.local/, and http://example3.local/ .

### To remove a website and its virtual host:
1. Run `remove-sites.cmd`. All websites that have a directory will be unregistered.
2. Remove its folder
3. Run `install-sites.cmd` to restore other virtual hosts.
4. Restart Apache.

### To add a new website:
1. Copy its root directory into the folder with `install-sites.cmd`.
2. Rename it if you want to have a different domain name.
3. Run `install-sites.cmd` to add a new website.
4. Restart Apache.

## License

Copyright (C) 2014 Vladimir P.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.