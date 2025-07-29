<?php
//==================================================================================================
// ■機能概要
//   ・精米倶楽部infomail一覧
//==================================================================================================
//----------------------------------------------------------------------------------------------
// 初期処理
//----------------------------------------------------------------------------------------------
//ログイン確認(COOKIEを利用)
if((!$_COOKIE['j_office_Uid']) or (!$_COOKIE['j_office_Pwd'])) {
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

	//実行プログラム名取得
	$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
	$prgname = "精米倶楽部infomail一覧";
	$prgmemo = "　精米倶楽部infomailを一覧で確認できます。";
	$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);

	//本日日付
	$today = date('Y/m/d');
	$comm->ouputlog("today=" . $today, $prgid, SYS_LOG_TYPE_DBUG);

	//1週間前の日付
	$weekago = date("Y-m-d",strtotime("-6 days"));

	//担当者確認(COOKIEを利用)
	if ($_COOKIE['con_perf_compcd']) {
		$p_compcd = $_COOKIE['con_perf_compcd'];
	}
	
	//----------------------------------------------------------------------------------------------
	// 引数取得処理
	//----------------------------------------------------------------------------------------------

	//開催日付(開始)
	if (isset($_POST['開催日付１'])) {
		$p_date1 = $_POST['開催日付１'];
	}
	else {
		// ================================================
		// ■　□　■　□　カレンダーマスタ取得　■　□　■　□
		// ================================================
		//----- データ抽出
		$query = "SELECT  MIN(A.date) as minDate, MAX(A.date) as maxDate";
		$query .= " FROM php_calendar A ";
		$query .= " WHERE A.week = ";
		$query .= "  (SELECT B.week from php_calendar B where B.date = " . sprintf("'%s'", $today) . ")";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		//データ設定
		while ($row = $rs->fetch_array()) {
			$minDate = $row['minDate'];
			$maxDate = $row['maxDate'];
		}
		//データ取得
		$p_date1 = $minDate;
	}
	//開催日付(終了)
	if (isset($_POST['開催日付２'])) {
		$p_date2 = $_POST['開催日付２'];
	}
	else {
		//データ設定
		$p_date2 = $maxDate;
	}
	//担当者
	if (isset($_POST['担当者'])) {
		$p_staff = $_POST['担当者'];
		setcookie ('con_perf_staff', '', time()-3600);
		setcookie ('con_perf_staff', $p_staff, time() + 24 * 60 * 60 * 365);
	}
	else {
		$p_staff = "";
		//担当者確認(COOKIEを利用)
		if ($_COOKIE['con_perf_staff']) {
			$p_staff = $_COOKIE['con_perf_staff'];
		}
	}
	$c_staff = $_COOKIE['con_perf_staff'];
	$comm->ouputlog("担当者=". $p_staff, $prgid, SYS_LOG_TYPE_DBUG);
	foreach($_POST as $key=>$val) {
		$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_DBUG);
	}
	$p_sendlist[][0] = $p_staff;
	if($p_compcd <> "T"){
		//----------------------------------------------------------------------------------------------
		// システムデータの取得
		//----------------------------------------------------------------------------------------------
		//販売担当者一覧の取得
		$comm->ouputlog("連絡担当者一覧の取得", $prgid, SYS_LOG_TYPE_INFO);
		if (!$rs = $comm->getstaff($db, 14)) {
			$comm->ouputlog("データ取得エラー", $prgid, SYS_LOG_TYPE_DBUG);
		}
		while ($row = $rs->fetch_array()) {
			$p_sendlist[] = $row;
			$comm->ouputlog("担当者=". $row[0], $prgid, SYS_LOG_TYPE_DBUG);
		}
	}
	//サポートセンターアルバイトを担当者に追加
	$query = "SELECT  A.staff ";
	$query .= " FROM php_l_user A ";
	$query .= " WHERE A.companycd = 'S' ";
	$query .= " AND staff NOT LIKE '%テスト%' ";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$p_sendlist[][0] = $row['staff'];
	}
	$p_sendlist[][0] = "島村";
	$p_sendlist[][0] = "ジェネシス";

	//対応中件数の取得
		$sta02 = $db->query("SELECT COUNT(*) AS num_sta02 FROM php_info_mail A WHERE (status = '3' || status = '2') AND delflg=0");
		$row_sta02 = $sta02->fetch_assoc();
	
	//未連絡件数の取得
		$sta00 = $db->query("SELECT COUNT(*) AS num_sta00 FROM php_info_mail A WHERE status = '0' AND delflg=0");
		$row_sta00 = $sta00->fetch_assoc();
		
	//対応状況の絞り込み
	$status ='';
	$chk00 = 1;
	$chk02 = 1;
	$chk03 = 1;
	$chk08 = 1;
	$chk09 = 1;
	$search_name = "";
	$g_search_name = "";
	$search_ruby = "";
	$g_search_ruby = "";
	$search_phone = "";
	$search_phone1 = "";
	$search_phone2 = "";
	$search_phone3 = "";
	$search_company = "";
	$search_staff = "";
	$search_idx = "";
	if(isset($_POST['検索'])){
		if(!isset($_POST['未連絡'])){
			$status .= " AND A.status <> 0";
			$chk00 = 0;
		}
		if(!isset($_POST['対応中'])){
			$status .= " AND A.status <> 2";
			$chk02 = 0;
		}
		if(!isset($_POST['確認待'])){
			$status .= " AND A.status <> 8";
			$chk08 = 0;
		}
		if(!isset($_POST['完了'])){
			$status .= " AND A.status <> 9";
			$chk09 = 0;
		}
		if(!isset($_POST['返信有'])){
			$status .= " AND A.status <> 3";
			$chk03 = 0;
		}
		if(isset($_POST['検索名前'])){
			$g_search_name = $_POST['検索名前'];
			if(mb_strpos($g_search_name, "　") === false){
				$search_name = $g_search_name;
			}else{
				$search_name = mb_substr($g_search_name , 0, mb_strpos($g_search_name, "　"))."%".mb_substr($g_search_name, mb_strpos($g_search_name, "　")+1);
			}
		}
		if(isset($_POST['検索かな'])){
			$g_search_ruby = $_POST['検索かな'];
			if(mb_strpos($g_search_ruby, "　") === false){
				$search_ruby = $g_search_ruby;
			}else{
				$search_ruby = mb_substr($g_search_ruby , 0, mb_strpos($g_search_ruby, "　"))."%".mb_substr($g_search_ruby, mb_strpos($g_search_ruby, "　")+1);
			}
		}
		if(isset($_POST['検索会社名'])){
			$search_company = $_POST['検索会社名'];
		}
		if(isset($_POST['検索日付'])){
			$search_date = $_POST['検索日付'];
		}
		if(isset($_POST['問合せ日付１'])){
			$search_q_date1 = $_POST['問合せ日付１'];
		}
		if(isset($_POST['問合せ日付２'])){
			$search_q_date2 = $_POST['問合せ日付２'];
		}
		if($_POST['検索電話番号1'] <> "" || $_POST['検索電話番号2'] <> "" || $_POST['検索電話番号3'] <> ""){
			$search_phone = $_POST['検索電話番号1']."%".$_POST['検索電話番号2']."%".$_POST['検索電話番号3'];
			$search_phone1 = $_POST['検索電話番号1'];
			$search_phone2 = $_POST['検索電話番号2'];
			$search_phone3 = $_POST['検索電話番号3'];
		}
		if(isset($_POST['検索担当者'])){
			$search_staff = $_POST['検索担当者'];
		}
		if(isset($_POST['検索番号'])){
			$search_idx = $_POST['検索番号'];
		}
		setcookie ('chk00', '', time()-3600);
		setcookie ('chk00', $chk00);
		setcookie ('chk02', '', time()-3600);
		setcookie ('chk02', $chk02);
		setcookie ('chk08', '', time()-3600);
		setcookie ('chk08', $chk08);
		setcookie ('chk09', '', time()-3600);
		setcookie ('chk09', $chk09);
		setcookie ('chk03', '', time()-3600);
		setcookie ('chk03', $chk03);
		setcookie ('search_name', '', time()-3600);
		setcookie ('search_name', $search_name);
		setcookie ('g_search_name', '', time()-3600);
		setcookie ('g_search_name', $g_search_name);
		setcookie ('search_ruby', '', time()-3600);
		setcookie ('search_ruby', $search_ruby);
		setcookie ('g_search_ruby', '', time()-3600);
		setcookie ('g_search_ruby', $g_search_ruby);
		setcookie ('search_phone', '', time()-3600);
		setcookie ('search_phone', $search_phone);
		setcookie ('search_phone1', '', time()-3600);
		setcookie ('search_phone1', $search_phone1);
		setcookie ('search_phone2', '', time()-3600);
		setcookie ('search_phone2', $search_phone2);
		setcookie ('search_phone3', '', time()-3600);
		setcookie ('search_phone3', $search_phone3);
		setcookie ('search_company', '', time()-3600);
		setcookie ('search_company', $search_company);
		setcookie ('search_date', '', time()-3600);
		setcookie ('search_date', $search_date);
		setcookie ('search_q_date1', '', time()-3600);
		setcookie ('search_q_date1', $search_q_date1);
		setcookie ('search_q_date2', '', time()-3600);
		setcookie ('search_q_date2', $search_q_date2);
		setcookie ('search_staff', '', time()-3600);
		setcookie ('search_staff', $search_staff);
		setcookie ('search_idx', '', time()-3600);
		setcookie ('search_idx', $search_idx);
	}else if(isset($_COOKIE['chk00'])){
		$chk00 = $_COOKIE['chk00'];
		$chk02 = $_COOKIE['chk02'];
		$chk03 = $_COOKIE['chk03'];
		$chk08 = $_COOKIE['chk08'];
		$chk09 = $_COOKIE['chk09'];
		$search_name = $_COOKIE['search_name'];
		$g_search_name = $_COOKIE['g_search_name'];
		$search_ruby = $_COOKIE['search_ruby'];
		$g_search_ruby = $_COOKIE['g_search_ruby'];
		$search_phone = $_COOKIE['search_phone'];
		$search_phone1 = $_COOKIE['search_phone1'];
		$search_phone2 = $_COOKIE['search_phone2'];
		$search_phone3 = $_COOKIE['search_phone3'];
		$search_company = $_COOKIE['search_company'];
		$search_date = $_COOKIE['search_date'];
		$search_q_date1 = $_COOKIE['search_q_date1'];
		$search_q_date2 = $_COOKIE['search_q_date2'];
		$search_staff = $_COOKIE['search_staff'];
		$search_idx = $_COOKIE['search_idx'];
		if($chk00==0){
			$status .= " AND A.status <> 0";
		}
		if($chk02==0){
			$status .= " AND A.status <> 2";
		}
		if($chk09==0){
			$status .= " AND A.status <> 9";
		}
		if($chk08==0){
			$status .= " AND A.status <> 8";
		}
		if($chk03==0){
			$status .= " AND A.status <> 3";
		}
	}else{
		$status = " AND A.status <>9";
		$status .= " AND A.status <>8";
		$chk08 = 0;
		$chk09 = 0;
		$search_name = "";
		$g_search_name = "";
		$search_ruby = "";
		$g_search_ruby = "";
		$search_phone = "";
		$search_phone1 = "";
		$search_phone2 = "";
		$search_phone3 = "";
		$search_company = "";
		$search_date = "";
		$search_q_date1 = "";
		$search_q_date2 = "";
		$search_staff = "";
		$search_idx = "";
	}
	if($g_search_name == "" && $g_search_ruby == "" && $search_company == "" && $search_date == "" && $search_phone == "" && $search_staff == "" && $search_idx == "" && $search_q_date1 == "" && $search_q_date2 == ""){
		$check = 0;
	}else{
		$check = 1;
	}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<meta http-equiv="Refresh" content="60">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
