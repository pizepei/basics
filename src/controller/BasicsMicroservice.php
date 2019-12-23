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


use pizepei\basics\service\microservice\BasicsMicroserviceAppsService;
use pizepei\staging\Controller;
use pizepei\staging\Request;

class BasicsMicroservice extends Controller
{
    /**
     * 基础控制器信息
     */
    const CONTROLLER_INFO = [
        'User'=>'pizepei',
        'title'=>'应用端微服务管理',//控制器标题
        'namespace'=>'bases',//门面控制器命名空间
        'baseAuth'=>'MicroserviceAuth:initializeData',//基础权限继承（加命名空间的类名称）
        'basePath'=>'/basics/microservice/',//基础路由
        'baseParam'=>'[$Request:pizepei\staging\Request]',//依赖注入对象
    ];

    /**
     * @Author 皮泽培
     * @Created 2019/10/21 14:24
     * @param Request $Request
     *   path [object] 路径参数
     *      appid [uuid] 应用appid
     *   get [object] 路径参数
     *   post [object] post参数
     *          configId [uuid required]  配置id
     *          number [int required] 手机号码
     *          TemplateParam [object]
     *              code   [int required] 验证码
     *   rule [object] 数据流参数
     * @return array [json] 定义输出返回数据
     *      id [uuid] uuid
     *      name [object] 同学名字
     * @title  路由标题
     * @explain 路由功能说明
     * @authExtend MicroserviceAuth.list:拓展权限
     * @baseAuth MicroserviceAuth:initializeData
     * @resourceType microservice
     * @throws \Exception
     * @router post  test/:appid[uuid]
     */
    public function test(Request $Request)
    {
        return $Request->post();
    }

    /**
     * @Author 皮泽培
     * @Created 2019/10/21 14:24
     * @param Request $Request
     *   path [object] 路径参数
     *   post [object] post参数
     *      name [string required] 应用名称
     *      icon [string required] 应用图标
     *      remark [string required] 应用备注
     *      project_id [objectList] 项目标识集合
     *      jurisdiction [objectList] 权限集合
     *      ip_white_list [raw] ip白名单
     *      sort [int] 排序
     *   rule [object] 数据流参数
     * @return array [json] 定义输出返回数据
     * @title  添加微服务应用
     * @explain 微服务应用为微服务集合
     * @authExtend MicroserviceAuth.list:拓展权限
     * @baseAuth MicroserviceAuth:public
     * @resourceType microservice
     * @throws \Exception
     * @router post  apps
     */
    public function addApps(Request $Request)
    {
        return BasicsMicroserviceAppsService::addApps($Request->post());
    }

    /**
     * @Author 皮泽培
     * @Created 2019/10/21 14:24
     * @param Request $Request
     *   path [object] 路径参数
     *   post [object] post参数
     *      name [string required] 应用名称
     *      remark [string required] 应用备注
     *      groups [uuid] 分组id
     *      sort [int] 排序
     *   rule [object] 数据流参数
     * @return array [json] 定义输出返回数据
     * @title  添加微服务应用
     * @explain 微服务应用为微服务集合
     * @authExtend MicroserviceAuth.list:拓展权限
     * @baseAuth MicroserviceAuth:public
     * @resourceType microservice
     * @throws \Exception
     * @router post  ms/config/groups
     */
    public function addMicroserviceCentreConfigGroups(Request $Request)
    {
        return BasicsMicroserviceAppsService::addMicroserviceCentreConfigGroups($Request->post());
    }
    /**
     * @Author 皮泽培
     * @Created 2019/10/26 14:36
     * @param Request $Request
     *   post [object] post参数
     *      name [string required] 应用名称
     *      remark [string required] 应用备注
     *      groups [uuid required] 分组id
     *      tage [string] 标签
     *      ip_white_list [raw] ip白名单
     *      sort [int] 排序
     * @return array [json] 定义输出返回数据
     * @title  路由标题
     * @explain 路由功能说明
     * @authExtend UserExtend.list:拓展权限
     * @baseAuth Resource:public
     * @return array
     * @throws \Exception
     * @router post ms/config
     * @throws \Exception
     */
    public function addMicroserviceCentreConfig(Request $Request)
    {
        return BasicsMicroserviceAppsService::addMicroserviceCentreConfig($Request->post());

    }
    /**
     * @Author 皮泽培
     * @Created 2019/10/26 14:36
     * @param Request $Request
     *   path [object]
     *      appid [uuid] 配置中心应用appid
     *   get [object] post参数
     *          timestamp [int required]   时间戳
     *          nonce [int required required]   随机数
     *          encrypt_msg [string required] 加密的数据
     *          signature [string required] 签名
     * @return array [json] 定义输出返回数据
     *      data [raw]
     * @title  微服务获取配置接口
     * @explain 微服务获取配置接口
     * @baseAuth Resource:public
     * @return array
     * @throws \Exception
     * @router get ms/config/:appid[uuid]
     * @throws \Exception
     */
    public function getMicroserviceCentreConfig(Request $Request)
    {
        return $this->succeed(BasicsMicroserviceAppsService::getFarAppsConfig($Request->path('appid')),'成功');
    }
    /**
     * @Author 皮泽培
     * @Created 2019/10/21 14:24
     * @param Request $Request
     *   path [object] 路径参数
     *      appid [uuid] MicroserviceCentreConfig的 appid
     *   raw [object] post参数
     *          timestamp [int required]   时间戳
     *          nonce [int required required]   随机数
     *          encrypt_msg [string required] 加密的数据
     *          signature [string required] 签名
     *          urlencode [bool] 是否使用urlencode
     * @return array [json] 定义输出返回数据
     *      data  [raw] apps配置
     *          timestamp [int required]   时间戳
     *          nonce [int required required]   随机数
     *          encrypt_msg [string required] 加密的数据
     *          signature [string required] 签名
     *          urlencode [bool] 是否使用urlencode
     * @title  配置中心响应微服务apps配置
     * @explain 配置中心响应微服务apps配置
     * @baseAuth MicroserviceAuth:public
     * @throws \Exception
     * @router post  apps/config/:appid[uuid]
     */
    public function getAppsConfig(Request $Request)
    {
        return $this->succeed(BasicsMicroserviceAppsService::getLocalAppsConfig($Request->raw(),$Request->path('appid')),'获取成功');
    }

}