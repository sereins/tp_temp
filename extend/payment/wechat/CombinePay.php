<?php
# 合单支付操作类
namespace payment\wechat;

use darkForest\App\Resp;
use ext\payment\wechat\WeChatBase;
use WeChatPay\Crypto\AesGcm;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Formatter;

class CombinePay extends WeChatBase
{
    /**
     * 发起支付基础信息配置
     *
     * @param $orders
     * @param $no
     * @return array
     */
    private function _getOptions($orders, $no)
    {
        $options = [
            'combine_appid' => $this->payConfig['appid'],
            'combine_mchid' => $this->payConfig['mchid'],
            'combine_out_trade_no' => $no,
            'combine_payer_info' => ['openid' => $this->openId],
            'notify_url' => $this->notifyUrl,
        ];

        $subOrder = [];
        foreach ($orders as $item) {
            $base['mchid'] = $this->payConfig['mchid'];
            $base['attach'] = empty($this->attach) ? $item['sn'] : $this->attach;
            $base['amount']['total_amount'] = $item['total_amount'] * 100;
            $base['amount']['currency'] = 'CNY';
            $base['out_trade_no'] = $item['sn'];
            $base['sub_mchid'] = $this->subMchid;
            $base['description'] = '购买';
            $subOrder[] = $base;
        }
        $options['sub_orders'] = $subOrder;

        return $options;
    }

    /**
     * jsapi方式支付
     *
     * @param $orders
     * @param $no
     * @return array
     */
    public function jsApi($orders, $no)
    {
        $chain = 'v3/combine-transactions/jsapi';

        $options = $this->_getOptions($orders, $no);

        dd($options);
        list($state, $msg, $result) = $this->_send($chain,  $options,'postJson');
        if ($state) Resp::ResError($state, $msg);

        return $result;
    }

    public function decryptNotify($content)
    {
        // 证书的配置目录
        $certPath = 'file://' . app()->make('path.config') . '/pay_cert/wechat/';

        $platformPublicKeyInstance = Rsa::from($certPath . $this->platformPublicKey, Rsa::KEY_TYPE_PUBLIC);

        // 检查通知时间偏移量，允许5分钟之内的偏移
        $timeOffsetStatus = 300 >= abs(Formatter::timestamp() - (int)$content['time']);

        // 验证签名
        $verifiedStatus = Rsa::verify(
        // 构造验签名串
            Formatter::joinedByLineFeed($content['time'], $content['a'], ''),
            '',
            $platformPublicKeyInstance
        );
        // 解密
        if ($timeOffsetStatus && $verifiedStatus) {
            // 转换通知的JSON文本消息为PHP Array数组
            $inBodyArray = (array)json_decode('', true);
            // 使用PHP7的数据解构语法，从Array中解构并赋值变量
            ['resource' => [
                'ciphertext' => $ciphertext,
                'nonce' => $nonce,
                'associated_data' => $aad
            ]] = $inBodyArray;
            // 加密文本消息解密
            $inBodyResource = AesGcm::decrypt($ciphertext, '', $nonce, $aad);
            // 把解密后的文本转换为PHP Array数组
            $inBodyResourceArray = (array)json_decode($inBodyResource, true);
            // print_r($inBodyResourceArray);// 打印解密后的结果
        }
    }
}