<!--sweetalert2-->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@8"></script>

  <title>infoメールお問い合わせ一覧</title>
  
  <!-- cascading style seet-->
  <link rel="stylesheet" type="text/css" href="css/bootstrap.css">


	<style type="text/css">  
	/*コンテナー（HPを囲むブロック）
	---------------------------------------------------------------------------*/
	#container {
		text-align: left;
		margin-right: auto;
		margin-left: auto;
		background-color: #FFFFFF;
		padding-right: 4px;
		padding-left: 4px;
	}
	body {
		color: #333333;
		background-color: #FFFFFF;
		margin: 0px;
		padding: 0px;
		text-align: center;
		font: 70%/2 "メイリオ", Meiryo, "ＭＳ Ｐゴシック", Osaka, "ヒラギノ角ゴ Pro W3", "Hiragino Kaku Gothic Pro";
	}
	#header {
		position: fixed;	/* ヘッダーを固定する */
		margin: 10x;
		padding: 10px;
		color: #f0ffff;
		background-color: #dcdcdc;
		padding: 0px;
		text-align: center;
		width:100%;
		height:180px; 
	}
	#main{
		width: 1200px;	/*コンテンツ幅*/
		text-align: center;
		margin: auto;
		padding-top: 200px;
		padding-bottom: 200px;
		overflow: auto; 	/* コンテンツの表示を自動に設定（スクロール） */
		background-color: white;
		color:gray;
	}
	#note {
		text-align: right;
		font-size:16px;
		width: 1200px;
		margin-right: auto;
		margin-left: auto;
	}
	#search {
		text-align: right;
		font-size:12px;
		width: 1050px;
		margin-right: auto;
		margin-left: auto;
	}
	#checkwait {
		text-align: right;
		font-size:12px;
		width: 1050px;
		margin-right: auto;
		margin-left: auto;
	}
	#note2 {
		text-align: right;
		font-size:12px;
		width: 1050px;
		margin-right: auto;
		margin-left: auto;
	}
	h2{
		font-size:36px;
		text-decoration: underline;
	}
	
	/*返信内容表示 td*/
	.correcont_td{
		width: 136px;
		max-height:64px;
		overflow-y:auto; 
		padding: 4px;
	}
	.sta00{
		background-color: #fff2cc;
	}
	.sta03{
		background-color: #ffdab9;
	}
	.sta08{
		background-color: #eaffff;
	}
	.sta09{
		background-color: #a9a9a9;
	}
	.ordinary{
		background-color: white;
	}
	.danger{
		background-color: #fdd5dc;
	}
	
	
	/* --- テーブル --- */
	table.tbh{
		margin:0 auto;
		border : 1px solid black;
	}
	/* --- テーブルヘッダーセル（th） --- */
	th.tbd_th_1 {
	padding: 20px 10px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: 0067c0; /* 見出しセルの背景色 */
	border-bottom : 1px solid black;
	border-top : 1px solid black;
	text-align: center;
	line-height: 130%;
	font-weight:bolder
	white-space: nowrap;
	width: 45px;
	}
	th.tbd_th_2 {
	padding: 20px 10px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: 0067c0; /* 見出しセルの背景色 */
	border-bottom : 1px solid black;
	border-top : 1px solid black;
	text-align: center;
	line-height: 130%;
	font-weight:bolder
	white-space: nowrap;
	width: 130px;
	}
	th.tbd_th_3 {
	padding: 20px 10px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: 0067c0; /* 見出しセルの背景色 */
	border-bottom : 1px solid black;
	border-top : 1px solid black;
	text-align: center;
	line-height: 130%;
	font-weight:bolder
	white-space: nowrap;
	width: 75px;
	}
	
	/* --- テーブルデータセル（td） --- */
	td.tbd_td_p1 {
		text-align: center;
		padding: 15px 10px; /* 見出しセルのパディング（上下、左右） */
	}
	td.tbd_td_p2 {
		text-align: center;
		padding: 15px 10px; /* 見出しセルのパディング（上下、左右） */
		width: 30px;
	}
	/*ボタン*/
	.search{
		display: inline-block;
		font-weight: bold;
		padding: 0.25em 0.5em;
		text-decoration: none;
		color: gray;
		background: #ECECEC;
		border-radius: 15px;
		transition: .4s;
	}
	.search:hover {
		background:gray ;
		color: #ECECEC;
	}
	/*検索*/
	#search_detail{
		background: #ECECEC;
		padding: 0.5em;
		margin: 10px 2px 0px 550px;
	}
	table.search_table {
		padding: 0.25em 0.5em;
		width: 600px;
	}
	th.sh_th {
		width: 130px;
		padding: 0.25em 0.5em;
	}
	td.sh_td_c {
		width: 20px;
		padding: 0.25em 0.5em;
	}
	td.sh_td_l {
		width: 450px;
		padding: 0.25em 0.5em;
	}
	table tbody tr:hover{
	  background-color: #A5E4FF;
	}
	/* 登録ボタン */
	.btn-borders.btn-infos.d {
		background:#8b0000;
		border:1px solid #8b0000;
		border-radius: 10px;
		font-weight: bold;
		font-size: 20px;
		color:#fffafa;
		width: 100px;
		height: 40px;
	}
	.btn-borders.btn-infos.d:hover,.btn-border.btn-info.d:active {
		border:4.5px solid #3dff9e;
	}
