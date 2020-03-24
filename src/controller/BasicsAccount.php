<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/1/15
 * Time: 11:28
 * @title 账号管理控制器
 */

namespace pizepei\basics\controller;

use pizepei\basics\model\account\AccountModel;
use pizepei\basics\service\account\BasicsAccountService;
use pizepei\basics\service\BasicsMenuService;
use pizepei\helper\Helper;
use pizepei\microserviceClient\MicroClient;
use pizepei\model\cache\Cache;
use pizepei\model\redis\Redis;
use pizepei\randomInformation\RandomUserInfo;
use pizepei\service\verifyCode\GifverifyCode;
use pizepei\staging\Controller;
use pizepei\staging\Request;
use pizepei\staging\Response;
use pizepei\wechat\model\OpenWechatCodeAppModel;
use pizepei\wechatClient\Client;

class BasicsAccount extends Controller
{
    /**
     * 基础控制器信息
     */
    const CONTROLLER_INFO = [
        'User'=>'pizepei',
        'title'=>'账号控制器',//控制器标题
        'namespace'=>'bases',//门面控制器命名空间
        'basePath'=>'/account/',//基础路由
        'baseParam'=>'[$Request:pizepei\staging\Request]',//依赖注入对象
    ];

    /**
     * @Author pizepei
     * @Created 2019/7/5 22:40
     * @param \pizepei\staging\Request $Request
     *      post [object]
     *          phone [string number] 手机号码
     *          phone_code [int required] 手机验证码
     *          email [string email] 邮箱
     *          email_code [int required] 邮箱验证码
     *          password [string required] 密码
     *          repass [string required] 确认密码
     *          nickname [string required] 昵称
     *          agreement [string required] 是否同意协议
     *          encrypted [object]
     *              signature [string required] 签名
     *              timestamp [int required] 时间戳
     *              signature [string required] 签名
     *              nonce [string required] 随机数
     *              encrypt_msg [string required] 密文
     *          openid [string required] openid
     *          code [int] 验证码
     *          id [uuid] 事件id
     * @title  注册接口
     * @explain 获注册接口
     * @throws \Exception
     * @return array [json]
     *      data [raw]
     * @router post account
     */
    public function registerAccount(Request $Request)
    {
        # 本地验证
        if (BasicsAccountService::codeSendFrequency('universal','register'.$Request->post('phone').$Request->path('email'),6)){
            return $this->error('操作频繁请稍后再试!!');
        }
        # 验证验证码
        $CodeApp = \Config::WEC_CHAT_CODE;
        $Client = new Client($CodeApp);
        $res = $Client->codeAppVerify($Request->post('encrypted'),$Request->post('id'),$Request->post('code'),$Request->post('openid'));
        if ($res['statusCode'] ==100){
            $this->error($res['msg']);
        }
        $res = $res['data'];
        # 验证通过
        if (!isset($res['content']) || !isset($res['type']) || $res['type'] !=='register'){
            return $this->error('请求错误');
        }
        # 判断手机验证码
        if ((int)$res['content']['param']['number'] !== (int)$Request->post('phone')  || (int)$res['content']['param']['numberCode'] !== (int)$Request->post('phone_code')){
            return $this->error('手机验证码错误！');
        }
        # 判断邮箱验证码
        if ($res['content']['param']['email'] !==$Request->post('email')  || $res['content']['param']['emailCode'] !== (int)$Request->post('email_code')){
            return $this->error('邮箱验证码错误！');
        }
        # 注册账号
        $Service = new BasicsAccountService();
        $Service->register(\Config::ACCOUNT,$Request->post());
    }
    /**
     * @Author pizepei
     * @Created 2019/3/23 16:23
     *
     * @param \pizepei\staging\Request $Request
     *      post [object] post
     *          phone [number] 手机号码
     *          password [string required] 密码
     *          code [string required] 验证码
     *          codeFA [string] 2FA双因子认证code
     * @return array [json]
     *      data [object] 数据
     *          result [object] 结果
     *              jwtArray [object] jwt
     *                  str [string] 内容
     *                  param [string] 参数
     *                  signature [string] 签名
     *                  exp [int] 有效期
     *              surname [string] 姓名
     *              name [string] 名字
     *              nickname [string] 昵称
     *              user_name [string]
     *              email [string]  邮箱
     *              phone [phone]  手机号码
     *          access_token [string] access_token
     * @throws \Exception
     * @title  登录验证
     * @explain 登录验证
     * @authTiny 微权限提供权限分配 [获取店铺所有  获取所有店铺  获取一个]
     * @router post logon
     */
    public function logon(Request $Request)
    {
        /**
         * 查询账号是否存在（可能会是邮箱  或者用户名）
         * 用户编码 为用户唯一标准     不同的用户编码  可以是同一个手机号码、或者邮箱   ？
         */
        $Account = AccountModel::table()
            ->where(['phone'=>$Request->post('phone')])
            ->fetch();
        if(empty($Account)){
            return $this->error('用户名或密码错误');
        }
        $AccountService = new BasicsAccountService();

        $result =  $AccountService->logon(\Config::ACCOUNT,$Request->post(),$Account,$this);
        if(isset($result['jwtArray']['str']) && $result['jwtArray']){
            # 方便扩展 返回信息在logon方法外
            return $this->succeed([
                'result'        =>$result,
                'access_token'  =>$result['jwtArray']['str'],
            ],'登录成功');
        }
        return $this->error($result['msg']);
    }
    /**
     * @Author pizepei
     * @Created 2019/4/23 23:02
     * @param \pizepei\staging\Request $Request
     * @return array [json]
     *      data [object]
     *          username [object]
     *              number [string] 标识
     *              nickname [string] 昵称
     *              user_name [string] 姓名
     *              email [string] 邮箱
     *              phone [int] 手机号码
     *              role [raw] 角色信息
     *          sex [string]
     *          role [int]
     *          menuData [objectList] 菜单数据
     *              name [string] 一级菜单名称（与视图的文件夹名称和路由路径对应）
     *              title [string] 一级菜单标题
     *              icon [string] 一级菜单图标样式
     *              spread [bool] 是否默认展子菜单
     *              list [objectList] 二级
     *                  name [string] 二级菜单名称（与视图的文件夹名称和路由路径对应）
     *                  title [string] 二级菜单标题
     *                  icon [string] 二级菜单图标样式
     *                  spread [bool] 是否默认展子菜单
     *                  list [objectList] 三级
     *                      name [string] 三级菜单名称
     *                      title [string] 三级菜单标题
     *                      icon [string] 三级菜单图标样式
     * @title  用户信息
     * @explain 简单用户信息一次性缓存在浏览器本地减少api请求数开发者可以随时追加数据
     * @baseAuth UserAuth:test
     * @router get session
     */
    public function session(Request $Request)
    {
        # 菜单信息
        $menuData = (new BasicsMenuService())->getUserMenuList($this->app->Authority->isSuperAdmin()?'SuperAdmin':($this->UserInfo['role']['menu']??[]));
        # 用户基础信息
        $username = $this->UserInfo;
        $data = [
            'menuData'      =>      $menuData,
            "username"      =>      $username
        ];
        return $this->succeed($data);
    }

