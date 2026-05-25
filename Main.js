// main.js — Interactive Periodic Table Logic

let currentMode = 'default';

// ═══════ INIT ═══════
document.addEventListener('DOMContentLoaded', () => {
  renderParticles();
  renderTable();
  renderMarkdownTable();
});

// ═══════ PARTICLES ═══════
function renderParticles() {
  const container = document.getElementById('particles');
  for (let i = 0; i < 50; i++) {
    const p = document.createElement('div');
    p.className = 'particle';
    p.style.cssText = `
      left: ${Math.random() * 100}%;
      top: ${Math.random() * 100}%;
      --dur: ${5 + Math.random() * 10}s;
      --delay: -${Math.random() * 10}s;
    `;
    container.appendChild(p);
  }
}

// ═══════ RENDER TABLE ═══════
function renderTable() {
  const grid = document.getElementById('periodicGrid');
  grid.innerHTML = '';
  const placed = new Set();

  // Create 9-row grid
  const cells = {}; // "row-col" -> element

  ELEMENTS.forEach(el => {
    const pos = getGridPos(el);
    cells[`${pos.row}-${pos.col}`] = el;
  });

  for (let row = 1; row <= 9; row++) {
    for (let col = 1; col <= 18; col++) {
      const key = `${row}-${col}`;
      const el = cells[key];

      if (row === 8 && col === 1) {
        // Lanthanide series label
        const sep = document.createElement('div');
        sep.className = 'lanthanide-label';
        sep.style.gridColumn = '1 / 3';
        sep.style.gridRow = '8';
        sep.innerHTML = '<span style="font-size:0.55rem;color:rgba(255,255,255,0.3);letter-spacing:0.1em;">Lanthanides</span>';
        grid.appendChild(sep);
        col = 2; continue;
      }

      if (row === 9 && col === 1) {
        const sep = document.createElement('div');
        sep.className = 'actinide-label';
        sep.style.gridColumn = '1 / 3';
        sep.style.gridRow = '9';
        sep.innerHTML = '<span style="font-size:0.55rem;color:rgba(255,255,255,0.3);letter-spacing:0.1em;">Actinides</span>';
        grid.appendChild(sep);
        col = 2; continue;
      }

      const cell = document.createElement('div');
      cell.style.gridColumn = col;
      cell.style.gridRow = row;

      if (el) {
        const color = GROUP_COLORS[el.group] || '#64c8ff';
        cell.className = 'el';
        cell.style.setProperty('--gc', color);
        cell.style.setProperty('--ad', `${(el.n * 0.03) % 4}s`);
        cell.dataset.n = el.n;
        cell.innerHTML = `
          <div class="el-num">${el.n}</div>
          <div class="el-sym">${el.sym}</div>
          <div class="el-name">${el.name}</div>
          <div class="el-mass">${el.mass}</div>
        `;
        cell.addEventListener('click', () => openModal(el));
        cell.addEventListener('mousemove', handleTilt);
        cell.addEventListener('mouseleave', resetTilt);
      } else {
        cell.className = 'el-empty';
        cell.style.cssText = 'background:transparent;border:none;';
      }
      grid.appendChild(cell);
    }
  }
}

// ═══════ TILT HANDLER ═══════
function handleTilt(e) {
  if (currentMode !== 'tilt') return;
  const el = e.currentTarget;
  const rect = el.getBoundingClientRect();
  const cx = rect.left + rect.width / 2;
  const cy = rect.top + rect.height / 2;
  const dx = (e.clientX - cx) / (rect.width / 2);
  const dy = (e.clientY - cy) / (rect.height / 2);
  const rotX = -dy * 25;
  const rotY = dx * 25;
  el.style.transform = `perspective(300px) rotateX(${rotX}deg) rotateY(${rotY}deg) scale(1.2)`;
  el.style.boxShadow = `${-dx * 10}px ${-dy * 10}px 25px color-mix(in srgb, var(--gc) 50%, transparent)`;
}

function resetTilt(e) {
  if (currentMode !== 'tilt') return;
  const el = e.currentTarget;
  el.style.transform = '';
  el.style.boxShadow = '';
}

// ═══════ SET MODE ═══════
function setMode(mode) {
  currentMode = mode;
  document.body.dataset.mode = mode;
  document.querySelectorAll('.ctrl-btn').forEach(b => {
    b.classList.toggle('active', b.dataset.mode === mode);
  });
}

// ═══════ MODAL ═══════
function openModal(el) {
  const color = GROUP_COLORS[el.group] || '#64c8ff';
  const overlay = document.getElementById('modalOverlay');
  const modal = document.getElementById('modal');

  modal.style.setProperty('--modal-color', color);
  document.getElementById('modalSymbol').textContent = el.sym;
  document.getElementById('modalName').textContent = el.name;
  document.getElementById('modalNumber').textContent = `Atomic Number: ${el.n}`;
  document.getElementById('modalGroupLabel').textContent = formatGroup(el.group);
  document.getElementById('mMass').textContent = el.mass;
  document.getElementById('mPeriod').textContent = el.period;
  document.getElementById('mGroupN').textContent = el.col <= 2 ? el.col : el.col;
  document.getElementById('mElectrons').textContent = el.n;
  document.getElementById('mState').textContent = el.state;
  document.getElementById('mConfig').textContent = el.config;
  document.getElementById('modalDesc').textContent = el.desc;

  renderBohr(el, color);
  overlay.classList.add('open');
}

