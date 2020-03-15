<?php
/**
 * Class BasicsLayuiAdminService
 * layuiadmin 处理服务
 */
namespace pizepei\basics\service\layuiadmin;
use pizepei\model\cache\Cache;

/**
 * Class BasicsLayuiAdminService
 * @package pizepei\basics\service\layuiadmin
 */
class BasicsLayuiAdminService
{
    /**
     * 获取首页index
     * @param string $domain
     * @return string
     */
    public function getIndexHtml(string $domain):string
    {
        $data = Cache::get(['BasicsLayuiAdminService','getIndexHtml']);
        if ((!app()->__EXPLOIT__ && \Deploy::ENVIRONMENT !=='develop')){
            if ($data)return $data;
        }
        $data = [
            'version'=>(!app()->__EXPLOIT__ && \Deploy::ENVIRONMENT !=='develop')?'1.0.1':date('YmdHis'),
            'title'=>\Config::PRODUCT_INFO['title'],
            'css'=>'https://www.layuicdn.com/layui-v2.5.6/css/layui.css',
            'js'=>'https://www.layuicdn.com/layui-v2.5.6/layui.all.js',
            'iconfont'=>'//at.alicdn.com/t/font_1692091_jaxujis0hta.css',//自定义图片库
            #'css'=>'./'.\Deploy::VIEW_RESOURCE_PREFIX.'/start/layui/css/layui.css',
            #'js'=>'./'.\Deploy::VIEW_RESOURCE_PREFIX.'/start/layui/layui.js',
        ];
        if (\Deploy::CDN_URL !==''){
                $data['index']  =   '../../'.\Deploy::MODULE_PREFIX.'/home/index';//index
                $data['base']   =   \Deploy::CDN_URL.'/'.\Deploy::VIEW_RESOURCE_PREFIX.((!app()->__EXPLOIT__ && \Deploy::ENVIRONMENT !=='develop')?'/dist/':'/src/');
        }else{
            $data['index']  =   '../../'.\Deploy::MODULE_PREFIX.'/home/index';//index
            $data['base']   =  './'.\Deploy::VIEW_RESOURCE_PREFIX.((!app()->__EXPLOIT__ && \Deploy::ENVIRONMENT !=='develop')?'/dist/':'/src/');
        }
        $file = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'template'.DIRECTORY_SEPARATOR.'index.html');
        $data = $this->str_replace($file,$data);
        Cache::set(['BasicsLayuiAdminService','getIndexHtml'],$data,120);
        return $data;
    }

    /**
     * 获取index配置
     * @param string $domain
     * @return string
     */
    public function getIndexJs(string $domain):string
    {
        $data = Cache::get(['BasicsLayuiAdminService','getIndexJs']);
        if ((!app()->__EXPLOIT__ && \Deploy::ENVIRONMENT !=='develop')){
            if ($data)return $data;
        }
        $data = [
            'config'=>'../../'.\Deploy::MODULE_PREFIX.'/home/config',
        ];
        $file = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'template'.DIRECTORY_SEPARATOR.'index.js');
        $data = $this->str_replace($file,$data);
        Cache::set(['BasicsLayuiAdminService','getIndexJs'],$data,120);
        return $data;
    }

    /**
     * 获取配置
     * @param string $domain
     * @return string
     */
    public function getConfig(string $domain):string
    {

        $data = Cache::get(['BasicsLayuiAdminService','getConfig']);
        if ((!app()->__EXPLOIT__ && \Deploy::ENVIRONMENT !=='develop')){
            if ($data)return $data;
        }
        $data = [
            'debug'=>(!app()->__EXPLOIT__ && \Deploy::ENVIRONMENT !=='develop')?'false':'true',
            'console'=>\Config::PRODUCT_INFO['name'].'控制台',
            'tokenName'=>\Config::ACCOUNT['GET_ACCESS_TOKEN_NAME'],
            'productInfo.name'=>\Config::PRODUCT_INFO['name'],
            'productInfo.describe'=>\Config::PRODUCT_INFO['describe'],
            'productInfo.extend'=>Helper()->json_encode(\Config::PRODUCT_INFO['extend']),
        ];
        $file = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'template'.DIRECTORY_SEPARATOR.'config.js');
        $data = $this->str_replace($file,$data);
        Cache::set(['BasicsLayuiAdminService','getConfig'],$data,120);
        return $data;
    }
    /**
     * 替换
     * @param $template
     * @param $data
     * @return string
     */
    protected function str_replace($template, $data):string
    {
        foreach($data as $key=>$vuleu){
            if(!is_array($vuleu)){
                $template = str_replace("'{{{$key}}}'",$vuleu,$template);
                $template = str_replace("{{{$key}}}",$vuleu,$template);
            }
        }
        return $template;
    }

}