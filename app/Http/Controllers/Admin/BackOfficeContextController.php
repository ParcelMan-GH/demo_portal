<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackOfficeAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BackOfficeContextController extends Controller
{
    public function updateWarehouse(Request $request, BackOfficeAccess $access): RedirectResponse
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user, 403);

        $value = $request->input('warehouse_id');
        $refererPath = parse_url($request->headers->get('referer', ''), PHP_URL_PATH);
        $module = $access->moduleFromRequestPath($refererPath ?: $request->path());

        if ($value === null || $value === '' || $value === 'all') {
            abort_unless($access->isHq($user), 403);

            $access->setSelectedWarehouse($user, null, $module);

            return back();
        }

        $access->setSelectedWarehouse($user, (int) $value, $module);

        return back();
    }
}
