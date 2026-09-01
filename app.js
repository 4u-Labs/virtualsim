/* ==========================================================================
   VirtualSim JavaScript Application
   Frontend controller handling API interactions, state management and polling loops
   ========================================================================== */

const API_BASE = 'api';
let userToken = localStorage.getItem('virtualsim_token') || null;
let userData = null;
let selectedService = 'whatsapp';
let selectedPackageIndex = 0;
let activeOrders = {}; // Store active polling intervals and details
let pixPollInterval = null;

let detectedLang = navigator.language || navigator.userLanguage || 'pt';
let defaultLang = detectedLang.startsWith('pt') ? 'pt' : 'en';
let currentLang = localStorage.getItem('lang') || defaultLang;

const translations = {
    pt: {
        nav_login: "Entrar",
        nav_register: "Criar Conta",
        nav_balance: "Saldo:",
        nav_reload: "Recarregar",
        nav_logout: "Sair",
        nav_share: "Compartilhar",
        panel_buy_title: "Solicitar Número Virtual",
        panel_buy_lbl_country: "1. Escolha o País do Número:",
        panel_buy_lbl_service: "2. Selecione o Serviço / Aplicativo:",
        panel_buy_btn: "Solicitar Número Agora",
        panel_buy_info: "Você só paga se o código SMS for entregue!",
        country_brazil: "Brasil 🇧🇷 (Chips Físicos Ativos)",
        timeline_title: "Como funciona? Passo a Passo",
        timeline_s1_title: "Abra o cadastro no WhatsApp Business",
        timeline_s1_desc: "Utilize preferencialmente o aplicativo <strong>WhatsApp Business</strong> no celular (pois possui menos exigências e regras de validação mais flexíveis).",
        timeline_s2_title: "Solicite e copie o número",
        timeline_s2_desc: "Selecione o serviço desejado ao lado, clique em <strong>Solicitar Número Agora</strong> e copie-o.",
        timeline_s3_title: "Envie o SMS de verificação",
        timeline_s3_desc: "Cole o número no aplicativo. <em>Se informar que o número gerado já foi banido/recusado, basta clicar em <strong>Cancelar e Estornar Saldo</strong> (o valor volta 100% na hora) e solicitar um novo número!</em>",
        timeline_s4_title: "Copie o código e ative",
        timeline_s4_desc: "Volte aqui, aguarde o código de 6 dígitos aparecer no card de status e copie-o para validar seu cadastro.",
        timeline_s5_title: "🔒 Dica de Ouro: WhatsApp Business & Blindagem (2FA)",
        timeline_s5_desc: "Recomendamos usar o <strong>WhatsApp Business</strong> (possui menos exigências). Se o número gerado estiver bloqueado pelo WhatsApp, basta clicar em <em>Cancelar e Estornar Saldo</em> (o saldo volta na hora) e gerar outro! Após ativar, vá em <em>Configurações > Conta > Confirmação em Duas Etapas</em> e crie uma senha (PIN) com seu e-mail para garantir a conta 100% sua!",
        timeline_footer: "Se o SMS não chegar em até 3 minutos, clique em <strong>Cancelar e Estornar Saldo</strong> para tentar com outro número gratuitamente!",
        panel_orders_title: "Seus Números Ativos",
        orders_empty_title: "Você não possui números ativos no momento.",
        orders_empty_desc: "Selecione as opções ao lado e clique em solicitar para gerar um novo número.",
        country_brazil: "Brasil (Recomendado)",
        country_paraguay: "Paraguai",
        country_argentina: "Argentina",
        country_russia: "Rússia",
        country_indonesia: "Indonésia",
        country_vietnam: "Vietnã",
        modal_auth_title: "Entrar no VirtualSim",
        modal_auth_email: "E-mail:",
        modal_auth_password: "Senha:",
        modal_auth_password_placeholder: "Mínimo de 6 caracteres",
        modal_auth_error: "Erro ao autenticar.",
        modal_auth_btn_login: "Entrar",
        modal_auth_footer_text: "Não tem uma conta?",
        modal_auth_footer_link: "Cadastrar-se",
        modal_reload_title: "Adicionar Saldo via PIX",
        modal_reload_intro: "Escolha o valor de recarga. O saldo cai na sua conta em segundos após o pagamento.",
        pack1_title: "Recarga R$ 10,00",
        pack1_desc: "Crédito total de R$ 10,00",
        pack2_title: "Recarga R$ 20,00",
        pack2_desc: "Crédito total de R$ 20,00",
        pack3_title: "Recarga R$ 50,00",
        pack3_desc: "Crédito total de R$ 50,00",
        modal_reload_error: "Erro ao gerar cobrança.",
        modal_reload_btn: "Gerar QR Code PIX",
        modal_pix_success: "Cobrança PIX Gerada!",
        modal_pix_copia_cola: "Código Copia e Cola:",
        btn_copy: "Copiar",
        btn_close: "Fechar Janela",
        modal_pix_instructions: "<p>1. Abra o aplicativo do seu banco.</p><p>2. Escolha a opção <strong>Pagar via PIX</strong> (QR Code ou Copia e Cola).</p><p>3. Pague a cobrança e o saldo cairá de forma automática em segundos.</p>",
        modal_pix_waiting: "Aguardando confirmação do pagamento...",
        
        // Dynamic elements
        sms_waiting: "Aguardando SMS...",
        sms_received: "Código Recebido!",
        btn_cancel: "Cancelar e Estornar Saldo",
        btn_clear: "Limpar do Histórico",
        toast_copy_success: "Texto copiado com sucesso!",
        toast_login_success: "Login realizado com sucesso!",
        toast_register_success: "Conta criada com sucesso!",
        toast_reload_success: "Pagamento Pix confirmado! Saldo adicionado.",
        toast_sms_requested: "Número solicitado com sucesso! Aguarde o SMS.",
        toast_sms_received: "SMS recebido com sucesso!",
        toast_sms_canceled: "Número cancelado e saldo estornado!",
        toast_sms_expired: "Número expirou ou foi cancelado pelo provedor.",
        security_2fa_alert: "<strong>🔒 BLINDAGEM OBRIGATÓRIA (2FA):</strong> Para garantir que ninguém mais acesse sua conta no futuro se o número for reciclado, ative agora a <strong>Confirmação em Duas Etapas</strong> (PIN de segurança) nas configurações do seu WhatsApp/Telegram!",
        auth_modal_title_register: "Cadastrar-se no VirtualSim",
        auth_modal_title_login: "Entrar no VirtualSim",
        btn_cancelling: "Cancelando..."
    },
    en: {
        nav_login: "Log In",
        nav_register: "Register",
        nav_balance: "Balance:",
        nav_reload: "Top Up",
        nav_logout: "Log Out",
        nav_share: "Share",
        panel_buy_title: "Request Virtual Number",
        panel_buy_lbl_country: "1. Choose Number Country:",
        panel_buy_lbl_service: "2. Select Service / App:",
        panel_buy_btn: "Request Number Now",
        panel_buy_info: "You only pay if the SMS code is delivered!",
        country_brazil: "Brazil 🇧🇷 (Active Physical SIMs)",
        timeline_title: "How it works? Step by Step",
        timeline_s1_title: "Open registration in WhatsApp Business",
        timeline_s1_desc: "We recommend using the <strong>WhatsApp Business</strong> app on your phone (it has fewer strict filters and more flexible validation rules).",
        timeline_s2_title: "Request and copy the number",
        timeline_s2_desc: "Select the desired service, click <strong>Request Number Now</strong> and copy it.",
        timeline_s3_title: "Send verification SMS",
        timeline_s3_desc: "Paste the number in the app. <em>If it says the number is blocked/banned, simply click <strong>Cancel & Refund Balance</strong> (100% refunded instantly) and request a new number!</em>",
        timeline_s4_title: "Copy the code and activate",
        timeline_s4_desc: "Return here, wait for the 6-digit code on the status card, and copy it to validate your account.",
        timeline_s5_title: "🔒 Gold Tip: WhatsApp Business & Shield (2FA)",
        timeline_s5_desc: "We recommend using <strong>WhatsApp Business</strong> (fewer strict requirements). If the generated number is blocked by WhatsApp, simply click <em>Cancel & Refund Balance</em> (refunded instantly) and generate another! Once activated, go to <em>Settings > Account > Two-Step Verification</em> and set a PIN with your email to keep your account forever!",
        timeline_footer: "If the SMS does not arrive within 3 minutes, click <strong>Cancel and Refund Balance</strong> to try another number for free!",
        panel_orders_title: "Your Active Numbers",
        orders_empty_title: "You have no active numbers at the moment.",
        orders_empty_desc: "Select the options on the left and click request to generate a new number.",
        country_brazil: "Brazil (Recommended)",
        country_paraguay: "Paraguay",
        country_argentina: "Argentina",
        country_russia: "Russia",
        country_indonesia: "Indonesia",
        country_vietnam: "Vietnam",
        modal_auth_title: "Log In to VirtualSim",
        modal_auth_email: "Email:",
        modal_auth_password: "Password:",
        modal_auth_password_placeholder: "Minimum 6 characters",
        modal_auth_error: "Authentication failed.",
        modal_auth_btn_login: "Log In",
        modal_auth_footer_text: "Don't have an account?",
        modal_auth_footer_link: "Sign Up",
        modal_reload_title: "Top Up Balance via PIX",
        modal_reload_intro: "Choose top-up value. The balance drops in your account in seconds after payment.",
        pack1_title: "Recharge R$ 10.00",
        pack1_desc: "Total credit of R$ 10.00",
        pack2_title: "Recharge R$ 20.00",
        pack2_desc: "Total credit of R$ 20.00",
        pack3_title: "Recharge R$ 50.00",
        pack3_desc: "Total credit of R$ 50.00",
        modal_reload_error: "Failed to generate billing.",
        modal_reload_btn: "Generate PIX QR Code",
        modal_pix_success: "PIX Billing Generated!",
        modal_pix_copia_cola: "Copy & Paste Code:",
        btn_copy: "Copy",
        btn_close: "Close Window",
        modal_pix_instructions: "<p>1. Open your bank app.</p><p>2. Choose the option <strong>Pay via PIX</strong> (QR Code or Copy & Paste).</p><p>3. Pay the bill and the balance will drop automatically in seconds.</p>",
        modal_pix_waiting: "Waiting for payment confirmation...",
        
        // Dynamic elements
        sms_waiting: "Waiting for SMS...",
        sms_received: "Code Received!",
        btn_cancel: "Cancel & Refund Balance",
        btn_clear: "Clear from History",
        toast_copy_success: "Text copied successfully!",
        toast_login_success: "Logged in successfully!",
        toast_register_success: "Account created successfully!",
        toast_reload_success: "Pix payment confirmed! Balance added.",
        toast_sms_requested: "Number requested successfully! Wait for SMS.",
        toast_sms_received: "SMS received successfully!",
        toast_sms_canceled: "Number canceled and balance refunded!",
        toast_sms_expired: "Number expired or canceled by provider.",
        security_2fa_alert: "<strong>🔒 MANDATORY SHIELD (2FA):</strong> To ensure no one else accesses your account in the future if the number is recycled, enable **Two-Step Verification** (security PIN) in your WhatsApp/Telegram settings now!",
        auth_modal_title_register: "Sign Up to VirtualSim",
        auth_modal_title_login: "Log In to VirtualSim",
        btn_cancelling: "Cancelling..."
    }
};

