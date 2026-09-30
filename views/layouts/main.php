<?php
/**
 * =========================================
 * SIG-GCM — Sistema Integrado de Gestão
 * Guarda Civil Municipal
 * =========================================
 * SISTEMA DE TESTE — Projeto acadêmico (TG/TC)
 * Desenvolvido 100% por:
 * Autor   : Rafaela Cristina Alcântara da Silva
 * GitHub  : https://github.com/rafaela13c
 * LinkedIn: https://www.linkedin.com/in/rafaela-cas1308/
 * =========================================
 * Este sistema NÃO é oficial e NÃO será
 * utilizado pela GCM. Uso exclusivo para
 * fins de estudo acadêmico.
 * =========================================
 *//**
 * Layout principal do SIG-GCM
 */
$user   = Auth::user();
$perfil = $user['perfil'] ?? 'operador';
$flash  = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$perfilLabels = [
    'operador'      => 'Guarda Municipal',
    'inspetor'      => 'Inspetor',
    'subcomandante' => 'Subcomandante',
    'comandante'    => 'Comandante',
    'corregedor'    => 'Corregedor',
    'admin'         => 'Administrador',
];

$nomeArr = explode(' ', $user['nome'] ?? 'U');
$iniciais = strtoupper(substr($nomeArr[0],0,1) . (isset($nomeArr[1]) ? substr($nomeArr[1],0,1) : ''));
$urlAtual = Router::getUrl();

function nav($url, $icon, $label, $active = false) {
    $a = $active ? ' active' : '';
    $b = BASE_URL;
    return "<a href=\"$b/$url\" class=\"nav-item$a\"><i class=\"fa-solid fa-$icon nav-icon\"></i> $label</a>";
}
function navGroup($id, $icon, $label, $open, $content) {
    $o = $open ? 'open' : '';
    $b = BASE_URL;
    return "
    <div class=\"nav-group-toggle nav-item $o\" onclick=\"toggleNav('$id', this)\">
      <span style=\"display:flex;align-items:center;gap:.65rem\"><i class=\"fa-solid fa-$icon nav-icon\"></i> $label</span>
      <span class=\"chevron\">›</span>
    </div>
    <div class=\"nav-sub $o\" id=\"$id\">$content</div>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="Rafaela Cristina Alcântara da Silva — https://github.com/rafaela13c">
  <meta name="generator" content="SIG-GCM v<?= APP_VERSION ?> — CC BY-NC-SA 4.0 — <?= APP_FINGERPRINT ?>">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle).' — ' : '' ?><?= APP_NAME ?></title>
  <!-- Font Awesome 6 Free -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
  <link rel="icon" href="<?= BASE_URL ?>/assets/img/logo-gcm.png?v=<?= time() ?>" type="image/png">
