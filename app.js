/* ==========================================================================
   VirtualSim JavaScript Application (v2.0)
   Hardened auth (Bearer-only), persistent orders/history, real-time stock,
   complete PT/EN i18n, Web Audio SMS chime, and safe cancellation/expiry.
   ========================================================================== */

const API_BASE = 'api';
const GOOGLE_CLIENT_ID = '569266864432-pd09jbb5no9ekdhdr018fj643nopp817.apps.googleusercontent.com';
let googleTokenClient = null;
let userToken = localStorage.getItem('virtualsim_token') || null;
let userData = null;
let selectedService = 'whatsapp';
let selectedPackageIndex = 1; // Default: R$ 25,00 (ideal for WhatsApp BR)
let activeOrders = {};
let historyOrders = [];
let pixTransactions = [];
let pixPollInterval = null;
let toastTimer = null;
let currentDashboardTab = 'active';
let lastStockData = null;

const detectedLang = navigator.language || navigator.userLanguage || 'pt';
const defaultLang = detectedLang.startsWith('pt') ? 'pt' : 'en';
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
        panel_buy_requesting: "Solicitando...",
        panel_buy_info: "Você só paga se o código SMS for entregue!",
        country_brazil: "Brasil 🇧🇷 (Chips Físicos Ativos)",
        service_economico: "Serviço Econômico",
        stock_available: "Disponível",
        stock_unavailable: "Sem estoque",
        stock_checking: "Verificando...",
        timeline_title: "Como funciona? Passo a Passo",
        timeline_s1_title: "Abra o cadastro no WhatsApp Business",
        timeline_s1_desc: "Utilize preferencialmente o aplicativo <strong>WhatsApp Business</strong> no celular (pois possui menos exigências e regras de validação mais flexíveis).",
        timeline_s2_title: "Solicite e copie o número",
        timeline_s2_desc: "Selecione o serviço desejado acima, clique em <strong class=\"text-highlight\">Solicitar Número Agora</strong> e copie-o.",
        timeline_s3_title: "Envie o SMS de verificação",
        timeline_s3_desc: "Cole o número no aplicativo. <em>Se informar que o número gerado já foi banido/recusado, basta clicar em <strong>Cancelar e Estornar Saldo</strong> (o valor volta 100% na hora) e solicitar um novo número!</em>",
        timeline_s4_title: "Copie o código e ative",
        timeline_s4_desc: "Volte aqui, aguarde o código de 6 dígitos aparecer no card de status e copie-o para validar seu cadastro.",
        timeline_s5_title: "🔒 Dica de Ouro: WhatsApp Business & Blindagem (2FA)",
        timeline_s5_desc: "Recomendamos usar o <strong>WhatsApp Business</strong> (possui menos exigências). Se o número gerado estiver bloqueado pelo WhatsApp, basta clicar em <em>Cancelar e Estornar Saldo</em> (o saldo volta na hora) e gerar outro! Após ativar, vá em <em>Configurações &gt; Conta &gt; Confirmação em Duas Etapas</em> e crie uma senha (PIN) com seu e-mail para garantir a conta 100% sua!",
        timeline_footer: "Se o SMS não chegar em até 3 minutos, clique em <strong>Cancelar e Estornar Saldo</strong> para tentar com outro número gratuitamente!",
        panel_orders_title: "Seus Números Ativos",
        tab_active: "Ativos",
        tab_history: "Histórico",
        tab_pix: "Recargas PIX",
        orders_empty_title: "Você não possui números ativos no momento.",
        orders_empty_desc: "Selecione as opções ao lado e clique em solicitar para gerar um novo número.",
        history_empty_title: "Nenhum histórico de SMS ainda.",
        history_empty_desc: "Seus códigos recebidos e pedidos cancelados aparecerão aqui.",
        pix_empty_title: "Nenhuma recarga PIX registrada.",
        pix_empty_desc: "Suas recargas de saldo aparecerão aqui automaticamente.",
        status_received: "Recebido",
        status_canceled: "Cancelado / Estornado",
        status_approved: "Aprovado",
        status_pending: "Pendente",
        modal_auth_title: "Entrar no VirtualSim",
        auth_modal_title_register: "Criar uma Conta",
        auth_modal_title_login: "Entrar no VirtualSim",
        modal_auth_email: "E-mail:",
        modal_auth_password: "Senha:",
        modal_auth_password_placeholder: "Mínimo de 6 caracteres",
        modal_auth_error: "Erro ao autenticar.",
        modal_auth_btn_login: "Entrar",
        modal_auth_btn_register: "Cadastrar",
        modal_auth_footer_text: "Não tem uma conta?",
        modal_auth_footer_link: "Cadastrar-se",
        modal_auth_footer_has_account: "Já possui uma conta?",
        modal_auth_footer_login_link: "Entrar",
        modal_reload_title: "Adicionar Saldo via PIX",
        modal_reload_intro: "Escolha o valor de recarga. O saldo cai na sua conta em segundos após o pagamento.",
        pack0_title: "Recarga R$ 10,00",
        pack0_desc: "Ideal para Telegram, Google e outros apps",
        pack1_title: "Recarga R$ 25,00",
        pack1_desc: "Cobre 1 WhatsApp Brasil (R$ 24,90) em 1 recarga",
        pack_popular: "Ideal WhatsApp BR",
        pack2_title: "Recarga R$ 50,00",
        pack2_desc: "Crédito total de R$ 50,00",
        pack3_title: "Recarga R$ 100,00",
        pack3_desc: "Crédito total de R$ 100,00",
        modal_reload_error: "Erro ao gerar cobrança.",
        modal_reload_btn: "Gerar QR Code PIX",
        modal_reload_generating: "Gerando PIX...",
        modal_pix_success: "Cobrança PIX Gerada!",
        modal_pix_copia_cola: "Código Copia e Cola:",
        btn_copy: "Copiar",
        btn_copied: "Copiado!",
        btn_copy_local: "Sem +55",
        btn_close: "Fechar Janela",
        modal_pix_instructions: "<p>1. Abra o aplicativo do seu banco.</p><p>2. Escolha a opção <strong>Pagar via PIX</strong> (QR Code ou Copia e Cola).</p><p>3. Pague a cobrança e o saldo cairá de forma automática em segundos.</p>",
        modal_pix_waiting: "Aguardando confirmação do pagamento...",
        sms_waiting: "Aguardando SMS...",
        sms_received: "Código Recebido!",
        btn_cancel: "Cancelar e Estornar Saldo",
        btn_clear: "Limpar da Lista",
        btn_cancelling: "Cancelando...",
        toast_copy_success: "Copiado com sucesso!",
        toast_login_success: "Login realizado com sucesso!",
        toast_register_success: "Conta criada com sucesso!",
        toast_logout: "Sessão encerrada.",
        toast_reload_success: "Recarga PIX aprovada! Novo saldo:",
        toast_sms_requested: "Número solicitado! Aguarde o código SMS.",
        toast_sms_received: "Código SMS recebido!",
        toast_sms_canceled: "Número cancelado e saldo estornado!",
        toast_sms_expired: "Número expirou ou foi cancelado pelo provedor (saldo estornado).",
        toast_canceling_prev: "Cancelando número anterior e estornando saldo...",
        toast_network_error: "Erro ao conectar ao servidor.",
        toast_buy_error: "Erro ao comprar número.",
        toast_cancel_error: "Não foi possível cancelar agora.",
        security_2fa_alert: "<strong>🔒 BLINDAGEM OBRIGATÓRIA (2FA):</strong> Para garantir que ninguém mais acesse sua conta no futuro se o número for reciclado, ative agora a <strong>Confirmação em Duas Etapas</strong> (PIN de segurança) nas configurações do seu WhatsApp/Telegram!",
        footer_rights: "Todos os direitos reservados.",
        footer_privacy: "Privacidade",
        footer_terms: "Termos de Uso",
        footer_support: "Suporte",
        footer_store: "Loja de Apps 4u-Labs",
        footer_donate: "Apoiar",
        labs_subtitle: "Aplicativos, suporte e comunidade oficial da 4u-Labs.",
        labs_institutional: "Institucional",
        labs_community: "Comunidade & Redes",
        labs_portal: "Portal 4U",
        labs_store: "Loja de Apps",
        labs_help: "Central de Ajuda",
        labs_about: "Sobre 4U",
        labs_terms: "Termos",
        labs_privacy: "Privacidade",
        labs_donate: "Apoiar a 4u-Labs",
        trust_chips: "Chips físicos do Brasil",
        trust_sms: "SMS em segundos",
        trust_refund: "Estorno 100% automático",
        trust_pix: "PIX instantâneo",
        btn_google_login: "Continuar com Google",
        btn_google_connecting: "Conectando ao Google...",
        auth_or_email: "ou use seu e-mail",
        panel_buy_btn_prefix: "Solicitar",
        faq_title: "Garantia & Dúvidas Rápidas",
        faq_q1: "Se o SMS não chegar, eu perco meu saldo?",
        faq_a1: "Não! Se o código não chegar ou o número for recusado, clique em <strong>Cancelar e Estornar Saldo</strong> e 100% do valor volta na hora para você tentar outro número.",
        faq_q2: "Qual aplicativo usar para ativar WhatsApp?",
        faq_a2: "Recomendamos o <strong>WhatsApp Business</strong>, que aceita ativações com muito menos restrições. Assim que ativar, configure a Confirmação em Duas Etapas (PIN + e-mail).",
        faq_q3: "Em quanto tempo a recarga PIX cai?",
        faq_a3: "A confirmação é automática via Mercado Pago e costuma creditar o saldo em poucos segundos após o pagamento.",
        faq_q4: "E se eu fechar a página enquanto aguardo o SMS?",
        faq_a4: "Sem problema! Seu número ativo fica salvo na sua conta e reaparece automaticamente com o cronômetro e o código assim que você voltar.",
        faq_guarantee: "<strong>Compra protegida:</strong> você só é cobrado quando o código SMS é entregue com sucesso."
    },
    en: {
        nav_login: "Log In",
        nav_register: "Sign Up",
        nav_balance: "Balance:",
        nav_reload: "Top Up",
        nav_logout: "Log Out",
        nav_share: "Share",
        panel_buy_title: "Request Virtual Number",
        panel_buy_lbl_country: "1. Choose Number Country:",
        panel_buy_lbl_service: "2. Select Service / App:",
        panel_buy_btn: "Request Number Now",
        panel_buy_requesting: "Requesting...",
        panel_buy_info: "You only pay if the SMS code is delivered!",
        country_brazil: "Brazil 🇧🇷 (Active Physical SIMs)",
        service_economico: "Budget Service",
        stock_available: "Available",
        stock_unavailable: "Out of stock",
        stock_checking: "Checking...",
        timeline_title: "How it works? Step by Step",
        timeline_s1_title: "Open registration in WhatsApp Business",
        timeline_s1_desc: "We recommend using the <strong>WhatsApp Business</strong> app on your phone (fewer strict filters and more flexible validation rules).",
        timeline_s2_title: "Request and copy the number",
        timeline_s2_desc: "Select the desired service above, click <strong class=\"text-highlight\">Request Number Now</strong> and copy it.",
        timeline_s3_title: "Send verification SMS",
        timeline_s3_desc: "Paste the number in the app. <em>If it says the number is blocked/banned, simply click <strong>Cancel &amp; Refund Balance</strong> (100% refunded instantly) and request a new number!</em>",
        timeline_s4_title: "Copy the code and activate",
        timeline_s4_desc: "Return here, wait for the 6-digit code on the status card, and copy it to validate your account.",
        timeline_s5_title: "🔒 Gold Tip: WhatsApp Business & Shield (2FA)",
        timeline_s5_desc: "We recommend using <strong>WhatsApp Business</strong> (fewer strict requirements). If the generated number is blocked by WhatsApp, simply click <em>Cancel &amp; Refund Balance</em> (refunded instantly) and generate another! Once activated, go to <em>Settings &gt; Account &gt; Two-Step Verification</em> and set a PIN with your email to keep your account yours!",
        timeline_footer: "If the SMS does not arrive within 3 minutes, click <strong>Cancel &amp; Refund Balance</strong> to try another number for free!",
        panel_orders_title: "Your Active Numbers",
        tab_active: "Active",
        tab_history: "History",
        tab_pix: "PIX Top-ups",
        orders_empty_title: "You have no active numbers at the moment.",
        orders_empty_desc: "Select the options on the left and click request to generate a new number.",
        history_empty_title: "No SMS history yet.",
        history_empty_desc: "Your received codes and canceled orders will appear here.",
        pix_empty_title: "No PIX top-ups recorded.",
        pix_empty_desc: "Your balance top-ups will appear here automatically.",
        status_received: "Received",
        status_canceled: "Canceled / Refunded",
        status_approved: "Approved",
        status_pending: "Pending",
        modal_auth_title: "Log In to VirtualSim",
        auth_modal_title_register: "Create an Account",
        auth_modal_title_login: "Log In to VirtualSim",
        modal_auth_email: "Email:",
        modal_auth_password: "Password:",
        modal_auth_password_placeholder: "Minimum 6 characters",
        modal_auth_error: "Authentication failed.",
        modal_auth_btn_login: "Log In",
        modal_auth_btn_register: "Sign Up",
        modal_auth_footer_text: "Don't have an account?",
        modal_auth_footer_link: "Sign Up",
        modal_auth_footer_has_account: "Already have an account?",
        modal_auth_footer_login_link: "Log In",
        modal_reload_title: "Top Up Balance via PIX",
        modal_reload_intro: "Choose a top-up package. Balance is credited automatically in seconds after payment.",
        pack0_title: "Top Up R$ 10.00",
        pack0_desc: "Great for Telegram, Google & other apps",
        pack1_title: "Top Up R$ 25.00",
        pack1_desc: "Covers 1 Brazil WhatsApp (R$ 24.90) in one top-up",
        pack_popular: "Best for WhatsApp BR",
        pack2_title: "Top Up R$ 50.00",
        pack2_desc: "Total credit of R$ 50.00",
        pack3_title: "Top Up R$ 100.00",
        pack3_desc: "Total credit of R$ 100.00",
        modal_reload_error: "Failed to generate billing.",
        modal_reload_btn: "Generate PIX QR Code",
        modal_reload_generating: "Generating PIX...",
        modal_pix_success: "PIX Billing Generated!",
        modal_pix_copia_cola: "Copy & Paste Code:",
        btn_copy: "Copy",
        btn_copied: "Copied!",
        btn_copy_local: "No +55",
        btn_close: "Close Window",
        modal_pix_instructions: "<p>1. Open your bank app.</p><p>2. Choose <strong>Pay via PIX</strong> (QR Code or Copy &amp; Paste).</p><p>3. Pay and your balance will be credited automatically in seconds.</p>",
        modal_pix_waiting: "Waiting for payment confirmation...",
        sms_waiting: "Waiting for SMS...",
        sms_received: "Code Received!",
        btn_cancel: "Cancel & Refund Balance",
        btn_clear: "Clear from List",
        btn_cancelling: "Cancelling...",
        toast_copy_success: "Copied successfully!",
        toast_login_success: "Logged in successfully!",
        toast_register_success: "Account created successfully!",
        toast_logout: "Signed out.",
        toast_reload_success: "PIX payment approved! New balance:",
        toast_sms_requested: "Number requested! Waiting for SMS code.",
        toast_sms_received: "SMS code received!",
        toast_sms_canceled: "Number canceled and balance refunded!",
        toast_sms_expired: "Number expired or canceled by provider (balance refunded).",
        toast_canceling_prev: "Canceling previous number and refunding balance...",
        toast_network_error: "Error connecting to server.",
        toast_buy_error: "Error purchasing number.",
        toast_cancel_error: "Could not cancel right now.",
        security_2fa_alert: "<strong>🔒 MANDATORY SHIELD (2FA):</strong> To ensure no one else accesses your account in the future if the number is recycled, enable <strong>Two-Step Verification</strong> (security PIN) in your WhatsApp/Telegram settings now!",
        footer_rights: "All rights reserved.",
        footer_privacy: "Privacy",
        footer_terms: "Terms of Use",
        footer_support: "Support",
        footer_store: "4u-Labs App Store",
        footer_donate: "Support",
        labs_subtitle: "Official 4u-Labs apps, support and community.",
        labs_institutional: "Institutional",
        labs_community: "Community & Social",
        labs_portal: "4U Portal",
        labs_store: "App Store",
        labs_help: "Help Center",
        labs_about: "About 4U",
        labs_terms: "Terms",
        labs_privacy: "Privacy",
        labs_donate: "Support 4u-Labs",
        trust_chips: "Brazil physical SIMs",
        trust_sms: "SMS in seconds",
        trust_refund: "100% automatic refund",
        trust_pix: "Instant PIX",
        btn_google_login: "Continue with Google",
        btn_google_connecting: "Connecting to Google...",
        auth_or_email: "or use your email",
        panel_buy_btn_prefix: "Request",
        faq_title: "Guarantee & Quick FAQ",
        faq_q1: "If the SMS doesn't arrive, do I lose my balance?",
        faq_a1: "No! If the code doesn't arrive or the number is rejected, click <strong>Cancel &amp; Refund Balance</strong> and 100% of the value returns instantly so you can try another number.",
        faq_q2: "Which app should I use to activate WhatsApp?",
        faq_a2: "We recommend <strong>WhatsApp Business</strong>, which has fewer strict filters. Right after activating, enable Two-Step Verification (PIN + email).",
        faq_q3: "How fast is PIX balance credited?",
        faq_a3: "Confirmation is automatic via Mercado Pago and usually credits your balance within seconds after payment.",
        faq_q4: "What if I close the page while waiting for the SMS?",
        faq_a4: "No problem! Your active number stays saved to your account and reappears automatically with its countdown and code when you return.",
        faq_guarantee: "<strong>Protected purchase:</strong> you are only charged when the SMS code is delivered."
    }
};

