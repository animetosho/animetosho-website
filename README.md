# Anime Tosho Website

This repository holds the code for the main *animetosho.org* website. The subdomain *feed.animetosho.org* also uses the same code, but effectively restricted to the *feed* ‘action’ (i.e. *feed.animetosho.org/{uri}* does the same as *animetosho.org/feed/{uri}*).

Note that this code is exclusively targeted at Anime Tosho’s use, and contains hardcoded configuration for such. It could be adopted for other uses, but would require some work (and you’re honestly better off just starting something new than trying to adopt this).

## Requirements

* PHP 7.4 or newer
* MariaDB 10 or newer
* ManticoreSearch (or SphinxSearch)
* nginx (or webserver of your choice, which needs to support `X-Accel-Redirect` headers used by this code)
* PHP modules:
  * GD
  * MySQLi
  * mbstring
  * APCu (optional)
  * ctype
  * [msgpack](https://pecl.php.net/package/msgpack)
  * [zstd](https://pecl.php.net/package/zstd)

## Setup

### Database

Note: all databases should default to the *utf8mb4* character set.

1. The MariaDB instance will need to have the *toto\_repl* and *anidb* database tables in place. These can be found in the [Anime Tosho updater code base](https://github.com/animetosho/animetosho-updater/tree/master/schema), [AniDB TCP API](https://github.com/animetosho/anidb-tcp-client/blob/master/schema.sql) and [HTTP API modules](https://github.com/animetosho/anidb-http-client/blob/master/schema.sql). These databases are updated on the updates server and replicated to the server hosting this script
2. Import the main database schema into a new database on the MariaDB instance from *schema/anito.sql*. I’ll assume the target database is named *anito*
3. Import *schema/toto\_repl-extra.sql* into the *toto\_repl* database, which creates triggers to update index tables in the *anito* database
4. If you imported data above, run *schema/anito-rebuild_scripts.sql* on the *anito* database to rebuild index tables
5. If you’re creating a database user for this script, it’ll need SELECT, INSERT, UPDATE and DELETE permissions on the *anito* database, and SELECT permissions on *toto\_repl* and *anidb* databases
6. (optional) Run *schema/toto_repl-optional.sql* and *schema/anidb-optional.sql* scripts on the *toto\_repl* and *anidb* databases respectively. This drops/nullifies indexes/tables not used by the front end, reducing storage space and improving performance

### Script

1. Edit *includes/config.php* as necessary, including details of the MariaDB instance set up above
2. Edit *pages/includes/listing.php* by finding `mysqli_connect` and put in Manticore/Sphinxsearch connection details
3. You may wish to search the codebase for instances of ‘animetosho.org’ and similar, and edit those
4. Emails are sent via PHP’s `mail` function. If you want those to work, configure PHP’s mail functionality to use whatever mailing setup you prefer
5. Configure webserver to map requests through index.php, and requests to /inc go directly to the /inc folder (see sample nginx config below)
   6. The *pages/admin.php* script proxies requests to the updates server - the webserver will need to be configured to do the proxying via the `/__proxy_updates/` location. This is only necessary if you want to provide a web interface to some backend functions

### Admin user

Once the site is running, you can register an account, then in the database, set *toto_users.accesslevel* of that account to 50 to elevate the account to admin.

### Sample nginx config

```
server {
	listen 80;
	root /var/www/anito;
	location ~ ^/(inc)/ {
		location ~* \.(js|css|html|htm|xml|txt|ico|jpg|gif|jpeg|png)$ {
			gzip_static on;
			expires 7d;
		}
		location ~ \.php$ {
			include snippets/fastcgi-php.conf;
			fastcgi_pass unix:/run/php/php8.4-fpm.sock;
		}
	}
	location ~* \.(js|css|html|htm|xml|txt|ico|jpg|gif|jpeg|png)$ {
		gzip_static on;
		expires 7d;
	}
	location / {
		rewrite ^/([a-z0-9A-Z%]+)/(.*)$ /index.php?action=$1&subaction=$2 break;
		rewrite ^/([a-z0-9A-Z%]+)$ /index.php?action=$1 break;
		rewrite ^/$ /index.php;
		include snippets/fastcgi-php.conf;
		fastcgi_pass unix:/run/php/php8.4-fpm.sock;
	}
	# proxy to updates server backend
	location ~* ^/__proxy_updates/GET/([a-zA-Z0-9]+)/([^/]+)/(.*)$ {
		internal;
		proxy_method GET;
		proxy_pass http://backend-$1/$3?$args;
		proxy_redirect http://$2/ https://$http_host/admin/;
	}
	location ~* ^/__proxy_updates/POST/([a-zA-Z0-9]+)/([^/]+)/(.*)$ {
		internal;
		proxy_method POST;
		proxy_pass http://backend-$1/$3?$args;
		proxy_redirect http://$2/ https://$http_host/admin/;
	}
	error_page 404 = /404.html;
	#error_page 502 503 504  /50x.html;
}
```

Note that this connects to PHP via FPM and `snippets/fastcgi-php.conf` contains default FastCGI parameters included on Debian’s install. You may need to adjust that and the `fastcgi_pass` directive for how to connect to FastCGI, or change this completely if you wish to serve PHP through a different runner.

### Mirror Alterations

The *mirror.animetosho.org* backup site was set up with *includes/db-readonly.php* being included instead of *includes/db.php* in *includes/config.php* (and the corresponding class name change). Also *USE_MINIMAL_FOOTER* is set in the config.

## Code Overview

All requests, excluding those to */inc* and static files in the root, are routed through *index.php*.

* */inc*: contains static resources
* */inc-uncompressed*: contains un-minified versions of some static resources and some generation scripts. This doesn’t need to be uploaded to the webserver
* */includes*: contains core modules used by this script
* */pages*: contains routes/views. The URL is defined as an *action* and *subaction*, for example the URL ‘http://example.com/about/news’ is defined as *action*=about, *subaction*=news. Files are named as *[action].php* or *[action]_[do].php* where *do* is a common parameter for POST requests
* */schema*: contains database schema definitions. It doesn’t need to be present on the webserver

### Backend Duplication

In *pages/includes*, the following files are used for handling file info, and are identical to those in the [updater script](https://github.com/animetosho/animetosho-updater):

* attach-info.php
* finfo-compress.php
* zstd-dict/\*

