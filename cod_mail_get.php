<?php header("Content-Type:text/html;charset=utf-8"); ?>
<?php //error_reporting(E_ALL | E_STRICT);
//=========================================================================================
// ■機能概要
//   ・通販サイト受注メール受信→DBへデータを格納・（JSPの場合ライセンスキー発行）
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
	$table = "php_jsp_license";

	//本日日付
	$today = date('YmdHis');

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);
	$comm->ouputlog("cod_mail_getログ出力", $prgid, SYS_LOG_TYPE_DBUG);
	
	$query = "SELECT modelnum, category, cash, cntflg, formid, desktopflg ";
	$query .= " FROM php_ecommerce_pc_info";
	$query .= " WHERE sales_name = 100001";
	$query .= " ORDER BY cash";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$category[$row['formid']] = $row['category'];
		$modelnum[$row['formid']] = $row['modelnum'];
		$cash[$row['formid']] = $row['cash'];
		$desktopflg[$row['formid']] = $row['desktopflg'];
	}
	$formid  = "";
	$timelist[] = "指定なし";
	$timelist["午前中"] = "0812";
	$timelist["午後～夕方"] = "1416";
	$timelist["夕方～夜間"] = "1820";
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
//	$body_buf = html_cut_syutoku($html_buf,$start_buf,$end_buf,0);
	$body_buf = mb_strstr($html_buf,$start_buf);
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
//	$m_body = html_cut_syutoku($body_buf, $start_buf, $end_buf,0);
	$m_body = mb_strstr($body_buf,$start_buf);
	$comm->ouputlog("m_body：".$m_body, $prgid, SYS_LOG_TYPE_INFO);

	//テキストのみのメールの場合の処理
	if($m_body == ""){
		$start_buf="Content-Type: text/plain;";
		$body_buf = strstr($html_buf,$start_buf);

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
	//日付
	$start_buf="Date: ";
	$end_buf="+";
	$b_recdate = html_cut_syutoku($html_buf,$start_buf,$end_buf,0);
	$b_recdate = substr($b_recdate, 0, 25);
	$recdate = date("Y-m-d H:i:s",strtotime($b_recdate));
	//フォームID
	$start_buf="フォームID";
	$end_buf="\n";
	$formid = html_cut_syutoku($body, $start_buf, $end_buf,0);
	$formid = str_replace(":", "", $formid);
	$formid = str_replace(" ", "", $formid);
	$comm->ouputlog("フォームID：".$formid, $prgid, SYS_LOG_TYPE_INFO);
	//金額
	$g_cash = 0;
	$start_buf="[合計]";
	$end_buf="円";
	$g_cash= html_cut_syutoku($body, $start_buf, $end_buf,0);
	$g_cash = str_replace(" ", "", $g_cash);
	$comm->ouputlog("金額：".$g_cash, $prgid, SYS_LOG_TYPE_INFO);
	//台数
	$g_buynum = 1;
	$start_buf="円)";
	$end_buf="台";
	$g_buynum= html_cut_syutoku($body, $start_buf, $end_buf,0);
	$g_buynum = str_replace(" ", "", $g_buynum);
	$comm->ouputlog("台数：".$g_buynum, $prgid, SYS_LOG_TYPE_INFO);
	//お名前
	$start_buf="お名前";
	$end_buf="\n";
	$name = html_cut_syutoku($body, $start_buf, $end_buf,0);
	$name = str_replace(":", "", $name);
	$name = str_replace(" ", "", $name);
	$comm->ouputlog("お名前：".$name, $prgid, SYS_LOG_TYPE_INFO);
	//ふりがな
	$start_buf="フリガナ";
	$end_buf="\n";
	$ruby = html_cut_syutoku($body, $start_buf, $end_buf,0);
	$ruby = str_replace(":", "", $ruby);
	$ruby = str_replace(" ", "", $ruby);
	$comm->ouputlog("フリガナ：".$ruby, $prgid, SYS_LOG_TYPE_INFO);
	//電話番号
	$start_buf="電話番号";
	$end_buf="\n";
	$phonenum = html_cut_syutoku($body, $start_buf, $end_buf,0);
	$phonenum = str_replace(":", "", $phonenum);
	$phonenum = str_replace(" ", "", $phonenum);
	$comm->ouputlog("電話番号：".$phonenum, $prgid, SYS_LOG_TYPE_INFO);
	//電話番号→ご連絡先となっている場合
	if($phonenum == ""){
		$start_buf="ご連絡先";
		$end_buf="\n";
		$phonenum = html_cut_syutoku($body, $start_buf, $end_buf,0);
		$phonenum = str_replace(":", "", $phonenum);
		$phonenum = str_replace(" ", "", $phonenum);
		$comm->ouputlog("電話番号：".$phonenum, $prgid, SYS_LOG_TYPE_INFO);
	}
	//メールアドレス
	$start_buf="メールアドレス";
	$end_buf="\n";
	$email = html_cut_syutoku($body, $start_buf, $end_buf,0);
	$email = str_replace(":", "", $email);
	$email = str_replace(" ", "", $email);
	$comm->ouputlog("メールアドレス：".$email, $prgid, SYS_LOG_TYPE_INFO);
	//ログNo
	$start_buf="ログ件数";
	$end_buf="\n";
	$order_num = html_cut_syutoku($body, $start_buf, $end_buf,0);
	$order_num = str_replace(":", "", $order_num);
	$order_num = str_replace(" ", "", $order_num);
	$comm->ouputlog("オーダーNo：".$order_num, $prgid, SYS_LOG_TYPE_INFO);
	//住所
	$start_buf="[郵便番号] : 〒";
	$end_buf="\n";
	$postcd = html_cut_syutoku($body, $start_buf, $end_buf,0);
	$postcd1 = mb_substr($postcd,0,3);
	$postcd2 = mb_substr($postcd,-4);
	$start_buf="[都道府県] : ";
	$end_buf="\n";
	$address1 = html_cut_syutoku($body, $start_buf, $end_buf,0);
	$start_buf="[市区町村] : ";
	$end_buf="\n";
	$address2 = html_cut_syutoku($body, $start_buf, $end_buf,0);
	$start_buf="[町名番地] : ";
	$end_buf="\n";
	$address3 = html_cut_syutoku($body, $start_buf, $end_buf,0);
	$start_buf="[建物名]   : ";
	$end_buf="\n";
	$address4 = html_cut_syutoku($body, $start_buf, $end_buf,0);
	$comm->ouputlog("住所：".$postcd."　".$address1.$address2.$address3.$address4, $prgid, SYS_LOG_TYPE_INFO);
	//着希望時間
	$start_buf="時間帯\n";
	$end_buf="お届け";
	$specified_times = html_cut_syutoku($body, $start_buf, $end_buf,0);
	$comm->ouputlog("着希望時間：".$specified_times, $prgid, SYS_LOG_TYPE_INFO);
	$option_han = "なし";
//	$option_han = "無線マウス・JSP";
	if($formid == "S95618275" && $g_cash > 74800){
		$option_han = "モニタ・マウス・キーボード";
	}
	
	if($modelnum[$formid] == "JSP"){
		$status = 9;
		$output_flg = 3;
	}else{
		$status = 1;
		$output_flg = 0;
	}
	//=================================================
	//SQLに書き込み
	//=================================================
	$getword1 = "【フォームズ】投稿通知メール";
	$getword4 = "Re:";
	$idxnum = 0;
	//既存顧客通販サイトからの注文メールの場合のみ、対応
	if($formid <> ""){
		$query = "SELECT modelnum, formid, formid_fan ";
		$query .= " FROM php_ecommerce_pc_info";
		$query .= " WHERE sales_name = 100001";
		$query .= " AND formid = '$formid'";
		$query .= " OR formid_fan = '$formid'";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$g_formid =  $row['formid'];
			$g_formid_fan =  $row['formid_fan'];
		}
		if((strpos($subject, $getword1) !== false || $formid == $g_formid) && $modelnum[$formid] <> "" && strpos($subject, $getword4) == false){
			$comm->ouputlog("==== 新規顧客用通販サイトでの注文が入りました。 ====", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog("charset=".$charset, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog("encoding=".$encoding, $prgid, SYS_LOG_TYPE_DBUG);
			
			//メールにNGワードが入っている場合、置換する
			$body = str_replace("'", "", $body);
			
			//通販注文テーブルにデータを格納
			if($desktopflg[$formid] == 0){
				//ノートPCの場合、まとめて格納
				$_insert = "INSERT php_telorder__ ";
				$_insert .= " ( insdt, upddt, status, output_flg, buynum, category, receptionday, cash, name, ruby, phonenum1, postcd1, postcd2, address1, address2, address3, mailaddress, sales_name, locale, staff, p_way, modelnum, specified_times,order_num, option_han, reception_telnum)";
				$_insert .= " VALUES ";
				$_insert .= "  ('$today', '$today', ".$status.", ".$output_flg.", '".$g_buynum."', '".$category[$formid]."', '$today', ".$g_cash.", '$name', '$ruby', '$phonenum', '$postcd1', '$postcd2', '$address1', '".$address2.$address3."', '$address4', '$email', '100001', 'ネット通販', 'NS', '2', '".$modelnum[$formid]."','".$timelist[$specified_times]."', '".$formid.$order_num."','".$option_han."', '新規')";
				$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
				$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
				//データ追加実行
				if (!($rs = $db->query($_insert))) {
					$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					require_once(dirname(_FILE_).'/codmail_error_mail.php');
					return false;
				}
				$t_idx = 0;
				//インデックス取得
				$t_idx = mysqli_insert_id($db);
				$_update = "UPDATE php_telorder__";
				$_update .= " SET upddt = ".sprintf("'%s'", $today);
				$_update .= " , updcount = updcount + 1 ";
				$_update .= " , t_idx  = " . sprintf("'%s'", $t_idx);
				$_update .= " WHERE idxnum  = " . sprintf("'%s'", $t_idx);
				$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
				$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
				//データ追加実行
				if (!($rs = $db->query($_update))) {
					$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					require_once(dirname(_FILE_).'/codmail_error_mail.php');
					return false;
				}
				$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
			}else{
				if($g_buynum < 1){
					$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					//require_once(dirname(_FILE_).'/codmail_error_mail.php');
					return false;
				}
				//デスクトップPCの場合、それぞれ格納
				for($i=0; $i<$g_buynum; ++$i){
					$_insert = "INSERT php_telorder__ ";
					$_insert .= " ( insdt, upddt, status, output_flg, buynum, category, receptionday, cash, name, ruby, phonenum1, postcd1, postcd2, address1, address2, address3, mailaddress, sales_name, locale, staff, p_way, modelnum, specified_times,order_num, option_han, reception_telnum)";
					$_insert .= " VALUES ";
					$_insert .= "  ('$today', '$today', ".$status.", ".$output_flg.", '1', '".$category[$formid]."', '$today', ".$g_cash/$g_buynum.", '$name', '$ruby', '$phonenum', '$postcd1', '$postcd2', '$address1', '".$address2.$address3."', '$address4', '$email', '100001', 'ネット通販', 'NS', '2', '".$modelnum[$formid]."','".$timelist[$specified_times]."', '".$formid.$order_num."','".$option_han."', '新規')";
					$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
					//データ追加実行
					if (!($rs = $db->query($_insert))) {
						$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						require_once(dirname(_FILE_).'/codmail_error_mail.php');
						return false;
					}
					if($i==0){
						$t_idx = 0;
						//インデックス取得
						$t_idx = mysqli_insert_id($db);
					}
					$g_idx = mysqli_insert_id($db);
					$_update = "UPDATE php_telorder__ ";
					$_update .= " SET upddt = ".sprintf("'%s'", $today);
					$_update .= " , updcount = updcount + 1 ";
					$_update .= " , t_idx  = " . sprintf("'%s'", $t_idx);
					$_update .= " WHERE idxnum  = " . sprintf("'%s'", $g_idx);
					$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
					//データ追加実行
					if (!($rs = $db->query($_update))) {
						$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						require_once(dirname(_FILE_).'/codmail_error_mail.php');
						return false;
					}
					$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
				}
			}
			
			//JSPの場合、ライセンスキーを発行
	/*		if($modelnum[$formid] == "JSP"){
				//ライセンスをキープする
				$_update = "UPDATE " . $table;
				$_update .= " SET upddt = ".sprintf("'%s'", $today);
				$_update .= " , updcount = updcount + 1 ";
				$_update .= " , name  = " . sprintf("'%s'", $name);
				$_update .= " , email  = " . sprintf("'%s'", $email);
				$_update .= " , order_num  = " . sprintf("'%s'", $order_num);
				$_update .= " , phonenum  = " . sprintf("'%s'", $phonenum);
				$_update .= " , o_date  = " . sprintf("'%s'", $today);
				$_update .= " , status = 2";
				$_update .= " , t_idx  = " . sprintf("'%s'", $t_idx);
				$_update .= " WHERE delflg = 0";
				$_update .= " AND ((status = 1)";
		//		$_update .= " OR (status = 0 AND l_date  > " . sprintf("'%s'", $today).")";
				$_update .= " )ORDER BY status, l_date, idxnum";
				$_update .= " LIMIT 1";
				$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
				$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
				//データ追加実行
				if (!($rs = $db->query($_update))) {
					$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					require_once(dirname(_FILE_).'/infomail_error_mail.php');
					return false;
				}
				$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);

				//データ確認
				$query = "SELECT idxnum, key1, key2, key3, key4, key5 ";
				$query .= " FROM " . $table;
				$query .= " WHERE status = 2";
				$query .= " AND name  = " . sprintf("'%s'", $name);
				$query .= " ORDER BY idxnum";
				$query .= " LIMIT 1";
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs = $db->query($query))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				while ($row = $rs->fetch_array()) {
					$jsp_key = $row['key1']."-".$row['key2']."-".$row['key3']."-".$row['key4']."-".$row['key5'];
					$idxnum = $row['idxnum'];
				}
				$comm->ouputlog("==== ライセンスキー発行 ====", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($jsp_key, $prgid, SYS_LOG_TYPE_INFO);
				//メール送信
				require_once(dirname(_FILE_).'/jsp_license_mail.php');
				$comm->ouputlog("==== メール送信完了 ====", $prgid, SYS_LOG_TYPE_INFO);
				//ライセンスが正常に発行できた場合
				if($idxnum > 0){
					//データ更新
					$_update = "UPDATE " .$table;
					$_update .= " SET upddt = ".sprintf("'%s'", $today);
					$_update .= " , updcount = updcount + 1 ";
					$_update .= " , status = 9";
					$_update .= " , e_date = ".sprintf("'%s'", $today);
					$_update .= " WHERE idxnum = ".sprintf("'%s'", $idxnum);
					$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
					//データ追加実行
					if (!($rs = $db->query($_update))) {
						$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						require_once(dirname(_FILE_).'/infomail_error_mail.php');
						return false;
					}
				//ライセンスが正常に発行できなかった場合
				}else{
					//テーブルにデータを登録する
					$_insert = "INSERT INTO " .$table;
					$_insert .= " (insdt, upddt, status, name, order_num, o_date, email, phonenum) ";
					$_insert .= " VALUES";
					$_insert .= " (".sprintf("'%s'", $today).",".sprintf("'%s'", $today).",3,".sprintf("'%s'", $name).",".sprintf("'%s'", $order_num).",".sprintf("'%s'", $today).",".sprintf("'%s'", $email).",".sprintf("'%s'", $phonenum).")";
					$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
					//データ追加実行
					if (!($rs = $db->query($_insert))) {
						$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						require_once(dirname(_FILE_).'/infomail_error_mail.php');
						return false;
					}
				}
			}
	*/	}
	}else{
		$comm->ouputlog("===========フォームズ 取り込みエラー！===========", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog("フォームIDを取得できませんでした。エラーメールを送信します。", $prgid, SYS_LOG_TYPE_DBUG);
		require_once(dirname(_FILE_).'/codmail_error_mail.php');
	}
	return true;

?> 