function t(key) {
    return (translations[currentLang] && translations[currentLang][key]) || translations.pt[key] || key;
}

function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function authHeaders(extra = {}) {
    const h = { ...extra };
    if (userToken) h['Authorization'] = `Bearer ${userToken}`;
    return h;
}

function setLanguage(lang) {
    currentLang = lang === 'en' ? 'en' : 'pt';
    localStorage.setItem('lang', currentLang);
    updateLanguageUI();
    renderAvailabilityBadges();
    renderHistoryTab();
    renderPixTab();
}

function updateLanguageUI() {
    document.documentElement.lang = currentLang === 'en' ? 'en' : 'pt-BR';

    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        const val = translations[currentLang]?.[key];
        if (val !== undefined) {
            el.innerHTML = val;
        }
    });

    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        const val = translations[currentLang]?.[key];
        if (val !== undefined) {
            el.setAttribute('placeholder', val);
        }
    });

    const btnPt = document.getElementById('lang-btn-pt');
    const btnEn = document.getElementById('lang-btn-en');
    if (btnPt && btnEn) {
        btnPt.classList.toggle('active', currentLang === 'pt');
        btnEn.classList.toggle('active', currentLang === 'en');
    }

    document.title = currentLang === 'pt'
        ? 'VirtualSim — Números Virtuais e SMS em Tempo Real'
        : 'VirtualSim — Virtual Numbers & SMS in Real Time';

    const logoutBtn = document.getElementById('btn-logout-title');
    if (logoutBtn) logoutBtn.setAttribute('title', t('nav_logout'));

    const shareBtn = document.getElementById('btn-share-app');
    if (shareBtn) shareBtn.setAttribute('title', t('nav_share'));

    updateRequestButtonLabel();
}

