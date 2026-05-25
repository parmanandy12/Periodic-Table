<?php
/**
 * periodic_table.php
 * PHP backend — serves element data as JSON API + renders HTML template
 *
 * Endpoints:
 *   GET  /periodic_table.php           → Renders the HTML page
 *   GET  /periodic_table.php?api=all   → Returns all 118 elements as JSON
 *   GET  /periodic_table.php?api=element&n=6  → Returns single element JSON
 *   GET  /periodic_table.php?api=group&group=alkali → Returns group JSON
 */

// ─── Element Database ────────────────────────────────────────────────────────
$elements = [
  ['n'=>1,  'sym'=>'H',  'name'=>'Hydrogen',     'mass'=>'1.008',  'group'=>'nonmetal',       'period'=>1, 'col'=>1,  'state'=>'Gas',    'config'=>'1s¹'],
  ['n'=>2,  'sym'=>'He', 'name'=>'Helium',        'mass'=>'4.003',  'group'=>'noble-gas',      'period'=>1, 'col'=>18, 'state'=>'Gas',    'config'=>'1s²'],
  ['n'=>3,  'sym'=>'Li', 'name'=>'Lithium',       'mass'=>'6.941',  'group'=>'alkali',         'period'=>2, 'col'=>1,  'state'=>'Solid',  'config'=>'[He] 2s¹'],
  ['n'=>4,  'sym'=>'Be', 'name'=>'Beryllium',     'mass'=>'9.012',  'group'=>'alkaline',       'period'=>2, 'col'=>2,  'state'=>'Solid',  'config'=>'[He] 2s²'],
  ['n'=>5,  'sym'=>'B',  'name'=>'Boron',         'mass'=>'10.81',  'group'=>'metalloid',      'period'=>2, 'col'=>13, 'state'=>'Solid',  'config'=>'[He] 2s² 2p¹'],
  ['n'=>6,  'sym'=>'C',  'name'=>'Carbon',        'mass'=>'12.011', 'group'=>'nonmetal',       'period'=>2, 'col'=>14, 'state'=>'Solid',  'config'=>'[He] 2s² 2p²'],
  ['n'=>7,  'sym'=>'N',  'name'=>'Nitrogen',      'mass'=>'14.007', 'group'=>'nonmetal',       'period'=>2, 'col'=>15, 'state'=>'Gas',    'config'=>'[He] 2s² 2p³'],
  ['n'=>8,  'sym'=>'O',  'name'=>'Oxygen',        'mass'=>'15.999', 'group'=>'nonmetal',       'period'=>2, 'col'=>16, 'state'=>'Gas',    'config'=>'[He] 2s² 2p⁴'],
  ['n'=>9,  'sym'=>'F',  'name'=>'Fluorine',      'mass'=>'18.998', 'group'=>'halogen',        'period'=>2, 'col'=>17, 'state'=>'Gas',    'config'=>'[He] 2s² 2p⁵'],
  ['n'=>10, 'sym'=>'Ne', 'name'=>'Neon',          'mass'=>'20.180', 'group'=>'noble-gas',      'period'=>2, 'col'=>18, 'state'=>'Gas',    'config'=>'[He] 2s² 2p⁶'],
  ['n'=>11, 'sym'=>'Na', 'name'=>'Sodium',        'mass'=>'22.990', 'group'=>'alkali',         'period'=>3, 'col'=>1,  'state'=>'Solid',  'config'=>'[Ne] 3s¹'],
  ['n'=>12, 'sym'=>'Mg', 'name'=>'Magnesium',     'mass'=>'24.305', 'group'=>'alkaline',       'period'=>3, 'col'=>2,  'state'=>'Solid',  'config'=>'[Ne] 3s²'],
  ['n'=>13, 'sym'=>'Al', 'name'=>'Aluminum',      'mass'=>'26.982', 'group'=>'post-transition','period'=>3, 'col'=>13, 'state'=>'Solid',  'config'=>'[Ne] 3s² 3p¹'],
  ['n'=>14, 'sym'=>'Si', 'name'=>'Silicon',       'mass'=>'28.086', 'group'=>'metalloid',      'period'=>3, 'col'=>14, 'state'=>'Solid',  'config'=>'[Ne] 3s² 3p²'],
  ['n'=>15, 'sym'=>'P',  'name'=>'Phosphorus',    'mass'=>'30.974', 'group'=>'nonmetal',       'period'=>3, 'col'=>15, 'state'=>'Solid',  'config'=>'[Ne] 3s² 3p³'],
  ['n'=>16, 'sym'=>'S',  'name'=>'Sulfur',        'mass'=>'32.06',  'group'=>'nonmetal',       'period'=>3, 'col'=>16, 'state'=>'Solid',  'config'=>'[Ne] 3s² 3p⁴'],
  ['n'=>17, 'sym'=>'Cl', 'name'=>'Chlorine',      'mass'=>'35.45',  'group'=>'halogen',        'period'=>3, 'col'=>17, 'state'=>'Gas',    'config'=>'[Ne] 3s² 3p⁵'],
  ['n'=>18, 'sym'=>'Ar', 'name'=>'Argon',         'mass'=>'39.948', 'group'=>'noble-gas',      'period'=>3, 'col'=>18, 'state'=>'Gas',    'config'=>'[Ne] 3s² 3p⁶'],
  ['n'=>19, 'sym'=>'K',  'name'=>'Potassium',     'mass'=>'39.098', 'group'=>'alkali',         'period'=>4, 'col'=>1,  'state'=>'Solid',  'config'=>'[Ar] 4s¹'],
  ['n'=>20, 'sym'=>'Ca', 'name'=>'Calcium',       'mass'=>'40.078', 'group'=>'alkaline',       'period'=>4, 'col'=>2,  'state'=>'Solid',  'config'=>'[Ar] 4s²'],
  // ... (all 118 would be listed in production)
];