function setLanguage(lang) {
    currentLang = lang;
    localStorage.setItem('lang', lang);
    updateLanguageUI();
}

function updateLanguageUI() {
    // Translate standard elements
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (translations[currentLang] && translations[currentLang][key]) {
            el.innerHTML = translations[currentLang][key];
        }
    });

    // Translate input placeholders
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        if (translations[currentLang] && translations[currentLang][key]) {
            el.setAttribute('placeholder', translations[currentLang][key]);
        }
    });

    // Update active class on switch buttons
    const btnPt = document.getElementById('lang-btn-pt');
    const btnEn = document.getElementById('lang-btn-en');
    if (btnPt && btnEn) {
        if (currentLang === 'pt') {
            btnPt.classList.add('active');
            btnEn.classList.remove('active');
        } else {
            btnEn.classList.add('active');
            btnPt.classList.remove('active');
        }
    }
    
    // Update document title and logout tooltip
    if (currentLang === 'pt') {
        document.title = "VirtualSim — Números Virtuais e SMS em Tempo Real";
        const logoutBtn = document.getElementById('btn-logout-title');
        if (logoutBtn) logoutBtn.setAttribute('title', 'Sair');
    } else {
        document.title = "VirtualSim — Virtual Numbers & SMS in Real Time";
        const logoutBtn = document.getElementById('btn-logout-title');
        if (logoutBtn) logoutBtn.setAttribute('title', 'Log Out');
    }
}

