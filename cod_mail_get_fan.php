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
	$comm->ouputlog("cod_mail_get_fanログ出力", $prgid, SYS_LOG_TYPE_DBUG);
	
	$query = "SELECT modelnum, category, cash, cntflg, formid_fan, desktopflg ";
	$query .= " FROM php_ecommerce_pc_info";
	$query .= " WHERE sales_name = 100001";
	$query .= " ORDER BY cash";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$category[$row['formid_fan']] = $row['category'];
		$modelnum[$row['formid_fan']] = $row['modelnum'];
		$cash[$row['formid_fan']] = $row['cash'];
		$desktopflg[$row['formid_fan']] = $row['desktopflg'];
	}
	$formid  = "";
	$timelist[] = "指定なし";
	$timelist["午前中"] = "0812";
	$timelist["午後～夕方"] = "1416";
	$timelist["夕方～夜間"] = "1820";
	$timelist_r = array("8～12時" => "0812", "12～14時" => "1214", "14～16時" => "1416", "16～18時" => "1618", "18～20時" => "1820", "18～21時" => "1821", "19～21時" => "1921", "指定なし" => "");
	
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
	$comm->ouputlog("body：".$body, $prgid, SYS_LOG_TYPE_INFO);
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
	$start_buf="下さい\n";
	$end_buf="お届け";
	$specified_times = html_cut_syutoku($body, $start_buf, $end_buf,0);
	$comm->ouputlog("着希望時間：".$specified_times, $prgid, SYS_LOG_TYPE_INFO);
