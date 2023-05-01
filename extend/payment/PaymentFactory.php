<?php

namespace payment;

class PaymentFactory
{
    /** @var null 静态工厂实例 */
    private static $FactoryInstance = null;

    /** @var array 实例对象 */
    private $concrete = [];

    /** @var array 支付配置信息 */
    private $config;

    private $plat;

    /**
     * 构造
     * @param $plat
     */
    private function __construct($plat)
    {
        $con = config('payment');

        $this->config = $con[$plat];

        $this->plat = $plat;
    }

    /**
     * 获取工厂
     *
     * @param string $plat 支付平台 微信，支付宝对于的英文单词
     * @return \ext\payment\PaymentFactory
     */
    public static function GetInstance(string $plat = 'wechat'): PaymentFactory
    {
        if (is_null(self::$FactoryInstance)) {
            self::$FactoryInstance = new self($plat);
        }

        return self::$FactoryInstance;
    }

    /**
     * 支付授权相关
     *
     * @return Authorize
     */
    public function auth(): Authorize
    {
        $key = $this->plat . ':auth' . $this->config['appid'];

        if (!isset($this->concrete[$key])) {
            $this->concrete[$key] = new Authorize($this->config);
        }
        return $this->concrete[$key];
    }

    public function singlePay(bool $combine = true): SinglePay
    {
        $key = $this->plat . ':singlePay' . $this->config['appid'];

        if (!isset($this->concrete[$key])) {
            $this->concrete[$key] = new SinglePay($this->config);
        }

        return $this->concrete[$key];
    }

    /**
     * 合并支付
     *
     * @return CombinePay
     */
    public function combinePay(): CombinePay
    {
        $key = $this->plat . ':combinePay' . $this->config['appid'];

        if (!isset($this->concrete[$key])) {
            $this->concrete[$key] = new CombinePay($this->config);
        }
        return $this->concrete[$key];
    }
}