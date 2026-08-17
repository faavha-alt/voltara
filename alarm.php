<?php
require __DIR__ . '/_session.php';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    setcookie(session_name(), '', time() - 3600, '/');
    session_destroy();
    header('Location: login.php');
    exit;
}
if (!isset($_SESSION['voltara_auth'])) { header('Location: login.php'); exit; }

$page_title  = 'Alarm - Voltara EMS';
$active      = 'alarm';
$tb_h1       = 'Status Alarm Sistem';
$tb_sub_init = 'Menghubungkan...';
$tb_pills    = [
    ['id' => 'sys-badge', 'class' => 'pill ok', 'text' => 'Online'],
];

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/sidebar.php';
include __DIR__ . '/partials/topbar.php';
?>

<div class="hero">
  <div class="hero-top">
    <div class="hero-eyebrow">Pemantauan Alarm</div>
    <div class="hero-title">Status sistem <em id="hero-word">...</em></div>
  </div>
  <div class="hero-stats">
    <div class="hs">
      <div class="hs-lbl"><?= icon('alert-triangle', 13) ?> Alarm Aktif</div>
      <div><span class="hs-val" id="h-active-count">-</span></div>
      <div class="hs-sub" id="h-active-sub">-</div>
    </div>
    <div class="hs">
      <div class="hs-lbl"><?= icon('check-circle', 13) ?> Sistem Terpantau</div>
      <div><span class="hs-val" id="h-total-count">-</span></div>
      <div class="hs-sub">Total indikator dicek</div>
    </div>
    <div class="hs">
      <div class="hs-lbl"><?= icon('clock', 13) ?> Diperbarui</div>
      <div><span class="hs-val" id="h-last-update" style="font-size:16px">-</span></div>
      <div class="hs-sub">Real-time (SSE)</div>
    </div>
  </div>
</div>

<div class="content">

  <div class="card" style="margin-bottom:0">
    <div class="card-hd">
      <div><div class="card-ttl">Kelistrikan AC / PZEM</div><div class="card-sub">Over/under voltage, over power, over temperature</div></div>
    </div>
    <div class="card-bd">
      <div class="alarm-grid">
        <div class="alarm-item" id="al-ov"><div class="alarm-dot"></div><div><div class="alarm-lbl">Over-Voltage</div><div class="alarm-sub" id="al-ov-sub">-</div></div></div>
        <div class="alarm-item" id="al-uv"><div class="alarm-dot"></div><div><div class="alarm-lbl">Under-Voltage</div><div class="alarm-sub" id="al-uv-sub">-</div></div></div>
        <div class="alarm-item" id="al-op"><div class="alarm-dot"></div><div><div class="alarm-lbl">Over-Power</div><div class="alarm-sub" id="al-op-sub">-</div></div></div>
        <div class="alarm-item" id="al-ot"><div class="alarm-dot"></div><div><div class="alarm-lbl">Over-Temperature</div><div class="alarm-sub" id="al-ot-sub">-</div></div></div>
      </div>
    </div>
  </div>

  <div class="grid2">
    <div class="card" style="margin-bottom:0">
      <div class="card-hd">
        <div><div class="card-ttl">Baterai Huawei</div><div class="card-sub">ESM-48100U2</div></div>
        <a href="huawei.php" class="tag" style="text-decoration:none">Detail</a>
      </div>
      <div class="card-bd">
        <div class="alarm-grid">
          <div class="alarm-item" id="al-h-alarm"><div class="alarm-dot"></div><div><div class="alarm-lbl">Alarm</div><div class="alarm-sub" id="al-h-alarm-sub">-</div></div></div>
          <div class="alarm-item" id="al-h-warn"><div class="alarm-dot"></div><div><div class="alarm-lbl">Warning</div><div class="alarm-sub" id="al-h-warn-sub">-</div></div></div>
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:0">
      <div class="card-hd">
        <div><div class="card-ttl">Baterai Jikong</div><div class="card-sub">JK-BMS 15S</div></div>
        <a href="jikong.php" class="tag" style="text-decoration:none">Detail</a>
      </div>
      <div class="card-bd">
        <div class="alarm-grid">
          <div class="alarm-item" id="al-j-alarm"><div class="alarm-dot"></div><div><div class="alarm-lbl">Alarm</div><div class="alarm-sub" id="al-j-alarm-sub">-</div></div></div>
          <div class="alarm-item" id="al-j-warn"><div class="alarm-dot"></div><div><div class="alarm-lbl">Warning</div><div class="alarm-sub" id="al-j-warn-sub">-</div></div></div>
        </div>
      </div>
    </div>
  </div>

  <div class="grid2">
    <div class="card" style="margin-bottom:0">
      <div class="card-hd">
        <div><div class="card-ttl">Solar MPPT</div><div class="card-sub">Koneksi & fault panel</div></div>
        <a href="mppt.php" class="tag" style="text-decoration:none">Detail</a>
      </div>
      <div class="card-bd">
        <div class="alarm-grid">
          <div class="alarm-item" id="al-mppt-online"><div class="alarm-dot"></div><div><div class="alarm-lbl">MPPT Online</div><div class="alarm-sub" id="al-mppt-online-sub">-</div></div></div>
          <div class="alarm-item" id="al-pv-fault"><div class="alarm-dot"></div><div><div class="alarm-lbl">PV Fault (siang)</div><div class="alarm-sub" id="al-pv-fault-sub">-</div></div></div>
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:0">
      <div class="card-hd">
        <div><div class="card-ttl">Sistem</div><div class="card-sub">Status keseluruhan</div></div>
      </div>
      <div class="card-bd">
        <div class="alarm-grid">
          <div class="alarm-item" id="al-sys-online"><div class="alarm-dot"></div><div><div class="alarm-lbl">Sistem Online</div><div class="alarm-sub" id="al-sys-online-sub">-</div></div></div>
          <div class="alarm-item" id="al-grid-active"><div class="alarm-dot"></div><div><div class="alarm-lbl">Grid Active</div><div class="alarm-sub" id="al-grid-active-sub">-</div></div></div>
        </div>
      </div>
    </div>
  </div>

  <div class="note-card">
    <?= icon('bell', 16) ?>
    <div>
      <b>Belum ada riwayat/log alarm</b> - halaman ini menampilkan status LIVE saat ini saja.
      Notifikasi otomatis (mis. ke Telegram) begitu ada alarm baru, beserta riwayat kejadian,
      direncanakan sebagai pengembangan berikutnya.
    </div>
  </div>

