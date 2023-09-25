<?php
namespace Eantool\Net\Exception;
use Throwable;

/**
 * Class BusinessException
 */
class Exception extends \Exception{
	private $data;

	public function __construct($message = "", $code = 1, $data = null, Throwable $previous = null){
		parent::__construct($message, $code, $previous);
		$this->data = $data;
	}

	/**
	 * get data
	 * @return mixed
	 */
	public function getData(){
		return $this->data;
	}

	/**
	 * convert to JSON
	 * @return false|string
	 */
	public function toJSON(){
		return json_encode([
			'code' => $this->code,
			'msg'  => $this->message,
			'data' => $this->data,
		]);
	}

	/**
	 * convert to string
	 * @return false|string
	 */
	public function __toString(){
		return $this->toJSON();
	}

	/**
	 * support debugInfo
	 * @return array
	 */
	public function __debugInfo(){
		return [
			'code'   => $this->code,
			'message' => $this->message,
			'data' => $this->data,
			'trace_info' => $this->getTraceAsString()
		];
	}
}