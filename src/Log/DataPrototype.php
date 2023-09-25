<?php
namespace Eantool\Net\Log;

use ArrayAccess;
use JsonSerializable;

/**
 * 数据对象原型
 * Class DataPrototype
 */
class DataPrototype implements ArrayAccess, JsonSerializable {
	public function __construct(array $data = []){
		if($data){
			foreach($data as $k=>$val){
				$this->{$k} = $val;
			}
		}
	}

	private static function getParentClasses($v){
	}

	public function offsetExists($offset){
		return isset($this->{$offset});
	}

    public function offsetGet($offset)
    {
        return isset($this->{$offset}) ? $this->{$offset} : null;
    }

	public function offsetSet($offset, $value){
		$this->{$offset} = $value;
	}

	public function offsetUnset($offset){
		if(isset($this->{$offset})){
			unset($this->{$offset});
		}
	}

    public function __debugInfo()
    {
        return $this->toArray();
    }

	public function jsonSerialize(){
		return $this->toArray();
	}

	public function __toString(){
		return $this->toJSON();
	}

	/**
	 * @param $name
	 * @param null $default
	 * @return mixed|null
	 */
	public function get($name, $default = null){
		return static::data_fetch($this, $name, $default);
	}

	/**
	 * @param $data
	 * @param $path
	 * @param null $default
	 * @param string $path_separator
	 * @return mixed|null
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

	public function toArray(){
		$data = [];
		foreach($this as $k=>$v){
            if (is_object($v) && method_exists($v, 'toArray')) {
				$data[$k] = $v->toArray();
			} else {
				$data[$k] = $v;
			}
		}
		return $data;
	}

	public function toJSON(){
		return json_encode($this->jsonSerialize(), JSON_UNESCAPED_UNICODE);
	}
}