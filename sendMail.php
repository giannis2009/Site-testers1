<?php
// sendMail.php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo "Method not allowed";
  exit;
}

function safe($v) {
  return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}

// Λήψη & sanitization
$name = isset($_POST['name']) ? safe($_POST['name']) : 'Πελάτης';
$email = isset($_POST['email']) ? safe($_POST['email']) : '';
$phone = isset($_POST['phone']) ? safe($_POST['phone']) : '';
$address = isset($_POST['address']) ? safe($_POST['address']) : '';
$payment = isset($_POST['payment']) ? safe($_POST['payment']) : 'Μη καθορισμένος';
$orderType = isset($_POST['orderType']) ? safe($_POST['orderType']) : 'delivery';
$cartJson = isset($_POST['cartJson']) ? $_POST['cartJson'] : '[]';

// validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo "Μη έγκυρο email.";
  exit;
}

// decode cart JSON
$cart = json_decode($cartJson, true);
if (!is_array($cart)) $cart = [];

// build message body (plain text)
$total = 0.0;
$itemsText = "";
foreach ($cart as $it) {
  $title = isset($it['title']) ? safe($it['title']) : 'Προϊόν';
  $qty = isset($it['qty']) ? (int)$it['qty'] : 1;
  $price = isset($it['price']) ? floatval($it['price']) : 0.0;
  $subtotal = $qty * $price;
  $total += $subtotal;
  $itemsText .= "{$qty} × {$title} — €" . number_format($price, 2) . " (Σύνολο €" . number_format($subtotal, 2) . ")\n";
}

// Compose email
$subject = "Απόδειξη Παραγγελίας - MISTVAPE";
$siteName = "MISTVAPE";
$now = date("d/m/Y H:i");
$orderTypeText = ($orderType === 'pickup') ? "Παραλαβή από το κατάστημα" : "Παράδοση / Παραγγελία";

$message = "Καλησπέρα {$name},\n\n";
$message .= "Ευχαριστούμε που επιλέξατε {$siteName}.\n";
$message .= "Η παραγγελία σας καταχωρήθηκε στις: {$now}\n";
$message .= "Τύπος παραγγελίας: {$orderTypeText}\n";
$message .= "Τρόπος πληρωμής: {$payment}\n\n";
if (!empty($phone)) $message .= "Τηλέφωνο: {$phone}\n";
if (!empty($address)) $message .= "Διεύθυνση: {$address}\n";
$message .= "\nΠΡΟΪΟΝΤΑ:\n";
$message .= "---------------------------\n";
$message .= $itemsText;
$message .= "---------------------------\n";
$message .= "ΣΥΝΟΛΟ: €" . number_format($total, 2) . "\n\n";
$message .= "Θα επικοινωνήσουμε σύντομα για την επιβεβαίωση.\n\n";
$message .= "Με εκτίμηση,\n{$siteName} Team\n";

// Headers
$fromEmail = "info@mistvape.gr"; // αλλάξτε στο δικό σας domain
$headers = "From: {$siteName} <{$fromEmail}>\r\n";
$headers .= "Reply-To: {$fromEmail}\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// attempt to send
$sent = mail($email, $subject, $message, $headers);

if ($sent) {
  // επίσης στέλνουμε αντίγραφο στην επιχειρησιακή διεύθυνση (προαιρετικά)
  // mail('orders@mistvape.gr', 'Νέα παραγγελία - αντιγραφή', $message, $headers);

  echo "Η παραγγελία καταχωρήθηκε και στάλθηκε απόδειξη στο email σας.";
} else {
  // Αν αποτύχει το mail(), προτείνουμε SMTP/PHPMailer
  echo "Σφάλμα: Δεν μπόρεσε να σταλεί το email. Αν το πρόβλημα επιμένει, ρύθμισε SMTP ή ζήτησε έκδοση με PHPMailer.";
}
?>
