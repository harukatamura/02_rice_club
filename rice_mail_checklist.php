<?php
//==================================================================================================
// ■機能概要
//   ・精米倶楽部メールチェックリスト一覧
//==================================================================================================
//----------------------------------------------------------------------------------------------
// 初期処理
//----------------------------------------------------------------------------------------------
//ログイン確認(COOKIEを利用)
if((!$_COOKIE['j_office_Uid']) or (!$_COOKIE['j_office_Pwd'])) {
		//Urlへ送信
		header("Location: ./idxnum.php"); //これを使うときは、これ以前にブラウザの書き込みは全てしないこと！
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
	$prgname = "infomail一覧";
	$prgmemo = "　infomailを一覧で確認できます。";
	$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);

	//本日日付
	$today = date('Y/m/d');
	$comm->ouputlog("today=" . $today, $prgid, SYS_LOG_TYPE_DBUG);

	//1週間前の日付
	$weekago = date("Y-m-d",strtotime("-6 days"));

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
// ----- 2019.06 ver7.0対応
//		if (! $rs = mysql_query($query, $db)) {
		if (!($rs = $db->query($query))) {
//			$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		//データ設定
// ----- 2019.06 ver7.0対応
//		while ($row = @mysql_fetch_array($rs)) {
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
	if ($_COOKIE['con_perf_compcd']) {
		$p_compcd = $_COOKIE['con_perf_compcd'];
	}
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
	$p_sendlist[][0] = "島村";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<meta http-equiv="Refresh" content="60">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>精米倶楽部メールお問い合わせ　確認待ち一覧</title>
  
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
		width: 470px;
	}
	th.sh_th {
		width: 100px;
		padding: 0.25em 0.5em;
	}
	td.sh_td_c {
		width: 20px;
		padding: 0.25em 0.5em;
	}
	td.sh_td_l {
		width: 350px;
		padding: 0.25em 0.5em;
	}
	table tbody tr:hover{
	  background-color: #A5E4FF;
	}
