<?php
/**
 * Class BasicsLayuiAdminService
 * layuiadmin 处理服务
 */
namespace pizepei\basics\service\layuiadmin;
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
        $data = [
            'title'=>\Config::PRODUCT_INFO['title'],
            'index'=>'../../'.\Deploy::MODULE_PREFIX.'/home/index',
            'base'=>'./'.\Deploy::VIEW_RESOURCE_PREFIX.'/src/',
            'css'=>'./'.\Deploy::VIEW_RESOURCE_PREFIX.'/start/layui/css/layui.css',
            'js'=>'./'.\Deploy::VIEW_RESOURCE_PREFIX.'/start/layui/layui.js',
        ];
        $file = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'template'.DIRECTORY_SEPARATOR.'index.html');
        return $this->str_replace($file,$data);
    }

    /**
     * 获取配置
     * @param string $domain
     * @return string
     */
    public function getIndexJs(string $domain):string
    {
        $data = [
            'config'=>'../../'.\Deploy::MODULE_PREFIX.'/home/config',
        ];
        $file = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'template'.DIRECTORY_SEPARATOR.'index.js');
        return $this->str_replace($file,$data);

    }

    /**
     * 获取配置
     * @param string $domain
     * @return string
     */
    public function getConfig(string $domain):string
    {
        $data = [
            'tokenName'=>\Config::ACCOUNT['GET_ACCESS_TOKEN_NAME'],
            'productInfo.name'=>\Config::PRODUCT_INFO['name'],
            'productInfo.describe'=>\Config::PRODUCT_INFO['describe'],
        ];
        $file = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'template'.DIRECTORY_SEPARATOR.'config.js');
        return $this->str_replace($file,$data);
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