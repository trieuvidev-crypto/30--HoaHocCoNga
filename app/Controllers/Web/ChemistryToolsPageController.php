<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;

/**
 * Renders the public Chemistry Tools page shell (Equation Balancer,
 * Molar Mass, pH, Dilution calculators). All actual computation happens
 * client-side via fetch() against the already-working public API
 * (/api/v1/chemistry/*) — this controller only serves the page.
 */
final class ChemistryToolsPageController
{
    public function index(Request $request, array $params): Response
    {
        $html = View::renderWithLayout('layouts.public', 'pages.chemistry.tools', [
            'pageTitle' => 'Công cụ Hóa học',
            'pageDescription' => 'Cân bằng phương trình hóa học, tính khối lượng mol, pH và pha loãng trực tuyến miễn phí.',
        ]);

        return Response::view($html);
    }
}
