<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/10/21
 * Time: 11:28
 * @baseControl pizepei\basics\src\controller\BasicsMicroservice
 * @baseAuth
 * @title 应用端微服务管理
 * @authGroup [user:用户相关,admin:管理员相关] 权限组列表
 * @basePath /basics/microservice/
 * @baseParam [$Request:pizepei\staging\Request] 注册依赖注入对象
 */


namespace pizepei\basics\controller;


use pizepei\staging\Controller;
use pizepei\staging\Request;

class BasicsMicroservice extends Controller
{

    /**
     * @Author 皮泽培
     * @Created 2019/10/21 14:24
     * @param Request $Request
     *   path [object] 路径参数
     *      appid [uuid] 应用appid
     *   get [object] 路径参数
     *   post [object] post参数
     *   rule [object] 数据流参数
     * @return array [json] 定义输出返回数据
     *      id [uuid] uuid
     *      name [object] 同学名字
     * @title  路由标题
     * @explain 路由功能说明
     * @authGroup basics.menu.getMenu:权限分组1,basics.index.menu:权限分组2
     * @authExtend MicroserviceAuth.list:拓展权限
     * @baseAuth MicroserviceAuth:initializeData
     * @resourceType microservice
     * @throws \Exception
     * @router get  test/:appid[uuid]
     */
    public function test(Request $Request)
    {
        return $Request->input();
    }
}