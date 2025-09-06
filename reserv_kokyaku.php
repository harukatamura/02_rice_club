<?php
//==================================================================================================
// ■機能概要
//   ・会場予約登録
//==================================================================================================

	//----------------------------------------------------------------------------------------------
	// 初期処理
	//----------------------------------------------------------------------------------------------
	//ログイン確認(COOKIEを利用)
	if ((!$_COOKIE['j_office_Uid']) or (!$_COOKIE['j_office_Pwd'])) {
			//Urlへ送信
			header("Location: ../../idx.php"); //これを使うときは、これ以前にブラウザの書き込みは全てしないこと！
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
	//タイムゾーン
	date_default_timezone_set('Asia/Tokyo');

	//オブジェクト生成
	$html = new html();
	$comm = new comm();
	$dba = new dbaccess();
	
	//セッションの開始
	session_start();

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);

	//本日日付
	$today = date('Y/m/d');

	//入力者取得
	$p_staff = $_COOKIE['con_perf_staff'];
	$p_Auth = $_COOKIE['con_perf_Auth'];
	$p_compcd = $_COOKIE['con_perf_compcd'];
	//リーダーフラグ
	$p_leaderflg = $_COOKIE['con_perf_leaderflg'];
	//NSフラグ
	$p_ns = $_COOKIE['con_perf_ns'];

	//GETデータ
	$g_idxnum = $_GET['idx'];
	$g_localeid = $_GET['localeid'];
	$change = $_GET['change'];
	$comm->ouputlog("get(idxnum)=" . $g_idxnum, $prgid, SYS_LOG_TYPE_DBUG);

	if($_GET['flg'] == 1){
		$alert = '<p><font color="red"><strong>登録が完了しました。</strong></font></p>';
	}
	//----------------------------------------------------------------------------------------------
	// システムデータの取得
	//----------------------------------------------------------------------------------------------
	//販売担当者一覧の取得
	$comm->ouputlog("販売担当者一覧の取得", $prgid, SYS_LOG_TYPE_INFO);
	if (!$rs = $comm->getstaff($db, 12)) {
		$comm->ouputlog("データ取得エラー", $prgid, SYS_LOG_TYPE_DBUG);
	}
	$comm->ouputlog("都道府県一覧の取得" , $prgid, SYS_LOG_TYPE_DBUG);
	if (!$rs = $comm->getcode($db, "address1")) {
		$comm->ouputlog("データ取得エラー", $prgid, SYS_LOG_TYPE_DBUG);
	}
	while ($row = $rs->fetch_array()) {
		$p_address1list[] = $row;
		$comm->ouputlog("都道府県=". $row[0], $prgid, SYS_LOG_TYPE_DBUG);
	}
	if(isset($g_idxnum) && !isset($g_localeid)){
		$query = " SELECT B.idxnum ";
		$query .= " FROM php_personal_info A ";
		$query .= " LEFT OUTER JOIN php_performance B ON concat(DATE_FORMAT(B.buydt,'%Y%m%d'),LPAD(B.lane,2,'0'),'-',B.branch)=A.venueid ";
		$query .= " WHERE A.idxnum = '".$g_idxnum."'";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$g_localeid = $row['idxnum'];
		}
	}
	//会場情報を取得
	$query = " SELECT A.buydt, A.staff, B.facility ,concat(DATE_FORMAT(A.buydt,'%Y%m%d'),LPAD(A.lane,2,'0'),'-',A.branch) as venueid, A.week, A.reserflg";
	$query .= " FROM php_performance A ";
	$query .= " LEFT OUTER JOIN php_facility B ON A.facility_id=B.facility_id ";
	$query .= " WHERE A.idxnum = ".sprintf("'%s'", $g_localeid);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$week = $row['week'];
		$g_buydt = $row['buydt'];
		$g_staff = $row['staff'];
		$g_locale = $row['facility'];
		$g_venueid = $row['venueid'];
		$reserflg = $row['reserflg'];
	}
	//型番をリストに格納
	$query = "SELECT A.modelnum ,A.tanka ";
	$query .= "FROM ( ";
	//-- 在庫型番
	$query .= " SELECT modelnum, tanka ";
	$query .= " FROM php_t_pc_zaiko ";
	$query .= " WHERE delflg = 0 ";
	$query .= " AND hanbaiflg = 0 ";
	$query .= " AND staff = " . sprintf("'%s'", $g_staff);
	$query .= " GROUP BY modelnum ,tanka ";
	//-- 未受取在庫型番
	$query .= " UNION ALL ";
	$query .= " SELECT modelnum, tanka ";
	$query .= " FROM php_s_pc_zaiko ";
	$query .= " WHERE delflg = 0 ";
	$query .= " AND receiveflg = 0 ";
	$query .= " AND staff = " . sprintf("'%s'", $g_staff);
	$query .= " GROUP BY modelnum ,tanka ";
	//-- 販売型番
	$query .= " UNION ALL ";
	$query .= " SELECT modelnum, tanka ";
	$query .= " FROM php_t_pc_hanbai ";
	$query .= " WHERE delflg = 0 ";
	$query .= " AND kbn = 1 ";
	$query .= " AND (mrenum > 0 ";
	$query .= " OR c_mrenum > 0 ";
	$query .= " OR c_grenum > 0 ";
	$query .= " OR grenum > 0 ";
	$query .= " OR hannum > 0)";
	$query .= " AND venueid = " . sprintf("'%s'", $g_venueid);
	//--価格表型番
	$query .= " UNION ALL ";
	$query .= " SELECT E.modelnum, E.tanka ";
	$query .= " FROM php_pc_price E";
	$query .= " WHERE E.week = " . sprintf("'%s'", $week);
	if($reserflg == 0){
		$query .= " AND E.reserv = 0 ";
	}
	//--価格表レンタル型番
	$query .= " UNION ALL ";
	$query .= " SELECT F.modelnum, F.tanka_rent as tanka ";
	$query .= " FROM php_pc_price F";
	$query .= " WHERE F.week = " . sprintf("'%s'", $week);
	$query .= " AND F.tanka_rent > 0 ";
	if($reserflg == 0){
		$query .= " AND F.reserv = 0 ";
	}
	//--価格表下取型番
	$query .= " UNION ALL ";
	$query .= " SELECT G.modelnum, G.tanka_trade as tanka ";
	$query .= " FROM php_pc_price G";
	$query .= " WHERE G.week = " . sprintf("'%s'", $week);
	$query .= " AND G.tanka_trade > 0 ";
	if($reserflg == 0){
		$query .= " AND G.reserv = 0 ";
	}
	$query .= " ) A ";
	$query .= " GROUP BY A.modelnum ,A.tanka ";
	$query .= " ORDER BY A.modelnum LIKE '%DSK' ASC, A.modelnum ,A.tanka ";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$modellist[] = $row['modelnum'];
		$price[] = $row['tanka'];
	}
	$json_price = json_encode($price);
	//オプションをリストに格納
	$query = " SELECT name, tanka FROM php_option_info ";
	$query .= " WHERE delflg=0 ";
	$query .= " AND r_flg=1 ";
	$query .= " GROUP BY name, tanka ";
	$query .= " ORDER BY tanka ";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$optionlist[] = $row['name'];
		$option_kin[$row['name']] = $row['tanka'];
	}
	//割引をリストに格納
	$query = "SELECT A.modelnum ,A.tanka ";
	$query .= "FROM ( ";
	//--販売
	$query .= " SELECT modelnum, tanka FROM php_t_pc_hanbai ";
	$query .= " WHERE delflg=0 ";
	$query .= " AND kbn=3 ";
	$query .= " AND venueid = " . sprintf("'%s'", $g_venueid);
	$query .= " GROUP BY modelnum, tanka ";
	//--テーブル
	$query .= " UNION ALL ";
	$query .= " SELECT name as modelnum, tanka FROM php_discount_info ";
	$query .= " WHERE delflg=0 ";
	$query .= " GROUP BY name, tanka ";
	//--個別価格表
	$query .= " UNION ALL ";
	$query .= " SELECT modelnum, tanka ";
	$query .= " FROM php_pc_price_team ";
	$query .= " WHERE  week = " . sprintf("'%s'", $week);
	$query .= " AND staff = " . sprintf("'%s'", $g_staff);
	$query .= " AND kbn=3 ";
	$query .= " ) A ";
	$query .= " GROUP BY modelnum, tanka ";
	$query .= " ORDER BY tanka ";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$optionlist[] = $row['modelnum'];
		$option_kin[$row['modelnum']] = $row['tanka'];
	}
	$check_list = array("お名前","電話番号１","郵便番号","郵便番号２","都道府県","ご住所１","型番1","予約","支払方法");
	$json_check = json_encode($check_list);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<head>
	<style type="text/css">
		/*　追加ボタン　*/
		.btn-coladd:hover {
			background: #67c5ff;
			color: white;
			font-weight: bold;
		}
		.btn-coladd {
			display: inline-block;
			font-weight: bold;
			padding: 0.3em 1em;
			text-decoration: none;
			color: #67c5ff;
			border: solid 2px #67c5ff;
			border-radius: 3px;
			transition: .4s;
			background:white;
  		}
		/*　保留ボタン　*/
		.btn-border.btn-info.u {
			border:3px solid #FF6600;
			font-size: 22px;
			color:#FF6600;
			height: 45px;
		}
		.btn-border.btn-info.u:hover,.btn-border.btn-info.u:active {
			color:#FFF;
			font-weight: bold;
			background:#FF6600;
		}
		/* 検索ボタン */
		.btn-brackets {
		  display: inline-block;
		  background-image: none;
		  background: rgba(0,0,0,0);
		  position: relative;
		  padding: 0.5em 3em;
		  text-decoration: none;
		  color: #000;
		  transition: .4s;
		}
		.btn-brackets:hover {
		  color:#ff7f7f;
		}
		.btn-brackets:before, .btn-brackets:after {
		  position: absolute;
		  top: 0;
		  content:'';
		  width: 8px;
		  height: 100%;
		  display: inline-block;
		}
		.btn-brackets:before {
		  border-left: solid 1px #ff7f7f;
		  border-top: solid 1px #ff7f7f;
		  border-bottom: solid 1px #ff7f7f;
		  left: 0;
		}
		.btn-brackets:after {
		  content: '';
		  border-top: solid 1px #ff7f7f;
		  border-right: solid 1px #ff7f7f;
		  border-bottom: solid 1px #ff7f7f;
		  right: 0;
		}
		/*　セレクト　*/
		.cp_ipselect {
			overflow: hidden;
			width: 100%;
			margin: 0em auto;
			text-align: center;
		}
		.cp_ipselect select {
			width: 100%;
			padding-right: 1em;
			cursor: pointer;
			text-indent: 0.01px;
			text-overflow: ellipsis;
			border: none;
			outline: none;
			background: transparent;
			background-image: none;
			box-shadow: none;
			-webkit-appearance: none;
			appearance: none;
		}
		.cp_ipselect select::-ms-expand {
		    display: none;
		}
		.cp_ipselect.cp_sl02 {
			position: relative;
			border: 1px solid #bbbbbb;
			border-radius: 2px;
			background: #ffffff;
		}
		.cp_ipselect.cp_sl02::before {
			position: absolute;
			top: 0.8em;
			right: 0.9em;
			width: 0;
			height: 0;
			padding: 0;
			content: '';
			border-left: 6px solid transparent;
			border-right: 6px solid transparent;
			border-top: 6px solid #666666;
			pointer-events: none;
		}
		.cp_ipselect.cp_sl02:after {
			position: absolute;
			top: 0;
			right: 2.5em;
			bottom: 0;
			width: 1px;
			content: '';
			border-left: 1px solid #bbbbbb;
		}
		.cp_ipselect.cp_sl02 select {
			padding: 8px 38px 8px 8px;
			color: #666666;
		}
	</style>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>会場予約顧客情報</title>
  
  <!-- cascading style seet-->
  <link rel="stylesheet" type="text/css" href="css/bootstrap.css">
