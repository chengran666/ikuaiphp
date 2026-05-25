<?php
/**
 * 缓存操作函数（文件存储于ikuaiphpcache目录）
 */
function cache($key, $value = null, $expire = 60) {
    static $cacheDir;
    if (!$cacheDir) {
        $cacheDir = dirname($_SERVER['SCRIPT_FILENAME']) . '/ikuaiphpcache/';
        if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
    }
    $filename = $cacheDir . 'cache_' . md5($key) . '.php';

    if ($value !== null) {
        $data = ['content' => $value, 'expire' => $expire > 0 ? time() + $expire : 0];
        return file_put_contents($filename, serialize($data)) !== false;
    }
    if (!file_exists($filename)) return null;
    $content = file_get_contents($filename);
    if ($content === false) return null;
    $data = unserialize($content);
    if ($data === false || ($data['expire'] > 0 && time() > $data['expire'])) {
        unlink($filename);
        return null;
    }
    return $data['content'];
}

function get_curl(string $url){
    global $proxy_domain;
    $url = processGithubUrl($url, $proxy_domain);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if(curl_errno($ch) || $httpCode >= 400) {
        msg("URL获取失败/异常 (Code: $httpCode): " . $url, 'error');
        $response = false;
    }
    curl_close($ch);
    return $response;
}

function post_curl(string $url, string $loginData, string $sessKey=""){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($loginData),
        "Cookie: sess_key=$sessKey",
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) return curl_error($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    curl_close($ch);
    return ['header' => $headers, 'body' => $body];
}

function isCidr($cidr) {
    $parts = explode('/', $cidr, 2);
    if (count($parts) !== 2) return false;
    list($ip, $prefix) = $parts;
    if (!filter_var($ip, FILTER_VALIDATE_IP)) return false;
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return is_numeric($prefix) && $prefix >= 0 && $prefix <= 32;
    } else {
        return is_numeric($prefix) && $prefix >= 0 && $prefix <= 128;
    }
}

function processGithubUrl($url, $prefix) {
    $url = trim($url);
    if (empty($url)) return $url;
    if (preg_match('/^https?:\/\/(.*\.)?(github\.com|githubusercontent\.com)/i', $url)) return $prefix . $url;
    return $url;
}

function txtToArray($txtContent) {
    if (is_object($txtContent) && method_exists($txtContent, 'getContent')) $txtContent = $txtContent->getContent();
    if (empty($txtContent) || !is_string($txtContent)) return [];
    if (0 === strpos($txtContent, chr(239) . chr(187) . chr(191))) $txtContent = substr($txtContent, 3);
    
    $txtContent = str_replace(["\r\n", "\r"], "\n", $txtContent);
    $lines = explode("\n", $txtContent);
    $result = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (stripos($trimmed, '<html') !== false || stripos($trimmed, '<!doctype') !== false) continue;
        if ($trimmed !== '') $result[] = $trimmed;
    }
    return array_values(array_unique($result));
}

function general(string $url, string $sessionKey, string $action, string $func_name, array $param){
    $list = post_curl($url, json_encode(['func_name' => $func_name, 'action' => $action, 'param' => $param]), $sessionKey);
    if(is_array($list)){
        $post_d = json_decode($list['body'], true);
        if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('解析响应失败');
        if($post_d['ErrMsg'] != "Success") throw new Exception("执行失败: ".$post_d['ErrMsg']);
        return $post_d;
    }else{
        throw new Exception($list);
    }
}

function login(string $url, string $username, string $passwordHash, string $pass){
    $result = post_curl($url, json_encode(['username' => $username, 'passwd' => $passwordHash, 'pass' => $pass, 'remember_password' => '']));
    if(is_array($result)){
        $login_d = json_decode($result['body'], true);
        if($login_d['ErrMsg'] != "Success") throw new Exception("登录失败:".$login_d['ErrMsg']);
        preg_match('/sess_key=([^;]+)/', $result['header'], $matches);
        if (!isset($matches[1])) throw new Exception('无法获取session key');
        return $matches[1];
    }else{
        throw new Exception($result);
    }
}

function issett($v){ return isset($v) && !empty($v); }

