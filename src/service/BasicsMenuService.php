<?php


namespace pizepei\basics\service;



use pizepei\basics\model\backstage\AdminMenuModel;
use pizepei\model\db\Model;

class BasicsMenuService
{


    public function getAdminMenu()
    {
        $AdminMenu = AdminMenuModel::table()->where(['status'=>2])->fetchAll();
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



}