</head>
<body>
<!--------------------------------------------------------------------------------------------------
	コンテンツ表示
---------------------------------------------------------------------------------------------------->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<meta charset="UTF-8">
<head>
	<title>会場予約顧客情報</title>
	<!-- cascading style seet-->
	<link rel="stylesheet" type="text/css" href="../css/bootstrap.css">
	<script src="http://maps.google.com/maps/api/js?sensor=false" charset="UTF-8" type="text/javascript"></script>
	<script src="js/hpbmapscript1.js" charset="UTF-8" type="text/javascript">HPBMAP_20150620053222</script>

	<script src="./js/jquery-1.11.1.min.js" charset="UTF-8" type="text/javascript"></script>
	<script src="./js/memf.js" charset="UTF-8" type="text/javascript"></script>

	<!-- ============================= -->
	<!-- ▼スクリプトをCDNから読み込む -->
	<!-- ============================= -->
	<script type="text/javascript" src="//code.jquery.com/jquery-2.1.0.min.js"></script>
	<script type="text/javascript" src="//jpostal-1006.appspot.com/jquery.jpostal.js"></script>
	<script src="sweetalert2/dist/sweetalert2.all.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@8"></script>
	<!-- ================================================== -->
	<!-- ▼郵便番号や各種住所の入力欄に関するID名を指定する -->
	<!-- ================================================== -->
	<script type="text/javascript">
		$(window).ready( function() {
			$('#postcode1').jpostal({
				postcode : [
					'#postcode1',
					'#postcode2'
				],
				address : {
					'#address1'  : '%3',
					'#address2'  : '%4%5'
				}
			});
		});
		$(function() {
			if ($('#name').val() != '') {
				change_back_true('name');
			}
			if ($('#phonenum1').val() != '') {
				change_back_true('phonenum1');
			}
			if ($('#postcode1').val() != '') {
				change_back_true('postcode1');
			}
			if ($('#postcode2').val() != '') {
				change_back_true('postcode2');
			}
			if ($('#address1').val() != '選択してください　') {
				change_back_true('address1');
			}
			if ($('#address2').val() != '') {
				change_back_true('address2');
			}
			// 電話番号入力フォーム監視 ハイフン・数字のみ入力可
			$('#phonenum1').on(
				'input', function() {
					let value = $(this).val();
					value = value.replace(
						/[０-９]/g, function(s) {
							return String.fromCharCode(
								s.charCodeAt(0) - 65248
							);
						}
					);
					value = value.replace(/[^0-9-]/g, '');
					$(this).val(value);
					if ($('#phonenum1').val() != '') {
						change_back_true('phonenum1');
					} else {
						change_back_false('phonenum1');
					}
					// 最大桁数設定
					let max_length = 11;
					const mobile = ['050', '070', '080', '090'];
					if (value.length >= 3) {
						n_slice = value.slice(0, 3);
						// 携帯番号の場合
						if(mobile.includes(n_slice)) {
							// ハイフンが入力されていれば最大桁数引上
							if (value.includes('-')) {
								max_length = 13;
							}
						// 固定番号の場合
						} else if (!mobile.includes(n_slice)) {
							// 最大桁数設定
							max_length = 10;
							// ハイフンが入力されていれば最大桁数引上
							if (value.includes('-')) {
								max_length = 12;
							}
						}
						// 最大桁数入力後自動遷移
						if (value.length == max_length) {
							setTimeout(
								function() {
									$('#postcode1').focus();
								}, 200
							);
						}
						// 最大桁以上入力制限
						if (value.length > max_length) {
							$(this).val(
								value.slice(0, max_length)
							);
							setTimeout(
								function() {
									$('#postcode1').focus();
								}, 200
							);
						}
					}
				}
			);
			// フォーカスが外れたらハイフンを挿入
			$('#phonenum1').blur(
				function() {
					let value = $(this).val();
					const mobile = ['050', '070', '080', '090'];
					let max_length = 11;
					if (value.length >= 3) {
						n_slice1 = value.slice(0, 3);
						// 携帯番号の場合
						if (mobile.includes(n_slice1)) {
							// 最大桁入力済ならハイフン挿入
							if (value.length == max_length) {
								n_slice2 = value.slice(3, 7);
								n_slice3 = value.slice(7, 13);
								// ハイフンが入力されていなければ実行
								if (!value.includes('-')) {
									value = n_slice1 + '-' + n_slice2 + '-' + n_slice3;
								}
								$(this).val(value);
							}
						// 固定番号の場合
						} else if(!mobile.includes(n_slice1)) {
							max_length = 10;
							// 最大桁入力済ならハイフン挿入
							if (value.length == max_length) {
								n_slice2 = value.slice(3, 6);
								n_slice3 = value.slice(6, 12);
								// ハイフンが入力されていなければ実行
								if (!value.includes('-')) {
									value = n_slice1 + '-' + n_slice2 + '-' + n_slice3;
								}
								$(this).val(value);
							}
						}
					}
				}
			);
			// 電話番号入力フォーム監視 ハイフン・数字のみ入力可
			$('#phonenum2').on(
				'input', function() {
					let value = $(this).val();
					value = value.replace(
						/[０-９]/g, function(s) {
							return String.fromCharCode(
								s.charCodeAt(0) - 65248
							);
						}
					);
					value = value.replace(/[^0-9-]/g, '');
					$(this).val(value);
					let max_length = 11;
					const mobile = ['050', '070', '080', '090'];
					if (value.length >= 3) {
						n_slice = value.slice(0, 3);
						// 携帯番号の場合
						if(mobile.includes(n_slice)) {
							// ハイフンが入力されていれば最大桁数引上
							if (value.includes('-')) {
								max_length = 13;
							}
						// 固定番号の場合
						} else if (!mobile.includes(n_slice)) {
							// 最大桁数変更
							max_length = 10;
							// ハイフンが入力されていれば最大桁数引上
							if (value.includes('-')) {
								max_length = 12;
							}
						}
						// 最大桁数入力後自動遷移
						if (value.length == max_length) {
							setTimeout(
								function() {
									$('#postcode1').focus();
								}, 200
							);
						}
						// 最大桁以上入力制限
						if (value.length > max_length) {
							$(this).val(
								value.slice(0, max_length)
							);
							setTimeout(
								function() {
									$('#postcode1').focus();
								}, 200
							);
						}
					}
				}
			);
			// フォーカスが外れたらハイフンを挿入
			$('#phonenum2').blur(
				function() {
					let value = $(this).val();
					const mobile = ['050', '070', '080', '090'];
					let max_length = 11;
					if (value.length >= 3) {
						n_slice1 = value.slice(0, 3);
						// 携帯番号の場合
						if (mobile.includes(n_slice1)) {
							// 最大桁入力済ならハイフン挿入
							if (value.length == max_length) {
								n_slice2 = value.slice(3, 7);
								n_slice3 = value.slice(7, 13);
								// ハイフンが入力されていなければ実行
								if (!value.includes('-')) {
									value = n_slice1 + '-' + n_slice2 + '-' + n_slice3;
								}
								$(this).val(value);
							}
						// 固定番号の場合
						} else if(!mobile.includes(n_slice1)) {
							max_length = 10;
							// 最大桁入力済ならハイフン挿入
							if (value.length == max_length) {
								n_slice2 = value.slice(3, 6);
								n_slice3 = value.slice(6, 12);
								// ハイフンが入力されていなければ実行
								if (!value.includes('-')) {
									value = n_slice1 + '-' + n_slice2 + '-' + n_slice3;
								}
								$(this).val(value);
							}
						}
					}
				}
			);
			$('#name').on(
				'input', function() {
					if ($('#name').val() != '') {
						change_back_true('name');
					} else {
						change_back_false('name');
					}
				}
			);
			$('#postcode1').on(
				'input', function() {
					let value = $(this).val();
					// 3桁以上入力不可
					if (value.length > 3) {
						$(this).val(value.slice(0, 3));
					}
					// 3桁入力したら郵便番号2へフォーカス
					if (value.length == 3) {
						$('#postcode2').focus();
					}
					// 全角→半角
					value = value.replace(
						/[０-９]/g, function(s) {
							return String.fromCharCode(s.charCodeAt(0) - 65248);
						}
					)
					// 文字列削除
					.replace(/[^0-9]/g, '');
					$(this).val(value);
					if ($('#postcode1').val() != '') {
						change_back_true('postcode1');
					} else {
						change_back_false('postcode1');
					}
					if ($('#address1').val() != '選択してください　') {
						change_back_true('address1');
					} else {
						change_back_false('address1');
					}
					if ($('#address2').val() != '') {
						change_back_true('address2');
					} else {
						change_back_false('address2');
					}
				}
			);
			$('#postcode2').on(
				'input', function() {
					let value = $(this).val();
					// 4桁以上入力不可
					if (value.length > 4) {
						$(this).val(value.slice(0, 4));
					}
					// 4桁入力したら住所へフォーカス
					if (value.length == 4) {
						$('#address2').focus();
					}
					// 全角→半角
					value = value.replace(
						/[０-９]/g, function(s) {
							return String.fromCharCode(s.charCodeAt(0) - 65248);
						}
					)
					// 文字列削除
					.replace(/[^0-9]/g, '');
					$(this).val(value);
					if ($('#postcode2').val() != '') {
						change_back_true('postcode2');
					} else {
						change_back_false('postcode2');
					}
					if ($('#address1').val() != '選択してください　') {
						change_back_true('address1');
					} else {
						change_back_false('address1');
					}
					if ($('#address2').val() != '') {
						change_back_true('address2');
					} else {
						change_back_false('address2');
					}
				}
			);
			$('#address1').on(
				'input', function() {
					if ($('#address1').val() != '選択してください　') {
						change_back_true('address1');
					} else {
						change_back_false('address1');
					}
				}
			);
			$('#address2').on(
				'input', function() {
					if ($('#address2').val() != '') {
						change_back_true('address2');
					} else {
						change_back_false('address2');
					}
				}
			);
		});
		function change_back_true(id) {
			$('#' + id).css('background-color', 'white');
		}
		function change_back_false(id) {
			$('#' + id).css('background-color', '#f4b3c2');
		}
	</script>

	<style type="text/css">
		.radio {
		  box-sizing: border-box;
		  -webkit-transition: background-color 0.2s linear;
		  transition: background-color 0.2s linear;
		  position: relative;
		  display: inline-block;
		  margin: 0 20px 8px 0;
		  padding: 12px 12px 12px 42px;
		  border-radius: 8px;
		  background-color: #f6f7f8;
		  vertical-align: middle;
		  cursor: pointer;
		}
		#postcode1, #postcode2 { ime-mode: inactive; }
		.form-control.required_form {
			background-color: #f4b3c2;
		}
		.button_div {
			display: flex;
			width: 100%;
		}
	</style>
		<script>
	    $(function(){
	        $("input").on("keydown", function(e) {
	            if ((e.which && e.which === 13) || (e.keyCode && e.keyCode === 13)) {
	                return false;
	            } else {
	                return true;
	            }
	        });
	    });
	</script>

	<script type="text/javascript">
		function calculation_category(g_row){
			var json_price = JSON.parse('<?php echo $json_price; ?>');
			var gg_str = document.forms['frm'].elements['型番'+g_row].value;
			var g_str = gg_str.substr(0,1);
			var gg_row = document.forms['frm'].elements['型番'+g_row].selectedIndex-1;
			var option_han = "なし";
			var cnt_op = document.forms['frm'].elements['cnt_op'].value;
			if(document.forms['frm'].elements['型番'+g_row].value == 0){
				var opkin = 0;
				var pckin = 0;
			}else{
				//PCの金額取得
				var pckin = 0;
				var pckin = parseInt(json_price[gg_row],10);
				const g_option_han = [];
				var opkin = 0;
				//オプションの金額計算
				for(i=0; i<cnt_op; ++i){
					if(document.forms['frm'].elements['オプション'+i+'_'+g_row].checked == true){
						var opkin = parseInt(opkin,10) + parseInt(document.forms['frm'].elements['opkin'+i+'_'+g_row].value,10);
						g_option_han.push(document.forms['frm'].elements['オプション'+i+'_'+g_row].value);
					}
				}
				if(g_option_han.length > 0){
					var option_han = g_option_han.join('・');
				}
			}
			//小計金額計算
			var s_sum = pckin + opkin;
			document.forms['frm'].elements['option_han'+g_row].value = option_han;
			document.forms['frm'].elements['金額'+g_row].value = s_sum;
			//合計金額計算
			calculation2();
		}
		//合計金額計算 *田村追加
		function calculation2(){
			var cnt_op = document.forms['frm'].elements['cnt_op'].value;
			var table = document.getElementById('tbl');
			//テーブルの行数取得
			var o = table.rows.length-1;
			var sum = 0;
			var cnt_row = 0;
			for(i=1; i<o; i++) {
				if(document.forms['frm'].elements['型番'+i].value == ''){
				}else{
					var num = Number(document.forms['frm'].elements['金額'+i].value);
					var sum = sum + num;
					var cnt_row = cnt_row + 1;
				}
			}
			document.forms['frm'].elements['total_all'].value = sum;
			document.forms['frm'].elements['total_num'].value = cnt_row;
			document.forms['frm'].elements['cnt_row'].value = o-1;
		}
		
		//追加ボタンで行をコピーして追加
		function coladd() {
			var cnt_op = document.forms['frm'].elements['cnt_op'].value;
			var table = document.getElementById('tbl');
			//テーブルの行数を取得
			var o = table.rows.length-1;
			//対象行をコピー
			var clone = table.rows[1].cloneNode(true);
			//cloneのHTMLを取得
			var str = clone.innerHTML;
			//nameを置換
			str = str.replace(1 + "台目", o + "台目");
			str = str.replace("行数" + 1, "行数_" + o);
			str = str.replace("category" + 1, "category" + o);
			str = str.replace("option_han" + 1, "option_han" + o);
			for(i=0; i<cnt_op; ++i){
				str = str.replace("オプション"+i+"_" + 1, "オプション"+i+"_" + o);
				str = str.replace("opkin"+i+"_" + 1, "opkin"+i+"_" + o);
				str = str.replace("calculation_category(" + 1 + ")", "calculation_category(" + o + ")");
				str = str.replace("checked='checked'", "");
			}
			str = str.replace("型番" + 1, "型番" + o);
			str = str.replace("金額" + 1, "金額" + o);
			str = str.replace("Tcategory" + 1, "Tcategory" + o);
			str = str.replace("Toption_han" + 1, "Toption_han" + o);
			str = str.replace("Tcash" + 1, "Tcash" + o);
			str = str.replace("calculation_category(" + 1 + ")", "calculation_category(" + o + ")");
			clone.innerHTML = str;
			clone.cells[1].children[0].selectedIndex = 0;//型番
			clone.cells[3].children[0].value = 0;//金額
			clone.cells[4].children[0].value = "";//T型番
			clone.cells[4].children[1].value = "";//オプション
			clone.cells[4].children[2].value = "";//Tオプション
			clone.cells[4].children[3].value = 0;//T金額
			clone.cells[4].children[4].value = 0;//インデックス
			//対象行の後にコピー行追加
			table.rows[o-1].after(clone);
			//追加ボタンを変更
			document.forms['frm'].elements['行追加'].value = o+1+"台目を登録";
			//合計金額計算
			calculation2();
		}
		function MClickBtn(action,status) {
			var table = document.getElementById('tbl');
			if(status == 9){
				//伝票発行済みの場合はアラートで確認
				if(window.confirm('伝票発行済のデータです。\nお客様情報や注文内容に変更がある場合は、\n伝票発行担当者に連絡してください。')){
				}else{
					return false;
				}
			}
			//テーブルの行数取得
			var o = table.rows.length-1;
			//オプションのみ入力されているデータがないか確認
			for(i=1; i<o; i++) {
				if(document.forms['frm'].elements['category'+i].value == ''){
					if(window.confirm(i+'台目のカテゴリが未選択です。\nカテゴリが未選択の場合、\nオプションや金額も反映されませんがよろしいですか？')){
						document.forms['frm'].elements[action].click();
						return false;
					}else{
						return false;
					}
				}
			}
			document.forms['frm'].elements[action].click();
		}
		function Mclk_Update(idxnum,status){
			if(status == 9){
				//伝票発行済みの場合はアラートで確認
				if(window.confirm('伝票発行済みのデータです。\n修正後は必ず発送担当者にご連絡ください。\n')){
					document.forms['frm'].action = './reserv_sql.php?do=update&idx='+idxnum;
					document.forms['frm'].submit();
					return false;
				}else{
					return false;
				}
			}else{
				document.forms['frm'].action = './reserv_sql.php?do=update&idx='+idxnum;
				document.forms['frm'].submit();
				return false;
			}
		}
		function Mclk_Ins(g_idx){
			var json_check = JSON.parse('<?php echo $json_check; ?>');
			var p_alert = "";
			json_check.forEach(function(val){
				if(document.forms['frm'].elements[val].value == ''){
					var p_alert = p_alert + "\n" + val;
				}
			});
			if(p_alert !== ""){
				//伝票発行済みの場合はアラートで確認
				alert("必須項目が入力されていません。\n確認してください。\n\n<不足項目>"+p_alert);
				return false;
			}else{
				let name = $('#name').val();
				let phonenum1 = $('#phonenum1').val();
				let postcode1 = $('#postcode1').val();
				let postcode2 = $('#postcode2').val();
				let address1 = $('#address1').val();
				let address2 = $('#address2').val();
				let modelnum = $('[name=型番1]').val();
				let submit_punc = '';
				let submit_swal = '';
				// 必須項目未入力ならエラー表示
				if (name == '' || phonenum1 == '' || phonenum1 == null || postcode1 == '' || postcode2 == '' || address1 == '' || address2 == '' || modelnum == '') {
					if (name == '') {
						submit_swal = '氏名'
						submit_punc = '・';
					}
					if (phonenum1 == '' || phonenum1 == null) {
						submit_swal += submit_punc + '電話番号'
						submit_punc = '・';
					}
					if (postcode1 == '' || postcode2 == '') {
						submit_swal += submit_punc + '郵便番号'
						submit_punc = '・';
					}
					if (address1 == '選択してください' || address2 == '') {
						submit_swal += submit_punc + '住所'
						submit_punc = '・';
					}
					if (modelnum == '') {
						submit_swal += submit_punc + '型番'
					}
					Swal.fire
					(
						{
							type: 'error', 
							title: '以下の必須項目が未入力です', 
							html: submit_swal
						}
					);
				} else {
					console.log(document.forms['frm']);
					document.forms['frm'].action = './reserv_sql.php?do=insert&idx='+g_idx;
					document.forms['frm'].submit();
					return false;
				}
			}
		}
	</script>
	<script src="https://ajaxzip3.github.io/ajaxzip3.js" charset="UTF-8"></script>
