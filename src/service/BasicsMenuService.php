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
    public function getMenu(string $type='admin')
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
                $data[] = $value;
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
                    $value['list'][] = $v;
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
                        if ($v['id'] == $vs['parent_id']){
                            $v['list'][] = $vs;
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
     * 获取后台菜单
     * @return array
     * @throws \Exception
     */
    public function getTreeMenu($type='admin',$spread=true)
    {
        $menuModel = $this->initModel($type);
        $menu = $menuModel->where(['status'=>2])->order('sort','desc')->fetchAll();

        $menuData= [
            'id'=>Model::UUID_ZERO,
            'title'=>'后台菜单',
            'spread'=>true,
        ];
        $this->recursiveMenu($menu,$menuData,Model::UUID_ZERO,$spread);
        $data[] = $menuData;
//
//        foreach ($menu as $key=>$value)
//        {
//            if ($value['parent_id'] == Model::UUID_ZERO){
//                $menuData = [
//                    'id'=>$value['id'],
//                    'title'=>$value['title'].'  --  ['.$value['name'].']',
//                    'spread'=>$value['spread']==0?false:true,
//                ];
//                unset($menu[$key]);
//                $this->recursiveMenu($menu,$menuData,$value['id'],$spread);
//                $data[] = $menuData;
//            }
//        }
        return $data;
    }

    /**
     * 处理子结点
     * @param $menu
     * @param $menuData
     * @param $parent_id
     */
    public function recursiveMenu(&$menu,&$menuData,$parent_id)
    {
        $data = [];
        foreach ($menu as $key=>$value)
        {
            if ($value['parent_id'] == $parent_id) {
                $menuInfo = [
                    'id'=>$value['id'],
                    'title'=>$value['title'].'  --  ['.$value['name'].']',
                    'spread'=>$value['spread']==0?false:true,
                ];
                unset($menu[$key]);
                $this->recursiveMenu($menu,$menuInfo,$value['id']);
                $data[] = $menuInfo;
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

}