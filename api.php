<?php
// api.php - Main API Router

require_once 'config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Get request parameters
    $action = $_GET['action'] ?? '';
    $lineCode = $_GET['line'] ?? 'CR2';
    $shift = $_GET['shift'] ?? '2';
    $date = $_GET['date'] ?? date('Y-m-d');
    $view = $_GET['view'] ?? 'today'; // today or week
    
    switch ($action) {
        case 'get_lines':
            getLines($db);
            break;
            
        case 'get_shifts':
            getShifts($db);
            break;
            
        case 'get_kpis':
            getKPIs($db, $lineCode, $shift, $date);
            break;
            
        case 'get_info':
            getInfo($db, $lineCode, $shift, $date);
            break;
            
        case 'get_production_table':
            getProductionTable($db, $lineCode, $shift, $date, $view);
            break;
            
        case 'get_progress_chart':
            getProgressChart($db, $lineCode, $shift, $date, $view);
            break;
            
        case 'get_ng_types':
            getNGTypes($db, $lineCode, $shift, $date);
            break;
            
        case 'get_user':
            getUser($db, $lineCode, $shift);
            break;
            
        case 'get_all_data':
            getAllData($db, $lineCode, $shift, $date, $view);
            break;
            
        default:
            sendError('Invalid action', 400);
    }
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    sendError('Internal server error: ' . $e->getMessage(), 500);
}

// ============================================================
// API FUNCTIONS
// ============================================================

function getLines($db) {
    $sql = "SELECT id, code, label FROM line WHERE is_active = 1 ORDER BY id";
    $stmt = $db->query($sql);
    $lines = $stmt->fetchAll();
    
    $result = array_map(function($line) {
        return [
            'id' => $line['code'],
            'label' => $line['label']
        ];
    }, $lines);
    
    sendResponse(['success' => true, 'data' => $result]);
}

function getShifts($db) {
    $sql = "SELECT id, code, label FROM shift_types 
            WHERE is_overtime = 0 AND is_long = 0 AND is_friday = 0
            ORDER BY id LIMIT 3";
    $stmt = $db->query($sql);
    $shifts = $stmt->fetchAll();
    
    $result = array_map(function($shift) {
        return [
            'id' => str_replace('S', '', $shift['code']),
            'label' => $shift['label']
        ];
    }, $shifts);
    
    sendResponse(['success' => true, 'data' => $result]);
}

function getKPIs($db, $lineCode, $shift, $date) {
    // Get production data for the day
    $sql = "SELECT 
                SUM(plan) as total_plan,
                SUM(actual) as total_actual,
                SUM(ng_by_machining + ng_by_material) as total_ng,
                SUM(stoptime_min) as total_stoptime,
                AVG(performance_pct) as avg_performance
            FROM production_dashboard_input
            WHERE line = :line 
            AND shift = :shift 
            AND waktu = :date";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        'line' => $lineCode,
        'shift' => 'S' . $shift,
        'date' => $date
    ]);
    
    $data = $stmt->fetch();
    
    // Calculate values
    $totalProduction = intval($data['total_actual'] ?? 0);
    $totalNg = intval($data['total_ng'] ?? 0);
    $goodProduct = $totalProduction - $totalNg;
    $performance = round($data['avg_performance'] ?? 0, 1);
    $stopTime = intval($data['total_stoptime'] ?? 0);
    
    // Get previous day for comparison
    $prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
    $sqlPrev = "SELECT 
                    SUM(actual) as total_actual,
                    SUM(ng_by_machining + ng_by_material) as total_ng,
                    AVG(performance_pct) as avg_performance,
                    SUM(stoptime_min) as total_stoptime
                FROM production_dashboard_input
                WHERE line = :line 
                AND shift = :shift 
                AND waktu = :date";
    
    $stmtPrev = $db->prepare($sqlPrev);
    $stmtPrev->execute([
        'line' => $lineCode,
        'shift' => 'S' . $shift,
        'date' => $prevDate
    ]);
    
    $prevData = $stmtPrev->fetch();
    
    // Calculate changes
    $prevTotal = intval($prevData['total_actual'] ?? 1);
    $prevNg = intval($prevData['total_ng'] ?? 1);
    $prevPerf = floatval($prevData['avg_performance'] ?? 1);
    $prevStop = intval($prevData['total_stoptime'] ?? 1);
    
    $totalChange = $prevTotal > 0 ? round((($totalProduction - $prevTotal) / $prevTotal) * 100, 1) : 0;
    $ngChange = $prevNg > 0 ? round((($totalNg - $prevNg) / $prevNg) * 100, 1) : 0;
    $perfChange = $prevPerf > 0 ? round($performance - $prevPerf, 1) : 0;
    $stopChange = $stopTime - $prevStop;
    
    $kpis = [
        'total' => [
            'value' => $totalProduction,
            'badge' => ($totalChange >= 0 ? '+' : '') . $totalChange . '%',
            'type' => $totalChange >= 0 ? 'pos' : 'neg'
        ],
        'good' => [
            'value' => $goodProduct,
            'badge' => ($totalChange >= 0 ? '+' : '') . $totalChange . '%',
            'type' => $totalChange >= 0 ? 'pos' : 'neg'
        ],
        'ng' => [
            'value' => $totalNg,
            'badge' => ($ngChange >= 0 ? '+' : '') . $ngChange . '%',
            'type' => $ngChange <= 0 ? 'pos' : 'neg'
        ],
        'perf' => [
            'value' => $performance . '%',
            'badge' => ($perfChange >= 0 ? '+' : '') . $perfChange . '%',
            'type' => $perfChange >= 0 ? 'pos' : 'neg'
        ],
        'stop' => [
            'value' => $stopTime . ' min',
            'badge' => ($stopChange >= 0 ? '+' : '') . $stopChange . 'm',
            'type' => $stopChange <= 0 ? 'pos' : 'neg'
        ]
    ];
    
    sendResponse(['success' => true, 'data' => $kpis]);
}

