<?php

declare(strict_types=1);

/**
 * Usage: php database/seeders/0003_chemistry_seeder.php
 *
 * Seeds the elements most relevant to the Vietnamese Grade 8-12 chemistry
 * curriculum (per CHEMISTRY_DOMAIN.md) with scientifically accurate data,
 * plus a set of real, commonly-taught compounds and reactions in Vietnamese.
 *
 * This is intentionally NOT all 118 elements / 5000 reactions — those
 * volumes (per DATABASE_DEMO_SPEC.md) require a verified data-import
 * pipeline (Phase 6, Chemistry Engine milestone) rather than hand-typed
 * physical constants, where transcription errors would silently corrupt
 * a core subsystem. What's seeded here is fully accurate and directly
 * usable for the Grade 8-12 + University Entrance Exam curriculum.
 */

require_once __DIR__ . '/../../bootstrap/helpers.php';
require_once __DIR__ . '/_bootstrap_seeder.php';

/** @var PDO $pdo */

// ------------------------------------------------------------------
// ELEMENTS — the 20 elements that dominate the THCS/THPT curriculum.
// ------------------------------------------------------------------
$elements = [
    [1, 'H', 'Hydrogen', 'Hiđro', 1.008, '1s1', 1, 1, 's', 'phi kim', '-1,+1', 2.20, 0.00009, -259.16, -252.87],
    [2, 'He', 'Helium', 'Heli', 4.0026, '1s2', 1, 18, 's', 'khí hiếm', null, null, 0.0001785, -272.20, -268.93],
    [6, 'C', 'Carbon', 'Cacbon', 12.011, '[He]2s2 2p2', 2, 14, 'p', 'phi kim', '-4,+2,+4', 2.55, 2.267, 3550.0, 4027.0],
    [7, 'N', 'Nitrogen', 'Nitơ', 14.007, '[He]2s2 2p3', 2, 15, 'p', 'phi kim', '-3,+1,+2,+3,+4,+5', 3.04, 0.0012506, -210.00, -195.79],
    [8, 'O', 'Oxygen', 'Oxi', 15.999, '[He]2s2 2p4', 2, 16, 'p', 'phi kim', '-2,-1', 3.44, 0.001429, -218.79, -182.96],
    [11, 'Na', 'Sodium', 'Natri', 22.990, '[Ne]3s1', 3, 1, 's', 'kim loại kiềm', '+1', 0.93, 0.971, 97.79, 882.94],
    [12, 'Mg', 'Magnesium', 'Magie', 24.305, '[Ne]3s2', 3, 2, 's', 'kim loại kiềm thổ', '+2', 1.31, 1.738, 650.0, 1090.0],
    [13, 'Al', 'Aluminium', 'Nhôm', 26.982, '[Ne]3s2 3p1', 3, 13, 'p', 'kim loại', '+3', 1.61, 2.70, 660.32, 2519.0],
    [14, 'Si', 'Silicon', 'Silic', 28.085, '[Ne]3s2 3p2', 3, 14, 'p', 'á kim', '-4,+2,+4', 1.90, 2.3296, 1414.0, 3265.0],
    [15, 'P', 'Phosphorus', 'Photpho', 30.974, '[Ne]3s2 3p3', 3, 15, 'p', 'phi kim', '-3,+3,+5', 2.19, 1.823, 44.15, 280.5],
    [16, 'S', 'Sulfur', 'Lưu huỳnh', 32.06, '[Ne]3s2 3p4', 3, 16, 'p', 'phi kim', '-2,+4,+6', 2.58, 2.067, 115.21, 444.72],
    [17, 'Cl', 'Chlorine', 'Clo', 35.45, '[Ne]3s2 3p5', 3, 17, 'p', 'phi kim (halogen)', '-1,+1,+3,+5,+7', 3.16, 0.003214, -101.5, -34.04],
    [19, 'K', 'Potassium', 'Kali', 39.098, '[Ar]4s1', 4, 1, 's', 'kim loại kiềm', '+1', 0.82, 0.862, 63.38, 759.0],
    [20, 'Ca', 'Calcium', 'Canxi', 40.078, '[Ar]4s2', 4, 2, 's', 'kim loại kiềm thổ', '+2', 1.00, 1.55, 842.0, 1484.0],
    [26, 'Fe', 'Iron', 'Sắt', 55.845, '[Ar]3d6 4s2', 4, 8, 'd', 'kim loại chuyển tiếp', '+2,+3', 1.83, 7.874, 1538.0, 2862.0],
    [29, 'Cu', 'Copper', 'Đồng', 63.546, '[Ar]3d10 4s1', 4, 11, 'd', 'kim loại chuyển tiếp', '+1,+2', 1.90, 8.96, 1084.62, 2562.0],
    [30, 'Zn', 'Zinc', 'Kẽm', 65.38, '[Ar]3d10 4s2', 4, 12, 'd', 'kim loại chuyển tiếp', '+2', 1.65, 7.14, 419.53, 907.0],
    [35, 'Br', 'Bromine', 'Brom', 79.904, '[Ar]3d10 4s2 4p5', 4, 17, 'p', 'phi kim (halogen)', '-1,+1,+3,+5', 2.96, 3.1028, -7.2, 58.8],
    [47, 'Ag', 'Silver', 'Bạc', 107.868, '[Kr]4d10 5s1', 5, 11, 'd', 'kim loại chuyển tiếp', '+1', 1.93, 10.49, 961.78, 2162.0],
    [56, 'Ba', 'Barium', 'Bari', 137.327, '[Xe]6s2', 6, 2, 's', 'kim loại kiềm thổ', '+2', 0.89, 3.51, 727.0, 1897.0],
];