// ==========================================================================
//  Initialization
// ==========================================================================
document.addEventListener('DOMContentLoaded', () => {
    initApp();
});

function initApp() {
    updateLanguageUI();
    updatePricingForCountry();
    loadAvailability();

    if (userToken) {
        syncUser(true);
    } else {
        updateNavbarState(false);
        renderHistoryTab();
        renderPixTab();
    }

    document.getElementById('select-country')?.addEventListener('change', () => {
        updatePricingForCountry();
        loadAvailability();
    });

    // Close modals on backdrop click or Escape
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) closeModal(backdrop.id);
        });
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            ['auth-modal', 'reload-modal', 'labs-modal'].forEach(id => {
                const el = document.getElementById(id);
                if (el && !el.classList.contains('hidden')) closeModal(id);
            });
        }
    });
}

// ==========================================================================
//  Real-Time Availability & Pricing
// ==========================================================================
function updatePricingForCountry() {
    const country = document.getElementById('select-country').value;
    const whatsappPrice = document.getElementById('price-whatsapp');
    if (whatsappPrice) {
        whatsappPrice.textContent = country === 'brazil' ? 'R$ 24,90' : 'R$ 4,90';
    }
    updateRequestButtonLabel();
}

function getSelectedServiceInfo() {
    const country = document.getElementById('select-country')?.value || 'brazil';
    const map = {
        whatsapp:  { name: 'WhatsApp', price: country === 'brazil' ? 'R$ 24,90' : 'R$ 4,90' },
        telegram:  { name: 'Telegram', price: 'R$ 2,90' },
        google:    { name: 'Google / Gmail', price: 'R$ 1,50' },
        microsoft: { name: 'Microsoft', price: 'R$ 1,50' },
        tiktok:    { name: 'TikTok', price: 'R$ 1,50' },
        economico: { name: t('service_economico'), price: 'R$ 0,50' }
    };
    return map[selectedService] || map.whatsapp;
}

