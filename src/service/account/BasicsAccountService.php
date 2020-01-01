<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/3/26
 * Time: 9:53
 * @title 账号体系
 */
declare(strict_types=1);

namespace pizepei\basics\service\account;

use pizepei\basics\model\account\AccountAndRoleModel;
use pizepei\basics\model\account\AccountMilestoneModel;
use pizepei\basics\model\account\AccountModel;
use pizepei\basics\model\account\AccountRoleMenuModel;
use pizepei\basics\model\account\AccountRoleModel;
use pizepei\basics\model\console\PersonShortcutTypeModel;
use pizepei\basics\service\BasicsMenuService;
use pizepei\encryption\google\GoogleAuthenticator;
use pizepei\helper\Helper;
use pizepei\model\cache\Cache;
use pizepei\model\db\Model;
use pizepei\model\redis\Redis;
use pizepei\service\encryption\PasswordHash;
use pizepei\service\jwt\JsonWebToken;
use pizepei\staging\App;
use pizepei\staging\Controller;

class BasicsAccountService
{
    /**
     * 注册账号
     * @param array                       $config
     * @param array                       $Request  邮箱、手机
     * @return array
     * @throws \Exception
     */
    public function register(array $config,array $Request)
    {
        /**
         * 判断两次密码是否一致
         */
        if($Request['password'] !== $Request['repass'])
        {
            error('两次密码不一致');
        }
        /**
         * 可以选择保存当前用户协议版本
         */
        if($Request['agreement'] !== 'on')
        {
            error('阅读并同意用户协议才能成为我们的一员');
        }
        /**
         * 实例化密码类
         */
        $PasswordHash = new PasswordHash();
        /**
         * 判断密码格式
         */
        if(empty($PasswordHash->password_match($config['password_regular'][0],$Request['password']))){
            error($config['password_regular'][1]);
        }
        /**
         * 查询是否有对应的账号存在
         */
        $AccountModel = AccountModel::table();

        /**
         * 验证验证码
         */
        if($AccountModel->where(['phone'=>$Request['phone']])->fetch(['id'])){ error('手机号码已经注册'); }
        if($AccountModel->where(['email'=>$Request['email']])->fetch(['id'])){ error('email已经注册'); }


        //获取密码hash
        $password_hash = $PasswordHash->password_hash($Request['password'],$config['algo'],$config['options']);
        if(!empty($password_hash)){
            $Data['password_hash'] = $password_hash;
        }

        $Data['number'] = 'common_'.Helper::str()->str_rand($config['number_count']);//编号固定开头的账号编码(common,tourist,app,appAdmin,appSuperAdmin,Administrators)
        $Data['phone'] = $Request['phone'];
        $Data['email'] = $Request['email'];
        $Data['type'] = 1;
        $Data['nickname'] = $Request['nickname']??'';

        $Data['logon_token_salt'] = Helper::str()->str_rand($config['user_logon_token_salt_count']);//建议user_logon_token_salt
        $AccountData = AccountModel::table()->add($Data);
        if (empty($AccountData))
        {
            error('注册失败');
        }
        # 写入账号与角色的关系
        AccountAndRoleModel::table()->add([
            'role_id'=>'0EQD12A2-8824-9943-E8C9-C83E40F360D1',# 默认角色id
            'account_id'=>key($AccountData),
        ]);
        if(is_array($AccountData)){
            $id = array_keys($AccountData)[0]??null;
        }
        succeed($AccountData,'注册成功');
    }


