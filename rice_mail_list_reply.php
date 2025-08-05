<?php
//----------------------------------------------------------------------------------------------
// 共通処理
//----------------------------------------------------------------------------------------------
//ファイル読込
require_once("./lib/comm.php");
require_once("./lib/define.php");
require_once("./lib/dbaccess.php");
require_once("./lib/html.php");

//タイムゾーン
date_default_timezone_set('Asia/Tokyo');

//オブジェクト生成
$html = new html();
$comm = new comm();
$dba = new dbaccess();

//実行プログラム名取得
$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
$prgname = "メーリス送信";
$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

//データベース接続
$db = "";
$result = $dba->mysql_con($db);

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
</head>
<body>
	<?php
		$phrase2 = "＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝\n";
		$phrase2 .= "こちらのメールは送信専用アドレスより配信されています。\n";
		$phrase2 .= "ご返信いただいても内容の確認・返信ができませんので、あらかじめご了承ください。\n";
		$phrase2 .= "＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝\n";
		$phrase2 .= "\n";
		$sign = "\n";
		$sign .= "\n";
		$sign .= "\n";
		$sign .= "─────────────────\n";
		$sign .= "日本電子機器補修協会　主食共同購入部\n";
		$sign .= "　TEL：050-5272-9665\n";
		$sign .= "　URL: https://jemtcnet.jp/kome/\n";
		$sign .= "─────────────────\n";

		//送信先を取得
		$query = " SELECT GROUP_CONCAT(B.email separator  ',') as bcc ";
		$query .= " FROM php_rice_subscription A ";
		$query .= " LEFT OUTER JOIN php_rice_personal_info B ON A.personal_idxnum=B.idxnum ";
		$query .= " WHERE B.delflg=0 ";
		$query .= " AND '".$today."' BETWEEN DATE_FORMAT(date_s, '%Y-%m-01') AND date_e ";
		if($g_post['送信先'] <> "全員"){
			$query .= " AND A.category = '".$m_send."'";
		}
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$m_bcc = $g_post['bcc'].",".$row['bcc'];
		}
	//	$m_bcc = "haruka.ihdc@gmail.com,haruka.ihdc@icloud.com";
		
		require_once( './PHPMailer/PHPMailerAutoload.php' );
		
		mb_language("Japanese");
		mb_internal_encoding("UTF-8");
		$from_name = "精米倶楽部インフォメーションセンター(送信専用)";
		$from_addr = "no-reply-kome@jemtcnet.jp";
		$smtp_user = "no-reply-kome@jemtcnet.jp";
		$m_bcc = $m_bcc.",".$from_addr;
		$array_bcc = explode(",", $m_bcc);
		$smtp_password = "DhbKXqrF2Yzb";
		
		$mail = new PHPMailer();
		$mail->IsSMTP();
		$mail->SMTPDebug = 0; 
		$mail->SMTPAuth = true;
		$mail->CharSet = 'utf-8';
		$mail->Host = "sv2039.xserver.jp";
		$mail->Port = 587;
		$mail->IsHTML(false);
		$mail->Username = $smtp_user;
		$mail->Password = $smtp_password; 
		$mail->SetFrom($from_addr,$from_name);
		$mail->From = $from_addr;
		$m_title = str_replace("\\", "", $m_title);
		$mail->Subject = $m_title;
		$m_contents = str_replace("\\", "", $m_contents);
		$m_contents = $phrase2.$m_contents.$sign;
		$mail->Body = $m_contents;
		$mail->AddAddress($m_email);
		$mail->AddCc($m_cc); //ccアドレス
		//BCc
		foreach($array_bcc as $val){
			$mail->AddBcc($val); //Bccアドレス
		}
		
		$mail->AddAttachment($m_file);//添付ファイル

		if( !$mail -> Send() ){
			$mail_result = "メールの送信に失敗しました。<br>";
			$mail_result .= "Mailer Error: " . $mailer->ErrorInfo;
		}else {
			$_update = "UPDATE php_rice_mail_list" ;
			$_update .= " SET upddt = NOW()";
			$_update .= " , send_dt = NOW()" ;
			$_update .= " , bcc = '$m_bcc'" ;
			$_update .= " , updcount = updcount + 1" ;
			$_update .= " , sendflg = 1" ;
			$_update .= " WHERE idxnum = " . sprintf("'%s'", $g_idxnum);
			$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
			//データ追加実行
			if (! $db->query($_update)) {
				$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				return false;
			}
			$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
			$mail_result =  "メールの送信が完了しました。";
		}
	?>
	
	
	<div class="formWrap" align="center">
		<h2><?php echo $mail_result; ?></h2>
	</div>
</body>
</html>