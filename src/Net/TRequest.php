<?php

namespace Eantool\Net\Net;

use Eantool\Net\Exception\ConnectException;
use Eantool\Net\Exception\Exception;
use GuzzleHttp\RequestOptions;

/**
 * 请求基础类
 * 请求方法返回 ToolResponse 对象
 */
class TRequest extends Request
{
    //请求UUID
    public static $req_uuid;
    //请求UUID在header中的key
    public static $req_uuid_key = 'Req-UUID';

    /**
     * JSON GET请求封装，以ToolResponse类型返回
     * @param string $url
     * @param array $param
     * @param array $patch_options
     * @return TResponse
     * @throws ConnectException
     * @throws Exception
     */
    public function getInJSON($url, $param = [], array $patch_options = [])
    {
        $data = self::sendInJSON($url, $param, false, $patch_options);
        return TResponse::fromJSON($data);
    }

    /**
     * Query GET请求封装，以ToolResponse类型返回
     * @param string $url
     * @param array $param
     * @param array $patch_options
     * @return TResponse
     * @throws ConnectException
     * @throws Exception
     */
    public function get($url, $param = [], array $patch_options = [])
    {
        $content       = parent::get($url, $param, $patch_options);
        $response_json = @json_decode($content, true);
        if (!isset($response_json)) {
            $logger = static::getLogger();
            $logger->warning('Response string decode fail', $content);
            throw new ConnectException('Response string decode fail', -1, $url, $param, $content);
        }
        return TResponse::fromJSON($response_json);
    }

    /**
     * JSON POST请求封装，以ToolResponse类型返回
     * @param $url
     * @param array $param
     * @param array $patch_options
     * @return TResponse
     * @throws ConnectException
     * @throws Exception
     */
    public function postInJSON($url, $param = [], array $patch_options = [])
    {
        $data = self::sendInJSON($url, $param, true, $patch_options);
        return TResponse::fromJSON($data);
    }

    /**
     * 重写发送请求，加入请求UUID
     * @param string $url
     * @param array|string|null $param
     * @param string $method
     * @param array $patch_options
     * @return string
     * @throws ConnectException
     * @throws Exception
     */
    public function send($url, $param = null, $method = self::METHOD_GET, array $patch_options = [])
    {
        if (!isset($patch_options[RequestOptions::HEADERS])) {
            $patch_options[RequestOptions::HEADERS] = [];
        }
        return parent::send($url, $param, $method, $patch_options);
    }

    /**
     * 合并请求UUID到所有请求中
     * @param array $headers
     * @return array
     */
    private static function patchRequestUUID(array $headers)
    {
        if (!self::$req_uuid) {
            return $headers;
        }
        $headers[] = self::$req_uuid_key . ": " . self::$req_uuid;
        return $headers;
    }

    /**
     * 添加trace_id
     * @return array
     */
    private static function patchTraceID()
    {
        $patchedHeaders = [];
        $allHeaders     = getallheaders();
        isset($allHeaders["X-B3-Flags"]) && $patchedHeaders["X-B3-Flags"] = $allHeaders["X-B3-Flags"];
        isset($allHeaders["X-B3-Sampled"]) && $patchedHeaders["X-B3-Sampled"] = $allHeaders["X-B3-Sampled"];
        isset($allHeaders["X-B3-Spanid"]) && $patchedHeaders["X-B3-Spanid"] = $allHeaders["X-B3-Spanid"];
        isset($allHeaders["X-B3-Traceid"]) && $patchedHeaders["X-B3-Traceid"] = $allHeaders["X-B3-Traceid"];
        return $patchedHeaders;
    }
}