    /**
     * @Author pizepei
     * @Created 2019/3/30 21:33
     *
     * @param \pizepei\staging\Request $Request
     *      post [object] post
     *          phone [int number] 手机号码
     *          password [string required] 密码
     *          repass [string required] 确认密码
     *          code [string required] 验证码
     * @return array [json]
     *
     * @title  修改密码
     * @explain 通过手机验证码修改密码
     * @authTiny 修改密码
     * @throws \Exception
     * @router put phone/password
     */
    public function changePasswordPhone(Request $Request)
    {
        $Account = AccountModel::table()
            ->where(['phone'=>$Request->post('phone')])
            ->replaceField('fetch',['type','status']);
        if(empty($Account)){
            $this->error('用户不存在');
        }
        $AccountService = new BasicsAccountService();
    }



    /**
     * @Author pizepei
     * @Created 2019/3/30 21:33
     *
     * @param \pizepei\staging\Request $Request
     *      raw [object] post
     *          oldPassword [string required] 原密码
     *          password [string required] 密码
     *          repass [string required] 确认密码
     * @return array [json]
     * @title  修改密码
     * @explain 通过原密码修改密码
     * @authTiny 修改密码
     * @baseAuth UserAuth:test
     * @throws \Exception
     * @router put password
     */
    public function changePassword(Request $Request)
    {

        $Account = AccountModel::table()
            ->where(['id'=>$this->UserInfo['id']])
            ->fetch();
        if(empty($Account)){
            $this->error('用户不存在');
        }
        $AccountService = new BasicsAccountService();
        return $AccountService->changePassword(\Config::ACCOUNT,$Request->raw(),$Account,$this,$Request->raw('oldPassword'));
    }


