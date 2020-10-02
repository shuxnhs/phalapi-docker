<?php
namespace App\Api;
use Base\Model\Base;
use PhalApi\Api;
use PhalApi\Model\DataModel;

/**
 * 默认接口服务类
 * @author: dogstar <chanzonghuang@gmail.com> 2014-10-04
 */
class Site extends Api {
    public function getRules() {
        return array(
            'index' => array(
                'username'  => array('name' => 'username', 'default' => 'PhalApi', 'desc' => '用户名'),
            ),
            'getEnv' => array()
        );
    }

    /**
     * 默认接口服务
     * @desc 默认接口服务，当未指定接口服务时执行此接口服务
     * @return string title 标题
     * @return string content 内容
     * @return string version 版本，格式：X.X.X
     * @return int time 当前时间戳
     * @exception 400 非法请求，参数传递错误
     */
    public function index() {
        return array(
            'title' => 'Hello ' . $this->username,
            'version' => PHALAPI_VERSION,
            'time' => $_SERVER['REQUEST_TIME'],
        );
    }

    /**
     * 获取环境变量
     * @return int[]
     */
    public function getEnv(){
        return $_ENV;
    }

    /**
     * 测试数据库链接
     */
    public function testDbStatus(){
        $di = \PhalApi\DI();
        $databaseName = $di->config->get('dbs.servers.db_master.name');
        $sql = "select table_schema, table_name, table_rows, data_length, index_length
            from information_schema.tables where table_schema='{$databaseName}'
            order by data_length desc, index_length desc;";
        return DataModel::model()->queryAll($sql, array());
    }
}
