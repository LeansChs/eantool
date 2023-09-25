<?php

namespace Eantool\Net\Exception;

use Throwable;

class ConnectException extends Exception {
	private $request_url;
	private $request_param;
	private $response_body;

	public function __construct($message, $code, $url, $param = null, $response_body = null, Throwable $previous = null){
		$message = $message ?: 'remote server connect error';
		$this->request_url = $url;
		$this->request_param = $param;
		$this->response_body = $response_body;
		parent::__construct($message, $code, $previous);
	}

	public function __debugInfo(){
		$debug = parent::__debugInfo();
		$debug['request_url'] = $this->request_url;
		$debug['request_param'] = $this->request_param;
		$debug['response_body'] = $this->response_body;
	}
}