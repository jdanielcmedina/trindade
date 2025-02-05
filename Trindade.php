<?php
namespace Trindade;

/**
 * Session Class - PHP Session Management
 * 
 * @package Trindade
 */
class Session {
    /**
     * Sets a session value
     *
     * @param string $key Session key
     * @param mixed $value Value to store
     * @return mixed Returns the stored value
     */
    public function set(string $key, $value) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION[$key] = $value;
        return $value;
    }
    
    /**
     * Gets a session value
     *
     * @param string $key Session key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    public function get(string $key, $default = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Removes a session key
     *
     * @param string $key Session key to remove
     * @return void
     */
    public function remove(string $key): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION[$key]);
    }
    
    /**
     * Destroys the entire session
     *
     * @return void
     */
    public function destroy(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}

/**
 * Cookie Class - Browser Cookie Management
 * 
 * @package Trindade
 */
class Cookie {
    /**
     * Sets a cookie value
     *
     * @param string $key Cookie name
     * @param mixed $value Cookie value
     * @param int $expires Expiration time in seconds
     * @return bool Success status
     */
    public function set(string $key, $value, int $expires = 3600): bool {
        return setcookie($key, $value, time() + $expires, '/');
    }
    
    /**
     * Gets a cookie value
     *
     * @param string $key Cookie name
     * @param mixed $default Default value if cookie doesn't exist
     * @return mixed Cookie value or default
     */
    public function get(string $key, $default = null) {
        return $_COOKIE[$key] ?? $default;
    }
    
    /**
     * Removes a cookie
     *
     * @param string $key Cookie name
     * @return bool Success status
     */
    public function remove(string $key): bool {
        return setcookie($key, '', time() - 3600, '/');
    }
}

/**
 * Database Class - PDO wrapper for database operations
 * 
 * @package Trindade
 * @property \PDO $db PDO Instance
 * @property callable $logger Logging Function
 */
class Database {
    protected ?\PDO $db = null;
    protected $logger;
    
    /**
     * Database constructor
     *
     * @param \PDO $db PDO instance
     * @param callable $logger Logging function
     */
    public function __construct(\PDO $db, callable $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }
    
