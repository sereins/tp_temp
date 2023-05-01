<?php

namespace email;


use app\utils\Str;
use Exception;
use PHPMailer\PHPMailer\PHPMailer;

class SendEmail
{
    // 参数说明博客
    // https://www.cnblogs.com/jesu/p/6184054.html

    /** @var null 静态实例 */
    private static $_instance = null;

    /** @var array 邮件配置,在config夹中定义 */
    private $config = [];

    /** @var PHPMailer|null 邮件操作对象 */
    private $phpEmail = null;

    // 每行的字符,超过自动换行
    private $wordWrap = 70;

    /**
     * 构造
     *
     * @param string $conKey
     * @throws EmailException
     */
    public function __construct(string $conKey = '')
    {
        // 设置配置
        $this->setConfig($conKey);

        // 初始化
        $this->init();
    }

    /**
     * 初始化邮件配置
     *
     * @return void
     * @throws EmailException
     */
    private function init()
    {
        $phpMail = new PHPMailer(true);

        // SMTP服务器的超时(秒)
        $phpMail->Timeout = 5;
        // 字符
        $phpMail->CharSet = "UTF-8";
        // 设置SMTP用户名
        $phpMail->Username = $this->config['username'];
        // 设置SMTP的密码
        $phpMail->Password = $this->config['password'];
        // 发件人E-mail地址
        $phpMail->From = $this->config['username'];
        // 发件人称呼
        $phpMail->FromName = $this->config['autograph'];

        // 使用SMTP来发件
        $phpMail->isSMTP();
        // 发邮件主机
        $phpMail->Host = $this->config['send_out']['host'];
        // 发邮件端口
        $phpMail->Port = $this->config['send_out']['port'];

        switch ($phpMail->Port) {
            case 25:
                $phpMail->SMTPAuth = false;
                $phpMail->SMTPAutoTLS = false;
                $phpMail->SMTPSecure = 'tls';
                break;
            case 465:
                $phpMail->SMTPAuth = true;
                $phpMail->SMTPAutoTLS = true;
                $phpMail->SMTPSecure = 'ssl';
                break;
            case 587:
                $phpMail->SMTPAuth = true;
                $phpMail->SMTPAutoTLS = true;
                $phpMail->SMTPSecure = 'tls';
                break;
            default:
                throw new EmailException('不支持的邮件端口');
        }
        $this->phpEmail = $phpMail;
    }

    /**
     * 设置配置
     *
     * @param $configKey
     * @return SendEmail
     * @throws EmailException
     */
    public function setConfig($configKey): SendEmail
    {
        $emailConf = config('email');

        $key = !empty($configKey) ? $configKey : $emailConf['default'];

        $this->config = $emailConf[$key];

        if (empty($this->config)) throw new EmailException('配置项不能为空!');

        return $this;
    }

    /**
     * 获取配置
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * 获取实例
     *
     * @param string $confKey
     * @return SendEmail
     * @throws EmailException
     */
    public static function GetInstance(string $confKey = ''): SendEmail
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($confKey);
        }

        return self::$_instance;
    }

    /**
     * 添加收件人(单个)
     *
     * @param $address
     * @param string $name
     * @return SendEmail
     * @throws EmailException
     * @throws Exception
     */
    public function addAddress($address, string $name = ''): SendEmail
    {
        // 清除所有收件人，包括CC(抄送)和BCC(密送)
        $this->phpEmail->ClearAllRecipients();

        if (!Str::IsEmail($address))
            throw new EmailException("{$address}不是一个合法的邮件");

        $this->phpEmail->addAddress($address, $name);

        return $this;
    }

    /**
     * 批量添加收件人
     *
     * 格式
     * $address = [
     *      ['address'=>'aa.@qq.con','name"=>'zhang san'],
     *      ['address'=>'bb.@qq.con','name"=>'li xi'],
     * ]
     * @param array $addresses
     * @return $this
     * @throws EmailException
     * @throws Exception
     */
    public function addAddressBath(array $addresses): SendEmail
    {
        // 清除所有收件人，包括CC(抄送)和BCC(密送)
        $this->phpEmail->ClearAllRecipients();

        foreach ($addresses as $address) {
            if (!Str::IsEmail($address['address']))
                throw new EmailException("{$address}不是一个合法的邮件");
            $this->phpEmail->addAddress($address['address'], $address['name']);
        }

        return $this;
    }

    /**
     * 添加一个邮件抄送
     *
     * @param $address
     * @param string $name
     * @return $this
     * @throws EmailException
     * @throws Exception
     */
    public function addCC($address, string $name = ''): SendEmail
    {
        if (!Str::IsEmail($address))
            throw new EmailException("{$address}不是一个合法的邮件");

        $this->phpEmail->AddCC($address, $name);

        return $this;
    }

    /**
     * 增加一个密送
     *
     * @param $address
     * @param $name
     * @return $this
     * @throws EmailException
     * @throws \Exception
     */
    public function addBcc($address, $name): SendEmail
    {
        if (!Str::IsEmail($address))
            throw new EmailException("{$address}不是一个合法的邮件");

        $this->phpEmail->AddBCC($address, $name);

        return $this;
    }

    /**
     * email中加入图片
     *
     * @param $path
     * @param $cid mixed 在html中的标识(当前图片放在那个位置，html模板中有图片需要加cid:)
     * @param string $name
     * @return void
     * @throws EmailException
     * @throws \Exception
     */
    public function addImage($path, $cid, string $name = ''): SendEmail
    {
        if (!file_exists($path))
            throw new EmailException('文件不存在');

        $this->phpEmail->AddEmbeddedImage($path, $cid, $name);
        return $this;
    }

    /**
     * 给邮件中添加一个附件
     *
     * @param $path
     * @param string $name 重写附件名称
     * @return void
     * @throws \Exception|EmailException
     */
    public function addAttachment($path, string $name = ''): SendEmail
    {
        if (!file_exists($path))
            throw new EmailException($path . '文件不存在');

        $this->phpEmail->AddAttachment($path, $name);
        return $this;
    }

    /**
     * 以html方式发送流程
     *
     * @param $template string 模板名称
     * @param $subject string 主题
     * @param $data array 需要替换的变量
     * @throws EmailException
     */
    public function sendWithHtml(string $template, string $subject, array $data): bool
    {
        $this->phpEmail->isHTML(true);
        // 邮件主题
        $this->phpEmail->Subject = $subject;

        $file = __DIR__ . '/template/' . $template . '.html';
        if (!file_exists($file))
            throw new EmailException("{$template}模板文件不存在");

        $content = file_get_contents($file);
        // 模版变量替换
        foreach ($data as $k => $v) {
            $content = Str::Replace($content, "{{" . $k . "}}", $v);
        }

        $this->phpEmail->Body = $content;

        return $this->_send();
    }

    /**
     * 直接发送文本
     *
     * @param $subject
     * @param $content
     * @return bool
     * @throws EmailException
     */
    public function sendWithText($subject, $content): bool
    {
        $this->phpEmail->isHTML(true);
        // 邮件主题
        $this->phpEmail->Subject = $subject;
        // 换行
        $this->phpEmail->WordWrap = $this->wordWrap;

        $this->phpEmail->Body = $content;

        return $this->_send();
    }

    /**
     * 发送
     *
     * @throws EmailException
     */
    private function _send(): bool
    {
        try {

            return $this->phpEmail->send();
        } catch (\Exception $exception) {
            throw new EmailException($exception->getMessage());
        }
    }
}
