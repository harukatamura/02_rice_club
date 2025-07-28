
<?php
//==================================================================================================
// ■機能概要
// ・通販伝票出力画面
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
	require_once('./Classes/PHPExcel.php');
	require_once('./Classes/PHPExcel/IOFactory.php');
	//タイムゾーン
	date_default_timezone_set('Asia/Tokyo');

	//オブジェクト生成
	$html = new html();
	$comm = new comm();
	$dba = new dbaccess();
	$sql = new SQL_aggregate();
	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);

	foreach($_POST as $key=>$val) {
		$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_INFO);
	}

	$j_office_Uid = $_COOKIE['j_office_Uid'];
	$s_staff = $_COOKIE['con_perf_staff']; // (2023/02/18 若尾追加) 発行者取得用
	$select_slip = "SELECT A.slip_flg";
	$select_slip .= " FROM php_l_user A ";
	$select_slip .= " WHERE A.userid = " . sprintf("'%s'", $j_office_Uid);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($select_slip, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($select_slip))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$p_slip_flg = $row['slip_flg'];
	}
	//金曜日の場合はデフォルト5日後着までのデータを抽出、他は3日後着までのデータを抽出
	if(date('w') == 5){
		$p_maxday = 10;
	}else{
		$p_maxday = 7;
	}
	$today = date('Y-m-d H:i:s');
	//実行プログラム名取得
	$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
	$prgname = "通販伝票発行画面";
	$prgmemo = "　通販の伝票を発行することができます。";
	$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

	if (isset($_POST['designated_day'])) {
		$designated_day = $_POST['designated_day'];
	} else {
		$designated_day = $p_maxday."日以内";
	}
	if ( $designated_day == $p_maxday."日以内" ) {
		$maxday = date("Y-m-d",strtotime("+".$p_maxday." day"));
	} else {
		$maxday = date("Y-m-d",strtotime("+365 day"));
	}
	if (isset($_POST['slip_status'])) {
		$slip_status = $_POST['slip_status'];
	} else {
		$slip_status = "未発行分のみ表示";
	}
	if(isset($_POST['登録担当者'])) {
		$p_staff = $_POST['登録担当者'];
	} else {
		$p_staff = "会場予約";
	}
	$kensaku_pldn = $_POST['kensaku_pldn'];
	$kensaku = $_POST['kensaku'];
	$order_date1 = "";
	$order_date2 = "";
	if ($_POST['order_date1'] != "" && $p_slip_flg == 1) {
		$order_date1 = $_POST['order_date1']." 00:00:00";
	}
	if ($_POST['order_date2'] != "" && $p_slip_flg == 1) {
		$order_date2 = $_POST['order_date2']." 23:59:59";
	}
	// 会場予約用の型番リスト取得
	if ($p_staff == "会場予約") {
		$_select = " 
		SELECT 
			A.modelnum  
		FROM 
			php_personal_info A 
		LEFT OUTER JOIN 
			( 
				SELECT 
					modelnum, desktop 
				FROM 
					php_pc_info	
				WHERE 
					delflg = 0 
				GROUP BY 
					modelnum 
			)B 
		ON 
			A.modelnum = B.modelnum 
		WHERE 
			A.delflg = 0 
			AND A.cancelflg = 0 
			AND A.reservflg = 1 
			AND (B.desktop = 0 OR B.desktop IS NULL) ";
		/* 未出力のデータのみリストに表示　2024/2/11 田村 */
		$_select .= " AND A.outputflg = 0 ";
		/* ここまで */
		$_select .= " GROUP BY A.modelnum ";
		$_select .= " ORDER BY A.modelnum ";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($_select, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($_select))) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		$n_modelnum_list = [];
		while ($row = $rs->fetch_array()) {
			$n_modelnum_list[] = $row['modelnum'];
		}
		$_select = " 
		SELECT 
			A.modelnum  
		FROM 
			php_personal_info A 
		LEFT OUTER JOIN 
			(
				SELECT 
					modelnum, desktop 
				FROM 
					php_pc_info 
				WHERE 
					delflg = 0 
				GROUP BY 
					modelnum 
			)B 
		ON 
			A.modelnum = B.modelnum 
		WHERE 
			A.delflg = 0 
			AND A.cancelflg = 0 
			AND A.reservflg = 1 
			AND B.desktop = 1 ";
		/* 未出力のデータのみリストに表示　2024/2/11 田村 */
		$_select .= " AND A.outputflg = 0 ";
		/* ここまで */
		$_select .= " GROUP BY A.modelnum ";
		$_select .= " ORDER BY A.modelnum ";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($_select, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($_select))) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		$d_modelnum_list = [];
		while ($row = $rs->fetch_array()) {
			$d_modelnum_list[] = $row['modelnum'];
		}
		if ((isset($_POST['型番ノート']) || isset($_POST['型番デスク'])) && ($_POST['登録担当者_old'] == "" || $_POST['登録担当者_old'] == $p_staff)) {
			$g_n_modelnum_list = $_POST['型番ノート'];
			$g_d_modelnum_list = $_POST['型番デスク'];
			if(count($g_n_modelnum_list)>0 && count($g_d_modelnum_list)>0){
				$s_modelnum_list = array_merge($g_d_modelnum_list, $g_n_modelnum_list);
			}else if(count($g_n_modelnum_list)>0){
				$s_modelnum_list = $g_n_modelnum_list;
			}else if(count($g_d_modelnum_list)>0){
				$s_modelnum_list = $g_d_modelnum_list;
			}
		} else {
			$g_n_modelnum_list = $n_modelnum_list;
			$g_d_modelnum_list = $d_modelnum_list;
			$s_modelnum_list = array_merge($d_modelnum_list, $n_modelnum_list);
		}
	// ネット・電話注文用の型番リスト取得
	} else {
		//ノートPCの型番リスト作成
		$_select = " SELECT B.modelnum ";
		$_select .= " FROM php_telorder__ A ";
		$_select .= " LEFT OUTER JOIN ( ";
		$_select .= " SELECT modelnum, desktopflg, kbn FROM php_ecommerce_pc_info  ";
		$_select .= " GROUP BY modelnum ";
		$_select .= " )B ON A.modelnum=B.modelnum ";
		$_select .= " WHERE A.delflg = 0 ";
		$_select .= " AND (B.desktopflg = 0 OR B.desktopflg IS NULL) ";
		$_select .= " AND (B.kbn = 1 OR B.kbn IS NULL) ";
		if ($slip_status == "未発行分のみ表示") {
			$_select .= " AND A.output_flg=0";
			$_select .= " AND A.status=1";
		}
		$_select .= " GROUP BY B.modelnum ";
		$_select .= " ORDER BY B.modelnum ";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($_select, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($_select))) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		$n_modelnum_list = [];
		while ($row = $rs->fetch_array()) {
			$n_modelnum_list[] = $row['modelnum'];
		}
		//デスクトップの型番リスト作成
		$_select = " SELECT B.modelnum ";
		$_select .= " FROM php_telorder__ A ";
		$_select .= " LEFT OUTER JOIN ( ";
		$_select .= " SELECT modelnum, desktopflg FROM php_ecommerce_pc_info  ";
		$_select .= " GROUP BY modelnum ";
		$_select .= " )B ON A.modelnum=B.modelnum ";
		$_select .= " WHERE A.delflg = 0 ";
		$_select .= " AND B.desktopflg = 1 ";
		if ($slip_status == "未発行分のみ表示") {
			$_select .= " AND A.output_flg=0";
			$_select .= " AND A.status=1";
		}
		$_select .= " GROUP BY B.modelnum ";
		$_select .= " ORDER BY B.modelnum ";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($_select, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($_select))) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		$d_modelnum_list = [];
		while ($row = $rs->fetch_array()) {
			$d_modelnum_list[] = $row['modelnum'];
		}
		//周辺機器の型番リスト作成
		$_select = " SELECT B.modelnum ";
		$_select .= " FROM php_telorder__ A ";
		$_select .= " LEFT OUTER JOIN ( ";
		$_select .= " SELECT modelnum, desktopflg, kbn FROM php_ecommerce_pc_info  ";
		$_select .= " GROUP BY modelnum ";
		$_select .= " )B ON A.modelnum=B.modelnum ";
		$_select .= " WHERE A.delflg = 0 ";
		$_select .= " AND (B.desktopflg = 0 OR B.desktopflg IS NULL) ";
		$_select .= " AND B.kbn = 2";
		if ($slip_status == "未発行分のみ表示") {
			$_select .= " AND A.output_flg=0";
			$_select .= " AND A.status=1";
		}
		$_select .= " GROUP BY B.modelnum ";
		$_select .= " ORDER BY B.modelnum ";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($_select, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($_select))) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		$o_modelnum_list = [];
		while ($row = $rs->fetch_array()) {
			$o_modelnum_list[] = $row['modelnum'];
		}
		$g_n_modelnum_list = [];
		$g_d_modelnum_list = [];
		if ($_POST['登録担当者_old'] == "" || $_POST['登録担当者_old'] == $p_staff) {
			$g_n_modelnum_list = $_POST['型番ノート'];
			$g_d_modelnum_list = $_POST['型番デスク'];
			$g_o_modelnum_list = $_POST['型番周辺機器'];
		}
		if(count($g_n_modelnum_list)>0 && count($g_d_modelnum_list)>0 && count($g_o_modelnum_list)>0){
			$s_modelnum_list = array_merge($g_d_modelnum_list, $g_n_modelnum_list, $g_o_modelnum_list);
		}else if(count($g_n_modelnum_list)>0 && count($g_d_modelnum_list)>0){
			$s_modelnum_list = array_merge($g_d_modelnum_list, $g_n_modelnum_list);
		}else if(count($g_n_modelnum_list)>0 && count($g_o_modelnum_list)>0){
			$s_modelnum_list = array_merge($g_n_modelnum_list, $g_o_modelnum_list);
		}else if(count($g_d_modelnum_list)>0 && count($g_o_modelnum_list)>0){
			$s_modelnum_list = array_merge($g_d_modelnum_list, $g_o_modelnum_list);
		}else if(count($g_n_modelnum_list)>0){
			$s_modelnum_list = $g_n_modelnum_list;
		}else if(count($g_d_modelnum_list)>0){
			$s_modelnum_list = $g_d_modelnum_list;
		}else if(count($g_o_modelnum_list)>0){
			$s_modelnum_list = $g_o_modelnum_list;
		}else{
			$g_n_modelnum_list = $n_modelnum_list;
			$g_d_modelnum_list = $d_modelnum_list;
			$g_o_modelnum_list = $o_modelnum_list;
			$s_modelnum_list = array_merge($d_modelnum_list, $n_modelnum_list, $o_modelnum_list);
		}
		$s_modelnum_list = str_replace("\n","",$s_modelnum_list);
	}
	//担当者リスト
