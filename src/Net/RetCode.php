<?php

namespace Eantool\Net\Net;

/**
 * 业务逻辑Exception，不推荐在技术异常环境中使用
 * Class BusinessException
 * @package Xiaoe\WeWork\Exception
 */
abstract class RetCode {
	const CODE_SUCCESS = 0;
	const CODE_UNKNOWN = 1;

	/**
     * 标准返回码通用码
     */
    const CODE_BAD_REQUEST = 400;
    const CODE_UNAUTHORIZED= 401;
    const CODE_FORBIDDEN = 403;
    const CODE_NOT_FOUND = 404;
    const CODE_METHOD_NOT_ALLOWED = 405;
    const CODE_SERVER_ERROR = 500;
    const CODE_UPSTREAM_ERROR = 502;
    const CODE_UPSTREAM_TIME_OUT = 504;

	/**
	 * @param $code
	 * @return bool
	 */
	final public static function isSuccess($code): bool
    {
		return $code == self::CODE_SUCCESS;
	}
}