<?php
/*
 * 默认配置是我自己的
 * 我自己的ikuai有多出口
 * adslPPPoE 电信拨号
 * ovpnix_hk 香港出口
 * wan3 是美国出口
 * wan4 是阿里云/腾讯云出口 跨网用
 *
 * */
return [
    "proxy_domain"=>"", //对于国内github访问问题 添加加速前缀网址请带/，比如https://hk.gh-proxy.com/ 最新加速网址 请自行寻找
    "url"=>"https://192.168.137.2:18888", //结尾不要带/
    "username"=> "admin",
    "password"=> "",
    "ssl_key_path"=> "", //不在当前文件同级目录  写绝对路径 并且设置好读取权限
    "ssl_cert_path"=> "",
    "ip-group"=>[ //IP分组 和端口分流配合使用
        [
            "name"=>"中国", //为了实现先增加后删除，因爱快的分组增加时分组名称不能有同名的存在，所以在最后增加了4位数字作后缀，如“国内_12_1234”,分组名最大20个字条，减去占用的字符，这里的名称最多12个字符。
            "url"=>"https://raw.githubusercontent.com/Loyalsoldier/geoip/release/text/cn.txt" // cidr列表，每行一个，超过1000行会自动分为多个，ipv6 地址会被删除
        ],
        [
            "name"=>"Telegram",
            "url"=>"https://raw.githubusercontent.com/Loyalsoldier/geoip/release/text/telegram.txt"
        ],
        [
            "name"=>"cloudflare",
            "url"=>"https://www.cloudflare.com/ips-v4"
        ],
        [
            "name"=>"google",
            "url"=>"https://api.whzxc.cn/google"
        ],
        [
            "name"=>"github",
            "url"=>"https://api.whzxc.cn/github"
        ],
        [
            "name"=>"tiktok",
            "url"=>"https://api.whzxc.cn/tiktok"
        ],
        [
            "name"=>"akamai",
            "url"=>"https://api.whzxc.cn/akamai"
        ],
        [
            "name"=>"湖北",
            "url"=>"https://raw.githubusercontent.com/metowolf/iplist/refs/heads/master/data/cncity/420000.txt"
        ],
        [
            "name"=>"电信",
            "url"=>"https://raw.githubusercontent.com/metowolf/iplist/refs/heads/master/data/isp/chinatelecom.txt"
        ],
        [
            "name"=>"移动",
            "url"=>"https://raw.githubusercontent.com/metowolf/iplist/refs/heads/master/data/isp/chinamobile.txt"
        ],
        [
            "name"=>"联通",
            "url"=>"https://raw.githubusercontent.com/metowolf/iplist/refs/heads/master/data/isp/chinaunicom.txt"
        ],
        [
            "name"=>"鹏博士",
            "url"=>"https://raw.githubusercontent.com/metowolf/iplist/refs/heads/master/data/isp/drpeng.txt"
        ],
        [
            "name"=>"教育网",
            "url"=>"https://raw.githubusercontent.com/metowolf/iplist/refs/heads/master/data/isp/cernet.txt"
        ],
        [
            "name"=>"科技网",
            "url"=>"https://raw.githubusercontent.com/metowolf/iplist/refs/heads/master/data/isp/cstnet.txt"
        ],
        [
            "name"=>"阿里云",
            "url"=>"https://raw.githubusercontent.com/metowolf/iplist/refs/heads/master/data/isp/aliyun.txt"
        ],
        [
            "name"=>"腾讯云",
            "url"=>"https://raw.githubusercontent.com/metowolf/iplist/refs/heads/master/data/isp/tencent.txt"
        ],
        [
            "name"=>"华为云",
            "url"=>"https://raw.githubusercontent.com/metowolf/iplist/refs/heads/master/data/isp/huawei.txt"
        ],
        [
            "name"=>"自定义美国",
            "url"=>"https://raw.githubusercontent.com/chengran666/ikuaiphp/refs/heads/master/us_ip.txt"
        ],
        [
            "name"=>"自定义电信",
            "url"=>"https://raw.githubusercontent.com/chengran666/ikuaiphp/refs/heads/master/cn_ip.txt"
        ],
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
        [//默认全部走香港
            "enabled"=>"yes",
            "type"=> 0,             # 分流方式：0-外网线路，1-下一跳网关
            "interface"=>"ovpnix_hk",    # 分流线路 这里默认到wan1 运营商，如果type填1 这里写网关地址，为1的时候  可以填写多个出口 以英文逗号隔开 比如 wan1,wan2
            "ifaceband"=>0,         # 在type=0时 可改，线路绑定 (线路断开后禁止切换到其他线路) ，0-不勾选， 1-勾选
            "mode"=>0,              #在type=0时 可改， 负载模式，0-新建连接数， 1-源IP， 2-源IP+源端口，3-源IP+目的IP，4-源IP+目的IP+目的端口，6-主备模式
            "protocol"=>"any",      #协议 参数选择可以有： any tcp udp tcp+udp icmp
            "src_addr"=>"",         # 分流的源地址   为空就代表全部，可以填写ip 、cidr、分组名
            "src_port"=>"",         #在protocol 不是any或者icmp的时候可填 目的端口
            "dst_addr"=>"",         # 分流的目的地址  为空就代表全部，可以填写ip 、cidr、分组名
            "dst_port"=>"",         #在protocol 不是any或者icmp的时候可填 目的端口
            "week"=>"1234567",      #生效周期 星期几
            "time"=>"00:00-23:59"   #生效时间
        ],
        [//国内走电信
            "enabled"=>"yes",
            "type"=> 0,             # 分流方式：0-外网线路，1-下一跳网关
            "interface"=>"adslPPPoE",    # 分流线路 这里默认到wan1 运营商，如果type填1 这里写网关地址，为1的时候  可以填写多个出口 以英文逗号隔开 比如 wan1,wan2
            "ifaceband"=>0,         # 在type=0时 可改，线路绑定 (线路断开后禁止切换到其他线路) ，0-不勾选， 1-勾选
            "mode"=>0,              #在type=0时 可改， 负载模式，0-新建连接数， 1-源IP， 2-源IP+源端口，3-源IP+目的IP，4-源IP+目的IP+目的端口，6-主备模式
            "protocol"=>"any",      #协议 参数选择可以有： any tcp udp tcp+udp icmp
            "src_addr"=>"",         # 分流的源地址   为空就代表全部，可以填写ip 、cidr、分组名
            "src_port"=>"",         #在protocol 不是any或者icmp的时候可填 源端口
            "dst_addr"=>"中国",         # 分流的目的地址  为空就代表全部，可以填写ip 、cidr、分组名
            "dst_port"=>"",         #在protocol 不是any或者icmp的时候可填 目的端口
            "week"=>"1234567",      #生效周期 星期几
            "time"=>"00:00-23:59"   #生效时间
        ],
        [//联通移动跨网 走阿里云腾讯云
            "enabled"=>"yes",
            "type"=> 0,             # 分流方式：0-外网线路，1-下一跳网关
            "interface"=>"wan4",    # 分流线路 这里默认到wan1 运营商，如果type填1 这里写网关地址，为1的时候  可以填写多个出口 以英文逗号隔开 比如 wan1,wan2
            "ifaceband"=>0,         # 在type=0时 可改，线路绑定 (线路断开后禁止切换到其他线路) ，0-不勾选， 1-勾选
            "mode"=>0,              #在type=0时 可改， 负载模式，0-新建连接数， 1-源IP， 2-源IP+源端口，3-源IP+目的IP，4-源IP+目的IP+目的端口，6-主备模式
            "protocol"=>"any",      #协议 参数选择可以有： any tcp udp tcp+udp icmp
            "src_addr"=>"",         # 分流的源地址   为空就代表全部，可以填写ip 、cidr、分组名
            "src_port"=>"",         #在protocol 不是any或者icmp的时候可填 源端口
            "dst_addr"=>"鹏博士,联通,移动",         # 分流的目的地址  为空就代表全部，可以填写ip 、cidr、分组名
            "dst_port"=>"",         #在protocol 不是any或者icmp的时候可填 目的端口
            "week"=>"1234567",      #生效周期 星期几
            "time"=>"00:00-23:59"   #生效时间
        ],
        [//tiktok 走美国wan3
            "enabled"=>"yes",
            "type"=> 0,             # 分流方式：0-外网线路，1-下一跳网关
            "interface"=>"wan3",    # 分流线路 这里默认到wan1 运营商，如果type填1 这里写网关地址，为1的时候  可以填写多个出口 以英文逗号隔开 比如 wan1,wan2
            "ifaceband"=>0,         # 在type=0时 可改，线路绑定 (线路断开后禁止切换到其他线路) ，0-不勾选， 1-勾选
            "mode"=>0,              #在type=0时 可改， 负载模式，0-新建连接数， 1-源IP， 2-源IP+源端口，3-源IP+目的IP，4-源IP+目的IP+目的端口，6-主备模式
            "protocol"=>"any",      #协议 参数选择可以有： any tcp udp tcp+udp icmp
            "src_addr"=>"",         # 分流的源地址   为空就代表全部，可以填写ip 、cidr、分组名
            "src_port"=>"",         #在protocol 不是any或者icmp的时候可填 源端口
            "dst_addr"=>"akamai,tiktok",         # 分流的目的地址  为空就代表全部，可以填写ip 、cidr、分组名
            "dst_port"=>"",         #在protocol 不是any或者icmp的时候可填 目的端口
            "week"=>"1234567",      #生效周期 星期几
            "time"=>"00:00-23:59"   #生效时间
        ],
        [//自定义 走美国wan3
            "enabled"=>"yes",
            "type"=> 0,             # 分流方式：0-外网线路，1-下一跳网关
            "interface"=>"wan3",    # 分流线路 这里默认到wan1 运营商，如果type填1 这里写网关地址，为1的时候  可以填写多个出口 以英文逗号隔开 比如 wan1,wan2
            "ifaceband"=>0,         # 在type=0时 可改，线路绑定 (线路断开后禁止切换到其他线路) ，0-不勾选， 1-勾选
            "mode"=>0,              #在type=0时 可改， 负载模式，0-新建连接数， 1-源IP， 2-源IP+源端口，3-源IP+目的IP，4-源IP+目的IP+目的端口，6-主备模式
            "protocol"=>"any",      #协议 参数选择可以有： any tcp udp tcp+udp icmp
            "src_addr"=>"",         # 分流的源地址   为空就代表全部，可以填写ip 、cidr、分组名
            "src_port"=>"",         #在protocol 不是any或者icmp的时候可填 源端口
            "dst_addr"=>"自定义美国",         # 分流的目的地址  为空就代表全部，可以填写ip 、cidr、分组名
            "dst_port"=>"",         #在protocol 不是any或者icmp的时候可填 目的端口
            "week"=>"1234567",      #生效周期 星期几
            "time"=>"00:00-23:59"   #生效时间
        ],
        [//自定义走电信
            "enabled"=>"yes",
            "type"=> 0,             # 分流方式：0-外网线路，1-下一跳网关
            "interface"=>"wan3",    # 分流线路 这里默认到wan1 运营商，如果type填1 这里写网关地址，为1的时候  可以填写多个出口 以英文逗号隔开 比如 wan1,wan2
            "ifaceband"=>0,         # 在type=0时 可改，线路绑定 (线路断开后禁止切换到其他线路) ，0-不勾选， 1-勾选
            "mode"=>0,              #在type=0时 可改， 负载模式，0-新建连接数， 1-源IP， 2-源IP+源端口，3-源IP+目的IP，4-源IP+目的IP+目的端口，6-主备模式
            "protocol"=>"any",      #协议 参数选择可以有： any tcp udp tcp+udp icmp
            "src_addr"=>"",         # 分流的源地址   为空就代表全部，可以填写ip 、cidr、分组名
            "src_port"=>"",         #在protocol 不是any或者icmp的时候可填 源端口
            "dst_addr"=>"自定义电信",         # 分流的目的地址  为空就代表全部，可以填写ip 、cidr、分组名
            "dst_port"=>"",         #在protocol 不是any或者icmp的时候可填 目的端口
            "week"=>"1234567",      #生效周期 星期几
            "time"=>"00:00-23:59"   #生效时间
        ],
        [//dns保底
            "enabled"=>"yes",
            "type"=> 0,             # 分流方式：0-外网线路，1-下一跳网关
            "interface"=>"adslPPPoE",    # 分流线路 这里默认到wan1 运营商，如果type填1 这里写网关地址，为1的时候  可以填写多个出口 以英文逗号隔开 比如 wan1,wan2
            "ifaceband"=>0,         # 在type=0时 可改，线路绑定 (线路断开后禁止切换到其他线路) ，0-不勾选， 1-勾选
            "mode"=>0,              #在type=0时 可改， 负载模式，0-新建连接数， 1-源IP， 2-源IP+源端口，3-源IP+目的IP，4-源IP+目的IP+目的端口，6-主备模式
            "protocol"=>"any",      #协议 参数选择可以有： any tcp udp tcp+udp icmp
            "src_addr"=>"",         # 分流的源地址   为空就代表全部，可以填写ip 、cidr、分组名
            "src_port"=>"",         #在protocol 不是any或者icmp的时候可填 源端口
            "dst_addr"=>"119.29.29.29,223.5.5.5",         # 分流的目的地址  为空就代表全部，可以填写ip 、cidr、分组名
            "dst_port"=>"",         #在protocol 不是any或者icmp的时候可填 目的端口
            "week"=>"1234567",      #生效周期 星期几
            "time"=>"00:00-23:59"   #生效时间
        ],
        [//dns保底
            "enabled"=>"yes",
            "type"=> 0,             # 分流方式：0-外网线路，1-下一跳网关
            "interface"=>"ovpnix_hk",    # 分流线路 这里默认到wan1 运营商，如果type填1 这里写网关地址，为1的时候  可以填写多个出口 以英文逗号隔开 比如 wan1,wan2
            "ifaceband"=>0,         # 在type=0时 可改，线路绑定 (线路断开后禁止切换到其他线路) ，0-不勾选， 1-勾选
            "mode"=>0,              #在type=0时 可改， 负载模式，0-新建连接数， 1-源IP， 2-源IP+源端口，3-源IP+目的IP，4-源IP+目的IP+目的端口，6-主备模式
            "protocol"=>"any",      #协议 参数选择可以有： any tcp udp tcp+udp icmp
            "src_addr"=>"",         # 分流的源地址   为空就代表全部，可以填写ip 、cidr、分组名
            "src_port"=>"",         #在protocol 不是any或者icmp的时候可填 源端口
            "dst_addr"=>"8.8.8.8,8.8.4.4",         # 分流的目的地址  为空就代表全部，可以填写ip 、cidr、分组名
            "dst_port"=>"",         #在protocol 不是any或者icmp的时候可填 目的端口
            "week"=>"1234567",      #生效周期 星期几
            "time"=>"00:00-23:59"   #生效时间
        ],
        [//dns保底
            "enabled"=>"yes",
            "type"=> 0,             # 分流方式：0-外网线路，1-下一跳网关
            "interface"=>"wan3",    # 分流线路 这里默认到wan1 运营商，如果type填1 这里写网关地址，为1的时候  可以填写多个出口 以英文逗号隔开 比如 wan1,wan2
            "ifaceband"=>0,         # 在type=0时 可改，线路绑定 (线路断开后禁止切换到其他线路) ，0-不勾选， 1-勾选
            "mode"=>0,              #在type=0时 可改， 负载模式，0-新建连接数， 1-源IP， 2-源IP+源端口，3-源IP+目的IP，4-源IP+目的IP+目的端口，6-主备模式
            "protocol"=>"any",      #协议 参数选择可以有： any tcp udp tcp+udp icmp
            "src_addr"=>"",         # 分流的源地址   为空就代表全部，可以填写ip 、cidr、分组名
            "src_port"=>"",         #在protocol 不是any或者icmp的时候可填 源端口
            "dst_addr"=>"1.1.1.1,1.0.0.1",         # 分流的目的地址  为空就代表全部，可以填写ip 、cidr、分组名
            "dst_port"=>"",         #在protocol 不是any或者icmp的时候可填 目的端口
            "week"=>"1234567",      #生效周期 星期几
            "time"=>"00:00-23:59"   #生效时间
        ]
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
        [//gfw默认走香港
            "enabled"=>"yes",
            "domain"=>"https://raw.githubusercontent.com/Loyalsoldier/v2ray-rules-dat/release/gfw.txt", #网址 或者多个域名 用英文逗号隔开
            "interface"=>"ovpnix_hk",
            "src_addr"=>"",
            "time"=>"00:00-23:59",
            "week"=>"1234567"
        ],
        [//tiktok域名走美国
            "enabled"=>"yes",
            "domain"=>"https://api.whzxc.cn/ttdomain", #网址 或者多个域名 用英文逗号隔开
            "interface"=>"wan3",
            "src_addr"=>"",
            "time"=>"00:00-23:59",
            "week"=>"1234567"
        ],
        [//俄罗斯域名 走香港
            "enabled"=>"yes",
            "domain"=>"*.ru", #网址 或者多个域名 用英文逗号隔开
            "interface"=>"ovpnix_hk",
            "src_addr"=>"",
            "time"=>"00:00-23:59",
            "week"=>"1234567"
        ],
        [//自己定义的走电信出口
            "enabled"=>"yes",
            "domain"=>"https://raw.githubusercontent.com/chengran666/ikuaiphp/refs/heads/master/cn_domain.txt", #网址 或者多个域名 用英文逗号隔开
            "interface"=>"adslPPPoE",
            "src_addr"=>"",
            "time"=>"00:00-23:59",
            "week"=>"1234567"
        ],
        [//自己定义走美国
            "enabled"=>"yes",
            "domain"=>"https://raw.githubusercontent.com/chengran666/ikuaiphp/refs/heads/master/us_domain.txt", #网址 或者多个域名 用英文逗号隔开
            "interface"=>"wan3",
            "src_addr"=>"",
            "time"=>"00:00-23:59",
            "week"=>"1234567"
        ],
    ]
];