    /**
     * @Author 皮泽培
     * @Created 2019/11/14 17:49
     * @param $AccountData
     * @title  账号初始化
     * @explain 账号创建成功后的一些初始化操作
     * @throws \Exception
     * @throws \Exception
     */
    public static  function registerEvent($AccountData)
    {
        # 写入控制台默认信息
        PersonShortcutTypeModel::table()->add([
            'name'=>'收藏夹',
            'Account_id'=>key($AccountData),
            'status'=>2,
        ]);
    }
    /**
     * @Author pizepei
     * @Created 2019/3/30 21:28
     *
     * @param array                       $config 配置
     * @param array                       $Request 请求参数
     * @param array                       $userData 会员数据
     * @param \pizepei\staging\Controller $Controller 控制器
     * @return array|bool|string
     * @throws \Exception
     */
    public function logon(array $config,array $Request,array $userData,Controller $Controller)
    {
        /**
         * 判断可登录的用户
         */

        # 查询密码错误限制（是否超过错误限制）
        $passwordWrongLock = $this->passwordWrongLock($userData);
        if($passwordWrongLock)
        {
            return ['result'=>false,'msg'=>$passwordWrongLock];
        }
        /**
         * 注意为了保证安全
         *      登录需要使用的logon_token_salt 解密进行如下处理
         *          1、实际进行操作是是使用项目全局的logon_token_salt拼接用户logon_token_salt
         *          2、在强制用户下线时：项目全局强制可通过修改项目logon_token_salt，单一用户修改用户logon_token_salt
         *          3、由于是jwt方式缓存用户logon_token_salt要注意缓存存和数据安全
         */
        # 实例化密码类 验证是否密码正确
        $PasswordHash = new PasswordHash();
        $hashResult = $PasswordHash->password_verify($Request['password'],$userData['password_hash'],$config['algo'],$config['options']);
        if(!$hashResult['result']){
            $this->passwordWrongLock($userData,false);# 设置一次密码错误记录
            return ['result'=>false,'msg'=>'账号或者密码错误'];
        }
        # 判断是否启用  A双因子认证secret
        if(!empty($userData['2fa_secret'])){
            $GoogleAuthenticator =  new GoogleAuthenticator();
            if(!$GoogleAuthenticator->verifyCode($userData['2fa_secret'],$Request['codeFA']??''))
            {
                return ['result'=>false,'msg'=>'双因子认证错误'];
            }
        }
        # 如果配置文件或者用户的logon_token_salt有修改就存在 newHash 自动修改数据表中的 password_hash
        if($hashResult['newHash']){
            # 密码加密参数有修改
            $AccountModel = AccountModel::table()->insert([ 'id'=>$userData['id'],'password_hash'=>$hashResult['newHash']]);
            if($AccountModel === 1){
                # 写入里程碑事件
                if(empty(AccountMilestoneModel::table()->add(['account_id'=>$userData['id'],'message'=> ['registerData'=>[ 'id'=>$userData['id'],'password_hash'=>$hashResult['newHash']],'requestId'=>__REQUEST_ID__], 'type'=>7,]))){
                    return ['result'=>false,'msg'=>'系统错误L002'];
                }
            }
        }
        # 判断登录数量限制
        if($this->logonCount($userData['id']) > $userData['logon_online_count']){ return $Controller->error('在线设备数量：当前在线'.$userData['logon_online_count']); }

        $Payload= [
            'nickname'=>$userData['nickname'],
            'type'=>$userData['type'],
            'number'=>$userData['number'],
            'iss'=>'logonServer',//主题
            'aud'=>'user',//受众
            'sub'=>'userLogon',//主题number
            'period_pattern'=>$userData['logon_token_period_pattern'],
            'period_time'=>$userData['logon_token_period_time'],
            'exp'=>$this->logoExp($userData['logon_token_period_pattern'],$userData['logon_token_period_time']),
        ];

        $result = $this->setLogonJwt('common',$Payload,$userData['logon_token_salt'],'number');
        return $result;

    }

    /**
     * @Author 皮泽培
     * @Created 2019/12/24 16:25
     * @title  获取当前用户的菜单权限
     * @explain 路由功能说明
     * @throws \Exception
     */
    public static function getMenu(App $app ,$UserInfo)
    {
        if (!isset($UserInfo['role'])) error('没权限');
        if ($app->Authority->isSuperAdmin($UserInfo)){
            return (new  BasicsMenuService())->getMenuList('admin','SuperAdmin');
        }
        $menuId = $UserInfo['role']['menu']??[];
        return (new  BasicsMenuService())->getMenuList('admin',$menuId);
    }


