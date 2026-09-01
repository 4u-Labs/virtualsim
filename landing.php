<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
$assetVersion = time();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>VirtualSim — Ativação por SMS em Tempo Real</title>
    <meta name="description" content="Ative WhatsApp, Telegram, Google e outros serviços sem chip físico. Números virtuais a partir de R$ 0,50. 100% online.">
    <!-- Open Graph / WhatsApp / Facebook Share Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="VirtualSim">
    <meta property="og:url" content="https://4u.ia.br/app/virtualsim/">
    <meta property="og:title" content="VirtualSim — Números Virtuais e SMS no Brasil">
    <meta property="og:description" content="Ative seu WhatsApp, Telegram e apps com números de chips físicos do Brasil sem expor seu telefone pessoal. Estorno 100% garantido!">
    <meta property="og:image" content="https://4u.ia.br/app/virtualsim/og-image.jpg">
    <meta property="og:image:secure_url" content="https://4u.ia.br/app/virtualsim/og-image.jpg">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="675">

    <!-- Twitter / X Share Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://4u.ia.br/app/virtualsim/">
    <meta name="twitter:title" content="VirtualSim — Números Virtuais e SMS no Brasil">
    <meta name="twitter:description" content="Ative seu WhatsApp, Telegram e apps com números de chips físicos do Brasil sem expor seu telefone pessoal. Estorno 100% garantido!">
    <meta name="twitter:image" content="https://4u.ia.br/app/virtualsim/og-image.jpg">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Dedicated Stylesheet Dynamic Anti-Cache -->
    <link rel="stylesheet" href="landing.css?v=<?php echo $assetVersion; ?>">
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512' fill='%230ea5e9'%3E%3Cpath d='M416 128H384v-32c0-35.3-28.7-64-64-64h-32V0h-32v32h-32V0h-32v32h-32c-35.3 0-64 28.7-64 64v32H96c-17.7 0-32 14.3-32 32v32H32v32h32v32H32v32h32v32c0 17.7 14.3 32 32 32h32v32c0 35.3 28.7 64 64 64h32v32h32v-32h32v32h32v-32h32c35.3 0 64-28.7 64-64v-32h32c17.7 0 32-14.3 32-32v-32h32v-32h-32v-32h32v-32h-32v-32c0-17.7-14.3-32-32-32zM352 352H160V160h192v192z'/%3E%3C/svg%3E">
</head>
<body>

    <!-- Header / Navbar -->
    <header class="navbar">
        <div class="container">
            <div class="logo">
                <i class="fa-solid fa-microchip logo-icon"></i>
                <span class="logo-text">Virtual<span class="text-highlight">Sim</span></span>
            </div>
            
            <nav class="nav-links">
                <a href="#beneficios" class="nav-link" data-i18n="nav_benefits">Benefícios</a>
                <a href="#precos" class="nav-link" data-i18n="nav_pricing">Preços</a>
                <a href="#como-funciona" class="nav-link" data-i18n="nav_how_it_works">Como Funciona</a>
                <a href="#faq" class="nav-link" data-i18n="nav_faq">Dúvidas</a>
            </nav>

            <div class="nav-actions">
                <!-- Language Switcher -->
                <div class="lang-switcher">
                    <button onclick="setLanguage('pt')" class="lang-btn active" id="lang-btn-pt">PT</button>
                    <span class="lang-divider">|</span>
                    <button onclick="setLanguage('en')" class="lang-btn" id="lang-btn-en">EN</button>
                </div>
                <a href="index.php" class="btn btn-primary btn-sm" data-i18n="nav_panel">Acessar Painel</a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container hero-grid">
            <div class="hero-content">
                <div class="badge badge-hero" data-i18n="hero_badge">Ativação Online Instantânea</div>
                <h1 class="hero-title" data-i18n="hero_title">O fim do chip físico.<br>Crie sua segunda conta em 3 minutos.</h1>
                <p class="hero-subtitle" data-i18n="hero_subtitle">
                    Ative WhatsApp, Telegram, Gmail e qualquer outra plataforma usando números temporários sem mensalidades ou chips físicos. Opções a partir de R$ 0,50.
                </p>

                <div class="hero-cta-group">
                    <a href="index.php" class="btn btn-primary btn-lg" data-i18n="btn_start">
                        <i class="fa-solid fa-bolt"></i> Começar Agora Gratuitamente
                    </a>
                    <a href="#faq" class="btn btn-outline btn-lg" data-i18n="btn_faq">
                        <i class="fa-solid fa-circle-question"></i> Tirar Dúvidas
                    </a>
                </div>

                <div class="hero-metrics">
                    <div class="metric">
                        <span class="metric-value">180+</span>
                        <span class="metric-label" data-i18n="metric_countries">Países Atendidos</span>
                    </div>
                    <div class="metric-divider"></div>
                    <div class="metric">
                        <span class="metric-value">R$ 0,50</span>
                        <span class="metric-label" data-i18n="metric_min_price">Preço Mínimo</span>
                    </div>
                    <div class="metric-divider"></div>
                    <div class="metric">
                        <span class="metric-value">0%</span>
                        <span class="metric-label" data-i18n="metric_no_fee">Sem Mensalidade</span>
                    </div>
                </div>
            </div>

            <!-- Hero Mockup/Visual -->
            <div class="hero-visual">
                <div class="card-mockup shadow-glow">
                    <div class="mockup-header">
                        <span class="dot red"></span>
                        <span class="dot yellow"></span>
                        <span class="dot green"></span>
                        <span class="mockup-title" data-i18n="mockup_title">Painel VirtualSim</span>
                    </div>
                    <div class="mockup-body">
                        <div class="mockup-item">
                            <div class="mockup-item-left">
                                <i class="fa-brands fa-whatsapp whatsapp-color text-xl"></i>
                                <div>
                                    <strong>WhatsApp (Brasil 🇧🇷)</strong>
                                    <p class="text-xs text-muted" data-i18n="mockup_phone">Número Gerado</p>
                                </div>
                            </div>
                            <span class="badge badge-success">+55 11 98842-1029</span>
                        </div>

                        <div class="mockup-sms-box">
                            <div class="sms-pulse">
                                <i class="fa-solid fa-message text-accent"></i>
                                <span data-i18n="mockup_sms_waiting">Aguardando SMS...</span>
                            </div>
                            <div class="sms-code-preview">
                                <span class="sms-label" data-i18n="mockup_sms_received">Código SMS Detectado</span>
                                <span class="sms-code font-mono">829 - 401</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="section section-benefits" id="beneficios">
        <div class="container">
            <div class="section-header text-center">
                <h2 class="section-title" data-i18n="benefits_title">Por que usar o VirtualSim?</h2>
                <p class="section-subtitle" data-i18n="benefits_subtitle">Nossa tecnologia oferece a melhor alternativa para proteger seus dados e expandir suas contas online com segurança.</p>
            </div>

            <div class="grid grid-3">
                <div class="card benefit-card">
                    <div class="card-icon text-accent bg-accent-light">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <h3 class="card-title" data-i18n="b1_title">Privacidade Total</h3>
                    <p class="card-text" data-i18n="b1_desc">Não exponha seu número pessoal em sites e cadastros genéricos, evitando spam, vazamento de dados e chamadas de telemarketing.</p>
                </div>

                <div class="card benefit-card">
                    <div class="card-icon text-success bg-success-light">
                        <i class="fa-solid fa-money-bill-wave"></i>
                    </div>
                    <h3 class="card-title" data-i18n="b2_title">Sem Mensalidades</h3>
                    <p class="card-text" data-i18n="b2_desc">Você paga apenas pelo SMS recebido. Não existem taxas recorrentes, planos de fidelidade ou cobranças de manutenção de chip.</p>
                </div>

                <div class="card benefit-card">
                    <div class="card-icon text-primary bg-primary-light">
                        <i class="fa-solid fa-sim-card"></i>
                    </div>
                    <h3 class="card-title" data-i18n="b3_title">Chips Físicos Reais</h3>
                    <p class="card-text" data-i18n="b3_desc">Ativações exclusivas com números móveis reais do Brasil (Vivo, TIM, Claro) com máxima taxa de aprovação no WhatsApp.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer py-8 border-t border-gray-800">
        <div class="container text-center text-xs text-gray-500 space-y-3">
            <p>&copy; <?php echo date("Y"); ?> VirtualSim. Todos os direitos reservados. Hospedado por <a href='https://4u.ia.br' target='_blank' class="text-sky-400">4u.ia.br</a>.</p>
            <div class="flex items-center justify-center gap-4 font-semibold text-gray-400">
                <a href="privacidade.php" target="_blank" class="hover:text-sky-400 transition-colors">Privacidade</a>
                <span>•</span>
                <a href="termos.php" target="_blank" class="hover:text-sky-400 transition-colors">Termos de Uso</a>
                <span>•</span>
                <a href="suporte.php" target="_blank" class="hover:text-sky-400 transition-colors">Suporte</a>
            </div>
        </div>
    </footer>

</body>
</html>
