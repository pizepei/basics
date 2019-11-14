<?php
/**
 * 首页控制器
 */
namespace pizepei\basics\controller;
use pizepei\basics\service\layuiadmin\BasicsLayuiAdminService;
use pizepei\staging\Controller;

class BasicsHome extends Controller
{
    /**
     * 基础控制器信息
     */
    const CONTROLLER_INFO = [
        'User'=>'pizepei',
        'title'=>'后台首页控制台',//控制器标题
        'className'=>'Home',//门面控制器名称
        'namespace'=>'app\bases',//门面控制器命名空间
        'baseAuth'=>'基础权限继承（加命名空间的类名称）',//基础权限继承（加命名空间的类名称）
        'authGroup'=>'[user:用户相关,admin:管理员相关]',//[user:用户相关,admin:管理员相关] 权限组列表
        'basePath'=>'/home/',//基础路由
        'baseParam'=>'[$Request:pizepei\staging\Request]',//依赖注入对象
    ];
    /**
     * @return array [html]
     * @title  / 默认首页
     * @explain 注意所有 path 路由都使用 正则表达式为唯一凭证 所以 / 路由只能有一个
     * @baseAuth UserAuth:public
     * @router get /index.html
     */
    public function index()
    {
        return (new BasicsLayuiAdminService())->getIndexHtml($_SERVER['SERVER_NAME']);
    }

    /**
     * @return array [js]
     * @title  / 默认首页
     * @explain 注意所有 path 路由都使用 正则表达式为唯一凭证 所以 / 路由只能有一个
     * @baseAuth UserAuth:public
     * @router get config.js
     */
    public function homeConfig()
    {
        return (new BasicsLayuiAdminService())->getConfig($_SERVER['SERVER_NAME']);
    }
    /**
     * @return array [js]
     * @title  / 默认首页
     * @explain 注意所有 path 路由都使用 正则表达式为唯一凭证 所以 / 路由只能有一个
     * @baseAuth UserAuth:public
     * @router get index.js
     */
    public function homeIndex()
    {
        return (new BasicsLayuiAdminService())->getIndexJs($_SERVER['SERVER_NAME']);
    }
}