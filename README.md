CakePHP 3.x JSON-RPC server

Original idea: James Watts https://github.com/jameswatts/cake-jsonrpc


Requirements
------------

* CakePHP 3.6+
* PHP 5.6+

Installation
------------

You can install using [composer](http://getcomposer.org).

```
composer require hecsedli/cakephpjsonrpcserver
```


```php
// config/bootstrap.php

Plugin::load('JSONRPCServer', ['bootstrap' => true]);
```

Implementation
--------------

Add the component to your controller

```php
public $components = ["JSONRPCServer.JsonrpcServer"];
```

```php
public function user($request) {
	if (isset($request->params->userId)) {
		return $this->User->findById($request->params->userId);
	} else {
		throw new Exception('No user ID was specified', 123);
	}
);
```

JSON request

```json
{
	"jsonrpc": "2.0", 
	"method": "user", 
	"params": {
		"userId": 5
	}, 
	"id": "test"
}
```