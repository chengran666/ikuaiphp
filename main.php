<?php
/**
 * 缓存操作函数（文件存储于ikuaiphpcache目录）
 * @param string $key 缓存标识
 * @param mixed $value 可选，缓存值（为null时读取，非null时设置）
 * @param int $expire 过期时间（秒），0表示永久（仅设置时有效）
 * @return mixed 读取时返回内容（过期/不存在返回null），设置时返回bool
 */
function cache($key, $value = null, $expire = 60) {
    // 定义缓存目录（当前脚本目录下的ikuaiphpcache文件夹）
    static $cacheDir;
    if (!$cacheDir) {
        $cacheDir = dirname($_SERVER['SCRIPT_FILENAME']) . '/ikuaiphpcache/';
        // 检测并创建缓存目录（权限不足时会失败）
        if (!is_dir($cacheDir)) {
            // 递归创建目录，权限设置为0755（可根据需要调整）
            mkdir($cacheDir, 0755, true);
        }
    }

    // 生成缓存文件名（md5加密key，避免特殊字符）
    $filename = $cacheDir . 'cache_' . md5($key) . '.php';

    // 情况1：设置缓存（$value不为null时）
    if ($value !== null) {
        // 准备缓存数据（包含内容和过期时间）
        $data = [
            'content' => $value,
            'expire'  => $expire > 0 ? time() + $expire : 0
        ];
        // 序列化数据（支持数组和字符串）
        $content = serialize($data);
        // 写入文件（返回是否成功）
        return file_put_contents($filename, $content) !== false;
    }

    // 情况2：读取缓存（$value为null时）
    // 缓存文件不存在直接返回null
    if (!file_exists($filename)) {
        return null;
    }

    // 读取并反序列化数据
    $content = file_get_contents($filename);
    if ($content === false) {
        return null;
    }
    $data = unserialize($content);
    if ($data === false) {
        unlink($filename); // 数据损坏时删除文件
        return null;
    }

    // 检查是否过期
    if ($data['expire'] > 0 && time() > $data['expire']) {
        unlink($filename); // 过期自动删除
        return null;
    }

    return $data['content'];
}

/**
 * 删除指定缓存
 * @param string $key 缓存标识
 * @return bool
 */
function cache_delete($key) {
    $cacheDir = dirname($_SERVER['SCRIPT_FILENAME']) . '/ikuaiphpcache/';
    $filename = $cacheDir . 'cache_' . md5($key) . '.php';
    return file_exists($filename) ? unlink($filename) : true;
}

/**
 * 清空所有缓存
 * @return bool
 */
function cache_clear() {
    $cacheDir = dirname($_SERVER['SCRIPT_FILENAME']) . '/ikuaiphpcache/';
    // 仅当目录存在时执行清空
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . 'cache_*.php');
        if ($files) {
            foreach ($files as $file) {
                is_file($file) && unlink($file);
            }
        }
    }
    return true;
}
function get_curl(string $url){
    global $proxy_domain;
    $url=processGithubUrl($url,$proxy_domain);
    // 初始化CURL
    $ch = curl_init();

    // 设置CURL选项
    curl_setopt($ch, CURLOPT_URL, $url); // 设置请求URL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 将响应结果以字符串返回，而不是直接输出
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 不验证SSL证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 不验证SSL主机名

    // 执行请求并获取响应
    $response = curl_exec($ch);

    // 检查是否有错误发生
    if(curl_errno($ch)) {
        // 输出错误信息（实际应用中可根据需要处理）
        msg('CURL错误: ' . curl_error($ch),'error');
        $response = false;
    }

    // 关闭CURL资源
    curl_close($ch);

    return $response;
}
function post_curl(string $url,string $loginData,string $sessKey=""){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 注意：生产环境中应验证SSL证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HEADER, true); // 包含响应头信息
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);


    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($loginData),
        "Cookie: sess_key=$sessKey",
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return curl_error($ch);
    }
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    curl_close($ch);
    return [
        'header'=>$headers,
        'body'=>$body
    ];
}
/**
 * 验证CIDR格式是否有效（支持IPv4和IPv6）
 * @param string $cidr 待验证的CIDR（如192.168.1.0/24）
 * @return bool 有效返回true，无效返回false
 */
