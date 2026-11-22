<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
$cssVersion = @filemtime(__DIR__ . '/style-v1.css') ?: time();
$jsVersion  = @filemtime(__DIR__ . '/app.js') ?: time();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>VirtualSim — Números Virtuais e SMS em Tempo Real</title>
    <meta name="description" content="Ative WhatsApp, Telegram e outros serviços usando números virtuais instantâneos. 100% online e sem chip físico.">
    <link rel="canonical" href="https://4u.ia.br/app/virtualsim/">

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
    <!-- Main Style -->
    <link rel="stylesheet" href="style-v1.css?v=<?php echo $cssVersion; ?>">

    <!-- PWA Manifest & Meta Tags -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0b0f19">
    <link rel="apple-touch-icon" href="icon-192.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="VirtualSim">
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png?v=20261120-2">
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=20261120-2">
    <!-- Google Identity Services -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        .institutional-footer {
            margin-top: 40px;
            padding: 24px 0 32px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }
        .institutional-footer a {
            color: #38bdf8;
            text-decoration: none;
            font-weight: 600;
            margin: 0 4px;
            transition: color 0.2s;
        }
        .institutional-footer a:hover {
            color: #7dd3fc;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    <header class="navbar">
        <div class="container">
            <div class="logo">
                <img src="icon-192.png" alt="VirtualSim" class="logo-img" width="34" height="34">
                <span class="logo-text">Virtual<span class="text-highlight">Sim</span></span>
            </div>

            <div class="nav-actions" id="nav-actions">
                <!-- Language Switcher -->
                <div class="lang-switcher">
                    <button type="button" onclick="setLanguage('pt')" class="lang-btn active" id="lang-btn-pt">PT</button>
                    <span class="lang-divider">|</span>
                    <button type="button" onclick="setLanguage('en')" class="lang-btn" id="lang-btn-en">EN</button>
                </div>
                <!-- 4u-Labs Ecosystem Button -->
                <button type="button" class="btn btn-outline btn-sm" onclick="openLabsModal()" title="4u-Labs" style="padding: 6px 11px; gap: 6px; border-color: rgba(148, 163, 184, 0.3); color: #cbd5e1;">
                    <i class="fa-solid fa-table-cells"></i> <span>4u-Labs</span>
                </button>
                <!-- Share WebApp Button -->
                <button type="button" class="btn btn-outline btn-sm btn-share" onclick="shareWebApp()" title="Compartilhar" id="btn-share-app" style="padding: 6px 12px; gap: 6px; border-color: rgba(56, 189, 248, 0.3); color: #38bdf8;">
                    <i class="fa-solid fa-share-nodes"></i> <span data-i18n="nav_share">Compartilhar</span>
                </button>
                <!-- Logged Out State -->
                <div class="auth-buttons" id="auth-buttons">
                    <button type="button" class="btn btn-nav-login" onclick="openAuthModal('login')">
                        <i class="fa-solid fa-right-to-bracket"></i> <span data-i18n="nav_login">Entrar</span>
                    </button>
                    <button type="button" class="btn btn-nav-register" onclick="openAuthModal('register')">
                        <i class="fa-solid fa-user-plus"></i> <span data-i18n="nav_register">Criar Conta</span>
                    </button>
                </div>
                <!-- Logged In State (Hidden by default) -->
                <div class="user-profile hidden" id="user-profile">
                    <div class="balance-display">
                        <span class="balance-label" data-i18n="nav_balance">Saldo:</span>
                        <span class="balance-value" id="header-balance">R$ 0,00</span>
                    </div>
                    <button type="button" class="btn btn-accent btn-sm" onclick="openReloadModal()">
                        <i class="fa-solid fa-plus-circle"></i> <span data-i18n="nav_reload">Recarregar</span>
                    </button>
                    <img id="header-avatar" class="user-avatar hidden" src="" alt="" referrerpolicy="no-referrer">
                    <span class="user-name" id="header-username">Usuario</span>
                    <button type="button" class="btn btn-icon" onclick="logout()" title="Sair" id="btn-logout-title">
                        <i class="fa-solid fa-sign-out-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Dashboard Section -->
    <section class="main-content" id="solicitar">
        <div class="container">
            <div class="trust-strip" aria-label="Destaques VirtualSim">
                <span class="trust-pill"><i class="fa-solid fa-sim-card"></i> <span data-i18n="trust_chips">Chips físicos do Brasil</span></span>
                <span class="trust-pill"><i class="fa-solid fa-bolt"></i> <span data-i18n="trust_sms">SMS em segundos</span></span>
                <span class="trust-pill"><i class="fa-solid fa-shield-halved"></i> <span data-i18n="trust_refund">Estorno 100% automático</span></span>
                <span class="trust-pill"><i class="fa-solid fa-qrcode"></i> <span data-i18n="trust_pix">PIX instantâneo</span></span>
            </div>
        </div>
        <div class="container grid-layout">

            <!-- Left Side: Order Panel -->
            <div class="column-left">
                <section class="panel panel-order">
                    <h2 class="panel-title"><i class="fa-solid fa-shopping-cart"></i> <span data-i18n="panel_buy_title">Solicitar Número Virtual</span></h2>

                    <!-- Step 1: Select Country -->
                    <div class="form-group">
                        <label class="form-label" for="select-country" data-i18n="panel_buy_lbl_country">1. Escolha o País do Número:</label>
                        <div class="custom-select-wrapper">
                            <select id="select-country" class="form-control">
                                <option value="brazil" data-flag="🇧🇷" data-i18n="country_brazil" selected>Brasil 🇧🇷 (Chips Físicos Ativos)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Step 2: Select Service -->
                    <div class="form-group">
                        <label class="form-label" data-i18n="panel_buy_lbl_service">2. Selecione o Serviço / Aplicativo:</label>
                        <div class="services-grid" id="services-grid">

                            <!-- WhatsApp Card -->
                            <div class="service-card active" data-service="whatsapp" onclick="selectService(this)">
                                <div class="service-icon"><i class="fa-brands fa-whatsapp text-success"></i></div>
                                <div class="service-details">
                                    <span class="service-name">WhatsApp</span>
                                    <div class="service-price-row">
                                        <span class="service-price" id="price-whatsapp">R$ 24,90</span>
                                        <span class="stock-badge stock-loading" id="stock-whatsapp">...</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Telegram Card -->
                            <div class="service-card" data-service="telegram" onclick="selectService(this)">
                                <div class="service-icon"><i class="fa-brands fa-telegram text-info"></i></div>
                                <div class="service-details">
                                    <span class="service-name">Telegram</span>
                                    <div class="service-price-row">
                                        <span class="service-price">R$ 2,90</span>
                                        <span class="stock-badge stock-loading" id="stock-telegram">...</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Google / Gmail Card -->
                            <div class="service-card" data-service="google" onclick="selectService(this)">
                                <div class="service-icon"><i class="fa-brands fa-google text-danger"></i></div>
                                <div class="service-details">
                                    <span class="service-name">Google / Gmail</span>
                                    <div class="service-price-row">
                                        <span class="service-price">R$ 1,50</span>
                                        <span class="stock-badge stock-loading" id="stock-google">...</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Microsoft Card -->
                            <div class="service-card" data-service="microsoft" onclick="selectService(this)">
                                <div class="service-icon"><i class="fa-brands fa-microsoft text-primary"></i></div>
                                <div class="service-details">
                                    <span class="service-name">Microsoft</span>
                                    <div class="service-price-row">
                                        <span class="service-price">R$ 1,50</span>
                                        <span class="stock-badge stock-loading" id="stock-microsoft">...</span>
                                    </div>
                                </div>
                            </div>

                            <!-- TikTok Card -->
                            <div class="service-card" data-service="tiktok" onclick="selectService(this)">
                                <div class="service-icon"><i class="fa-brands fa-tiktok text-light"></i></div>
                                <div class="service-details">
                                    <span class="service-name">TikTok</span>
                                    <div class="service-price-row">
                                        <span class="service-price">R$ 1,50</span>
                                        <span class="stock-badge stock-loading" id="stock-tiktok">...</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Economico Card -->
                            <div class="service-card" data-service="economico" onclick="selectService(this)">
                                <div class="service-icon"><i class="fa-solid fa-tags text-warning"></i></div>
                                <div class="service-details">
                                    <span class="service-name" data-i18n="service_economico">Serviço Econômico</span>
                                    <div class="service-price-row">
                                        <span class="service-price">R$ 0,50</span>
                                        <span class="stock-badge stock-loading" id="stock-economico">...</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Buy Action Button -->
                    <button type="button" class="btn btn-primary btn-block btn-lg" id="btn-request-number" onclick="requestNumber()">
                        <i class="fa-solid fa-key"></i> <span data-i18n="panel_buy_btn">Solicitar Número Agora</span>
                    </button>
                    <div class="info-note text-center mt-3">
                        <i class="fa-solid fa-shield-halved"></i> <span data-i18n="panel_buy_info">Você só paga se o código SMS for entregue!</span>
                    </div>
                </section>

                <!-- Instructions Panel -->
                <section class="panel panel-instructions">
                    <h3 class="panel-title panel-title-sm">
                        <i class="fa-solid fa-circle-info text-accent"></i> <span data-i18n="timeline_title">Como funciona? Passo a Passo</span>
                    </h3>
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-badge">1</div>
                            <div class="timeline-content">
                                <h4 data-i18n="timeline_s1_title">Abra o cadastro no WhatsApp Business</h4>
                                <p data-i18n="timeline_s1_desc">Utilize preferencialmente o aplicativo <strong>WhatsApp Business</strong> no celular (pois possui menos exigências e regras de validação mais flexíveis).</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-badge">2</div>
                            <div class="timeline-content">
                                <h4 data-i18n="timeline_s2_title">Solicite e copie o número</h4>
                                <p data-i18n="timeline_s2_desc">Selecione o serviço desejado acima, clique em <strong class="text-highlight">Solicitar Número Agora</strong> e copie-o.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-badge">3</div>
                            <div class="timeline-content">
                                <h4 data-i18n="timeline_s3_title">Envie o SMS de verificação</h4>
                                <p data-i18n="timeline_s3_desc">Cole o número no aplicativo. <em>Se informar que o número gerado já foi banido/recusado, basta clicar em <strong>Cancelar e Estornar Saldo</strong> (o valor volta 100% na hora) e solicitar um novo número!</em></p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-badge">4</div>
                            <div class="timeline-content">
                                <h4 data-i18n="timeline_s4_title">Copie o código e ative</h4>
                                <p data-i18n="timeline_s4_desc">Volte aqui, aguarde o código de 6 dígitos aparecer no card de status e copie-o para validar seu cadastro.</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-badge" style="background: #eab308; color: #000; font-weight: 800;">5</div>
                            <div class="timeline-content">
                                <h4 data-i18n="timeline_s5_title">🔒 Dica de Ouro: WhatsApp Business &amp; Blindagem (2FA)</h4>
                                <p data-i18n="timeline_s5_desc">Recomendamos usar o <strong>WhatsApp Business</strong> (possui menos exigências). Se o número gerado estiver bloqueado pelo WhatsApp, basta clicar em <em>Cancelar e Estornar Saldo</em> (o saldo volta na hora) e gerar outro! Após ativar, vá em <em>Configurações &gt; Conta &gt; Confirmação em Duas Etapas</em> e crie uma senha (PIN) com seu e-mail para garantir a conta 100% sua!</p>
                            </div>
                        </div>
                    </div>
                    <div class="timeline-footer">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span data-i18n="timeline_footer">Se o SMS não chegar em até 3 minutos, clique em <strong>Cancelar e Estornar Saldo</strong> para tentar com outro número gratuitamente!</span>
                    </div>
                </section>
            </div>

            <!-- Right Side: Active Numbers & History Dashboard -->
            <div class="column-right">
                <section class="panel panel-dashboard">
                    <h2 class="panel-title"><i class="fa-solid fa-list-check"></i> <span data-i18n="panel_orders_title">Seus Números Ativos</span></h2>

                    <div class="panel-tabs" role="tablist">
                        <button type="button" class="panel-tab-btn active" id="tab-btn-active" onclick="switchDashboardTab('active')">
                            <i class="fa-solid fa-bolt"></i>
                            <span data-i18n="tab_active">Ativos</span>
                            <span class="tab-count" id="tab-count-active">0</span>
                        </button>
                        <button type="button" class="panel-tab-btn" id="tab-btn-history" onclick="switchDashboardTab('history')">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <span data-i18n="tab_history">Histórico</span>
                            <span class="tab-count" id="tab-count-history">0</span>
                        </button>
                        <button type="button" class="panel-tab-btn" id="tab-btn-pix" onclick="switchDashboardTab('pix')">
                            <i class="fa-solid fa-wallet"></i>
                            <span data-i18n="tab_pix">Recargas PIX</span>
                            <span class="tab-count" id="tab-count-pix">0</span>
                        </button>
                    </div>

                    <!-- Tab 1: Active Orders -->
                    <div class="orders-container" id="orders-container">
                        <div class="empty-state" id="empty-orders-state">
                            <i class="fa-solid fa-mobile-screen empty-icon"></i>
                            <p class="empty-text" data-i18n="orders_empty_title">Você não possui números ativos no momento.</p>
                            <p class="empty-subtext" data-i18n="orders_empty_desc">Selecione as opções ao lado e clique em solicitar para gerar um novo número.</p>
                        </div>
                    </div>

                    <!-- Tab 2: SMS History -->
                    <div class="history-list hidden" id="history-container"></div>

                    <!-- Tab 3: PIX Recharges -->
                    <div class="history-list hidden" id="pix-history-container"></div>
                </section>

                <!-- Guarantee & Quick FAQ Panel (Balances Right Column) -->
                <section class="panel panel-faq">
                    <h3 class="panel-title panel-title-sm">
                        <i class="fa-solid fa-circle-question text-accent"></i> <span data-i18n="faq_title">Garantia &amp; Dúvidas Rápidas</span>
                    </h3>
                    <div class="faq-list">
                        <details class="faq-item" open>
                            <summary data-i18n="faq_q1">Se o SMS não chegar, eu perco meu saldo?</summary>
                            <p data-i18n="faq_a1">Não! Se o código não chegar ou o número for recusado, clique em <strong>Cancelar e Estornar Saldo</strong> e 100% do valor volta na hora para você tentar outro número.</p>
                        </details>
                        <details class="faq-item">
                            <summary data-i18n="faq_q2">Qual aplicativo usar para ativar WhatsApp?</summary>
                            <p data-i18n="faq_a2">Recomendamos o <strong>WhatsApp Business</strong>, que aceita ativações com muito menos restrições. Assim que ativar, configure a Confirmação em Duas Etapas (PIN + e-mail).</p>
                        </details>
                        <details class="faq-item">
                            <summary data-i18n="faq_q3">Em quanto tempo a recarga PIX cai?</summary>
                            <p data-i18n="faq_a3">A confirmação é automática via Mercado Pago e costuma creditar o saldo em poucos segundos após o pagamento.</p>
                        </details>
                        <details class="faq-item">
                            <summary data-i18n="faq_q4">E se eu fechar a página enquanto aguardo o SMS?</summary>
                            <p data-i18n="faq_a4">Sem problema! Seu número ativo fica salvo na sua conta e reaparece automaticamente com o cronômetro e o código assim que você voltar.</p>
                        </details>
                    </div>
                    <div class="guarantee-box">
                        <i class="fa-solid fa-circle-check"></i>
                        <span data-i18n="faq_guarantee"><strong>Compra protegida:</strong> você só é cobrado quando o código SMS é entregue com sucesso.</span>
                    </div>
                </section>
            </div>

        </div>

        <!-- Institutional Footer (4u-Labs Standard) -->
        <footer class="institutional-footer">
            <div class="container">
                <p>&copy; <?php echo date("Y"); ?> VirtualSim &bull; <strong>4u-Labs</strong> &bull; <span data-i18n="footer_rights">Todos os direitos reservados.</span></p>
                <div style="margin-top: 8px;">
                    <a href="privacidade.php" target="_blank" rel="noopener noreferrer" data-i18n="footer_privacy">Privacidade</a> &bull;
                    <a href="termos.php" target="_blank" rel="noopener noreferrer" data-i18n="footer_terms">Termos de Uso</a> &bull;
                    <a href="suporte.php" target="_blank" rel="noopener noreferrer" data-i18n="footer_support">Suporte</a> &bull;
                    <a href="https://4u.ia.br/loja/index.php" target="_blank" rel="noopener noreferrer" data-i18n="footer_store">Loja de Apps 4u-Labs</a> &bull;
                    <a href="https://www.paypal.com/ncp/payment/L7YRCS984T33N" target="_blank" rel="noopener noreferrer">♡ <span data-i18n="footer_donate">Apoiar</span></a>
                </div>
                <div class="footer-social-links">
                    <a href="https://4u.ia.br/" target="_blank" rel="noopener noreferrer">Portal 4U</a>
                    <a href="https://github.com/4u-Labs" target="_blank" rel="noopener noreferrer">GitHub</a>
                    <a href="https://4u-labs.github.io/" target="_blank" rel="noopener noreferrer">Blog Dev</a>
                    <a href="https://alternativeto.net/user/4u-ia-br/added/" target="_blank" rel="noopener noreferrer">AlternativeTo</a>
                    <a href="https://www.youtube.com/@fbr4g4" target="_blank" rel="noopener noreferrer">YouTube</a>
                    <a href="https://www.instagram.com/fbr4g4/" target="_blank" rel="noopener noreferrer">Instagram</a>
                    <a href="https://www.facebook.com/fbr4g4/" target="_blank" rel="noopener noreferrer">Facebook</a>
                    <a href="https://x.com/fbr4g4" target="_blank" rel="noopener noreferrer">X / Twitter</a>
                    <a href="https://www.linkedin.com/in/fbr4g4/" target="_blank" rel="noopener noreferrer">LinkedIn</a>
                </div>
            </div>
        </footer>
    </section>

    <!-- Modal: Auth (Login / Register) -->
    <div class="modal-backdrop hidden" id="auth-modal">
        <div class="modal-card">
            <button type="button" class="modal-close-btn" onclick="closeModal('auth-modal')"><i class="fa-solid fa-xmark"></i></button>
            <h3 class="modal-title" id="auth-modal-title" data-i18n="modal_auth_title">Entrar no VirtualSim</h3>

            <button type="button" class="btn-google" id="btn-google-login" onclick="startGoogleLogin()">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#4285F4" d="M23.745 12.27c0-.79-.07-1.54-.19-2.27h-11.3v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58v3h3.86c2.26-2.09 3.56-5.17 3.56-8.82z"/>
                    <path fill="#34A853" d="M12.255 24c3.24 0 5.95-1.08 7.93-2.91l-3.86-3c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96h-3.98v3.09C3.515 21.3 7.565 24 12.255 24z"/>
                    <path fill="#FBBC05" d="M5.525 14.29c-.25-.72-.38-1.49-.38-2.29s.14-1.57.38-2.29V6.62h-3.98a11.86 11.86 0 0 0 0 10.76l3.98-3.09z"/>
                    <path fill="#EA4335" d="M12.255 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C18.205 1.19 15.495 0 12.255 0c-4.69 0-8.74 2.7-10.71 6.62l3.98 3.09c.95-2.85 3.6-4.96 6.73-4.96z"/>
                </svg>
                <span data-i18n="btn_google_login">Continuar com Google</span>
            </button>

            <div class="auth-divider"><span data-i18n="auth_or_email">ou use seu e-mail</span></div>

            <form id="auth-form" onsubmit="handleAuthSubmit(event)">
                <div class="form-group">
                    <label class="form-label" for="auth-email" data-i18n="modal_auth_email">E-mail:</label>
                    <input type="email" id="auth-email" class="form-control" autocomplete="email" placeholder="exemplo@gmail.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="auth-password" data-i18n="modal_auth_password">Senha:</label>
                    <input type="password" id="auth-password" class="form-control" autocomplete="current-password" placeholder="Mínimo de 6 caracteres" data-i18n-placeholder="modal_auth_password_placeholder" required>
                </div>

                <div class="error-banner hidden" id="auth-error" data-i18n="modal_auth_error">Erro ao autenticar.</div>

                <button class="btn btn-primary btn-block" type="submit" id="btn-auth-submit" data-i18n="modal_auth_btn_login">Entrar</button>
            </form>

            <div class="modal-footer">
                <span id="auth-toggle-text" data-i18n="modal_auth_footer_text">Não tem uma conta?</span>
                <a href="#" id="auth-toggle-link" onclick="toggleAuthMode(event, 'register')" data-i18n="modal_auth_footer_link">Cadastrar-se</a>
            </div>
        </div>
    </div>

    <!-- Modal: Reload Balance (Pix Checkout) -->
    <div class="modal-backdrop hidden" id="reload-modal">
        <div class="modal-card">
            <button type="button" class="modal-close-btn" onclick="closeModal('reload-modal')"><i class="fa-solid fa-xmark"></i></button>
            <h3 class="modal-title"><i class="fa-solid fa-wallet text-accent"></i> <span data-i18n="modal_reload_title">Adicionar Saldo via PIX</span></h3>

            <!-- Step 1: Select Package -->
            <div id="reload-step-select">
                <p class="modal-intro" data-i18n="modal_reload_intro">Escolha o valor de recarga. O saldo cai na sua conta em segundos após o pagamento.</p>
                <div class="packages-list">
                    <div class="package-item" onclick="selectPackage(0)">
                        <div class="package-info">
                            <span class="package-title" data-i18n="pack0_title">Recarga R$ 10,00</span>
                            <span class="package-desc" data-i18n="pack0_desc">Ideal para Telegram, Google e outros apps</span>
                        </div>
                        <span class="package-price">R$ 10</span>
                    </div>
                    <div class="package-item active" onclick="selectPackage(1)">
                        <div class="package-info">
                            <span class="package-title">
                                <span data-i18n="pack1_title">Recarga R$ 25,00</span>
                                <span class="package-badge-popular" data-i18n="pack_popular">Ideal WhatsApp BR</span>
                            </span>
                            <span class="package-desc" data-i18n="pack1_desc">Cobre 1 WhatsApp Brasil (R$ 24,90) em 1 recarga</span>
                        </div>
                        <span class="package-price">R$ 25</span>
                    </div>
                    <div class="package-item" onclick="selectPackage(2)">
                        <div class="package-info">
                            <span class="package-title" data-i18n="pack2_title">Recarga R$ 50,00</span>
                            <span class="package-desc" data-i18n="pack2_desc">Crédito total de R$ 50,00</span>
                        </div>
                        <span class="package-price">R$ 50</span>
                    </div>
                    <div class="package-item" onclick="selectPackage(3)">
                        <div class="package-info">
                            <span class="package-title" data-i18n="pack3_title">Recarga R$ 100,00</span>
                            <span class="package-desc" data-i18n="pack3_desc">Crédito total de R$ 100,00</span>
                        </div>
                        <span class="package-price">R$ 100</span>
                    </div>
                </div>

                <div class="error-banner hidden" id="reload-error" data-i18n="modal_reload_error">Erro ao gerar cobrança.</div>

                <button type="button" class="btn btn-primary btn-block btn-lg mt-4" id="btn-generate-pix" onclick="generatePix()">
                    <i class="fa-solid fa-qrcode"></i> <span data-i18n="modal_reload_btn">Gerar QR Code PIX</span>
                </button>
            </div>

            <!-- Step 2: Show QR Code -->
            <div id="reload-step-qrcode" class="hidden text-center">
                <p class="modal-success-text"><i class="fa-solid fa-circle-check text-success"></i> <span data-i18n="modal_pix_success">Cobrança PIX Gerada!</span></p>

                <div class="qrcode-wrapper">
                    <img id="pix-qrcode-img" src="" alt="QR Code PIX">
                </div>

                <div class="form-group text-left">
                    <label class="form-label" for="pix-copia-cola" data-i18n="modal_pix_copia_cola">Código Copia e Cola:</label>
                    <div class="copy-input-group">
                        <input type="text" id="pix-copia-cola" class="form-control" readonly>
                        <button type="button" class="btn btn-accent btn-sm" onclick="copyPixCode(this)">
                            <i class="fa-solid fa-copy"></i> <span data-i18n="btn_copy">Copiar</span>
                        </button>
                    </div>
                </div>

                <div class="pix-instructions" data-i18n="modal_pix_instructions">
                    <p>1. Abra o aplicativo do seu banco.</p>
                    <p>2. Escolha a opção <strong>Pagar via PIX</strong> (QR Code ou Copia e Cola).</p>
                    <p>3. Pague a cobrança e o saldo cairá de forma automática em segundos.</p>
                </div>

                <div class="polling-indicator">
                    <i class="fa-solid fa-circle-notch fa-spin"></i> <span data-i18n="modal_pix_waiting">Aguardando confirmação do pagamento...</span>
                </div>

                <button type="button" class="btn btn-outline btn-block mt-4" onclick="closeModal('reload-modal')" data-i18n="btn_close">Fechar Janela</button>
            </div>
        </div>
    </div>

    <!-- Modal: 4u-Labs Ecosystem (14 Official Links + Support) -->
    <div class="modal-backdrop hidden" id="labs-modal">
        <div class="modal-card">
            <button type="button" class="modal-close-btn" onclick="closeModal('labs-modal')"><i class="fa-solid fa-xmark"></i></button>
            <h3 class="modal-title"><i class="fa-solid fa-rocket text-accent"></i> <span>4u-Labs</span></h3>
            <p class="modal-intro" data-i18n="labs_subtitle">Aplicativos, suporte e comunidade oficial da 4u-Labs.</p>

            <div class="labs-section-label" data-i18n="labs_institutional">Institucional</div>
            <div class="labs-grid">
                <a href="https://4u.ia.br/" target="_blank" rel="noopener noreferrer" data-i18n="labs_portal">Portal 4U</a>
                <a href="https://4u.ia.br/loja/index.php" target="_blank" rel="noopener noreferrer" data-i18n="labs_store">Loja de Apps</a>
                <a href="https://4u.ia.br/suporte.html" target="_blank" rel="noopener noreferrer" data-i18n="labs_help">Central de Ajuda</a>
                <a href="https://4u.ia.br/sobre.html" target="_blank" rel="noopener noreferrer" data-i18n="labs_about">Sobre 4U</a>
                <a href="https://4u.ia.br/termos.html" target="_blank" rel="noopener noreferrer" data-i18n="labs_terms">Termos</a>
                <a href="https://4u.ia.br/politica.html" target="_blank" rel="noopener noreferrer" data-i18n="labs_privacy">Privacidade</a>
            </div>

            <div class="labs-section-label" data-i18n="labs_community">Comunidade &amp; Redes</div>
            <div class="labs-grid">
                <a href="https://github.com/4u-Labs" target="_blank" rel="noopener noreferrer">GitHub</a>
                <a href="https://4u-labs.github.io/" target="_blank" rel="noopener noreferrer">Blog Dev</a>
                <a href="https://alternativeto.net/user/4u-ia-br/added/" target="_blank" rel="noopener noreferrer">AlternativeTo</a>
                <a href="https://www.youtube.com/@fbr4g4" target="_blank" rel="noopener noreferrer">YouTube</a>
                <a href="https://www.instagram.com/fbr4g4/" target="_blank" rel="noopener noreferrer">Instagram</a>
                <a href="https://www.facebook.com/fbr4g4/" target="_blank" rel="noopener noreferrer">Facebook</a>
                <a href="https://x.com/fbr4g4" target="_blank" rel="noopener noreferrer">X / Twitter</a>
                <a href="https://www.linkedin.com/in/fbr4g4/" target="_blank" rel="noopener noreferrer">LinkedIn</a>
            </div>

            <a href="https://www.paypal.com/ncp/payment/L7YRCS984T33N" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-block mt-4" style="text-decoration: none;">
                ♡ <span data-i18n="labs_donate">Apoiar a 4u-Labs</span>
            </a>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="toast" class="toast hidden">Notificação</div>

    <!-- Core App JS -->
    <script src="app.js?v=<?php echo $jsVersion; ?>"></script>
</body>
</html>
