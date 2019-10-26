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
     * @title  路由标题
     * @return array
     * @throws \Exception
     */
    public static function getMicroserviceCentreConfig(string $appid,bool $Cache=true){
        # 通过appid 获取配置
        if ($Cache){
            $cacheData = Cache::get(['MicroserviceCentreConfig',$appid],'Microservice');
        }else{
            $cacheData = null;
        }
        if (!$cacheData){
            $cacheData = MicroserviceCentreConfigModel::table()->where(['appid'=>$appid])->fetch();
            if (!$cacheData){ throw new \Exception('Configuration does not exist');}
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
    public static function getFarAppsConfig($appid)
    {
        $data = [
            'appid'=>$appid,
            'action'=>'getFarAppsConfig',
        ];
        $Prpcrypt = new Prpcrypt(\Deploy::MicroService['encodingAesKey']);
        return $Prpcrypt->yieldCiphertext(Helper()->json_encode($data),\Deploy::MicroService['appid'],\Deploy::MicroService['token'],\Deploy::MicroService['urlencode']);
    }

    # 获取apps配置（微服务管理中心使用）
    public static function getLocalAppsConfig(array $data,string $appid)
    {
        $MicroserviceCentreConfig = static::getMicroserviceCentreConfig($appid);
        # 验证
        $Prpcrypt = new Prpcrypt($MicroserviceCentreConfig['encodingAesKey']);
        $res = $Prpcrypt->decodeCiphertext($MicroserviceCentreConfig['token'],$data);
        $body = Helper()->json_decode($res);
        if (!$body){ throw new \Exception('CentreConfig [error body]');}
        if (!isset($res['action'])){ throw new \Exception('CentreConfig [error body action]');}
        if ($res['action'] =='getFarAppsConfig'){ throw new \Exception('CentreConfig [error body getFarAppsConfig]');}

        return Helper()->json_decode($res);
        return $Prpcrypt->decodeCiphertext($MicroserviceCentreConfig['token'],$data);
    }
}