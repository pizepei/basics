<?php
/**
 * 控制台控制器
 */

namespace pizepei\basics\controller;


use pizepei\basics\model\account\AccountModel;
use pizepei\basics\model\console\PersonShortcutModel;
use pizepei\basics\model\console\PersonShortcutTypeModel;
use pizepei\staging\Controller;
use pizepei\staging\Request;

class BasicsConsole extends Controller
{
    /**
     * 基础控制器信息
     */
    const CONTROLLER_INFO = [
        'User'=>'pizepei',
        'title'=>'后台首页控制台',//控制器标题
        'namespace'=>'bases',//门面控制器命名空间
        'baseAuth'=>'UserAuth:test',//基础权限继承（加命名空间的类名称）
        'basePath'=>'/home/console/',//基础路由
        'baseParam'=>'[$Request:pizepei\staging\Request]',//依赖注入对象
    ];

    /**
     * @Author 皮泽培
     * @Created 2019/8/26 14:20
     * @param \pizepei\staging\Request $Request
     *      path [object]
     *          type [string] 快捷方式类型
     * @return array [json] 定义输出返回数据
     *      data [raw]
     * @title  获取快捷导航
     * @baseAuth UserAuth:test
     * @authGroup systemUser
     * @throws \Exception
     * @router get person/shortcut-list
     */
    public function personShortcut(Request $Request)
    {
        $accounId = AccountModel::table()->forceIndex(['number'])->where(['number'=>$this->Payload['number']])->cache(['Account','info'],20)->fetch(['id']);
        $data = PersonShortcutTypeModel::table()
            ->where(['Account_id'=>$accounId['id']])
            ->order('sort','desc')
            ->fetchAll(['name','id','explain']);
        foreach ($data as $key=>&$value)
        {
            $value['list'] = PersonShortcutModel::table()
                ->where(['type_id'=>$value['id'],'status'=>2])
                ->order('sort','desc')
                ->fetchAll(['name','id','url','explain','sort']);
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
     *          name [string] 名称
     *          url [string] url地址
     *          explain [string] 描述
     *          status [int] 状态类型
     * @title  添加导航到分类
     * @explain 添加个人导航到分类中
     * @baseAuth UserAuth:test
     * @authGroup systemUser
     * @throws \Exception
     * @router post person/shortcut/:typeId[uuid]
     */
    public function addPersonShortcut(Request $Request)
    {
        $accounId = AccountModel::table()->where(['number'=>$this->Payload['number']])->cache(['Account','info'],20)->fetch(['id']);
        $PersonShortcutType = PersonShortcutTypeModel::table()->where(['id'=>$Request->path('typeId'),'Account_id'=>$accounId['id']])->fetch();
        if (empty($PersonShortcutType)){
            return $this->error('分类不存在');
        }

        $accounId = AccountModel::table()->where(['number'=>$this->Payload['number']])->cache(['Account','info'],20)->fetch(['id']);
        $data = $Request->post();
        $data['type_id'] = $Request->path('typeId');
        $data['Account_id'] = $accounId['id'];
        if (PersonShortcutModel::table()->add($data)){
            return $this->succeed([],'添加成功');
        }
        return $this->error('添加错误');
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
     * @title  获取分类下导航
     * @explain 获取分类下的导航列表
     * @baseAuth UserAuth:test
     * @authGroup systemUser
     * @throws \Exception
     * @router get person/shortcut/:typeId[uuid]
     */
    public function getPersonInfo(Request $Request)
    {
        $accounId = AccountModel::table()->where(['number'=>$this->Payload['number']])->cache(['Account','info'],20)->fetch(['id']);
        $PersonShortcutType = PersonShortcutTypeModel::table()->where(['id'=>$Request->path('typeId'),'Account_id'=>$accounId['id']])->fetch();

        if (empty($PersonShortcutType)){return $this->error('分类不存在');}

        $Shortcut = PersonShortcutModel::table()->where([
            'type_id'=>$Request->path('typeId'),
            'Account_id'=>$accounId['id']
        ])->fetchAll();

        return $this->succeed(['list'=>$Shortcut],'获取成功');

    }

    /**
     * @Author 皮泽培
     * @Created 2019/8/26 14:20
     * @param \pizepei\staging\Request $Request
     *      path [object]
     *          id [string] 快捷方式类型
     *      raw [object] 添加的数据
     *          name [string] 名称
     *          url [string] url地址
     *          explain [string] 描述
     *          status [int] 状态类型
     *          sort [int] 排序
     * @return array [json] 定义输出返回数据
     *      data [raw]
     * @title  编辑快捷导航
     * @explain 编辑更新快捷导航
     * @baseAuth UserAuth:test
     * @authGroup systemUser
     * @throws \Exception
     * @router put person/shortcut/:id[uuid]
     */
    public function updatePersonInfo(Request $Request)
    {
        $accounId = AccountModel::table()->where(['number'=>$this->Payload['number']])->cache(['Account','info'],20)->fetch(['id']);
        # 编辑
        $data = $Request->raw();
        $Shortcut = PersonShortcutModel::table()->where([
            'id'=>$Request->path('id')
        ])->update($data);
        if (empty($Shortcut)){
            return $this->error('修改失败');
        }
        return $this->succeed($Shortcut,'更新成功');

    }


    /**
     * @Author 皮泽培
     * @Created 2019/8/26 14:20
     * @param \pizepei\staging\Request $Request
     *      path [object]
     *          id [string] 快捷方式类型
     *      raw [object] 添加的数据
     *          name [string] 名称
     *          url [string] url地址
     *          explain [string] 描述
     *          status [int] 状态类型
     * @return array [json] 定义输出返回数据
     *      data [raw]
     * @title  删除导航
     * @explain 删除导航
     * @baseAuth UserAuth:test
     * @authGroup systemUser
     * @throws \Exception
     * @router delete person/shortcut/:id[uuid]
     */
    public function deletePersonInfo(Request $Request)
    {
        $accounId = AccountModel::table()->where(['number'=>$this->Payload['number']])->cache(['Account','info'],20)->fetch(['id']);
        $Shortcut = PersonShortcutModel::table()
            ->where(['Account_id'=>$accounId['id']])
            ->del(['id'=>$Request->path('id')]);
        if (empty($Shortcut)){
            return $this->error('删除失败');
        }
        return $this->succeed($Shortcut,'删除成功');

    }





}