$insertElement = $pdo->prepare(
    'INSERT INTO chemistry_elements
        (uuid, atomic_number, symbol, name_en, name_vi, atomic_mass, electron_configuration,
         period_number, group_number, block, category, oxidation_states, electronegativity,
         density_g_cm3, melting_point_c, boiling_point_c, created_at, updated_at)
     VALUES
        (:uuid, :atomic_number, :symbol, :name_en, :name_vi, :atomic_mass, :econf,
         :period, :group_no, :block, :category, :oxidation, :electroneg,
         :density, :melting, :boiling, NOW(), NOW())
     ON DUPLICATE KEY UPDATE name_vi = VALUES(name_vi)'
);

$elementIdBySymbol = [];

foreach ($elements as $e) {
    [$z, $symbol, $nameEn, $nameVi, $mass, $econf, $period, $group, $block, $category, $oxidation, $eneg, $density, $melt, $boil] = $e;

    $insertElement->execute([
        'uuid' => generate_uuid_v4(),
        'atomic_number' => $z,
        'symbol' => $symbol,
        'name_en' => $nameEn,
        'name_vi' => $nameVi,
        'atomic_mass' => $mass,
        'econf' => $econf,
        'period' => $period,
        'group_no' => $group,
        'block' => $block,
        'category' => $category,
        'oxidation' => $oxidation,
        'electroneg' => $eneg,
        'density' => $density,
        'melting' => $melt,
        'boiling' => $boil,
    ]);

    $elementIdBySymbol[$symbol] = (int) $pdo->query(
        'SELECT id FROM chemistry_elements WHERE symbol = ' . $pdo->quote($symbol)
    )->fetchColumn();
}

echo 'Đã tạo ' . count($elements) . " nguyên tố hóa học.\n";