    /**
     * 获取登录有效期
     * @param $periodPattern
     * @param $periodTime
     * @return float|int|string
     */
    public function logoExp($periodPattern ,$periodTime)
    {
        $exp = '';
        $h = 60*60;     # 小时
        switch($periodPattern)
        {
            case 1://谨慎（分钟为单位）
                $exp = 60*$periodTime;
                break;
            case 2://常规（小时为单位）
                $exp = $h*$periodTime;
                break;
            case 3://方便（天为单位）
                $exp = $periodTime*$h*24;
                break;
            case 4://游客（单位分钟没有操作注销
                $exp = $periodTime*60;
                break;
            case 5:
                break;
            case 6:
                break;
            default:
        }
        return $exp;
        //登录token有效期
    }


    /**
     * @Author pizepei
     * @Created 2019/3/30 21:35
     * @param array                       $config 配置
     * @param array                       $Request 请求数据
     * @param array                       $userData 用户数据
     * @param \pizepei\staging\Controller $Controller 控制器类
     * @return array
     * @throws \Exception
     * @title  修改密码
     * @explain 修改密码（注意配置）
     */
    public function changePassword(array $config,array $Request,array $userData,Controller $Controller)
    {
        # 判断两次密码是否一致
        if($Request['password'] !== $Request['repass'])
        {
            return ['result'=>false,'msg'=>'两次密码不一致'];
        }
        # 实例化密码类
        $PasswordHash = new PasswordHash();
        # 判断密码格式
        if(empty($PasswordHash->password_match($config['password_regular'][0],$Request['password']))){
            return $Controller->error($config['password_regular'][1]);
        }
        /**
         * 判断密码要求
         *      如：上次修改  是否是原密码
         */
        # 生成新密码 获取密码hash
        $password_hash = $PasswordHash->password_hash($Request['password'],$config['algo'],$config['options']);
        if(empty($password_hash)){
            return $Controller->error('系统错误','系统错误','L003');
        }
        # 修改并且写入里程碑事件update
        $updateData = ['password_hash'=>$password_hash,'version'=>$userData['version']+1];
        $AccountModel = AccountModel::table()->where(['version'=>$userData['version']])->update($updateData);
        //$AccountModel = AccountModel::table()->where(['version'=>$userData['version']])->insert([ 'id'=>$userData['id'],'password_hash'=>$password_hash,'version'=>$userData['version']+1]);
        if($AccountModel == 1){
            # 写入里程碑事件
            if(empty(AccountMilestoneModel::table()->add(['account_id'=>$userData['id'],'message'=> ['registerData'=>[ 'id'=>$userData['id'],'password_hash'=>$password_hash,'version'=>$userData['version']+1],'requestId'=>__REQUEST_ID__], 'type'=>7,]))){
                return $Controller->error('系统错误','系统错误','L002');
            }
        }else{
            return $Controller->succeed($AccountModel,'修改错误');
        }
        return $Controller->succeed($AccountModel,'修改成功');
    }

