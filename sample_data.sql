-- ============================================================
-- SAMPLE DATA INSERTION QUERIES
-- Production Dashboard Database
-- ============================================================

-- Use this file to insert additional test data into your database

USE daily_report_db;

-- ============================================================
-- Insert Sample Production Data for Today
-- ============================================================

-- Line CR2, Shift 1, Today's Date
INSERT INTO production_dashboard_input 
(no, pic, nik, line, model, part_number, shift, waktu, plan, cycle_time, actual, 
 ng_by_machining, ng_by_material, stoptime_min, classification_stoptime, 
 performance_pct, interval_seq)
VALUES 
-- Interval 1: 06:30 - 08:40
('1', 'AGUS HERMAWAN', '2015039', 'CR2', '3TNV76', '119717-23001', 'S1', 
 CURDATE(), 400, 23.10, 380, 5, 3, 10, 'Tool Setting', 95.00, 1),

-- Interval 2: 08:40 - 10:40
('2', 'AGUS HERMAWAN', '2015039', 'CR2', '3TNV76', '119717-23001', 'S1', 
 CURDATE(), 400, 23.10, 395, 2, 1, 0, '', 98.75, 2),

-- Interval 3: 10:40 - 13:25
('3', 'AGUS HERMAWAN', '2015039', 'CR2', '3TNV76', '119717-23001', 'S1', 
 CURDATE(), 550, 23.10, 520, 8, 5, 15, 'Material', 94.55, 3),

-- Interval 4: 13:25 - 15:00
('4', 'AGUS HERMAWAN', '2015039', 'CR2', '3TNV76', '119717-23001', 'S1', 
 CURDATE(), 350, 23.10, 345, 1, 2, 5, 'Quality Check', 98.57, 4);

-- ============================================================
-- Insert Sample Production Data for Past Week
-- ============================================================

-- CR2, Shift 2, Last 7 Days
INSERT INTO production_dashboard_input 
(no, pic, nik, line, model, part_number, shift, waktu, plan, cycle_time, actual, 
 ng_by_machining, ng_by_material, stoptime_min, classification_stoptime, 
 performance_pct, interval_seq)
VALUES 
-- Monday
('1', 'AHMAD FAUZI', '2017084', 'CR2', '3TNV76', '119717-23001', 'S2', 
 DATE_SUB(CURDATE(), INTERVAL 6 DAY), 1600, 23.10, 1550, 15, 10, 30, 'Tool Setting', 96.88, 1),

-- Tuesday
('2', 'AHMAD FAUZI', '2017084', 'CR2', '3TNV76', '119717-23001', 'S2', 
 DATE_SUB(CURDATE(), INTERVAL 5 DAY), 1600, 23.10, 1580, 8, 5, 15, 'Material', 98.75, 1),

-- Wednesday
('3', 'AHMAD FAUZI', '2017084', 'CR2', '3TNV76', '119717-23001', 'S2', 
 DATE_SUB(CURDATE(), INTERVAL 4 DAY), 1600, 23.10, 1520, 20, 15, 45, 'Machine Down', 95.00, 1),

-- Thursday
('4', 'AHMAD FAUZI', '2017084', 'CR2', '3TNV76', '119717-23001', 'S2', 
 DATE_SUB(CURDATE(), INTERVAL 3 DAY), 1600, 23.10, 1570, 10, 8, 20, 'Quality Check', 98.13, 1),

-- Friday
('5', 'AHMAD FAUZI', '2017084', 'CR2', '3TNV76', '119717-23001', 'S2', 
 DATE_SUB(CURDATE(), INTERVAL 2 DAY), 1600, 23.10, 1590, 5, 3, 10, 'Tool Setting', 99.38, 1),

-- Saturday (half day)
('6', 'AHMAD FAUZI', '2017084', 'CR2', '3TNV76', '119717-23001', 'S2', 
 DATE_SUB(CURDATE(), INTERVAL 1 DAY), 800, 23.10, 780, 8, 6, 15, 'Material', 97.50, 1);