// ------------------------------------------------------------------
// COMPOUNDS — common compounds taught across Grade 8-12.
// ------------------------------------------------------------------
$compounds = [
    ['H2O', 'H₂O', 'Nước', 'Water', null, 'other', 18.015, 'liquid', 'không màu'],
    ['H2SO4', 'H₂SO₄', 'Axit sunfuric', 'Sulfuric acid', null, 'acid', 98.079, 'liquid', 'không màu, sánh như dầu'],
    ['HCl', 'HCl', 'Axit clohiđric', 'Hydrochloric acid', 'Axit muối', 'acid', 36.458, 'aqueous', 'không màu'],
    ['HNO3', 'HNO₃', 'Axit nitric', 'Nitric acid', null, 'acid', 63.012, 'liquid', 'không màu, bốc khói trong không khí ẩm'],
    ['NaOH', 'NaOH', 'Natri hiđroxit', 'Sodium hydroxide', 'Xút ăn da', 'base', 39.997, 'solid', 'trắng, dạng vảy hoặc hạt'],
    ['Ca(OH)2', 'Ca(OH)₂', 'Canxi hiđroxit', 'Calcium hydroxide', 'Vôi tôi', 'base', 74.093, 'solid', 'trắng'],
    ['NaCl', 'NaCl', 'Natri clorua', 'Sodium chloride', 'Muối ăn', 'salt', 58.443, 'solid', 'trắng'],
    ['CaCO3', 'CaCO₃', 'Canxi cacbonat', 'Calcium carbonate', 'Đá vôi', 'salt', 100.087, 'solid', 'trắng, không tan trong nước'],
    ['CuSO4', 'CuSO₄', 'Đồng(II) sunfat', 'Copper(II) sulfate', 'Phèn xanh (dạng khan)', 'salt', 159.609, 'solid', 'trắng (khan) / xanh lam (ngậm nước)'],
    ['Na2CO3', 'Na₂CO₃', 'Natri cacbonat', 'Sodium carbonate', 'Sô-đa', 'salt', 105.988, 'solid', 'trắng'],
    ['CO2', 'CO₂', 'Cacbon đioxit', 'Carbon dioxide', 'Khí cacbonic', 'gas', 44.009, 'gas', 'không màu'],
    ['SO2', 'SO₂', 'Lưu huỳnh đioxit', 'Sulfur dioxide', 'Khí sunfurơ', 'gas', 64.066, 'gas', 'không màu, mùi hắc'],
    ['Fe2O3', 'Fe₂O₃', 'Sắt(III) oxit', 'Iron(III) oxide', 'Gỉ sắt (thành phần chính)', 'oxide', 159.687, 'solid', 'đỏ nâu'],
    ['CH4', 'CH₄', 'Metan', 'Methane', null, 'organic', 16.043, 'gas', 'không màu'],
    ['C2H5OH', 'C₂H₅OH', 'Etanol', 'Ethanol', 'Cồn', 'organic', 46.069, 'liquid', 'không màu'],
    ['CH3COOH', 'CH₃COOH', 'Axit axetic', 'Acetic acid', 'Giấm (dạng loãng)', 'acid', 60.052, 'liquid', 'không màu, mùi chua'],
    ['AgNO3', 'AgNO₃', 'Bạc nitrat', 'Silver nitrate', null, 'salt', 169.872, 'solid', 'trắng'],
    ['BaSO4', 'BaSO₄', 'Bari sunfat', 'Barium sulfate', null, 'salt', 233.39, 'solid', 'trắng, không tan trong nước'],
    ['KMnO4', 'KMnO₄', 'Kali pemanganat', 'Potassium permanganate', 'Thuốc tím', 'salt', 158.034, 'solid', 'tím đen'],
    ['NH3', 'NH₃', 'Amoniac', 'Ammonia', null, 'other', 17.031, 'gas', 'không màu, mùi khai'],
];

$insertCompound = $pdo->prepare(
    'INSERT INTO chemistry_compounds
        (uuid, formula, formula_display, name_vi, name_en, common_name, category,
         molar_mass_g_mol, physical_state, color, is_organic, created_at, updated_at)
     VALUES
        (:uuid, :formula, :formula_display, :name_vi, :name_en, :common_name, :category,
         :molar_mass, :state, :color, :is_organic, NOW(), NOW())
     ON DUPLICATE KEY UPDATE name_vi = VALUES(name_vi)'
);