//	if($modelnum[$formid] == "Cel-4GB-HDD"){
//		$option_han = "脳トレゲーム";
//	}else{
//		$option_han = "無線マウス・JSP";
//	}
	$option_han = "なし";
	
	if($modelnum[$formid] == "JSP"){
		$status = 9;
		$output_flg = 3;
	}else{
		$status = 1;
		$output_flg = 0;
	}


	//本文を1行単位に配列にセットする
	$bodylist = explode("\n", $body);
	//本体選択
	$start_buf="■本体選択";
	for($i=0; $i<count($bodylist);$i++){
		if (false !== strpos($bodylist[$i], $start_buf)) {
			$value = $bodylist[$i+1];
			break;
		}
	}
	$category = $value;
	$comm->ouputlog("本体選択：".$value, $prgid, SYS_LOG_TYPE_INFO);

	//連絡事項など
	$start_buf="■連絡事項など";
	$next_item="[フォーム情報]";
	//取得値初期化
	$value = "";
	for($i=0; $i<count($bodylist);$i++){
		$comm->ouputlog("i=".$i, $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog("value=".$value, $prgid, SYS_LOG_TYPE_DBUG);
		//対象項目が見つかった場合
		if ($value <> ""){
			//次の項目を探す
			$flg=0;
			for($j=0; $j<count($next_item);$j++){
				if (false !== strpos($bodylist[$i+1], $next_item[$j])) {
					$flg=1;
					break;
				}
			}
			//次の項目が見つかった場合、ループを抜ける
			if ($flg==1){
				break;
			}
			//次の項目が見つからなかった場合、追加する
			$value .= $bodylist[$i+1] . "\n";

		//対象項目がまだ見つかっていない場合
		} else {
			$comm->ouputlog("bodylist=".$bodylist[$i], $prgid, SYS_LOG_TYPE_DBUG);
			if (false !== strpos($bodylist[$i], $start_buf)) {
				$value = $bodylist[$i+1] . "\n";
			}
		}
	}
	$remark = $value;
	$comm->ouputlog("連絡事項：".$value, $prgid, SYS_LOG_TYPE_INFO);

	//=================================================
	//SQLに書き込み
	//=================================================
	$getword1 = "【フォームズ】投稿通知メール";
	$getword4 = "Re:";
	$idxnum = 0;
	//既存顧客通販サイトからの注文メールの場合のみ、対応
	if($formid <> ""){
		//お米
		if($formid == "S16427895" || $formid == "S88742786" || $formid == "S83203637"){
		
			$query = "SELECT category, weight, tanka  ";
			$query .= " FROM php_rice_category ";
			$query .= " ORDER BY category, weight ";
			$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (!($rs = $db->query($query))) {
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
			while ($row = $rs->fetch_array()) {
				$rice_tanka[$row['category']][$row['weight']] = $row['tanka'];
			}
			$arr_sales_way = array("S16427895" => "web", "S88742786" => "tel", "S83203637" => "introduction");
			$table_p = "php_rice_personal_info";
			$table_s = "php_rice_subscription";
			$table_y = "php_rice_shipment";
			
			//申込内容
			$start_buf="■ご紹介者様のお名前（ご自分のお名前ではありません...";
			$end_buf="■";
			$introduction = html_cut_syutoku($body, $start_buf, $end_buf,0);
			$introduction = trim($introduction);
			if($name == "（ご自分のお名前ではありません..."){
				$start_buf="お名前         : ";
				$end_buf="メールアドレス";
				$name = html_cut_syutoku($body, $start_buf, $end_buf,0);
				$name = trim($name);
			}
			$start_buf="■コース";
			$end_buf="■";
			$category = html_cut_syutoku($body, $start_buf, $end_buf,0);
			$category = trim($category);
			$start_buf="■キロ数";
			$end_buf="ｋｇ";
			$weight = html_cut_syutoku($body, $start_buf, $end_buf,0);
			$weight = trim($weight);
			$weight = mb_convert_kana($weight, 'n');
			$start_buf="■お届け希望時間帯";
			$end_buf="時";
			$g_time = html_cut_syutoku($body, $start_buf, $end_buf,0);
			$g_time = trim($g_time)."時";
			$start_buf="■連絡事項など";
			$end_buf="[フォーム情報]";
			$remarks = html_cut_syutoku($body, $start_buf, $end_buf,0);
			//開始日取得　15日まで→当月開始、16日以降→翌月開始
			if(date('j',strtotime($today)) < 16){
				//当月の26日
				$date_s = date('Y-m-26', strtotime($today));
			}else{
				//翌月の26日
				$date_s = date('Y-m-26', strtotime('first day of next month', strtotime($today)));
			}
			//date_sの11ヶ月後
			$date_e = date('Y-m-26', strtotime('+11 months', strtotime($date_s)));
			
			//重複チェック
			$query = "SELECT idxnum ";
			$query .= " FROM php_rice_personal_info ";
			$query .= " WHERE delflg = 0 ";
			$query .= " AND (name = ".sprintf("'%s'", $name);
			$query .= " OR phonenum1 = ".sprintf("'%s'", $phonenum);
			$query .= " OR (address2 = ".sprintf("'%s'", $address2.$address3);
			$query .= " AND address3 = ".sprintf("'%s'", $address4).")";
			$query .= " )";
			$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (!($rs = $db->query($query))) {
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
			$g_idxnum = 0;
			while ($row = $rs->fetch_array()) {
				$g_idxnum = $row['idxnum'];
			}
			if($g_idxnum > 0){
				$remarks .= "重複注文の可能性があります。注文No.".$g_idxnum."のデータを確認してください。";
			}

			//テーブル項目取得
			$collist_p = $dba->mysql_get_collist($db, $table_p);
			$collist_s = $dba->mysql_get_collist($db, $table_s);
			$collist_y = $dba->mysql_get_collist($db, $table_y);
			
			//初期値設定
			$m_query1 = "";
			$m_query2 = "";
			$m_query1 .= " INSERT INTO ".$table_p;
			$m_query1 .= " ( ";
			$m_query2 .= " VALUES ( ";
			//登録日時
			$m_query1 .= $collist_p["登録日時"];
			$m_query2 .= sprintf("'%s'", $today);
			//更新日時
			$m_query1 .= "," . $collist_p["更新日時"];
			$m_query2 .= sprintf(",'%s'", $today);
			//個人情報
			$m_query1 .= "," . $collist_p["名前"];
			$m_query2 .= sprintf(",'%s'", $name);
			$m_query1 .= "," . $collist_p["電話番号1"];
			$m_query2 .= sprintf(",'%s'", $phonenum);
			$m_query1 .= "," . $collist_p["メールアドレス"];
			$m_query2 .= sprintf(",'%s'", $email);
			$m_query1 .= "," . $collist_p["郵便番号１"];
			$m_query2 .= sprintf(",'%s'", $postcd1);
			$m_query1 .= "," . $collist_p["郵便番号２"];
			$m_query2 .= sprintf(",'%s'", $postcd2);
			$m_query1 .= "," . $collist_p["都道府県"];
			$m_query2 .= sprintf(",'%s'", $address1);
			$m_query1 .= "," . $collist_p["ご住所"];
			$m_query2 .= sprintf(",'%s'", $address2.$address3);
			$m_query1 .= "," . $collist_p["建物名"];
			$m_query2 .= sprintf(",'%s'", $address4);
			$m_query1 .= "," . $collist_p["地域"];
			$m_query2 .= sprintf(",'%s'", $address2);
			$m_query1 .= "," . $collist_p["申込方法"];
			$m_query2 .= sprintf(",'%s'", $arr_sales_way[$formid]);
			$m_query1 .= "," . $collist_p["支払方法"];
			$m_query2 .= sprintf(",'%s'", "2");
			$m_query1 .= "," . $collist_p["オーダー番号"];
			$m_query2 .= sprintf(",'%s'", $order_num);
			$m_query1 .= "," . $collist_p["紹介者"];
			$m_query2 .= sprintf(",'%s'", $introduction);
			$m_query1 .= ")";
			$m_query2 .= ")";
			
			//DBに登録
			$_insert_p = "";
			$_insert_p = $m_query1.$m_query2;
			$comm->ouputlog("===データ更新ＳＱＬ===", $_insert_p, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog($_insert_p, $prgid, SYS_LOG_TYPE_DBUG);
			//データ追加実行
			if (!($rs = $db->query($_insert_p))) {
				$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				require_once(dirname(_FILE_).'/codmail_error_mail.php');
				return false;
			}
			//インデックス取得
			$g_idx = mysqli_insert_id($db);
			
			//初期値設定
			$m_query3 = "";
			$m_query4 = "";
			$m_query3 .= " INSERT INTO ".$table_s;
			$m_query3 .= " ( ";
			$m_query4 .= " VALUES ( ";
			//登録日時
			$m_query3 .= $collist_s["登録日時"];
			$m_query4 .= sprintf("'%s'", $today);
			//更新日時
			$m_query3 .= "," . $collist_s["更新日時"];
			$m_query4 .= sprintf(",'%s'", $today);
			//インデックス
			$m_query3 .= "," . $collist_s["個人情報インデックス"];
			$m_query4 .= sprintf(",'%s'", $g_idx);
			//申込内容
			$m_query3 .= "," . $collist_s["申込内容"];
			$m_query4 .= sprintf(",'%s'", "申込");
			$m_query3 .= "," . $collist_s["コース"];
			$m_query4 .= sprintf(",'%s'", $category);
			$m_query3 .= "," . $collist_s["量"];
			$m_query4 .= sprintf(",'%s'", $weight);
			$m_query3 .= "," . $collist_y["金額"];
			$m_query4 .= sprintf(",'%s'", $rice_tanka[$category][$weight]);
			$m_query3 .= "," . $collist_s["開始日"];
			$m_query4 .= sprintf(",'%s'", $date_s);
			$m_query3 .= "," . $collist_s["終了日"];
			$m_query4 .= sprintf(",'%s'", $date_e);
			$m_query3 .= "," . $collist_s["注文時備考"];
			$m_query4 .= sprintf(",'%s'", $remarks);
			$m_query3 .= ")";
			$m_query4 .= ")";
			//DBに登録
			$_insert_s = "";
			$_insert_s = $m_query3.$m_query4;
			$comm->ouputlog("===データ更新ＳＱＬ===", $_insert_s, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog($_insert_s, $prgid, SYS_LOG_TYPE_DBUG);
			//データ追加実行
			if (!($rs = $db->query($_insert_s))) {
				$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				require_once(dirname(_FILE_).'/codmail_error_mail.php');
				return false;
			}
			//インデックス取得
			$g_idx_s = mysqli_insert_id($db);
			
			//初期値設定
			$m_query5 = "";
			$m_query6 = "";
			$m_query7 = "";
			$m_query5 .= " INSERT INTO ".$table_y;
			$m_query5 .= " ( ";
			$m_query6 .= " ( ";
			//登録日時
			$m_query5 .= $collist_y["登録日時"];
			$m_query6 .= sprintf("'%s'", $today);
			//更新日時
			$m_query5 .= "," . $collist_y["更新日時"];
			$m_query6 .= sprintf(",'%s'", $today);
			//インデックス
			$m_query5 .= "," . $collist_y["申込インデックス"];
			$m_query6 .= sprintf(",'%s'", $g_idx_s);
			//申込内容
			$m_query5 .= "," . $collist_y["コース"];
			$m_query6 .= sprintf(",'%s'", $category);
			$m_query5 .= "," . $collist_y["量"];
			$m_query6 .= sprintf(",'%s'", $weight);
			$m_query5 .= "," . $collist_y["金額"];
			$m_query6 .= sprintf(",'%s'", $rice_tanka[$category][$weight]);
			$m_query5 .= "," . $collist_y["到着指定時間帯"];
			$m_query6 .= sprintf(",'%s'", $timelist_r[$g_time]);
			$m_query5 .= "," . $collist_y["配送日"];
			$m_query5 .= ") VALUES";
			for($i=0; $i<12; ++$i){
				$g_date = date('Y-m-26', strtotime('+'.$i.' months', strtotime($date_s)));
				if($m_query7 <> ""){
					$m_query7 .= ",";
				}
				$m_query7 .= $m_query6.sprintf(",'%s'", $g_date).")";
			}
			//DBに登録
			$_insert_y = "";
			$_insert_y = $m_query5.$m_query7.";";
			$comm->ouputlog("===データ更新ＳＱＬ===", $_insert_y, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog($_insert_y, $prgid, SYS_LOG_TYPE_DBUG);
			//データ追加実行
			if (!($rs = $db->query($_insert_y))) {
				$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				require_once(dirname(_FILE_).'/codmail_error_mail.php');
				return false;
			}
			//備考に記載がある場合は問い合わせに登録
			if($remarks <> ""){
				//テーブル名取得
				$table_m = "php_rice_mail";
				$table_md = "php_rice_mail_detail";
				//テーブル項目取得
				$collist_m = $dba->mysql_get_collist($db, $table_m);
				$collist_md = $dba->mysql_get_collist($db, $table_md);
				
				//初期値設定
				$m_query1 = "";
				$m_query2 = "";
				$m_query1 .= " INSERT INTO ".$table_m;
				$m_query1 .= " ( ";
				$m_query2 .= " VALUES ( ";
				//登録日時
				$m_query1 .= $collist_m["登録日時"];
				$m_query2 .= sprintf("'%s'", $today);
				//更新日時
				$m_query1 .= "," . $collist_m["更新日時"];
				$m_query2 .= sprintf(",'%s'", $today);
				//インデックス
				$m_query1 .= "," . $collist_m["個人情報インデックス"];
				$m_query2 .= sprintf(",'%s'", $g_idx);
				//質問内容
				$m_query1 .= "," . $collist_m["質問内容"];
				$m_query2 .= sprintf(",'%s'", $remarks);
				$m_query1 .= ")";
				$m_query2 .= ")";
				//DBに登録
				$_insert_m = "";
				$_insert_m = $m_query1.$m_query2;
				$comm->ouputlog("===データ更新ＳＱＬ===", $_insert_m, SYS_LOG_TYPE_DBUG);
				$comm->ouputlog($_insert_m, $prgid, SYS_LOG_TYPE_DBUG);
				//データ追加実行
				if (!($rs = $db->query($_insert_m))) {
					$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					require_once(dirname(_FILE_).'/codmail_error_mail.php');
					return false;
				}
			}
		
		}else{
			//=================================================
			//データ重複チェック
			//=================================================
			$query = "
				SELECT
					idxnum
				FROM
					php_telorder__
				WHERE
					sales_name = '100001'
				AND
					mailaddress = '$email'
				AND
					receptionday = '$recdate'
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

			$query = "SELECT modelnum, formid, formid_fan, cash, desktopflg ";
			$query .= " FROM php_ecommerce_pc_info";
			$query .= " WHERE sales_name = 100001";
			$query .= " AND formid = '$formid'";
			//$query .= " OR formid_fan = '$formid'";
			if ($category <> "") {
				$query .= " AND category = '$category'";
			}
			$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (!($rs = $db->query($query))) {
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
			while ($row = $rs->fetch_array()) {
				$g_formid =  $row['formid'];
				$g_formid_fan =  $row['formid_fan'];
				$cash =  $row['cash'];
				$modelnum =  $row['modelnum'];
				$desktopflg =  $row['desktopflg'];
			}
			if((strpos($subject, $getword1) !== false || $formid == $g_formid_fan) && $modelnum[$formid] <> "" ){
				$comm->ouputlog("==== 既存顧客用通販サイトでの注文が入りました。 ====", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog("charset=".$charset, $prgid, SYS_LOG_TYPE_DBUG);
				$comm->ouputlog("encoding=".$encoding, $prgid, SYS_LOG_TYPE_DBUG);
				
				//メールにNGワードが入っている場合、置換する
				$body = str_replace("'", "", $body);

				//金額の移送がない場合、マスタの金額をセット
				if (trim($g_cash) == "") {$g_cash = $cash;}

				//台数の移送がない場合、初期値(1)をセット
				if (trim($g_buynum) == "") {$g_buynum = 1;}

				//通販注文テーブルにデータを格納
				if($desktopflg == 0){
					//ノートPCの場合、まとめて格納
					$_insert = "INSERT php_telorder__ ";
					$_insert .= " ( insdt, upddt, status, output_flg, buynum, category, receptionday, cash, name, ruby, phonenum1, postcd1, postcd2, address1, address2, address3, mailaddress, sales_name, locale, staff, p_way, modelnum, specified_times,order_num, option_han, reception_telnum,remark)";
					$_insert .= " VALUES ";
					//$_insert .= "  ('$today', '$today', ".$status.", ".$output_flg.", '".$g_buynum."', '$category', '$today', ".$g_cash.", '$name', '$ruby', '$phonenum', '$postcd1', '$postcd2', '$address1', '".$address2.$address3."', '$address4', '$email', '100001', 'ネット通販', 'NS', '2', '".$modelnum."','".$timelist[$specified_times]."', '".$formid.$order_num."','".$option_han."', '既存','remark')";
					$_insert .= "  ('$today', '$today', '$status', '$output_flg', '$g_buynum', '$category', '$recdate', '$g_cash', '$name', '$ruby', '$phonenum', '$postcd1', '$postcd2', '$address1', '".$address2.$address3."', '$address4', '$email', '100001', 'ネット通販', 'NS', '2', '$modelnum','$timelist[$specified_times]', '".$formid.$order_num."','$option_han', '既存','$remark')";
					$comm->ouputlog("カテゴリ=".$category, $prgid, SYS_LOG_TYPE_DBUG);
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
					$_update = "UPDATE php_telorder__ ";
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
					$g_idx = $t_idx;
					$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
				}else{
					if($g_buynum < 1){
						$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						require_once(dirname(_FILE_).'/codmail_error_mail.php');
						return false;
					}
					//デスクトップPCの場合、それぞれ格納
					for($i=0; $i<$g_buynum; ++$i){
						$_insert = "INSERT php_telorder__ ";
						$_insert .= " ( insdt, upddt, status, output_flg, buynum, category, receptionday, cash, name, ruby, phonenum1, postcd1, postcd2, address1, address2, address3, mailaddress, sales_name, locale, staff, p_way, modelnum, specified_times,order_num, option_han, reception_telnum)";
						$_insert .= " VALUES ";
						$_insert .= "  ('$today', '$today', ".$status.", ".$output_flg.", '1', '".$category."', '$recdate', ".$g_cash/$g_buynum.", '$name', '$ruby', '$phonenum', '$postcd1', '$postcd2', '$address1', '".$address2.$address3."', '$address4', '$email', '100001', 'ネット通販', 'NS', '2', '".$modelnum."','".$timelist[$specified_times]."', '".$formid.$order_num."','".$option_han."', '既存')";
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
		*/	
				//備考がある場合、問合せテーブルへ格納
				if($remark <> ""){
					$question2 = "ネット注文No.".$g_idx."\n";
					$question0 = "【注文時備考】\n";
					$p_question = $question0.$question2.$question3.$remark;
					
					$_insert01 = "INSERT INTO php_info_mail ( ";
					$_insert02 .= ")VALUES(";
					$_insert01 .= " insdt ";
					$_insert02 .= sprintf("'%s'", $today);
					$_insert01 .= ", upddt ";
					$_insert02 .= sprintf(",'%s'", $today);
					$_insert01 .= ", status ";
					$_insert02 .= sprintf(",'%s'", 1);
					$_insert01 .= ", name ";
					$_insert02 .= sprintf(",'%s'", $name);
					$_insert01 .= ", ruby ";
					$_insert02 .= sprintf(",'%s'", "");
					$_insert01 .= ", email ";
					$_insert02 .= sprintf(",'%s'", $email);
					$_insert01 .= ", phonenum ";
					$_insert02 .= sprintf(",'%s'", $phonenum);
					$_insert01 .= ", postcd1 ";
					$_insert02 .= sprintf(",'%s'", $postcd1);
					$_insert01 .= ", postcd2 ";
					$_insert02 .= sprintf(",'%s'", $postcd2);
					$_insert01 .= ", address1 ";
					$_insert02 .= sprintf(",'%s'", $address1);
					$_insert01 .= ", address2 ";
					$_insert02 .= sprintf(",'%s'", $address2.$address3.$address4);
					$_insert01 .= ", contact ";
					$_insert02 .= sprintf(",'%s'", "メール");
					$_insert01 .= ", question ";
					$_insert02 .= sprintf(",'%s'", $p_question);
					$_insert01 .= ", delflg ";
					$_insert02 .= sprintf(",'%s'", 0);
					$_insert03 .= ")";
					$query = $_insert01.$_insert02.$_insert03;
					$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					//データ追加実行
					if (!($rs = $db->query($query))) {
						$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						require_once(dirname(_FILE_).'/codmail_error_mail.php');
						return false;
					}
					$i_idx = mysqli_insert_id($db);
					$_update = "UPDATE php_telorder__ ";
					$_update .= " SET upddt = ".sprintf("'%s'", $today);
					$_update .= " , updcount = updcount + 1 ";
					$_update .= " , remark  = CONCAT(remark, ' 問合No.".$i_idx."')";
					$_update .= " WHERE t_idx  = " . sprintf("'%s'", $g_idx);
					$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
					//データ追加実行
					if (!($rs = $db->query($_update))) {
						$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						require_once(dirname(_FILE_).'/codmail_error_mail.php');
						return false;
					}
				}
			}
		}
	}else{
		$comm->ouputlog("===========フォームズ 取り込みエラー！===========", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog("フォームIDを取得できませんでした。エラーメールを送信します。", $prgid, SYS_LOG_TYPE_DBUG);
		require_once(dirname(_FILE_).'/codmail_error_mail.php');
	}
	return true;

?> 