<?php


namespace pizepei\basics\service;



use pizepei\basics\model\backstage\AdminMenuModel;
use pizepei\model\db\Model;

class BasicsMenuService
{

    /**
     * 获取后台菜单
     * @return array
     * @throws \Exception
     */
    public function getAdminMenu()
    {
        $AdminMenu = AdminMenuModel::table()->where(['status'=>2])->order('sort','desc')->fetchAll();
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
//        var_dump($data);
    }


    /**
     * 获取后台菜单
     * @return array
     * @throws \Exception
     */
    public function getTreeMenu($type='admin',$spread=true)
    {
        if ($type == 'admin'){
            $menu = AdminMenuModel::table()->where(['status'=>2])->order('sort','desc')->fetchAll();
        }
        $data = [];
        foreach ($menu as $key=>$value)
        {
            if ($value['parent_id'] == Model::UUID_ZERO){
                $menuData = [
                    'id'=>$value['id'],
                    'title'=>$value['title'],
                    'spread'=>$value['spread']==0?false:true,
                ];
                unset($menu[$key]);
                $this->recursiveMenu($menu,$menuData,$value['id'],$spread);
                $data[] = $menuData;
            }
        }
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
                    'title'=>$value['title'],
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


}