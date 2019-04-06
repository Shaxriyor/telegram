<?php
ob_start();
define('API_KEY','885872068:AAHkz5EMrVHcouNoQWh1NyfjCDJ2ODbb3iA');
echo "https://api.telegram.org/bot".API_KEY."/setwebhook?url=".$_SERVER['SERVER_NAME']."".$_SERVER['SCRIPT_NAME'];
function bot($method,$datas=[]){
    $url = "https://api.telegram.org/bot".API_KEY."/".$method;
$ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
    $res = curl_exec($ch);
    if(curl_error($ch)){
        var_dump(curl_error($ch));
    }else{
        return json_decode($res);
    }
}
$update = json_decode(file_get_contents('php://input'));
$message = $update->message;
$chat_id = $message->chat->id;
$text = $message->text;
$chat_id2 = $update->callback_query->message->chat->id;
$message_id = $update->callback_query->message->message_id;
$data = $update->callback_query->data;
$from_id = $message->from->id;
$name = $update->message->from->first_name;
$ssa = json_decode(file_get_contents('yuklandi.json'),1);
if($text != null){
	if($text == '/start'){
		bot('sendMessage',[
				'chat_id'=>$chat_id,
				'text'=>'Привет! Просто отправь мне имя артиста и/или название композиции, и я найду эту песню для тебя!',
				'reply_markup'=>json_encode([
		      'inline_keyboard'=>[
		      	[['text'=>"Разработчик", 'url'=>"https://t.me/prokuror47"]],
		      ]
	      ])
		]);
	} elseif($text != (null and '/start')){
		if(preg_match('/(http(s|):|)\/\/(www\.|)yout(.*?)\/(embed\/|watch.*?v=|)([a-z_A-Z0-9\-]{11})/i', $text)){
			 preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $text, $match);
    	 $id = $match[1];
    	  bot('sendphoto',[
			      'chat_id'=>$chat_id,
        		'photo'=>$text,
        		'caption'=>"📌┇ Как скачать?",
            'reply_markup'=>json_encode([
               	'inline_keyboard'=>[
               [['text'=>'🎬┇ Видео','callback_data'=>"vi##$id"] ]
               ]
               ])]);
		} else {
			$item = json_decode(file_get_contents("https://www.googleapis.com/youtube/v3/search?part=snippet&q=".urlencode($text)."&type=video&key=AIzaSyBdKFK59eajOVchi0TpuKkNGbYx4ZpiduI&maxResults=10"))->items;
	  	$keyboard["inline_keyboard"]=[];
	  	for ($i=0; $i < count($item); $i++) { 
	  		$keyboard["inline_keyboard"][$i] = [['text'=>$item[$i]->snippet->title,'callback_data'=>'dl##'.$item[$i]->id->videoId]];
	  	}
	  	$reply_markup=json_encode($keyboard);
	  	bot('sendMessage',[
	  		'chat_id'=>$chat_id,
	  		'text'=>'РЕЗУЛЬТАТЫ ПОИСКА:',
	  		'reply_markup'=>$reply_markup
	  	]);
		}
	}
}
if($data != null){
	$yt = explode('##', $data);
		if($yt[0] == 'vi'){
		$get = json_decode(file_get_contents("https://wapis.ga/yt/?url=https://www.youtube.com/watch?v=".$yt[1]));
		$info = $get->result;
		$title = $info->info->title;
		$url = $get; 
	  bot('sendMessage',[
      'chat_id'=>$chat_id2,
      'text'=>'Скачивается...',
     ]);
     
     
     $size = $url->result->video[2]->sizebits;
     if($size > 50000000000){
     	bot('sendMessage',[
     		'chat_id'=>$chat_id2,
     		'text'=>'🚫 Не скачиваем большие файлы!',
     		]);
     
     //} else {
     	file_put_contents($chat_id2.'.mp4',file_get_contents( $url->result->video[2]->url ));
	     $video = bot('sendvideo',[
	       'chat_id'=>$chat_id2,
	       'video'=>new CURLFile($chat_id2.'.mp4'),
	 			]);
	 			$ssa['video']['video#'.$yt[1]] = ['title'=>$info->info->title,'file_id'=>$video->result->video->file_id];
	 			file_put_contents('yuklandi.json', json_encode($ssa));
     } 
      unlink($chat_id2.'.mp4');
	}
}
if($update->inline_query != null){
	$yt = explode('#', $update->inline_query->query);
	 if($yt[0] == 'video' and $ssa['video'][$update->inline_query->query] != null){
		bot('answerInlineQuery',[
            'inline_query_id'=>$update->inline_query->id,    
            'results' =>json_encode([[
            	  'type'=>'video',
            	  'title'=>$ssa['video'][$update->inline_query->query]['title'],
            	  'caption'=>$ssa['video'][$update->inline_query->query]['title'],
            	  'video_file_id'=>$ssa['video'][$update->inline_query->query]['file_id'],
                'id'=>base64_encode(rand(5,555)),
            	]])
     ]);
	} else {
		$item = json_decode(file_get_contents("https://www.googleapis.com/youtube/v3/search?part=snippet&q=".urlencode($update->inline_query->query)."&type=video&key=AIzaSyBdKFK59eajOVchi0TpuKkNGbYx4ZpiduI&maxResults=10"))->items;
	  	 for($i=0;$i < count($item);$i++){
        $res[$i] = [
                'type'=>'article',
                'id'=>base64_encode(rand(5,555)),
                'thumb_url'=>$item[$i]->snippet->thumbnails->default->url,
                'title'=>$item[$i]->snippet->title,
                'input_message_content'=>['parse_mode'=>'HTML','message_text'=>"https://www.youtube.com/watch?v=".$item[$i]->id->videoId],
          ];
    }
	  	$r = json_encode($res);
    bot('answerInlineQuery',[
            'inline_query_id'=>$update->inline_query->id,    
            'results' =>($r)
        ]);
	}
}