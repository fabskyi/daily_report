<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Productivity Ratio </title>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;700&family=Barlow:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <style>
      :root {
        --bg-primary: #f1f5f9;
        --bg-card: #ffffff;
        --bg-card2: #f8fafc;
        --border: #e2e8f0;
        --border-strong: #e2e8f0;

        --accent-blue: #2563eb;
        --accent-cyan: #06b6d4;
        --accent-green: #10b981;
        --accent-red: #ef4444;
        --accent-orange: #f59e0b;
        --accent-purple: #8b5cf6;

        --text-primary: #0f172a;
        --text-muted: #64748b;
        --text-dim: #94a3b8;

        --radius-sm: 10px;
        --radius: 16px;

        --font-display: "Rajdhani", sans-serif;
        --font-body: "Barlow", sans-serif;
        --font-mono: "JetBrains Mono", monospace;
      }

      *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

      html, body {
        width: 100vw; height: 100vh;
        overflow: hidden;
        font-family: var(--font-body);
        background: var(--bg-primary);
        color: var(--text-primary);
      }

      body::before {
        content: "";
        position: fixed; inset: 0;
        background-image: radial-gradient(circle, rgba(0,0,0,0.025) 1px, transparent 1px);
        background-size: 28px 28px;
        pointer-events: none; z-index: 0;
      }

      #app {
        position: relative; z-index: 1;
        display: flex; flex-direction: column;
        height: 100vh;
        padding: 10px 12px;
        gap: 8px;
      }

      /* ══════════════════════════════
         HEADER
      ══════════════════════════════ */
      header {
        flex: 0 0 60px;
        display: flex; align-items: center; justify-content: space-between;
        background: var(--bg-card);
        border: 1px solid var(--border-strong);
        border-radius: var(--radius);
        padding: 0 20px; gap: 16px;
      }

      .header-left { display: flex; align-items: center; gap: 14px; }
      .header-right { display: flex; align-items: center; gap: 14px; }

      .logo-box {
        width: 38px; height: 38px;
        background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-family: var(--font-display); font-weight: 700; font-size: 15px; color: #fff;
        flex-shrink: 0;
      }

      .header-title h1 {
        font-family: var(--font-display); font-size: 18px; font-weight: 700;
        letter-spacing: 0.4px; color: var(--text-primary);
      }
      .header-title p {
        font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); margin-top: 1px;
      }

      .divider-v { width: 1px; height: 28px; background: var(--border-strong); flex-shrink: 0; }

      .select-group { display: flex; align-items: center; gap: 8px; }
      .select-label {
        font-family: var(--font-mono); font-size: 11px; color: var(--text-muted);
        text-transform: uppercase; letter-spacing: 0.6px;
      }

      select.hdr-select {
        height: 32px; font-size: 12px; font-family: var(--font-mono);
        padding: 0 28px 0 10px;
        background: var(--bg-card2);
        border: 1px solid var(--border-strong);
        border-radius: 8px; color: var(--text-primary); cursor: pointer;
        outline: none; appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 8px center;
      }
      select.hdr-select:focus { border-color: var(--accent-blue); }

      .avatar {
        width: 36px; height: 36px; border-radius: 50%;
        background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
        display: flex; align-items: center; justify-content: center;
        font-family: var(--font-display); font-weight: 700; font-size: 14px; color: #fff;
        flex-shrink: 0;
      }

      .user-meta span { display: block; font-family: var(--font-mono); }
      .user-meta .name { font-size: 13px; color: var(--text-primary); font-weight: 600; }
      .user-meta .role { font-size: 10px; color: var(--text-muted); }

      .clock-block { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; }
      #live-time {
        font-family: var(--font-mono); font-size: 20px; font-weight: 700;
        color: var(--text-primary); letter-spacing: 1px;
      }
      #live-date {
        font-family: var(--font-mono); font-size: 11px; color: var(--accent-cyan);
        background: rgba(6,182,212,0.08); border: 1px solid rgba(6,182,212,0.25);
        padding: 2px 8px; border-radius: 5px;
      }

      /* ══════════════════════════════
         KPI STRIP
      ══════════════════════════════ */
      .kpi-strip {
        flex: 0 0 110px;
        display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px;
      }

      .kpi-card {
        background: var(--bg-card);
        border: 1px solid var(--border-strong);
        border-radius: var(--radius);
        padding: 14px 16px;
        position: relative; overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex; flex-direction: column; justify-content: space-between;
      }
      .kpi-card::after {
        content: ""; position: absolute; top: 0; left: 0; right: 0;
        height: 3px; border-radius: var(--radius) var(--radius) 0 0;
      }
      .kpi-card.blue::after   { background: var(--accent-blue);   box-shadow: 0 0 12px rgba(37,99,235,0.5); }
      .kpi-card.green::after  { background: var(--accent-green);  box-shadow: 0 0 12px rgba(16,185,129,0.5); }
      .kpi-card.red::after    { background: var(--accent-red);    box-shadow: 0 0 12px rgba(239,68,68,0.5); }
      .kpi-card.cyan::after   { background: var(--accent-cyan);   box-shadow: 0 0 12px rgba(6,182,212,0.5); }
      .kpi-card.orange::after { background: var(--accent-orange); box-shadow: 0 0 12px rgba(245,158,11,0.5); }
      .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }

      .kpi-top { display: flex; justify-content: space-between; align-items: center; }
      .kpi-label {
        font-size: 10px; color: var(--text-muted);
        font-family: var(--font-mono); text-transform: uppercase; letter-spacing: 0.7px;
      }
      .kpi-badge {
        font-size: 10px; font-family: var(--font-mono); padding: 2px 7px; border-radius: 5px;
      }
      .kpi-badge.pos { background: rgba(16,185,129,0.12); color: var(--accent-green); }
      .kpi-badge.neg { background: rgba(239,68,68,0.12);  color: var(--accent-red); }

      .kpi-value {
        font-family: var(--font-display); font-size: 32px; font-weight: 700;
        letter-spacing: -0.5px; line-height: 1;
      }
      .kpi-card.blue .kpi-value   { color: var(--accent-blue); }
      .kpi-card.green .kpi-value  { color: var(--accent-green); }
      .kpi-card.red .kpi-value    { color: var(--accent-red); }
      .kpi-card.cyan .kpi-value   { color: var(--accent-cyan); }
      .kpi-card.orange .kpi-value { color: var(--accent-orange); }

      .kpi-icon { position: absolute; bottom: 8px; right: 12px; font-size: 28px; opacity: 0.06; }

      /* ══════════════════════════════
         INFO BAR
      ══════════════════════════════ */
      .info-bar {
        flex: 0 0 54px;
        display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;
      }

      .info-cell {
        background: var(--bg-card);
        border: 1px solid var(--border-strong);
        border-radius: var(--radius-sm);
        padding: 0 18px;
        display: flex; align-items: center; gap: 14px;
      }
      .info-cell-label {
        font-size: 11px; color: var(--text-muted);
        font-family: var(--font-mono); text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;
      }
      .info-divider { width: 1px; height: 22px; background: var(--border-strong); flex-shrink: 0; }
      .info-cell-value {
        font-family: var(--font-display); font-size: 20px; font-weight: 700; color: var(--text-primary);
      }

      /* ══════════════════════════════
         MAIN AREA
      ══════════════════════════════ */
      .main-area {
        flex: 1 1 0;
        display: grid; grid-template-columns: 3fr 2fr;
        gap: 8px; min-height: 0;
      }

      .table-card, .chart-card {
        background: var(--bg-card);
        border: 1px solid var(--border-strong);
        border-radius: var(--radius);
        padding: 14px 16px;
        display: flex; flex-direction: column; min-height: 0;
      }

      .card-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 10px; flex-shrink: 0;
      }
      .card-title {
        font-family: var(--font-display); font-size: 16px; font-weight: bold;
        text-transform: uppercase; letter-spacing: 1.2px; color: var(--text-muted);
      }

      .pill-group { display: flex; gap: 5px; }
      .pill-btn {
        font-size: 11px; font-family: var(--font-mono);
        padding: 4px 13px; border-radius: 20px;
        border: 1px solid var(--border-strong);
        background: var(--bg-card2); color: var(--text-muted);
        cursor: pointer; transition: all 0.15s;
      }
      .pill-btn:hover, .pill-btn.active {
        background: rgba(37,99,235,0.1); border-color: var(--accent-blue); color: var(--accent-blue);
      }

      /* ── PRODUCTION TABLE ── */
      .prod-table-wrap { flex: 1; min-height: 0; overflow-y: auto; }

      .prod-table {
        width: 100%; border-collapse: collapse;
        font-family: var(--font-mono);
      }

      .prod-table thead th {
        position: sticky; top: 0;
        background: var(--bg-card2);
        border-bottom: 2px solid var(--border-strong);
        padding: 10px 13px;
        text-align: center;
        font-size: 16px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.8px;
        color: var(--text-muted);
        z-index: 2; white-space: nowrap;
      }
      .prod-table thead th:first-child { text-align: left; }
      .prod-table thead th.th-plan  { color: var(--accent-blue);   border-bottom-color: var(--accent-blue); }
      .prod-table thead th.th-good  { color: var(--accent-green);  border-bottom-color: var(--accent-green); }
      .prod-table thead th.th-ng    { color: var(--accent-red);    border-bottom-color: var(--accent-red); }
      .prod-table thead th.th-stop  { color: var(--accent-orange); border-bottom-color: var(--accent-orange); }

      .prod-table tbody tr {
        border-bottom: 1px solid var(--border);
        transition: background 0.15s;
      }
      .prod-table tbody tr:hover { background: rgba(37,99,235,0.04); }
      .prod-table tbody tr:last-child { border-bottom: none; }

      .prod-table tbody td {
        padding: 11px 13px;
        text-align: center; vertical-align: middle;
      }
      .prod-table tbody td:first-child { text-align: left; }

      .td-time {
        font-family: var(--font-mono); font-size: 12px;
        color: var(--text-muted); white-space: nowrap; font-weight: 500;
      }
      .td-plan { font-weight: 700; font-size: 15px; color: var(--accent-blue); }
      .td-good { font-weight: 700; font-size: 15px; color: var(--accent-green); }
      .td-ng   { font-weight: 700; font-size: 15px; color: var(--accent-red); }

      .stop-badge {
        display: inline-block;
        padding: 4px 11px; border-radius: 6px;
        font-size: 11px; font-family: var(--font-mono); font-weight: 600;
        white-space: nowrap;
      }
      .stop-badge.tool     { background: rgba(245,158,11,0.12); color: var(--accent-orange); border: 1px solid rgba(245,158,11,0.3); }
      .stop-badge.material { background: rgba(239,68,68,0.10);  color: var(--accent-red);    border: 1px solid rgba(239,68,68,0.3); }
      .stop-badge.machine  { background: rgba(139,92,246,0.10); color: var(--accent-purple); border: 1px solid rgba(139,92,246,0.3); }
      .stop-badge.quality  { background: rgba(6,182,212,0.10);  color: var(--accent-cyan);   border: 1px solid rgba(6,182,212,0.3); }
      .stop-badge.none     { background: rgba(16,185,129,0.08); color: var(--accent-green);  border: 1px solid rgba(16,185,129,0.2); }

      .ach-bar-wrap { display: flex; align-items: center; gap: 8px; }
      .ach-bar-bg {
        flex: 1; height: 7px;
        background: var(--border); border-radius: 99px;
        overflow: hidden; min-width: 48px;
      }
      .ach-bar-fill { height: 100%; border-radius: 99px; transition: width 0.4s ease; }
      .ach-pct {
        font-size: 12px; font-family: var(--font-mono);
        min-width: 38px; text-align: right; font-weight: 700;
      }

      .prod-table tfoot tr {
        background: rgba(37,99,235,0.05);
        border-top: 2px solid var(--border-strong);
      }
      .prod-table tfoot td {
        padding: 11px 13px; font-weight: 700; font-size: 14px;
        text-align: center; color: var(--text-primary);
      }
      .prod-table tfoot td:first-child {
        text-align: left; font-family: var(--font-mono);
        font-size: 11px; color: var(--text-muted);
        text-transform: uppercase; letter-spacing: 0.8px; font-weight: 700;
      }

      /* ── CHART ── */
      .chart-wrap { flex: 1; min-height: 0; position: relative; }
      .chart-legend {
        display: flex; gap: 16px; justify-content: center;
        padding-top: 8px; flex-shrink: 0;
      }
      .legend-item {
        display: flex; align-items: center; gap: 7px;
        font-size: 12px; font-family: var(--font-mono); color: var(--text-muted);
      }
      .legend-box { width: 13px; height: 13px; border-radius: 3px; }

      /* ══════════════════════════════
         BOTTOM — NG TYPES
      ══════════════════════════════ */
      .bottom-area {
        flex: 0 0 148px;
        display: grid; grid-template-columns: 1fr; gap: 8px;
      }

      .ng-card {
        background: var(--bg-card);
        border: 1px solid var(--border-strong);
        border-radius: var(--radius);
        padding: 12px 18px;
        display: flex; flex-direction: column;
        min-height: 0; overflow: hidden;
      }

      .ng-two-col {
        display: grid; grid-template-columns: 1fr 1fr;
        gap: 24px; flex: 1; overflow: hidden;
      }

      .ng-col-title {
        font-size: 11px; font-family: var(--font-mono);
        text-transform: uppercase; letter-spacing: 0.6px;
        padding-bottom: 6px; border-bottom: 1px solid var(--border-strong);
        margin-bottom: 6px; font-weight: 700;
      }
      .ng-col-title.mach { color: var(--accent-orange); }
      .ng-col-title.mat  { color: var(--accent-red); }

      .ng-rows { overflow-y: auto; }
      .ng-row {
        display: flex; justify-content: space-between;
        padding: 5px 0; border-bottom: 1px solid var(--border);
        font-family: var(--font-mono); font-size: 12px;
      }
      .ng-row:last-child { border-bottom: none; }
      .ng-time { color: var(--text-muted); }
      .ng-qty  { color: var(--text-primary); font-weight: 600; }

      /* ══════════════════════════════
         STATUS BAR
      ══════════════════════════════ */
      .status-bar {
        flex: 0 0 32px;
        display: flex; align-items: center; justify-content: space-between;
        background: var(--bg-card);
        border: 1px solid var(--border-strong);
        border-radius: var(--radius-sm);
        padding: 0 16px;
        font-family: var(--font-mono); font-size: 11px; color: var(--text-muted);
      }

      .status-dot {
        display: inline-block; width: 7px; height: 7px; border-radius: 50%;
        background: var(--accent-green); box-shadow: 0 0 6px var(--accent-green);
        margin-right: 6px; animation: pulse 2s infinite;
      }

      @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.35} }

      ::-webkit-scrollbar { width: 4px; height: 4px; }
      ::-webkit-scrollbar-track { background: transparent; }
      ::-webkit-scrollbar-thumb { background: var(--border-strong); border-radius: 2px; }
    </style>
  </head>
  <body>
    <div id="app">

      <!-- HEADER -->
      <header>
        <div class="header-left">
          <div class="logo"><img src="assets/yanmar.png" alt="Logo" width="40" height="40"></div>
          <div class="header-title">
            <h1>DASHBOARD</h1>
            <p id="active-line-label">Loading…</p>
          </div>
          <div class="divider-v"></div>
          <div class="select-group">
            <span class="select-label">Line</span>
            <select class="hdr-select" id="select-line" onchange="handleLineChange(this.value)"></select>
          </div>
          <div class="select-group">
            <span class="select-label">Shift</span>
            <select class="hdr-select" id="select-shift" onchange="handleShiftChange(this.value)"></select>
          </div>
        </div>
        <div class="header-right">
          <div style="display:flex;align-items:center;gap:10px">
            <div class="avatar" id="avatar-initials">U</div>
            <div class="user-meta">
              <span class="role" id="user-role">USER</span>
              <span class="name" id="user-name">—</span>
            </div>
          </div>
          <div class="divider-v"></div>
          <div class="clock-block">
            <span id="live-date">—</span>
            <span id="live-time">—</span>
          </div>
        </div>
      </header>

      <!-- KPI STRIP -->
      <div class="kpi-strip">
        <div class="kpi-card blue">
          <div class="kpi-top">
            <span class="kpi-label">Total Production</span>
            <span class="kpi-badge pos" id="kpi-total-badge">—</span>
          </div>
          <div class="kpi-value" id="kpi-total">—</div>
          <div class="kpi-icon">📦</div>
        </div>
        <div class="kpi-card green">
          <div class="kpi-top">
            <span class="kpi-label">Good Product</span>
            <span class="kpi-badge pos" id="kpi-good-badge">—</span>
          </div>
          <div class="kpi-value" id="kpi-good">—</div>
          <div class="kpi-icon">✅</div>
        </div>
        <div class="kpi-card red">
          <div class="kpi-top">
            <span class="kpi-label">NG Product</span>
            <span class="kpi-badge neg" id="kpi-ng-badge">—</span>
          </div>
          <div class="kpi-value" id="kpi-ng">—</div>
          <div class="kpi-icon">⚠️</div>
        </div>
        <div class="kpi-card cyan">
          <div class="kpi-top">
            <span class="kpi-label">Performance</span>
            <span class="kpi-badge pos" id="kpi-perf-badge">—</span>
          </div>
          <div class="kpi-value" id="kpi-perf">—</div>
          <div class="kpi-icon">⚡</div>
        </div>
        <div class="kpi-card orange">
          <div class="kpi-top">
            <span class="kpi-label">Stop Time</span>
            <span class="kpi-badge neg" id="kpi-stop-badge">—</span>
          </div>
          <div class="kpi-value" id="kpi-stop">—</div>
          <div class="kpi-icon">⏸️</div>
        </div>
      </div>

      <!-- INFO BAR -->
      <div class="info-bar">
        <div class="info-cell">
          <span class="info-cell-label">Cycle Time</span>
          <div class="info-divider"></div>
          <span class="info-cell-value" id="info-cycle">—</span>
        </div>
        <div class="info-cell">
          <span class="info-cell-label">Model</span>
          <div class="info-divider"></div>
          <span class="info-cell-value" id="info-model">—</span>
        </div>
        <div class="info-cell">
          <span class="info-cell-label">Part Number</span>
          <div class="info-divider"></div>
          <span class="info-cell-value" id="info-part">—</span>
        </div>
        <div class="info-cell">
          <span class="info-cell-label">Jumlah Operator</span>
          <div class="info-divider"></div>
          <span class="info-cell-value" id="info-operator">—</span>
        </div>
      </div>

      <!-- MAIN AREA -->
      <div class="main-area">

        <!-- Detail Produksi -->
        <div class="table-card">
          <div class="card-header">
            <span class="card-title">Detail Produksi</span>
            <div class="pill-group">
              <button class="pill-btn active" onclick="setTableView('today',this)">Today</button>
              <button class="pill-btn" onclick="setTableView('week',this)">Week</button>
            </div>
          </div>
          <div class="prod-table-wrap">
            <table class="prod-table">
              <thead>
                <tr>
                  <th>Waktu</th>
                  <th class="th-plan">Plan</th>
                  <th class="th-good">Good</th>
                  <th class="th-ng">Not Good</th>
                  <th class="th-stop">Stop Time</th>
                  <th>Achievement</th>
                </tr>
              </thead>
              <tbody id="prod-tbody"></tbody>
              <tfoot id="prod-tfoot"></tfoot>
            </table>
          </div>
        </div>

        <!-- Progress Plan vs Actual Chart -->
        <div class="chart-card">
          <div class="card-header">
            <span class="card-title">Progress Plan vs Actual</span>
            <div class="pill-group">
              <button class="pill-btn active" onclick="setProgressView('today',this)">Today</button>
              <button class="pill-btn" onclick="setProgressView('week',this)">Week</button>
            </div>
          </div>
          <div class="chart-wrap">
            <canvas id="progressChart"></canvas>
          </div>
          <div class="chart-legend">
            <div class="legend-item"><span class="legend-box" style="background:rgba(37,99,235,0.25)"></span>Plan</div>
            <div class="legend-item"><span class="legend-box" style="background:#2563eb"></span>Actual</div>
          </div>
        </div>

      </div>

      <!-- BOTTOM: NG TYPES FULL WIDTH -->
      <div class="bottom-area">
        <div class="ng-card">
          <div class="card-header">
            <span class="card-title">NG Types</span>
            <button class="pill-btn" onclick="refreshNgData()">↻ Refresh</button>
          </div>
          <div class="ng-two-col">
            <div>
              <div class="ng-col-title mach">⚙️&nbsp; Machining Problem</div>
              <div class="ng-rows" id="ng-mach"></div>
            </div>
            <div>
              <div class="ng-col-title mat">🧱&nbsp; Material Problem</div>
              <div class="ng-rows" id="ng-mat"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- STATUS BAR -->
      <div class="status-bar">
        <span><span class="status-dot"></span>LIVE — Auto-refresh 30s</span>
        <span id="last-updated">Last updated: —</span>
        <span id="db-status">DB: Not connected (demo mode)</span>
      </div>

    </div>

    <script>
      const DEMO_DATA = {
        lines: [
          { id: "line1", label: "Conrod Line 1" },
          { id: "line2", label: "Conrod Line 2" },
          { id: "line3", label: "Conrod Line 3" },
        ],
        shifts: [
          { id: "1", label: "Shift 1" },
          { id: "2", label: "Shift 2" },
          { id: "3", label: "Shift 3" },
        ],
        user: { role: "VALENTINO", name: "20206003" },
        kpis: {
          total: { value: 17  , badge: "+2.1%", type: "pos" },
          good:  { value: 15, badge: "+1.4%", type: "pos" },
          ng:    { value: 2,  badge: "-0.3%", type: "neg" },
          perf:  { value: "87.4%", badge: "+3.2%", type: "pos" },
          stop:  { value: "30 min", badge: "+12m", type: "neg" },
        },
        info: { cycleTime: "3600 sec", model: "NF60", partNo: "10510G-23050", operators: "4 Orang" },
        ngTypes: {
          machining: [
            { time: "14:52", qty: "10 Qty" },
            { time: "13:40", qty: "7 Qty" },
            { time: "12:15", qty: "4 Qty" },
            { time: "11:00", qty: "8 Qty" },
            { time: "10:30", qty: "3 Qty" },
          ],
          material: [
            { time: "14:52", qty: "10 Qty" },
            { time: "13:50", qty: "5 Qty" },
            { time: "12:30", qty: "9 Qty" },
            { time: "11:20", qty: "2 Qty" },
            { time: "10:05", qty: "7 Qty" },
          ],
        },
      };

      const PROD_TABLE_DATA = {
        today: [
          { time: "06:00 – 07:00", plan: 120, good: 115, ng: 5,  stop: "Tool Setting",  stopType: "tool" },
          { time: "07:00 – 08:00", plan: 120, good: 118, ng: 2,  stop: "—",              stopType: "none" },
          { time: "08:00 – 09:00", plan: 120, good: 110, ng: 10, stop: "Material",       stopType: "material" },
          { time: "09:00 – 10:00", plan: 120, good: 120, ng: 0,  stop: "—",              stopType: "none" },
          { time: "10:00 – 11:00", plan: 120, good: 108, ng: 12, stop: "Machine Down",   stopType: "machine" },
          { time: "11:00 – 12:00", plan: 120, good: 117, ng: 3,  stop: "Tool Setting",  stopType: "tool" },
          { time: "13:00 – 14:00", plan: 120, good: 119, ng: 1,  stop: "—",              stopType: "none" },
          { time: "14:00 – 15:00", plan: 120, good: 105, ng: 15, stop: "Quality Check", stopType: "quality" },
        ],
        week: [
          { time: "Monday",    plan: 960, good: 920, ng: 40, stop: "Tool Setting",  stopType: "tool" },
          { time: "Tuesday",   plan: 960, good: 945, ng: 15, stop: "—",              stopType: "none" },
          { time: "Wednesday", plan: 960, good: 900, ng: 60, stop: "Machine Down",  stopType: "machine" },
          { time: "Thursday",  plan: 960, good: 930, ng: 30, stop: "Material",      stopType: "material" },
          { time: "Friday",    plan: 960, good: 950, ng: 10, stop: "—",              stopType: "none" },
          { time: "Saturday",  plan: 480, good: 455, ng: 25, stop: "Tool Setting",  stopType: "tool" },
        ],
      };

      const PROGRESS_DATA = {
        today: {
          labels: ["06:30 – 08:40","08:40 – 10:50","10:50 – 13:00","13:00 – 15:10"],
          plan:   [1600, 1800, 1900, 1400],
          actual: [1200, 900,  1100, 700],
        },
        week: {
          labels: ["Mon","Tue","Wed","Thu","Fri","Sat"],
          plan:   [8000, 8000, 8000, 8000, 8000, 4000],
          actual: [7900, 8100, 8500, 7800, 8200, 3200],
        },
      };

      let state = { line: "line2", shift: "2", tableView: "today", progressView: "today" };
      let progressChart;

      /* CLOCK */
      function updateClock() {
        const now = new Date();
        const days   = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
        const months = ["January","February","March","April","May","June","July","August","September","October","November","December"];
        document.getElementById("live-date").textContent =
          `${days[now.getDay()]}, ${months[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()}`;
        document.getElementById("live-time").textContent =
          now.toLocaleTimeString("id-ID", { hour12: false }) + " WIB";
      }
      setInterval(updateClock, 1000);
      updateClock();

      /* DROPDOWNS */
      function populateDropdowns() {
        const lineEl = document.getElementById("select-line");
        const shiftEl = document.getElementById("select-shift");
        DEMO_DATA.lines.forEach(l => {
          const o = document.createElement("option");
          o.value = l.id; o.textContent = l.label;
          if (l.id === state.line) o.selected = true;
          lineEl.appendChild(o);
        });
        DEMO_DATA.shifts.forEach(s => {
          const o = document.createElement("option");
          o.value = s.id; o.textContent = s.label;
          if (s.id === state.shift) o.selected = true;
          shiftEl.appendChild(o);
        });
      }
      function handleLineChange(val)  { state.line  = val; refreshAll(); }
      function handleShiftChange(val) { state.shift = val; refreshAll(); }

      /* KPI */
      function renderKPIs(d) {
        document.getElementById("kpi-total").textContent       = d.total.value.toLocaleString("id-ID");
        document.getElementById("kpi-total-badge").textContent = d.total.badge;
        document.getElementById("kpi-good").textContent        = d.good.value.toLocaleString("id-ID");
        document.getElementById("kpi-good-badge").textContent  = d.good.badge;
        document.getElementById("kpi-ng").textContent          = d.ng.value.toLocaleString("id-ID");
        document.getElementById("kpi-ng-badge").textContent    = d.ng.badge;
        document.getElementById("kpi-perf").textContent        = d.perf.value;
        document.getElementById("kpi-perf-badge").textContent  = d.perf.badge;
        document.getElementById("kpi-stop").textContent        = d.stop.value;
        document.getElementById("kpi-stop-badge").textContent  = d.stop.badge;
      }

      /* INFO BAR */
      function renderInfo(d) {
        document.getElementById("info-cycle").textContent    = d.cycleTime;
        document.getElementById("info-model").textContent    = d.model;
        document.getElementById("info-part").textContent     = d.partNo;
        document.getElementById("info-operator").textContent = d.operators;
        const line = DEMO_DATA.lines.find(l => l.id === state.line);
        document.getElementById("active-line-label").textContent =
          `${line ? line.label : ""} · Shift ${state.shift}`;
      }

      /* PRODUCTION TABLE */
      function getAchColor(pct) {
        if (pct >= 95) return "#10b981";
        if (pct >= 80) return "#f59e0b";
        return "#ef4444";
      }

      function renderProdTable(rows) {
        const tbody = document.getElementById("prod-tbody");
        const tfoot = document.getElementById("prod-tfoot");
        let totalPlan = 0, totalGood = 0, totalNg = 0;

        tbody.innerHTML = rows.map(row => {
          totalPlan += row.plan;
          totalGood += row.good;
          totalNg   += row.ng;
          const pct = Math.round((row.good / row.plan) * 100);
          const color = getAchColor(pct);
          return `
            <tr>
              <td><span class="td-time">${row.time}</span></td>
              <td><span class="td-plan">${row.plan}</span></td>
              <td><span class="td-good">${row.good}</span></td>
              <td><span class="td-ng">${row.ng > 0 ? row.ng : `<span style="color:var(--text-dim)">0</span>`}</span></td>
              <td><span class="stop-badge ${row.stopType}">${row.stop}</span></td>
              <td>
                <div class="ach-bar-wrap">
                  <div class="ach-bar-bg">
                    <div class="ach-bar-fill" style="width:${Math.min(pct,100)}%;background:${color}"></div>
                  </div>
                  <span class="ach-pct" style="color:${color}">${pct}%</span>
                </div>
              </td>
            </tr>`;
        }).join("");

        const totalPct = Math.round((totalGood / totalPlan) * 100);
        const totalColor = getAchColor(totalPct);
        tfoot.innerHTML = `
          <tr>
            <td>TOTAL</td>
            <td style="color:var(--accent-blue)">${totalPlan.toLocaleString("id-ID")}</td>
            <td style="color:var(--accent-green)">${totalGood.toLocaleString("id-ID")}</td>
            <td style="color:var(--accent-red)">${totalNg.toLocaleString("id-ID")}</td>
            <td>—</td>
            <td>
              <div class="ach-bar-wrap">
                <div class="ach-bar-bg">
                  <div class="ach-bar-fill" style="width:${Math.min(totalPct,100)}%;background:${totalColor}"></div>
                </div>
                <span class="ach-pct" style="color:${totalColor}">${totalPct}%</span>
              </div>
            </td>
          </tr>`;
      }

      function setTableView(view, btn) {
        state.tableView = view;
        btn.closest(".pill-group").querySelectorAll(".pill-btn").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        renderProdTable(PROD_TABLE_DATA[view]);
      }

      /* PROGRESS CHART */
      function buildProgressChart() {
        const d = PROGRESS_DATA[state.progressView];
        const ctx = document.getElementById("progressChart").getContext("2d");
        if (progressChart) progressChart.destroy();
        progressChart = new Chart(ctx, {
          type: "bar",
          data: {
            labels: d.labels,
            datasets: [
              {
                label: "Plan", data: d.plan,
                backgroundColor: "rgba(37,99,235,0.18)", borderColor: "rgba(37,99,235,0.4)",
                borderWidth: 1, borderRadius: 5,
              },
              {
                label: "Actual", data: d.actual,
                backgroundColor: "#2563eb", borderColor: "#60a5fa",
                borderWidth: 1, borderRadius: 5,
              },
            ],
          },
          options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
              legend: { display: false },
              tooltip: {
                backgroundColor: "#1e293b", titleColor: "#94a3b8",
                bodyColor: "#e2e8f0", borderColor: "rgba(255,255,255,0.08)",
                borderWidth: 1, padding: 10,
                titleFont: { family: "JetBrains Mono", size: 11 },
                bodyFont:  { family: "JetBrains Mono", size: 12 },
              },
            },
            scales: {
              x: {
                grid: { display: false },
                ticks: { color: "#64748b", font: { family: "JetBrains Mono", size: 11 } },
              },
              y: {
                grid: { color: "rgba(0,0,0,0.04)" },
                ticks: { color: "#64748b", font: { family: "JetBrains Mono", size: 11 } },
              },
            },
          },
        });
      }

      function setProgressView(view, btn) {
        state.progressView = view;
        btn.closest(".pill-group").querySelectorAll(".pill-btn").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        buildProgressChart();
      }

      /* NG TYPES */
      function renderNgTypes(data) {
        document.getElementById("ng-mach").innerHTML = data.machining.map(r =>
          `<div class="ng-row"><span class="ng-time">${r.time}</span><span class="ng-qty">${r.qty}</span></div>`
        ).join("");
        document.getElementById("ng-mat").innerHTML = data.material.map(r =>
          `<div class="ng-row"><span class="ng-time">${r.time}</span><span class="ng-qty">${r.qty}</span></div>`
        ).join("");
      }
      function refreshNgData() { renderNgTypes(DEMO_DATA.ngTypes); }

      /* USER */
      function renderUser(u) {
        document.getElementById("user-role").textContent = u.role;
        document.getElementById("user-name").textContent = u.name;
        document.getElementById("avatar-initials").textContent = u.name.charAt(0);
      }

      /* REFRESH ALL */
      function refreshAll() {
        renderKPIs(DEMO_DATA.kpis);
        renderInfo(DEMO_DATA.info);
        renderProdTable(PROD_TABLE_DATA[state.tableView]);
        renderNgTypes(DEMO_DATA.ngTypes);
        buildProgressChart();
        renderUser(DEMO_DATA.user);
        document.getElementById("last-updated").textContent =
          "Last updated: " + new Date().toLocaleTimeString("id-ID", { hour12: false });
      }

      document.addEventListener("DOMContentLoaded", () => {
        populateDropdowns();
        refreshAll();
        setInterval(refreshAll, 30_000);
      });
    </script>
  </body>
</html>