</head>
<body>
<div class="app-wrapper">

  <!-- Overlay mobile -->
  <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

  <!-- ==================== SIDEBAR ==================== -->
  <aside class="sidebar" id="sidebar">
    <!-- Marca / Logo -->
    <div class="sidebar-brand">
      <div class="sidebar-brand-icon">
        <img src="<?= BASE_URL ?>/assets/img/logo-gcm.png?v=<?= time() ?>" alt="Logo GCM" style="width:42px;height:42px;object-fit:contain">
      </div>
      <div class="sidebar-brand-text">
        <strong><?= APP_NAME ?></strong>
        <small><?= APP_CITY ?></small>
      </div>
    </div>

    <!-- GitHub Link -->
    <div style="padding: 0 1.25rem 0.5rem; margin-top:-0.25rem; margin-bottom: 0.5rem; font-size: 0.78rem;">
      <a href="https://github.com/rafaela13c" target="_blank" style="color: var(--text-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem; font-weight: 500; opacity: 0.85; transition: opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.85'">
        <i class="fa-brands fa-github"></i> GitHub do Projeto
      </a>
    </div>

    <!-- Pesquisa no menu -->
    <div class="sidebar-search">
      <div class="sidebar-search-wrap">
        <i class="fas fa-search sidebar-search-icon"></i>
        <input
          type="text"
          id="navSearch"
          class="sidebar-search-input"
          placeholder="Buscar no menu..."
          autocomplete="off"
          spellcheck="false"
        >
      </div>
    </div>

    <!-- Navegação -->
    <nav class="sidebar-nav" id="sidebarNav">
      <div class="nav-no-results" id="navNoResults">Nenhuma opção encontrada.</div>

      <!-- Dashboard -->
      <div class="nav-section">
        <?= nav('dashboard','house','Dashboard',$urlAtual==='dashboard') ?>
      </div>

      <?php if (Auth::can('taloes.criar') || Auth::can('taloes.ver_todos')): ?>
      <!-- Operacional -->
      <div class="nav-section">
        <div class="nav-section-label">Operacional</div>
        <?php
        $sub = nav('taloes','list','Todos os Talões',$urlAtual==='taloes');
        if (Auth::can('taloes.criar')) $sub .= nav('taloes/novo','plus','Novo Talão',$urlAtual==='taloes/novo');
        $sub .= nav('taloes/mapa-calor','map-location-dot','Mapa de Calor',$urlAtual==='taloes/mapa-calor');
        echo navGroup('nav-taloes','file-lines','Talões de Ocorrência',str_starts_with($urlAtual,'taloes'),$sub);
        ?>
      </div>
      <?php endif; ?>

      <?php if (Auth::can('frota.checklists') || Auth::can('frota.cadastrar_viaturas')): ?>
      <!-- Frota -->
      <div class="nav-section">
        <div class="nav-section-label">Frota</div>
        <?php
        $sub = '';
        if (Auth::can('frota.cadastrar_viaturas')) $sub .= nav('frota/viaturas','truck','Viaturas',$urlAtual==='frota/viaturas');
        if (Auth::can('frota.checklists')) $sub .= nav('frota/checklists','clipboard-check','Checklists',$urlAtual==='frota/checklists');
        $sub .= nav('frota/abastecimentos','gas-pump','Abastecimentos',$urlAtual==='frota/abastecimentos');
        echo navGroup('nav-frota','car','Frota',str_starts_with($urlAtual,'frota'),$sub);
        ?>
      </div>
      <?php endif; ?>

      <!-- Central do Agente -->
      <div class="nav-section">
        <div class="nav-section-label">Central do Agente</div>
        <?php
        $sub  = nav('escalas','calendar-days','Escala Semanal',$urlAtual==='escalas');
        $sub .= nav('trocas','arrows-rotate','Trocas de Plantão',$urlAtual==='trocas');
        $open = str_starts_with($urlAtual,'escala')||str_starts_with($urlAtual,'troc')||str_starts_with($urlAtual,'banco');
        echo navGroup('nav-agente','user-shield','Central do Agente',$open,$sub);
        ?>
      </div>

      <?php if (Auth::can('ronda_escolar.registrar') || Auth::can('ronda_escolar.ver_todos')): ?>
      <!-- Ronda Escolar -->
      <div class="nav-section">
        <div class="nav-section-label">Ronda Escolar</div>
        <?php
        $sub  = nav('ronda-escolar','school','Escolas');
        $sub .= nav('ronda-escolar/registrar','eye','Registrar Ronda');
        echo navGroup('nav-ronda','shield-halved','Ronda Escolar',str_starts_with($urlAtual,'ronda-escolar'),$sub);
        ?>
      </div>
      <?php endif; ?>

      <?php if (Auth::can('maria_penha.visitar') || Auth::can('maria_penha.cadastrar_vitima')): ?>
      <!-- Maria da Penha -->
      <div class="nav-section">
        <div class="nav-section-label">Maria da Penha</div>
        <?php
        $sub  = nav('maria-penha','person','Vítimas',$urlAtual==='maria-penha');
        $sub .= nav('maria-penha/mapa','map-pin','Mapa');
        echo navGroup('nav-penha','scale-balanced','Maria da Penha',str_starts_with($urlAtual,'maria-penha'),$sub);
        ?>
      </div>
      <?php endif; ?>

      <?php if (Auth::can('corregedoria.ver')): ?>
      <!-- Corregedoria -->
      <div class="nav-section">
        <div class="nav-section-label">Corregedoria</div>
        <?= nav('corregedoria','gavel','Processos Disciplinares',str_starts_with($urlAtual,'corregedoria')) ?>
      </div>
      <?php endif; ?>

      <!-- Comunicados -->
      <div class="nav-section">
        <div class="nav-section-label">Comunicações</div>
        <?= nav('comunicados','bullhorn','Comunicados',str_starts_with($urlAtual,'comunicados')) ?>
      </div>

      <!-- Relatórios -->
      <?php if (Auth::can('relatorios.basicos') || Auth::can('relatorios.gerenciais')): ?>
      <div class="nav-section">
        <div class="nav-section-label">Relatórios</div>
        <?php
        $sub  = nav('relatorios','chart-pie','Dashboard Analítico',  $urlAtual==='relatorios');
        $sub .= nav('relatorios/taloes','file-alt','Talões',           $urlAtual==='relatorios/taloes');
        $sub .= nav('relatorios/escalas','calendar-alt','Escalas',     $urlAtual==='relatorios/escalas');
        $sub .= nav('relatorios/frota','car','Frota',                  $urlAtual==='relatorios/frota');
        $sub .= nav('relatorios/maria-penha','shield-halved','Maria da Penha', $urlAtual==='relatorios/maria-penha');
        $sub .= nav('relatorios/ronda-escolar','graduation-cap','Ronda Escolar', $urlAtual==='relatorios/ronda-escolar');
        if (Auth::can('relatorios.gerenciais')) {
          $sub .= nav('relatorios/corregedoria','gavel','Corregedoria',  $urlAtual==='relatorios/corregedoria');
          $sub .= nav('relatorios/auditoria','history','Auditoria',      $urlAtual==='relatorios/auditoria');
        }
        echo navGroup('nav-relatorios','chart-bar','Relatórios',str_starts_with($urlAtual,'relatorios'),$sub);
        ?>
      </div>
      <?php endif; ?>

      <!-- Utilidades -->

      <div class="nav-section">
        <div class="nav-section-label">Utilidades</div>
        <?= nav('clima','cloud-sun','Clima — Eldorado dos Ventos',str_starts_with($urlAtual,'clima')) ?>
        <?= nav('consultas','magnifying-glass-chart','Consultas Integradas',str_starts_with($urlAtual,'consultas')) ?>
      </div>

      <?php if (Auth::can('usuarios.criar')): ?>
      <!-- Administração -->
      <div class="nav-section">
        <div class="nav-section-label">Administração</div>
        <?= nav('usuarios','users','Usuários',str_starts_with($urlAtual,'usuarios')) ?>
        <?php if (Auth::can('auditoria.ver')): ?>
        <?php
        $sub  = nav('auditoria','history','Log de Ações',$urlAtual==='auditoria');
        $sub .= nav('auditoria/logins','lock','Log de Logins',$urlAtual==='auditoria/logins');
        echo navGroup('nav-audit','magnifying-glass','Auditoria',str_starts_with($urlAtual,'auditoria'),$sub);
        ?>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($perfil !== 'operador'): ?>
      <!-- Módulo Ajuda -->
      <div class="nav-section">
        <a href="#" class="nav-item" onclick="openAiChat(event)" style="border-left-color: var(--primary-light);">
          <i class="fa-solid fa-robot nav-icon" style="color: var(--primary-light);"></i>
          Modo Ajuda <span class="ai-badge">IA</span>
        </a>
      </div>
      <?php endif; ?>

    </nav>

    <!-- Usuário logado -->
    <div class="sidebar-user">
      <div class="sidebar-user-avatar">
        <?php if (!empty($user['foto'])): ?>
          <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($user['foto']) ?>" alt="">
        <?php else: ?>
          <?= $iniciais ?>
        <?php endif; ?>
      </div>
      <div class="sidebar-user-info">
        <strong><?= htmlspecialchars($user['nome']) ?></strong>
        <span><?= $perfilLabels[$perfil] ?? $perfil ?></span>
      </div>
      <a href="<?= BASE_URL ?>/logout" class="sidebar-user-logout" title="Sair">
        <i class="fa-solid fa-right-from-bracket"></i>
      </a>
    </div>
  </aside>

  <!-- ==================== CONTEÚDO PRINCIPAL ==================== -->
  <div class="main-content">

    <!-- Topbar -->
    <header class="topbar">
      <button class="topbar-toggle" onclick="openSidebar()" aria-label="Abrir menu">
        <i class="fa-solid fa-bars"></i>
      </button>
      <div class="topbar-title">
        <?= isset($pageTitle) ? htmlspecialchars($pageTitle) : APP_NAME ?>
      </div>
      <div class="topbar-actions">
        <!-- Toggle dark/light mode -->
        <button class="topbar-btn" id="themeToggle" onclick="toggleTheme()" title="Alternar modo claro/escuro">
          <i class="fa-solid fa-moon" id="themeIcon"></i>
        </button>
        <a href="<?= BASE_URL ?>/comunicados" class="topbar-btn" title="Comunicados">
          <i class="fa-solid fa-bullhorn"></i>
        </a>
        <a href="<?= BASE_URL ?>/logout" class="topbar-btn" title="Sair">
          <i class="fa-solid fa-right-from-bracket"></i>
        </a>
      </div>
    </header>

    <!-- Flash Message -->
    <?php if ($flash): ?>
    <div class="toast-container" id="toastContainer">
      <?php
        $ft = $flash['type'];
        $fic = $ft === 'error' ? 'circle-xmark' : ($ft === 'warning' ? 'triangle-exclamation' : 'circle-check');
        $fcls = $ft === 'error' ? 'toast-error' : ($ft === 'warning' ? 'toast-warning' : 'toast-success');
      ?>
      <div class="toast <?= $fcls ?>" id="mainToast">
        <span class="toast-icon"><i class="fa-solid fa-<?= $fic ?>"></i></span>
        <div class="toast-body"><?= htmlspecialchars($flash['message']) ?></div>
        <button class="toast-close" onclick="dismissToast('mainToast')"><i class="fa-solid fa-xmark"></i></button>
      </div>
    </div>
    <?php endif; ?>

    <!-- Conteúdo da página -->
    <main class="page-content">
      <?= $content ?>
    </main>

  </div>

