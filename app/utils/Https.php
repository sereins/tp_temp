<?php

namespace app\utils;

class Https
{
    // 参数文档:https://www.php.net/manual/zh/function.curl-setopt.php
    /** @var mixed 静态实例 */
    private static $instance = null;

    /** @var mixed curl执行句丙 */
    private static $ch = null;

    /** @var  string 请求地址 */
    private static $url;

    // 函数最长执行时间
    private $time = 10;
    // 尝试链接最长等待时间
    private $timeOut = 10;

    /**
     * 获取实例
     *
     * @param $url
     * @return Https
     */
    public static function Url($url): Https
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        self::$ch = curl_init();
        self::$url = $url;

        return self::$instance;
    }

    /**
     * curl函数执行的最长秒数
     *
     * @param $time
     * @return void
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    /**
     * 在尝试连接时等待的秒数。设置为0，则无限等待
     *
     * @param $timeOut
     * @return void
     */
    public function setTimeOut($timeOut)
    {
        $this->timeOut = $timeOut;
    }

    /**
     * 设置代理
     *
     * @param string $ip
     * @param int $port
     * @return void
     */
    public function setProxy(string $ip = "127.0.0.1", int $port = 1087)
    {
        $ip = $_SERVER["REMOTE_ADDR"] ?? $ip;
        // 代理认证模式
        curl_setopt(self::$ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
        // 开启代理
        curl_setopt(self::$ch, CURLOPT_HTTPPROXYTUNNEL, true);
        // 代理ip
        curl_setopt(self::$ch, CURLOPT_PROXY, $ip);
        // 代理端口
        curl_setopt(self::$ch, CURLOPT_PROXYPORT, $port);
        // http代理模式
        curl_setopt(self::$ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
    }

    /**
     * 设置ssl证书
     *
     * @param $sslCertPath
     * @param $sslKeyPath
     * @return void
     */
    public function setSsLPem($sslCertPath, $sslKeyPath)
    {
        curl_setopt(self::$ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt(self::$ch, CURLOPT_SSLCERT, $sslCertPath);
        curl_setopt(self::$ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt(self::$ch, CURLOPT_SSLKEY, $sslKeyPath);
    }

    /**
     * 设置头
     *
     * @return void
     */
    public function setHeader($data)
    {
        curl_setopt(self::$ch, CURLOPT_HTTPHEADER, $data);
    }

    /**
     * 设置请求cookie
     *
     * @param $data
     * @return void
     */
    public function setCookie($data)
    {
        curl_setopt(self::$ch, CURLOPT_COOKIE, $data);
    }

    /**
     * 发送请求
     * @return bool|string|mixed
     */
    private function _send()
    {
        // 执行url
        curl_setopt(self::$ch, CURLOPT_URL, self::$url);

        // 最长执行时间
        curl_setopt(self::$ch, CURLOPT_TIMEOUT, $this->time);
        // 最长链接时间
        curl_setopt(self::$ch, CURLOPT_CONNECTTIMEOUT, $this->timeOut);
        // 将curl_exec()获取的信息以字符串返回，而不是直接输出
        curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, true);

        // 重定向
        curl_setopt(self::$ch, CURLOPT_FOLLOWLOCATION, true);

        //SSL 防止报错
        if (substr(self::$url, 0, 5) == 'https') {
            curl_setopt(self::$ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        // 执行
        $result = curl_exec(self::$ch);

        // curl 执行错误 返回错误信息
        if (curl_errno(self::$ch)) Resp::ResError(132300, curl_error(self::$ch));

        // 关闭句柄
        curl_close(self::$ch);

        return $result;
    }

    /**
     * 会以post方式发送请求
     *
     * @param $data
     * @return Https
     */
    public function post($data): Https
    {
        $data = http_build_query($data);

        curl_setopt(self::$ch, CURLOPT_POST, 1);
        curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $data);

        return self::$instance;
    }

    /**
     * post 数据json格式
     *
     * @param $data
     * @param int $flag
     * @return Https
     */
    public function postJson($data, int $flag = 0): Https
    {
        $data = json_encode($data, $flag);

        curl_setopt(self::$ch, CURLOPT_POST, 1);
        curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $data);

        return self::$instance;
    }

    /**
     * 以xml的方式发送post请求
     *
     * @param $data
     * @return null
     */
    public function postXml($data)
    {
        $xml = '';
        foreach ($data as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }

        $data = "<xml>" . $xml . "</xml>";
        curl_setopt(self::$ch, CURLOPT_POST, 1);
        curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $data);

        return self::$instance;
    }


    /**
     * 发送一个get请求
     *
     * @param array $data
     * @return Https
     */
    public function get(array $data = []): Https
    {
        if (!empty($data))
            self::$url .= '?' . http_build_query($data);

        return self::$instance;
    }

    /**
     * 返回结果是json的转为数组
     *
     * @return bool|mixed|string
     */
    public function json()
    {
        $data = $this->_send();

        $result = json_decode($data, true);

        // 如果解析失败,返回最原始的结果
        if (json_last_error() != JSON_ERROR_NONE) return $data;

        return $result;
    }

    /**
     * 请求结果是xml格式，返回数组
     *
     * @return mixed
     */
    public function xml()
    {
        $xml = $this->_send();

        $xml = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);

        return json_decode($json, TRUE);
    }

    /**
     * 返回请求的原始结果
     *
     * @return bool|mixed|string
     */
    public function originalData()
    {
        return $this->_send();
    }

    /**
     * 获取链接
     *
     * @return string
     */
    public function getUrl()
    {
        return self::$url;
    }
}