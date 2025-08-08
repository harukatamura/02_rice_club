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
	//送信完了件数
	$query = "SELECT SUM(A.sendflg) as send_num ";
	$query .= " FROM php_rice_mail_list A ";
	$query .= " WHERE A.delflg='0' ";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$send_num = $row['send_num'];
	}
	$p_sendlist[][0] = "島村";

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

  <title>精米倶楽部メールお問い合わせ一覧</title>
  
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
	width: 200px;
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
					, url: "./rice_mail_check_sql.php"
					, data: {
						"idx":idx
					}
				//　POST送信成功
				}).done(function(data) {
					var result = JSON.parse(data);
					var get_status = result[0];
					var get_staff = result[1];
					if ( get_staff != '未選択' && staff != '未選択') {
						if (get_staff != staff) {
							staff_err_flg = 1;
						}
					}
					if ( get_status != old_status ) {
						status_err_flg = 1;
					}
					if ( status_err_flg == 1) {
						alert('ステータスが変更されています\n現在のステータスを確認してください。');
						location.href = './rice_mail.php';
					} else if ( staff_err_flg == 1 ) {
						staff_check = confirm('登録されている職員と異なる職員が設定されようとしています\nよろしいですか？');
						if ( staff_check == true ) {
							document.forms['frm'].action = './rice_mail_sql.php?' + rowINX;
							document.forms['frm'].submit();
						} else {
							location.href = './rice_mail.php';
						}
					} else {
						document.forms['frm'].action = './rice_mail_sql.php?' + rowINX;
						document.forms['frm'].submit();
					}
				});
			}
		}
		//コメント登録
		function Push_jyokyo(idx){
			var rowINX = 'do=ins&idxnum='+idx;
			window.open('./rice_mail_form.php?' + rowINX);
		}
		//詳細ページにジャンプ
		function mailDetail(idx,flg) {
			if(flg == 0){
				var rowINX = 'do=reply&idxnum='+idx;
			}else{
				var rowINX = 'do=show&idxnum='+idx;
			}
			window.open ( "./rice_mail_list_form.php?"+ rowINX, "_blank", "width=1600, height=1200, scrollbars=yes" );
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
	</script> 

</head>
<body>
	<div id="container">
		<div id="header">
			<br><h2>精米倶楽部メーリス配信</h2>
	  <!-- 項目①
	  ================================================== -->
			<br>
			<div id="note">
				送信済：<?php echo $send_num; ?>　件
			</div>
		</div>
		<div id="main">
			<br>
			<form name="search" method="post" action="./rice_mail.php">
				<? if($p_staff == "島村" || $p_staff == "田村"){ ?>
					<div id="checkwait">
						<input type="button" class="search" OnClick="window.open('./rice_mail_list_form.php?do=reply')" value="新規作成"><br>
					</div>
				<? } ?>
			</form>
			<form name="frm" method="post">
				<!-- 一覧テーブル -->
				<table class="tbh" id= "TBL">
				<thead>
					<tr>
						<th class="tbd_th_1">NO.</th>
						<th class="tbd_th_2">状況</th>
						<th class="tbd_th_2">送信日時</th>
						<th class="tbd_th_3">送信先</th>
						<th class="tbd_th_2" title="マウスオンで全文表示">件名※</th>
						<th class="tbd_th_2" title="マウスオンで全文表示">内容※</th>
						<th class="tbd_th_2">担当者</th>
					</tr>
				</thead>
				<tbody>
				<?php
					//----- データ抽出
					$query = "";
					$query .= " SELECT A.idxnum, A.sendflg, A.send_dt, A.mail_group, A.to_email, A.bcc, A.send_num, A.fail_num, A.title, A.contents, A.correstaf";
					$query .= " FROM php_rice_mail_list A ";
					$query .= " WHERE delflg = 0";
					$query .= " ORDER BY A.sendflg, A.idxnum ";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($query))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$i = 0;
					$category = "";
					$status = array(0 => "下書", 1 => "送信済");
					while ($row = $rs->fetch_array()) {
						$i++;
						?>
						<tr onClick="Javascript:mailDetail(<?php echo $row['idxnum'] ?>,<?php echo $row['sendflg'] ?>)">
							<td class="tbd_td_p1">
								<? if($p_staff == "島村" || $p_staff == "田村"){ ?>
									<a href=""><?php echo $row['idxnum'] ?></a>
								<? }else{ ?>
									<? if($row['sendflg'] > 0){ ?>
										<a href="Javascript:mailDetail(<?php echo $row['idxnum'] ?>,<?php echo $row['sendflg'] ?>)"><?php echo $row['idxnum'] ?></a>
									<? }else{ ?>
										<?php echo $row['idxnum'] ?>
									<? } ?>
									
								<? } ?>
							</td>
							<td class="tbd_td_p1">
								<?php echo $status[$row['sendflg']] ?>
							</td>
							<td class="tbd_td_p1">
								<?php if($row['sendflg'] > 0){echo date('Y/m/d H:i', strtotime($row['send_dt']));} ?>
							</td>
							<td class="tbd_td_p1">
								<?php echo $row['mail_group'] ?>
							</td>
							<td class="tbd_td_p1" title="<? echo $row['title'] ?>">
								<?php echo mb_substr($row['title'],0,6,'UTF-8') ?>
							</td>
							<td class="tbd_td_p1" title="<? echo $row['contents'] ?>">
								<?php echo mb_substr($row['contents'],0,6,'UTF-8') ?>
							</td>
							<td class="tbd_td_p1">
								<?php echo $row['correstaf'] ?>
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