<?php
/**
 * 账号管理
 */

namespace pizepei\basics\controller;


use pizepei\staging\Controller;

class BasicsAccountManage extends Controller
{
    /**
     * 基础控制器信息
     */
    const CONTROLLER_INFO = [
        'User'=>'pizepei',
        'title'=>'账号管理控制器',//控制器标题
        'namespace'=>'bases',//门面控制器命名空间
        'baseAuth'=>'UserAuth:test',//基础权限继承（加命名空间的类名称）
        'basePath'=>'/account/manage/',//基础路由
        'baseParam'=>'[$Request:pizepei\staging\Request]',//依赖注入对象
    ];

    /**
     * @Author 皮泽培
     * @Created 2019/11/16 11:50
     * @return array [json] 定义输出返回数据
     *      data [object]
     *          lsit [objectList]
     * @title  路由标题
     * @explain 路由功能说明
     * @baseAuth Resource:public
     * @throws \Exception
     * @router get
     */
    public function accountList()
    {

    }

}