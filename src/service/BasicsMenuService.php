<?php


namespace pizepei\basics\service;



use pizepei\basics\model\backstage\AdminMenuModel;
use pizepei\model\db\Db;
use pizepei\model\db\Model;
use function Sodium\add;

class BasicsMenuService
{

    /**
     * 获取后台菜单
     * @return array
     * @throws \Exception
     */
    public function getMenuList(string $type='admin',$menuId)
    {
        $menuModel = $this->initModel($type);
        $AdminMenu = $menuModel->where(['status'=>2])->order('sort','desc')->fetchAll();
        $data = [];
        /**
         * 合并
         */
        foreach ($AdminMenu as $key=>$value)
        {
            if ($value['parent_id'] == Model::UUID_ZERO){
                if ($menuId !== 'SuperAdmin' ) {
                    if (in_array($value['id'],$menuId)){
                        $data[] = $value;
                    }
                }else{
                    $data[] = $value;
                }

                unset($AdminMenu[$key]);
            }
        }

        /**
         * 二级菜单
         */
        foreach ($data as $key=>&$value)
        {

            foreach ($AdminMenu as $k=>$v)
            {
                if ($value['id'] == $v['parent_id']){
                    if ($menuId !== 'SuperAdmin' ){
                        if (in_array($v['id'],$menuId)){
                            $value['list'][] = $v;
                        }
                    }else{
                        $value['list'][] = $v;
                    }

                    unset($AdminMenu[$k]);
                }
            }
        }

        foreach ($data as $key=>&$value)
        {
            if (isset($value['list'])){
                foreach ($value['list'] as $k=>&$v)
                {
                    foreach ($AdminMenu as $ks=>$vs)
                    {
                        if ($v['id'] === $vs['parent_id']){
                            if ($menuId !== 'SuperAdmin' ) {
                                if (in_array($v['id'],$menuId)){
                                    $v['list'][] = $vs;
                                }else{

                                }
                            }else{
                                $v['list'][] = $vs;
                            }
                            unset($AdminMenu[$ks]);
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 初始化菜单模型
     * @param string $type
     * @return Model
     * @throws \Exception
     */
    private function initModel(string $type='admin'):object
    {
        if ($type == 'admin'){
            $menu = AdminMenuModel::table();
        }
        return $menu;
    }

    /**
     * @Author 皮泽培
     * @Created 2019/11/16 10:44
     * @param string $type 菜单类型
     * @param bool $spread  是否展开
     * @param string $roleId 角色id
     * @param array $gather  当前角色选中的菜单id集合
     * @param string $resultType  filtration 过滤没有权限的菜单 showChecked 根据$gather设置checked  default不处理
     * @title  获取后台菜单
     * @explain 路由功能说明
     * @return array
     * @throws \Exception
     */
    public function getTreeMenu($type='admin',$spread=true,$roleId=Model::UUID_ZERO,$gather=[],$resultType='default')
    {
        $menuModel = $this->initModel($type);
        $menu = $menuModel->where(['status'=>2])->order('sort','desc')->fetchAll();

        $menuData= [
            'id'=>$roleId,
            'title'=>'后台菜单',
            'spread'=>true,
            'disabled'=>true,
        ];
        $this->recursiveMenu($menu,$menuData,Model::UUID_ZERO,$spread,$gather,$resultType);
        $data[] = $menuData;
        return $data;
    }

    /**
     * 处理子结点
     * @param $menu
     * @param $menuData
     * @param $parent_id
     */
    public function recursiveMenu(&$menu,&$menuData,$parent_id,$spread,$gather,$resultType)
    {
        $data = [];
        foreach ($menu as $key=>$value)
        {
            if ($gather ==[] || in_array($value['id'],$gather)|| in_array($resultType,['default','showChecked'])){
                if ($value['parent_id'] == $parent_id) {
                    if ($gather ===null){ $gather = [];}
                    $menuInfo = [
                        'id'=>$value['id'],
                        'title'=>$value['title'].'  --  ['.$value['name'].']',
                        'spread'=>$value['spread']==0?false:true,
                        # 如果$resultType ==  showChecked 并且菜单id在$gather中就 true
                        'checked'=>in_array($value['id'],$gather)?true:false,
                    ];
                    unset($menu[$key]);
                    $this->recursiveMenu($menu,$menuInfo,$value['id'],$spread,$gather,$resultType);
                    $data[] = $menuInfo;
                }
            }
        }
        if ($data !==[]){
            $menuData['children'] = $data;
        }
    }

    /**
     * 删除
     * @param string $id
     * @param string $type
     */
    public function delMenu(string $id,string $type='admin')
    {
        $menuModel = $this->initModel($type);
        if (!empty($menuModel->where(['parent_id'=>$id])->fetch(['id']))){
            throw new \Exception('该菜单下有子菜单');
        }
        if (!$menuModel->where(['id'=>$id])->del(['id'=>$id])){
            throw new \Exception('删除失败');
        }
        return true;
    }

    /**
     * 获取一个菜单的详情
     * @param string $id
     * @param string $type
     */
    public function getMenuInfo(string $id,string $type='admin')
    {
        $menuModel = $this->initModel($type);
        return $menuModel->get($id);
    }


    /**
     * 添加
     * @param string $data
     * @param string $type
     * @return array
     * @throws \Exception
     */
    public function addMenu(array $data,string $type='admin')
    {
        # 检查数据
        if (!isset($data['parent_id'])){throw new \Exception('parent_id must');}
        if (!isset($data['title'])){throw new \Exception('title must');}
        if (!isset($data['name'])){throw new \Exception('name must');}
        $data['spread'] = $data['spread']=='on'?1:0;
        $menuModel = $this->initModel($type);

        if ($data['parent_id'] !== Model::UUID_ZERO ){
            if (empty($menuModel->where(['id'=>$data['parent_id']])->fetch())){
                if (!isset($data['name'])){throw new \Exception('parent must');}
            }
        }
        $res = $menuModel->add($data);
        if (empty($res)){
            throw new \Exception('添加失败');
        }
        return $res;
    }

    /**
     * 添加
     * @param string $data
     * @param string $type
     * @return array
     * @throws \Exception
     */
    public function updateMenu(string $id,array $data,string $type='admin')
    {
        # 检查数据
        if (isset($data['parent_id'])){throw new \Exception('parent_id Referred by');}
        if (!isset($data['title'])){throw new \Exception('title must');}
        if (!isset($data['name'])){throw new \Exception('name must');}
        $data['spread'] = $data['spread']??0;
        $data['spread'] = $data['spread'] === 'on'?1:0;
        $menuModel = $this->initModel($type);
        $res = $menuModel->where(['id'=>$id])->update($data);
        if (empty($res)){
            throw new \Exception('修改失败');
        }
        return $res;
    }

    /**
     * @Author 皮泽培
     * @Created 2019/11/16 10:27
     * @param $data
     * @title  获取menuid
     * @return array
     * @throws \Exception
     */
    public function updateRoleMenuId($data)
    {
        foreach ($data as $value){
            if (isset($value['id'])){
                $menuId[] = $value['id'];
                if (isset($value['children']) && !empty($value['children']) && is_array($value['children'])){
                    $this->recursiveUpdateRoleMenuId($menuId,$value['children']);
                }
            }
        }
        return $menuId;
    }

    /**
     * @Author 皮泽培
     * @Created 2019/11/16 10:27
     * @param $menuId
     * @param $children
     * @title  递归函数获取menuid
     * @throws \Exception
     */
    public function recursiveUpdateRoleMenuId(&$menuId,$children)
    {
        foreach ($children as $value){
            if (isset($value['id'])){
                $menuId[] = $value['id'];
                if (isset($value['children']) && !empty($value['children']) && is_array($value['children'])){
                    $this->recursiveUpdateRoleMenuId($menuId,$value['children']);
                }
            }
        }
    }

    /**
     * @Author 皮泽培
     * @Created 2019/12/26 15:58
     * @param array|string $menuId 如果是SuperAdmin 就显示超级管理员权限 array 就屏蔽超级管理员菜单 再使用菜单id判断
     * @param string $type  菜单类型 目前只有admin
     * @title  获取用户可看的菜单
     */
    public function getUserMenuList($menuId,string $type='admin'):array
    {
        $data = \BaseMenu::DATA;
        if ($menuId !=='SuperAdmin' && !is_array($menuId)){
            error('错误的menuId');
        }else if ($menuId ==='SuperAdmin') {
            # 超级管理员直接返回
            return $data;
        }else if ($menuId ===[]){
            return [];
        }
        #  获取基础菜单数据 菜单有多少层级运行多少次
        $this->isUserMenuList($data,$menuId);

        #$this->isUserMenuList($data,$menuId);
        #$this->isUserMenuList($data,$menuId);
        return $data;
    }

    /**
     * @Author 皮泽培
     * @Created 2019/12/26 16:12
     * @param $data
     * @title  判断过滤用户菜单权限
     * @throws \Exception
     */
    public function isUserMenuList(&$data,$menuId)
    {
        foreach ($data as $key =>&$value)
        {
            $value['SuperAdmin'] = $value['SuperAdmin']??false;

            if ($menuId !=='SuperAdmin' && $value['SuperAdmin'] ===true){
                // var_dump($value['title']);
                # 如果菜单是必须超级管理员可看 当前用户不是超级管理员 就直接删除当前菜单
                unset($data[$key]);
            }else{

                # 正常菜单  判断当前菜单id是否在$menuId中 是否有下一级
                if (!in_array($value['id'],$menuId)){
                    # 不在其中  直接删除  没有下一级
                    unset($data[$key]);
                }else{

                    if ( isset($value['list']) && is_array($value['list']) && $value['list'] !==[] )
                    {

                        $this->isUserMenuList($value['list'],$menuId);
                    }
                }
            }
        }

    }


    /**
     * 获树菜单导航
     */
    public function getUserMenuListTree(array &$data,array $menuId)
    {
        if (!$data) error('数据为空');

        foreach ($data as $key=>&$value)
        {

            $value['field'] = $value['id'];                                             # 节点字段名	String	一般对应表字段名
            $value['checked'] = in_array($value['id'],$menuId)?true:false;              # 节点是否初始为选中状态（如果开启复选框的话），默认 false	Boolean	true
            $value['disabled'] = ($value['SuperAdmin']??false)?true:false;              # 节点是否为禁用状态。默认 false	Boolean	false
            if (isset($value['list']) && is_array($value['list']) && !empty($value['list']))  {
                $value['children'] = $value['list'];
                $this->getUserMenuListTree($value['children'],$menuId);
            }
        }
    }

}