    /**
     * Performs a SELECT query
     *
     * @param string $table Table name
     * @param string|array $columns Columns to select
     * @param array $where Where conditions
     * @return array Query results
     */
    public function select(string $table, $columns = '*', array $where = []): array {
        try {
            $sql = "SELECT " . (is_array($columns) ? implode(', ', $columns) : $columns) . " FROM {$table}";
            $params = [];

            if (!empty($where)) {
                $conditions = [];
                foreach ($where as $key => $value) {
                    if ($key === 'ORDER' || $key === 'LIMIT') continue;
                    $conditions[] = "{$key} = ?";
                    $params[] = $value;
                }
                $sql .= " WHERE " . implode(' AND ', $conditions);
            }

            if (!empty($where['ORDER'])) {
                $sql .= " ORDER BY ";
                foreach ($where['ORDER'] as $key => $value) {
                    $sql .= "{$key} {$value}";
                }
            }

            if (!empty($where['LIMIT'])) {
                $sql .= " LIMIT " . (int)$where['LIMIT'];
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            ($this->logger)("Select error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Performs an INSERT query
     *
     * @param string $table Table name
     * @param array $data Data to insert
     * @return int|null Last insert ID or null on failure
     */
    public function insert(string $table, array $data): ?int {
        try {
            $fields = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($data));
            return (int)$this->db->lastInsertId();
        } catch (\PDOException $e) {
            ($this->logger)("Insert error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Updates database records
     *
     * @param string $table Table name
     * @param array $data Data to update
     * @param array $where Where conditions
     * @return bool Success status
     */
    public function update(string $table, array $data, array $where): bool {
        try {
            $fields = [];
            $params = [];
            foreach ($data as $key => $value) {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }

            $conditions = [];
            foreach ($where as $key => $value) {
                $conditions[] = "{$key} = ?";
                $params[] = $value;
            }

            $sql = "UPDATE {$table} SET " . implode(', ', $fields) . " WHERE " . implode(' AND ', $conditions);
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            ($this->logger)("Update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Deletes database records
     *
     * @param string $table Table name
     * @param array $where Where conditions
     * @return bool Success status
     */
    public function delete(string $table, array $where): bool {
        try {
            $conditions = [];
            $params = [];
            foreach ($where as $key => $value) {
                $conditions[] = "{$key} = ?";
                $params[] = $value;
            }

            $sql = "DELETE FROM {$table} WHERE " . implode(' AND ', $conditions);
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            ($this->logger)("Delete error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Cache Class - File-based caching system
 * 
 * @package Trindade
 * @property array $memory Memory cache
 * @property string $path Cache files path
 */
class Cache {
    protected array $memory = [];
    protected string $path;
    
    /**
     * Cache constructor
     *
     * @param string $path Cache directory path
     */
    public function __construct(string $path) {
        $this->path = $path;
    }
    
    /**
     * Sets a cache value
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds
     * @return mixed Cached value
     */
    public function set(string $key, $value, int $ttl = 3600) {
        $file = $this->path . '/' . md5($key);
        $this->memory[$key] = $value;
        
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        
        file_put_contents($file, serialize([
            'expires' => time() + $ttl,
            'data' => $value
        ]));
        
        return $value;
    }
    
    public function get(string $key, $default = null) {
        if (isset($this->memory[$key])) {
            return $this->memory[$key];
        }

        $file = $this->path . '/' . md5($key);
        if (file_exists($file)) {
            $data = unserialize(file_get_contents($file));
            if (time() < $data['expires']) {
                $this->memory[$key] = $data['data'];
                return $data['data'];
            }
            unlink($file);
        }

        return $default;
    }
    
    public function remove(string $key): void {
        unset($this->memory[$key]);
        $file = $this->path . '/' . md5($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }
}

/**
 * Mail Class - Email sending system
 * 
 * @package Trindade
 * @property array $config Email configurations
 * @property callable $logger Logging function
 * @property array $attachments Attachments list
 * @property array $toRecipients Main recipients
 * @property array $ccRecipients Carbon copy recipients
 * @property array $bccRecipients Blind carbon copy recipients
 */
class Mail {
    protected array $config;
    protected $logger;
    protected array $attachments = [];
    protected array $toRecipients = [];
    protected array $ccRecipients = [];
    protected array $bccRecipients = [];
    protected string $emailSubject = '';
    protected string $emailBody = '';
    protected string $boundaryString;
    
    public function __construct(array $config, callable $logger) {
        $this->config = $config;
        $this->logger = $logger;
        $this->boundaryString = md5(uniqid(time()));
    }
    
    /**
     * Resets all email parameters
     *
     * @return self
     */
    public function reset(): self {
        $this->attachments = [];
        $this->toRecipients = [];
        $this->ccRecipients = [];
        $this->bccRecipients = [];
        $this->emailSubject = '';
        $this->emailBody = '';
        $this->boundaryString = md5(uniqid(time()));
        return $this;
    }
    
    /**
     * Adds recipients to the email
     *
     * @param string|array $email Email address or array of addresses
     * @param string $name Recipient name
     * @return self
     */
    public function to($email, string $name = ''): self {
        return $this->addRecipient('toRecipients', $email, $name);
    }
    
    /**
     * Adds CC recipients to the email
     *
     * @param string|array $email Email address or array of addresses
     * @param string $name Recipient name
     * @return self
     */
    public function cc($email, string $name = ''): self {
        return $this->addRecipient('ccRecipients', $email, $name);
    }
    
    /**
     * Adds BCC recipients to the email
     *
     * @param string|array $email Email address or array of addresses
     * @param string $name Recipient name
     * @return self
     */
    public function bcc($email, string $name = ''): self {
        return $this->addRecipient('bccRecipients', $email, $name);
    }
    
    protected function addRecipient(string $type, $email, string $name = ''): self {
        $emails = is_array($email) ? $email : [$email => $name];
        
        foreach ($emails as $emailAddr => $recipientName) {
            if (is_int($emailAddr)) {
                $emailAddr = $recipientName;
                $recipientName = '';
            }
            
            $emailAddr = strtolower(trim($emailAddr));
            
            if (!in_array($emailAddr, array_merge(
                array_keys($this->toRecipients),
                array_keys($this->ccRecipients),
                array_keys($this->bccRecipients)
            ))) {
                $this->$type[$emailAddr] = $recipientName;
            }
        }
        
        return $this;
    }
    
    /**
     * Sets the email subject
     *
     * @param string $subject Email subject
     * @return self
     */
    public function subject(string $subject): self {
        $this->emailSubject = $subject;
        return $this;
    }
    
    /**
     * Sets the email body
     *
     * @param string $content Email content
     * @param bool $isHtml Whether content is HTML
     * @return self
     */
    public function body(string $content, bool $isHtml = true): self {
        $this->emailBody = $isHtml ? $this->createHtmlMessage($content) : $content;
        return $this;
    }
    
    public function attach(string $filePath, string $fileName = ''): self {
        if (file_exists($filePath)) {
            $this->attachments[] = [
                'path' => $filePath,
                'name' => $fileName ?: basename($filePath),
                'content' => base64_encode(file_get_contents($filePath)),
                'type' => mime_content_type($filePath)
            ];
        } else {
            ($this->logger)("Attachment not found: " . $filePath);
        }
        return $this;
    }
    
    public function send(): bool {
        if (empty($this->config['username']) || empty($this->config['smtp']['host'])) {
            ($this->logger)("Mail configuration not found");
            return false;
        }

        if (empty($this->toRecipients)) {
            ($this->logger)("No recipients specified");
            return false;
        }

        if (!empty($this->config['smtp'])) {
            ini_set('SMTP', $this->config['smtp']['host']);
            ini_set('smtp_port', $this->config['smtp']['port']);
            
            if ($this->config['smtp']['auth']) {
                ini_set('smtp_username', $this->config['username']);
                ini_set('smtp_password', $this->config['password']);
            }

            if ($this->config['smtp']['secure'] === 'tls') {
                ini_set('SMTPSecure', 'tls');
            } elseif ($this->config['smtp']['secure'] === 'ssl') {
                ini_set('SMTPSecure', 'ssl');
            }
        }

        $headers = [
            'MIME-Version: 1.0',
            'From: ' . $this->config['fromName'] . ' <' . $this->config['username'] . '>'
        ];

        if (!empty($this->ccRecipients)) {
            $headers[] = 'Cc: ' . $this->formatRecipients($this->ccRecipients);
        }

        if (!empty($this->bccRecipients)) {
            $headers[] = 'Bcc: ' . $this->formatRecipients($this->bccRecipients);
        }

        if (!empty($this->attachments)) {
            $headers[] = 'Content-Type: multipart/mixed; boundary="' . $this->boundaryString . '"';
            $message = $this->buildMultipartMessage();
        } else {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $message = $this->emailBody;
        }

        $success = mail(
            $this->formatRecipients($this->toRecipients),
            $this->emailSubject,
            $message,
            implode("\r\n", $headers)
        );

        if (!$success) {
            ($this->logger)("Failed to send email to: " . implode(', ', array_keys($this->toRecipients)));
        }

        return $success;
    }
    
    protected function formatRecipients(array $recipients): string {
        $formatted = [];
        foreach ($recipients as $emailAddr => $recipientName) {
            $formatted[] = $recipientName ? "{$recipientName} <{$emailAddr}>" : $emailAddr;
        }
        return implode(', ', $formatted);
    }
    
    protected function buildMultipartMessage(): string {
        $message = "--{$this->boundaryString}\n";
        $message .= "Content-Type: text/html; charset=UTF-8\n";
        $message .= "Content-Transfer-Encoding: base64\n\n";
        $message .= chunk_split(base64_encode($this->emailBody)) . "\n";

        foreach ($this->attachments as $attachment) {
            $message .= "--{$this->boundaryString}\n";
            $message .= "Content-Type: {$attachment['type']}; name=\"{$attachment['name']}\"\n";
            $message .= "Content-Disposition: attachment; filename=\"{$attachment['name']}\"\n";
            $message .= "Content-Transfer-Encoding: base64\n\n";
            $message .= chunk_split($attachment['content']) . "\n";
        }

        $message .= "--{$this->boundaryString}--";
        return $message;
    }
    
    protected function createHtmlMessage(string $content): string {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$this->emailSubject}</title>
        </head>
        <body>
            {$content}
        </body>
        </html>
        ";
    }
}

/**
 * File Class - File and upload management
 * 
 * @package Trindade
 * @property string $uploadsPath Base path for uploads
 */
class File {
    protected string $uploadsPath;
    
    public function __construct(string $uploadsPath) {
        $this->uploadsPath = rtrim($uploadsPath, '/');
        
        if (!is_dir($this->uploadsPath)) {
            mkdir($this->uploadsPath, 0777, true);
        }
    }
    
    /**
     * Uploads a file
     *
     * @param array $file $_FILES array element
     * @param string $directory Target subdirectory
     * @return array|null File info or null on failure
     */
    public function upload(array $file, string $directory = ''): ?array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        $directory = trim($directory, '/');
        $targetDir = $this->uploadsPath . ($directory ? '/' . $directory : '');
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . ($extension ? '.' . $extension : '');
        $targetPath = $targetDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return [
                'name' => $file['name'],
                'path' => $targetPath,
                'url' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $targetPath),
                'size' => $file['size'],
                'type' => $file['type'],
                'extension' => $extension
            ];
        }
        
        return null;
    }
    
    /**
     * Downloads a file
     *
     * @param string $path File path
     * @param string|null $filename Custom filename for download
     * @throws \RuntimeException When file not found
     * @return void
     */
    public function download(string $path, string $filename = null): void {
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }
        
        $filename = $filename ?: basename($path);
        $mimeType = mime_content_type($path);
        
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-cache');
        readfile($path);
        exit;
    }
    
    public function delete(string $path): bool {
        if (file_exists($path) && is_file($path)) {
            return unlink($path);
        }
        return false;
    }
    
    /**
     * Checks if file exists
     *
     * @param string $path File path
     * @return bool Whether file exists
     */
    public function exists(string $path): bool {
        return file_exists($path) && is_file($path);
    }
    
    /**
     * Gets file size
     *
     * @param string $path File path
     * @return int File size in bytes
     */
    public function size(string $path): int {
        return file_exists($path) ? filesize($path) : 0;
    }
    
    public function extension(string $path): string {
        return pathinfo($path, PATHINFO_EXTENSION);
    }
    
    public function mimeType(string $path): string {
        return mime_content_type($path);
    }
    
    public function move(string $source, string $destination): bool {
        return rename($source, $destination);
    }
    
    public function copy(string $source, string $destination): bool {
        return copy($source, $destination);
    }
    
    public function read(string $path): ?string {
        return file_exists($path) ? file_get_contents($path) : null;
    }
    
    public function write(string $path, string $content): bool {
        return file_put_contents($path, $content) !== false;
    }
    
    public function append(string $path, string $content): bool {
        return file_put_contents($path, $content, FILE_APPEND) !== false;
    }
}

/**
 * Hash Class - Hashing and encryption utilities
 * 
 * @package Trindade
 */
class Hash {
    public function make(string $value, string $algo = PASSWORD_DEFAULT, array $options = []): string {
        return password_hash($value, $algo, $options);
    }
    
    public function verify(string $value, string $hash): bool {
        return password_verify($value, $hash);
    }
    
    public function needsRehash(string $hash, string $algo = PASSWORD_DEFAULT, array $options = []): bool {
        return password_needs_rehash($hash, $algo, $options);
    }
    
    public function md5(string $value): string {
        return md5($value);
    }
    
    public function sha1(string $value): string {
        return sha1($value);
    }
    
    public function sha256(string $value): string {
        return hash('sha256', $value);
    }
    
    public function hmac(string $value, string $key, string $algo = 'sha256'): string {
        return hash_hmac($algo, $value, $key);
    }
    
    public function encrypt(string $value, string $key): string {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }
    
    public function decrypt(string $value, string $key): ?string {
        $decoded = base64_decode($value);
        list($encrypted_data, $iv) = explode('::', $decoded, 2);
        return openssl_decrypt($encrypted_data, 'AES-256-CBC', $key, 0, $iv);
    }
    
    public function random(int $length = 32): string {
        return bin2hex(random_bytes($length / 2));
    }
    
    public function uuid(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

/**
 * Utils Class - Miscellaneous utility functions
 * 
 * @package Trindade
 */
class Utils {
    /**
     * Creates a URL-friendly slug from text
     *
     * @param string $text Input text
     * @return string Slugified text
     */
    public function slug(string $text): string {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        $text = preg_replace('/[^a-zA-Z0-9\s-]/', '', $text);
        $text = strtolower(trim($text));
        return preg_replace('/[\s-]+/', '-', $text);
    }
    
    /**
     * Truncates text to specified length
     *
     * @param string $text Input text
     * @param int $length Maximum length
     * @param string $append String to append if truncated
     * @return string Truncated text
     */
    public function truncate(string $text, int $length = 100, string $append = '...'): string {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return rtrim(mb_substr($text, 0, $length)) . $append;
    }
    
    /**
     * Creates an excerpt from text
     *
     * @param string $text Input text
     * @param int $words Maximum number of words
     * @param string $append String to append if truncated
     * @return string Text excerpt
     */
    public function excerpt(string $text, int $words = 50, string $append = '...'): string {
        $words_array = preg_split('/\s+/', strip_tags($text));
        if (count($words_array) <= $words) {
            return $text;
        }
        return implode(' ', array_slice($words_array, 0, $words)) . $append;
    }
    
    public function sanitize(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    
    public function formatNumber(float $number, int $decimals = 2): string {
        return number_format($number, $decimals, ',', '.');
    }
    
    public function formatMoney(float $value, string $currency = '€'): string {
        return $currency . ' ' . $this->formatNumber($value, 2);
    }
    
    public function formatDate(string $date, string $format = 'd/m/Y'): string {
        return date($format, strtotime($date));
    }
    
    public function timeAgo(string $date): string {
        $timestamp = strtotime($date);
        $diff = time() - $timestamp;
        
        $intervals = [
            31536000 => 'ano',
            2592000 => 'mês',
            604800 => 'semana',
            86400 => 'dia',
            3600 => 'hora',
            60 => 'minuto',
            1 => 'segundo'
        ];
        
        foreach ($intervals as $secs => $str) {
            $d = $diff / $secs;
            if ($d >= 1) {
                $r = round($d);
                return $r . ' ' . $str . ($r > 1 ? ($str === 'mês' ? 'es' : 's') : '') . ' atrás';
            }
        }
        
        return 'agora mesmo';
    }
    
    public function mask(string $value, string $mask): string {
        $masked = '';
        $k = 0;
        
        for ($i = 0; $i < strlen($mask); $i++) {
            if ($mask[$i] === '#') {
                if (isset($value[$k])) {
                    $masked .= $value[$k++];
                }
            } else {
                $masked .= $mask[$i];
            }
        }
        
        return $masked;
    }
    
    public function onlyNumbers(string $value): string {
        return preg_replace('/[^0-9]/', '', $value);
    }
    
    public function onlyLetters(string $value): string {
        return preg_replace('/[^a-zA-Z]/', '', $value);
    }
    
    public function capitalize(string $value): string {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }
    
    public function random(array $array) {
        return $array[array_rand($array)];
    }
    
    public function isEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public function isUrl(string $url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    public function isIp(string $ip): bool {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
    
    public function isCp(string $cp): bool {
        $cp = $this->onlyNumbers($cp);
        
        // Verifica se tem 7 dígitos (0000000)
        if (strlen($cp) !== 7) {
            return false;
        }
        
        // Formata para 0000-000
        return true;
    }
}

/**
 * Logger Class - Logging system with levels
 * 
 * @package Trindade
 * @property string $path Log files path
 * @property array $levels Available logging levels
 */
class Logger {
    protected string $path;
    protected array $levels = [
        'emergency' => 0,
        'alert'     => 1,
        'critical'  => 2,
        'error'     => 3,
        'warning'   => 4,
        'notice'    => 5,
        'info'      => 6,
        'debug'     => 7
    ];

    public function __construct(string $path) {
        $this->path = rtrim($path, '/');
        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
        }
    }

    public function emergency(string $message, array $context = []): void {
        $this->log('emergency', $message, $context);
    }

    public function alert(string $message, array $context = []): void {
        $this->log('alert', $message, $context);
    }

    public function critical(string $message, array $context = []): void {
        $this->log('critical', $message, $context);
    }

    public function error(string $message, array $context = []): void {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void {
        $this->log('warning', $message, $context);
    }

    public function notice(string $message, array $context = []): void {
        $this->log('notice', $message, $context);
    }

    public function info(string $message, array $context = []): void {
        $this->log('info', $message, $context);
    }

    public function debug(string $message, array $context = []): void {
        $this->log('debug', $message, $context);
    }

    protected function log(string $level, string $message, array $context = []): void {
        $logFile = $this->path . '/' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        
        // Substituir placeholders no contexto
        foreach ($context as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        $logMessage = "[{$timestamp}] {$level}: {$message}" . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

/**
 * Assets Class - Asset management (CSS/JS/Images)
 * 
 * @package Trindade
 * @property string $publicPath Public assets path
 * @property string $manifestFile Manifest file path
 * @property array|null $manifest Manifest cache
 * @property bool $useVersioning Versioning flag
 */
class Assets {
    protected string $publicPath;
    protected string $manifestFile;
    protected ?array $manifest = null;
    protected bool $useVersioning;

    public function __construct(string $publicPath, bool $useVersioning = true) {
        $this->publicPath = rtrim($publicPath, '/');
        $this->manifestFile = $this->publicPath . '/manifest.json';
        $this->useVersioning = $useVersioning;
    }

    public function css(string $path): string {
        return $this->asset($path, 'css');
    }

    public function js(string $path): string {
        return $this->asset($path, 'js');
    }

    public function img(string $path): string {
        return $this->asset($path, 'img');
    }

    protected function asset(string $path, string $type): string {
        $path = ltrim($path, '/');
        
        if (!$this->useVersioning) {
            return "/{$path}";
        }

        // Carregar manifest
        if ($this->manifest === null) {
            $this->loadManifest();
        }

        // Verificar se existe no manifest
        if (isset($this->manifest[$path])) {
            return $this->manifest[$path];
        }

        // Se não existe no manifest, criar versão
        $fullPath = "{$this->publicPath}/{$path}";
        if (file_exists($fullPath)) {
            $version = substr(md5_file($fullPath), 0, 8);
            $versionedPath = preg_replace('/\.(js|css|jpg|jpeg|png|gif|svg)$/', ".{$version}.$1", $path);
            
            // Copiar arquivo com versão
            copy($fullPath, "{$this->publicPath}/{$versionedPath}");
            
            // Atualizar manifest
            $this->manifest[$path] = "/{$versionedPath}";
            $this->saveManifest();
            
            return "/{$versionedPath}";
        }

        return "/{$path}";
    }

    protected function loadManifest(): void {
        if (file_exists($this->manifestFile)) {
            $this->manifest = json_decode(file_get_contents($this->manifestFile), true) ?? [];
        } else {
            $this->manifest = [];
        }
    }

    protected function saveManifest(): void {
        file_put_contents($this->manifestFile, json_encode($this->manifest, JSON_PRETTY_PRINT));
    }
}

/**
 * Lang Class - Internationalization system
 * 
 * @package Trindade
 * @property string $path Language files path
 * @property string $locale Current locale
 * @property array $messages Messages cache
 */
class Lang {
    protected string $path;
    protected string $locale;
    protected array $messages = [];

    public function __construct(string $path, string $defaultLocale = 'pt') {
        $this->path = rtrim($path, '/');
        $this->setLocale($defaultLocale);
    }

    public function setLocale(string $locale): void {
        $this->locale = $locale;
        $this->loadMessages();
    }

    public function getLocale(): string {
        return $this->locale;
    }

    public function get(string $key, array $replace = []): string {
        $message = $this->messages;
        
        foreach (explode('.', $key) as $segment) {
            if (!isset($message[$segment])) {
                return $key;
            }
            $message = $message[$segment];
        }

        if (!is_string($message)) {
            return $key;
        }

        return $this->replaceParams($message, $replace);
    }

    protected function loadMessages(): void {
        $file = "{$this->path}/{$this->locale}.php";
        
        if (file_exists($file)) {
            $this->messages = require $file;
        } else {
            $this->messages = [];
        }
    }

    protected function replaceParams(string $message, array $replace): string {
        foreach ($replace as $key => $value) {
            $message = str_replace(':' . $key, $value, $message);
        }
        return $message;
    }
}

/**
 * Trindade Class - Framework Core
 * 
 * @package Trindade
 * @property array $config Global configurations
 * @property array $routes Registered routes
 * @property string $currentPrefix Current group prefix
 * @property array $plugins Loaded plugins
 * @property bool $debug Debug mode
 * @property Session $session Session manager
 * @property Cookie $cookie Cookie manager
 * @property Database $db Database manager
 * @property Cache $cache Cache system
 * @property Mail $mail Email system
 * @property File $file File manager
 * @property Hash $hash Hashing utilities
 * @property Utils $utils Utility functions
 * @property Logger $log Logging system
 * @property Assets $assets Asset manager
 * @property Lang $lang Language system
 */
class Trindade {
    protected static ?Trindade $instance = null;
    protected array $config = [];
    protected array $routes = [];
    protected string $currentPrefix = '';
    protected array $plugins = [];
    protected bool $debug = false;
    
    // Componentes
    public Session $session;
    public Cookie $cookie;
    public Database $db;
    public Cache $cache;
    public Mail $mail;
    public File $file;
    public Hash $hash;
    public Utils $utils;
    public Logger $log;
    public Assets $assets;
    public Lang $lang;

    protected bool $found = false;
    protected string $parent = '';
    protected array $shortcuts = [];

    protected array $notFoundHandlers = [];

    public function __construct(array $config = []) {
        $this->config = $config ?: $this->loadConfig();
        $this->debug = $this->config['debug'] ?? false;
        
        // Setup error handling
        $this->setupErrorHandling();
        
        // Inicializar componentes
        $this->session = new Session();
        $this->cookie = new Cookie();
        
        // Cache com caminho das configurações
        $cachePath = $this->config['paths']['cache'] ?? __DIR__ . '/storage/cache';
        $this->cache = new Cache($cachePath);
        
        // Logger
        $logPath = $this->config['paths']['logs'] ?? __DIR__ . '/storage/logs';
        $this->log = new Logger($logPath);
        
        // Assets
        $publicPath = $this->config['paths']['public'] ?? __DIR__ . '/public';
        $this->assets = new Assets($publicPath);
        
        // Lang
        $langPath = $this->config['paths']['lang'] ?? __DIR__ . '/lang';
        $this->lang = new Lang($langPath);
        
        // Componentes que precisam de config
        if (!empty($this->config['mysql'])) {
            $pdo = new \PDO(
                "mysql:host={$this->config['mysql']['host']};dbname={$this->config['mysql']['database']};charset=utf8mb4",
                $this->config['mysql']['username'],
                $this->config['mysql']['password'],
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            $this->db = new Database($pdo, [$this, 'log']);
        }
        
        if (!empty($this->config['mail'])) {
            $this->mail = new Mail($this->config['mail'], [$this, 'log']);
        }

        // Inicializar File com o caminho de uploads
        $uploadsPath = $this->config['paths']['uploads'] ?? __DIR__ . '/storage/uploads';
        $this->file = new File($uploadsPath);

        $this->hash = new Hash;
        $this->utils = new Utils;

        static::$instance = $this;
    }

    // Load configuration
    protected function loadConfig(): array {
        // Primeiro tenta carregar do diretório raiz do projeto
        $configFile = getcwd() . '/config.php';
        if (file_exists($configFile)) {
            return require $configFile;
        }
        
        // Se não encontrar, tenta na mesma pasta do framework
        $configFile = __DIR__ . '/config.php';
        if (file_exists($configFile)) {
            return require $configFile;
        }
        
        // Se não encontrar nenhum, retorna array vazio
        return [];
    }

    // Logging
    public function log(string $message): void {
        if ($this->debug) {
            $logFile = ($this->config['paths']['logs'] ?? __DIR__ . '/storage/logs') . '/app.log';
            $dir = dirname($logFile);
            
            // Criar diretório se não existir
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents(
                $logFile,
                "[{$timestamp}] {$message}" . PHP_EOL,
                FILE_APPEND
            );
        }
    }

    protected function setupErrorHandling(): void {
        error_reporting(E_ALL);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        
        if ($this->debug) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
        } else {
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
        }
    }

    public function handleError($errno, $errstr, $errfile, $errline): bool {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $error = [
            'type' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ];

        if ($this->debug) {
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        } else {
            error_log(json_encode($error));
            $this->view('errors/500', ['error' => $error]);
        }

        return true;
    }

    public function handleException(\Throwable $e): void {
        $error = [
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];

        if ($this->debug) {
            echo '<pre>';
            print_r($error);
            echo '</pre>';
        } else {
            error_log(json_encode($error));
            $this->view('errors/500', ['error' => $error]);
        }
    }

    /**
     * Registers a route handler
     *
     * @param string $route Route pattern
     * @param callable $handler Route handler function
     * @return self
     */
    public function on(string $route, callable $handler): self {
        // Separa método HTTP da rota
        if (strpos($route, ' ') !== false) {
            list($method, $path) = explode(' ', $route, 2);
            $route = $path;
            $method = strtoupper($method);
        } else {
            $method = 'GET';
        }
        
        // Adiciona o prefixo do grupo à rota e normaliza
        $route = $this->normalizeRoute($this->currentPrefix . $route);
        
        // Debug log
        if ($this->debug) {
            $this->log("Registering route: {$method} {$route}");
        }
        
        // Caso especial para a rota index
        if ($route === '/') {
            $pattern = '/^\\/?$/i';
        } else {
            // Caso especial para :any - deve vir antes da conversão normal de parâmetros
            if (strpos($route, ':any') !== false) {
                $pattern = str_replace(':any', '.*', $route);
            } else {
                // Converte parâmetros da rota para regex
                $pattern = preg_replace('/:[a-zA-Z]+/', '([^/]+)', $route);
            }
            
            $pattern = str_replace('/', '\/', $pattern);
            $pattern = '/^' . $pattern . '\/?$/i';
        }
        
        // Verifica se esta rota corresponde ao URL atual
        $uri = $this->normalizeRoute($_SERVER['REQUEST_URI']);
        $currentMethod = $_SERVER['REQUEST_METHOD'];
        
        // Debug log
        if ($this->debug) {
            $this->log("Checking route: {$currentMethod} {$uri} against pattern {$pattern}");
        }
        
        if (($method === $currentMethod || $method === 'ANY') && preg_match($pattern, $uri, $matches)) {
            array_shift($matches);
            
            try {
                ob_start();
                $handler = $handler->bindTo($this);
                call_user_func_array($handler, $matches);
                $output = ob_get_clean();
                
                if ($output) {
                    echo $output;
                }
                exit;
            } catch (\Throwable $e) {
                $this->handleException($e);
            }
        }
        
        // Se chegou aqui, verifica se é uma rota da API e executa o handler 404 apropriado
        if ($this->isRouteNotFound($uri)) {
            $this->handleNotFound($uri);
        }
        
        return $this;
    }

    /**
     * Normaliza uma rota para um formato consistente
     */
    protected function normalizeRoute(string $route): string {
        // Remove query string se existir
        if (($pos = strpos($route, '?')) !== false) {
            $route = substr($route, 0, $pos);
        }
        
        // Converte para minúsculas
        $route = strtolower($route);
        
        // Garante que começa com /
        $route = '/' . ltrim($route, '/');
        
        // Remove barras duplicadas
        $route = preg_replace('#/+#', '/', $route);
        
        // Remove barra final exceto se for apenas /
        return $route === '/' ? '/' : rtrim($route, '/');
    }

    /**
     * Renders a view with data
     *
     * @param string $name View name/path
     * @param array $data Data to pass to the view
     * @throws \RuntimeException When view file not found
     * @return void
     */
    public function view(string $name, array $data = []): void {
        $file = ($this->config['paths']['views'] ?? __DIR__ . '/views') . '/' . $name . '.php';
        
        if (!file_exists($file)) {
            throw new \RuntimeException("View not found: {$name}");
        }
        
        extract($data);
        include $file;
    }

    /**
     * Sends plain text response
     *
     * @param string $content Response content
     * @param int $statusCode HTTP status code
     * @return void
     */
    public function text(string $content, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $content;
    }

    public function json($data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * Gets GET request parameter
     *
     * @param string|null $key Parameter key
     * @param mixed $default Default value if key not found
     * @return mixed Parameter value or default
     */
    public function get(?string $key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * Gets POST request parameter
     *
     * @param string|null $key Parameter key
     * @param mixed $default Default value if key not found
     * @return mixed Parameter value or default
     */
    public function post(?string $key = null, $default = null) {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    /**
     * Gets request parameter from GET or POST
     *
     * @param string|null $key Parameter key
     * @param mixed $default Default value if key not found
     * @return mixed Parameter value or default
     */
    public function request(?string $key = null, $default = null) {
        if ($key === null) {
            return $_REQUEST;
        }
        return $_REQUEST[$key] ?? $default;
    }

    public function input(?string $key = null, $default = null) {
        if ($key === null) {
            return array_merge($_GET, $_POST);
        }
        return $_REQUEST[$key] ?? $default;
    }

    /**
     * Sets or gets an HTTP header
     *
     * @param string $key Header name
     * @param string|null $value Header value (null to get)
     * @return string|array|null Header value, all headers, or null
     */
    public function header(string $key, string $value = null): string|array|null {
        if ($value !== null) {
            header("$key: $value");
            return $value;
        }
        
        $headers = getallheaders();
        if ($key === null) {
            return $headers;
        }
        
        // Procura o header independente de maiúsculas/minúsculas
        $key = strtolower($key);
        foreach ($headers as $k => $v) {
            if (strtolower($k) === $key) {
                return $v;
            }
        }
        
        return null;
    }

    public function setHeaders(array $headers): self {
        foreach ($headers as $key => $value) {
            header("$key: $value");
        }
        return $this;
    }

    public function getHeaders(): array {
        return getallheaders();
    }

    public function hasHeader(string $key): bool {
        $headers = getallheaders();
        $key = strtolower($key);
        
        foreach ($headers as $k => $v) {
            if (strtolower($k) === $key) {
                return true;
            }
        }
        
        return false;
    }

    public function removeHeader(string $key): self {
        header_remove($key);
        return $this;
    }

    /**
     * Groups routes under a common prefix
     *
     * @param string $prefix URL prefix for the group
     * @param callable $callback Group definition callback
     * @param callable|null $notFoundHandler 404 handler for this group
     * @return self
     */
    public function group(string $prefix, callable $callback, ?callable $notFoundHandler = null): self {
        // Guardar o prefixo anterior
        $previousPrefix = $this->currentPrefix;
        
        // Adicionar o novo prefixo ao prefixo atual
        $this->currentPrefix .= $prefix;
        
        // Se tiver um handler de 404, registra para este prefixo
        if ($notFoundHandler) {
            $this->notFoundHandlers[$this->currentPrefix] = $notFoundHandler->bindTo($this);
        }
        
        // Executar o callback
        $callback();
        
        // Restaurar o prefixo anterior
        $this->currentPrefix = $previousPrefix;
        
        return $this;
    }

    protected function isRouteNotFound(string $uri): bool {
        static $checked = false;
        
        // Evita verificação múltipla para a mesma requisição
        if ($checked) {
            return false;
        }
        
        $checked = true;
        return true;
    }

    protected function handleNotFound(string $uri): void {
        // Encontra o handler mais específico para a URI atual
        $bestMatch = '';
        $handler = null;
        
        foreach ($this->notFoundHandlers as $prefix => $notFoundHandler) {
            if (strpos($uri, $prefix) === 0 && strlen($prefix) > strlen($bestMatch)) {
                $bestMatch = $prefix;
                $handler = $notFoundHandler;
            }
        }
        
        if ($handler) {
            $handler();
            exit;
        }
    }
} 