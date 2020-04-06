<?php
/**
 * 账号管理
 */

namespace pizepei\basics\controller;


use pizepei\basics\model\account\AccountModel;
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
        'baseAuth'=>'UserAuth:superAdmin',//基础权限继承（加命名空间的类名称）
        'basePath'=>'/account/manage/',//基础路由
        'baseParam'=>'[$Request:pizepei\staging\Request]',//依赖注入对象
    ];

    /**
     * @Author 皮泽培
     * @Created 2019/11/16 11:50
     * @return array [json] 定义输出返回数据
     *      data [object]
     *          limit [int]
     *          page [int]
     *          count [int]
     *          list [objectList]
     *              id  [string]
     *              surname  [string]
     *              name  [string]
     *              nickname [string]
     *              user_name [string]
     *              email  [string]
     *              phone [string]
     *              parent_id [uuid]
     *              logon_online_count [int]
     *              type [int]
     *              status [int]
     *              creation_time [string]
     * @title  获取用户列表
     * @explain 获取用户列表
     * @throws \Exception
     * @router get account-list
     */
    public function accountList()
    {
        succeed(AccountModel::table()
                ->order('creation_time','desc')
                ->fetchAllPage(),'获取成功');
    }

}