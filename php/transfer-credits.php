<?
include_once 'utils.php';

$fromId = isset($_POST['from']) ? (is_numeric($_POST['from']) ? intval($_POST['from']) : getId($_POST['from'])) : $user['id'];
$toId = isset($_POST['to']) ? (is_numeric($_POST['to']) ? intval($_POST['to']) : getId($_POST['to'])) : $user['id'];
$amount = intval($_POST['amount']);
$key = $_POST['key'];
$msg = $connection->real_escape_string($_POST['msg']);

if($fromId < 1 || $toId < 1 || $amount < 1 || strlen($key) < 1 || $toId == $fromId){
  $connection->close();
  exit('{"success":false,"msg":"Invalid data"}');
} else if($key != getKey($fromId)){
  $connection->close();
  exit('{"success":false,"msg":"Invalid key"}');
} else if(getCredits($fromId) < $amount){
  $connection->close();
  exit('{"success":false,"msg":"Insufficient funds"}');
}

$query = $connection->prepare('
  UPDATE users SET credits =
  (CASE
      WHEN id = ? THEN credits + ?
      WHEN id = ? THEN credits - ?
      ELSE credits
    END)
');
$query->bind_param('iiii', $toId, $amount, $fromId, $amount);
$query->execute();
$query->close();

$query = $connection->prepare('INSERT INTO transactions (from_id, to_id, amount, message) VALUES (?,?,?,?)');
$query->bind_param('iiis', $fromId, $toId, $amount, $msg);
$query->execute();
$query->close();
$connection->close();

echo '{"success":true,"msg":"Credits transferred"}';
?>
