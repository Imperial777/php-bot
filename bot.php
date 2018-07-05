<?php 

define("VKAPI_VERSION", "5.80");
define("ACCESS_TOKEN", ""); // токен группы
define("USER_ACCESS_TOKEN", ""); // vk.cc/8eulLN
define('SERVICE_ACCESS_TOKEN', ''); // сервисный ключ доступа
define("CONFIRMATION_TOKEN", ""); // строка, которую должен вернуть сервер
define("SECRET_KEY", ""); // секретный ключ
define("GROUP_ID", 0); // ид группы
define("OWNER", 0); // ваш ид
define("CHAT_PEER_ID", 2000000000);
define("KEYBOARD_DEFAULT", '{"one_time": false, "buttons": [[{"action": {"type": "text", "payload": "{\"command\": \"green\"}", "label": "Green"}, "color": "positive"}, {"action": {"type": "text", "payload": "{\"command\": \"red\"}", "label": "Red"}, "color": "negative"}]]}'); // клавиатура

$data = json_decode(file_get_contents('php://input')); 

if($data->secret != SECRET_KEY) {
	header("Content-type:application/json");
	echo '{"error": {"error_code": 1, "error_msg": "Security error: secret is invalid"}}';
	exit();
}
if($data->secret != GROUP_ID) {
	header("Content-type:application/json");
	echo '{"error": {"error_code": 3, "error_msg": "Security error: group_id is invalid"}}';
	exit();
}

function vkapi($method, $params = array())
{
	if(!isset($params['access_token'])) $params['access_token'] = ACCESS_TOKEN;
	$params['v'] = VKAPI_VERSION;
	$query = http_build_query($params);
	$url = 'https://api.vk.com/method/'.$method.'?'.$query;
	$response = json_decode(file_get_contents($url), true);
	return $response;
}
function send($peer_id, $message, $attachment = '', $keyboard = '{"one_time": false, "buttons": []}')
{
	$params['peer_id'] = $peer_id;
	$params['message'] = $message;
	$params['attachment'] = $attachment;
	if($peer_id < 2000000000) $params['keyboard'] = $keyboard;
	$params['access_token'] = ACCESS_TOKEN;
	$params['v'] = VKAPI_VERSION;
	$get_params = http_build_query($params);

	$response = json_decode(file_get_contents('https://api.vk.com/method/messages.send?'.$get_params));
	if(isset($response->error)) return false;

	return true;
}
function getUser($user_id, $fields = 'sex', $name_case = 'nom')
{
	$params['user_ids'] = $user_id;
	$params['fields'] = $fields;
	$params['name_case'] = $name_case;
	$params['access_token'] = ACCESS_TOKEN;
	$params['v'] = VKAPI_VERSION;

	$response = json_decode(file_get_contents("https://api.vk.com/method/users.get?".http_build_query($params)), true);
	return $response['response'][0];
}

switch ($data->type) { 
	case 'confirmation':
		echo CONFIRMATION_TOKEN; 
		break;

	case 'message_new':
		echo 'ok';
		$peer_id = $data->object->peer_id;
		$user_id = $data->object->from_id;
		$payload = json_decode($data->object->payload);
		$message = $data->object->text;
		$message = str_replace(',', '', $message);
		$message = trim(preg_replace('|\s+|', ' ', trim(preg_replace('/\[club[0-9]{1,}\|[^]]{1,}]/','',$message))));
		$message = trim(preg_replace('|\s+|', ' ', trim(preg_replace('/\[id[0-9]{1,}\|[^]]{1,}]/','',$message))));
		$message = mb_strtolower($message, "UTF-8");
    
    if(empty($payload)) {
      if($message == 'v' || $message == 'версия')
        send($peer_id, "Example bot.", '', KEYBOARD_DEFAULT);
      elseif($peer_id < CHAT_PEER_ID)
        send($user_id, "&#129302; Упс... Я тебя не понимаю! Воспользуйся командами.", '', KEYBOARD_DEFAULT);
    } else {
      switch($payload->command) {
        case 'green':
          $user = getUser($user_id);
          send($peer_id, "{$user['first_name']}, ты нажал кнопку \"Green\"!", '', KEYBOARD_DEFAULT);
          break;
          
        case 'red':
          $user = getUser($user_id);
          send($peer_id, "{$user['first_name']}, ты нажал кнопку \"Green\"!", '', KEYBOARD_DEFAULT);
          break;
          
        default:
          send($user_id, "&#129302; Упс... Я тебя не понимаю! Воспользуйся командами.", '', KEYBOARD_DEFAULT);
          break;
      }
    }
		break;

	default:
		header("Content-type:application/json");
		echo '{"error": {"error_code": 2, "error_msg": "Unsupported event."}}';
		break;

}
