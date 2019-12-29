<?php
/**
 * Class BasicsMicroserviceAppsService
 * 微服务应用
 */
declare(strict_types=1);

namespace pizepei\basics\service\microservice;

use pizepei\basics\model\microservice\MicroserviceAppsConfigModel;
use pizepei\basics\model\microservice\MicroserviceCentreConfigGroupsModel;
use pizepei\basics\model\microservice\MicroserviceCentreConfigModel;
use pizepei\encryption\aes\Prpcrypt;
use pizepei\model\cache\Cache;
use pizepei\model\db\Model;
use pizepei\terminalInfo\TerminalInfo;

class BasicsMicroserviceAppsService
{
    /**
     * @Author 皮泽培
     * @Created 2019/10/24 16:51
     * @return array [json] 定义输出返回数据
     * @title  添加apps应用
     * @explain 添加apps应用
     * @throws \Exception
     */
    public static function addApps($data)
    {
        # 判断是否有添加权限或者超过应用次数

        # 判断应用类型

        # 通过选择的权限判断姓名标识集合  ：处理项目和权限

        # 自动生成appid  加密信息等
        $appsData = [
            'appid'=>Helper()->getUuid(true),
            'name'=>$data['name'],
            'remark'=>$data['remark'],
            'ip_white_list'=>Helper()->json_decode($data['ip_white_list']),
            'appsecret'=>Helper()->str()->str_rand(32),
            'encodingAesKey'=>Helper()->str()->str_rand(43),
            'token'=>Helper()->str()->str_rand(32),
        ];
        return MicroserviceAppsConfigModel::table()->add($appsData);
    }

    /**
     * @Author 皮泽培
     * @Created 2019/10/26 10:42
     * @param $data
     * @return array [json] 定义输出返回数据
     * @title  添加微服务配置中心的分组
     * @explain 路由功能说明
     * @throws \Exception
     */
    public static function addMicroserviceCentreConfigGroups($data)
    {
        $ser = MicroserviceCentreConfigGroupsModel::table()->where(['name'=>$data['name']])->fetch();
        if (!empty($ser)){
            throw new  \Exception('服务名称已经存在');
        }
        return MicroserviceCentreConfigGroupsModel::table()->add($data);
    }

    /**
     * @Author 皮泽培
     * @Created 2019/10/26 14:34
     * @param $data
     * @return array [json] 定义输出返回数据
     * @title  添加微服务中心配置
     * @explain 路由功能说明
     * @throws \Exception
     */
    public static function addMicroserviceCentreConfig($data)
    {
        if (!isset($data['ip_white_list'])){ throw  new  \Exception('IP白名单是必须的');}
        $appsData = [
            'appid'=>Helper()->getUuid(true),
            'name'=>$data['name'],
            'remark'=>$data['remark'],
            'groups'=>$data['groups']??Model::UUID_ZERO,
            'tage'=>$data['tage']??'',
            'ip_white_list'=>Helper()->json_decode($data['ip_white_list']),
            'appsecret'=>Helper()->str()->str_rand(32),
            'encodingAesKey'=>Helper()->str()->str_rand(43),
            'token'=>Helper()->str()->str_rand(32),
            'status'=>2,
        ];
        return MicroserviceCentreConfigModel::table()->add($appsData);
    }

    /**
     * @Author 皮泽培
     * @Created 2019/10/26 17:20
     * @param string $appid  appid
     * @param bool $Cache 是否使用缓存
     * @title  配置中心配置
     * @return array
     * @throws \Exception
     */
    public static function getMicroserviceCentreConfig(string $appid,bool $Cache=true):array
    {
        # 通过appid 获取配置
        if ($Cache){
            $cacheData = Cache::get(['MicroserviceCentreConfig',$appid],'Microservice');
        }else{
            $cacheData = null;
        }
        if (!$cacheData){
            $cacheData = MicroserviceCentreConfigModel::table()->where(['appid'=>$appid])->fetch();
            if (!$cacheData){ throw new \Exception('Configuration does not exist '.$appid);}
            # 设置缓存
            Cache::set(['MicroserviceCentreConfig',$appid],$cacheData,$cacheData['cache_time'],'Microservice');
        }
        return $cacheData;
    }

