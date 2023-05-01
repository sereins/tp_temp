<?php

namespace app\controller\admin;

use app\BaseController;
use app\model\AdminModel;
use app\model\AuthGroupModel;
use app\model\AuthModel;
use app\Request;
use app\RespVo;
use think\response\Json;

class AuthGroup extends BaseController
{
    /**
     * 新增权限组
     *
     * @param Request $request
     * @return Json
     */
    public function create(Request $request): Json
    {
        $name = $request->param('name', "");
        if (empty($name)) return RespVo::Fail(RespVo::CODE_INFO, "分组名称不能为空!");

        // 分组是否存在
        $exits = AuthGroupModel::where([
            ['name', '=', $name],
        ])->value('id');
        if ($exits) return RespVo::Fail(RespVo::CODE_INFO, "权限组【{$name}】已经存在");


        $data['time_add'] = date("Y-m-d H:i:s");
        $data['name'] = $name;
        $data['notes'] = $request->param('notes', '');
        $model = new AuthGroupModel();
        if (!$model->save($data)) {
            return RespVo::Fail(RespVo::CODE_INFO, '新增权限组失败!');
        }
        return RespVo::Suc([]);
    }

    /**
     * 根据id删除权限组
     *
     * @param Request $request
     * @return Json
     */
    public function del(Request $request): Json
    {
        $id = $request->param('id');
        if (empty($id)) return RespVo::Fail(RespVo::CODE_INFO, "id不能为空");

        $group = AuthGroupModel::findOrEmpty($id);
        if ($group->isEmpty()) return RespVo::Fail(RespVo::CODE_INFO, "权限组不存在");

        // 是否有用户绑定了当前权限
        $check = AdminModel::where('auth_group', $id)->value('id');
        if ($check) return RespVo::Fail(RespVo::CODE_INFO, "有用户绑定当前权限组不可删除");

        if (!$group->delete()) {
            return RespVo::Fail(RespVo::CODE_INFO, "删除失败");
        }
        return RespVo::Suc('success');
    }

    /**
     * 修改分组名字以及备注
     *
     * @param Request $request
     * @return Json
     */
    public function put(Request $request): Json
    {
        $params = $request->param();
        if (empty($params['id'])) return RespVo::Fail(RespVo::CODE_WARING, "id不能为空!");
        if (empty($params['name'])) return RespVo::Fail(RespVo::CODE_WARING, "分组名称不能为空!");

        $group = AuthGroupModel::findOrEmpty($params['id']);
        if (empty($group)) return RespVo::Fail(RespVo::CODE_WARING, "记录不存在!");

        if (!$group->allowField(['name', 'notes'])->save($params)) {
            return RespVo::Fail(RespVo::CODE_WARING, "修改失败");
        }

        return RespVo::Suc('success');
    }

    /**
     * 权限组列表
     *
     * @param Request $request
     * @return Json
     */
    public function lists(Request $request): Json
    {
        $group = AuthGroupModel::field('id,name,notes')->select();

        return RespVo::Suc($group);
    }

    /**
     * 绑定权限
     *
     * @param Request $request
     * @return Json
     */
    public function bind_auth(Request $request): Json
    {
        $params = $request->param();

        $group = AuthGroupModel::findOrEmpty($params['id']);
        if (empty($group)) return RespVo::Fail(RespVo::CODE_WARING, "记录不存在!");

        $params['auth_id'] = trim($params['auth_id'], ',');
        if (!$group->allowField(['auth_id'])->save($params)) {
            return RespVo::Fail(RespVo::CODE_WARING, "修改失败");
        }

        return RespVo::Suc([]);
    }

    /**
     * 绑定用户
     *
     * @param Request $request
     * @return Json
     */
    public function bind_user(Request $request): Json
    {
        $params = $request->param();

        $admin = AdminModel::FindById($params['admin_id']);
        if ($admin->isEmpty()) return RespVo::Fail(RespVo::CODE_WARING, "管理员不存在!");

        $admin->auth_group = $request->param('id', 0);
        if (!$admin->save()) {
            return RespVo::Fail(RespVo::CODE_WARING, "修改失败");
        }

        return RespVo::Suc([]);
    }
}