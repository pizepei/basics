<?php
/**
 * 统一的appis请求日志（放在响应控制器中记录 使用微服务应用id做表名）
 */

namespace pizepei\basics\model\microservice;


use pizepei\model\db\Model;

class MicroserviceAppsReqLogModel extends Model
{
    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'appid'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>Model::UUID_ZERO, 'COMMENT'=>'微服务通信id',
        ],
        'api'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>Model::UUID_ZERO, 'COMMENT'=>'api',
        ],
        'module_prefix'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>Model::UUID_ZERO, 'COMMENT'=>'当前微服务模块prefix',
        ],
        'apps_config_id'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>Model::UUID_ZERO, 'COMMENT'=>'微服务应用下级配置id',
        ],
        'request_id'=>[
            'TYPE'=>"uuid", 'DEFAULT'=>Model::UUID_ZERO, 'COMMENT'=>'请求ID',
        ],
        'request'=>[
            'TYPE'=>"json", 'DEFAULT'=>false, 'COMMENT'=>'解密的请求数据',
        ],
        'response'=>[
            'TYPE'=>"json", 'DEFAULT'=>false, 'COMMENT'=>'控制器响应数据',
        ],
        'extend'=>[
            'TYPE'=>"json", 'DEFAULT'=>false, 'COMMENT'=>'扩展',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4')", 'DEFAULT'=>'1', 'COMMENT'=>'1失败 2成功 3等待处理',
        ],
        /**
         * UNIQUE 唯一
         * SPATIAL 空间
         * NORMAL 普通 key
         * FULLTEXT 文本
         */
        'INDEX'=>[
            ['TYPE'=>'INDEX','FIELD'=>'appid','NAME'=>'appid','USING'=>'BTREE','COMMENT'=>'微服务通信id'],
            ['TYPE'=>'INDEX','FIELD'=>'request_id','NAME'=>'request_id','USING'=>'BTREE','COMMENT'=>'请求id'],
        ],//索引 KEY `ip` (`ip`) COMMENT 'sss 'user_name
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '统一的appis请求日志';
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
}