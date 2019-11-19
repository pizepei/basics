<?php
/**
 * @Author: pizepei
 * @ProductName: PhpStorm
 * @Created: 2019/1/20 22:20
 * @title 角色表
 */

namespace pizepei\basics\model\account;


use pizepei\model\db\Model;

class AccountRoleModel extends Model
{
    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'name'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'角色名称',
        ],
        'apps_id'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>Model::UUID_ZERO, 'COMMENT'=>'应用id',
        ],
        'remark'=>[
            'TYPE'=>'varchar(522)', 'DEFAULT'=>'', 'COMMENT'=>'角色备注',
        ],
        'type'=>[
            'TYPE'=>"ENUM('1','2','3','4','5','6','7','8')", 'DEFAULT'=>'1', 'COMMENT'=>'账号类型1普通子账号common、2游客tourist、3应用账号app、4应用管理员appAdmin、5应用超级管理员appSuperAdmin、6超级管理员Administrators',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4','5')", 'DEFAULT'=>'1', 'COMMENT'=>'状态1等待审核、2审核通过3、禁止使用4、保留',
        ],
        /**
         * UNIQUE 唯一
         * SPATIAL 空间
         * NORMAL 普通 key
         * FULLTEXT 文本
         */
        'INDEX'=>[
            //  NORMAL KEY `create_time` (`create_time`) USING BTREE COMMENT '参数'
            ['TYPE'=>'UNIQUE','FIELD'=>'name','NAME'=>'name','USING'=>'BTREE','COMMENT'=>'角色名称'],
        ],//索引 KEY `ip` (`ip`) COMMENT 'sss 'user_name

        'PRIMARY'=>'id',//主键

    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '角色表';
    /**
     * @var int 表版本（用来记录表结构版本）在表备注后面@$table_version
     */
    protected $table_version = 0;
    /**
     * @var array 表结构变更日志 版本号=>['表结构修改内容sql','表结构修改内容sql']
     */
    protected $table_structure_log = [

    ];
    /**
     * 类型模板
     * 状态1等待审核、2审核通过3、禁止使用4、保留
     * replace_type
     */
    protected $replace_status =[
        1=>'等待审核',
        2=>'审核通过',
        3=>'禁止使用',
        4=>'保留',
        5=>'保留',
    ];
    /**
     * 账号类型
     * 账号类型1普通子账号common、2游客tourist、3应用账号app、4应用管理员appAdmin、5应用超级管理员appSuperAdmin、6超级管理员Administrators
     * @var array
     */
    protected $replace_type =[
        1=>'普通子账号common',
        2=>'游客tourist',
        3=>'应用账号app',
        4=>'应用管理员appAdmin',
        5=>'应用超级管理员appSuperAdmin',
        6=>'超级管理员Administrators',
        7=>'其他',
    ];
    /**
     * 初始化数据：表不存在时自动创建表然后自动插入$initData数据
     *      支持多条
     * @var array
     */
    protected $initData = [
        ['id'=>'0EQD12A2-8824-9943-E8C9-C83E40F360D1','name'=>'默认用户权限','apps_id'=>Model::UUID_ZERO,'remark'=>'系统初始化时设置的默认角色','status'=>'2'],
    ];
}