// Initialize App
document.addEventListener('DOMContentLoaded', () => {
    initApp();
});

function initApp() {
    updateLanguageUI();
    if (userToken) {
        syncUser();
    } else {
        updateNavbarState(false);
    }
    updatePricingForCountry();

    // Re-evaluate prices when country changes
    document.getElementById('select-country').addEventListener('change', () => {
        updatePricingForCountry();
    });
}

// ==========================================================================
//  Pricing Updates
// ==========================================================================
function updatePricingForCountry() {
    const country = document.getElementById('select-country').value;
    const whatsappPrice = document.getElementById('price-whatsapp');
    
    if (country === 'brazil') {
        whatsappPrice.textContent = 'R$ 24,90';
    } else {
        whatsappPrice.textContent = 'R$ 4,90';
    }
}

// ==========================================================================
//  UI Controls (Modals & Toast)
// ==========================================================================
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.className = `toast ${type === 'error' ? 'toast-error' : ''}`;
    
    let icon = '<i class="fa-solid fa-circle-check text-success"></i>';
    if (type === 'error') icon = '<i class="fa-solid fa-circle-exclamation text-danger"></i>';
    if (type === 'info') icon = '<i class="fa-solid fa-circle-info text-primary"></i>';
    
    toast.innerHTML = `${icon} <span>${message}</span>`;
    toast.classList.remove('hidden');
    
    setTimeout(() => {
        toast.classList.add('hidden');
    }, 5000);
}

