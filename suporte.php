<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
$assetVersion = time();

$feedbackMsg = "";
$feedbackType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"] ?? "");
    $email = filter_var(trim($_POST["email"] ?? ""), FILTER_VALIDATE_EMAIL);
    $assunto = trim($_POST["assunto"] ?? "Dúvida sobre o VirtualSim");
    $mensagem = trim($_POST["mensagem"] ?? "");

    if ($nome && $email && $mensagem) {
        $logData = [
            "timestamp" => date("Y-m-d H:i:s"),
            "app" => "VirtualSim",
            "nome" => $nome,
            "email" => $email,
            "assunto" => $assunto,
            "mensagem" => $mensagem,
            "ip" => $_SERVER["REMOTE_ADDR"] ?? "127.0.0.1"
        ];

        $uploadsDir = __DIR__ . "/uploads";
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        $logFile = $uploadsDir . "/messages_log.json";
        $currentLogs = [];
        if (file_exists($logFile)) {
            $currentLogs = json_decode(file_get_contents($logFile), true) ?: [];
        }
        $currentLogs[] = $logData;
        file_put_contents($logFile, json_encode($currentLogs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Envio de E-mail via remetente oficial do próprio domínio
        $to = "contato@4u.ia.br";
        $subject = "VirtualSim Suporte: " . $assunto;
        $body = "Nova mensagem enviada pela Central de Suporte VirtualSim:\n\n" .
                "Nome: $nome\n" .
                "E-mail: $email\n" .
                "Assunto: $assunto\n\n" .
                "Mensagem:\n$mensagem\n\n" .
                "Data: " . date("d/m/Y H:i:s") . "\n" .
                "IP: " . ($logData["ip"]);

        $headers = "From: contato@4u.ia.br\r\n" .
                   "Reply-To: $email\r\n" .
                   "X-Mailer: PHP/" . phpversion();

        @mail($to, $subject, $body, $headers);

        $feedbackMsg = "Sua mensagem foi enviada com sucesso! Nossa equipe responderá em breve no seu e-mail.";
        $feedbackType = "success";
    } else {
        $feedbackMsg = "Por favor, preencha todos os campos corretamente.";
        $feedbackType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Central de Suporte &amp; FAQ — VirtualSim</title>
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
    <main class="max-w-4xl mx-auto px-6 py-12 flex-1 w-full space-y-12">
        <div class="text-center max-w-xl mx-auto">
            <h1 class="text-3xl font-extrabold text-white mb-2">Central de Suporte &amp; Ajuda</h1>
            <p class="text-sm text-gray-400">Tire suas dúvidas ou entre em contato direto com nosso atendimento técnico.</p>
        </div>

        <!-- FAQ Section -->
        <section class="bg-gray-900/60 border border-gray-800 p-8 rounded-2xl shadow-2xl space-y-6">
            <h2 class="text-xl font-bold text-white mb-4">Perguntas Frequentes (FAQ)</h2>

            <div class="space-y-4">
                <div class="border-b border-gray-800 pb-3">
                    <h3 class="font-bold text-white text-sm">O que acontece se o SMS não chegar no meu número virtual?</h3>
                    <p class="text-xs text-gray-400 mt-1">Se o código SMS não for entregue dentro do tempo estipulado, o pedido é cancelado automaticamente e o saldo correspondente é 100% devolvido para a sua conta no VirtualSim.</p>
                </div>

                <div class="border-b border-gray-800 pb-3">
                    <h3 class="font-bold text-white text-sm">Como funciona a recarga de saldo via PIX?</h3>
                    <p class="text-xs text-gray-400 mt-1">A recarga é efetuada através da integração oficial com o Mercado Pago. O QR Code PIX é gerado na hora e o saldo é creditado em segundos na sua conta assim que o pagamento for confirmado.</p>
                </div>

                <div class="border-b border-gray-800 pb-3">
                    <h3 class="font-bold text-white text-sm">Os números virtuais são reutilizados?</h3>
                    <p class="text-xs text-gray-400 mt-1">Cada número de ativação SMS temporária é destinado exclusivamente ao serviço contratado durante o período da ordem.</p>
                </div>
            </div>
        </section>

        <!-- Contact Form -->
        <section class="bg-gray-900/60 border border-gray-800 p-8 rounded-2xl shadow-2xl space-y-6">
            <h2 class="text-xl font-bold text-white mb-2">Formulário de Contato</h2>
            <p class="text-xs text-gray-400 mb-4">Preencha os campos abaixo para enviar uma mensagem diretamente para nossa equipe técnica.</p>

            <?php if ($feedbackMsg): ?>
                <div class="p-4 rounded-xl text-xs font-semibold <?php echo $feedbackType === 'success' ? 'bg-emerald-950/80 text-emerald-300 border border-emerald-700' : 'bg-red-950/80 text-red-300 border border-red-700'; ?>">
                    <?php echo htmlspecialchars($feedbackMsg); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="suporte.php" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-300 mb-1">Seu Nome *</label>
                    <input type="text" name="nome" required placeholder="Digite seu nome completo" class="w-full bg-gray-950 border border-gray-800 rounded-xl p-3 text-xs text-white focus:border-sky-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-300 mb-1">Seu E-mail *</label>
                    <input type="email" name="email" required placeholder="seuemail@exemplo.com" class="w-full bg-gray-950 border border-gray-800 rounded-xl p-3 text-xs text-white focus:border-sky-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-300 mb-1">Assunto</label>
                    <input type="text" name="assunto" placeholder="Ex: Dúvida sobre recarga PIX" class="w-full bg-gray-950 border border-gray-800 rounded-xl p-3 text-xs text-white focus:border-sky-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-300 mb-1">Mensagem *</label>
                    <textarea name="mensagem" rows="5" required placeholder="Escreva detalhadamente sua dúvida ou solicitação..." class="w-full bg-gray-950 border border-gray-800 rounded-xl p-3 text-xs text-white focus:border-sky-500 focus:outline-none"></textarea>
                </div>

                <button type="submit" class="w-full py-3 bg-gradient-to-r from-sky-600 to-blue-600 hover:from-sky-500 hover:to-blue-500 text-white font-bold rounded-xl text-xs shadow-lg transition-all">
                    Enviar Mensagem
                </button>
            </form>
        </section>
    </main>

    <!-- Institutional Footer -->
    <footer class="bg-gray-900 border-t border-gray-800 py-6 px-6 text-center text-xs text-gray-500">
        <div class="max-w-4xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <div>&copy; <?php echo date("Y"); ?> VirtualSim &bull; Todos os direitos reservados.</div>
            <div class="flex items-center gap-4 font-semibold text-gray-400">
                <a href="privacidade.php" class="hover:text-sky-400 transition-colors">Privacidade</a>
                <span>•</span>
                <a href="termos.php" class="hover:text-sky-400 transition-colors">Termos</a>
                <span>•</span>
                <a href="suporte.php" class="text-sky-400">Suporte</a>
            </div>
        </div>
    </footer>

</body>
</html>
