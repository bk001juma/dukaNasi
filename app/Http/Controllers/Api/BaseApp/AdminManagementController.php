<?php

namespace App\Http\Controllers\Api\BaseApp;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminManagementController extends Controller
{
    public function createBusinessOwner(Request $request): JsonResponse
    {
        $currentAdmin = auth()->user();
        if (!$currentAdmin || $currentAdmin->super_user != 1) {
            return response()->json([
                'success' => false,
                'message' => 'Only super admin can create business owners'
            ], 403);
        }

        $validated = $request->validate([
            'username' => 'required|string|unique:admins',
            'email' => 'required|email|unique:admins',
            'password' => 'required|string|min:6',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'company_name' => 'nullable|string',
            'business_category' => 'nullable|string',
            'phone' => 'nullable|string',
            'country' => 'nullable|string',
            'city' => 'nullable|string',
        ]);

        $businessOwner = Admin::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'company_name' => $validated['company_name'] ?? null,
            'business_category' => $validated['business_category'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'country' => $validated['country'] ?? null,
            'city' => $validated['city'] ?? null,
            'super_user' => 0,
            'parent_admin_id' => $currentAdmin->id,
            'active' => 1,
            'shop_limit' => 5,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Business owner created successfully',
            'data' => $businessOwner
        ], 201);
    }

    public function getBusinessOwners(): JsonResponse
    {
        $currentAdmin = auth()->user();
        if (!$currentAdmin || $currentAdmin->super_user != 1) {
            return response()->json([
                'success' => false,
                'message' => 'Only super admin can view business owners'
            ], 403);
        }

        $businessOwners = $currentAdmin->childAdmins()->get();

        return response()->json([
            'success' => true,
            'data' => $businessOwners
        ]);
    }

    public function updateBusinessOwner(Request $request, Admin $admin): JsonResponse
    {
        $currentAdmin = auth()->user();
        
        if (!$currentAdmin || $currentAdmin->super_user != 1) {
            return response()->json([
                'success' => false,
                'message' => 'Only super admin can update business owners'
            ], 403);
        }

        if ($admin->parent_admin_id !== $currentAdmin->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update other super admin\'s business owners'
            ], 403);
        }

        $validated = $request->validate([
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'phone' => 'nullable|string',
            'company_name' => 'nullable|string',
            'business_category' => 'nullable|string',
            'shop_limit' => 'nullable|integer|min:1',
            'active' => 'nullable|boolean',
        ]);

        $admin->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Business owner updated successfully',
            'data' => $admin
        ]);
    }
}