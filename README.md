超轻量的Swoole Http Server

开始

    修改配置文件内的本地配置： Jb-Server/config/config.php

启动
    
    /pathto/php Jb-Server/main.php -s [start|stop|restart]
    
至此httpServer已经启动完成了，就是如此简单！

目录结构
~~~
Jb-Server
├─application           应用目录
│  ├─models             模型目录
│  └─http               http目录，http单独目录方便扩展websocket等
│     └──controller     
│           ├─Base.php        http控制器基类，处理请求及响应逻辑，是所有控制器的父类
│           ├─Index.php       默认控制器
│           └─ ...            更多控制器
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
├─README.md           
└─main.php             入口文件
~~~


### 喜欢的同学可以点个star，欢迎Issues交流。