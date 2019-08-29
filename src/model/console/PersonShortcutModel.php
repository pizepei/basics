<?php
/**
 * 个人控制台快捷方式
 */
namespace pizepei\basics\model\console;


use pizepei\model\db\Model;

class PersonShortcutModel extends Model
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
            'TYPE'=>'varchar(100)', 'DEFAULT'=>'', 'COMMENT'=>'菜单名称（与视图的文件夹名称和路由路径对应）',
        ],
        'Account_id'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>Model::UUID_ZERO, 'COMMENT'=>'账号表id',
        ],
        'type_id'=>[
            'TYPE'=>"uuid", 'DEFAULT'=>Model::UUID_ZERO, 'COMMENT'=>'类型变id',
        ],
        'explain'=>[
            'TYPE'=>'varchar(510)', 'DEFAULT'=>'', 'COMMENT'=>'描述说明',
        ],
        'url'=>[
            'TYPE'=>"varchar(250)", 'DEFAULT'=>'oauth.heil.top', 'COMMENT'=>'地址',
        ],
        'extend'=>[
            'TYPE'=>"json", 'DEFAULT'=>false, 'COMMENT'=>'扩展',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4','5')", 'DEFAULT'=>'1', 'COMMENT'=>'状态1等待审核、2正常3、禁用4、保留',
        ],
        /**
         * UNIQUE 唯一
         * SPATIAL 空间
         * NORMAL 普通 key
         * FULLTEXT 文本
         */
        'INDEX'=>[
            ['TYPE'=>'UNIQUE','FIELD'=>'name,Account_id','NAME'=>'name,Account_id','USING'=>'BTREE','COMMENT'=>'一个人下是唯一的'],
            ['TYPE'=>'INDEX','FIELD'=>'type_id','NAME'=>'type_id','USING'=>'BTREE','COMMENT'=>'类型ID'],
        ],//索引 KEY `ip` (`ip`) COMMENT 'sss 'user_name
        'PRIMARY'=>'id',//主键

    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '个人控制台快捷方式';
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