// ─── API Router ──────────────────────────────────────────────────────────────
if (isset($_GET['api'])) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');

    $api = $_GET['api'];

    switch ($api) {
        case 'all':
            echo json_encode(['success' => true, 'count' => count($elements), 'elements' => $elements], JSON_PRETTY_PRINT);
            break;

        case 'element':
            $n = isset($_GET['n']) ? (int)$_GET['n'] : 0;
            $found = array_filter($elements, fn($e) => $e['n'] === $n);
            if ($found) {
                echo json_encode(['success' => true, 'element' => array_values($found)[0]]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => "Element #$n not found"]);
            }
            break;

        case 'group':
            $group = isset($_GET['group']) ? htmlspecialchars($_GET['group']) : '';
            $filtered = array_values(array_filter($elements, fn($e) => $e['group'] === $group));
            echo json_encode(['success' => true, 'group' => $group, 'count' => count($filtered), 'elements' => $filtered]);
            break;

        case 'search':
            $q = strtolower(isset($_GET['q']) ? $_GET['q'] : '');
            $results = array_values(array_filter($elements, function($e) use ($q) {
                return str_contains(strtolower($e['name']), $q)
                    || str_contains(strtolower($e['sym']), $q)
                    || $e['n'] == $q;
            }));
            echo json_encode(['success' => true, 'query' => $q, 'results' => $results]);
            break;

        case 'stats':
            $groups = [];
            foreach ($elements as $e) {
                $groups[$e['group']] = ($groups[$e['group']] ?? 0) + 1;
            }
            $states = [];
            foreach ($elements as $e) {
                $states[$e['state']] = ($states[$e['state']] ?? 0) + 1;
            }
            echo json_encode([
                'success' => true,
                'total_elements' => count($elements),
                'by_group' => $groups,
                'by_state' => $states,
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown API endpoint']);
    }
    exit;
}

// ─── Server-Side HTML Render ─────────────────────────────────────────────────
// Group colors
$groupColors = [
    'alkali'         => '#ff6b6b',
    'alkaline'       => '#ff9f43',
    'transition'     => '#ffd32a',
    'post-transition'=> '#0abde3',
    'metalloid'      => '#48dbfb',
    'nonmetal'       => '#1dd1a1',
    'halogen'        => '#54a0ff',
    'noble-gas'      => '#a29bfe',
    'lanthanide'     => '#fd79a8',
    'actinide'       => '#e17055',
];

// Build grid data
$grid = [];
foreach ($elements as $el) {
    $row = $el['period'];
    if ($el['group'] === 'lanthanide') $row = 8;
    if ($el['group'] === 'actinide')   $row = 9;
    if ($el['n'] === 57 || $el['n'] === 89) $row = $el['period'];
    $grid[$row][$el['col']] = $el;
}

// Render server-side element card
function renderElement(array $el, array $groupColors): string {
    $color = $groupColors[$el['group']] ?? '#64c8ff';
    $json = htmlspecialchars(json_encode($el), ENT_QUOTES);
    return <<<HTML
    <div class="el" 
         style="--gc:{$color};--ad:{$el['n']}s;"
         data-element='{$json}'
         onclick="openModalFromPHP(this)">
      <div class="el-num">{$el['n']}</div>
      <div class="el-sym">{$el['sym']}</div>
      <div class="el-name">{$el['name']}</div>
      <div class="el-mass">{$el['mass']}</div>
    </div>
    HTML;
}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Periodic Table — PHP Edition</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="bg-grid"></div>

