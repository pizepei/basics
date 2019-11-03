<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/1/15
 * Time: 16:24
 * @title 权限控制基础类 
 */
declare(strict_types=1);
namespace pizepei\basics\authority;

use pizepei\basics\service\account\BasicsAccountService;
use pizepei\model\redis\Redis;
use pizepei\staging\App;
use pizepei\staging\AuthorityInterface;
use service\basics\account\AccountService;

class BasicsAuthority extends \pizepei\staging\BasicsAuthority
{

    /**
     *  获取 property
     *
     * @param $propertyName
     * @return |null
     */
    public function __get($propertyName)
    {
        if(isset($this->$propertyName)){
            return $this->$propertyName;
        }
        return null;
    }


    /**
     * @Author 皮泽培
     * @Created 2019/10/22 17:49
     * @return bool 是否登录
     * @title  判断是否登录
     * @explain 判断是否登录
     * @throws \Exception
     */
    public function is_login():bool
    {
        // *方法路由：注册到不同操作权限资源里面用authGroup【admin.bbx:user.bbx】中文名字、注册扩展扩展authExtend  控制器：方法（方法里面有返回数据、）
        $AccountService = new BasicsAccountService();

        if (!isset($this->app->Request()->SERVER[\Config::ACCOUNT['HEADERS_ACCESS_TOKEN_NAME']]) || $this->app->Request()->SERVER[\Config::ACCOUNT['HEADERS_ACCESS_TOKEN_NAME']] ==''){
            error('非法请求[TOKEN]',\ErrorOrLog::NOT_LOGGOD_IN_CODE);
        }
        # 是否要设置Payload缓存
        # 每次请求都进行解密操作是否对性能消耗严重？
        #  规则：设置频率 5分钟内超过 60次请求（300s 超过平均5s内点击请求一次） 就重新进行解密没有超过60次到5分钟依然进行重新解密进行缓存
        #  是否做安全更新？根据↑一条的规则，在更新缓存时请求登录微服务中心 确定jwt是否更新、账号信息是否更新、jwt是否依然有效
        #  请求账号微服务中心？  通过ip所在地、用户id分片请求？
        # 每个用户在5分钟内至少有一次请求是需要请求配置中心的？
        # 大流量打并发时按照客户端IP 分发到最近机房 一个机房内部使用同一的redis存储会话。如果IP出现地区性变化强制下线重新登录（其实是请求到了其他地区的机房redis中没有信息）
            # 那么怎么做IP地址地区的分发呢？
            #无论如何 依然需要一个机制处理信息同步和会话安全问题。
        $this->Payload =  $AccountService->decodeLogonJwt($this->pattern,$this->app->Request()->SERVER[\Config::ACCOUNT['HEADERS_ACCESS_TOKEN_NAME']]??'',Redis::init());

    }

    /**
     * 判断是否登录
     * @throws \Exception
     */
    public function WhetherTheLogin()
    {
        // *方法路由：注册到不同操作权限资源里面用authGroup【admin.bbx:user.bbx】中文名字、注册扩展扩展authExtend  控制器：方法（方法里面有返回数据、）
        $AccountService = new BasicsAccountService();
        $Redis = Redis::init();
//        var_dump($_SERVER);
        if (!isset($this->app->Request()->SERVER[\Config::ACCOUNT['HEADERS_ACCESS_TOKEN_NAME']]) || $this->app->Request()->SERVER[\Config::ACCOUNT['HEADERS_ACCESS_TOKEN_NAME']] ==''){throw new \Exception('非法请求[TOKEN]',\ErrorOrLog::NOT_LOGGOD_IN_CODE);}
        $this->Payload =  $AccountService->decodeLogonJwt($this->pattern,$this->app->Request()->SERVER[\Config::ACCOUNT['HEADERS_ACCESS_TOKEN_NAME']]??'',$Redis);
    }
    /**
     * 权限判断(使用数据缓存或者数据库的版本)
     * @param array $data 权限数据集合
     * @throws \Exception
     */
    public function jurisdictionTidy(array $data)
    {
        $Route = $this->app->Route();
        $Route->authTag;

        if(!isset($data[$Route->authTag])){
            throw new \Exception('无权限',\ErrorOrLog::JURISDICTION_CODE);
        }
        /**
         * 判断是否存在扩展信息
         */
        $this->authExtend = $data[$Route->authTag]['extend']??[];
        /**
         * 当前账号的权限集合
         * 当前路由的权限的tag
         * 获取自定义资源
         *
         * 1、通过判断路由唯一标识 是否在用户权限数据集合中判断是否有权限
         *
         * 2、有权限就通过  routeAuthExtend  使用对应的类的方法 设置对应的拓展属性
         */

    }

    /**
     * 权限判断（根据配置文件和jwt信息自己判断权限 服务端和客户端都不保存详细客户信息）
     * @param $data 权限数据集合
     * @param $tag 当前路由tag
     */
    public function jurisdiction($data,$tag)
    {
        /**
         * 当前账号的权限
         */
    }
}