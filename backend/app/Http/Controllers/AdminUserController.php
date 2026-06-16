<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = intval($request->query('per_page', 15));
        
        $users = User::orderBy('name', 'asc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($users->items())->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->role
                ];
            }),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total()
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:guest,staff,admin',
        ], [
            'name.required' => 'กรุณาระบุชื่อผู้ใช้งาน',
            'email.required' => 'กรุณาระบุอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique' => 'อีเมลนี้มีอยู่ในระบบแล้ว',
            'password.required' => 'กรุณาระบุรหัสผ่าน',
            'password.min' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร',
            'role.required' => 'กรุณาระบุบทบาทหน้าที่',
            'role.in' => 'บทบาทไม่ถูกต้อง (เลือกได้เฉพาะ guest, staff, admin)'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลผู้ใช้ไม่ถูกต้อง',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'สร้างบัญชีผู้ใช้งานสำเร็จ',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ], 201);
    }

    public function updateRole(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "ไม่พบผู้ใช้งาน ID {$id}"
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|in:guest,staff,admin',
        ], [
            'role.required' => 'กรุณาระบุบทบาทสิทธิ์',
            'role.in' => 'บทบาทไม่ถูกต้อง (เลือกได้เฉพาะ guest, staff, admin)'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลบทบาทไม่ถูกต้อง',
                'errors' => $validator->errors()
            ], 422);
        }

        // Prevent self role downgrade
        if ($user->id === auth()->user()->id && $request->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลดสิทธิ์ผู้ดูแลระบบของตัวเองได้'
            ], 403);
        }

        $user->update([
            'role' => $request->role
        ]);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตสิทธิ์บทบาทผู้ใช้งานสำเร็จ',
            'data' => [
                'user_id' => $user->id,
                'role' => $user->role,
                'updated_at' => now()->toIso8601String()
            ]
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "ไม่พบผู้ใช้งาน ID {$id}"
            ], 404);
        }

        // Prevent deleting oneself
        if ($user->id === auth()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบบัญชีผู้ใช้ที่กำลังใช้งานอยู่ได้'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบบัญชีผู้ใช้งานสำเร็จ'
        ]);
    }
}
