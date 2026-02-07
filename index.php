<?php
// ha_window_status.php
// 单文件 PHP 网站：轮询 Home Assistant 实体并显示“使用者状态（大标题）”和“前台窗口名称（小标题）”
// 配置项（修改这里即可）
$CONFIG = [
    // Home Assistant 实体 URL（注意保留完整路径）
    'api_url' => 'Home Assistant 实体 URL（注意保留完整路径）http(s)://your-ha-url/api/states/sensor.frontend_window_title',
    // 在这里填入你的 long-lived access token（保持此位置以便保密，请不要放在前端）
    'api_key' => 'YOUR long-lived access token',
    // 页面轮询间隔（秒）——前端会使用此值进行请求
    'refresh_seconds' => 8,
    // 多久没有变化则判定为“挂了”（秒）
    'dead_seconds' => 20 * 60,
];

if (isset($_GET['action']) && $_GET['action'] === 'fetch') {
    header('Content-Type: application/json');

    $ch = curl_init($CONFIG['api_url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $CONFIG['api_key'],
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);

    $resp = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false) {
        echo json_encode(['ok' => false, 'error' => $err, 'code' => $errno]);
        exit;
    }

    $data = json_decode($resp, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $state = isset($data['state']) ? $data['state'] : null;
        $last_changed = isset($data['last_changed']) ? $data['last_changed'] : (isset($data['last_updated']) ? $data['last_updated'] : null);
        echo json_encode(['ok' => true, 'state' => $state, 'last_changed' => $last_changed, 'raw' => $data]);
    } else {
        // 返回原始文本（非JSON或解析失败）
        echo json_encode(['ok' => true, 'state' => null, 'raw_text' => $resp]);
    }
    exit;
}

// 前端页面部分（布局：使用者状态为主体，窗口名在状态下方）
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>ChickenTracier还活着吗</title>
    <style>
        /* 视觉：毛玻璃卡片 + 大号状态 */
        :root{
            --card-bg: rgba(255,255,255,0.06);
            --card-border: rgba(255,255,255,0.14);
            --accent: rgba(255,255,255,0.92);
        }
        html,body{height:100%;margin:0;font-family:Inter, "Segoe UI", Roboto, "Helvetica Neue", Arial;}
        body{
            display:flex;align-items:center;justify-content:center;
            background: linear-gradient(120deg,#071026 0%, #081824 60%, #041018 100%);
            color:var(--accent);
        }
        .bg-visual{
            position:fixed;inset:0;filter:blur(40px) saturate(120%);opacity:0.55;
            background-image: radial-gradient(circle at 10% 25%, rgba(255,99,132,0.06) 0 14%, transparent 25%),
                              radial-gradient(circle at 85% 75%, rgba(0,176,255,0.05) 0 22%, transparent 35%);
            pointer-events:none;
        }
        .card{
            width:min(760px,92%);
            background:var(--card-bg);
            border:1px solid var(--card-border);
            border-radius:20px;
            padding:36px 28px;
            box-shadow: 0 18px 60px rgba(2,6,23,0.7);
            backdrop-filter: blur(14px) saturate(120%);
            -webkit-backdrop-filter: blur(14px) saturate(120%);
            display:flex;flex-direction:column;align-items:center;gap:18px;
        }

        /* 主体：大号状态圈 */
        .status-wrap{display:flex;flex-direction:column;align-items:center;gap:14px}
        .status-main{
            width:260px;height:260px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;
            font-size:48px;letter-spacing:1px;background:linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01));
            border:1px solid rgba(255,255,255,0.04);box-shadow: 0 8px 30px rgba(2,6,23,0.6);position:relative;overflow:hidden
        }
        .status-main.alive{box-shadow:0 0 60px 12px rgba(34,197,94,0.25), 0 0 120px 24px rgba(34,197,94,0.12), 0 12px 40px rgba(2,6,23,0.6);border:1px solid rgba(34,197,94,0.25);animation: breathe-green 3s ease-in-out infinite}
        .status-main.dead{box-shadow:0 0 60px 12px rgba(239,68,68,0.25), 0 0 120px 24px rgba(239,68,68,0.12), 0 12px 40px rgba(2,6,23,0.6);border:1px solid rgba(239,68,68,0.25);animation: breathe-red 3s ease-in-out infinite}
        .status-label{font-size:14px;color:rgba(255,255,255,0.75);}

        /* 窗口名置于状态下方 */
        .window-name{font-size:18px;font-weight:600;color:var(--accent);max-width:94%;text-align:center;white-space:nowrap;transform-origin:center;}100%{transform:translateX(-100%)}}
        .small-info{font-size:12px;color:rgba(255,255,255,0.62);margin-top:6px}

        /* 小块信息区域 */
        .info-row{display:flex;gap:12px;width:100%;justify-content:space-between;margin-top:8px}
        .info-card{flex:1;background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));padding:12px;border-radius:12px;border:1px solid rgba(255,255,255,0.03)}
        .info-card .label{font-size:12px;color:rgba(255,255,255,0.6);margin-bottom:6px}
        .info-card .value{font-size:13px;color:var(--accent)}

        footer{margin-top:10px;font-size:12px;color:rgba(255,255,255,0.5);width:100%;text-align:center}

        @media (max-width:520px){
            .status-main{width:170px;height:170px;font-size:28px}
            .window-name{font-size:16px}
        }
    @keyframes breathe-green {
    0%   { box-shadow:0 0 40px 8px rgba(34,197,94,0.18), 0 0 80px 16px rgba(34,197,94,0.08), 0 12px 40px rgba(2,6,23,0.6); }
    50%  { box-shadow:0 0 80px 20px rgba(34,197,94,0.35), 0 0 160px 40px rgba(34,197,94,0.18), 0 12px 40px rgba(2,6,23,0.6); }
    100% { box-shadow:0 0 40px 8px rgba(34,197,94,0.18), 0 0 80px 16px rgba(34,197,94,0.08), 0 12px 40px rgba(2,6,23,0.6); }
}