//	$staff_list[] = "";
	$staff_list[] = "会場予約";
	$staff_list[] = "新規顧客ネット通販";
	$staff_list[] = "既存顧客ネット通販";
	$staff_list[] = "電話通販";
	$staff_list[] = "代理店経由ネット通販";
	// $staff_list[] = "PCレンタル";
	//購入方法取得
	$_select = " SELECT A.sales_name, A.idxnum ";
	$_select .= " FROM php_headoffice_list A ";
	$_select .= " WHERE A.aggregation_flg = 1 ";
	$_select .= " AND A.delflg = 0 ";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($_select, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($_select))) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$sales_name[$row['idxnum']] = $row['sales_name'];
	}
	//共通型番取得
	$_select = " SELECT A.sales_name, A.sales_name, A.category, A.modelnum ";
	$_select .= " FROM php_ecommerce_pc_info A ";
	$_select .= " WHERE A.delflg = 0 ";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($_select, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($_select))) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$modelnum[$row['sales_name']][$row['category']] = $row['modelnum'];
	}
	//支払方法
	$p_way[0] = "元払";
	$p_way[2] = "代引";
	$p_way["元払"] = 0;
	$p_way["代引"] = 2;
	$seikyu_code["本部"] = "0529368887";
	$seikyu_code["補修センター"] = "052325741205";
	$code4["ネット通販"] = "NAS";
	$code4["電話通販"] = "TEL";
	$code4["PCレンタル"] = "RENT";
	//エクセル出力処理
	$outputno = $_POST['outputno'];
	$outputno2 = $_POST['outputno'];
	if((isset($_POST['output_btn_honbu']) || isset($_POST['output_btn_hosyu'])) && $outputno != "" && $p_staff != "会場予約") {
		$reader = PHPExcel_IOFactory::createReader("Excel2007");
		$book = $reader->load("./reservation_template4.xlsx");
		$sheet = $book->getSheetByName("ヤマト");
		$chksheet = $book->getSheetByName("リスト");
		foreach($outputno as $value){
			$query = "UPDATE php_telorder__ ";
			$query .= " SET output_flg = 2";
			$query .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
			$query .= " ,updcount = updcount + 1";
			$query .= " WHERE t_idx = $value";
			$query .= " AND modelnum IN (";
			$k=0;
			foreach($s_modelnum_list as $key => $val){
				if($k > 0){
					$query .= ", ";
				}
				$query .= "'".$val."'";
				++$k;
			}
			$query .= " )";
			$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
			$query = "SELECT A.option_han, A.buynum, A.status, B.opflg, B.desktopflg, A.reception_telnum, A.idxnum, B.category as tel_category, A.remark";
			$query .= " FROM php_telorder__ A ";
			$query .= " LEFT OUTER JOIN php_ecommerce_pc_info B ON A.category=B.category ";
			$query .= " WHERE A.t_idx = $value";
			if ($slip_status == "未発行分のみ表示") {
				$query .= " AND A.status = 1";
			}
			$query .= " AND A.output_flg = 2";
			$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (!($rs = $db->query($query))) {
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
			while ($row = $rs->fetch_array()) {
				$s_oprion_han = "";
				//付属品付きデータの場合、付属品付きデータは付属品をつける
				if ($row['status'] < 9) {
					if($row['opflg'] > 0 && $row['reception_telnum'] <> "代理店"){
						//備品欄に記入するオプションを取得
						for($i=0; $i<$row['buynum']; ++$i){
							if($row['option_han'] <> "なし" && $row['option_han'] <> ""){
								$s_oprion_han = $row['option_han'].'・';
							}
							$query_o = "SELECT A.name,A.opflg";
							$query_o .= " FROM php_ecommerce_pc_option A ";
							$query_o .= " WHERE A.opflg = '".$row['opflg']."'";
						//	$query_o .= " AND A.desktopflg = '".$row['desktopflg']."'";
							$query_o .= " AND A.kbn = '1'";
							$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($query_o, $prgid, SYS_LOG_TYPE_DBUG);
							if (!($rs_o = $db->query($query_o))) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
							while ($row_o = $rs_o->fetch_array()) {
								$s_oprion_han .= $row_o['name'].'・';
							}
							$s_oprion_han = rtrim($s_oprion_han, "・");
							$query2 = "UPDATE php_telorder__ ";
							$query2 .= " SET option_han = '".$s_oprion_han."'";
							$query2 .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
							$query2 .= " ,updcount = updcount + 1";
							$query2 .= " WHERE idxnum = '".$row['idxnum']."'";
							$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($query2, $prgid, SYS_LOG_TYPE_DBUG);
							if (!($rs2 = $db->query($query2))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
						}
						//備考欄に記入するオプションを取得
						$s_remarks = "";
						$query_o = "SELECT A.name,A.opflg";
						$query_o .= " FROM php_ecommerce_pc_option A ";
						$query_o .= " WHERE A.opflg = '".$row['opflg']."'";
					//	$query_o .= " AND A.desktopflg = '".$row['desktopflg']."'";
						$query_o .= " AND A.kbn = '2'";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($query_o, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs_o = $db->query($query_o))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						while ($row_o = $rs_o->fetch_array()) {
							$s_remarks .= $row_o['name'].',';
						}
						$s_remarks = rtrim($s_remarks, ",");
						$query2 = "UPDATE php_telorder__ ";
						$query2 .= " SET remark = '".$s_remarks." ".addslashes($row['remark'])."'";
						$query2 .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
						$query2 .= " ,updcount = updcount + 1";
						$query2 .= " WHERE idxnum = '".$row['idxnum']."'";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($query2, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs2 = $db->query($query2))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
					}
				}
			}
		}
		if(isset($_POST['output_btn_honbu'])){
			$g_factory = "本部";
		}else if(isset($_POST['output_btn_hosyu'])){
			$g_factory = "補修センター";
		}
		$a = 1;
		$i = 2;
		$j = 4; // 開始行
		for($desktopflg=0; $desktopflg<2; ++$desktopflg){
			//出力対象のデータを取得
			$query = "SELECT COUNT(A.name) as cnt_row, A.t_idx, MIN(A.idxnum) as idxnum ,A.postcd1 , A.postcd2 , A.address1 , A.address2 , A.address3 ";
			$query .= " , CASE ";
			$query .= " WHEN A.receipt<>'' THEN A.receipt ";
			$query .= " ELSE A.name ";
			$query .= " END as name ";
			$query .= " ,A.name as g_name , A.company , A.phonenum1, A.category, A.option_han ,A.designated_day, A.specified_times, A.remark, A.sales_name, A.order_num";
			$query .= " , SUM(A.cash) as sumcash, SUM(A.buynum) as sumnum, DATE(A.receptionday) as receptionday, A.p_way, A.locale, B.desktopflg, A.reception_telnum, B.opflg ";
			$query .= " FROM php_telorder__ A";
			$query .= " LEFT OUTER JOIN php_ecommerce_pc_info B ON A.category=B.category AND A.sales_name=B.sales_name";
			$query .= " WHERE A.output_flg = 2 AND A.delflg = 0";
			if ($slip_status == "未発行分のみ表示") {
				$query .= " AND A.status = 1 "; 
			}
			if ($order_date1 != "") {
				$query .= " AND A.receptionday >= " . sprintf("'%s'", $order_date1);
			}
			if ($order_date2 != "") {
				$query .= " AND A.receptionday <= " . sprintf("'%s'", $order_date2);
			}
			$query .= " AND B.desktopflg = '".$desktopflg."'";
			if($desktopflg == 0){
				$query .= " GROUP BY A.name , A.company , A.phonenum1 ,A.postcd1 , A.postcd2 , A.address1 , A.address2 , A.address3, A.p_way, A.sales_name ";
			}else{
				$query .= " GROUP BY A.idxnum ";
			}
			$query .= " ORDER BY A.remark <> '' DESC, sumnum DESC, B.modelnum, A.category, A.cash DESC, A.option_han, A.designated_day <>'0000-00-00' DESC, A.sales_name, A.receptionday, A.address1 LIKE '沖縄%' DESC, A.address1 LIKE '北海道%' DESC, A.idxnum";
			$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (!($rs = $db->query($query))) {
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
			while ($row = $rs->fetch_array()) {
				$way = $row['locale'];
				if($row['reception_telnum'] == "既存" || $row['reception_telnum'] == "代理店経由ネット通販"){
					$g_way[] = $row['reception_telnum'].$row['locale'];
				}else{
					$g_way[] = $row['locale'];
				}
				$query2 = "SELECT A.idxnum, A.category, A.cash, A.option_han, IFNULL(B.modelnum, A.category) as modelnum, A.buynum, B.factory, A.buynum, A.remark";
				$query2 .= " FROM php_telorder__ A";
				$query2 .= " LEFT OUTER JOIN php_ecommerce_pc_info B ON A.category=B.category AND A.sales_name=B.sales_name";
				$query2 .= " WHERE A.output_flg = 2 AND A.delflg = 0";
				if ($slip_status == "未発行分のみ表示") {
					$query2 .= " AND A.status = 1 "; 
				}
				$query2 .= " AND (A.name = '".addslashes($row['name'])."'";
				$query2 .= " OR A.receipt = '".addslashes($row['name'])."')";
				$query2 .= " AND A.company = '".addslashes($row['company'])."'";
				$query2 .= " AND A.phonenum1 = '".addslashes($row['phonenum1'])."'";
				$query2 .= " AND A.postcd1 = '".addslashes($row['postcd1'])."'";
				$query2 .= " AND A.postcd2 = '".addslashes($row['postcd2'])."'";
				$query2 .= " AND A.address1 = '".addslashes($row['address1'])."'";
				$query2 .= " AND A.address2 = '".addslashes($row['address2'])."'";
				$query2 .= " AND A.address3 = '".addslashes($row['address3'])."'";
				$query2 .= " AND A.sales_name = '".addslashes($row['sales_name'])."'";
				$query2 .= " AND B.desktopflg = '".addslashes($row['desktopflg'])."'";
				$query2 .= " AND A.p_way = '".addslashes($row['p_way'])."'";
				if($desktopflg == 1){
					$query2 .= " AND A.idxnum = '".addslashes($row['idxnum'])."'";
				}
				$query2 .= " ORDER BY B.modelnum, A.category";
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query2, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs2 = $db->query($query2))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				$g_cashlist = [];
				$g_modellist = [];
				$g_option1 = "";
				$g_option2 = "";
				$option_han = [];
				$remarks = "";
				$p_modelnum = "";
				$s_modelnum = "";
				while($row2 = $rs2->fetch_array()) {
					for($t=0; $t<$row2['buynum']; ++$t){
						$g_modellist[] = $row2['modelnum'];
						$g_cashlist[] = $row2['cash']/$row2['buynum'];
						if($row2['option_han'] <> "なし"){
							$g_option1 .= $row2['option_han']."・";
						}
						if($remarks <> $row2['remark']){
							$remarks .= $row2['remark'];
						}
					}
					//複数購入の場合、sendstaffの項目に1行目のインデックスを入れる
					$update2 = "UPDATE php_telorder__ ";
					$update2 .= " SET sendstaff = " . sprintf("'%s'", $row['idxnum']);
					$update2 .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
					$update2 .= " ,updcount = updcount + 1";
					$update2 .= " WHERE idxnum = '".$row2['idxnum']."'";
					$update2 .= " AND output_flg = 2";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($update2, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs3 = $db->query($update2))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
				}
				$g_option1 = rtrim($g_option1, "・");
				//複数購入の場合
				if(count($g_modellist) > 1){
					//カテゴリを台数ごとに表示
					foreach(array_count_values($g_modellist) as $key => $val){
						$p_modelnum .= $key."×".$val."台".",";
						$s_modelnum .= $key.",";
					}
					$p_modelnum = rtrim($p_modelnum, ",");
					$s_modelnum = rtrim($s_modelnum, ",");
					if($remarks <> ""){
						$remarks .= "\r\n";
					}
					$remarks .= "複数台購入(";
					//備考に金額の内訳を記入
					foreach(array_count_values($g_cashlist) as $key => $val){
						if($val > 1){
							$remarks .= $key."円×".$val.",";
						}else{
							$remarks .= $key."円,";
						}
					}
					$remarks = rtrim($remarks, ",");
					$remarks .= ")";
				//単品購入の場合
				}else{
					$p_modelnum = $g_modellist[0];
					$s_modelnum = $g_modellist[0];
				}
				//オプションを配列に格納
				$g_option2 = explode("・",$g_option1);
				foreach(array_count_values($g_option2) as $key => $val){
					if($val == 1){
						$option_han[] = $key;
					}else{
						$option_han[] = $key."×".$val."個";
					}
				}
				if($desktopflg == 1){
					if($remarks <> ""){
						$remarks .= "　";
					}
					$remarks .= "ﾃﾞｽｸﾄｯﾌﾟ";
				}
				if(mb_substr($row['address1'],0,2) == "沖縄" || mb_substr($row['address1'],0,3) == "北海道"){
					if($remarks <> ""){
						$remarks .= "　";
					}
					$remarks .= "陸・船便発送";
				}
				if(strpos($s_modelnum, "Ci5-4GB-R73") !== false){
					if($remarks <> ""){
						$remarks .= "　";
					}
					$remarks .= "※i5R73";
				}
				if(strpos($s_modelnum, "Ci5-4GB-SSDM") !== false){
					if($remarks <> ""){
						$remarks .= "　";
					}
					if($row['sales_name'] == "100002"){
						$remarks .= "※VK26 OR VK27";
					}else if($row['sales_name'] == "100001"){
						$remarks .= "※i5 ﾚｯﾂﾉｰﾄ";
					}
				}
				if($row['sales_name'] == "100005"){
					$remarks .= "レンタルPC";
				}
				//ヤマトシートのセルに値をセット
				//暫定対応
				if($row['p_way'] == 1) {
					$sheet->setCellValueByColumnAndRow(1, $i, "A");
				} else {
					$sheet->setCellValueByColumnAndRow(1, $i, $row['p_way']);
				}
				$sheet->setCellValueByColumnAndRow(4, $i, date('Y/n/j'));
				if($row['designated_day'] <> "0000-00-00"){
					$sheet->setCellValueByColumnAndRow(5, $i, date('Y/n/j', strtotime($row['designated_day'])));
					if(strtotime($row['designated_day'].'- 7 day') > strtotime(date('Ymd'))){
						$sheet->setCellValueByColumnAndRow(4, $i, date('Y/n/j',strtotime($row['designated_day'].'-7day')));
					}
				}
				if($row['specified_times'] <> "指定なし"){
					$sheet->setCellValueExplicitByColumnAndRow(6, $i, $row['specified_times'], PHPExcel_Cell_DataType::TYPE_STRING);
				}
				$sheet->setCellValueExplicitByColumnAndRow(8, $i, mb_convert_kana($row['phonenum1'],"nk"), PHPExcel_Cell_DataType::TYPE_STRING);
				$sheet->setCellValueByColumnAndRow(10, $i, mb_convert_kana($row['postcd1'],"nk")."-".mb_convert_kana($row['postcd2'],"nk"));
				$sheet->setCellValueByColumnAndRow(11, $i, $row['address1'].mb_convert_kana($row['address2'],"nk"));
				$sheet->setCellValueByColumnAndRow(12, $i, mb_convert_kana($row['address3'],"nk"));
				$sheet->setCellValueByColumnAndRow(13, $i, $row['company']);
				$sheet->setCellValueByColumnAndRow(15, $i, $row['name']);
				$sheet->setCellValueExplicitByColumnAndRow(19, $i, "052-936-8887", PHPExcel_Cell_DataType::TYPE_STRING);
				$sheet->setCellValueExplicitByColumnAndRow(21, $i, "461-0011", PHPExcel_Cell_DataType::TYPE_STRING);
				$sheet->setCellValueByColumnAndRow(22, $i, "愛知県名古屋市東区白壁3-12-13");
				$sheet->setCellValueByColumnAndRow(23, $i, "中部産業連盟ビル新館4F");
				$sheet->setCellValueByColumnAndRow(24, $i, "(一社)日本電子機器補修協会");
				$sheet->setCellValueByColumnAndRow(27, $i, "PC(".$s_modelnum."　計".$row['sumnum']."点)");
				if($g_option1 <> ""){
					$sheet->setCellValueByColumnAndRow(29, $i, "周辺機器");
				}
					$sheet->setCellValueByColumnAndRow(29, $i, "ご注文日：".date('Y/n/j', strtotime($row['receptionday']))."No.".$row['t_idx']);
				$sheet->setCellValueByColumnAndRow(30, $i, "精密機器");
				$sheet->setCellValueByColumnAndRow(31, $i, "ワレ物注意");
				$sheet->setCellValueByColumnAndRow(32, $i, date('ymd', strtotime($row['receptionday']))." ".$row['locale']." (".$row['address1'].") No.".$row['t_idx']);
				if($row['p_way'] == "2" || $row['p_way'] == "9"){
					$sheet->setCellValueByColumnAndRow(33, $i, $row['sumcash']);
					$sheet->setCellValueExplicitByColumnAndRow(38, $i, "2", PHPExcel_Cell_DataType::TYPE_STRING);
				}else{
					$sheet->setCellValueExplicitByColumnAndRow(38, $i, "1", PHPExcel_Cell_DataType::TYPE_STRING);
				}
				$sheet->setCellValueExplicitByColumnAndRow(39, $i, $seikyu_code[$g_factory], PHPExcel_Cell_DataType::TYPE_STRING);
				$sheet->setCellValueExplicitByColumnAndRow(41, $i, "01", PHPExcel_Cell_DataType::TYPE_STRING);
				//検索キー
				if($way <> "電話通販"){
					//電話注文以外ならオーダー番号を入れる
					$sheet->setCellValueExplicitByColumnAndRow(74, $i, "オーダー番号");
					$sheet->setCellValueExplicitByColumnAndRow(75, $i, $row['order_num']);
				}
				$sheet->setCellValueExplicitByColumnAndRow(76, $i, "金額");
				$sheet->setCellValueExplicitByColumnAndRow(77, $i, $row['sumcash']);
				$sheet->setCellValueExplicitByColumnAndRow(78, $i, "注文番号");
				$sheet->setCellValueExplicitByColumnAndRow(79, $i, $row['idxnum'], PHPExcel_Cell_DataType::TYPE_STRING);
				$sheet->setCellValueExplicitByColumnAndRow(80, $i, "購入方法");
				$sheet->setCellValueExplicitByColumnAndRow(81, $i, $code4[$way]);
				$sheet->setCellValueExplicitByColumnAndRow(82, $i, "枝番");
				$sheet->setCellValueExplicitByColumnAndRow(83, $i, $row['branch']);
				//改ページ
				$j2 = $j - 1;
				if($g_modelnum <> $p_modelnum || $g_option1 <> $g_option || $g_cash <> $row['sumcash'] || $g_buynum <> $row['buynum'] || ($g_remarks == "" && $remarks <> "") || ($g_remarks == "" && $row['remarks'] <> "") || $g_deliveryday <> date('Y/n/j', strtotime($row['designated_day'])) || $g_receptionday <> date('Y/n/j', strtotime($row['receptionday']))){
					if($i>2){
						$chksheet->setBreak('A'.$j2, PHPExcel_Worksheet::BREAK_ROW);
					}
					$g_modelnum = $p_modelnum;
					$g_option = $g_option1;
					$g_cash = $row['sumcash'];
					$g_buynum = $row['buynum'];
					$g_deliveryday = date('Y/n/j', strtotime($row['designated_day']));
					$g_remarks = $remarks;
					$g_receptionday = date('Y/n/j', strtotime($row['receptionday']));
					$a = 1;
				}
				//チェックシートのセルに値をセット
				$chksheet->setCellValueByColumnAndRow(0, $j, "□");
				$chksheet->setCellValueByColumnAndRow(1, $j, date('n/j', strtotime($row['receptionday'])));
				$chksheet->setCellValueByColumnAndRow(2, $j, $a);
				$chksheet->setCellValueByColumnAndRow(3, $j, $row['name']);
				$chksheet->setCellValueByColumnAndRow(4, $j, $p_modelnum);
				$chksheet->setCellValueByColumnAndRow(5, $j, $row['sumcash']/100);
				if($row['designated_day'] <> "0000-00-00"){
					$chksheet->setCellValueByColumnAndRow(6, $j, date('Y/n/j', strtotime($row['designated_day'])));
				}
				$chksheet->setCellValueByColumnAndRow(7, $j, $option_han[0]);
				$chksheet->setCellValueByColumnAndRow(8, $j, $option_han[1]);
				$chksheet->setCellValueByColumnAndRow(9, $j, $option_han[2]);
				$chksheet->setCellValueByColumnAndRow(10, $j, $option_han[3]);
				$chksheet->setCellValueByColumnAndRow(11, $j, $option_han[4]);
				$chksheet->setCellValueByColumnAndRow(12, $j, $remarks);
				++$a;
				//複数購入の場合は、背景色をつける
				if($row['sumnum'] > 1){
					$chksheet->getStyle('A'.$j.':M'.$j)->getFill()->setFillType( PHPExcel_Style_Fill::FILL_SOLID )->getStartColor()->setARGB('ccffcc');
				}
				++$i;
				++$j;
			}
		}
		foreach(array_count_values($g_way) as $key => $val){
			$s_way .= $key.",";
		}
		$s_way = rtrim($s_way, ",");
		$s_way = str_replace('通販','',$s_way);
		$j = $j - 1;
		//罫線をつける
		$chksheet->getStyle('A4:M'.$j)->getBorders()->getAllBorders()->setBorderStyle( PHPExcel_Style_Border::BORDER_THIN );
		//印刷範囲を指定
		$chksheet->getPageSetup()->setPrintArea('A1:M'.$j);
		//ヘッダの記入
		/* 
		(2023/02/18 若尾更新)
		発行者を表示
		$chktitle = $s_way."（".date('Y/n/j')."発行)";
		*/
		$chktitle = $s_way."（".date('Y/n/j')." 発行) 発行者：".$s_staff;
		$chksheet->setCellValue("A1", "$chktitle");
		$chksheet->mergeCells( 'A1:G1');
		$chksheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT); 
		//ecommerceテーブルにデータを登録
		$insert = " INSERT INTO php_ecommerce ";
		$insert .= " (insdt, upddt, name, phonenum1, buynum, cash, receptionday, category, locale, reception_telnum, postcd1, postcd2, address1, address2, address3, option_han, modelnum) ";
		$insert .= " SELECT '".date('Y-m-d H:i:s')."','".date('Y-m-d H:i:s')."',A.name, A.phonenum1, A.buynum, A.cash, A.receptionday, A.category, A.sales_name, A.reception_telnum, A.postcd1, A.postcd2, A.address1, A.address2, A.address3, A.option_han, B.modelnum ";
		$insert .= " FROM php_telorder__ A ";
		$insert .= " LEFT OUTER JOIN php_ecommerce_pc_info B  ON A.category=B.category AND A.sales_name=B.sales_name";
		$insert .= " WHERE A.delflg=0 ";
		if ($slip_status == "未発行分のみ表示") {
			$query .= " AND A.status = 1";
		}
		$insert .= " AND A.output_flg = 2 ";
		$comm->ouputlog("データ登録 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($insert))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		//フラグ更新
		foreach($outputno2 as $value){
			$query = "UPDATE php_telorder__ ";
			$query .= " SET status = 9";
			$query .= " , response = '".$g_factory."'";
			$query .= " , output_flg = 3";
			$query .= " ,outputdt = " . sprintf("'%s'", date('YmdHis'));
			$query .= " ,slip_staff = " . sprintf("'%s'", $s_staff);
			$query .= " WHERE t_idx = $value";
			$query .= " AND modelnum IN (";
			$k=0;
			foreach($s_modelnum_list as $key => $val){
				if($k > 0){
					$query .= ", ";
				}
				$query .= "'".$val."'";
				++$k;
			}
			$query .= " )";
			$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
		}
		if($g_factory == "補修センター"){
			$filename = "【".$s_way."】".date('Ymd')." 依頼.xlsx";
		}else{
			$filename = "【".$s_way."】".date('Ymd')." 本部発送.xlsx";
		}
		$filename = mb_convert_encoding($filename,'sjis','utf-8');
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $filename . '"');
		header('Cache-Control: max-age=0');
		//ファイル破損を防ぐ
		ob_clean(); 
		//対象ファイル保存する
		$objWriter = PHPExcel_IOFactory::createWriter($book,'Excel2007');
		$objWriter->save('php://output',$filename);
		exit;
	} else if ((isset($_POST['output_btn_honbu']) || isset($_POST['output_btn_hosyu'])) && $outputno != "" && $p_staff == "会場予約") {
		$comm->ouputlog("Excel出力 実行", $prgid, SYS_LOG_TYPE_INFO);
		$reader = PHPExcel_IOFactory::createReader("Excel2007");
		$book = $reader->load("./reservation_template3.xlsx");
		$sheet = $book->getSheetByName("ヤマト");
		$chksheet = $book->getSheetByName("リスト");
		$date_chksheet = $book->getSheetByName("リスト_日付指定");
		//フラグを立てて出力するデータを取得
		foreach($outputno as $value){
			$query = "UPDATE php_personal_info ";
			$query .= " SET outputflg = 3";
			$query .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
			$query .= " ,updcount = updcount + 1";
			$query .= " WHERE idxnum = $value";
			$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
		}
		if(isset($_POST['output_btn_honbu'])){
			$g_factory = "本部";
		}else if(isset($_POST['output_btn_hosyu'])){
			$g_factory = "補修センター";
		}
		$i = 1;
		$j = 3; // 開始行
		$t = 0;
		$g_modelnum = "";
		$g_cash = "";
		$g_g_staff = "";
		$g_buynum = "";
		$p_chksheet = $chksheet;
		for($deliveryflg=0; $deliveryflg<2; ++$deliveryflg){
		/*	if($deliveryflg == 0){
				$p_chksheet = $chksheet;
			}else{
				//シートを変更
				$comm->ouputlog("===========シートを変更===========", $prgid, SYS_LOG_TYPE_INFO);
				//罫線をつける
				$p_chksheet->getStyle('B4:O'.$j)->getBorders()->getAllBorders()->setBorderStyle( PHPExcel_Style_Border::BORDER_THIN );
				//印刷範囲を指定
				$p_chksheet->getPageSetup()->setPrintArea('A1:O'.$j);
				//ヘッダを記入
				$p_chksheet->setCellValueByColumnAndRow(0, 1, "譲渡会予約PC(".date('n/j').")");
				$p_chksheet = $date_chksheet;
				$j = 3;
				$t = 0;
			}
		*/	for($desktopflg=0; $desktopflg<2; ++$desktopflg){
				//出力対象のデータを取得
				$query = "SELECT A.idxnum, SUM(A.cash) as sumkin, SUM(A.buynum) as buynum, A.venueid";
				$query .= " ,A.name, A.company, A.phonenum1, A.postcd1, A.postcd2, A.address1, A.address2, A.address3 ";
				$query .= " ,A.g_buydt, A.g_staff, A.g_locale, A.remarks, A.p_way, A.reserv, A.deliveryday, A.deliverytime, B.desktop, A.b_way, A.modelnum , A.reserv";
				$query .= " FROM php_personal_info A";
				$query .= " LEFT OUTER JOIN (";
				$query .= " SELECT distinct(modelnum) as modelnum, desktop FROM php_pc_info";
				$query .= " WHERE delflg = 0";
				$query .= " ) B ON A.modelnum=B.modelnum";
				$query .= " WHERE A.outputflg = 3 AND A.delflg = 0";
				$query .= " AND A.cancelflg = 0 ";
				$query .= " AND B.desktop = '".$desktopflg."'";
				if($deliveryflg == 0){
					$query .= " AND A.deliveryday = '0000-00-00' ";
				}else{
					$query .= " AND A.deliveryday <> '0000-00-00' ";
				}
				if($desktopflg == 0){
					$query .= " GROUP BY A.name , A.company , A.phonenum1 ,A.postcd1 , A.postcd2 , A.address1 , A.address2 , A.address3, A.p_way, A.venueid , A.reserv, A.deliveryday";
				}else{
					$query .= " GROUP BY A.idxnum ";
				}
				/* ソート順を変更 2024/2/11 田村 
				$query .= " ORDER BY A.remarks DESC  ,A.modelnum  ,A.cash  ,A.deliveryday  ,A.option1  ,A.option2  ,A.option3  ,A.option4  ,A.venueid, A.address1 LIKE '沖縄%' DESC, A.address1 LIKE '北海道%' DESC ,A.name ,A.reserv";
				*/
				$query .= " ORDER BY A.remarks DESC  ,A.modelnum  ,A.cash  ,A.deliveryday  ,A.option1  ,A.option2  ,A.option3  ,A.option4  ,A.venueid, A.address1 LIKE '沖縄%' DESC, A.address1 LIKE '北海道%' DESC ,A.name ,A.reserv";
				/* ここまで修正 2024/2/11 田村 */
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs = $db->query($query))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				while($row = $rs->fetch_array()){
					//デスクトップの場合、価格表の備考欄を取得
					$d_remarks = "";
					if($desktopflg == 1){
						$query_d = "SELECT A.modelnum, A.memo ";
						$query_d .= " FROM php_pc_price A";
						$query_d .= " WHERE A.modelnum = '".$row['modelnum']."'";
						$query_d .= " AND A.week = ";
						$query_d .= " (SELECT week FROM php_calendar WHERE date = '".mb_substr($row['venueid'], 0, 8)."')";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($query_d, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs_d = $db->query($query_d))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						while($row_d = $rs_d->fetch_array()) {
							$d_remarks = $row_d['memo'];
						}
					}
					$query2 = "SELECT A.modelnum, A.cash, A.option1, A.option2, A.option3, A.option4, A.buynum, A.remarks, A.reserv ";
					$query2 .= " FROM php_personal_info A";
					$query2 .= " LEFT OUTER JOIN (";
					$query2 .= " SELECT distinct(modelnum) as modelnum, IFNULL(desktop,0) as desktop FROM php_pc_info";
					$query2 .= " WHERE delflg = 0";
					$query2 .= " ) B ON A.modelnum=B.modelnum";
					$query2 .= " WHERE A.status = 1 AND A.outputflg = 3 AND A.delflg = 0";
					$query2 .= " AND A.cancelflg = 0 ";
					$query2 .= " AND A.name = '".$row['name']."'";
					$query2 .= " AND A.name = '".$row['name']."'";
					$query2 .= " AND A.company = '".$row['company']."'";
					$query2 .= " AND A.phonenum1 = '".$row['phonenum1']."'";
					$query2 .= " AND A.postcd1 = '".$row['postcd1']."'";
					$query2 .= " AND A.postcd2 = '".$row['postcd2']."'";
					$query2 .= " AND A.address1 = '".$row['address1']."'";
					$query2 .= " AND A.address2 = '".$row['address2']."'";
					$query2 .= " AND A.address3 = '".$row['address3']."'";
					$query2 .= " AND A.venueid = '".$row['venueid']."'";
					$query2 .= " AND A.p_way = '".$row['p_way']."'";
					if($desktopflg == 1){
						$query2 .= " AND A.idxnum = '".$row['idxnum']."'";
						$query2 .= " AND B.desktop = '".$row['desktop']."'";
					}else{
						$query2 .= "AND (B.desktop = '".$row['desktop']."' OR B.desktop IS NULL)";
					}
					$query2 .= " AND A.deliveryday = '".$row['deliveryday']."'";
					$query2 .= " ORDER BY B.modelnum";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query2, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs2 = $db->query($query2))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$g_cashlist = [];
					$g_modellist = [];
					$g_option1 = "";
					$g_option2 = "";
					$option_han = [];
					$remarks = "";
					$p_modelnum = "";
					$s_modelnum = "";
					$g_option2 = [];
					while($row2 = $rs2->fetch_array()) {
						for($a=0; $a<$row2['buynum']; ++$a){
							if ($row['reserv'] == $row2['reserv']) {
								$g_modellist[] = $row2['modelnum'];
								$g_cashlist[] = $row2['cash']/$row2['buynum'];
								for($l=1;$l<6;++$l){
									if($row2['option'.$l] <> "駐車料金" && $row2['option'.$l] <> ""){
										$g_option2[] = $row2['option'.$l];
									}
								}
								if($remarks <> $row2['remarks']){
									$remarks .= $row2['remarks'];
								}
							}
						}
						if(count($p_modelnum_list)==0 || !in_array($row2['modelnum'], $p_modelnum_list)){
							$p_modelnum_list[] = $row2['modelnum'];
						}
						if(count($p_date_list[$row2['modelnum']])==0 || !in_array($row['g_buydt'], $p_date_list[$row2['modelnum']])){
							$p_date_list[$row2['modelnum']][] = $row['g_buydt'];
						}
						if(count($p_staff_list[$row2['modelnum']][$row['g_buydt']])==0 || !in_array($row['g_staff'], $p_staff_list[$row2['modelnum']][$row['g_buydt']])){
							$p_staff_list[$row2['modelnum']][$row['g_buydt']][] = $row['g_staff'];
						}
						$sumnum_list[$row2['modelnum']][$row['g_buydt']][$row['g_staff']] = $sumnum_list[$row2['modelnum']][$row['g_buydt']][$row['g_staff']] + $row2['buynum'];
						
						//sendidxnumの項目に1行目のインデックスを入れる
						$update2 = "UPDATE php_personal_info ";
						$update2 .= " SET send_idxnum = " . sprintf("'%s'", $row['idxnum']);
						$update2 .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
						$update2 .= " ,updcount = updcount + 1";
						$update2 .= " WHERE idxnum = '".$row2['idxnum']."'";
						$update2 .= " AND outputflg = 3";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($update2, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs3 = $db->query($update2))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
					}
					//複数購入の場合
					if(count($g_modellist) > 1){
						//カテゴリを台数ごとに表示
						foreach(array_count_values($g_modellist) as $key => $val){
							$p_modelnum .= $key."×".$val."台".",";
							$s_modelnum .= $key.",";
						}
						$p_modelnum = rtrim($p_modelnum, ",");
						$s_modelnum = rtrim($s_modelnum, ",");
						if($remarks <> ""){
							$remarks .= "\r\n";
						}
						$remarks .= "複数台購入(";
						//備考に金額の内訳を記入
						foreach(array_count_values($g_cashlist) as $key => $val){
							if($val > 1){
								$remarks .= $key."×".$val.",";
							}else{
								$remarks .= $key.",";
							}
						}
						$remarks = rtrim($remarks, ",");
						$remarks .= ")";
					//単品購入の場合
					}else{
						$p_modelnum = $g_modellist[0];
					}
					//オプションを配列に格納
					if(count($g_option2)>0){
						foreach(array_count_values($g_option2) as $key => $val){
							if($val == 1){
								$option_han[] = $key;
							}else{
								$option_han[] = $key."×".$val."個";
							}
						}
					}
					//備考に日付指定ありと追記
					if($row['deliveryday'] <> "0000-00-00"){
						if($remarks <> ""){
							$remarks .= "\n";
						}
						$remarks .= "※日付指定有※";
					}
					//備考にデスクトップと追記
					if($desktopflg == 1){
						if($remarks <> ""){
							$remarks .= "　";
						}
						$remarks .= "ﾃﾞｽｸﾄｯﾌﾟ";
					}
					//備考にレンタルと追記
					if($row['b_way'] == "レンタル"){
						$remarks = "ﾚﾝﾀﾙ:".$remarks;
					}
					//備考に下取と追記
					if($row['b_way'] == "下取"){
						$remarks = "【下取】".$remarks;
					}
					//備考に発送方法追記
					if(mb_substr($row['address1'],0,2) == "沖縄" || mb_substr($row['address1'],0,3) == "北海道"){
						if($remarks <> ""){
							$remarks .= "　";
						}
						$remarks .= "陸・船便発送";
					}
					//備考に価格表の備考を追記
					$remarks .= " ".$d_remarks;
					++$i;
					++$j;
					++$t;
					$j2 = $j-1;
					//ヤマトシートのセルに値をセット
					$sheet->setCellValueByColumnAndRow(1, $i, $p_way[$row['p_way']]);
					$sheet->setCellValueByColumnAndRow(4, $i, date('Y/n/j'));
					if($row['deliveryday'] <> "0000-00-00"){
						$sheet->setCellValueByColumnAndRow(5, $i, date('Y/n/j', strtotime($row['deliveryday'])));
						if(strtotime($row['deliveryday'].'- 7 day') > strtotime(date('Ymd'))){
							$sheet->setCellValueByColumnAndRow(4, $i, date('Y/n/j',strtotime($row['deliveryday'].'-7day')));
						}
					}
					if($row['deliverytime'] <> "指定なし"){
						$sheet->setCellValueExplicitByColumnAndRow(6, $i, $row['deliverytime'], PHPExcel_Cell_DataType::TYPE_STRING);
					}
					$sheet->setCellValueExplicitByColumnAndRow(8, $i, $row['phonenum1'], PHPExcel_Cell_DataType::TYPE_STRING);
					$sheet->setCellValueByColumnAndRow(10, $i, $row['postcd1']."-".$row['postcd2']);
					$sheet->setCellValueByColumnAndRow(11, $i, $row['address1'].$row['address2']);
					$sheet->setCellValueByColumnAndRow(12, $i, $row['address3']);
					$sheet->setCellValueByColumnAndRow(13, $i, $row['company']);
					$sheet->setCellValueByColumnAndRow(15, $i, $row['name']);
					$sheet->setCellValueExplicitByColumnAndRow(19, $i, "052-936-8887", PHPExcel_Cell_DataType::TYPE_STRING);
					$sheet->setCellValueExplicitByColumnAndRow(21, $i, "461-0011", PHPExcel_Cell_DataType::TYPE_STRING);
					$sheet->setCellValueByColumnAndRow(22, $i, "愛知県名古屋市東区白壁3-12-13");
					$sheet->setCellValueByColumnAndRow(23, $i, "中部産業連盟ビル新館4F");
					$sheet->setCellValueByColumnAndRow(24, $i, "(一社)日本電子機器補修協会");
					$sheet->setCellValueByColumnAndRow(27, $i, "予約PC(".$p_modelnum."　計".$row['buynum']."台)");
					$sheet->setCellValueByColumnAndRow(29, $i, $row['option1']." ".$row['option2']." ".$row['option3']." ".$row['option4']);
					$sheet->setCellValueByColumnAndRow(29, $i, "ご予約日：".date('y/n/j', strtotime($row['g_buydt']))." No.".$row['idxnum']);
					$sheet->setCellValueByColumnAndRow(30, $i, "精密機器");
					$sheet->setCellValueByColumnAndRow(31, $i, "ワレ物注意");
					$sheet->setCellValueByColumnAndRow(32, $i, date('ymd', strtotime($row['g_buydt']))." ".mb_convert_kana($row['g_locale'],"ksa"));
					if($row['p_way'] == "代引"){
						$sheet->setCellValueByColumnAndRow(33, $i, $row['sumkin']*100);
						$sheet->setCellValueByColumnAndRow(38, $i, "3");
					}else{
						$sheet->setCellValueByColumnAndRow(38, $i, "2");
					}
					$sheet->setCellValueExplicitByColumnAndRow(39, $i, $seikyu_code[$g_factory], PHPExcel_Cell_DataType::TYPE_STRING);
					$sheet->setCellValueExplicitByColumnAndRow(41, $i, "01", PHPExcel_Cell_DataType::TYPE_STRING);
					$sheet->setCellValueExplicitByColumnAndRow(76, $i, "金額");
					$sheet->setCellValueExplicitByColumnAndRow(77, $i, $row['sumkin']*100);
					$sheet->setCellValueExplicitByColumnAndRow(78, $i, "注文番号");
					$sheet->setCellValueExplicitByColumnAndRow(79, $i, $row['idxnum'], PHPExcel_Cell_DataType::TYPE_STRING);
					$sheet->setCellValueExplicitByColumnAndRow(80, $i, "購入方法");
					$sheet->setCellValueExplicitByColumnAndRow(81, $i, "PREF");
					//チェックシートのセルに値をセット
					//改ページ
					//if($g_modelnum <> $p_modelnum || $g_cash <> $row['sumkin'] || $g_buynum <> $row['buynum'] || $g_option_han <> $option_han || $g_g_staff <> $row['g_staff'] || ($g_deliveryday <> date('Y/n/j', strtotime($row['deliveryday'])) && $row['deliveryday'] <> "0000-00-00")){
					if($g_modelnum <> $p_modelnum || $g_cash <> $row['sumkin'] || $g_buynum <> $row['buynum'] || $g_option_han <> $option_han || ($g_deliveryday <> date('Y/n/j', strtotime($row['deliveryday'])) && $row['deliveryday'] <> "0000-00-00")){
						if($j>4){
							$p_chksheet->setBreak('A'.$j2, PHPExcel_Worksheet::BREAK_ROW);
							$t=1;
						}
						$g_modelnum = $p_modelnum;
						$g_option_han = $option_han;
						$g_cash = $row['sumkin'];
						$g_buynum = $row['buynum'];
						$g_g_staff = $row['g_staff'];
						$g_deliveryday = date('Y/n/j', strtotime($row['deliveryday']));
					}
					$p_chksheet->setCellValueByColumnAndRow(0, $j, "□");
					$p_chksheet->setCellValueByColumnAndRow(1, $j, $t);
					$p_chksheet->setCellValueByColumnAndRow(2, $j, $row['name']);
					$p_chksheet->setCellValueByColumnAndRow(3, $j, $p_modelnum);
					$p_chksheet->setCellValueByColumnAndRow(4, $j, $row['sumkin']);
					$p_chksheet->setCellValueByColumnAndRow(5, $j, $row['reserv']);
					if($row['deliveryday'] <> "0000-00-00"){
						$p_chksheet->setCellValueByColumnAndRow(6, $j, date('Y/n/j', strtotime($row['deliveryday'])));
					}
					$p_chksheet->setCellValueByColumnAndRow(7, $j, $option_han[0]);
					$p_chksheet->setCellValueByColumnAndRow(8, $j, $option_han[1]);
					$p_chksheet->setCellValueByColumnAndRow(9, $j, $option_han[2]);
					$p_chksheet->setCellValueByColumnAndRow(10, $j, $option_han[3]);
					$p_chksheet->setCellValueByColumnAndRow(11, $j, date('Y/n/j', strtotime($row['g_buydt'])));
					$p_chksheet->setCellValueByColumnAndRow(12, $j, $row['g_staff']);
					$p_chksheet->setCellValueByColumnAndRow(13, $j, mb_convert_kana($row['g_locale'],"ksa"));
					$p_chksheet->setCellValueByColumnAndRow(14, $j, $remarks);
				}
			}
		}
		//罫線をつける
		$p_chksheet->getStyle('B4:O'.$j)->getBorders()->getAllBorders()->setBorderStyle( PHPExcel_Style_Border::BORDER_THIN );
		//印刷範囲を指定
		$p_chksheet->getPageSetup()->setPrintArea('A1:O'.$j);
		/*
		//ヘッダを記入
		(2023/02/18 若尾更新)
		新テンプレートに合わせ行数変更・発行者を表示
		$p_chksheet->setCellValueByColumnAndRow(0, 1, "譲渡会予約PC(".date('n/j').")");
		*/
		$p_chksheet->setCellValueByColumnAndRow(0, 1, "譲渡会予約PC(".date('Y/n/j')." 発行) 発行者：".$s_staff);
		$p_chksheet->getStyleByColumnAndRow(0, 1)->getFont()->setBold(true);
		//リストを作成する
		$j=4;
		asort($p_modelnum_list);
		asort($p_date_list);
		asort($p_staff_list);
		foreach($p_modelnum_list as $val){
			foreach($p_date_list[$val] as $val2){
				foreach($p_staff_list[$val][$val2] as $val3){
					$chksheet->setCellValueByColumnAndRow(15, $j, $val);
					$chksheet->setCellValueByColumnAndRow(16, $j, date('Y/n/j',strtotime($val2)));
					$chksheet->setCellValueByColumnAndRow(17, $j, $val3);
					$chksheet->setCellValueByColumnAndRow(18, $j, $sumnum_list[$val][$val2][$val3]);
					//セルに色をつける
					$sheet->getStyle('P4:S'.$j)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("ffff00");
					//罫線をつける
					$chksheet->getStyle('P4:S'.$j)->getBorders()->getAllBorders()->setBorderStyle( PHPExcel_Style_Border::BORDER_THIN );
					++$j;
				}
			}
		}
		//フラグを更新
		$query = "UPDATE php_personal_info ";
		$query .= " SET outputflg = 9";
		$query .= " , response = '".$g_factory."'";
		$query .= " , slip_staff = '".$s_staff."'";
		$query .= " , outputdt = '".$today."'";
		$query .= " , delivery_person = 'ヤマト'";
		$query .= " , status = 9";
		$query .= " WHERE outputflg = 3 ";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		//ファイル出力
		$filename = date('ymd')."_予約伝票_".$g_factory."発送.xlsx";
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $filename . '"');
		header('Cache-Control: max-age=0');
		//ファイル破損を防ぐ
		ob_clean(); 
		//対象ファイル保存する
		$objWriter = PHPExcel_IOFactory::createWriter($book,'Excel2007');
		$objWriter->save('php://output',$filename);
		exit;
		setcookie ('downloaded', '', time()-3600);
		setcookie ('downloaded', "yes");
	}
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	
	
	$json_array_s_modelnum = json_encode($s_modelnum_list);
