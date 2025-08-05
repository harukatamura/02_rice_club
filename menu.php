<?php
//==================================================================================================
// ■機能概要
//   ・メニュー一覧
//
// ■履歴
//   2019.06 バージョン更新対応 (PHP5.4.16 → PHP7.0.33)	K.Mizutani
//==================================================================================================

	//----------------------------------------------------------------------------------------------
	// 初期処理
	//----------------------------------------------------------------------------------------------
	//ログイン確認(COOKIEを利用)
	if ((!$_COOKIE['j_office_Uid']) or (!$_COOKIE['j_office_Pwd'])) {
			//Urlへ送信
			header("Location: ./idx.php"); //これを使うときは、これ以前にブラウザの書き込みは全てしないこと！
			exit();
	}
	//----------------------------------------------------------------------------------------------
	// 共通処理
	//----------------------------------------------------------------------------------------------
	//ファイル読込
	require_once("./lib/comm.php");
	require_once("./lib/define.php");
	require_once("./lib/dbaccess.php");
	require_once("./lib/html.php");
	require_once("./sql/sql_aggregate.php");
	//タイムゾーン
	date_default_timezone_set('Asia/Tokyo');

	//オブジェクト生成
	$html = new html();
	$comm = new comm();
	$dba = new dbaccess();
	$sql = new SQL_aggregate();

	//グローバルIP取得
	$g_ip = $_SERVER['REMOTE_ADDR'];
	//事務所　グローバルIPアドレス
	$office_ip = '113.40.164.162';

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);
	//担当者
	$p_staff = $_COOKIE['con_perf_staff'];
	//権限
	$p_Auth = $_COOKIE['con_perf_Auth'];
	//経理
	$p_acco = $_COOKIE['con_perf_acco'];
	//会社
	$p_compcd = $_COOKIE['con_perf_compcd'];
	//銀行
	$p_bank = $_COOKIE['con_perf_bank'];
	//タウンメール
	$p_tm = $_COOKIE['con_perf_tm'];
	//リーダーフラグ
	$p_leaderflg = $_COOKIE['con_perf_leaderflg'];
	//NSフラグ
	$p_ns = $_COOKIE['con_perf_ns'];
	//高規格フラグ
	$p_h_flg = $_COOKIE['con_h_flg'];
	//スケジュール
	$p_Sche = $_COOKIE['con_perf_sche'];
	//スケジュール
	$p_Sche_b = $_COOKIE['con_perf_sche_b'];

	//
	$datetime = new DateTime();
	$week = array("日", "月", "火", "水", "木", "金", "土");
	$w = (int)$datetime->format('w');
	$today = date('Y/m/d') . "(" .  $week[$w] . ")";

	$notice = array();
//	$notice[] = "【201907-会場予定】　<a href='./price/201907-plan.pdf' target=_blank>2019年7月～</a>";
//	$notice[] = "【2020年末年始】　<a href='./pdf/2020NewYearHolidays.pdf' target=_blank>スケジュール</a>";
	// ================================================
	// ■　□　■　□　お知らせ情報取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "SELECT  A.target, A.subject, A.contents, A.url ";
	$query = $query." FROM php_info A ";
	$query = $query." WHERE A.target  = (SELECT week FROM php_calendar where date ='" . date('Y/m/d') . "')";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {

//		$comm->ouputlog("☆★☆データ取得エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ取得エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	//データ設定
	$p_info = "";
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$notice[] =  $row['target'] . "週　【" . $row['subject'] ."】<a href='" . $row['url'] . "' target=_blank>". $row['contents'] . "</a>";
	}
	$comm->ouputlog("☆★☆p_info☆★☆  " . $p_info[0], $prgid, SYS_LOG_TYPE_ERR);
	// ================================================
	// ■　□　■　□　カレンダーマスタ取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "SELECT A.week";
	$query.= " FROM php_calendar A ";
	$query.= " WHERE A.date = '".date('Y-m-d')."'";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$this_week = $row['week'];
	}
	// ================================================
	// ■　□　■　□　出荷明細　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "
		SELECT A.venueid
		  FROM php_send_in A
		INNER JOIN php_calendar B
				 ON B.week LIKE '$this_week'
				AND A.venueid like DATE_FORMAT(B.date , '%Y%m%d%' )
		UNION ALL
		SELECT A.venueid
		  FROM php_cardboard A
		INNER JOIN php_calendar B
				 ON B.week LIKE '$this_week'
				AND A.venueid like DATE_FORMAT(B.date , '%Y%m%d%' )
		limit 1
	";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$notice[] =  $this_week . "週　【送り込み】<a href='./send_in_list.php' target=_blank>出荷明細</a>";
	}
	// ================================================
	// ■　□　■　□　出勤関連設定　■　□　■　□
	// ================================================
	$notice[] =  $this_week . "週　【スケジュール】<a href='./schedule_today.php' target=_blank>出勤状況</a>　<a href='./rest_list.php' target=_blank>休憩予定</a>　<a href='./schedule2.php' target=_blank>全体スケジュール</a>　<a href='./plan_schedule_list.php' target=_blank>当日作業一覧</a>　<a href='./manual/block/block_menu.php' target=_blank>対応注意ユーザー</a>";
	if($p_compcd == "J" || $p_staff=="JEMTC") {
		$notice[] =  date('Y/n/j') . "　【実績】<a href='./plan_kanri2.php?p_date=".date('Y-m-d')."&p_staff=".$p_staff."' target=_blank>作業予定</a>　<a href='./performance_list.php' target=_blank>実績一覧</a>　<a href='./response.php' target=_blank>対応状況一覧</a>";
	} else {
		$notice[] =  date('Y/n/j') . "　【実績】<a href='./plan_kanri2.php?p_date=".date('Y-m-d')."&p_staff=".$p_staff."' target=_blank>作業予定</a>";
	}
	$notice[] =  $this_week . "週　【価格表】<a href='./pc_price.php' target=_blank>価格表</a>　【送込】<a href='./shipment_slipnumber_list.php' target=_blank>伝票番号一覧</a>";
	// ================================================
	// ■　□　■　□　WEBカメラ取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "SELECT  A.url ,A.pwd";
	$query = $query." FROM php_web_camera A ";
	$query = $query." WHERE DATE_FORMAT(A.insdt, '%Y/%m/%d') = '" . date('Y/m/d') . "'";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	//データ設定
	$p_url = "";
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$notice[] =  $today . "　【Jemtc ビデオ通信URL】<a href='" . $row['url'] . "'>". $row['url'] . "</a> パスワード：". $row['pwd'];
//		$p_url =  $today . "　【Jemtc ビデオ通信URL】<a href='" . $row['url'] . "'>". $row['url'] . "</a>";
	}

	$select_count = 1;
	$query = "SELECT A.week,MIN(B.buydt), MAX(B.buydt)";
	$query.= " FROM( SELECT week FROM php_calendar ";
	$query.= " WHERE date> '".date('Y-m-d'). "' GROUP BY week";
	$query.= " ORDER BY date LIMIT 0,3 )";
	$query.= " A LEFT OUTER JOIN php_performance B ";
	$query.= " ON A.week=B.week GROUP BY A.week"; 
	$query.= " ORDER BY A.week ASC";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		if ($select_count == 1) {
			$this_week_min = $row['MIN(B.buydt)'];
			$this_week_max = $row['MAX(B.buydt)'];
		} else if ($select_count == 2) {
			$next_week_min = $row['MIN(B.buydt)'];
			$next_week_max = $row['MAX(B.buydt)'];
		} else if ($select_count == 3) {
			$next_week2_min = $row['MIN(B.buydt)'];
			$next_week2_max = $row['MAX(B.buydt)'];
		}
		$select_count++;
	}
	//データ設定
	$p_end_year = "";
	$p_iphone = "";
	//$p_end_year = "年末年始　<a href='./pdf/info.pdf' target=_blank>【各窓口休業日】</a>";
//	$notice[] =  "【個人評価】<a href='./assessment_input.php?kbn=2' target=_blank>事務所評価シート</a>";
	
/*	// ================================================
	// ■　□　■　□　在庫マスタ最新ファイル取得　■　□　■　□
	// ================================================
	$query = "SELECT A.filename, A.insdt";
	$query .= " FROM php_pc_barcode A ";
	$query .= " WHERE A.filename LIKE '_______________01%'";
	$query .= " ORDER BY insdt DESC";
	$query .= " LIMIT 0,1";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		//データ設定
		$notice[] = "【在庫マスタ最新ファイル】".$row['filename']."（".date('Y/n/j H:i:s', strtotime($row['insdt']))."更新）";
	}
	// ================================================
	// ■　□　■　□　販売情報最新ファイル取得　■　□　■　□
	// ================================================
	$query = "SELECT DISTINCT(B.filename), MAX(B.insdt) as insdt";
	$query .= " FROM php_staff A ";
	$query .= " LEFT OUTER JOIN php_pc_barcode B ON A.barcode = B.entry_personno ";
	$query .= " WHERE B.filename LIKE '_______________09%'";
	$query .= " AND A.staff = '".$_COOKIE['con_perf_staff']."'";
	$query .= " ORDER BY B.insdt DESC";
	$query .= " LIMIT 0,1";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		if($row['filename']<>""){
			//データ設定
			$notice[] =  "【販売情報最新ファイル】".$row['filename']."（".date('Y/n/j H:i:s', strtotime($row['insdt']))."更新）";
		}
	}
*/	// ================================================
	// ■　□　■　□　出勤状況取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "SELECT A.vacation_type_1, A.vacation_type_2, A.attendance_type, B.status";
	$query .= " FROM php_staff_info B";
	$query .= " LEFT OUTER JOIN  php_schedule_info A ON A.s_staff=B.staff AND A.delflg=0 AND DATE(A.vacation_day) = '".date('Ymd')."' ";
	$query .= " WHERE B.delflg = 0";
	$query .= " AND B.displayname = '".$_COOKIE['con_perf_staff']."'";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	$zaitakuflg = 0;
	$dutyflg = 0;
	$status = 0;
	while ($row = $rs->fetch_array()) {
		if($row['vacation_type_2'] == "在" && ($row['vacation_type_1'] == "半" || $row['vacation_type_1'] == "早" || $row['vacation_type_1'] == "遅" || $row['vacation_type_1'] == "研" || $row['vacation_type_1'] == "" )){
			$zaitakuflg = 1;
		}
		if ($row['attendance_type'] == "日") {
			$dutyflg = 1;
		}
		//雇用形態取得
		$status = $row['status'];
	}
	$query = "SELECT A.idxnum";
	$query .= " FROM php_kintai A ";
	$query .= " WHERE A.action = '出勤'";
	$query .= " AND DATE(A.insdt) = '".date('Ymd')."'";
	$query .= " AND A.staff = '".$_COOKIE['con_perf_staff']."'";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	$attend_id = 0;
	while ($row = $rs->fetch_array()) {
		$attend_id = $row['idxnum'];
	}
	//----- データ抽出
	$query = "SELECT A.idxnum";
	$query .= " FROM php_kintai A ";
	$query .= " WHERE A.action = '退勤'";
	$query .= " AND DATE(A.insdt) = '".date('Ymd')."'";
	$query .= " AND A.staff = '".$_COOKIE['con_perf_staff']."'";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	$leave_id = 0;
	while ($row = $rs->fetch_array()) {
		$leave_id = $row['idxnum'];
	}