</div>

<script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= time() ?>"></script>
<script>
// ── Sidebar Nav Search ──────────────────────────────────────────────────────
(function() {
  const input   = document.getElementById('navSearch');
  const nav     = document.getElementById('sidebarNav');
  const noRes   = document.getElementById('navNoResults');
  if (!input || !nav) return;

  input.addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();

    if (!q) {
      // Reset: show everything, collapse groups back to natural state
      nav.querySelectorAll('.nav-section').forEach(s => s.classList.remove('nav-hidden'));
      nav.querySelectorAll('.nav-item').forEach(el => el.classList.remove('nav-hidden'));
      nav.querySelectorAll('.nav-sub').forEach(sub => {
        // Reopen only the originally-open ones
        if (!sub.dataset.wasOpen) sub.classList.remove('open');
      });
      noRes.style.display = 'none';
      return;
    }

    let anyVisible = false;

    // Look at every nav-section
    nav.querySelectorAll('.nav-section').forEach(function(section) {
      // Items in this section
      const items = section.querySelectorAll('a.nav-item, .nav-sub a.nav-item');
      let sectionHasMatch = false;

      items.forEach(function(item) {
        const text = item.textContent.trim().toLowerCase();
        if (text.includes(q)) {
          item.classList.remove('nav-hidden');
          sectionHasMatch = true;
          anyVisible = true;
          // Expand parent accordion if inside a sub-nav
          const sub = item.closest('.nav-sub');
          if (sub) sub.classList.add('open');
        } else {
          item.classList.add('nav-hidden');
        }
      });

      // Also check the toggle labels (group titles)
      const toggle = section.querySelector('.nav-group-toggle');
      if (toggle) {
        const toggleText = toggle.textContent.trim().toLowerCase();
        if (toggleText.includes(q)) {
          // Show all items in this section when the group title matches
          items.forEach(i => i.classList.remove('nav-hidden'));
          sectionHasMatch = true;
          anyVisible = true;
          const sub = section.querySelector('.nav-sub');
          if (sub) sub.classList.add('open');
        }
        // Hide the toggle itself only if nothing in the section matches
        toggle.classList.toggle('nav-hidden', !sectionHasMatch);
      }

      section.classList.toggle('nav-hidden', !sectionHasMatch);
    });

    noRes.style.display = anyVisible ? 'none' : 'block';
  });

  // Save originally-open states on page load
  nav.querySelectorAll('.nav-sub.open').forEach(sub => sub.dataset.wasOpen = '1');

  // Keyboard shortcut: Ctrl+K or / focuses the search
  document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey && e.key === 'k') || (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA')) {
      e.preventDefault();
      input.focus();
      input.select();
    }
  });
})();
</script>

