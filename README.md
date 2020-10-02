# 使用Docker快速部署你的Phalapi项目

## 为什么要用docker

![Image text](http://cd7.yesapi.net/89E670FD80BA98E7F7D7E81688123F32_20200928215312_dee6612f9e5b862c37cd816c3222ce81.jpeg)

docker作为一种新兴的虚拟化技术，相较于传统的部署方式，使用docker去部署我们的项目，可以有更多的好处，如

### 更快的部署，启动时间
传统去部署我们的phalapi项目或phalapi-pro项目，需要安装nginx，php，fpm，mysql等等环境，php的拓展，nginx的配置，fpm的配置等等问题都可以
花费很多时间，即使自己亲手部署过很多次，隔一小段时间后重新去部署一个新环境也会出现其他问题，使用docker可以大大缩短我们的部署时间，启动时间，让
我们有更多的时间，精力去愉快的coding(摸鱼)！！！

### 一致的环境，消除不同环境的隐藏问题
在开发的过程中，常常会听到这么一句话：这个在我的电脑是好好的呀，怎么到你这就有问题！由于开发环境，测试环境，生产环境的不一致，导致有一些bug并未在
开发的过程中被发现。Docker镜像提供了除内核外完整的运行时环境，确保了应用运行环境一致性。

### 持续构建，部署，迁移
对开发和运维(DevOps)人员来说，最希望的就是一次创建或配置，可以在任意地方正常运行。Docker 确保了执行环境的一致性，使得应用的迁移更加容易。Docker
可以在很多平台上运行，无论是物理机、虚拟机、公有云、私有云，甚至是我们自己不同系统的电脑，其运行结果是一致的。 因此用户可以很轻易的将在一个平台上运行
的应用，迁移到另一个平台上，而不用担心运行 环境的变化导致应用无法正常运行的情况。

## 正式开始

接下来，废话不多说，正式开始！首先开始准备我们的环境，由于我们部署的需要nginx和php环境，这里使用docker-compose去编排我们的镜像

### 安装docker
```shell script
sudo yum install docker
# 建立 docker 组：
sudo groupadd docker
# 将当前用户加入docker组
sudo usermod -aG docker $USER
# 启动docker
sudo service docker start
sudo systemctl enable docker
```

### 安装docker-compose
```shell script
sudo curl -L https://github.com/docker/compose/releases/download/1.23.2/docker-compose-`uname -s`-`uname -m` > /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

### 准备我们的工作目录
```shell script
# 假设我们的工作目录是：/home/phalapi-docker
export $DOCKERPATH =/home/phalapi-docker
# 将项目clone，并移入/home/app目录下
git clone https://github.com/shuxnhs/phalapi-docker.git
```

## 运行我们的项目

1. 首先提供我们的docker-compose.yml,可以将本项目clone到/home目录
```yaml
version: '3'
services:
  nginx:
    image: nginx
    ports:
      - "8000:80"
    depends_on:
      - phalapi
    volumes:
      - "$DOCKERPATH/phalapi-docker/conf/nginx/conf.d:/etc/nginx/conf.d"
      - "$DOCKERPATH/phalapi-docker/conf/nginx/nginx.conf:/usr/local/nginx/conf/nginx.conf"
      - "$DOCKERPATH/phalapi-docker/html:/usr/share/nginx/html"
      - "$DOCKERPATH/phalapi-docker/log:/var/log/nginx"
    networks:
      - phalapi_net
    container_name: "phalapi_nginx"
  phalapi:
    image: shuxnhs/phalapi:latest
    ports: ["9000"]
    volumes:
      - "$DOCKERPATH/phalapi-docker/html/phalapi:/var/www/html/phalapi"
    networks:
      - phalapi_net
    container_name: "phalapi"
networks:
  phalapi_net:
```

2.介绍一下docker-compose.yml的命令作用
+ nginx中，将我们的配置，日志挂载数据卷，同时将我们的phalapi项目放到/app/nginx/html目录下挂载进容器
+ phalapi中是我们的php环境，这里最新的镜像使用的是php-7.1的环境，安装了常用的gd，pdo_mysql，mcrypt扩展，如果有需要php5的环境后续再更新的镜像

3.开始运动我们的项目
```shell script
docker-compose up -d
```

4. 打开我们的浏览器：127.0.0.1:8000即可以看到我们的项目首页：

![Image text](http://cd7.yesapi.net/89E670FD80BA98E7F7D7E81688123F32_20200928232349_2d6400e6eb7f384359b5c06bf7610f52.png)



完美，如果感觉有帮助欢迎来github给个小星星✨，后面再来讲讲结合容器中计划任务相关的东西。


# 使用Docker快速部署你的Phalapi项目进阶版
之前的文章讲述了如何将我们的项目运行起来，今天这一篇主要讲一下数据库相关的和使用计划任务该如何配置。

## 数据库配置
由于我们项目是由挂载进去容器，我们本地修改后也会随之更新进容器中不需重启，但是如果我们有需要新的挂载目录的话，我们就需要讲容器重启。
附命令：docker-compose down && docker-compose up -d

### 现成的数据库配置
1. 项目的数据库配置有很多种方式，最简便的方法就是将账号密码信息等写死在代码里面，但这种硬编码的形式并不是那么的好，更好的方式是采用配置方式。
2. 这里使用环境变量来配置我们的数据库，首先介绍下docker如何设置容器中的环境变量
```yaml
# 假设我们的phalapi需要的数据库配置为：
#    'db_master' => array(                       
#        'type'      => 'mysql',                 
#        'host'      => $_ENV['DB_HOST'],        //数据库域名
#        'name'      => 'phalapi',               //数据库名字
#        'user'      => $_ENV['DB_USER'],        //数据库用户名
#        'password'  => $_ENV['DB_PASS'],	    //数据库密码
#        'port'      => $_ENV['DB_PORT'],        //数据库端口
#    ),

# 在docker-compose中我们可以通过environment去设置我们的环境变量，对应在docker-compose.yml中则为：
phalapi:
    image: shuxnhs/phalapi:latest
    ports: ["9000"]
    environment:
      - DB_HOST=host
      - DB_USER=root
      - DB_PASS=123456
      - DB_PORT=3306
    volumes:
      - "$DOCKERPATH/phalapi-docker/html/phalapi:/var/www/html/phalapi"
      - "$DOCKERPATH/phalapi-docker/conf/php-fpm/www.conf:/usr/local/etc/php-fpm.d/www.conf"

    networks:
      - phalapi_net
    container_name: "phalapi"    
```
3. 通过设置环境变量可以打入我们需要的变量，但是这只是Cli模式下的环境变量，php-fpm不一定共用的Cli模式的配置，同时我们也需要将变量写进www.conf配置
文件下(可参考conf/php-fpm/www.conf)，配置完重启容器即可。

4. 重启完就随便写个接口测试下链接情况
```php
<?php
namespace App\Api;
use PhalApi\Api;
use PhalApi\Model\DataModel;

class Site extends Api {

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
```

5. 这时候调用一下接口就可以查看到连接到数据库，并查看数据库的情况。

![](http://cd7.yesapi.net/89E670FD80BA98E7F7D7E81688123F32_20201002175428_fb394fbea015d81c8dd21661fa4b2db9.png)

进入容器中，使用cli模式请求下接口：

![](http://cd7.yesapi.net/89E670FD80BA98E7F7D7E81688123F32_20201002212408_43db4b42a860bc6b99b29d0b00b3083f.png)


### docker-compose编排数据库
如果我们从零开始，项目所使用的数据库也都想用docker来搭建也是可以的，只需要在docker-compose.yml写上数据库的配置即可，只需要在docker-compose
的yaml文件中追加mysql的配置
```yaml
  mysql:
    image: mysql:5.7
    ports:
      - "3306:3306"
    environment:
      - MYSQL_ROOT_PASSWORD=123456
    volumes:
      - "$DOCKERPATH/phalapi-docker/mysql:/var/lib/mysql"
    networks:
      - phalapi_net
    container_name: "phalapi_mysql"
```
新增完运行起来mysql后通过命令 docker inspect phalapi_mysql 查看容器的IPAddress并配置进www.conf 和 docker-compose.yml的environment
然后重启下容器phalapi即可。

![](http://cd7.yesapi.net/89E670FD80BA98E7F7D7E81688123F32_20201002224836_e82054c076732bcc783f1a538e79f87e.png)


## 计划任务配置
在我们的项目中可能存在一些计划任务去帮助我们完成一些任务，我们常常使用crontab去执行一些计划任务，我们也可以通过在宿主机计划任务中添加任务去实现。
比如，我们在bin目录有一个计划任务：
```php
<?php
require_once dirname(__FILE__) . '/../public/init.php';
echo date("Y-m-d H:i:s").PHP_EOL;
```
这时候我们在我们的宿主机设置一个计划任务：
```shell script
*/1 * * * * docker exec phalapi php /var/www/html/phalapi/bin/testCrontab.php >> /var/log/testCrontab.log
```

![](http://cd7.yesapi.net/89E670FD80BA98E7F7D7E81688123F32_20201002222933_e64d8b4ecbd1fdc441abd0f2adbe4d02.png)

配置文件可以从github获取，如果感觉有帮助欢迎来github给个小星星✨。