$compoundIdByFormula = [];

foreach ($compounds as $c) {
    [$formula, $display, $nameVi, $nameEn, $common, $category, $molarMass, $state, $color] = $c;
    $isOrganic = $category === 'organic' ? 1 : 0;

    $insertCompound->execute([
        'uuid' => generate_uuid_v4(),
        'formula' => $formula,
        'formula_display' => $display,
        'name_vi' => $nameVi,
        'name_en' => $nameEn,
        'common_name' => $common,
        'category' => $category,
        'molar_mass' => $molarMass,
        'state' => $state,
        'color' => $color,
        'is_organic' => $isOrganic,
    ]);

    $exists = $pdo->prepare('SELECT id FROM chemistry_compounds WHERE formula = :formula');
    $exists->execute(['formula' => $formula]);
    $compoundIdByFormula[$formula] = (int) $exists->fetchColumn();
}

echo 'Đã tạo ' . count($compounds) . " hợp chất hóa học.\n";

// Aliases: common names + accent-stripped normalized forms for typo-tolerant search.
// Uses the same App\Core\VietnameseTextNormalizer the application code uses
// (ChemistryCompoundService), so seeded normalized values and runtime
// lookups are guaranteed to match exactly.
$normalize = static fn (string $text): string => \App\Core\VietnameseTextNormalizer::stripDiacritics($text);

$insertAlias = $pdo->prepare(
    'INSERT INTO chemistry_compound_aliases (compound_id, alias, alias_normalized) VALUES (:compound_id, :alias, :normalized)'
);

foreach ($compounds as $c) {
    [$formula, , $nameVi, $nameEn, $common] = $c;
    $compoundId = $compoundIdByFormula[$formula];

    foreach (array_filter([$nameVi, $nameEn, $common, $formula]) as $alias) {
        $insertAlias->execute([
            'compound_id' => $compoundId,
            'alias' => $alias,
            'normalized' => $normalize($alias),
        ]);
    }
}

echo "Đã tạo bí danh (alias) tìm kiếm cho các hợp chất.\n";

