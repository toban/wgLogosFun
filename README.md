# wgLogosFun
Tired of that old placeholder logo in mediawiki? Try wgLogosFun!

# Installation

If you are using https://github.com/addshore/mediawiki-docker-dev

in LocalSettings.php
```
$wgLogo = 'http://default.web.mw.localhost:8080/mediawiki/wgLogosFun.php';
```

In your docker-compose.override.yml on the web container

```
    volumes:
     - /home/toan/Documents/dev/wgLogosFun/wgLogosFun.php:/var/www/mediawiki/wgLogosFun.php
```

