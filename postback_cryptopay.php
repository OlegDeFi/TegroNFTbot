<?php 
$data_init = file_get_contents('php://input');
$data = json_decode($data_init, true);

include "config.php";

include "global.php";
$link = mysqli_connect($hostName, $userName, $password, $databaseName) or die ("Error connect to database");
mysqli_set_charset($link, "utf8");

###########SAVE DATA############
$date_time = date("j-m-Y G:i");
$results = "

=========$date_time========
";
$results .= print_r($data, true);

if($file = fopen("debug.txt", "a+")){
		fputs($file, $results);
		fclose($file);
} // end frite to file
###########SAVE DATA############

if($data['update_type'] == 'invoice_paid'){

	$p = explode(":", $data['payload']['payload']);
	$chat_id = $p[0];
	$nfttype = $p[1];
	$paidSumForNFT = $data['payload']['amount'];

// LANGUAGE
$str3select = "SELECT `lang` FROM `users` WHERE `chatid`='$chat_id'";
$result3 = mysqli_query($link, $str3select);
$row3 = @mysqli_fetch_object($result3);
if($row3->lang != ''){
	$langcode = $row3->lang;
}else{
	$langcode = 0;	
}
require "lang.php";
for ($i = 0; $i < count($text); $i++) {
	for ($k = 0; $k < count($text[$i]); $k++) {
		$text[$i][$k] = str_replace("&#13;&#10;", "
", $text[$i][$k]);
		$text[$i][$k] = str_replace("&#9;", "", $text[$i][$k]);
		$text[$i][$k] = str_replace("&#60;", "<", $text[$i][$k]);
		$text[$i][$k] = str_replace("&#62;", ">", $text[$i][$k]);
		$text[$i][$k] = str_replace("&#39;", "'", $text[$i][$k]);
		$text[$i][$k] = str_replace("", "", $text[$i][$k]);						
	} // end FOR
} // end FOR	
// LANGUAGE

	if(preg_match("/^([0-9])+$/", $nfttype)){
	// if WIN
		
		$case = $nfttype;
		require "roulette.php";
		roulette($case, $paidSumForNFT, "CryptoPayBot");
	
	}else{
	// if buy NFT	

		if($nfttype == "blogger") {$rate = $BloggerNFT;}
		elseif($nfttype == "custom") {$rate = $Blogger3D;}
		elseif($nfttype == "nude") {$rate = $NFTNude;}
		
		$ssum = $paidSumForNFT/$rate;
		$gotNFT = number_format($ssum, 2, '.', ''); 
		
		$str16select = "SELECT * FROM `nft` WHERE `chatid`='$chat_id'";
		$result16 = mysqli_query($link, $str16select);
		if(mysqli_num_rows($result16) == 0){
			if($nfttype == "blogger"){
				$nfttype2 = "blogger";
			}elseif($nfttype == "custom"){
				$nfttype2 = "custom3d";				
			}elseif($nfttype == "nude"){
				$nfttype2 = "nude";				
			}			
			$str2ins = "INSERT INTO `nft` (`chatid`,`".$nfttype2."`) VALUES ('$chat_id','$gotNFT')";
			mysqli_query($link, $str2ins);
		}else{
			$row16 = @mysqli_fetch_object($result16);
			if($nfttype == "blogger"){
				$oldsum = $row16->blogger;
				$nfttype2 = "blogger";
			}elseif($nfttype == "custom"){
				$oldsum = $row16->custom;
				$nfttype2 = "custom3d";				
			}elseif($nfttype == "nude"){
				$oldsum = $row16->nude;								
				$nfttype2 = "nude";				
			}
			$newsum = $oldsum + $gotNFT;					
			$str11upd = "UPDATE `nft` SET `".$nfttype2."`='".$newsum."' WHERE `chatid`='$chat_id'";
			mysqli_query($link, $str11upd);
		}
	
		########## REF FEE ##########
		$str12select = "SELECT * FROM `users` WHERE `chatid`='$chat_id'";
		$result12 = mysqli_query($link, $str12select);
		$row12 = @mysqli_fetch_object($result12);	
		
		$earnRefNFT = $gotNFT / 100 * $NFTRefPercent * $rate;
		
		if($row12->ref > 1){
			$str10upd = "UPDATE `users` SET `refbalance`=`refbalance`+$earnRefNFT WHERE `chatid`='".$row12->ref."'";
			mysqli_query($link, $str10upd);	
		}
		########## REF FEE ##########
	
		######## SAVE TRANSACTION ###########
		if($nfttype == "blogger"){
			$cat = $gotNFT;
			$dog = 0;
			$nude = 0;
		}elseif($nfttype == "custom"){
			$cat = 0;
			$dog = $gotNFT;	
			$nude = 0;					
		}elseif($nfttype == "nude"){
			$cat = 0;
			$dog = 0;										
			$nude = $gotNFT;					
		}
		$date_time = date("j-m-Y G:i");
		$str2ins = "INSERT INTO `transactions` (`chatid`,`sender`,`date_time`,`blogger`,`custom`,`nude`) VALUES ('$chat_id','$senderid','$date_time','$cat','$dog','$nude')";
		mysqli_query($link, $str2ins);
		######## SAVE TRANSACTION ###########
		
		$response = array(
			'chat_id' => $chat_id, 
			'text' => "Success: your payment received!",
			'parse_mode' => 'HTML');	
		sendit($response, 'sendMessage');					
	}
}

function sendit($response, $restype){
	$ch = curl_init('https://api.telegram.org/bot' . TOKEN . '/'.$restype);  
	curl_setopt($ch, CURLOPT_POST, 1);  
	curl_setopt($ch, CURLOPT_POSTFIELDS, $response);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_exec($ch);
	curl_close($ch);	
}

function send($id, $message, $keyboard) {   
		
		//Удаление клавы
		if($keyboard == "DEL"){		
			$keyboard = array(
				'remove_keyboard' => true
			);
		}
		if($keyboard){
			//Отправка клавиатуры
			$encodedMarkup = json_encode($keyboard);
			
			$data = array(
				'chat_id'      => $id,
				'text'     => $message,
				'reply_markup' => $encodedMarkup,
				'parse_mode' => 'HTML',
				'disable_web_page_preview' => True
			);
		}else{
			//Отправка сообщения
			$data = array(
				'chat_id'      => $id,
				'text'     => $message,
				'parse_mode' => 'HTML',
				'disable_web_page_preview' => True				
			);
		}
       
        $out = sendit($data, 'sendMessage');       
        return $out;
} 
?>