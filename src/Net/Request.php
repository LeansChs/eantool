<?php
namespace Eantool\Net\Net;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use LFPhp\Logger\Logger;
use Eantool\Net\Exception\ConnectException;
use Eantool\Net\Exception\Exception;
use Eantool\Net\Util\EventTrait;
use function LFPhp\Func\get_time_left;

class Request {
	use EventTrait;

	//发送事件
	const EVENT_BEFORE_SEND = __CLASS__.'_EVENT_BEFORE_SEND';

	//响应事件
	const EVENT_AFTER_RESPONSE = __CLASS__.'_EVENT_AFTER_RESPONSE';

	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
	const METHOD_DELETE = 'DELETE';
	const METHOD_OPTION = 'OPTION';
	const METHOD_PUT = 'PUT';
	const METHOD_HEAD = 'HEAD';

	const METHOD_POST_JSON = 'POST_JSON';
	const METHOD_GET_JSON = 'GET_JSON';

	//默认超时时间（秒）
	//如需在项目中统一设定缺省时间，可通过 Request::$DEFAULT_TIMEOUT = ? 进行设定
	public static $DEFAULT_TIMEOUT = 10;

	protected static $logger;
	protected $config;

	/**
	 * Request constructor.
	 * @param array $config [timeout=>10]
	 */
	protected function __construct(array $config = []){
		$this->config = array_merge([
			'timeout' => static::$DEFAULT_TIMEOUT,
		], $config);
	}

	/**
	 * 设置日志记录器
	 * @param $logger
	 */
	public static function setLogger($logger){
		static::$logger = $logger;
	}

	/**
	 * 获取日志记录器
	 * @return Logger
	 */
	public static function getLogger(){
		return static::$logger ?: Logger::instance(__CLASS__);
	}

	/**
	 * JSON GET请求
	 * @param string $url
	 * @param array $param
	 * @param array $patch_options
	 * @return mixed
	 * @throws ConnectException
	 * @throws Exception
	 */
	public function getInJSON($url, $param = [], array $patch_options = []){
		return static::sendInJSON($url, $param, false, $patch_options);
	}

	/**
	 * JSON POST请求
	 * @param $url
	 * @param array $param
	 * @param array $patch_options
	 * @return array
	 * @throws ConnectException
	 * @throws Exception
	 */
	public function postInJSON($url, $param = [], array $patch_options = []){
		return static::sendInJSON($url, $param, true, $patch_options);
	}

	/**
	 * 以JSON数据包方式发送请求，结果以JSON方式进行识别解析
	 * @param string $url
	 * @param array|string $param
	 * @param bool $in_post
	 * @param array $patch_options
	 * @return mixed
	 * @throws ConnectException
	 * @throws Exception
	 */
	public function sendInJSON($url, array $param = [], $in_post = false, array $patch_options = []){
		$method = $in_post ? self::METHOD_POST_JSON : self::METHOD_GET_JSON;
		$content = $this->send($url, $param, $method, $patch_options);
		$response_json = @json_decode($content, true);
		if(!isset($response_json)){
			$logger = static::getLogger();
			$logger->warning('Response string decode fail', $content);
			throw new ConnectException('Response string decode fail', -1, $url, $param, $content);
		}
		return $response_json;
	}

	/**
	 * send request by normal get
	 * @param $url
	 * @param null $param
	 * @param array $patch_options
	 * @return string
	 */
	public function get($url, $param = null, array $patch_options =  []){
		return static::send($url, $param, self::METHOD_GET, $patch_options);
	}

	/**
	 * send request by normal post
	 * @param $url
	 * @param null $param
	 * @param array $patch_options
	 * @return string
	 * @throws ConnectException
	 * @throws Exception
	 */
	public function post($url, $param = null, array $patch_options =  []){
		return static::send($url, $param, self::METHOD_POST, $patch_options);
	}