// ------------------------------------------------------------------
// REACTIONS — a curated, accurate set spanning the reaction types
// students encounter across the curriculum.
// ------------------------------------------------------------------
$reactions = [
    [
        'name' => 'Phản ứng trung hòa giữa axit sunfuric và natri hiđroxit',
        'equation' => 'H₂SO₄ + 2NaOH → Na₂SO₄ + 2H₂O',
        'type' => 'acid_base',
        'conditions' => 'Nhiệt độ thường',
        'explanation' => 'Axit mạnh phản ứng với bazơ mạnh tạo thành muối trung hòa và nước. Đây là phản ứng trung hòa điển hình, tỏa nhiệt.',
        'applications' => 'Ứng dụng trong xử lý nước thải công nghiệp để trung hòa độ pH trước khi xả thải.',
        'difficulty' => 'easy',
        'grade_slug' => 'lop-9',
        'participants' => [
            ['formula' => 'H2SO4', 'role' => 'reactant', 'coefficient' => 1, 'state' => 'aqueous'],
            ['formula' => 'NaOH', 'role' => 'reactant', 'coefficient' => 2, 'state' => 'aqueous'],
            ['formula' => null, 'role' => 'product', 'coefficient' => 1, 'state' => 'aqueous'], // Na2SO4 not yet seeded individually
            ['formula' => 'H2O', 'role' => 'product', 'coefficient' => 2, 'state' => 'liquid'],
        ],
    ],
    [
        'name' => 'Phản ứng giữa kẽm và axit clohiđric',
        'equation' => 'Zn + 2HCl → ZnCl₂ + H₂↑',
        'type' => 'single_replacement',
        'conditions' => 'Nhiệt độ thường',
        'gas_released' => 'Khí hiđro (H₂) thoát ra, có thể thử bằng que đóm đang cháy (nổ nhẹ, tiếng "pop")',
        'explanation' => 'Kim loại kẽm đứng trước hiđro trong dãy hoạt động hóa học nên đẩy được hiđro ra khỏi axit, tạo thành muối kẽm clorua và giải phóng khí hiđro.',
        'applications' => 'Phản ứng minh họa dãy hoạt động hóa học của kim loại, thường dùng để điều chế khí H₂ trong phòng thí nghiệm.',
        'difficulty' => 'easy',
        'grade_slug' => 'lop-9',
        'participants' => [
            ['formula' => 'HCl', 'role' => 'reactant', 'coefficient' => 2, 'state' => 'aqueous'],
        ],
    ],
    [
        'name' => 'Phản ứng cháy của metan',
        'equation' => 'CH₄ + 2O₂ → CO₂ + 2H₂O',
        'type' => 'combustion',
        'conditions' => 'Đốt cháy, có mồi lửa',
        'explanation' => 'Metan (thành phần chính của khí thiên nhiên) cháy hoàn toàn trong oxi tạo ra khí cacbonic và hơi nước, tỏa nhiều nhiệt.',
        'applications' => 'Cơ sở của việc sử dụng khí thiên nhiên/khí đốt làm nhiên liệu trong đun nấu và sản xuất điện.',
        'difficulty' => 'medium',
        'grade_slug' => 'lop-11',
        'participants' => [
            ['formula' => 'CH4', 'role' => 'reactant', 'coefficient' => 1, 'state' => 'gas'],
            ['formula' => 'CO2', 'role' => 'product', 'coefficient' => 1, 'state' => 'gas'],
            ['formula' => 'H2O', 'role' => 'product', 'coefficient' => 2, 'state' => 'gas'],
        ],
    ],
    [
        'name' => 'Phản ứng nhiệt phân canxi cacbonat',
        'equation' => 'CaCO₃ →(t°) CaO + CO₂↑',
        'type' => 'decomposition',
        'temperature' => 'Khoảng 900°C',
        'gas_released' => 'Khí cacbonic (CO₂)',
        'explanation' => 'Đá vôi (CaCO₃) khi nung ở nhiệt độ cao bị phân hủy thành vôi sống (CaO) và khí cacbonic. Đây là phản ứng nền tảng của công nghiệp sản xuất vôi và xi măng.',
        'applications' => 'Sản xuất vôi sống trong công nghiệp xây dựng, luyện kim và xử lý môi trường.',
        'difficulty' => 'medium',
        'grade_slug' => 'lop-9',
        'participants' => [
            ['formula' => 'CaCO3', 'role' => 'reactant', 'coefficient' => 1, 'state' => 'solid'],
            ['formula' => 'CO2', 'role' => 'product', 'coefficient' => 1, 'state' => 'gas'],
        ],
    ],
    [
        'name' => 'Phản ứng giữa bạc nitrat và natri clorua',
        'equation' => 'AgNO₃ + NaCl → AgCl↓ + NaNO₃',
        'type' => 'precipitation',
        'conditions' => 'Nhiệt độ thường, trong dung dịch',
        'precipitate' => 'Kết tủa trắng AgCl, không tan trong axit',
        'explanation' => 'Ion Ag⁺ kết hợp với ion Cl⁻ tạo thành kết tủa bạc clorua (AgCl) màu trắng, không tan trong nước và không tan trong axit nitric loãng.',
        'applications' => 'Phản ứng đặc trưng dùng để nhận biết ion clorua (Cl⁻) trong hóa phân tích định tính.',
        'difficulty' => 'medium',
        'grade_slug' => 'lop-9',
        'participants' => [
            ['formula' => 'AgNO3', 'role' => 'reactant', 'coefficient' => 1, 'state' => 'aqueous'],
            ['formula' => 'NaCl', 'role' => 'reactant', 'coefficient' => 1, 'state' => 'aqueous'],
        ],
    ],
    [
        'name' => 'Phản ứng oxi hóa khử giữa sắt và đồng(II) sunfat',
        'equation' => 'Fe + CuSO₄ → FeSO₄ + Cu',
        'type' => 'redox',
        'conditions' => 'Nhiệt độ thường, trong dung dịch',
        'color_change' => 'Dung dịch xanh lam nhạt dần, có lớp kim loại màu đỏ (Cu) bám trên đinh sắt',
        'explanation' => 'Sắt hoạt động hóa học mạnh hơn đồng nên đẩy được đồng ra khỏi dung dịch muối, đồng thời sắt bị oxi hóa (nhường electron) còn ion Cu²⁺ bị khử (nhận electron).',
        'applications' => 'Minh họa trực quan cho dãy hoạt động hóa học và bản chất phản ứng oxi hóa - khử.',
        'difficulty' => 'medium',
        'grade_slug' => 'lop-9',
        'participants' => [
            ['formula' => 'CuSO4', 'role' => 'reactant', 'coefficient' => 1, 'state' => 'aqueous'],
        ],
    ],
];

