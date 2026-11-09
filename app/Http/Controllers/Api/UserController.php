<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserController extends Controller
{
    // 15. DELETE /api/v1/users/{id}
    public function destroy($id)
    {
        $user = User::find($id);
        if (! $user) {
            return response()->json(['message' => 'Naudotojas nerastas'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'Naudotojas sėkmingai pašalintas'], 200);
    }
}
