<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/1/15
 * Time: 11:28
 * @title 账号管理控制器
 */

namespace pizepei\basics\controller;

use model\basics\account\AccountModel;
use pizepei\basics\service\account\BasicsAccountService;
use pizepei\helper\Helper;
use pizepei\microserviceClient\MicroClient;
use pizepei\model\cache\Cache;
use pizepei\model\redis\Redis;
use pizepei\randomInformation\RandomUserInfo;
use pizepei\service\verifyCode\GifverifyCode;
use pizepei\staging\Controller;
use pizepei\staging\Request;
use pizepei\wechat\model\OpenWechatCodeAppModel;
use pizepei\wechatClient\Client;

class BasicsAccount extends Controller
{
    /**
     * @return array [object]
     * @title  账号获取列表
     * @explain 注意所有 path 路由都使用 正则表达式为唯一凭证 所以 / 路由只能有一个
     * @router get index
     */
    public function index()
    {

    }

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
            return $this->error([],'操作频繁请稍后再试!!');
        }
        # 验证验证码
        $CodeApp = \Config::WEC_CHAT_CODE;
        $Client = new Client($CodeApp);
        $res = $Client->codeAppVerify($Request->post('encrypted'),$Request->post('id'),$Request->post('code'),$Request->post('openid'),$this->app->__CLIENT_IP__);
        # 验证通过
        if (!isset($res['content']) || !isset($res['type']) || $res['type'] !=='register'){
            return $this->error([],'请求错误');
        }
        # 判断手机验证码
        if ($res['content']['param']['number'] !== $Request->post('phone')  || $res['content']['param']['numberCode'] !== (int)$Request->post('phone_code')){
            return $this->error([],'手机验证码错误！');
        }
        # 判断邮箱验证码
        if ($res['content']['param']['email'] !==$Request->post('email')  || $res['content']['param']['emailCode'] !== (int)$Request->post('email_code')){
            return $this->error([],'邮箱验证码错误！');
        }
        # 注册账号
        $Service = new BasicsAccountService();
        $res = $Service->register(\Config::ACCOUNT,$Request->post());
        if($res['result'])
        {
            return $this->succeed('',$res['msg']);
        }else{
            return $this->error('',$res['msg']);
        }
    }
    /**
     * @Author pizepei
     * @Created 2019/3/23 16:23
     *
     * @param \pizepei\staging\Request $Request
     *      post [object] post
     *          phone [int number] 手机号码
     *          password [string required] 密码
     *          code [string required] 验证码
     *          codeFA [string] 2FA双因子认证code
     * @return array [json]
     *      data [object] 数据
     *          result [raw] 结果
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
            return $this->error($Request->post('phone'),'用户名或密码错误');
        }
        $AccountService = new BasicsAccountService();

        $result =  $AccountService->logon(\Config::ACCOUNT,$Request->post(),$Account,$this);
        if(isset($result['jwtArray']['str']) && $result['jwtArray']){
            return $this->succeed([
                'result'=>$result,
                'access_token'=>$result['jwtArray']['str']
            ],'登录成功');
        }
        return $this->error($result,$result['msg']);
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
     * @router put password
     */
    public function changePassword(Request $Request)
    {
        $Account = AccountModel::table()
            ->where(['phone'=>$Request->post('phone')])
            ->replaceField('fetch',['type','status']);
        if(empty($Account)){
            $this->error($Request->post(),'用户不存在');
        }
        $AccountService = new BasicsAccountService();
        return $AccountService->changePassword(\Config::ACCOUNT,$Request->post(),$Account,$this);
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
     *          code [string required] 短信验证码
     * @return array [json]
     * @title  发送短信验证码
     * @explain 验证结果并且返回一个唯一的参数以进行后面的配置
     * @throws \Exception
     * @router post smsCodeVerification
     */
    public function smsCodeVerification(Request $Request)
    {
        #
        return $this->succeed('','成功');
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
     *              nonce [string required] 随机数
     *              encrypt_msg [string required] 密文
     *          openid [string required] openid
     *          code [int] 验证码
     *          id [uuid] 事件id
     * @return array [json]
     *      data [raw]
     * @title  发送注册验证码(邮箱和手机)
     * @explain 发送注册验证码
     * @throws \Exception
     * @router post sms-code-register-send
     */
    public function smsCodeRegisterSend(Request $Request)
    {
        $CodeApp = \Config::WEC_CHAT_CODE;
        $Client = new Client($CodeApp);
        $res = $Client->codeAppVerify($Request->post('encrypted'),$Request->post('id'),$Request->post('code'),$Request->post('openid'),$this->app->__CLIENT_IP__);
        # 验证通过
        if (!isset($res['content']) || !isset($res['type']) || $res['type'] !=='register'){
            return $this->error([],'请求错误');
        }
        # 本地验证
        if (BasicsAccountService::codeSendFrequency('number',$res['content']['param']['number'])){
            return $this->error($res['content']['param']['number'],'短信发送频率过高请稍后再尝试!!');
        }
        # 本地验证
        if (BasicsAccountService::codeSendFrequency('mail',$res['content']['param']['email'])){
            return $this->error($res['content']['param']['email'],'邮件发送频率过高请稍后再尝试!!');
        }
        # 查询是否已经存在邮箱或者手机号码
        if (AccountModel::table()->where(['email'=>$res['content']['param']['email']])->fetch('id')){return $this->error($res['content']['param']['email'],'邮件已注册!');}
        if (AccountModel::table()->where(['phone'=>$res['content']['param']['number']])->fetch('id')){return $this->error($res['content']['param']['number'],'手机号码已注册!');}
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
            return $this->succeed('','邮件验证码发送失败请稍后再尝试！');
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
            return $this->succeed('','发送成功');
        }else if (isset($res['data']['Code']) && $res['data']['Code'] !== 'OK'){
            return $this->succeed('','发送频率过高请稍后再尝试！');
        }else{
            return $this->succeed('','发送失败请稍后再尝试！');
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
        echo GifverifyCode::Draw(4, 2, 100, 31, 4, 1, 70, "secode");
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
        if (BasicsAccountService::codeSendFrequency('universal',$Request->path('number').$Request->path('email'),5)){
            return $this->error([],'获取二维码频率过高!!');
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
     *   path [object] 路径参数
     *   post [object] post参数
     * @return array [json] 定义输出返回数据
     * @title  微信验证回调地址
     * @explain 微信验证回调地址
     * @baseAuth Resource:public
     * @throws \Exception
     * @router get wecht-qr-target
     */
    public function wechtQrTarget()
    {
//        $Client = new \pizepei\service\websocket\Client(['data'=>['uid'=>Helper::init()->getUuid()]]);
//        $Client->connect();
//        var_dump($Client->sendUser('661846A0-FF37-F459-93C1-462EC854456D',
//            ['type'=>'init','content'=>'您好','appid'=>'00663B8F-D021-373C-8330-E1DD3440FF3C'],true));
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

    
}