    /**
     *  构建登录JWT
     * @param        $secret
     * @param        $Payload
     * @param string $TokenSalt
     * @param string $TokenSaltName
     * @return array
     * @throws \Exception
     */
    public function setLogonJwt($secret,$Payload,$TokenSalt,$TokenSaltName ='number')
    {
        if(!isset($Payload['number'])){ throw new \Exception('非法数据');}
        $Redis = Redis::init();

        $JsonWebToken = new JsonWebToken();
        $jwtArray = $JsonWebToken->setJWT($Payload,\Config::JSON_WEB_TOKEN_SECRET[$secret],$TokenSalt,$TokenSaltName);
        /**
         * redis key
         */
         $logonSignature= $Redis->get('user-logon-jwt-info:'.$Payload['number'].':'.$jwtArray['signature']);
         if(!empty($logonSignature)){
             throw new \Exception('系统繁忙');
         }
        /**
         * 写入缓存
         */
        $Redis->setex('user-logon-jwt-info:'.$Payload['number'].':'.$jwtArray['signature'],$jwtArray['exp'],json_encode($Payload));
        $this->logonTokenSalt($Payload['number'],$TokenSalt);

        //KEYS
        return ['jwtArray'=>$jwtArray];
    }
    /**
     * 当前登录设备数量
     * @param string $number
     * @param bool   $del
     * @return int
     */
    public function logonCount(string $number,bool$del= false)
    {
        $Redis = Redis::init();
        if($del)
        {
            return $Redis->del($Redis->keys('user-logon-jwt-info:'.$number.'*'));
        }
        return count($Redis->keys('user-logon-jwt-info:'.$number.'*'));
    }
    /**
     * 登录TokenSalt获取与设置
     * @param string $number
     * @param string $value
     * @return bool|string
     */
    public function logonTokenSalt(string $number,string $value)
    {
        $Redis = Redis::init();
        /**
         * 设置
         */
        if($value)
        {
            return $Redis->set('user-logon-jwt-tokenSalt:'.$number,$value);
        }
        return $Redis->get('user-logon-jwt-tokenSalt:'.$number);
    }
    /**
     * @Author pizepei
     * @Created 2019/4/14 11:28
     *
     * @title 验证解密
     * @throws \Exception
     */
    public function decodeLogonJwt($secret,$jwtStr,$Redis)
    {
        # 获取缓存
        $JsonWebToken = new JsonWebToken();

        $decodeJWT = $JsonWebToken->decodeJWT($jwtStr,\Config::JSON_WEB_TOKEN_SECRET[$secret],$Redis);
        return $decodeJWT;
    }
    /**
     * 密码错误限制
     * @param array $userData
     * @param bool  $Type  true 查询是否成功限制 false 设置错误缓存
     * @return bool|string
     */
    protected function passwordWrongLock(array$userData,$Type=true)
    {
        $Redis = Redis::init();

        if($Type){
            /**
             * 查询密码错误
             */
            $password_wrong_count = $Redis->hget('user-logon-wrong:'.$userData['id'],'logonRestrict_wrong_count');//查，取值
            if($password_wrong_count){
                /**
                 * 判断密码错误数
                 */
                if($password_wrong_count >= $userData['password_wrong_count']){
                    $password_wrong_time = $Redis->hget('user-logon-wrong:'.$userData['id'],'logonRestrict_wrong_time');//查，取值【value|false】

                    if(((time()-$password_wrong_time)/60) >$userData['password_wrong_lock'])
                    {
                        /**
                         * 修改成为数量为0
                         */
                        $Redis->hset('user-logon-wrong:'.$userData['id'],'logonRestrict_wrong_count',0);
                    }else{

                        return '密码错误超限:'.round(($userData['password_wrong_lock']-((time()-$password_wrong_time)/60))).'分钟后解除限制';
                    }
                }
            }
            return false;
        }else{
            $password_wrong_count = $Redis->hget('user-logon-wrong:'.$userData['id'],'logonRestrict_wrong_count');
            if($password_wrong_count){
                $password_wrong_count = $password_wrong_count+1;
            }else{
                $password_wrong_count = 1;
            }
            $Redis->hset('user-logon-wrong:'.$userData['id'],'logonRestrict_wrong_count',$password_wrong_count);
            $Redis->hset('user-logon-wrong:'.$userData['id'],'logonRestrict_wrong_time',time());
        }


    }


    const codeSendFrequencyType = [
        'number'    =>'smsCodeSendFrequency',
        'mail'      =>'smsCodeMailFrequency',
        'universal'      =>'universalFrequency',
    ];

