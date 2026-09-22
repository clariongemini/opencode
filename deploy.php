<?php

/**
 * DEPLOYER.PHP — KAMELYA Production Deploy
 * =============================================================================
 * Kullanım:
 *   composer require deployer/deployer --dev
 *   vendor/bin/dep deploy production
 *
 * Veya phar olarak:
 *   curl -o deployer.phar https://deployer.org/deployer.phar
 *   php deployer.phar deploy production
 * =============================================================================
 */

namespace Deployer;

require 'recipe/laravel.php'; // Laravel receipti Symfony/PSR-4 uyumlu, uyarı vermeyebilir
// Veya minimal receipt: require 'recipe/common.php';

// =============================================================================
// CONFIG
// =============================================================================

set('application', 'kamelya');
set('repository', 'git@github.com:clariongemini/opencode.git'); // TODO: Gerçek repo
set('git_tty', true);
set('ssh_multiplexing', true);
set('keep_releases', 5);

# Sunucu (Hetzner Cloud CX22)
host('production')
    ->set('hostname', 'TODO_SERVER_IP')           # TODO: Hetzner IPv4
    ->set('user', 'deploy')                       # TODO: Deploy kullanıcısı
    ->set('port', 22)
    ->set('identity_file', '~/.ssh/id_ed25519')   # TODO: SSH key yolu
    ->set('deploy_path', '/var/www/kamelya')
    ->stage('production');

# =============================================================================
// SHARED FILES / DIRS
// =============================================================================

set('shared_files', [
    '.env',                 # Production .env (manuel oluşturulur)
]);

set('shared_dirs', [
    'storage',              # Loglar, uploads, cache
    'public/uploads',       # Eğer varsa
]);

# =============================================================================
// WRITABLE DIRS (Permission fix)
// =============================================================================

set('writable_dirs', [
    'storage',
    'storage/logs',
    'storage/cache',
    'public/uploads',
]);

set('writable_mode', '775');
set('writable_use_sudo', false);

// =============================================================================
// COMPOSER
// =============================================================================

set('composer_options', '--no-dev --prefer-dist --optimize-autoloader --no-interaction');
set('composer_action', 'install'); // 'update' değil

// =============================================================================
// MIGRATIONS (Sıfır-downtime stratejisi)
// =============================================================================

task('migrate', function () {
    $output = run('cd {{release_path}} && php scripts/migrate.php up 2>&1');
    writeln('<info>Migration output:</info> ' . $output);
})->desc('Run database migrations');

// Migration rollback (geri alma)
task('migrate:rollback', function () {
    $output = run('cd {{release_path}} && php scripts/migrate.php down 2>&1');
    writeln('<info>Rollback output:</info> ' . $output);
})->desc('Rollback last migration');

// =============================================================================
// HEALTH CHECK
// =============================================================================

task('health:check', function () {
    $url = 'https://kamelya.com/api/v1/health';
    $maxAttempts = 10;
    $attempt = 0;

    while ($attempt < $maxAttempts) {
        $attempt++;
        $result = runLocally("curl -s -o /dev/null -w '%{http_code}' -k --max-time 10 $url");
        if (trim($result) === '200') {
            writeln('<fg=green>✓ Health check PASSED (200)</fg=green>');
            return;
        }
        writeln("<fg=yellow>Health check attempt $attempt/$maxAttempts failed (HTTP $result), retrying in 5s...</fg=yellow>");
        sleep(5);
    }
    throw new \Exception('Health check FAILED after ' . $maxAttempts . ' attempts');
})->desc('Post-deploy health check');

// =============================================================================
// CACHE CLEAR
// =============================================================================

task('cache:clear', function () {
    run('cd {{release_path}} && rm -rf storage/cache/* storage/views/* 2>/dev/null; echo "Cache cleared"');
})->desc('Clear application cache');

// =============================================================================
// SYMLINK SWITCH (Atomic)
// =============================================================================

// Deployer default 'deploy:symlink' atomik yapar, ekstra bir şey gerekmez.

// =============================================================================
// ROLLBACK
// =============================================================================

// 'dep rollback production' otomatik önceki release'e döner.

// =============================================================================
// DEPLOY FLOW
// =============================================================================

desc('Deploy to production');
task('deploy', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'composer:install',
    'migrate',
    'cache:clear',
    'deploy:symlink',
    'health:check',
    'deploy:unlock',
    'cleanup',
])->desc('Full production deploy');

// =============================================================================
// HOOKS
// =============================================================================

after('deploy:failed', 'deploy:unlock');

// =============================================================================
// HELPER TASKS
// =============================================================================

task('logs', function () {
    run('tail -f {{deploy_path}}/shared/storage/logs/*.log');
})->desc('Tail production logs');

task('db:backup', function () {
    $date = date('Y-m-d_H-i-s');
    $file = "{{deploy_path}}/backups/db_backup_{$date}.sql.gz";
    run("mkdir -p {{deploy_path}}/backups");
    run("mysqldump --single-transaction --routines --triggers -h {{getenv('DB_HOST')}} -u {{getenv('DB_KULLANICI')}} -p{{getenv('DB_SIFRE')}} {{getenv('DB_ADI')}} | gzip > $file");
    writeln("<info>Backup saved: $file</info>");
})->desc('Database backup');

task('db:restore', function () {
    $backup = ask('Backup dosya yolu (örn: /var/www/kamelya/backups/db_backup_2026-09-22_10-00-00.sql.gz):');
    run("gunzip -c $backup | mysql -h {{getenv('DB_HOST')}} -u {{getenv('DB_KULLANICI')}} -p{{getenv('DB_SIFRE')}} {{getenv('DB_ADI')}}");
    writeln('<info>Restore completed</info>');
})->desc('Database restore from backup');