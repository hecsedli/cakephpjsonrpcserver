# cakephpjsonrpcserver
CakePHP 3.x JSON-RPC server

```php
// config/bootstrap.php

Plugin::load('JSONRPCServer', ['bootstrap' => true]);
```

```php
// Controller
public $components = ["JSONRPCServer.JsonrpcServer" => ["listen" => ["index"]]];
```