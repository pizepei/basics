<?php
/**
 * 站点基础配置
 */
namespace pizepei\basics\model\console;


use pizepei\model\db\Model;

class SiteConfigModel extends Model
{
    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'config'=>[
            'TYPE'=>"json", 'DEFAULT'=>false, 'COMMENT'=>'详细配置',
        ],
        'extend'=>[
            'TYPE'=>"json", 'DEFAULT'=>false, 'COMMENT'=>'扩展',
        ],
        /**
         * UNIQUE 唯一
         * SPATIAL 空间
         * NORMAL 普通 key
         * FULLTEXT 文本
         */
        'INDEX'=>[
        ],//索引 KEY `ip` (`ip`) COMMENT 'sss 'user_name
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '站点基础配置';
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
        ['config'=>[
            'user'=>[
                /**
                 * 注册配置
                 */
                'register'=>[
                    'role'=>'0EQD12A2-8824-9943-E8C9-C83E40F360D1',//注册账号默认角色
                    'status'=>'2',//状态1等待审核、2审核通过3、禁止使用4、保留
                ],
                /**
                 * 登录配置
                 */
                'login'=>[
                    'logon_online_count'=>'3',//同时在线数
                    'password_wrong_count'=>'5',//密码错误数
                    'password_wrong_lock'=>'10',//密码错误超过限制的锁定时间：分钟
                    'logon_token_period_pattern'=>'3',//登录token模式1、谨慎（分钟为单位）2、常规（小时为单位）3、方便（天为单位）4、游客（单位分钟没有操作注销）
                    'logon_token_period_time'=>'2',//登录token有效期对应logon_token_period_pattern
                    'password_wrong_lock'=>'10',//密码错误超过限制的锁定时间：分钟
                    'password_wrong_lock'=>'10',//密码错误超过限制的锁定时间：分钟
                ],//默认注册账号登录限制
            ],
            ''=>'',
            ''=>'',
        ]],
    ];
}