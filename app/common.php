<?php
// 应用公共文件

/**
 * 打印函数
 *
 * @param ...$argc
 * @return void
 */
function d(...$argc)
{
    foreach ($argc as $value) {
        var_dump($value);
    }
    die();
}