?>

<!--------------------------------------------------------------------------------------------------
	コンテンツ表示
---------------------------------------------------------------------------------------------------->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<meta charset="UTF-8">
<head>
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
		width:900px;
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
	 background-image:url("./img/satei.jpg");
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
		width: 950px;	/*メインコンテンツ幅*/
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
		padding-left: 40px;
		overflow: hidden;
	}
	/*段落タグの余白設定*/
	#main p {
		padding: 0.5em 10px 1em;	/*左から、上、左右、下への余白*/
	}

	/*コンテンツ（左右ブロックとフッターを囲むブロック）
	---------------------------------------------------------------------------*/
	#contents {
		clear: left;
		width: 100%;
		padding-top: 4px;
	}
	/* --- ヘッダーセル（th） --- */
	th.tbd_th_p1 {
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #2B8225; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	}
	th.tbd_th_p2 {
	width: 200px;
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #2B8225; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	}
	th.tbd_th_p2_h {
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #0C58A6; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	}
	th.tbd_th_p2_s {
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #ff6699; /* 見出しセルの背景色 */
	text-align: center;
	line-height: 130%;
	}
	th.tbd_th_p3_h {
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #007AC1; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	}
	th.tbd_th_p3_s {
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #FFB2CB; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	}
	th.tbd_th_p3_c {
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #FFB2CB; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	width: 100px;
	}

	/* --- データセル（td） --- */

	td.tbd_td_p1 {
	width: 200px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p2 {
	width: 100px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p3 {
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p3_err {
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p4 {
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	td.tbd_td_p4_r {
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	/* --- 仕切り線セル --- */
	td.tbd_line_p1 {
	width: 10px;
	background-color: #e0f1fc; /* 任意項目セルの背景色 */
	border: 1px solid #b6b6b6;
	}
	td.tbd_line_p2 {
	width: 2px;
	background-color: #e0f1fc; /* 任意項目セルの背景色 */
	border-bottom: 1px #c0c0c0 dotted; /* データセルの下境界線 */
	}
	select.sizechange{
	font-size:120%;
	}

	.tbd thead th {
	  /* 縦スクロール時に固定する */
	  position: -webkit-sticky;
	  position: sticky;
	  top: 0;
	  background-color:#00885a;
	  height: 3em;
	   /* tbody内のセルより手前に表示する */
	  z-index: 1;
	  color:white;			
	}
	input[type=checkbox] {
	  transform: scale(1.5);
	}
	</style>
	<script type="text/javascript">
		<!--
		function hpbmapinit() {
			hpbmaponload();
		}
		//-->
	</script>
	<?php $html->output_htmlheadinfo3($prgname); ?>
	<script type="text/javascript" src="//code.jquery.com/jquery-2.1.0.min.js"></script>
	<script type="text/javascript">
		//編集ボタン
		function Mclk_Stat(){
			//画面項目設定
			document.forms['frm'].action = './headoffice_perf_sql.php?kbn=headoffice_perf_all';
			document.forms['frm'].submit();
		}
	</script>
	<script type="text/javascript">
		//二重登録防止後伝票出力
		function MClickBtn(action,g_fac,horyuu_num) {
			if(window.confirm(g_fac+'出荷用の伝票を出力します。')){
				if(horyuu_num > 0){
					if(window.confirm('保留データが残っていますが、このまま出力していいですか？')){
					//	document.forms['frm'].elements[action+'_button'].disabled = true;
						document.forms['frm'].elements[action].click();
						return;
					}
				}else{
					document.forms['frm'].elements[action].click();
					return;
				}
			}else{
				return false;
			}
		}
		//伝票発行登録ボタン
		function Push_slipnumber(Cell){
 			var myTbl = document.getElementById('TBL');
			var Cells=myTbl.rows[Cell.parentNode.parentNode.rowIndex].cells[0];
			var rowINX = 'idxnum='+Cells.innerHTML;
				window.open('./pc_Initialfailure_senddone.php?' + rowINX,'_blank');
		}
		//行選択でチェック
		function CheckRow(row) {
			// チェックボックスはスルー
	        if (event.target.type !== 'checkbox') {
	            document.getElementById('box'+row).click();
	        }
		}
		var json_array_s_modelnum = JSON.parse('<? echo $json_array_s_modelnum; ?>');

		function ChangeColor(){
			var table = document.getElementById('TBL');
			var maxrow = document.getElementById('行数').value
			for(i=1; i<=maxrow; ++i){
				if(document.getElementById('box'+i).checked == true){
					table.rows[i].style.background = "pink";
				} else {
					if (document.getElementById('falseflg' + i).value == 1) {
						table.rows[i].style.background = "#ff0000";
					} else { 
						if (i % 2 == 0) {
							table.rows[i].style.background = "white";
						} else {
							table.rows[i].style.background = "#EDEDED";
						}
					}
				}
			}
		}
	</script>
	<style type="text/css">
		.btn-circle-border-simple {
		position: relative;
		display: inline-block;
		text-decoration: none;
		background: #b3e1ff;
		color: #668ad8;
		width: 300px;
		border-radius: 10%;
		border: solid 2px #668ad8;
		text-align: center;
		overflow: hidden;
		font-weight: bold;
		transition: .4s;
		box-shadow: 1px 1px 3px #666666;
		font-size: 30px;
		padding: 50px;
		margin: 20px 30px 20px 30px;
		}
		.btn-circle-border-simple:hover {
		background: #668ad8;
		color: white;
		text-decoration: none;
		}
		.btn-circle-border-simple2 {
		display: inline-block;
		text-decoration: none;
		background: #ffdab9;
		color: #ff8c00;
		width: 300px;
		border-radius: 10%;
		border: solid 2px #ff8c00;
		text-align: center;
		overflow: hidden;
		font-weight: bold;
		transition: .4s;
		box-shadow: 1px 1px 3px #666666;
		font-family: Courier New;
		font-size: 30px;
		padding: 50px;
		margin: 20px 30px 20px 30px;
		}
		.btn-circle-border-simple2:hover {
		background: #ff8c00;
		color: white;
		text-decoration: none;
		}
	</style>
</head>

<body>
<br>
<div id="container">
	<table class="base" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
		<!-- ヘッダー情報生成 -->
		<div id="header">
			<p><img src="images/logo_ecommerce_output.png" alt="" /></p>
		</div>
	</table>
	<div id="contents">
		<div id="main">
			<div id="formWrap">
			<!--
			<p style="text-align:right"><a href="./pdf/yamato_manual_ns.pdf" target="_blank">ヤマト連携方法マニュアル</a></p>
			-->
				<p style="color:red">
					伝票を発行された方は、必ずヤマトデータの取込をおこなってください。<a href="./pdf/yamato_manual_ns.pdf">（マニュアル）</a>　<a href="./yamato_upload.php">取込画面</a>
				</p>
				<h2>検索条件</h2><br>
				<form name="frm" method = "post" action="./ecommerce_slip_repair.php" >
					<input type="hidden" name="登録担当者_old" value="<? echo $p_staff ?>">
					<table class="tbd" align="center" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
						<tr>
							<th class="tbd_th"><strong>担当者</strong></th>
							<td class="tbd_req"><img src="./images/hisu.gif" alt="必須"></td>
							<td class="tbd_td">
								<?php
									$j = 5;
									foreach($staff_list as $key => $val) {
										if ($p_staff == $val) {
										 ?>
											<label for="radio<? echo $j ?>" class="radio"><input type="radio" name="登録担当者" value="<? echo $val ?>" checked='checked' id="radio<? echo $j ?>" checked='checked'><strong><? echo $val; if ($val == "") { echo "全て"; } ?></strong></label>
										<?
										}else{
										?>
											<label for="radio<? echo $j ?>" class="radio"><input type="radio" name="登録担当者" value="<? echo $val ?>" id="radio<? echo $j ?>"><strong><? echo $val; if ($val == "") { echo "全て"; } ?></strong></label>
										<?
										}
										$j++;
									}
								?>
							</td>
						</tr>
						<tr>
							<?
							if ($p_staff == "会場予約") { 
							?>
								<th class="tbd_th"><strong>型番</strong></th>
								<td class="tbd_req"></td>
								<td class="tbd_td">
									<label><input type="checkbox" id="checkAllNote" name="checkAllNote" <? if($g_n_modelnum_list == $n_modelnum_list){ ?>checked='checked'<? } ?>>ノートPC</label>
									<label><input type="checkbox" id="checkAllDsk" name="checkAllDsk" <? if($g_d_modelnum_list == $d_modelnum_list){ ?>checked='checked'<? } ?>>デスクトップ</label><br>
									<? foreach($n_modelnum_list as $key => $val){ ?>
										<label><input type="checkbox" name="型番ノート[]" value="<? echo $val ?>" <? if(in_array($val, $s_modelnum_list)){ ?>checked='checked'<? } ?>><? echo $val ?></label>
									<? } ?>
									<? foreach($d_modelnum_list as $key => $val){ ?>
										<label><input type="checkbox" name="型番デスク[]" value="<? echo $val ?>" <? if(in_array($val, $s_modelnum_list)){ ?>checked='checked'<? } ?>><? echo $val ?></label>
									<? } ?>
								</td>
								<script>
								</script>
							<? } else { ?>
								<th class="tbd_th"><strong>型番</strong></th>
								<td class="tbd_req"></td>
								<td class="tbd_td">
									<label><input type="checkbox" id="checkAllNote" name="checkAllNote" <? if($g_n_modelnum_list == $n_modelnum_list){ ?>checked='checked'<? } ?>><B>ノートPC</B></label>
									<? foreach($n_modelnum_list as $key => $val){ ?>
										<br>　<label><input type="checkbox" name="型番ノート[]" value="<? echo $val ?>" <? if(in_array($val, $s_modelnum_list)){ ?>checked='checked'<? } ?>> <? echo $val ?></label>
									<? } ?>
									<br>
									<label><input type="checkbox" id="checkAllDsk" name="checkAllDsk" <? if($g_d_modelnum_list == $d_modelnum_list){ ?>checked='checked'<? } ?>><B>デスクトップ</B></label><br>
									　
									<? foreach($d_modelnum_list as $key => $val){ ?>
										<label><input type="checkbox" name="型番デスク[]" value="<? echo $val ?>" <? if(in_array($val, $s_modelnum_list)){ ?>checked='checked'<? } ?>> <? echo $val ?></label>
									<? } ?>
									<br>
									<label><input type="checkbox" id="checkAllOp" name="checkAllOp" <? if($g_o_modelnum_list == $o_modelnum_list){ ?>checked='checked'<? } ?>><B>周辺機器</B></label><br>
									<? foreach($o_modelnum_list as $key => $val){ ?>
									<label><input type="checkbox" name="型番周辺機器[]" value="<? echo $val ?>" <? if(in_array($val, $s_modelnum_list)){ ?>checked='checked'<? } ?>> <? echo $val ?></label>
									<? } ?>
								</td>		
							<? } ?>
						</tr>
						<tr>
							<th class="tbd_th"><strong>着日</strong></th>
							<td class="tbd_req"></td>
							<td class="tbd_td">
								<label for="radio01" class="radio"><input type="radio" name="designated_day" value="全て表示" id="radio01" <? if($designated_day == "全て表示") { ?>checked='checked'<? } ?>><strong>全て表示</strong></label>
								<label for="radio02" class="radio"><input type="radio" name="designated_day" value="<? echo $p_maxday ?>日以内" id="radio02" <? if($designated_day == $p_maxday."日以内") { ?>checked='checked'<? } ?>><strong><? echo $p_maxday ?>日以内</strong></label>
							</td>
						</tr>
						<?php
						if ($p_slip_flg == 1) {
						?>
							<th class="tbd_th"><strong>ステータス</strong></th>
								<td class="tbd_req"></td>
								<td class="tbd_td">
									<label for="radio03" class="radio"><input type="radio" name="slip_status" value="全て表示" id="radio03" <? if($slip_status == "全て表示") { ?>checked="checked"<? } ?>><strong>全て表示</strong></label>
									<label for="radio04" class="radio"><input type="radio" name="slip_status" value="未発行分のみ表示" id="radio04" <? if($slip_status == "未発行分のみ表示") { ?>checked="checked"<? } ?>><strong>未発行分のみ表示</strong></label>
								</td>
							</tr>
							<th class="tbd_th"><strong>受付日</strong></th>
								<td class="tbd_req"></td>
								<td class="tbd_td">
								<input type="date" name="order_date1" value="<?php if ($order_date1 != "") { echo substr($order_date1, 0,10); } ?>">～<input type="date" name="order_date2" value="<?php if ($order_date2 != "") { echo substr($order_date2, 0,10); } ?>">
								</td>
							</tr>
							<tr>
								<th class="tbd_th"><strong>絞り込み</strong></th>
								<td class="tbd_req"></td>
								<td class="tbd_td">
									<select name="kensaku_pldn">
										<option value="kensaku_idxnum" <?php if ($kensaku_pldn == "kensaku_idxnum"){echo "selected";} ?>>受付NO.</option>
										<option value="kensaku_name" <?php if ($kensaku_pldn == "kensaku_name"){echo "selected";} ?>>名前</option>
										<option value="kensaku_tel" <?php if ($kensaku_pldn == "kensaku_tel"){echo "selected";} ?>>電話番号</option>
									</select>
									<input name="kensaku" style="height:25px; width:240px;" value=<?php echo $_POST['kensaku'] ?>>
								</td>
							</tr>
						<?php
						}
						?>
					</table>
					<table class="tbf3" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
						<tr>
							<td class="tbf3_td_p1_c"><input type="submit" name="search" style="width:100px; height:30px; font-size:12px;" value="検索"></td>
						</tr>
					</table>
					<?
					//保留数量取得
					$_select = " SELECT modelnum, SUM(buynum) as sumnum ";
					$_select .= " FROM php_telorder__ A ";
					$_select .= " WHERE A.delflg = 0 ";
					$_select .= " AND A.status = 2 ";
					if($p_staff <> ""){
						if($p_staff == "新規顧客ネット通販"){
							$_select .= " AND A.locale = 'ネット通販' ";
							$_select .= " AND (A.reception_telnum = '新規' OR A.reception_telnum = '') ";
						}else if($p_staff == "既存顧客ネット通販"){
							$_select .= " AND A.locale = 'ネット通販' ";
							$_select .= " AND (A.reception_telnum = '既存') ";
						}else if($p_staff == "代理店経由ネット通販"){
							$_select .= " AND A.locale = 'ネット通販' ";
							$_select .= " AND (A.reception_telnum = '代理店') ";
						}else{
							$_select .= " AND A.locale = '".$p_staff."' ";
						}
					}
					$_select .= " GROUP BY modelnum ";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($_select, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($_select))) {
						$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					if ($rs->num_rows > 0) { ?>
						<h2>保留中台数</h2><br>
						<a href="./nsorder_list_all2.php?horyuflg=1" target="_blank">リスト表示</a><br>
						<table class="tbh" width="600" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
							<tr style="background:#ccccff">
								<th class="tbd_th_p1"><strong>型番</strong></th>
								<th class="tbd_th_p2"><strong>台数</strong></th>
							</tr>
							<?
							$t = 0;
							$g_horyuu_num = 0;
							while ($row = $rs->fetch_array()) {
								$g_horyuu_num += $row['buynum']; ?>
								<tr <?if($t % 2 == 0){echo ' style="background-color:#EDEDED;"';} ?>>
									<td class="tbd_td_p1"><? echo $row['modelnum']; ?></td>
									<td class="tbd_td_p4_r"><? echo $row['sumnum']; ?>台</td>
								</tr>
							<? } ?>
						</table><br>
					<? }
					if ($p_staff != "会場予約") { ?>
						<h2>選択中台数</h2><br>
						<table class="tbh" width="600" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
							<tr style="background:#ccccff">
								<th class="tbd_th_p1"><strong>型番</strong></th>
								<th class="tbd_th_p2"><strong>台数</strong></th>
							</tr>
							<? $t=0;
								foreach($n_modelnum_list as $key => $val){
									if(in_array($val, $s_modelnum_list)){ ?>
										<tr <?if($t % 2 == 0){echo ' style="background-color:#EDEDED;"';} ?>>
											<td class="tbd_td_p1"><? echo $val ?></td>
											<td class="tbd_td_p4_r"><input type="text" value="0" id="cntnum<? echo $val ?>" name="cntnum<? echo $val ?>" style="border:none; background:transparent; font-size:14px;" size="3">台</td>
										</tr>
										<? ++$t;
									}
								} ?>
								<? foreach($d_modelnum_list as $key => $val){
									if(in_array($val, $s_modelnum_list)){ ?>
										<tr <?if($t % 2 == 0){echo ' style="background-color:#EDEDED;"';} ?>>
											<td class="tbd_td_p1"><? echo $val ?></td>
											<td class="tbd_td_p4_r"><input type="text" value="0" id="cntnum<? echo $val ?>" name="cntnum<? echo $val ?>" style="border:none; background:transparent; font-size:14px;" size="3">台</td>
										</tr>
									<?	++$t;
									}
								}
								foreach($o_modelnum_list as $key => $val){
									if(in_array($val, $s_modelnum_list)){ ?>
										<tr <?if($t % 2 == 0){echo ' style="background-color:#EDEDED;"';} ?>>
											<td class="tbd_td_p1"><? echo $val ?></td>
											<td class="tbd_td_p4_r"><input type="text" value="0" id="cntnum<? echo $val ?>" name="cntnum<? echo $val ?>" style="border:none; background:transparent; font-size:14px;" size="3">個</td>
										</tr>
										<?
										++$t;
									}
								}
							}
							?>
					</table>
					<h2>受注詳細</h2><br>
					<a href="javascript:MClickBtn('output_btn_honbu','本部','<? echo $g_horyuu_num ?>')" class="btn-circle-border-simple">本部伝票出力</a>
					<input type="submit" name="output_btn_honbu" value="伝票データ出力" style="display:none;">
					<a href="javascript:MClickBtn('output_btn_hosyu','補修センター','<? echo $g_horyuu_num ?>')" class="btn-circle-border-simple2">補修センター伝票出力</a>
					<input type="submit" name="output_btn_hosyu" value="伝票データ出力" style="display:none;">
					<p style="text-align:right">※背景が赤くなっているデータは、存在しない型番のため伝票の出力ができません。php_ecommerce_pc_infoテーブルにデータを追加した上で、出力してください。<br>（もしくは、システム担当者までお問い合わせください）</p>
					<table class="tbh" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
							<tr><td class="category"><strong>■◇■販売データ■◇■</strong></td></tr>
					</table>
					<table class="tbd" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル" id= "TBL">
						<thead>
							<tr>
								<?
								if ($p_staff == "会場予約") {
								?>
									<th class="tbd_th_c" ><label>全選択<br><input type="checkbox" class="list" id="checkAll" name="checkAll" onchange="Javascript:ChangeColor()"></label></th>
									<th >受付NO.</th>
									<th>受付日</th>
									<th >名前</th>
									<th>型番</th>
									<th>予約方法</th>
									<th>オプション</th>
									<th>金額</th>
									<th>購入台数</th>
									<th>会場名<br>担当者</th>
									<th title="全文">備考※</th>
								<? } else { ?>
									<th><label><input type="checkbox" class="list" id="checkAll" name="checkAll" onchange="Javascript:CntRow();"></label></th>
									<th>受付NO.</th>
									<th>受付日</th>
									<th>名前</th>
									<th>型番</th>
									<th>オプション</th>
									<th>金額</th>
									<th>購入台数</th>
									<th>購入方法</th>
									<th>支払方法</th>
									<th>着日指定</th>
									<th title="全文">備考※</th>
								<? } ?>
							</tr>
						</thead>
						<!-- 個別表示 -->
						<?php
						$comm->ouputlog("☆★☆処理開始☆★☆ ", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog("　電話番号＝" . $p_phoneno, $prgid, SYS_LOG_TYPE_INFO);
						//データ存在フラグ
						$dateflg1 = 0;
						$dateflg2 = 0;
						//データ存在フラグ
						$custodydt = "";
						// ================================================
						// ■　□　■　□　個別表示　■　□　■　□
						// ================================================

						// ========================================================================================
						
						//----- データ抽出 会場予約
						
						// ========================================================================================
						if ($p_staff == "会場予約") {
							$query = "
							SELECT 
								A.idxnum, A.name, A.modelnum, A.buynum, A.cash, A.option1, A.remarks, A.reserv 
								,B.buydt, B.staff, B.locale, D.modelnum as i_modelnum 
							FROM 
								php_personal_info A 
							LEFT OUTER JOIN 
								php_performance B 
							ON 
								concat(DATE_FORMAT(B.buydt,'%Y%m%d'),LPAD(B.lane,2,'0'),'-',B.branch) = A.venueid 
							LEFT OUTER JOIN 
								php_staff C 
							ON 
								B.staff = C.staff 
							LEFT OUTER JOIN 
								(
									SELECT DISTINCT 
										modelnum 
									FROM 
										php_pc_info 
									WHERE 
										delflg = 0
								) D 
							ON 
								A.modelnum = D.modelnum 
							WHERE 
								A.delflg = 0 
								AND A.reservflg = 1 
								AND A.outputflg = 0 
								AND A.cancelflg = 0 
								AND A.modelnum != '' 
								AND (
									(C.companycd='F' AND check_staff <> '') 
									OR 
									(C.companycd <> 'F')
								) ";
							/*
							if($s_staff <> ""){
								$query .= " AND B.staff = '".$s_staff."' ";
							}
							if($s_order == 1){
								$query .= " AND A.deliveryday = '' ";
							}else if($s_order == 2){
								$query .= " AND A.deliveryday <> '' ";
							}
							if($s_reserv <> ""){
								$query .= " AND A.reserv = '".$s_reserv."' ";
							}
							*/
							if(count($s_modelnum_list) > 0) {
								$i=0;
								$query .= " AND A.modelnum IN (";
								foreach($s_modelnum_list as $key => $val){
									if($i > 0){
										$query .= ", ";
									}
									$query .= "'".$val."'";
									++$i;
								}
								$query .= " )";
							}
							$query .= "
							ORDER BY 
								B.buydt ASC, A.remarks ,A.modelnum  ,A.cash  ,A.deliveryday  ,A.option1  ,A.option2  ,A.option3  ,A.option4 ,A.name ";
							$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
							if (! $rs = $db->query($query)) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
							$i=0;
						while ($row = $rs->fetch_array()) {
							++$i;
							//明細設定
							if($row['i_modelnum'] == ""){ ?>
								<tr style="background-color:#ff0000;" onclick="Javascript:CheckRow(<? echo $i ?>)">
							<? }else if (($rowcnt % 2) == 0) { ?>
								<tr onclick="Javascript:CheckRow(<? echo $i ?>)">
							<? } else { ?>
								<tr style="background-color:#EDEDED;" onclick="Javascript:CheckRow(<? echo $i ?>)">
							<? }
							$rowcnt = $rowcnt +1; ?>
								<td style="display:none;"><?php echo $t_idx?></td>
								<!-- 印刷用チェックボックス -->
								<td style="text-align:center; vertical-align:middle;padding:0">
									<input id="box<? echo $i ?>" type="checkbox" value="<? echo $row['idxnum'] ?>" <? if($row['i_modelnum'] == ""){echo 'disabled="disabled"'; }else{echo 'name="outputno[]" ';} ?> readonly="readonly" onchange="Javascript:ChangeColor()">
									<input type="text" id="falseflg<? echo $i ?>" <? if($row['i_modelnum'] == ""){echo 'value="1"'; }else{echo 'value="0"';} ?>  style="display:none">
								</td>
								<!-- インデックス -->
								<td width="70" style="text-align:center; vertical-align:middle;"><?php echo str_pad($row['idxnum'], 6, "0", STR_PAD_LEFT) ?></td>
								<!-- 受付日時 -->
								<td style="text-align:center; vertical-align:middle;"><?php echo date('Y/n/j', strtotime($row['buydt'])); ?>
								</td>
								<!-- お名前 -->
								<td style="vertical-align:middle;">
									<?php echo $row['name'] ?>
								</td>
								<!-- 型番 -->
								<td style="text-align:left; vertical-align:middle;"><?php echo $row['modelnum']; ?></td>
								<!-- 予約 -->
								<td style="text-align:center; vertical-align:middle;"><?php echo $row['reserv']; ?></td>
								<!-- オプション -->
								<td style="text-align:center; vertical-align:middle;"><?php echo $option_han; ?></td>
								<!-- 金額 -->
								<td style="text-align:right; vertical-align:middle;"><?php echo number_format($row['cash']*100)."円"; ?></td>
								<!-- 台数 -->
								<td style="text-align:right; vertical-align:middle;"><?php echo $row['buynum']."台"; ?></td>
								<!-- 会場名 -->
								<td style="text-align:center; vertical-align:middle;">
									<?php echo $row['staff']."　".$row['locale']; ?><br>
								</td>
								<!-- 備考 -->
								<? $remarks = $row['remarks'];
								if($row['i_modelnum'] == ""){ 
									$remarks = "存在しない型番のため、伝票発行不可　".$remarks;
								} ?>
								<td style="text-align:left; vertical-align:middle;" title="<? echo $remarks ?>"><?php echo mb_substr($remarks,0,10); ?><? if(mb_strlen($remarks) > 9){echo "・・・";} ?></td>
							</tr>
							<?
							}
							?>
							<script>
								//チェックボックス全選択
								$(function(){
									var checkAll = '#checkAll'; //「すべて」のチェックボックスのidを指定
									var checkBox = 'input[name="outputno[]"]'; //チェックボックスのnameを指定
									$( checkAll ).on('click', function() {
									$( checkBox ).prop('checked', $(this).is(':checked') );
									});
									$( checkBox ).on( 'click', function() {
									var boxCount = $( checkBox ).length; //全チェックボックスの数を取得
									var checked = $( checkBox + ':checked' ).length; //チェックされているチェックボックスの数を取得
									if( checked === boxCount ) {
									$( checkAll ).prop( 'checked', true );
									} else {
									$( checkAll ).prop( 'checked', false );
									}
									});
								});
								//デスクトップPCチェックボックス全選択
								$(function(){
									var checkAll = '#checkAllDsk'; //「すべて」のチェックボックスのidを指定
									var checkBox = 'input[name="型番デスク[]"]'; //チェックボックスのnameを指定
									$( checkAll ).on('click', function() {
									$( checkBox ).prop('checked', $(this).is(':checked') );
									});
									$( checkBox ).on( 'click', function() {
									var boxCount = $( checkBox ).length; //全チェックボックスの数を取得
									var checked = $( checkBox + ':checked' ).length; //チェックされているチェックボックスの数を取得
									if( checked === boxCount ) {
									$( checkAll ).prop( 'checked', true );
									} else {
									$( checkAll ).prop( 'checked', false );
									}
									});
								});
								//ノートPCチェックボックス全選択
								$(function(){
									var checkAll = '#checkAllNote'; //「すべて」のチェックボックスのidを指定
									var checkBox = 'input[name="型番ノート[]"]'; //チェックボックスのnameを指定
									$( checkAll ).on('click', function() {
									$( checkBox ).prop('checked', $(this).is(':checked') );
									});
									$( checkBox ).on( 'click', function() {
									var boxCount = $( checkBox ).length; //全チェックボックスの数を取得
									var checked = $( checkBox + ':checked' ).length; //チェックされているチェックボックスの数を取得
									if( checked === boxCount ) {
									$( checkAll ).prop( 'checked', true );
									} else {
									$( checkAll ).prop( 'checked', false );
									}
									});
								});
							</script>
						<?
						} else {
							// ========================================================================================
						
							//----- データ抽出 電話・ネット注文
						
							// ========================================================================================
							$query .= " SELECT A.t_idx , COUNT(*) as cnt, A.idxnum , A.updcount , A.insdt , A.receptionist , A.upddt , A.receptionday , A.status , A.name , A.phonenum1 , A.response, IFNULL(B.modelnum, A.category) as modelnum, A.cash, A.buynum, A.remark, A.option_han, A.sales_name, A.designated_day, A.output_flg, A.p_method, A.p_way";
							$query .= " ,CASE WHEN B.modelnum IS NULL THEN 1 ELSE 0 END AS flg ";
							$query .= " FROM php_telorder__ A ";
							$query .= " LEFT OUTER JOIN php_ecommerce_pc_info B ON A.category=B.category AND A.sales_name=B.sales_name ";
							$query .= " WHERE A.delflg = 0 ";
							if ($slip_status == '未発行分のみ表示') {
								$query .= " AND A.output_flg = 0 AND A.status = 1 ";
							}
							if ($order_date1 != "") {
								$query .= " AND A.receptionday >= " . sprintf("'%s'", $order_date1);
							}
							if ($order_date2 != "") {
								$query .= " AND A.receptionday <= " . sprintf("'%s'", $order_date2);
							}
							if($p_staff <> ""){
								if($p_staff == "新規顧客ネット通販"){
									$query .= " AND A.locale = 'ネット通販' ";
									$query .= " AND (A.reception_telnum = '新規' OR A.reception_telnum = '') ";
								}else if($p_staff == "既存顧客ネット通販"){
									$query .= " AND A.locale = 'ネット通販' ";
									$query .= " AND (A.reception_telnum = '既存') ";
								}else if($p_staff == "代理店経由ネット通販"){
									$query .= " AND A.locale = 'ネット通販' ";
									$query .= " AND (A.reception_telnum = '代理店') ";
								}else{
									$query .= " AND A.locale = '".$p_staff."' ";
								}
							}
							if(count($s_modelnum_list)>0){
								$i=0;
								$query .= " AND ((B.modelnum IN (";
								foreach($s_modelnum_list as $key => $val){
									if($i > 0){
										$query .= ", ";
									}
									$query .= "'".$val."'";
									++$i;
								}
								$query .= " ))";
								$query .= " OR B.modelnum IS NULL )";
							}
							if($kensaku_pldn == 'kensaku_idxnum' ){
								$query.= " AND A.idxnum LIKE '%$kensaku%'";
							} elseif($kensaku_pldn == 'kensaku_name' ){
								$query.= " AND A.name LIKE '%$kensaku%'";
							} elseif($kensaku_pldn == 'kensaku_tel' ){
								$query.= " AND (A.phonenum1 LIKE '%$kensaku%' OR A.phonenum2 LIKE '%$kensaku%')";
							}
							$query .= " GROUP BY A.t_idx";
							$query .= " ORDER BY A.idxnum DESC";
							$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
							if (! $rs = $db->query($query)) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
							$i = 0;
							$cash = 0;
							$flg = 0;
							$category = "";
							$remark = "";
							$buynum = 0;
							$idxnum = "";
							$option_han = "";
							$p_option_han = "";
							$insdt = "";
							$name = "";
							$receptionday = "";
							$receptionist = "";
							$upddt = "";
							$status = "";
							$phonenum1 = "";
							$p_method = "";
							$p_way = "";
							$g_category = [];
							$g_option_han = [];
							while ($row = $rs->fetch_array()) {
								$cash = 0;
								$category = "";
								$remark = "";
								$buynum = 0;
								$idxnum = "";
								$option_han = "";
								$p_option_han = "";
								$insdt = "";
								$name = "";
								$receptionday = "";
								$receptionist = "";
								$upddt = "";
								$status = "";
								$phonenum1 = "";
								$g_category = [];
								$g_option_han = [];
								$cnt_row = $row['cnt'];
								if($cnt_row > 1){
									$query2 = "SELECT A.idxnum, A.cash, A.category, IFNULL(B.modelnum, A.category) as modelnum, A.postcd1, A.postcd2, A.address1, A.address2, A.address3, A.name, A.phonenum1, A.option_han, A.designated_day, A.specified_times, A.buynum, A.remark, A.sales_name, A.designated_day";
									$query2 .= " FROM php_telorder__ A";
									$query2 .= " LEFT OUTER JOIN php_ecommerce_pc_info B ON A.category=B.category AND A.sales_name=B.sales_name ";
									$query2 .= " WHERE A.delflg = '".$row['delflg']."'";
									$query2 .= " AND A.output_flg = '".$row['output_flg']."'";
									$query2 .= " AND A.status = '".$row['status']."'";
									$query2 .= " AND A.t_idx = '".$row['t_idx']."'";
									$query2 .= " ORDER BY A.idxnum DESC ";
									$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
									$comm->ouputlog($query2, $prgid, SYS_LOG_TYPE_DBUG);
									if (!($rs2 = $db->query($query2))) {
										$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
									}
									while($row2 = $rs2->fetch_array()) {
										$cash += $row2['cash'];
										$buynum += $row2['buynum'];
										$g_category[] = $row2['modelnum'];
										if($row2['option_han'] <> "なし"){
											if($p_option_han ==""){
												$p_option_han = $row2['option_han'];
											}else{
												$p_option_han .= "・".$row2['option_han'];
											}
										}
										$idxnum = $row2['idxnum'];
									}
									foreach(array_count_values($g_category) as $key => $val){
										$category .= $key." × ".$val." 台 <br>";
									}
									if($p_option_han == ""){
										$option_han = "なし";
									}else{
										$g_option_han = explode("・", $p_option_han);
										foreach(array_count_values($g_option_han) as $key => $val){
											$option_han .= $key." × ".$val." 個 <br>";
										}
									}
								}else{
									$cash = $row['cash'];
									$category = $row['modelnum'];
									$buynum = $row['buynum'];
									$idxnum = $row['idxnum'];
									$option_han = $row['option_han'];
								}
								$flg = $row['flg'];
								$remark = $row['remark'];
								$insdt = $row['insdt'];
								$name = $row['name'];
								$receptionday = $row['receptionday'];
								$receptionist = $row['receptionist'];
								$designated_day_s = $row['designated_day'];
								$upddt = $row['upddt'];
								$status = $row['status'];
								$phonenum1 = $row['phonenum1'];
								$p_way = $row['p_way'];
								$p_method = $row['p_method'];
								if ($row['designated_day'] == '0000-00-00') {
									$designated_day_h = '';	
								} else {
									$designated_day_h = $row['designated_day'];
								}
								if ( $designated_day_s <= $maxday || $remark != "" ) {
									$i++;
									//明細設定
									if ($row['flg'] > 0) { ?>
										<tr style="background-color:#ff0000;">
									<? }else if (($i % 2) == 0) { ?>
										<tr style="<?php if($status == 9) { echo 'background-color:#ffff00'; } ?>" onclick="Javascript:CheckRow(<? echo $i ?>,<? echo $status ?>)">
									<? } else { ?>
										<tr style="<?php if($status == 9) { echo 'background-color:#ffff00'; } else { echo 'background-color:#EDEDED;'; } ?>" onclick="Javascript:CheckRow(<? echo $i ?>,<? echo $status ?>)">
									<? }?>
										<td style="display:none;"><?php echo $t_idx?></td>
										<!-- 印刷用チェックボックス -->
										<? if($row['flg'] == 0){ ?>
											<td style="text-align:center; vertical-align:middle;padding:0">
												<input id="box<? echo $i ?>" type="checkbox" name="outputno[]" value="<? echo $row['t_idx'] ?>" readonly="readonly" onchange="Javascript:CntRow()">
												<input type="text" id="台数<? echo $i ?>" value="<? echo $cnt_row ?>"  style="display:none">
												<input type="text" id="modelnum<? echo $i ?>" value="<? echo $category ?>"  style="display:none">
												<input type="text" id="status<? echo $i ?>" value="<? echo $status ?>"  style="display:none">
												<input type="text" id="flg<? echo $i ?>" value="<? echo $row['flg'] ?>"  style="display:none">
												<? if($cnt_row > 1){ ?>
													<? $t = 1;
													foreach($g_category as $val){ ?>
														<input type="text" id="modelnum<? echo $i ?>_<? echo $t; ?>" value="<? echo $val ?>"  style="display:none">
														<? ++$t;
													} ?>
												<? } ?>
											</td>
										<? } else{ ?>
											<td style="text-align:center; vertical-align:middle;padding:0">
												<input id="box<? echo $i ?>" type="checkbox" name="outputno[]" value="<? echo $row['t_idx'] ?>" style="display:none;">
												<input type="text" id="台数<? echo $i ?>" value="<? echo $cnt_row ?>"  style="display:none">
												<input type="text" id="modelnum<? echo $i ?>" value="<? echo $category ?>"  style="display:none">
												<input type="text" id="status<? echo $i ?>" value="<? echo $status ?>"  style="display:none">
												<input type="text" id="flg<? echo $i ?>" value="<? echo $row['flg'] ?>"  style="display:none">
												<? if($cnt_row > 1){ ?>
													<? $t = 1;
													foreach($g_category as $val){ ?>
														<input type="text" id="modelnum<? echo $i ?>_<? echo $t; ?>" value="<? echo $val ?>"  style="display:none">
														<? ++$t;
													} ?>
												<? } ?>
											</td>
										<? } ?>
										<!-- インデックス -->
										<td width="70" style="text-align:center; vertical-align:middle;"><?php echo str_pad($row['t_idx'], 6, "0", STR_PAD_LEFT) ?></td>
										<!-- 受付日時 -->
										<td style="text-align:center; vertical-align:middle;"><?php echo date('Y/n/j', strtotime($row['receptionday'])); ?><br>
										<? echo date('H:i:s',strtotime($row['insdt'])) ?>
										</td>
										<!-- お名前 -->
										<td style="vertical-align:middle;">
											<?php echo $name ?>
										</td>
										<!-- 型番 -->
										<td style="text-align:center; vertical-align:middle;"><?php echo $category; ?></td>
										<!-- オプション -->
										<td style="text-align:center; vertical-align:middle;"><?php echo $option_han; ?></td>
										<!-- 金額 -->
										<td style="text-align:center; vertical-align:middle;"><?php echo number_format($cash)."円"; ?></td>
										<!-- 台数 -->
										<td style="text-align:center; vertical-align:middle;"><?php echo $buynum."台"; ?></td>
										<!-- 販売方法 -->
										<td style="text-align:center; vertical-align:middle;"><?php echo $sales_name[$row['sales_name']]; ?></td>
										<!-- 支払方法 -->
										<td style="text-align:center; vertical-align:middle;">
											<?php
											echo $p_method;
											if ($p_method == "") {
												if ($p_way == 2) {
													echo "代金引換";
												}
											}
											?>
										</td>
										<!-- 着日指定 -->
										<td style="text-align:center; vertical-align:middle;"><?php echo $designated_day_h; ?></td>
										<!-- 備考 -->
										<td style="text-align:left; vertical-align:middle;" title="<? echo $remark ?>"><?php echo mb_substr($remark,0,10); ?><? if(mb_strlen($remark) > 9){echo "・・・";} ?></td>
									</tr>
									<? 
								}
							}
							?>
							<script>
								//チェックボックス全選択
								$(function(){
									var checkAll = '#checkAll'; //「すべて」のチェックボックスのidを指定
									var checkBox = 'input[name="outputno[]"]'; //チェックボックスのnameを指定
									$( checkAll ).on('click', function() {
									$( checkBox ).prop('checked', $(this).is(':checked') );
									});
									$( checkBox ).on( 'click', function() {
									var boxCount = $( checkBox ).length; //全チェックボックスの数を取得
									var checked = $( checkBox + ':checked' ).length; //チェックされているチェックボックスの数を取得
									if( checked === boxCount ) {
									$( checkAll ).prop( 'checked', true );
									} else {
									$( checkAll ).prop( 'checked', false );
									}
									});
									CntRow();
								});
								//デスクトップPCチェックボックス全選択
								$(function(){
									var checkAll = '#checkAllDsk'; //「すべて」のチェックボックスのidを指定
									var checkBox = 'input[name="型番デスク[]"]'; //チェックボックスのnameを指定
									$( checkAll ).on('click', function() {
									$( checkBox ).prop('checked', $(this).is(':checked') );
									});
									$( checkBox ).on( 'click', function() {
									var boxCount = $( checkBox ).length; //全チェックボックスの数を取得
									var checked = $( checkBox + ':checked' ).length; //チェックされているチェックボックスの数を取得
									if( checked === boxCount ) {
									$( checkAll ).prop( 'checked', true );
									} else {
									$( checkAll ).prop( 'checked', false );
									}
									});
								});
								//ノートPCチェックボックス全選択
								$(function(){
									var checkAll = '#checkAllNote'; //「すべて」のチェックボックスのidを指定
									var checkBox = 'input[name="型番ノート[]"]'; //チェックボックスのnameを指定
									$( checkAll ).on('click', function() {
									$( checkBox ).prop('checked', $(this).is(':checked') );
									});
									$( checkBox ).on( 'click', function() {
									var boxCount = $( checkBox ).length; //全チェックボックスの数を取得
									var checked = $( checkBox + ':checked' ).length; //チェックされているチェックボックスの数を取得
									if( checked === boxCount ) {
									$( checkAll ).prop( 'checked', true );
									} else {
									$( checkAll ).prop( 'checked', false );
									}
									});
								});
								//周辺機器チェックボックス全選択
								$(function(){
									var checkAll = '#checkAllOp'; //「すべて」のチェックボックスのidを指定
									var checkBox = 'input[name="型番周辺機器[]"]'; //チェックボックスのnameを指定
									$( checkAll ).on('click', function() {
									$( checkBox ).prop('checked', $(this).is(':checked') );
									});
									$( checkBox ).on( 'click', function() {
									var boxCount = $( checkBox ).length; //全チェックボックスの数を取得
									var checked = $( checkBox + ':checked' ).length; //チェックされているチェックボックスの数を取得
									if( checked === boxCount ) {
									$( checkAll ).prop( 'checked', true );
									} else {
									$( checkAll ).prop( 'checked', false );
									}
									});
								});
								function CntRow(){
									sumnum = {};
									for(var key in json_array_s_modelnum){
										var p_key = json_array_s_modelnum[key];
										sumnum[p_key] = 0;
									}
									var table = document.getElementById('TBL');
									var maxrow = document.getElementById('行数').value
									console.log("カウント開始");
									for(i=1; i<=maxrow; ++i){
										if(document.getElementById('box'+i).checked == true){
											if(document.getElementById('台数'+i).value > 1){
												for(t=1; t<=document.getElementById('台数'+i).value; ++t){
													sumnum[document.getElementById('modelnum'+i+'_'+t).value] = sumnum[document.getElementById('modelnum'+i+'_'+t).value] + 1;
													console.log('modelnum'+i+'_'+t+"："+document.getElementById('modelnum'+i+'_'+t).value);
												}
											}else{
												sumnum[document.getElementById('modelnum'+i).value] = sumnum[document.getElementById('modelnum'+i).value] + 1;
												console.log('modelnum'+i+"："+document.getElementById('modelnum'+i).value);
											}
											table.rows[i].style.background = "pink";
										} else {
											if(document.getElementById('flg'+i).value > 0){
												table.rows[i].style.background = "#ff0000";
											}else if (document.getElementById('status' + i).value == 9) {
												table.rows[i].style.background = "#ffff00";
											} else { 
												if (i % 2 == 0) {
													table.rows[i].style.background = "white";
												} else {
													table.rows[i].style.background = "#EDEDED";
												}
											}
										}
									}
									console.log("リスト表示");
									for(var key in json_array_s_modelnum){
										var p_key = json_array_s_modelnum[key];
										document.getElementById("cntnum"+p_key).value = sumnum[p_key];
									}
								}
							</script>
						<?
						}
						?>
						</table>
						<input type="text" name="行数"  id="行数" value="<? echo $i ?>" style="display:none">
					<table class="tbf3" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
						<td class="tbf3_td_p2_c"><a href="#" onClick="window.close(); return false;"><input type="button" value="閉じる"></a></td>
					</table>
				</form>
			</div>
		</div>
	</div>
</div>
</body>

<!-- データベース切断 -->
<?php if ($result) { $dba->mysql_discon($db); } ?>

</html>
