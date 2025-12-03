<?php

namespace App\Http\Controllers\Household;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Str;

class HouseholdController extends Controller
{
    private function generateInviteCode()
    {
        return strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));
    }


    public function index()
    {
     
        $user_id = Auth::id();
    
        if (!$user_id) {
            return self::responseJSON(null, "unauthorized", 401);
        }
       
        $user = User::find($user_id);

        if (!$user) {
            return self::responseJSON(null, "User not found", 404);
        }

        $households = $user->households()->withCount('users')->get();

        return self::responseJSON($households, "Households retrieved successfully", 200);
    }


    public function store(Request $request)
    {
        
        
    //    return self::responseJSON(null, "unauthorized", 401);
        $request->validate([
            'name' => 'required|string|max:255'
        ]);
        
        $user_id = Auth::id();

        if (!$user_id) {
            return self::responseJSON(null, "unauthorized", 401);
        }

        $household = new Household;
        $household->name = $request->name;
        $household->invite_code = $this->generateInviteCode();

        if ($household->save()) {

            // Attach authenticated user as admin
            $household->users()->attach($user_id, [
                'role' => 'admin'
            ]);

            return self::responseJSON($household, "Household created successfully", 201);
        }

        return self::responseJSON(null, "Failed to create household", 500);
    }


    public function show($id)
    {
        $household = Household::with('users')->find($id);

        if (!$household) {
            return self::responseJSON(null, "Household not found", 404);
        }

        return self::responseJSON($household, "Household retrieved successfully", 200);
    }

    public function join(Request $request)
    {
        $request->validate([
            'invite_code' => 'required|string',
        ]);

        $user_id = Auth::id();

        if (!$user_id) {
            return self::responseJSON(null, "unauthorized", 401);
        }

        $household = Household::where('invite_code', $request->invite_code)->first();

        if (!$household) {
            return self::responseJSON(null, "Invalid invite code", 404);
        }

        // Check if user already exists in household
        if ($household->users()->where('user_id', $user_id)->exists()) {
            return self::responseJSON(null, "User already belongs to this household", 200);
        }

        // Add user as member
        $household->users()->attach($user_id, [
            'role' => 'member'
        ]);

        return self::responseJSON($household, "Joined household successfully", 200);
    }
}
