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
use pizepei\helper\Helper;
use pizepei\microserviceClient\MicroClient;
use pizepei\model\redis\Redis;
use pizepei\service\jwt\JsonWebToken;
use pizepei\staging\App;
use pizepei\staging\AuthorityInterface;
use service\basics\account\AccountService;

class BasicsAuthority extends \pizepei\staging\BasicsAuthority
{
    /**
     * 用户信息缓存有效期单位分钟
     */
    const userPeriod = 1;
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
        # 获取JWT  Payload 数据（初步验证是否登录)
        $this->getPayload($AccountService);
        return true;
    }

    /**
     * @Author 皮泽培
     * @Created 2019/11/4 15:24
     * @param $jwtString
     * @title  从缓存获取getPayload
     * @explain 本地使用
     * @throws \Exception
     */
    public function getPayload( BasicsAccountService $AccountService)
    {
        if (!isset($this->app->Request()->SERVER[\Config::ACCOUNT['HEADERS_ACCESS_TOKEN_NAME']]) || $this->app->Request()->SERVER[\Config::ACCOUNT['HEADERS_ACCESS_TOKEN_NAME']] ==''){
            error('非法请求[TOKEN]',\ErrorOrLog::NOT_LOGGOD_IN_CODE);
        }
        $explode = explode('.',$this->app->Request()->SERVER[\Config::ACCOUNT['HEADERS_ACCESS_TOKEN_NAME']]);
        if(count($explode)  !== 3){throw new \Exception('Payload加密错误',\ErrorOrLog::NOT_LOGGOD_IN_CODE);}
        $this->ACCESS_TOKEN = $this->app->Request()->SERVER[\Config::ACCOUNT['HEADERS_ACCESS_TOKEN_NAME']];
        $key = end($explode);
        $this->ACCESS_SIGNATURE= end($explode);
        # 每个请求缓存5分钟  过期后就重新解密JWT 再缓存
        # 规则：设置频率 5分钟内超过 60次请求（300s 超过平均5s内点击请求一次） 就重新进行解密没有超过60次到5分钟依然进行重新解密进行缓存
        #读取是否有Lock
        $Lock = Redis::init()->get('account:jwt:payload:Lock'.\Config::MICROSERVICE['ACCOUNT']['configId'].':'.$explode[2]);
        if (!empty($Lock)){
            # 如果此jwt之前已经验证并且是异常的
            $Lock = Helper()->json_decode($Lock);
            error($Lock[ $this->app->__INIT__['ErrorReturnJsonMsg']['name']], $Lock[$this->app->__INIT__['ErrorReturnJsonCode']['name']],'异常请求');
        }
        # 读取jwt缓存
        $payload = Redis::init()->get('account:jwt:payload:'.\Config::MICROSERVICE['ACCOUNT']['configId'].':'.$explode[2]);
        $UserInfo = Redis::init()->get('account:jwt:userInfo:'.\Config::MICROSERVICE['ACCOUNT']['configId'].':'.$explode[2]);

        if (!empty($payload) && !empty($UserInfo)){
            # 有缓存
            $this->Payload = Helper()->json_decode($payload);
            $this->UserInfo = Helper()->json_decode($UserInfo);
            JsonWebToken::is_time($this->Payload);# 验证有效期
            return true;
        }

        # 请求服务中心（服务中心本身的api资源类型路由一样通过请求账号资源中心获取信息（其实是自己），因为如果直接读取本地就也是有资源消耗的）
        $res =  $this->getRemotePayload();
        # 设置缓存
        if ($res['statusCode'] !== 200){
            # 异常数据
            Redis::init()->setex('account:jwt:payload:Lock'.\Config::MICROSERVICE['ACCOUNT']['configId'].':'.$explode[2],60*self::userPeriod,Helper()->json_encode($res));
            error($res[ $this->app->__INIT__['ErrorReturnJsonMsg']['name']], $res[$this->app->__INIT__['ErrorReturnJsonCode']['name']],$res);
        }
        if (empty($res[$this->app->__INIT__['ReturnJsonData']])){
            Redis::init()->setex('account:jwt:payload:Lock'.\Config::MICROSERVICE['ACCOUNT']['configId'].':'.$explode[2],60*self::userPeriod,Helper()->json_encode($res));
            error('登陆信息为空');
        }
        $this->Payload = $res[$this->app->__INIT__['ReturnJsonData']]['Payload'];
        $this->UserInfo = $res[$this->app->__INIT__['ReturnJsonData']]['UserInfo'];
        $payload = Redis::init()->setex('account:jwt:userInfo:'.\Config::MICROSERVICE['ACCOUNT']['configId'].':'.$explode[2],60*self::userPeriod,Helper()->json_encode($this->UserInfo));
        $payload = Redis::init()->setex('account:jwt:payload:'.\Config::MICROSERVICE['ACCOUNT']['configId'].':'.$explode[2],60*self::userPeriod,Helper()->json_encode($this->Payload));
        # 进行统一验证
        JsonWebToken::is_time($this->Payload);# 验证有效期
    }

    /**
     * @Author 皮泽培
     * @Created 2019/11/4 15:24
     * @param $jwtString
     * @title  从远程账号配置中心获取getPayload(如果配置不存在从本地获取)
     * @explain 服务本地使用请求远程
     * @throws \Exception
     */
    public function getRemotePayload()
    {
        # 如果是主项目就直接使用本地
        if (\Deploy::CENTRE_ID === \Deploy::PROJECT_ID ){
            $Redis = Redis::init();
            $AccountService = new BasicsAccountService();
            $Payload = $AccountService->decodeLogonJwt($this->app->Authority->pattern,$this->app->Request()->SERVER[\Config::ACCOUNT['HEADERS_ACCESS_TOKEN_NAME']],$Redis);
            $userInfo = BasicsAccountService::getUserInfo('',$Payload['number']);
            return [
                'statusCode'=>200,
                $this->app->__INIT__['ReturnJsonData']=>['Payload'=>$Payload,'UserInfo'=>$userInfo],
            ];
        }
        # 不是 主项目 如果有远程配置中心就从远程配置中心获取
        if ( isset( \Config::MICROSERVICE['ACCOUNT']) && !empty(\Config::MICROSERVICE['ACCOUNT']) && isset(\Config::MICROSERVICE['ACCOUNT']['configId']) && !empty(\Config::MICROSERVICE['ACCOUNT']['configId']) )
        {
            # 准备微服务客户端
            $MicroClient = MicroClient::init(Redis::init(),\Config::MICROSERVICE);
            $res = $MicroClient->send(
                [
                    'JWT'=>$this->app->Request()->SERVER[\Config::ACCOUNT['HEADERS_ACCESS_TOKEN_NAME']],
                ],'ACCOUNT'
            );
            return $res;
        }
        # 没有 就提示错误
        error('非主项目，请配置JWT接口');
    }


    /**
     * 判断是否登录
     * @throws \Exception
     */
    public function WhetherTheLogin()
    {
        $this->getRemotePayload();
        // *方法路由：注册到不同操作权限资源里面用authGroup【admin.bbx:user.bbx】中文名字、注册扩展扩展authExtend  控制器：方法（方法里面有返回数据、）
        $AccountService = new BasicsAccountService();
        $Redis = Redis::init();
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

    /**
     * @Author 皮泽培
     * @Created 2019/11/19 16:34
     * @title  判断是否是超级管理员
     * @explain 非必要情况下不建议使用
     * @return bool
     * @throws \Exception
     */
    public function isSuperAdmin()
    {
        if((int)$this->app->Authority->UserInfo['typeInt'] !==88)return false;
        if((int)$this->app->Authority->UserInfo['role']['role']['type'] !== 6) return false;
        if (!in_array('SuperAdmin',explode('_',$this->app->Authority->UserInfo['number'])))return false;
        return true;
    }
}