function msg(string $message, string $level = 'info', bool $withTimestamp = true) {
    $levelConfig = [
        'success' => "\033[0;32m", 'info' => "\033[0;36m",
        'warning' => "\033[1;33m", 'error' => "\033[0;31m",
    ];
    $level = strtolower($level);
    if (!isset($levelConfig[$level])) $level = 'info';
    $timestamp = $withTimestamp ? date('[Y-m-d H:i:s] ') : '';
    echo $timestamp . $levelConfig[$level] . ' ' . $message . "\033[0m\n";
}

// 内存地址映射解析核心
function mapAddresses($addr_string, $group_name_map) {
    if (empty($addr_string)) return "";
    $items = explode(',', $addr_string);
    $result = [];
    foreach ($items as $item) {
        $item = trim($item);
        if (empty($item)) continue;
        if (isset($group_name_map[$item])) {
            $result = array_merge($result, $group_name_map[$item]);
        } else {
            $result[] = $item;
        }
    }
    return implode(',', $result);
}

// ====================== 主逻辑开始 ======================

$del_key = "ikuaiphpauto";
$proxy_domain = "";

try {
    $config_file = __DIR__ . "/ikuaiphp_config.php";
    if (!is_file($config_file)) throw new Exception("没有找到配置文件: " . $config_file);
    $config = include($config_file);

    if(issett($config['url'])){
        $action_url = $config['url'] . "/Action/call";
        msg("--开始登陆");
        if(cache('session_key')){
            $session_key = cache('session_key');
        }else{
            $session_key = login($config['url'].'/Action/login', $config['username'], md5($config['password']), base64_encode("salt_11" . $config['password']));
            cache('session_key', $session_key);
        }
        msg('----登陆成功', 'success');
    }else{
        throw new Exception('没有设置登陆地址！');
    }
    
    $proxy_domain = $config['proxy_domain'] ?? '';

    // ================= [Phase 1] 极致预检 (网络请求) =================
    msg("================ 开始执行严格网络预取与数据校验 ================", 'warning');
    
    $prepared_ipv4 = [];
    if(issett($config['ip-group'])){
        foreach ($config['ip-group'] as $group){
            if(issett($group['url'])){
                $res = get_curl($group['url']);
                if ($res === false) throw new Exception("URL预取失败: " . $group['url']);
                $arr = txtToArray($res);
                if (count($arr) < 1) throw new Exception("URL数据异常为空: " . $group['url']);
                
                $ips = [];
                foreach ($arr as $ip){
                    if((isCidr($ip) && strpos($ip, ':') === false) || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) $ips[] = $ip;
                }
                if (empty($ips)) throw new Exception("未解析出有效的IPv4: " . $group['url']);
                $prepared_ipv4[] = ['group' => $group, 'ips' => $ips];
            }
        }
    }

    $prepared_ipv6 = [];
    if(issett($config['ipv6-group'])){
        foreach ($config['ipv6-group'] as $group){
            if(issett($group['url'])){
                $res = get_curl($group['url']);
                if ($res === false) throw new Exception("URL预取失败: " . $group['url']);
                $arr = txtToArray($res);
                $ips = [];
                foreach ($arr as $ip){ if(isCidr($ip) && strpos($ip, ':') !== false) $ips[] = $ip; }
                $prepared_ipv6[] = ['group' => $group, 'ips' => $ips];
            }
        }
    }

    $add_stream_domain_data = [];
    if(issett($config['stream-domain'])){
        foreach ($config['stream-domain'] as $group){
            $is_http = substr($group['domain'], 0, 4) == 'http';
            if(empty($group['domain'])) continue;
            $group['comment'] = $del_key;
            
            if($is_http){
                $res = get_curl($group['domain']);
                if ($res === false) throw new Exception("域名预取失败: " . $group['domain']);
                $arr = txtToArray($res);
                if (count($arr) < 1) throw new Exception("域名数据为空: " . $group['domain']);

                foreach (array_chunk($arr, 999) as $chunk){
                    $tmp = $group;
                    $tmp['domain'] = implode(',', $chunk);
                    $add_stream_domain_data[] = $tmp;
                }
            }else{
                $add_stream_domain_data[] = $group;
            }
        }
    }

    msg("所有数据预检通过！进入安全重建流程。", 'success');

    // ================= [Phase 2] 原地编辑 IP 分组 (保护手动规则引用) =================
    msg("-- 正在更新静态 IP 分组...");
    
    $group_name_map = []; 
    $used_v4_ids = [];    
    $used_v6_ids = [];

    // 读取现存 IP 分组用于判断是 edit 还是 add
    $existing_v4 = general($action_url, $session_key, 'show', 'ipgroup', ['limit'=>'0,1000']);
    $v4_map = [];
    if(issett($existing_v4['Data']['data'])){
        foreach($existing_v4['Data']['data'] as $v) {
            if(isset($v['comment']) && $v['comment'] === $del_key) $v4_map[$v['group_name']] = $v['id'];
        }
    }

    $existing_v6 = general($action_url, $session_key, 'show', 'ipv6group', ['limit'=>'0,1000']);
    $v6_map = [];
    if(issett($existing_v6['Data']['data'])){
        foreach($existing_v6['Data']['data'] as $v) {
            if(isset($v['comment']) && $v['comment'] === $del_key) $v6_map[$v['group_name']] = $v['id'];
        }
    }

    // 写入 IPv4
    foreach ($prepared_ipv4 as $item) {
        $original_name = trim($item['group']['name']);
        $safe_prefix = mb_substr($original_name, 0, 6); // 保证绝对安全长度
        $group_name_map[$original_name] = [];
        
        foreach (array_chunk($item['ips'], 999) as $kip => $ip){
            $w_gn = $safe_prefix . "_" . $kip;
            $group_name_map[$original_name][] = $w_gn;

            $param = ['group_name' => $w_gn, 'comment' => $del_key, 'addr_pool' => implode(',', $ip)];
            if (isset($v4_map[$w_gn])) {
                $param['id'] = $v4_map[$w_gn];
                general($action_url, $session_key, 'edit', 'ipgroup', $param);
                $used_v4_ids[] = $v4_map[$w_gn];
            } else {
                $param['type'] = 0; $param['newRow'] = true;
                general($action_url, $session_key, 'add', 'ipgroup', $param);
            }
        }
    }

    // 写入 IPv6
    foreach ($prepared_ipv6 as $item) {
        $original_name = trim($item['group']['name']);
        $safe_prefix = mb_substr($original_name, 0, 6);
        $group_name_map[$original_name] = [];
        
        foreach (array_chunk($item['ips'], 999) as $kip => $ip){
            $w_gn = $safe_prefix . "_" . $kip;
            $group_name_map[$original_name][] = $w_gn;

            $param = ['group_name' => $w_gn, 'comment' => $del_key, 'addr_pool' => implode(',', $ip)];
            if (isset($v6_map[$w_gn])) {
                $param['id'] = $v6_map[$w_gn];
                general($action_url, $session_key, 'edit', 'ipv6group', $param);
                $used_v6_ids[] = $v6_map[$w_gn];
            } else {
                $param['type'] = 0; $param['newRow'] = true;
                general($action_url, $session_key, 'add', 'ipv6group', $param);
            }
        }
    }


    // ================= [Phase 3] 保持优先级的内存重排与写入 =================
    
    // -- 处理端口分流 --
    if(issett($config['stream-ipport'])){
        msg('--正在重构端口分流 (绝对优先级保持模式)...');
        
        // 组装最新需要添加的自动规则
        $need_add = [];
        foreach ($config['stream-ipport'] as $group){
            if(!empty($group['src_addr'])) $group['src_addr'] = mapAddresses($group['src_addr'], $group_name_map);
            if(!empty($group['dst_addr'])) $group['dst_addr'] = mapAddresses($group['dst_addr'], $group_name_map);
            $group['comment'] = $del_key;
            if($group['protocol'] == 'any' || $group['protocol'] == 'icmp'){
                $group['src_port'] = ""; $group['dst_port'] = "";
            }
            $need_add[] = $group;
        }

        $old_stream = general($action_url, $session_key, 'show', 'stream_ipport', ['limit'=>'0,1000']);
        $original_rules = $old_stream['Data']['data'] ?? [];

        // 核心：在内存中重排，自动寻找旧自动规则的“坑位”，将新规则原封不动塞进去
        $final_list = [];
        $new_rules_inserted = false;
        foreach ($original_rules as $old_rule) {
            if (isset($old_rule['comment']) && $old_rule['comment'] === $del_key) {
                if (!$new_rules_inserted) {
                    $final_list = array_merge($final_list, $need_add);
                    $new_rules_inserted = true;
                }
            } else {
                unset($old_rule['id']); // 删除旧ID用于重新写入
                $final_list[] = $old_rule;
            }
        }
        // 如果路由器里本来一条自动规则都没有，那就追加到最后
        if (!$new_rules_inserted) $final_list = array_merge($final_list, $need_add);

        // 带回滚事务的重新写入
        if (!empty($original_rules) || !empty($final_list)) {
            try {
                foreach($original_rules as $old_v) general($action_url, $session_key, 'del', 'stream_ipport', ['id' => $old_v['id']]);
                foreach ($final_list as $item) general($action_url, $session_key, 'add', 'stream_ipport', $item);
            } catch (Exception $e) {
                msg("端口分流写入异常，正在触发紧急回滚...", 'error');
                $curr = general($action_url, $session_key, 'show', 'stream_ipport', ['limit'=>'0,1000']);
                foreach($curr['Data']['data'] ?? [] as $v) general($action_url, $session_key, 'del', 'stream_ipport', ['id' => $v['id']]);
                foreach($original_rules as $old_v) { unset($old_v['id']); @general($action_url, $session_key, 'add', 'stream_ipport', $old_v); }
                throw new Exception("端口分流已回滚，中止执行: " . $e->getMessage());
            }
        }
    }

    // -- 处理域名分流 --
    if(!empty($add_stream_domain_data)){
        msg('--正在重构域名分流 (绝对优先级保持模式)...');
        
        foreach ($add_stream_domain_data as &$d_group) {
            if(!empty($d_group['src_addr'])) $d_group['src_addr'] = mapAddresses($d_group['src_addr'], $group_name_map);
        }
        unset($d_group);

        $old_domain = general($action_url, $session_key, 'show', 'stream_domain', ['limit'=>'0,1000']);
        $original_domain_rules = $old_domain['Data']['data'] ?? [];

        $final_domain_list = [];
        $new_domain_inserted = false;
        foreach ($original_domain_rules as $old_rule) {
            if (isset($old_rule['comment']) && $old_rule['comment'] === $del_key) {
                if (!$new_domain_inserted) {
                    $final_domain_list = array_merge($final_domain_list, $add_stream_domain_data);
                    $new_domain_inserted = true;
                }
            } else {
                unset($old_rule['id']); 
                $final_domain_list[] = $old_rule;
            }
        }
        if (!$new_domain_inserted) $final_domain_list = array_merge($final_domain_list, $add_stream_domain_data);

        if (!empty($original_domain_rules) || !empty($final_domain_list)) {
            try {
                foreach($original_domain_rules as $old_v) general($action_url, $session_key, 'del', 'stream_domain', ['id' => $old_v['id']]);
                foreach ($final_domain_list as $item) general($action_url, $session_key, 'add', 'stream_domain', $item);
            } catch (Exception $e) {
                msg("域名分流写入异常，正在触发紧急回滚...", 'error');
                $curr = general($action_url, $session_key, 'show', 'stream_domain', ['limit'=>'0,1000']);
                foreach($curr['Data']['data'] ?? [] as $v) general($action_url, $session_key, 'del', 'stream_domain', ['id' => $v['id']]);
                foreach($original_domain_rules as $old_v) { unset($old_v['id']); @general($action_url, $session_key, 'add', 'stream_domain', $old_v); }
                throw new Exception("域名分流已回滚，中止执行: " . $e->getMessage());
            }
        }
    }

    // ================= [Phase 4] 尾部垃圾清理 =================
    // 清理那些因为源列表缩短（例如昨天是 _0 到 _3，今天只有 _0 到 _2）而多出来的废弃静态分组
    msg("-- 开始清理尾巴遗留 IP 组 --");
    
    foreach ($v4_map as $name => $id) {
        if (!in_array($id, $used_v4_ids)) general($action_url, $session_key, 'del', 'ipgroup', ['id' => $id]);
    }
    foreach ($v6_map as $name => $id) {
        if (!in_array($id, $used_v6_ids)) general($action_url, $session_key, 'del', 'ipv6group', ['id' => $id]);
    }

    msg("🎉 所有配置完美更新！排序优先级与原配置丝毫不差！", 'success');

} catch (Exception $e) {
    msg("阻断性异常: " . $e->getMessage(), 'error');
}
