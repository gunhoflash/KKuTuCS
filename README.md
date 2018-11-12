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
php -S 0.0.0.0:{PORT}
```
### Terminal (at root of the server)
```
php server.php
```
### Web Browser
```
{SERVER IP HERE}:{PORT}
```

## Project File Overview
```
/ ┬ img ─ kkutucs_char.jpg
  ├ libs ┬ Client.php
  │      ├ GameRoom.php
  │      ├ KKuTuCSRequest.php
  │      ├ socketHandle.php
  │      └ wordCheck.php
  ├ public ┬ css ┬ bootstrap.css
  │        │     └ bootstrap.min.css
  │        └ js ┬ bootstrap.bundle.js
  │             └ bootstrap.bundle.min.js
  ├ script ┬ client.js
  │        └ jquery-3.3.1.min.js
  ├ views ┬ db.sql
  │       ├ KKuTu.php
  │       └ other.html (will be deleted)
  ├ .gitignore
  ├ action_page.html
  ├ favicon.ico
  ├ index.html
  ├ LICENSE
  ├ README.md
  └ server.php
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