<?php
/**
 * 2019 10 21
 * 微服务权限控制扩展
 */

namespace pizepei\basics\authority;


use pizepei\basics\model\microservice\MicroserviceAppsRequestLogModel;
use pizepei\basics\service\microservice\BasicsMicroserviceAppsService;
use pizepei\encryption\aes\Prpcrypt;
use pizepei\model\redis\Redis;
use pizepei\staging\App;
use pizepei\staging\BasicsAuthority;

class BasicsMicroserviceAuthority extends BasicsAuthority
{
    protected $authExtend=[];
    /**
     * 资源应用appid
     * @var string
     */
    protected $appsid = '';
    /**
     * 应用配置
     * @var array
     */
    protected $appsConfig = [];
    /**
     * @Author 皮泽培
     * @Created 2019/10/22 16:58
     * @return array [json] 定义输出返回数据
     * @title  微服务服务端接收请求时对加密数据进行处理
     * @explain 通过部署配置\Deploy::MicroService 从远程配置中心获取到对应的apps 配置参数 然后对客户端加密请求过来的参数进行解密，然后写入请求对象的请求参数
     * @throws \Exception
     */
    public function initializeData(...$data)
    {
        # 获取appid  appid只支持path传递  （这里的appid 是apps应用的appid）
        $this->appsid = $this->app->Request()->path('appid');
        if ($this->appsid === null){
            throw new  \Exception('appid  necessary');
        }
        # 通过部署配置\Deploy::MicroService 从远程配置中心获取到对应的apps 配置参数（进行缓存）

        # syncLock
        Helper()->syncLock(Redis::init(),['BasicsMicroservice','AppsService','getFarAppsConfig',$this->appsid]);
        $this->appsConfig = BasicsMicroserviceAppsService::getFarAppsConfig($this->appsid);
        # 解除  syncLock
        Helper()->syncLock(Redis::init(),['BasicsMicroservice','AppsService','getFarAppsConfig',$this->appsid],false);

        if (empty($this->appsConfig)){
            throw new \Exception('AppsConfig not exist');
        }

        # 过滤IP
        if(!in_array($this->app->__CLIENT_IP__,$this->appsConfig['ip_white_list'])){
            throw new \Exception('Illegal IP '.$this->app->__CLIENT_IP__);
        }
        # 验证当前服务模块是否在其中

        # 进行签名验证
        $Prpcrypt = new Prpcrypt($this->appsConfig['encodingAesKey']);
        $body = Helper()->json_decode(file_get_contents("php://input",true));
        if (Helper()->is_empty($body,['nonce','timestamp','signature','encrypt_msg'])){
            throw new \Exception('nonce timestamp signature encrypt_msg  empty');
        }
        $res = $Prpcrypt->decodeCiphertext($this->appsConfig['token'],$body);
        # 重新放置给请求对象的POST
        $this->app->Request()->POST = Helper()->json_decode($res);
        # 进行权限验证（把当前请求当一个在线用户使用当前用户的权限控制）使用框架权限过滤进行过滤

    }

    /**
     * @Author 皮泽培
     * @Created 2019/11/1 14:32
     * @title  记录微服务apps请求日志
     * @explain 此请求日志为正常请求响应日志 throw 异常日志 不记录
     * @throws \Exception
     */
    public function setMsAppsResponseLog($result)
    {
        if (!is_array($result)){
            $result = Helper()->json_decode($result);
        }
        $AppsRequestLogModel = MicroserviceAppsRequestLogModel::table($this->appsid);
        $AppsRequestLogModel->add([
            'appid'=>\Deploy::MicroService['appid'],        # 当前微服务的配置ID
            'apps_config_id'=>$this->appsConfig['id'],      # apps config配置id
            'request_id'    =>$this->app->__REQUEST_ID__,   # 请求ID
            'request'       =>$this->app->Request()->POST,  # 解密的请求数据  psot
            'api'           =>$this->app->Route()->atRoute, # 当前路由
            'module_prefix' =>\Deploy::MODULE_PREFIX,       # 当前微服务的模块路径
            'response'      =>$result,
        ]);
    }

}