function openAuthModal(mode = 'login') {
    const modal = document.getElementById('auth-modal');
    modal.classList.remove('hidden');
    
    const title = document.getElementById('auth-modal-title');
    const submitBtn = document.getElementById('btn-auth-submit');
    const toggleText = document.getElementById('auth-toggle-text');
    const toggleLink = document.getElementById('auth-toggle-link');
    
    document.getElementById('auth-error').classList.add('hidden');
    
    if (mode === 'login') {
        title.textContent = 'Entrar no VirtualSim';
        submitBtn.textContent = 'Entrar';
        toggleText.textContent = 'Não tem uma conta?';
        toggleLink.textContent = 'Cadastrar-se';
        toggleLink.setAttribute('onclick', "toggleAuthMode(event, 'register')");
        submitBtn.setAttribute('data-mode', 'login');
    } else {
        title.textContent = 'Criar uma Conta';
        submitBtn.textContent = 'Cadastrar';
        toggleText.textContent = 'Já possui uma conta?';
        toggleLink.textContent = 'Entrar';
        toggleLink.setAttribute('onclick', "toggleAuthMode(event, 'login')");
        submitBtn.setAttribute('data-mode', 'register');
    }
}

function toggleAuthMode(event, mode) {
    event.preventDefault();
    openAuthModal(mode);
}

function openReloadModal() {
    if (!userToken) {
        openAuthModal('login');
        return;
    }
    
    document.getElementById('reload-modal').classList.remove('hidden');
    document.getElementById('reload-step-select').classList.remove('hidden');
    document.getElementById('reload-step-qrcode').classList.add('hidden');
    document.getElementById('reload-error').classList.add('hidden');
    
    selectPackage(0);
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    if (modalId === 'reload-modal') {
        if (pixPollInterval) {
            clearInterval(pixPollInterval);
            pixPollInterval = null;
        }
    }
}

function selectService(cardElement) {
    document.querySelectorAll('.service-card').forEach(card => {
        card.classList.remove('active');
    });
    cardElement.classList.add('active');
    selectedService = cardElement.getAttribute('data-service');
}

function selectPackage(index) {
    document.querySelectorAll('.package-item').forEach((item, idx) => {
        if (idx === index) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
    selectedPackageIndex = index;
}

// ==========================================================================
//  User Session & Auth API Requests
// ==========================================================================
function updateNavbarState(isLoggedIn) {
    const authButtons = document.getElementById('auth-buttons');
    const userProfile = document.getElementById('user-profile');
    
    if (isLoggedIn && userData) {
        authButtons.classList.add('hidden');
        userProfile.classList.remove('hidden');
        document.getElementById('header-username').textContent = userData.display_name;
        document.getElementById('header-balance').textContent = formatCentsToBRL(userData.credits);
    } else {
        authButtons.classList.remove('hidden');
        userProfile.classList.add('hidden');
    }
}

function formatCentsToBRL(cents) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100);
}

