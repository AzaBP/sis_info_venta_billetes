<?php
// Intentar cargar autoload de Composer si existe
$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// Comprobar si PHPMailer está disponible
$hasPHPMailer = class_exists('PHPMailer\\PHPMailer\\PHPMailer');

class Mailer {
    private $config;
    private $usePHPMailer;

    public function __construct() {
        $this->config = require __DIR__ . '/../config_mail.php';
        $this->usePHPMailer = class_exists('PHPMailer\\PHPMailer\\PHPMailer');
        if (!$this->usePHPMailer) {
            error_log('PHPMailer no disponible: usando mail() como fallback');
        }
    }

    public function getConfigIssue(): ?string {
        $host = trim((string)($this->config['host'] ?? ''));
        $username = trim((string)($this->config['username'] ?? ''));
        $password = trim((string)($this->config['password'] ?? ''));
        $fromEmail = trim((string)($this->config['from_email'] ?? ''));

        if (
            $host === '' || $username === '' || $password === '' || $fromEmail === '' ||
            $host === 'smtp.example.com' || $username === 'usuario@example.com' ||
            $password === 'tu_password' || $fromEmail === 'no-reply@example.com'
        ) {
            return 'SMTP_HOST, SMTP_USER, SMTP_PASS y SMTP_FROM';
        }

        return null;
    }

    public function send($toEmail, $toName, $subject, $bodyHtml, $bodyText = '', array $attachments = []) {
        $configIssue = $this->getConfigIssue();
        if ($configIssue !== null) {
            error_log('Mailer config invalida: completa ' . $configIssue);
            return false;
        }

        if ($this->usePHPMailer) {
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                // Ensure UTF-8 everywhere
                $mail->CharSet = 'UTF-8';
                //Server settings
                $mail->isSMTP();
                $mail->Host = $this->config['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $this->config['username'];
                $mail->Password = $this->config['password'];
                $mail->SMTPSecure = $this->config['secure'];
                $mail->Port = $this->config['port'];

                $mail->setFrom($this->config['from_email'], $this->config['from_name']);
                $mail->addAddress($toEmail, $toName);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $bodyHtml;
                if (!empty($bodyText)) $mail->AltBody = $bodyText;

                foreach ($attachments as $attachment) {
                    if (!is_array($attachment)) {
                        continue;
                    }

                    $filename = (string)($attachment['filename'] ?? 'adjunto.bin');
                    $mimeType = (string)($attachment['mime'] ?? 'application/octet-stream');

                    if (!empty($attachment['path']) && is_string($attachment['path']) && file_exists($attachment['path'])) {
                        $mail->addAttachment($attachment['path'], $filename, 'base64', $mimeType);
                        continue;
                    }

                    if (array_key_exists('content', $attachment)) {
                        $content = (string)$attachment['content'];
                        $mail->addStringAttachment($content, $filename, 'base64', $mimeType);
                    }
                }

                return $mail->send();
            } catch (\Exception $e) {
                error_log('Mailer error (PHPMailer): ' . $e->getMessage());
                return false;
            }
        }

        // Fallback: usar mail() nativo
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit" . "\r\n";
        // Encode the From name and subject for proper UTF-8 display in simple mail()
        $fromName = (string)($this->config['from_name'] ?? '');
        if (function_exists('mb_encode_mimeheader')) {
            $fromNameEnc = mb_encode_mimeheader($fromName, 'UTF-8');
        } else {
            $fromNameEnc = $fromName;
        }
        $headers .= "From: " . $fromNameEnc . " <" . ($this->config['from_email'] ?? '') . ">" . "\r\n";
        $message = $bodyHtml;
        // Encode subject if mbstring available
        if (function_exists('mb_encode_mimeheader')) {
            $subjectEnc = mb_encode_mimeheader($subject, 'UTF-8');
        } else {
            $subjectEnc = $subject;
        }
        $result = mail($toEmail, $subjectEnc, $message, $headers);
        if (!$result) {
            error_log('Mailer error (mail fallback): fallo al enviar a ' . $toEmail);
        }
        return $result;
    }
}
