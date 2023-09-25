<?php
namespace Eantool\Net\Net;

use ArrayAccess;
use JsonSerializable;
use Eantool\Net\Exception\Exception;
use \Exception as RootException;

class TResponse implements JsonSerializable, ArrayAccess{
	public $code;
	public $msg;
	public $data;
	public $forward_url;

	public function __construct($code, $message, $data = null, $forward_url = ''){
		$this->code = $code;
		$this->msg = $message;
		$this->data = $data;
		$this->forward_url = $forward_url;
	}

	/**
	 * 获取数据
	 * @param string $name
	 * @param array $default
	 * @return mixed
	 */
	public function get($name, $default = null){
		return static::data_fetch($this->data, $name, $default);
	}

	/**
	 * 动态获取data里面的数据
	 * @param $name
	 * @return mixed
	 */
	public function __get($name){
		return static::data_fetch($this->data, $name);
	}

	/**
	 * 成功（只包含数据）
	 * @param $data
	 * @return TResponse
	 */
	public static function successData($data){
		return static::success('success', $data);
	}

	/**
	 * 成功返回
	 * @param $message
	 * @param mixed $data
	 * @return TResponse
     */
	public static function success($message = '操作成功', $data = null){
		return new static(RetCode::CODE_SUCCESS, $message, $data);
	}

	/**
	 * 错误返回
	 * @param string $message
	 * @param mixed $data
	 * @param int $code
	 * @return TResponse
	 */
	public static function error($message = '系统繁忙，请稍后重试。', $data = null, $code = RetCode::CODE_UNKNOWN){
		return new static($code, $message, $data);
	}

	/**
	 * check is success
	 * @return bool
	 */
	public function isSuccess(){
		return RetCode::isSuccess($this->code);
	}

	/**
	 * 断言成功
	 * 使用demo：
	 * $rsp->assertSuccess(BusinessException::class, [499]); //只针对 499情况抛出 BusinessException，其他错误码不抛异常
	 * @param string $exception_class 抛出异常类型，缺省为 \Exception
	 * @param number[] $exclude_codes 无需定义为异常的错误码列表
	 * @throws \Exception
	 */
	public function assertSuccess($exception_class = null, $exclude_codes = []){
		$exception_class = $exception_class ?: RootException::class;
		if(!$this->isSuccess() && (!$exclude_codes || !in_array($this->code, $exclude_codes))){
			throw new $exception_class($this->msg, $this->code);
		}
	}

	/**
	 * JSON序列化接口
	 * @return array
	 */
	public function jsonSerialize(){
		return [
			'code'        => $this->code,
			'msg'         => $this->msg,
			'data'        => $this->data,
			'forward_url' => $this->forward_url,
		];
	}

	/**
	 * 从JSON中创建
	 * @param $json
	 * @return TResponse
	 * @throws Exception
	 */
	public static function fromJSON($json){
		if(!is_array($json)){
			throw new Exception('JSON格式错误：'.var_export($json, true));
		}
		if(!isset($json['code'])){
			throw new Exception('JSON字段缺失，必须包含code字段：'.var_export($json, true));
		}
		if(!isset($json['msg'])){
			throw new Exception('JSON字段缺失，必须包含msg字段：'.var_export($json, true));
		}
		return new static($json['code'], $json['msg'], self::data_fetch($json, 'data'), self::data_fetch($json, 'forward_url'));
	}

	/**
	 * 封装为JSON字符串
	 * @return false|string
	 */
	public function toJSON(){
		return json_encode($this, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * 字符串化
	 * @return false|string
	 */
	public function __toString(){
		return $this->toJSON();
	}

	/**
	 * 从数组中获取指定字段路径的数据
	 * @param array $data 数据
	 * @param string $path 字段路径
	 * @param mixed|null $default 缺省值（不能是匿名函数，这点跟之前的不太一致）
	 * @param string $path_separator 字段路径分割符
	 * @return mixed
	 */
	private static function data_fetch($data, $path, $default = null, $path_separator = '.'){
		if(is_null($path)){
			return $data;
		}
		if(isset($data[$path])){
			return $data[$path];
		}
		foreach(explode($path_separator, $path) as $segment){
			if((!is_array($data) || !array_key_exists($segment, $data)) && (!$data instanceof ArrayAccess || !$data->offsetExists($segment))){
				return $default;
			}
			$data = $data[$segment];
		}
		return $data;
	}

	public function offsetExists($offset){
		return isset($this->data[$offset]);
	}

	public function offsetGet($offset){
		return isset($this->data[$offset]) ? $this->data[$offset] : null;
	}

	public function offsetSet($offset, $value){
		if(is_null($this->data)){
			$this->data = [];
		}
		$this->data[$offset] = $value;
	}

	public function offsetUnset($offset){
		if(isset($this->data[$offset])){
			unset($this->data[$offset]);
		}
	}
}
