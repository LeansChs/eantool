<?php

namespace Eantool\Net\Log;

use Eantool\Net\Net\IP;

/**
 * @todo
 * RPC调用（被调）日志
 * @package Eantool\Net\Log
 * @property string $log_at 日志记录时间
 * @property string $module_name 模块名称
 * @property string $app_id 店铺ID
 * @property string $user_id 用户ID
 * @property string $target_url 请求目标URL
 * @property string $method 请求方法
 * @property string $params 参数（JSON）
 * @property string $client_ip 客户端IP
 * @property string $server_ip 服务端IP
 * @property string $agent 代理
 * @property string $content 内容
 * @property string $http_status HTTP响应状态
 * @property string $code 返回码
 * @property string $msg 返回消息
 * @property float $begin_time 请求开始时间
 * @property float $end_time 请求结束时间
 * @property float $cost_time 耗时
 */
class RpcLog extends DataPrototype
{
    /**
     * 客户端记录被调情况
     * @param RpcLog $log
     * @return RpcLog
     */
    public static function createAsClient(RpcLog $log)
    {
        $now           = date('Y-m-d H:i:s');
        $log->log_at   = $log->log_at ?: $now;
        $log->end_time = $log->end_time ?: $now;
        $rpc_log       = new self($log->toArray());
        return $rpc_log;
    }

    /**
     * 服务端记录被调情况
     * @param RpcLog $log
     * @return RpcLog
     */
    public static function createAsServer(RpcLog $log)
    {
        $now              = date('Y-m-d H:i:s');
        $now_msc          = microtime(true);
        $log->log_at      = $log->log_at ?: $now;
        $log->target_url  = $log->target_url ?: self::getRequestUrl();
        $log->method      = $log->method ?: $_SERVER['REQUEST_METHOD'];
        $log->params      = $log->params ?: $_POST;
        $log->client_ip   = $log->client_ip ?: IP::getClientIp();
        $log->server_ip   = $log->server_ip ?: $_SERVER['REMOTE_ADDR'];
        $log->agent       = $log->agent ?: $_SERVER['HTTP_USER_AGENT'];
        $log->content     = $log->content ?: null;
        $log->http_status = $log->http_status ?: 200; //默认成功
        $log->code        = $log->code ?: null;
        $log->msg         = $log->msg ?: null;
        $log->begin_time  = $log->begin_time ?: $_SERVER['REQUEST_TIME_FLOAT'];
        $log->end_time    = $log->end_time ?: $now_msc;
        $log->cost_time   = $log->cost_time ?: ($now_msc - $_SERVER['REQUEST_TIME_FLOAT']) * 1000000; //毫秒
        return new self($log->toArray());
    }

    /**
     * 获取接收到的请求
     * @return string
     */
    public static function getRequestUrl()
    {
        $proc     = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $port_str = $_SERVER['SERVER_PORT'];
        return "$proc://{$_SERVER['HTTP_HOST']}{$port_str}/{$_SERVER['REQUEST_URI']}";
    }
}