$gradeIdBySlug = fn (string $slug) => (int) $pdo->query(
    'SELECT id FROM grades WHERE slug = ' . $pdo->quote($slug)
)->fetchColumn();

$insertReaction = $pdo->prepare(
    'INSERT INTO chemistry_reactions
        (uuid, reaction_name, equation_display, reaction_type, conditions, temperature,
         color_change, gas_released, precipitate, explanation, applications, difficulty,
         grade_id, created_at, updated_at)
     VALUES
        (:uuid, :name, :equation, :type, :conditions, :temperature,
         :color_change, :gas_released, :precipitate, :explanation, :applications, :difficulty,
         :grade_id, NOW(), NOW())'
);

$insertParticipant = $pdo->prepare(
    'INSERT INTO chemistry_reaction_participants (reaction_id, compound_id, role, coefficient, physical_state, sort_order)
     VALUES (:reaction_id, :compound_id, :role, :coefficient, :state, :sort_order)'
);

$reactionCount = 0;

foreach ($reactions as $r) {
    $exists = $pdo->prepare('SELECT id FROM chemistry_reactions WHERE reaction_name = :name');
    $exists->execute(['name' => $r['name']]);

    if ($exists->fetchColumn()) {
        continue; // idempotent re-run
    }

    $insertReaction->execute([
        'uuid' => generate_uuid_v4(),
        'name' => $r['name'],
        'equation' => $r['equation'],
        'type' => $r['type'],
        'conditions' => $r['conditions'] ?? null,
        'temperature' => $r['temperature'] ?? null,
        'color_change' => $r['color_change'] ?? null,
        'gas_released' => $r['gas_released'] ?? null,
        'precipitate' => $r['precipitate'] ?? null,
        'explanation' => $r['explanation'],
        'applications' => $r['applications'] ?? null,
        'difficulty' => $r['difficulty'],
        'grade_id' => $gradeIdBySlug($r['grade_slug']),
    ]);

    $reactionId = (int) $pdo->lastInsertId();

    foreach ($r['participants'] as $order => $p) {
        if ($p['formula'] === null || !isset($compoundIdByFormula[$p['formula']])) {
            continue; // skip compounds not individually seeded above (e.g. Na2SO4, ZnCl2)
        }

        $insertParticipant->execute([
            'reaction_id' => $reactionId,
            'compound_id' => $compoundIdByFormula[$p['formula']],
            'role' => $p['role'],
            'coefficient' => $p['coefficient'],
            'state' => $p['state'],
            'sort_order' => $order,
        ]);
    }

    $reactionCount++;
}

echo "Đã tạo {$reactionCount} phản ứng hóa học mẫu.\n";
echo "\nHoàn tất seed dữ liệu Hóa học cốt lõi.\n";
