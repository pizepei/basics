<?php
/**
 * 角色相关控制器
 */

namespace pizepei\basics\controller;


use authority\app\Resource;
use pizepei\basics\model\account\AccountRoleMenuModel;
use pizepei\basics\model\account\AccountRoleModel;
use pizepei\basics\service\BasicsMenuService;
use pizepei\model\db\Model;
use pizepei\staging\Controller;
use pizepei\staging\Request;

class BasicsRole extends Controller
{
    /**
     * 基础控制器信息
     */
    const CONTROLLER_INFO = [
        'User'=>'pizepei',
        'title'=>'角色相关控制器',//控制器标题
        'namespace'=>'bases',//门面控制器命名空间
        'baseAuth'=>'UserAuth:test',//基础权限继承（加命名空间的类名称）
        'basePath'=>'/basics/role/',//基础路由
    ];

    /**
     * @Author 皮泽培
     * @Created 2019/11/15 14:12
     * @param Request $Request
     *   post [object] post参数
     *      name [string required] 角色名称
     *      remark [string required] 角色备注
     *      type [int required] 角色类型
     *      status [int required] 角色状态
     * @return array [json] 定义输出返回数据
     *      data [raw]
     * @title  添加角色
     * @explain 添加角色
     * @authExtend UserExtend.list:拓展权限
     * @baseAuth Resource:public
     * @throws \Exception
     * @router post role-info
     */
    public function addRole(Request $Request)
    {
        $this->succeed(AccountRoleModel::table()->add($Request->post()),'添加成功');
    }
    /**
     * @Author 皮泽培
     * @Created 2019/11/15 14:55
     * @param Request $Request
     * @return array [json] 定义输出返回数据
     *      data [object]
     *          list [objectList]
     *              id [uuid]
     *              name [string] 角色名称
     *              apps_id [uuid] 应用id
     *              remark [string] 角色备注
     *              type [int] 账号类型1普通子账号common、2游客tourist、3应用账号app、4应用管理员appAdmin、5应用超级管理员appSuperAdmin、6超级管理员Administrators
     *              status [int] 状态1等待审核、2审核通过3、禁止使用4、保留
     *              update_time [string] 更新时间
     *              creation_time [string] 创建时间
     * @title  获取角色列表
     * @authExtend UserExtend.list:拓展权限
     * @baseAuth Resource:public
     * @throws \Exception
     * @router get list
     */
    public function lsit(Request $Request)
    {
        $this->succeed(['list'=>AccountRoleModel::table()->fetchAll()],'获取成功');
    }
    /**
     * @Author 皮泽培
     * @Created 2019/11/15 16:10
     * @param Request $Request
     *   path [object] 路径参数
     * @return array [json] 定义输出返回数据
     * @title  删除角色
     * @explain 如果角色上有账号不允许删除
     * @authExtend UserExtend.list:拓展权限
     * @baseAuth Resource:public
     * @throws \Exception
     * @router delete role-info/:id[uuid]
     */
    public function del(Request $Request)
    {
        $this->succeed(AccountRoleModel::table()->del($Request->path()));
    }
    /**
     * @Author 皮泽培
     * @Created 2019/11/15 16:10
     * @param Request $Request
     *   path [object]
     *      id [uuid] 角色id
     *   raw [object] 路径参数
     *          name [string required] 角色名称
     *          apps_id [uuid required] 应用id
     *          remark [string required] 角色备注
     *          type [int required] 账号类型1普通子账号common、2游客tourist、3应用账号app、4应用管理员appAdmin、5应用超级管理员appSuperAdmin、6超级管理员Administrators
     *          status [int required] 状态1等待审核、2审核通过3、禁止使用4、保留
     * @return array [json] 定义输出返回数据
     * @title  更新角色
     * @explain 更新角色
     * @authExtend UserExtend.list:拓展权限
     * @baseAuth Resource:public
     * @throws \Exception
     * @router put role-info/:id[uuid]
     */
    public function update(Request $Request)
    {
        $this->succeed(AccountRoleModel::table()->where(['id'=>$Request->path('id')])->update($Request->raw()));
    }

    /**
     * @Author 皮泽培
     * @Created 2019/11/16 9:16
     * @param Request $Request
     *   path [object] 路径参数
     *      roleId [uuid] 角色id
     * @return array [json] 定义输出返回数据
     * @title  通过角色id获取对应的角色菜单信息
     * @explain 通过角色id获取对应的角色菜单信息
     * @authExtend UserExtend.list:拓展权限
     * @baseAuth Resource:public
     * @throws \Exception
     * @router get  role-menu/:roleId[uuid]
     */
    public function getRoleMenu(Request $Request)
    {
        if ($Request->path('roleId') ===Model::UUID_ZERO){
            $this->error('请选择角色');
        }
        # 通过角色id获取对应的角色菜单信息 从数据库获取当前角色可看的菜单id
        $rolemMenuIdData = AccountRoleMenuModel::table()->where(['role_id'=>$Request->path('roleId')])->fetch(['gather']);
        $MenuData = (new  BasicsMenuService())->getTreeMenu('admin',true,$Request->path('roleId'),$rolemMenuIdData['gather'],'showChecked');
        # 获取当前项目的所有菜单
        return $this->succeed($MenuData);
    }
    /**
     * @Author 皮泽培
     * @Created 2019/11/16 9:16
     * @param Request $Request
     *   path [object] 路径参数
     *      roleId [uuid] 角色id
     *   raw [object]
     *      data [raw]
     * @return array [json] 定义输出返回数据
     * @title 更新角色的菜单权限
     * @explain 更新角色的菜单权限
     * @authExtend UserExtend.list:拓展权限
     * @baseAuth Resource:public
     * @throws \Exception
     * @router put  role-menu/:roleId[uuid]
     */
    public function updateRoleMenu(Request $Request)
    {
        $BasicsMenuService = new  BasicsMenuService();
        $MenuId = $BasicsMenuService->updateRoleMenuId($Request->raw('data'));
        # 通过角色id获取对应的角色菜单信息 从数据库获取当前角色可看的菜单id
        $rolemMenuData = AccountRoleMenuModel::table()->where(['role_id'=>$Request->path('roleId')])->fetch();
        if (empty($rolemMenuData)){
            succeed(AccountRoleMenuModel::table()->add(['role_id'=>$Request->path('roleId'),'gather'=>$MenuId]),'保存成功');
        }else{
            succeed(AccountRoleMenuModel::table()->where(['role_id'=>$Request->path('roleId')])->update(['gather'=>$MenuId]),'更新成功');
        }
    }

    /**
     * @Author 皮泽培
     * @Created 2019/11/16 9:16
     * @param Request $Request
     *   path [object] 路径参数
     *      roleId [uuid] 角色id
     *   raw [object]
     *      data [raw]
     * @return array [json] 定义输出返回数据
     * @title 更新角色的菜单权限
     * @explain 更新角色的菜单权限
     * @authExtend UserExtend.list:拓展权限
     * @baseAuth Resource:public
     * @throws \Exception
     * @router get  role-authority/:roleId[uuid]
     */
    public function getRoleAuthority()
    {
        var_dump($this->app->Route()->Permissions);

//        # 获取当前角色的api权限详情
        return $this->succeed([
            'list'=>Resource::initJurisdictionList($this->app->Route()->Permissions,$this->app),
            'checkedId'=>['getMenu','409bfd433e7dd7af7d7530ad5fb7bc46'],
        ]);
    }

}