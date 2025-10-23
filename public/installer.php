<?php
declare(strict_types=1);

session_start();
set_time_limit(0);

$basePath = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$envPath = $basePath . '/.env';
$envExamplePath = $basePath . '/.env.example';
$lockFile = $basePath . '/storage/installer.lock';
$lockFileExists = is_file($lockFile);

$envExisting = loadEnvValues($envPath);
$envExample = loadEnvValues($envExamplePath);
$currentEnvValues = array_merge($envExample, $envExisting);
$envFileExists = is_file($envPath);
$initialState = createInitialInstallerState($currentEnvValues, $envFileExists);

if (!isset($_SESSION['installer_state']) || !is_array($_SESSION['installer_state'])) {
    $_SESSION['installer_state'] = $initialState;
} else {
    $_SESSION['installer_state'] = array_replace($initialState, $_SESSION['installer_state']);
}

$state = &$_SESSION['installer_state'];
$state['env_saved'] = $envFileExists;
$state['env_values'] = array_merge($state['env_values'] ?? [], $currentEnvValues);

if (!$lockFileExists && ($state['install_done'] ?? false)) {
    $_SESSION['installer_state'] = $initialState;
    $state = &$_SESSION['installer_state'];
}

$flashMessages = $_SESSION['installer_flash'] ?? [];
unset($_SESSION['installer_flash']);

$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;
$step = max(1, min(4, $step));

$requirements = evaluateRequirements($basePath);
$allRequirementsMet = array_reduce($requirements, static function (bool $carry, array $item): bool {
    return $carry && $item['passed'];
}, true);

if ($lockFileExists && !($state['install_done'] ?? false)) {
    $step = 0;
} elseif ($step > 1 && !$allRequirementsMet) {
    addFlash('error', 'Verifica prima i requisiti prima di procedere con l\'installazione.');
    redirectToStep(1);
}
if (!$lockFileExists && $step > 2 && !$state['env_saved'] && !is_file($envPath)) {
    addFlash('error', 'Configura e salva il file .env prima di proseguire.');
    redirectToStep(2);
}
if (!$lockFileExists && $step > 3 && !$state['install_done']) {
    addFlash('error', 'Completa l\'installazione automatica prima di proseguire.');
    redirectToStep(3);
}

$formValues = $state['env_values'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($lockFileExists && !($state['install_done'] ?? false)) {
        addFlash('error', 'Installazione gia\' completata. Rimuovi il file di lock per rieseguire l\'installer.');
        redirectToStep(0);
    }

    if ($action === 'save_env') {
        $submitted = $_POST['env'] ?? [];
        $sanitized = [];
        foreach ($submitted as $key => $value) {
            $sanitized[$key] = is_string($value) ? trim($value) : '';
        }
        $sanitized['APP_ENV'] = 'production';
        $sanitized['APP_DEBUG'] = 'false';

        $validation = validateEnvInput($sanitized);

        if (!$validation['valid']) {
            foreach ($validation['errors'] as $error) {
                addFlash('error', $error);
            }
            $formValues = array_merge($formValues, $sanitized);
            $step = 2;
        } else {
            $writeSuccess = writeEnvFile($envPath, $sanitized, $envExamplePath);
            if ($writeSuccess) {
                $state['env_saved'] = true;
                $updatedEnv = loadEnvValues($envPath);
                $state['env_values'] = array_merge($envExample, $updatedEnv, $sanitized);
                addFlash('success', 'File .env aggiornato con successo.');
                redirectToStep(3);
            }

            addFlash('error', 'Impossibile scrivere il file .env. Verifica i permessi.');
            $formValues = array_merge($formValues, $sanitized);
            $step = 2;
        }
    }

    if ($action === 'run_install') {
        if (!$allRequirementsMet) {
            addFlash('error', 'Verifica i requisiti prima di eseguire l\'installazione.');
            redirectToStep(1);
        }
        if (!$state['env_saved']) {
            addFlash('error', 'Configura e salva il file .env prima di eseguire l\'installazione.');
            redirectToStep(2);
        }

        $productionEnvs = [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
        ];
        $state['env_values'] = array_merge($state['env_values'], $productionEnvs);
        writeEnvFile($envPath, $productionEnvs, $envExamplePath);

        $sequenceResult = runInstallationSequence($basePath, $envPath, $envExamplePath, $state['env_values']);
        $state['install_log'] = $sequenceResult['log'];
        $state['install_done'] = $sequenceResult['success'];
        $state['install_exit_code'] = $sequenceResult['success'] ? 0 : 1;

        if ($sequenceResult['success']) {
            addFlash('success', 'Installazione completata con successo.');
            if (ensureLockFile($lockFile)) {
                addFlash('success', 'Il file di lock e\' stato creato in storage/installer.lock.');
            } else {
                addFlash('warning', 'Installazione riuscita, ma non e\' stato possibile creare il file di lock.');
            }
            redirectToStep(4);
        }

        addFlash('error', 'Installazione interrotta. Controlla i log dei comandi eseguiti.');
        redirectToStep(3);
    }
}