</style>
	<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<script type="text/javascript">
		window.addEventListener ('DOMContentLoaded', function () {
			var trueflg = localStorage.getItem('mail_check_flg');
			if ( trueflg == 1 ) {
				swal("Finish!", "メールを送信しました。", "success");
				localStorage.removeItem('mail_check_flg');
			}
		} )
		//状態変更
		function Change_Sql(idxnum){
			var rowINX = 'do=changetop&page=check&idxnum='+idxnum;
			document.forms['frm'].action = './rice_mail_sql.php?' + rowINX;
			document.forms['frm'].submit();
		}
		//状態変更_対応中のみ
		function Change_Sql_corre(idxnum){
			var status = document.getElementById('status' + idxnum).value; 
			if (status != 2) {
				document.forms['frm'].reset();
			} else {
				var rowINX = 'do=changetop&page=check&idxnum='+idxnum;
				document.forms['frm'].action = './rice_mail.php?' + rowINX;
				document.forms['frm'].submit();
			}
		}
		//コメント登録
		function Push_jyokyo(idxnum){
			var rowINX = 'do=ins&idxnum='+idxnum;
			window.open('./rice_mail_form.php?' + rowINX);
		}
		//詳細ページにジャンプ
		function mailDetail(idxnum) {
			var rowINX = 'idxnum='+idxnum;
			window.open('./rice_mail_detail.php?' + rowINX);
		}
		//返信ページにジャンプ
		function mailsend(idxnum) {
			var rowINX = 'idxnum='+idxnum;
			window.open ( "./rice_mail_form.php?do=reply&"+ rowINX, "返信内容確認", "width=1600, height=1200, scrollbars=yes" );
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
	</script> 

</head>
<body>
	<div id="container">
		<div id="header">
			<br><h2>精米倶楽部お問い合わせ確認待ち一覧</h2>
	  <!-- 項目①
	  ================================================== -->
			<br>
			<div id="note">
			</div>
		</div>
		<div id="main">
			<br>
			<form name="search" method="post" action="./rice_mail.php">
				<div id="note2">
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
						<th class="tbd_th_2">お問い合わせ内容</th>
						<th class="tbd_th_1">担当者</th>
						<th class="tbd_th_1">緊急度</th>
						<th class="tbd_th_2">最新対応内容</th>
					</tr>
				</thead>
				<?php
					//----- データ抽出
					$query = "";
					$query .= " SELECT E.mail_idxnum, E.updcount, A.name, A.insdt, A.upddt, A.ruby, A.company, A.address1";
					$query .= " , A.phonenum1, A.email, E.mail_status, E.correstaf, E.question, E.urgency ";
					$query .= " , D.category, D.contents";
					$query .= " FROM php_rice_mail E";
					$query .= " LEFT OUTER JOIN php_rice_personal_info A";
					$query .= " ON A.idxnum=E.personal_idxnum";
					$query .= " left outer join";
					$query .= " (SELECT B.category, B.contents, B.detail_idxnum, B.mail_idxnum";
					$query .= " FROM php_rice_mail_detail B";
					$query .= " INNER JOIN(";
					$query .= " SELECT mail_idxnum, MAX(detail_idxnum) as max_detail_idxnum";
					$query .= " FROM php_rice_mail_detail ";
					$query .= " GROUP BY mail_idxnum ) as C";
					$query .= " ON B.mail_idxnum= C.mail_idxnum";
					$query .= " AND B.detail_idxnum = C.max_detail_idxnum) as D";
					$query .= " ON E.mail_idxnum = D.mail_idxnum";
					$query .= " WHERE DATE(E.insdt) = CURDATE()";
					$query .= " AND E.mail_status = 8";
					if($p_compcd == "T"){
						$query .= " AND A.correstaf = 'ジェネシス'";
					}
					$query .= " UNION ALL SELECT E.mail_idxnum, E.updcount, A.name, A.insdt, A.upddt, A.ruby, A.company";
					$query .= " , A.address1, A.phonenum1, A.email, E.mail_status, E.correstaf, E.question, E.urgency ";
					$query .= " , D.category, D.contents";
					$query .= " FROM php_rice_mail E";
					$query .= " LEFT OUTER JOIN php_rice_personal_info A";
					$query .= " ON A.idxnum=E.personal_idxnum";
					$query .= " left outer join";
					$query .= " (SELECT B.category, B.contents, B.detail_idxnum, B.mail_idxnum";
					$query .= " FROM php_rice_mail_detail B";
					$query .= " INNER JOIN(";
					$query .= " SELECT mail_idxnum, MAX(detail_idxnum) as max_detail_idxnum";
					$query .= " FROM php_rice_mail_detail ";
					$query .= " GROUP BY mail_idxnum ) as C";
					$query .= " ON B.mail_idxnum= C.mail_idxnum";
					$query .= " AND B.detail_idxnum = C.max_detail_idxnum) as D";
					$query .= " ON E.mail_idxnum = D.mail_idxnum";
					$query .= " WHERE DATE(E.insdt) < CURDATE() ";
					$query .= " AND E.mail_status = 8";
					if($p_compcd == "T"){
						$query .= " AND A.correstaf = 'ジェネシス'";
					}
					$query .= " ORDER BY upddt DESC, mail_idxnum DESC ";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($query))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$i = 0;
					$category = "";
					while ($row = $rs->fetch_array()) {
					$i++;
				?>
				<tbody>
				<tr
				<?php 
					if ($row['mail_status'] != SYS_STATUS_9 and $row['upddt'] <= $weekago) {
						echo 'class="danger"'; 
					}
					else if ($row['mail_status'] == SYS_STATUS_0){
						echo 'class="sta00"';
					}
					else if ($row['mail_status'] == SYS_STATUS_3){
						echo 'class="sta03"';
					}
					else if ($row['mail_status'] == SYS_STATUS_8){
						echo 'class="sta08"';
					}
					else if ($row['mail_status'] == SYS_STATUS_9){
						echo 'class="sta09"';
					}
					else{
						echo 'class="ordinary"'; 
					}
				?>>
					<td class="tbd_td_p1">
						<a href="Javascript:mailsend(<?php echo $row['mail_idxnum'] ?>)"><?php echo $row['mail_idxnum'] ?></a>
					</td>
					<td style="display:none;">
						<?php echo $row['updcount'] ?>
					</td>
					<td class="tbd_td_p1">
						<select name="状態<?php echo $row['mail_idxnum'] ?>" id="status<?php echo $row['mail_idxnum'] ?>" onchange="Change_Sql_corre(<?php echo $row['mail_idxnum'] ?>)">
							<option value="0" <?php if($row['mail_status'] == SYS_STATUS_0) echo 'selected'; ?>>未連絡</option>
							<option value="2" <?php if($row['mail_status'] == SYS_STATUS_2) echo 'selected'; ?>>対応中</option>
							<option value="8" <?php if($row['mail_status'] == SYS_STATUS_8) echo 'selected'; ?>>確認待</option>
							<option value="9" <?php if($row['mail_status'] == SYS_STATUS_9) echo 'selected'; ?>>完了</option>
							<option value="3" <?php if($row['mail_status'] == SYS_STATUS_3) echo 'selected'; ?>>返信有</option>
						</select>
					</td>
					<td class="tbd_td_p1">
						<?php echo date('Y/m/d H:i', strtotime($row['insdt'])) ?><br>
						(<?php echo date('Y/m/d H:i', strtotime($row['upddt'])) ?>)
					</td>
					<td class="tbd_td_p1">
						<?php echo $row['name'] ?><br>
						<?php if($row['ruby'] <> ""){echo "(".$row['ruby'].")"; }  ?>
					</td>
					<td class="tbd_td_p1">
						<?php echo $row['address1'] ?>
					</td>
					<td class="tbd_td_p1">
						<?php echo mb_substr($row['question'],0,6,'UTF-8') ?>
					</td>
					<td class="tbd_td_p1">
						<select name="担当者<?php echo $row['mail_idxnum'] ?>" id="correstaf<?php echo $row['mail_idxnum'] ?>" onchange="Change_Sql(<?php echo $row['mail_idxnum'] ?>)">
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
						<select  name="緊急度<?php echo $row['mail_idxnum'] ?>" id="urgency<?php echo $row['mail_idxnum'] ?>" onchange="Change_Sql(<?php echo $row['mail_idxnum'] ?>)">
							<option value="">未選択</option>
							<option <?php if($row['urgency'] === '火急') echo 'selected'; ?>>火急</option>
							<option <?php if($row['urgency'] === '早急') echo 'selected'; ?>>早急</option>
							<option <?php if($row['urgency'] === '普通') echo 'selected'; ?>>普通</option>
						</select>
					</td>
					<td>
						<a href="javascript:Push_jyokyo(<?php echo $row['mail_idxnum'] ?>)">追記<img src="images/pen.png" alt="pen"></a>
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