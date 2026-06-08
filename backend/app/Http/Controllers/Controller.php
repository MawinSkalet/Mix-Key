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

    /**
     * @OA\Get(
     *     path="/stations",
     *     summary="ดึงรายชื่อสถานีทั้งหมด (Placeholder)",
     *     tags={"Stations"},
     *     @OA\Response(
     *         response=200,
     *         description="ดึงข้อมูลสำเร็จ"
     *     )
     * )
     */
    public function placeholder() {}
}