-- ============================================================
-- Insert Sample Data for Multiple Lines
-- ============================================================

-- CR1 - Today
INSERT INTO production_dashboard_input 
(no, pic, nik, line, model, part_number, shift, waktu, plan, cycle_time, actual, 
 ng_by_machining, ng_by_material, stoptime_min, classification_stoptime, 
 performance_pct, interval_seq)
VALUES 
('1', 'TEGUH EKO MULYANTO', '1993003', 'CR1', 'NF60', '10510G-23050', 'S1', 
 CURDATE(), 1600, 22.80, 1550, 12, 8, 25, 'Tool Setting', 96.88, 1);

-- CR3 - Today
INSERT INTO production_dashboard_input 
(no, pic, nik, line, model, part_number, shift, waktu, plan, cycle_time, actual, 
 ng_by_machining, ng_by_material, stoptime_min, classification_stoptime, 
 performance_pct, interval_seq)
VALUES 
('1', 'SAMSURI', '1996005', 'CR3', '4TNE94', '129900-23001', 'S1', 
 CURDATE(), 1400, 27.20, 1350, 15, 10, 30, 'Machine Down', 96.43, 1);

-- CR4 - Today
INSERT INTO production_dashboard_input 
(no, pic, nik, line, model, part_number, shift, waktu, plan, cycle_time, actual, 
 ng_by_machining, ng_by_material, stoptime_min, classification_stoptime, 
 performance_pct, interval_seq)
VALUES 
('1', 'AYI IRAWAN', '2015013', 'CR4', '3TNV88', '129150-23100', 'S1', 
 CURDATE(), 1600, 23.90, 1570, 10, 8, 20, 'Quality Check', 98.13, 1);

-- ============================================================
-- Useful Queries for Testing
-- ============================================================

-- View all production data for today
-- SELECT * FROM production_dashboard_input 
-- WHERE waktu = CURDATE()
-- ORDER BY line, shift, interval_seq;

-- View summary by line for today
-- SELECT 
--     line,
--     shift,
--     SUM(plan) as total_plan,
--     SUM(actual) as total_actual,
--     SUM(ng_by_machining + ng_by_material) as total_ng,
--     ROUND(AVG(performance_pct), 2) as avg_performance
-- FROM production_dashboard_input
-- WHERE waktu = CURDATE()
-- GROUP BY line, shift;

-- View NG breakdown for today
-- SELECT 
--     line,
--     shift,
--     DATE_FORMAT(created_at, '%H:%i') as time,
--     ng_by_machining,
--     ng_by_material,
--     classification_stoptime
-- FROM production_dashboard_input
-- WHERE waktu = CURDATE()
-- AND (ng_by_machining > 0 OR ng_by_material > 0)
-- ORDER BY created_at DESC;

-- Delete test data (use carefully!)
-- DELETE FROM production_dashboard_input 
-- WHERE waktu = CURDATE();

-- ============================================================
-- Update Sample Data
-- ============================================================

-- Update performance calculation
-- UPDATE production_dashboard_input
-- SET performance_pct = ROUND((actual / plan) * 100, 2)
-- WHERE waktu = CURDATE();

-- Update stop time classification
-- UPDATE production_dashboard_input
-- SET classification_stoptime = 'Tool Setting'
-- WHERE line = 'CR2' AND waktu = CURDATE() AND stoptime_min > 20;

-- ============================================================
-- Clean Up Old Data (optional)
-- ============================================================

-- Delete data older than 30 days
-- DELETE FROM production_dashboard_input 
-- WHERE waktu < DATE_SUB(CURDATE(), INTERVAL 30 DAY);

-- ============================================================
-- Backup Query
-- ============================================================

-- Create backup of production data
-- CREATE TABLE production_dashboard_input_backup AS
-- SELECT * FROM production_dashboard_input
-- WHERE waktu >= DATE_SUB(CURDATE(), INTERVAL 7 DAY);
