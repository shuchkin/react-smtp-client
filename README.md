# react-smtp-client
[ReactPHP](https://reactphp.org/) async SMTP client to send a simple email.

## Basic Usage
```php
$loop = \React\EventLoop\Factory::create();

$smtp = new \Shuchkin\ReactSMTP\Client( $loop ); // localhost:25

$smtp->send('info@example.org', 'sergey.shuchkin@gmail.com', 'Test ReactPHP mailer', 'Hello, Sergey!')->then(
	function() {
		echo 'Message sent'.PHP_EOL;
	},
	function ( \Exception $ex ) {
		echo 'SMTP error '.$ex->getCode().' '.$ex->getMessage().PHP_EOL;
	}
);

$loop->run();
```
## Using Google SMTP server
```php
$loop = \React\EventLoop\Factory::create();

$smtp = new \Shuchkin\ReactSMTP\Client( $loop, 'tls://smtp.google.com:465', 'username@gmail.com','password' );

$smtp->send('username@gmail.com', 'sergey.shuchkin@gmail.com', 'Test ReactPHP mailer', 'Hello, Sergey!')->then(
	function() {
		echo 'Message sent via Google SMTP'.PHP_EOL;
	},
	function ( \Exception $ex ) {
		echo 'SMTP error '.$ex->getCode().' '.$ex->getMessage().PHP_EOL;
	}
);

$loop->run();
```
Google limit for personal SMTP 99 messages per 24 hours.
## Install

The recommended way to install this library is [through Composer](https://getcomposer.org).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

This will install the latest supported version:

```bash
$ composer require shuchkin/react-smtp-client
```