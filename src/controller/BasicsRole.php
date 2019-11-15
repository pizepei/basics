<?php
/**
 * 角色相关控制器
 */

namespace pizepei\basics\controller;


use pizepei\basics\model\account\AccountRoleModel;
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
        'baseAuth'=>'基础权限继承（加命名空间的类名称）',//基础权限继承（加命名空间的类名称）
        'authGroup'=>'[user:用户相关,admin:管理员相关]',//[user:用户相关,admin:管理员相关] 权限组列表
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
     * @authGroup basics.menu.getMenu:权限分组1,basics.index.menu:权限分组2
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
     * @explain 角色列表
     * @authGroup basics.menu.getMenu:权限分组1,basics.index.menu:权限分组2
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
     * @authGroup basics.menu.getMenu:权限分组1,basics.index.menu:权限分组2
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
     * @authGroup basics.menu.getMenu:权限分组1,basics.index.menu:权限分组2
     * @authExtend UserExtend.list:拓展权限
     * @baseAuth Resource:public
     * @throws \Exception
     * @router put role-info/:id[uuid]
     */
    public function update(Request $Request)
    {
        $this->succeed(AccountRoleModel::table()->where(['id'=>$Request->path('id')])->update($Request->raw()));
    }
}