function getInfo($db, $lineCode, $shift, $date) {
    // Get the most recent production entry for this line/shift/date
    $sql = "SELECT 
                pdi.cycle_time,
                pdi.model,
                pdi.part_number,
                COUNT(DISTINCT pdi.nik) as operator_count
            FROM production_dashboard_input pdi
            WHERE pdi.line = :line 
            AND pdi.shift = :shift 
            AND pdi.waktu = :date
            GROUP BY pdi.model, pdi.part_number, pdi.cycle_time
            LIMIT 1";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        'line' => $lineCode,
        'shift' => 'S' . $shift,
        'date' => $date
    ]);
    
    $data = $stmt->fetch();
    
    if ($data) {
        $info = [
            'cycleTime' => round($data['cycle_time']) . ' sec',
            'model' => $data['model'],
            'partNo' => $data['part_number'],
            'operators' => $data['operator_count'] . ' Orang'
        ];
    } else {
        $info = [
            'cycleTime' => '— sec',
            'model' => '—',
            'partNo' => '—',
            'operators' => '— Orang'
        ];
    }
    
    sendResponse(['success' => true, 'data' => $info]);
}

function getProductionTable($db, $lineCode, $shift, $date, $view) {
    if ($view === 'today') {
        // Get hourly data for today
        $sql = "SELECT 
                    sti.start_time,
                    sti.end_time,
                    sti.description,
                    sti.interval_seq,
                    COALESCE(SUM(pdi.plan), 0) as plan,
                    COALESCE(SUM(pdi.actual), 0) as good,
                    COALESCE(SUM(pdi.ng_by_machining + pdi.ng_by_material), 0) as ng,
                    COALESCE(SUM(pdi.stoptime_min), 0) as stoptime,
                    MAX(pdi.classification_stoptime) as stop_type
                FROM shift_time_intervals sti
                INNER JOIN shift_types st ON sti.shift_type_id = st.id
                LEFT JOIN production_dashboard_input pdi ON 
                    pdi.interval_seq = sti.interval_seq 
                    AND pdi.shift = st.code
                    AND pdi.line = :line
                    AND pdi.waktu = :date
                WHERE st.code = :shift
                GROUP BY sti.interval_seq, sti.start_time, sti.end_time, sti.description
                ORDER BY sti.interval_seq";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'line' => $lineCode,
            'shift' => 'S' . $shift,
            'date' => $date
        ]);
        
        $rows = $stmt->fetchAll();
        
        $result = array_map(function($row) {
            $plan = intval($row['plan']);
            $good = intval($row['good']);
            $ng = intval($row['ng']);
            
            return [
                'time' => substr($row['start_time'], 0, 5) . ' – ' . substr($row['end_time'], 0, 5),
                'plan' => $plan,
                'good' => $good,
                'ng' => $ng,
                'stop' => $row['stoptime'] > 0 ? classifyStopTime($row['stop_type']) : '—',
                'stopType' => $row['stoptime'] > 0 ? getStopTypeBadge($row['stop_type']) : 'none'
            ];
        }, $rows);
        
    } else {
        // Weekly view - get daily aggregates
        $startDate = date('Y-m-d', strtotime($date . ' -6 days'));
        $endDate = $date;
        
        $sql = "SELECT 
                    waktu,
                    SUM(plan) as plan,
                    SUM(actual) as good,
                    SUM(ng_by_machining + ng_by_material) as ng,
                    SUM(stoptime_min) as stoptime,
                    MAX(classification_stoptime) as stop_type
                FROM production_dashboard_input
                WHERE line = :line 
                AND shift = :shift 
                AND waktu BETWEEN :start_date AND :end_date
                GROUP BY waktu
                ORDER BY waktu";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'line' => $lineCode,
            'shift' => 'S' . $shift,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        $rows = $stmt->fetchAll();
        
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        $result = array_map(function($row) use ($daysOfWeek) {
            $dayName = $daysOfWeek[date('N', strtotime($row['waktu'])) - 1];
            $plan = intval($row['plan']);
            $good = intval($row['good']);
            $ng = intval($row['ng']);
            
            return [
                'time' => $dayName,
                'plan' => $plan,
                'good' => $good,
                'ng' => $ng,
                'stop' => $row['stoptime'] > 0 ? classifyStopTime($row['stop_type']) : '—',
                'stopType' => $row['stoptime'] > 0 ? getStopTypeBadge($row['stop_type']) : 'none'
            ];
        }, $rows);
    }
    
    sendResponse(['success' => true, 'data' => $result]);
}

