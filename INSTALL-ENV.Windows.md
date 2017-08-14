# phpBrute Windows "Install"

You can use these steps to setup a full environment for phpBrute usage. 

### Requirements
- PHP 7 - Thread Safety (ZTS) enabled
- pthreads 3
- cURL
- v8js (recommended for Cloudflare bypass)

### Install
You can use the PHP 7.0 builds by Jan-e located here: https://www.apachelounge.com/viewtopic.php?t=6359
Make sure to grab one **without "nts"**. (nts stands for Not Thread Safe, we don't need that)
Choose 64bit if possible. It should improve threading.
Ex: *php-7.0.22-Win32-VC14-x64.zip*

Extract the contents of the archive to a directory like C:\php
Make sure the following files are present:
- /ext/php_curl.dll
- /ext/php_pthreads.dll
- /ext/php_v8js.dll
- pthreadvc2.dll
- v8.dll

### Configuration
Copy and/or rename **php.ini-development** to **php.ini**
Open it up in a text editor. Search for the following line. Make sure it says "ext" and is uncommented by removing the ";":
```
;extension_dir = "ext"
```

Now search for:
```
extension=
```

Most of the extensions will be commented out and some will be missing
Make sure the following extensions are added or uncommented:
```
extension=php_bz2.dll
extension=php_curl.dll
extension=php_fileinfo.dll
extension=php_ftp.dll
extension=php_gd2.dll
extension=php_intl.dll
extension=php_imap.dll
extension=php_mbstring.dll
extension=php_exif.dll
extension=php_openssl.dll
extension=php_sockets.dll
extension=php_pthreads.dll
extension=php_v8js.dll
```

Save the file. It's possible that you might need to edit this configuration for future- or third-party modules.

### Add to PATH
To make the "php" command usable from anywhere on your Windows machine, you need to add it to your PATH environment variable.
An easy way is to run the following command (Start->Run):
```
%windir%\System32\rundll32.exe sysdm.cpl,EditEnvironmentVariables
```
Add the location of your php folder and you should be able to run the following command to verify:
```
php -v
```

### Notes
PHP needs the VS2015 redistributable (VC14):
https://www.microsoft.com/en-us/download/details.aspx?id=48145