function isCidr($cidr) {
    // 分割IP和前缀长度（格式：IP/前缀）
    $parts = explode('/', $cidr, 2);
    if (count($parts) !== 2) {
        return false; // 缺少前缀部分
    }
    list($ip, $prefix) = $parts;

    // 验证IP部分是否有效
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }

    // 验证前缀长度是否合法
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        // IPv4前缀范围：0-32
        return is_numeric($prefix) && $prefix >= 0 && $prefix <= 32;
    } else {
        // IPv6前缀范围：0-128
        return is_numeric($prefix) && $prefix >= 0 && $prefix <= 128;
    }
}
/**
 * 从混合字符串中提取分组名
 * @param string $input 包含分组名、IP或CIDR的字符串，使用逗号分隔
 * @return array 分组名数组
 */
function getGroupNames($input) {
    // 分割输入字符串为数组
    $items = explode(',', $input);
    $groupNames = [];

    // 正则表达式：匹配IP地址或CIDR格式
    $ipCidrPattern = '/^(?P<ip>(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?))(?:\/(?P<cidr>[0-9]|[12][0-9]|3[0-2]))?$/';

    foreach ($items as $item) {
        // 去除前后空格
        $item = trim($item);

        // 跳过空项
        if (empty($item)) {
            continue;
        }

        // 检查是否为IP或CIDR格式，如果不是则视为分组名
        if (!preg_match($ipCidrPattern, $item)) {
            $groupNames[] = $item;
        }
    }

    return $groupNames;
}
/**
 * 从混合字符串中提取分组名
 * @param string $input 包含分组名、IP或CIDR的字符串，使用逗号分隔
 * @return array 分组名数组
 */
function getGroupNamesIPCIDR($input) {
    // 分割输入字符串为数组
    $items = explode(',', $input);
    $groupNames = [];

    // 正则表达式：匹配IP地址或CIDR格式
    $ipCidrPattern = '/^(?P<ip>(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?))(?:\/(?P<cidr>[0-9]|[12][0-9]|3[0-2]))?$/';

    foreach ($items as $item) {
        // 去除前后空格
        $item = trim($item);

        // 跳过空项
        if (empty($item)) {
            continue;
        }

        // 检查是否为IP或CIDR格式，如果不是则视为分组名
        if (preg_match($ipCidrPattern, $item)) {
            $groupNames[] = $item;
        }
    }

    return $groupNames;
}
/**
 * 根据关键词前缀提取数组项
 * @param array $data 原始数据数组
 * @param array $keywords 关键词数组
 * @return array 匹配的结果数组
 */
function extractByPrefix($data, $keywords) {
    $result = [];
    $matchedKeywords = []; // 记录已匹配到的关键词

    // 先收集所有匹配关键词前缀的group_name
    foreach ($data as $item) {
        if (!isset($item['group_name'])) {
            continue;
        }
        $groupName = $item['group_name'];
        foreach ($keywords as $keyword) {
            if (strpos($groupName, $keyword) === 0) {
                $result[] = $groupName;
                $matchedKeywords[$keyword] = true; // 标记该关键词已匹配
                break;
            }
        }
    }

    // 补充未匹配的关键词本身
    foreach ($keywords as $keyword) {
        if (!isset($matchedKeywords[$keyword])) {
            $result[] = $keyword;
        }
    }

    return $result;
}

/**
 * 处理URL：如果是GitHub相关网址（含githubusercontent）则添加前缀，否则返回原URL
 * @param string $url 需要判断的URL
 * @param string $prefix 需要添加的前缀
 * @return string 处理后的URL
 */
function processGithubUrl($url, $prefix) {
    // 去除URL前后空格
    $url = trim($url);
    if (empty($url)) {
        return $url;
    }

    // 正则表达式匹配GitHub相关域名：
    // 包括 github.com 及其子域名，以及 githubusercontent.com 及其子域名
    $githubPattern = '/^https?:\/\/(.*\.)?(github\.com|githubusercontent\.com)/i';

    // 检查URL是否匹配GitHub相关域名
    if (preg_match($githubPattern, $url)) {
        return $prefix . $url;
    } else {
        return $url;
    }
}
/**
 * 将TXT内容转换为数组（按行分割）
 * @param string $txtContent TXT文件内容
 * @return array 按行分割后的数组（过滤空行）
 */
function txtToArray($txtContent) {
    if(empty($txtContent)) {
        return [];
    }

    // 按换行符分割（兼容Windows和Linux换行格式）
    $lines = preg_split('/\r\n|\r|\n/', $txtContent);

    // 过滤空行和纯空格行
    $result = [];
    foreach($lines as $line) {
        $trimmed = trim($line);
        if(!empty($trimmed)) {
            $result[] = $trimmed;
        }
    }

    return $result;
}

