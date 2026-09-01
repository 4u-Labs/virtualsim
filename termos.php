<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
$assetVersion = time();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Termos de Uso — VirtualSim</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512' fill='%230ea5e9'%3E%3Cpath d='M416 128H384v-32c0-35.3-28.7-64-64-64h-32V0h-32v32h-32V0h-32v32h-32c-35.3 0-64 28.7-64 64v32H96c-17.7 0-32 14.3-32 32v32H32v32h32v32H32v32h32v32c0 17.7 14.3 32 32 32h32v32c0 35.3 28.7 64 64 64h32v32h32v-32h32v32h32v-32h32c35.3 0 64-28.7 64-64v-32h32c17.7 0 32-14.3 32-32v-32h32v-32h-32v-32h32v-32h-32v-32c0-17.7-14.3-32-32-32zM352 352H160V160h192v192z'/%3E%3C/svg%3E">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box !important; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #070a12; color: #cbd5e1; }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between">

    <!-- Top Header -->
    <header class="bg-gray-900/90 border-b border-gray-800 py-4 px-6 sticky top-0 z-30 backdrop-blur-md">
        <div class="max-w-5xl mx-auto flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-2 text-white font-extrabold text-lg">
                <span class="text-sky-400">Virtual</span>Sim
            </a>
            <a href="index.php" class="text-xs text-sky-400 hover:text-sky-300 font-bold">
                ← Voltar ao App
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-6 py-12 flex-1">
        <div class="bg-gray-900/60 border border-gray-800 p-8 rounded-2xl shadow-2xl space-y-6">
            <h1 class="text-3xl font-extrabold text-white">Termos de Uso</h1>
            <p class="text-xs text-gray-400">Última atualização: <?php echo date("d/m/Y"); ?></p>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-white">1. Aceitação dos Termos</h2>
                <p class="text-sm text-gray-300 leading-relaxed">Ao acessar ou utilizar a plataforma <strong>VirtualSim</strong>, você concorda expressamente em cumprir todos os termos, condições e políticas aqui descritos. Se você não concordar com qualquer disposição, não deverá utilizar nossos serviços.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-white">2. Descrição dos Serviços e Regra de Reembolso</h2>
                <p class="text-sm text-gray-300 leading-relaxed">O <strong>VirtualSim</strong> disponibiliza o aluguel temporário de números virtuais para recebimento de SMS de verificação. Caso o SMS não seja recebido durante o período de validade do pedido ou caso a ordem seja cancelada antes do recebimento do código, o saldo cobrado é reembolsado automaticamente para a carteira do usuário no sistema.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-white">3. Conduta Permitida e Uso Responsável</h2>
                <p class="text-sm text-gray-300 leading-relaxed">É estritamente proibido utilizar os números virtuais do VirtualSim para qualquer finalidade ilícita, incluindo, sem limitação, fraude, spam, engenharia social ou violação de termos de terceiros. O uso indevido resultará no bloqueio imediato da conta sem direito a restituição de saldos.</p>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-bold text-white">4. Limitação de Responsabilidade Legal</h2>
                <p class="text-sm text-gray-300 leading-relaxed">O VirtualSim atua como intermediário técnico de utilitários SMS e não se responsabiliza por políticas internas de bloqueio, suspensão ou diretrizes dos aplicativos finais (como WhatsApp, Telegram ou Google).</p>
            </section>
        </div>
    </main>

    <!-- Institutional Footer -->
    <footer class="bg-gray-900 border-t border-gray-800 py-6 px-6 text-center text-xs text-gray-500">
        <div class="max-w-4xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <div>&copy; <?php echo date("Y"); ?> VirtualSim &bull; Todos os direitos reservados.</div>
            <div class="flex items-center gap-4 font-semibold text-gray-400">
                <a href="privacidade.php" class="hover:text-sky-400 transition-colors">Privacidade</a>
                <span>•</span>
                <a href="termos.php" class="text-sky-400">Termos</a>
                <span>•</span>
                <a href="suporte.php" class="hover:text-sky-400 transition-colors">Suporte</a>
            </div>
        </div>
    </footer>

</body>
</html>