    /**
     *  通过缓存验证是否超过触发限制（在查询是否超频的同时设置记录+1）
     * @param string $type      发送类型 number mail  universal
     * @param $object            发送对象
     * @param int $Frequency    单位时间发送数量      默认 4
     * @param int $time         单位时间  默认300s 5分钟
     * @param string $ip        当前IP
     * @param int $IpFrequency    单位时间发送数量      默认 20
     * @return bool
     * @throws \Exception
     */
    public static function codeSendFrequency(string $type,$object,int $Frequency=4,int $time=300,string $ip='',int $IpFrequency=20):bool
    {
        if (!isset(self::codeSendFrequencyType[$type])){
            throw new \Exception('codeSendFrequencyType error');
        }

        # 验证IP频率（）
        if ($ip !== ''){
            $ipNumberCache = Cache::get([self::codeSendFrequencyType[$type],$ip]);
            if (empty($ipNumberCache)){
                Cache::set([self::codeSendFrequencyType[$type],$ip],['update_time'=>time(),'count'=>1]);
            }else{
                # 增加频率记录一次
                Cache::set([self::codeSendFrequencyType[$type],$ip],['update_time'=>time(),'count'=>$ipNumberCache['count']+1]);
                # 判断是否超过限制
                if ($ipNumberCache['update_time'] > (time()-$time)){
                    # 在限制的时间内进行了信息发送  判断发送的数量是否超过限制
                    if ($ipNumberCache['count'] >=$IpFrequency){
                        return true;
                    }
                }
            }
        }


        # 本地验证
        $numberCache = Cache::get([self::codeSendFrequencyType[$type],$object]);
        if (empty($numberCache)){
            # 设置信息
            Cache::set([self::codeSendFrequencyType[$type],$object],['update_time'=>time(),'count'=>1]);
            return false;
        }
        # 有缓存记录
        # 一定时间内可发送的频率
        # 判断上次发送时间
        if ($numberCache['update_time'] > (time()-$time)){
            # 在限制的时间内进行了信息发送  判断发送的数量是否超过限制
            if ($numberCache['count'] >=$Frequency){
                return true;
            }else{
                # 增加频率记录一次
                Cache::set([self::codeSendFrequencyType[$type],$object],['update_time'=>time(),'count'=>$numberCache['count']+1]);
                return false;
            }
        }else{
            # 在限制的时间内没有进行信息发送  重置 记录为一次
            Cache::set([self::codeSendFrequencyType[$type],$object],['update_time'=>time(),'count'=>1]);
            return false;
        }

    }

    /**
     * @Author 皮泽培
     * @Created 2019/11/6 17:15
     * @title  获取登录用户的信息
     * @explain 路由功能说明
     * @throws \Exception
     */
    public static function getUserInfo(string $accountId='',string $number='')
    {
        if ($accountId !==''){
            $where['id'] = $accountId;
        }
        if ($number !==''){
            $where['number'] = $number;
        }
        $data = AccountModel::table()
            ->where($where)
            ->replaceField('fetch',['type','status'],[
                'id','number','surname','name',
                'nickname','user_name','email',
                'phone','parent_id','logon_online_count', 'type','status',
            ]);
        if (!$data){return [];}
        $data['typeInt'] = $data['type'] === '超级管理员SuperAdmin'?88:66;
        # 查询相关权限  角色信息、菜单权限、功能接口权限
        $AndRole = AccountAndRoleModel::table()->where([
            'account_id'=>$data['id']
        ])->fetch();
        if (!$AndRole){
            error('账号没有角色');
        }

        $Role = AccountRoleModel::table()->where([
            'id'=>$AndRole['role_id'],
            'status'=>2,
        ])->fetch(['id','name','type','status','apps_id']);
        if (!$Role){            error('角色状态异常');}
        #查询菜单权限
        $gather = AccountRoleMenuModel::table()->where(['role_id'=>$AndRole['role_id']])->fetch(['gather']);
        $data['role'] =[
            'role'=>$Role,
            'api'=>[],
            'menu'=>$gather['gather'],
        ];
        return $data;
    }

    public static function smsCodeRegisterSend($CodeApp)
    {



    }

}