<?php
// 应用返回封装
namespace app;

use think\response\Json;

class RespVo
{
    // 成功的状态码
    const CODE_SUCCESS = 0;
    // 系统错误的状态码
    const CODE_FAIL = -1;
    // 提示的状态码
    const CODE_INFO = 1101;
    // 警告的状态码
    const CODE_WARING = 1102;
    // 危险的状态码
    const CODE_DANGER = 1103;

    // 未登录状态码
    const NOT_LOGIN = 1000;
    // 登录失效的状态码
    const LOGIN_EXPIRE = 1001;

    // 返回状态码
    private $code = 0;
    // 返回信息
    private $msg = '';
    // 返回的数据
    private $data = [];

    public function set_code($code): RespVo
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    public function set_msg($msg): RespVo
    {
        $this->msg = $msg;
        return $this;
    }

    public function set_data($data): RespVo
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 失败响应
     *
     * @param $code
     * @param string $msg
     * @return Json
     */
    public static function Fail($code, string $msg = ""): Json
    {
        return json([
            "code" => $code,
            "msg" => $msg,
            "data" => ""
        ]);
    }

    /**
     * 成功响应
     *
     * @param $data
     * @return Json
     */
    public static function Suc($data): Json
    {
        return json([
            "code" => self::CODE_SUCCESS,
            "msg" => "success",
            "data" => $data
        ]);
    }

    /**
     * 响应json
     *
     * @return Json
     */
    public function resp_json(): Json
    {
        return json([
            "code" => $this->code,
            'msg' => $this->msg,
            'data' => $this->data
        ]);
    }

    /**
     * 提醒信息
     *
     * @param $msg
     * @param array $data
     * @return RespVo
     */
    public function fail_info($msg, array $data = []): RespVo
    {
        $this->code = self::CODE_INFO;
        $this->msg = $msg;
        $this->data = $data;
        return $this;
    }

    /**
     * 危险信息
     *
     * @param $msg
     * @param array $data
     * @return RespVo
     */
    public function danger_msg($msg, array $data = []): RespVo
    {
        $this->code = self::CODE_DANGER;
        $this->msg = $msg;
        $this->data = $data;
        return $this;
    }

    /**
     * 警告信息
     *
     * @param $msg
     * @param array $data
     * @return RespVo
     */
    public function warn_msg($msg, array $data = []): RespVo
    {
        $this->code = self::CODE_WARING;
        $this->msg = $msg;
        $this->data = $data;
        return $this;
    }

    /**
     * 成功
     *
     * @param $data
     * @return RespVo
     */
    public function success($data): RespVo
    {
        $this->code = self::CODE_SUCCESS;
        $this->msg = 'success';
        $this->data = $data;
        return $this;
    }
}