<?php
/**
 * Class BasicsMicroserviceAppsService
 * 微服务应用
 */
declare(strict_types=1);

namespace pizepei\basics\service\microservice;

use pizepei\basics\model\microservice\MicroserviceAppsConfigModel;

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

}