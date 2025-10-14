<?php
return [
    "proxy_domain"=>"", //对于国内github访问问题 添加加速前缀网址请带/，比如https://hk.gh-proxy.com/ 最新加速网址 请自行寻找
    "url"=>"http://192.168.137.3", //结尾不要带/
    "username"=> "admin",
    "password"=> "",
    "ssl_key_path"=> "", //不在当前文件同级目录  写绝对路径 并且设置好读取权限
    "ssl_cert_path"=> "",
    "ip-group"=>[ //IP分组 和端口分流配合使用
//        [
//            "name"=>"国内", //为了实现先增加后删除，因爱快的分组增加时分组名称不能有同名的存在，所以在最后增加了4位数字作后缀，如“国内_12_1234”,分组名最大20个字条，减去占用的字符，这里的名称最多12个字符。
//            "url"=>"https://raw.githubusercontent.com/Loyalsoldier/geoip/release/text/cn.txt" // cidr列表，每行一个，超过1000行会自动分为多个，ipv6 地址会被删除
//        ],
//        [
//            "name"=>"Telegram",
//            "url"=>"https://raw.githubusercontent.com/Loyalsoldier/geoip/release/text/telegram.txt"
//        ],
    ],
    "ipv6-group"=>[ // ipv6
//        [
//            "name"=>"国内",//为了实现先增加后删除，因爱快的分组增加时分组名称不能有同名的存在，所以在最后增加了4位数字作后缀，如“国内_12_1234”,分组名最大20个字条，减去占用的字符，这里的名称最多12个字符。
//            "url"=>"https://raw.githubusercontent.com/Loyalsoldier/geoip/release/text/cn.txt"// cidr列表，每行一个，超过1000行会自动分为多个，ipv4 地址会被删除
//        ],
//        [
//            "name"=>"Telegram",
//            "url"=>"https://raw.githubusercontent.com/Loyalsoldier/geoip/release/text/telegram.txt"
//        ]
    ],
    "stream-ipport"=>[//端口分流 都是ikuai后台提交的参数 可以在浏览器f12 控制台查看
//        [
//            "enabled"=>"yes",
//            "type"=> 0,             # 分流方式：0-外网线路，1-下一跳网关
//            "interface"=>"wan1",    # 分流线路 这里默认到wan1 运营商，如果type填1 这里写网关地址，为1的时候  可以填写多个出口 以英文逗号隔开 比如 wan1,wan2
//            "ifaceband"=>0,         # 在type=0时 可改，线路绑定 (线路断开后禁止切换到其他线路) ，0-不勾选， 1-勾选
//            "mode"=>0,              #在type=0时 可改， 负载模式，0-新建连接数， 1-源IP， 2-源IP+源端口，3-源IP+目的IP，4-源IP+目的IP+目的端口，6-主备模式
//            "protocol"=>"any",      #协议 参数选择可以有： any tcp udp tcp+udp icmp
//            "src_addr"=>"",         # 分流的源地址   为空就代表全部，可以填写ip 、cidr、分组名
//            "src_port"=>"",         #在protocol 不是any或者icmp的时候可填 目的端口
//            "dst_addr"=>"国内,119.29.29.29,8.8.0.0/24",         # 分流的目的地址  为空就代表全部，可以填写ip 、cidr、分组名
//            "dst_port"=>"",         #在protocol 不是any或者icmp的时候可填 源端口
//            "week"=>"1234567",      #生效周期 星期几
//            "time"=>"00:00-23:59"   #生效时间
//        ],
//        [
//            "enabled"=>"yes",
//            "type"=> 0,             # 分流方式：0-外网线路，1-下一跳网关
//            "interface"=>"wan1",    # 分流线路 这里默认到wan1 运营商，如果type填1 这里写网关地址，为1的时候  可以填写多个出口 以英文逗号隔开 比如 wan1,wan2
//            "ifaceband"=>0,         # 在type=0时 可改，线路绑定 (线路断开后禁止切换到其他线路) ，0-不勾选， 1-勾选
//            "mode"=>0,              #在type=0时 可改， 负载模式，0-新建连接数， 1-源IP， 2-源IP+源端口，3-源IP+目的IP，4-源IP+目的IP+目的端口，6-主备模式
//            "protocol"=>"any",      #协议 参数选择可以有： any tcp udp tcp+udp icmp
//            "src_addr"=>"",         # 分流的源地址   为空就代表全部，可以填写ip 、cidr、分组名
//            "src_port"=>"",         #在protocol 不是any或者icmp的时候可填 目的端口
//            "dst_addr"=>"Telegram,1.1.1.0/24",         # 分流的目的地址  为空就代表全部，可以填写ip 、cidr、分组名
//            "dst_port"=>"",         #在protocol 不是any或者icmp的时候可填 源端口
//            "week"=>"1234567",      #生效周期 星期几
//            "time"=>"00:00-23:59"   #生效时间
//        ]
    ],
    "stream-domain"=>[
//        [
//            "enabled"=>"yes",
//            "domain"=>"a.com,b.com", #网址 或者多个域名 用英文逗号隔开
//            "interface"=>"wan1",
//            "src_addr"=>"",
//            "time"=>"00:00-23:59",
//            "week"=>"1234567"
//        ],
        [
            "enabled"=>"yes",
            "domain"=>"https://raw.githubusercontent.com/Loyalsoldier/v2ray-rules-dat/release/gfw.txt", #网址 或者多个域名 用英文逗号隔开
            "interface"=>"wan1",
            "src_addr"=>"",
            "time"=>"00:00-23:59",
            "week"=>"1234567"
        ]
    ]
];