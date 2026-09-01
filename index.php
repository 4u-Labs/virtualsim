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
    <title>VirtualSim — Números Virtuais e SMS em Tempo Real</title>
    <meta name="description" content="Ative WhatsApp, Telegram e outros serviços usando números virtuais instantâneos. 100% online e sem chip físico.">
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
    <!-- Main Style Dynamic Anti-Cache -->
    <link rel="stylesheet" href="style-v1.css?v=<?php echo $assetVersion; ?>">
    <!-- PWA Manifest & Meta Tags -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0b0f19">
    <link rel="apple-touch-icon" href="icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="VirtualSim">
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512' fill='%230ea5e9'%3E%3Cpath d='M416 128H384v-32c0-35.3-28.7-64-64-64h-32V0h-32v32h-32V0h-32v32h-32c-35.3 0-64 28.7-64 64v32H96c-17.7 0-32 14.3-32 32v32H32v32h32v32H32v32h32v32c0 17.7 14.3 32 32 32h32v32c0 35.3 28.7 64 64 64h32v32h32v-32h32v32h32v-32h32c35.3 0 64-28.7 64-64v-32h32c17.7 0 32-14.3 32-32v-32h32v-32h-32v-32h32v-32h-32v-32c0-17.7-14.3-32-32-32zM352 352H160V160h192v192z'/%3E%3C/svg%3E">
    <style>
        .institutional-footer {
            margin-top: 40px;
            padding: 24px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }
        .institutional-footer a {
            color: #38bdf8;
            text-decoration: none;
            font-weight: 600;
            margin: 0 6px;
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
                <i class="fa-solid fa-microchip logo-icon"></i>
                <span class="logo-text">Virtual<span class="text-highlight">Sim</span></span>
            </div>
            
            <div class="nav-actions" id="nav-actions">
                <!-- Language Switcher -->
                <div class="lang-switcher">
                    <button onclick="setLanguage('pt')" class="lang-btn active" id="lang-btn-pt">PT</button>
                    <span class="lang-divider">|</span>
                    <button onclick="setLanguage('en')" class="lang-btn" id="lang-btn-en">EN</button>
                </div>
                <!-- Share WebApp Button -->
                <button class="btn btn-outline btn-sm btn-share" onclick="shareWebApp()" title="Compartilhar" id="btn-share-app" style="padding: 6px 12px; gap: 6px; border-color: rgba(56, 189, 248, 0.3); color: #38bdf8;">
                    <i class="fa-solid fa-share-nodes"></i> <span data-i18n="nav_share">Compartilhar</span>
                </button>
                <!-- Logged Out State -->
                <div class="auth-buttons" id="auth-buttons">
                    <button class="btn btn-nav-login" onclick="openAuthModal('login')">
                        <i class="fa-solid fa-right-to-bracket"></i> <span data-i18n="nav_login">Entrar</span>
                    </button>
                    <button class="btn btn-nav-register" onclick="openAuthModal('register')">
                        <i class="fa-solid fa-user-plus"></i> <span data-i18n="nav_register">Criar Conta</span>
                    </button>
                </div>
                <!-- Logged In State (Hidden by default) -->
                <div class="user-profile hidden" id="user-profile">
                    <div class="balance-display">
                        <span class="balance-label" data-i18n="nav_balance">Saldo:</span>
                        <span class="balance-value" id="header-balance">R$ 0,00</span>
                    </div>
                    <button class="btn btn-accent btn-sm" onclick="openReloadModal()">
                        <i class="fa-solid fa-plus-circle"></i> <span data-i18n="nav_reload">Recarregar</span>
                    </button>
                    <span class="user-name" id="header-username">Usuario</span>
                    <button class="btn btn-icon" onclick="logout()" title="Sair" id="btn-logout-title">
                        <i class="fa-solid fa-sign-out-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Dashboard Section -->
    <section class="main-content" id="solicitar">
        <div class="container grid-layout">
            
            <!-- Left Side: Order Panel -->
            <div class="column-left">
                <section class="panel panel-order">
                    <h2 class="panel-title"><i class="fa-solid fa-shopping-cart"></i> <span data-i18n="panel_buy_title">Solicitar Número Virtual</span></h2>
                    
                    <!-- Step 1: Select Country -->
                    <div class="form-group">
                        <label class="form-label" data-i18n="panel_buy_lbl_country">1. Escolha o País do Número:</label>
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
                                    <span class="service-price" id="price-whatsapp">R$ 24,90</span>
                                </div>
                            </div>

                            <!-- Telegram Card -->
                            <div class="service-card" data-service="telegram" onclick="selectService(this)">
                                <div class="service-icon"><i class="fa-brands fa-telegram text-info"></i></div>
                                <div class="service-details">
                                    <span class="service-name">Telegram</span>
                                    <span class="service-price">R$ 2,90</span>
                                </div>
                            </div>

                            <!-- Google / Gmail Card -->
                            <div class="service-card" data-service="google" onclick="selectService(this)">
                                <div class="service-icon"><i class="fa-brands fa-google text-danger"></i></div>
                                <div class="service-details">
                                    <span class="service-name">Google / Gmail</span>
                                    <span class="service-price">R$ 1,50</span>
                                </div>
                            </div>

                            <!-- Microsoft Card -->
                            <div class="service-card" data-service="microsoft" onclick="selectService(this)">
                                <div class="service-icon"><i class="fa-brands fa-microsoft text-primary"></i></div>
                                <div class="service-details">
                                    <span class="service-name">Microsoft</span>
                                    <span class="service-price">R$ 1,50</span>
                                </div>
                            </div>

                            <!-- TikTok Card -->
                            <div class="service-card" data-service="tiktok" onclick="selectService(this)">
                                <div class="service-icon"><i class="fa-brands fa-tiktok text-light"></i></div>
                                <div class="service-details">
                                    <span class="service-name">TikTok</span>
                                    <span class="service-price">R$ 1,50</span>
                                </div>
                            </div>

                            <!-- Economico Card -->
                            <div class="service-card" data-service="economico" onclick="selectService(this)">
                                <div class="service-icon"><i class="fa-solid fa-tags text-warning"></i></div>
                                <div class="service-details">
                                    <span class="service-name">Serviço Econômico</span>
                                    <span class="service-price">R$ 0,50</span>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Buy Action Button -->
                    <button class="btn btn-primary btn-block btn-lg" id="btn-request-number" onclick="requestNumber()">
                        <i class="fa-solid fa-key"></i> <span data-i18n="panel_buy_btn">Solicitar Número Agora</span>
                    </button>
                    <div class="info-note text-center mt-3" data-i18n="panel_buy_info">
                        <i class="fa-solid fa-shield-halved"></i> Você só paga se o código SMS for entregue!
                    </div>
                </section>

                <!-- Instructions Panel -->
                <section class="panel panel-instructions">
                    <h3 class="panel-title panel-title-sm" data-i18n="timeline_title">
                        <i class="fa-solid fa-circle-info text-accent"></i> Como funciona? Passo a Passo
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
                                <p data-i18n="timeline_s2_desc">Selecione o serviço desejado ao lado, clique em <strong class="text-highlight">Solicitar Número Agora</strong> e copie-o.</p>
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
                                <h4 data-i18n="timeline_s5_title">🔒 Dica de Ouro: WhatsApp Business & Blindagem (2FA)</h4>
                                <p data-i18n="timeline_s5_desc">Recomendamos usar o <strong>WhatsApp Business</strong> (possui menos exigências). Se o número gerado estiver bloqueado pelo WhatsApp, basta clicar em <em>Cancelar e Estornar Saldo</em> (o saldo volta na hora) e gerar outro! Após ativar, vá em <em>Configurações > Conta > Confirmação em Duas Etapas</em> e crie uma senha (PIN) com seu e-mail para garantir a conta 100% sua!</p>
                            </div>
                        </div>
                    </div>
                    <div class="timeline-footer" data-i18n="timeline_footer">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Se o SMS não chegar em até 3 minutos, clique em <strong>Cancelar e Estornar Saldo</strong> para tentar com outro número gratuitamente!</span>
                    </div>
                </section>
            </div>

            <!-- Right Side: Active Numbers Dashboard -->
            <div class="column-right">
                <section class="panel panel-dashboard">
                    <h2 class="panel-title"><i class="fa-solid fa-list-check"></i> <span data-i18n="panel_orders_title">Seus Números Ativos</span></h2>
                    
                    <div class="orders-container" id="orders-container">
                        <!-- Dynamic Placeholder: No active orders -->
                        <div class="empty-state" id="empty-orders-state">
                            <i class="fa-solid fa-mobile-screen empty-icon"></i>
                            <p class="empty-text" data-i18n="orders_empty_title">Você não possui números ativos no momento.</p>
                            <p class="empty-subtext" data-i18n="orders_empty_desc">Selecione as opções ao lado e clique em solicitar para gerar um novo número.</p>
                        </div>

                        <!-- Active Orders will be dynamically injected here -->
                    </div>
                </section>
            </div>

        </div>

        <!-- Institutional Footer -->
        <footer class="institutional-footer">
            <div class="container">
                <p>&copy; <?php echo date("Y"); ?> VirtualSim &bull; Todos os direitos reservados.</p>
                <div style="margin-top: 8px;">
                    <a href="privacidade.php" target="_blank">Privacidade</a> &bull;
                    <a href="termos.php" target="_blank">Termos de Uso</a> &bull;
                    <a href="suporte.php" target="_blank">Suporte</a>
                </div>
            </div>
        </footer>
    </section>

    <!-- Modal: Auth (Login / Register) -->
    <div class="modal-backdrop hidden" id="auth-modal">
        <div class="modal-card">
            <button class="modal-close-btn" onclick="closeModal('auth-modal')"><i class="fa-solid fa-xmark"></i></button>
            <h3 class="modal-title" id="auth-modal-title" data-i18n="modal_auth_title">Entrar no VirtualSim</h3>
            
            <form id="auth-form" onsubmit="handleAuthSubmit(event)">
                <div class="form-group">
                    <label class="form-label" data-i18n="modal_auth_email">E-mail:</label>
                    <input type="email" id="auth-email" class="form-control" placeholder="exemplo@gmail.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label" data-i18n="modal_auth_password">Senha:</label>
                    <input type="password" id="auth-password" class="form-control" placeholder="Mínimo de 6 caracteres" data-i18n-placeholder="modal_auth_password_placeholder" required>
                </div>
                
                <div class="error-banner hidden" id="auth-error" data-i18n="modal_auth_error">Erro ao autenticar.</div>
                
                <button class="btn btn-primary btn-block" type="submit" id="btn-auth-submit" data-i18n="modal_auth_btn_login">Entrar</button>
            </form>
            
            <div class="modal-footer">
                <span id="auth-toggle-text" data-i18n="modal_auth_footer_text">Não tem uma conta?</span>
                <a href="#" id="auth-toggle-link" onclick="toggleAuthMode(event)" data-i18n="modal_auth_footer_link">Cadastrar-se</a>
            </div>
        </div>
    </div>

    <!-- Modal: Reload Balance (Pix Checkout) -->
    <div class="modal-backdrop hidden" id="reload-modal">
        <div class="modal-card">
            <button class="modal-close-btn" onclick="closeModal('reload-modal')"><i class="fa-solid fa-xmark"></i></button>
            <h3 class="modal-title"><i class="fa-solid fa-wallet text-accent"></i> <span data-i18n="modal_reload_title">Adicionar Saldo via PIX</span></h3>
            
            <!-- Step 1: Select Package -->
            <div id="reload-step-select">
                <p class="modal-intro" data-i18n="modal_reload_intro">Escolha o valor de recarga. O saldo cai na sua conta em segundos após o pagamento.</p>
                <div class="packages-list">
                    <div class="package-item active" onclick="selectPackage(0)">
                        <div class="package-info">
                            <span class="package-title" data-i18n="pack1_title">Recarga R$ 10,00</span>
                            <span class="package-desc" data-i18n="pack1_desc">Crédito total de R$ 10,00</span>
                        </div>
                        <span class="package-price">R$ 10</span>
                    </div>
                    <div class="package-item" onclick="selectPackage(1)">
                        <div class="package-info">
                            <span class="package-title" data-i18n="pack2_title">Recarga R$ 20,00</span>
                            <span class="package-desc" data-i18n="pack2_desc">Crédito total de R$ 20,00</span>
                        </div>
                        <span class="package-price">R$ 20</span>
                    </div>
                    <div class="package-item" onclick="selectPackage(2)">
                        <div class="package-info">
                            <span class="package-title" data-i18n="pack3_title">Recarga R$ 50,00</span>
                            <span class="package-desc" data-i18n="pack3_desc">Crédito total de R$ 50,00</span>
                        </div>
                        <span class="package-price">R$ 50</span>
                    </div>
                </div>
                
                <div class="error-banner hidden" id="reload-error" data-i18n="modal_reload_error">Erro ao gerar cobrança.</div>
                
                <button class="btn btn-primary btn-block btn-lg mt-4" id="btn-generate-pix" onclick="generatePix()">
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
                    <label class="form-label" data-i18n="modal_pix_copia_cola">Código Copia e Cola:</label>
                    <div class="copy-input-group">
                        <input type="text" id="pix-copia-cola" class="form-control" readonly>
                        <button class="btn btn-accent btn-sm" onclick="copyPixCode(this)">
                            <i class="fa-solid fa-copy"></i> <span data-i18n="btn_copy">Copiar</span>
                        </button>
                    </div>
                </div>
                
                <div class="pix-instructions" data-i18n="modal_pix_instructions">
                    <p>1. Abra o aplicativo do seu banco.</p>
                    <p>2. Escolha a opção <strong>Pagar via PIX</strong> (QR Code ou Copia e Cola).</p>
                    <p>3. Pague a cobrança e o saldo cairá de forma automática em segundos.</p>
                </div>
                
                <div class="polling-indicator" data-i18n="modal_pix_waiting">
                    <i class="fa-solid fa-circle-notch fa-spin"></i> Aguardando confirmação do pagamento...
                </div>
                
                <button class="btn btn-outline btn-block mt-4" onclick="closeModal('reload-modal')" data-i18n="btn_close">Fechar Janela</button>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="toast" class="toast hidden">Notificação</div>

    <!-- Core App JS Dynamic Anti-Cache -->
    <script src="app.js?v=<?php echo $assetVersion; ?>"></script>
</body>
</html>