function updateRequestButtonLabel() {
    const btn = document.getElementById('btn-request-number');
    if (!btn || btn.disabled) return;
    const info = getSelectedServiceInfo();
    btn.innerHTML = `<i class="fa-solid fa-key"></i> <span>${escapeHtml(t('panel_buy_btn_prefix'))} ${escapeHtml(info.name)} — ${escapeHtml(info.price)}</span>`;
}

async function loadAvailability() {
    const country = document.getElementById('select-country')?.value || 'brazil';
    try {
        const res = await fetch(`${API_BASE}/sms.php?action=availability&country=${encodeURIComponent(country)}`);
        const data = await res.json();
        if (res.ok && data.success && data.services) {
            lastStockData = data.services;
            renderAvailabilityBadges();
        }
    } catch {
        // Silently keep badges subtle if offline
    }
}

function renderAvailabilityBadges() {
    if (!lastStockData) return;
    for (const [service, count] of Object.entries(lastStockData)) {
        const el = document.getElementById(`stock-${service}`);
        if (!el) continue;
        if (count === null || count === undefined) {
            el.className = 'stock-badge stock-loading';
            el.textContent = '—';
        } else if (count > 0) {
            el.className = 'stock-badge stock-ok';
            el.textContent = `● ${t('stock_available')}`;
        } else {
            el.className = 'stock-badge stock-empty';
            el.textContent = `● ${t('stock_unavailable')}`;
        }
    }
}

// ==========================================================================
//  Sound & Browser Notification when SMS arrives
// ==========================================================================
function playSmsChime() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const now = ctx.currentTime;
        [880, 1174.66].forEach((freq, idx) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, now + idx * 0.12);
            gain.gain.setValueAtTime(0.001, now + idx * 0.12);
            gain.gain.exponentialRampToValueAtTime(0.14, now + idx * 0.12 + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.001, now + idx * 0.12 + 0.28);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(now + idx * 0.12);
            osc.stop(now + idx * 0.12 + 0.3);
        });
    } catch {}
}

function notifySmsReceived(code) {
    playSmsChime();
    try {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('VirtualSim — ' + t('sms_received'), {
                body: `SMS: ${code}`,
                icon: 'icon-192.png'
            });
        }
    } catch {}
}

// ==========================================================================
//  UI Controls (Modals, Tabs & Toast)
// ==========================================================================
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.className = `toast ${type === 'error' ? 'toast-error' : ''}`;

    let icon = '<i class="fa-solid fa-circle-check text-success"></i>';
    if (type === 'error') icon = '<i class="fa-solid fa-circle-exclamation text-danger"></i>';
    if (type === 'info') icon = '<i class="fa-solid fa-circle-info text-primary"></i>';

    toast.innerHTML = `${icon} <span>${escapeHtml(message)}</span>`;
    toast.classList.remove('hidden');

    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toast.classList.add('hidden');
    }, 5000);
}

function openLabsModal() {
    document.getElementById('labs-modal')?.classList.remove('hidden');
}

