<?php
/**
 * Trindade Framework
 *
 * Mail System
 * 
 * Handles email operations using PHPMailer.
 * Provides email sending with templates and attachments.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

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
    /**
     * PHPMailer instance
     * 
     * @var PHPMailer
     */
    private $mailer;
    
    /**
     * Mail configuration
     * 
     * @var array
     */
    private $config;
    
    /**
     * Logger callback
     * 
     * @var callable|null
     */
    private $logger;
    
    /**
     * Constructor
     * 
     * @param array $config Mail configuration
     * @param callable|null $logger Logger callback
     */
    public function __construct(array $config, ?callable $logger = null) {
        $this->config = array_merge([
            'driver' => 'smtp',
            'host' => 'localhost',
            'port' => 587,
            'encryption' => 'tls',
            'username' => '',
            'password' => '',
            'from' => ['address' => '', 'name' => ''],
            'debug' => false
        ], $config);
        
        $this->logger = $logger;
        $this->initialize();
    }
    
    /**
     * Initializes PHPMailer with configuration
     * 
     * @return void
     * @throws \RuntimeException If initialization fails
     */
    protected function initialize(): void {
        try {
            $this->mailer = new PHPMailer(true);
            
            if ($this->config['driver'] === 'smtp') {
                $this->mailer->isSMTP();
                $this->mailer->Host = $this->config['host'];
                $this->mailer->Port = $this->config['port'];
                $this->mailer->SMTPAuth = !empty($this->config['username']);
                $this->mailer->Username = $this->config['username'];
                $this->mailer->Password = $this->config['password'];
                $this->mailer->SMTPSecure = $this->config['encryption'];
            }
            
            $this->mailer->setFrom(
                $this->config['from']['address'],
                $this->config['from']['name']
            );
            
            $this->mailer->CharSet = 'UTF-8';
            
            if ($this->config['debug']) {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            }
        } catch (Exception $e) {
            throw new \RuntimeException('Mail initialization failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Sends an email
     * 
     * @param string|array $to Recipient email or array of emails
     * @param string $subject Email subject
     * @param string $body Email body
     * @param array $options Additional options (cc, bcc, attachments, etc)
     * @return bool Success status
     */
    public function send($to, string $subject, string $body, array $options = []): bool {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Add recipients
            if (is_array($to)) {
                foreach ($to as $address => $name) {
                    if (is_numeric($address)) {
                        $this->mailer->addAddress($name);
                    } else {
                        $this->mailer->addAddress($address, $name);
                    }
                }
            } else {
                $this->mailer->addAddress($to);
            }
            
            // Set subject and body
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->isHTML(true);
            
            // Add CC
            if (!empty($options['cc'])) {
                foreach ((array)$options['cc'] as $cc) {
                    $this->mailer->addCC($cc);
                }
            }
            
            // Add BCC
            if (!empty($options['bcc'])) {
                foreach ((array)$options['bcc'] as $bcc) {
                    $this->mailer->addBCC($bcc);
                }
            }
            
            // Add attachments
            if (!empty($options['attachments'])) {
                foreach ((array)$options['attachments'] as $attachment) {
                    if (is_array($attachment)) {
                        $this->mailer->addAttachment(
                            $attachment['path'],
                            $attachment['name'] ?? '',
                            $attachment['encoding'] ?? 'base64',
                            $attachment['type'] ?? ''
                        );
                    } else {
                        $this->mailer->addAttachment($attachment);
                    }
                }
            }
            
            $result = $this->mailer->send();
            
            if ($this->logger) {
                call_user_func($this->logger, "Email sent successfully to: " . implode(', ', (array)$to));
            }
            
            return $result;
        } catch (Exception $e) {
            if ($this->logger) {
                call_user_func($this->logger, "Email sending failed: " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Sends an email using a template
     * 
     * @param string|array $to Recipient email or array of emails
     * @param string $template Template name or path
     * @param array $data Template data
     * @param array $options Additional options
     * @return bool Success status
     */
    public function sendTemplate($to, string $template, array $data = [], array $options = []): bool {
        if (!file_exists($template)) {
            throw new \RuntimeException("Template not found: {$template}");
        }
        
        ob_start();
        extract($data);
        include $template;
        $body = ob_get_clean();
        
        return $this->send($to, $options['subject'] ?? '', $body, $options);
    }
    
    /**
     * Gets the PHPMailer instance
     * 
     * @return PHPMailer
     */
    public function getMailer(): PHPMailer {
        return $this->mailer;
    }
    
    /**
     * Gets the current configuration
     * 
     * @return array
     */
    public function getConfig(): array {
        return $this->config;
    }
    
    /**
     * Sets a configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @return self
     */
    public function setConfig(string $key, $value): self {
        $this->config[$key] = $value;
        return $this;
    }
    
    /**
     * Logs a message using the logger callback
     * 
     * @param string $message Message to log
     * @return void
     */
    protected function log(string $message): void {
        if ($this->logger) {
            call_user_func($this->logger, $message);
        }
    }
}