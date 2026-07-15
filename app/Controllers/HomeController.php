<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class HomeController
{
    public function index(Request $request, array $params): Response
    {
        return Response::view('<!doctype html><html lang="vi"><head><meta charset="utf-8">'
            . '<title>HoaHocCoNga.Com — Học Hóa Cô Nga</title></head><body>'
            . '<h1>HoaHocCoNga.Com — hệ thống khởi động thành công.</h1>'
            . '</body></html>');
    }

    /**
     * Lightweight health check for deployment scripts / uptime monitors.
     * Verifies the DB connection is actually alive, not just that PHP booted.
     */
    public function health(Request $request, array $params): Response
    {
        try {
            Database::getInstance()->fetchOne('SELECT 1 AS ok');
            $dbStatus = 'ok';
        } catch (\Throwable) {
            $dbStatus = 'down';
        }

        return Response::apiSuccess([
            'app' => config('app.name'),
            'env' => config('app.env'),
            'database' => $dbStatus,
            'server_time' => gmdate('c'),
        ], 'Kiểm tra tình trạng hệ thống hoàn tất.');
    }
}