function openAuthModal(mode = 'login') {
    const modal = document.getElementById('auth-modal');
    modal.classList.remove('hidden');

    const title = document.getElementById('auth-modal-title');
    const submitBtn = document.getElementById('btn-auth-submit');
    const toggleText = document.getElementById('auth-toggle-text');
    const toggleLink = document.getElementById('auth-toggle-link');
    const passInput = document.getElementById('auth-password');

    document.getElementById('auth-error').classList.add('hidden');

    if (mode === 'login') {
        title.textContent = t('auth_modal_title_login');
        submitBtn.textContent = t('modal_auth_btn_login');
        toggleText.textContent = t('modal_auth_footer_text');
        toggleLink.textContent = t('modal_auth_footer_link');
        toggleLink.setAttribute('onclick', "toggleAuthMode(event, 'register')");
        submitBtn.setAttribute('data-mode', 'login');
        if (passInput) passInput.setAttribute('autocomplete', 'current-password');
    } else {
        title.textContent = t('auth_modal_title_register');
        submitBtn.textContent = t('modal_auth_btn_register');
        toggleText.textContent = t('modal_auth_footer_has_account');
        toggleLink.textContent = t('modal_auth_footer_login_link');
        toggleLink.setAttribute('onclick', "toggleAuthMode(event, 'login')");
        submitBtn.setAttribute('data-mode', 'register');
        if (passInput) passInput.setAttribute('autocomplete', 'new-password');
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

    selectPackage(1); // Default R$ 25,00 (ideal for WhatsApp BR)
}

function closeModal(modalId) {
    document.getElementById(modalId)?.classList.add('hidden');
    if (modalId === 'reload-modal' && pixPollInterval) {
        clearInterval(pixPollInterval);
        pixPollInterval = null;
    }
}

function selectService(cardElement) {
    document.querySelectorAll('.service-card').forEach(card => card.classList.remove('active'));
    cardElement.classList.add('active');
    selectedService = cardElement.getAttribute('data-service');
    updateRequestButtonLabel();
}

function selectPackage(index) {
    document.querySelectorAll('.package-item').forEach((item, idx) => {
        item.classList.toggle('active', idx === index);
    });
    selectedPackageIndex = index;
}

function switchDashboardTab(tab) {
    currentDashboardTab = tab;
    const map = {
        active: { btn: 'tab-btn-active', pane: 'orders-container' },
        history: { btn: 'tab-btn-history', pane: 'history-container' },
        pix: { btn: 'tab-btn-pix', pane: 'pix-history-container' }
    };
    Object.entries(map).forEach(([key, ids]) => {
        document.getElementById(ids.btn)?.classList.toggle('active', key === tab);
        document.getElementById(ids.pane)?.classList.toggle('hidden', key !== tab);
    });
}

function updateTabCounts() {
    const activeCount = Object.keys(activeOrders).length;
    const elA = document.getElementById('tab-count-active');
    const elH = document.getElementById('tab-count-history');
    const elP = document.getElementById('tab-count-pix');
    if (elA) elA.textContent = String(activeCount);
    if (elH) elH.textContent = String(historyOrders.length);
    if (elP) elP.textContent = String(pixTransactions.length);
}

// ==========================================================================
//  User Session & Auth API Requests
// ==========================================================================
function updateNavbarState(isLoggedIn) {
    const authButtons = document.getElementById('auth-buttons');
    const userProfile = document.getElementById('user-profile');
    const avatar = document.getElementById('header-avatar');

    if (isLoggedIn && userData) {
        authButtons.classList.add('hidden');
        userProfile.classList.remove('hidden');
        document.getElementById('header-username').textContent = userData.display_name;
        document.getElementById('header-balance').textContent = formatCentsToBRL(userData.credits);
        if (avatar) {
            if (userData.avatar_url) {
                avatar.src = userData.avatar_url;
                avatar.classList.remove('hidden');
            } else {
                avatar.classList.add('hidden');
            }
        }
    } else {
        authButtons.classList.remove('hidden');
        userProfile.classList.add('hidden');
        if (avatar) avatar.classList.add('hidden');
    }
}

function startGoogleLogin() {
    const errorBanner = document.getElementById('auth-error');
    const btn = document.getElementById('btn-google-login');
    errorBanner?.classList.add('hidden');

    if (!window.google?.accounts?.oauth2) {
        if (errorBanner) {
            errorBanner.textContent = currentLang === 'pt'
                ? 'Serviço do Google ainda carregando. Tente novamente em 1 segundo.'
                : 'Google sign-in is still loading. Please try again in a second.';
            errorBanner.classList.remove('hidden');
        }
        return;
    }

    const originalHtml = btn ? btn.innerHTML : '';
    const resetBtn = () => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    };

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> <span>${escapeHtml(t('btn_google_connecting'))}</span>`;
    }

    try {
        googleTokenClient = window.google.accounts.oauth2.initTokenClient({
            client_id: GOOGLE_CLIENT_ID,
            scope: 'openid email profile',
            error_callback: () => {
                resetBtn();
            },
            callback: async (tokenResponse) => {
                if (!tokenResponse || !tokenResponse.access_token) {
                    resetBtn();
                    return;
                }
                try {
                    const response = await fetch(`${API_BASE}/auth.php?action=google`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ access_token: tokenResponse.access_token })
                    });
                    const data = await response.json();
                    if (response.ok && data.success) {
                        userToken = data.token;
                        localStorage.setItem('virtualsim_token', userToken);
                        userData = data.user;
                        updateNavbarState(true);
                        closeModal('auth-modal');
                        showToast(data.is_new ? t('toast_register_success') : t('toast_login_success'));
                        loadOrdersAndHistory();
                    } else if (errorBanner) {
                        errorBanner.textContent = data.error || t('modal_auth_error');
                        errorBanner.classList.remove('hidden');
                    }
                } catch {
                    if (errorBanner) {
                        errorBanner.textContent = t('toast_network_error');
                        errorBanner.classList.remove('hidden');
                    }
                } finally {
                    resetBtn();
                }
            }
        });
        googleTokenClient.requestAccessToken({ prompt: 'select_account' });
    } catch {
        resetBtn();
    }
}

function formatCentsToBRL(cents) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format((Number(cents) || 0) / 100);
}

function formatDateTime(isoUtc) {
    if (!isoUtc) return '';
    try {
        const d = new Date(isoUtc.replace(' ', 'T') + (isoUtc.includes('T') ? '' : 'Z'));
        return d.toLocaleString(currentLang === 'en' ? 'en-US' : 'pt-BR', {
            day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit'
        });
    } catch {
        return isoUtc;
    }
}

async function syncUser(alsoLoadOrders = false) {
    if (!userToken) return;
    try {
        const response = await fetch(`${API_BASE}/auth.php`, {
            headers: authHeaders()
        });
        if (response.status === 401) {
            logout(true);
            return;
        }
        const data = await response.json();
        if (data.success) {
            userData = data.user;
            updateNavbarState(true);
            if (alsoLoadOrders) {
                loadOrdersAndHistory();
            }
        }
    } catch (err) {
        console.error('Failed to sync user:', err);
    }
}

async function handleAuthSubmit(event) {
    event.preventDefault();

    const email = document.getElementById('auth-email').value.trim();
    const password = document.getElementById('auth-password').value;
    const submitBtn = document.getElementById('btn-auth-submit');
    const mode = submitBtn.getAttribute('data-mode') || 'login';

    const errorBanner = document.getElementById('auth-error');
    errorBanner.classList.add('hidden');
    submitBtn.disabled = true;

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
            showToast(mode === 'register' ? t('toast_register_success') : t('toast_login_success'));
            loadOrdersAndHistory();
        } else {
            errorBanner.textContent = data.error || t('modal_auth_error');
            errorBanner.classList.remove('hidden');
        }
    } catch {
        errorBanner.textContent = t('toast_network_error');
        errorBanner.classList.remove('hidden');
    } finally {
        submitBtn.disabled = false;
    }
}

function logout(silent = false) {
    const prevToken = userToken;
    if (prevToken) {
        fetch(`${API_BASE}/auth.php?action=logout`, {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${prevToken}` }
        }).catch(() => {});
    }

    userToken = null;
    userData = null;
    historyOrders = [];
    pixTransactions = [];
    localStorage.removeItem('virtualsim_token');
    updateNavbarState(false);
    if (!silent) showToast(t('toast_logout'), 'info');

    Object.keys(activeOrders).forEach(id => {
        clearInterval(activeOrders[id].interval);
        clearInterval(activeOrders[id].timerInterval);
    });
    activeOrders = {};

    renderEmptyActiveState();
    renderHistoryTab();
    renderPixTab();
    updateTabCounts();
}