    /**
     * @Author pizepei
     * @Created 2019/3/30 21:33
     *
     * @param \pizepei\staging\Request $Request
     *      raw [object] post
     *          code [int required] 验证码
     *          identification [string required] 安全验证码
     *          password [string required] 密码
     *          repass [string required] 确认密码
     *          cellphone [phone required] 手机号码
     * @return array [json]
     * @title  通过短信验证码修改密码
     * @explain 通过短信验证码修改密码
     * @baseAuth UserAuth:public
     * @throws \Exception
     * @router put sms-code/verification-password-retrieve
     */
    public function smsCodeVerificationPasswordRetrieve(Request $Request)
    {
        # 判断验证码是否错误
        $idRes = Redis::init()->get('sms:'.$Request->raw('cellphone').':retrieve:'.$Request->raw('identification'));
        $Account = AccountModel::table()
            ->where(['phone'=>$Request->raw('cellphone')])
            ->fetch();
        if(empty($Account)){
            $this->error('用户不存在');
        }
        $AccountService = new BasicsAccountService();
        return $AccountService->changePassword(\Config::ACCOUNT,$Request->raw(),$Account,$this,'');
    }



    /**
     * @Author pizepei
     * @Created 2019/3/30 21:33
     * @param \pizepei\staging\Request $Request
     *      get [object]
     *          phone [phone required] 手机号码
     * @return array [json]
     *      data [raw]
     * @title  发送短信验证码
     * @explain 发送短信验证码
     * @throws \Exception
     * @router get sms-code-retrieve-send
     */
    public function smsCodeRetrieveSend(Request $Request)
    {
        $CodeApp = \Config::WEC_CHAT_CODE;
        $Client = new Client($CodeApp);
        # 本地验证
        if (BasicsAccountService::codeSendFrequency('number',$Request->input('phone'))){
            return $this->error('短信发送频率过高请稍后再尝试!!');
        }
        # 查询是否已经存在邮箱或者手机号码
        if (!AccountModel::table()->where(['phone'=>$Request->input('phone')])->fetch(['id'])){return $this->error('手机号码不存在!');}
        # 准备微服务客户端
        $MicroClient = MicroClient::init(Redis::init(),\Config::MICROSERVICE);
        # 验证通过发送验证码
        $code = Helper::str()->int_rand(4);
        # 验证通过发送验证码
        $res = $MicroClient->send(
            [
                'type'=>'retrieve',
                'number'=>$Request->input('phone'),
                'TemplateParam'=>['code'=>$code]
            ],'M_SMS'
        );
        if (isset($res['data']['Code']) && $res['data']['Code']== 'OK'){
            # 缓存标识
            $identification = Helper::str()->str_rand(20);
            Redis::init()->setex('sms:'.$Request->input('phone').':retrieve:'.$identification,600,$code);
            $this->succeed(['identification'=>$identification],'短信发送成功！如没有收到请查看是否被定义为垃圾短信');
        }else if (isset($res['data']['Code']) && $res['data']['Code'] !== 'OK'){
            $this->error('','发送频率过高请稍后再尝试！');
        }else{
            $this->error('','发送失败请稍后再尝试！');
        }
    }