async function syncUser() {
    try {
        const response = await fetch(`${API_BASE}/auth.php?token=${encodeURIComponent(userToken)}`, {
            headers: { 'Authorization': `Bearer ${userToken}` }
        });
        if (response.status === 401) {
            logout();
            return;
        }
        const data = await response.json();
        if (data.success) {
            userData = data.user;
            updateNavbarState(true);
        }
    } catch (err) {
        console.error('Failed to sync user:', err);
    }
}

async function handleAuthSubmit(event) {
    event.preventDefault();
    
    const email = document.getElementById('auth-email').value;
    const password = document.getElementById('auth-password').value;
    const submitBtn = document.getElementById('btn-auth-submit');
    const mode = submitBtn.getAttribute('data-mode');
    
    const errorBanner = document.getElementById('auth-error');
    errorBanner.classList.add('hidden');
    
    try {
        const endpoint = mode === 'register' ? 'auth.php?action=register' : 'auth.php?action=login';
        const response = await fetch(`${API_BASE}/${endpoint}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        if (response.ok && data.success) {
            userToken = data.token;
            localStorage.setItem('virtualsim_token', userToken);
            userData = data.user;
            
            updateNavbarState(true);
            closeModal('auth-modal');
            showToast(mode === 'register' ? 'Conta criada com sucesso!' : 'Login efetuado com sucesso!');
        } else {
            errorBanner.textContent = data.error || 'Erro na autenticação. Tente novamente.';
            errorBanner.classList.remove('hidden');
        }
    } catch (err) {
        errorBanner.textContent = 'Erro ao conectar ao servidor.';
        errorBanner.classList.remove('hidden');
    }
}

function logout() {
    userToken = null;
    userData = null;
    localStorage.removeItem('virtualsim_token');
    updateNavbarState(false);
    showToast('Sessão encerrada.', 'info');
    
    // Clear dashboard
    document.getElementById('orders-container').innerHTML = `
        <div class="empty-state" id="empty-orders-state">
            <i class="fa-solid fa-mobile-screen empty-icon"></i>
            <p class="empty-text">Você não possui números ativos no momento.</p>
            <p class="empty-subtext">Selecione as opções ao lado e clique em solicitar para gerar um novo número.</p>
        </div>
    `;
    
    // Clear polling orders
    Object.keys(activeOrders).forEach(id => {
        clearInterval(activeOrders[id].interval);
        clearInterval(activeOrders[id].timerInterval);
    });
    activeOrders = {};
}

// ==========================================================================
//  Recharge Pix Checkout
// ==========================================================================
async function generatePix() {
    const errorBanner = document.getElementById('reload-error');
    errorBanner.classList.add('hidden');
    
    try {
        const response = await fetch(`${API_BASE}/mp_create.php?token=${encodeURIComponent(userToken)}`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${userToken}`
            },
            body: JSON.stringify({ package_index: selectedPackageIndex })
        });
        
        const data = await response.json();
        if (response.ok && data.success) {
            document.getElementById('reload-step-select').classList.add('hidden');
            document.getElementById('reload-step-qrcode').classList.remove('hidden');
            
            document.getElementById('pix-qrcode-img').src = `data:image/png;base64,${data.qr_code_base64}`;
            document.getElementById('pix-copia-cola').value = data.qr_code;
            
            // Start checking for payment approval in background
            startPixPolling(userData.credits);
        } else {
            errorBanner.textContent = data.error || 'Erro ao gerar cobrança.';
            errorBanner.classList.remove('hidden');
        }
    } catch (err) {
        errorBanner.textContent = 'Erro ao conectar ao servidor.';
        errorBanner.classList.remove('hidden');
    }
}

function copyPixCode(btnElement) {
    const input = document.getElementById('pix-copia-cola');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value);
    showToast(translations[currentLang].toast_copy_success);
    
    if (btnElement) {
        const originalHTML = btnElement.innerHTML;
        btnElement.innerHTML = `<i class="fa-solid fa-circle-check"></i> ` + (currentLang === 'pt' ? 'Copiado!' : 'Copied!');
        btnElement.style.backgroundColor = 'var(--success)';
        btnElement.style.borderColor = 'var(--success)';
        
        setTimeout(() => {
            btnElement.innerHTML = originalHTML;
            btnElement.style.backgroundColor = '';
            btnElement.style.borderColor = '';
        }, 2000);
    }
}