@keyframes breathe-red {
    0%   { box-shadow:0 0 40px 8px rgba(239,68,68,0.18), 0 0 80px 16px rgba(239,68,68,0.08), 0 12px 40px rgba(2,6,23,0.6); }
    50%  { box-shadow:0 0 80px 20px rgba(239,68,68,0.35), 0 0 160px 40px rgba(239,68,68,0.18), 0 12px 40px rgba(2,6,23,0.6); }
    100% { box-shadow:0 0 40px 8px rgba(239,68,68,0.18), 0 0 80px 16px rgba(239,68,68,0.08), 0 12px 40px rgba(2,6,23,0.6); }
}

</style>
</head>
<body>
    <div class="bg-visual" aria-hidden="true"></div>
    <div class="card">
        <div class="status-wrap" role="region" aria-label="使用者状态">
            <div id="statusMain" class="status-main small" aria-live="polite">-</div>
            <div class="status-label">使用者状态</div>
            <div id="windowName" class="window-name">正在加载窗口名称…</div>
            <div id="windowSub" class="small-info">来源：******** · <span id="countdown">-</span> 秒后更新</div>
        </div>

        <div class="info-row">
            <div class="info-card">
                <div class="label">上次更新（HA 时间）</div>
                <div id="lastUpdated" class="value">-</div>
            </div>
            <div class="info-card" style="max-width:240px">
                <div class="label">上次窗口名变化</div>
                <div id="lastChanged" class="value">-</div>
            </div>
        </div>

        <footer>Created by ChickenTracier & ChatGPT</footer>
    </div>

<script>
(function(){
    const REFRESH_MS = <?php echo intval($CONFIG['refresh_seconds'] * 1000); ?>;
    const DEAD_MS = <?php echo intval($CONFIG['dead_seconds'] * 1000); ?>;
    const FETCH_URL = location.pathname + '?action=fetch'; // 同一文件代理请求

    let lastWindowName = null;
    let lastNameChangeTs = Date.now();

    const elStatusMain = document.getElementById('statusMain');
    const elWindowName = document.getElementById('windowName');
    const elLastUpdated = document.getElementById('lastUpdated');
    const elLastChanged = document.getElementById('lastChanged');
    const elCountdown = document.getElementById('countdown');

    let nextFetchAt = Date.now() + REFRESH_MS;

    function formatTime(ts){
        if(!ts) return '-';
        const d = new Date(ts);
        return d.toLocaleString();
    }

    function applyStatusClass(alive){
        elStatusMain.classList.remove('alive','dead');
        elStatusMain.classList.add(alive? 'alive' : 'dead');
        elStatusMain.textContent = alive? '活着' : '似了';
    }

    async function fetchOnce(){
        nextFetchAt = Date.now() + REFRESH_MS;
        try{
            const res = await fetch(FETCH_URL, {cache:'no-store'});
            if(!res.ok) throw new Error('HTTP ' + res.status);
            const j = await res.json();
            const state = j.state;
            if(state === 'unavailable'){
                elStatusMain.textContent = '似了';
                elStatusMain.classList.add('dead');
                elStatusMain.classList.remove('alive');
                elLastChanged.textContent = '来源已断开';
            return;
            }
            if(state === 'Windows Default Lock Screen'){
                elWindowName.textContent = 'Windows Default Lock Screen';
                elStatusMain.textContent = '似了';
                elStatusMain.classList.add('dead');
                elStatusMain.classList.remove('alive');
            return;
            }

            if(!j.ok){
                elWindowName.textContent = '错误：' + (j.error || JSON.stringify(j));
                return;
            }
            const windowName = j.state ?? (j.raw && (j.raw.state ?? null)) ?? (j.raw_text ?? null) ?? '无';
            const last_changed_iso = j.last_changed ?? (j.raw && j.raw.last_changed) ?? null;

            // 更新显示
            elWindowName.textContent = windowName;
        fitText(elWindowName);
            elLastUpdated.textContent = last_changed_iso ? new Date(last_changed_iso).toLocaleString() : '-';

            // 判断窗口名变化时间
            if (lastWindowName === null) {
                lastWindowName = windowName;
                lastNameChangeTs = Date.now();
            } else if (windowName !== lastWindowName) {
                lastWindowName = windowName;
                lastNameChangeTs = Date.now();
            }
            elLastChanged.textContent = formatTime(lastNameChangeTs);

            // 判定活/挂
            const alive = (Date.now() - lastNameChangeTs) < DEAD_MS;
            applyStatusClass(alive);

        } catch (e){
            elWindowName.textContent = '请求失败：' + e.message;
            elLastUpdated.textContent = '-';
            elLastChanged.textContent = formatTime(lastNameChangeTs);
            const alive = (Date.now() - lastNameChangeTs) < DEAD_MS;
            applyStatusClass(alive);
        }
    }

    // 首次立刻请求并启动周期
    fetchOnce();
    setInterval(fetchOnce, REFRESH_MS);

    function fitText(el){
        el.style.transform = 'scaleX(1)';
        const max = el.parentElement.clientWidth;
        const real = el.scrollWidth;
        if(real > max){
            const ratio = max / real;
            el.style.transform = `scaleX(${ratio})`;
        }
    }

    // 倒计时显示
    setInterval(() => {
        const remain = Math.max(0, Math.ceil((nextFetchAt - Date.now()) / 1000));
        elCountdown.textContent = remain;
    }, 1000);
})();
</script>
</body>
</html>
