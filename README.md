react-smtp-client v0.2
======================

[ReactPHP](https://reactphp.org/) async SMTP client to send a emails like php
mail(). Simple UTF-8 text/plain messages out-of-the-box.

Basic Usage
-----------

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
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
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Google SMTP Server – How to send bulk emails for free
-----------------------------------------------------

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
$loop = \React\EventLoop\Factory::create();

$smtp = new \Shuchkin\ReactSMTP\Client( $loop, 'tls://smtp.gmail.com:465', 'username@gmail.com','password' );

$recipients = ['sergey.shuchkin@gmail.com','example@example.com'];

foreach( $recipients as $to ) {

    $smtp->send('username@gmail.com', $to, 'Test ReactPHP mailer', 'Hello, Sergey!')->then(
        function() use ( $to ) {
            echo 'Message to '.$to.' sent via Google SMTP'.PHP_EOL;
        },
        function ( \Exception $ex ) use ( $to ) {
            echo 'Message to '.$to.' not sent: '.$ex->getMessage().PHP_EOL;
        }
    );
}

$loop->run();
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Google limit for personal SMTP 99 messages per 24 hours.

Using mime/mail class, send mails and attachments
-------------------------------------------------

See https://github.com/shuchkin/simplemail

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ bash
$ composer require shuchkin/simplemail
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ php
$smtp = new \Shuchkin\ReactSMTP\Client( $loop, 'example.com:25', 'username', 'password' );

// setup fabric
$sm = new \Shuchkin\SimpleMail();
$sm->setFrom( 'example@example.com' );
$sm->setTransport( function ( \Shuchkin\SimpleMail $m, $encoded ) use ( $smtp ) {

    $smtp->send( $m->getFromEmail(), $encoded['to'], $encoded['subject'], $encoded['message'], $encoded['headers'] )
        ->then(
            function () {
                echo "\r\nSent mail";
            },
            function ( \Exception $ex ) {
                echo "\r\n" . $ex->getMessage();
            }
        );
});

// send mail
$m->to( ['sergey.shuchkin@gmail.com', 'reactphp@example.com'] )
    ->setSubject('Async mail with ReactPHP')
    ->setText('Async mail sending perfect! See postcard')
    ->attach('image/postcard.jpg')
    ->send();
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Install
-------

The recommended way to install this library is [through
Composer](https://getcomposer.org). [New to
Composer?](https://getcomposer.org/doc/00-intro.md)

This will install the latest supported version:

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ bash
$ composer require shuchkin/react-smtp-client
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Changelog
---------

0.2 (2020-02-19) - basic UTF-8 text/plain messages out-of-the-box, ReactPHP
actual versions in composer.json

0.1.1 (2019-03-12) - Initial release

 