//通用提交方法
function general(string $url,string $sessionKey,string $action,string $func_name,array $param){

    $Data = json_encode([
        'func_name' => $func_name,
        'action' => $action,
        'param' => $param
    ]);
    $list=post_curl($url,$Data,$sessionKey);
    if(is_array($list)){
        $post_d=json_decode($list['body'],true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('解析'.$url.':'.$func_name.":".$action.'响应失败: ' . json_last_error_msg());
        }
        if($post_d['ErrMsg']!="Success"){
            throw new Exception("执行".$url.':'.$func_name.":".$action."失败 相应错误:".$post_d['ErrMsg']);
        }
        return $post_d;
    }else{
        throw new Exception($list);
    }
}

/*
 * 登陆函数
 * 返回sessionkey
 * */
function login(string $url,string $username,string $passwordHash,string $pass){
    $loginData = json_encode([
        'username' => $username,
        'passwd' => $passwordHash,
        'pass' => $pass,
        'remember_password' => ''
    ]);
    $result=post_curl($url,$loginData);
    if(is_array($result)){
        $login_d=json_decode($result['body'],true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('解析登录响应失败: ' . json_last_error_msg());
        }
        if($login_d['ErrMsg']!="Success"){
            throw new Exception("登录失败:".$login_d['ErrMsg']);
        }

        preg_match('/sess_key=([^;]+)/', $result['header'], $matches);

        if (!isset($matches[1])) {
            throw new Exception('无法获取session key');
        }
        return $matches[1];
    }else{
        throw new Exception($result);
    }
}
/**
 * 命令行环境下的调试输出函数，类似Laravel的dd
 * 打印变量信息并终止脚本运行
 *
 * @param mixed ...$args 一个或多个需要调试的变量
 */
function dd(...$args)
{
    // 遍历所有传入的变量
    foreach ($args as $arg) {
        // 输出变量类型
        $type = gettype($arg);
        $varId = 'N/A';

        // 只有对象才获取哈希值
        if (is_object($arg)) {
            $varId = spl_object_hash($arg);
        }

        echo "\033[1;34m[" . $type . " (ID: " . $varId . ")]\033[0m\n";

        // 根据变量类型使用不同的输出方式
        if (is_bool($arg)) {
            echo $arg ? "\033[1;32mtrue\033[0m" : "\033[1;31mfalse\033[0m";
        } elseif (is_null($arg)) {
            echo "\033[1;35mnull\033[0m";
        } elseif (is_string($arg)) {
            // 处理包含引号的字符串
            echo "\033[1;33m'" . addslashes($arg) . "'\033[0m";
        } elseif (is_numeric($arg)) {
            echo "\033[1;36m" . $arg . "\033[0m";
        } else {
            // 复杂类型使用print_r并增加缩进
            echo "\n";
            print_r($arg);
        }

        echo "\n\n";
    }

    // 输出结束标记
    echo "\033[1;31m[Execution halted]\033[0m\n";

    // 终止脚本
    exit(0);
}

function issett($v){
    return isset($v)&&!empty($v);
}

function msg(string $message, string $level = 'info', bool $withTimestamp = true)
{
    // 定义日志等级对应的颜色代码和标识
    $levelConfig = [
        'success' => ['color' => "\033[0;32m", 'label' => 'Success'],   // 青色
        'info'  => ['color' => "\033[0;36m", 'label' => 'INFO'],    // 绿色
        'warning'  => ['color' => "\033[1;33m", 'label' => 'WARN'],    // 黄色
        'error' => ['color' => "\033[0;31m", 'label' => 'ERROR'],   // 红色
        'fatal' => ['color' => "\033[1;41m", 'label' => 'FATAL'],   // 红色背景
    ];

    // 处理未知等级
    $level = strtolower($level);
    if (!isset($levelConfig[$level])) {
        $level = 'info';
    }

    // 获取配置
    $color = $levelConfig[$level]['color'];
    $label = $levelConfig[$level]['label'];
    $reset = "\033[0m"; // 重置颜色

    // 构建时间戳
    $timestamp = $withTimestamp ? date('[Y-m-d H:i:s] ') : '';

    // 输出日志
    echo $timestamp . $color  . ' ' . $message .$reset. "\n";
}