function getProgressChart($db, $lineCode, $shift, $date, $view) {
    if ($view === 'today') {
        // Get hourly progress
        $sql = "SELECT 
                    sti.start_time,
                    sti.end_time,
                    sti.interval_seq,
                    COALESCE(SUM(pdi.plan), 0) as plan,
                    COALESCE(SUM(pdi.actual), 0) as actual
                FROM shift_time_intervals sti
                INNER JOIN shift_types st ON sti.shift_type_id = st.id
                LEFT JOIN production_dashboard_input pdi ON 
                    pdi.interval_seq = sti.interval_seq 
                    AND pdi.shift = st.code
                    AND pdi.line = :line
                    AND pdi.waktu = :date
                WHERE st.code = :shift
                GROUP BY sti.interval_seq, sti.start_time, sti.end_time
                ORDER BY sti.interval_seq";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'line' => $lineCode,
            'shift' => 'S' . $shift,
            'date' => $date
        ]);
        
        $rows = $stmt->fetchAll();
        
        $labels = [];
        $planData = [];
        $actualData = [];
        
        foreach ($rows as $row) {
            $labels[] = substr($row['start_time'], 0, 5) . ' – ' . substr($row['end_time'], 0, 5);
            $planData[] = intval($row['plan']);
            $actualData[] = intval($row['actual']);
        }
        
        $result = [
            'labels' => $labels,
            'plan' => $planData,
            'actual' => $actualData
        ];
        
    } else {
        // Weekly view
        $startDate = date('Y-m-d', strtotime($date . ' -6 days'));
        $endDate = $date;
        
        $sql = "SELECT 
                    waktu,
                    SUM(plan) as plan,
                    SUM(actual) as actual
                FROM production_dashboard_input
                WHERE line = :line 
                AND shift = :shift 
                AND waktu BETWEEN :start_date AND :end_date
                GROUP BY waktu
                ORDER BY waktu";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'line' => $lineCode,
            'shift' => 'S' . $shift,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        $rows = $stmt->fetchAll();
        
        $daysShort = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $labels = [];
        $planData = [];
        $actualData = [];
        
        foreach ($rows as $row) {
            $dayIndex = date('N', strtotime($row['waktu'])) - 1;
            $labels[] = $daysShort[$dayIndex];
            $planData[] = intval($row['plan']);
            $actualData[] = intval($row['actual']);
        }
        
        $result = [
            'labels' => $labels,
            'plan' => $planData,
            'actual' => $actualData
        ];
    }
    
    sendResponse(['success' => true, 'data' => $result]);
}

