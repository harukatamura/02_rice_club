<?php
//========================================================================================
// ■クラス名
// comm
// 
// ■概要
// 標準機能クラス
// 
// ■履歴
//   2019.06 バージョン更新対応 (PHP5.4.16 → PHP7.0.33)	K.Mizutani
//========================================================================================

class comm
{
	//--------------------------------------------------------------------------------------------------
	// ■メソッド名
	// ouputlog
	// 
	// ■概要
	// ログ情報を出力
	//
	// ■引数
	// 第一引数：出力情報
	// 第二引数：ログ種類
	// 
	//--------------------------------------------------------------------------------------------------
	function ouputlog($val, $programid, $type){

		// デバッグタイプのログはテスト時のみ出力する
		if ($type == SYS_LOG_TYPE_DBUG && SYS_TYPE == 0) {return;}
		error_log(sprintf("%s", date('Y/m/d H:i:s')) . " " . $type . " " . str_pad($programid, 20, " ", STR_PAD_RIGHT) . " " . $val . "\n", 3, SYS_LOG_PATH . date('Ymd') . "_syslog.log");
		return;

	}
	//--------------------------------------------------------------------------------------------------
	// ■メソッド名
	// databaselog
	//
	// ■概要
	// ログ情報をデータベースへ格納
	//
	// ■引数
	// 第一引数：ＤＢ情報
	// 第二引数：何をしたか
	//
	//--------------------------------------------------------------------------------------------------
	function databaselog($db, $do){
		$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
		$this->ouputlog("詳細ログ", $prgid, SYS_LOG_TYPE_DBUG);
		//現在時刻
		$today = date('Y-m-d H:i:s');
		//担当者
		if(isset($_COOKIE['con_perf_staff'])){
			$staff = $_COOKIE['con_perf_staff'];
		}else{
			$staff = "";
		}
		//ログテーブルにインサート
		$query = " INSERT INTO php_log";
		$query .= " (insdt, staff, prgid, action)";
		$query .= " VALUES";
		$query .= " ('$today', '$staff', '$prgid', '$do')";
		$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (! $rs = $db->query($query)) {
			$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		return;
	}
	//--------------------------------------------------------------------------------------------------
	// ■メソッド名
	// databaselog_detail
	//
	// ■概要
	// ログ情報をデータベースへ格納
	//
	// ■引数
	// 第一引数：ＤＢ情報
	// 第二引数：何をしたか
	//
	//--------------------------------------------------------------------------------------------------
	function databaselog_detail($db, $table_name, $do, $num){
		$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
		$this->ouputlog("ログ", $prgid, SYS_LOG_TYPE_DBUG);
		//現在時刻
		$today = date('Y-m-d H:i:s');
		//担当者
		$staff = $_COOKIE['con_perf_staff'];
		//ログテーブルにインサート
		$query = " INSERT INTO php_log";
		$query .= " (insdt, staff, prgid, table_name, action, num)";
		$query .= " VALUES";
		$query .= " ('$today', '$staff', '$prgid', '$table_name', '$do', '$num')";
		$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (! $rs = $db->query($query)) {
			$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		return;
	}
	//--------------------------------------------------------------------------------------------------
	// ■メソッド名
	// getcode
	// 
	// ■概要
	// コードマスタの値を取得
	//
	// ■引数
	// 第一引数：ＤＢ情報
	// 第二引数：カテゴリ
	// 
	//--------------------------------------------------------------------------------------------------
	function getcode($db, $category){

		//実行プログラム名取得
		$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
		//コードマスタの取得
		$query = "SELECT codename FROM php_code";
		$query .= " WHERE category = " . sprintf("'%s'", $category);
		$query .= " ORDER BY idxnum ";
		$this->ouputlog("query=" . $query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//		return mysql_query($query, $db);
		return $db->query($query);
	}
	//--------------------------------------------------------------------------------------------------
	// ■メソッド名
	// getstaff
	// 
	// ■概要
	// コードマスタの値を取得
	//
	// ■引数
	// 第一引数：ＤＢ情報
	// 第二引数：担当者フラグ
	// 
	//--------------------------------------------------------------------------------------------------
	function getstaff($db, $staffflg){

		//実行プログラム名取得
		$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
		//担当者マスタの取得
		$query = "SELECT A.staff FROM php_staff A";
		$query .= " LEFT OUTER JOIN php_l_user B ON A.staff=B.staff";
		$query .= " LEFT OUTER JOIN php_staff_info C ON A.staff=C.displayname";
		//譲渡担当
		if ($staffflg == 1) {
			$query .= " WHERE A.buyflg = 1 ";
		}
		//連絡担当
		elseif ($staffflg == 2) {
			$query .= " WHERE A.cnttflg = 1 ";
		}
		//受取担当
		elseif ($staffflg == 3) {
			$query .= " WHERE A.receflg = 1 ";
		}
		//発送担当
		elseif ($staffflg == 4) {
			$query .= " WHERE A.sendflg = 1 ";
		}
		//コンシューマ受付担当
		elseif ($staffflg == 5) {
			$query .= " WHERE A.conreceflg = 1 ";
		}
		//コンシューマ担当
		elseif ($staffflg == 6) {
			$query .= " WHERE A.conflg = 1 ";
		}
		//WEB担当
		elseif ($staffflg == 7) {
			$query .= " WHERE A.webflg > 0 ";
		}
		//本部
		elseif ($staffflg == 8) {
			$query .= " WHERE A.unit = 'H' ";
		}
		//不良
		elseif ($staffflg == 9) {
			$query .= " WHERE A.failureflg = 1 ";
		}
		//不良以外
		elseif ($staffflg == 10) {
			$query .= " WHERE A.webflg > 0 ";
			$query .= " AND A.failureflg = 0 ";
		}
		//不良以外
		elseif ($staffflg == 11) {
			$query .= " WHERE A.webflg > 0 ";
			$query .= " AND A.failureflg = 0 ";
		}
		//WEB担当&譲渡担当
		elseif ($staffflg == 12) {
			$query .= " WHERE A.webflg > 0 ";
			$query .= " AND A.buyflg = 1 ";
		}
		//MSO担当&譲渡担当
		elseif ($staffflg == 13) {
			$query .= " WHERE A.webflg > 0 ";
			$query .= " AND A.buyflg = 1 ";
			$query .= " AND A.companycd = 'M' ";
		}
		//メール担当
		elseif ($staffflg == 14) {
			$query .= " WHERE A.mailflg > 0 ";
			$query .= " AND A.unit = 'H' ";
		}
		//工場
		elseif ($staffflg == 15) {
			$query .= " WHERE A.unit = 'F' ";
		}
		elseif ($staffflg == 16) {
			$query .= " WHERE (B.kintaiflg<>'0'";
			$query .= " OR A.staff LIKE '本部_')";
			$query .= " AND A.staff <> '田村test'";
		}
		//法人
		elseif ($staffflg == 17) {
			$query .= " WHERE B.crpflg > 0 ";
		}
		//本部＆補修センター
		elseif ($staffflg == 18) {
			$query .= " WHERE (A.companycd ='J' OR A.companycd='F') ";
		}
		//***************************
		//** 並び順
		//***************************
		$query .= " AND A.delflg = 0 ";
		if ($staffflg == 7 || $staffflg == 12) {
			$query .= " ORDER BY A.staff LIKE '本部%' ASC, A.staff LIKE '未選択' DESC, A.zaikoflg LIKE '0' ASC, A.companycd LIKE 'Z' DESC, C.phonetic, A.idxnum ";
		}else if($staffflg == 16 || $staffflg == 11){
			$query .= " ORDER BY A.webflg LIKE '1' DESC, A.webflg LIKE '5' ASC, A.staff LIKE '本部%' ASC, C.phonetic, A.staff";
		}else{
			$query .= " ORDER BY A.idxnum ";
		}
		$this->ouputlog("query=" . $query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//		return mysql_query($query, $db);
		return $db->query($query);
	}
	//--------------------------------------------------------------------------------------------------
	// ■メソッド名
	// getfactory
	// 
	// ■概要
	// 工場マスタの値を取得
	//
	// ■引数
	// 第一引数：ＤＢ情報
	// 第二引数：フラグ
	// 
	//--------------------------------------------------------------------------------------------------
	function getfactory($db, $factoryflg){

		//実行プログラム名取得
		$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
		//担当者マスタの取得
		$query = "SELECT factory FROM php_factory";
		$query .= " ORDER BY idxnum ";
		$this->ouputlog("query=" . $query, $prgid, SYS_LOG_TYPE_DBUG);
		return $db->query($query);
	}
	//--------------------------------------------------------------------------------------------------
	// ■メソッド名
	// getweek
	//
	// ■概要
	// システムマスタの週の値を取得
	//
	// ■引数
	// 第一引数：ＤＢ情報
	//
	//--------------------------------------------------------------------------------------------------
	function getweek($db){

		//実行プログラム名取得
		$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
		//コードマスタの取得
		$query = "SELECT week FROM php_calendar";
		$query .= " WHERE date = '".date('Y-m-d')."'";
		$this->ouputlog("query=" . $query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//		if (! $rs = mysql_query($query, $db)) {
		if (! $rs = $db->query($query)) {
//			$this->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
			$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
// ----- 2019.06 ver7.0対応
//		while ($row = @mysql_fetch_array($rs)) {
		while ($row = $rs->fetch_array()) {
			$week = $row['week'];
		}
		return $week;
	}
	//--------------------------------------------------------------------------------------------------
	// ■メソッド名
	// getcalender
	// 
	// ■概要
	// 日付・担当者検索の出力
	// $choices		1：日付を選択
	// （第二引数）		2：担当者を選択
	// 			3：日付・担当者を選択（担当者はperformanceテーブルから）
	// 			4：日付・担当者・表示区分を選択
	// 			5：日付・出荷元を選択
	// 			6：日付・担当者を選択
	// 			7：日付を１日のみで選択
	// 			8：日付・担当者を選択（担当者はstaffテーブルから）
	// 			9：日付・担当者を選択（初期表示：チーム）
	// 			10：担当者を選択（初期表示：チーム）
	// 			11：日付・担当者を選択（担当者は職員のみ）
	// 			12：日付・担当者を選択（担当者は職員のみ、初期表示：チーム）
	// 			13：日付・担当者を選択（担当者は職員のみ、初期表示：全員）
	// 			14：日付・出勤区分を選択（初期表示：全員）
	// 			15：日付・担当者・区分を選択（初期表示：チーム）
	// 			16：日付・担当者を選択（初期表示：A・Bチーム）
	// 			17：担当者を選択（初期表示：A・Bチーム）
	// 			18：日付・担当者を選択（担当者はperformanceテーブルから）
	// 			19：日付・担当者を選択（初期表示：A・Bチーム）
	// 			20：担当者を選択（初期表示：A・Bチーム）工場も表示
	// 			21：日付・担当者を選択（担当者：販売方法）
	// 			22：日付・担当者・時間を選択（担当者：販売方法）
	// 			23：担当者を選択（担当者：販売方法）
	// 			24：日付・担当者を選択（権限あり→担当者：職員、なし→担当者は自分だけ）
	// 			25：日付・担当者を選択（リーダー・）
	// 			26：担当者を選択（初期表示：その週のチーム）
	// 			27：日付・担当者を選択（初期表示：その週のチーム）
	// 			28：日付・担当者を選択（初期表示：本部→その週のチーム、経営者→自分）
	// 			29：担当者を選択（初期表示：本部→その週のチーム、経営者→自分）
	// $calender		1：入力された値をそのまま取得
	// （第三引数）		2：水曜日始まりの１週間毎で日付を取得
	// 			3：水曜日始まりの１ヶ月毎で日付を取得
	// 			4：デフォルトのみ水曜日始まりの１週間毎、入力された場合はそのまま取得
	// 			5：今週の水曜日始まりの１ヶ月毎で日付を取得
	// 			6：デフォルトのみ先々月初日始まりの今月末まで、入力された場合はそのまま取得
	// 			7：1日～月末までの１ヶ月毎で日付を取得
	// 			8：デフォルトのみ当週月曜日始まりの1ヶ月後まで、入力された場合はそのまま取得
	// 			9：デフォルト当日、１日のみ取得
	// 			10：当日からの１週間を取得
	// 			11：翌日からの１週間を取得
	// 			12：日曜日始まりの１週間毎で日付を取得
	// 			13：水曜日始まりの１週間毎で日付を取得（COKKIEを保持）
	// 			14：当日からの１週間を取得、入力値はそのまま取得
	// 			1５：前日から今日までを取得
	// 			16：今日から今日まで、入力値はそのまま取得、日毎、週毎に遷移
	// 			17：デフォルト翌週、１週間ごとに取得
	// 			18：1ヶ月前から今日までを取得、入力値はそのまま取得、週単位で取得
	// 			19：1ヶ月前から1ヶ月後までを取得、入力値はそのまま取得、週単位で取得
	// 			20：1日～月末までの１ヶ月毎で日付を取得、入力値はそのまま取得
	//--------------------------------------------------------------------------------------------------
	 function getcalender($db, $choices, $calender) { 
	 	//日付検索
	 	if($choices == 1 || $choices == 3 || $choices == 4 || $choices == 5 || $choices == 6 || $choices == 8 || $choices == 9 || $choices == 11 || $choices == 12 || $choices == 13 || $choices == 14 || $choices == 15 || $choices == 16 || $choices == 18 || $choices == 19 || $choices == 21 || $choices == 22 || $choices == 24 || $choices == 25 || $choices == 27 || $choices == 28 || $choices == 30){
			//日付の取得
			if($calender == 1 || $calender == 4){
				if(isset($_POST['search'])){
					$p_date1 = $_POST['開催日付１'];
					$p_date2 = $_POST['開催日付２'];
					if($p_date2 <= $p_date1){
						$p_date2 =date ( 'Y-m-d', strtotime(date($p_date1).'+6'.'day')); 
					}
				}else if(isset($_POST['lastweek'])){
					$p_date1 = date ( 'Y-m-d', strtotime(date($_POST['開催日付１']).'-7'.'day'));
					$p_date2 = date ( 'Y-m-d', strtotime(date($p_date1).'+6'.'day'));
				}else if(isset($_POST['nextweek'])){
					$p_date1 = date ( 'Y-m-d', strtotime(date($_POST['開催日付１']).'+7'.'day'));
					$p_date2 = date ( 'Y-m-d', strtotime(date($p_date1).'+6'.'day'));
				}else if(isset($_POST['開催日付１']) || isset($_POST['開催日付２'])){
					$p_date1 = $_POST['開催日付１'];
					$p_date2 = $_POST['開催日付２'];
					if($p_date2 <= $p_date1){
						$p_date2 =date ( 'Y-m-d', strtotime(date($p_date1).'+6'.'day')); 
					}
				}else{
					if($calender == 1){
						$p_date1 = date('Y-m-d');
						$p_date2 = date('Y-m-d', strtotime(date($p_date1).'+6'.'day')); 
					}else if($calender == 4){
						if(date('w')==3){
							$p_date1 = date('Y-m-d');
						}else{
							$p_date1 = date('Y-m-d' , strtotime("last wednesday"));
						}
						$p_date2 = date('Y-m-d', strtotime(date($p_date1).'+6'.'day')); 
					}
				}
			}else if($calender == 2 || $calender == 3 || $calender == 5 || $calender == 13 || $calender == 17){
				if(isset($_POST['search'])){
					$s_date1 = $_POST['開催日付１'];
					$s_date2 = $_POST['開催日付２'];
					if($s_date2 <= $s_date1){
						$s_date2 =date ( 'Y-m-d', strtotime(date($s_date1).'+6'.'day')); 
					}
				}else if(isset($_POST['lastmonth'])){
					$s_date1 = date ( 'Y-m-01', strtotime(date($_POST['開催日付１']).'-6'.'day'));
					$s_date2 = date ( 'Y-m-t', strtotime(date($s_date1)));
				}else if(isset($_POST['nextmonth'])){
					$s_date2 = date ( 'Y-m-t', strtotime(date($_POST['開催日付２']).'+6'.'days'));
					$s_date1 = date ( 'Y-m-01', strtotime(date($s_date2)));
				}else if(isset($_POST['開催日付１']) || isset($_POST['開催日付２'])){
					$s_date1 = $_POST['開催日付１'];
					$s_date2 = $_POST['開催日付２'];
					if($s_date2 <= $s_date1){
						$s_date2 =date ( 'Y-m-d', strtotime(date($s_date1).'+6'.'day')); 
					}
				}else if($_COOKIE['week']){
					$week = $_COOKIE['week'];
				}else if($_COOKIE['s_date1']){
					$s_date1 = $_COOKIE['s_date1'];
					$s_date2 = $_COOKIE['s_date2'];
				}else{
					if($calender == 2 || $calender == 5 || $calender == 13){
						$s_date1 = date('Y-m-d');
					}else if($calender == 3){
						$s_date1 = date("Y-m-01", time());//月初の日付
						$s_date2 = date("Y-m-t", time());//月末の日付
					}else if($calender == 17){
						$s_date1 = date('Y-m-d' , strtotime(date($p_date1).'+ 7 day')); 
					}
				}
				if(!isset($week)){
					//週の取得
					$query = "SELECT A.week";
					$query .= " FROM php_calendar A ";
					$query .= " WHERE A.date = ".date('Ymd', strtotime($s_date1));
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$week = $row['week'];
					}
				}
				//開始日・終了日
				$query = "SELECT MIN(A.date) as minDate, MAX(A.date) as maxDate";
				$query .= " FROM php_calendar A ";
				$query .= " WHERE A.week = ".$week;
				$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (! $rs = $db->query($query)) {
					$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				//データ設定
				while ($row = $rs->fetch_array()) {
					$p_date1 = $row['minDate'];
					$p_date2 = $row['maxDate'];
				}
				
				if(isset($_POST['lastweek']) || isset($_POST['nextweek'])){
					//週の取得
					$query = "SELECT A.week";
					$query .= " FROM php_calendar A ";
					$query .= " WHERE A.date = ".date('Ymd', strtotime($_POST['開催日付１']));
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$g_week = $row['week'];
					}
					if(isset($_POST['lastweek'])){
						$query = " SELECT DISTINCT(week) FROM `php_calendar`";
						$query .= " WHERE week < ".$g_week;
						$query .= " ORDER BY week DESC";
						$query .= " LIMIT 0,1";
					}else if(isset($_POST['nextweek'])){
						$query = " SELECT DISTINCT(week) FROM `php_calendar`";
						$query .= " WHERE week > ".$g_week;
						$query .= " ORDER BY week";
						$query .= " LIMIT 0,1";
					}
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$week = $row['week'];
					}
					$query = "SELECT MIN(A.date) as minDate, MAX(A.date) as maxDate";
					$query .= " FROM php_calendar A ";
					$query .= " WHERE A.week = ".$week;
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$p_date1 = $row['minDate'];
						$p_date2 = $row['maxDate'];
					}
				}
				if($calender == 3){
					$query = "SELECT A.week";
					$query .= " FROM php_calendar A ";
					$query .= " WHERE A.date = ".date('Ymd', strtotime($s_date2));
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$week2 = $row['week'];
					}
					//開始日・終了日
					$query = "SELECT MIN(A.date) as minDate, MAX(A.date) as maxDate";
					$query .= " FROM php_calendar A ";
					$query .= " WHERE A.week = ".$week2;
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					//データ設定
					while ($row = $rs->fetch_array()) {
						$p_date2 = $row['maxDate'];
					}
				}
				if($calender == 5){
					if(isset($_POST['nextweek']) || isset($_POST['lastweek'])){
						$p_date2 = date ('Y-m-d', strtotime(date($p_date1).'+27'.'days'));
					}else{
						$p_date2 = date ('Y-m-t', strtotime(date($p_date1).'+6'.'days'));
					}
				}
				//クッキーに保存
				if($calender == 13){
/*					setcookie ('week', '', time()-3600);
					setcookie ('week', $week);
					setcookie ('s_date1', '', time()-3600);
					setcookie ('s_date1', $s_date1);
					setcookie ('s_date2', '', time()-3600);
					setcookie ('s_date2', $s_date2);
*/				}
			}else if($calender == 6){
				if(isset($_POST['search'])){
					$p_date1 = $_POST['開催日付１'];
					$p_date2 = $_POST['開催日付２'];
					if($p_date2 <= $p_date1){
					$p_date2 = date('Y-m-t' , strtotime(date($p_date2).'+ 2 month')); 
					}
				}else if(isset($_POST['lastmonth'])){
					$p_date1 = date ( 'Y-m-01', strtotime(date($_POST['開催日付１']).'- 1 month'));
					$p_date2 = date('Y-m-t' , strtotime(date($p_date1).'+ 2 month')); 
				}else if(isset($_POST['nextmonth'])){
					$p_date2 = date ( 'Y-m-t', strtotime(date($_POST['開催日付２']).'+ 1 month'));
					$p_date1 = date('Y-m-01' , strtotime(date($p_date2).'- 2 month')); 
				}else{
					$p_date2 = date('Y-m-t'); 
					$p_date1 = date('Y-m-01' , strtotime(date($p_date2).'- 2 month')); 
				}
			}else if($calender == 7){
				if(isset($_POST['search'])){
					$p_date1 = date ( 'Y-m-01', strtotime(date($_POST['開催日付１'])));
					$p_date2 = date('Y-m-t' , strtotime(date($p_date1))); 
				}else if(isset($_POST['lastmonth'])){
					$p_date1 = date ( 'Y-m-01', strtotime(date($_POST['開催日付１']).'- 1 month'));
					$p_date2 = date('Y-m-t' , strtotime(date($p_date1))); 
				}else if(isset($_POST['nextmonth'])){
					$p_date1 = date ( 'Y-m-01', strtotime(date($_POST['開催日付１']).'+ 1 month'));
					$p_date2 = date('Y-m-t' , strtotime(date($p_date1))); 
				}else{
					$p_date1 = date("Y-m-01", time());//月初の日付
					$p_date2 = date("Y-m-t", time());//月末の日付
				}
			}else if($calender == 8){
				if(isset($_POST['search'])){
					$p_date1 = $_POST['開催日付１'];
					$p_date2 = $_POST['開催日付２'];
					if($p_date2 <= $p_date1){
						$p_date2 = date('Y-m-d' , strtotime(date($p_date1).'+ 1 month - 1 day')); 
					}
				}else if(isset($_POST['lastmonth'])){
					$p_date1 = date ( 'Y-m-d', strtotime(date($_POST['開催日付１']).'- 1 month'));
					$p_date2 = date('Y-m-d' , strtotime(date($p_date1).'+ 1 month - 1 day')); 
				}else if(isset($_POST['nextmonth'])){
					$p_date1 = date ( 'Y-m-d', strtotime(date($_POST['開催日付１']).'+ 1 month'));
					$p_date2 = date('Y-m-d' , strtotime(date($p_date1).'+ 1 month - 1 day')); 
				}else{
					if(date('w') == '1'){
						$p_date1 = date('Y-m-d');
					}else{
						$p_date1 = date('Y-m-d' , strtotime("last monday"));
					}
					$p_date2 = date('Y-m-d' , strtotime(date($p_date1).'+ 1 month - 1 day')); 
				}
			}else if($calender == 9){
				if(isset($_POST['search'])){
					$p_date1 = $_POST['開催日付１'];
				}else if(isset($_POST['lastweek'])){
					$p_date1 = date ( 'Y-m-d', strtotime(date($_POST['開催日付１']).'- 1 day'));
				}else if(isset($_POST['nextweek'])){
					$p_date1 = date ( 'Y-m-d', strtotime(date($_POST['開催日付１']).'+ 1 day'));
				}else{
					$p_date1 = date('Y-m-d');
				}
			}else if($calender == 10 || $calender == 11 || $calender == 14){
				if(isset($_POST['search'])){
					$p_date1 = $_POST['開催日付１'];
					$p_date2 = $_POST['開催日付２'];
					if($p_date2 < $p_date1){
						$p_date2 = date('Y-m-d' , strtotime(date($p_date1).'+ 6 day')); 
					}
				}else if(isset($_POST['lastweek'])){
					$p_date1 = date ( 'Y-m-d', strtotime(date($_POST['開催日付１']).'- 1 week'));
					$p_date2 = date('Y-m-d' , strtotime(date($p_date1).'+ 6 day'));
				}else if(isset($_POST['nextweek'])){
					$p_date1 = date ( 'Y-m-d', strtotime(date($_POST['開催日付１']).'+ 1 week'));
					$p_date2 = date('Y-m-d' , strtotime(date($p_date1).'+ 6 day'));
				}else{
					if($calender == 10){
						$p_date1 = date('Y-m-d');
						$p_date2 = date('Y-m-d' , strtotime(date($p_date1).'+ 6 day')); 
					}else if($calender == 11){
						$p_date1 = date('Y-m-d' , strtotime(date('Ymd').'+ 1 day')); 
						$p_date2 = date('Y-m-d' , strtotime(date($p_date1).'+ 6 day')); 
					}else if($calender == 11){
						$p_date1 = date('Y-m-d' , strtotime(date('Ymd').'+ 1 day')); 
						$p_date2 = date('Y-m-d' , strtotime(date($p_date1).'+ 6 day')); 
					}
				}
			}else if($calender == 12){
				if($_COOKIE['week']){
					$week = $_COOKIE['week'];
				}else if(isset($_POST['lastweek']) || isset($_POST['nextweek'])){
					//週の取得
					$query = "SELECT A.s_week";
					$query .= " FROM php_calendar A ";
					$query .= " WHERE A.date = ".date('Ymd', strtotime($_POST['開催日付１']));
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$g_week = $row['s_week'];
					}
					if(isset($_POST['lastweek'])){
						$query = " SELECT DISTINCT(s_week) FROM `php_calendar`";
						$query .= " WHERE s_week < ".$g_week;
						$query .= " ORDER BY s_week DESC";
						$query .= " LIMIT 0,1";
					}else if(isset($_POST['nextweek'])){
						$query = " SELECT DISTINCT(s_week) FROM `php_calendar`";
						$query .= " WHERE s_week > ".$g_week;
						$query .= " ORDER BY s_week";
						$query .= " LIMIT 0,1";
					}
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$week = $row['s_week'];
					}
				}else{
					if(isset($_POST['search'])){
						$s_date1 = $_POST['開催日付１'];
					}else if(isset($_POST['開催日付１']) || isset($_POST['開催日付２'])){
						$s_date1 = $_POST['開催日付１'];
					}else if($_COOKIE['s_date1']){
						$s_date1 = $_COOKIE['s_date1'];
					}else{
						$s_date1 = date('Y-m-d');
					}
					//週の取得
					$query = "SELECT A.s_week";
					$query .= " FROM php_calendar A ";
					$query .= " WHERE A.date = ".date('Ymd', strtotime($s_date1));
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$week = $row['s_week'];
					}
				}
				$query = "SELECT MIN(A.date) as minDate, MAX(A.date) as maxDate";
				$query .= " FROM php_calendar A ";
				$query .= " WHERE A.s_week = ".$week;
				$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (! $rs = $db->query($query)) {
					$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				while ($row = $rs->fetch_array()) {
					$p_date1 = $row['minDate'];
					$p_date2 = $row['maxDate'];
				}
			}else if($calender == 15){
				if(isset($_POST['search'])){
					$p_date1 = $_POST['開催日付１'];
					$p_date2 = $_POST['開催日付２'];
					if($p_date2 < $p_date1){
						$p_date2 = date('Y-m-d' , strtotime(date($p_date1).'+ 1 day')); 
					}
				}else if(isset($_POST['lastweek'])){
					$p_date1 = date ( 'Y-m-d', strtotime(date($_POST['開催日付１']).'- 1 week'));
					$p_date2 = date('Y-m-d' , strtotime(date($p_date1).'+ 6 day'));
				}else if(isset($_POST['nextweek'])){
					$p_date1 = date ( 'Y-m-d', strtotime(date($_POST['開催日付１']).'+ 1 week'));
					$p_date2 = date('Y-m-d' , strtotime(date($p_date1).'+ 6 day'));
				}else{
					$p_date1 = date('Y-m-d' , strtotime(date($p_date1).'- 1 day')); 
					$p_date2 = date('Y-m-d');
				}
			}else if($calender == 16){
				if(isset($_POST['search'])){
					$p_date1 = $_POST['開催日付１'];
					$p_date2 = $_POST['開催日付２'];
					if($p_date2 < $p_date1){
						$p_date2 = $p_date1;
					}
				}else if(isset($_POST['lastweek'])){
					$p_date1 = date ( 'Y-m-d', strtotime(date($_POST['開催日付１']).'- 1 day'));
					$p_date2 = date ( 'Y-m-d', strtotime(date($_POST['開催日付２']).'- 1 day'));
				}else if(isset($_POST['nextweek'])){
					$p_date1 = date ( 'Y-m-d', strtotime(date($_POST['開催日付１']).'+ 1 day'));
					$p_date2 = date ( 'Y-m-d', strtotime(date($_POST['開催日付２']).'+ 1 day'));
				}else if(isset($_POST['lastmonth'])){
					$p_date1 = date ( 'Y-m-d', strtotime(date($_POST['開催日付１']).'- 1 week'));
					$p_date2 = date ( 'Y-m-d', strtotime(date($_POST['開催日付２']).'- 1 week'));
				}else if(isset($_POST['nextmonth'])){
					$p_date1 = date ( 'Y-m-d', strtotime(date($_POST['開催日付１']).'+ 1 week'));
					$p_date2 = date ( 'Y-m-d', strtotime(date($_POST['開催日付２']).'+ 1 week'));
				}else{
					$p_date1 = date('Y-m-d'); 
					$p_date2 = date('Y-m-d');
				}
			}else if($calender == 18 || $calender == 19){
				if(isset($_POST['search'])){
					$query = "SELECT MIN(A.date) as minDate, MAX(A.date) as maxDate";
					$query .= " FROM php_calendar A ";
					$query .= " WHERE A.week = (";
					$query .= " SELECT week ";
					$query .= " FROM php_calendar  ";
					$query .= " WHERE date = '".date ( 'Y-m-d', strtotime($_POST['開催日付１']))."'";
					$query .= " )";
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$p_date1 = $row['minDate'];
					}
					$query = "SELECT MIN(A.date) as minDate, MAX(A.date) as maxDate";
					$query .= " FROM php_calendar A ";
					$query .= " WHERE A.week = (";
					$query .= " SELECT week ";
					$query .= " FROM php_calendar  ";
					$query .= " WHERE date = '".date ( 'Y-m-d', strtotime($_POST['開催日付２']))."'";
					$query .= " )";
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$p_date2 = $row['maxDate'];
					}
					if($p_date2 < $p_date1){
						$query = "SELECT MIN(A.date) as minDate, MAX(A.date) as maxDate";
						$query .= " FROM php_calendar A ";
						$query .= " WHERE A.week = (";
						$query .= " SELECT week ";
						$query .= " FROM php_calendar  ";
						$query .= " WHERE date = '".date ( 'Y-m-d', strtotime($_POST['開催日付１']))."'";
						$query .= " )";
						$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
						if (! $rs = $db->query($query)) {
							$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						while ($row = $rs->fetch_array()) {
							$p_date2 = $row['maxDate'];
						}
					}
				}else if(isset($_POST['lastweek'])){
					$p_date1 = date ( 'Y-m-d', strtotime(date($_POST['開催日付１']).'- 1 week'));
					$p_date2 = date ( 'Y-m-d', strtotime(date($_POST['開催日付２']).'- 1 week'));
				}else if(isset($_POST['nextweek'])){
					$p_date1 = date ( 'Y-m-d', strtotime(date($_POST['開催日付１']).'+ 1 week'));
					$p_date2 = date ( 'Y-m-d', strtotime(date($_POST['開催日付２']).'+ 1 week'));
				}else if(isset($_POST['lastmonth'])){
					$query = "SELECT MIN(A.date) as minDate, MAX(A.date) as maxDate";
					$query .= " FROM php_calendar A ";
					$query .= " WHERE A.week = (";
					$query .= " SELECT week ";
					$query .= " FROM php_calendar  ";
					$query .= " WHERE date = '".date ( 'Y-m-d', strtotime(date($_POST['開催日付１']).'- 28 days'))."'";
					$query .= " )";
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$p_date1 = $row['minDate'];
					}
					$query = "SELECT MIN(A.date) as minDate, MAX(A.date) as maxDate";
					$query .= " FROM php_calendar A ";
					$query .= " WHERE A.week = (";
					$query .= " SELECT week ";
					$query .= " FROM php_calendar  ";
					$query .= " WHERE date = '".date ( 'Y-m-d', strtotime(date($_POST['開催日付２']).'- 28 days'))."'";
					$query .= " )";
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$p_date2 = $row['minDate'];
					}
				}else if(isset($_POST['nextmonth'])){
					$query = "SELECT MIN(A.date) as minDate, MAX(A.date) as maxDate";
					$query .= " FROM php_calendar A ";
					$query .= " WHERE A.week = (";
					$query .= " SELECT week ";
					$query .= " FROM php_calendar  ";
					$query .= " WHERE date = '".date ( 'Y-m-d', strtotime(date($_POST['開催日付１']).'+ 28 days'))."'";
					$query .= " )";
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$p_date1 = $row['minDate'];
					}
					$query = "SELECT MIN(A.date) as minDate, MAX(A.date) as maxDate";
					$query .= " FROM php_calendar A ";
					$query .= " WHERE A.week = (";
					$query .= " SELECT week ";
					$query .= " FROM php_calendar  ";
					$query .= " WHERE date = '".date ( 'Y-m-d', strtotime(date($_POST['開催日付２']).'+ 28 days'))."'";
					$query .= " )";
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$p_date2 = $row['minDate'];
					}
				}else{
					$query = "SELECT MIN(A.date) as minDate, MAX(A.date) as maxDate";
					$query .= " FROM php_calendar A ";
					$query .= " WHERE A.week = (";
					$query .= " SELECT week ";
					$query .= " FROM php_calendar  ";
					$query .= " WHERE date = '".date ( 'Y-m-d', strtotime(date('Y-m-d').'- 28 day'))."'";
					$query .= " )";
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$p_date1 = $row['minDate'];
					}
					if($calender == 18){
						$query = "SELECT MIN(A.date) as minDate, MAX(A.date) as maxDate";
						$query .= " FROM php_calendar A ";
						$query .= " WHERE A.week = (";
						$query .= " SELECT week ";
						$query .= " FROM php_calendar  ";
						$query .= " WHERE date = '".date('Y-m-d')."'";
						$query .= " )";
						$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
						if (! $rs = $db->query($query)) {
							$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						while ($row = $rs->fetch_array()) {
							$p_date2 = $row['maxDate'];
						}
					}else if($calender == 19){
						$query = "SELECT MIN(A.date) as minDate, MAX(A.date) as maxDate";
						$query .= " FROM php_calendar A ";
						$query .= " WHERE A.week = (";
						$query .= " SELECT week ";
						$query .= " FROM php_calendar  ";
						$query .= " WHERE date = '".date ( 'Y-m-d', strtotime(date('Y-m-d').'+ 28 day'))."'";
						$query .= " )";
						$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
						if (! $rs = $db->query($query)) {
							$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						while ($row = $rs->fetch_array()) {
							$p_date2 = $row['maxDate'];
						}
					}
				}
			}else if($calender == 20){
				if(isset($_POST['search'])){
					$p_date1 = date ( 'Y-m-d', strtotime(date($_POST['開催日付１'])));
					$p_date2 = date('Y-m-d' , strtotime(date($_POST['開催日付２'])));
				}else if(isset($_POST['lastmonth'])){
					$p_date1 = date ( 'Y-m-01', strtotime(date($_POST['開催日付１']).'- 1 month'));
					$p_date2 = date('Y-m-t' , strtotime(date($p_date1))); 
				}else if(isset($_POST['nextmonth'])){
					$p_date1 = date ( 'Y-m-01', strtotime(date($_POST['開催日付１']).'+ 1 month'));
					$p_date2 = date('Y-m-t' , strtotime(date($p_date1))); 
				}else{
					$p_date1 = date("Y-m-01", time());//月初の日付
					$p_date2 = date("Y-m-t", time());//月末の日付
				}
			}
		//日付１日のみの検索
		}if($choices == 7){
			if(isset($_POST['search'])){
				$p_date1 = $_POST['開催日付１'];
			}else if(isset($_POST['lastweek'])){
				$p_date1 = date ( 'Y-m-d', strtotime(date($_POST['開催日付１']).'- 1 day'));
			}else if(isset($_POST['nextweek'])){
				$p_date1 = date ( 'Y-m-d', strtotime(date($_POST['開催日付１']).'+ 1 day'));
			}else{
				$p_date1 = date('Y-m-d');
			}
	 	//担当者検索
		}if($choices == 2 || $choices == 3 || $choices == 4 || $choices == 6 || $choices == 8 || $choices == 9 || $choices == 10 || $choices == 11 || $choices == 12 || $choices == 13 || $choices == 15 || $choices == 16 || $choices == 17 || $choices == 18 || $choices == 19 || $choices == 20 || $choices == 21 || $choices == 22 || $choices == 23 || $choices == 24 || $choices == 25 || $choices == 26 || $choices == 27 || $choices == 28 || $choices == 29 || $choices == 30){
			//担当者
			$p_staff = "";
			if (isset($_POST['登録担当者'])) {
				$p_staff = $_POST['登録担当者'];
			}else {
				if($choices == 6){
					$p_staff = "";
				}else if($choices == 9 || $choices == 10 || $choices == 12 || $choices == 16 || $choices == 17 || $choices == 18 || $choices == 19 || $choices == 20){
					$p_staff = $_COOKIE['con_perf_staff'];
					$query = "SELECT team";
					$query .= " FROM php_staff A ";
					$query .= " WHERE A.staff='".$p_staff."'";
					$query .= " AND A.delflg=0 ";
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						if($choices <> 16 && $choices <> 17 && $choices <> 18 && $choices <> 19 && $choices <> 20){
							if($row['team'] <> ""){
								$p_staff = "本部".substr($row['team'],0,1);
							}
						}else{
							if($row['team'] == "Aチーム" || $row['team'] == "Bチーム" || $row['team'] == "Cチーム"){
								$p_staff = "本部A";
							}else if($row['team'] == "Dチーム" || $row['team'] == "Eチーム" || $row['team'] == "Fチーム"){
								$p_staff = "本部B";
							}
						}
					}
				}else if($choices == 26 || $choices == 27 || $choices == 15 || $choices == 28 || $choices == 29 || $choices == 30){
					if($choices == 26){
						$query = "SELECT MAX(A.date) as maxdate, MIN(A.date) as mindate";
						$query .= " FROM php_calendar A ";
						$query .= " WHERE week=";
						$query .= " (SELECT A.week";
						$query .= " FROM php_calendar A ";
						$query .= " WHERE date='".date('Y-m-d')."')";
						$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs = $db->query($query))) {
							$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						while ($row = $rs->fetch_array()) {
							$p_date1 = $row['mindate'];
							$p_date2 = $row['maxdate'];
						}
					}
					$p_staff = $_COOKIE['con_perf_staff'];
					//会場の参加予定がある場合はチームを初期表示
					$query = "SELECT B.staff";
					$query .= " FROM php_event_staff A ";
					$query .= " LEFT OUTER JOIN php_performance B ON A.venueid=CONCAT(DATE_FORMAT(B.buydt , '%Y%m%d' ), LPAD(B.lane,2,'0'), '-', B.branch) ";
					$query .= " WHERE A.staff='".$p_staff."'";
					$query .= " AND B.week =";
					$query .= " (SELECT week";
					$query .= " FROM php_calendar  ";
					$query .= " WHERE date='".date('Y-m-d')."')";
					$query .= " ORDER BY ";
					$query .= "  B.buydt LIKE '".date('Y-m-d')."' ASC";
					$query .= " , B.staff LIKE '".$p_staff."' ASC";
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($query))) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$p_staff = $row['staff'];
					}
					//担当者リスト
					$query = "SELECT distinct A.staff";
					$query .= " FROM php_performance A ";
					$query .= " WHERE A.week =";
					$query .= " (SELECT week";
					$query .= " FROM php_calendar  ";
					$query .= " WHERE date=".sprintf("'%s'", $p_date1).")";
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$g_flg = 0;
					while ($row = $rs->fetch_array()) {
						if($p_staff == $row['staff']){
							$g_flg = 1;
						}
					}
					if(($choices == 28 || $choices == 29 || $choices == 30) && $_COOKIE['con_perf_mana'] == 2){
						$p_staff = $_COOKIE['con_perf_staff'];
						$g_flg = 1;
					}
					if($g_flg == 0){
						$p_staff = $_COOKIE['con_perf_staff'];
					}
				}else if($choices == 19){
					$p_staff = $_COOKIE['con_perf_staff'];
					$query = "SELECT team";
					$query .= " FROM php_staff A ";
					$query .= " WHERE A.staff='".$p_staff."'";
					$query .= " AND A.delflg=0 ";
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						if($choices <> 16 && $choices <> 17 && $choices <> 18 && $choices <> 19 && $choices <> 20){
							if($row['team'] <> ""){
								$p_staff = "本部".substr($row['team'],0,1);
							}
						}else{
							if($row['team'] == "Aチーム" || $row['team'] == "Bチーム" || $row['team'] == "Cチーム"){
								$p_staff = "本部A";
							}else if($row['team'] == "Dチーム" || $row['team'] == "Eチーム" || $row['team'] == "Fチーム"){
								$p_staff = "本部B";
							}
						}
					}
				}else if($choices == 13 || $choices == 22 || $choices == 25){
					$p_staff = "";
				}else if($choices == 21){
					$p_staff = "NS";
				}else{
					//担当者(COOKIEを利用)
					$p_staff = $_COOKIE['con_perf_staff'];
				}
			}
			//権限
			$p_Auth = $_COOKIE['con_perf_Auth'];
			//会社
			$p_compcd = $_COOKIE['con_perf_compcd'];

			if($choices == 2 || $choices == 4 || $choices == 6 || $choices == 10 || $choices == 17 || $choices == 20 || $choices == 26 || $choices == 29){
				//販売担当者一覧の取得
				$this->ouputlog("連絡担当者一覧の取得", $prgid, SYS_LOG_TYPE_INFO);
				if (!$rs = $this->getstaff($db, 7)) {
					$this->ouputlog("データ取得エラー", $prgid, SYS_LOG_TYPE_DBUG);
				}
				while ($row = $rs->fetch_array()) {
					$staff_list[] = $row;
					$this->ouputlog("担当者=". $row[0], $prgid, SYS_LOG_TYPE_DBUG);
				}
				if($choices == 20){
					$query = "SELECT A.factory";
					$query .= " FROM php_factory A ";
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$staff_list[] = $row;
						$this->ouputlog("担当者=". $row[0], $prgid, SYS_LOG_TYPE_DBUG);
					}
				}
			}else if($choices == 3 || $choices == 9 || $choices == 15 || $choices == 16 || $choices == 18 || $choices == 27 || $choices == 28 || $choices == 30){
				//担当者リスト
				$query = "SELECT distinct A.staff";
				$query .= " FROM php_performance A ";
				$query .= " WHERE A.week = ";
				$query .= " (SELECT week";
				$query .= " FROM php_calendar  ";
				$query .= " WHERE date=".sprintf("'%s'", $p_date1).")";
				$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (! $rs = $db->query($query)) {
					$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				while ($row = $rs->fetch_array()) {
					$staff_list[] = $row;
					$this->ouputlog("担当者=". $row[0], $prgid, SYS_LOG_TYPE_DBUG);
				}
				if($staff_list != ""){
					array_unshift($staff_list, "");
				}
				$g_manager = $_COOKIE['con_perf_mana'];
				if($g_manager == 5){
					$staff_list = [];
					$staff_list[][] = "未選択";
					$staff_list[][] = "木村";
				}else if($g_manager == 6){
					$staff_list = [];
					$staff_list[][] = $_COOKIE['con_perf_staff'];
				}
				if($choices == 30){
					$this->ouputlog("担当者一覧の取得", $prgid, SYS_LOG_TYPE_INFO);
					if (!$rs = $this->getstaff($db, 16)) {
						$this->ouputlog("データ取得エラー", $prgid, SYS_LOG_TYPE_DBUG);
					}
					$staff_list2[] = "";
					while ($row = $rs->fetch_array()) {
						$staff_list2[] = $row;
						$this->ouputlog("担当者=". $row[0], $prgid, SYS_LOG_TYPE_DBUG);
					}
					$staff_list = array_merge($staff_list, $staff_list2);
				}
			}else if($choices == 8){
				//担当者リスト
				$query = "SELECT distinct A.staff";
				$query .= " FROM php_staff A ";
				$query .= " WHERE A.unit = 'H'";
				$query .= " AND A.delflg=0 ";
				$this->ouputlog("担当者データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (! $rs = $db->query($query)) {
					$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				while ($row = $rs->fetch_array()) {
					$staff_list[] = $row;
					$this->ouputlog("担当者=". $row[0], $prgid, SYS_LOG_TYPE_DBUG);
				}
			} else if($choices == 11) {
				//担当者リスト
				$this->ouputlog("担当者一覧の取得", $prgid, SYS_LOG_TYPE_INFO);
				if (!$rs = $this->getstaff($db, 8)) {
					$this->ouputlog("データ取得エラー", $prgid, SYS_LOG_TYPE_DBUG);
				}
				$staff_list[] = "";
				while ($row = $rs->fetch_array()) {
					$staff_list[] = $row;
					$this->ouputlog("担当者=". $row[0], $prgid, SYS_LOG_TYPE_DBUG);
				}
			}else if($choices == 12 || $choices == 13 || $choices == 19 || $choices == 25){
				//担当者リスト
				$this->ouputlog("担当者一覧の取得", $prgid, SYS_LOG_TYPE_INFO);
				if (!$rs = $this->getstaff($db, 16)) {
					$this->ouputlog("データ取得エラー", $prgid, SYS_LOG_TYPE_DBUG);
				}
				$staff_list[] = "";
				while ($row = $rs->fetch_array()) {
					$staff_list[] = $row;
					$this->ouputlog("担当者=". $row[0], $prgid, SYS_LOG_TYPE_DBUG);
				}
			}else if($choices == 21){
				$query = "SELECT A.staff";
				$query .= " FROM php_headoffice_list A ";
				$query .= " WHERE A.delflg=0 ";
				$query .= " GROUP BY A.staff ";
				$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (! $rs = $db->query($query)) {
					$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				$staff_list[] = "";
				while ($row = $rs->fetch_array()) {
					$staff_list[] = $row;
				}
			}else if($choices == 22 || $choices == 23){
				$query = "SELECT A.sales_name";
				$query .= " FROM php_headoffice_list A ";
				$query .= " WHERE A.delflg=0 ";
				$query .= " AND A.aggregation_flg=1 ";
				$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (! $rs = $db->query($query)) {
					$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				$staff_list[] = "";
				while ($row = $rs->fetch_array()) {
					$staff_list[] = $row;
				}
			}else if($choices == 24){
				if($_COOKIE['con_perf_tm'] == 1){
					$query = "SELECT DISTINCT(A.staff) as staff";
					$query .= " FROM php_performance A ";
					$query .= " WHERE A.buydt BETWEEN CAST(" . sprintf("'%s'", $p_date1) . " AS DATE)";
					$query .= " AND CAST(" . sprintf("'%s'", $p_date2) . " AS DATE)";
					$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$staff_list[] = "";
					while ($row = $rs->fetch_array()) {
						$staff_list[] = $row;
					}
				}else{
					$staff_list[0] = "";
					$staff_list[0][] = $_COOKIE['con_perf_staff'];
				}
			}
		}if($choices == 4){
			//表示区分
			$p_kbn = "";
			if (isset($_POST['表示区分'])) {
				$p_kbn = $_POST['表示区分'];
			}
			else {
				$p_kbn = 1;
			}
		
		} if($choices == 5){
			//出荷元
			$factory = "";
			if($_COOKIE['con_perf_compcd'] == "R"){
				$factory = "RNG";
			}else if($_COOKIE['con_perf_compcd'] == "Y"){
				$factory = "YKO";
			}else if($_COOKIE['con_perf_compcd'] == "H"){
				$factory = "補修センター";
			}else if($_COOKIE['con_perf_compcd'] == "U"){
				$factory = "UPW";
			}else if(isset($_POST['出荷元'])) {
				$factory = $_POST['出荷元'];
			}else if($_COOKIE['con_perf_repair'] == "1"){
				$factory = "再生";
			}else{
				$factory = "本部";
			}
		} if($choices == 14){
			//出勤区分
			$p_kbn = "";
			if (isset($_POST['出勤区分'])) {
				$p_kbn = $_POST['出勤区分'];
			}
		}if($choices == 15){
			if (isset($_POST['区分'])) {
				$p_kbn = $_POST['区分'];
			}
		}if($choices == 22){
			if (isset($_POST['開始時刻１'])) {
				$p_time1 = $_POST['開始時刻１'];
			}
			if (isset($_POST['開始時刻２'])) {
				$p_time2 = $_POST['開始時刻２'];
			}
		}if($choices == 25){
			$p_team = "";
			if (isset($_POST['チーム'])) {
				$p_team = $_POST['チーム'];
			}
			$query = "SELECT team, leader FROM php_team";
			$query .= " WHERE delflg=0";
			$query .= " AND unit='事務所'";
			$query .= " AND leader='".$_COOKIE['con_perf_staff']."'";
			$this->ouputlog("開始日付設定データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
			$this->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (! $rs = $db->query($query)) {
				$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
			$team_list[] = "";
			while ($row = $rs->fetch_array()) {
				$l_flg[$row['leader']] = 1;
				$team_list[] = $row;
			}
		} ?>
		<form name="date" method = "post" action="<?php $action_url ?>" >
			<table class="tbd" align="center" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
				<?php if($choices == 1 || $choices == 3 || $choices == 4 || $choices == 5 || $choices == 6 || $choices == 8 || $choices == 9 || $choices == 11 || $choices == 12 || $choices == 13 || $choices == 14 || $choices == 15 || $choices == 16 || $choices == 18 || $choices == 19 || $choices == 21 || $choices == 22 || $choices == 24 || $choices == 25 || $choices == 26 || $choices == 27 || $choices == 28 || $choices == 29 || $choices == 30){ ?>
					<?php if($calender <> 9){ ?>
						<tr>
							<th class="tbd_th"><strong>開催日付</strong></th>
							<td class="tbd_req"><img src="./images/hisu.gif" alt="必須"></td>
							<td class="tbd_td">
								<small>枠の中に日付<? if($choices == 22){echo "と時間";} ?>を入力してください。</small><br>
								<input type="date" name="開催日付１" size="10" maxlength="16" value="<?php echo $p_date1 ?>">～
								<input type="date" name="開催日付２" size="10" maxlength="16" value="<?php echo $p_date2 ?>"><br>
								<small>　記入例）2016/11/21</small><br>
							</td>
						</tr>
					<?php } ?>
				<?php }if($choices == 7 || $calender == 9){ ?>
					<tr>
						<th class="tbd_th"><strong>開催日付</strong></th>
						<td class="tbd_req"><img src="./images/hisu.gif" alt="必須"></td>
						<td class="tbd_td">
							<small>枠の中に日付を入力してください。</small><br>
							<input type="date" name="開催日付１" size="10" maxlength="16" value="<?php echo $p_date1 ?>">
							<?php if($choices == 22){ ?>
								<select name="開始時刻１">
									<option value="" <? if($p_time1==""){ ?>selected="selected"<? } ?>>終日</option>
									<option value="AM" <? if($p_time1=="AM"){ ?>selected="selected"<? } ?>>AM</option>
									<option value="PM" <? if($p_time1=="PM"){ ?>selected="selected"<? } ?>>PM</option>
								</select>
							<? } ?>
							<small>　記入例）2016/11/21</small><br>
							<?php if($choices == 22){ ?>
								<small>AM：～12:00、PM：12:00～</small>
							<? } ?>
						</td>
					</tr>
				<?php }if($choices == 2 || $choices == 3 || $choices == 4 || $choices == 6 || $choices == 8 || $choices == 9 || $choices == 10 || $choices == 11 || $choices == 12 || $choices == 13 || $choices == 15 || $choices == 16 || $choices == 17 || $choices == 18 || $choices == 19 || $choices == 20 || $choices == 21 || $choices == 22 || $choices == 23 || $choices == 24 || $choices == 25 || $choices == 26 || $choices == 27 || $choices == 28 || $choices == 29 || $choices == 30){ ?>
					<tr>
						<th class="tbd_th"><strong>担当者</strong></th>
						<td class="tbd_req"><img src="./images/hisu.gif" alt="必須"></td>
						<td class="tbd_td">
							<select name="登録担当者">
								<?php
									foreach($staff_list as $row) {
										if ($p_staff == $row[0]) { ?>
											<option value="<? echo $row[0] ?>" selected="selected" ><? echo $row[0] ?></option>
										<? }else if("未選択" == $row[0]) { ?>
											<option value=""><? echo $row[0] ?></option>
										<? }else{ ?>
											<option value="<? echo $row[0] ?>"><? echo $row[0]?></option>
										<? }
									}
								?>
							</select>
						</td>
					</tr>
				<?php }if($choices == 4){ ?>
					<tr>
						<th class="tbd_th"><strong>表示区分</strong></th>
						<td class="tbd_req"><img src="./images/hisu.gif" alt="必須"></td>
						<td class="tbd_td">
							<small>在庫を表示する単位を選択してください。</small><br>
							<input type="radio" name="表示区分" value="1" <?php if ($p_kbn == "1") {echo "checked";} ?>> カテゴリ単位	
							<input type="radio" name="表示区分" value="2" <?php if ($p_kbn == "2") {echo "checked";} ?>> 詳細単位
						</td>
					</tr>
				<?php }if($choices == 5){
					if($_COOKIE['con_perf_compcd'] <> "J"){
						$arr_factory = array($factory);
					}else{
						$arr_factory = array("未選択","RNG", "YKO", "本部", "補修センター", "UPW", "再生");
					} ?>
					<tr>
						<th class="tbd_th"><strong>出荷元</strong></th>
						<td class="tbd_req"><img src="./images/hisu.gif" alt="必須"></td>
						<td class="tbd_td">
							<select name="出荷元">
								<?php foreach($arr_factory as $key=>$val) {
									if($val == $factory){ ?>
										<option value="<? echo $val ?>" selected="selected"><? echo $val ?></option>
									<? }else{ ?>
										<option value="<? echo $val ?>"><? echo $val ?></option>
									<? }
								} ?>
							</select>
						</td>
					</tr>
				<?php }if($choices == 14){
					$arr_kbn = array("", "事務所", "自宅勤務", "会場"); ?>
					<tr>
						<th class="tbd_th"><strong>出勤区分</strong></th>
						<td class="tbd_req"><img src="./images/hisu.gif" alt="必須"></td>
						<td class="tbd_td">
							<select name="出勤区分">
								<?php foreach($arr_kbn as $key=>$val) {
									if($val == $p_kbn){ ?>
										<option value="<? echo $val ?>" selected="selected"><? echo $val ?></option>
									<? }else{ ?>
										<option value="<? echo $val ?>"><? echo $val ?></option>
									<? }
								} ?>
							</select>
						</td>
					</tr>
				<?php }if($choices == 15){
					$arr_kbn = array("", "伝票未発行", "伝票発行済", "未発送", "発送済"); ?>
					<tr>
						<th class="tbd_th"><strong>区分</strong></th>
						<td class="tbd_req"><img src="./images/hisu.gif" alt="必須"></td>
						<td class="tbd_td">
							<select name="区分">
								<?php foreach($arr_kbn as $key=>$val) {
									if($val == $p_kbn){ ?>
										<option value="<? echo $val ?>" selected="selected"><? echo $val ?></option>
									<? }else{ ?>
										<option value="<? echo $val ?>"><? echo $val ?></option>
									<? }
								} ?>
							</select>
						</td>
					</tr>
				<?php }if($choices == 25 && $l_flg[$_COOKIE['con_perf_staff']] > 0){ ?>
					<tr>
						<th class="tbd_th"><strong>チーム</strong></th>
						<td class="tbd_req"></td>
						<td class="tbd_td">
							<select name="チーム">
								<?php
									foreach($team_list as $row) {
										if ($p_team == $row[0]) { ?>
											<option value="<? echo $row[0] ?>" selected="selected" ><? echo $row[0] ?></option>
										<? }else{ ?>
											<option value="<? echo $row[0] ?>"><? echo $row[0]?></option>
										<? }
									}
								?>
							</select>
						</td>
					</tr>
				<?php } ?>
			</table>
			<table class="tbf3" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
				<tr>
				<?php if($choices == 1 || $choices == 3 || $choices == 4 || $choices == 5 || $choices == 6 || $choices == 7 || $choices == 8 || $choices == 9 || $choices == 11 || $choices == 12 || $choices == 13 || $choices == 14 || $choices == 15 || $choices == 16 || $choices == 18 || $choices == 19 || $choices == 21 || $choices == 22 || $choices == 24 || $choices == 25 || $choices == 27 || $choices == 28 || $choices == 30){ ?>
					<?php if($calender == 3 || $calender == 5 || $calender == 6 || $calender == 7 || $calender == 8 || $calender == 19 || $calender == 20){ ?>
						<td class="tbf3_td_p1_c"><input type="submit" name="lastmonth" style="width:100px; height:30px; font-size:12px;" value="<<"></td>
					<?php } ?>
					<?php if($calender <> 6 && $calender <> 7 && $calender <> 8 && $calender <> 20){ ?>
					<td class="tbf3_td_p1_c"><input type="submit" name="lastweek" style="width:100px; height:30px; font-size:12px;" value="<"></td>
					<?php } ?>
				<?php } ?>
					<td class="tbf3_td_p1_c"><input type="submit" name="search" style="width:100px; height:30px; font-size:12px;" value="検索"></td>
				<?php if($choices == 1 || $choices == 3 || $choices == 4 || $choices == 5 || $choices == 6 || $choices == 7 || $choices == 8 || $choices == 9 || $choices == 11 || $choices == 12 || $choices == 13 || $choices == 14 || $choices == 15 || $choices == 16 || $choices == 18 || $choices == 19 || $choices == 21 || $choices == 22 || $choices == 24 || $choices == 25 || $choices == 27 || $choices == 28 || $choices == 30){ ?>
					<?php if($calender <> 6 && $calender <> 7 && $calender <> 8 && $calender <> 20){ ?>
					<td class="tbf3_td_p1_c"><input type="submit" name="nextweek" style="width:100px; height:30px; font-size:12px;" value=">"></td>
					<?php } ?>
					<?php if($calender == 3 || $calender == 5 || $calender == 6 || $calender == 7 || $calender == 8 || $calender == 19 || $calender == 20){ ?>
						<td class="tbf3_td_p1_c"><input type="submit" name="nextmonth" style="width:100px; height:30px; font-size:12px;" value=">>"></td>
					<?php } ?>
				<?php } ?>
				</tr>
			</table>
		</form>
		<?php return array($week, $p_date1, $p_date2, $p_staff, $p_Auth, $p_compcd, $p_kbn, $factory, $p_time1, $p_time2, $p_team);
	}
	//--------------------------------------------------------------------------------------------------
	// ■メソッド名
	// getmodelnum
	//
	// ■概要
	// 型番一覧を取得
	//
	// ■引数
	// 第一引数：ＤＢ情報
	//
	//--------------------------------------------------------------------------------------------------
	function getmodelnum($db){

		//実行プログラム名取得
		$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
		//コードマスタの取得
		$query = "SELECT modelnum FROM php_pc_info";
		$query .= " WHERE delflg = 0 ";
		$query .= " GROUP BY modelnum ";
		$query .= " ORDER BY modelnum ";
		$this->ouputlog("query=" . $query, $prgid, SYS_LOG_TYPE_DBUG);
		if (! $rs = $db->query($query)) {
			$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		return $db->query($query);
	}
	//--------------------------------------------------------------------------------------------------
	// ■メソッド名
	// getStaffVenue
	//
	// ■概要
	// 会場担当者一覧を取得
	//
	// ■引数
	// 第一引数：ＤＢ情報
	// 第二引数：区分(1:会場担当者　2:会場メンバー)
	//
	//--------------------------------------------------------------------------------------------------
	function getStaffVenue($db,$kbn){
		//実行プログラム名取得
		$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
		//主担当
		if ($kbn == 1) {
			//担当者マスタの取得
			$query = "
				SELECT
					staff,team_members
				FROM
					php_staff_venue
				WHERE
					delflg = 0
				ORDER BY
					companycd desc,staff
			";
		} else if($kbn == 2) {
			//担当者マスタの取得
			$query = "
				SELECT
					staff
				FROM
					php_staff
				WHERE
					delflg = 0
				AND
					venueflg > 0
				ORDER BY
					venueflg ,idxnum
			";
		}
		$this->ouputlog("query=" . $query, $prgid, SYS_LOG_TYPE_DBUG);
		return $db->query($query);
	}
}
?>
