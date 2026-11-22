<?php
// AxLive — releu GPS telefon → tabletă. Telefonul trimite poziția (POST), tableta o citește (GET).
// Pozițiile stau într-un fișier mic per cod, în folderul gpsdata (protejat de .htaccess).
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$c = isset($_GET['c']) ? $_GET['c'] : '';
if (!preg_match('/^[0-9]{4,8}$/', $c)) { http_response_code(400); echo '{"err":"cod invalid"}'; exit; }

$dir = __DIR__ . '/gpsdata';
if (!is_dir($dir)) {
  @mkdir($dir, 0755, true);
  @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
}
$f = $dir . '/p' . $c . '.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $in = json_decode(file_get_contents('php://input', false, null, 0, 2000), true);
  if (!is_array($in) || !isset($in['lat'], $in['lon']) || !is_numeric($in['lat']) || !is_numeric($in['lon'])) {
    http_response_code(400); echo '{"err":"date invalide"}'; exit;
  }
  $o = array(
    'lat' => (float)$in['lat'], 'lon' => (float)$in['lon'],
    'acc' => isset($in['acc']) && is_numeric($in['acc']) ? (float)$in['acc'] : null,
    't'   => isset($in['t']) && is_numeric($in['t']) ? (float)$in['t'] : round(microtime(true) * 1000),
    'srv' => round(microtime(true) * 1000)
  );
  file_put_contents($f, json_encode($o), LOCK_EX);
  // curățenie: coduri nefolosite de peste o zi
  if (mt_rand(0, 200) === 0) foreach (glob($dir . '/p*.json') as $g) if (filemtime($g) < time() - 86400) @unlink($g);
  echo '{"ok":1}'; exit;
}

if (!is_file($f)) { echo '{"none":1}'; exit; }
$s = file_get_contents($f);
$o = json_decode($s, true);
if (is_array($o)) $o['age'] = round(microtime(true) * 1000) - $o['srv'];
echo json_encode($o);