</div>
</main>
</div><!-- /layout -->

<script>
const el  = id => document.getElementById(id);
const set = (id, v) => { const e = el(id); if (e) e.textContent = v; };
function ss(d, k) { return (d && d[k]) ? (d[k].state ?? '-') : '-'; }
function isOn(d, k) { return ss(d, k) === 'on'; }

function setAlarmItem(itemId, subId, isBad, okText, badText, isBinaryOnBad = true) {
  const item = el(itemId), sub = el(subId);
  if (item) item.className = 'alarm-item ' + (isBad ? 'bad' : 'ok');
  if (sub) sub.textContent = isBad ? badText : okText;
}

function renderStates(d) {
  if (!d || typeof d !== 'object') return;
  try {
    set('tb-sub', 'Diperbarui ' + new Date().toLocaleTimeString('id-ID'));
    set('h-last-update', new Date().toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'}));

    const alOv = isOn(d, 'binary_sensor.smartpowermeter_over_voltage_alarm');
    const alUv = isOn(d, 'binary_sensor.smartpowermeter_under_voltage_alarm');
    const alOp = isOn(d, 'binary_sensor.smartpowermeter_over_power_alarm');
    const alOt = isOn(d, 'binary_sensor.smartpowermeter_over_temperature_alarm');
    setAlarmItem('al-ov', 'al-ov-sub', alOv, 'Normal', 'AKTIF - Tegangan berlebih');
    setAlarmItem('al-uv', 'al-uv-sub', alUv, 'Normal', 'AKTIF - Tegangan kurang');
    setAlarmItem('al-op', 'al-op-sub', alOp, 'Normal', 'AKTIF - Daya berlebih');
    setAlarmItem('al-ot', 'al-ot-sub', alOt, 'Normal', 'AKTIF - Suhu berlebih');

    const hAlarm = ss(d, 'sensor.huawei_has_alarm') === 'True' || ss(d, 'sensor.huawei_has_alarm') === 'on';
    const hWarn  = ss(d, 'sensor.huawei_has_warning') === 'True' || ss(d, 'sensor.huawei_has_warning') === 'on';
    setAlarmItem('al-h-alarm', 'al-h-alarm-sub', hAlarm, 'Normal', 'ADA ALARM');
    setAlarmItem('al-h-warn',  'al-h-warn-sub',  hWarn,  'Normal', 'Ada warning');

    const jAlarm = ss(d, 'sensor.jikong_has_alarm') === 'True' || ss(d, 'sensor.jikong_has_alarm') === 'on';
    const jWarn  = ss(d, 'sensor.jikong_has_warning') === 'True' || ss(d, 'sensor.jikong_has_warning') === 'on';
    setAlarmItem('al-j-alarm', 'al-j-alarm-sub', jAlarm, 'Normal', 'ADA ALARM');
    setAlarmItem('al-j-warn',  'al-j-warn-sub',  jWarn,  'Normal', 'Ada warning');

    const mpptOnline = isOn(d, 'binary_sensor.smartpowermeter_mppt_online') || isOn(d, 'binary_sensor.voltara_system_online');
    const pvFault    = isOn(d, 'binary_sensor.smartpowermeter_pv_fault_daylight');
    setAlarmItem('al-mppt-online', 'al-mppt-online-sub', !mpptOnline, 'Terhubung', 'Terputus');
    setAlarmItem('al-pv-fault', 'al-pv-fault-sub', pvFault, 'Normal', 'FAULT terdeteksi');

    const sysOnline = isOn(d, 'binary_sensor.voltara_system_online');
    const gridActive= isOn(d, 'binary_sensor.grid_active');
    setAlarmItem('al-sys-online', 'al-sys-online-sub', !sysOnline, 'Online', 'Offline');
    // Grid active bukan alarm (netral) - selalu tampil ok/info, bukan bad
    const gridEl = el('al-grid-active'), gridSub = el('al-grid-active-sub');
    if (gridEl) gridEl.className = 'alarm-item ok';
    if (gridSub) gridSub.textContent = gridActive ? 'Terhubung' : 'Tidak terhubung';

    // Hitung total alarm aktif utk hero
    const alarmFlags = [alOv, alUv, alOp, alOt, hAlarm, hWarn, jAlarm, jWarn, !mpptOnline, pvFault, !sysOnline];
    const activeCount = alarmFlags.filter(Boolean).length;
    set('h-active-count', activeCount);
    set('h-active-sub', activeCount === 0 ? 'Semua aman' : `${activeCount} perlu perhatian`);
    set('h-total-count', alarmFlags.length);

    const word = el('hero-word');
    if (word) {
      word.textContent = activeCount === 0 ? 'aman' : activeCount <= 2 ? 'perlu diperiksa' : 'perlu perhatian segera';
      word.style.color = activeCount === 0 ? '#10b981' : activeCount <= 2 ? '#f59e0b' : '#e11d48';
    }

    const badge = el('sys-badge');
    if (badge) {
      badge.textContent = activeCount === 0 ? 'Semua Aman' : `${activeCount} Alarm`;
      badge.className = 'pill ' + (activeCount === 0 ? 'ok' : 'warn');
    }

    const pill = el('sys-pill'); if (pill) pill.className = 'sys-pill' + (sysOnline?'':' off');
    const dot  = el('sys-dot');  if (dot)  dot.className  = 'dot' + (sysOnline?'':' off');
    const txt  = el('sys-txt');  if (txt)  { txt.textContent = sysOnline ? 'Semua Sistem Online' : 'Sistem Offline'; txt.className = 'sys-txt' + (sysOnline?'':' off'); }

  } catch(err) { console.error('renderStates error:', err); }
}

// LIVE DATA: SSE, fallback ke polling 15 detik
async function fetchStatesOnce() {
  try {
    const res = await fetch('proxy.php?type=ha_states');
    if (res.status === 401) { location.href = 'login.php'; return; }
    if (!res.ok) throw new Error();
    const d = await res.json();
    if (d && !d.error) renderStates(d);
  } catch(e) { set('tb-sub', 'Gagal memuat - ' + new Date().toLocaleTimeString('id-ID')); }
}
let pollTimer = null;
let liveEs = null;
let lastLiveTs = Date.now();
function startPollingFallback() {
  if (pollTimer) return;
  fetchStatesOnce();
  pollTimer = setInterval(fetchStatesOnce, 15000);
}
function stopPollingFallback() { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } }
function connectSSE() {
  if (!window.EventSource) { startPollingFallback(); return; }
  if (liveEs) { try { liveEs.close(); } catch(e) {} }
  liveEs = new EventSource('proxy.php?type=sse');
  let sseWorking = false;
  let fallbackTimer = setTimeout(startPollingFallback, 6000);

  liveEs.onmessage = (ev) => {
    sseWorking = true;
    lastLiveTs = Date.now();
    clearTimeout(fallbackTimer);
    stopPollingFallback();
    try {
      const data = JSON.parse(ev.data);
      if (data && !data.error) renderStates(data);
    } catch (e) {}
  };

  // Heartbeat named event (bukan comment SSE) - jadi bisa dideteksi JS
  // buat watchdog di bawah, penjaga kalau koneksi macet diam-diam.
  liveEs.addEventListener('heartbeat', () => { lastLiveTs = Date.now(); });
  liveEs.addEventListener('reconnect', () => {});

  liveEs.onerror = () => {
    if (!sseWorking) {
      fetch('proxy.php?type=ha_states').then(r => {
        if (r.status === 401) { location.href = 'login.php'; }
      }).catch(() => {});
      startPollingFallback();
    }
  };
}

function startLive() {
  connectSSE();
  // Watchdog: kalau >30 detik nggak ada data ATAU heartbeat sama sekali,
  // paksa reconnect sendiri - ini menutup celah "macet diam-diam tanpa
  // error" yang bikin dashboard berhenti update total tanpa alasan jelas.
  setInterval(() => {
    if (Date.now() - lastLiveTs > 30000) {
      console.warn('SSE macet lebih dari 30 detik - reconnect paksa...');
      startPollingFallback();
      connectSSE();
      lastLiveTs = Date.now();
    }
  }, 10000);
}
startLive();
</script>
</body>
</html>