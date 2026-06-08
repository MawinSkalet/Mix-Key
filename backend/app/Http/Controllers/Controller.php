<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="Mix-Key Water Monitoring API",
 *     version="1.0.0",
 *     description="API Standard Documentation for Mix-Key Flood Alert System (Mae Chan, Chiang Rai)"
 * )
 * @OA\Server(
 *     url="http://localhost/api/v1",
 *     description="Local Development API Gateway"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter Bearer Token to access protected Staff/Admin endpoints"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /*
    |--------------------------------------------------------------------------
    | 🔓 Guest / Public APIs
    |--------------------------------------------------------------------------
    */

    /**
     * @OA\Get(
     *     path="/stations",
     *     summary="ดึงรายชื่อสถานีทั้งหมด (รองรับ GPS)",
     *     tags={"Guest APIs"},
     *     @OA\Parameter(
     *         name="latitude",
     *         in="query",
     *         description="ละติจูดของผู้ใช้ (เช่น 19.98765)",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="longitude",
     *         in="query",
     *         description="ลองจิจูดของผู้ใช้ (เช่น 99.87654)",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="radius",
     *         in="query",
     *         description="รัศมีการค้นหา หน่วยกิโลเมตร (เช่น 10)",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ดึงข้อมูลสำเร็จ"
     *     )
     * )
     */
    public function getStations() {}

    /**
     * @OA\Get(
     *     path="/stations/{id}",
     *     summary="ดึงข้อมูลรายละเอียดสถานี",
     *     tags={"Guest APIs"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="รหัสสถานี",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ดึงข้อมูลสำเร็จ"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="ไม่พบข้อมูลสถานี"
     *     )
     * )
     */
    public function getStationById() {}

    /**
     * @OA\Get(
     *     path="/stations/{id}/water-level",
     *     summary="ดึงข้อมูลประวัติระดับน้ำย้อนหลัง (มี Aggregation + Pagination)",
     *     tags={"Guest APIs"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="รหัสสถานี",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="range",
     *         in="query",
     *         description="ช่วงเวลาของข้อมูล (6h, 1m, 3m, 6m)",
     *         required=false,
     *         @OA\Schema(type="string", default="6h")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="หน้าข้อมูล (Pagination)",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="จำนวนจุดข้อมูลต่อหน้า",
     *         required=false,
     *         @OA\Schema(type="integer", default=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ดึงข้อมูลประวัติสำเร็จ"
     *     )
     * )
     */
    public function getWaterLevels() {}

    /**
     * @OA\Get(
     *     path="/alerts/active",
     *     summary="ดึงข้อมูลการแจ้งเตือนภัยพายุ/ระดับน้ำวิกฤตปัจจุบันจาก TMD",
     *     tags={"Guest APIs"},
     *     @OA\Response(
     *         response=200,
     *         description="ดึงข้อมูลสำเร็จ"
     *     )
     * )
     */
    public function getActiveAlerts() {}


    /*
    |--------------------------------------------------------------------------
    | 📡 IoT Ingestion APIs
    |--------------------------------------------------------------------------
    */

    /**
     * @OA\Post(
     *     path="/telemetry/ingest",
     *     summary="ช่องทางรับข้อมูลดิบจาก Node-RED/Sensor",
     *     tags={"IoT Telemetry Ingestion"},
     *     @OA\Parameter(
     *         name="X-API-KEY",
     *         in="header",
     *         description="รหัสลับความปลอดภัยอุปกรณ์",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"sensor_id", "water_level_cm", "battery_voltage"},
     *             @OA\Property(property="sensor_id", type="string", example="PT-01"),
     *             @OA\Property(property="timestamp", type="string", format="date-time", example="2026-06-08T16:15:00+07:00"),
     *             @OA\Property(property="water_level_cm", type="number", format="float", example=245.0),
     *             @OA\Property(property="battery_voltage", type="number", format="float", example=3.82),
     *             @OA\Property(property="signal_strength_rssi", type="integer", example=-72)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="บันทึกข้อมูลเรียบร้อยแล้ว"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="API Key ไม่ถูกต้อง"
     *     )
     * )
     */
    public function ingestTelemetry() {}


    /*
    |--------------------------------------------------------------------------
    | 🔒 Staff APIs
    |--------------------------------------------------------------------------
    */

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="เข้าสู่ระบบสตาฟฟ์/แอดมิน เพื่อรับ Bearer Token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="mawin.staff@maejhan.go.th"),
     *             @OA\Property(property="password", type="string", format="password", example="securepassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ล็อกอินสำเร็จ"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="ข้อมูลตรวจสอบไม่ผ่าน"
     *     )
     * )
     */
    public function login() {}

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="ออกจากระบบ (ทำลาย Token)",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="ออกจากระบบสำเร็จ"
     *     )
     * )
     */
    public function logout() {}

    /**
     * @OA\Get(
     *     path="/staff/stations/{id}/telemetry",
     *     summary="ดึงข้อมูลสถานะการทำงานจริงของเครื่อง (สตาฟฟ์เท่านั้น)",
     *     tags={"Staff APIs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="รหัสสถานี",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ดึงข้อมูลสำเร็จ"
     *     )
     * )
     */
    public function getStaffTelemetry() {}

    /**
     * @OA\Patch(
     *     path="/staff/stations/{id}/thresholds",
     *     summary="ปรับเปลี่ยนเกณฑ์เตือนภัยระดับน้ำ (สตาฟฟ์เท่านั้น)",
     *     tags={"Staff APIs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="รหัสสถานี",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="warning_datum_m", type="number", format="float", example=2.20),
     *             @OA\Property(property="critical_datum_m", type="number", format="float", example=3.10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="อัปเดตระดับเตือนภัยสำเร็จ"
     *     )
     * )
     */
    public function updateThresholds() {}

    /**
     * @OA\Get(
     *     path="/staff/stations/{id}/maintenance",
     *     summary="เรียกดูประวัติการซ่อมบำรุงของสถานี (สตาฟฟ์เท่านั้น)",
     *     tags={"Staff APIs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="รหัสสถานี",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ดึงประวัติสำเร็จ"
     *     )
     * )
     */
    public function getMaintenanceHistory() {}

    /**
     * @OA\Post(
     *     path="/staff/maintenance",
     *     summary="บันทึกผลงานการซ่อมบำรุงและรูปภาพหลักฐาน (สตาฟฟ์เท่านั้น)",
     *     tags={"Staff APIs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"station_id", "maintenance_date", "description", "status"},
     *                 @OA\Property(property="station_id", type="integer", example=1),
     *                 @OA\Property(property="maintenance_date", type="string", format="date", example="2026-06-08"),
     *                 @OA\Property(property="description", type="string", example="ทำความสะอาดตะไคร่น้ำหัวเซนเซอร์"),
     *                 @OA\Property(property="status", type="string", example="completed"),
     *                 @OA\Property(property="image", type="string", format="binary", description="ไฟล์รูป JPEG/PNG ไม่เกิน 5MB")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="บันทึกข้อมูลและอัปโหลดรูปสำเร็จ"
     *     )
     * )
     */
    public function logMaintenance() {}

    /**
     * @OA\Post(
     *     path="/staff/maintenance-requests",
     *     summary="ส่งคำร้องขอเบิกอุปกรณ์/แจ้งจุดซ่อมบำรุงด่วน (สตาฟฟ์เท่านั้น)",
     *     tags={"Staff APIs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"station_id", "request_type", "item_requested", "priority", "reason"},
     *             @OA\Property(property="station_id", type="integer", example=2),
     *             @OA\Property(property="request_type", type="string", example="hardware"),
     *             @OA\Property(property="item_requested", type="string", example="Ultrasonic Sensor Module"),
     *             @OA\Property(property="priority", type="string", example="high"),
     *             @OA\Property(property="reason", type="string", example="เซนเซอร์พังจากน้ำท่วมขัง")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="ส่งคำร้องสำเร็จ"
     *     )
     * )
     */
    public function createMaintenanceRequest() {}

    /**
     * @OA\Get(
     *     path="/staff/notifications",
     *     summary="ดึงรายการประวัติการแจ้งเตือนภัยของ Staff (สตาฟฟ์เท่านั้น)",
     *     tags={"Staff APIs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="unread_only",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="boolean", default=false)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ดึงรายการแจ้งเตือนสำเร็จ"
     *     )
     * )
     */
    public function getStaffNotifications() {}

    /**
     * @OA\Put(
     *     path="/staff/notifications/{id}/read",
     *     summary="กดรับทราบ/อ่านเหตุการณ์แจ้งเตือนภัยพิบัติ (สตาฟฟ์เท่านั้น)",
     *     tags={"Staff APIs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="รหัสการแจ้งเตือนภัย",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="บันทึกการอ่านสำเร็จ"
     *     )
     * )
     */
    public function readNotification() {}


    /*
    |--------------------------------------------------------------------------
    | 🔑 Admin APIs
    |--------------------------------------------------------------------------
    */

    /**
     * @OA\Get(
     *     path="/admin/users",
     *     summary="ดึงรายการผู้ใช้ทั้งหมดในระบบ (แอดมินเท่านั้น)",
     *     tags={"Admin APIs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ดึงรายชื่อสำเร็จ"
     *     )
     * )
     */
    public function getAdminUsers() {}

    /**
     * @OA\Post(
     *     path="/admin/users",
     *     summary="สร้างบัญชีผู้ใช้ใหม่ให้ Staff หรือ Admin โดยตรง (แอดมินเท่านั้น)",
     *     tags={"Admin APIs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "role"},
     *             @OA\Property(property="name", type="string", example="กอฟ Database"),
     *             @OA\Property(property="email", type="string", format="email", example="golf@maejhan.go.th"),
     *             @OA\Property(property="password", type="string", example="temporaryPassword123"),
     *             @OA\Property(property="role", type="string", example="admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="สร้างบัญชีสำเร็จ"
     *     )
     * )
     */
    public function createAdminUser() {}

    /**
     * @OA\Patch(
     *     path="/admin/users/{id}/role",
     *     summary="อัปเดตระดับสิทธิ์ผู้ใช้ (แอดมินเท่านั้น)",
     *     tags={"Admin APIs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="รหัสผู้ใช้งาน",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"role"},
     *             @OA\Property(property="role", type="string", example="staff")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="อัปเดตสิทธิ์สำเร็จ"
     *     )
     * )
     */
    public function updateAdminUserRole() {}

    /**
     * @OA\Delete(
     *     path="/admin/users/{id}",
     *     summary="ลบบัญชีผู้ใช้ออกจากระบบ (แอดมินเท่านั้น)",
     *     tags={"Admin APIs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="รหัสผู้ใช้งาน",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ลบบัญชีสำเร็จ"
     *     )
     * )
     */
    public function deleteAdminUser() {}
}