//	$notice[] = "【機器情報】<a href='./pdf/FUJITSUtabQ704.pdf' target=_blank>FUJITSUタブレットQ704</a>";
//	$notice[] = "【機器情報】<a href='./pdf/FujitsutabQL2.pdf' target=_blank>FUJITSUタブレットQL2</a>";
//	$notice[] = "【機器情報】<a href='./pdf/NEC VersaPro VK15E.pdf' target=_blank>NECタブレットVersaPro VZ-H(VK15)</a>";
//	$notice[] = "【会場販売】<a href='./training_input.php' target=_blank>参加申請</a>";
//	$notice[] = "【電話注文】<a href='./telorder_expenses.php' target=_blank>経費一覧</a>";
	$notice[] = "【社内通知】<a href='./announcement_list.php' target=_blank>社内通知一覧</a>";
	if ($p_leaderflg == 1 ){
		$notice[] = "【クレーム】<a href='./claim.php' target=_blank>クレーム登録</a>　<a href='./claim_list.php' target=_blank>クレーム一覧</a>";
	} else {
		$notice[] = "【クレーム】<a href='./claim_list.php' target=_blank>クレーム一覧</a>";
	}
	$notice[] = "【問合せ状況】<a href='./j_mail_list.php' target=_blank>一覧</a>";

	//担当部署抽出
	$department_flg = 0;
	$henpin_flg = 0;
	$huryo_flg = 0;
	$huryo_flg_hosyu = 0;
	$event_flg = 0;
	$okurikomi_flg = 0;
	$houzin_flg = 0;
	$repair_flg = 0;
	$department_count = 0;
	$henpin_count = 0;
	$huryo_count = 0;
	$repair_huryo_count = 0;
	$huryo_count_hosyu = 0;
	$bmail_count = 0;
	$kintai_flg = 0;
	$kintai_count = 0;
	$not_receipt_count = 0;
	$order_data = [];

	$query = "SELECT DISTINCT A.m_category";
	$query .= " FROM php_plan_staff A ";
	$query .= " WHERE A.staff = '".$_COOKIE['con_perf_staff']."' AND A.delflg = 0";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$m_category = $row['m_category'];
		if (strpos($m_category,'問合せメール') !== false){
			$mail_flg = 1;
		}
		if (strpos($m_category,'返品') !== false){
			$henpin_flg = 1;
		}
		if (strpos($m_category,'初期不良') !== false){
			$huryo_flg = 1;
		}
		if (strpos($m_category,'法人') !== false){
			$houzin_flg = 1;
		}
		if (strpos($m_category,'再生PC') !== false){
			$repair_flg = 1;
		}
	}
	if ($p_compcd == 'H') {
		$huryo_flg_hosyu = 1;
	}
	if ($p_staff == '奥田') {
		$kintai_flg = 1;
	}
	$query = "SELECT A.venueid";
	$query .= " FROM php_event_staff A ";
	$query .= " WHERE A.staff = '".$_COOKIE['con_perf_staff']."'";
	$query .= " AND A.venueid LIKE '".date('Ymd')."%'";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	if ($rs != "") {
		while ($row = $rs->fetch_array()) {
			$e_venueid = $row['venueid'];
			$event_flg = 1;
		}
	}
	$alert_day = date('Y-m-d', strtotime('-9 days'));
	$alertflg = [];
	$alertflg2 = [];
	if($p_compcd == "J"){
		//本部職員の場合、参加会場で集計表未完成のものがあればアラート表示
		$query = "SELECT B.staff, B.lane, B.week, MIN(B.buydt) as mindate, MAX(B.buydt) as maxdate";
		$query .= " FROM php_event_staff A ";
		$query .= " LEFT OUTER JOIN php_performance B ON A.venueid=CONCAT(REPLACE(B.buydt,'-',''),LPAD(B.lane, 2, '0'),'-',B.branch) ";
		$query .= " WHERE A.staff = '".$p_staff."'";
		$query .= " AND ( ";
		$query .= " B.week = ";
		$query .= " (SELECT week FROM php_calendar  ";
		$query .= " WHERE date = '".date('Ymd')."'";
		$query .= " ) ";
		$query .= " OR B.week = ";
		$query .= " (SELECT MAX(week) FROM php_calendar  ";
		$query .= " WHERE week < ";
		$query .= " (SELECT week FROM php_calendar  ";
		$query .= " WHERE date = '".date('Ymd')."'";
		$query .= " ) ";
		$query .= " ) ";
		$query .= " ) ";
		$query .= " GROUP BY B.staff, B.week ";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		$week_event_staff = [];
		while ($row = $rs->fetch_array()) {
			$arr_week_event[] = $row['week'];
			//集計表・会場実績の値を取得
			$query2 = $sql->syuukei_list($row['staff'],$row['mindate'],$row['maxdate']);
			$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query2, $prgid, SYS_LOG_TYPE_DBUG);
			if (!($rs2 = $db->query($query2))) {
				$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
			while ($row2 = $rs2->fetch_array()) {
				if($row2['hannum']<>$row2['buynum'] || $row2['buykin']<>$row2['hankin']){
					$alertflg[$row['week']]["集計表と会場実績が一致しません。(".date('Y/n/j', strtotime($row2['buydt'])).")"] = 1;
				}
				$g_today = date('Ymd');
				if(strtotime($row2['buydt']) == strtotime($g_today) && date('H') > 16 || strtotime($row2['buydt']) > strtotime($g_today)){
					if($row2['genkin']-($row2['creditkin']+$row2['bank_payment']) > 999 || $row2['genkin']-($row2['creditkin']+$row2['bank_payment']) < 0){
						$alertflg2[$row['week']]["集計表の現金額・ｸﾚｼﾞｯﾄ金額にエラーがあります。確認してください。※会場実績登録画面をご確認ください"] = 1;
					}
				}
				//個人情報の値を取得
				$query3 = " SELECT SUM(M.buynum) as buynum, SUM(M.cash)*100 as cash, SUM(M.row) as num, SUM(M.checkflg) as checknum ";
				$query3 .= " FROM (";
				$query3 .= " SELECT buynum, cash, 1 as row, ";
				$query3 .= " CASE WHEN check_staff <> '' THEN 1 ELSE 0 END as checkflg ";
				$query3 .= " FROM php_personal_info";
				$query3 .= " WHERE g_buydt = ".sprintf("'%s'", $row2['buydt']);
				$query3 .= " AND g_staff = ".sprintf("'%s'", $row2['staff']);
				$query3 .= " AND delflg = 0 ";
				$query3 .= " AND reserv <> ''";
				$query3 .= " )M";
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query3, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs3 = $db->query($query3))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				while ($row3 = $rs3->fetch_array()) {
					if($row3['buynum'] <> ($row2['gre_P']+$row2['mre_P']+$row2['c_gre_P']+$row2['c_mre_P']) || $row3['cash'] <> ($row2['grek_P']+$row2['mrek_P']+$row2['c_grek_P']+$row2['c_mrek_P']+$row2['grek_O']+$row2['mrek_O']+$row2['c_grek_O']+$row2['c_mrek_O'])){
						$alertflg[$row['week']]["集計表と個人情報の値が一致していません。(".date('Y/n/j', strtotime($row2['buydt'])).")"] = 1;
					}
					if($row3['num'] <> $row3['checknum']){
						$alertflg[$row['week']]["予約個人情報の確認登録が完了していません。(".date('Y/n/j', strtotime($row2['buydt'])).")"] = 1;
					}
				}
				//修理の実績を取得
				$query3 = " SELECT SUM(A.num) as repairnum ";
				$query3 .= " FROM php_t_pc_repair A";
				$query3 .= " LEFT OUTER JOIN php_performance B ON A.venueid=CONCAT(REPLACE(B.buydt,'-',''),LPAD(B.lane, 2, '0'),'-',B.branch) ";
				$query3 .= " WHERE B.buydt = ".sprintf("'%s'", $row2['buydt']);
				$query3 .= " AND B.staff = ".sprintf("'%s'", $row2['staff']);
				$query3 .= " AND A.delflg = 0 ";
				$query3 .= " GROUP BY A.venueid";
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query3, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs3 = $db->query($query3))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				$g_repair_num = 0;
				while ($row3 = $rs3->fetch_array()) {
					$g_repair_num = $row3['repairnum'];
				}
				//データ消去実施時間
				$query3b = " SELECT delete_h_s , repair_h_s ";
				$query3b .= " FROM php_performance ";
				$query3b .= " WHERE buydt = ".sprintf("'%s'", $row2['buydt']);
				$query3b .= " AND staff = ".sprintf("'%s'", $row2['staff']);
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query3b, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs3b = $db->query($query3b))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				while ($row3b = $rs3b->fetch_array()) {
					$delete_h_s = $row3b['delete_h_s'];
					$repair_h_s = $row3b['repair_h_s'];
				}
				if ($row2['buynum'] > 0 && $g_repair_num == 0 && $delete_h_s != null && $repair_h_s != null) {
					$alertflg[$row['week']]["修理・データ消去実績の入力をお願いします。(".date('Y/n/j', strtotime($row2['buydt'])).")"] = 1;
				}
			}
			//週末の場合は棚卸・在庫移動未完了の確認
			if(date('Ymd',strtotime($row['maxdate'])) <= date('Ymd') && date('H') > 16 && $row['week'] == $this_week) {
				//棚卸を取得
				$query4 = " SELECT COUNT(suryou) as inventories";
				$query4 .= " FROM php_inventories";
				$query4 .= " WHERE staff = ".sprintf("'%s'", $row['staff']);
				$query4 .= " AND week = ".sprintf("'%s'", $row['week']);
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query4, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs4 = $db->query($query4))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				$g_inventories = 0;
				while ($row4 = $rs4->fetch_array()) {
					$g_inventories = $row4['inventories'];
				}
				if($g_inventories == 0){
					$alertflg2[$row['week']]["棚卸が未登録です、集計表完成後に登録してください"] = 1;
				}
				//在庫を取得
				$query4 = " SELECT COUNT(*) as zaikonum";
				$query4 .= " FROM php_t_pc_zaiko";
				$query4 .= " WHERE staff = ".sprintf("'%s'", $row['staff']);
				$query4 .= " AND delflg = 0 ";
				$query4 .= " AND hanbaiflg = 0 ";
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query4, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs4 = $db->query($query4))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				$g_zaiko = 0;
				while ($row4 = $rs4->fetch_array()) {
					$g_zaiko = $row4['zaikonum'];
				}
				if($g_zaiko > 0){
					$alertflg2[$row['week']]["在庫を次の会場に移動登録してください。"] = 1;
				}
			}
		}
		$false_day = date('Y-m-d', strtotime('-90 day'));
		//本部職員の場合、参加会場で予約未発送のものがあればアラート表示
		$query = "SELECT B.staff, B.lane, B.week, MIN(B.buydt) as mindate, MAX(B.buydt) as maxdate";
		$query .= " FROM php_event_staff A ";
		$query .= " LEFT OUTER JOIN php_performance B ON A.venueid=CONCAT(REPLACE(B.buydt,'-',''),LPAD(B.lane, 2, '0'),'-',B.branch) ";
		$query .= " LEFT OUTER JOIN php_personal_info C ON A.venueid=C.venueid AND C.delflg=0 AND C.cancelflg=0 ";
		$query .= " LEFT OUTER JOIN (SELECT slipnumber FROM php_yamato_status GROUP BY slipnumber) D ON C.slipnumber=D.slipnumber ";
		$query .= " WHERE A.staff = '".$p_staff."'";
		$query .= " AND B.buydt < ".sprintf("'%s'", $alert_day);
		$query .= " AND B.buydt > ".sprintf("'%s'", $false_day);
		$query .= " AND B.week > '20250401' ";
		$query .= " AND (D.slipnumber IS NULL ";
		$query .= " AND C.name IS NOT  NULL) ";
		$query .= " GROUP BY B.staff, B.week ";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$alertflg[$row['week']."/".$row['staff']]["予約未発送データがあります。確認してください。"] = 1;
		}
		foreach($alertflg as $key => $val){
			foreach($alertflg[$key] as $key2 => $val2){
				$comm->ouputlog("key = ".$key.", key2 = ".$key2.", val2 = ".$val2, $prgid, SYS_LOG_TYPE_INFO);
			}
		}
		foreach($alertflg2 as $key => $val){
			foreach($alertflg2[$key] as $key2 => $val2){
				$comm->ouputlog("key = ".$key.", key2 = ".$key2.", val2 = ".$val2, $prgid, SYS_LOG_TYPE_INFO);
			}
		}
	}
	$json_alertflg = json_encode($alertflg);
	$json_alertflg2 = json_encode($alertflg2);
	$query = "SELECT A.venueid";
	$query .= " FROM php_event_staff A ";
	$query .= " WHERE (CONCAT(SUBSTRING(A.venueid,1,4) , '-', SUBSTRING(A.venueid,5,2) , '-' , SUBSTRING(A.venueid,7,2)) BETWEEN" .sprintf("'%s'", $this_week_min) ." AND ".sprintf("'%s'", $this_week_max);
	$query .= " OR CONCAT(SUBSTRING(A.venueid,1,4) , '-', SUBSTRING(A.venueid,5,2) , '-' , SUBSTRING(A.venueid,7,2)) BETWEEN" .sprintf("'%s'", $next_week_min) ." AND ".sprintf("'%s'", $next_week_max);
	$query .= " OR CONCAT(SUBSTRING(A.venueid,1,4) , '-', SUBSTRING(A.venueid,5,2) , '-' , SUBSTRING(A.venueid,7,2)) BETWEEN" .sprintf("'%s'", $next_week2_min) ." AND ".sprintf("'%s'", $next_week2_max);
	$query .= ") AND A.staff = '".$_COOKIE['con_perf_staff']."'";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	if ($rs != "") {
		while ($row = $rs->fetch_array()) {
			$arr_venue[] = $row['venueid'];
			$okurikomi_flg = 1;
		}
	}
	if ($mail_flg == 1) {
		// 未対応・未確認メールカウント
		$query = "SELECT COUNT( ( A.status = 0 OR A.status = 3 ) or null ) as uns_mail ";
		$query .= ", COUNT( ( A.status = 8 ) or null ) as unc_mail ";
		$query .= " FROM php_info_mail A ";
		$query .= " WHERE A.delflg = 0 ";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$mail_count = $row['uns_mail'];
			$mail_count_unc = $row['unc_mail'];
		}
	}
	if ($henpin_flg == 1) {
		// 返品未チェックカウント
		$query = "SELECT COUNT(A.inputdt)";
		$query .= " FROM php_pc_failure A ";
		$query .= " WHERE A.status = 2 AND A.delflg = 0 AND A.kbn = '返品' ";
		$query .= " AND A.inputdt > ".date('Y-m-d');
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$henpin_count = $row['COUNT(A.inputdt)'];
		}
	}
	if ($huryo_flg == 1) {
		// 初期不良未対応カウント(本部)
		$query = "SELECT COUNT(A.status)";
		$query .= " FROM php_pc_failure A ";
		$query .= " WHERE A.status = 1 AND A.delflg = 0 AND A.kbn = '交換' ";
		$query .= " AND A.department = '本部'";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$huryo_count = $row['COUNT(A.status)'];
		}
	}
	if ($huryo_flg_hosyu == 1) {
		// 初期不良未対応カウント(補修センター)
		$query = "SELECT COUNT(A.status)";
		$query .= " FROM php_pc_failure A ";
		$query .= " WHERE A.status = 1 AND A.delflg = 0 AND A.kbn = '交換' ";
		$query .= " AND A.department = '補修センター'";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$huryo_count_hosyu = $row['COUNT(A.status)'];
		}
	}
	if ($event_flg == 1) {
		// 未受取PC在庫カウント
		$e_buydt = substr($e_venueid, 0, 8);
		$e_lane = substr($e_venueid, 8, 2);
		$e_lane = ltrim($e_lane, '0');
		$e_branch = substr($e_venueid, 11, 1);
		$query = "SELECT IFNULL(sum(A.suryou - A.receive), 0) as num , event_staff.staff ";
		$query.= " FROM php_s_pc_zaiko A ";
		$query.= " INNER JOIN (SELECT B.staff ";
		$query.= " FROM php_performance B ";
		$query.= " WHERE REPLACE(B.buydt,'-','') = ".sprintf("'%s'", $e_buydt);
		$query.= " AND B.lane = ".sprintf("'%s'", $e_lane);
		$query.= " AND B.branch = ".sprintf("'%s'", $e_branch);
		$query.= " ) AS event_staff ON A.staff = event_staff.staff ";
		$query.= " WHERE A.receiveflg = 0  AND A.delflg = 0";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$e_pc_zaiko = $row['num'];
			$event_staff = $row['staff'];
		}
		// 未受取オプション在庫カウント
		$query = "SELECT IFNULL(sum(A.suryou - A.receive), 0) as num ";
		$query .= " FROM php_s_option_zaiko A ";
		$query .= " WHERE A.receiveflg = 0 ";
		$query .= " AND A.delflg = 0 ";
		$query .= " AND A.staff = " . sprintf("'%s'", $event_staff);
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$e_option_zaiko = $row['num'];
		}		
	}
	if ($okurikomi_flg == 1) {
		$okurikomi_errflg = 0;
		$okurikomi_venue = "";
		$venue_count = 0;
		foreach ($arr_venue as $value) {
			if ($venue_count == 0) {
				$okurikomi_venue = "'".$value."'";
			} else {
				$okurikomi_venue = $okurikomi_venue.','."'".$value."'";
			}
			$venue_count++;
		}
		$query = "SELECT A.buydt, A.lane, A.branch, C.city as city, C.facility as locale, A.room, C.postcd, C.city as l_city, C.prefecture, C.address, A.idxnum as pid, A.eventflg ,B.centerflg, B.center, B.centercode, B.postcd1, B.postcd2, B.address1, B.address2, B.address3, B.address4, IFNULL(B.staff_tel,D.phonenum) as staff_tel  , B.a_date, B.a_time, B.sg_date, B.sg_time, B.idxnum";
		$query .= " FROM php_performance A LEFT OUTER JOIN php_shipment_address B ON A.buydt=B.buydt AND A.lane=B.lane AND A.branch=B.branch AND B.kbn=1";
		$query .= " LEFT OUTER JOIN php_facility C ON A.facility_id=C.facility_id  LEFT OUTER JOIN php_staff D ON A.staff=D.staff AND D.delflg=0";
		$query .= " WHERE CONCAT(REPLACE(A.buydt,'-',''),LPAD(A.lane, 2, '0'),'-',A.branch) IN (".$okurikomi_venue.")";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		$okurikomi_pop = 0;
		while ($row = $rs->fetch_array()) {

			if ($row['centerflg'] == 0) {

				if ($row['staff_tel'] == "" || $row['postcd1'] == "" || $row['postcd2'] == "" || $row['address1'] == "" || $row['address2'] == "" || $row['address3'] == "") {
					$okurikomi_errflg = 1;
				} else {
					$okurikomi_errflg = 0;
				}

			} else {

				if ($row['center'] == "" || $row['centercode'] == "" || $row['staff_tel'] == "") {
					$okurikomi_errflg = 1;
				} else {
					$okurikomi_errflg = 0;
				}
			}

			if ($okurikomi_errflg == 1) {
				$buydt_locale[] = date('m月d日' ,strtotime($row['buydt']))."：".$row['locale'];
				$okurikomi_pop = 1;
			}
		}
	}
	if ($repair_flg == 1) {
		// 初期不良未対応カウント(再生PC)
		$query = "SELECT COUNT(A.status)";
		$query .= " FROM php_pc_failure A ";
		$query .= " WHERE A.status = 1 AND A.delflg = 0 AND A.kbn = 'リペアPC' ";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$repair_huryo_count = $row['COUNT(A.status)'];
		}
	}
	if($p_compcd == "J"){
		//本部職員の場合、着払い在庫確認
		$query = " SELECT SUM(suryou-receive) as num";
		$query .= " FROM php_s_pc_zaiko";
		$query .= " WHERE receiveflg=0";
		$query .= " AND delflg=0";
		$query .= " AND (m_staff<>'RNG' AND m_staff<>'YKO' AND m_staff<>'補修センター' AND m_staff<>'本部')";
		$query .= " AND staff IN (";
		$query .= " SELECT B.staff";
		$query .= " FROM php_event_staff A";
		$query .= " LEFT OUTER JOIN php_performance B ON A.venueid=CONCAT( REPLACE(B.buydt,'-',''), LPAD(B.lane, 2, '0' ) , '-' ,B.branch)";
		$query .= " WHERE B.week=".sprintf("'%s'", $this_week);
		$query .= " AND A.staff=".sprintf("'%s'", $p_staff);
		$query .= " GROUP BY B.staff";
		$query .= " )";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$m_pc_zaiko = $row['num'];
		}
	}
	if ($houzin_flg == 1) {
		// 未対応メールカウント(法人)
		$query = "SELECT COUNT(A.status)";
		$query .= " FROM php_business_mail A ";
		$query .= " WHERE (A.status = 0 OR A.status = 3) AND A.delflg = 0 ";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$bmail_count = $row['COUNT(A.status)'];
		}
	}
	if ($kintai_flg == 1) {
		$query = " SELECT COUNT(A.idxnum)";
		$query .= " FROM php_kintai_correction A ";
		$query .= " WHERE A.delflg = 0";
		$query .= " AND A.approval = ''";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$kintai_count = $row['COUNT(A.idxnum)'];
		}
	}
	// 未受取発注パソコン確認
	$query = " SELECT A.idxnum , A.order_details";
	$query .= " FROM php_order_request A ";
	$query .= " WHERE A.delflg = 0";
	$query .= " AND A.insstaff = ".sprintf("'%s'", $p_staff);
	$query .= " AND A.shipment_dt != '0000-00-00'";
	$query .= " AND A.shipment_st != ''";
	$query .= " AND A.receipt_dt = '0000-00-00'";
	$query .= " AND A.receipt_st = ''";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$order_data[] = "発注No:".$row['idxnum']." (".$row['order_details'].")";
		$not_receipt_count++;
	}
	//補修センター　OR　NS　OR リーダーの場合は伝票番号未取込のデータがある場合アラート表示
	$nonumber_flg = 0;
	$g_factory = "";
	$daysover_flg = 0;
	if($p_compcd == "H" || $p_ns > 0 || $p_leaderflg > 0){
		//伝票番号未取込のデータを取得
		$query = " SELECT A.idxnum, A.name, A.response ";
		$query .= " FROM `php_telorder__` A ";
		$query .= " LEFT OUTER JOIN php_pc_failure B ON A.idxnum=B.tel_idx AND B.delflg=0 ";
		$query .= " WHERE A.response<>'' ";
		$query .= " AND A.slipnumber='' ";
		$query .= " AND A.delflg=0 ";
		if($p_compcd == "H"){
			$query .= " AND A.response = '補修センター' ";
		}else{
			$query .= " AND A.response = '本部' ";
		}
		$query .= " AND A.receptionday>'2025-04-06' ";
		$query .= " AND B.kbn IS NULL ";
		$query .= " UNION ALL ";
		$query .= " SELECT idxnum, name, response ";
		$query .= " FROM `php_personal_info` ";
		$query .= " WHERE response<>'' ";
		$query .= " AND slipnumber='' ";
		$query .= " AND delflg=0 ";
		$query .= " AND cancelflg=0 ";
		if($p_compcd == "H"){
			$query .= " AND response = '補修センター' ";
		}else{
			$query .= " AND response = '本部' ";
		}
		$query .= " AND insdt>'2025-04-06' ";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$nonumber_flg = 1;
		}
	}if($p_compcd == "H" || $p_compcd == "J"){
		//注文から10日以上経過
		$query = " SELECT A.idxnum, A.name, A.response ";
		$query .= " FROM php_telorder__ A  ";
		$query .= " LEFT OUTER JOIN ( ";
		$query .= " SELECT DISTINCT(slipnumber) as slipnumber FROM php_yamato_status ";
		$query .= " )B ON A.slipnumber=B.slipnumber ";
		$query .= " LEFT OUTER JOIN php_pc_failure C ON A.t_idx=C.tel_idx AND C.kbn='返品' AND C.delflg=0 ";
		$query .= " WHERE B.slipnumber IS NULL ";
		$query .= " AND A.receptionday <  ".sprintf("'%s'", $alert_day);
		$query .= " AND A.receptionday > '2025-04-06' ";
		$query .= " AND C.kbn IS NULL ";
		$query .= " AND (A.modelnum <>'JSP' OR (A.modelnum='JSP' AND A.p_way>0)) ";
		$query .= " AND A.delflg=0 ";
		if($p_compcd == "H"){
			$query .= " AND A.response = '補修センター' ";
		}else{
			$query .= " AND A.response = '本部' ";
		}
		$query .= " UNION ALL ";
		$query .= " SELECT A.idxnum, A.name, A.response ";
		$query .= " FROM php_personal_info A  ";
		$query .= " LEFT OUTER JOIN ( ";
		$query .= " SELECT DISTINCT(slipnumber) as slipnumber FROM php_yamato_status ";
		$query .= " )B ON A.slipnumber=B.slipnumber ";
		$query .= " WHERE B.slipnumber IS NULL ";
		$query .= " AND A.g_buydt <  ".sprintf("'%s'", $alert_day);
		$query .= " AND A.g_buydt > '2025-04-06' ";
		$query .= " AND A.delflg=0 ";
		$query .= " AND A.cancelflg=0 ";
		$query .= " AND A.reserv<>'' ";
		if($p_compcd == "H"){
			$query .= " AND A.response = '補修センター' ";
		}else{
			$query .= " AND A.response = '本部' ";
		}
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$daysover_flg = 1;
		}
		if($p_compcd == "H"){
			$g_factory = "補修センター";
		}else{
			$g_factory = "本部";
		}
	}
	$json_daysover_flg = json_encode($daysover_flg);
	$json_nonumber_flg = json_encode($nonumber_flg);
	$json_factory = json_encode($g_factory);
	/*
	// ブロックリスト最新No確認
	$query = " SELECT MAX(A.idxnum) as block_no";
	$query .= " FROM php_block_list A ";
	$query .= " WHERE A.delflg = 0";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$block_no = $row['block_no'];
	}
	*/
	?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>J-Office</title>