function renderEmptyActiveState() {
    document.getElementById('orders-container').innerHTML = `
        <div class="empty-state" id="empty-orders-state">
            <i class="fa-solid fa-mobile-screen empty-icon"></i>
            <p class="empty-text" data-i18n="orders_empty_title">${escapeHtml(t('orders_empty_title'))}</p>
            <p class="empty-subtext" data-i18n="orders_empty_desc">${escapeHtml(t('orders_empty_desc'))}</p>
        </div>
    `;
}

// ==========================================================================
//  Persistent Orders & History Loading
// ==========================================================================
async function loadOrdersAndHistory() {
    if (!userToken) return;
    try {
        const res = await fetch(`${API_BASE}/sms.php?action=list`, {
            headers: authHeaders()
        });
        if (res.status === 401) {
            logout(true);
            return;
        }
        const data = await res.json();
        if (!res.ok || !data.success) return;

        historyOrders = data.history || [];
        pixTransactions = data.transactions || [];

        const activeList = data.active || [];
        if (activeList.length > 0) {
            const placeholder = document.getElementById('empty-orders-state');
            if (placeholder) placeholder.remove();
            for (const order of activeList) {
                if (!activeOrders[order.order_id]) {
                    addOrderCard(order);
                    // Trigger immediate status check on restored orders
                    checkOrderStatus(order.order_id);
                }
            }
        }

        renderHistoryTab();
        renderPixTab();
        updateTabCounts();
    } catch (err) {
        console.error('Failed to load orders/history:', err);
    }
}

function renderHistoryTab() {
    const container = document.getElementById('history-container');
    if (!container) return;

    if (!historyOrders.length) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fa-solid fa-clock-rotate-left empty-icon"></i>
                <p class="empty-text">${escapeHtml(t('history_empty_title'))}</p>
                <p class="empty-subtext">${escapeHtml(t('history_empty_desc'))}</p>
            </div>
        `;
        return;
    }

    container.innerHTML = historyOrders.map(item => {
        const isRec = item.status === 'received';
        const statusCls = isRec ? 'status-received' : 'status-canceled';
        const statusLabel = isRec ? t('status_received') : t('status_canceled');
        const rightHtml = isRec && item.sms_code
            ? `<button type="button" class="history-code-pill" onclick="copyRawText('${escapeHtml(item.sms_code)}', this)" title="${escapeHtml(t('btn_copy'))}">
                   <i class="fa-solid fa-copy"></i> ${escapeHtml(item.sms_code)}
               </button>`
            : `<span class="history-status-pill ${statusCls}">${escapeHtml(statusLabel)}</span>`;

        return `
            <div class="history-item">
                <div class="history-item-main">
                    <div class="history-item-title">
                        <span class="order-badge badge-${escapeHtml(item.product)}">${escapeHtml(item.product)}</span>
                        <span>${escapeHtml(item.phone)}</span>
                    </div>
                    <div class="history-item-sub">${escapeHtml(formatDateTime(item.created_at))} &bull; ${escapeHtml(formatCentsToBRL(item.cost_cents))}</div>
                </div>
                ${rightHtml}
            </div>
        `;
    }).join('');
}

function renderPixTab() {
    const container = document.getElementById('pix-history-container');
    if (!container) return;

    if (!pixTransactions.length) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fa-solid fa-wallet empty-icon"></i>
                <p class="empty-text">${escapeHtml(t('pix_empty_title'))}</p>
                <p class="empty-subtext">${escapeHtml(t('pix_empty_desc'))}</p>
            </div>
        `;
        return;
    }

    container.innerHTML = pixTransactions.map(tx => {
        const status = tx.status || 'pending';
        const statusCls = status === 'approved' ? 'status-approved' : (status === 'pending' ? 'status-pending' : 'status-canceled');
        const statusLabel = status === 'approved' ? t('status_approved') : (status === 'pending' ? t('status_pending') : t('status_canceled'));
        return `
            <div class="history-item">
                <div class="history-item-main">
                    <div class="history-item-title">
                        <span>${escapeHtml(tx.package_label || 'PIX')}</span>
                    </div>
                    <div class="history-item-sub">${escapeHtml(formatDateTime(tx.created_at))} &bull; ${escapeHtml(formatCentsToBRL(tx.credits_added))}</div>
                </div>
                <span class="history-status-pill ${statusCls}">${escapeHtml(statusLabel)}</span>
            </div>
        `;
    }).join('');
}