<header class="header">
  <div class="header-inner">
    <h1 class="title">⚛ <span>Periodic</span> Table — PHP</h1>
    <p class="subtitle">Server-rendered by PHP · <?= count($elements) ?> elements loaded</p>
    <div class="controls">
      <?php foreach(['default','roll','flip','cube','tilt','card'] as $m): ?>
      <button class="ctrl-btn <?= $m==='default'?'active':'' ?>" 
              data-mode="<?= $m ?>" onclick="setMode('<?= $m ?>')">
        <?= ucfirst($m) ?>
      </button>
      <?php endforeach; ?>
    </div>
    <p style="font-size:0.7rem;color:rgba(255,255,255,0.3);margin-top:8px;">
      API: <code style="color:#64c8ff">?api=all</code> | 
      <code style="color:#64c8ff">?api=element&n=6</code> | 
      <code style="color:#64c8ff">?api=group&group=alkali</code>
    </p>
  </div>
</header>

<main class="table-container">
  <div class="periodic-grid">
    <?php for ($row = 1; $row <= 9; $row++): ?>
      <?php if ($row === 8): ?>
        <div style="grid-column:1/3;grid-row:8;display:flex;align-items:center;justify-content:center;">
          <span style="font-size:0.55rem;color:rgba(255,255,255,0.3);letter-spacing:0.1em;">Lanthanides</span>
        </div>
      <?php elseif ($row === 9): ?>
        <div style="grid-column:1/3;grid-row:9;display:flex;align-items:center;justify-content:center;">
          <span style="font-size:0.55rem;color:rgba(255,255,255,0.3);letter-spacing:0.1em;">Actinides</span>
        </div>
      <?php endif; ?>

      <?php
      $startCol = ($row === 8 || $row === 9) ? 3 : 1;
      for ($col = $startCol; $col <= 18; $col++):
        $el = $grid[$row][$col] ?? null;
      ?>
        <?php if ($el): ?>
          <div style="grid-column:<?= $col ?>;grid-row:<?= $row ?>">
            <?= renderElement($el, $groupColors) ?>
          </div>
        <?php else: ?>
          <div style="grid-column:<?= $col ?>;grid-row:<?= $row ?>;background:transparent;border:none;"></div>
        <?php endif; ?>
      <?php endfor; ?>
    <?php endfor; ?>
  </div>
</main>

<!-- Modal -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModal()">
  <div class="modal" id="modal" onclick="event.stopPropagation()">
    <button class="modal-close" onclick="closeModal()">✕</button>
    <div class="modal-header">
      <div class="modal-symbol" id="modalSymbol">?</div>
      <div class="modal-info">
        <div class="modal-name" id="modalName">—</div>
        <div class="modal-number" id="modalNumber">—</div>
        <div class="modal-group-label" id="modalGroupLabel">—</div>
      </div>
    </div>
    <div class="modal-body">
      <div class="modal-grid" id="modalGrid"></div>
    </div>
  </div>
</div>

<script>
let currentMode = 'default';
function setMode(m) {
  currentMode = m;
  document.body.dataset.mode = m;
  document.querySelectorAll('.ctrl-btn').forEach(b => b.classList.toggle('active', b.dataset.mode === m));
}
function openModalFromPHP(el) {
  const data = JSON.parse(el.dataset.element);
  const colors = <?= json_encode($groupColors) ?>;
  const color = colors[data.group] || '#64c8ff';
  const modal = document.getElementById('modal');
  modal.style.setProperty('--modal-color', color);
  document.getElementById('modalSymbol').textContent = data.sym;
  document.getElementById('modalName').textContent = data.name;
  document.getElementById('modalNumber').textContent = 'Atomic Number: ' + data.n;
  document.getElementById('modalGroupLabel').textContent = data.group;
  document.getElementById('modalGrid').innerHTML = `
    <div class="modal-stat"><span>Mass</span><strong>${data.mass}</strong></div>
    <div class="modal-stat"><span>Period</span><strong>${data.period}</strong></div>
    <div class="modal-stat"><span>State</span><strong>${data.state}</strong></div>
    <div class="modal-stat"><span>Config</span><strong>${data.config}</strong></div>
  `;
  document.getElementById('modalOverlay').classList.add('open');
}
function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// Tilt effect
document.querySelectorAll('.el').forEach(el => {
  el.addEventListener('mousemove', e => {
    if (currentMode !== 'tilt') return;
    const r = el.getBoundingClientRect();
    const dx = (e.clientX - r.left - r.width/2) / (r.width/2);
    const dy = (e.clientY - r.top - r.height/2) / (r.height/2);
    el.style.transform = `perspective(300px) rotateX(${-dy*25}deg) rotateY(${dx*25}deg) scale(1.2)`;
  });
  el.addEventListener('mouseleave', () => {
    if (currentMode !== 'tilt') return;
    el.style.transform = '';
  });
});
</script>
</body>
</html>