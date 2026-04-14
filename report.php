<?php
// =======================
// index.php – Single File + Jam Shift + Time Interval Dropdown
// =======================

$host = '127.0.0.1';
$dbname = 'daily_report_db';
$user = 'root';
$pass = '';
$port = 3306;

$pdo = null;

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

if (isset($_GET['ajax_parts']) && isset($_GET['line_code'])) {
    header('Content-Type: application/json');
    $line_code = $_GET['line_code'] ?? null;
    if (!$line_code) {
        echo json_encode([]);
        exit;
    }
    $stmt = $pdo->prepare("
        SELECT p.id, p.part_number, p.finish, p.cycle_time_sec
        FROM parts p
        JOIN line l ON l.id = p.line_id
        WHERE l.code = ?
        ORDER BY p.part_number
    ");
    $stmt->execute([$line_code]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

function getShiftIntervals($pdo, $shiftCode)
{
    $stmt = $pdo->prepare("
        SELECT sti.interval_seq, sti.start_time, sti.end_time, sti.description
        FROM shift_time_intervals sti
        JOIN shift_types st ON st.id = sti.shift_type_id
        WHERE st.code = ?
        ORDER BY sti.interval_seq
    ");
    $stmt->execute([$shiftCode]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$message = $alert = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $waktu = $_POST['waktu'] ?? null;
    $line = $_POST['line'] ?? null;
    $nik = $_POST['nik'] ?? null;
    $part_number = $_POST['part_number'] ?? null;
    $model = $_POST['model'] ?? null;
    $shift = $_POST['shift'] ?? null;
    $plan = (int) ($_POST['plan'] ?? 0);
    $cycle_time = floatval($_POST['cycle_time'] ?? 0);
    $actual = (int) ($_POST['actual'] ?? 0);
    $ng_by_machining = (int) ($_POST['ng_by_machining'] ?? 0);
    $ng_by_material = (int) ($_POST['ng_by_material'] ?? 0);
    $stoptime_min = (int) ($_POST['stoptime_min'] ?? 0);
    $selected_interval_seq = (int) ($_POST['selected_interval_seq'] ?? 0);
    $classification_stoptime = $_POST['classification_stoptime'] ?? null;

    $stmt = $pdo->prepare("SELECT full_name FROM employees WHERE nik = ?");
    $stmt->execute([$nik]);
    $row = $stmt->fetch();
    $pic = $row ? $row['full_name'] : $nik;

    $performance = $plan > 0 ? round(($actual / $plan) * 100, 2) : 0;

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO production_dashboard_input
             (no, pic, nik, line, model, part_number, shift, waktu, plan,
              cycle_time, actual, ng_by_machining, ng_by_material,
              stoptime_min, classification_stoptime, performance_pct, interval_seq)
             VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $pic,
            $nik,
            $line,
            $model,
            $part_number,
            $shift,
            $waktu,
            $plan,
            $cycle_time,
            $actual,
            $ng_by_machining,
            $ng_by_material,
            $stoptime_min,
            $classification_stoptime,
            $performance,
            $selected_interval_seq
        ]);
        $message = "✅ Data berhasil disimpan.";
        $alert = "success";
    } catch (Exception $e) {
        $message = "❌ Gagal menyimpan data: " . $e->getMessage();
        $alert = "danger";
    }
}

$selectedShiftCode = $_POST['shift'] ?? null;
$shiftIntervals = [];
if ($selectedShiftCode) {
    $shiftIntervals = getShiftIntervals($pdo, $selectedShiftCode);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Daily Report – Machine Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --navy: #0f1f38;
            --navy2: #162844;
            --accent: #f59e0b;
            --accent2: #fbbf24;
            --surface: #ffffff;
            --bg: #eef1f6;
            --border: #d0d7e3;
            --text: #1e293b;
            --muted: #64748b;
            --danger: #ef4444;
            --success: #10b981;
            --label-size: 0.7rem;
            --input-size: 0.82rem;
            --row-gap: 6px;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            overflow: hidden;
            background: var(--bg);
            font-family: 'IBM Plex Sans', sans-serif;
            color: var(--text);
        }

        /* ── WRAPPER: fills viewport exactly ── */
        .page-wrap {
            display: flex;
            flex-direction: column;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        /* ── HEADER ── */
        .app-header {
            background: var(--navy);
            color: #fff;
            padding: 8px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            border-bottom: 3px solid var(--accent);
        }

        .app-header .logo {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .app-header .logo-title {
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            line-height: 1.2;
        }

        .app-header .logo-sub {
            font-size: 0.68rem;
            color: #94a3b8;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .app-header .date-badge {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.75rem;
            background: var(--navy2);
            border: 1px solid #2a3f5f;
            color: var(--accent);
            padding: 4px 10px;
            border-radius: 6px;
            letter-spacing: 0.05em;
        }

        /* ── ALERT STRIP ── */
        .alert-strip {
            flex-shrink: 0;
            padding: 0 16px;
        }

        .alert-strip .alert {
            margin: 6px 0 0;
            padding: 6px 12px;
            font-size: 0.78rem;
            border-radius: 6px;
        }

        /* ── FORM BODY ── */
        .form-body {
            flex: 1;
            overflow-y: auto;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding: 10px 16px 10px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        /* Section label */
        .section-bar {
            background: var(--navy);
            color: var(--accent);
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 2px;
        }

        /* Grid rows */
        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 8px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .grid-4 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 8px;
        }

        .grid-5 {
            display: grid;
            grid-template-columns: 1.4fr 1.4fr 1fr 1fr 1.2fr;
            gap: 8px;
        }

        .col-span-2 {
            grid-column: span 2;
        }

        .col-span-3 {
            grid-column: span 3;
        }

        /* Field */
        .field {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .field label {
            font-size: var(--label-size);
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
        }

        .field label .req {
            color: var(--danger);
        }

        .field input,
        .field select {
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: var(--input-size);
            padding: 5px 8px;
            border: 1.5px solid var(--border);
            border-radius: 6px;
            background: #fff;
            color: var(--text);
            width: 100%;
            height: 32px;
            transition: border-color 0.15s;
            outline: none;
        }

        .field input:focus,
        .field select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.15);
        }

        .field input[readonly] {
            background: #f1f5f9;
            color: var(--muted);
            cursor: default;
        }

        /* Shift interval info inline */
        .interval-info {
            font-size: 0.65rem;
            color: var(--muted);
            margin-top: 2px;
            line-height: 1.4;
        }

        /* Performance display */
        .perf-display {
            background: var(--navy);
            border: none;
            color: var(--accent);
            font-family: 'IBM Plex Mono', monospace;
            font-size: 1rem !important;
            font-weight: 700;
            text-align: center;
            height: 32px !important;
            letter-spacing: 0.05em;
        }

        /* ── FOOTER / BUTTONS ── */
        .form-footer {
            flex-shrink: 0;
            padding: 8px 16px 10px;
            display: flex;
            gap: 10px;
            align-items: center;
            border-top: 1px solid var(--border);
            background: #fff;
        }

        .form-inner {
            min-width: 700px;
            /* ← lebar minimum sebelum mulai bisa geser */
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .btn-save {
            background: var(--accent);
            color: var(--navy);
            border: none;
            font-weight: 700;
            font-size: 0.82rem;
            padding: 8px 28px;
            border-radius: 7px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-save:hover {
            background: var(--accent2);
        }

        .btn-reset {
            background: transparent;
            color: var(--muted);
            border: 1.5px solid var(--border);
            font-size: 0.78rem;
            padding: 7px 18px;
            border-radius: 7px;
            cursor: pointer;
            text-decoration: none;
            transition: border-color 0.15s;
        }

        .btn-reset:hover {
            border-color: var(--navy);
            color: var(--navy);
        }

        /* Divider line between sections */
        .section-divider {
            border: none;
            border-top: 1px dashed var(--border);
            margin: 0;
        }

        /* Plan Box Styling */
        .plan-box {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            width: 85px !important;
            padding: 10px 6px !important;
            background: #fff !important;
            border: 2px solid #e2e8f0 !important;
            border-radius: 10px !important;
            font-family: 'IBM Plex Mono', monospace !important;
            font-size: 0.82rem !important;
            font-weight: 600 !important;
            text-align: center !important;
            transition: all 0.2s ease !important;
            cursor: pointer !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .plan-box:hover {
            border-color: #f59e0b !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 16px rgba(245, 158, 11, 0.2) !important;
        }

        .plan-box input {
            width: 100% !important;
            border: none !important;
            background: transparent !important;
            text-align: center !important;
            font-weight: 700 !important;
            color: #1e293b !important;
            height: 32px !important;
            font-size: 1rem !important;
        }

        .plan-box input:focus {
            outline: none !important;
            color: #f59e0b !important;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2) !important;
        }
    </style>
</head>

<body>
    <div class="page-wrap">

        <!-- HEADER -->
        <div class="app-header">
            <div class="logo">
                <div class="logo-title">Daily Report &mdash; Machine Shop</div>
                <div class="logo-sub">PT. Yanmar Diesel Indonesia</div>
            </div>
            <div class="date-badge" id="liveClock">--:--:--</div>
        </div>

        <!-- ALERT STRIP -->
        <?php if ($message): ?>
            <div class="alert-strip">
                <div class="alert alert-<?= $alert ?> d-flex justify-content-between align-items-center py-1 px-3">
                    <span><?= $message ?></span>
                    <a href="?cleared=1" class="btn btn-sm btn-outline-secondary py-0 px-2 ms-3"
                        style="font-size:0.7rem">✕</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- FORM BODY -->
        <form id="form-input" action="" method="post" autocomplete="off" class="form-body">
            <div class="form-inner">
                <!-- ROW 1: Identitas Dasar -->
                <div>
                    <span class="section-bar">Identitas</span>
                </div>
                <div class="grid-3">
                    <div class="field">
                        <label>Tanggal <span class="req">*</span></label>
                        <input type="date" name="waktu" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="field">
                        <label>Line <span class="req">*</span></label>
                        <select name="line" id="lineSel" onchange="loadParts()" required>
                            <option value="">Pilih Line</option>
                            <?php
                            $stmt = $pdo->prepare("SELECT id, code, label FROM line ORDER BY id");
                            $stmt->execute();
                            while ($row = $stmt->fetch()) {
                                echo "<option value='{$row['code']}'>{$row['code']} – {$row['label']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>PIC / NIK <span class="req">*</span></label>
                        <select name="nik" id="nikSel" required>
                            <option value="">Pilih NIK</option>
                            <?php
                            $stmt = $pdo->prepare("SELECT nik, full_name FROM employees WHERE is_active = 1 ORDER BY full_name");
                            $stmt->execute();
                            while ($row = $stmt->fetch()) {
                                echo "<option value='{$row['nik']}'>{$row['full_name']} ({$row['nik']})</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <hr class="section-divider">

                <!-- ROW 2: Part & Shift -->
                <div>
                    <span class="section-bar">Part & Shift</span>
                </div>
                <div class="grid-3">
                    <div class="field">
                        <label>Part Number <span class="req">*</span></label>
                        <select name="part_number" id="partSel" onchange="updateModelAndCycle()" required>
                            <option value="">Pilih Part Number</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Model / Finish</label>
                        <input type="text" name="model" id="model" placeholder="Otomatis terisi" readonly>
                        <input type="hidden" id="partId" name="part_id">
                    </div>
                    <div class="field">
                        <label>Jumlah Operator <span class="req">*</span></label>
                        <input type="number" name="operator_count" id="operatorCount" min="1" value="1" step="1"
                            required>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="field">
                        <label>Shift <span class="req">*</span></label>
                        <select name="shift" id="shiftSel" onchange="handleShiftChange()" required>
                            <option value="">Pilih Shift</option>
                            <?php
                            $stmt = $pdo->prepare("SELECT code, label FROM shift_types ORDER BY code");
                            $stmt->execute();
                            while ($row = $stmt->fetch()) {
                                $selected = $selectedShiftCode === $row['code'] ? 'selected' : '';
                                echo "<option value='{$row['code']}' $selected>{$row['code']} – {$row['label']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Time Interval</label>
                        <select name="selected_interval_seq" id="intervalSel">
                            <option value="">-- Pilih Sesi --</option>
                            <?php foreach ($shiftIntervals as $iv): ?>
                                <option value="<?= $iv['interval_seq'] ?>">
                                    Sesi <?= $iv['interval_seq'] ?> (<?= $iv['start_time'] ?>–<?= $iv['end_time'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="interval-info" id="intervalInfo">
                            <?php foreach ($shiftIntervals as $iv): ?>
                                <span>S<?= $iv['interval_seq'] ?>: <?= $iv['start_time'] ?>–<?= $iv['end_time'] ?>
                                    &nbsp;</span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="field">
                        <!-- spacer -->
                    </div>
                </div>

                <!-- 🚀 BULK PLAN PER KOTAK (Dynamic berdasarkan shift) -->
                <div class="grid-3">
                    <div class="field col-span-3">
                        <label>📊 <strong>Plan Per Sesi</strong> <span id="sessionCount">(Pilih shift
                                dulu)</span></label>

                        <!-- Dynamic input boxes container -->
                        <div id="bulkPlanInputs"
                            style="display:flex;flex-wrap:wrap;gap:6px;padding:12px;border:2px dashed #d0d7e3;border-radius:12px;background:#f8fafc;min-height:60px;align-items:center;">
                            <!-- Auto-generated berdasarkan shift intervals -->
                        </div>

                        <small class="interval-info" style="margin-top:6px;color:#64748b;font-weight:500;">
                            <strong>✨ Input plan tiap sesi secara visual! Total otomatis terhitung</strong>
                        </small>
                    </div>
                </div>

                <hr class="section-divider">

                <!-- ROW 3: Produksi (Updated Plan Label) -->
                <div>
                    <span class="section-bar">Data Produksi</span>
                </div>
                <div class="grid-5">
                    <div class="field">
                        <label>Plan Qty <span class="req">*</span> <small style="color:#10b981;">(Auto dari
                                Bulk)</small></label>
                        <input type="number" name="plan" id="plan" min="1" value="10" step="1" required>
                    </div>
                    <div class="field">
                        <label>Cycle Time (dtk) <span class="req">*</span></label>
                        <input type="number" name="cycle_time" id="cycle_time" placeholder="detik" step="0.01"
                            min="0.01" required>
                    </div>
                    <div class="field">
                        <label>Actual Qty <span class="req">*</span></label>
                        <input type="number" name="actual" id="actual" value="0" step="1" required>
                    </div>
                    <div class="field">
                        <label>NG Machining</label>
                        <input type="number" name="ng_by_machining" id="ng_machining" value="0" step="1">
                    </div>
                    <div class="field">
                        <label>NG Material</label>
                        <input type="number" name="ng_by_material" id="ng_material" value="0" step="1">
                    </div>
                </div>

                <hr class="section-divider">

                <!-- ROW 4: Stoptime + Performance -->
                <div>
                    <span class="section-bar">Stop Time & Performance</span>
                </div>
                <div class="grid-3">
                    <div class="field">
                        <label>Stop Time (menit)</label>
                        <input type="number" name="stoptime_min" id="stoptime_min" value="0" step="1">
                    </div>
                    <div class="field">
                        <label>Classification Stoptime</label>
                        <select name="classification_stoptime" id="classificationStoptime">
                            <option value="">-- Pilih Klasifikasi --</option>
                            <option value="tool_problem">Tool Problem</option>
                            <option value="machining_problem">Machining Problem</option>
                            <option value="setting_tool">Setting Tool</option>
                            <option value="material_problem">Material Problem</option>
                            <option value="ganti_model">Ganti Model</option>
                            <option value="quality_check">Quality Check</option>
                            <option value="stop_by_management">Stop By Management</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Performance</label>
                        <input type="text" id="performance" class="perf-display" placeholder="0 %" readonly>
                    </div>
                </div>

        </form>

        <!-- FOOTER BUTTONS -->
        <div class="form-footer">
            <button type="submit" form="form-input" class="btn-save">&#10003; Simpan Data</button>
            <a href="?" class="btn-reset">&#8634; Bersihkan Form</a>
            <span style="margin-left:auto;font-size:0.68rem;color:var(--muted);font-family:'IBM Plex Mono',monospace;"
                id="statusBar">Siap input data</span>
        </div>

    </div><!-- end page-wrap -->

    </div>

    <script>
        // Live clock
        function updateClock() {
            const now = new Date();
            const t = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            document.getElementById('liveClock').textContent = t;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Shift schedule AJAX
        function updateShiftSchedule() {
            const code = document.getElementById('shiftSel').value;
            const intervalSel = document.getElementById('intervalSel');
            const infoDiv = document.getElementById('intervalInfo');

            if (!code) {
                intervalSel.innerHTML = '<option value="">-- Pilih Sesi --</option>';
                infoDiv.innerHTML = '';
                return;
            }

            fetch('<?= $_SERVER['PHP_SELF'] ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'shift=' + encodeURIComponent(code)
            })
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    // Extract interval options from parsed page
                    const srcSel = doc.querySelector('#intervalSel');
                    if (srcSel) intervalSel.innerHTML = srcSel.innerHTML;

                    const srcInfo = doc.querySelector('#intervalInfo');
                    if (srcInfo) infoDiv.innerHTML = srcInfo.innerHTML;
                })
                .catch(() => {
                    infoDiv.innerHTML = '<span style="color:red">Gagal memuat jadwal shift.</span>';
                });
        }

        // Load parts by line
        function loadParts() {
            const lineCode = document.getElementById('lineSel').value;
            const partSel = document.getElementById('partSel');
            if (!lineCode) {
                partSel.innerHTML = '<option value="">Pilih Line dulu</option>';
                resetPartFields();
                return;
            }
            partSel.innerHTML = '<option value="" disabled>Memuat...</option>';
            fetch('<?= $_SERVER['PHP_SELF'] ?>?ajax_parts=1&line_code=' + lineCode)
                .then(r => r.json())
                .then(data => {
                    partSel.innerHTML = '<option value="">Pilih Part Number</option>';
                    if (!data.length) partSel.innerHTML += '<option disabled>Tidak ada part</option>';
                    data.forEach(p => {
                        const o = document.createElement('option');
                        o.value = p.part_number;
                        o.text = `${p.part_number} (${p.finish})`;
                        o.dataset.partId = p.id;
                        o.dataset.finish = p.finish;
                        o.dataset.cycleTime = p.cycle_time_sec || 0;
                        partSel.appendChild(o);
                    });
                    resetPartFields();
                })
                .catch(() => partSel.innerHTML = '<option value="">Gagal memuat</option>');
        }

        function resetPartFields() {
            document.getElementById('model').value = '';
            document.getElementById('cycle_time').value = '';
            document.getElementById('partId').value = '';
            updatePerformance();
        }

        function updateModelAndCycle() {
            const partSel = document.getElementById('partSel');
            const opt = partSel.options[partSel.selectedIndex];
            if (!opt || !opt.dataset.partId) { resetPartFields(); return; }
            document.getElementById('model').value = opt.dataset.finish || '';
            document.getElementById('cycle_time').value = opt.dataset.cycleTime;
            document.getElementById('partId').value = opt.dataset.partId;
            updatePerformance();
        }

        function updatePerformance() {
            const plan = parseFloat(document.getElementById('plan').value) || 0;
            const actual = parseFloat(document.getElementById('actual').value) || 0;
            const perf = plan > 0 ? Math.round((actual / plan) * 10000) / 100 : 0;
            document.getElementById('performance').value = perf + ' %';
            const bar = document.getElementById('statusBar');
            if (plan > 0) bar.textContent = `Performance: ${perf}% ${perf >= 100 ? '✓ Target Tercapai' : '⚠ Belum Target'}`;
        }

        // Stoptime → classification wajib jika > 0
        document.getElementById('stoptime_min').addEventListener('input', function () {
            const sel = document.getElementById('classificationStoptime');
            if (parseInt(this.value) > 0) {
                sel.setAttribute('required', 'required');
            } else {
                sel.removeAttribute('required');
                sel.value = '';
            }
        });

        document.getElementById('actual').addEventListener('input', updatePerformance);
        document.getElementById('plan').addEventListener('input', updatePerformance);

        document.addEventListener('DOMContentLoaded', updatePerformance);

        // 🔥 DYNAMIC BULK PLAN PER KOTAK
        function generatePlanInputs(intervals) {
            const container = document.getElementById('bulkPlanInputs');
            const planInput = document.getElementById('plan');
            const sessionCount = document.getElementById('sessionCount');

            container.innerHTML = '';

            if (intervals.length === 0) {
                container.innerHTML = '<div style="color:#94a3b8;font-style:italic;padding:16px;text-align:center;width:100%;">👆 Pilih shift untuk melihat sesi plan</div>';
                sessionCount.textContent = '(Pilih shift dulu)';
                return;
            }

            let totalPlan = 0;
            sessionCount.textContent = `(${intervals.length} sesi)`;

            intervals.forEach((interval) => {
                const sesiNum = interval.interval_seq;
                const timeRange = `${interval.start_time.substring(0, 5)}–${interval.end_time.substring(0, 5)}`;

                // Default plan 250, nanti bisa load dari DB
                const defaultPlan = 250;

                const planBox = document.createElement('div');
                planBox.className = 'plan-box';
                planBox.innerHTML = `
            <div style="font-size:0.7rem;color:#64748b;margin-bottom:4px;height:26px;line-height:1.2;overflow:hidden;font-weight:500;">
                S${sesiNum}
            </div>
            <input type="number" 
                   data-sesi="${sesiNum}" 
                   value="${defaultPlan}" 
                   min="0" max="999" step="5"
                   onchange="updateTotalPlan()"
                   onfocus="this.parentElement.style.borderColor='#f59e0b'"
                   onblur="this.parentElement.style.borderColor='#e2e8f0'">
            <div style="font-size:0.6rem;color:#94a3b8;margin-top:2px;height:20px;">
                ${timeRange}
            </div>
        `;

                totalPlan += defaultPlan;
                container.appendChild(planBox);
            });

            // Update total
            planInput.value = totalPlan;
            updatePerformance();
            document.getElementById('statusBar').innerHTML =
                `<span style="color:#10b981;">✅ ${intervals.length} sesi ready | Total: <strong>${totalPlan.toLocaleString()}</strong> pcs</span>`;
        }

        // Update total plan
        function updateTotalPlan() {
            const inputs = document.querySelectorAll('#bulkPlanInputs input[data-sesi]');
            let totalPlan = 0;

            inputs.forEach(input => {
                totalPlan += parseInt(input.value) || 0;
            });

            const planInput = document.getElementById('plan');
            planInput.value = totalPlan;
            updatePerformance();

            const statusBar = document.getElementById('statusBar');
            statusBar.innerHTML = `
        <span style="color:#10b981;font-weight:600;">
            ✅ Total Plan: <strong>${totalPlan.toLocaleString()}</strong> pcs 
            (${inputs.length} sesi)
        </span>
    `;
        }
        // ✅ FUNGSI BARU 1: Handle shift change
        function handleShiftChange() {
            updateShiftSchedule(); // AJAX original

            setTimeout(() => {
                const intervalOptions = Array.from(document.querySelectorAll('#intervalSel option'));
                const intervals = intervalOptions.slice(1).map(opt => ({
                    interval_seq: parseInt(opt.value),
                    start_time: opt.text.match(/(\d{2}:\d{2})/)?.[1] || '00:00',
                    end_time: opt.text.match(/–(\d{2}:\d{2})/)?.[1] || '00:00'
                })).filter(i => i.interval_seq > 0);

                generatePlanInputs(intervals);
            }, 200);
        }

        // ✅ FUNGSI BARU 2: Fix plan input untuk form submit
        function generatePlanInputs(intervals) {
            const container = document.getElementById('bulkPlanInputs');
            const planInput = document.getElementById('plan');
            const sessionCount = document.getElementById('sessionCount');

            container.innerHTML = '';

            if (intervals.length === 0) {
                container.innerHTML = '<div style="color:#94a3b8;font-style:italic;padding:16px;text-align:center;width:100%;">👆 Pilih shift untuk melihat sesi plan</div>';
                sessionCount.textContent = '(Pilih shift dulu)';
                return;
            }

            let totalPlan = 0;
            sessionCount.textContent = `(${intervals.length} sesi)`;

            intervals.forEach((interval) => {
                const sesiNum = interval.interval_seq;
                const timeRange = `${interval.start_time.substring(0, 5)}–${interval.end_time.substring(0, 5)}`;
                const defaultPlan = 250;

                const planBox = document.createElement('div');
                planBox.className = 'plan-box';
                planBox.innerHTML = `
            <div style="font-size:0.7rem;color:#64748b;margin-bottom:4px;height:26px;line-height:1.2;overflow:hidden;font-weight:500;">
                S${sesiNum}
            </div>
            <input type="number" name="plan_sesi[]" 
                   data-sesi="${sesiNum}" value="${defaultPlan}" 
                   min="0" max="999" step="5"
                   onchange="updateTotalPlan()"
                   onfocus="this.parentElement.style.borderColor='#f59e0b'"
                   onblur="this.parentElement.style.borderColor='#e2e8f0'">
            <div style="font-size:0.6rem;color:#94a3b8;margin-top:2px;height:20px;">
                ${timeRange}
            </div>
        `;

                totalPlan += defaultPlan;
                container.appendChild(planBox);
            });

            planInput.value = totalPlan;
            updatePerformance();
            document.getElementById('statusBar').innerHTML =
                `<span style="color:#10b981;">✅ ${intervals.length} sesi ready | Total: <strong>${totalPlan.toLocaleString()}</strong> pcs</span>`;
        }
    </script>
</body>

</html>