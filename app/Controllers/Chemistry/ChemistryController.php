<?php

declare(strict_types=1);

namespace App\Controllers\Chemistry;

use App\Core\Request;
use App\Core\Response;
use App\Services\Chemistry\ChemistryCalculatorService;
use App\Services\Chemistry\ChemistryCompoundService;
use App\Services\Chemistry\EquationBalancerService;
use RuntimeException;

final class ChemistryController
{
    public function __construct(
        private readonly ChemistryCompoundService $compounds,
        private readonly EquationBalancerService $balancer,
        private readonly ChemistryCalculatorService $calculator
    ) {
    }

    public function searchCompounds(Request $request, array $params): Response
    {
        $query = (string) $request->query('q', '');

        if (trim($query) === '') {
            return Response::apiError('Vui lòng nhập từ khóa tìm kiếm.', [], 'VALIDATION_ERROR', 422);
        }

        return Response::apiSuccess($this->compounds->search($query), 'Kết quả tìm kiếm hợp chất.');
    }

    public function showCompound(Request $request, array $params): Response
    {
        try {
            $compound = $this->compounds->findByUuid($params['uuid']);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'NOT_FOUND', 404);
        }

        return Response::apiSuccess($compound, 'Thông tin hợp chất.');
    }

    public function balanceEquation(Request $request, array $params): Response
    {
        $input = $request->allInput();
        $reactants = (array) ($input['reactants'] ?? []);
        $products = (array) ($input['products'] ?? []);

        try {
            $result = $this->balancer->balance($reactants, $products);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'BALANCE_FAILED', 422);
        }

        return Response::apiSuccess([
            'reactants' => array_map(
                fn (string $formula, int $coefficient) => ['formula' => $formula, 'coefficient' => $coefficient],
                $reactants,
                $result['reactant_coefficients']
            ),
            'products' => array_map(
                fn (string $formula, int $coefficient) => ['formula' => $formula, 'coefficient' => $coefficient],
                $products,
                $result['product_coefficients']
            ),
        ], 'Cân bằng phương trình thành công.');
    }

    public function molarMass(Request $request, array $params): Response
    {
        $formula = (string) $request->query('formula', '');

        try {
            $result = $this->calculator->molarMass($formula);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'CALCULATION_FAILED', 422);
        }

        return Response::apiSuccess($result, 'Tính khối lượng mol thành công.');
    }

    public function ph(Request $request, array $params): Response
    {
        $concentration = (float) $request->query('h_concentration', 0);

        try {
            $result = $this->calculator->pH($concentration);
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'CALCULATION_FAILED', 422);
        }

        return Response::apiSuccess($result, 'Tính pH thành công.');
    }

    public function dilution(Request $request, array $params): Response
    {
        $input = $request->allInput();

        $toFloatOrNull = fn ($v) => $v === null || $v === '' ? null : (float) $v;

        try {
            $result = $this->calculator->dilution(
                $toFloatOrNull($input['c1'] ?? null),
                $toFloatOrNull($input['v1'] ?? null),
                $toFloatOrNull($input['c2'] ?? null),
                $toFloatOrNull($input['v2'] ?? null)
            );
        } catch (RuntimeException $e) {
            return Response::apiError($e->getMessage(), [], 'CALCULATION_FAILED', 422);
        }

        return Response::apiSuccess($result, 'Tính pha loãng thành công.');
    }
}
