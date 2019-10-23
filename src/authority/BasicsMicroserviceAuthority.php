<?php
/**
 * 2019 10 21
 * 微服务权限控制扩展
 */

namespace pizepei\basics\authority;


use pizepei\staging\App;
use pizepei\staging\BasicsAuthority;

class BasicsMicroserviceAuthority extends BasicsAuthority
{
    protected $authExtend=[];
    /**
     * 资源应用appid
     * @var string
     */
    protected $appid = '';
    /**
     * 应用配置
     * @var array
     */
    protected $appConfig = [];
    /**
     * @Author 皮泽培
     * @Created 2019/10/22 16:58
     * @return array [json] 定义输出返回数据
     * @title  路由标题
     * @explain 路由功能说明
     * @throws \Exception
     */
    public function initializeData(...$data)
    {
//        var_dump($this->$parameter());

        # 获取appid  appid只支持path传递
        $this->appid = $this->app->Request()->path('appid');
        if ($this->appid === null){
            throw new  \Exception('appid  necessary');
        }
        # 通过appid 获取 应用配置
        #  考虑到应用配置统一问题
        #       通过接口去配置中心获取配置？
        #       每个微服务都有一份配置？



        # 通过应用配置  IP白名单-》接口权限-》签名-》数据解析

        # 进入路由控制器方法内部

        # 正常获取路由配置中的参数

    }



}