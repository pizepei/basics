<?php
/**
 * @Author: pizepei
 * @ProductName: PhpStorm
 * @Created: 2019/4/23 22:33
 * @title 菜单相关控制器
 */
namespace pizepei\basics\controller;

use pizepei\basics\service\BasicsMenuService;
use pizepei\staging\Controller;
use pizepei\staging\Request;
use service\basics\MenuService;

class BasicsMenu extends Controller
{
    /**
     * 基础控制器信息
     */
    const CONTROLLER_INFO = [
        'User'=>'pizepei',
        'title'=>'菜单相关控制器',//控制器标题
        'namespace'=>'bases',//门面控制器命名空间
        'baseAuth'=>'基础权限继承（加命名空间的类名称）',//基础权限继承（加命名空间的类名称）
        'authGroup'=>'[user:用户相关,admin:管理员相关]',//[user:用户相关,admin:管理员相关] 权限组列表
        'basePath'=>'/admin/menu/',//基础路由
        'baseParam'=>'[$Request:pizepei\staging\Request]',//依赖注入对象
    ];

    /**
     * @Author pizepei
     * @Created 2019/4/23 22:35
     * @return array [json]
     *      data [objectList]
     *          name [string] 一级菜单名称（与视图的文件夹名称和路由路径对应）
     *          title [string] 一级菜单标题
     *          icon [string] 一级菜单图标样式
     *          spread [bool] 是否默认展子菜单
     *          list [objectList] 二级
     *              name [string] 二级菜单名称（与视图的文件夹名称和路由路径对应）
     *              title [string] 二级菜单标题
     *              icon [string] 二级菜单图标样式
     *              spread [bool] 是否默认展子菜单
     *              list [objectList] 三级
     *                  name [string] 三级菜单名称
     *                  title [string] 三级菜单标题
     *                  icon [string] 三级菜单图标样式
     * @title  获取菜单列表
     * @explain 获取菜单列表（权限不同内容不同）
     * @authTiny 微权限提供权限分配 [获取店铺所有  获取所有店铺  获取一个]
     * @authGroup basics.menu.getMenu:获取后台菜单1,basics.index.menu:获取首页导航2,system.admin.getAdmin:获取首页导航3,system.admin.getAdmin:获取首页导航4
     * @authExtend UserExtend.list:删除账号操作
     * @baseAuth UserAuth:test
     * @router get menu-list
     * @throws \Exception
     */
    public function index()
    {
        return $this->succeed((new  BasicsMenuService())->getMenuList());
    }

    /**
     * @Author pizepei
     * @Created 2019/4/23 23:02
     * @param \pizepei\staging\Request $Request
     * @return array [json]
     *      data [object]
     *          username [string]
     *          sex [string]
     *          role [int]
     * @title  用户信息
     * @explain 简单用户信息
     * @authGroup basics.index.user:获取后台菜单
     * @router get session
     */
    public function session(Request $Request)
    {
        $data = [
            "username"=> "pizepei", "sex"=>"男", "role"=> 1
        ];
        return $this->succeed($data);
    }
    /**
     * @Author pizepei
     * @Created 2019/4/23 23:02
     * @param \pizepei\staging\Request $Request
     * @return array [json]
     *     data [object]
     *      newmsg [int] 新信息
     * @title  用户信息
     * @explain 简单用户信息
     * @authTiny 微权限提供权限分配 [获取店铺所有  获取所有店铺  获取一个]
     * @authGroup basics.index.message:控制台新信息
     * @router get message/new
     */
    public function messageNew(Request $Request)
    {
        $data = [
            "newmsg"=>  3
        ];
        return $this->succeed($data);
    }

    /**
     * @Author pizepei
     * @Created 2019/4/23 23:02
     * @param \pizepei\staging\Request $Request
     * @return array [json]
     *     data [raw]
     * @title  用户信息
     * @explain 简单用户信息
     * @authTiny 微权限提供权限分配 [获取店铺所有  获取所有店铺  获取一个]
     * @authGroup basics.index.message:控制台新信息
     * @router get tree-menu-info
     */
    public function getTreeMenu()
    {
        return $this->succeed((new  BasicsMenuService())->getTreeMenu());
    }


    /**
     * @Author pizepei
     * @Created 2019/4/23 23:02
     * @param \pizepei\staging\Request $Request
     *      post [object] post
     *          name [string]   菜单名称（与视图的文件夹名称和路由路径对应）
     *          parent_id [uuid] 父id
     *          title [string] 菜单标题
     *          icon [string] 单图标样式
     *          spread [string] 是否默认展子菜单
     *          jump [string] 默认按照 name 解析。一旦设置，将优先按照 jump 设定的路由跳转
     *          sort [string] 排序
     *          status [int] 1 不显示  2 显示
     * @return array [json]
     *     data [raw]
     * @title  添加后台菜单
     * @explain 添加后台菜单
     * @authGroup basics.index.message:控制台新信息
     * @router post admin-menu
     */
    public function addAdminNenu(Request $Request)
    {
        return $this->succeed((new  BasicsMenuService())->addMenu($Request->post()),'添加成功');
    }


    /**
     * @Author pizepei
     * @Created 2019/4/23 23:02
     * @param \pizepei\staging\Request $Request
     *      path [object]
     *          id [uuid] 菜单id
     *      raw [object] post
     *          name [string]  菜单名称（与视图的文件夹名称和路由路径对应）
     *          title [string] 菜单标题
     *          icon [string] 单图标样式
     *          spread [string] 是否默认展子菜单
     *          jump [string] 默认按照 name 解析。一旦设置，将优先按照 jump 设定的路由跳转
     *          sort [string] 排序
     *          status [int] 1 不显示  2 显示
     * @return array [json]
     *     data [raw]
     * @title  更新后台菜单
     * @explain 更新后台菜单
     * @authGroup basics.index.message:控制台新信息
     * @router put admin-menu/:id[uuid]
     */
    public function updateAdminNenu(Request $Request)
    {
        return $this->succeed((new  BasicsMenuService())->updateMenu($Request->path('id'),$Request->raw()),'更新成功');
    }
    /**
     * @Author pizepei
     * @Created 2019/4/23 23:02
     * @param \pizepei\staging\Request $Request
     *      path [object]
     *          id [uuid] 菜单id  uuid
     * @return array [json]
     *     data [raw]
     * @title  添加后台菜单
     * @explain 添加后台菜单
     * @authGroup basics.index.message:控制台新信息
     * @router delete admin-menu/:id[uuid]
     */
    public function delAdminNenu(Request $Request)
    {
        return $this->succeed((new  BasicsMenuService())->delMenu($Request->path('id')),'删除成功');
    }
    /**
     * @Author pizepei
     * @Created 2019/4/23 23:02
     * @param \pizepei\staging\Request $Request
     *      path [object]
     *          id [uuid] 菜单id  uuid
     * @return array [json]
     *     data [raw]
     * @title  获取菜单详情
     * @explain 获取菜单详情
     * @authGroup basics.index.message:控制台新信息
     * @router get admin-menu/:id[uuid]
     */
    public function getAdminNenu(Request $Request)
    {
        return $this->succeed((new  BasicsMenuService())->getMenuInfo($Request->path('id')),'获取成功');
    }

}