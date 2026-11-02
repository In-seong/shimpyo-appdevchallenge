<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * 한 Laravel 앱이 두 SPA를 서빙한다.
 *   shimpyo.revuplan.com        → 차량 앱
 *   shimpyo-admin.revuplan.com  → 관리자
 *
 * 클로저 대신 컨트롤러를 쓰는 이유는 route:cache 를 쓰기 위해서다.
 */
class SpaController extends Controller
{
    public function admin()
    {
        return view('admin');
    }

    public function app(Request $request)
    {
        return str_starts_with($request->getHost(), 'shimpyo-admin')
            ? view('admin')
            : view('app');
    }
}
