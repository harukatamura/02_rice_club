<?php header("Content-Type:text/html;charset=utf-8"); ?>
<?php //error_reporting(E_ALL | E_STRICT);
//=========================================================================================
// ■機能概要
//   ・infoメール受信→SQLへデータを格納
//=========================================================================================

	error_reporting(0);
	//--------------------------------------------------
	// 共通処理
	//--------------------------------------------------
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

	//スパム防止のためのリファラチェック
	$Referer_check = 0;
	//リファラチェックを「する」場合のドメイン
	$Referer_check_domain = "forincs.com";
	
	//対象テーブル
	$table = "php_rice_mail";
	$table_detail = "php_rice_mail_detail";

	//本日日付
	$today = date('YmdHis');

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);
	$comm->ouputlog("php_rice_mail_getログ出力", $prgid, SYS_LOG_TYPE_DBUG);
	

	//=================================================
	//メールデータの取得
	//=================================================

	// 文字列取得関数
	function html_cut_syutoku($html_buf, $start_buf, $end_buf, $int_positon_cnt){
		if(strstr($html_buf, $start_buf)){
			$srt_position = strpos($html_buf, $start_buf, $int_positon_cnt);
			$srt_position = $srt_position + strlen($start_buf);
			$end_position = strpos($html_buf, $end_buf, $srt_position);
			$result_buf = substr($html_buf, $srt_position, $end_position-$srt_position);
		}else{
			$result_buf = "";
		}
		return $result_buf;
	}

	//メールのソースを変数に格納
	$html_buf= file_get_contents("php://stdin");
	
	//文字の種類を日本語、UTF-8に指定
	mb_language("Japanese");
	mb_internal_encoding("UTF-8");

	//送信元アドレス
	$start_buf="Return-Path: <";
	$end_buf=">";
	$email = html_cut_syutoku($html_buf,$start_buf,$end_buf,0);

	//件名
	$start_buf="Subject: ";
	$end_buf=":";
	$subject_buf = html_cut_syutoku($html_buf,$start_buf,$end_buf,0);
	$sub_cut = strrchr($subject_buf, "\n");
	$start_buf = "Subject: ";
	$end_buf = $sub_cut;
	$subject = html_cut_syutoku($html_buf,$start_buf,$end_buf,0);
	//デコード
	$subject = mb_decode_mimeheader($subject);
		$comm->ouputlog("subject=".$subject, $prgid, SYS_LOG_TYPE_DBUG);

	//本文
	$start_buf="Content-Type: text/plain;";
	$end_buf="Content-Type: text/html;";
	$body_buf = html_cut_syutoku($html_buf,$start_buf,$end_buf,0);
	$comm->ouputlog("body_buf：".$body_buf, $prgid, SYS_LOG_TYPE_INFO);

	//charsetを取得
	$start_buf='charset="';
	$end_buf='"';
	$charset = html_cut_syutoku($body_buf, $start_buf, $end_buf,0);
	if($charset == ""){
		$start_buf='charset=';
		$end_buf="\n";
		$charset = html_cut_syutoku($body_buf, $start_buf, $end_buf,0);
	}

	//encodingを取得
	$start_buf="Content-Transfer-Encoding: ";
	$end_buf="\n";
	$encoding = html_cut_syutoku($body_buf, $start_buf, $end_buf,0);

	//テキストを取得
	$start_buf="\n\n";
	$end_buf="--";
	$m_body = html_cut_syutoku($body_buf, $start_buf, $end_buf,0);
	$comm->ouputlog("m_body：".$m_body, $prgid, SYS_LOG_TYPE_INFO);

	//テキストのみのメールの場合の処理
	if($m_body == ""){
		$comm->ouputlog("テキストメール", $prgid, SYS_LOG_TYPE_INFO);
		
		$start_buf="Content-Type: text/plain;";
		$body_buf = strstr($html_buf,$start_buf);
		$comm->ouputlog("body_buf：".$body_buf, $prgid, SYS_LOG_TYPE_INFO);

		//charsetを取得
		$start_buf='charset="';
		$end_buf='"';
		$charset = html_cut_syutoku($body_buf, $start_buf, $end_buf,0);
		if($charset == ""){
			$start_buf='charset=';
			$end_buf="\n";
			$charset = html_cut_syutoku($body_buf, $start_buf, $end_buf,0);
		}
		mb_internal_encoding($charset);

		//encodingを取得
		$start_buf="Content-Transfer-Encoding: ";
		$end_buf="\n";
		$encoding = html_cut_syutoku($body_buf, $start_buf, $end_buf,0);

		//テキストを取得
		$start_buf="\n\n";
		$m_body = strstr($body_buf, $start_buf);
		$comm->ouputlog("m_body：".$m_body, $prgid, SYS_LOG_TYPE_INFO);
	}
	
	//エンコーディングの種類にあわせてデコードする
	if(strpos($encoding,'quoted') !== false){
		$body = quoted_printable_decode($m_body);
	}else if(strpos($encoding,'base64') !== false || strpos($encoding,'Base64') !== false){
		$body = base64_decode($m_body);
	}else{
		$body = $m_body;
	}
	//文字のエンコードがUTF-8以外の場合、エンコードし直す
	if(strpos($charset,'UTF-8') === false && strpos($charset,'utf-8') === false){
		$body = mb_convert_encoding($body, "UTF-8", "iso-2022-jp,Shift_JIS");
	}
	
	//取り込みできない文字列を置換する
	/*
	$body = str_replace('"', '', $body);
	$body = str_replace("'", "", $body);
	$body = str_replace("\\", "円", $body);
	*/
	$comm->ouputlog("body：".$body, $prgid, SYS_LOG_TYPE_INFO);
	
	//日付
	$start_buf="Date: ";
	$end_buf="+";
	$b_recdate = html_cut_syutoku($html_buf,$start_buf,$end_buf,0);
	$b_recdate = substr($b_recdate, 0, 25);
	$recdate = date("Y-m-d H:i:s",strtotime($b_recdate));

	//=================================================
	//SQLに書き込み
	//=================================================

	$ngword1 = "【JEMTC】ホームページお問い合わせ　※お問合せNo";
	$ngword2 = "お問い合わせフォームから送信されました";
	$ngword3 = "MAILER-DAEMON";
	$ngword4 = "迷惑メール一覧通知";
	$error_mail = "Mail System Error - Returned Mail";
	//1通目のメール・エラー通知メールはここでは格納しない
	if(strpos($subject, $ngword1) !== false || strpos($body, $ngword2) !== false){
		$comm->ouputlog("1通目のメールです", $prgid, SYS_LOG_TYPE_INFO);
	}else if(strpos($subject, $ngword4) !== false){
		$comm->ouputlog("エラー通知メールです", $prgid, SYS_LOG_TYPE_INFO);
	}else{
		//エラーメールの場合は
		if(strpos($email, $ngword3) !== false || strpos($subject, $error_mail) !== false){
			$comm->ouputlog("エラーログを格納します", $prgid, SYS_LOG_TYPE_INFO);
			$g_email = mb_substr($body,mb_strpos($body, "<")+1);
			$email = mb_substr($g_email,0,mb_strpos($g_email, ">"));
			$comm->ouputlog("email=".$email, $prgid, SYS_LOG_TYPE_INFO);
		}
		$comm->ouputlog("charset=".$charset, $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog("encoding=".$encoding, $prgid, SYS_LOG_TYPE_DBUG);
		//同じemailアドレスから送られてきている最新のメールのインデックスを調べる
		$query = "SELECT name, max(idxnum) as max_idxnum FROM " . $table . " WHERE email = '".$email."'";
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ取得エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			require_once(dirname(_FILE_).'/infomail_error_mail.php');
		}else {
			//あればインデックスに格納
			if ($rs->num_rows > 0) {
				while ($row = $rs->fetch_array()) {
					$m_idxnum = $row['max_idxnum'];
					$name = $row['name'];
				}
			}
		}
		
		//メールにNGワードが入っている場合、置換する
		/*
		$body = str_replace("'", "", $body);
		$body = str_replace("´", "", $body);
		$body = str_replace('"', '', $body);
		*/
		// エスケープ処理
		if ($subject != "") {
			$subject = addslashes($subject);
		}
		if ($body != "") {
			$body = addslashes($body);
		}
		if ($name != "") {
			$name = addslashes($name);
		}

		//=================================================
		//データ重複チェック
		//=================================================
		$query = "
			SELECT
				idxnum
			FROM
				$table_detail
			WHERE
				idxnum = '$m_idxnum'
			AND
				subject like '$subject'
			AND
				contents like '$body'
			limit 1
		";
		$comm->ouputlog("データ重複チェック 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$comm->ouputlog("データが重複しているため、処理終了", $prgid, SYS_LOG_TYPE_INFO);
			exit;
		}

		//データ更新
		$_insert = "INSERT INTO " . $table_detail;
		$_insert .= " (insdt, upddt, corredt,  mail_idx,  ";
		$_insert .= " email, name, category, subject, contents)";
		$_insert .= " VALUE ('$recdate', '$today', '$today', '$m_idxnum', ";
		$_insert .= " '$email', '$name', 'メール受信', '$subject', '$body' )";
		$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
		//データ追加実行
		if (!($rs = $db->query($_insert))) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			require_once(dirname(_FILE_).'/infomail_error_mail.php');
			return false;
		}
		
		//対応状況を返信有に更新
		$_insert = "UPDATE " . $table;
		$_insert .= " SET upddt = '$today' ";
		$_insert .= " , updcount = updcount + 1 ";
		$_insert .= " , status = 3 ";
		$_insert .= " WHERE idxnum = '$m_idxnum' ";
		$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
		//データ追加実行
		if (!($rs = $db->query($_insert))) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			require_once(dirname(_FILE_).'/infomail_error_mail.php');
			return false;
		}

		$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
		return true;
	}

?> 