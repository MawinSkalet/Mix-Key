<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Default Users
        \App\Models\User::create([
            'name' => 'Mawin Skalet',
            'email' => 'mawin.staff@maejhan.go.th',
            'password' => \Illuminate\Support\Facades\Hash::make('securepassword123'),
            'role' => 'staff'
        ]);

        \App\Models\User::create([
            'name' => 'กอฟ Database',
            'email' => 'golf.admin@maejhan.go.th',
            'password' => \Illuminate\Support\Facades\Hash::make('adminpassword123'),
            'role' => 'admin'
        ]);

        \App\Models\User::create([
            'name' => 'ปังปอน Dataflow',
            'email' => 'pangpon@maejhan.go.th',
            'password' => \Illuminate\Support\Facades\Hash::make('guestpassword123'),
            'role' => 'guest'
        ]);

        // 2. Create Default Stations
        $s1 = \App\Models\Station::create([
            'id' => 1,
            'name' => 'สถานีป่าตึง (Pa Tueng Station)',
            'location_description' => 'สะพานแม่น้ำคำ ต.ป่าตึง อ.แม่จัน',
            'latitude' => 20.04321,
            'longitude' => 99.81234,
            'warning_datum_m' => 2.00,
            'critical_datum_m' => 3.00,
            'water_height_datum' => 2.45,
            'calculated_msl' => 390.45,
            'trend' => 'rising',
            'rain_status' => 'rain',
            'battery_voltage' => 3.82,
            'signal_strength_rssi' => -72,
            'is_online' => true,
            'rating_curve_enabled' => true,
            'rating_curve_version' => 'v1.2_2026',
            'flow_rate_q_m3s' => 18.5,
            'velocity_v_ms' => 1.2
        ]);

        $s2 = \App\Models\Station::create([
            'id' => 2,
            'name' => 'สถานีบ้านหลัว (Ban Lua Station)',
            'location_description' => 'ฝายน้ำคำ ต.ป่าซาง อ.แม่จัน',
            'latitude' => 20.08456,
            'longitude' => 99.86543,
            'warning_datum_m' => 2.50,
            'critical_datum_m' => 3.80,
            'water_height_datum' => 1.20,
            'calculated_msl' => 385.20,
            'trend' => 'stable',
            'rain_status' => 'none',
            'battery_voltage' => 4.10,
            'signal_strength_rssi' => -65,
            'is_online' => true,
            'rating_curve_enabled' => false
        ]);

        // 3. Create Default Active Warnings
        \App\Models\Alert::create([
            'id' => 5,
            'type' => 'storm_warning',
            'title' => 'แจ้งเตือนเฝ้าระวังพายุโซนร้อน',
            'message' => 'กรมอุตุนิยมวิทยาเตือนภัยฝนตกหนักถึงหนักมากในพื้นที่อำเภอแม่จัน ระหว่างวันที่ 8-12 มิ.ย. นี้ ขอให้ประชาชนริมตลิ่งเฝ้าระวังน้ำป่าไหลหลาก',
            'severity' => 'high',
            'announced_at' => now()->subHour(),
            'expires_at' => now()->addDays(3)
        ]);

        // 4. Create Maintenance Logs
        \App\Models\MaintenanceLog::create([
            'id' => 101,
            'station_id' => 1,
            'maintenance_date' => '2026-05-10',
            'description' => 'ทำความสะอาดตัวรับเซนเซอร์อัลตร้าโซนิคเนื่องจากฝุ่นจับหนา',
            'status' => 'completed',
            'photo_url' => null,
            'performed_by' => 'Mawin Skalet'
        ]);

        \App\Models\MaintenanceLog::create([
            'id' => 102,
            'station_id' => 1,
            'maintenance_date' => '2026-05-20',
            'description' => 'เปลี่ยนแบตเตอรี่ LiPo ขนาด 10,000mAh และเช็คซีลกันน้ำตัวเคสอุปกรณ์',
            'status' => 'completed',
            'photo_url' => null,
            'performed_by' => 'Mawin Skalet'
        ]);

        // 5. Create Maintenance Requests
        \App\Models\MaintenanceRequest::create([
            'ticket_number' => 'REQ-20260520-01',
            'station_id' => 1,
            'request_type' => 'hardware',
            'item_requested' => 'Ultrasonic Sensor Module',
            'priority' => 'high',
            'reason' => 'เซนเซอร์สำรองในคลังเหลือน้อย',
            'status' => 'approved'
        ]);

        // 6. Create Staff Notifications
        \App\Models\StaffNotification::create([
            'id' => 1001,
            'station_id' => 1,
            'severity' => 'critical',
            'message' => 'ระดับน้ำสูงวิกฤตเกินขีดจำกัด: 3.02 ม. (Threshold: 3.00 ม.) แนะนำให้ประกาศแจ้งเตือนชุมชนท้ายน้ำเตรียมขนย้ายสิ่งของขึ้นที่สูง',
            'is_read' => false,
            'triggered_at' => now()->subMinutes(20)
        ]);

        \App\Models\StaffNotification::create([
            'id' => 1002,
            'station_id' => 1,
            'severity' => 'warning',
            'message' => 'ระดับน้ำเข้าสู่จุดเตือนภัย: 2.15 ม. (Threshold: 2.00 ม.) เฝ้าระวังปริมาณน้ำฝนสะสมเพิ่มเติมอย่างใกล้ชิด',
            'is_read' => true,
            'triggered_at' => now()->subHour()
        ]);

        // 7. Generate 6 hours of historical water levels (every 15m) for both stations
        $now = now();
        $intervalMinutes = 15;
        $count = 24;

        // Station 1: Pa Tueng (datum base ~2.2m, msl base ~390.2m)
        for ($i = $count - 1; $i >= 0; $i--) {
            $timestamp = $now->copy()->subMinutes($i * $intervalMinutes);
            $wave = sin($i / 6) * 0.4;
            $noise = (rand(-5, 5) / 100);
            $water_height_datum = max(0.1, round(2.2 + $wave + $noise, 2));
            $calculated_msl = round(390.2 + $wave + $noise, 2);

            \App\Models\Telemetry::create([
                'station_id' => 1,
                'timestamp' => $timestamp,
                'water_height_datum' => $water_height_datum,
                'calculated_msl' => $calculated_msl
            ]);
        }

        // Station 2: Ban Lua (datum base ~1.1m, msl base ~385.1m)
        for ($i = $count - 1; $i >= 0; $i--) {
            $timestamp = $now->copy()->subMinutes($i * $intervalMinutes);
            $wave = sin($i / 6) * 0.4;
            $noise = (rand(-5, 5) / 100);
            $water_height_datum = max(0.1, round(1.1 + $wave + $noise, 2));
            $calculated_msl = round(385.1 + $wave + $noise, 2);

            \App\Models\Telemetry::create([
                'station_id' => 2,
                'timestamp' => $timestamp,
                'water_height_datum' => $water_height_datum,
                'calculated_msl' => $calculated_msl
            ]);
        }
    }
}
