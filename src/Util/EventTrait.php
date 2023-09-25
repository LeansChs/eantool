<?php
namespace Eantool\Net\Util;

/**
 * 简单事件模型
 */
trait EventTrait {
	private static $_events = [/** [$event, $handle],... */];

	/**
	 * 事件监听
	 * @param string $event
	 * @param callable $handle
	 */
	final public static function listenEvent($event, callable $handle){
		self::$_events[] = [$event, $handle];
	}

	/**
	 * 事件触发
	 * @param string $event 事件
	 * @param mixed ...$args 触发参数
	 * @return bool|null true表示命中钩子，false表示被钩子中断
	 */
	final public static function fireEvent($event, &...$args){
		$hit = null;
		foreach(self::$_events as list($ev, $handle)){
			if($event == $ev){
				$rst = call_user_func_array($handle, $args);
				if($rst === false){
					return false;
				}
				$hit = true;
			}
		}
		return $hit;
	}
}