</style>
	<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<script type="text/javascript">
		var c_staff = '<?php echo $c_staff; ?>';
		//ステータス変更トースト
		window.addEventListener ('DOMContentLoaded', function () {
			var trueflg = localStorage.getItem('mail_check_before_flg');
			if ( trueflg == 1 ) {
				swal("Complete!", "ステータスを確認待ちに変更しました。", "success");
				localStorage.removeItem('mail_check_before_flg');
			}
		} )
		//状態変更
		function Change_Sql ( idx ) {
			var rowINX = 'do=changetop&idxnum='+idx;
			var status = document.getElementById('status' + idx).value; 
			var staff = document.getElementById('correstaf' + idx).value;
			var old_status = document.getElementById('old_status' + idx).value;
			var staff_err_flg = 0;
			var status_err_flg = 0;
			if (status != 0 && status != 2 && status != 9 && status != 8 && status != 3) {
				document.forms['frm'].reset();
			} else {
				$.ajax({
					type: "POST"
					, url: "./infomail_check_sql.php"
					, data: {
						"idx":idx
					}
				//　POST送信成功
				}).done(function(data) {
					var result = JSON.parse(data);
					var get_idx = result[0];
					var get_staff = result[1];
					if ( get_staff != '未選択' && staff != '未選択') {
						if (get_staff != staff) {
							staff_err_flg = 1;
						}
					}
					if ( get_idx != old_status ) {
						status_err_flg = 1;
					}
					if ( status_err_flg == 1) {
						alert('ステータスが変更されています\n現在のステータスを確認してください。');
						location.href = './info_mail.php';
					} else if ( staff_err_flg == 1 ) {
						staff_check = confirm('登録されている職員と異なる職員が設定されようとしています\nよろしいですか？');
						if ( staff_check == true ) {
							document.forms['frm'].action = './info_mail_sql.php?' + rowINX;
							document.forms['frm'].submit();
						} else {
							location.href = './info_mail.php';
						}
					} else {
						document.forms['frm'].action = './info_mail_sql.php?' + rowINX;
						document.forms['frm'].submit();
					}
				});
			}
		}
		//コメント登録
		function Push_jyokyo(idx){
			var rowINX = 'do=ins&idxnum='+idx;
			window.open('./info_mail_form.php?' + rowINX);
		}
		//詳細ページにジャンプ
		function mailDetail(idx) {
			var rowINX = 'idxnum='+idx;
			var status = document.getElementById('status' + idx).value; 
			var staff = document.getElementById('correstaf' + idx).value;
//			if (status != 0 && staff != "未選択" || c_staff == "松本" || c_staff == "田村") {
			if (status != 0 && staff != "未選択") {
				window.open ( "./info_mail_detail.php?"+ rowINX, "_blank", "width=1600, height=1200, scrollbars=yes" );
			} else {
				document.forms['frm'].reset();
			    	//アラート表示
				Swal.fire({
					title: '',
					text: '最初に状況と担当者を登録してください。',
					type: 'error',
					allowOutsideClick : false,   //枠外クリックは許可しない
					onAfterClose : () => {
						//フォーカスを当てる
						focus_code();
					}
				})
			}
		}
		//検索ボタンを表示
		function Show_Search(){
			var check = document.forms['search'].elements['check'].value
			if(check == 0){
				document.getElementById('search_detail').style.display = 'block';
				document.forms['search'].elements['check'].value = 1;
			}else{
				document.getElementById('search_detail').style.display = 'none';
				document.forms['search'].elements['check'].value = 0;
			}
		}
		//検索日付クリア
		function ClearDate(content1,content2) {
			document.forms['search'].elements[content1].value = 0;
			document.forms['search'].elements[content2].value = 0;
		}
		// 連絡不通ボタン
		function tabsence_sql(idx){
			//画面項目設定
 			var rowINX = 'do=tabsence&idxnum='+idx;
			document.forms['frm'].action = './info_mail_sql.php?' + rowINX;
			document.forms['frm'].submit();
		}
	</script> 

