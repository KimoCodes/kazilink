<?php

declare(strict_types=1);

final class Logger
{
    private const LOG_FILE = BASE_PATH . '/storage/logs/debug.log';
    private const MAX_FILE_SIZE = 10485760; // 10MB
    private const BACKUP_COUNT = 5;

    private string $logFile;
    private string $context;

    public function __construct(string $context = 'app')
    {
        $this->logFile = self::LOG_FILE;
        $this->context = $context;
        $this->ensureLogDirectory();
    }

    public static function debug(string $message, array $context = []): void
    {
        self::getInstance()->log('DEBUG', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::getInstance()->log('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::getInstance()->log('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::getInstance()->log('ERROR', $message, $context);
    }

    public static function security(string $message, array $context = []): void
    {
        self::getInstance()->log('SECURITY', $message, $context);
    }

    public static function payment(string $message, array $context = []): void
    {
        self::getInstance()->log('PAYMENT', $message, $context);
    }

    public static function database(string $message, array $context = []): void
    {
        self::getInstance()->log('DATABASE', $message, $context);
    }

    private function log(string $level, string $message, array $context = []): void
    {
        $this->rotateIfNeeded();

        $timestamp = date('Y-m-d H:i:s');
        $userId = Auth::id() ?? 'guest';
        $ip = request_ip();
        $route = current_route();
        
        $contextStr = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        $logEntry = "[{$timestamp}] {$level} [{$this->context}] [user:{$userId}] [ip:{$ip}] [route:{$route}] {$message}{$contextStr}" . PHP_EOL;

        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    private function ensureLogDirectory(): void
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    private function rotateIfNeeded(): void
    {
        if (!file_exists($this->logFile) || filesize($this->logFile) < self::MAX_FILE_SIZE) {
            return;
        }

        // Rotate logs
        for ($i = self::BACKUP_COUNT - 1; $i >= 1; $i--) {
            $oldFile = $this->logFile . '.' . $i;
            $newFile = $this->logFile . '.' . ($i + 1);
            
            if (file_exists($oldFile)) {
                if ($i === self::BACKUP_COUNT - 1) {
                    unlink($oldFile); // Delete oldest
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }

        // Move current log to .1
        rename($this->logFile, $this->logFile . '.1');
    }

    private static function getInstance(): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

    public static function logException(Throwable $exception, array $context = []): void
    {
        $context['exception'] = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
        
        self::error('Uncaught exception: ' . $exception->getMessage(), $context);
    }

    public static function logQuery(string $sql, array $params = [], float $executionTime = null): void
    {
        $context = [
            'sql' => $sql,
            'params' => $params,
        ];
        
        if ($executionTime !== null) {
            $context['execution_time_ms'] = round($executionTime * 1000, 2);
        }
        
        self::database('Query executed', $context);
    }

    public static function logAuthEvent(string $event, array $context = []): void
    {
        self::security("Auth event: {$event}", $context);
    }

    public static function logPaymentEvent(string $event, array $context = []): void
    {
        self::payment("Payment event: {$event}", $context);
    }
}