<style type="text/css">
body {
	color: #333333;
	background-color: #FFFFFF;
	margin: 0px;
	padding: 0px;
	text-align: center;
	font: 90%/2 "メイリオ", Meiryo, "ＭＳ Ｐゴシック", Osaka, "ヒラギノ角ゴ Pro W3", "Hiragino Kaku Gothic Pro";
	background-image: url(./images/bg.jpg);	/*背景壁紙*/
	background-repeat: no-repeat;			/*背景をリピートしない*/
	background-position: center top;		/*背景を中央、上部に配置*/
}
#formWrap {
	width:1000px;
	margin:0 auto;
	color:#555;
	line-height:120%;
	font-size:100%;
}
table.formTable{
	width:100%;
	margin:0 auto;
	border-collapse:collapse;
}
table.formTable td,table.formTable th{
	border:1px solid #FF8C00;
	background:#ffffff;
	padding:10px;
}
table.formTable th{
	width:30%;
	font-weight:bolder;
	background:#FFDEAD;
	text-align:left;
}
input[type=submit]{
 background-image:url("./images/satei.jpg");
 background-repeat:no-repeat;
 background-color:#000000;
 border:none;
 width:430px;
 height:59px;
 cursor: pointer;
}

h2{
	margin: 0px;
	padding: 0px;
}

/*コンテナー（HPを囲むブロック）
---------------------------------------------------------------------------*/
#container {
	text-align: left;
	width: 1010px;	/*コンテナー幅*/
	margin-right: auto;
	margin-left: auto;
	background-color: #FFFFFF;						/*背景色*/
	padding-right: 4px;
	padding-left: 4px;
}

/*メインコンテンツ
---------------------------------------------------------------------------*/
#main {
	width: 1000px;	/*メインコンテンツ幅*/
	padding: 10px 2px 50px 0px;	/*左から、上、右、下、左への余白*/
}
/*h2タグ設定*/
#main h2 {
	font-size: 120%;		/*文字サイズ*/
	color: #FFFFFF;			/*文字色*/
	background-image: url(./images/bg2.gif);	/*背景画像の読み込み*/
	background-repeat: no-repeat;			/*背景画像をリピートしない*/
	clear: both;
	line-height: 40px;
	height: 40px;
	width: 100%;
	padding-left: 40px;
	overflow: hidden;
}
/*段落タグの余白設定*/
#main p {
	padding: 0.5em 10px 1em;	/*左から、上、左右、下への余白*/
}
/*ヘッダー（ロゴが入っている最上段のブロック）
---------------------------------------------------------------------------*/
#header {
	background-repeat: no-repeat;
	height: 100px;	/*ヘッダーの高さ*/
	width: 100%;
	position: relative;
}
/*h1タグ設定*/
#header h1 {
	font-size: 10px;	/*文字サイズ*/
	line-height: 16px;	/*行間*/
	position: absolute;
	font-weight: normal;	/*文字サイズをデフォルトの太字から標準に。太字がいいならこの１行削除。*/
	right: 0px;		/*ヘッダーブロックに対して、右側から0pxの位置に配置*/
	bottom: 0px;	/*ヘッダーブロックに対して、下側から0pxの位置に配置*/
}
#header h1 a {
	text-decoration: none;
}
/*ロゴ画像設定*/
#header #logo {
	position: absolute;
	left: 10px;	/*ヘッダーブロックに対して、左側から10pxの位置に配置*/
	top: 12px;	/*ヘッダーブロックに対して、上側から12pxの位置に配置*/
}

/*コンテンツ（左右ブロックとフッターを囲むブロック）
---------------------------------------------------------------------------*/
#contents {
	clear: left;
	width: 100%;
	padding-top: 4px;
}

/*表示/非表示制御（お知らせ）
---------------------------------------------------------------------------*/
/*全体*/
.hidden_box {
    margin: 2em 0;
    padding: 0;
}

/*ボタン装飾*/
.hidden_box label {
    padding: 5px;
    font-weight: bold;
    background: #efefef;
    border-radius: 5px;
    cursor :pointer;
    transition: .5s;
}

/*アイコンを表示*/
.hidden_box label:before {
    display: inline-block;
    content: '\f078';
    font-family: 'FontAwesome';
    padding-right: 5px;
    transition: 0.2s;
}