</head>
<body>
	<div id="container">
		<div id="header">
			<br><h2>infoお問い合わせ一覧</h2>
	  <!-- 項目①
	  ================================================== -->
			<br>
			<div id="note">
				未連絡：&nbsp;&nbsp;<?php print $row_sta00["num_sta00"]; ?>　件
				対応中：<?php print $row_sta02["num_sta02"]; ?>　件
			</div>
		</div>
		<div id="main">
			<br>
			<form name="search" method="post" action="./info_mail.php">
				<? if($p_compcd <> "T"){ ?>
					<div id="checkwait">
						<input type="button" class="search" OnClick="window.open('./info_mail_checklist.php')" value="確認待ちリスト"><br>
					</div>
				<? } ?>
				<div id="checkwait">
					<input type="button" class="search" OnClick="window.open('./info_mail_input.php')" value="新規登録"><br>
				</div>
				<div id="search">
					<input type="button" class="search" OnClick="Javascript:Show_Search()" value="絞り込み🔎"><br>
					<div id="search_detail" <? if($check == 0){ ?>style="display:none;"<? } ?>>
						<table class="search_table">
							<tr>
								<th class="sh_th">問合せNo</th>
								<td class="sh_td_c"></td>
								<td class="sh_td_l">
									<input type="text" name="検索番号" value="<? echo $search_idx ?>" size="21"><br>
								</td>
							</tr>
							<tr>
								<th class="sh_th">氏名（漢字）</th>
								<td class="sh_td_c"></td>
								<td class="sh_td_l">
									<input type="text" name="検索名前" value="<? echo $g_search_name ?>" size="21"><br>
									※姓と名の間に全角スペースを入れてください。<br>
								</td>
							</tr>
							<tr>
								<th class="sh_th">ふりがな</th>
								<td class="sh_td_c"></td>
								<td class="sh_td_l">
									<input type="text" name="検索かな" value="<? echo $g_search_ruby ?>" size="21"><br>
									※姓と名の間に全角スペースを入れてください。<br>
								</td>
							</tr>
							<tr>
								<th class="sh_th">
									電話番号
								</th>
								<td class="sh_td_c"></td>
								<td class="sh_td_l">
									<input type="text" name="検索電話番号1" size="3" value="<? echo $search_phone1 ?>">-
									<input type="text" name="検索電話番号2" size="4" value="<? echo $search_phone2 ?>">-
									<input type="text" name="検索電話番号3" size="4" value="<? echo $search_phone3 ?>">
								</td>
							</tr>
							<tr>
								<th class="sh_th">会社名</th>
								<td class="sh_td_c"></td>
								<td class="sh_td_l"><input type="text" name="検索会社名" value="<? echo $search_company ?>" size="21"></td>
							</tr>
							<tr>
								<th class="sh_th">担当者</th>
								<td class="sh_td_c"></td>
								<td class="sh_td_l">
									<select name="検索担当者">
										<?php foreach($p_sendlist as $list) {
											if ($search_staff =="" &&  $list[0] == "未選択" || $search_staff == $list[0]) { ?>
												<option value="<? echo $list[0] ?>" selected="selected"><? echo $list[0]; ?></option>
											<? }else{ ?>
												<option value="<? echo  $list[0]?>"><? echo  $list[0]; ?></option>
											<? }
										} ?>
									</select>
								</td>
							</tr>
							<tr>
								<th class="sh_th">問合せ日<input type="button" OnClick="Javascript:ClearDate('問合せ日付１','問合せ日付２')" value="ｸﾘｱ"></th>
								<td class="sh_td_c"></td>
								<td class="sh_td_l">
									<input type="date" name="問合せ日付１" value="<? echo $search_q_date1 ?>">～<input type="date" name="問合せ日付２" value="<? echo $search_q_date2 ?>">
								</td>
							</tr>
							<tr>
								<th class="sh_th">最終更新日<input type="button" OnClick="Javascript:ClearDate('検索日付','')" value="ｸﾘｱ"></th>
								<td class="sh_td_c"></td>
								<td class="sh_td_l">
									<input type="date" name="検索日付" value="<? echo $search_date ?>">
								</td>
							</tr>
							<tr>
								<th class="sh_th">対応状況</th>
								<td class="sh_td_c"></td>
								<td class="sh_td_l">
									<label><input type="checkbox" name="未連絡" <?php if($chk00==1){echo 'checked="checked"';} ?>>未連絡</label>
									<label><input type="checkbox" name="対応中" <?php if($chk02==1){echo 'checked="checked"';} ?>>対応中</label>
									<label><input type="checkbox" name="確認待" <?php if($chk08==1){echo 'checked="checked"';} ?>>確認待</label>
									<label><input type="checkbox" name="完了" <?php if($chk09==1){echo 'checked="checked"';} ?>>完了</label>
									<label><input type="checkbox" name="返信有" <?php if($chk03==1){echo 'checked="checked"';} ?>>返信有</label>
								</td>
							</tr>
						</table>
						<input type="submit" class="search" name="検索" value="検索">
						<br>
						<input type="text" name="check" value="<? echo $check ?>" style="display:none"><br>
					</div>
				</div>
				<div id="note2">
					<span class="sta00">　　　</span>未連絡　<span class="sta03">　　　</span>返信有　<span class="sta08">　　　</span>確認待　<span class="danger">　　　</span>最新の対応が１週間以上前　<span class="sta09">　　　</span>完了
				</div>
			</form>
			<form name="frm" method="post">
				<!-- 一覧テーブル -->
				<table class="tbh" id= "TBL">
				<thead>
					<tr>
						<th class="tbd_th_1">NO.</th>
						<th class="tbd_th_1">状況</th>
						<th class="tbd_th_2">問合せ日時<br>(更新日時)</th>
						<th class="tbd_th_2">お名前<br>（ふりがな）</th>
						<th class="tbd_th_3">都道府県</th>
						<th class="tbd_th_2" title="マウスオンで全文表示">お問い合わせ内容※</th>
						<th class="tbd_th_1">担当者</th>
						<th class="tbd_th_1">緊急度</th>
						<th class="tbd_th_1">種別<br>種別詳細</th>
						<th class="tbd_th_2">最新対応内容</th>
						<th class="tbd_th_2"></th>
					</tr>
				</thead>
				<?php
					//----- データ抽出
					$query = "";
					$query .= " SELECT A.idxnum, A.updcount, A.name, A.insdt, A.upddt, A.ruby, A.company, A.address1";
					$query .= " , A.phonenum, A.email, A.status, A.correstaf, A.question, A.urgency, A.kind, A.kind_detail";
					$query .= " , D.category, D.contents";
					$query .= " FROM php_rice_personal_info A";
					$query .= " left outer join php_rice_mail E ON A.idxnum=E.personal_idxnum ";
					$query .= " left outer join";
					$query .= " (SELECT B.category, B.contents, B.detail_idx, B.idxnum";
					$query .= " FROM php_rice_mail_detail B";
					$query .= " INNER JOIN(";
					$query .= " SELECT idxnum, MAX(detail_idx) as max_detail_idx";
					$query .= " FROM php_rice_mail_detail ";
					$query .= " GROUP BY idxnum ) as C";
					$query .= " ON B.idxnum= C.idxnum";
					$query .= " AND B.detail_idx = C.max_detail_idx) as D";
					$query .= " ON A.idxnum = D.idxnum";
					$query .= " WHERE DATE(A.insdt) = '$today'";
					if($g_search_name <> ""){
						$query .= " AND A.name collate utf8_unicode_ci LIKE '%".$search_name."%'";
					}if($g_search_ruby <> ""){
						$query .= " AND A.ruby collate utf8_unicode_ci LIKE '%".$search_ruby."%'";
					}if($search_phone <> ""){
						$query .= " AND A.phonenum LIKE '%".$search_phone."%'";
					}if($search_company <> ""){
						$query .= " AND A.company LIKE '%".$search_company."%'";
					}if($search_staff <> "未選択" && $search_staff <> ""){
						$query .= " AND A.correstaf = '".$search_staff."'";
					}if($search_idx <> ""){
						$query .= " AND A.idxnum = ".$search_idx;
					}if($search_date <> 0){
						$query .= " AND DATE(A.upddt)= '".date('Y-m-d', strtotime($search_date))."'";
					}if($search_q_date1 <> 0){
						$query .= " AND DATE(A.insdt)>= '".date('Y-m-d', strtotime($search_q_date1))."'";
					}if($search_q_date2 <> 0){
						$query .= " AND DATE(A.insdt)<= '".date('Y-m-d', strtotime($search_q_date2))."'";
					}
					$query .= " AND A.delflg =0";
					$query .= $status;
					$query .= " UNION ALL SELECT A.idxnum, A.updcount, A.name, A.insdt, A.upddt, A.ruby, A.company";
					$query .= " , A.address1, A.phonenum, A.email, A.status, A.correstaf, A.question, A.urgency, A.kind, A.kind_detail";
					$query .= " , D.category, D.contents";
					$query .= " FROM php_rice_personal_info A";
					$query .= " left outer join php_rice_mail E ON A.idxnum=E.personal_idxnum ";
					$query .= " left outer join";
					$query .= " (SELECT B.category, B.contents, B.detail_idx, B.idxnum";
					$query .= " FROM php_rice_mail_detail B";
					$query .= " INNER JOIN(";
					$query .= " SELECT idxnum, MAX(detail_idx) as max_detail_idx";
					$query .= " FROM php_rice_mail_detail ";
					$query .= " GROUP BY idxnum ) as C";
					$query .= " ON B.idxnum= C.idxnum";
					$query .= " AND B.detail_idx = C.max_detail_idx) as D";
					$query .= " ON A.idxnum = D.idxnum";
					$query .= " WHERE  DATE(A.insdt)< '$today' ";
					if($search_name <> ""){
						$query .= " AND A.name collate utf8_unicode_ci LIKE '%".$search_name."%'";
					}if($search_ruby <> ""){
						$query .= " AND A.ruby collate utf8_unicode_ci LIKE '%".$search_ruby."%'";
					}if($search_phone <> ""){
						$query .= " AND A.phonenum LIKE '%".$search_phone."%'";
					}if($search_company <> ""){
						$query .= " AND A.company LIKE '%".$search_company."%'";
					}if($search_staff <> "未選択" && $search_staff <> ""){
						$query .= " AND A.correstaf = '".$search_staff."'";
					}if($search_idx <> ""){
						$query .= " AND A.idxnum = ".$search_idx;
					}if($search_date <> 0){
						$query .= " AND DATE(A.upddt)= '".date('Y-m-d', strtotime($search_date))."'";
					}if($search_q_date1 <> 0){
						$query .= " AND DATE(A.insdt)>= '".date('Y-m-d', strtotime($search_q_date1))."'";
					}if($search_q_date2 <> 0){
						$query .= " AND DATE(A.insdt)<= '".date('Y-m-d', strtotime($search_q_date2))."'";
					}
					$query .= $status;
					$query .= " AND A.delflg =0";
					$query .= " ORDER BY status='3' DESC, upddt DESC, idxnum DESC ";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($query))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$i = 0;
					$category = "";
					while ($row = $rs->fetch_array()) {
					if($row['kind'] == "操作方法"){
						$kind_detail_list = array('JMBOOK', 'WPS設定', 'クレーム', 'ネット設定', 'プリンタ設定', 'メール設定', '不具合', '付属品', '初期設定', '問い合わせ', '基本操作', '検査・修理', '返品' );
					}
					$i++;
				?>
				<tbody>
				<tr
				<?php 
					if ($row['status'] != SYS_STATUS_9 and $row['upddt'] <= $weekago) {
						echo 'class="danger"'; 
					}
					else if ($row['status'] == SYS_STATUS_0){
						echo 'class="sta00"';
					}
					else if ($row['status'] == SYS_STATUS_3){
						echo 'class="sta03"';
					}
					else if ($row['status'] == SYS_STATUS_8){
						echo 'class="sta08"';
					}
					else if ($row['status'] == SYS_STATUS_9){
						echo 'class="sta09"';
					}
					else{
						echo 'class="ordinary"'; 
					}
				?>>
					<td class="tbd_td_p1">
						<a href="Javascript:mailDetail(<?php echo $row['idxnum'] ?>)"><?php echo $row['idxnum'] ?></a>
					</td>
					<td style="display:none;">
						<?php echo $row['updcount'] ?>
					</td>
					<td style="display:none;">
						<input type="hidden" id="old_status<?php echo $row['idxnum'] ?>" value="<?php echo $row['status'] ?>">
					</td>
					<td class="tbd_td_p1">
						<select name="状態<?php echo $row['idxnum'] ?>" id="status<?php echo $row['idxnum'] ?>" onchange="Change_Sql(<?php echo $row['idxnum'] ?>)">
							<option value="0" <?php if($row['status'] == SYS_STATUS_0) echo 'selected'; ?>>未連絡</option>
							<option value="2" <?php if($row['status'] == SYS_STATUS_2) echo 'selected'; ?>>対応中</option>
							<option value="8" <?php if($row['status'] == SYS_STATUS_8) echo 'selected'; ?>>確認待</option>
							<option value="9" <?php if($row['status'] == SYS_STATUS_9) echo 'selected'; ?>>完了</option>
							<option value="3" <?php if($row['status'] == SYS_STATUS_3) echo 'selected'; ?>>返信有</option>
						</select>
					</td>
					<td class="tbd_td_p1">
						<?php echo date('Y/m/d H:i', strtotime($row['insdt'])) ?><br>
						(<?php echo date('Y/m/d H:i', strtotime($row['upddt'])) ?>)
					</td>
					<td class="tbd_td_p1">
						<?php echo $row['name'] ?><br>
						(<?php echo $row['ruby'] ?>)
					</td>
					<td class="tbd_td_p1">
						<?php echo $row['address1'] ?>
					</td>
					<td class="tbd_td_p1" title="<? echo $row['question'] ?>">
						<?php echo mb_substr($row['question'],0,6,'UTF-8') ?>
					</td>
					<td class="tbd_td_p1">
						<select name="担当者<?php echo $row['idxnum'] ?>" id="correstaf<?php echo $row['idxnum'] ?>" onchange="Change_Sql(<?php echo $row['idxnum'] ?>)">
							<?php
								foreach($p_sendlist as $list) {
									if ($row['correstaf'] =="" &&  $list[0] == "未選択" || $row['correstaf'] == $list[0]) {
										echo "<option value=" . $list[0] . " selected >" . $list[0];
									}else{
										echo "<option value=" . $list[0] . ">" . $list[0];
									}
								}
							?>
						</select>
					</td>
					<td class="tbd_td_p1">
						<select  name="緊急度<?php echo $row['idxnum'] ?>" id="urgency<?php echo $row['idxnum'] ?>" onchange="Change_Sql(<?php echo $row['idxnum'] ?>)">
							<option value="">未選択</option>
							<option <?php if($row['urgency'] === '火急') echo 'selected'; ?>>火急</option>
							<option <?php if($row['urgency'] === '早急') echo 'selected'; ?>>早急</option>
							<option <?php if($row['urgency'] === '普通') echo 'selected'; ?>>普通</option>
						</select>
					</td>
					<td class="tbd_td_p1">
						<select  name="内容<?php echo $row['idxnum'] ?>" id="kind<?php echo $row['idxnum'] ?>" onchange="Change_Sql(<?php echo $row['idxnum'] ?>)">
							<option value="">未選択</option>
							<option <?php if($row['kind'] === '会場案内') echo 'selected'; ?>>会場案内</option>
							<option <?php if($row['kind'] === '購入案内') echo 'selected'; ?>>購入案内</option>
							<option <?php if($row['kind'] === '操作方法') echo 'selected'; ?>>操作方法</option>
							<option <?php if($row['kind'] === '修理') echo 'selected'; ?>>修理</option>
							<option <?php if($row['kind'] === '返品') echo 'selected'; ?>>返品</option>
							<option <?php if($row['kind'] === 'クレーム') echo 'selected'; ?>>クレーム</option>
						</select>
						<select class="form-gray" id="state" name="種別詳細<?php echo $row['idxnum'] ?>" onchange="Change_Sql(<?php echo $row['idxnum'] ?>)">
							<option value="">未選択</option>
							<? for($i=0; $i<count($kind_detail_list); ++$i){ ?>
								<option <?php if($row['kind_detail'] == $kind_detail_list[$i]){echo "selected='selected'";} ?>><? echo $kind_detail_list[$i] ?></option>
							<? } ?>
						</select>
					</td>
					<td>
						<a href="javascript:Push_jyokyo(<?php echo $row['idxnum'] ?>)">追記<img src="images/pen.png" alt="pen"></a>
						<div class="correcont_td">
							<?php
								if($row['category'] == 'コメント'){
									echo $row['contents'];
								}else{
									echo $row['category'];
								}
							?>
						</div>
					</td>
					<td>
						<center><button type="button" class="btn-infos btn-borders d" onclick="tabsence_sql(<?php echo $row['idxnum'] ?>);">不通</button></center>
					</td>
				</tr>
				<?php } ?>
				</tbody>
				</table>
			</form>
		</div>
	</div>
</body>

<!-- データベース切断 -->
<?php if ($result) { $dba->mysql_discon($db); } ?>
</html>