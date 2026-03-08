-- ============================================================
-- NWPC Vehicle Fleet & Booking Management System
-- Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS nwp_vehicle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nwp_vehicle;

-- ============================================================
-- 1. OFFICES
-- ============================================================
CREATE TABLE IF NOT EXISTS offices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    telephone VARCHAR(20),
    email VARCHAR(100),
    fax VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 2. USERS
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    username VARCHAR(80) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin','office_admin','subject_officer','general_user') NOT NULL DEFAULT 'general_user',
    office_id INT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 3. DRIVERS
-- ============================================================
CREATE TABLE IF NOT EXISTS drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    nic VARCHAR(20) NOT NULL UNIQUE,
    license_number VARCHAR(50) NOT NULL,
    contact_number VARCHAR(20),
    office_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 4. VEHICLES
-- ============================================================
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reg_number VARCHAR(20) NOT NULL UNIQUE,
    type ENUM('Car','Van','SUV','Jeep','Double Cab','Single Cab','Bus','Lorry','Crew Cab','Three-Wheeler') NOT NULL,
    brand VARCHAR(100),
    fuel_type ENUM('Petrol','Diesel','EV','Hybrid') NOT NULL,
    seating_capacity INT NOT NULL DEFAULT 4,
    condition_status ENUM('Good Running Condition','Under Repairing','Not Running Condition') NOT NULL DEFAULT 'Good Running Condition',
    ac_available TINYINT(1) NOT NULL DEFAULT 0,
    driver_id INT NULL,
    office_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL,
    FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 5. BOOKINGS
-- ============================================================
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    booker_name VARCHAR(150) NOT NULL,
    booked_by_user_id INT NOT NULL,
    journey_date DATE NOT NULL,
    start_location VARCHAR(200) NOT NULL,
    destinations TEXT NOT NULL,
    start_time TIME NOT NULL,
    return_time TIME NOT NULL,
    distance_km DECIMAL(8,2) NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    nirdesha TEXT NULL,
    nirdesha_by INT NULL,
    nirdesha_at TIMESTAMP NULL,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (booked_by_user_id) REFERENCES users(id),
    FOREIGN KEY (nirdesha_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA: Default Super Admin Account
-- Password: Admin@1234  (bcrypt hashed)
-- ============================================================
INSERT INTO users (full_name, username, password, role, office_id, is_active) VALUES
('Super Administrator', 'superadmin', '$2y$10$ghN0RtUmeczBzLXxdLgT.uLrT7wdPt0jgXdXARDFiNVX4FikmEdO2', 'super_admin', NULL, 1);
-- Default password: Admin@1234 — Change this after first login!
