# KKuTuCS
University of Seoul - Internet Programming\
Term Project by __Team 6__

## Collaborators
* 2015920003 [gunhoflash](https://github.com/gunhoflash)
* 2017920061 [KimMalsu](https://github.com/KimMalsu)
* 2017920049 [LeeMir](https://github.com/LeeMir)

## Implementation Test
### Terminal (at root of the server)
```
php -S 0.0.0.0:7000
```
### Terminal (at root of the server)
```
php socket_test.php
```
### Web Browser
```
(SERVER IP HERE):7000
```

## Project File Overview
```
/ ┬ libs ┬ KKuTuCSRequest
  │      ├ Request.php (will be deleted)
  │      └ time.php
  ├ public ─ bootstrap ┬ css ─ ...
  │                    └ js ─ ...
  ├ script ┬ jquery-3.3.1.min.js
  │        └ post.js
  ├ views─other.html (will be deleted)
  ├ .gitignore
  ├ favicon.ico
  ├ index.html
  ├ LICENSE
  ├ README.md
  ├ server.php (will be deleted)
  ├ server2.php (will be deleted)
  └ socket_test.php
```

## We refered to:
* PHP
  * Document: [php.net](http://php.net/)
* Websocket
  * Introduction: [NAVER D2](https://d2.naver.com/helloworld/1336)
  * Supported Browsers: [Can I use](https://caniuse.com/#search=websocket)
  * Document: [MDN](https://developer.mozilla.org/ko/docs/Web/API/WebSocket)
  * Example for Simple Communication: [CUELOGIC](https://www.cuelogic.com/blog/php-and-html5-websocket-server-and-client-communication)
  * Example for Multiple clients: [Nolan's Blog](https://www.nolanchou.com/?p=997&fbclid=IwAR2RI43qe_OkmmaCXOUC7wyDw6_lxljrnBctD-i2XVpPF-cn6arA9Uyxads)