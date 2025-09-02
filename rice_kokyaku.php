<?php
//==================================================================================================
// ■機能概要
//   ・精米倶楽部　顧客情報編集
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
	$g_idx = $_GET['idx'];
	$g_localeid = $_GET['localeid'];
	$change = $_GET['change'];
	$comm->ouputlog("get(idxnum)=" . $g_idxnum, $prgid, SYS_LOG_TYPE_DBUG);

	if($_GET['flg'] == 1){
		$alert = '<p><font color="red"><strong>更新が完了しました。</strong></font></p>';
	}else if($_GET['flg'] == 2){
		$alert = '<p><font color="red"><strong>更新に失敗しました。</strong></font></p>';
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
	$timelist_r = array("8～12時" => "0812", "12～14時" => "1214", "14～16時" => "1416", "16～18時" => "1618", "18～20時" => "1820", "18～21時" => "1821", "19～21時" => "1921", "指定なし" => "");
	$query = "
		 SELECT A.category, A.weight, A.tanka
		 FROM php_rice_category A
		 WHERE A.delflg = 0
		 ORDER BY A.idxnum
		";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$category_list[] = $row['category'];
		$weight_list[$row['weight']] = $row['weight']."kg";
		$tanka_list[$row['category']][$row['weight']] = $row['tanka'];
	}
	//配列の整理
	$category_list = array_unique($category_list);
	ksort($weight_list);
	//配列をjsonに変換
	$json_tanka_list = json_encode($tanka_list);

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

  <title>精米倶楽部顧客情報</title>
  
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
	<title>精米倶楽部顧客情報</title>
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
	<script src="https://ajaxzip3.github.io/ajaxzip3.js" charset="UTF-8"></script>
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
			text-align: center;
			display: flex;
			width: 100%;
		}
		/* 青系シンプルボタン */
		.button-blue {
		    background-color: #3498db; /* メインの青 */
		    color: #ffffff;           /* 文字色は白 */
		    padding: 3px 20px;        /*  */
		    margin: 3px 0px;        /*  */
		    border: none;             
		    border-radius: 5px;       
		    font-size: 16px;          /* 元の文字サイズを維持 */
		    cursor: pointer;
		    transition: background-color 0.3s ease;
		}

		/* ホバー時 */
		.button-blue:hover {
		    background-color: #2980b9; /* 少し濃い青 */
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
		//配列を変換
		var json_tanka_list = JSON.parse('<? echo $json_tanka_list; ?>');
		
		function Mclk_Update(g_idx){
			var status = document.forms['frm'].elements["状態"].value;
			let name = $('#name').val();
			let phonenum1 = $('#phonenum1').val();
			let postcode1 = $('#postcode1').val();
			let postcode2 = $('#postcode2').val();
			let address1 = $('#address1').val();
			let address2 = $('#address2').val();
			let submit_punc = '';
			let submit_swal = '';
			// 必須項目未入力ならエラー表示
			if(name == '' || phonenum1 == '' || phonenum1 == null || postcode1 == '' || postcode2 == '' || address1 == '' || address2 == '') {
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
				Swal.fire
				(
					{
						type: 'error', 
						title: '以下の必須項目が未入力です', 
						html: submit_swal
					}
				);
			}else if(status == 9){
				//伝票発行済みの場合はアラートで確認
				Swal.fire({
					title: '発送準備中のデータがあります',
					text: '修正後は必ずJEMTC担当者にご連絡ください',
					type: 'warning',
					showCancelButton: true,
					confirmButtonText: '更新',
					cancelButtonText: 'キャンセル'  , 
					allowOutsideClick : false   //枠外クリックは許可しない
					}).then((result) => {
					if (result.value) {
						document.forms['frm'].action = './rice_sql.php?do=update&idx='+g_idx;
						document.forms['frm'].submit();
						return false;
					}else{
						return false;
					}
				});
			}else{
				document.forms['frm'].action = './rice_sql.php?do=update&idx='+g_idx;
				document.forms['frm'].submit();
				return false;
			}
		}
		function Mclk_Cancel(g_idx){
			var status = document.forms['frm'].elements["状態"].value;
			if(status == 9){
				//伝票発行済みの場合はアラートで確認
				Swal.fire({
					title: '発送準備中のデータがあります',
					text: 'キャンセル登録後は必ずJEMTC担当者にご連絡ください',
					type: 'warning',
					showCancelButton: true,
					confirmButtonText: 'ｷｬﾝｾﾙ登録',
					cancelButtonText: 'やめる'  , 
					allowOutsideClick : false   //枠外クリックは許可しない
					}).then((result) => {
					if (result.value) {
						document.forms['frm'].action = './rice_sql.php?do=cancel&idx='+g_idx;
						document.forms['frm'].submit();
						return false;
					}else{
						return false;
					}
				});
			}else{
				//伝票発行済みの場合はアラートで確認
				Swal.fire({
					title: 'キャンセル登録します',
					text: '次回以降すべての配送をキャンセルします',
					type: 'warning',
					showCancelButton: true,
					confirmButtonText: 'ｷｬﾝｾﾙ登録',
					cancelButtonText: 'やめる'  , 
					allowOutsideClick : false   //枠外クリックは許可しない
					}).then((result) => {
					if (result.value) {
						document.forms['frm'].action = './rice_sql.php?do=cancel&idx='+g_idx;
						document.forms['frm'].submit();
						return false;
					}else{
						return false;
					}
				});
			}
		}
		function Set_tanka(i){
			var category = document.forms['frm'].elements["コース"+i].value;
			var weight = document.forms['frm'].elements["量"+i].value;
			if(category == "わんぱくコース" && (weight == 5 || weight == 15 || weight == 25)){
				//伝票発行済みの場合はアラートで確認
				Swal.fire({
					title: 'エラー',
					text: 'わんぱくコースは10kg、20kg、30kgのいずれかを選択してください。',
					type: 'warning',
					showCancelButton: false,
					confirmButtonText: 'OK',
					allowOutsideClick : false   //枠外クリックは許可しない
					}).then((result) => {
					if (result.value) {
						document.forms['frm'].elements["金額"+i].value = 0;
					}
				});
			}else{
				var g_tanka = json_tanka_list[category][weight];
				document.forms['frm'].elements["金額"+i].value = g_tanka;
			}
		}
		function Copy_data(g_columm, i){
			var set_data = document.forms['frm'].elements[g_columm+i].value;
			var set_weight = document.forms['frm'].elements["量"+i].value;
			var set_tanka = document.forms['frm'].elements["金額"+i].value;
			var max_row = document.forms['frm'].elements["最大行"].value;
			Swal.fire({
				title: g_columm + 'のデータを一括で変更します',
				text: '次回以降すべての配送データを変更します。※データが反映されていることを確認後、更新ボタンを押してください。',
				type: 'warning',
				showCancelButton: true,
				confirmButtonText: '反映',
				cancelButtonText: 'やめる'  , 
				allowOutsideClick : false   //枠外クリックは許可しない
				}).then((result) => {
				if (result.value) {
					for(j=i+1; j<=max_row; ++j){
						document.forms['frm'].elements[g_columm+j].value = set_data;
						if(g_columm == "コース"){
							document.forms['frm'].elements["量"+j].value = set_weight;
							document.forms['frm'].elements["金額"+j].value = set_tanka;
						}
					}
					return false;
				}else{
					return false;
				}
			});
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
		<h1>精米倶楽部顧客情報</h1>
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
					<? if(isset($g_idx)){
						//----- データ抽出
						//テーブル項目取得
						$table = "php_rice_subsucription";
						$table_p = "php_rice_personal_info";
						$collist = $dba->mysql_get_collist($db, $table_p);
						$arr_keylist = array("名前", "電話番号1", "電話番号2", "郵便番号１", "郵便番号２", "都道府県", "ご住所", "建物名", "メールアドレス");
						//データを取得
						$query = "
							 SELECT A.idxnum, A.name, A.address1, A.address2, A.address3, A.postcd1, A.postcd2, A.phonenum1, A.phonenum2
							 , A.email, B.remarks, A.receipt, A.ruby, B.delflg, A.sales_way, A.introduction
							 , B.category, B.weight, B.tanka as s_tanka, B.date_s, B.date_e
							 FROM php_rice_subscription B
							 LEFT OUTER JOIN php_rice_personal_info A ON A.idxnum=B.personal_idxnum 
							 WHERE B.subsc_idxnum='".$g_idx."' 
							";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs = $db->query($query))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						while ($row = $rs->fetch_array()) { ?>
							<table class="table">
								<div class="col-lg-12">
									<?
										//二重登録防止セッション定義
										$check = md5(uniqid(rand(), true));
										$_SESSION['check2'] = $check;
										$comm->ouputlog("SESSION＝".$_SESSION['check2'], $prgid, SYS_LOG_TYPE_INFO);
									?>
									<input name="check2" type="hidden" value="<?php echo $check ?>">
								</div>
								<? if($row['delflg'] == 1){ ?>
									<div class="col-lg-12">
										<p style="font-size:1.5em; color:red; font-weight:bold;">削除済のデータです</p>
									</div>
								<? } ?>
								<?if($row['sales_way'] == "introduction"){ ?>
									<div class="col-lg-12">
										<h4>紹介者様情報</h4>
										<hr>
										<!-- 名前入力 -->
										<div class="col-lg-6">
											<div class="form-group">
												<label>氏名（漢字）（16文字以内）</label>
												<input type="text" id="intro_name" class="form-control" name="紹介者" value="<?php echo $row['introduction'] ?>">
											</div>
										</div>
									</div>
								<? } ?>
								<div class="col-lg-12">
									<h4>お客様情報</h4>
									<hr>
									<!-- 名前入力 -->
									<div class="col-lg-6">
										<div class="form-group">
											<img src="./images/hisu.gif" alt="必須" >&nbsp;
											<label>氏名（漢字）（16文字以内）</label>
											<input type="text" style="display:none;" name="インデックス" value="<?php echo $row['idxnum'] ?>">
											<input type="text" id="name" class="form-control required_form" name="名前" value="<?php echo $row['name'] ?>">
										</div>
									</div>
									<div class="col-xs-7"></div>
									<!-- 電話番号1 -->
									<div class="col-lg-6">
										<div class="form-group">
											<img src="./images/hisu.gif" alt="必須" >
											<label>電話番号</label>
											<p><strong><font color="red">※ハイフンは入力しなくても自動で挿入されます</font></p>
											<p><strong><font color="red">※送り状伝票に記載するため、繋がりやすい番号を記入してください。</font></p>
											<input type="tel" maxlength="13" id="phonenum1" class="form-control required_form" name="電話番号1" value="<?php echo $row['phonenum1'] ?>">
										</div>
									</div>
									<!-- 電話番号2 -->
									<div class="col-lg-6">
										<div class="form-group">
											<label>電話番号２</label>
											</br></br></br></br>
											<input type="tel" maxlength="13" id="phonenum2" class="form-control" name="電話番号2" value="<?php echo $row['phonenum2'] ?>">
										</div>
									</div>
									<!-- メールアドレス -->
									<div class="col-lg-12">
										<div class="form-group">
											<img src="./images/hisu.gif" alt="必須" >
											<label>メールアドレス</label>
											<input type="text" id="email" name="メールアドレス" value="<?php echo $row['email'] ?>" class="form-control">
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
											<input type="tel" name="郵便番号１" value="<?php echo $row['postcd1'] ?>" class="form-control required_form" maxlength="3" id="postcode1">
										</div>
									</div>
									<div class="col-lg-3">
										<div class="form-group">
											<input type="tel" name="郵便番号２" value="<?php echo $row['postcd2'] ?>" class="form-control required_form" maxlength="4" id="postcode2">
										</div>
									</div>
									<br><br><br>
									<div class="col-lg-8">
										<div class="form-group">
											<img src="./images/hisu.gif" alt="必須" >
											<label>都道府県</label>
											<select id="address1" name="都道府県" class="form-control required_form">
											<?php
												$comm->ouputlog("都道府県一覧", $prgid, SYS_LOG_TYPE_DBUG);
												foreach($p_address1list as $val) {
													$comm->ouputlog("都道府県=". $val[0], $prgid, SYS_LOG_TYPE_DBUG);
													if ($row['address1'] == $val[0]) {
														echo "<option value=" . $val[0] . " selected >" . $val[0];
													}
													else {
														echo "<option value=" . $val[0] . ">" . $val[0];
													}
												}
											?>
											</select>
										</div>
									</div>
									<div class="col-lg-12">
										<div class="form-group">
											<img src="./images/hisu.gif" alt="必須" >
											<label>住所</label>
											<input type="text" id="address2" class="form-control required_form" name="ご住所" value="<?php echo $row['address2'] ?>">
										</div>
									</div>
									<div class="col-lg-12">
										<div class="form-group">
											<label>マンション名</label>
											<input type="text" name="建物名" value="<?php echo $row['address3'] ?>" class="form-control" id="address3">
										</div>
									</div>
									<!--備考-->
									<div class="col-lg-12">
										<div class="form-group">
											<label>備考（その他希望内容）</label>
											<textarea name="注文時備考" rows="3" cols="70" class="form-control"><?php echo $row['remarks'] ?></textarea>
										</div>
									</div>
									<!--元データ-->
									<div class="col-lg-8" style="display:none">
										<? foreach($arr_keylist as $val){ ?>
											<input type="text" name="T<?= $val.$row['subsc_idxnum']; ?>" value="<? echo $row[$collist[$val]] ?>"><br>
										<?  }?>
										<textarea name="T注文時備考"><? echo $row['remarks'] ?></textarea>
									</div>
								</div>
								<div class="col-lg-12">
									<h4>配送予定</h4>
									<hr>
									<table class="table table-bordered" id="tbl">
										<tr class="info">
											<td style="text-align:center; vertical-align:middle;" class="col-lg-2"><strong>時期</strong></td>
											<td style="text-align:center; vertical-align:middle;" class="col-lg-2"><strong>状況</strong></td>
											<td style="text-align:center; vertical-align:middle;" class="col-lg-2"><strong>コース<br>量</strong></td>
											<td style="text-align:center; vertical-align:middle;" class="col-lg-2"><strong>配送日<br>配送時間<br><font color="red" size="0.7em">※配送日の変更は基本的に不可ですが<br>予定日後3日間のみ選択可能です</font></strong></td>
											<td style="text-align:center; vertical-align:middle;" class="col-lg-2"><strong>金額（半角数字）</strong></td>
											<td style="text-align:center; vertical-align:middle;" class="col-lg-2"><strong>佐川伝票番号</strong></td>
										</tr>
										<?
										$query2 = "
											SELECT 
											 C.category, C.tanka, C.weight, C.stopflg, C.output_flg, C.slipnumber, C.ship_date, C.delivery_date, C.specified_times, C.ship_idxnum
											 , CASE
												  WHEN C.receive_date <>'0000-00-00' THEN CONCAT('配達完了<br>(',date_format(C.receive_date, '%y/%c/%e'),')') 
												  WHEN C.ship_date <>'0000-00-00 00:00:00' THEN CONCAT('配送中<br>(',date_format(C.ship_date, '%y/%c/%e'),')') 
												  WHEN C.output_flg > 0 THEN '配送準備中'
												  WHEN C.stopflg > 0 THEN '休止'
												  ELSE '注文受付'
											 END as status
											 FROM php_rice_shipment C
											 WHERE C.subsc_idxnum='".$g_idx."' 
											 ORDER BY C.delivery_date
											";
										$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
										$comm->ouputlog($query2, $prgid, SYS_LOG_TYPE_DBUG);
										if (!($rs2 = $db->query($query2))) {
											$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
										}
										$i = 0;
										$status = 0;
										$next = 0;
										while ($row2 = $rs2->fetch_array()) { 
											if($row2['status'] <> "注文受付" && mb_substr($row2['status'],0,4) <> "配達完了"){
												$status = 9;
											}
											if($next == 1){
												$next = 2;
											}if($next == 0 && $row2['status'] == "注文受付"){
												$next = 1;
											}
											$min_date = date('Y-m-26', strtotime($row2['delivery_date']));
											$max_date = date('Y-m-d', strtotime('+3 days', strtotime($min_date)));
											?>
											<? if($row2['status'] == "注文受付"){ ?>
												<tr>
													<td style="vertical-align:middle; text-align:center">
														<?= date('Y年n月', strtotime($row2['delivery_date'])); ?>
													</td>
													<td style="vertical-align:middle; text-align:center">
														<?= $row2['status']; ?>
													</td>
													<td style="vertical-align:middle; text-align:right">
														<select name="コース<?= $i; ?>" onchange="Set_tanka(<?= $i; ?>)">
															<? foreach($category_list as $val){ ?>
																<option value="<?= $val; ?>" <? if($val == $row2['category']){echo "selected='selected'";} ?> ><?= $val; ?></option>
															<? } ?>
														</select>
														<select name="量<?= $i; ?>" onchange="Set_tanka(<?= $i; ?>)">
															<? foreach($weight_list as $key => $val){ ?>
																<option value="<?= $key; ?>" <? if($key == $row2['weight']){echo "selected='selected'";} ?> ><?= $val; ?></option>
															<? } ?>
														</select><br>
														<? if($next == 1 && $p_staff == "田村"){ ?>
															<input type="button" class="button-blue" value="一括反映" onclick="Copy_data('コース', <?= $i; ?>)">
														<? } ?>
													</td>
													<td style="vertical-align:middle; text-align:right">
														<input type="date" name="配送日<?= $i; ?>" value="<?= $row2['delivery_date']; ?>" min="<?= $min_date; ?>" max="<?= $max_date; ?>">
														<select name="到着指定時間帯<?= $i; ?>">
															<? foreach($timelist_r as $key => $val){ ?>
																<option value="<?= $val; ?>" <? if($val == $row2['specified_times']){echo "selected='selected'";} ?> ><?= $key; ?></option>
															<? } ?>
														</select><br>
														<? if($next == 1 && $p_staff == "田村"){ ?>
															<input type="button" class="button-blue" value="一括反映" onclick="Copy_data('到着指定時間帯', <?= $i; ?>)">
														<? } ?>
													</td>
													<td style="text-align:right; vertical-align:middle;">
														<input type="text" pattern="^[0-9]+$" name="金額<? echo $i ?>" size="6" value="<? echo $row2['tanka'] ?>"> 円
													</td>
													<td style="text-align:right; vertical-align:middle;">
														<input type="text" pattern="^[0-9]+$" name="伝票番号<? echo $i ?>" size="16" value="<? echo $row2['slipnumber'] ?>">
													</td>
													<td style="display:none">
														<input type="text" name="配送インデックス<?= $i; ?>" value="<? echo $row2['ship_idxnum'] ?>">
														<input type="text" name="Tコース<?= $i; ?>" value="<? echo $row2['category'] ?>">
														<input type="text" name="T量<?= $i; ?>" value="<? echo $row2['weight'] ?>">
														<input type="text" name="T配送日<?= $i; ?>" value="<? echo $row2['delivery_date'] ?>">
														<input type="text" name="T到着指定時間帯<?= $i; ?>" value="<? echo $row2['specified_times'] ?>">
														<input type="text" name="T金額<?= $i; ?>" value="<? echo $row2['tanka'] ?>">
														<input type="text" name="T伝票番号<?= $i; ?>" value="<? echo $row2['slipnumber'] ?>">
														<input type="text" name="end<?= $i; ?>">
													</td>
											<? }else{ ?>
												<? if($row2['status'] == "配送準備中" || mb_substr($row2['status'],0,3) == "配送中"){ ?>
													<tr style="background-color:#ffff00;">
												<? }else{ ?>
													<tr style="background-color:#a9a9a9;">
												<? } ?>
													<td style="vertical-align:middle; text-align:center">
														<?= date('Y年n月', strtotime($row2['delivery_date'])); ?>
													</td>
													<td style="vertical-align:middle; text-align:center">
														<?= $row2['status']; ?>
													</td>
													<td style="vertical-align:middle; text-align:right">
														<? echo $row2['category']; ?>
														<? echo $row2['weight']; ?>kg
													</td>
													<td style="vertical-align:middle; text-align:right">
														<? echo date('Y/n/j',strtotime($row2['delivery_date'])); ?>
														<? echo $timelist_r[$row2['specified_times']]; ?>
													</td>
													<td style="text-align:right; vertical-align:middle;">
														<? echo number_format($row2['tanka']); ?>円
													</td>
													<td style="text-align:right; vertical-align:middle;">
														<a href="https://k2k.sagawa-exp.co.jp/p/web/okurijosearch.do?okurijoNo=<?= $row2['slipnumber'] ?>" target="_blank"><?= $row2['slipnumber'] ?></a>
													</td>
											<? } ?>
											</tr>
										<?  	++$i;
										} ?>
									</table>
								</div>
								<input type="text" name="最大行" value="<?= $i; ?>" style="display:none">
								<input type="text" name="状態" value="<?= $status; ?>" style="display:none">
								<div class="button_div">
									<!--登録・戻るボタン-->
									<div class="col-lg-4" style="text-align:right;">
										<div class="form-group" style="text-align:right;">
											<input type="button" value="更新" class="btn-info btn-border" onclick="javascript:Mclk_Update(<? echo $g_idx ?>)">
										</div>
									</div>
									<? if($p_staff == "田村"){ ?> 
									<div class="col-lg-4" style="text-align:right;">
										<div class="form-group" style="text-align:right;">
											<input type="button" value="ｷｬﾝｾﾙ登録" class="btn-info btn-border" onclick="javascript:Mclk_Cancel(<? echo $g_idx ?>)">
										</div>
									</div>
									<? } ?> 
									<div class="col-lg-4" style="text-align:right;">
										<div class="form-group" style="text-align:right;">
											<a href="telorder_list.php"><input type="button" value="閉じる" class="btn-info btn-border" onClick="window.close()"></a>
										</div>
									</div>
									</br></br></br></br>
								</div>
							</table>
						<? } ?>
					<? }else{ ?>
						<p>顧客が選択されていません。</p>
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