// ==========================================================================
//  Recharge Pix Checkout
// ==========================================================================
async function generatePix() {
    const errorBanner = document.getElementById('reload-error');
    const btn = document.getElementById('btn-generate-pix');
    errorBanner.classList.add('hidden');
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> <span>${escapeHtml(t('modal_reload_generating'))}</span>`;

    try {
        const response = await fetch(`${API_BASE}/mp_create.php`, {
            method: 'POST',
            headers: authHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ package_index: selectedPackageIndex })
        });

        const data = await response.json();
        if (response.ok && data.success) {
            document.getElementById('reload-step-select').classList.add('hidden');
            document.getElementById('reload-step-qrcode').classList.remove('hidden');

            document.getElementById('pix-qrcode-img').src = `data:image/png;base64,${data.qr_code_base64}`;
            document.getElementById('pix-copia-cola').value = data.qr_code;

            startPixPolling(userData ? userData.credits : 0);
            loadOrdersAndHistory();
        } else {
            errorBanner.textContent = data.error || t('modal_reload_error');
            errorBanner.classList.remove('hidden');
        }
    } catch {
        errorBanner.textContent = t('toast_network_error');
        errorBanner.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i class="fa-solid fa-qrcode"></i> <span data-i18n="modal_reload_btn">${escapeHtml(t('modal_reload_btn'))}</span>`;
    }
}

function copyPixCode(btnElement) {
    const input = document.getElementById('pix-copia-cola');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value);
    showToast(t('toast_copy_success'));

    if (btnElement) {
        const originalHTML = btnElement.innerHTML;
        btnElement.innerHTML = `<i class="fa-solid fa-circle-check"></i> ${escapeHtml(t('btn_copied'))}`;
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
            const response = await fetch(`${API_BASE}/auth.php`, {
                headers: authHeaders()
            });
            const data = await response.json();
            if (data.success && data.user.credits > previousCredits) {
                clearInterval(pixPollInterval);
                pixPollInterval = null;

                userData = data.user;
                updateNavbarState(true);
                closeModal('reload-modal');
                showToast(`${t('toast_reload_success')} ${formatCentsToBRL(userData.credits)}`);
                loadOrdersAndHistory();
            }
        } catch (err) {
            console.error('Error polling Pix:', err);
        }
    }, 4000);
}

// ==========================================================================
//  SMS Virtual Numbers Ordering & Polling
// ==========================================================================
async function requestNumber() {
    if (!userToken) {
        openAuthModal('login');
        return;
    }

    // Request browser notification permission gently on first order interaction
    try {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().catch(() => {});
        }
    } catch {}

    // Auto-cancel previous active orders before placing a new order
    const existingOrderIds = Object.keys(activeOrders);
    if (existingOrderIds.length > 0) {
        showToast(t('toast_canceling_prev'), 'info');
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
    btn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> <span>${escapeHtml(t('panel_buy_requesting'))}</span>`;

    try {
        const response = await fetch(`${API_BASE}/sms.php?action=buy`, {
            method: 'POST',
            headers: authHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ country, product })
        });
        const data = await response.json();

        if (response.ok && data.success) {
            switchDashboardTab('active');
            const placeholder = document.getElementById('empty-orders-state');
            if (placeholder) placeholder.remove();

            addOrderCard(data);
            updateTabCounts();
            syncUser();
            showToast(t('toast_sms_requested'));

            setTimeout(() => {
                document.getElementById(`order-card-${data.order_id}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
        } else {
            showToast(data.error || t('toast_buy_error'), 'error');
        }
    } catch {
        showToast(t('toast_network_error'), 'error');
    } finally {
        btn.disabled = false;
        updateRequestButtonLabel();
    }
}

function stripBrazilDDI(phone) {
    const clean = String(phone || '').replace(/[^\d+]/g, '');
    if (clean.startsWith('+55')) return clean.slice(3);
    if (clean.startsWith('55') && clean.length >= 12) return clean.slice(2);
    return clean;
}

function addOrderCard(order) {
    const container = document.getElementById('orders-container');
    const orderId = String(order.order_id);
    if (document.getElementById(`order-card-${orderId}`)) return;

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
    const localDigits = stripBrazilDDI(order.phone);
    const hasLocalVariant = localDigits !== String(order.phone || '').replace(/[^\d+]/g, '');

    let timeLeft = typeof order.remaining_seconds === 'number' ? order.remaining_seconds : 900;
    const initMin = Math.floor(timeLeft / 60).toString().padStart(2, '0');
    const initSec = (timeLeft % 60).toString().padStart(2, '0');

    card.innerHTML = `
        <div class="order-header">
            <div class="order-meta-info">
                <span class="order-badge ${productBadge}">${escapeHtml(order.product)}</span>
                <span class="order-country">${flag} ${escapeHtml(String(order.country || '').toUpperCase())}</span>
            </div>
            <div class="order-timer" id="timer-${escapeHtml(orderId)}">
                <i class="fa-solid fa-clock"></i> <span id="time-val-${escapeHtml(orderId)}">${initMin}:${initSec}</span>
            </div>
        </div>

        <div class="number-row">
            <span class="number-value" id="phone-${escapeHtml(orderId)}">${escapeHtml(order.phone)}</span>
            <div class="number-actions-group">
                ${hasLocalVariant ? `<button type="button" class="btn btn-outline btn-sm btn-copy-local" onclick="copyRawText('${escapeHtml(localDigits)}', this)" title="${escapeHtml(t('btn_copy_local'))}">${escapeHtml(t('btn_copy_local'))}</button>` : ''}
                <button type="button" class="btn btn-outline btn-sm" onclick="copyText('phone-${escapeHtml(orderId)}', this)" title="${escapeHtml(t('btn_copy'))}">
                    <i class="fa-solid fa-copy"></i>
                </button>
            </div>
        </div>

        <div class="sms-status-section" id="sms-section-${escapeHtml(orderId)}">
            <div class="sms-waiting">
                <i class="fa-solid fa-envelope-open-text sms-pulse-icon"></i> ${escapeHtml(t('sms_waiting'))}
            </div>
        </div>

        <div class="order-actions" id="actions-${escapeHtml(orderId)}">
            <button type="button" class="btn btn-danger btn-block" onclick="cancelOrder('${escapeHtml(orderId)}')">
                ${escapeHtml(t('btn_cancel'))}
            </button>
        </div>
    `;

    container.insertBefore(card, container.firstChild);

    const timerInterval = setInterval(() => {
        timeLeft--;
        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            handleOrderExpired(orderId);
        } else {
            const minutes = Math.floor(timeLeft / 60).toString().padStart(2, '0');
            const seconds = (timeLeft % 60).toString().padStart(2, '0');
            const tv = document.getElementById(`time-val-${orderId}`);
            if (tv) tv.textContent = `${minutes}:${seconds}`;
        }
    }, 1000);

    const interval = setInterval(() => {
        checkOrderStatus(orderId);
    }, 4000);

    activeOrders[orderId] = { interval, timerInterval, timeLeft };
}

