<?php
// =======================
// index.php – Single File + Jam Shift + Time Interval Dropdown
// =======================

$host = '127.0.0.1';
$dbname = 'daily_report_db';
$user = 'root';  // sesuaikan
$pass = '';      // sesuaikan
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

// Cek apakah ini halaman untuk ambil parts berdasarkan line
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
    $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($parts);
    exit;
}

// Ambil jam‑jam shift dari shift_time_intervals untuk sebuah shift_type_id
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

// Proses form submit
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
    $selected_interval_seq = (int) ($_POST['selected_interval_seq'] ?? 0); // ini yang kita fokuskan

    // Ambil nama PIC dari nik
    $stmt = $pdo->prepare("SELECT full_name FROM employees WHERE nik = ?");
    $stmt->execute([$nik]);
    $row = $stmt->fetch();
    $pic = $row ? $row['full_name'] : $nik;

    // Hitung performance
    $performance = $plan > 0 ? round(($actual / $plan) * 100, 2) : 0;

    // Simpan ke dashboard
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO production_dashboard_input
             (no, pic, nik, line, model, part_number, shift, waktu, plan,
              cycle_time, actual, ng_by_machining, ng_by_material,
              stoptime_min, performance_pct, interval_seq)
             VALUES
             (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
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

// Jika ada shift yang dipilih, ambil jamnya
$selectedShiftCode = $_POST['shift'] ?? $shift ?? null;
$shiftIntervals = [];
if ($selectedShiftCode) {
    $shiftIntervals = getShiftIntervals($pdo, $selectedShiftCode);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Daily Reporrt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px 0;
        }

        .form-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px 24px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .form-label {
            font-weight: 500;
        }

        .required::after {
            content: " *";
            color: #dc3545;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
        }

        .btn-primary {
            border-radius: 8px;
        }

        /* Jam shift */
        .shift-schedule {
            margin-top: 10px;
            padding: 10px 14px;
            background: #f1f3f5;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .shift-schedule-item {
            padding: 2px 0;
        }
    </style>
</head>

<body>
    <!-- Alert setelah submit -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $alert ?> mx-3">
            <?= $message ?>
            <a href="?cleared=1" class="btn btn-sm btn-outline-secondary float-end">X</a>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <h2 class="mb-4 text-center">Daily Report Machine Shop</h2>
        <p class="text-muted text-center mb-4">
            PT. Yanmar Diesel Indonesia | Machine Shop
        </p>

        <form id="form-input" action="" method="post" autocomplete="off">

            <!-- WAKTU -->
            <div class="mb-3">
                <label class="form-label required">Tanggal</label>
                <input type="date" name="waktu" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <!-- LINE -->
            <div class="mb-3">
                <label class="form-label required">Line</label>
                <select name="line" class="form-select" id="lineSel" onchange="loadParts()" required>
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

            <!-- PIC / NIK -->
            <div class="mb-3">
                <label class="form-label required">PIC / NIK</label>
                <select name="nik" class="form-select" id="nikSel" required>
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

            <!-- PART NUMBER & MODEL -->
            <div class="mb-3">
                <label class="form-label required">Part Number</label>
                <select name="part_number" class="form-select" id="partSel" onchange="updateModelAndCycle()" required>
                    <option value="">Pilih Part Number</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Model / Finish</label>
                <input type="text" name="model" class="form-control" id="model" placeholder="Model akan otomatis terisi"
                    readonly>
                <input type="hidden" id="partId" name="part_id">
            </div>

            <!-- SHIFT + TIME INTERVAL -->
            <div class="mb-3">
                <label class="form-label required">Shift</label>
                <select name="shift" class="form-select" id="shiftSel" onchange="updateShiftSchedule()" required>
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

                <!-- Time Interval -->
                <div class="shift-schedule" id="shiftScheduleContainer">
                    <?php if ($shiftIntervals): ?>
                        <div class="mb-2">
                            <label class="form-label">Time Interval</label>
                            <select name="selected_interval_seq" class="form-select" id="intervalSel">
                                <option value="">Pilih sesi</option>
                                <?php foreach ($shiftIntervals as $interval): ?>
                                    <option value="<?= $interval['interval_seq'] ?>">
                                        Sesi <?= $interval['interval_seq'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- tampil jam asli di bawah (info) -->
                        <div class="small text-muted">
                            <?php foreach ($shiftIntervals as $interval): ?>
                                <div class="mb-1">
                                    Sesi <?= $interval['interval_seq'] ?>:
                                    <strong><?= $interval['start_time'] ?> – <?= $interval['end_time'] ?></strong>
                                    (<?= $interval['description'] ?>)
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-muted small">Pilih shift untuk melihat jam kerja.</div>
                    <?php endif; ?>
                </div>
            </div>


            <!-- JUMLAH OPERATOR -->
            <div class="mb-3">
                <label class="form-label required">Jumlah Operator</label>
                <input type="number" name="operator_count" class="form-control" id="operatorCount" min="1" value="1"
                    step="1" required>
            </div>

            <!-- PLAN & CYCLE TIME -->
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Plan Qty</label>
                        <input type="number" name="plan" class="form-control" id="plan" min="1" value="1600" step="1"
                            required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Cycle Time (detik)</label>
                        <input type="number" name="cycle_time" class="form-control" id="cycle_time"
                            placeholder="Masukkan cycle time (detik)" step="0.01" min="0.01" required>
                    </div>
                </div>
            </div>

            <!-- ACTUAL PRODUKSI -->
            <div class="mb-3">
                <label class="form-label required">Actual Qty</label>
                <input type="number" name="actual" class="form-control" id="actual" value="0" step="1" required>
            </div>

            <!-- NG -->
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">NG by Machining</label>
                        <input type="number" name="ng_by_machining" class="form-control" id="ng_machining" value="0"
                            step="1">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">NG by Material</label>
                        <input type="number" name="ng_by_material" class="form-control" id="ng_material" value="0"
                            step="1">
                    </div>
                </div>
            </div>

            <!-- STOPTIME -->
            <div class="mb-3">
                <label class="form-label">Stop Time (menit)</label>
                <input type="number" name="stoptime_min" class="form-control" id="stoptime_min" value="0" step="1">
            </div>

            <!-- Performance (readonly) -->
            <div class="mb-4">
                <label class="form-label">Performance (%)</label>
                <input type="text" class="form-control" id="performance"
                    placeholder="Performance akan otomatis dihitung" readonly>
            </div>

            <!-- Tombol simpan -->
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary btn-lg px-5">SIMPAN DATA</button>
                <a href="?" class="btn btn-outline-secondary btn-lg px-4">BERSIHKAN FORM</a>
            </div>
        </form>
    </div>

    <script>

        // Ambil jam shift dari server (AJAX), lalu isi ulang schedule
        function updateShiftSchedule() {
            const shiftSel = document.getElementById('shiftSel');
            const code = shiftSel.value;
            const container = document.getElementById('shiftScheduleContainer');

            if (!code) {
                container.innerHTML = '<div class="text-muted">Pilih shift untuk melihat jam kerja.</div>';
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
                    const newContainer = doc.querySelector('#shiftScheduleContainer');
                    if (newContainer) {
                        container.innerHTML = newContainer.innerHTML;
                    }
                })
                .catch(err => {
                    container.innerHTML = '<div class="text-danger">Gagal memuat jam shift.</div>';
                    console.error('updateShiftSchedule error:', err);
                });
        }

        // Ambil parts berdasarkan line (via AJAX)
        function loadParts() {
            const lineSel = document.getElementById('lineSel');
            const partSel = document.getElementById('partSel');
            const lineCode = lineSel.value;

            if (!lineCode) {
                partSel.innerHTML = '<option value="">Pilih Line dulu</option>';
                document.getElementById('model').value = '';
                document.getElementById('cycle_time').value = 0;
                document.getElementById('performance').value = '';
                updatePerformance(); // reset performance
                return;
            }

            partSel.innerHTML = '<option value="" disabled>Memuat...</option>';

            fetch('<?= $_SERVER['PHP_SELF'] ?>?ajax_parts=1&line_code=' + lineCode)
                .then(r => {
                    if (!r.ok) throw new Error('Gagal memuat data parts');
                    return r.json();
                })
                .then(data => {
                    partSel.innerHTML = '<option value="">Pilih Part Number</option>';
                    if (data.length === 0) {
                        partSel.innerHTML += '<option value="" disabled>Tidak ada part untuk line ini</option>';
                    }
                    data.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.part_number;
                        opt.text = `${p.part_number} (${p.finish})`;
                        opt.setAttribute('data-part-id', p.id);
                        opt.setAttribute('data-finish', p.finish);
                        opt.setAttribute('data-cycle-time', p.cycle_time_sec || 0);
                        partSel.appendChild(opt);
                    });
                    // reset part & model karena pilihan line baru
                    document.getElementById('partSel').selectedIndex = 0;
                    document.getElementById('model').value = '';
                    document.getElementById('cycle_time').value = 0;
                    document.getElementById('partId').value = '';
                    updatePerformance();
                })
                .catch(err => {
                    partSel.innerHTML = '<option value="">Gagal memuat data</option>';
                    console.error('loadParts error:', err);
                });
        }

        // Update MODEL dan CYCLE TIME dari part yang dipilih
     function updateModelAndCycle() {
    const partSel = document.getElementById('partSel');
    const opt = partSel.options[partSel.selectedIndex];
    if (!opt || !opt.hasAttribute('data-part-id')) {
        document.getElementById('model').value = '';
        document.getElementById('cycle_time').value = 0;
        document.getElementById('partId').value = '';
        updatePerformance();
        return;
    }

    const finish = opt.getAttribute('data-finish');
    const cycleTime = parseFloat(opt.getAttribute('data-cycle-time')) || 0;
    const partId = opt.getAttribute('data-part-id');

    document.getElementById('model').value = finish || '';
    document.getElementById('cycle_time').value = cycleTime;  // ini dihapus dari auto
    document.getElementById('partId').value = partId;

    updatePerformance();
}
        // Hitung dan update Performance (%) dari plan & actual
        function updatePerformance() {
            const plan = parseFloat(document.getElementById('plan').value) || 0;
            const actual = parseFloat(document.getElementById('actual').value) || 0;

            if (plan <= 0) {
                document.getElementById('performance').value = '0 %';
                return;
            }

            const perf = Math.round((actual / plan) * 10000) / 100;
            document.getElementById('performance').value = perf + ' %';
        }

        // Event listener untuk input plan & actual
        document.getElementById('actual').addEventListener('input', updatePerformance);
        document.getElementById('plan').addEventListener('input', updatePerformance);

        // Jika perlu, bisa di-trigger saat form load (misal kalau form sudah ada nilai default)
        document.addEventListener('DOMContentLoaded', () => {
            updatePerformance();
        });

    </script>
</body>

</html>