</head>
<body>
<!-- ヘッダー -->
<header>
	<script>header();</script>
</header>
<!-- 本文 -->
<div class="container">
	<div class="row">
		<h1>予約顧客情報</h1>
	</div>
</div>
	<div class="container">
		<div class="row">
			<div class="panel panel-primary">
				<div class="panel-heading" style="font-size:18px;">登録フォーム</div>
				<form method="post" name="frm">
				<table class="table">
					<div class="col-lg-12">
						<h4><? echo $alert ?></h4>
					</div>
				</table>
				<? if(isset($g_idxnum)){
					//----- データ抽出
					//データを取得
					$query2 = "SELECT A.idxnum, A.name, A.company, A.phonenum1, A.phonenum2, A.postcd1, A.postcd2";
					$query2 .= ", A.address1, A.address2, A.address3, A.staff, A.cash, A.modelnum ";
					$query2 .= ", A.option1, A.option2, A.option3, A.option4, A.option5, A.option6, A.outputflg ";
					$query2 .= " , A.deliveryday, A.deliverytime, A.reserv, A.b_way, A.p_way, A.remarks, A.slipnumber ";
					$query2 .= ", A.buynum, A.status, A.g_buydt, A.g_locale, A.g_staff";
					$query2 .= " FROM php_personal_info A ";
					$query2 .= " WHERE A.idxnum = '".$g_idxnum."'";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query2, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs2 = $db->query($query2))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$g_option_han = [];
					$option_han = "";
					$cash_ = [];
					$idxnum = [];
					$cnt_row = 0;
					$total_num = 0;
					$total_all = 0;
					$i = 0;
					while ($row2 = $rs2->fetch_array()) {
						$modelnum = $row2['modelnum'];
						for($t=1; $t<7; ++$t){
							if($row2['option'.$t] <> ""){
								$option_han .= $row2['option'.$t]."・";
							}
						}
						rtrim($option_han);
						$cash_[] = $row2['cash'] * 100;
						$cnt_row += $row2['buynum'];
						$total_num += $row2['buynum'];
						$total_all += $row2['cash'] * 100;
						$tanka = $row2['cash']*100;
						$idxnum[] = $row2['idxnum'];
						$slipnumber = $row2['slipnumber'];
						
						$g_buydt = $row2['g_buydt'];
						$g_locale = $row2['g_locale'];
						$g_staff = $row2['g_staff'];
						$name = $row2['name'];
						$company = $row2['company'];
						$phonenum1 = mb_convert_kana($row2['phonenum1'],"pas");
						$phonenum2 = mb_convert_kana($row2['phonenum2'],"pas");
						$postcd1 = mb_convert_kana($row2['postcd1'],"as");
						$postcd2 = mb_convert_kana($row2['postcd2'],"as");
						$address1 = mb_convert_kana($row2['address1'],"as");
						$address2 = mb_convert_kana($row2['address2'],"as");
						$address3 = mb_convert_kana($row2['address3'],"as");
						$designated_day = $row2['deliveryday'];
						$specified_times = $row2['deliverytime'];
						$buynum = $row2['buynum'];
						$remark = $row2['remarks'];
						$status = $row2['status'];
						$reserv = $row2['reserv'];
						$p_way = $row2['p_way'];
						$b_way = $row2['b_way'];
						$outputflg = $row2['outputflg'];
					} ?>
					<table class="table">
						<div class="col-lg-12">
							<?
								//二重登録防止セッション定義
								$check = md5(uniqid(rand(), true));
								$_SESSION['check2'] = $check;
								$comm->ouputlog("SESSION＝".$_SESSION['check2'], $prgid, SYS_LOG_TYPE_INFO);
							?>
							<input name="check2" type="hidden" value="<?php echo $check ?>">
							<h4>会場情報</h4>
							<hr>
							<!-- 開催日付入力 -->
							<div class="col-lg-8">
								<div class="form-group">
									開催日付：
									<input type="text" name="取得日付" value="<? echo date('Y/n/j', strtotime($g_buydt)) ?>" readonly="readonly" style="border:none; background-color:white;" size="50">
								</div>
							</div>
							<!-- 担当者入力 -->
							<div class="col-lg-8">
								<div class="form-group">
									担当者　：
									<input type="text" name="取得担当者" value="<? echo $g_staff ?>" readonly="readonly" style="border:none; background-color:white;" size="50">
								</div>
							</div>
							<!-- 会場名入力 -->
							<div class="col-lg-8">
								<div class="form-group">
									会場名　：
									<input type="text" name="取得会場" value="<? echo $g_locale ?>" readonly="readonly" style="border:none; background-color:white;" size="50">
								</div>
							</div>
						</div>
						<div class="col-lg-12">
							<h4>お客様情報</h4>
							<hr>
							<p><strong><font color="red">※入力された情報は、パソコン配送時に使用します。</font></br>
							<div class="form-group">
								<div class="col-lg-12">
									<?php
									if($status == 1){
										echo '<font color="red"><strong>ステータス　　：本登録済</font></strong></br></br>';
									}else if($status == 9){
										echo '<font color="red"><strong>ステータス　　：伝票発行済</strong></font></br></br>';
									}
									?>
								</div>
							</div>
							<!-- 会社名入力 -->
							<div class="col-lg-8">
								<div class="form-group">
									<label>会社名（25文字以内）</label>
									<p>例)　一般社団法人　日本電子機器補修協会</p>
									<p style=" color:red; background-color:#fffacd"><strong>※代引の場合はお名前と合わせて領収書の宛名になります</br></p>
									<input type="text" class="form-control" name="会社名" value="<?php echo $company ?>">
								</div>
							</div>
							<!-- 名前入力 -->
							<div class="col-lg-6">
								<div class="form-group">
									<img src="./images/hisu.gif" alt="必須" >&nbsp;
									<label>氏名（漢字）（16文字以内）</label>
									<p>例)　山田　太郎</p>
									<p style=" color:red; background-color:#fffacd"><strong>※代引の場合は会社名と合わせて領収書の宛名になります</br></p>
									<input type="text" style="display:none;" name="インデックス" value="<?php echo $g_idxnum ?>">
									<input type="text" id="name" class="form-control required_form" name="お名前" value="<?php echo $name ?>">
								</div>
							</div>
							<div class="col-xs-7"></div>
							<!-- 電話番号1 -->
							<div class="col-lg-6">
								<div class="form-group">
									<img src="./images/hisu.gif" alt="必須" >
									<label>電話番号1</label>
									<p><strong><font color="red">※ハイフンは入力しなくても自動で挿入されます</font></p>
									<p><strong><font color="red">※送り状伝票に記載するため、繋がりやすい番号を記入してください。</font></p>
									<input type="tel" maxlength="13" id="phonenum1" class="form-control required_form" name="電話番号１" value="<?php echo $phonenum1 ?>">
								</div>
							</div>
							<!-- 電話番号2 -->
							<div class="col-lg-6">
								<div class="form-group">
									<label>電話番号2</label>
									</br></br></br></br>
									<input type="tel" maxlength="13" id="phonenum2" class="form-control" name="電話番号２" value="<?php echo $phonenum2 ?>">
								</div>
							</div>
							<br><br><br><br>
							<!--住所-->
							<div class="col-lg-12">
								<label>郵便番号</label>
								<p><strong><font color="red">※入力することで、該当する住所を自動で表示します。</font></p>
							</div>
							<div class="col-lg-2">
								<div class="form-group">
									<input type="tel" name="郵便番号" value="<?php echo $postcd1 ?>" class="form-control required_form" maxlength="3" id="postcode1">
								</div>
							</div>
							<div class="col-lg-3">
								<div class="form-group">
									<input type="tel" name="郵便番号２" value="<?php echo $postcd2 ?>" class="form-control required_form" maxlength="4" id="postcode2">
								</div>
							</div>
							<br><br><br>
							<div class="col-lg-8">
								<div class="form-group">
									<img src="./images/hisu.gif" alt="必須" >&nbsp;
									<label>都道府県</label>
									<select id="address1" name="都道府県" class="form-control required_form">
									<?php
										$comm->ouputlog("都道府県一覧", $prgid, SYS_LOG_TYPE_DBUG);
										foreach($p_address1list as $row) {
											$comm->ouputlog("都道府県=". $row[0], $prgid, SYS_LOG_TYPE_DBUG);
											if ($address1 == $row[0]) {
												echo "<option value=" . $row[0] . " selected >" . $row[0];
											}
											else {
												echo "<option value=" . $row[0] . ">" . $row[0];
											}
										}
									?>
									</select>
								</div>
							</div>
							<div class="col-lg-12">
								<div class="form-group">
									<img src="./images/hisu.gif" alt="必須" >&nbsp;
									<label>住所</label>
									<input type="text" name="ご住所１" value="<?php echo $address2 ?>" class="form-control required_form" id="address2">
								</div>
							</div>
							<div class="col-lg-12">
								<div class="form-group">
									<label>マンション名</label>
									<input type="text" name="ご住所２" value="<?php echo $address3 ?>" class="form-control" id="address3">
								</div>
							</div>
						</div>
						<div class="col-lg-12">
							<h4>パソコン希望内容</h4>
							<hr>
							<table class="table table-bordered" id="tbl">
								<tr class="info">
									<td style="text-align:center;" class="col-lg-2">
									</td>
									<td style="text-align:center;" class="col-lg-4">
										<strong>カテゴリ</strong>
									</td>
									<td style="text-align:center;" class="col-lg-4">
										<strong>オプション</strong>
									</td>
									<td style="text-align:center;" class="col-lg-2">
										<strong>金額（半角数字）</strong>
									</td>
								</tr>
								<?  for($j=0; $j<$cnt_row; ++$j){ ?>
									<tr>
										<td style="vertical-align:middle;">
											<input type="text" name="行数<? echo $j+1 ?>" value="<? echo $j+1 ?>台目" readonly="readinly" style="font-size:15px;border:none;background-color:transparent;">
										</td>
										<td style="vertical-align:middle;">
											<select name="型番<? echo $j+1 ?>" id="modelnum<? echo $j ?>" onchange="javascript:calculation_category(<? echo $j+1 ?>)">
												<option value="">未選択</option>
												<? for($i=0; $i<count($modellist); ++$i){ ?>
													<option value="<? echo $modellist[$i]; ?>" <? if($modelnum ==$modellist[$i]){ ?>selected="selected" <? } ?>><? echo $modellist[$i]; ?></option>
												<? } ?>
											</select>
										</td>
										<td>
											<? for($i=0; $i<count($optionlist); ++$i){ ?>
												<label><input type="checkbox" name="オプション<? echo $i ?>_<? echo $j+1 ?>" value="<? echo $optionlist[$i] ?>" onchange="javascript:calculation_category(<? echo $j+1 ?>)"<? if(mb_strpos($option_han,$optionlist[$i]) !== false){ ?>checked='checked'<? } ?>><? echo $optionlist[$i]."(税込".number_format($option_kin[$optionlist[$i]])."円)" ?></label></br>
												<input type="text" name="opkin<? echo $i ?>_<? echo $j+1 ?>" value="<? echo $option_kin[$optionlist[$i]] ?>" style="display:none">
											<? } ?>
										</td>
										<td style="text-align:right; vertical-align:middle;">
											<input type="text" pattern="^[0-9]+$" name="金額<? echo $j+1 ?>" size="6" value="<? echo $tanka ?>" onchange="javascript:calculation2()"> 円
										</td>
										<td style="display:none">
											<input type="text" name="option_han<? echo $j+1 ?>" value="<? echo $option_han ?>">
											<input type="text" name="Tcategory<? echo $j+1 ?>" value="<? echo $model[$j] ?>">
											<input type="text" name="Toption_han<? echo $j+1 ?>" value="<? echo $option_han ?>">
											<input type="text" name="Tcash<? echo $j+1 ?>" value="<? echo $cash_[$j] ?>">
											<input type="text" name="idxnum<? echo $j+1 ?>" value="<? echo $idxnum[$j] ?>">
											<input type="text" name="end<? echo $j+1 ?>">
										</td>
									</tr>
								<?  } ?>
								<tr bgcolor="#d3d3d3">
									<td style="text-align:left; vertical-align:middle;">
									<!--	<input type="button" name="行追加" onclick="javascript:coladd()" value="<? echo $cnt_row+2 ?>台目を登録" class="btn-coladd">-->
									</td>
									<td style="text-align:center; vertical-align:middle;" colspan="2">
										<strong>合計</strong>
									</td>
									<td style="text-align:right;" colspan="2">
										<strong><input type="text" name="total_num" size="8" value="<? echo $total_num ?>" readonly="readonly" style="font-size:15px;border:none;background-color:transparent;text-align:right;"></strong> 台<br>
										<strong><input type="text" name="total_all" size="8" value="<? echo $total_all ?>" readonly="readonly" style="font-size:15px;border:none;background-color:transparent;text-align:right;"></strong> 円
										<input type="text" name="cnt_row" value="<? echo $cnt_row ?>" style="display:none;">
										<input type="text" name="cnt_op" value="<? echo count($optionlist) ?>" style="display:none;">
									</td>
								</tr>
							</table>
							<table class="table">
							<div class="col-lg-8">
								<div class="form-group">
									<img src="./images/hisu.gif">&nbsp;
									<label>予約方法</label></br>
									　<label><input type="radio" name="予約" value="見本" <? if($reserv=="見本"){ echo "checked='checked'";} ?>>見本</label>
									　<label><input type="radio" name="予約" value="現物" <? if($reserv=="現物"){ echo "checked='checked'";} ?>>現物</label>
								</div>
							</div>
							<div class="col-lg-8">
								<div class="form-group">
									<img src="./images/hisu.gif">&nbsp;
									<label>発送方法</label></br>
									　<label><input type="radio" name="支払方法" value="代引" <? if($p_way=="代引"){ echo "checked='checked'";} ?>>代引</label>
									　<label><input type="radio" name="支払方法" value="元払" <? if($p_way=="元払"){ echo "checked='checked'";} ?>>元払</label>
								</div>
							</div>
							<div class="col-lg-8">
								<div class="form-group">
									<img src="./images/hisu.gif">&nbsp;
									<label>購入方法</label></br>
									　<label><input type="radio" name="購入方法" value="販売" <? if($b_way=="販売"){ echo "checked='checked'";} ?>>販売</label>
									　<label><input type="radio" name="購入方法" value="レンタル" <? if($b_way=="レンタル"){ echo "checked='checked'";} ?>>レンタル</label>
									　<label><input type="radio" name="購入方法" value="下取" <? if($b_way=="下取"){ echo "checked='checked'";} ?>>下取</label>
								</div>
							</div>
							<div class="col-lg-8">
								<div class="form-group">
									<label>配送日指定</label></br>
									<input type="date" class="form-control" name="お届け予定日" <? if($designated_day <> 0){ ?>value="<?php echo $designated_day;} ?>">
								</div>
							</div>
							<div class="col-lg-8">
								<div class="form-group">
									<img src="./images/hisu.gif">&nbsp;
									<label>配達希望時間</label>
									<select name="お届時間" class="form-control">
										<option value="指定なし"<?php if($specified_times =="指定なし"){echo 'selected'; } ?>>指定なし</option>
										<option value="0812"<?php if($specified_times =="0812"){echo 'selected'; } ?>>午前中</option>
										<option value="1416"<?php if($specified_times =="1416"){echo 'selected'; } ?>>14時～16時</option>
										<option value="1618"<?php if($specified_times =="1618"){echo 'selected'; } ?>>16時～18時</option>
										<option value="1820"<?php if($specified_times =="1820"){echo 'selected'; } ?>>18時～20時</option>
										<option value="1921"<?php if($specified_times =="1921"){echo 'selected'; } ?>>19時～21時</option>
									</select>
								</div>
							</div>
							<!--備考-->
							<div class="col-lg-12">
								<div class="form-group">
									<label>備考（その他希望内容）</label>
									<textarea name="備考" rows="3" cols="70" class="form-control"><?php echo $remark ?></textarea>
								</div>
							</div>
							<!--伝票番号-->
							<div class="col-lg-12">
								<div class="form-group">
									<label>伝票番号<font>※基本的に触らない※</font></label>
									<? if($p_leaderflg > 0 || $p_ns > 0 || $p_Auth > 8){ ?>
									<input type="text" name="伝票番号" value="<?php echo $slipnumber ?>" class="form-control">
									<br>（発送：<? echo $response ?>）
									<? }else{ ?>
										<p><? echo $slipnumber; ?>（発送：<? echo $response ?>）</p>
									<? } ?>
								</div>
							</div>
							<!--元データ-->
							<div class="col-lg-8" style="display:none">
								<input type="text" name="Tidxnum" value="<? echo $g_idxnum ?>">
								<input type="text" name="Tcompany" value="<? echo $company ?>">
								<input type="text" name="Tname" value="<? echo $name ?>">
								<input type="text" name="Tphonenum1" value="<? echo $phonenum1 ?>">
								<input type="text" name="Tphonenum2" value="<? echo $phonenum2 ?>">
								<input type="text" name="Tpostcd1" value="<? echo $postcd1 ?>">
								<input type="text" name="Tpostcd2" value="<? echo $postcd2 ?>">
								<input type="text" name="Taddress1" value="<? echo $address1 ?>">
								<input type="text" name="Taddress2" value="<? echo $address2 ?>">
								<input type="text" name="Taddress3" value="<? echo $address3 ?>">
								<input type="text" name="receptionday" value="<? echo $receptionday ?>">
								<input type="date" name="Tdesignated_day" <? if($designated_day <> 0){ ?>value="<?php echo $designated_day;} ?>">
								<input type="text" name="Tspecified_times" value="<? echo $specified_times ?>">
								<input type="text" name="Tslipnumber" value="<? echo $slipnumber ?>">
								<textarea name="Tremark"><? echo $remark ?></textarea>
							</div>
						</div>
						<div class="button_div">
							<!--登録・戻るボタン-->
							<div class="col-lg-4">
								<div class="form-group">
									<input type="button" value="更新" class="btn-info btn-border" onclick="javascript:Mclk_Update(<? echo $g_idxnum ?>,<? echo $status ?>)">
								</div>
							</div>
							<div class="col-lg-4">
								<div class="form-group">
									<a href="telorder_list.php"><input type="button" value="閉じる" class="btn-info btn-border" onClick="window.close()"></a>
								</div>
							</div>
							</br></br></br></br>
						</div>
					</table>
				<? }else{ ?>
					<table class="table">
						<div class="col-lg-12">
							<?
								//二重登録防止セッション定義
								$check = md5(uniqid(rand(), true));
								$_SESSION['check2'] = $check;
								$comm->ouputlog("SESSION＝".$_SESSION['check2'], $prgid, SYS_LOG_TYPE_INFO);
							?>
							<input name="check2" type="hidden" value="<?php echo $check ?>">
							<h4>会場情報</h4>
							<hr>
							<!-- 開催日付入力 -->
							<div class="col-lg-8">
								<div class="form-group">
									開催日付：
									<input type="text" name="取得日付" value="<? echo date('Y/n/j', strtotime($g_buydt)) ?>" readonly="readonly" style="border:none; background-color:white;" size="50">
								</div>
							</div>
							<!-- 担当者入力 -->
							<div class="col-lg-8">
								<div class="form-group">
									担当者　：
									<input type="text" name="取得担当者" value="<? echo $g_staff ?>" readonly="readonly" style="border:none; background-color:white;" size="50">
								</div>
							</div>
							<!-- 会場名入力 -->
							<div class="col-lg-8">
								<div class="form-group">
									会場名　：
									<input type="text" name="取得会場" value="<? echo $g_locale ?>" readonly="readonly" style="border:none; background-color:white;" size="50">
								</div>
							</div>
						</div>
						<div class="col-lg-12">
							<h4>お客様情報</h4>
							<hr>
							<p><strong><font color="red">※入力された情報は、パソコン配送時に使用します。</font></br></p>
							<!-- 会社名入力 -->
							<div class="col-lg-8">
								<div class="form-group">
									<label>会社名（25文字以内）</label>
									<p>例)　一般社団法人　日本電子機器補修協会</p>
									<p style=" color:red; background-color:#fffacd"><strong>※代引の場合はお名前と合わせて領収書の宛名になります</br></p>
									<input type="text" class="form-control" name="会社名">
								</div>
							</div>
							<!-- 名前入力 -->
							<div class="col-lg-6">
								<div class="form-group">
									<img src="./images/hisu.gif" alt="必須" >&nbsp;
									<label>氏名（漢字）（16文字以内）</label>
									<p>例)　山田　太郎</p>
									<p style=" color:red; background-color:#fffacd"><strong>※代引の場合は会社名と合わせて領収書の宛名になります</br></p>
									<input type="text" id="name" class="form-control required_form" name="お名前">
								</div>
							</div>
							<div class="col-xs-7"></div>
							<!-- 電話番号1 -->
							<div class="col-lg-6">
								<div class="form-group">
									<img src="./images/hisu.gif" alt="必須" >
									<label>電話番号1</label>
									<p><strong><font color="red">※ハイフンは入力しなくても自動で挿入されます</font></p>
									<p><strong><font color="red">※送り状伝票に記載するため、繋がりやすい番号を記入してください。</font></p>
									<input type="tel" maxlength="13" id="phonenum1" class="form-control required_form" name="電話番号１">
								</div>
							</div>
							<!-- 電話番号2 -->
							<div class="col-lg-6">
								<div class="form-group">
									<label>電話番号2</label>
									</br></br></br></br>
									<input type="tel" maxlength="13" id="phonenum2" class="form-control" name="電話番号２">
								</div>
							</div>
							<br><br><br><br>
							<!--住所-->
							<div class="col-lg-12">
								<label>郵便番号</label>
								<p><strong><font color="red">※入力することで、該当する住所を自動で表示します。</font></p>
							</div>
							<div class="col-lg-2">
								<div class="form-group">
									<input type="tel" name="郵便番号" class="form-control required_form" maxlength="3" id="postcode1">
								</div>
							</div>
							<div class="col-lg-3">
								<div class="form-group">
									<input type="tel" name="郵便番号２" class="form-control required_form" maxlength="4" id="postcode2">
								</div>
							</div>
							<br><br><br>
							<div class="col-lg-8">
								<div class="form-group">
									<img src="./images/hisu.gif" alt="必須" >&nbsp;
									<label>都道府県</label>
									<select id="address1" name="都道府県" class="form-control required_form">
									<?
										$comm->ouputlog("都道府県一覧", $prgid, SYS_LOG_TYPE_DBUG);
										foreach($p_address1list as $row) {
											echo "<option value=" . $row[0] . ">" . $row[0];
										}
									?>
									</select>
								</div>
							</div>
							<div class="col-lg-12">
								<div class="form-group">
									<img src="./images/hisu.gif" alt="必須" >&nbsp;
									<label>住所</label>
									<input type="text" name="ご住所１" class="form-control required_form" id="address2">
								</div>
							</div>
							<div class="col-lg-12">
								<div class="form-group">
									<label>マンション名</label>
									<input type="text" name="ご住所２" class="form-control" id="address3">
								</div>
							</div>
						</div>
						<div class="col-lg-12">
							<h4>パソコン希望内容</h4>
							<hr>
							<table class="table table-bordered" id="tbl">
								<tr class="info">
									<td style="text-align:center;" class="col-lg-2">
									</td>
									<td style="text-align:center;" class="col-lg-4">
										<strong>カテゴリ</strong>
									</td>
									<td style="text-align:center;" class="col-lg-4">
										<strong>オプション</strong>
									</td>
									<td style="text-align:center;" class="col-lg-2">
										<strong>金額（半角数字）</strong>
									</td>
								</tr>
								<tr>
									<td style="vertical-align:middle;">
										<input type="text" name="行数1" value="1台目" readonly="readinly" style="font-size:15px;border:none;background-color:transparent;">
									</td>
									<td style="vertical-align:middle;">
										<select name="型番1" onchange="javascript:calculation_category(1)">
											<option value="">未選択</option>
											<? for($i=0; $i<count($modellist); ++$i){ ?>
												<option value="<? echo $modellist[$i]; ?>" id="modelnum<? echo $i ?>"><? echo $modellist[$i]; ?>（<? echo number_format($price[$i]); ?>円）</option>
											<? } ?>
										</select>
									</td>
									<td>
										<? for($i=0; $i<count($optionlist); ++$i){ ?>
											<label><input type="checkbox" name="オプション<? echo $i ?>_1" value="<? echo $optionlist[$i] ?>" onchange="javascript:calculation_category(1)"<? if(mb_strpos($option_han,$optionlist[$i]) !== false){ ?>checked='checked'<? } ?>><? echo $optionlist[$i]."(税込".number_format($option_kin[$optionlist[$i]])."円)" ?></label></br>
											<input type="text" name="opkin<? echo $i ?>_1" value="<? echo $option_kin[$optionlist[$i]] ?>" style="display:none">
										<? } ?>
									</td>
									<td style="text-align:right; vertical-align:middle;">
										<input type="text" pattern="^[0-9]+$" name="金額1" size="6" value="<? echo $tanka ?>" onchange="javascript:calculation2()"> 円
									</td>
									<td style="display:none">
										<input type="text" name="option_han1" value="<? echo $option_han ?>">
										<input type="text" name="Tcategory1" value="<? echo $model[$j] ?>">
										<input type="text" name="Toption_han1" value="<? echo $option_han ?>">
										<input type="text" name="Tcash1" value="<? echo $cash_[$j] ?>">
										<input type="text" name="idxnum1" value="<? echo $idxnum[$j] ?>">
										<input type="text" name="end1">
									</td>
								</tr>
								<tr bgcolor="#d3d3d3">
									<td style="text-align:left; vertical-align:middle;">
										<input type="button" name="行追加" onclick="javascript:coladd()" value="<? echo $cnt_row+2 ?>台目を登録" class="btn-coladd">
									</td>
									<td style="text-align:center; vertical-align:middle;" colspan="2">
										<strong>合計</strong>
									</td>
									<td style="text-align:right;" colspan="2">
										<strong><input type="text" name="total_num" size="8" value="<? echo $total_num ?>" readonly="readonly" style="font-size:15px;border:none;background-color:transparent;text-align:right;"></strong> 台<br>
										<strong><input type="text" name="total_all" size="8" value="<? echo $total_all ?>" readonly="readonly" style="font-size:15px;border:none;background-color:transparent;text-align:right;"></strong> 円
										<input type="text" name="cnt_row" value="<? echo $cnt_row ?>" style="display:none;">
										<input type="text" name="cnt_op" value="<? echo count($optionlist) ?>" style="display:none;">
									</td>
								</tr>
							</table>
							<table class="table">
							<div class="col-lg-8">
								<div class="form-group">
									<img src="./images/hisu.gif">&nbsp;
									<label>予約方法</label></br>
									　<label><input type="radio" name="予約" value="見本" checked="checked">見本</label>
									　<label><input type="radio" name="予約" value="現物">現物</label>
								</div>
							</div>
							<div class="col-lg-8">
								<div class="form-group">
									<img src="./images/hisu.gif">&nbsp;
									<label>発送方法</label></br>
									　<label><input type="radio" name="支払方法" value="代引" checked="checked">代引</label>
									　<label><input type="radio" name="支払方法" value="元払">元払</label>
								</div>
							</div>
							<div class="col-lg-8">
								<div class="form-group">
									<img src="./images/hisu.gif">&nbsp;
									<label>購入方法</label></br>
									　<label><input type="radio" name="購入方法" value="販売" checked="checked">販売</label>
									　<label><input type="radio" name="購入方法" value="レンタル">レンタル</label>
									　<label><input type="radio" name="購入方法" value="下取">下取</label>
								</div>
							</div>
							<div class="col-lg-8">
								<div class="form-group">
									<label>配送日指定</label></br>
									<input type="date" class="form-control" name="お届け予定日" style="display:none">
									<strong><font color="red">※入荷状況が不安定なため、配送日指定は受付できません。</font></strong>
								</div>
							</div>
							<div class="col-lg-8">
								<div class="form-group">
									<img src="./images/hisu.gif">&nbsp;
									<label>配達希望時間</label>
									<select name="お届時間" class="form-control">
										<option value="指定なし">指定なし</option>
										<option value="0812">午前中</option>
										<option value="1416">14時～16時</option>
										<option value="1618">16時～18時</option>
										<option value="1820">18時～20時</option>
										<option value="1921">19時～21時</option>
									</select>
								</div>
							</div>
							<!--備考-->
							<div class="col-lg-12">
								<div class="form-group">
									<label>備考（その他希望内容）</label>
									<textarea name="備考" rows="3" cols="70" class="form-control"></textarea>
								</div>
							</div>
							<div class="col-lg-8" style="display:none">
								<input type="text" name="会場ＩＤ" value="<? echo $g_venueid ?>">
								<input type="text" name="end">
							</div>
						</div>
						<div class="button_div">
							<!--登録・戻るボタン-->
							<div class="col-lg-4">
								<div class="form-group">
									<input type="button" value="登録" class="btn-info btn-border" onclick="javascript:Mclk_Ins(<? echo $g_localeid ?>)">
								</div>
							</div>
							<div class="col-lg-4">
								<div class="form-group">
									<input type="button" value="閉じる" class="btn-info btn-border" onClick="window.close()">
								</div>
							</div>
							</br></br></br></br>
						</div>
					</table>
				<? } ?>
				</form>
			</div>
		</div>
	</div>
</div>
</body>
</html>
<!-- データベース切断 -->
<?php if ($result) { $dba->mysql_discon($db); }?>
</html>