function closeModal() {
  document.getElementById('modalOverlay').classList.remove('open');
}

function formatGroup(g) {
  const map = {
    'alkali': 'Alkali Metal', 'alkaline': 'Alkaline Earth Metal',
    'transition': 'Transition Metal', 'post-transition': 'Post-Transition Metal',
    'metalloid': 'Metalloid', 'nonmetal': 'Nonmetal',
    'halogen': 'Halogen', 'noble-gas': 'Noble Gas',
    'lanthanide': 'Lanthanide', 'actinide': 'Actinide'
  };
  return map[g] || g;
}

// ═══════ BOHR MODEL ═══════
function renderBohr(el, color) {
  const container = document.getElementById('bohrModel');
  const shells = getElectronShells(el.n);
  const cx = 60, cy = 60;
  const maxR = 55;
  const radii = shells.map((_, i) => 12 + (i * (maxR - 12)) / Math.max(shells.length - 1, 1));

  let svg = `<svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
    <defs>
      <filter id="glow">
        <feGaussianBlur stdDeviation="2" result="coloredBlur"/>
        <feMerge><feMergeNode in="coloredBlur"/><feMergeNode in="SourceGraphic"/></feMerge>
      </filter>
    </defs>`;

  // Orbits
  radii.forEach(r => {
    svg += `<circle cx="${cx}" cy="${cy}" r="${r}" class="bohr-orbit"/>`;
  });

  // Nucleus
  svg += `<circle cx="${cx}" cy="${cy}" r="${Math.min(8, 4 + shells.length)}px" fill="${color}" filter="url(#glow)" style="animation:symbolPulse 2s infinite alternate"/>`;
  svg += `<text x="${cx}" y="${cy + 4}" text-anchor="middle" font-size="6" fill="rgba(255,255,255,0.8)" font-family="Orbitron">${el.n}</text>`;

  // Electrons on orbits
  shells.forEach((count, i) => {
    const r = radii[i];
    const duration = 2 + i * 1.5;
    const delay = -i * 0.8;

    for (let j = 0; j < count; j++) {
      const angle = (j / count) * 2 * Math.PI;
      const ex = cx + r * Math.cos(angle);
      const ey = cy + r * Math.sin(angle);
      svg += `
        <g class="electron-path" style="animation-duration:${duration}s;animation-delay:${delay}s">
          <circle cx="${ex}" cy="${ey}" r="2.5" fill="${color}" opacity="0.9" filter="url(#glow)"/>
        </g>`;
    }
  });

  svg += '</svg>';
  container.innerHTML = svg;
}

function getElectronShells(n) {
  const maxPerShell = [2, 8, 18, 32, 32, 18, 8, 2];
  const shells = [];
  let remaining = n;
  for (const cap of maxPerShell) {
    if (remaining <= 0) break;
    const placed = Math.min(remaining, cap);
    shells.push(placed);
    remaining -= placed;
  }
  return shells;
}

// ═══════ KEYBOARD ═══════
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeModal();
});

// ═══════ MARKDOWN TABLE ═══════
function renderMarkdownTable() {
  const first20 = ELEMENTS.slice(0, 20);
  const headers = ['Atomic #', 'Element Name', 'Symbol', 'Atomic Mass', 'Group', 'Electron Config'];
  const widths = [9, 14, 8, 12, 20, 20];
  const pad = (s, w) => String(s).padEnd(w);

  let table = '| ' + headers.map((h, i) => pad(h, widths[i])).join(' | ') + ' |\n';
  table += '| ' + widths.map(w => '-'.repeat(w)).join(' | ') + ' |\n';

  const groupLabel = g => ({
    'alkali': 'Alkali Metal', 'alkaline': 'Alkaline Earth',
    'transition': 'Transition Metal', 'post-transition': 'Post-Transition',
    'metalloid': 'Metalloid', 'nonmetal': 'Nonmetal',
    'halogen': 'Halogen', 'noble-gas': 'Noble Gas'
  }[g] || g);

  first20.forEach(el => {
    const row = [el.n, el.name, el.sym, el.mass, groupLabel(el.group), el.config];
    table += '| ' + row.map((v, i) => pad(v, widths[i])).join(' | ') + ' |\n';
  });

  document.getElementById('markdownTable').textContent = table;
}

// ═══════ COPY PROMPT ═══════
function copyPrompt() {
  const text = document.getElementById('aiPrompt').textContent;
  navigator.clipboard.writeText(text).then(() => {
    const btn = document.querySelector('.copy-btn');
    btn.textContent = '✅ Copied!';
    btn.classList.add('copied');
    setTimeout(() => {
      btn.textContent = '📋 Copy Prompt';
      btn.classList.remove('copied');
    }, 2000);
  });
}
