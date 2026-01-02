<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envia um email usando PHPMailer
 * 
 * @param string $to Email do destinatário
 * @param string $subject Assunto do email
 * @param string $body Corpo do email (HTML)
 * @return bool True se enviado com sucesso, False caso contrário
 */
function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        // Configurações do servidor SMTP
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Remetente e destinatário
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Conteúdo do email
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        // Envia o email
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Erro ao enviar email: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Envia email de verificação de conta
 */
function sendVerificationEmail($email, $username, $token) {
    $verifyLink = BASE_URL . "/verify_email.php?token=" . urlencode($token);
    
    $subject = "Verifica a tua conta - GameList";
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #00bfff; }
            .header h1 { color: #00bfff; margin: 0; }
            .content { padding: 20px 0; }
            .button { display: inline-block; padding: 12px 30px; background: #00bfff; color: #fff; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; padding-top: 20px; border-top: 1px solid #ddd; color: #999; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎮 GameList</h1>
            </div>
            <div class='content'>
                <h2>Olá, {$username}! 👋</h2>
                <p>Obrigado por te registares na GameList!</p>
                <p>Para ativares a tua conta, clica no botão abaixo:</p>
                <a href='{$verifyLink}' class='button'>Verificar Email</a>
                <p style='color: #666; font-size: 14px;'>Ou copia e cola este link no teu navegador:</p>
                <p style='word-break: break-all; color: #00bfff;'>{$verifyLink}</p>
                <p style='color: #999; font-size: 12px; margin-top: 30px;'>Este link expira em 24 horas.</p>
            </div>
            <div class='footer'>
                <p>Se não criaste esta conta, ignora este email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
}

/**
 * Envia email de reset de password
 */
function sendPasswordResetEmail($email, $username, $token) {
    $resetLink = BASE_URL . "/reset_password.php?token=" . urlencode($token);
    
    $subject = "Recuperar Password - GameList";
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #00bfff; }
            .header h1 { color: #00bfff; margin: 0; }
            .content { padding: 20px 0; }
            .button { display: inline-block; padding: 12px 30px; background: #00bfff; color: #fff; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; padding-top: 20px; border-top: 1px solid #ddd; color: #999; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎮 GameList</h1>
            </div>
            <div class='content'>
                <h2>Olá, {$username}! 👋</h2>
                <p>Recebemos um pedido para recuperar a password da tua conta.</p>
                <p>Clica no botão abaixo para criares uma nova password:</p>
                <a href='{$resetLink}' class='button'>Recuperar Password</a>
                <p style='color: #666; font-size: 14px;'>Ou copia e cola este link no teu navegador:</p>
                <p style='word-break: break-all; color: #00bfff;'>{$resetLink}</p>
                <p style='color: #999; font-size: 12px; margin-top: 30px;'>Este link expira em 1 hora.</p>
            </div>
            <div class='footer'>
                <p>Se não fizeste este pedido, ignora este email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
}

/**
 * Envia email de verificação ao mudar email
 */
function sendEmailChangeVerification($newEmail, $username, $token) {
    $verifyLink = BASE_URL . "/verify_email_change.php?token=" . urlencode($token);
    
    $subject = "Confirma o teu novo email - GameList";
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #00bfff; }
            .header h1 { color: #00bfff; margin: 0; }
            .content { padding: 20px 0; }
            .button { display: inline-block; padding: 12px 30px; background: #00bfff; color: #fff; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; padding-top: 20px; border-top: 1px solid #ddd; color: #999; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎮 GameList</h1>
            </div>
            <div class='content'>
                <h2>Olá, {$username}! 👋</h2>
                <p>Recebemos um pedido para alterar o email da tua conta.</p>
                <p>Para confirmares o novo email, clica no botão abaixo:</p>
                <a href='{$verifyLink}' class='button'>Verificar Novo Email</a>
                <p style='color: #666; font-size: 14px;'>Ou copia e cola este link no teu navegador:</p>
                <p style='word-break: break-all; color: #00bfff;'>{$verifyLink}</p>
                <p style='color: #999; font-size: 12px; margin-top: 30px;'>Este link expira em 1 hora.</p>
            </div>
            <div class='footer'>
                <p>Se não fizeste este pedido, ignora este email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($newEmail, $subject, $body);
}
