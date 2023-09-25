<?php
namespace Eantool\Net\Net;

class IP {

    /** 批量检查ip地址是否在checkIps里面
     * @param string $requestIp
     * @param array $checkIPs
     * @return bool
     */
    public static function batchCheckIp4($requestIp, $checkIPs){
        foreach ($checkIPs as $checkIP){
            if (self::checkIp4($requestIp, $checkIP)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compares two IPv4 addresses.
     * In case a subnet is given, it checks if it contains the request IP.
     *
     * @param string $requestIp IPv4 address to check
     * @param string $ip        IPv4 address or subnet in CIDR notation
     *
     * @return bool Whether the request IP matches the IP, or whether the request IP is within the CIDR subnet.
     */
    public static function checkIp4($requestIp, $ip)
    {
        if (false !== strpos($ip, '/')) {
            list($address, $netmask) = explode('/', $ip, 2);

            if ($netmask === '0') {
                // Ensure IP is valid - using ip2long below implicitly validates, but we need to do it manually here
                return filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
            }

            if ($netmask < 0 || $netmask > 32) {
                return false;
            }
        } else {
            $address = $ip;
            $netmask = 32;
        }

        return 0 === substr_compare(sprintf('%032b', ip2long($requestIp)), sprintf('%032b', ip2long($address)), 0, $netmask);
    }

	/**
	 * 获取客户端IP
	 * @return string
	 */
    public static function getClientIp(){
	    $ip = '';
	    if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')){
		    $ip = getenv('HTTP_CLIENT_IP');
	    }elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')){
		    $ip = getenv('HTTP_X_FORWARDED_FOR');
	    }elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')){
		    $ip = getenv('REMOTE_ADDR');
	    }elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')){
		    $ip = $_SERVER['REMOTE_ADDR'];
	    }
	    return preg_match('/[\d.]{7,15}/', $ip, $matches) ? $matches [0] : $ip;
    }

}