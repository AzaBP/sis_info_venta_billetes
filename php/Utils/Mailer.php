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

    public function send($toEmail, $toName, $subject, $bodyHtml, $bodyText = '') {
        if ($this->usePHPMailer) {
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
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

                return $mail->send();
            } catch (\Exception $e) {
                error_log('Mailer error (PHPMailer): ' . $e->getMessage());
                return false;
            }
        }

        // Fallback: usar mail() nativo
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: " . ($this->config['from_name'] ?? '') . " <" . ($this->config['from_email'] ?? '') . ">" . "\r\n";
        $message = $bodyHtml;
        $result = mail($toEmail, $subject, $message, $headers);
        if (!$result) {
            error_log('Mailer error (mail fallback): fallo al enviar a ' . $toEmail);
        }
        return $result;
    }
}
