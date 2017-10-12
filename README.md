# phpBrute

phpBrute is a threaded web-automation tool. It's main purpose is either pentesting or threaded automation. It's modular and allows anybody with simple PHP cURL coding knowledge to create modules.

Modules will mostly be used from the command line, but can also be loaded directly using phpBrute's ModuleFactory.

```
   phpBrute v0.5
   
      https://github.com/yanikore/phpBrute

   Usage:
   
     -m <name>             the module to be loaded
     -i <path>             a list of input entries
     -x <amount>           the amount of empty input entries
     -s <string>           a string of input entries, seperated by 3 commas: ,,,
     -f <string>           a custom input format
     -o <path>             the output file
     -a <path>             the output file for partial successes
     -t <amount>           the amount of threads to use (default: 1)
     -p <path>             a list of proxies to use
     -r <string>           a string of proxies to use, seperated by a comma
     -k <path>             a list of socks5 proxies to use
     -u <path>             a list of useragents to use

     -d <string>           a delimiter for outputting module data (default: ,)
     -z                    check and remove duplicate entries (slower)
	 
     -h, -help             show this help dialog
     -debug                enable debugging of core features and modules

   Module settings:
   
     --<variable>=<value>  module specific variable
     --<flag>              module specific flag
``` 

### Requirements
- PHP 7 - Thread Safety (ZTS) enabled
- pthreads 3
- cURL
- v8js (recommended for Cloudflare bypass)

For Windows you can have a look at [INSTALL-ENV.Windows.md](INSTALL-ENV.Windows.md).
Right now there's no easy way to get the required installation for Linux or Mac. You'll have to compile most of it yourself.

### Base modules

* **DeathByCaptcha**
	+ Takes a captcha file and solves it using the DeathByCaptcha service.

* **ProxyChecker**
	+ A proxy checker that can differentiate between proxy types.

* **ProxyScraper**
	+ Scrapes a list of URLs for proxies.
	+ Example source list: https://pastebin.com/raw/uPik26B7

* **WebSocketExample**
    + An example of a websocket connection.

### Examples
```
php phpBrute.php -m DeathByCaptcha

php phpBrute.php -m DeathByCaptcha -s captcha.png --dbc-user=yani --dbc-pass=example

php phpBrute.php -m ProxyScraper -i sources.txt -o proxies_unchecked.txt -t 100

php phpBrute.php -m ProxyChecker -i proxies_unchecked.txt -o proxies.txt -t 100

php phpBrute.php -m ProxyChecker -i proxies_unchecked.txt -o proxies_elite_anon.txt -t 100 --proxy-type=l1,l2
```

Enjoy :)
Yani