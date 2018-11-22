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
### SQL Data Import (by using Bitnami WAMP)
```
1. Launch Bitnami WAMP Stack.
2. Type this command on WAMP Stack.
> mysql -u root -p
3. Create Database named kkutudb.
> create database kkutudb;
> use kkutudb;
4. Use Source command to import DB in your MySQL.
   For example,
> source c:\path\to\file.sql
5. You should change line 22 in server.php to connect MySQL.
```

## Project File Overview
```
/ ┬ img ┬ kkutucs_char.jpg
  │     └ logo.png
  ├ libs ┬ Client.php
  │      ├ GameRoom.php
  │      ├ KKuTuCSRequest.php
  │      ├ socketHandle.php
  │      └ wordCheck.php
  ├ public ┬ css ┬ bootstrap.css
  │        │     ├ bootstrap.min.css
  │        │     └ signin.css
  │        └ js ┬ bootstrap.bundle.js
  │             ├ bootstrap.bundle.min.js
  │             ├ client.js
  │             └ jquery-3.3.1.min.js
  ├ views ┬ KKuTu.php (will be deleted)
  │       └ other.html (will be deleted)
  ├ .gitignore
  ├ action_page.html (will be deleted)
  ├ favicon.ico
  ├ index.html
  ├ KKuTuDB.sql
  ├ LICENSE
  ├ README.md
  └ server.php
```

## We refered to:
* PHP
  * Document: [php.net](http://php.net/)
* Websocket(server - PHP)
  * Introduction: [NAVER D2](https://d2.naver.com/helloworld/1336)
  * Supported Browsers: [Can I use](https://caniuse.com/#search=websocket)
  * Document: [MDN](https://developer.mozilla.org/ko/docs/Web/API/WebSocket)
  * Example for Simple Communication: [CUELOGIC](https://www.cuelogic.com/blog/php-and-html5-websocket-server-and-client-communication)
  * Example for Multiple clients: [Nolan's Blog](https://www.nolanchou.com/?p=997&fbclid=IwAR2RI43qe_OkmmaCXOUC7wyDw6_lxljrnBctD-i2XVpPF-cn6arA9Uyxads)
* Websocket(client - JS)
  * Document: [MDN](https://developer.mozilla.org/ko/docs/Web/API/WebSocket)
  * Example: [MDN](https://developer.mozilla.org/ko/docs/WebSockets/Writing_WebSocket_client_applications)