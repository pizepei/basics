<?php
/**
 * 首页控制器
 */
namespace pizepei\basics\controller;
use pizepei\staging\Controller;

class BasicsHome extends Controller
{
    /**
     * @return array [html]
     * @title  / 路由的应用
     * @explain 注意所有 path 路由都使用 正则表达式为唯一凭证 所以 / 路由只能有一个
     * @baseAuth UserAuth:public
     * @router get /index.html
     */
    public function index()
    {
        return $this->view();
    }
}