$stepLabels = [
    1 => 'Requisiti',
    2 => 'Configura .env',
    3 => 'Installa dipendenze',
    4 => 'Finalizza',
];
$lockoutLabel = 'Installazione bloccata';

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Installer | SpenderLock</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
<header class="bg-slate-900 py-6 text-white">
    <div class="mx-auto flex max-w-5xl items-center justify-between px-4">
        <h1 class="text-2xl font-semibold">Installer SpenderLock</h1>
        <span class="text-xs uppercase tracking-widest text-slate-400">Setup guidato</span>
    </div>
</header>
<main class="mx-auto max-w-5xl px-4 py-10 space-y-6">
    <?php if (!$lockFileExists || ($state['install_done'] ?? false)): ?>
        <section class="grid gap-3 sm:grid-cols-4">
            <?php foreach ($stepLabels as $index => $label): ?>
                <?php
                $stepClasses = 'rounded-xl border px-4 py-3 text-center text-sm font-semibold transition-all';
                $isCompleted = ($index === 2 && $state['env_saved'])
                    || ($index === 3 && $state['install_done'])
                    || ($index === 4 && $state['install_done']);
                $stepStatus = $step === $index
                    ? 'border-blue-600 bg-blue-600 text-white shadow-lg shadow-blue-500/20'
                    : (($step > $index || $isCompleted)
                        ? 'border-emerald-500 bg-emerald-500 text-white shadow-lg shadow-emerald-400/30'
                        : 'border-slate-300 bg-white text-slate-600');
                ?>
                <div class="<?php echo $stepClasses . ' ' . $stepStatus; ?>">
                    <div class="text-xs font-normal uppercase tracking-wide text-slate-200">
                        <?php echo htmlspecialchars('Step ' . $index, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <div class="text-base font-semibold">
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php
    $flashPalette = [
        'success' => 'border-emerald-600 bg-emerald-50 text-emerald-800',
        'error' => 'border-rose-600 bg-rose-50 text-rose-800',
        'warning' => 'border-amber-500 bg-amber-50 text-amber-800',
    ];
    ?>
    <?php foreach ($flashMessages as $message): ?>
        <?php
        $flashClass = $flashPalette[$message['type']] ?? 'border-slate-500 bg-slate-50 text-slate-700';
        ?>
        <div class="flex items-start gap-3 rounded-lg border-l-4 px-4 py-3 shadow-sm <?php echo $flashClass; ?>">
            <span class="mt-1 text-lg">
                <?php echo $message['type'] === 'success' ? '&#10003;' : ($message['type'] === 'error' ? '&#9888;' : '&#8505;'); ?>
            </span>
            <p class="text-sm font-medium">
                <?php echo htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </div>
    <?php endforeach; ?>

    <?php if ($lockFileExists && !($state['install_done'] ?? false)): ?>
        <section class="rounded-2xl border border-rose-200 bg-rose-50 p-6 shadow-lg shadow-rose-200/40">
            <div class="flex items-start gap-4">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-rose-600 text-lg font-semibold text-white">
                    &#9888;
                </span>
                <div class="space-y-2">
                    <h2 class="text-xl font-semibold text-rose-700">Installazione gia' completata</h2>
                    <p class="text-sm text-rose-600">
                        E' stato rilevato il file <code>storage/installer.lock</code>. Per rieseguire l'installer rimuovi manualmente il file di lock.
                    </p>
                    <p class="text-sm text-rose-600">
                        Se l'installazione e' stata completata correttamente, puoi eliminare <code>public/installer.php</code> dal server.
                    </p>
                </div>
            </div>
        </section>
        <footer class="pb-10 text-center text-xs text-slate-500">
            Installer bloccato. Rimuovi il file di lock solo se devi reinstallare.
        </footer>
    </main>
</body>
</html>
<?php
    exit;
endif;
?>
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/40">
        <?php if ($step === 1): ?>
            <header class="space-y-1">
                <h2 class="text-xl font-semibold text-slate-900">1. Verifica requisiti</h2>
                <p class="text-sm text-slate-600">
                    Assicurati che il server soddisfi i requisiti minimi per installare l'applicazione.
                </p>
            </header>
            <ul class="mt-6 divide-y divide-slate-200">
                <?php foreach ($requirements as $requirement): ?>
                    <li class="flex items-center justify-between gap-4 py-4 text-sm">
                        <div>
                            <p class="font-semibold text-slate-800">
                                <?php echo htmlspecialchars($requirement['label'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                            <p class="text-xs text-slate-500">
                                <?php echo htmlspecialchars($requirement['details'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full text-base font-semibold <?php echo $requirement['passed'] ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-600'; ?>">
                            <?php echo $requirement['passed'] ? '&#10003;' : '&#10007;'; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="mt-8 flex flex-wrap gap-3">
                <?php if ($allRequirementsMet): ?>
                    <a class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-6 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                       href="installer.php?step=2">
                        Prosegui
                    </a>
                <?php else: ?>
                    <p class="text-sm font-semibold text-rose-600">
                        Risolvi i requisiti mancanti per continuare.
                    </p>
                <?php endif; ?>
            </div>

        <?php elseif ($step === 2): ?>
            <header class="space-y-1">
                <h2 class="text-xl font-semibold text-slate-900">2. Configura il file .env</h2>
                <p class="text-sm text-slate-600">
                    Inserisci i parametri di configurazione principali. Verranno aggiornati direttamente nel file <code>.env</code>.
                </p>
            </header>
            <form method="post" action="installer.php?step=2" class="mt-6 space-y-6">
                <input type="hidden" name="action" value="save_env">
                <div class="grid gap-5">
                    <?php foreach (getEnvFieldGroups() as $groupLabel => $fields): ?>
                        <fieldset class="rounded-xl border border-slate-200 bg-slate-50/60 p-5">
                            <legend class="px-2 text-sm font-semibold uppercase tracking-wide text-slate-500">
                                <?php echo htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </legend>
                            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                <?php foreach ($fields as $key => $meta): ?>
                                    <?php
                                    $value = $formValues[$key] ?? $meta['default'];
                                    $requiredAttr = $meta['required'] ? 'required' : '';
                                    $hasOptions = isset($meta['options']) && is_array($meta['options']) && !empty($meta['options']);
                                    ?>
                                    <label class="flex flex-col gap-1 text-sm font-medium text-slate-700" for="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">
                                        <span>
                                            <?php echo htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8'); ?>
                                            <?php if ($meta['required']): ?>
                                                <span class="text-rose-600">*</span>
                                            <?php endif; ?>
                                        </span>
                                        <?php if ($hasOptions): ?>
                                            <select
                                                id="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"
                                                name="env[<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>]"
                                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/60"
                                                <?php echo $requiredAttr; ?>
                                            >
                                                <?php foreach ($meta['options'] as $optionValue => $optionLabel): ?>
                                                    <option value="<?php echo htmlspecialchars((string) $optionValue, ENT_QUOTES, 'UTF-8'); ?>"
                                                        <?php echo ((string) $value === (string) $optionValue) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <input
                                                type="<?php echo htmlspecialchars($meta['type'], ENT_QUOTES, 'UTF-8'); ?>"
                                                id="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"
                                                name="env[<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>]"
                                                value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"
                                                placeholder="<?php echo htmlspecialchars($meta['placeholder'], ENT_QUOTES, 'UTF-8'); ?>"
                                                <?php echo $requiredAttr; ?>
                                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/60"
                                            >
                                        <?php endif; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                    <?php endforeach; ?>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-6 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Salva .env
                    </button>
                    <a class="inline-flex items-center justify-center rounded-lg bg-slate-200 px-6 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
                       href="installer.php?step=1">
                        Indietro
                    </a>
                </div>
            </form>

        <?php elseif ($step === 3): ?>
            <header class="space-y-1">
                <h2 class="text-xl font-semibold text-slate-900">3. Installa dipendenze e prepara il sistema</h2>
                <p class="text-sm text-slate-600">
                    Questo step esegue automaticamente i comandi necessari (composer, npm, artisan) e prepara i database SQLite.
                </p>
            </header>
            <?php $sequencePreview = buildInstallationSequence($basePath, $envPath, $envExamplePath, $state['env_values']); ?>
            <div class="mt-6 space-y-3">
                <p class="text-sm font-semibold text-slate-700">Sequenza automatica (eseguita dallo script):</p>
                <ol class="space-y-2 text-sm text-slate-700">
                    <?php foreach ($sequencePreview as $item): ?>
                        <li class="flex items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 shadow-sm">
                            <span class="mt-0.5 text-xs font-semibold text-slate-500">#</span>
                            <span><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
            <form method="post" action="installer.php?step=3" class="mt-6 flex flex-wrap gap-3">
                <input type="hidden" name="action" value="run_install">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-6 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Avvia installazione automatica
                </button>
                <a class="inline-flex items-center justify-center rounded-lg bg-slate-200 px-6 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
                   href="installer.php?step=2">
                    Indietro
                </a>
            </form>
            <?php
            $installLogHtml = buildInstallLogHtml($state['install_log']);
            if ($installLogHtml !== '') {
                echo $installLogHtml;
            }
            ?>

        <?php elseif ($step === 4): ?>
            <header class="space-y-1">
                <h2 class="text-xl font-semibold text-slate-900">4. Finalizza</h2>
                <p class="text-sm text-slate-600">
                    L'installazione e\' stata completata. Di seguito trovi il riepilogo dei comandi eseguiti.
                </p>
            </header>
            <?php
            $installLogHtml = buildInstallLogHtml($state['install_log']);
            if ($installLogHtml !== '') {
                echo $installLogHtml;
            }
            ?>
            <div class="mt-8 flex items-start gap-3 rounded-lg border-l-4 border-emerald-600 bg-emerald-50 px-4 py-3 text-emerald-800 shadow-sm">
                <span class="mt-1 text-lg">&#10003;</span>
                <div class="space-y-2 text-sm font-medium">
                    <p>L'applicazione e\' pronta. Ricorda di rimuovere <code>public/installer.php</code> o di limitarne l'accesso.</p>
                    <p>Se necessario, configura il virtual host del web server puntando alla directory <code>public/</code>.</p>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <footer class="pb-10 text-center text-xs text-slate-500">
        Installer PHP autogenerato per SpenderLock. Per sicurezza rimuovi questo file al termine.
    </footer>
</main>
</body>
</html>
<?php

function addFlash(string $type, string $message): void
{
    if (!isset($_SESSION['installer_flash'])) {
        $_SESSION['installer_flash'] = [];
    }
    $_SESSION['installer_flash'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function redirectToStep(int $step): void
{
    header('Location: installer.php?step=' . $step);
    exit;
}

function createInitialInstallerState(array $envValues, bool $envFileExists): array
{
    $envValues = array_merge([
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
    ], $envValues);

    return [
        'env_saved' => $envFileExists,
        'env_values' => $envValues,
        'install_done' => false,
        'install_log' => [],
        'install_exit_code' => null,
    ];
}

function evaluateRequirements(string $basePath): array
{
    $requirements = [];

    $phpRequired = '8.2';
    $phpVersion = PHP_VERSION;
    $requirements[] = [
        'label' => "PHP >= {$phpRequired}",
        'details' => "Versione rilevata: {$phpVersion}",
        'passed' => version_compare($phpVersion, $phpRequired, '>='),
    ];

    $phpExtensions = [
        'bcmath',
        'ctype',
        'fileinfo',
        'json',
        'mbstring',
        'openssl',
        'pdo',
        'pdo_sqlite',
        'sqlite3',
        'tokenizer',
        'xml',
    ];
    $missingExtensions = array_filter($phpExtensions, static function (string $extension): bool {
        return !extension_loaded($extension);
    });
    $requirements[] = [
        'label' => 'Estensioni PHP richieste',
        'details' => empty($missingExtensions)
            ? 'Tutte le estensioni necessarie sono abilitate.'
            : 'Mancano: ' . implode(', ', $missingExtensions),
        'passed' => empty($missingExtensions),
    ];

    $writableDirectories = [
        $basePath . '/storage' => 'storage',
        $basePath . '/bootstrap/cache' => 'bootstrap/cache',
    ];
    foreach ($writableDirectories as $path => $label) {
        $requirements[] = [
            'label' => "Permessi di scrittura su {$label}",
            'details' => is_dir($path) ? $path : "{$path} (directory non trovata)",
            'passed' => is_dir($path) && is_writable($path),
        ];
    }

    $composerInfo = commandVersion('composer');
    $requirements[] = [
        'label' => 'Composer CLI',
        'details' => $composerInfo['available']
            ? sprintf('%s (versione: %s)', $composerInfo['path'], $composerInfo['version'] ?? 'sconosciuta')
            : 'Non trovato nel PATH',
        'passed' => $composerInfo['available'],
    ];

    $nodeInfo = commandVersion('node', '--version');
    $nodeRequired = '18.0.0';
    $requirements[] = [
        'label' => "Node.js >= {$nodeRequired}",
        'details' => $nodeInfo['available']
            ? sprintf('%s (versione: %s)', $nodeInfo['path'], $nodeInfo['version'] ?? 'sconosciuta')
            : 'Non trovato nel PATH',
        'passed' => $nodeInfo['available'] && version_compare($nodeInfo['version'] ?? '0.0.0', $nodeRequired, '>='),
    ];

    $npmInfo = commandVersion('npm', '--version');
    $npmRequired = '9.0.0';
    $requirements[] = [
        'label' => "npm >= {$npmRequired}",
        'details' => $npmInfo['available']
            ? sprintf('%s (versione: %s)', $npmInfo['path'], $npmInfo['version'] ?? 'sconosciuta')
            : 'Non trovato nel PATH',
        'passed' => $npmInfo['available'] && version_compare($npmInfo['version'] ?? '0.0.0', $npmRequired, '>='),
    ];

    return $requirements;
}

function commandVersion(string $command, string $flag = '--version'): array
{
    $escaped = escapeshellcmd($command);
    $whichCmd = 'command -v ' . $escaped . ' 2>/dev/null';
    $path = trim((string) shell_exec($whichCmd));

    if ($path === '') {
        return [
            'available' => false,
            'path' => null,
            'version' => null,
            'raw' => '',
            'details' => 'Non trovato nel PATH',
        ];
    }

    $versionOutput = trim((string) shell_exec("{$escaped} {$flag} 2>&1"));
    if ($versionOutput === '') {
        $versionOutput = 'Comando disponibile';
    }

    return [
        'available' => true,
        'path' => $path,
        'version' => extractVersionFromString($versionOutput),
        'raw' => $versionOutput,
        'details' => "{$path} ({$versionOutput})",
    ];
}

function extractVersionFromString(string $output): ?string
{
    if ($output === '') {
        return null;
    }
    if (preg_match('/\d+\.\d+(?:\.\d+)?/', $output, $matches)) {
        return $matches[0];
    }
    return null;
}

function validateEnvInput(array $values): array
{
    $errors = [];

    $requiredKeys = [
        'APP_NAME',
        'APP_URL',
        'MAIL_MAILER',
        'MAIL_HOST',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
        'DEFAULT_TENANT_NAME',
        'DEFAULT_TENANT_DOMAIN',
        'DEFAULT_ADMIN_NAME',
        'DEFAULT_ADMIN_EMAIL',
        'DEFAULT_ADMIN_PASSWORD',
    ];

    foreach ($requiredKeys as $key) {
        if (!isset($values[$key]) || $values[$key] === '') {
            $errors[] = "Il campo {$key} e' obbligatorio.";
        }
    }

    if (!empty($values['APP_URL']) && !filter_var($values['APP_URL'], FILTER_VALIDATE_URL)) {
        $errors[] = 'APP_URL deve essere un URL valido.';
    }

    if (!empty($values['DEFAULT_ADMIN_EMAIL']) && !filter_var($values['DEFAULT_ADMIN_EMAIL'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'DEFAULT_ADMIN_EMAIL deve essere una e-mail valida.';
    }

    if (!empty($values['MAIL_PORT']) && !ctype_digit((string) $values['MAIL_PORT'])) {
        $errors[] = 'MAIL_PORT deve essere numerico.';
    }

    $fieldDefinitions = getEnvFieldGroups();
    foreach ($fieldDefinitions as $fields) {
        foreach ($fields as $key => $meta) {
            if (!isset($values[$key])) {
                continue;
            }
            if (isset($meta['options']) && is_array($meta['options']) && !array_key_exists($values[$key], $meta['options'])) {
                $errors[] = "Il campo {$key} contiene un valore non valido.";
            }
        }
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors,
    ];
}

function loadEnvValues(string $path): array
{
    if (!is_file($path) || !is_readable($path)) {
        return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return [];
    }

    $values = [];
    foreach ($lines as $line) {
        if ($line === '' || $line === false) {
            continue;
        }

        $trimmed = ltrim($line);
        if ($trimmed === '' || $trimmed[0] === '#') {
            continue;
        }

        $position = strpos($line, '=');
        if ($position === false) {
            continue;
        }

        $key = trim(substr($line, 0, $position));
        $rawValue = substr($line, $position + 1);
        $values[$key] = decodeEnvValue($rawValue);
    }

    return $values;
}

function decodeEnvValue(string $value): string
{
    $value = trim($value);
    $length = strlen($value);

    if ($length >= 2 && $value[0] === '"' && $value[$length - 1] === '"') {
        $inner = substr($value, 1, $length - 2);
        return stripcslashes($inner);
    }

    if ($length >= 2 && $value[0] === "'" && $value[$length - 1] === "'") {
        return substr($value, 1, $length - 2);
    }

    return $value;
}

function writeEnvFile(string $path, array $updates, ?string $templatePath = null): bool
{
    $lines = [];

    if (is_file($path) && is_readable($path)) {
        $fileLines = file($path, FILE_IGNORE_NEW_LINES);
        if ($fileLines !== false) {
            $lines = $fileLines;
        }
    } elseif ($templatePath !== null && is_file($templatePath) && is_readable($templatePath)) {
        $templateLines = file($templatePath, FILE_IGNORE_NEW_LINES);
        if ($templateLines !== false) {
            $lines = $templateLines;
        }
    }

    $indexByKey = [];
    foreach ($lines as $index => $line) {
        $trimmed = ltrim($line);
        if ($trimmed === '' || $trimmed[0] === '#') {
            continue;
        }

        $position = strpos($line, '=');
        if ($position === false) {
            continue;
        }

        $key = trim(substr($line, 0, $position));
        if ($key !== '') {
            $indexByKey[$key] = $index;
        }
    }

    foreach ($updates as $key => $value) {
        $formatted = formatEnvValue($value);
        if (array_key_exists($key, $indexByKey)) {
            $lines[$indexByKey[$key]] = $key . '=' . $formatted;
        } else {
            $lines[] = $key . '=' . $formatted;
        }
    }

    $content = implode(PHP_EOL, $lines);
    if ($content === '' || substr($content, -1) !== PHP_EOL) {
        $content .= PHP_EOL;
    }

    $directory = dirname($path);
    if (!is_dir($directory)) {
        return false;
    }

    if (file_put_contents($path, $content, LOCK_EX) === false) {
        return false;
    }

    return true;
}

function formatEnvValue(string $value): string
{
    $normalized = trim($value);

    $specialLiterals = ['true', 'false', 'null', 'NULL', 'TRUE', 'FALSE'];
    if ($normalized === '' && $value === '') {
        return '""';
    }

    if (in_array($normalized, $specialLiterals, true)) {
        return $normalized;
    }

    if (preg_match('/\s|#|=|\"|\'/', $value)) {
        $escaped = str_replace('"', '\"', $value);
        return '"' . $escaped . '"';
    }

    return $value;
}

function runCommand(string $command, string $cwd): array
{
    $commandWithExit = 'cd ' . escapeshellarg($cwd) . ' && ' . $command . ' 2>&1; echo "\n__EXIT_CODE:$?"';
    $rawOutput = shell_exec($commandWithExit);

    if ($rawOutput === null) {
        return [
            'exitCode' => 1,
            'output' => 'shell_exec non ha restituito output. Verifica i permessi e l\'ambiente di esecuzione.',
        ];
    }

    $exitCode = null;
    if (preg_match('/__EXIT_CODE:(\d+)/', $rawOutput, $matches)) {
        $exitCode = (int) $matches[1];
        $rawOutput = str_replace($matches[0], '', $rawOutput);
    }

    $output = trim($rawOutput);

    return [
        'exitCode' => $exitCode ?? 1,
        'output' => $output,
    ];
}

function buildInstallationSequence(string $basePath, string $envPath, string $envExamplePath, array $envValues): array
{
    $tenantName = $envValues['DEFAULT_TENANT_NAME'] ?? 'La Tua Azienda';
    $tenantDomain = $envValues['DEFAULT_TENANT_DOMAIN'] ?? 'localhost';
    $adminName = $envValues['DEFAULT_ADMIN_NAME'] ?? 'Administrator';
    $adminEmail = $envValues['DEFAULT_ADMIN_EMAIL'] ?? 'admin@localhost';
    $adminPassword = $envValues['DEFAULT_ADMIN_PASSWORD'] ?? 'password123';

    $tenantCommand = 'php artisan tenants:setup-default'
        . ' --tenant-name=' . escapeshellarg($tenantName)
        . ' --tenant-domain=' . escapeshellarg($tenantDomain)
        . ' --admin-name=' . escapeshellarg($adminName)
        . ' --admin-email=' . escapeshellarg($adminEmail)
        . ' --admin-password=' . escapeshellarg($adminPassword);

    return [
        [
            'label' => '1) composer install --no-interaction --prefer-dist',
            'type' => 'command',
            'command' => 'composer install --no-interaction --prefer-dist',
        ],
        [
            'label' => '2) npm install',
            'type' => 'command',
            'command' => 'npm install',
        ],
        [
            'label' => '3) npm run build',
            'type' => 'command',
            'command' => 'npm run build',
        ],
        [
            'label' => '4) Imposta permessi su storage e bootstrap/cache',
            'type' => 'callable',
            'handler' => function () use ($basePath): array {
                return applyDirectoryPermissions([
                    $basePath . '/storage',
                    $basePath . '/bootstrap/cache',
                ]);
            },
        ],
        [
            'label' => '5) Copia .env.example in .env (se necessario)',
            'type' => 'callable',
            'handler' => function () use ($envExamplePath, $envPath): array {
                return ensureEnvFilePresence($envExamplePath, $envPath);
            },
        ],
        [
            'label' => '6) php artisan key:generate',
            'type' => 'command',
            'command' => 'php artisan key:generate --force',
        ],
        [
            'label' => '7) touch database/landlord.sqlite',
            'type' => 'callable',
            'handler' => function () use ($basePath): array {
                return ensureSqliteDatabase($basePath . '/database/landlord.sqlite');
            },
        ],
        [
            'label' => '8) touch database/database.sqlite',
            'type' => 'callable',
            'handler' => function () use ($basePath): array {
                return ensureSqliteDatabase($basePath . '/database/database.sqlite');
            },
        ],
        [
            'label' => '9) php artisan migrate --force',
            'type' => 'command',
            'command' => 'php artisan migrate --force',
        ],
        [
            'label' => '10) php artisan migrate --database=landlord --path=database/migrations/landlord',
            'type' => 'command',
            'command' => 'php artisan migrate --database=landlord --path=database/migrations/landlord --force',
        ],
        [
            'label' => '11) php artisan tenants:setup-default',
            'type' => 'command',
            'command' => $tenantCommand,
        ],
    ];
}

function runInstallationSequence(string $basePath, string $envPath, string $envExamplePath, array $envValues): array
{
    $steps = buildInstallationSequence($basePath, $envPath, $envExamplePath, $envValues);
    $log = [];
    $success = true;

    foreach ($steps as $index => $step) {
        $result = executeInstallationStep($step, $basePath);
        $log[] = [
            'label' => $step['label'],
            'success' => $result['success'],
            'output' => $result['output'],
            'exitCode' => $result['exitCode'] ?? null,
            'skipped' => $result['skipped'] ?? false,
        ];

        if (!$result['success']) {
            $success = false;
            $remaining = array_slice($steps, $index + 1);
            foreach ($remaining as $remainingStep) {
                $log[] = [
                    'label' => $remainingStep['label'],
                    'success' => false,
                    'output' => 'Non eseguito a causa di un errore precedente.',
                    'exitCode' => null,
                    'skipped' => true,
                ];
            }
            break;
        }
    }

    return [
        'success' => $success,
        'log' => $log,
    ];
}

function executeInstallationStep(array $step, string $basePath): array
{
    if ($step['type'] === 'command') {
        $result = runCommand($step['command'], $basePath);
        $output = $result['output'] !== '' ? $result['output'] : 'Comando eseguito.';
        return [
            'success' => $result['exitCode'] === 0,
            'output' => $output,
            'exitCode' => $result['exitCode'],
        ];
    }

    if ($step['type'] === 'callable') {
        $handler = $step['handler'];
        $callResult = $handler();
        return [
            'success' => $callResult['success'],
            'output' => $callResult['output'] ?? '',
        ];
    }

    return [
        'success' => false,
        'output' => 'Tipo di step non valido.',
    ];
}

function ensureEnvFilePresence(string $examplePath, string $envPath): array
{
    if (is_file($envPath)) {
        return [
            'success' => true,
            'output' => '.env presente; nessuna copia necessaria.',
        ];
    }

    if (!is_file($examplePath) || !is_readable($examplePath)) {
        return [
            'success' => false,
            'output' => '.env.example non trovato o non leggibile.',
        ];
    }

    if (@copy($examplePath, $envPath)) {
        return [
            'success' => true,
            'output' => '.env.example copiato in .env.',
        ];
    }

    return [
        'success' => false,
        'output' => 'Copia di .env.example in .env non riuscita.',
    ];
}

function ensureSqliteDatabase(string $filePath): array
{
    $directory = dirname($filePath);
    if (!is_dir($directory) && !@mkdir($directory, 0775, true)) {
        return [
            'success' => false,
            'output' => "Impossibile creare la directory: {$directory}",
        ];
    }

    if (is_file($filePath)) {
        return [
            'success' => true,
            'output' => "{$filePath} gia' presente.",
        ];
    }

    if (@touch($filePath)) {
        return [
            'success' => true,
            'output' => "{$filePath} creato.",
        ];
    }

    return [
        'success' => false,
        'output' => "Impossibile creare il file: {$filePath}",
    ];
}

function applyDirectoryPermissions(array $paths, int $mode = 0775): array
{
    $messages = [];
    $errors = [];

    if (DIRECTORY_SEPARATOR === '\\') {
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                $errors[] = "Directory non trovata: {$path}";
            }
        }
        $messages[] = 'Sistema Windows rilevato; verifica manualmente i permessi di scrittura.';

        return [
            'success' => empty($errors),
            'output' => implode("\n", array_merge($errors, $messages)),
        ];
    }

    foreach ($paths as $path) {
        if (!is_dir($path)) {
            $errors[] = "Directory non trovata: {$path}";
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $itemPath = $item->getPathname();
            if (!@chmod($itemPath, $mode)) {
                $errors[] = "Impossibile impostare i permessi su: {$itemPath}";
            }
        }

        if (!@chmod($path, $mode)) {
            $errors[] = "Impossibile impostare i permessi sulla directory: {$path}";
        }

        $messages[] = "Permessi impostati su {$path}.";
    }

    return [
        'success' => empty($errors),
        'output' => implode("\n", empty($errors) ? $messages : array_merge($errors, $messages)),
    ];
}

function buildInstallLogHtml(array $log): string
{
    if (empty($log)) {
        return '';
    }

    ob_start();
    ?>
    <div class="mt-8 space-y-4">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Esito comandi</h3>
        <ol class="space-y-4">
            <?php foreach ($log as $item): ?>
                <?php
                $isSkipped = $item['skipped'] ?? false;
                $statusClass = $item['success']
                    ? 'border-emerald-200 bg-emerald-50/80 text-emerald-800'
                    : ($isSkipped ? 'border-slate-200 bg-slate-50 text-slate-500' : 'border-rose-200 bg-rose-50 text-rose-800');
                $iconBg = $item['success']
                    ? 'bg-emerald-500'
                    : ($isSkipped ? 'bg-slate-400' : 'bg-rose-500');
                $iconChar = $item['success']
                    ? '&#10003;'
                    : ($isSkipped ? '&#10145;' : '&#10007;');
                ?>
                <li class="rounded-2xl border <?php echo $statusClass; ?> p-4 shadow-sm backdrop-blur-sm">
                    <div class="flex items-start gap-4">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full text-base font-semibold text-white <?php echo $iconBg; ?>">
                            <?php echo $iconChar; ?>
                        </span>
                        <div class="flex-1 space-y-3">
                            <div class="flex flex-col gap-1">
                                <p class="text-sm font-semibold">
                                    <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                                <?php if (isset($item['exitCode']) && $item['exitCode'] !== null && !$isSkipped): ?>
                                    <span class="text-xs font-medium uppercase tracking-wide text-slate-500">
                                        Exit code: <?php echo htmlspecialchars((string) $item['exitCode'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($item['output'])): ?>
                                <div class="rounded-xl border border-slate-800/60 bg-slate-950 p-3 text-xs text-slate-100 shadow-inner">
                                    <pre class="max-h-72 overflow-y-auto whitespace-pre-wrap leading-relaxed"><?php echo htmlspecialchars($item['output'], ENT_QUOTES, 'UTF-8'); ?></pre>
                                </div>
                            <?php endif; ?>
                            <?php if ($isSkipped && empty($item['output'])): ?>
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                                    Step non eseguito a causa di un errore precedente.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
    <?php
    return (string) ob_get_clean();
}

function getEnvFieldGroups(): array
{
    return [
        'Applicazione' => [
            'APP_NAME' => [
                'label' => 'Nome applicazione',
                'placeholder' => 'SpenderLock',
                'default' => 'SpenderLock',
                'type' => 'text',
                'required' => true,
            ],
            'APP_URL' => [
                'label' => 'URL applicazione',
                'placeholder' => 'https://example.com',
                'default' => 'http://localhost',
                'type' => 'text',
                'required' => true,
            ],
        ],
        'Posta elettronica' => [
            'MAIL_MAILER' => [
                'label' => 'Mailer',
                'placeholder' => '',
                'default' => 'log',
                'type' => 'text',
                'required' => true,
                'options' => [
                    'log' => 'Log locale',
                    'smtp' => 'SMTP',
                    'sendmail' => 'Sendmail',
                    'ses' => 'Amazon SES',
                ],
            ],
            'MAIL_HOST' => [
                'label' => 'Host SMTP',
                'placeholder' => 'smtp.example.com',
                'default' => '',
                'type' => 'text',
                'required' => true,
            ],
            'MAIL_PORT' => [
                'label' => 'Porta SMTP',
                'placeholder' => '587',
                'default' => '587',
                'type' => 'number',
                'required' => false,
            ],
            'MAIL_USERNAME' => [
                'label' => 'Username SMTP',
                'placeholder' => 'user@example.com',
                'default' => '',
                'type' => 'text',
                'required' => true,
            ],
            'MAIL_PASSWORD' => [
                'label' => 'Password SMTP',
                'placeholder' => '********',
                'default' => '',
                'type' => 'password',
                'required' => true,
            ],
            'MAIL_ENCRYPTION' => [
                'label' => 'Crittografia SMTP',
                'placeholder' => '',
                'default' => 'tls',
                'type' => 'text',
                'required' => false,
                'options' => [
                    'tls' => 'TLS',
                    'ssl' => 'SSL',
                    'null' => 'Nessuna (null)',
                ],
            ],
            'MAIL_FROM_ADDRESS' => [
                'label' => 'Email mittente',
                'placeholder' => 'noreply@example.com',
                'default' => '',
                'type' => 'email',
                'required' => false,
            ],
        ],
        'Tenant & Admin' => [
            'DEFAULT_TENANT_NAME' => [
                'label' => 'Nome tenant',
                'placeholder' => 'La Tua Azienda',
                'default' => 'La Tua Azienda',
                'type' => 'text',
                'required' => true,
            ],
            'DEFAULT_TENANT_DOMAIN' => [
                'label' => 'Dominio tenant',
                'placeholder' => 'example.com',
                'default' => 'localhost',
                'type' => 'text',
                'required' => true,
            ],
            'DEFAULT_ADMIN_NAME' => [
                'label' => 'Nome amministratore',
                'placeholder' => 'Administrator',
                'default' => 'Administrator',
                'type' => 'text',
                'required' => true,
            ],
            'DEFAULT_ADMIN_EMAIL' => [
                'label' => 'Email amministratore',
                'placeholder' => 'admin@example.com',
                'default' => 'admin@localhost',
                'type' => 'email',
                'required' => true,
            ],
            'DEFAULT_ADMIN_PASSWORD' => [
                'label' => 'Password amministratore',
                'placeholder' => '********',
                'default' => '',
                'type' => 'password',
                'required' => true,
            ],
        ],
    ];
}

function ensureLockFile(string $lockFilePath): bool
{
    $directory = dirname($lockFilePath);
    if (!is_dir($directory)) {
        return false;
    }

    if (!is_writable($directory)) {
        return false;
    }

    $content = 'installed_at=' . date('c') . PHP_EOL;
    return file_put_contents($lockFilePath, $content, LOCK_EX) !== false;
}