function startPixPolling(previousCredits) {
    if (pixPollInterval) clearInterval(pixPollInterval);
    
    pixPollInterval = setInterval(async () => {
        try {
            const response = await fetch(`${API_BASE}/auth.php?token=${encodeURIComponent(userToken)}`, {
                headers: { 'Authorization': `Bearer ${userToken}` }
            });
            const data = await response.json();
            if (data.success && data.user.credits > previousCredits) {
                // Payment was approved!
                clearInterval(pixPollInterval);
                pixPollInterval = null;
                
                userData = data.user;
                updateNavbarState(true);
                
                closeModal('reload-modal');
                showToast(`Recarga aprovada! Seu novo saldo é de ${formatCentsToBRL(userData.credits)}`);
            }
        } catch (err) {
            console.error('Error polling Pix:', err);
        }
    }, 4000);
}

// ==========================================================================
//  SMS virtual numbers ordering & polling
// ==========================================================================
async function requestNumber() {
    if (!userToken) {
        openAuthModal('login');
        return;
    }
    
    // Auto-cancel previous active orders (with automatic balance refund) before placing new order
    const existingOrderIds = Object.keys(activeOrders);
    if (existingOrderIds.length > 0) {
        showToast(currentLang === 'pt' ? 'Cancelando número anterior e estornando saldo...' : 'Canceling previous number and refunding balance...', 'info');
        for (const prevId of existingOrderIds) {
            try {
                await cancelOrder(prevId);
            } catch (e) {
                console.warn('Error auto-canceling previous order:', e);
            }
        }
    }

    const country = document.getElementById('select-country').value;
    const product = selectedService;
    
    const btn = document.getElementById('btn-request-number');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Solicitando...';
    
    try {
        const response = await fetch(`${API_BASE}/sms.php?action=buy&country=${country}&product=${product}&token=${encodeURIComponent(userToken)}`, {
            headers: { 'Authorization': `Bearer ${userToken}` }
        });
        const data = await response.json();
        
        if (response.ok && data.success) {
            // Remove empty placeholder if it exists
            const placeholder = document.getElementById('empty-orders-state');
            if (placeholder) placeholder.remove();
            
            // Add order card
            addOrderCard(data);
            
            // Sync user balance
            syncUser();
            showToast(translations[currentLang].toast_sms_requested);

            // Smooth scroll to the requested active order card
            setTimeout(() => {
                const targetCard = document.getElementById(`order-card-${data.order_id}`);
                if (targetCard) {
                    targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 100);
        } else {
            showToast(data.error || (currentLang === 'pt' ? 'Erro ao comprar número.' : 'Error purchasing number.'), 'error');
        }
    } catch (err) {
        showToast(currentLang === 'pt' ? 'Erro ao conectar ao servidor de SMS.' : 'Error connecting to SMS server.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i class="fa-solid fa-key"></i> ${translations[currentLang].panel_buy_btn}`;
    }
}

function addOrderCard(order) {
    const container = document.getElementById('orders-container');
    const orderId = order.order_id;
    
    const card = document.createElement('div');
    card.className = 'order-item-card';
    card.id = `order-card-${orderId}`;
    
    let productBadge = 'badge-other';
    if (order.product === 'whatsapp') productBadge = 'badge-whatsapp';
    if (order.product === 'telegram') productBadge = 'badge-telegram';
    if (order.product === 'google') productBadge = 'badge-google';
    
    const flagMap = {
        brazil: '🇧🇷', paraguay: '🇵🇾', argentina: '🇦🇷',
        russia: '🇷🇺', indonesia: '🇮🇩', vietnam: '🇻🇳'
    };
    const flag = flagMap[order.country] || '🌐';
    
    card.innerHTML = `
        <div class="order-header">
            <div class="order-meta-info">
                <span class="order-badge ${productBadge}">${order.product}</span>
                <span class="order-country">${flag} ${order.country.toUpperCase()}</span>
            </div>
            <div class="order-timer" id="timer-${orderId}">
                <i class="fa-solid fa-clock"></i> <span id="time-val-${orderId}">15:00</span>
            </div>
        </div>
        
        <div class="number-row">
            <span class="number-value" id="phone-${orderId}">${order.phone}</span>
            <button class="btn btn-outline btn-sm" onclick="copyText('phone-${orderId}', this)">
                <i class="fa-solid fa-copy"></i>
            </button>
        </div>
        
        <div class="sms-status-section" id="sms-section-${orderId}">
            <div class="sms-waiting">
                <i class="fa-solid fa-envelope-open-text sms-pulse-icon"></i> ${translations[currentLang].sms_waiting}
            </div>
        </div>
        
        <div class="order-actions" id="actions-${orderId}">
            <button class="btn btn-danger btn-block" onclick="cancelOrder('${orderId}')">
                ${translations[currentLang].btn_cancel}
            </button>
        </div>
    `;
    
    container.insertBefore(card, container.firstChild);
    
    // Start countdown timer (15 minutes)
    let timeLeft = 900;
    const timerInterval = setInterval(() => {
        timeLeft--;
        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            handleOrderExpired(orderId);
        } else {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById(`time-val-${orderId}`).textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
    }, 1000);
    
    // Start Polling for SMS received
    const interval = setInterval(() => {
        checkOrderStatus(orderId);
    }, 4000);
    
    activeOrders[orderId] = {
        interval,
        timerInterval,
        timeLeft
    };
}

async function checkOrderStatus(orderId) {
    try {
        const response = await fetch(`${API_BASE}/sms.php?action=check&id=${orderId}&token=${encodeURIComponent(userToken)}`, {
            headers: { 'Authorization': `Bearer ${userToken}` }
        });
        const data = await response.json();
        
        if (response.ok && data.success) {
            if (data.status === 'received') {
                // Stop polling & timer
                clearInterval(activeOrders[orderId].interval);
                clearInterval(activeOrders[orderId].timerInterval);
                
                // Update UI to show code
                const card = document.getElementById(`order-card-${orderId}`);
                card.classList.add('received');
                
                document.getElementById(`sms-section-${orderId}`).innerHTML = `
                    <div class="sms-result">
                        <span class="sms-result-label">${translations[currentLang].sms_received}</span>
                        <div class="sms-code-wrapper">
                            <span class="sms-code-value" id="code-${orderId}">${data.sms_code}</span>
                            <button class="btn btn-success btn-sm" onclick="copyText('code-${orderId}', this)">
                                <i class="fa-solid fa-copy"></i> ${translations[currentLang].btn_copy}
                            </button>
                        </div>
                        <div class="2fa-security-alert" style="margin-top: 15px; padding: 12px; background-color: rgba(234, 179, 8, 0.08); border: 1px dashed rgba(234, 179, 8, 0.3); border-radius: 8px; font-size: 11px; text-align: left; line-height: 1.4; color: rgba(234, 179, 8, 0.95);">
                            <i class="fa-solid fa-shield-halved" style="margin-right: 5px;"></i>
                            ${translations[currentLang].security_2fa_alert}
                        </div>
                    </div>
                `;
                
                // Hide timer and actions
                document.getElementById(`timer-${orderId}`).classList.add('hidden');
                document.getElementById(`actions-${orderId}`).innerHTML = `
                    <button class="btn btn-outline btn-block" onclick="removeCard('${orderId}')">
                        ${translations[currentLang].btn_clear}
                    </button>
                `;
                
                showToast(translations[currentLang].toast_sms_received, 'success');
                syncUser(); // sync final balance
                
            } else if (data.status === 'canceled') {
                // Expired / Canceled from carrier side
                clearInterval(activeOrders[orderId].interval);
                clearInterval(activeOrders[orderId].timerInterval);
                
                removeCard(orderId);
                showToast('Número expirou ou foi cancelado pelo provedor.', 'info');
                syncUser(); // sync refunded balance
            }
        }
    } catch (err) {
        console.error('Error checking SMS status:', err);
    }
}

async function cancelOrder(orderId) {
    const actions = document.getElementById(`actions-${orderId}`);
    actions.innerHTML = '<button class="btn btn-outline btn-block" disabled><i class="fa-solid fa-circle-notch fa-spin"></i> Cancelando...</button>';
    
    try {
        const response = await fetch(`${API_BASE}/sms.php?action=cancel&id=${orderId}&token=${encodeURIComponent(userToken)}`, {
            headers: { 'Authorization': `Bearer ${userToken}` }
        });
        const data = await response.json();
        
        if (response.ok && data.success) {
            // Stop polling
            clearInterval(activeOrders[orderId].interval);
            clearInterval(activeOrders[orderId].timerInterval);
            delete activeOrders[orderId];
            
            removeCard(orderId);
            showToast(translations[currentLang].toast_sms_canceled, 'info');
            syncUser();
        } else {
            showToast(data.error || (currentLang === 'pt' ? 'Erro ao cancelar.' : 'Error canceling.'), 'error');
            // Restore button
            actions.innerHTML = `
                <button class="btn btn-danger btn-block" onclick="cancelOrder('${orderId}')">
                    ${translations[currentLang].btn_cancel}
                </button>
            `;
        }
    } catch (err) {
        showToast(currentLang === 'pt' ? 'Erro ao se comunicar com o servidor.' : 'Error communicating with server.', 'error');
    }
}

function handleOrderExpired(orderId) {
    clearInterval(activeOrders[orderId].interval);
    clearInterval(activeOrders[orderId].timerInterval);
    delete activeOrders[orderId];
    
    removeCard(orderId);
    showToast(currentLang === 'pt' ? 'Tempo limite de ativação esgotado (Saldo estornado).' : 'Activation timeout expired (Balance refunded).', 'info');
    syncUser();
}

function removeCard(orderId) {
    const card = document.getElementById(`order-card-${orderId}`);
    if (card) card.remove();
    
    // If no more cards left, show empty state placeholder
    const container = document.getElementById('orders-container');
    if (container.children.length === 0) {
        container.innerHTML = `
            <div class="empty-state" id="empty-orders-state">
                <i class="fa-solid fa-mobile-screen empty-icon"></i>
                <p class="empty-text">${translations[currentLang].orders_empty_title}</p>
                <p class="empty-subtext">${translations[currentLang].orders_empty_desc}</p>
            </div>
        `;
    }
}

function copyText(elementId, btnElement) {
    const textElement = document.getElementById(elementId);
    let text = textElement.value || textElement.textContent || textElement.innerText;
    
    navigator.clipboard.writeText(text);
    showToast(translations[currentLang].toast_copy_success);
    
    if (btnElement) {
        const originalHTML = btnElement.innerHTML;
        btnElement.innerHTML = `<i class="fa-solid fa-circle-check"></i>` + (originalHTML.includes('Copiar') || originalHTML.includes('Copy') ? ' ' + (currentLang === 'pt' ? 'Copiado!' : 'Copied!') : '');
        btnElement.style.borderColor = 'var(--success)';
        
        setTimeout(() => {
            btnElement.innerHTML = originalHTML;
            btnElement.style.borderColor = '';
        }, 2000);
    }
}

// Registro de Service Worker para PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('service-worker.js')
            .then(reg => console.log('Service Worker registrado com sucesso!'))
            .catch(err => console.log('Erro ao registrar Service Worker:', err));
    });
}

