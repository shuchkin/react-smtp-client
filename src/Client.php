<?php

namespace Shuchkin\ReactSMTP;

class Client extends \Evenement\EventEmitter implements \React\Socket\ConnectorInterface {
	private $loop;
	private $uri;
	private $connector;
	/* @var \React\Socket\ConnectionInterface $conn */
	private $conn;
	private $buffer;
	private $lines;
	/* @var \React\Promise\Deferred $deffered */
	private $deffered;
	private $username;
	private $password;
	private $queue;
	private $auth;

	public function __construct( \React\EventLoop\LoopInterface $loop, $uri = 25, $username = '', $password = '' ) {
		$this->loop     = $loop;
		$this->uri      = $uri;
		$this->username = $username;
		$this->password = $password;
		$this->auth     = '';
		$this->queue    = [];
		$this->lines    = [];
	}

	public function send( $from, $to, $subject, $message, $headers = [] ) {
		$deffered = new \React\Promise\Deferred();
		$from = strpos($from,'<') === false ? '<' . $from . '>' : $from;
		$lines    = [ 'MAIL FROM: '. $from ];

		if ( is_string( $to ) ) {
			$to = [ $to ];
		}
		foreach( $to as $k => $t ) {
			$t = strpos($t,'<') === false ? '<'.$t.'>' : $t;
			$to[ $k ] = $t;
			$lines[] = 'RCPT TO: '.$t;
		}

		$headers = array_merge([
			'From' => $from,
			'To' => implode(', ', $to),
			'Subject' => $subject
		], $headers);

		$headers_str = '';

		foreach ($headers as $k => $v ) {
			$headers_str .= $k.': '.$v."\r\n";
		}


		$lines[] = 'DATA';
		$lines[] = $headers_str . "\r\n" . $message . "\r\n.";

		$this->processQueue( $deffered, $lines );

		return $deffered->promise();
	}

	private function processQueue( $deffered = null, $lines = null ) {
		if ( $deffered ) {
			$this->queue[] = [ 'deffered' => $deffered, 'lines' => $lines ];
		}
		if ( !$this->connector && !$this->conn ) {
			$this->connect();
			return;
		}
		if ( \count($this->lines) ) {
			return;
		}
		if ( $this->auth !== 'OK' ) {
			$this->lines[] = 'HELO server';
			if ( ! empty( $this->username ) ) {
				$this->lines[] = 'AUTH LOGIN';
				$this->lines[] = base64_encode( $this->username );
				$this->lines[] = base64_encode( $this->password );
			}
			$this->auth = 'LOGIN';
		} else if ( \count( $this->queue ) ) {
			$this->buffer   = '';
			$m              = array_shift( $this->queue );
			$this->lines    = $m['lines'];
			$this->deffered = $m['deffered'];
			if ( isset( $this->listeners['debug'] ) ) {
				$this->emit( 'debug', [ '----------- New message ----------' ] );
			}
//				$this->handleData('');
		} else {
			$this->close();
		}
	}

	public function connect( $uri = null ) {
		if ( $uri ) {
			$this->uri = $uri;
		}
		$this->connector = new \React\Socket\Connector( $this->loop );

		/** @noinspection NullPointerExceptionInspection */
		return $this->connector->connect( $this->uri )->then(
			function ( \React\Socket\ConnectionInterface $conn ) {
				if ( isset( $this->listeners['debug'] ) ) {
					$this->emit( 'debug', [ 'Connected to ' . $this->uri ] );
				}
				$this->buffer = '';

				$conn->on( 'data', [ $this, 'handleData' ] );
				$conn->on( 'end', [ $this, 'handleEnd' ] );
				$conn->on( 'close', [ $this, 'handleClose' ] );
				$conn->on( 'error', [ $this, 'handleError' ] );
				$this->conn = $conn;
				$this->processQueue();
			},
			function ( \Exception $ex ) {
				if ( isset( $this->listeners['debug'] ) ) {
					$this->emit( 'debug', [ $ex->getMessage() ] );
				}
				foreach ( $this->queue as $m ) {
					/** @noinspection PhpUndefinedMethodInspection */
					$m['deffered']->reject( $ex );
				}
				$this->queue = [];
			} );
	}

	public function handleData( $data ) {
		if ( isset( $this->listeners['debug'] ) ) {
			$this->emit( 'debug', [ 'S: ' . trim( $data ) ] );
		}

		$this->buffer .= $data;
		if ( substr( $this->buffer, 3, 1 ) === '-' ) {
			return;
		}
		if ( strpos( $this->buffer, '5' ) === 0 ) {
			$this->close( new \Exception( trim( $this->buffer) )  );
			return;
		}

		if ( strpos( $this->buffer, '250' ) === 0 && !\count($this->lines) ) {
			$this->deffered->resolve( $this->buffer );
			$this->reset();
			$this->processQueue();
		}

		if ( strpos( $this->buffer, '235' ) === 0 ) {
			$this->auth = 'OK';
			$this->processQueue();
		}
		$this->buffer = '';
		if ( \count( $this->lines ) ) {
			$line = array_shift( $this->lines );
			$this->conn->write( $line . "\r\n" );
			if ( isset( $this->listeners['debug'] ) ) {
				$this->emit( 'debug', [ 'C: ' . $line ] );
			}
		}
	}

	/**
	 * @param \Exception|null $reason
	 */
	public function close( $reason = null ) {
		if ( !$reason ) {
			$reason = new \Exception( 'Manually closed' );
		}
		foreach ( $this->queue as $m ) {
			/** @noinspection PhpUndefinedMethodInspection */
			$m['deffered']->reject( $reason );
		}
		$this->buffer = '';
		$this->queue  = [];
		$this->lines  = [];
		$this->conn->close();
		$this->conn = false;
		$this->connector = false;
		$this->auth = false;
	}

	private function reset() {
		$this->buffer   = '';
		$this->lines    = [];
		$this->deffered = false;
	}

	public function handleEnd() {
		if ( isset( $this->listeners['debug'] ) ) {
			$this->emit( 'debug', [ 'Stream end ' . $this->uri ] );
		}
	}

	public function handleClose() {
		if ( isset( $this->listeners['debug'] ) ) {
			$this->emit( 'debug', [ 'Disconnected from ' . $this->uri ] );
		}
		foreach ( $this->queue as $m ) {
			/** @noinspection PhpUndefinedMethodInspection */
			$m['deffered']->reject( new \Exception( 'Disconnected' ) );
		}
		$this->queue = [];
		$this->reset();
	}

	public function handleError( \Exception $ex ) {
		if ( isset( $this->listeners['debug'] ) ) {
			$this->emit( 'debug', ['Error: ' . $ex->getMessage()] );
		}
		/** @noinspection PhpUnhandledExceptionInspection */
		throw $ex;
	}

	public function __destruct() {
		if ( $this->conn ) {
			$this->conn->end( "QUIT\r\n" );
		}
	}
}