/*ボタンホバー時*/
.hidden_box label:hover {
    background: silver;
}

/*アイコンを切り替え*/
.hidden_box input:checked ~ label:before {
     content: '\f00d';
     -webkit-transform: rotate(360deg);
     transform: rotate(360deg);
     color: #668ad8;
}

/*チェックは見えなくする*/
.hidden_box input {
    display: none;
}

/*中身を非表示にしておく*/
.hidden_box .hidden_show {
    height: 0;
    padding: 0;
    overflow: hidden;
    opacity: 0;
    transition: 0.8s;
}

/*クリックで中身表示*/
.hidden_box input:checked ~ .hidden_show {
    padding: 10px 0;
    height: auto;
    opacity: 1;
}

/* 販売実績一覧 */
.ttl1,.ttl2{
    color: #fff !important;
}

.fare {
    margin: 30px auto;
    padding: 20px;
    border: 1px solid #555;
  background:#053352;
  background-image: -webkit-linear-gradient(top, #053352, Courier New);
  background-image: -moz-linear-gradient(top, #053352, Courier New);
  background-image: -ms-linear-gradient(top, #053352, Courier New);
  background-image: -o-linear-gradient(top, #053352, Courier New);
  background-image: linear-gradient(to bottom, #053352, Courier New);
  -webkit-border-radius: 6;
  -moz-border-radius: 6;
  border-radius: 6px;
  font-family: Courier New;
  color: #ffffff;
  font-size: 20px;
  padding: 20px 20px 20px 20px;
  text-decoration: none;
}

.fare-calendar .fare-rates {
    position: relative;
    background: #104833;
}

.fare-calendar .fare-rates .fare-monthcontainer {
    color: #092a5e ;
    /*display:table-cell;*/
}
.fare-calendar .fare-rates .h2 {
    font-size: 25px;
    text-align: center;
	border: medium solid #fff;
}

.fare-calendar .fare-rates .fare-monthcontainer .fare-month {
    margin: 0 2px;
    /*vertical-align: bottom;*/
    display: inline-block;
    width: 75px;
    text-align: center;
}

.fare-calendar .fare-rates .fare-monthcontainer .fare-month a
, .fare-calendar .fare-rates .fare-monthcontainer .fare-month span {
    display: block;
}

.fare-calendar .fare-rates .fare-monthcontainer .fare-month span:first-of-type {
    margin-bottom: 7px;
}

.fare-calendar .fare-rates .fare-monthcontainer .fare-month span:last-of-type {
    margin: 10px 0 7px;
}

.fare-calendar .fare-rates .fare-monthcontainer .fare-month button.btn2 {
    width: 45px;
    padding-left: 0;
    padding-right: 0;
}

.fare-calendar .fare-rates .fare-monthcontainer .fare-month span.fare-price {
    min-height: 0px;
    background-color: #FFF ;
    transform-origin: 100% 100%;
    -webkit-animation: priceAnimation 0.5s 1 ease-in-out;
    -moz-animation: priceAnimation 0.5s 1 ease-in-out;
    -o-animation: priceAnimation 0.5s 1 ease-in-out;
    border-radius: 3px;
    /*                        -webkit-transition: all 0.5s 1 ease-in-out;
    -moz-transition: all 0.5s 1 ease-in-out;
    -o-transition: all 0.5s 1 ease-in-out; */
}

.fare-calendar .fare-rates .fare-monthcontainer .fare-month span.fare-price.cheapest {
    background-color: #EF3D84 ;
}
/* 会場実績登録ボタン */
.btn-square-pop {
  position: relative;
  display: inline-block;
  padding: 0.25em 0.5em;
  text-decoration: none;
  color: #FFF;
  background: #fd9535;/*背景色*/
  border-bottom: solid 2px #d27d00;/*少し濃い目の色に*/
  border-radius: 4px;/*角の丸み*/
  box-shadow: inset 0 2px 0 rgba(255,255,255,0.2), 0 2px 2px rgba(0, 0, 0, 0.19);
  font-weight: bold;
  width: 100px;
  text-align: center;
}

.btn-square-pop:active {
  border-bottom: solid 2px #fd9535;
  box-shadow: 0 0 2px rgba(0, 0, 0, 0.30);
}
</style>
<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">

<!-- 以下3行トーストプラグイン読込 -->
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>	
<link href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" rel="stylesheet" />
<script src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<!--sweetalert2-->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@8"></script>
<script type="text/javascript">
	<!--
	function kintai(action){
		if(action == 'attend'){
			var action_j = "出勤";
		}else if(action == 'leave'){
			var action_j = "退勤";
		}
		if(window.confirm(action_j+'打刻を行います。')){
			window.open('./kintai_sql.php?do='+action);
		}
	}
	// トースト表示
	window.addEventListener ('DOMContentLoaded', function () {
		let mail_count = document.getElementById("mail_count").value;
		let henpin_count = document.getElementById("henpin_count").value;
		let huryo_count = document.getElementById("huryo_count").value;
		let huryo_count_hosyu = document.getElementById("huryo_count_hosyu").value;
		let pc_zaiko_count = document.getElementById("pc_zaiko_count").value;
		let m_pc_zaiko_count = document.getElementById("m_pc_zaiko_count").value;
		let option_zaiko_count = document.getElementById("option_zaiko_count").value;
		let bmail_count = document.getElementById("bmail_count").value;
		let kintai_count = document.getElementById("kintai_count").value;
		let mail_count_unc = document.getElementById("mail_count_unc").value;
		let not_receipt_count = document.getElementById("not_receipt_count").value;
		let repair_huryo_count = document.getElementById("repair_huryo_count").value;
		let dutyflg = <? echo $dutyflg; ?>;
		let nonumber_flg = JSON.parse('<? echo $json_nonumber_flg; ?>');
		let daysover_flg = JSON.parse('<? echo $json_daysover_flg; ?>');
		var buydt_locale_value = document.getElementById("buydt_locale").value;
		var order_data = document.getElementById("order_data").value;
		var json_alertflg = JSON.parse('<? echo $json_alertflg; ?>');
		var json_alertflg2 = JSON.parse('<? echo $json_alertflg2; ?>');
		let factory = JSON.parse('<? echo $json_factory; ?>');
		let compcd = '<? echo $p_compcd; ?>';
		/*
		var compcd = document.getElementById("compcd").value;
		var block_no = document.getElementById("block_no").value;
		var storage_block_no = localStorage.getItem("storage_block_no");
		*/
		if (compcd == "Y") {return;}
		if (mail_count > 0 || henpin_count > 0 || huryo_count > 0 || huryo_count_hosyu > 0 || buydt_locale_value != "" || bmail_count > 0 || mail_count_unc > 0 || dutyflg > 0 || nonumber_flg > 0 || daysover_flg > 0 ||  Object.keys(json_alertflg).length > 0 || Object.keys(json_alertflg2).length > 0) {
			jQuery(document).ready(function() {
				// 初回表示判定のローカルストレージを削除
				if (localStorage.getItem('menu_flg') != null) {
					var now_day = <? echo date('Ymd') ?>;
					var ls_day = localStorage.getItem('menu_flg');
					if ( now_day > ls_day) {
						localStorage.removeItem('menu_flg');
					}
				}	
				toastr.options = {
					"closeButton": true,
					"debug": false,
					"newestOnTop": false,
					"progressBar": false,
					"positionClass": "toast-bottom-right",
					"preventDuplicates": false,
					"onclick": null,
					"showDuration": "300",
					"hideDuration": "1000",
					"timeOut": "5000",
					"extendedTimeOut": "5000",
					"showEasing": "swing",
					"hideEasing": "linear",
					"showMethod": "fadeIn",
					"hideMethod": "fadeOut"
				}
				// 問い合わせメール関連
				if (mail_count > 0) {
					toastr.options = {
						"onclick": function() {
							window.open('./info_mail.php');
						},
						"closeButton": true,
						"positionClass": "toast-bottom-right",
						"timeOut": "5000",
						"extendedTimeOut": "5000"
					}
					Command: toastr["error"]("問い合わせメール：未対応" + mail_count + "件<br>クリックでリスト表示");
					jQuery('#linkButton').click(function() {
						toastr.error('click');
					});
				}
				if (mail_count_unc > 0) {
					toastr.options = {
						"onclick": function() {
							window.open('./info_mail_checklist.php');
						},
						"closeButton": true,
						"positionClass": "toast-bottom-right",
						"timeOut": "5000",
						"extendedTimeOut": "5000"
					}
					Command: toastr["warning"]("問い合わせメール：未確認" + mail_count_unc + "件<br>クリックでリスト表示");
					jQuery('#linkButton').click(function() {
						toastr.warning('click');
					});
				}
				// 返品関連
				if (henpin_count > 0) {
					toastr.options = {
						"onclick": function() {
							window.open('./manual/henpin/pc_failure_arrival.php');
						},
						"closeButton": true,
						"positionClass": "toast-bottom-right",
						"timeOut": "5000",
						"extendedTimeOut": "5000"
					}
					Command: toastr["error"]("返品入力情報：未チェック" + henpin_count + "件<br>クリックでリスト表示");
					jQuery('#linkButton').click(function() {
						toastr.error('click');
					});
				}
				// 初期不良対応関連
				if (huryo_count > 0) {
					toastr.options = {
						"onclick": function() {
							window.open('./pc_Initialfailure_list.php');
						},
						"closeButton": true,
						"positionClass": "toast-bottom-right",
						"timeOut": "5000",
						"extendedTimeOut": "5000"
					}
					Command: toastr["error"]("初期不良登録：未対応" + huryo_count + "件<br>クリックでリスト表示");
					jQuery('#linkButton').click(function() {
						toastr.error('click');
					});
				}
				if (huryo_count_hosyu > 0) {
					toastr.options = {
						"onclick": function() {
							window.open('./pc_Initialfailure_list.php');
						},
						"closeButton": true,
						"positionClass": "toast-bottom-right",
						"timeOut": "5000",
						"extendedTimeOut": "5000"
					}
					Command: toastr["error"]("初期不良登録：未対応" + huryo_count_hosyu + "件<br>クリックでリスト表示");
					jQuery('#linkButton').click(function() {
						toastr.error('click');
					});
				}
				// 在庫受取
				if (pc_zaiko_count > 0) {
					var p_zaiko_alert = "PC受取登録待ち：" + pc_zaiko_count + "台<br>クリックでリスト表示";
					toastr.options = {
						"onclick": function() {
							window.open('./shipment_input.php');
						},
						"closeButton": true,
						"positionClass": "toast-bottom-right",
						"timeOut": "5000",
						"extendedTimeOut": "5000"
					}
					Command: toastr["warning"]("PC受取登録待ち：" + pc_zaiko_count + "台<br>クリックでリスト表示");
					jQuery('#linkButton').click(function() {
						toastr.warning('click');
					});
				}
				// 在庫受取
				if (m_pc_zaiko_count > 0) {
					toastr.options = {
						"onclick": function() {
							window.open('./shipment_input.php');
						},
						"closeButton": true,
						"positionClass": "toast-bottom-right",
						"timeOut": "5000",
						"extendedTimeOut": "5000"
					}
					Command: toastr["warning"]("着払PC受取登録待ち：" + m_pc_zaiko_count + "台<br>クリックでリスト表示");
					jQuery('#linkButton').click(function() {
						toastr.warning('click');
					});
				}
				if (option_zaiko_count > 0) {
					toastr.options = {
						"onclick": function() {
							window.open('./shipment_input.php');
						},
						"closeButton": true,
						"positionClass": "toast-bottom-right",
						"timeOut": "5000",
						"extendedTimeOut": "5000"
					}
					Command: toastr["warning"]("備品受取登録待ち：" + option_zaiko_count + "個<br>クリックでリスト表示");
					jQuery('#linkButton').click(function() {
						toastr.warning('click');
					});
				}
				// 送り込み未記入
				if (buydt_locale_value != "") {
					var result = buydt_locale_value.replace(/,/g,'</br>');
					toastr.options = {
						"onclick": function() {
							window.open('./shipment_input_venue.php?kbn=1');
						},
						"closeButton": true,
						"positionClass": "toast-bottom-right",
						"timeOut": "5000",
						"extendedTimeOut": "5000"
					}
					Command: toastr["warning"]("送り込み先が登録されていません。</br>" + result);
					jQuery('#linkButton').click(function() {
						toastr.warning('click');
					});
				}
				// 問い合わせメール関連（法人）
				if (bmail_count > 0) {
					toastr.options = {
						"onclick": function() {
							window.open('./t_business_mail.php');
						},
						"closeButton": true,
						"positionClass": "toast-bottom-right",
						"timeOut": "5000",
						"extendedTimeOut": "5000"
					}
					Command: toastr["error"]("法人問い合わせメール：未対応" + bmail_count + "件<br>クリックでリスト表示");
					jQuery('#linkButton').click(function() {
						toastr.error('click');
					});
				}
				// 勤怠修正承認待ち
				if (kintai_count > 0) {
					toastr.options = {
						"onclick": function() {
							window.open('./kintai.php?kbn=5');
						},
						"closeButton": true,
						"positionClass": "toast-bottom-right",
						"timeOut": "5000",
						"extendedTimeOut": "5000"
					}
					Command: toastr["warning"]("勤怠修正承認待ち：" + kintai_count + "件<br>クリックでリスト表示");
					jQuery('#linkButton').click(function() {
						toastr.warning('click');
					});
				}
				// 依頼在庫未受取
				if ( not_receipt_count > 0 ) {
					var order_result = order_data.replace(/,/g,'</br>');
					toastr.options = {
						"onclick": function() {
							window.open('./order_request_list.php');
						},
						"closeButton": true,
						"positionClass": "toast-bottom-right",
						"timeOut": "5000",
						"extendedTimeOut": "5000"
					}
					Command: toastr["warning"]( "依頼在庫受取待ち：" + not_receipt_count + "件<br>" + order_result );
					jQuery('#linkButton').click(function() {
						toastr.warning('click');					
					});
				}
				// 再生PC不良未対応
				if ( repair_huryo_count > 0 ) {
					var order_result = order_data.replace(/,/g,'</br>');
					toastr.options = {
						"onclick": function() {
							window.open('./pc_Initialfailure_list.php?department=repair_pc');
						},
						"closeButton": true,
						"positionClass": "toast-bottom-right",
						"timeOut": "5000",
						"extendedTimeOut": "5000"
					}
					Command: toastr["error"]( "再生PC不良未対応：" + repair_huryo_count + "件<br>クリックでリスト表示");
					jQuery('#linkButton').click(function() {
						toastr.error('click');					
					});
				}
				// 伝票番号未取込
				if ( nonumber_flg > 0 ) {
					toastr.options = {
						"onclick": function() {
							window.open('./yamato_upload.php');
						},
						"closeButton": true,
						"positionClass": "toast-bottom-right",
						"timeOut": "5000",
						"extendedTimeOut": "5000"
					}
					Command: toastr["error"]( "伝票発行後伝票番号未取込のデータがあります。伝票を出した方は取込をしてください。<br>クリックで取込画面表示");
					jQuery('#linkButton').click(function() {
						toastr.error('click');
					});
				}
				//10日以上経過未発送
				if ( daysover_flg > 0 ) {
					toastr.options = {
						"onclick": function() {
							window.open('./nsorder_list_all_y.php?出荷元='+factory);
						},
						"closeButton": true,
						"positionClass": "toast-bottom-right",
						"timeOut": "5000",
						"extendedTimeOut": "5000"
					}
					Command: toastr["error"]( "注文から10日以上経過している未発送データがあります。<br>クリックで未発送一覧画面表示");
					jQuery('#linkButton').click(function() {
						toastr.error('click');
					});
				}
				// 日直通知メッセージ表示 初回のみ
				if (dutyflg > 0) {
					if (localStorage.getItem('menu_flg') == null) {
						alert("本日の日直担当です。");
						localStorage.setItem('menu_flg',<? echo date('Ymd') ?>);
					}
				}
				// ブロックリスト最大値取得 (職員・アルバイト・ラン)
				/*if ( compcd == "J" || compcd == "S" || compcd == "E" ) {
					if ( block_no > storage_block_no || storage_block_no == null ) {
						toastr.options = {
						"onclick": function() {
							window.open('./manual/block/block_list.php');
						},
						"closeButton": true,
						"positionClass": "toast-top-full-width",
						"timeOut": "5000",
						"extendedTimeOut": "5000"
						}
						Command: toastr["error"]("未確認の対応注意ユーザーがあります<br>クリックでリスト表示");
						jQuery('#linkButton').click(function() {
							toastr.error('click');
						});
					}
				}
				*/
				// 会場予約未発送チェック
				/*
				for(var key in json_alertflg){
					for(var key2 in json_alertflg[key]){
						if(json_alertflg[key][key2] == 1)
						var p_week = key.substr(4,2)+"月"+key.substr(7,1)+"週";
						var p_syuukei_alert = p_week +"の"+key2;
						if (key2 == '予約未発送データがあります。確認してください。') {
							let s_key = key.split('/');
							toastr.options = {
								"onclick": function() {
									window.open('./shipment_unregistered.php?g_staff=' + s_key[1] + '&g_week=' + s_key[0]);
								},
								"closeButton": true,
								"positionClass": "toast-top-full-width",
								"timeOut": "5000",
								"extendedTimeOut": "5000"
							}
							Command: toastr["warning"](p_syuukei_alert);
							jQuery('#linkButton').click(function() {
								toastr.warning('click');
							});
							break;
						} else {
							toastr.options = {
								"onclick": function() {
									window.open('./syuukei_list.php');
								},
								"closeButton": true,
								"positionClass": "toast-top-full-width",
								"timeOut": "5000",
								"extendedTimeOut": "5000"
							}
							Command: toastr["warning"](p_syuukei_alert);
							jQuery('#linkButton').click(function() {
								toastr.warning('click');
							});
							break;
						}
					}
				}
				*/
				// 集計表未完成チェック
				for(var key in json_alertflg2){
					for(var key2 in json_alertflg2[key]){
						if(json_alertflg2[key][key2] == 1)
						var p_week = key.substr(4,2)+"月"+key.substr(7,1)+"週";
						var p_syuukei_alert = p_week +"の"+key2;
						console.log(p_syuukei_alert);
						Swal.fire({
							text: p_syuukei_alert,
							type: 'warning',
							allowOutsideClick : false,   //枠外クリックは許可しない
						})
						break;
					}
				}
			});
		}
	})
	//-->
</script>

</head>
<body>
	<!--件数表示（トースト用）-->
	<input type="hidden" id="mail_count" value="<?php echo $mail_count ?>">
	<input type="hidden" id="mail_count_unc" value="<?php echo $mail_count_unc ?>">
	<input type="hidden" id="henpin_count" value="<?php echo $henpin_count ?>">
	<input type="hidden" id="huryo_count" value="<?php echo $huryo_count ?>">
	<input type="hidden" id="huryo_count_hosyu" value="<?php echo $huryo_count_hosyu ?>">
	<input type="hidden" id="repair_huryo_count" value="<?php echo $repair_huryo_count ?>">
	<input type="hidden" id="pc_zaiko_count" value="<?php echo $e_pc_zaiko ?>">
	<input type="hidden" id="m_pc_zaiko_count" value="<?php echo $m_pc_zaiko ?>">
	<input type="hidden" id="option_zaiko_count" value="<?php echo $e_option_zaiko ?>">
	<input type="hidden" id="buydt_locale" value="<?php if ($okurikomi_pop != 0) { foreach ($buydt_locale as $value) { echo str_replace("-", "pineapple", $value).","; } } ?>">
	<input type="hidden" id="bmail_count" value="<?php echo $bmail_count ?>">
	<input type="hidden" id="kintai_count" value="<?php echo $kintai_count ?>">
	<input type="hidden" id="not_receipt_count" value="<?php echo $not_receipt_count ?>">
	<input type="hidden" id="order_data" value="<?php if ( $not_receipt_count != 0 ) { foreach ( $order_data as $value ) { echo $value.","; } } ?>">
	<!--
	<input type="hidden" id="compcd" value="<? /*echo $p_compcd ?>">
	<input type="hidden" id="block_no" value="<?php echo $block_no */?>">
	-->
	<!--container-->
	<div id="container">
		<div id="header">
			<p>
			<img src="images/logo.gif" alt="" align="left"/>
			</p>
			<div class="uname" align="right">
				<?php echo $today ?>　こんにちは、<?php echo $p_staff; ?>さん
				<?php
				if($_COOKIE['con_perf_kintaiflg']=="1" && ($g_ip==$office_ip || $zaitakuflg == 1)|| $_COOKIE['con_perf_kintaiflg']=="2"){
	//			if($_COOKIE['con_perf_kintaiflg']=="1" && $zaitakuflg == 1 || $_COOKIE['con_perf_kintaiflg']=="2"){
					if($attend_id == 0){ ?>
						<div class="kintai" style="text-align:center"><a href="Javascript:kintai('attend')"><img src="images/attend_big.png"></a>　<a href="Javascript:kintai('leave')"><img src="images/leave.png"></a></div>
					<? }else if($leave_id == 0 && date('H') > 17){ ?>
						<div class="kintai" style="text-align:center"><a href="Javascript:kintai('attend')"><img src="images/attend.png"></a>　<a href="Javascript:kintai('leave')"><img src="images/leave_big.png"></a></div>
					<? }else{ ?>
						<div class="kintai"><a href="Javascript:kintai('attend')"><img src="images/attend.png"></a>　<a href="Javascript:kintai('leave')"><img src="images/leave.png"></a></div>
					<? }
				} ?>
				<form name="frm2" method = "post" enctype="multipart/form-data" target=_blank action="https://jemof.xyz/office/setting_info.php">
					<input type="hidden" name="con_perf_staff" value="<?php echo $p_staff; ?>">
					<a href="javascript:frm2.submit()">パソコン設定</a>
				</form>
				<?php if ($p_compcd == "J") { ?>
				<form name="frm5" method = "post" enctype="multipart/form-data" target=_blank action="https://jemof.xyz/office/bank.php">
					<input type="hidden" name="con_perf_staff" value="<?php echo $p_staff; ?>">
					<a href="javascript:frm5.submit()">個人情報設定</a>
				</form>
				<?php } ?>
				<?php if ($p_bank == 1) { ?>
				<form name="frm6" method = "post" enctype="multipart/form-data" target=_blank action="https://jemof.xyz/office/bank_list.php">
					<input type="hidden" name="con_perf_staff" value="<?php echo $p_staff; ?>">
					<a href="javascript:frm6.submit()">銀行情報参照</a>
				</form>
				<?php } ?>
				<?php if ($p_compcd == "J") { ?>
				<a href="./schedule_check.php" target="_blank" class="btn-square-pop">ｽｹｼﾞｭｰﾙ確定</a>
				<?php } ?>
				<?php if ($p_Sche_b <> "0") { ?>
				<a href="./schedule_plan.php?status=<? echo $p_Sche_b?>" target="_blank" class="btn-square-pop">Aｽｹｼﾞｭｰﾙ登録</a>
				<?php } ?>
				<div class="out"><img src="images/logout.png" alt=""/><a href="./login.php">ログアウト</a></div>
			</div>
		</div>
		<!--contents-->
		<div id="contents">
			<div id="main">
			<?php if ($p_compcd == "T") { ?>
				<h2>電話メニュー</h2>
					<p align="left">
					<a href="./manual/top.php" target="_blank"><img src="images/manual.png" onmouseover="this.src='images/manual_b.png'" onmouseout="this.src='images/manual.png'"></a>
					<a href="./tel_performance.php" target="_blank"><img src="images/tel_performance.png" onmouseover="this.src='images/tel_performance_b.png'" onmouseout="this.src='images/tel_performance.png'"></a>
					<a href="https://tel.jemtc.top/" target="_blank"><img src="images/tel_jemtc_top.png" onmouseover="this.src='images/tel_jemtc_top_b.png'" onmouseout="this.src='images/tel_jemtc_top.png'"></a><br>
					<a href="https://pc-helper.or.jp/" target="_blank"><img src="images/pc-helper.png" onmouseover="this.src='images/pc-helper_b.png'" onmouseout="this.src='images/pc-helper.png'"></a>
					</p>
				<h2>会場詳細メニュー</h2>
					<p align="left">
					<a href="./syousai_display.php" target="_blank"><img src="images/syousai_display.png" onmouseover="this.src='images/syousai_display_b.png'" onmouseout="this.src='images/syousai_display.png'"></a>
					<a href="./schedule2.php" target="_blank"><img src="images/schedule.png" onmouseover="this.src='images/schedule_b.png'" onmouseout="this.src='images/schedule.png'"></a>
					</p>
				<h2>メールメニュー</h2>
					<p align="left">
					<!--1段目-->
					<a href="./info_mail.php" target="_blank"><img src="images/info_mail.png" onmouseover="this.src='images/info_mail_b.png'" onmouseout="this.src='images/info_mail.png'"></a>
					</p>
			<?php }else{ ?>
				<?php if ($p_compcd != "Y") { ?>
				<h2>お知らせ</h2>
					<!--contents-->
					<div class="hidden_box">
						<?php echo $notice[0]; ?>
						　　　
					    <input type="checkbox" id="label1" align="right"/>
					    <br><label for="label1">..read more</label>
					    <div class="hidden_show">
					    <!--非表示ここから-->
						<?php
							for ($i = 1; $i < count($notice); $i++) {
								echo $notice[$i] . "<br>";
								$comm->ouputlog("☆★☆☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
						?>
						<form name="frm3" method = "post" enctype="multipart/form-data" target=_blank action="https://jemof.xyz/office/assessment_input.php?kbn=2">
							<input type="hidden" name="con_perf_staff" value="<?php echo $p_staff; ?>">
							【個人評価】<a href="javascript:frm3.submit()">事務所評価シート</a>
						</form>
						<form name="frm4" method = "post" enctype="multipart/form-data" target=_blank action="https://jemof.xyz/office/training_input.php">
							<input type="hidden" name="con_perf_staff" value="<?php echo $p_staff; ?>">
							【アンケート】<a href="javascript:frm4.submit()">登録</a>
						</form>
						【Eset】<a href="./eset_serial.php" target="_blank" >更新</a><br>
						【周知事項】<a href="./pdf/parking.pdf" target="_blank" >駐車場について</a><br>
						【周知事項】<a href="./pdf/rcpt_note.pdf" target="_blank" >領収書について</a><br>
						【販売実績】<a href="./inquiry_hanbai_list.php" target="_blank" >カテゴリ別実績</a><br>
						【クレジット】<a href="https://jemof.xyz/office/stores_info.php" target="_blank" >ログイン情報について</a>　　<a href="./pdf/credit_ope.pdf" target="_blank" >運用イメージ</a><br>
						【セブン銀行】<a href="./pdf/7_app.pdf" target="_blank" >アプリDL</a>　　<a href="./pdf/7_payment.pdf" target="_blank" >入金方法</a>
					      <!--ここまで-->
					    </div>
					</div>
				<?php } ?>
				<?php if ($p_compcd == "S") { ?>
				<h2>サポートセンターメニュー</h2>
					<p align="left">
					<a href="./manual/jsupport/jsupport_top.html" target="_blank"><img src="images/support_manual.png" onmouseover="this.src='images/support_manual_b.png'" onmouseout="this.src='images/support_manual.png'"></a>
					<a href="https://pc-helper.or.jp/" target="_blank"><img src="images/pc-helper.png" onmouseover="this.src='images/pc-helper_b.png'" onmouseout="this.src='images/pc-helper.png'"></a>
					<a href="./info_mail.php" target="_blank"><img src="images/info_mail.png" onmouseover="this.src='images/info_mail_b.png'" onmouseout="this.src='images/info_mail.png'"></a>
					</p>
					<p align="left">
					<a href="./manual/top_s.php" target="_blank"><img src="images/manual.png" onmouseover="this.src='images/manual_b.png'" onmouseout="this.src='images/manual.png'"></a>
					<a href="https://tel.jemtc.top/" target="_blank"><img src="images/tel_jemtc_top.png" onmouseover="this.src='images/tel_jemtc_top_b.png'" onmouseout="this.src='images/tel_jemtc_top.png'"></a>
					<a href="./holding_venue.php" target="_blank"><img src="images/holding_venue.png" onmouseover="this.src='images/holding_venue_b.png'" onmouseout="this.src='images/holding_venue.png'"></a>
					</p>
					<p align="left">
					<a href="./schedule_plan.php?status=<? echo $status;?>" target="_blank"><img src="images/schedule.png" onmouseover="this.src='images/schedule_b.png'" onmouseout="this.src='images/schedule.png'"></a>
					<a href="./ns_list_dispatch.php" target="_blank"><img src="images/nsorder_list.png" onmouseover="this.src='images/nsorder_list_b.png'" onmouseout="this.src='images/nsorder_list.png'" height="200px;"></a>
					<a href="./pc_Initialfailure_list_support.php" target="_blank"><img src="images/pc_Initialfailure_list.png" onmouseover="this.src='images/pc_Initialfailure_list_b.png'" onmouseout="this.src='images/pc_Initialfailure_list.png'"></a>
					</p>
					<p align="left">
					<a href="./hanbai_rieki_category.php" target="_blank"><img src="images/rieki_category.png" onmouseover="this.src='images/rieki_category_b.png'" onmouseout="this.src='images/rieki_category.png'" height="200px;" width="320px;"></a>
					<a href="./pc_henpin_list_support.php" target="_blank"><img src="images/pc_failure_list_h.png" onmouseover="this.src='images/pc_failure_list_h_b.png'" onmouseout="this.src='images/pc_failure_list_h.png'"></a>
					<a href="./reserv_list_dispatch.php" target="_blank"><img src="images/reserv_list.png" onmouseover="this.src='images/reserv_list_b.png'" onmouseout="this.src='images/reserv_list.png'" height="200px;" width="320px;"></a>
					</p>
				<h2>会場詳細メニュー</h2>
					<p align="left">
					<a href="./kaijyo_top.php" target="_blank"><img src="images/kaijyo_top.png" onmouseover="this.src='images/kaijyo_top_b.png'" onmouseout="this.src='images/kaijyo_top.png'"></a>
					<a href="./syousai_display.php" target="_blank"><img src="images/syousai_display.png" onmouseover="this.src='images/syousai_display_b.png'" onmouseout="this.src='images/syousai_display.png'"></a>
					</p>
				<h2>JSPメニュー</h2>
					<p align="left">
					<a href="./jsp_error_list.php" target="_blank"><img src="images/jsp_error_list.png" onmouseover="this.src='images/jsp_error_list_b.png'" onmouseout="this.src='images/jsp_error_list.png'" height="200px;" width="320px;"></a>
					</p>
				<h2>WPSメニュー</h2>
					<p align="left">
					<a href="./wps_input.php?kbn=2" target="_blank"><img src="images/wps_input.png" onmouseover="this.src='images/wps_input_b.png'" onmouseout="this.src='images/wps_input.png'"></a>
					<a href="./wps_check.php?kbn=2" target="_blank"><img src="images/wps_check.png" onmouseover="this.src='images/wps_check_b.png'" onmouseout="this.src='images/wps_check.png'"></a>
					</p>
				<h2>DMメニュー</h2>
					<p align="left">
					<a href="./tellorder_check.php" target="_blank"><img src="images/tellorder_check.png" onmouseover="this.src='images/tellorder_check_b.png'" onmouseout="this.src='images/tellorder_check.png'"></a>
					<a href="./personal_delete.php" target="_blank"><img src="images/dm_back.png" onmouseover="this.src='images/dm_back_b.png'" onmouseout="this.src='images/dm_back.png'" height="200px;" width="320px;"></a>
					</p>
				<h2>タウンメールメニュー</h2>
					<p align="left">
					<a href="./postoffice_refer.php" target="_blank"><img src="images/postoffice_refer.png" onmouseover="this.src='images/postoffice_refer_b.png'" onmouseout="this.src='images/postoffice_refer.png'"></a>
					<a href="./postoffice_list.php" target="_blank"><img src="images/postoffice_list.png" onmouseover="this.src='images/postoffice_list_b.png'" onmouseout="this.src='images/postoffice_list.png'"></a>
					<a href="./tm_staff.php" target="_blank"><img src="images/tm_staff.png" onmouseover="this.src='images/tm_staff_b.png'" onmouseout="this.src='images/tm_staff.png'"></a><br>
					</p>
				<h2>広報業者メニュー</h2>
					<p align="left">
					<a href="./posting_list2.php" target="_blank"><img src="images/posting.png" onmouseover="this.src='images/posting_b.png'" onmouseout="this.src='images/posting.png'" height="200px;" width="320px;"></a>
					</p>
				<?php } ?>
				<?php if ($p_compcd <> "S") { ?>
					<?php if ($p_compcd == "J" or $p_compcd == "H" or $p_compcd == "Z" or $p_compcd == "Y") { ?>
					<?
					// ================================================
					// ■　□　■　□　販売実績取得　■　□　■　□
					// ================================================
					//----- データ抽出
					$today = date('Y-m-d');
					$query = "
						SELECT
							A.lane, A.staff  ,A.eventflg  ,B.prefecture  ,B.city ,SUM(N.buynum) as buynum
						FROM
							php_performance A
							INNER JOIN
								php_facility B
							ON
								B.facility_id = A.facility_id
							LEFT OUTER JOIN (
								SELECT
									M.lane, M.section, M.staff, IFNULL(IF(SUM(M.h_buynum)>0, SUM(M.h_buynum), SUM(M.b_buynum)),0) as buynum, M.kbn
								FROM (
									SELECT
										E.lane, C.section, E.staff, IFNULL(SUM(C.hannum), 0)+IFNULL(SUM(C.grenum), 0)+IFNULL(SUM(C.mrenum), 0)+IFNULL(SUM(C.c_grenum), 0)+IFNULL(SUM(C.c_mrenum), 0) as h_buynum
										, 0 as b_buynum, C.kbn
									FROM php_t_pc_hanbai C
										LEFT OUTER JOIN
											php_performance E
										ON
											C.venueid=CONCAT(DATE_FORMAT(E.buydt , '%Y%m%d' ), LPAD(E.lane,2,'0') , '-' , E.branch)
									WHERE C.delflg=0
									AND C.henpinflg=0
									AND C.kbn in (1,4,5)
									AND E.buydt = '$today'
									GROUP BY C.venueid, C.section, C.kbn
									UNION ALL
									SELECT
										E.lane, D.section, E.staff, 0 as h_buynum
										, IFNULL(SUM(C.hannum), 0)+IFNULL(SUM(C.c_grenum), 0)+IFNULL(SUM(C.c_mrenum), 0) as b_buynum, C.kbn
									FROM php_t_pc_barcode C
										LEFT OUTER JOIN
											php_t_pc_receipt D
										ON
											C.receiptno=CONCAT(D.venueid,D.resisterno, LPAD(D.venueno,3,'0'))
										LEFT OUTER JOIN
											php_performance E
										ON
											D.venueid=CONCAT(DATE_FORMAT(E.buydt , '%Y%m%d' ), LPAD(E.lane,2,'0') , '-' , E.branch)
									WHERE C.delflg=0 AND C.kbn in (1,4,5)
									AND E.buydt = '$today'
									GROUP BY D.venueid, D.section, C.kbn
								)M
								GROUP BY M.section, M.lane, M.kbn
							)N ON A.lane=N.lane
						WHERE A.buydt = '$today'
						GROUP BY A.lane
						ORDER BY A.lane
					";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($query))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$arr_performance = [];
					$eventflg = 1;
					while ($row = $rs->fetch_array()) {
						$arr_performance[] = $row;
					}
					if (count($arr_performance) > 0) {
						$px = 1.3;
						if ($eventflg == 1) { $px = 0.5; }
					?>
					<h2>販売実績速報</h2>
						<div class="fare">
						<left>
							<div class="fare-calendar">
							    <!--本日の販売実績-->
								【本日の販売実績】
								<div class="fare-rates">
									<div class="fare-monthcontainer">
										<ul>
											<?
											$all_buynum=0;
											for($i=0; $i<count($arr_performance); $i++) {
											?>
												<li class="fare-month">
													<span class="ttl2">
														<? if ($arr_performance[$i]['buynum'] <> "") { ?>
														<b><? echo $arr_performance[$i]['buynum'] ?></b><font size="3">台</font>
														<? $all_buynum +=$arr_performance[$i]['buynum']; ?>
														<? } ?>
													</span>
													<span class="fare-price" style="height:<? echo $arr_performance[$i]['buynum']  * $px ?>px;" ></span>
													<span class="ttl1" style="line-height: 1.0;">
														<font size="5">
															<b><? echo $arr_performance[$i]['lane'] ?></b>ﾚｰﾝ
														</font>
														<br>
														<font size="2">
															<b><? echo $arr_performance[$i]['prefecture'] ?></b>
														</font>
														<br>
														<font size="1">
															<b><? echo $arr_performance[$i]['city'] ?></b>
														</font>
														<br>
														<font size="4">
															<b><? echo $arr_performance[$i]['staff'] ?></b>
														</font>
													</span>
												</li>
											<?
											}
											?>
											<li class="fare-month">
												<span class="ttl2">
													<? if ($all_buynum > 0) { ?>
													<b><? echo $all_buynum ?></b><font size="3">台</font>
													<? } ?>
												</span>
												<span class="fare-price" style="height:<? echo $all_buynum  * $px ?>px;" ></span>
												<span class="ttl1" style="line-height: 1.0;">
													<font size="5">
														<b></b>
													</font>
													<br>
													<font size="2">
														<b></b>
													</font>
													<br>
													<font size="1">
														<b></b>
													</font>
													<br>
													<font size="4">
														<b>合計</b>
													</font>
												</span>
											</li>
										</ul>
									</div>
								</div>
							    <!--目標実績-->
								<? if ($eventflg == 1) { ?>
									<?
									// ================================================
									// ■　□　■　□　販売実績取得　■　□　■　□
									// ================================================
									//----- データ抽出
									$query = "";
									$query .= " SELECT  AA.lane  ,AA.goalnum  ,sum(AA.buynum - IFNULL(AA.hannum,0)) as buynum ";
									$query .= " from  ";
									$query .= " ( ";
									$query .= " SELECT  A.buydt, A.lane, A.branch ,A.goalnum  ,SUM(A.buynum) as buynum, A.week, 0 as hannum, A.eventflg ";
									$query .= "   FROM ( ";									
									
									$query .= " SELECT M.buydt, M.lane, M.branch, M.goalnum, M.week, M.eventflg, M.section, M.staff, IFNULL(IF(M.h_buynum>0, M.h_buynum, M.b_buynum),0) as buynum ";
									$query .= " FROM ( ";
									$query .= " SELECT E.buydt, E.lane, E.branch, E.goalnum, E.week, E.eventflg, C.section, E.staff, IFNULL(SUM(C.hannum), 0)+IFNULL(SUM(C.grenum), 0)+IFNULL(SUM(C.mrenum), 0)+IFNULL(SUM(C.c_grenum), 0)+IFNULL(SUM(C.c_mrenum), 0) as h_buynum, 0 as b_buynum  ";
									$query .= " FROM php_t_pc_hanbai C ";
									$query .= " LEFT OUTER JOIN php_performance E ON C.venueid=CONCAT(DATE_FORMAT(E.buydt , '%Y%m%d' ), LPAD(E.lane,2,'0') , '-' , E.branch)  ";
									$query .= " WHERE C.delflg=0 AND C.henpinflg=0 AND C.kbn in (1,4,5) ";
									$query .= " AND E.week  = (SELECT week FROM php_calendar where date ='" . date('Y/m/d') . "')";
									$query .= " GROUP BY C.venueid, C.section  ";
									$query .= " UNION ALL ";
									$query .= " SELECT E.buydt, E.lane, E.branch, E.goalnum, E.week, E.eventflg, D.section, E.staff, 0 as h_buynum, IFNULL(SUM(C.hannum), 0)+IFNULL(SUM(C.c_grenum), 0)+IFNULL(SUM(C.c_mrenum), 0) as b_buynum  ";
									$query .= " FROM php_t_pc_barcode C  ";
									$query .= " LEFT OUTER JOIN php_t_pc_receipt D ON C.receiptno=CONCAT(D.venueid,D.resisterno, LPAD(D.venueno,3,'0'))  ";
									$query .= " LEFT OUTER JOIN php_performance E ON D.venueid=CONCAT(DATE_FORMAT(E.buydt , '%Y%m%d' ), LPAD(E.lane,2,'0') , '-' , E.branch)  ";
									$query .= " WHERE C.delflg=0 AND C.kbn in (1,4,5) ";
									$query .= " AND E.week  = (SELECT week FROM php_calendar where date ='" . date('Y/m/d') . "')";
									$query .= " GROUP BY D.venueid, D.section ";
									$query .= " UNION ALL ";
									$query .= " SELECT E.buydt, E.lane, E.branch, E.goalnum, E.week, E.eventflg, 0 as section, E.staff, 0 as h_buynum, 0 as b_buynum  ";
									$query .= " FROM php_performance E ";
									$query .= " WHERE E.week  = (SELECT week FROM php_calendar where date ='" . date('Y/m/d') . "')";
								//	$query .= " AND E.goalnum > 0 ";
									$query .= " )M ";
									$query .= " GROUP BY M.lane,section, M.buydt ";
									
									$query .= " )A ";
									$query .= " WHERE A.week  = (SELECT week FROM php_calendar where date ='" . date('Y/m/d') . "')";
									$query .= "   GROUP BY A.lane  ,A.goalnum  ,A.buynum, A.buydt ";
									$query .= " ) AA ";
									$query .= "   GROUP BY AA.lane  ,AA.goalnum ";
									$query .= "   ORDER BY AA.lane ";
									$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
									$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
									if (!($rs = $db->query($query))) {
										$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
									}
									$arr_goalnum = [];
									while ($row = $rs->fetch_array()) {
										$arr_goalnum[] = $row;
									}
									$query = " SELECT sum(AAA.goalnum) as goalnum  ,sum(AAA.buynum) as buynum ";
									$query .= " from  ";
									$query .= " ( ";
									$query .= " SELECT  AA.lane  ,AA.goalnum  ,sum(AA.buynum - IFNULL(AA.hannum,0)) as buynum ";
									$query .= " from  ";
									$query .= " ( ";
									$query .= " SELECT  A.buydt, A.lane, A.branch ,A.goalnum  ,SUM(A.buynum) as buynum, A.week, 0 as hannum, A.eventflg ";
									$query .= "   FROM ( ";
									
									$query .= " SELECT M.buydt, M.lane, M.branch, M.goalnum, M.week, M.eventflg, M.section, M.staff, IFNULL(IF(M.h_buynum>0, M.h_buynum, M.b_buynum),0) as buynum ";
									$query .= " FROM ( ";
									$query .= " SELECT E.buydt, E.lane, E.branch, E.goalnum, E.week, E.eventflg, C.section, E.staff, IFNULL(SUM(C.hannum), 0)+IFNULL(SUM(C.grenum), 0)+IFNULL(SUM(C.mrenum), 0)+IFNULL(SUM(C.c_grenum), 0)+IFNULL(SUM(C.c_mrenum), 0) as h_buynum, 0 as b_buynum  ";
									$query .= " FROM php_t_pc_hanbai C ";
									$query .= " LEFT OUTER JOIN php_performance E ON C.venueid=CONCAT(DATE_FORMAT(E.buydt , '%Y%m%d' ), LPAD(E.lane,2,'0') , '-' , E.branch)  ";
									$query .= " WHERE C.delflg=0 AND C.henpinflg=0 AND C.kbn in (1,4,5) ";
									$query .= " AND E.week  = (SELECT week FROM php_calendar where date ='" . date('Y/m/d') . "')";
								//	$query .= " AND E.goalnum > 0 ";
									$query .= " GROUP BY C.venueid, C.section  ";
									$query .= " UNION ALL ";
									$query .= " SELECT E.buydt, E.lane, E.branch, E.goalnum, E.week, E.eventflg, D.section, E.staff, 0 as h_buynum, IFNULL(SUM(C.hannum), 0)+IFNULL(SUM(C.c_grenum), 0)+IFNULL(SUM(C.c_mrenum), 0) as b_buynum  ";
									$query .= " FROM php_t_pc_barcode C  ";
									$query .= " LEFT OUTER JOIN php_t_pc_receipt D ON C.receiptno=CONCAT(D.venueid,D.resisterno, LPAD(D.venueno,3,'0'))  ";
									$query .= " LEFT OUTER JOIN php_performance E ON D.venueid=CONCAT(DATE_FORMAT(E.buydt , '%Y%m%d' ), LPAD(E.lane,2,'0') , '-' , E.branch)  ";
									$query .= " WHERE C.delflg=0 AND C.kbn in (1,4,5) ";
									$query .= " AND E.week  = (SELECT week FROM php_calendar where date ='" . date('Y/m/d') . "')";
								//	$query .= " AND E.goalnum > 0 ";
									$query .= " GROUP BY D.venueid, D.section ";
									$query .= " UNION ALL ";
									$query .= " SELECT E.buydt, E.lane, E.branch, E.goalnum, E.week, E.eventflg, 0 as section, E.staff, 0 as h_buynum, 0 as b_buynum  ";
									$query .= " FROM php_performance E ";
									$query .= " WHERE E.week  = (SELECT week FROM php_calendar where date ='" . date('Y/m/d') . "')";
								//	$query .= " AND E.goalnum > 0 ";
									$query .= " )M ";
									$query .= " GROUP BY M.lane, M.section, M.buydt, M.branch ";
									
									$query .= " )A ";
									$query .= " WHERE A.week  = (SELECT week FROM php_calendar where date ='" . date('Y/m/d') . "')";
									$query .= "   GROUP BY A.lane  ,A.goalnum  ,A.buynum, A.buydt ";
									$query .= " ) AA ";
									$query .= "   GROUP BY AA.lane  ,AA.goalnum ";
									$query .= " ) AAA ";
									$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
									$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
									if (!($rs = $db->query($query))) {
										$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
									}
									$sum_goalnum = "";
									while ($row = $rs->fetch_array()) {
										$sum_goalnum[] = $row;
									}
									?>
									【今週の目標実績】
									<div class="fare-rates">
										<div class="fare-monthcontainer">
											<ul>
												<?
												for($i=0; $i<count($arr_performance); $i++) {
													$goalnum = 0;
													$buynum = 0;
													$percentage = 0;
													//イベントの会場のみ実績を取る
													for($j=0; $j<count($arr_goalnum); $j++) {
														if ($arr_performance[$i]['lane'] == $arr_goalnum[$j]['lane']) {
															$goalnum = $arr_goalnum[$j]['goalnum'];
															$buynum = $arr_goalnum[$j]['buynum'];
															if ($arr_goalnum[$j]['goalnum'] > 0) {
																$percentage = round($arr_goalnum[$j]['buynum'] / $arr_goalnum[$j]['goalnum'] * 100);
															}
															break;
														}
													}
												?>
													<li class="fare-month">
														<span class="ttl2">
															<? if ($percentage > 0) { ?>
															<b><? echo $percentage ?></b><font size="3">％</font>
																<? if ($percentage>=100){ ?>
																	<br><b><font size="2">☆目標達成☆</font></b>
																<? } ?>
															<? } ?>
														</span>
														<span class="fare-price <? if ($percentage>=100){echo "cheapest";} ?>" style="height:<? echo $percentage  * 1.3 ?>px;" ></span>
														<span class="ttl1" style="line-height: 1.0;">
															<font size="5">
																<b><? echo $arr_performance[$i]['lane'] ?></b>ﾚｰﾝ
															</font>
															<br>
															<font size="2">
																目標:<b><? echo $goalnum ?></b>
															</font>
															<br>
															<font size="2">
																実績:<b><? echo $buynum ?></b>
															</font>
															<br>
															<font size="2">
																差分:<b><? echo $buynum - $goalnum ?></b>
															</font>
														</span>
													</li>
												<?
												}
												?>
												<!--　総合計目標　-->
												<? 
													$goalnum = $sum_goalnum[0]['goalnum'];
													$buynum = $sum_goalnum[0]['buynum'];
													if ($sum_goalnum[0]['goalnum'] > 0) {
														$percentage = round($sum_goalnum[0]['buynum'] / $sum_goalnum[0]['goalnum'] * 100);
													}
												?>
												<li class="fare-month">
													<span class="ttl2">
														<? if ($percentage > 0) { ?>
														<b><? echo $percentage ?></b><font size="3">％</font>
															<? if ($percentage>=100){ ?>
																<br><b><font size="2">☆目標達成☆</font></b>
															<? } ?>
														<? } ?>
													</span>
													<span class="fare-price <? if ($percentage>=100){echo "cheapest";} ?>" style="height:<? echo $percentage  * 1.3 ?>px;" ></span>
													<span class="ttl1" style="line-height: 1.0;">
														<font size="3">
															<b>全体目標</b>
														</font>
														<br>
														<font size="2">
															目標:<b><? echo $goalnum ?></b>
														</font>
														<br>
														<font size="2">
															実績:<b><? echo $buynum ?></b>
														</font>
														<br>
														<font size="2">
															差分:<b><? echo $buynum - $goalnum ?></b>
														</font>
													</span>
												</li>
											</ul>
										</div>
									</div>
								<? } ?>
								<?php if ($p_compcd <> "Y") { ?>
								<a href="./performance_input2.php" target="_blank" class="btn-square-pop" style="width: 200px;">会場実績登録</a>　　
								<a href="./repair_input.php" target="_blank" class="btn-square-pop" style="width: 200px;">修理実績登録</a>　　
								<a href="./event_menu.php" target="_blank" class="btn-square-pop" style="width: 200px;">イベントメニュー</a>
								<? } ?>
							</div>
						</left>
						</div>
					<? } ?>
					<?php if ($p_compcd == "Y") { ?>
						<h2>販売＆会場詳細メニュー</h2>
							<p align="left">
							<a href="./inquiry_hanbai.php" target="_blank"><img src="images/inquiry_hanbai.png" onmouseover="this.src='images/inquiry_hanbai_b.png'" onmouseout="this.src='images/inquiry_hanbai.png'"></a>
							<a href="./kaijyo_top.php" target="_blank"><img src="images/kaijyo_top.png" onmouseover="this.src='images/kaijyo_top_b.png'" onmouseout="this.src='images/kaijyo_top.png'"></a>
							</p>
					<?php } else  { ?>
						<?php if ($p_compcd != "Z") { ?>
						<h2>社内関連メニュー</h2>
							<p align="left">
							<a href="./shanai.php" target="_blank"><img src="images/shanai.png" onmouseover="this.src='images/shanai_b.png'" onmouseout="this.src='images/shanai.png'"></a>
							<a href="./manual/top_s.php" target="_blank"><img src="images/manual.png" onmouseover="this.src='images/manual_b.png'" onmouseout="this.src='images/manual.png'"></a>
							<a href="https://tel.jemtc.top/" target="_blank"><img src="images/tel_jemtc_top.png" onmouseover="this.src='images/tel_jemtc_top_b.png'" onmouseout="this.src='images/tel_jemtc_top.png'"></a><br>
							<a href="./order_request_list.php" target="_blank"><img src="images/order_request_list.png" onmouseover="this.src='images/order_request_list_b.png'" onmouseout="this.src='images/order_request_list.png'"></a>
							<a href="./receive_input.php" target="_blank"><img src="images/receive_input.png" onmouseover="this.src='images/receive_input_b.png'" onmouseout="this.src='images/receive_input.png'"></a>
							<a href="./accept_in_menu.php" target="_blank"><img src="images/accept_in_menu.png" onmouseover="this.src='images/accept_in_menu_b.png'" onmouseout="this.src='images/accept_in_menu.png'"></a><br>
							<a href="./honbu_zaiko_menu.php" target="_blank"><img src="images/honbu_zaiko_menu.png" onmouseover="this.src='images/honbu_zaiko_menu_b.png'" onmouseout="this.src='images/honbu_zaiko_menu.png'"></a>
							<a href="./get_productkey.php" target="_blank"><img src="images/get_productkey.png" onmouseover="this.src='images/get_productkey_b.png'" onmouseout="this.src='images/get_productkey.png'"></a>
							</p>
						<?php } ?>
						<?php if ($p_compcd == "Z") { ?>
						<h2>社内関連メニュー</h2>
							<p align="left">
							<a href="./manual/top_s.php" target="_blank"><img src="images/manual.png" onmouseover="this.src='images/manual_b.png'" onmouseout="this.src='images/manual.png'"></a>
							<a href="https://tel.jemtc.top/" target="_blank"><img src="images/tel_jemtc_top.png" onmouseover="this.src='images/tel_jemtc_top_b.png'" onmouseout="this.src='images/tel_jemtc_top.png'"></a>
							<a href="./ns_list_dispatch.php" target="_blank"><img src="images/nsorder_list.png" onmouseover="this.src='images/nsorder_list_b.png'" onmouseout="this.src='images/nsorder_list.png'" height="200px;"></a>
							</p>
						<?php } ?>
						<?php if ($p_compcd != "M") { ?>
						<h2>販売・修理入力メニュー</h2>
							<p align="left">
							<a href="./performance_input.php" target="_blank"><img src="images/performance_input.png" onmouseover="this.src='images/performance_input_b.png'" onmouseout="this.src='images/performance_input.png'"></a>
							<a href="./reservationshipment_input3.php" target="_blank"><img src="images/reservationshipment_input.png" onmouseover="this.src='images/reservationshipment_input_b.png'" onmouseout="this.src='images/reservationshipment_input.png'"></a>
							<a href="./tel_order_d.php?display=2" target="_blank"><img src="images/tel_order_d3.png" onmouseover="this.src='images/tel_order_d3_b.png'" onmouseout="this.src='images/tel_order_d3.png'"></a><br>
							<?php if (!($p_Auth == 0 && $p_compcd == "H")) { ?>
								<a href="./syuukei_list.php" target="_blank"><img src="images/syuukei.png" onmouseover="this.src='images/syuukei_b.png'" onmouseout="this.src='images/syuukei.png'"></a>
								<a href="./goalplan_input.php" target="_blank"><img src="images/goalplan_input.png" onmouseover="this.src='images/goalplan_input_b.png'" onmouseout="this.src='images/goalplan_input.png'"></a>
									<?php if ($p_ns > 0) { ?>
										<a href="./inventories_check3.php" target="_blank"><img src="images/inventories_check.png" onmouseover="this.src='images/inventories_check_b.png'" onmouseout="this.src='images/inventories_check.png'"></a>
									<? } ?><br>
								<a href="./zaiko.php" target="_blank"><img src="images/zaiko_g.png" onmouseover="this.src='images/zaiko_g_b.png'" onmouseout="this.src='images/zaiko_g.png'"></a>
								<a href="./keiri_list.php" target="_blank"><img src="images/keiri_list.png" onmouseover="this.src='images/keiri_list_b.png'" onmouseout="this.src='images/keiri_list.png'"></a>
								<a href="./shipment_menu.php" target="_blank"><img src="images/shipment_menu.png" onmouseover="this.src='images/shipment_menu_b.png'" onmouseout="this.src='images/shipment_menu.png'"></a><br>
								<a href="./henpin_detail.php" target="_blank"><img src="images/henpin_detail.png" onmouseover="this.src='images/henpin_detail_b.png'" onmouseout="this.src='images/henpin_detail.png'"></a>
								<a href="./henpin_kanri.php?kbn=1" target="_blank"><img src="images/henpin_kanri.png" onmouseover="this.src='images/henpin_kanri_b.png'" onmouseout="this.src='images/henpin_kanri.png'"></a>
								<a href="./henpin_kanri.php?kbn=2" target="_blank"><img src="images/henpin_kanri2.png" onmouseover="this.src='images/henpin_kanri2_b.png'" onmouseout="this.src='images/henpin_kanri2.png'"></a>
							<?php } ?>
							<a href="./reservationshipment_ec.php" target="_blank"><img src="images/reservationshipment_ec.png" onmouseover="this.src='images/reservationshipment_ec_b.png'" onmouseout="this.src='images/reservationshipment_ec.png'" height="200px;" width="320px;"></a>
							<?php if($p_compcd == "H"){ ?>
								<a href="./repair_list.php" target="_blank"><img src="images/repair_list.png" onmouseover="this.src='images/repair_list_b.png'" onmouseout="this.src='images/repair_list.png'" height="200px;" width="320px;"></a><br>
							<?php } ?>
							</p>
						<?php if (!($p_compcd == "H")) { ?>
						<h2>販売・修理確認メニュー</h2>
							<p align="left">
							<a href="./inquiry_hanbai.php" target="_blank"><img src="images/inquiry_hanbai.png" onmouseover="this.src='images/inquiry_hanbai_b.png'" onmouseout="this.src='images/inquiry_hanbai.png'"></a>
							<a href="./hanbai_rieki.php" target="_blank"><img src="images/hanbai_rieki.png" onmouseover="this.src='images/hanbai_rieki_b.png'" onmouseout="this.src='images/hanbai_rieki.png'"></a>
							<a href="./option_hanbai_rieki.php" target="_blank"><img src="images/option_rieki.png" onmouseover="this.src='images/option_rieki_b.png'" onmouseout="this.src='images/option_rieki.png'"></a><br>
							<a href="./inquiry_headoffice.php" target="_blank"><img src="images/inquiry_headoffice.png" onmouseover="this.src='images/inquiry_headoffice_b.png'" onmouseout="this.src='images/inquiry_headoffice.png'"></a>
							<a href="./tel_order_d.php?display=3" target="_blank"><img src="images/tel_order_d3.png" onmouseover="this.src='images/tel_order_d3_b.png'" onmouseout="this.src='images/tel_order_d3.png'"></a>
							<a href="./repair_list.php" target="_blank"><img src="images/repair_list.png" onmouseover="this.src='images/repair_list_b.png'" onmouseout="this.src='images/repair_list.png'" height="200px;" width="320px;"></a><br>
							<a href="./ns_user_order_d.php?display=3" target="_blank"><img src="images/ns_order_d.png" onmouseover="this.src='images/ns_order_d_b.png'" onmouseout="this.src='images/ns_order_d.png'" height="200px;" width="320px;"></a>
							<a href="./hanbai_rieki_category.php" target="_blank"><img src="images/rieki_category.png" onmouseover="this.src='images/rieki_category_b.png'" onmouseout="this.src='images/rieki_category.png'" height="200px;" width="320px;"></a>
							<? if($p_h_flg > 0){ ?>
								<a href="./inquiry_hanbai_high.php" target="_blank"><img src="images/inquiry_hanbai_high.png" onmouseover="this.src='images/inquiry_hanbai_high_b.png'" onmouseout="this.src='images/inquiry_hanbai_high.png'"></a>
							<? } ?>
							</p>
						<?php } ?>
						<?php } ?>
						<?php if($p_compcd == "H"){ ?>
							<h2>出荷登録関連メニュー</h2>
							<p align="left">
							<a href="./zaiko_factory.php" target="_blank"><img src="images/zaiko_list.png" onmouseover="this.src='images/zaiko_list_b.png'" onmouseout="this.src='images/zaiko_list.png'"></a>
							<a href="./zaiko_input_factory.php" target="_blank"><img src="images/zaiko_input_factory.png" onmouseover="this.src='images/zaiko_input_factory_b.png'" onmouseout="this.src='images/zaiko_input_factory.png'"></a>
							<a href="./shipment_input_factory_t.php" target="_blank"><img src="images/zaiko_f_input.png" onmouseover="this.src='images/zaiko_f_input_b.png'" onmouseout="this.src='images/zaiko_f_input.png'"></a><br>
							<a href="./zaiko_f_input.php" target="_blank"><img src="images/zaiko_i_input.png" onmouseover="this.src='images/zaiko_i_input_b.png'" onmouseout="this.src='images/zaiko_i_input.png'"></a>
							<!--在庫修正-->
							<a href="./zaiko_chg_factory.php" target="_blank"><img src="images/zaiko_h.png" onmouseover="this.src='images/zaiko_h_b.png'" onmouseout="this.src='images/zaiko_h.png'"></a>
							<a href="./zaiko_f_input.php?kbn=0" target="_blank"><img src="images/add_shipment.png" onmouseover="this.src='images/add_shipment_b.png'" onmouseout="this.src='images/add_shipment.png'"></a><br>
							<a href="./shipment_input.php" target="_blank"><img src="images/shipment_input.png" onmouseover="this.src='images/shipment_input_b.png'" onmouseout="this.src='images/shipment_input.png'"></a>
							<a href="./ecommerce_slip_repair.php" target="_blank"><img src="images/ecommerce_output.png" onmouseover="this.src='images/ecommerce_output_b.png'" onmouseout="this.src='images/ecommerce_output.png'"></a>
							<a href="./yamato_upload.php" target="_blank"><img src="images/yamato_upload.png" onmouseover="this.src='images/yamato_upload_b.png'" onmouseout="this.src='images/yamato_upload.png'"></a><br>
							<a href="./shipment_slip.php" target="_blank"><img src="images/shipment_slip_a.jpg" onmouseover="this.src='images/shipment_slip_b.jpg'" onmouseout="this.src='images/shipment_slip_a.jpg'"></a>
							</p>
							<h2>WPS関連メニュー</h2>
							<p align="left">
							<a href="./wps_input.php?kbn=2" target="_blank"><img src="images/wps_input.png" onmouseover="this.src='images/wps_input_b.png'" onmouseout="this.src='images/wps_input.png'"></a>
							<a href="./wps_check.php" target="_blank"><img src="images/wps_check.png" onmouseover="this.src='images/wps_check_b.png'" onmouseout="this.src='images/wps_check.png'"></a>
							<a href="./wps_output.php" target="_blank"><img src="images/wps_output.png" onmouseover="this.src='images/wps_output_b.png'" onmouseout="this.src='images/wps_output.png'"></a>
							</p>
						<?php } ?>
						<h2>会場詳細メニュー</h2>
							<p align="left">
							<a href="./kaijyo_top.php" target="_blank"><img src="images/kaijyo_top.png" onmouseover="this.src='images/kaijyo_top_b.png'" onmouseout="this.src='images/kaijyo_top.png'"></a>
							<a href="./syousai_display.php" target="_blank"><img src="images/syousai_display.png" onmouseover="this.src='images/syousai_display_b.png'" onmouseout="this.src='images/syousai_display.png'"></a>
							<a href="./performance_payment_list.php" target="_blank"><img src="images/performance_payment_list.png" onmouseover="this.src='images/performance_payment_list_b.png'" onmouseout="this.src='images/performance_payment_list.png'"></a>
							<br>
							<a href="./holding_pref_venue.php" target="_blank"><img src="images/holding_pref_venue.png" onmouseover="this.src='images/holding_pref_venue_b.png'" onmouseout="this.src='images/holding_pref_venue.png'"></a>
							<a href="./results_by_prefecture.php" target="_blank"><img src="images/results_by_prefecture.png" onmouseover="this.src='images/results_by_prefecture_b.png'" onmouseout="this.src='images/results_by_prefecture.png'"></a>
							<br>
							<a href="./facility_check.php" target="_blank"><img src="images/facility_check.png" onmouseover="this.src='images/facility_check_b.png'" onmouseout="this.src='images/facility_check.png'"></a>
							<a href="./staff_schedule.php" target="_blank"><img src="images/staff_schedule.png" onmouseover="this.src='images/staff_schedule_b.png'" onmouseout="this.src='images/staff_schedule.png'"></a>
							<br>
							<a href="./kaijyo_humaknex_input.php" target="_blank"><img src="images/kaijyo_humaknex_input.png" onmouseover="this.src='images/kaijyo_humaknex_input_b.png'" onmouseout="this.src='images/kaijyo_humaknex_input.png'"></a>
							</p>
						<?php if ($p_acco == 1 ) { ?>
						<h2>返金メニュー</h2>
							<p align="left">
							<a href="./pc_failure_nyukin_list.php" target="_blank"><img src="images/pc_failure_nyukin_list.png" onmouseover="this.src='images/pc_failure_nyukin_list_b.png'" onmouseout="this.src='images/pc_failure_nyukin_list.png'"></a>
							<a href="./pc_failure_send_list.php" target="_blank"><img src="images/pc_failure_send_list.png" onmouseover="this.src='images/pc_failure_send_list_b.png'" onmouseout="this.src='images/pc_failure_send_list.png'"></a>
							<a href="./pc_failure_send_list_ns.php" target="_blank"><img src="images/pc_failure_send_list_ns.png" onmouseover="this.src='images/pc_failure_send_list_ns_b.png'" onmouseout="this.src='images/pc_failure_send_list_ns.png'" height="200px;" width="320px;"></a>
							</p>
						<?php } ?>
						<?php if ($p_compcd == "J" or $p_compcd == "Z") { ?>
						<h2>タウンメールメニュー</h2>
							<p align="left">
							<a href="./tm_upload.php" target="_blank"><img src="images/tm_upload.png" onmouseover="this.src='images/tm_upload_b.png'" onmouseout="this.src='images/tm_upload.png'"></a>
							<a href="./tm_input1.php" target="_blank"><img src="images/tm_input.png" onmouseover="this.src='images/tm_input_b.png'" onmouseout="this.src='images/tm_input.png'"></a>
							<a href="http://people.mapexpert.net/AddrKenList" target="_blank"><img src="images/mapexpert.png" onmouseover="this.src='images/mapexpert_b.png'" onmouseout="this.src='images/mapexpert.png'"></a><br>
							<a href="./tm_refer.php" target="_blank"><img src="images/tm_input_s.png" onmouseover="this.src='images/tm_input_s_b.png'" onmouseout="this.src='images/tm_input_s.png'"></a>
							<a href="./postoffice_refer.php" target="_blank"><img src="images/postoffice_refer.png" onmouseover="this.src='images/postoffice_refer_b.png'" onmouseout="this.src='images/postoffice_refer.png'"></a>
							<a href="./tm_staff.php" target="_blank"><img src="images/tm_staff.png" onmouseover="this.src='images/tm_staff_b.png'" onmouseout="this.src='images/tm_staff.png'"></a><br>
							<a href="./postoffice_list.php" target="_blank"><img src="images/postoffice_list.png" onmouseover="this.src='images/postoffice_list_b.png'" onmouseout="this.src='images/postoffice_list.png'"></a>
							<? if($p_tm == 1){ ?>
							<!--	<a href="./syousai_upload2.php" target="_blank"><img src="images/expenses_upload.png" onmouseover="this.src='images/expenses_upload_b.png'" onmouseout="this.src='images/expenses_upload.png'"></a>-->
								<a href="./postoffice_input.php" target="_blank"><img src="images/postoffice_input.png" onmouseover="this.src='images/postoffice_input_b.png'" onmouseout="this.src='images/postoffice_input.png'"></a>
							<? } ?>
							</p>
						<?php } ?>
						<h2>DMメニュー</h2>
							<p align="left">
							<a href="./dm_area_list.php" target="_blank"><img src="images/dm_area_list.png" onmouseover="this.src='images/dm_area_list_b.png'" onmouseout="this.src='images/dm_area_list.png'"></a>
							<a href="./tellorder_check.php" target="_blank"><img src="images/tellorder_check.png" onmouseover="this.src='images/tellorder_check_b.png'" onmouseout="this.src='images/tellorder_check.png'"></a>
							<a href="./personal_delete.php" target="_blank"><img src="images/dm_back.png" onmouseover="this.src='images/dm_back_b.png'" onmouseout="this.src='images/dm_back.png'" height="200px;" width="320px;"></a>
							</p>
						<? if($p_tm == 1){ ?>
							<h2>折込広告メニュー</h2>
								<p align="left">
								<a href="./sheet_area_list.php" target="_blank"><img src="images/sheet_area_list.png" onmouseover="this.src='images/sheet_area_list_b.png'" onmouseout="this.src='images/sheet_area_list.png'" height="200px;" width="320px;"></a>
								</p>
						<? } ?>
						<h2>ホテル関連メニュー</h2>
							<p align="left">
							<a href="http://forincs.com/aceat/aceat.html" target="_blank"><img src="images/hotel.png" onmouseover="this.src='images/hotel_b.png'" onmouseout="this.src='images/hotel.png'"></a>
							</p>
						<h2>ＨＰ関連メニュー</h2>
							<p align="left">
							<a href="https://xn--n8jo6b6g7aydt115d.com/" target="_blank"><img src="images/hp_event.png" onmouseover="this.src='images/hp_event_b.png'" onmouseout="this.src='images/hp_event.png'"></a>
							<a href="https://secure.xserver.ne.jp/xapanel/login/xserver/mail/" target="_blank"><img src="images/web_mail.png" onmouseover="this.src='images/web_mail_b.png'" onmouseout="this.src='images/web_mail.png'"></a>
							</p>
						<h2>精米倶楽部関連メニュー</h2>
							<p align="left">
							<a href="./rice_order.php" target="_blank"><img src="images/rice_order.png" onmouseover="this.src='images/rice_order_b.png'" onmouseout="this.src='images/rice_order.png'" height="200px;" width="320px;"></a>
							</p>
					<?php } ?>
					<?php } ?>
				<? } ?>
			<? } ?>
			</div>
		</div>
		<!--/contents-->
	</div>
	<!--/container-->
</body>
</html>
