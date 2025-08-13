<?php
//==================================================================================================
// ■機能概要
// ・精米倶楽部地域別販売実績画面
//
// ■履歴
// 
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

	//実行プログラム名取得
	$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
	$prgname = "精米倶楽部地域別販売実績画面";
	$prgmemo = "　精米倶楽部の地域別の実績が確認できます。";
	$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);
	
	$action_url = './kaijyo_top.php';
	
	foreach($_POST as $key=>$val) {
		$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_DBUG);
	}

	//$p_display
	//  1:時間別
	//  2:カテゴリ別
	//  3:地域別
	//  4:担当者別(東海ラジオ)

	//----------------------------------------------------------------------------------------------
	// 引数取得処理
	//----------------------------------------------------------------------------------------------
	//表示内容
	$p_display = 3;
	if (isset($_GET['display'])) {
		$p_display = $_GET['display'];
	}
	//画面自動更新
	$refresh = 1;
	if (isset($_GET['ref'])) {
		$refresh = $_GET['ref'];
	}
	//----------------------------------------------------------------------------------------------
	// クッキー情報
	//----------------------------------------------------------------------------------------------
	//会社
	$p_compcd = $_COOKIE['con_perf_compcd'];
	//JEMTC職員ではない場合、画面を表示させない
	if ($p_compcd == "P") {
		$p_display = 4;
	}

?>

<!--------------------------------------------------------------------------------------------------
	コンテンツ表示