async function checkOrderStatus(orderId) {
    if (!userToken || !activeOrders[orderId]) return;
    try {
        const response = await fetch(`${API_BASE}/sms.php?action=check&id=${encodeURIComponent(orderId)}`, {
            headers: authHeaders()
        });
        const data = await response.json();

        if (response.ok && data.success) {
            if (data.status === 'received') {
                clearInterval(activeOrders[orderId]?.interval);
                clearInterval(activeOrders[orderId]?.timerInterval);
                delete activeOrders[orderId];

                const card = document.getElementById(`order-card-${orderId}`);
                if (card) card.classList.add('received');

                const smsSection = document.getElementById(`sms-section-${orderId}`);
                if (smsSection) {
                    smsSection.innerHTML = `
                        <div class="sms-result">
                            <span class="sms-result-label">${escapeHtml(t('sms_received'))}</span>
                            <div class="sms-code-wrapper">
                                <span class="sms-code-value" id="code-${escapeHtml(orderId)}">${escapeHtml(data.sms_code)}</span>
                                <button type="button" class="btn btn-success btn-sm" onclick="copyText('code-${escapeHtml(orderId)}', this)">
                                    <i class="fa-solid fa-copy"></i> ${escapeHtml(t('btn_copy'))}
                                </button>
                            </div>
                            <div class="2fa-security-alert" style="margin-top: 15px; padding: 12px; background-color: rgba(234, 179, 8, 0.08); border: 1px dashed rgba(234, 179, 8, 0.3); border-radius: 8px; font-size: 11px; text-align: left; line-height: 1.4; color: rgba(234, 179, 8, 0.95);">
                                <i class="fa-solid fa-shield-halved" style="margin-right: 5px;"></i>
                                ${t('security_2fa_alert')}
                            </div>
                        </div>
                    `;
                }

                document.getElementById(`timer-${orderId}`)?.classList.add('hidden');
                const actions = document.getElementById(`actions-${orderId}`);
                if (actions) {
                    actions.innerHTML = `
                        <button type="button" class="btn btn-outline btn-block" onclick="removeCard('${escapeHtml(orderId)}')">
                            ${escapeHtml(t('btn_clear'))}
                        </button>
                    `;
                }

                notifySmsReceived(data.sms_code);
                showToast(t('toast_sms_received'), 'success');
                syncUser(true);

            } else if (data.status === 'canceled') {
                clearInterval(activeOrders[orderId]?.interval);
                clearInterval(activeOrders[orderId]?.timerInterval);
                delete activeOrders[orderId];

                removeCard(orderId);
                showToast(t('toast_sms_expired'), 'info');
                syncUser(true);
            }
        }
    } catch (err) {
        console.error('Error checking SMS status:', err);
    }
}

function restoreCancelButton(orderId) {
    const actions = document.getElementById(`actions-${orderId}`);
    if (actions) {
        actions.innerHTML = `
            <button type="button" class="btn btn-danger btn-block" onclick="cancelOrder('${escapeHtml(orderId)}')">
                ${escapeHtml(t('btn_cancel'))}
            </button>
        `;
    }
}

async function cancelOrder(orderId) {
    const actions = document.getElementById(`actions-${orderId}`);
    if (actions) {
        actions.innerHTML = `<button type="button" class="btn btn-outline btn-block" disabled><i class="fa-solid fa-circle-notch fa-spin"></i> ${escapeHtml(t('btn_cancelling'))}</button>`;
    }

    try {
        const response = await fetch(`${API_BASE}/sms.php?action=cancel`, {
            method: 'POST',
            headers: authHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ id: orderId })
        });
        const data = await response.json();

        if (response.ok && data.success) {
            clearInterval(activeOrders[orderId]?.interval);
            clearInterval(activeOrders[orderId]?.timerInterval);
            delete activeOrders[orderId];

            removeCard(orderId);
            showToast(t('toast_sms_canceled'), 'info');
            syncUser(true);
        } else {
            showToast(data.error || t('toast_cancel_error'), 'error');
            restoreCancelButton(orderId);
        }
    } catch {
        showToast(t('toast_network_error'), 'error');
        restoreCancelButton(orderId);
    }
}

// When timer hits 00:00, check server status & request real backend cancellation/refund
async function handleOrderExpired(orderId) {
    if (!activeOrders[orderId]) return;
    await checkOrderStatus(orderId);
    if (activeOrders[orderId]) {
        await cancelOrder(orderId);
    }
}

function removeCard(orderId) {
    document.getElementById(`order-card-${orderId}`)?.remove();
    const container = document.getElementById('orders-container');
    if (container && container.children.length === 0) {
        renderEmptyActiveState();
    }
    updateTabCounts();
}

function copyText(elementId, btnElement) {
    const textElement = document.getElementById(elementId);
    if (!textElement) return;
    const text = textElement.value || textElement.textContent || textElement.innerText;
    copyRawText(text, btnElement);
}

function copyRawText(text, btnElement) {
    navigator.clipboard.writeText(text).then(() => {
        showToast(t('toast_copy_success'));
    }).catch(() => {});

    if (btnElement) {
        const originalHTML = btnElement.innerHTML;
        btnElement.innerHTML = `<i class="fa-solid fa-circle-check"></i>`;
        btnElement.style.borderColor = 'var(--success)';

        setTimeout(() => {
            btnElement.innerHTML = originalHTML;
            btnElement.style.borderColor = '';
        }, 1800);
    }
}

// Service Worker registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('service-worker.js').catch(() => {});
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
        } catch {}
    } else {
        try {
            await navigator.clipboard.writeText(shareData.text);
            showToast(t('toast_copy_success'), 'success');
        } catch {
            showToast(t('toast_network_error'), 'error');
        }
    }
}
