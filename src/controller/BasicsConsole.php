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
        $data = PersonShortcutTypeModel::table()
            ->where(['Account_id'=>$this->UserInfo['id']])
            ->order('sort','desc')
            ->fetchAll(['name','id','explain']);
        if (empty($data)){
            # 如果没有就创建第一个导航默认分类   (后期从后台配置中获取)
            $type = PersonShortcutTypeModel::table()->add([
                'account_id'    =>$this->UserInfo['id'],
                'name'          =>'政策数据源',
                'explain'       =>'各种宏观经济与政策数据平台资源',
                'status'        =>2,
                'sort'          =>49,
            ]);
            # $typeId
            $typeId = key($type);
            if ($typeId){
                PersonShortcutModel::table()->add(
                    [
                        [
                            'name'              =>'全球经济数据',
                            'type_id'           =>$typeId,
                            'account_id'        =>$this->UserInfo['id'],
                            'explain'           =>'一个提供全球国家宏观经济数据网站',
                            'url'               =>'https://zh.tradingeconomics.com/china/indicators',
                            'status'            =>2,
                            'sort'              =>99,

                        ],
                        [
                            'name'              =>'中国政府网',
                            'type_id'           =>$typeId,
                            'account_id'        =>$this->UserInfo['id'],
                            'explain'           =>'国家政府网站最新政策',
                            'url'               =>'http://www.gov.cn/zhengce/index.htm',
                            'status'            =>2,
                            'sort'              =>98,

                        ],
                        [
                            'name'              =>'统计局',
                            'type_id'           =>$typeId,
                            'account_id'        =>$this->UserInfo['id'],
                            'explain'           =>'中华人民共和国国家统计局，各种宏观数据',
                            'url'               =>'http://www.stats.gov.cn/tjsj/zxfb/',
                            'status'            =>2,
                            'sort'              =>97,

                        ],
                        [
                            'name'              =>'工信部',
                            'type_id'           =>$typeId,
                            'account_id'        =>$this->UserInfo['id'],
                            'explain'           =>'中华人民共和国工业和信息化部',
                            'url'               =>'http://www.miit.gov.cn/',
                            'status'            =>2,
                            'sort'              =>96,

                        ],
                        [
                            'name'              =>'市监局',
                            'type_id'           =>$typeId,
                            'account_id'        =>$this->UserInfo['id'],
                            'explain'           =>'中华人民共和国市场监督管理总局',
                            'url'               =>'http://www.samr.gov.cn/',
                            'status'            =>2,
                            'sort'              =>80,

                        ],
                    ]);
            }
            # 重新查询分类数据
            $data = PersonShortcutTypeModel::table()
                ->where(['account_id'=>$this->UserInfo['id']])
                ->order('sort','desc')
                ->fetchAll(['name','id','explain']);
        }
        if ($data){
            foreach ($data as $key=>&$value)
            {
                $value['list'] = PersonShortcutModel::table()
                    ->where(['type_id'=>$value['id'],'status'=>2])
                    ->order('sort','desc')
                    ->fetchAll(['name','id','url','explain','sort']);
            }
        }
        return $this->succeed($data??[],'获取成功');
    }


    /**
     * @Author 皮泽培
     * @Created 2019/8/26 14:20
     * @param \pizepei\staging\Request $Request
     *      path [object]
     *          type [string] 快捷方式类型
     * @return array [json] 定义输出返回数据
     *      data [raw]
     * @title  获取公共快捷导航
     * @baseAuth UserAuth:public
     * @authGroup systemUser
     * @throws \Exception
     * @router get public/shortcut-list
     */
    public function publicShortcut(Request $Request)
    {
        return $this->succeed($data??[],'获取成功');
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
        $PersonShortcutType = PersonShortcutTypeModel::table()->where(['id'=>$Request->path('typeId'),'account_id'=>$this->UserInfo['id']])->fetch();
        if (empty($PersonShortcutType)){
            return $this->error('分类不存在');
        }
        $data = $Request->post();
        $data['type_id'] = $Request->path('typeId');
        $data['account_id'] = $this->UserInfo['id'];
        if (PersonShortcutModel::table()->add($data)){
            return $this->succeed([],'添加成功');
        }
        return $this->error('添加错误');
    }


    /**
     * @Author 皮泽培
     * @Created 2019/8/26 14:20
     * @param \pizepei\staging\Request $Request
     *      post [object] 添加的数据
     *          name [string] 名称
     *          images [string] url地址
     *          explain [string] 描述
     *          status [int] 状态类型
     * @return array [json] 定义输出返回数据
     *      data [raw]
     *          name [string] 名称
     *          images [string] url地址
     *          explain [string] 描述
     *          status [int] 状态类型
     * @title  添加导航到分类
     * @explain 添加个人导航到分类中
     * @baseAuth UserAuth:test
     * @authGroup systemUser
     * @throws \Exception
     * @router post person/shortcut/type
     */
    public function addPersonShortcutType(Request $Request)
    {
        # 查询当前用户下是否已经有相同的分类
        $PersonShortcutType = PersonShortcutTypeModel::table()->where([
            'account_id'=>$this->UserInfo['id'],
            'name'=>$Request->post('name'),
        ])->fetch();
        if ($PersonShortcutType){ $this->error('分类已存在:'.$Request->post('name'));}
        # 写入分类数据
        $data = $Request->post();

        $data['account_id'] = $this->UserInfo['id'];
        $res = PersonShortcutTypeModel::table()->add($data);
        if (!$res)$this->error('添加错误');
        $this->succeed('','操作成功');
    }

    /**
     * @Author 皮泽培
     * @Created 2019/8/26 14:20
     * @param \pizepei\staging\Request $Request
     * @return array [json] 定义输出返回数据
     *      data [object]
     *          list [objectList]
     *              id  [uuid] id
     *              name [string] 名称
     *              images [string] 图片url地址
     *              sort [int]  排序
     *              explain [string] 描述
     *              creation_time [string] 描述
     *              update_time [string] 描述
     *              status [int] 状态类型
     * @title  导航到分类列表
     * @explain 导航到分类列表
     * @baseAuth UserAuth:test
     * @authGroup systemUser
     * @throws \Exception
     * @router get person/shortcut/type-list
     */
    public function addPersonShortcutTypeList(Request $Request)
    {
        # 查询当前用户下是否已经有相同的分类
        $PersonShortcutType = PersonShortcutTypeModel::table()
            ->where([
            'account_id'=>$this->UserInfo['id'],
        ])->fetchAll();
        # 写入分类数据
        $this->succeed(['list'=>$PersonShortcutType],'操作成功');
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
        $PersonShortcutType = PersonShortcutTypeModel::table()->where(['id'=>$Request->path('typeId'),'Account_id'=>$this->UserInfo['id']])->fetch();
        if (empty($PersonShortcutType)){return $this->error('分类不存在');}
        $Shortcut = PersonShortcutModel::table()->where([
            'type_id'=>$Request->path('typeId'),
            'account_id'=>$this->UserInfo['id']
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
        $data = $Request->raw();
        $Shortcut = PersonShortcutModel::table()->where([
            'id'=>$Request->path('id'),
            'account_id'=>$this->UserInfo['id']
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
     *          images [string] url地址
     *          explain [string] 描述
     *          status [int] 状态类型
     *          sort [int] 排序
     * @return array [json] 定义输出返回数据
     *      data [raw]
     * @title  编辑快捷导航类型
     * @explain 编辑更新快捷导航类型
     * @baseAuth UserAuth:test
     * @authGroup systemUser
     * @throws \Exception
     * @router put person/shortcut/type/:id[uuid]
     */
    public function updatePersonTypeInfo(Request $Request)
    {
        $data = $Request->raw();
        $Shortcut = PersonShortcutTypeModel::table()->where([
            'id'=>$Request->path('id'),
            'account_id'=>$this->UserInfo['id']
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
        $Shortcut = PersonShortcutModel::table()
            ->where(['account_id'=>$this->UserInfo['id']])
            ->del(['id'=>$Request->path('id')]);
        if (empty($Shortcut)){
            return $this->error('删除失败');
        }
        return $this->succeed($Shortcut,'删除成功');

    }

    /**
     * @Author 皮泽培
     * @Created 2019/8/26 14:20
     * @param \pizepei\staging\Request $Request
     *      path [object]
     *          id [string] 快捷方式类型
     * @return array [json] 定义输出返回数据
     *      data [raw]
     * @title  删除导航分类
     * @explain 删除导航分类
     * @baseAuth UserAuth:test
     * @authGroup systemUser
     * @throws \Exception
     * @router delete person/shortcut/type/:id[uuid]
     */
    public function deletePersonTypeInfo(Request $Request)
    {
        $ShortcutRes = PersonShortcutModel::table()
            ->where(['account_id'=>$this->UserInfo['id'],'type_id'=>$Request->path('id')])
            ->fetch();
        if ($ShortcutRes)return $this->error('分类下有导航不可删除！');
        $ShortcutType = PersonShortcutTypeModel::table()
            ->where(['account_id'=>$this->UserInfo['id']])
            ->del(['id'=>$Request->path('id')]);
        if (empty($ShortcutType)){
            return $this->error('删除失败');
        }
        return $this->succeed($ShortcutType,'删除成功');

    }



}