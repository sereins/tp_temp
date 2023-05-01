<?php

namespace app\controller\admin;

use app\BaseController;
use app\model\AuthModel;
use app\Request;
use app\RespVo;
use app\validate\admin\AuthVa;
use think\exception\ValidateException;
use think\response\Json;

class Auth extends BaseController
{

    /**
     * 新增权限
     *
     * @param Request $request
     * @return Json
     */
    public function create(Request $request): Json
    {
        $data = $request->param();

        // 验证
        try {
            validate(AuthVa::class)->scene('create')->check($data);
        } catch (ValidateException $e) {
            return RespVo::Fail(RespVo::CODE_INFO, $e->getMessage());
        }

        // 名称和p_id唯一记录
        $exits = AuthModel::where([
            ['name', '=', $data['name']],
            ['p_id', '=', $data['p_id']],
        ])->value('id');
        if ($exits) return RespVo::Fail(RespVo::CODE_INFO, "权限【{$data['name']}】已经存在");

        $data['time_add'] = date("Y-m-d H:i:s");
        $data['time_put'] = $data['time_add'];
        $model = new AuthModel();
        if (!$model->save($data)) {
            return RespVo::Fail(RespVo::CODE_INFO, '新增权限失败!');
        }

        return RespVo::Suc([]);
    }

    /**
     * 根据id删除权限
     *
     * @param Request $request
     * @return Json
     */
    public function del(Request $request): Json
    {
        $id = $request->param('id');
        if (empty($id)) return RespVo::Fail(RespVo::CODE_INFO, "id不能为空");

        $auth = AuthModel::findOrEmpty($id);
        if (empty($auth)) return RespVo::Fail(RespVo::CODE_INFO, "权限不存在");

        // 当前权限是否绑定的有子权限
        $check = AuthModel::where('p_id', $id)->value('id');
        if ($check) return RespVo::Fail(RespVo::CODE_INFO, "当前权限绑定有子权限不可删除");

        if (!$auth->delete()) {
            return RespVo::Fail(RespVo::CODE_INFO, "删除失败");
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
            validate(AuthVa::class)->scene('edit')->check($data);
        } catch (ValidateException $e) {
            return RespVo::Fail(RespVo::CODE_INFO, $e->getMessage());
        }

        $auth = AuthModel::findOrEmpty($data['id']);
        if (!$auth->allowField(['name', 'url', 'path', 'icon'])->save($data)) {
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
            validate(AuthVa::class)->scene('disable')->check($data);
        } catch (ValidateException $e) {
            return RespVo::Fail(RespVo::CODE_INFO, $e->getMessage());
        }

        $admin = AuthModel::findOrEmpty($data['id']);
        if ($admin->isEmpty()) return RespVo::Fail(RespVo::CODE_WARING, "权限不存在");

        if (!$admin->allowField(['state'])->save($data)) {
            return RespVo::Fail(RespVo::CODE_WARING, "操作失败");
        }

        return RespVo::Suc('success');
    }

    /**
     * 权限菜单
     *
     * @param Request $request
     * @return Json
     */
    public function menu(Request $request): Json
    {
        // todo 需要和用户权限关联
        $auths = AuthModel::where([
            ['type', '=', 0]
        ])->field('id,name,path,icon')->select()->toArray();

        return RespVo::Suc($auths);
    }

    /**
     * 用户所有的权限
     *
     * @return Json
     */
    public function lists(): Json
    {
        // todo 需要和用户权限关联
        $auths = AuthModel::where([
            ['type', '=', 1],
            ['state', '=', 1]
        ])->field('id,name,url')->select()->toArray();

        return RespVo::Suc($auths);
    }

    /**
     * 权限树形结构
     *
     * @return Json
     */
    public function tree(): Json
    {
        // todo 需要和用户权限关联
        $auths = AuthModel::where([
            ['state', '=', 1]
        ])->field('id,name,url,p_id')->select()->toArray();

        return RespVo::Suc($auths);
    }
}