<?php

namespace app\utils;

class Str
{
    /**
     * 是否包含另一个字符
     *
     * @param string $haystack
     * @param $needles
     * @return bool
     */
    public static function Contains(string $haystack, $needles): bool
    {
        foreach ((array)$needles as $needle) {
            if ('' != $needle && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 判断是否是中文·
     *
     * @param string $str
     * @return bool
     */
    public static function IsCn(string $str): bool
    {
        if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $str)) {
            return true;
        }
        return false;
    }

    /**
     * 是否是英文字母
     * @param $str
     * @return bool
     */
    public static function isEn($str): bool
    {
        if (preg_match('/[a-zA-Z]/', $str) > 0) {
            return true;
        }
        return false;
    }

    /**
     * 是否邮件
     *
     * @param string $str 邮件
     * @return bool
     */
    public static function IsEmail(string $str): bool
    {
        return (bool)filter_var($str, FILTER_VALIDATE_EMAIL);
    }

    /**
     * 验证手机号是否合法
     * @param string $str
     * @return bool
     */
    public static function IsPhone(string $str): bool
    {
        return (bool)preg_match("/^1[345789]\d{9}$/", $str);
    }

    /**
     * 验证身份证
     *
     * @param $value
     * @return bool
     */
    public static function IdCard($value): bool
    {
        if (!preg_match('/^\d{17}[0-9xX]$/', $value)) { //基本格式校验
            return false;
        }

        $parsed = date_parse(substr($value, 6, 8));
        if (!(isset($parsed['warning_count'])
            && $parsed['warning_count'] == 0)) { //年月日位校验
            return false;
        }

        $base = substr($value, 0, 17);

        $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];

        $tokens = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];

        $checkSum = 0;
        for ($i = 0; $i < 17; $i++) {
            $checkSum += intval(substr($base, $i, 1)) * $factor[$i];
        }

        $mod = $checkSum % 11;
        $token = $tokens[$mod];

        $lastChar = strtoupper(substr($value, 17, 1));

        return ($lastChar === $token); //最后一位校验位校验
    }

    /**
     * 邮件掩码
     * @param string $str 原字符串
     * @param string $mask 掩饰符号
     * @return string
     */
    public static function RepEmail(string $str, string $mask = "*"): string
    {
        $len = strlen(explode("@", $str)[0]);

        return self::RepMask($str, 1, $len - 1, $mask);
    }

    /**
     * 姓名掩码
     *
     * @param string $str 原字符串
     * @param string $mask 掩饰符号
     * @return string
     */
    public static function RepName(string $str, string $mask = "*"): string
    {
        $len = mb_strlen($str);

        return self::RepMask($str, 0, $len - 1, $mask);
    }

    /**
     * 手机掩码
     *
     * @param string $str 原字符串
     * @param string $mask 掩饰符号
     * @return string
     */
    public static function RepPhone(string $str, string $mask = "*"): string
    {
        return self::RepMask($str, 3, 4, $mask);
    }

    /**
     * 截取字符串
     * 中文为1个字符串
     *
     * @param string $str 原字符串
     * @param int $start
     * @param null $len
     * @return string
     */
    public static function Substr(string $str, int $start = 0, $len = null): string
    {
        $text = "";
        foreach (self::strtoarr($str) as $i => $value) {
            if ($i >= $start && $i < $start + $len) {
                $text .= $value;
            }
        }
        return $text;
    }

    /**
     * 替换为掩码
     * @param string $str 原字符串
     * @param int $start 待替换位置0开始
     * @param int $len 替换长度
     * @param string $mask 掩码
     * @return string
     */
    public static function RepMask(string $str, int $start, int $len, string $mask = "*"): string
    {
        $text = "";
        $re = self::strtoarr($str);
        foreach ($re as $i => $value) {
            if ($i >= $start && $i < $start + $len) {
                $text .= $mask;
            } else {
                $text .= $value;
            }
        }
        return $text;
    }

    /**
     * 字符串分割数组
     * 中文为1个字符串
     *
     * @param string $str 原字符串
     * @return array
     */
    public static function StrToArr(string $str): array
    {
        $mbLen = mb_strlen($str);
        $re = [];
        for ($i = 0; $i < $mbLen; $i++) {
            $mbSubstr = mb_substr($str, $i, 1, 'utf-8');
            if (strlen($mbSubstr) >= 4) {
                $re[] = $mbSubstr;
                continue;
            }
            if (strlen($mbSubstr) == 3) {
                $re[] = $mbSubstr;
                continue;
            }
            $re[] = $mbSubstr;
        }
        return $re;
    }

    /**
     * 替换字符串
     *
     * @param string $str 原字符串
     * @param string|array $from 待替换内容
     * @param string|null $to 转换内容
     * @return string
     */
    public static function Replace(string $str, $from, string $to = null): string
    {
        return str_replace($from, $to, $str);
    }

    /**
     * 分割字符串
     * @param string $str
     * @return string
     */
    public static function MbSplit(string $str): string
    {
        return $str = preg_split('/(?<!^)(?!$)/u', $str);
    }

    /**
     * 将unicode字符转化成utf8格式
     * 样式 \u4534
     * @param $content
     * @return string
     */
    public static function UnicodeToUtf8($content): string
    {
        $json = '{"str":"' . $content . '"}';
        $arr = json_decode($json, true);
        if (empty($arr)) return '';
        return $arr['str'];
    }

    /**
     * 字母转数字
     *
     * @param $abc
     * @return float|int
     */
    public static function ChrToInt($abc)
    {
        $abc = strtoupper($abc);
        $ten = 1;
        $len = strlen($abc);
        for ($i = 1; $i <= $len; $i++) {
            $char = substr($abc, 0 - $i, 1);
            $int = ord($char);
            if ($i > 1) {
                $ten += 26;
            }
            $ten += ($int - 65) * pow(26, $i - 1);
        }
        return $ten;
    }

    /**
     * 数字转字母
     * @param int $index 需要转换的数字 0开始
     */
    public static function IntToChr(int $index = 0, $start = 65): string
    {
        $str = '';
        $page = floor($index / 26);
        if ($page > 0) {
            $str .= self::IntToChr(((int)$page - 1));
        }
        return $str . chr(($index % 26) + $start);
    }

}