    /**
     * @Author pizepei
     * @Created 2019/3/30 21:33
     *
     * @param \pizepei\staging\Request $Request
     *      post [object] post
     *          encrypted [object]
     *              signature [string required] 签名
     *              timestamp [int required] 时间戳
     *              signature [string required] 签名
     *              nonce [int required] 随机数
     *              encrypt_msg [string required] 密文
     *          openid [string required] openid
     *          code [int] 验证码
     *          id [uuid] 事件id
     * @return array [json]
     *      data [raw]
     *          openid [string]
     *          code [int] 验证码
     * @title  发送注册验证码(邮箱和手机)
     * @explain 发送注册验证码
     * @throws \Exception
     * @router post sms-code-register-send
     */
    public function smsCodeRegisterSend(Request $Request)
    {

        $CodeApp = \Config::WEC_CHAT_CODE;
        $Client = new Client($CodeApp);
        $res = $Client->codeAppVerify($Request->post('encrypted'),$Request->post('id'),$Request->post('code'),$Request->post('openid'));
        if ($res['statusCode'] ==100){
            $this->error($res['msg']);
        }
        $res = $res['data'];
        # 验证通过
        if (!isset($res['content']) || !isset($res['type']) || $res['type'] !=='register'){
            return $this->error('请求错误');
        }
        # 本地验证
        if (BasicsAccountService::codeSendFrequency('number',$res['content']['param']['number'])){
            return $this->error('短信发送频率过高请稍后再尝试!!');
        }
        # 本地验证
        if (BasicsAccountService::codeSendFrequency('mail',$res['content']['param']['email'])){
            return $this->error('邮件发送频率过高请稍后再尝试!!');
        }

        # 查询是否已经存在邮箱或者手机号码
        if (AccountModel::table()->where(['email'=>$res['content']['param']['email']])->fetch(['id'])){return $this->error('邮件已注册!');}
        if (AccountModel::table()->where(['phone'=>$res['content']['param']['number']])->fetch(['id'])){return $this->error('手机号码已注册!');}
        # 准备微服务客户端
        $MicroClient = MicroClient::init(Redis::init(),\Config::MICROSERVICE);
        # 验证通过发送验证码
        $param['numberCode'] = Helper::str()->int_rand(4);
        $emailRes = $MicroClient->send(
            [
                'type'=>$res['content']['param']['type'],
                'mail'=>$res['content']['param']['email'],
                'bodyType'=>'TextBody',
                'body'=>'您好'.PHP_EOL.'您的验证码为：'.$res['content']['param']['emailCode'].PHP_EOL.'请妥善保管您的验证码不要告诉他人，前不要回复本邮件！',
                'Subject'=>'Lifetyle大嘴云邮箱验证',
            ],'E_MAIL'
        );
        if (isset($emailRes['data']['code']) || isset($emailRes['data']['Message'])){
            $this->error('邮件验证码发送失败请稍后再尝试！',0,$emailRes);
        }

        # 验证通过发送验证码
        $res = $MicroClient->send(
            [
                'type'=>$res['content']['param']['type'],
                'number'=>$res['content']['param']['number'],
                'TemplateParam'=>['code'=>$res['content']['param']['numberCode']]
            ],'M_SMS'
        );
        if (isset($res['data']['Code']) && $res['data']['Code']== 'OK'){
            $this->succeed('','短信与邮件发送成功！'.PHP_EOL.'如没有收到请查看是否在被定义为垃圾邮件');
        }else if (isset($res['data']['Code']) && $res['data']['Code'] !== 'OK'){
            $this->error('','发送频率过高请稍后再尝试！');
        }else{
            $this->error('','发送失败请稍后再尝试！');
        }
    }

    /**
     * @Author pizepei
     * @Created 2019/7/5 22:40
     * @param \pizepei\staging\Request $Request
     *      path [object] post
     *          code [int] 数字
     * @title  获取gif验证码
     * @explain 获取gif验证码
     * @throws \Exception
     * @return array [gif]
     * @router get gif-verify-code/:code[int]
     */
    public function serviceConfig(Request $Request)
    {
        /**
        生成GIF图片验证
        @$L 验证码长度
        @$F 生成Gif图的帧数
        @$W 宽度
        @$H 高度
        @$MixCnt 干扰线数
        @$lineGap 网格线间隔
        @$noisyCnt 澡点数
        @$sessionName 验证码Session名称
         */
        return GifverifyCode::Draw(4, 1, 99, 31, 1, 5, 15, "secode");
    }
    /**
     * @Author pizepei
     * @Created 2019/7/5 22:40
     * @param \pizepei\staging\Request $Request
     *      path [object] post
     *          count [int] 数字
     * @title  获取gif验证码
     * @explain 获取gif验证码
     * @throws \Exception
     * @return array [json]
     * @router get random-user-info/:count[int]
     */
    public function getRandomUserInfo(Request $Request)
    {
        /**
         * random-user-info/:count[int] 为0时无法识别的问题
         */
        $count = $Request->path('count')?$Request->path('count'):'rand';
        return ['Nickname'=>RandomUserInfo::getNickname(),'Compellation'=>RandomUserInfo::getCompellation($count)];
    }

