超轻量的Swoole Http Server

开始

    修改配置文件内的本地配置： Jb-HttpServer/config/config.php

启动
    
    /pathto/php Jb-HttpServer/application/http/main.php -s [start|stop|restart]
    
至此httpServer已经启动完成了，就是如此简单！

目录结构
~~~
Jb-HttpServer
├─application           应用目录
│  ├─models             模型目录
│  └─http               http目录，http单独目录方便扩展websocket等
│     ├─controller     
│     │     ├─Base.php        http控制器基类，处理请求及响应逻辑，是所有控制器的父类
│     │     ├─Index.php       默认控制器
│     │     └─ ...            更多控制器
│     └─main.php       httpserver入口文件
│
├─config                
│  └─config.php         配置文件         
│
├─extend               扩展目录
│  ├─common.php        公共函数库
│  └─...            
│
├─library              项目核心目录
├─log                  日志目录
├─README.md             README 文件
~~~

> http目录下的子类继承Base后，Swoole->Request信息已赋值给超全局变量$_GET,$_POST等，业务控制器直接使用即可。