<?php if ($perfil !== 'operador'): ?>
<!-- ==================== MODO AJUDA (IA) ==================== -->
<div class="ai-overlay" id="aiOverlay" onclick="closeAiChat()"></div>

<button class="ai-fab" id="aiFab" onclick="openAiChat(event)" title="Modo Ajuda (IA)">
  <i class="fa-solid fa-robot"></i>
</button>

<div class="ai-drawer" id="aiDrawer">
  <div class="ai-drawer-header">
    <div class="ai-drawer-title">
      <i class="fa-solid fa-robot"></i> Modo Ajuda <span class="ai-badge">IA</span>
    </div>
    <button class="ai-drawer-close" onclick="closeAiChat()"><i class="fa-solid fa-xmark"></i></button>
  </div>
  
  <div class="ai-drawer-body" id="aiDrawerBody">
    <div class="ai-msg ai-msg-bot">
      <div class="ai-msg-avatar"><i class="fa-solid fa-robot"></i></div>
      <div class="ai-msg-bubble">
        <p>Olá, <strong><?= htmlspecialchars($user['nome']) ?></strong>! Eu sou o assistente virtual do <strong>SIG-GCM</strong>.</p>
        <p>Estou pronto para te ajudar com dúvidas de uso deste sistema acadêmico de testes do SIG-GCM. Como posso te orientar?</p>
      </div>
    </div>
    
    <div class="ai-chips-container" id="aiChipsContainer">
      <div class="ai-chip" onclick="sendQuickQuestion('Como registrar um talão de ocorrência?')">Como fazer ocorrência?</div>
      <div class="ai-chip" onclick="sendQuickQuestion('Como funciona a escala semanal 12x36 e a escala adm 5x2?')">Como funciona a escala?</div>
      <div class="ai-chip" onclick="sendQuickQuestion('Como registrar a ronda escolar?')">Como registrar ronda?</div>
      <div class="ai-chip" onclick="sendQuickQuestion('Quem criou o sistema SIG-GCM?')">Quem criou o sistema?</div>
    </div>
  </div>
  
  <div class="ai-drawer-body-typing ai-msg ai-msg-bot" id="aiTypingIndicator" style="display: none; padding-top: 0; min-height: unset; margin-top: -0.5rem; animation: none;">
    <div class="ai-msg-avatar"><i class="fa-solid fa-robot"></i></div>
    <div class="ai-msg-bubble" style="padding: 0.4rem 0.8rem;">
      <div class="ai-typing">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
  </div>
  
  <div class="ai-drawer-footer">
    <div class="ai-input-wrap">
      <input type="text" id="aiInput" class="ai-input" placeholder="Pergunte sobre o sistema..." autocomplete="off">
      <button class="ai-btn-send" id="aiBtnSend" onclick="submitAiMessage()" disabled>
        <i class="fa-solid fa-paper-plane"></i>
      </button>
    </div>
  </div>
</div>

<script>
  window.SIG_GCM_USER_NAME = <?= json_encode($user['nome']) ?>;
  window.SIG_GCM_USER_PERFIL = <?= json_encode($perfil) ?>;
  window.SIG_GCM_USER_PERFIL_LABEL = <?= json_encode($perfilLabels[$perfil] ?? $perfil) ?>;
  window.BASE_URL = <?= json_encode(BASE_URL) ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/ai-assistant.js?v=<?= time() ?>"></script>
<?php endif; ?>

</body>
</html>