// Native Web Share API with Clipboard Fallback
async function shareWebApp() {
    const cleanUrl = 'https://4u.ia.br/app/virtualsim/';
    const shareText = currentLang === 'pt'
        ? `🔥 ${cleanUrl}\n\n🔒 Precisa de um número extra do Brasil para WhatsApp ou Telegram sem comprar chip físico?\n\n⚡ O VirtualSim entrega seu código por SMS em segundos!\n🛡️ Teste sem risco: Se o SMS não chegar, o saldo é estornado 100% na hora.`
        : `🔥 ${cleanUrl}\n\n🔒 Need an extra Brazil number for WhatsApp or Telegram without buying a physical SIM?\n\n⚡ VirtualSim delivers your SMS code in seconds!\n🛡️ Risk-free: 100% refund if SMS doesn't arrive.`;

    const shareData = {
        title: 'VirtualSim — Números Virtuais e SMS no Brasil',
        text: shareText,
        url: cleanUrl
    };

    if (navigator.share) {
        try {
            await navigator.share(shareData);
        } catch (err) {
            console.log('Compartilhamento cancelado ou não suportado:', err);
        }
    } else {
        try {
            await navigator.clipboard.writeText(`${shareData.text} ${shareData.url}`);
            showToast(currentLang === 'pt' ? 'Link de compartilhamento copiado com sucesso!' : 'Share link copied successfully!', 'success');
        } catch (e) {
            showToast(currentLang === 'pt' ? 'Não foi possível copiar o link.' : 'Could not copy share link.', 'error');
        }
    }
}