    /**
     * 发送网络请求
     * @param string $url
     * @param string|array|null $param
     * @param string $method
     * @param array $patch_options 补充选项，例如HEADS可以放进来
     * @return string response body content
     * @throws ConnectException
     * @throws Exception
     * @throws GuzzleException
     */
	public function send($url, $param = null, $method = self::METHOD_GET, array $patch_options = []){
		$logger = static::getLogger();
		$logger->debug('Request start', $url, $param);

        // 传递访问链路头X-Forwarded-For
        array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && $patch_options['headers']['X-Forwarded-For'] = $_SERVER['HTTP_X_FORWARDED_FOR'];

		$client = new Client([
			'base_uri' => self::resolveBaseUri($url),
			'timeout'  => $this->config['timeout'],
		]);

		switch($method){
			case self::METHOD_GET:
                $param = self::patchParamFromUrl($param, $url);
                $m = self::METHOD_GET;
                $options = [RequestOptions::QUERY => $param];
                break;
			case self::METHOD_GET_JSON:
				$param = self::patchParamFromUrl($param, $url);
				$m = self::METHOD_GET;
				$options = [RequestOptions::JSON => $param];
				break;
			case self::METHOD_POST_JSON:
				$m = self::METHOD_POST;
				$options = [RequestOptions::JSON => $param];
				break;
			case self::METHOD_HEAD:
			case self::METHOD_POST:
			case self::METHOD_DELETE:
			case self::METHOD_PUT:
			case self::METHOD_OPTION:
			default:
				$m = $method;
				$options = [RequestOptions::QUERY => $param];
		}

		if($patch_options){
			$options = array_merge($options, $patch_options);
		}
		self::fireEvent(self::EVENT_BEFORE_SEND, $url, $options);

		//start request
		$logger->info("Req start [$m]", $url, $options);

		//timeout no enough warning
		if($this->config['timeout']){
			$time_left = get_time_left();
			if($time_left !== null && $time_left <= $this->config['timeout']){
				$time_left = round($time_left, 2);
				$logger->error("Req setting {timeout} maybe no enough for response ({$time_left}sec <= {$this->config['timeout']}sec)");
			}
		}

		$response = $client->request($m, $url, $options);
		$content = $response->getBody()->getContents();
		$status_code = $response->getStatusCode();

		$logger->info("Request finish [$status_code], body size:".strlen($content));
		$logger->info('Response body',$content);
		self::fireEvent(self::EVENT_AFTER_RESPONSE, $url, $options, $status_code, $content);

		if($status_code != 200){
			$logger->error('Response http code error:'.$status_code);
			throw new ConnectException('Response http code error', -1, $url, $param, $content);
		}
		return $content;
	}

	/**
	 * 从URL中解析HOST
	 * @param $url
	 * @return string
	 * @throws Exception
	 */
	private static function resolveBaseUri($url){
		$tmp = parse_url($url);

        if(!isset($tmp['scheme'])){
            throw new Exception('URL scheme resolve fail:'.$url);
        }

        if(!isset($tmp['host'])){
            throw new Exception('URL host resolve fail:'.$url);
        }

		return "{$tmp['scheme']}://{$tmp['host']}" . (isset($tmp['port']) ? ':' . $tmp['port'] : '') . "/";
	}

	/**
	 * @param $param
	 * @param $url
	 * @return array
	 */
	private static function patchParamFromUrl($param, $url){
		//修正如果是GET方式请求，url中的请求参数将被截取掉
		$tmp = parse_url($url);
		if(isset($tmp['query'])){
			parse_str($tmp['query'], $patch_param);
			if($patch_param){
				$param = array_merge($param, $patch_param);
			}
		}
		return $param;
	}

	/**
	 * 单例
	 * @param array $config
	 * @return static
	 */
	public static function instance(array $config = []){
		static $instance_list;
		$key = serialize($config).get_called_class();
		if(!$instance_list || !isset($instance_list[$key])){
			$instance_list[$key] = new static($config);
		}
		return $instance_list[$key];
	}

	private function __clone(){}
}
