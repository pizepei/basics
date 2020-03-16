<?php
/**
 * 网站配置控制器
 */

namespace pizepei\basics\controller;


use pizepei\basics\model\account\AccountLoginLogModel;
use pizepei\staging\Controller;
use pizepei\staging\Request;

class BasicsConfig extends Controller
{
    /**
     * 基础控制器信息
     */
    const CONTROLLER_INFO = [
        'User'=>'pizepei',
        'title'=>'网站配置控制器',//控制器标题
        'namespace'=>'bases',//门面控制器命名空间
        'baseAuth'=>'UserAuth:test',//基础权限继承（加命名空间的类名称）
        'basePath'=>'/home/config/',//基础路由
        'baseParam'=>'[$Request:pizepei\staging\Request]',//依赖注入对象
    ];

    /**
     * @Author 皮泽培
     * @Created 2020/3/16 11:47
     * @param Request $Request
     * @return array [json] 定义输出返回数据
     *      data [object]
     *          id [uuid] uuid
     *          name [object] 同学名字
     * @title  网站配置列表
     * @explain 路由功能说明
     * @authGroup basics.menu.getMenu:权限分组1,basics.index.menu:权限分组2
     * @baseAuth Resource:public
     * @throws \Exception
     * @router get  info
     */
    public function info(Request $Request)
    {
        AccountLoginLogModel::table()->fetch();
    }

}