function getNGTypes($db, $lineCode, $shift, $date) {
    // Get NG by machining
    $sqlMach = "SELECT 
                    DATE_FORMAT(created_at, '%H:%i') as time,
                    ng_by_machining as qty
                FROM production_dashboard_input
                WHERE line = :line 
                AND shift = :shift 
                AND waktu = :date
                AND ng_by_machining > 0
                ORDER BY created_at DESC
                LIMIT 5";
    
    $stmtMach = $db->prepare($sqlMach);
    $stmtMach->execute([
        'line' => $lineCode,
        'shift' => 'S' . $shift,
        'date' => $date
    ]);
    
    $machining = $stmtMach->fetchAll();
    
    // Get NG by material
    $sqlMat = "SELECT 
                    DATE_FORMAT(created_at, '%H:%i') as time,
                    ng_by_material as qty
                FROM production_dashboard_input
                WHERE line = :line 
                AND shift = :shift 
                AND waktu = :date
                AND ng_by_material > 0
                ORDER BY created_at DESC
                LIMIT 5";
    
    $stmtMat = $db->prepare($sqlMat);
    $stmtMat->execute([
        'line' => $lineCode,
        'shift' => 'S' . $shift,
        'date' => $date
    ]);
    
    $material = $stmtMat->fetchAll();
    
    $result = [
        'machining' => array_map(function($row) {
            return [
                'time' => $row['time'],
                'qty' => $row['qty'] . ' Qty'
            ];
        }, $machining),
        'material' => array_map(function($row) {
            return [
                'time' => $row['time'],
                'qty' => $row['qty'] . ' Qty'
            ];
        }, $material)
    ];
    
    sendResponse(['success' => true, 'data' => $result]);
}

function getUser($db, $lineCode, $shift) {
    // Get a random active user for demo purposes
    // In production, this would get the logged-in user
    $sql = "SELECT nik, full_name FROM employees WHERE is_active = 1 ORDER BY RAND() LIMIT 1";
    $stmt = $db->query($sql);
    $user = $stmt->fetch();
    
    if ($user) {
        $result = [
            'role' => 'OPERATOR',
            'name' => $user['nik']
        ];
    } else {
        $result = [
            'role' => 'USER',
            'name' => '—'
        ];
    }
    
    sendResponse(['success' => true, 'data' => $result]);
}

function getAllData($db, $lineCode, $shift, $date, $view) {
    // Aggregate function to get all data at once
    $data = [
        'lines' => [],
        'shifts' => [],
        'user' => [],
        'kpis' => [],
        'info' => [],
        'ngTypes' => []
    ];
    
    // Get lines
    $sql = "SELECT id, code, label FROM line WHERE is_active = 1 ORDER BY id";
    $stmt = $db->query($sql);
    $lines = $stmt->fetchAll();
    $data['lines'] = array_map(function($line) {
        return ['id' => $line['code'], 'label' => $line['label']];
    }, $lines);
    
    // Get shifts
    $sql = "SELECT id, code, label FROM shift_types 
            WHERE is_overtime = 0 AND is_long = 0 AND is_friday = 0
            ORDER BY id LIMIT 3";
    $stmt = $db->query($sql);
    $shifts = $stmt->fetchAll();
    $data['shifts'] = array_map(function($shift) {
        return ['id' => str_replace('S', '', $shift['code']), 'label' => $shift['label']];
    }, $shifts);
    
    // Get user
    $sql = "SELECT nik, full_name FROM employees WHERE is_active = 1 ORDER BY RAND() LIMIT 1";
    $stmt = $db->query($sql);
    $user = $stmt->fetch();
    $data['user'] = $user ? ['role' => 'OPERATOR', 'name' => $user['nik']] : ['role' => 'USER', 'name' => '—'];
    
    // Note: For KPIs, info, and ngTypes, we would need to call those functions
    // but since we're returning JSON, we'll add placeholders
    
    sendResponse(['success' => true, 'data' => $data]);
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function classifyStopTime($classification) {
    if (empty($classification)) return '—';
    
    $classifications = [
        'tool' => 'Tool Setting',
        'material' => 'Material',
        'machine' => 'Machine Down',
        'quality' => 'Quality Check'
    ];
    
    $lower = strtolower($classification);
    foreach ($classifications as $key => $value) {
        if (strpos($lower, $key) !== false) {
            return $value;
        }
    }
    
    return $classification;
}

function getStopTypeBadge($classification) {
    if (empty($classification)) return 'none';
                
    $lower = strtolower($classification);
    
    if (strpos($lower, 'tool') !== false) return 'tool';
    if (strpos($lower, 'material') !== false) return 'material';
    if (strpos($lower, 'machine') !== false) return 'machine';
    if (strpos($lower, 'quality') !== false) return 'quality';
    
    return 'none';
}