    /**
     * @Author 皮泽培
     * @Created 2019/7/27 14:52
     * @param \pizepei\staging\Request $Request
     *   path [object] 路径参数
     *      number [int number] 手机号码
     *      type [string required] 验证类型
     *      email [email required] 验证类型
     * @return array [json] 定义输出返回数据
     *      data [raw]
     *          src [string] 显示二维码
     *          id [uuid] 唯一标识
     *          url [string] 二维码内容
     *          type [string] 类型
     *          number [int number] 手机号码
     *          email [email] 邮箱
     *          expire_seconds [int] 二维码有效期
     *          jwt_url [string] webscoke
     * @title  获取微信二维码验证码
     * @explain 获取微信二维码验证码
     * @throws \Exception
     * @router get wecht-qr-code/:type[string]/:number[number]/:email[email]
     */
    public function getWechtQr(Request $Request)
    {
        $CodeApp = \Config::WEC_CHAT_CODE;
        # 本地验证
        if (BasicsAccountService::codeSendFrequency('universal',$Request->path('number').$Request->path('email'),50)){
            return $this->error('获取二维码频率过高!!');
        }
        $CodeApp['url'] = 'http://oauth.heil.top/'.\Deploy::MODULE_PREFIX.'/wechat/common/code-app/qr/'.$CodeApp['appid'].'.json';
        $client = new Client($CodeApp);
        $param = $Request->path();
        $param['emailCode'] = Helper::str()->int_rand(4);
        $param['numberCode'] = Helper::str()->int_rand(4);
        $qr= $client->getQr(Helper::str()->int_rand(6), $Request->path('type'),200,$param);
        # 获取到二维码
        $qr['number'] = $Request->path('number');
        $qr['email'] = $Request->path('email');

        return $this->succeed($qr);
    }

    /**
     * @Author 皮泽培
     * @Created 2019/8/2 14:20
     * @return array [json] 定义输出返回数据
     * @title  退出登录
     * @explain 退出登录
     * @baseAuth UserAuth:test
     * @throws \Exception
     * @router get admin-user/logout
     */
    public function logout()
    {
        return $this->succeed(['cs'],'退出成功');
    }

