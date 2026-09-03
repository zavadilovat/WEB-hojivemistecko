<?php
/**
 * Kontaktní formulář pro Hojivé Místečko (hojivemistecko.cz)
 * Bezpečné odeslání zprávy přes PHP s ochranou proti spamu (honeypot).
 */

// 1. Nastavení příjemců
// Po dokončení testování můžete řádek se zavadilova.t@gmail.com jednoduše smazat.
$recipients = [
    'jirinamerglova@seznam.cz',
    'zavadilova.t@gmail.com', // Testovací e-mail
];

// Odesílací adresa (doménový e-mail zajišťuje bezproblémové doručení do schránky)
$fromEmail = 'info@hojivemistecko.cz';
$siteName = 'Hojivé Místečko';

// 2. Kontrola metody POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /kontakt');
    exit;
}

// 3. Ochrana proti spamu – Honeypot (past na roboty)
// Pole "website" je pro běžné lidi skryté. Pokud ho někdo vyplní, je to bot.
if (!empty($_POST['website'])) {
    // Pro bota předstíráme úspěch, ale e-mail neodešleme
    respondSuccess();
}

// 4. Získání a očištění dat z formuláře
$name = isset($_POST['name']) ? trim(strip_tags($_POST['name'])) : '';
$email = isset($_POST['email']) ? trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)) : '';
$phone = isset($_POST['phone']) ? trim(strip_tags($_POST['phone'])) : '';
$message = isset($_POST['message']) ? trim(strip_tags($_POST['message'])) : '';

// 5. Validace povinných polí
if (empty($name) || empty($email) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respondError('Prosím vyplňte všechna povinná pole a zadejte platný e-mail.');
}

// 6. Sestavení předmětu a obsahu e-mailu
$subject = "Nová zpráva z webu {$siteName}: {$name}";
$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
$date = date('d. m. Y v H:i');

// HTML verze e-mailu
$emailHtml = '
<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; color: #333333; line-height: 1.6; }
    .container { max-width: 600px; margin: 20px auto; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
    .header { background: #247D79; color: #ffffff; padding: 24px; text-align: center; }
    .header h1 { margin: 0; font-size: 20px; letter-spacing: 0.5px; }
    .content { padding: 28px 24px; background: #ffffff; }
    .item { margin-bottom: 16px; }
    .label { font-weight: 700; color: #247D79; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
    .value { font-size: 15px; margin-top: 4px; }
    .message-box { background: #f8fafc; border-left: 4px solid #36B6B1; padding: 16px; border-radius: 4px; font-size: 15px; white-space: pre-wrap; margin-top: 6px; }
    .footer { background: #f1f5f9; color: #64748b; padding: 14px 24px; font-size: 12px; text-align: center; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Nová zpráva z kontaktního formuláře</h1>
    </div>
    <div class="content">
      <div class="item">
        <div class="label">Odesílatel (Jméno a příjmení):</div>
        <div class="value"><strong>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</strong></div>
      </div>
      <div class="item">
        <div class="label">E-mail:</div>
        <div class="value"><a href="mailto:' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '" style="color: #247D79; text-decoration: underline;">' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</a></div>
      </div>
      <div class="item">
        <div class="label">Telefonní číslo:</div>
        <div class="value">' . (!empty($phone) ? '<a href="tel:' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . '</a>' : '<em>Neuvedeno</em>') . '</div>
      </div>
      <div class="item">
        <div class="label">Zpráva nebo dotaz:</div>
        <div class="message-box">' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</div>
      </div>
    </div>
    <div class="footer">
      Zpráva byla odeslána ' . $date . ' z webu hojivemistecko.cz.<br>
      Přímo v e-mailovém programu můžete kliknout na „Odpovědět“ pro odpověď odesílateli.
    </div>
  </div>
</body>
</html>';

// 7. Sestavení e-mailových hlaviček
$cleanSenderName = '=?UTF-8?B?' . base64_encode($name) . '?=';
$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    "From: =?UTF-8?B?" . base64_encode($siteName) . "?= <{$fromEmail}>",
    "Reply-To: {$cleanSenderName} <{$email}>",
    "X-Mailer: PHP/" . phpversion(),
];
$headersStr = implode("\r\n", $headers);

// 8. Odeslání e-mailu všem příjemcům
$allSent = true;
foreach ($recipients as $recipient) {
    $recipient = trim($recipient);
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        continue;
    }
    $sent = @mail($recipient, $encodedSubject, $emailHtml, $headersStr);
    if (!$sent) {
        $allSent = false;
    }
}

// 9. Úspěšné přesměrování / odpověď
respondSuccess();

// --- Pomocné funkce ---
function respondSuccess() {
    $isAjax = (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
           || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'redirect' => '/dekuji']);
        exit;
    }

    header('Location: /dekuji');
    exit;
}

function respondError($message) {
    $isAjax = (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
           || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8', true, 400);
        echo json_encode(['ok' => false, 'error' => $message]);
        exit;
    }

    echo '<!DOCTYPE html><html lang="cs"><head><meta charset="UTF-8"><title>Chyba</title></head><body>';
    echo '<p style="color: red;">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p><a href="javascript:history.back()">Zpět na formulář</a></p>';
    echo '</body></html>';
    exit;
}
