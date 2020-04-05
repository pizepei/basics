<?php
/**
 * 登录日志
 */

namespace pizepei\basics\model\account;

use pizepei\model\db\Model;

class AccountLoginLogModel extends Model
{
    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'account_id'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>Model::UUID_ZERO, 'COMMENT'=>'登录账号',
        ],
        'role_id'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>Model::UUID_ZERO, 'COMMENT'=>'角色id',
        ],
        'logon_online_count'=>[
            'TYPE'=>"ENUM('3','5','6','8','10','15')", 'DEFAULT'=>5, 'COMMENT'=>'同时在线数',
        ],
        'logon_token_period_pattern'=>[
            'TYPE'=>"ENUM('1','2','3','4','5','6')", 'DEFAULT'=>1, 'COMMENT'=>'登录token模式1、谨慎（分钟为单位）2、常规（小时为单位）3、方便（天为单位）4、游客（单位分钟没有操作注销）',
        ],
        'logon_token_period_time'=>[
            'TYPE'=>"int(10)", 'DEFAULT'=>10, 'COMMENT'=>'登录token有效期',
        ],
        'terminal'=>[
            'TYPE'=>"json", 'DEFAULT'=>false, 'COMMENT'=>'终端信息',
        ],
        'IPv6'=>[
            'TYPE'=>'varchar(40)', 'DEFAULT'=>'', 'COMMENT'=>'终端访问的IPV6地址',
        ],
        'IPv4'=>[
            'TYPE'=>'varchar(15)', 'DEFAULT'=>'', 'COMMENT'=>'终端访问的IPV4地址',
        ],
        'Ipanel'=>[
            'TYPE'=>"json", 'DEFAULT'=>false, 'COMMENT'=>'浏览器信息',
        ],
        'Build'=>[
            'TYPE'=>"json", 'DEFAULT'=>false, 'COMMENT'=>'终端信息',
        ],
        'BuildName'=>[
            'TYPE'=>"varchar(50)", 'DEFAULT'=>'', 'COMMENT'=>'终端设备名称',
        ],
        'OS'=>[
            'TYPE'=>"varchar(40)", 'DEFAULT'=>'', 'COMMENT'=>'系统',
        ],
        'NetworkType'=>[
            'TYPE'=>"varchar(30)", 'DEFAULT'=>'', 'COMMENT'=>'网络类型',
        ],
        'province'=>[
            'TYPE'=>"varchar(32)", 'DEFAULT'=>'', 'COMMENT'=>'省',
        ],
        'city'=>[
            'TYPE'=>"varchar(40)", 'DEFAULT'=>'', 'COMMENT'=>'市',
        ],
        'isp'=>[
            'TYPE'=>"varchar(40)", 'DEFAULT'=>'', 'COMMENT'=>'运营商',
        ],
        'human'=>[
            'TYPE'=>"varchar(10)", 'DEFAULT'=>'', 'COMMENT'=>'human',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2')", 'DEFAULT'=>'2', 'COMMENT'=>'状态1成功、2失败',
        ],
        /**
         * UNIQUE 唯一
         * SPATIAL 空间
         * NORMAL 普通 key
         * FULLTEXT 文本
         */
        'INDEX'=>[
            ['TYPE'=>'KEY','FIELD'=>'account_id','NAME'=>'account_id','USING'=>'BTREE','COMMENT'=>'account_id'],
        ],//索引 KEY `ip` (`ip`) COMMENT 'sss 'user_name
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '登录日志';
    /**
     * @var int 表版本（用来记录表结构版本）在表备注后面@$table_version
     */
    protected $table_version = 0;
    /**
     * @var array 表结构变更日志 版本号=>['表结构修改内容sql','表结构修改内容sql']
     */
    protected $table_structure_log = [

    ];

}