    /**
     * @Author 皮泽培
     * @Created 2019/10/30 14:43
     * @param Request $Request
     *      path [object]
     *          appid [uuid required] apps应用appid
     *      post [object] 数据
     *          configId [uuid required]  配置id
     *          JWT [string required] jwt str数据
     * @return array [json] 定义输出返回数据
     *      data [object]
     *          Payload [object] Payload解密数据
     *              iss [string required]
     *              exp [int required]
     *              sub [string required]
     *              aud [string required]
     *              nbf [int required]
     *              iat [int required]
     *              jti [int required]
     *              nickname [string]
     *              type [int required]
     *              number [string required]
     *              period_pattern  [string required]
     *              period_time  [int required]
     *          UserInfo [object] 最新的用户数据
     *              id [uuid] 账号id
     *              number [string required] 编号固定开头的账号编码
     *              surname [string] 真实姓氏
     *              name [string] 真实名
     *              nickname [string] 昵称
     *              user_name [string] 姓名
     *              email [string required] 邮箱
     *              phone [string required] 手机号码
     *              parent_id [string required] 父级组织id（uuid）
     *              logon_online_count [int required] 同时在线数
     *              type [string required] 账号类型1普通子账号common、2游客tourist、3应用账号app、4应用管理员appAdmin、5应用超级管理员appSuperAdmin、6超级管理员Administrators
     *              status [string required] 状态1等待审核
     *              typeInt [int] 标记是状态 88 超级管理员 66 非创建管理员
     *              role [object] 对应的角色信息
     *                  role [object] 当前角色
     *                      name [string] 角色
     *                      id [uuid] 角色id
     *                      apps_id [uuid] 应用id
     *                      status [int] 状态 状态1等待审核、2审核通过3、禁止使用4、保留
     *                      type [int] 账号类型1普通子账号common、2游客tourist、3应用账号app、4应用管理员appAdmin、5应用超级管理员appSuperAdmin、6超级管理员Administrators
     *                  api [raw] api权限信息
     *                  menu [raw] 菜单权限信息
     * @title  发送邮件
     * @explain 路由功能说明
     * @authExtend UserExtend.list:拓展权限
     * @baseAuth MicroserviceAuth:initializeData
     * @resourceType microservice
     * @throws \Exception
     * @router post auth-jwt-service/:appid[uuid]
     */
    public function getAuthJwt(Request $Request)
    {

        $Redis = Redis::init();
        $AccountService = new BasicsAccountService();
        $Payload = $AccountService->decodeLogonJwt($this->app->Authority->pattern,$Request->post('JWT'),$Redis);
        $userInfo = BasicsAccountService::getUserInfo('',$Payload['number']);
        $this->succeed(['Payload'=>$Payload,'UserInfo'=>$userInfo],'获取成功');
    }


    /**
     * @Author pizepei
     * @Created 2019/3/23 16:23
     *
     * @param \pizepei\staging\Request $Request
     *      post [object] post
     *          logon_online_count [int required] 同时在线数 '3','5','6','8','10','15'
     *          password_wrong_count [int required] 密码错误数'3','5','6','8','10'
     *          password_wrong_lock [int required] 密码错误超过限制的锁定时间：分钟'10','20','30','60','120','240'
     *          logon_token_period_pattern [int string] 登录token模式1、谨慎（分钟为单位）2、常规（小时为单位）3、方便（天为单位）4、游客（单位分钟没有操作注销）
     *          logon_token_period_time [int string] 登录token模式1、谨慎（分钟为单位）2、常规（小时为单位）3、方便（天为单位）4、游客（单位分钟没有操作注销）
     * @return array [json]
     *      data [raw] 数据
     * @throws \Exception
     * @title  登录配置
     * @explain 用户设置自己的登录配置
     * @baseAuth UserAuth:test
     * @router post logon-config
     */
    public function setLogonConifg(Request $Request)
    {
         $Account = AccountModel::table()
            ->where(['id'=>$this->UserInfo['id']])
            ->update($Request->post());
         if ($Account){
             $this->succeed($Account,'操作成功');
         }
         $this->error('操作失败');
    }
    /**
     * @Author pizepei
     * @Created 2019/3/23 16:23
     *
     * @param \pizepei\staging\Request $Request
     * @return array [json]
     *      data [object] 数据
     *          logon_online_count [int required] 同时在线数 '3','5','6','8','10','15'
     *          password_wrong_count [int required] 密码错误数'3','5','6','8','10'
     *          password_wrong_lock [int required] 密码错误超过限制的锁定时间：分钟'10','20','30','60','120','240'
     *          logon_token_period_pattern [int required] 登录token模式1、谨慎（分钟为单位）2、常规（小时为单位）3、方便（天为单位）4、游客（单位分钟没有操作注销）
     *          logon_token_period_time [int required] 登录token模式1、谨慎（分钟为单位）2、常规（小时为单位）3、方便（天为单位）4、游客（单位分钟没有操作注销）
     * @throws \Exception
     * @title  获取登录配置
     * @explain 获取用户登录配置
     * @baseAuth UserAuth:test
     * @router get logon-config
     */
    public function getLogonConifg(Request $Request)
    {
        $Account = AccountModel::table()
            ->where(['id'=>$this->UserInfo['id']])
            ->fetch(['logon_online_count','password_wrong_count','password_wrong_lock','logon_token_period_pattern','logon_token_period_time']);
        if ($Account){
            $this->succeed($Account,'获取成功');
        }
        $this->error('获取失败');
    }

}