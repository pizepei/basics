<?php
/**
 * @Author: pizepei
 * @ProductName: PhpStorm
 * @Created: 2019/1/20 22:20
 * @title 角色对应的菜单权限
 */

namespace pizepei\basics\model\account;


use pizepei\model\db\Model;

class AccountRoleMenuModel extends Model
{
    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'role_id'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>Model::UUID_ZERO, 'COMMENT'=>'角色id',
        ],
        'gather'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'角色对应的菜单id集合',
        ],
        'INDEX'=>[
            ['TYPE'=>'UNIQUE','FIELD'=>'role_id','NAME'=>'role_id','USING'=>'BTREE','COMMENT'=>'角色id'],
        ],
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '角色对应的菜单权限';
    /**
     * @var int 表版本（用来记录表结构版本）在表备注后面@$table_version
     */
    protected $table_version = 0;
    /**
     * @var array 表结构变更日志 版本号=>['表结构修改内容sql','表结构修改内容sql']
     */
    protected $table_structure_log = [

    ];
    protected $initData = [
        ['role_id'=>'0EQD12A2-8824-9943-E8C9-C83E40F360D1','gather'=>["0ECD12A2-8824-9843-E8C9-C33E40F36E10"]],# 角色id系统默认角色id  gather 内容我首页控制台
    ];
}