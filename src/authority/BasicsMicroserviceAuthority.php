<?php
/**
 * 2019 10 21
 * 微服务权限控制扩展
 */

namespace pizepei\basics\authority;


use pizepei\staging\App;

class BasicsMicroserviceAuthority
{
    protected $authExtend=[];

    /**
     * Authority constructor.
     * @param $pattern
     * @param App $app
     */
    public function __construct($pattern,App $app)
    {
        $this->app = $app;
        # jwt模式
        $this->pattern = $pattern;
    }
    /**
     * 统一返回
     * @param $parameter 方法
     * @return array
     */
    public function start($parameter)
    {
        if($parameter === 'public'){
            $this->status = true;
        }
        return $this->$parameter();
    }
    /**
     * @Author 皮泽培
     * @Created 2019/10/22 16:58
     * @return array [json] 定义输出返回数据
     * @title  路由标题
     * @explain 路由功能说明
     * @throws \Exception
     */
    public function initializeData()
    {
//        var_dump($this->$parameter());

        # 获取appid  appid只支持path传递

        #通过appid 获取 应用配置

        # 通过应用配置  IP白名单-》接口权限-》签名-》数据解析

        # 进入路由控制器方法内部

        # 正常获取路由配置中的参数

    }



}