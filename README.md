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