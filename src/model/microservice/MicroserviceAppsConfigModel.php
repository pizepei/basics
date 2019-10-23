<?php
/**
 * Class MicroserviceConfigModel
 * 微服务配置表
 */
namespace pizepei\basics\model\microservice;

use pizepei\model\db\Model;

/**
 * Class MicroserviceConfigModel
 * @package pizepei\basics\model\microservice
 */
class MicroserviceAppsConfigModel extends Model
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
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'服务名称',
        ],
        'appid'=>[
            'TYPE'=>"uuid", 'DEFAULT'=>Model::UUID_ZERO, 'COMMENT'=>'应用ID',
        ],
        'type'=>[
            'TYPE'=>"uuid", 'DEFAULT'=>Model::UUID_ZERO, 'COMMENT'=>'应用类型表ID',
        ],
        'icon'=>[
            'TYPE'=>"varchar(200)", 'DEFAULT'=>'', 'COMMENT'=>'应用图标',
        ],
        'remark'=>[
            'TYPE'=>"varchar(600)", 'DEFAULT'=>'', 'COMMENT'=>'应用备注',
        ],
        'project_id'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'该应用的项目标识集合',
        ],
        'jurisdiction'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'该应用的权限集合',
        ],
        'ip_white_list '=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'ip白名单',
        ],


        'appsecret'=>[
            'TYPE'=>'varchar(32)', 'DEFAULT'=>'', 'COMMENT'=>'appsecret',
        ],
        'EncodingAESKey'=>[
            'TYPE'=>'varchar(43)', 'DEFAULT'=>'', 'COMMENT'=>'消息加密密钥由43位字符组成，可随机修改，字符范围为A-Z，a-z，0-9',
        ],
        'token'=>[
            'TYPE'=>'varchar(43)', 'DEFAULT'=>'', 'COMMENT'=>'消息校验Token',
        ],

        'extend'=>[
            'TYPE'=>"json", 'DEFAULT'=>false, 'COMMENT'=>'扩展',
        ],
        'sort'=>[
            'TYPE'=>"int(10)", 'DEFAULT'=>0, 'COMMENT'=>'排序',
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
            ['TYPE'=>'UNIQUE','FIELD'=>'appid','NAME'=>'appid','USING'=>'BTREE','COMMENT'=>'应用ID'],


        ],//索引 KEY `ip` (`ip`) COMMENT 'sss 'user_name
        'PRIMARY'=>'id',//主键

    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '微服务配置表';
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
     * 初始化数据：表不存在时自动创建表然后自动插入$initData数据
     *      支持多条
     * @var array
     */
    protected $initData = [
    ];

    # 通过项目标识获取？
    # 通过获取


}