    /**
     * @Author 皮泽培
     * @Created 2019/10/26 17:51
     * @param $appid  需要获取配置的apps
     * @title  远程获取apps配置（微服务使用）
     * @return array
     * @throws \Exception
     */
    public static function getFarAppsConfig($appid,bool $Cache=true):array
    {

        # appid 是apps 的appid   通过\Deploy::MicroService配置获取对应的应用信息（请求配置中心）
        # 缓存
        if ($Cache){
            $cacheData = Cache::get(['MicroserviceFarAppsConfig',$appid],'Microservice');
        }else{
            $cacheData = null;
        }
        if (!$cacheData){
            $data = [
                'appid'=>$appid,
                'action'=>'getFarAppsConfig',
            ];
            $Prpcrypt = new Prpcrypt(\Deploy::MicroService['encodingAesKey']);
            $data = $Prpcrypt->yieldCiphertext(Helper()->json_encode($data),\Deploy::MicroService['appid'],\Deploy::MicroService['token'],\Deploy::MicroService['urlencode']);

            # 进行请求
            $url = \Deploy::MicroService['url'].\Deploy::MicroService['appid'].'.json';
            $res = Helper()->httpRequest($url,Helper()->json_encode($data),empty(\Deploy::MicroService['hostDomain'])?[]:['header'=>['Host:'.\Deploy::MicroService['hostDomain']]]);
            if ($res['code'] !==200){throw  new \Exception('The request failed   code:'.$res['code']);}
            $body = Helper()->json_decode($res['body']);
            if (!isset($body['data'])){throw  new \Exception('The request failed   body empty '); }
            if (!isset($body['code'] ) || $body['code'] !==200){
                throw  new \Exception($body['msg']??$res['body']);
            }
            # 解密
            $cacheData = Helper()->json_decode($Prpcrypt->decodeCiphertext(\Deploy::MicroService['token'],$body['data']));
            if (!$cacheData){ throw  new \Exception('The request failed   cacheData empty ');  }
            # 缓存
            Cache::set(['MicroserviceFarAppsConfig',$appid],$cacheData,$cacheData['cache_time'],'Microservice');
        }
        return $cacheData;

    }

    /**
     * 获取apps配置（微服务管理中心使用）
     * @param array $data
     * @param string $appid  发起请求的 微服务项目的\Deploy::MicroService['appid']
     * @return array
     * @throws \Exception
     */
    public static function getLocalAppsConfig(array $data,string $appid)
    {
        $MicroserviceCentreConfig = static::getMicroserviceCentreConfig($appid);
        # 验证IP
        $data['clientInfo'] = terminalInfo::get_ip();
        if (!in_array(terminalInfo::get_ip(),$MicroserviceCentreConfig['ip_white_list'])){ throw new \Exception('Illegal request IP  '.terminalInfo::get_ip()); }
        # 验证
        $Prpcrypt = new Prpcrypt($MicroserviceCentreConfig['encodingAesKey']);
        $res = $Prpcrypt->decodeCiphertext($MicroserviceCentreConfig['token'],$data,1200);
        $body = Helper()->json_decode($res);
        if (!$body){ throw new \Exception('CentreConfig [error body]');}
        if (!isset($body['action'])){ throw new \Exception('CentreConfig [error body action]');}
        if ($body['action'] !=='getFarAppsConfig'){ throw new \Exception('CentreConfig [error body getFarAppsConfig]');}
        # 验证通过 获取配置
        $icroserviceApps = static::getMicroserviceAppsConfig($body['appid']);
        if (empty($icroserviceApps)){throw new \Exception('icroserviceApps empty');}
        # 加密返回
        $resData = $Prpcrypt->yieldCiphertext(Helper()->json_encode($icroserviceApps),\Deploy::MicroService['appid'],\Deploy::MicroService['token'],\Deploy::MicroService['urlencode']);
        return $resData;

    }

    /**
     * 获取apps 配置
     * @param string $appid
     * @param bool $Cache
     * @return array
     */
    public static function getMicroserviceAppsConfig(string $appid,bool $Cache=true):array
    {
        # 通过appid 获取配置
        if ($Cache){
            $cacheData = Cache::get(['MicroserviceAppsConfig',$appid],'MicroserviceApps');
        }else{
            $cacheData = null;
        }
        if (!$cacheData){
            $cacheData = MicroserviceAppsConfigModel::table()->where(['appid'=>$appid])->fetch();
            if (!$cacheData){ throw new \Exception('MicroserviceAppsConfig does not exist '.$appid);}
            # 设置缓存
            Cache::set(['MicroserviceAppsConfig',$appid],$cacheData,$cacheData['cache_time'],'MicroserviceApps');
        }
        return $cacheData;

    }
}