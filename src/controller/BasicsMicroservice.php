<?php
/**
 * 应用端微服务管理
 */

namespace pizepei\basics\controller;


use pizepei\staging\Request;

class BasicsMicroservice
{

    /**
     * @Author 皮泽培
     * @Created 2019/10/21 14:24
     * @param Request $Request
     *   path [object] 路径参数
     *   get [object] 路径参数
     *   post [object] post参数
     *   rule [object] 数据流参数
     * @return array [json] 定义输出返回数据
     *      id [uuid] uuid
     *      name [object] 同学名字
     * @title  路由标题
     * @explain 路由功能说明
     * @authGroup basics.menu.getMenu:权限分组1,basics.index.menu:权限分组2
     * @authExtend UserExtend.list:拓展权限
     * @baseAuth Resource:public
     * @resourceType microservice
     * @throws \Exception
     * @router get  test
     */
    public function test(Request $Request)
    {

    }
}