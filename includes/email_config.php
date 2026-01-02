<?php
/**
 * Configuração de Email
 * 
 * INSTRUÇÕES PARA GMAIL:
 * 1. Vai a https://myaccount.google.com/apppasswords
 * 2. Seleciona "Mail" e "Windows Computer"
 * 3. Gera uma palavra-passe de app
 * 4. Usa essa palavra-passe aqui (não a tua password normal)
 */

// Configurações SMTP
define('SMTP_HOST', 'smtp.gmail.com');           // Gmail SMTP
define('SMTP_PORT', 587);                         // Porta TLS
define('SMTP_USERNAME', 'thomaz29valente@gmail.com');   // ⚠️ ALTERAR: Teu email
define('SMTP_PASSWORD', 'kdqhlpnebgbicvgc');     // Password de app do Gmail (SEM ESPAÇOS)
define('SMTP_FROM_EMAIL', 'thomaz29valente@gmail.com'); // ⚠️ ALTERAR: Mesmo email
define('SMTP_FROM_NAME', 'GameList');             // Nome que aparece no remetente

// URL base do site (sem / no final)
define('BASE_URL', 'http://localhost/PAP_TomasValente_GAMELIST');

/**
 * ALTERNATIVAS AO GMAIL:
 * 
 * OUTLOOK/HOTMAIL:
 * - SMTP_HOST: smtp.office365.com
 * - SMTP_PORT: 587
 * 
 * YAHOO:
 * - SMTP_HOST: smtp.mail.yahoo.com
 * - SMTP_PORT: 587
 * 
 * OUTROS PROVEDORES:
 * - SendGrid, Mailgun, Amazon SES (mais profissionais)
 */