function update_cert($url,$session_key,$key_path,$cert_path){
    $certContent = @file_get_contents($cert_path);
    $keyContent = @file_get_contents($key_path);
    if ($certContent === false || $keyContent === false) {
        return '读取证书或私钥文件失败';
    }

    // 替换换行符
    $certContent = str_replace(array("\r\n", "\n", "\r"), '@', $certContent);
    $certContent=str_replace(' ', '#', $certContent);

    $keyContent = str_replace(array("\r\n", "\n", "\r"), '@', $keyContent);
    $keyContent=str_replace(' ', '#', $keyContent);

    return general($url, $session_key, 'save', 'key_manager', [
            'id' => 1,
            'enabled' => 'yes',
            'comment' => date('Ymd'), // 当前年月日
            'ca' => $certContent,
            'key' => $keyContent
    ]);


}

function get_rename(string $name,array $all,int $count=1){
    if(in_array($name,$all)){
        $new_name=$name."_".$count;
        $count++;
        return get_rename($new_name,$all,$count);
    }else{
        return $name;
    }
}


$del_key="ikuaiphpauto";
$proxy_domain="";
try {
    $config_file= __DIR__ . "/ikuaiphp_config.php";
    if (!is_file($config_file)){
        msg("没有找到配置文件",'warning');
    }

    $config=include ($config_file);
    if(issett($config['url'])){

        $action_url=$config['url']."/Action/call";

        msg("--开始登陆");
        if(!issett($config['username'])&&!issett($config['password'])){
            throw new Exception('用户名密码未设置，请检查');
        }
        //正常运行后 可以不要这个缓存存储session  直接用下面这个替换掉 免得还要写文件
        //$session_key=login($config['url'].'/Action/login',$config['username'],md5($config['password']),base64_encode("salt_11" . $config['password']));
        //------------start
        if(cache('session_key')){
            $session_key=cache('session_key');
        }else{
            $session_key=login($config['url'].'/Action/login',$config['username'],md5($config['password']),base64_encode("salt_11" . $config['password']));
            cache('session_key',$session_key);
        }
        //------------------end

        msg('----登陆成功 凭证为：'.$session_key,'success');
    }else{
        throw new Exception('没有设置登陆地址！');
    }
    $proxy_domain=$config['proxy_domain'];

    //更新证书

    if(issett($config['ssl_key_path'])&&issett($config['ssl_cert_path'])){
        msg('--开始更新证书');
        if(!is_file($config['ssl_key_path'])||!is_file($config['ssl_cert_path'])){
            msg("----没有获取到SSL文件，跳过更新证书",'warning');
        }
        $update_cert_status=update_cert($action_url,$session_key,$config['ssl_key_path'],$config['ssl_cert_path']);
        if(!is_array($update_cert_status)){
            msg('----'.$update_cert_status.'，跳过更新证书','warning');
        }else{
            msg("----证书更新成功！",'success');
        }

    }else{
        msg('SSL证书路径为空，跳过');
    }

    //更新ipv4-group
    if(issett($config['ip-group'])){
        msg('--开始处理IPV4分组');
        //开始获取已有ip分组
        $old_ipgroup=general($action_url,$session_key,'show','ipgroup',[
            'ORDER'=>'',
            'ORDER_BY'=>'',
            'TYPE'=>'total,data',
            'limit'=>'0,100'
        ]);
        $tmp_ipv4_group_name=[];
        if(issett($old_ipgroup['Data']['data'])){
            foreach($old_ipgroup['Data']['data'] as $old_v){
                if($old_v['comment']==$del_key){
                    msg('----开始删除旧IPV4分组：'.$old_v['group_name'],'success');
                    general($action_url,$session_key,'del','ipgroup',['id'=>$old_v['id']]);
                }else{
                    $tmp_ipv4_group_name[]=$old_v['group_name'];
                }
            }
        }

        foreach ($config['ip-group'] as $group){
            if(issett($group['url'])){
                $url_content=get_curl($group['url']);
                $ips=[];
                if($url_content!==false){
                    $tmp_array=txtToArray($url_content);
                    foreach ($tmp_array as $ip){
                        if(isCidr($ip)&&strpos($ip, ':')===false){
                            $ips[]=$ip;
                        }
                    }
                }
                $tmp_ipv4=array_chunk($ips,999);
                foreach ($tmp_ipv4 as $kip=>$ip){
                    $w_gn=get_rename(mb_substr(trim($group['name']),0,14)."-".$kip,$tmp_ipv4_group_name);

                    $add_ips_param=[
                        'type'=>0,
                        'newRow'=>true,
                        'group_name'=>$w_gn,
                        'comment'=>$del_key,
                        'addr_pool'=>implode(',',$ip)
                    ];
                   general($action_url,$session_key,'add','ipgroup',$add_ips_param);
                   msg('----开始添加IPV4分组：'.$w_gn,'success');
                }
            }
        }
    }else{
        msg('IPv4分组为空，跳过');
    }

    //更新ipv6-group
    if(issett($config['ipv6-group'])){
        msg('--开始处理IPV6分组');
        //开始获取已有ip分组
        $old_ipgroup=general($action_url,$session_key,'show','ipv6group',[
            'ORDER'=>'',
            'ORDER_BY'=>'',
            'TYPE'=>'total,data',
            'limit'=>'0,100'
        ]);
        $tmp_ipv6_group_name=[];
        if(issett($old_ipgroup['Data']['data'])){
            foreach($old_ipgroup['Data']['data'] as $old_v){
                if($old_v['comment']==$del_key){
                    msg('----开始删除旧IPV6分组：'.$old_v['group_name'],'success');
                    general($action_url,$session_key,'del','ipv6group',['id'=>$old_v['id']]);
                }else{
                    $tmp_ipv6_group_name[]=$old_v['group_name'];
                }
            }
        }

        foreach ($config['ipv6-group'] as $group){
            if(issett($group['url'])){
                $url_content=get_curl($group['url']);
                $ips=[];
                if($url_content!==false){
                    $tmp_array=txtToArray($url_content);
                    foreach ($tmp_array as $ip){
                        if(isCidr($ip)&&strpos($ip, ':')!==false){
                            $ips[]=$ip;
                        }
                    }
                }
                $tmp_ipv6=array_chunk($ips,999);
                foreach ($tmp_ipv6 as $kip=>$ip){
                    $w_gn=get_rename(mb_substr(trim($group['name'])."v6",0,14)."-".$kip,$tmp_ipv6_group_name);

                    $add_ips_param=[
                        'type'=>0,
                        'newRow'=>true,
                        'group_name'=>$w_gn,
                        'comment'=>$del_key,
                        'addr_pool'=>implode(',',$ip)
                    ];
                    general($action_url,$session_key,'add','ipv6group',$add_ips_param);
                    msg('----开始添加IPV6分组：'.$w_gn,'success');
                }
            }
        }
    }else{
        msg('IPV6分组为空，跳过');
    }

    //开始更新端口分流
    if(issett($config['stream-ipport'])){
        msg('--开始处理端口分流');
        //开始获取已有ip分组
        $old_stream_ipport=general($action_url,$session_key,'show','stream_ipport',[
            'ORDER'=>'',
            'ORDER_BY'=>'',
            'TYPE'=>'total,data',
            'limit'=>'0,100'
        ]);

        if(issett($old_stream_ipport['Data']['data'])){
            foreach($old_stream_ipport['Data']['data'] as $old_k=>$old_v){
                    msg('----开始删除所有端口分流的第'.($old_k+1).'个','success');
                    general($action_url,$session_key,'del','stream_ipport',['id'=>$old_v['id']]);
            }
        }

        msg('----开始获取ipv4分组');
        $ipgroup=general($action_url,$session_key,'show','ipgroup',[
            'ORDER'=>'',
            'ORDER_BY'=>'',
            'TYPE'=>'total,data',
            'limit'=>'0,100'
        ]);
        $tmp_ipv4_group=[];
        if(issett($ipgroup['Data']['data'])){
            foreach($ipgroup['Data']['data'] as $old_v){
                if($old_v['comment']==$del_key){
                    $tmp_ipv4_group[]=$old_v;
                }
            }
        }
        $need_add=[];
        foreach ($config['stream-ipport'] as $group_k=>$group){
            if(!empty($group['src_addr'])){
                $group_name=getGroupNames($group['src_addr']);
                $prefix_group=extractByPrefix($tmp_ipv4_group,$group_name);
                $ipcidr=getGroupNamesIPCIDR($group['src_addr']);
                $total_array=array_merge($ipcidr,$prefix_group);
                $group['src_addr']=implode(',',$total_array);
            }
            if(!empty($group['dst_addr'])){
                $group_name=getGroupNames($group['dst_addr']);
                $prefix_group=extractByPrefix($tmp_ipv4_group,$group_name);
                $ipcidr=getGroupNamesIPCIDR($group['dst_addr']);
                $total_array=array_merge($ipcidr,$prefix_group);
                $group['dst_addr']=implode(',',$total_array);
            }
            $group['comment']=$del_key;
            if($group['protocol']=='any'||$group['protocol']=='icmp'){
                $group['src_port']="";
                $group['dst_port']="";
            }
            $need_add[]=$group;

        }
        $result = [];
        if(issett($old_stream_ipport['Data']['data'])){
            $newItems=$need_add;
            $original=$old_stream_ipport['Data']['data'];

            // 查找需要删除的子数组的开始和结束索引
            $startIndex = -1;
            $endIndex = -1;
            for ($i = 0; $i < count($original); $i++) {
                if ($original[$i]['comment'] === $del_key) {
                    if ($startIndex === -1) {
                        $startIndex = $i;
                    }
                    $endIndex = $i;
                } elseif ($startIndex !== -1) {
                    break;
                }
            }

            // 如果找到了需要删除的区域
            if ($startIndex !== -1 && $endIndex !== -1) {
                // 删除comment为"php"的子数组
                array_splice($original, $startIndex, $endIndex - $startIndex + 1);
                // 在相同的位置插入$newItems数组
                array_splice($original, $startIndex, 0, $newItems);
            }

            $result=$original;
        }else{
            $result = $need_add;
        }
        if(issett($result)){
            foreach ($result as $item_k=>$item){
                general($action_url,$session_key,'add','stream_ipport',$item);
                msg('----开始添加端口分流第'.($item_k+1).'组','success');
            }

        }
    }else{
        msg('端口分流为空，跳过');
    }


    //开始更新
    if(issett($config['stream-domain'])){
        msg('--开始处理域名分流');
        //开始获取已有ip分组
        $old_stream_domain=general($action_url,$session_key,'show','stream_domain',[
            'ORDER'=>'',
            'ORDER_BY'=>'',
            'TYPE'=>'total,data',
            'limit'=>'0,1000'
        ]);

        if(issett($old_stream_domain['Data']['data'])){
            foreach($old_stream_domain['Data']['data'] as $old_k=>$old_v){
                msg('----开始删除所有域名分流的第'.($old_k+1).'个','success');
                general($action_url,$session_key,'del','stream_domain',['id'=>$old_v['id']]);
            }
        }

        msg("开始添加域名分流列表");
        $add_stream_domain_data=[];
        foreach ($config['stream-domain'] as $group_k=>$group){
            $is_http=substr($group['domain'],0,4)=='http';
            if(empty($group['domain'])){
                continue;
            }
            $group['comment']=$del_key;
            //网址
            if($is_http){
                $domain_list=get_curl($group['domain']);
                if(!empty($domain_list)){
                    $arr_domain=txtToArray($domain_list);
                    $domain_arr=array_chunk($arr_domain,999);
                    foreach ($domain_arr as $item_k=>$item_v){
                        $tmp_add_stream_domain_data=$group;
                        $tmp_add_stream_domain_data['domain']=implode(',',$item_v);
                        $add_stream_domain_data[]=$tmp_add_stream_domain_data;
                    }
                }else{
                    msg("URL:".$group['domain']."下载为空，跳过",'warning');
                    continue;
                }

            }else{
                $add_stream_domain_data[]=$group;
            }

        }


        $result = [];
        if(issett($old_stream_domain['Data']['data'])){
            $newItems=$add_stream_domain_data;
            $original=$old_stream_domain['Data']['data'];

            // 查找需要删除的子数组的开始和结束索引
            $startIndex = -1;
            $endIndex = -1;
            for ($i = 0; $i < count($original); $i++) {
                if ($original[$i]['comment'] === $del_key) {
                    if ($startIndex === -1) {
                        $startIndex = $i;
                    }
                    $endIndex = $i;
                } elseif ($startIndex !== -1) {
                    break;
                }
            }

// 如果找到了需要删除的区域
            if ($startIndex !== -1 && $endIndex !== -1) {
                // 删除comment为"php"的子数组
                array_splice($original, $startIndex, $endIndex - $startIndex + 1);
                // 在相同的位置插入$newItems数组
                array_splice($original, $startIndex, 0, $newItems);
            }

            $result=$original;

        }else{
            $result = $add_stream_domain_data;
        }

        if(issett($result)){
            foreach ($result as $item_k=>$item){
                general($action_url,$session_key,'add','stream_domain',$item);
                msg('----开始添加域名分流第'.($item_k+1).'组','success');
            }

        }
    }else{
        msg('域名分流为空，跳过');
    }






}catch (Exception $e) {
    msg($e->getMessage(),'error');
}