---------------------------------------------------------------------------------------------------->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<meta charset="UTF-8">
<? if($refresh == 1){ //更新ＯＮの場合?>
	<meta http-equiv="Refresh" content="30;URL='./<? echo $prgid ?>.php?display=<? echo $p_display ?>'">
<? } ?>
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
	padding: 5px 4px; /* 見出しセルのパディング（上下、左右） */
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
	width: 70px;
	padding: 5px 5px 5px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	td.tbd_td_p4_r {
	width: 100px;
	padding: 5px 5px 5px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p4_l {
	padding: 5px 5px 5px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p5_l {
	width: 100px;
	padding: 5px 5px 5px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p6_l {
	width: 200px;
	padding: 5px 5px 5px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
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


	@import url(https://fonts.googleapis.com/css?family=Open+Sans);


	fieldset {
	  border: none;
	  padding: 0;
	  margin: 0;
	  text-align: left;
	}

	h1 {
	  margin: 0;
	  line-height: 1.2;
	}

	p {
	  margin: 0 0 1.6rem;
	  padding-bottom: 0.2rem;
	  border-bottom: 1px solid #ddd;
	}

	.radio-inline__input {
	    clip: rect(1px, 1px, 1px, 1px);
	    position: absolute !important;
	}

	.radio-inline__label {
	    display: inline-block;
	    padding: 0.5rem 1rem;
	    margin-right: 18px;
	    border-radius: 3px;
	    transition: all .2s;
	}

	.radio-inline__input:checked + .radio-inline__label {
	    background: #B54A4A;
	    color: #fff;
	    text-shadow: 0 0 1px rgba(0,0,0,.7);
	}

	.radio-inline__input:focus + .radio-inline__label {
	    outline-color: #4D90FE;
	    outline-offset: -2px;
	    outline-style: auto;
	    outline-width: 5px;
	}

	</style>
	<script type="text/javascript">
		<!--
		function hpbmapinit() {
			hpbmaponload();
		}
		//-->
	</script>
	<?php $html->output_htmlheadinfo(); ?>
	<script type="text/javascript">
		function myfunc(display) {
      		  	let ref = 1;
			if (document.getElementById("b_ch1").checked) {
				ref = 1;
			} else {
				ref = 0;
			}
			//画面項目設定
			document.forms['frm'].action = './<? echo $prgid ?>.php?display=' + display + '&ref=' + ref;
			document.forms['frm'].submit();
        
        }
	</script>
</head>

<body>
<br>
<div id="container">
	<table class="base" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
		<!-- ヘッダー情報生成 -->
		<div id="header">
			<p><img src="images/logo_ns_user_perf.png" alt="" /></p>
		</div>
	</table>
	<div id="contents">
		<div id="main">
			<div id="formWrap">
				<form name="frm" method = "post" action="./<? echo $prgid ?>.php?display=<?echo $p_display ?>&ref=<?echo $refresh ?>" >
						<!-- JEMTC職員ではない場合、画面を表示させない -->
						<? if ($p_display <> 4) { //担当者別?>
							<div class="cp_ipcheck">
								<right>
								<input type="checkbox" id="b_ch1" name="自動更新" onchange="myfunc(<? echo $p_display?>)" <?php if ($refresh==1){echo "checked";} ?>/>
								<label for="b_ch1">自動更新</label>
								</right>
							</div>
							<table class="tbh" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
									<tr><td class="category"><strong>■◇■検索条件■◇■</strong></td></tr>
							</table>
							<br>
							<!-- 検索条件 -->
							<fieldset>
								<input id="item-3" class="radio-inline__input" type="radio" name="display" value="3" <?php if ($p_display==3) {echo "checked=\"checked\"";} ?> onclick="Change_RadioBox(3,<? echo $refresh ?>)" />
								<label class="radio-inline__label" for="item-3">
									<b>地域別</b>
								</label>
							</fieldset>
							<?php list($week, $p_date1, $p_date2, $p_staff) = $comm->getcalender($db,1,6); ?>
						<? } ?>
						<!-- 全体実績 -->
						<?php
							// ================================================
							// ■　□　■　□　個別表示　■　□　■　□
							// ================================================
							//----- データ抽出
							$query = "SELECT ";
							if ($p_display <> 4) { //担当者別
								$query = " 
									SELECT A.area, COUNT(B.category) as cnt
									FROM php_rice_subscription B 
									LEFT OUTER JOIN php_rice_personal_info A ON B.personal_idxnum=A.idxnum
									WHERE B.delflg=0
									AND B.date_s BETWEEN CAST(" . sprintf("'%s'", $p_date1) . " AS DATE)
									AND CAST(" . sprintf("'%s'", $p_date2) . " AS DATE)
									GROUP BY A.area
									";
							}
							$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
							if (!($rs = $db->query($query))) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
							//変数初期化
							$buynumSum0 = 0;
							$buynumSum1 = 0;
							$buynumSum = 0;
							$buykinSum = 0;
							$buyavg = 0;
							while ($row = $rs->fetch_array()) {
								$buynumSum = $buynumSum + $row['buynum'];
								$buykinSum = $buykinSum + $row['cash'];
								if ($row['tm'] == 0) { $buynumSum0 = $buynumSum0 + $row['buynum']; } //タウンメール以外
								if ($row['tm'] == 1) { $buynumSum1 = $buynumSum1 + $row['buynum']; } //タウンメール
							}
							if ($buynumSum > 0) {
								$buyavg    = ROUND($buykinSum / $buynumSum);
							}
						?>
						<table class="tbh" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
								<tr><td class="category"><strong>■◇■全体実績■◇■</strong></td></tr>
						</table>
						<table class="tbd" cellspacing="0" cellpadding="0" border="0" style="{padding-bottom: 40px;}" summary="ベーステーブル">
							<tr>
								<? if ($p_display <> 4) { //担当者別?>
								<th class="tbd_th"><strong>総販売台数/金額</strong></td>
								<? } else { ?>
								<th class="tbd_th"><strong>総販売台数</strong></td>
								<? }?>
								<td class="tbd_arb"></td>
								<td class="tbd_td">
									<input type="text" size="10" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($buynumSum) ?>">台
									<? if ($p_display <> 4) { //担当者別以外?>
									<input type="text" class="money" size="20" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($buykinSum) ?> ">円<br>
									<? }?>
								</td>
							</tr>
							<? if ($p_display <> 4) { //担当者別?>
							<tr>
								<th class="tbd_th"><strong>平均単価</strong></td>
								<td class="tbd_arb"></td>
								<td class="tbd_td">
									<input type="text" class="money" size="20" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($buyavg) ?>">円
								</td>
							</tr>
							<? }?>
						</table>
						<!-- 都道府県別販売実績 -->
						<?php if ($p_display == 3) { //地域別 ?>
						<?php
							$comm->ouputlog("☆★☆処理開始☆★☆ ", $prgid, SYS_LOG_TYPE_INFO);
							//データ存在フラグ
							$dateflg1 = 0;
							$dateflg2 = 0;
							// ================================================
							// ■　□　■　□　個別表示　■　□　■　□
							// ================================================
							//----- データ抽出
							$query = "SELECT ";
							$query .= "  A.address1  ";
							$query .= " ,sum(A.buynum) as buynum  ";
							$query .= " FROM php_telorder__ A ";
							$query .= " WHERE A.receptionday BETWEEN CAST(" . sprintf("'%s'", date('Y-m-d 00:00:00', strtotime($p_date1))) . " AS DATETIME)";
							$query .= " AND CAST(" . sprintf("'%s'", date('Y-m-d 23:59:59', strtotime($p_date2))) . " AS DATETIME)";
							$query .= " AND sales_name = '100001'";
							$query .= " AND delflg = 0";
							$query .= " AND status in (1,9)";
							$query .= " AND modelnum LIKE '%user%'";
							$query .= " GROUP BY A.address1 ";
							$query .= " ORDER BY A.address1 ";
							$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
							if (!($rs = $db->query($query))) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
							//変数初期化
							$i = 0;
							$rowcnt = 0;
						?>
							<table class="tbh" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
									<tr><td class="category"><strong>■◇■実績■◇■</strong></td></tr>
							</table>
							<table class="tbd" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル" id= "TBL" style="width:500px;">
							<tr>
								<th class="tbd_th_p1"><strong>都道府県</strong></th>
								<th class="tbd_th_p1"><strong>台数(台)</strong></th>
								<th class="tbd_th_p1"><strong>割合(％)</strong></th>
							</tr>
						<?
							$cnt=0;
							$category ="";
							$address1 ="";
							while ($row = $rs->fetch_array()) {
								// ========================
								// 実績内容
								// ========================
								//明細設定
								if (($rowcnt % 2) == 0) {
									echo "<tr>\n";
								} else {
									echo "<tr style=\"background-color:#EDEDED;\">\n";
								}
						?>
								<td class="tbd_td_p5_l"><? echo $row['address1'] ?></td>
								<td class="tbd_td_p4_r" align="right"><? echo $row['buynum'] ?></td>
								<td class="tbd_td_p4_r" align="right"><? echo ceil(($row['buynum'] / $buynumSum) * 100) ?></td>
								</tr>
						<?
								$rowcnt = $rowcnt + 1;
							}
						?>
							</table>

						<?php } ?>


						<!-- 担当者別販売実績 -->
						<?php if ($p_display == 4) { //担当者別 ?>
						<?php
							$comm->ouputlog("☆★☆処理開始☆★☆ ", $prgid, SYS_LOG_TYPE_INFO);
							//データ存在フラグ
							$dateflg1 = 0;
							$dateflg2 = 0;
							// ================================================
							// ■　□　■　□　個別表示　■　□　■　□
							// ================================================
							//----- データ抽出
							$query = "SELECT ";
							$query .= "  A.receptionist  ";
							$query .= " ,sum(A.buynum) as buynum  ";
							$query .= " FROM php_telorder__ A ";
							$query .= " WHERE A.receptionist like  'EP%'";
							$query .= " AND sales_name = '100001'";
							$query .= " AND delflg = 0";
							$query .= " AND status in (1,9)";
							$query .= " AND modelnum LIKE '%user%'";
							$query .= " GROUP BY A.receptionist ";
							$query .= " ORDER BY A.receptionist ";
							$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
							if (!($rs = $db->query($query))) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
							//変数初期化
							$i = 0;
							$rowcnt = 0;
						?>
							<table class="tbh" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
									<tr><td class="category"><strong>■◇■総合計■◇■</strong></td></tr>
							</table>
							<table class="tbd" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル" id= "TBL" style="width:500px;">
							<tr>
								<th class="tbd_th_p1"><strong>担当者</strong></th>
								<th class="tbd_th_p1"><strong>台数</strong></th>
								<th class="tbd_th_p1"><strong>インセンティブ</strong></th>
							</tr>
						<?
							$cnt=0;
							$category ="";
							$address1 ="";
							while ($row = $rs->fetch_array()) {
								// ========================
								// 実績内容
								// ========================
								//明細設定
								if (($rowcnt % 2) == 0) {
									echo "<tr>\n";
								} else {
									echo "<tr style=\"background-color:#EDEDED;\">\n";
								}
						?>
								<td class="tbd_td_p5_l"><? echo $row['receptionist'] ?></td>
								<td class="tbd_td_p4_r" align="right"><? echo number_format($row['buynum']) ?>台</td>
								<td class="tbd_td_p4_r" align="right"><? echo number_format($row['buynum'] * 50) ?>円</td>
								</tr>
						<?
								$rowcnt = $rowcnt + 1;
							}
						?>
							</table>
						<?php } ?>

						<!-- 販売実績 -->
						<?php
							$comm->ouputlog("☆★☆処理開始☆★☆ ", $prgid, SYS_LOG_TYPE_INFO);
							//データ存在フラグ
							$dateflg1 = 0;
							$dateflg2 = 0;
							// ================================================
							// ■　□　■　□　個別表示　■　□　■　□
							// ================================================
							//----- データ抽出
							$query = "SELECT ";
							if ($p_display == 1) { //時間別
								$query .= "  AA.dt  ";
							} elseif ($p_display == 2) { //カテゴリ別
								$query .= "  AA.category  ";
								$query .= " ,AA.cash  ";
							} elseif ($p_display == 3) { //地域別
								$query .= "  AA.address1  ";
								$query .= " ,AA.address_city  ";
							} elseif ($p_display == 4) { //担当者別
								$query .= "  A.receptionist  ";
								$query .= " ,DATE_FORMAT(A.insdt,'%m/%d') as dt  ";
								$query .= " ,sum(A.buynum) as buynum  ";
							} elseif ($p_display == 5) { //担当者別(全体)
								$query .= "  AA.receptionist  ";
							}
							if ($p_display == 4) { //担当者別
								$query .= " FROM php_telorder__ A ";
								$query .= " WHERE A.receptionist like  'EP%'";
							} else {
								$query .= " ,sum(AA.buynum) as buynum ";
								$query .= " ,sum(case AA.tm when 0 then AA.buynum else 0 end) as buynum0 ";
								$query .= " ,sum(case AA.tm when 1 then AA.buynum else 0 end) as buynum1 ";
								$query .= " from ( ";
								$query .= " SELECT ";
								if ($p_display == 1) { //時間別
									$query .= "  DATE_FORMAT(A.receptionday,'%H:00') as dt  ";
								} elseif ($p_display == 2) { //カテゴリ別
									$query .= "  A.category  ";
									$query .= " ,A.cash  ";
								} elseif ($p_display == 3) { //地域別
									$query .= "  A.address1  ";
									$query .= " ,A.address_city  ";
								} elseif ($p_display == 5) { //担当者別(全体)
									$query .= "  A.receptionist  ";
								}
								$query .= " ,CASE WHEN A.tm and isnull(B.prefecture) = 0 then 1 else 0 end as tm  ";
								$query .= " ,B.prefecture  ";
								$query .= " ,sum(A.buynum) as buynum ";
								$query .= " FROM php_telorder__ A ";
								$query .= " LEFT OUTER JOIN php_tm_telorder B ";
								$query .= "   ON B.prefecture = A.address1 ";
								$query .= "  AND B.city = A.address_city ";
								$query .= " WHERE A.receptionday BETWEEN CAST(" . sprintf("'%s'", date('Y-m-d 00:00:00', strtotime($p_date1))) . " AS DATETIME)";
								$query .= " AND CAST(" . sprintf("'%s'", date('Y-m-d 23:59:59', strtotime($p_date2))) . " AS DATETIME)";
							}
							$query .= " AND sales_name = '100001'";
							$query .= " AND delflg = 0";
							$query .= " AND status in (1,9)";
							$query .= " AND modelnum LIKE '%user%'";

							//
							if ($p_display == 1) { //時間別
								$query .= " GROUP BY dt ,tm ,B.prefecture";
								$query .= " ) AA  ";
								$query .= " GROUP BY AA.dt  ";
								$query .= " ORDER BY AA.dt ";
							} elseif ($p_display == 2) { //カテゴリ別
								$query .= " GROUP BY A.category ,A.cash ,tm ,B.prefecture";
								$query .= " ) AA  ";
								$query .= " GROUP BY AA.category,AA.cash ";
								$query .= " ORDER BY AA.category,AA.cash ";
							} elseif ($p_display == 3) { //地域別
								$query .= " GROUP BY A.address1  ,A.address_city ,tm ,B.prefecture";
								$query .= " ) AA  ";
								$query .= " GROUP BY AA.address1  ,AA.address_city ";
								$query .= " ORDER BY AA.address1  ,AA.address_city ";
							} elseif ($p_display == 4) { //担当者別
								$query .= " GROUP BY A.receptionist  ,dt ";
								$query .= " ORDER BY A.receptionist  ,dt ";
							} elseif ($p_display == 5) { //担当者別
								$query .= " GROUP BY A.receptionist ,tm ,B.prefecture";
								$query .= " ) AA  ";
								$query .= " GROUP BY AA.receptionist ";
								$query .= " ORDER BY AA.buynum DESC ";
							}
							$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
							if (!($rs = $db->query($query))) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
							//変数初期化
							$i = 0;
							$rowcnt = 0;
							$buynumSum = 0;
							$buykinSum = 0;
							$repairnumSum = 0;
							$cntSum = 0;
						?>
							<table class="tbh" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
									<tr><td class="category"><strong>■◇■販売実績■◇■</strong></td></tr>
							</table>
							<table class="tbd" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル" id= "TBL">
							<tr>
						<? if ($p_display == 1) { //時間別 ?>
								<th class="tbd_th_p1"><strong>時間</strong></th>
								<th class="tbd_th_p1"><strong>台数</strong></th>
								<th class="tbd_th_p1"><strong></strong></th>
						<? } elseif ($p_display == 2) { //カテゴリ別 ?>
								<th class="tbd_th_p1"><strong>型番</strong></th>
								<th class="tbd_th_p1"><strong>金額</strong></th>
								<th class="tbd_th_p1"><strong>台数</strong></th>
								<th class="tbd_th_p1"><strong></strong></th>
						<? } elseif ($p_display == 3) { //地域別 ?>
								<th class="tbd_th_p1"><strong>都道府県</strong></th>
								<th class="tbd_th_p1"><strong>市町村</strong></th>
								<th class="tbd_th_p1"><strong>台数</strong></th>
								<th class="tbd_th_p1"><strong></strong></th>
						<? } elseif ($p_display == 4) { //担当者別 ?>
								<th class="tbd_th_p1"><strong>担当者</strong></th>
								<th class="tbd_th_p1"><strong>日付</strong></th>
								<th class="tbd_th_p1"><strong>台数/ｲﾝｾﾝﾃｨﾌﾞ</strong></th>
								<th class="tbd_th_p1"><strong></strong></th>
						<? } elseif ($p_display == 5) { //担当者別 ?>
								<th class="tbd_th_p1"><strong>担当者</strong></th>
								<th class="tbd_th_p1"><strong>台数</strong></th>
								<th class="tbd_th_p1"><strong></strong></th>
						<? } ?>
							</tr>
						<?
							$cnt=0;
							$category ="";
							$address1 ="";
							$receptionist ="";
							$buynumSUM=0;
							while ($row = $rs->fetch_array()) {
								// ========================
								// 実績内容
								// ========================
								//合計集計
								if ($p_display == 4 and $buynumSUM > 0 and $receptionist <> $row['receptionist']) {
						?>
									<tr style="background-color:#EAD3D3;">
										<td class="tbd_td_p4" align="center" colspan="2"><b>合計</b></td>
										<td class="tbd_td_p4_r" align="right">
											<b><? echo number_format($buynumSUM) ?>台</b><br>
											<b><? echo number_format($buynumSUM * 50) ?>円</b>
										</td>
										<td class="tbd_td_p4_l"></td>
									<tr>
						<?
									$buynumSUM = 0;
								}
								//明細設定
								if (($rowcnt % 2) == 0) {
									echo "<tr>\n";
								} else {
									echo "<tr style=\"background-color:#EDEDED;\">\n";
								}
						?>

						<? if ($p_display == 1) { //時間別 ?>
								<td class="tbd_td_p4" align="center"><? echo $row['dt'] ?></td>
								<td class="tbd_td_p4_r" align="right"><? echo $row['buynum'] ?></td>
								<td class="tbd_td_p4_l">
						<? } elseif ($p_display == 2) { //カテゴリ別 ?>
								<? if ($category <> $row['category']){ ?>
									<td class="tbd_td_p4" align="center"><? echo $row['category'] ?></td>
								<? } else { ?>
									<td class="tbd_td_p4" align="center"></td>
								<? } ?>
								<td class="tbd_td_p4" align="center"><? echo $row['cash'] ?></td>
								<td class="tbd_td_p4_r" align="right"><? echo $row['buynum'] ?></td>
								<td class="tbd_td_p4_l">
						<? } elseif ($p_display == 3) { //地域別 ?>
								<? if ($address1 <> $row['address1']){ ?>
									<td class="tbd_td_p5_l"><? echo $row['address1'] ?></td>
								<? } else { ?>
									<td class="tbd_td_p5_l"></td>
								<? } ?>

								<td class="tbd_td_p6_l"><? echo $row['address_city'] ?></td>
								<td class="tbd_td_p4_r" align="right"><? echo $row['buynum'] ?></td>
								<td class="tbd_td_p4_l">
						<? } elseif ($p_display == 4) { //担当者別 ?>
								<? if ($receptionist <> $row['receptionist']){ ?>
									<td class="tbd_td_p4_l" align="center"><? echo $row['receptionist'] ?></td>
								<? } else { ?>
									<td class="tbd_td_p4_l" align="center"></td>
								<? } ?>
								<td class="tbd_td_p4" align="center"><? echo $row['dt'] ?></td>
								<td class="tbd_td_p4_r" align="right">
									<? echo $row['buynum'] ?>台<br>
									<? echo $row['buynum'] * 50 ?>円
								</td>
								<td class="tbd_td_p4_l">
						<? } elseif ($p_display == 5) { //担当者別 ?>
								<? if ($receptionist <> $row['receptionist']){ ?>
									<td class="tbd_td_p4_l" align="center"><? echo $row['receptionist'] ?></td>
								<? } else { ?>
									<td class="tbd_td_p4_l" align="center"></td>
								<? } ?>
								<td class="tbd_td_p4_r" align="right">
									<? echo $row['buynum'] ?>台
								</td>
								<td class="tbd_td_p4_l">
						<? } ?>
						<? 
								$j = 0;
								$k = 0;
								$img = "";
								for ($i = 0; $i < $row['buynum']; $i++) {
									if ($p_display <> 4) {
										if ($i < $row['buynum0']) {
											$img .=  "<img src='images/g-1.png'>";
										} else {
											$img .=  "<img src='images/g-2.png'>";
										}
									} else {
										$img .=  "<img src='images/g-1.png'>";
									}
									$j = $j + 1;
									if ($j == 10) {
										$img .=  "<font size=\"1.5\" color=\"#ff0000\">" . str_pad(($i + 1), 3, " ", STR_PAD_LEFT) . "</font>";
										$j = 0;
									}
									$k = $k + 1;
									if ($k == 50) {
										$img .=  "<br>";
										$k = 0;
									}
								}
								echo $img;
						?>
								</td>
								</tr>
						<?
								$rowcnt = $rowcnt + 1;
								//カテゴリ別の場合
								if ($p_display == 2) { $category = $row['category'];}
								if ($p_display == 3) { $address1 = $row['address1'];}
								if ($p_display == 4) {
									$receptionist = $row['receptionist'];
									$buynumSUM = $buynumSUM + $row['buynum'];
								}
								if ($p_display == 5) { $receptionist = $row['receptionist'];}
							}
							if ($p_display == 4 and $buynumSUM > 0) {
						?>
								<tr style="background-color:#EAD3D3;">
									<td class="tbd_td_p4" align="center" colspan="2"><b>合計</b></td>
									<td class="tbd_td_p4_r" align="right">
										<b><? echo number_format($buynumSUM) ?>台</b><br>
										<b><? echo number_format($buynumSUM * 50) ?>円</b>
									</td>
									<td class="tbd_td_p4_l"></td>
								<tr>
						<? } ?>
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
