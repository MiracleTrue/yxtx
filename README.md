
******************************项目自述与更新******************************
项目安装:
必须Linux系统  PHP版本7.0以上 (拥有Laravel框架的插件支持)  安装:PHP Composer , MySql数据库 , Git客户端 , crond服务

1.git clone ssh://git@47.92.90.154:22/git/yxtx.git

2.composer install

3.修改.env全局配置文件,生成APP_KEY  (php artisan key:generate)

4.还原数据库,项目文档目录下的sql_data.sql

5.将Http访问目录设置为,项目根目录下的 /public/index.php

6.crontab -e 添加
  * * * * * php /var/www/jmrh/artisan schedule:run >> /dev/null 2>&1    保存
  crontab -u root -l   查看


开发环境额外配置:

1.IDE-高亮提示助手
  php artisan ide-helper:generate

备注:
Linux问题:
1.将项目目录所有人和所有组设置为apache 或者 storage 目录和 bootstrap/cache 目录应该允许 Web 服务器写入 (权限问题)
2.关闭selinux
3.防火墙问题

项目目录结构:
｜–　app 包含Controller、Model、路由等在内的应用目录，大部分业务将在该目录下进行
｜　　｜–　Console 命令行程序目录
｜　　｜　　｜–　Commands 包含了用于命令行执行的类，可在该目录下自定义类
｜　　｜　　｜–　Kernel.php 命令调用内核文件，包含commands变量(命令清单，自定义的命令需加入到这里)和schedule方法(用于任务调度，即定时任务)
｜　　｜–　Events 事件目录
｜　　｜–　Exceptions 包含了自定义错误和异常处理类
｜　　｜–　Http HTTP传输层相关的类目录
｜　　｜　　｜–　Controllers 控制器目录
｜　　｜　　｜–　Middleware 中间件目录
｜　　｜　　｜–　Requests 请求类目录
｜　　｜　　｜–　Kernel.php 包含http中间件和路由中间件的内核文件
｜　　｜　　｜–　routes.php 强大的路由
｜　　｜–　Jobs 该目录下包含队列的任务类
｜　　｜–　Listeners 监听器目录
｜　　｜–　Providers 服务提供者目录
｜　　｜–　User.php 自带的模型实例，我们新建的Model默认也存储在该目录
｜–　bootstrap 框架启动载入目录
｜　　｜–　app.php 创建框架应用实例
｜　　｜–　autoload.php 自动加载
｜　　｜–　cache 存放框架启动缓存，web服务器需要有该目录的写入权限
｜–　config 各种配置文件的目录
｜　　｜–　app.php 系统级配置文件
｜　　｜–　auth.php 用户身份认证配置文件，指定好table和model就可以很方便地用身份认证功能了
｜　　｜–　broadcasting.php 事件广播配置文件
｜　　｜–　cache.php 缓存配置文件
｜　　｜–　compile.php 编译额外文件和类需要的配置文件，一般用户很少用到
｜　　｜–　database.php 数据库配置文件
｜　　｜–　filesystems.php 文件系统配置文件，这里可以配置云存储参数
｜　　｜–　mail.php 电子邮件配置文件
｜　　｜–　queue.php 消息队列配置文件
｜　　｜–　services.php 可存放第三方服务的配置信息
｜　　｜–　session.php 配置session的存储方式、生命周期等信息
｜　　｜–　view.php 模板文件配置文件，包含模板目录和编译目录等
｜–　database 数据库相关目录
｜　　｜–　factories 5.1以上版本的新特性，工厂类目录，也是用于数据填充
｜　　｜　　｜–　ModelFactory.php 在该文件可定义不同Model所需填充的数据类型
｜　　｜–　migrations 存储数据库迁移文件
｜　　｜–　seeds 存放数据填充类的目录
｜　　　　　｜–　DatabaseSeeder.php 执行php artisan db:seed命令将会调用该类的run方法。该方法可调用执行该目录下其他Seeder类，也可调用factories方法生成ModelFactory里定义的数据模型
｜–　public 网站入口，应当将ip或域名指向该目录而不是根目录。可供外部访问的css、js和图片等资源皆放置于此
｜　　｜–　index.php 入口文件
｜　　｜–　.htaccess Apache服务器用该文件重写URL
｜　　｜–　web.config IIS服务器用该文件重写URL
｜–　resources 资源文件目录
｜　　｜–　assets 可存放包含LESS、SASS、CoffeeScript在内的原始资源文件
｜　　｜–　lang 本地化文件目录
｜　　｜–　views 视图文件就放在这啦
｜–　storage 存储目录。web服务器需要有该目录及所有子目录的写入权限
｜　　｜–　app 可用于存储应用程序所需的一些文件
｜　　｜–　framework 该目录下包括缓存、sessions和编译后的视图文件
｜　　｜–　logs 日志目录
｜–　tests 测试目录
｜–　vendor 该目录下包含Laravel源代码和第三方依赖包
｜–　.env 环境配置文件。config目录下的配置文件会使用该文件里面的参数，不同生产环境使用不同的.env文件即可。
｜–　artisan 强大的命令行接口，你可以在app/Console/Commands下编写自定义命令
｜–　composer.json 存放依赖关系的文件
｜–　composer.lock 锁文件，存放安装时依赖包的真实版本
｜–　gulpfile.js gulp（一种前端构建工具）配置文件
｜–　package.json gulp配置文件
｜–　phpspec.yml phpspec（一种PHP测试框架）配置文件
｜–　phpunit.xml phpunit（一种PHP测试框架）配置文件
｜–　server.php PHP内置的Web服务器将把这个文件作为入口。以public/index.php为入口的可以忽略掉该文件


.env文件详解
APP_NAME=                                     //项目名称
APP_ENV=                                      //开发: local    测试: testing    预上线: staging    正式环境: production
APP_KEY=                                      //php artisan key:generate 生成
APP_DEBUG=                                    //开启Debug:true   关闭Debug:false 生产环境必须关闭
APP_LOG_LEVEL=                                //日志记录的等级默认记录全部 debug 生成环境应该为:error
APP_URL=                                      //项目的Url地址  http://www.xxx.com
DEBUGBAR_DEBUG=                               //插件: 开启Debug显示框:true   关闭Debug显示框:false
CONFIG_ADMIN_SECURE=                          //插件: Laravel-admin  是否启用Https 启用:true 不启用false

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=homestead
DB_USERNAME=homestead
DB_PASSWORD=secret

BROADCAST_DRIVER=log
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_DRIVER=sync

WECHAT_MINI_PROGRAM_APPID=                     //微信小程序app_id
WECHAT_MINI_PROGRAM_SECRET=                    //微信小程序secret
WECHAT_MINI_PROGRAM_TOKEN=                     //微信小程序token
WECHAT_MINI_PROGRAM_AES_KEY=                   //微信小程序aes_key

WECHAT_PAYMENT_APPID=                          //微信支付应用的 app_id
WECHAT_PAYMENT_MCH_ID=                         //微信支付的商户号
WECHAT_PAYMENT_KEY=                            //微信支付设置的key
WECHAT_PAYMENT_CERT_PATH=                      //微信支付证书 cert.pem !绝对路径
WECHAT_PAYMENT_KEY_PATH=                       //微信支付证书 key.pem  !绝对路径

ALI_ACCESS_KEY_ID=yourAccessKeyId              //阿里云短信 应用id
ALI_ACCESS_KEY_SECRET=yourAccessKeySecret      //阿里云短信 应用Secret

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
