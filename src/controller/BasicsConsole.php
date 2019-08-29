<?php
/**
 * 控制台控制器
 */

namespace pizepei\basics\controller;


use model\basics\account\AccountModel;
use pizepei\basics\model\console\PersonShortcutModel;
use pizepei\basics\model\console\PersonShortcutTypeModel;
use pizepei\staging\Controller;
use pizepei\staging\Request;

class BasicsConsole extends Controller
{
    /**
     * @Author 皮泽培
     * @Created 2019/8/26 14:20
     * @param \pizepei\staging\Request $Request
     *      path [object]
     *          type [string] 快捷方式类型
     * @return array [json] 定义输出返回数据
     *      data [raw]
     * @title  获取个人快捷方式
     * @explain 退出登录
     * @baseAuth UserAuth:test
     * @throws \Exception
     * @router get person/shortcut-list
     */
    public function personShortcut(Request $Request)
    {

        $accounId = AccountModel::table()->where(['number'=>$this->Payload['number']])->cache(['Account','info'],20)->fetch(['id']);

        $data = PersonShortcutTypeModel::table()->where(['Account_id'=>$accounId['id']])->fetchAll(['name','id','explain']);

        foreach ($data as $key=>&$value)
        {
            $value['list'] = PersonShortcutModel::table()->where(['type_id'=>$value['id']])->fetchAll(['name','id','url','explain']);
        }
        return $this->succeed($data,'获取成功');
    }

    /**
     * @Author 皮泽培
     * @Created 2019/8/26 14:20
     * @param \pizepei\staging\Request $Request
     *      path [object]
     *          type [string] 快捷方式类型
     *      post [object] 添加的数据
     *          name [string] 名称
     *          url [string] url地址
     *          explain [string] 描述
     *          status [int] 状态类型
     * @return array [json] 定义输出返回数据
     *      data [raw]
     * @title  添加个人快捷方式到对应类型中
     * @explain 退出登录
     * @baseAuth UserAuth:test
     * @throws \Exception
     * @router post person/shortcut/:typeId[uuid]
     */
    public function addPersonShortcut(Request $Request)
    {
        $PersonShortcutType = PersonShortcutTypeModel::table()->get($Request->path('typeId'));
        if (empty($PersonShortcutType)){
            return $this->error([],'分类不存在');
        }
        $accounId = AccountModel::table()->where(['number'=>$this->Payload['number']])->cache(['Account','info'],20)->fetch(['id']);

        if (PersonShortcutModel::table()->add([
            'Account_id'=>$accounId['id'],
            'name'=>$Request->post('name'),
            'type_id'=>$Request->path('typeId'),
            'url'=>$Request->post('url'),
            'status'=>$Request->post('status'),
            'explain'=>$Request->post('explain'),
        ])){
            return $this->succeed([],'添加成功');
        }
        return $this->error([],'添加错误');
    }
}