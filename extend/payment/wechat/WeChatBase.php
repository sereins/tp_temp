<?php

namespace payment\wechat;

use darkForest\Exception\App\GenerateException;
use darkForest\Tools\Https;
use ext\constClass\STD;
use WeChatPay\Builder;
use WeChatPay\BuilderChainable;
use WeChatPay\Crypto\AesGcm;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\MediaUtil;
use WeChatPay\Util\PemUtil;

abstract class WeChatBase
{
    /** @var string 商户私钥地址 */
    private $privateKeyPath = 'apiclient_key.pem';

    /** @var string 微信支付平台证书(下载) */
    protected $platformPublicKey = 'cert.pem';

    /** @var  BuilderChainable 操作实例 */
    protected $wechatPayInstance;

    /** @var array 微信支付配置 */
    protected $payConfig;

    /** @var null 微信支付平台证书实例 */
    private $platformPublicKeyInstance = null;

    /** @var string 微信支付证书序列号 */
    protected $platformCertificateSerial = '';

    /** @var null 商户私钥实例 */
    private $merchantPrivateKeyInstance = null;

    // 支付信息
    protected $openId;
    protected $attach = '';
    protected $subMchid;
    protected $description;
    protected $notifyUrl = '';

    /**
     * 构造
     *
     * @param $payConfig
     */
    public function __construct($payConfig)
    {
        $this->payConfig = $payConfig;
    }

    /**
     * 构建一个微信支付sdk实例
     *
     * @return void
     */
    protected function getWxInstance(): BuilderChainable
    {
        if (is_null($this->wechatPayInstance)) {
            // 证书的配置目录

            //「商户API私钥实例」会用来生成请求的签名
            $merchantPrivateKeyInstance = $this->getMerchantPrivateKeyInstance();

            // 「微信支付平台证书」，用来验证微信支付应答的签名
            $platformPublicKeyInstance = $this->getPlatformPublicKeyInstance();

            // 从「微信支付平台证书」中获取「证书序列号」
            $platformCertificateSerial = $this->getPlatformCertificateSerial();

            $this->wechatPayInstance = Builder::factory([
                'mchid' => $this->payConfig['mchid'],
                'serial' => $this->payConfig['serial'], # 商户证书序列号
                'privateKey' => $merchantPrivateKeyInstance,
                'certs' => [
                    $platformCertificateSerial => $platformPublicKeyInstance,
                ],
            ]);
        }
        return $this->wechatPayInstance;
    }

    /**
     * 获取证书的全路径
     *
     * @param string $cert
     * @param bool $type
     * @return string
     */
    private function getCertPath(string $cert = '', bool $type = true): string
    {
        $base = app()->make('path.config') . '/pay_cert/wechat/' . $cert;

        return $type ? 'file://' . $base : $base;
    }

    /**
     * 获取微信支付平台实例
     *
     * @return mixed|\OpenSSLAsymmetricKey|resource
     */
    public function getPlatformPublicKeyInstance()
    {
        if (is_null($this->platformPublicKeyInstance)) {
            $this->platformPublicKeyInstance = Rsa::from(
                $this->getCertPath($this->platformPublicKey),
                Rsa::KEY_TYPE_PUBLIC
            );
        }
        return $this->platformPublicKeyInstance;
    }

    /**
     * 获取商户私钥实例
     *
     * @return mixed|\OpenSSLAsymmetricKey|resource|null
     */
    public function getMerchantPrivateKeyInstance()
    {
        if (is_null($this->merchantPrivateKeyInstance)) {
            $this->merchantPrivateKeyInstance = Rsa::from(
                $this->getCertPath($this->privateKeyPath),
                Rsa::KEY_TYPE_PRIVATE);
        }
        return $this->merchantPrivateKeyInstance;
    }

    /**
     * 获取平台证书序列号
     *
     * @return string
     */
    public function getPlatformCertificateSerial(): string
    {
        if (empty($this->platformCertificateSerial)) {
            $this->platformCertificateSerial =
                PemUtil::parseCertificateSerialNo($this->getCertPath($this->platformPublicKey));
        }
        return $this->platformCertificateSerial;
    }

    /**
     * 信息加密
     *
     * @param $msg
     * @return string
     */
    public function encrypt($msg): string
    {
        return Rsa::encrypt($msg, $this->getPlatformPublicKeyInstance());
    }

    /**
     * 发送微信请求
     *
     * @param string $chain 链路,请求地址
     * @param array $options 参数
     * @param string $method 方式
     * @return array
     */
    protected function _send(string $chain, array $options, string $method = 'post'): array
    {
        try {

            switch ($method) {
                case "post":
                    $resp = $this->getWxInstance()->chain($chain)->post($options);
                    break;
                case 'get':
                    $resp = $this->getWxInstance()->chain($chain)->get($options);
                    break;
                default:
                    throw new \Exception('不支持的请求方式');
            }

            return [0, '', json_decode($resp->getBody(), true)];
        } catch (\Exception $e) {
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $r = $e->getResponse();
                $result = json_decode($r->getBody()->getContents(), true);
                return [STD::COM_O_WARING_D, $result['message'] ?? $r->getReasonPhrase()];
            }

            return [STD::COM_O_WARING_D, $e->getMessage(), ''];
        }
    }

    /**
     * 将媒体文件发送给微信
     *
     * @param $path
     * @return string
     * @throws GenerateException
     */
    public function fileToWx($path)
    {
        // 媒体对象
        $path = app()->make('path') . '/../public/files/' . $path;
        $media = new MediaUtil($path);

        list($state, $msg, $result) = $this->_send('v3/merchant/media/upload', [
            'body' => $media->getStream(),
            'headers' => [
                'Content-Type' => $media->getContentType(),
            ]
        ]);

        if ($state) throw new GenerateException($msg);
        return $result['media_id'];
    }

    /**
     * 下载微信支付证书
     *
     * @return void
     * @throws \Exception
     */
    public function getCertificates()
    {
        $method = 'GET';
        $chain = '/v3/certificates';
        $timestamp = time();
        $nonce = md5(rand(1000, 9999));
        $body = '';

        // 签名字符串
        $signStr = $method . "\n" . $chain . "\n" . $timestamp . "\n" . $nonce . "\n" . $body . "\n";

        $sign = Rsa::sign($signStr, $this->getMerchantPrivateKeyInstance());
        $token = sprintf('WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
            $this->payConfig['mchid'], $nonce, $timestamp, $this->payConfig['serial'], $sign);

        // url
        $url = 'https://api.mch.weixin.qq.com/v3/certificates';

        $https = Https::Url($url);
        $https->setHeader([
            'Authorization:' . $token,
            'Accept:application/json',
            'User-Agent:*/*'
        ]);

        $result = $https->get()->json();

        if (empty($result)) throw new \Exception('请求异常');

        $data = $result['data'][0];
        $c = AesGcm::decrypt(
            $data['encrypt_certificate']['ciphertext'],
            $this->payConfig['apikeyv3'],
            $data['encrypt_certificate']['nonce'],
            $data['encrypt_certificate']['associated_data'],
        );
        // 将证书写入到文件中
        if (!empty($c)) {
            $path = $this->getCertPath($this->platformPublicKey, false);
            file_put_contents($path, $c);
        }
    }

    public function setOpenId($openid)
    {
        $this->openId = $openid;
    }

    public function setAttach($attach)
    {
        $this->attach = $attach;
    }

    /**
     * 设置子商户号
     *
     * @param $subMchid
     * @return void
     */
    public function setSubMchid($subMchid)
    {
        $this->subMchid = $subMchid;
    }

    /**
     * 支付描述
     * @param $description
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

}
