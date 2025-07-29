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
$prgname = "メール送信";
$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

//データベース接続
$db = "";
$result = $dba->mysql_con($db);

//引数取得
$g_idxnum=$_GET['idxnum'];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
</head>
<body>
	<?php
		require_once( './PHPMailer/PHPMailerAutoload.php' );
		
		mb_language("Japanese");
		mb_internal_encoding("UTF-8");
		$from_name = "精米倶楽部インフォメーションセンター";
		$from_addr = "kome@jemtcnet.jp";
		$smtp_user = "kome@jemtcnet.jp";
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
		$mail->From     = $from_addr;
		$m_title = str_replace("\\", "", $m_title);
		$mail->Subject = $m_title;
		$m_contents = str_replace("\\", "", $m_contents);
		$mail->Body = $m_contents;
		$mail->AddAddress($m_email);
		$mail->AddCc($m_cc); //ccアドレス
		$mail->AddBcc($from_addr); //Bccアドレス
		$mail->AddAttachment($m_file);//添付ファイル

		if( !$mail -> Send() ){
			$mail_result = "メールの送信に失敗しました。<br>";
			$mail_result .= "Mailer Error: " . $mailer->ErrorInfo;
		}else {
			$mail_result =  "メールの送信が完了しました。";
			header("Location:./rice_mail_form.php?finish=send&idxnum=".$g_idxnum);
		}
	?>
	
	
	<div class="formWrap" align="center">
		<h2><?php echo $mail_result; ?></h2>
		<input type="button" name="name" value="閉じる" onclick="window.close()">
	</div>
</body>
</html>