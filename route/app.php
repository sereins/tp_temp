<?php

use think\facade\Route;

// 后台路由
Route::group("admin", function () {
    // 管理员路由
    Route::get('admin/login', 'admin.admin/login');
    Route::get('admin/logout', 'admin.admin/logout');
    Route::post('admin/create', 'admin.admin/create');
    Route::post('admin/put', 'admin.admin/put');
    Route::post('admin/del', 'admin.admin/del');
    Route::post('admin/disable', 'admin.admin/disable');
    Route::post('admin/rest_pass', 'admin.admin/rest_pass');
    Route::get('admin/lists', 'admin.admin/lists');
    Route::get('admin/info', 'admin.admin/info');

    // 权限列表
    Route::post('auth/create', 'admin.auth/create');
    Route::post('auth/put', 'admin.auth/put');
    Route::post('auth/del', 'admin.auth/del');
    Route::post('auth/disable', 'admin.auth/disable');
    Route::get('auth/menu', 'admin.auth/menu');
    Route::get('auth/lists', 'admin.auth/lists');
    Route::get('auth/tree', 'admin.auth/tree');

    // 权限组
    Route::post('group/create', 'admin.auth_group/create');
    Route::post('group/put', 'admin.auth_group/put');
    Route::post('group/del', 'admin.auth_group/del');
    Route::post('group/bind_auth', 'admin.auth_group/bind_auth');
    Route::post('group/bind_user', 'admin.auth_group/bind_user');
    Route::get('group/lists', 'admin.auth_group/lists');
    Route::get('group/auths', 'admin.auth_group/lists');
});


// 前台路由


// 未定义路由
Route::miss(function () {
    return \app\RespVo::Fail(\app\RespVo::CODE_DANGER, "路由未定义!");
});