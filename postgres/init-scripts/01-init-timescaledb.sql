-- Enable the TimescaleDB extension if not already enabled
CREATE EXTENSION IF NOT EXISTS timescaledb CASCADE;

-- Create Master Stations Table
CREATE TABLE IF NOT EXISTS stations (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    latitude DECIMAL(9,6) NOT NULL,
    longitude DECIMAL(9,6) NOT NULL,
    threshold_orange DECIMAL(5,2) NOT NULL DEFAULT 3.00, -- Orange Warning Threshold in meters
    threshold_red DECIMAL(5,2) NOT NULL DEFAULT 4.00,    -- Red Alert Threshold in meters
    battery_status VARCHAR(50) DEFAULT 'Good',           -- 'Good', 'Low', 'Critical'
    status VARCHAR(50) NOT NULL DEFAULT 'active',        -- 'active', 'offline', 'maintenance'
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create Time-Series Telemetry Table
CREATE TABLE IF NOT EXISTS telemetry (
    time TIMESTAMP WITH TIME ZONE NOT NULL,
    station_id INTEGER NOT NULL REFERENCES stations(id) ON DELETE CASCADE,
    water_level DECIMAL(5,2) NOT NULL,                   -- Water depth/level in meters
    rainfall DECIMAL(5,2) NOT NULL,                      -- Rainfall volume in mm
    battery_voltage DECIMAL(4,2) NOT NULL                -- Sensor battery voltage in Volts
);

-- Convert Telemetry Table into TimescaleDB Hypertable
-- Partitioned by time (7-day intervals by default) and space (by station_id)
SELECT create_hypertable(
    'telemetry', 
    'time', 
    partitioning_column => 'station_id', 
    number_partitions => 4,
    if_not_exists => TRUE
);

-- Create Alert Logs Table to Track Active Warnings
CREATE TABLE IF NOT EXISTS alert_logs (
    id SERIAL PRIMARY KEY,
    station_id INTEGER NOT NULL REFERENCES stations(id) ON DELETE CASCADE,
    alert_level VARCHAR(50) NOT NULL,                    -- 'orange', 'red'
    water_level DECIMAL(5,2) NOT NULL,                   -- Level that triggered the warning
    triggered_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP WITH TIME ZONE                 -- Null if the alert is still active
);

-- Optimize Indexing for Quick Lookup and Chart Rendering
CREATE INDEX IF NOT EXISTS idx_telemetry_station_time ON telemetry (station_id, time DESC);
CREATE INDEX IF NOT EXISTS idx_alert_logs_active ON alert_logs (station_id) WHERE resolved_at IS NULL;
