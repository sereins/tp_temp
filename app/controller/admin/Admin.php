<?php

namespace app\controller\admin;

use app\BaseController;
use app\model\AdminModel;
use app\Request;
use app\RespVo;
use app\service\admin\AdminService;
use app\service\LoginService;
use app\validate\admin\AdminVa;
use think\exception\ValidateException;
use think\response\Json;

class Admin extends BaseController
{
    /**
     * 登录
     *
     * @param Request $request
     * @return Json
     */
    public function login(Request $request): \think\response\Json
    {
        $data = $request->param();

        // 验证
        if (empty($data['account'] || empty($data['password']))) {
            return RespVo::Fail(RespVo::CODE_INFO, '账号密码不能为空!');
        }

        $loginService = new LoginService();
        return $loginService->admin_login($data)->resp_json();
    }

    /**
     * 退出登录
     *
     * @param Request $request
     * @return Json
     */
    public function logout(Request $request): Json
    {
        $loginService = new LoginService();

        $loginService->logout($request->header('authorization'));

        return RespVo::Suc([]);
    }

    /**
     * 新增管理员账号
     *
     * @param Request $request
     * @return Json
     */
    public function create(Request $request): Json
    {
        $data = $request->param();

        // 验证
        try {
            validate(AdminVa::class)->scene('create')->check($data);
        } catch (ValidateException $e) {
            return RespVo::Fail(RespVo::CODE_INFO, $e->getMessage());
        }

        // 账号创建
        $adminService = new AdminService();
        return $adminService->create($data)->resp_json();
    }

    /**
     * 根据id删除管理员账号
     *
     * @param Request $request
     * @return Json
     */
    public function del(Request $request): Json
    {
        $id = $request->param('id');
        if (empty($id)) return RespVo::Fail(RespVo::CODE_WARING, "id不能为空");

        $admin = AdminModel::FindById($id);
        if ($admin->isEmpty()) return RespVo::Fail(RespVo::CODE_WARING, "账号不存在");
        if ($admin->admin == 1) return RespVo::Fail(RespVo::CODE_WARING, "超级管理员不能删除");

        if (!$admin->delete()) {
            return RespVo::Fail(RespVo::CODE_WARING, "删除失败");
        }
        return RespVo::Suc('success');
    }

    /**
     * 管理员基本信息编辑
     *
     * @param Request $request
     * @return Json
     */
    public function put(Request $request): Json
    {
        $data = $request->param();
        // 验证
        try {
            validate(AdminVa::class)->scene('edit')->check($data);
        } catch (ValidateException $e) {
            return RespVo::Fail(RespVo::CODE_INFO, $e->getMessage());
        }

        $admin = AdminModel::FindById($data['id']);
        if ($admin->isEmpty()) return RespVo::Fail(RespVo::CODE_WARING, "账号不存在");

        $data['time_put'] = date("Y-m-d H:i:s");
        if (!$admin->allowField(['nickname', 'sex', 'department', 'time_put'])->save($data)) {
            return RespVo::Fail(RespVo::CODE_WARING, "修改失败");
        }

        return RespVo::Suc('success');
    }

    /**
     * 启用或者禁用
     *
     * @param Request $request
     * @return Json
     */
    public function disable(Request $request): Json
    {
        $data = $request->param();
        // 验证
        try {
            validate(AdminVa::class)->scene('disable')->check($data);
        } catch (ValidateException $e) {
            return RespVo::Fail(RespVo::CODE_INFO, $e->getMessage());
        }

        $admin = AdminModel::FindById($data['id']);
        if ($admin->isEmpty()) return RespVo::Fail(RespVo::CODE_WARING, "账号不存在");

        $data['time_put'] = date('Y-m-d H:i:s');
        if (!$admin->allowField(['state', 'time_put'])->save($data)) {
            return RespVo::Fail(RespVo::CODE_WARING, "操作失败");
        }

        return RespVo::Suc('success');
    }

    /**
     * 重置密码
     *
     * @param Request $request
     * @return Json
     */
    public function rest_pass(Request $request): Json
    {
        $data = $request->param();
        // 验证
        try {
            validate(AdminVa::class)->scene('rest')->check($data);
        } catch (ValidateException $e) {
            return RespVo::Fail(RespVo::CODE_INFO, $e->getMessage());
        }

        $admin = AdminModel::FindById($data['id']);
        if ($admin->isEmpty()) return RespVo::Fail(RespVo::CODE_WARING, "账号不存在");

        $data['time_put'] = date('Y-m-d H:i:s');
        $data['password'] = LoginService::md5_pass($data['password']);
        if (!$admin->allowField(['password', 'time_put'])->save($data)) {
            return RespVo::Fail(RespVo::CODE_WARING, "重置密码失败");
        }

        return RespVo::Suc([]);
    }

    /**
     * 管理员列表
     *
     * @param Request $request
     * @return Json
     */
    public function lists(Request $request): Json
    {
        $data = $request->param();
        $paginator = new \Paginator($data);
        $query = new AdminModel();
        $query = $query->field('id,nickname,phone,email,sex,state,department,time_add,time_put');

        if (!empty($data['search'])) {
            $query->where('nickname|email|phone', 'like', "%{$data['search']}%");
        }

        $query->order('time_add', 'desc');

        $data = $paginator->pages($query);
        return RespVo::Suc($data);
    }

    /**
     * 返回用户的登录信息
     *
     * @return Json
     */
    public function info(): Json
    {
        unset($this->userInfo['password']);
        return RespVo::Suc($this->userInfo);
    }
}