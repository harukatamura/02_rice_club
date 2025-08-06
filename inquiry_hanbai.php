<?php
//==================================================================================================
// ■機能概要
// ・販売実績画面
//==================================================================================================

	//----------------------------------------------------------------------------------------------
	// 初期処理
	//----------------------------------------------------------------------------------------------
	//ログイン確認(COOKIEを利用)
	if((!$_COOKIE['j_office_Uid']) or (!$_COOKIE['j_office_Pwd'])){
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
	$prgname = "販売実績一覧";
	$prgmemo = "　販売台数の状況確認ができます。";
	$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);

	//本日日付
	$today = date('Y/m/d');
	$comm->ouputlog("today=" . $today, $prgid, SYS_LOG_TYPE_DBUG);

	//----------------------------------------------------------------------------------------------
	// 引数取得処理
	//----------------------------------------------------------------------------------------------

	//画面自動更新
	$refresh = 0;
	if($_GET['ref'] == 1){
		$refresh = $_GET['ref'];
	}
	if($_POST['自動更新']){
		$refresh = 1;
	}
	$comm->ouputlog("自動更新=". $refresh, $prgid, SYS_LOG_TYPE_DBUG);
	//担当者
	if(isset($_POST['担当者'])){
		$p_staff = $_POST['担当者'];
		setcookie ('con_perf_staff', '', time()-3600);
		setcookie ('con_perf_staff', $p_staff, time() + 24 * 60 * 60 * 365);
	}
	else {
		$p_staff = "";
		//担当者確認(COOKIEを利用)
		if($_COOKIE['con_perf_staff']){
			$p_staff = $_COOKIE['con_perf_staff'];
		}
	}
	//会社
	$p_compcd = $_COOKIE['con_perf_compcd'];
	$comm->ouputlog("担当者=". $p_staff, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("会社　=". $p_compcd, $prgid, SYS_LOG_TYPE_DBUG);
	foreach($_POST as $key=>$val){
		$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_DBUG);
	}

	//----------------------------------------------------------------------------------------------
	// システムデータの取得
	//----------------------------------------------------------------------------------------------
	//販売担当者一覧の取得
	$comm->ouputlog("連絡担当者一覧の取得", $prgid, SYS_LOG_TYPE_INFO);
	if(!$rs = $comm->getstaff($db, 1)){
		$comm->ouputlog("データ取得エラー", $prgid, SYS_LOG_TYPE_DBUG);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)){
	while ($row = $rs->fetch_array()) {
		$p_sendlist[] = $row;
		$comm->ouputlog("担当者=". $row[0], $prgid, SYS_LOG_TYPE_DBUG);
	}
	
	//出荷元を配列に格納
	$arr_factory = array("11" => "ﾘﾝｸﾞﾛｰ", "12" => "融興", "13" => "補修ｾﾝﾀｰ",  "15" => "ｱｯﾌﾟｳｪﾙ", "QR" => "補修ｾﾝﾀｰ", "21" => "再生", "01" => "本部", "" => "ｽｷｬﾝなし"); 

	//--------------------------------------------------------------------------------------------------
	// ■メソッド名
	// mysql_upd_performance
	//
	// ■概要
	// 会場詳細情報更新
	//
	// ■引数
	// 第一引数：データベース
	//
	//--------------------------------------------------------------------------------------------------
	function set_staff_performance() {

		//グローバル変数
		global $staff;//担当者
		global $g_staff;//担当者
		global $event;//イベント
		global $lane;//レーン
		global $goalnum;//目標台数
		global $stocknum;//総在庫
		global $nbuynum;
		global $nhannum;
		global $kaisainum;
		global $buynum;
		global $hannum;
		global $resnum;
		global $retnum;
		global $hankin;
		global $reskin;
		global $retkin;
		global $hanall;
		global $hankinall;
		global $opnum;
		global $opkin;
		global $opretnum;
		global $opretkin;
		global $rownum;
		global $rentnum;
		global $rentkin;
		global $rentresnum;
		global $rentreskin;

		// ================================================
		// ■　□　■　□　個別表示　■　□　■　□
		// ================================================

		$goalclear = $hanall - $goalnum;
		if($hanall > $goalnum && $goalnum > 0){
			echo "<tr style=\"background-color:#FFDDE4;\">";
		} else {
			if(($rownum % 2) == 0){
				echo "<tr style=\"background-color:#EDEDED;\">";
			} else {
				echo "<tr>";
			}
		}
			if($staff <> $g_staff) {
				// レーン情報
				echo "<td class=\"tbd_td_p6_c\">" . $lane . "</td>";
				if($event == 0) { //個人会場
					echo "<td class=\"tbd_td_p5_c\">" . $staff  . "</td>";
				} else { //イベント会場
					echo "<td class=\"tbd_td_p5_c\">" . $staff . "<br><img src=\"./images/kaijo_e_s.jpg\"></td>";
				}
				// 総在庫-->
				echo "<td class=\"tbd_td_p6_r\">";
					if(strtotime($today) < strtotime($p_date2) && $nhannum < $nbuynum){
						echo $stocknum - ($nbuynum - $nhannum) . "台";
					}else{
						echo $stocknum . "台";
					}
				echo "</td>";
			} else {
				echo "<td class=\"tbd_td_p6_c\"></td>";
				echo "<td class=\"tbd_td_p5_c\">" . $staff . "<br><img src=\"./images/kaijo_e_s.jpg\"></td>";
				echo "<td class=\"tbd_td_p6_c\"></td>";
			}
			echo "<td class=\"tbd_td_p6_c\">" . $kaisainum . "回</td>";
			// 目標台数
			if($goalnum > 0){
				echo "<td class=\"tbd_td_p6_r\">" . $goalnum . "台<br>(";
				if($goalclear > 0) {
					echo $goalclear;
				} else {
					echo 0;
				}
				echo "台)</td>";
			} else {
				echo "<td class=\"tbd_td_p6_r\"> - 台</td>";
			}
			echo "<td class=\"tbd_td_p3_r\">" . $hanall . "台<br>";
			// 総計
			if($hankinall <> 0) {
				echo  number_format($hankinall) . "円<br>";
				if($hanall > 0){
					echo number_format($hankinall / $hanall);
				} else {
					echo 0;
				}
				echo "円</td>";
			} else {
				echo "0円<br>0円</td>";
			}
			//現物実績
			if($hannum <> 0) {
				echo "<td class=\"tbd_td_p3_r\">";
					echo $hannum - $retnum . "台";
					if($retnum <> 0) { 
						echo "<br>";
						echo "<small><FONT color=\"red\">(返:" . $retnum . "台)</FONT></small>";
					}
					echo "<br>";
					echo number_format($hankin - $retkin) . "円";
					if($retnum[$staff[$i]] <> 0){
						echo "<br>";
						echo "<small><FONT color=\"red\">(返:" . number_format($retkin) . "円)</FONT></small>";
					}
				echo "</td>";
			} else {
				echo "<td class=\"tbd_td_p3_r\"></td>";
			}
			//予約実績
			if($resnum <> 0 || $reskin <> 0){
				echo "<td class=\"tbd_td_p3_r\">" . number_format($resnum) . "台";
				echo "<br>";
				echo  number_format($reskin) . "円</td>";
			} else {
				echo "<td class=\"tbd_td_p3_r\"></td>";
			}
			//レンタル現物実績
			if($rentnum <> 0) {
				echo "<td class=\"tbd_td_p3_r\">";
					echo $rentnum . "台";
					echo "<br>";
					echo number_format($rentkin) . "円";
					echo "<br>";
					if($rentkin > 0){
						echo number_format($rentkin / $rentnum);
					} else {
						echo 0;
					}
				echo "円</td>";
			} else {
				echo "<td class=\"tbd_td_p3_r\"></td>";
			}
			//レンタル予約実績
			if($rentresnum <> 0){
				echo "<td class=\"tbd_td_p3_r\">" . number_format($rentresnum) . "台";
				echo "<br>";
				echo  number_format($rentreskin) . "円</td>";
			} else {
				echo "<td class=\"tbd_td_p3_r\"></td>";
			}
			// 備品実績
			if($opnum <> 0 || $opkin <> 0) {
				echo "<td class=\"tbd_td_p3_r\">";
					echo number_format($opnum - $opretnum) . "個";
					if($opnum > 0 && $hanall > 0) {
						echo "<br>";
						echo "<small>(" . number_format($opnum/$hanall*100) . "%)</small>";
					}
					if($opretnum <> 0) {
						echo "<br>";
						echo "<small><FONT color=\"red\">(返:" . $opretnum . "個)</FONT></small>";
					}
				echo "<br>";
					echo number_format($opkin - $opretkin) . "円";
					if($opretnum <> 0) {
						echo "<br>";
						echo "<small><FONT color=\"red\">(返:" . number_format($opretkin) . "円)</FONT></small>";
					}
				echo "</td>";
			} else {
				echo "<td class=\"tbd_td_p3_r\"></td>";
			}
		echo "</tr>";
		//初期化
		$kaisainum = 0;
		$buynum = 0;
		$hannum = 0; //現物．台数
		$resnum = 0; //予約．台数
		$retnum = 0; //返品．台数
		$hankin = 0;
		$reskin = 0;
		$retkin = 0;
		$hanall = 0;
		$hankinall = 0;

		$opnum = 0;
		$opkin = 0;
		$opretnum = 0;
		$opretkin = 0;
		$rentnum = 0;
		$rentkin = 0;
		$rentresnum = 0;
		$rentreskin = 0;
	}
?>

<!--------------------------------------------------------------------------------------------------
	コンテンツ表示
---------------------------------------------------------------------------------------------------->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<meta charset="UTF-8">
<?php if($refresh == 1){ ?>
<meta http-equiv="Refresh" content="60;URL='./<? echo $prgid ?>.php?ref=1'">
<?php } ?>
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
		width:1200px;
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
		width: 1200px;	/*コンテナー幅*/
		margin-right: auto;
		margin-left: auto;
		background-color: #FFFFFF;						/*背景色*/
		padding-right: 4px;
		padding-left: 4px;
	}

	/*メインコンテンツ
	---------------------------------------------------------------------------*/
	#main {
		width: 1200px;	/*メインコンテンツ幅*/
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

	th.tbd_th_p1 {
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #2B8225; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	}
	th.tbd_th_p2 {
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #5FCF57; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	}
	/* --- データセル（td） --- */

	td.tbd_td_p1 {
	width: 200px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p2 {
	width: 200px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p3 {
	width: 150px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	td.tbd_td_p3_l {
	width: 150px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p3_r {
	width: 150px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p4_c {
	width: 50px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	td.tbd_td_p4_r {
	width: 100px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p4_l {
	width: 100px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p5_r {
	width: 150px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p5_c {
	width: 120px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	td.tbd_td_p5_l {
	width: 500px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p6_l {
	width: 200px;
	color: white;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p6_r {
	width: 80px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p6_c {
	width: 50px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	/* --- 仕切り線セル --- */
	td.tbd_line_p1 {
	width: 10px;
	background-color: #e0f1fc; /* 任意項目セルの背景色 */
	border-bottom: 1px #c0c0c0 dotted; /* データセルの下境界線 */
	}
	td.tbd_line_p2 {
	width: 2px;
	background-color: #e0f1fc; /* 任意項目セルの背景色 */
	border-bottom: 1px #c0c0c0 dotted; /* データセルの下境界線 */
	}
	select.sizechange{
	font-size:120%;
	}

	/* --- テーブル --- */
	table.tbt {
	width: 100%; /* テーブルの幅 */
	background-color: #f9f9f9; /* テーブルの背景色 */
	border: 1px #c0c0c0 solid; /* テーブルの境界線 */
	margin-top: 10px;
	margin-bottom: 20px;
	}

	</style>
	<script type="text/javascript">
		<!--
		function hpbmapinit(){
			hpbmaponload();
		}
		//-->
	</script>
	<?php $html->output_htmlheadinfo2($prgname); ?>
	<script type="text/javascript">
		//編集ボタン
		function Mclk_Stat(Cell){
			//画面項目設定
 			var myTbl = document.getElementById('TBL');
			//画面項目設定
			var Cell1=myTbl.rows[Cell.parentNode.rowIndex].cells[0]; //i番行のj番列のセル "td"
			//画面項目設定
			var cell1 = 'idx='+Cell1.innerHTML;
			document.forms['frm'].action = './kaijo_perf_sql.php?' + cell1;
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
			<p><img src="images/logo_h_<? echo $prgid ?>.png" alt="" /></p>
		</div>
	</table>
	<div id="contents">
		<div id="main">
			<?php echo $prgmemo; ?>
			<div id="formWrap">
				<form name="frm" method = "post" action="./<? echo $prgid ?>.php" >
					<h2>検索条件</h2><br>
					<div class="cp_ipcheck">
						<? echo $pfrm->city; ?>
					</div>
					<div class="cp_ipcheck">
						<input type="checkbox" id="b_ch1" name="自動更新" <?php if ($refresh==1){echo "checked";} ?>/>
						<label for="b_ch1">自動更新</label>
					</div>
					<?php list($week, $p_date1, $p_date2, $p_staff) = $comm->getcalender($db,1,2); ?>
					<h2>全体実績</h2><br>
					<!-- 全体表示 -->
					<?php
					$comm->ouputlog("☆★☆処理開始☆★☆ ", $prgid, SYS_LOG_TYPE_INFO);
					//データ存在フラグ
					$dateflg1 = 0;
					$dateflg2 = 0;
					//データ存在フラグ
					$custodydt = "";
					// ================================================
					// ■　□　■　□　全体実績表示　■　□　■　□
					// ================================================
					//----- データ抽出(事業主・ＭＳＯ合計取得)
					$query  =" SELECT MIN(AA.buydtMin) AS buydtMin, MAX(AA.buydtMax) AS buydtMax";
					$query .=" FROM (";
					$query .= " SELECT MIN(A.buydt) AS buydtMin, MAX(A.buydt) AS buydtMax";
					$query .=" FROM php_performance A";
					$query .=" WHERE A.buydt BETWEEN CAST(" . sprintf("'%s'", $p_date1) . " AS DATE)";
					$query .=" AND CAST(" . sprintf("'%s'", $p_date2) . " AS DATE)";
					$query .=" AND A.tm_flg = 0 ";
					$query .=" ) AA ";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//					if(! $rs = mysql_query($query, $db)){
					if (!($rs = $db->query($query))) {
//						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$buydtMin = 0;
					$buydtMax = 0;
					$buynumAll = 0;
// ----- 2019.06 ver7.0対応
//					while ($row = @mysql_fetch_array($rs)){
					while ($row = $rs->fetch_array()) {
						$buydtMin = $row['buydtMin'];
						$buydtMax = $row['buydtMax'];
					}
					// ================================================
					// ■　□　■　□　本部会場担当者実績　■　□　■　□
					// ================================================
					//----- データ抽出(会場担当者合計取得)
					$query = " SELECT SUM(A.buynum) AS buynumSum, SUM(A.buykin) AS buykinSum ";
					$query .=" FROM php_performance A";
					$query .=" WHERE A.buydt BETWEEN CAST(" . sprintf("'%s'", $p_date1) . " AS DATE)";
					$query .=" AND CAST(" . sprintf("'%s'", $p_date2) . " AS DATE)";
					$query .=" AND A.tm_flg = 0 ";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//					if(! $rs = mysql_query($query, $db)){
					if (!($rs = $db->query($query))) {
//						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$buynumSum = 0;
					$buykinSum = 0;
					$buykinAvg = 0;
// ----- 2019.06 ver7.0対応
//					while ($row = @mysql_fetch_array($rs)){
					while ($row = $rs->fetch_array()) {
						$buynumSum = $row['buynumSum'];
						$buykinSum = $row['buykinSum'];
					}
					$query = " SELECT SUM(A.goalnum) AS goalnumSum ";
					$query .=" FROM ";
					$query .=" (SELECT goalnum FROM php_performance ";
					$query .=" WHERE buydt BETWEEN CAST(" . sprintf("'%s'", $p_date1) . " AS DATE)";
					$query .=" AND CAST(" . sprintf("'%s'", $p_date2) . " AS DATE)";
					$query .=" AND tm_flg = 0 ";
					$query .=" GROUP BY lane) A ";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($query))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$goalnumSum = 0;
					while ($row = $rs->fetch_array()) {
						$goalnumSum = $row['goalnumSum'];
					}

					//イベント実績を表示
					//----- データ抽出
					$query = "";
					$query .=" SELECT ";
					$query .=" IFNULL(SUM(M.han_P),0) - IFNULL(SUM(M.ret_P),0) as han_P "; //現物販売台数
					$query .=" ,IFNULL(SUM(M.hank_P),0) + IFNULL(SUM(M.hank_O),0) + IFNULL(SUM(M.hank_W),0) - IFNULL(SUM(M.retk_P), 0) - IFNULL(SUM(M.retk_O), 0) - IFNULL(SUM(M.retk_W),0) as hank_P "; //現物販売金額
					$query .=" ,IFNULL(SUM(M.gre_P),0) as gre_P "; //現物予約台数
					$query .=" ,IFNULL(SUM(M.grek_P),0) + IFNULL(SUM(M.grek_O),0) + IFNULL(SUM(M.grek_W),0) as grek_P "; //現物予約金額
					$query .=" ,IFNULL(SUM(M.mre_P),0) as mre_P "; //見本予約台数
					$query .=" ,IFNULL(SUM(M.mrek_P),0) + IFNULL(SUM(M.mrek_O),0) + IFNULL(SUM(M.mrek_W),0) as mrek_P "; //見本予約金額
					$query .=" ,IFNULL(SUM(M.rent_P),0) as rent_P "; //レンタル台数
					$query .=" ,IFNULL(SUM(M.rentk_P),0) as rentk_P "; //レンタル金額
					$query .=" ,IFNULL(SUM(M.rentgre_P),0) as rentgre_P "; //レンタル現物予約台数
					$query .=" ,IFNULL(SUM(M.rentgrek_P),0) as rentgrek_P "; //レンタル現物予約金額
					$query .=" ,IFNULL(SUM(M.rentmre_P),0) as rentmre_P "; //レンタル見本予約台数
					$query .=" ,IFNULL(SUM(M.rentmrek_P),0) as rentmrek_P "; //レンタル見本予約金額
					$query .=" ,IFNULL(SUM(M.han_O),0) + IFNULL(SUM(M.gre_O),0) + IFNULL(SUM(M.mre_O),0) - IFNULL(SUM(M.ret_O),0) as han_O "; //備品販売個数
					$query .=" ,IFNULL(SUM(M.hank_O),0) + IFNULL(SUM(M.grek_O),0) + IFNULL(SUM(M.mrek_O),0) - IFNULL(SUM(M.retk_O),0) as hank_O "; //備品販売金額
					$query .=" ,IFNULL(SUM(M.ret_P), 0) AS ret_P "; //返品台数
					$query .=" ,IFNULL(SUM(M.retk_P), 0) + IFNULL(SUM(M.retk_O), 0)  + IFNULL(SUM(M.retk_W), 0) AS retk_P "; //返品金額
					$query .=" from ";
					$query .=" ( ";
					//--- レンタル ---
					$query .=" SELECT AAAAAAAA.venueid, AAAAAAAA.staff ,AAAAAAAA.buydt ,AAAAAAAA.lane ,AAAAAAAA.companycd ,AAAAAAAA.goalnum ,AAAAAAAA.goalclear ";
					$query .=" ,AAAAAAAA.buynum,AAAAAAAA.buykin ";
					$query .=" ,AAAAAAAA.han_P, AAAAAAAA.hank_P ";
					$query .=" ,AAAAAAAA.gre_P, AAAAAAAA.grek_P ";
					$query .=" ,AAAAAAAA.mre_P, AAAAAAAA.mrek_P ";
					$query .=" ,AAAAAAAA.ret_P, AAAAAAAA.retk_P ";
					$query .=" ,AAAAAAAA.han_O, AAAAAAAA.hank_O ";
					$query .=" ,AAAAAAAA.gre_O, AAAAAAAA.grek_O ";
					$query .=" ,AAAAAAAA.mre_O, AAAAAAAA.mrek_O ";
					$query .=" ,AAAAAAAA.ret_O, AAAAAAAA.retk_O ";
					$query .=" ,AAAAAAAA.han_W, AAAAAAAA.hank_W ";
					$query .=" ,AAAAAAAA.gre_W, AAAAAAAA.grek_W ";
					$query .=" ,AAAAAAAA.mre_W, AAAAAAAA.mrek_W ";
					$query .=" ,AAAAAAAA.ret_W, AAAAAAAA.retk_W ";
					$query .=" ,ifnull(SUM(I.hannum),0) as rent_P, ifnull(sum(I.hannum * I.tanka),0) as rentk_P ";
					$query .=" ,ifnull(SUM(I.grenum),0)+ifnull(SUM(I.c_grenum),0) as rentgre_P, ifnull(sum(I.grenum * I.tanka),0)+ifnull(sum(I.c_grenum * I.tanka),0) as rentgrek_P ";
					$query .=" ,ifnull(SUM(I.mrenum),0)+ifnull(SUM(I.c_mrenum),0) as rentmre_P, ifnull(sum(I.mrenum * I.tanka),0)+ifnull(sum(I.c_mrenum * I.tanka),0) as rentmrek_P ";
					$query .=" from ";
					$query .=" ( ";
					//--- 割引返品 ---
					$query .=" SELECT AAAAAAA.venueid, AAAAAAA.staff ,AAAAAAA.buydt ,AAAAAAA.lane ,AAAAAAA.companycd ,AAAAAAA.goalnum ,AAAAAAA.goalclear ";
					$query .=" ,AAAAAAA.buynum,AAAAAAA.buykin ";
					$query .=" ,AAAAAAA.han_P, AAAAAAA.hank_P ";
					$query .=" ,AAAAAAA.gre_P, AAAAAAA.grek_P ";
					$query .=" ,AAAAAAA.mre_P, AAAAAAA.mrek_P ";
					$query .=" ,AAAAAAA.ret_P, AAAAAAA.retk_P ";
					$query .=" ,AAAAAAA.han_O, AAAAAAA.hank_O ";
					$query .=" ,AAAAAAA.gre_O, AAAAAAA.grek_O ";
					$query .=" ,AAAAAAA.mre_O, AAAAAAA.mrek_O ";
					$query .=" ,AAAAAAA.ret_O, AAAAAAA.retk_O ";
					$query .=" ,AAAAAAA.han_W, AAAAAAA.hank_W ";
					$query .=" ,AAAAAAA.gre_W, AAAAAAA.grek_W ";
					$query .=" ,AAAAAAA.mre_W, AAAAAAA.mrek_W ";
					$query .=" ,ifnull(SUM(H.hannum),0) as ret_W, ifnull(SUM(H.tanka),0) as retk_W ";
					$query .=" from ";
					$query .=" ( ";
					//--- 割引販売 ---
					$query .=" SELECT AAAAAA.venueid, AAAAAA.staff ,AAAAAA.buydt ,AAAAAA.lane ,AAAAAA.companycd ,AAAAAA.goalnum ,AAAAAA.goalclear ";
					$query .=" ,AAAAAA.buynum,AAAAAA.buykin ";
					$query .=" ,AAAAAA.han_P, AAAAAA.hank_P ";
					$query .=" ,AAAAAA.gre_P, AAAAAA.grek_P ";
					$query .=" ,AAAAAA.mre_P, AAAAAA.mrek_P ";
					$query .=" ,AAAAAA.ret_P, AAAAAA.retk_P ";
					$query .=" ,AAAAAA.han_O, AAAAAA.hank_O ";
					$query .=" ,AAAAAA.gre_O, AAAAAA.grek_O ";
					$query .=" ,AAAAAA.mre_O, AAAAAA.mrek_O ";
					$query .=" ,AAAAAA.ret_O, AAAAAA.retk_O ";
					$query .=" ,ifnull(sum(G.hannum),0) AS han_W, ifnull(sum(G.hannum * G.tanka),0) as hank_W ";
					$query .=" ,ifnull(sum(G.grenum),0)+ifnull(sum(G.c_grenum),0) AS gre_W, ifnull(sum(G.grenum * G.tanka),0)+ifnull(sum(G.c_grenum * G.tanka),0) as grek_W ";
					$query .=" ,ifnull(sum(G.mrenum),0)+ifnull(sum(G.c_mrenum),0) AS mre_W, ifnull(sum(G.mrenum * G.tanka),0)+ifnull(sum(G.c_mrenum * G.tanka),0) as mrek_W ";
					$query .=" from ";
					$query .=" ( ";
					//--- 備品返品 ---
					$query .=" SELECT AAAAA.venueid, AAAAA.staff ,AAAAA.buydt ,AAAAA.lane ,AAAAA.companycd ,AAAAA.goalnum ,AAAAA.goalclear ";
					$query .=" ,AAAAA.buynum,AAAAA.buykin ";
					$query .=" ,AAAAA.han_P, AAAAA.hank_P ";
					$query .=" ,AAAAA.gre_P, AAAAA.grek_P ";
					$query .=" ,AAAAA.mre_P, AAAAA.mrek_P ";
					$query .=" ,AAAAA.ret_P, AAAAA.retk_P ";
					$query .=" ,AAAAA.han_O, AAAAA.hank_O ";
					$query .=" ,AAAAA.gre_O, AAAAA.grek_O ";
					$query .=" ,AAAAA.mre_O, AAAAA.mrek_O ";
					$query .=" ,ifnull(SUM(F.hannum),0) as ret_O, ifnull(SUM(F.tanka),0) as retk_O ";
					$query .=" from ";
					$query .=" ( ";
					//--- PC返品 ---
					$query .=" SELECT AAAA.venueid, AAAA.staff ,AAAA.buydt ,AAAA.lane ,AAAA.companycd ,AAAA.goalnum ,AAAA.goalclear ";
					$query .=" ,AAAA.buynum,AAAA.buykin ";
					$query .=" ,AAAA.han_P, AAAA.hank_P ";
					$query .=" ,AAAA.gre_P, AAAA.grek_P ";
					$query .=" ,AAAA.mre_P, AAAA.mrek_P ";
					$query .=" ,AAAA.han_O, AAAA.hank_O ";
					$query .=" ,AAAA.gre_O, AAAA.grek_O ";
					$query .=" ,AAAA.mre_O, AAAA.mrek_O ";
					$query .=" ,ifnull(SUM(E.hannum),0) as ret_P, ifnull(SUM(E.tanka),0) as retk_P ";
					$query .=" from ";
					$query .=" ( ";
					//--- PC予約 ---
					$query .=" SELECT AAA.venueid, AAA.staff ,AAA.buydt ,AAA.lane ,AAA.companycd ,AAA.goalnum ,AAA.goalclear ";
					$query .=" ,AAA.buynum,AAA.buykin ";
					$query .=" ,AAA.han_P, AAA.hank_P ";
					$query .=" ,AAA.gre_P, AAA.grek_P ";
					$query .=" ,AAA.han_O, AAA.hank_O ";
					$query .=" ,AAA.gre_O, AAA.grek_O ";
					$query .=" ,AAA.mre_O, AAA.mrek_O ";
					$query .=" ,ifnull(sum(D.mrenum),0) + AAA.mre_P AS mre_P, ifnull(sum(D.mrenum * D.tanka),0) + AAA.mrek_P as mrek_P ";
					$query .=" from ";
					$query .=" ( ";
					//--- 備品販売 ---
					$query .=" SELECT AA.venueid, AA.staff ,AA.buydt ,AA.lane ,AA.companycd ,AA.goalnum ,AA.goalclear";
					$query .=" ,AA.buynum,AA.buykin ";
					$query .=" ,AA.han_P, AA.hank_P ";
					$query .=" ,AA.gre_P, AA.grek_P ";
					$query .=" ,AA.mre_P, AA.mrek_P ";
					$query .=" ,ifnull(sum(C.hannum),0) AS han_O, ifnull(sum(C.hannum * C.tanka),0) as hank_O ";
					$query .=" ,ifnull(sum(C.grenum),0)+ifnull(sum(C.c_grenum),0) AS gre_O, ifnull(sum(C.grenum * C.tanka),0)+ifnull(sum(C.c_grenum * C.tanka),0) as grek_O ";
					$query .=" ,ifnull(sum(C.mrenum),0)+ifnull(sum(C.c_mrenum),0) AS mre_O, ifnull(sum(C.mrenum * C.tanka),0)+ifnull(sum(C.c_mrenum * C.tanka),0) as mrek_O ";
					$query .=" ,count(C.venueno) AS cnt_C, ifnull(sum(C.tanka),0) as kin_C ";
					$query .=" from ";
					$query .=" ( ";
					//--- 会場情報　＆　PC販売 ---
					$query .=" SELECT CONCAT( REPLACE(A.buydt,'-',''), LPAD( A.lane, 2, '0' ) , '-' , A.branch ) as venueid ";
					$query .=" ,A.staff ,A.buydt ,A.lane ,A.companycd";
					$query .=" ,A.buynum,A.buykin ,A.goalnum, A.buynum - A.goalplan as goalclear ";
					$query .=" ,ifnull(sum(B.hannum),0) AS han_P, ifnull(sum(B.hannum * B.tanka),0) as hank_P ";
					$query .=" ,ifnull(sum(B.grenum),0)+ifnull(sum(B.c_grenum),0) AS gre_P, ifnull(sum(B.grenum * B.tanka),0)+ifnull(sum(B.c_grenum * B.tanka),0) as grek_P ";
					$query .=" ,ifnull(sum(B.mrenum),0)+ifnull(sum(B.c_mrenum),0) AS mre_P, ifnull(sum(B.mrenum * B.tanka),0)+ifnull(sum(B.c_mrenum * B.tanka),0) as mrek_P ";
					$query .=" FROM php_performance A ";
					$query .="   left outer join php_t_pc_hanbai B ";
					$query .="     ON CONCAT( REPLACE(A.buydt,'-',''), LPAD( A.lane, 2, '0' ) , '-' , A.branch ) = B.venueid ";
					$query .="    AND B.delflg = 0 ";
					$query .="    AND B.kbn = 1 ";
					$query .="    AND B.venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
					$query .=" WHERE A.buydt BETWEEN CAST(" . sprintf("'%s'", $p_date1) . " AS DATE)";
					$query .="   AND CAST(" . sprintf("'%s'", $p_date2) . " AS DATE)";
					$query .="   AND A.tm_flg = 0 ";
					$query .=" GROUP BY A.staff ,A.buydt ,A.lane ,A.companycd ,A.buynum ,A.buykin ,A.goalnum ";
					$query .=" ) AA ";
					//--- 会場情報　＆　PC販売 ---
					$query .="   left outer join php_t_pc_hanbai C ";
					$query .="     ON AA.venueid = C.venueid ";
					$query .="    AND C.delflg = 0 ";
					$query .="    AND C.tanka > 0";
					$query .="    AND C.kbn in (2,3,6)";
					$query .="    AND C.venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
					$query .=" GROUP BY AA.venueid, AA.staff ,AA.buydt ,AA.lane ,AA.companycd ,AA.goalnum ,AA.goalclear";
					$query .=" ,AA.buynum,AA.buykin ";
					$query .=" ,AA.han_P, AA.hank_P ";
					$query .=" ,AA.gre_P, AA.grek_P ";
					$query .=" ,AA.mre_P, AA.mrek_P ";
					$query .=" ) AAA ";
					//--- 備品販売 ---
					$query .=" left outer join php_t_pc_reserv D ";
					$query .="   ON AAA.venueid = D.venueid ";
					$query .=" GROUP BY AAA.venueid, AAA.staff ,AAA.buydt ,AAA.lane ,AAA.companycd ,AAA.goalnum ,AAA.goalclear";
					$query .=" ,AAA.buynum,AAA.buykin ";
					$query .=" ,AAA.han_P, AAA.hank_P ";
					$query .=" ,AAA.gre_P, AAA.grek_P ";
					$query .=" ,AAA.mre_P, AAA.mrek_P ";
					$query .=" ,AAA.han_O, AAA.hank_O ";
					$query .=" ,AAA.gre_O, AAA.grek_O ";
					$query .=" ,AAA.mre_O, AAA.mrek_O ";
					$query .=" ) AAAA ";
					//--- PC予約 ---
					$query .=" left outer join php_t_pc_returned E ";
					$query .="   ON AAAA.venueid = E.tvenueid ";
					$query .=" GROUP BY AAAA.venueid, AAAA.staff ,AAAA.buydt ,AAAA.lane ,AAAA.companycd ,AAAA.goalnum ,AAAA.goalclear";
					$query .=" ,AAAA.buynum,AAAA.buykin ";
					$query .=" ,AAAA.han_P, AAAA.hank_P ";
					$query .=" ,AAAA.gre_P, AAAA.grek_P ";
					$query .=" ,AAAA.mre_P, AAAA.mrek_P ";
					$query .=" ,AAAA.han_O, AAAA.hank_O ";
					$query .=" ,AAAA.gre_O, AAAA.grek_O ";
					$query .=" ,AAAA.mre_O, AAAA.mrek_O ";
					$query .=" ) AAAAA ";
					//--- PC返品 ---
					$query .=" left outer join php_t_option_returned F ";
					$query .=" ON AAAAA.venueid = F.tvenueid ";
					$query .=" AND F.tanka > 0";
					$query .=" GROUP BY AAAAA.venueid, AAAAA.staff ,AAAAA.buydt ,AAAAA.lane ,AAAAA.companycd ,AAAAA.goalnum ,AAAAA.goalclear";
					$query .=" ,AAAAA.buynum,AAAAA.buykin ";
					$query .=" ,AAAAA.han_P, AAAAA.hank_P ";
					$query .=" ,AAAAA.gre_P, AAAAA.grek_P ";
					$query .=" ,AAAAA.mre_P, AAAAA.mrek_P ";
					$query .=" ,AAAAA.ret_P, AAAAA.retk_P ";
					$query .=" ,AAAAA.han_O, AAAAA.hank_O ";
					$query .=" ,AAAAA.gre_O, AAAAA.grek_O ";
					$query .=" ,AAAAA.mre_O, AAAAA.mrek_O ";
					$query .=" ) AAAAAA ";
					//--- 備品返品 ---
					$query .=" left outer join php_t_pc_hanbai G ";
					$query .=" ON AAAAAA.venueid = G.venueid ";
					$query .=" AND G.delflg = 0 ";
					$query .=" AND G.tanka < 0";
					$query .=" AND G.kbn in (2,3,6)";
					$query .=" GROUP BY AAAAAA.venueid, AAAAAA.staff ,AAAAAA.buydt ,AAAAAA.lane ,AAAAAA.companycd ,AAAAAA.goalnum ,AAAAAA.goalclear";
					$query .=" ,AAAAAA.buynum,AAAAAA.buykin ";
					$query .=" ,AAAAAA.han_P, AAAAAA.hank_P ";
					$query .=" ,AAAAAA.gre_P, AAAAAA.grek_P ";
					$query .=" ,AAAAAA.mre_P, AAAAAA.mrek_P ";
					$query .=" ,AAAAAA.ret_P, AAAAAA.retk_P ";
					$query .=" ,AAAAAA.han_O, AAAAAA.hank_O ";
					$query .=" ,AAAAAA.gre_O, AAAAAA.grek_O ";
					$query .=" ,AAAAAA.mre_O, AAAAAA.mrek_O ";
					$query .=" ,AAAAAA.ret_O, AAAAAA.retk_O ";
					$query .=" ) AAAAAAA ";
					//--- 割引販売 ---
					$query .=" left outer join php_t_option_returned H ";
					$query .=" ON AAAAAAA.venueid = H.tvenueid ";
					$query .=" AND H.tanka < 0";
					$query .=" GROUP BY AAAAAAA.venueid, AAAAAAA.staff ,AAAAAAA.buydt ,AAAAAAA.lane ,AAAAAAA.companycd ,AAAAAAA.goalnum ,AAAAAAA.goalclear";
					$query .=" ,AAAAAAA.buynum,AAAAAAA.buykin ";
					$query .=" ,AAAAAAA.han_P, AAAAAAA.hank_P ";
					$query .=" ,AAAAAAA.gre_P, AAAAAAA.grek_P ";
					$query .=" ,AAAAAAA.mre_P, AAAAAAA.mrek_P ";
					$query .=" ,AAAAAAA.ret_P, AAAAAAA.retk_P ";
					$query .=" ,AAAAAAA.han_O, AAAAAAA.hank_O ";
					$query .=" ,AAAAAAA.gre_O, AAAAAAA.grek_O ";
					$query .=" ,AAAAAAA.mre_O, AAAAAAA.mrek_O ";
					$query .=" ,AAAAAAA.ret_O, AAAAAAA.retk_O ";
					$query .=" ,AAAAAAA.han_W, AAAAAAA.hank_W ";
					$query .=" ,AAAAAAA.gre_W, AAAAAAA.grek_W ";
					$query .=" ,AAAAAAA.mre_W, AAAAAAA.mrek_W ";
					$query .=" ) AAAAAAAA ";
					//--- 割引返品 ---
					$query .="   left outer join php_t_pc_hanbai I ";
					$query .="     ON AAAAAAAA.venueid = I.venueid ";
					$query .="    AND I.delflg = 0 ";
					$query .="    AND I.kbn = 5 ";
					$query .=" GROUP BY AAAAAAAA.venueid, AAAAAAAA.staff ,AAAAAAAA.buydt ,AAAAAAAA.lane ,AAAAAAAA.companycd ,AAAAAAAA.goalnum ,AAAAAAAA.goalclear";
					$query .=" ,AAAAAAAA.buynum,AAAAAAAA.buykin ";
					$query .=" ,AAAAAAAA.han_P, AAAAAAAA.hank_P ";
					$query .=" ,AAAAAAAA.gre_P, AAAAAAAA.grek_P ";
					$query .=" ,AAAAAAAA.mre_P, AAAAAAAA.mrek_P ";
					$query .=" ,AAAAAAAA.ret_P, AAAAAAAA.retk_P ";
					$query .=" ,AAAAAAAA.han_O, AAAAAAAA.hank_O ";
					$query .=" ,AAAAAAAA.gre_O, AAAAAAAA.grek_O ";
					$query .=" ,AAAAAAAA.mre_O, AAAAAAAA.mrek_O ";
					$query .=" ,AAAAAAAA.ret_O, AAAAAAAA.retk_O ";
					$query .=" ,AAAAAAAA.han_W, AAAAAAAA.hank_W ";
					$query .=" ,AAAAAAAA.gre_W, AAAAAAAA.grek_W ";
					$query .=" ,AAAAAAAA.mre_W, AAAAAAAA.mrek_W ";
					//--- PCレンタル ---
					$query .=" ) M ";

					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($query))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$grenumSum = 0;
					$grekinSum = 0;
					$mrenumSum = 0;
					$mrekinSum = 0;
					while ($row = $rs->fetch_array()) {
						$hannumSum = $row['han_P'];
						$hankinSum = $row['hank_P'];
						$grenumSum = $row['gre_P'];
						$grekinSum = $row['grek_P'];
						$mrenumSum = $row['mre_P'];
						$mrekinSum = $row['mrek_P'];
						$rentnumSum = $row['rent_P'];
						$rentkinSum = $row['rentk_P'];
						$rentgrenumSum = $row['rentgre_P'];
						$rentgrekinSum = $row['rentgrek_P'];
						$rentmrenumSum = $row['rentmre_P'];
						$rentmrekinSum = $row['rentmrek_P'];
						$bhannumSum = $row['han_O'];
						$bhankinSum = $row['hank_O'];
						$retnumSum = $row['ret_P'];
						$retkinSum = $row['retk_P'];
						$whannumSum = $row['han_W'];
						$whankinSum = $row['hank_W'];
					}
					//----- 返品数計算
					$buynumSum -= $retnumSum;
					$buykinSum -= $retkinSum;
					//平均単価計算
					$buykinAvg = 0;
					if($buynumSum>0 && $buykinSum > 0){
						$buykinAvg = ROUND($buykinSum / $buynumSum);
					}

					//----- データ抽出(会場担当者返品合計取得 本部)
					$query = " SELECT IFNULL(SUM(A.returnnum), 0) AS returnnumSum, IFNULL(SUM(A.returnkin), 0) AS returnkinSum ";
					$query .=" FROM php_returned A";
					$query .=" WHERE A.companycd in ('Z','F')";
					$query .=" AND A.returnReas IS NULL";
					$query .=" AND A.proceflg = " . sprintf("'%s'", $p_week);
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//					if(! $rs = mysql_query($query, $db)){
					if (!($rs = $db->query($query))) {
//						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$returnnumSum = 0;
					$returnkinSum = 0;
// ----- 2019.06 ver7.0対応
//					while ($row = @mysql_fetch_array($rs)){
					while ($row = $rs->fetch_array()) {
						$returnnumSum = $row['returnnumSum'];
						$returnkinSum = $row['returnkinSum'];
					}
					//----- データ抽出(会場担当者電話返品合計取得 本部)
					$query = " SELECT count(*) AS returnnumSum, IFNULL(SUM(A.cash), 0) AS returnkinSum ";
					$query .=" FROM php_pc_failure A";
					$query .=" WHERE A.companycd in ('Z')";
					$query .=" AND A.receptionday BETWEEN CAST(" . sprintf("'%s'", $p_date1) . " AS DATE)";
					$query .=" AND CAST(" . sprintf("'%s'", $p_date2) . " AS DATE)";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//					if(! $rs = mysql_query($query, $db)){
					if (!($rs = $db->query($query))) {
//						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$rettelnumSum = 0;
					$rettelkinSum = 0;
// ----- 2019.06 ver7.0対応
//					while ($row = @mysql_fetch_array($rs)){
					while ($row = $rs->fetch_array()) {
						$rettelnumSum = $row['returnnumSum'];
						$rettelkinSum = $row['returnkinSum'];
					}
					//----- データ抽出(会場担当者返品合計取得 MSO)
					$query = " SELECT IFNULL(SUM(A.returnnum), 0) AS returnnumSum, IFNULL(SUM(A.returnkin), 0) AS returnkinSum ";
					$query .=" FROM php_returned A";
					$query .=" WHERE A.companycd = 'M'";
					$query .=" AND A.returnReas IS NULL";
					$query .=" AND A.proceflg = " . sprintf("'%s'", $p_week);
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//					if(! $rs = mysql_query($query, $db)){
					if (!($rs = $db->query($query))) {
//						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$returnnumSum_m = 0;
					$returnkinSum_m = 0;
// ----- 2019.06 ver7.0対応
//					while ($row = @mysql_fetch_array($rs)){
					while ($row = $rs->fetch_array()) {
						$returnnumSum_m = $row['returnnumSum'];
						$returnkinSum_m = $row['returnkinSum'];
					}
					//----- データ抽出(会場担当者返品合計取得 MSO)
					$query = " SELECT count(*) AS returnnumSum, IFNULL(SUM(A.cash), 0) AS returnkinSum ";
					$query .=" FROM php_pc_failure A";
					$query .=" WHERE A.companycd in ('M')";
					$query .=" AND A.receptionday BETWEEN CAST(" . sprintf("'%s'", $p_date1) . " AS DATE)";
					$query .=" AND CAST(" . sprintf("'%s'", $p_date2) . " AS DATE)";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//					if(! $rs = mysql_query($query, $db)){
					if (!($rs = $db->query($query))) {
//						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$rettelnumSum_m = 0;
// ----- 2019.06 ver7.0対応
//					while ($row = @mysql_fetch_array($rs)){
					while ($row = $rs->fetch_array()) {
						$rettelnumSum_m = $row['returnnumSum'];
					}
					// ================================================
					// ■　□　■　□　総合計　■　□　■　□
					// ================================================
					//----- 台数計算(返品数考慮)
					$buynumAll = $buynumAll - $returnAll;
					 $put_style = "";
					if($buynumSum - $goalnumSum > -1){
					 $put_style = "style='background-color:#FFDDE4;'";
					 }
					 ?>
					<table class="tbh" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
						<tr><td class="category"><strong>■◇■総合計■◇■</strong></td></tr>
					</table>
					<table class="tbd" cellspacing="0" cellpadding="0" border="0" style="{padding-bottom: 40px;}" summary="ベーステーブル">
						<tr>
							<th class="tbd_th" <? echo $put_style ?>><strong>期間</strong></td>
							<td class="tbd_arb"></td>
							<td class="tbd_td" <? echo $put_style ?>>
								<input type="text" size="10" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo date('Y/m/d', strtotime($buydtMin)) ?>">
								 ～
								<input type="text" size="10" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo date('Y/m/d', strtotime($buydtMax) )?>">
							</td>
						</tr>
						<tr>
							<th class="tbd_th" <? echo $put_style ?>><strong>総販売台数/金額</strong></td>
							<td class="tbd_arb"></td>
							<td class="tbd_td" <? echo $put_style ?>>
								<input type="text" size="10" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($buynumSum) ?>">台
								<input type="text" class="money" size="20" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($buykinSum) ?> ">円<br>
							</td>
						</tr>
						<tr>
							<th class="tbd_th" <? echo $put_style ?>> <strong>総目標（クリア台数）</strong></td>
							<td class="tbd_arb"></td>
							<td class="tbd_td" <? echo $put_style ?>>
								<input type="text" size="10" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($goalnumSum) ?>">台（
								<input type="text" class="money" size="20" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($buynumSum-$goalnumSum) ?> ">台）<br>
							</td>
						</tr>
						<tr>
							<th class="tbd_th" <? echo $put_style ?>><strong>平均単価</strong></td>
							<td class="tbd_arb"></td>
							<td class="tbd_td" <? echo $put_style ?>>
								<input type="text" class="money" size="20" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($buykinAvg) ?>">円
							</td>
						</tr>
					</table>
					<h2>販売実績詳細</h2>
					<table class="tbh" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
						<tr><td class="category"><strong>■◇■合計■◇■</strong></td></tr>
					</table>
					<table class="tbd" cellspacing="0" cellpadding="0" border="0" style="{padding-bottom: 40px;}" summary="ベーステーブル">
						<tr>
							<th class="tbd_th"><strong>現物販売台数/金額</strong></td>
							<td class="tbd_arb"></td>
							<td class="tbd_td">
								<input type="text" size="10" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($hannumSum) ?>">台
								<input type="text" class="money" size="20" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($hankinSum) ?>">円<br>
							</td>
						</tr>
						<tr>
							<th class="tbd_th"><strong>現物予約台数/金額</strong></td>
							<td class="tbd_arb"></td>
							<td class="tbd_td">
								<input type="text" size="10" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($grenumSum) ?>">台
								<input type="text" class="money" size="20" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($grekinSum) ?>">円<br>
							</td>
						</tr>
						<tr>
							<th class="tbd_th"><strong>見本予約台数/金額</strong></td>
							<td class="tbd_arb"></td>
							<td class="tbd_td">
								<input type="text" size="10" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($mrenumSum) ?>">台
								<input type="text" class="money" size="20" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($mrekinSum) ?>">円<br>
							</td>
						</tr>
						<tr>
							<th class="tbd_th"><strong>下取り台数/金額</strong></td>
							<td class="tbd_arb"></td>
							<td class="tbd_td">
								<input type="text" size="10" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($rentnumSum) ?>">台
								<input type="text" class="money" size="20" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($rentkinSum) ?>">円<br>
							</td>
						</tr>
						<tr>
							<th class="tbd_th"><strong>下取り現物予約台数/金額</strong></td>
							<td class="tbd_arb"></td>
							<td class="tbd_td">
								<input type="text" size="10" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($rentgrenumSum) ?>">台
								<input type="text" class="money" size="20" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($rentgrekinSum) ?>">円<br>
							</td>
						</tr>
						<tr>
							<th class="tbd_th"><strong>下取り見本予約台数/金額</strong></td>
							<td class="tbd_arb"></td>
							<td class="tbd_td">
								<input type="text" size="10" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($rentmrenumSum) ?>">台
								<input type="text" class="money" size="20" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($rentmrekinSum) ?>">円<br>
							</td>
						</tr>
						<tr>
							<th class="tbd_th"><strong>内　備品販売台数/金額</strong></td>
							<td class="tbd_arb"></td>
							<td class="tbd_td">
								<input type="text" size="10" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($bhannumSum) ?>">個
								<input type="text" class="money" size="20" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($bhankinSum) ?>">円<br>
							</td>
						</tr>
						</table>

						<!-- ================================================
						// ■　□　■　□　返品総合計表示　■　□　■　□
						// ================================================-->
						<table class="tbh" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
								<tr><td class="category"><strong>■◇■返品合計■◇■</strong></td></tr>
						</table>
						<table class="tbd" cellspacing="0" cellpadding="0" border="0" style="{padding-bottom: 40px;}" summary="ベーステーブル">
						<tr>
							<th class="tbd_th"><strong>会場返品台数/金額</strong></td>
							<td class="tbd_arb"></td>
							<td class="tbd_td">
								<input type="text" size="10" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo $retnumSum ?>">台
								<input type="text" class="money" size="20" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($retkinSum) ?>">円
							</td>
						</tr>
						<tr>
							<th class="tbd_th"><strong>電話返品台数/金額</strong></td>
							<td class="tbd_arb"></td>
							<td class="tbd_td">
								<input type="text" size="10" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo $rettelnumSum ?>">台
								<input type="text" class="money" size="20" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($rettelkinSum) ?>">円
							</td>
						</tr>
					</table>
					
					<h2>担当者別実績</h2><br>
					<!--合計-->
					<table class="tbh" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
						<tr><td class="category"><strong>■◇■総合計■◇■</strong></td></tr>
					</table>
					<p><b>※返品情報は実績がある場合のみ表示します。<br>
					<b>※返品実績は台数、金額に含まれています。<br>
					<b>※目標台数の下に目標クリア数の合計が表示されます。</b></p>
					<table class="tbt" cellspacing="0" cellpadding="0" border="0" style="{padding-bottom: 40px;}" summary="ベーステーブル">
						<!--ヘッダー-->
						<tr>
							<th class="tbd_th_p1" ROWSPAN="3"><strong>ﾚｰﾝ</strong></th>
							<th class="tbd_th_p1" ROWSPAN="3"><strong>担当者</strong></th>
							<th class="tbd_th_p1" ROWSPAN="3"><strong>総在庫</strong></th>
							<th class="tbd_th_p1" ROWSPAN="3"><strong>開催<br>回数</strong></th>
							<th class="tbd_th_p1" ROWSPAN="3"><strong>目標<br>台数<br>(ｸﾘｱ)</strong></th>
							<th class="tbd_th_p1" COLSPAN="1"><strong>総計</strong></th>
							<th class="tbd_th_p1" COLSPAN="2"><strong>販売</strong></th>
							<th class="tbd_th_p1" COLSPAN="2"><strong>下取り</strong></th>
							<th class="tbd_th_p1" COLSPAN="1" ROWSPAN="2"><strong>備品</strong></th>
						</tr>
						<tr>
							<th class="tbd_th_p2" ROWSPAN="2"><strong>台数<br>金額<br>平均単価</strong></th>
							<th class="tbd_th_p1" COLSPAN="1"><strong>現物</strong></th>
							<th class="tbd_th_p1" COLSPAN="1"><strong>予約</strong></th>
							<th class="tbd_th_p1" COLSPAN="1"><strong>販売</strong></th>
							<th class="tbd_th_p1" COLSPAN="1"><strong>予約</strong></th>
						</tr>
						<tr>
							<th class="tbd_th_p2"><strong>台数<br>金額</strong></th>
							<th class="tbd_th_p2"><strong>台数<br>金額</strong></th>
							<th class="tbd_th_p2"><strong>台数<br>金額</strong></th>
							<th class="tbd_th_p2"><strong>台数<br>金額</strong></th>
							<th class="tbd_th_p2"><strong>個数<br>金額</strong></th>
						</tr>
						<?php
						// ================================================
						// ■　□　■　□　担当者別合計表示　■　□　■　□
						// ================================================
						//----- データ抽出
						$query = " SELECT a_table.lane "; //レーン
						$query .= " , a_table.staff "; //担当者
						$query .= " , a_table.buynum "; //総計.台数
						$query .= " , a_table.buykin "; //総計.金額
						$query .= " , a_table.goalnum "; //目標台数
						$query .= " , a_table.eventflg "; //イベントフラグ
						$query .= " , IFNULL(b_table.stocknum,0) as stocknum "; //総在庫
						$query .= " , IFNULL(c_table.hannum,0) as hannum "; //現物.台数
						$query .= " , IFNULL(c_table.hankin,0) as hankin "; //現物.金額
						$query .= " , IFNULL(c_table.grenum,0) as grenum "; //予約.台数
						$query .= " , IFNULL(c_table.grekin,0) as grekin "; //予約.金額
						$query .= " , (IFNULL(c_table.mrenum,0) + IFNULL(j_table.r_mrenum,0)) as mrenum "; //予約.台数
						$query .= " , (IFNULL(c_table.mrekin,0) + IFNULL(j_table.r_mrekin,0)) as mrekin "; //予約.金額
						$query .= " , IFNULL(l_table.rentnum,0) as rentnum "; //レンタル.台数
						$query .= " , IFNULL(l_table.rentkin,0) as rentkin "; //レンタル.金額
						$query .= " , IFNULL(l_table.rentgrenum,0) as rentgrenum "; //レンタル現物予約.台数
						$query .= " , IFNULL(l_table.rentgrekin,0) as rentgrekin "; //レンタル現物予約.金額
						$query .= " , IFNULL(l_table.rentmrenum,0) as rentmrenum "; //レンタル見本予約.台数
						$query .= " , IFNULL(l_table.rentmrekin,0) as rentmrekin "; //レンタル見本予約.金額
						$query .= " , IFNULL(f_table.retnum,0) as retnum "; //返品.台数
						$query .= " , IFNULL(f_table.retkin,0) as retkin "; //返品.金額
						$query .= " , IFNULL(g_table.option_hannum,0) as option_hannum "; //オプション.現物個数
						$query .= " , IFNULL(g_table.option_hankin,0) as option_hankin "; //オプション.現物金額
						$query .= " , IFNULL(g_table.option_grenum,0) as option_grenum "; //オプション.予約個数
						$query .= " , IFNULL(g_table.option_grekin,0) as option_grekin "; //オプション.予約金額
						$query .= " , IFNULL(g_table.option_mrenum,0) as option_mrenum "; //オプション.予約個数
						$query .= " , IFNULL(g_table.option_mrekin,0) as option_mrekin "; //オプション.予約金額
						$query .= " , IFNULL(i_table.option_retnum,0) as option_retnum "; //オプション.返品個数
						$query .= " , IFNULL(i_table.option_retkin,0) as option_retkin "; //オプション.返品金額
						$query .= " , IFNULL(h_table.waribiki_hankin,0) as waribiki_hankin "; //割引.現物金額
						$query .= " , IFNULL(h_table.waribiki_grekin,0) as waribiki_grekin "; //割引.予約金額
						$query .= " , IFNULL(h_table.waribiki_mrekin,0) as waribiki_mrekin "; //割引.予約金額
						$query .= " , IFNULL(k_table.waribiki_retkin,0) as waribiki_retkin "; //割引.返品金額
						$query .= " FROM";
						$query .= " (";
						//レーン情報
						$query .= " SELECT A.lane, A.staff, A.buynum, A.buykin, A.goalnum, A.eventflg, CONCAT(REPLACE(A.buydt,'-',''), LPAD(A.lane,2, '0'), '-', A.branch) as venueid";
						$query .= " FROM php_performance A";
						$query .= " WHERE A.buydt BETWEEN CAST(" . sprintf("'%s'", $p_date1) . " AS DATE)";
						$query .= " AND CAST(" . sprintf("'%s'", $p_date2) . " AS DATE)";
						$query .= " AND A.tm_flg = 0 ";
						$query .= " ) a_table ";
						$query .= " LEFT OUTER JOIN ";
						$query .= " (";
						//在庫情報
						$query .= " SELECT B.staff, SUM(B.stocknum) as stocknum ";
						$query .= " FROM(";
						$query .= " SELECT B1.staff, COUNT(B1.modelnum) as stocknum ";
						$query .= " FROM php_t_pc_zaiko B1 ";
						$query .= " WHERE B1.hanbaiflg=0 AND B1.delflg=0 GROUP BY B1.staff";
						$query .= " UNION ALL ";
						$query .= " SELECT B2.staff, (SUM(B2.suryou)-SUM(B2.receive)) as stocknum ";
						$query .= " FROM php_s_pc_zaiko B2 ";
						$query .= " LEFT OUTER JOIN php_performance B3 ON B2.venueid=concat(DATE_FORMAT(B3.buydt , '%Y%m%d' ), LPAD(B3.lane,2,'0'), '-' , B3.branch) ";
						$query .= " WHERE B2.receiveflg=0 ";
						$query .= " AND B3.buydt BETWEEN CAST(" . sprintf("'%s'", $p_date1) . " AS DATE)";
						$query .= " AND CAST(" . sprintf("'%s'", $p_date2) . " AS DATE)";
						$query .= " AND B2.delflg=0 ";
						$query .= " GROUP BY B2.staff) B";
						$query .= " GROUP BY B.staff";
						$query .= " ) b_table ON a_table.staff=b_table.staff ";
						$query .= " LEFT OUTER JOIN ";
						$query .= " (";
						//販売
						$query .= " SELECT C.venueid ";
						$query .= " , SUM(C.hannum) as hannum ";
						$query .= " , SUM(CASE WHEN C.hannum > 0 THEN C.tanka ELSE 0 END) as hankin ";
						$query .= " , SUM(C.grenum)+SUM(C.c_grenum) as grenum ";
						$query .= " , SUM(CASE WHEN C.grenum > 0 OR C.c_grenum > 0 THEN C.tanka ELSE 0 END) as grekin ";
						$query .= " , SUM(C.mrenum)+SUM(C.c_mrenum) as mrenum ";
						$query .= " , SUM(CASE WHEN C.mrenum > 0 OR C.c_mrenum > 0 THEN C.tanka ELSE 0 END) as mrekin ";
						$query .= " FROM php_t_pc_hanbai C";
						$query .= " WHERE 1 ";
						$query .= " AND C.delflg=0";
						$query .= " AND C.kbn =1 ";
						$query .= " AND C.venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
						$query .= " GROUP BY C.venueid";
						$query .= " ) c_table ON a_table.venueid=c_table.venueid";
						$query .= " LEFT OUTER JOIN ";
						$query .= " (";
						//返品
						$query .= " SELECT  F.tvenueid, SUM(F.hannum) as retnum, SUM(F.tanka) as retkin";
						$query .= " FROM php_t_pc_returned F";
						$query .= " GROUP BY F.tvenueid";
						$query .= " )as f_table ON a_table.venueid=f_table.tvenueid";
						$query .= " LEFT OUTER JOIN ";
						$query .= " (";
						//備品販売
						$query .= " SELECT G.venueid ";
						$query .= " , SUM(G.hannum) as option_hannum ";
						$query .= " , SUM(CASE WHEN G.hannum > 0 THEN G.tanka ELSE 0 END) as option_hankin ";
						$query .= " , SUM(G.grenum)+SUM(G.c_grenum) as option_grenum ";
						$query .= " , SUM(CASE WHEN G.grenum > 0 OR G.c_grenum > 0 THEN G.tanka ELSE 0 END) as option_grekin ";
						$query .= " , SUM(G.mrenum)+SUM(G.c_mrenum) as option_mrenum ";
						$query .= " , SUM(CASE WHEN G.mrenum > 0 OR G.c_mrenum > 0 THEN G.tanka ELSE 0 END) as option_mrekin ";
						$query .= " FROM php_t_pc_hanbai G";
						$query .= " WHERE 1 ";
						$query .= " AND G.delflg=0";
						$query .= " AND G.kbn in (2,3,6)";
						$query .= " AND G.tanka > 0";
						$query .= " AND G.venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
						$query .= " GROUP BY G.venueid";
						$query .= " ) g_table ON a_table.venueid=g_table.venueid";
						$query .= " LEFT OUTER JOIN ";
						$query .= " (";
						//備品返品
						$query .= " SELECT  I.tvenueid, SUM(I.hannum) as option_retnum, SUM(I.tanka) as option_retkin";
						$query .= " FROM php_t_option_returned I";
						$query .= " WHERE I.tanka > 0";
						$query .= " GROUP BY I.tvenueid";
						$query .= " ) i_table ON a_table.venueid=i_table.tvenueid";
						$query .= " LEFT OUTER JOIN ";
						$query .= " (";
						//見本予約(予約テーブル)
						$query .= " SELECT J.venueid, SUM(J.mrenum) as r_mrenum, SUM(J.tanka) as r_mrekin";
						$query .= " FROM php_t_pc_reserv J";
						$query .= " WHERE J.delflg=0";
						$query .= " AND J.venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
						$query .= " GROUP BY J.venueid";
						$query .= " ) j_table ON a_table.venueid=j_table.venueid";
						$query .= " LEFT OUTER JOIN ";
						$query .= " (";
						//割引販売
						$query .= " SELECT H.venueid ";
						$query .= " , SUM(H.hannum) as waribiki_hannum ";
						$query .= " , SUM(CASE WHEN H.hannum > 0 THEN H.tanka ELSE 0 END) as waribiki_hankin ";
						$query .= " , SUM(H.grenum)+SUM(H.c_grenum) as waribiki_grenum ";
						$query .= " , SUM(CASE WHEN H.grenum > 0 OR H.grenum > 0 THEN H.tanka ELSE 0 END) as waribiki_grekin ";
						$query .= " , SUM(H.mrenum)+SUM(H.c_mrenum) as waribiki_mrenum ";
						$query .= " , SUM(CASE WHEN H.mrenum > 0 OR H.c_mrenum > 0 THEN H.tanka ELSE 0 END) as waribiki_mrekin ";
						$query .= " FROM php_t_pc_hanbai H";
						$query .= " WHERE 1 ";
						$query .= " AND H.delflg=0";
						$query .= " AND H.kbn in (2,3,6)";
						$query .= " AND H.tanka < 0";
						$query .= " AND H.venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
						$query .= " GROUP BY H.venueid";
						$query .= " ) h_table ON a_table.venueid=h_table.venueid";
						$query .= " LEFT OUTER JOIN ";
						$query .= " (";
						//割引返品
						$query .= " SELECT  K.tvenueid, SUM(K.tanka) as waribiki_retkin";
						$query .= " FROM php_t_option_returned K";
						$query .= " WHERE K.tanka < 0";
						$query .= " GROUP BY K.tvenueid";
						$query .= " ) k_table ON a_table.venueid=k_table.tvenueid";
						$query .= " LEFT OUTER JOIN ";
						$query .= " (";
						//販売
						$query .= " SELECT L.venueid ";
						$query .= " , SUM(L.hannum) as rentnum ";
						$query .= " , SUM(CASE WHEN L.hannum > 0 THEN L.tanka ELSE 0 END) as rentkin ";
						$query .= " , SUM(L.grenum)+SUM(L.c_grenum) as rentgrenum ";
						$query .= " , SUM(CASE WHEN L.grenum > 0 OR L.c_grenum > 0 THEN L.tanka ELSE 0 END) as rentgrekin ";
						$query .= " , SUM(L.mrenum)+SUM(L.c_mrenum) as rentmrenum ";
						$query .= " , SUM(CASE WHEN L.mrenum > 0 OR L.c_mrenum > 0 THEN L.tanka ELSE 0 END) as rentmrekin ";
						$query .= " FROM php_t_pc_hanbai L";
						$query .= " WHERE 1 ";
						$query .= " AND L.delflg=0";
						$query .= " AND L.kbn=5";
						$query .= " AND L.venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
						$query .= " GROUP BY L.venueid";
						$query .= " ) l_table ON a_table.venueid=l_table.venueid";
						$query .= " ORDER BY a_table.lane, a_table.staff  ,a_table.eventflg";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs = $db->query($query))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						$staff = "";
						$event = "";
						$g_staff = "";
						$rownum = 0;
						while ($row = $rs->fetch_array()) {

							if(($staff <> $row['staff'] || $event <> $row['eventflg']) && $staff <> ""){
								//明細内容設定
								set_staff_performance();
								$g_staff = $staff;
								$rownum = $rownum + 1;
							}
						?>
							<?php
								//担当者
								$staff = $row['staff'];
								//イベント
								$event = $row['eventflg'];
								//レーン
								$lane = $row['lane'];
								//目標台数
								$goalnum = $row['goalnum'];
								//総在庫
								$stocknum = $row['stocknum'];
								if($row['buynum'] > 0){
									$nbuynum = $row['buynum'];
									$nhannum = $row['hannum'] + $row['grenum'] + $row['mrenum'];
								}
								++$kaisainum;
								$buynum += $row['buynum'];
								$hannum += $row['hannum']; //現物．台数
								$resnum += ($row['grenum'] + $row['mrenum']); //予約．台数
								$retnum += $row['retnum']; //返品．台数
								$hankin += ($row['hankin'] + $row['option_hankin'] + $row['waribiki_hankin']);
								$reskin += ($row['grekin'] + $row['mrekin'] + $row['option_grekin']  + $row['option_mrekin'] + $row['waribiki_grekin']  + $row['waribiki_mrekin']);
								$retkin += ($row['retkin'] + $row['option_retkin'] + $row['waribiki_retkin']);
								$hanall += ($row['buynum']  - $row['retnum']);
								$hankinall += ($row['buykin'] - ($row['retkin'] + $row['option_retkin'] + $row['waribiki_retkin']));

								$opnum += $row['option_hannum'] + $row['option_grenum'] + $row['option_mrenum'];
								$opkin += $row['option_hankin'] + $row['option_grekin'] + $row['option_mrekin'];
								$opretnum += $row['option_retnum'];
								$opretkin += $row['option_retkin'];
								$rentnum += $row['rentnum']; //レンタル．台数
								$rentkin += $row['rentkin'] + $row['waribiki_hankin']; //レンタル．台数
								$rentresnum += $row['rentgrenum']; //レンタル現物．台数
								$rentreskin += $row['rentgrekin'] + $row['waribiki_hankin']  + $row['waribiki_mrekin']; //レンタル現物．台数
								$rentresnum += $row['rentmrenum']; //レンタル見本．台数
								$rentreskin += $row['rentmrekin']; //レンタル見本．台数
							?>
					<?php
						}
						if ($staff <> "") {
							//明細内容設定
							set_staff_performance();
						}
					?>
					</table>
					<h2>カテゴリ別実績　※全レーンの総台数</h2><br>
					<table class="tbt" cellspacing="0" cellpadding="0" border="0" style="{padding-bottom: 40px;}" summary="ベーステーブル">
						<!--ヘッダー-->
						<tr>
							<th class="tbd_th_p1"><strong>型番</strong></th>
							<th class="tbd_th_p1"><strong>販売台数</strong></th>
							<th class="tbd_th_p1"><strong>現物予約<Br>台数</strong></th>
							<th class="tbd_th_p1"><strong>見本予約<Br>台数</strong></th>
							<th class="tbd_th_p1"><strong>下取台数</strong></th>
							<th class="tbd_th_p1"><strong>下取現物<Br>台数</strong></th>
							<th class="tbd_th_p1"><strong>下取見本<Br>台数</strong></th>
							<th class="tbd_th_p1"><strong>返品台数</strong></th>
						</tr>
						<?php
						// ================================================
						// ■　□　■　□　担当者別合計表示　■　□　■　□
						// ================================================
						//----- データ抽出
						$query = " SELECT  ";
						$query .= "   a_table.modelnum, a_table.kbn, a_table.g_b_code "; //型番
						$query .= " , SUM(IFNULL(c_table.hannum,0)) as hannum "; //現物.台数
						$query .= " , SUM(IFNULL(c_table.grenum,0)) as grenum "; //予約.台数
						$query .= " , SUM(IFNULL(c_table.mrenum,0) + IFNULL(j_table.r_mrenum,0)) as mrenum "; //予約.台数
						$query .= " , SUM(IFNULL(f_table.retnum,0)) as retnum "; //返品.台数
						$query .= " , SUM(IFNULL(k_table.rentnum,0)) as rentnum "; //レンタル.台数
						$query .= " , SUM(IFNULL(k_table.rentgrenum,0)) as rentgrenum "; //レンタル予約.台数
						$query .= " , SUM(IFNULL(k_table.rentmrenum,0)) as rentmrenum "; //レンタル予約.台数
						$query .= " FROM";
						$query .= " (";
						//型番
						$query .= " SELECT A.modelnum, A.kbn ";
						$query .= " , CASE ";
						$query .= "  WHEN A.modelnum LIKE 'Ci5-8GB-SSD%' THEN SUBSTRING(A.b_code,1,2)";
						$query .= " ELSE '' END";
						$query .= " as g_b_code";
						$query .= " FROM php_t_pc_hanbai A";
						$query .= " WHERE 1 ";
						$query .= " AND A.delflg=0";
						$query .= " AND A.kbn in (1,3,5)";
						$query .= " AND A.venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
						$query .= " GROUP BY A.modelnum, g_b_code";
						$query .= " ) a_table ";
						$query .= " LEFT OUTER JOIN ";
						$query .= " (";
						//販売
						$query .= " SELECT C.venueid ";
						$query .= " , C.modelnum ";
						$query .= " , CASE ";
						$query .= "  WHEN C.modelnum LIKE 'Ci5-8GB-SSD%' THEN SUBSTRING(C.b_code,1,2)";
						$query .= " ELSE '' END";
						$query .= " as g_b_code";
						$query .= " , SUM(C.hannum) as hannum ";
						$query .= " , SUM(C.grenum)+SUM(C.c_grenum) as grenum ";
						$query .= " , SUM(C.mrenum)+SUM(C.c_mrenum) as mrenum ";
						$query .= " FROM php_t_pc_hanbai C";
						$query .= " WHERE 1 ";
						$query .= " AND C.delflg=0";
						$query .= " AND (C.kbn=1 OR C.kbn=3)";
						$query .= " AND C.venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
						$query .= " GROUP BY C.modelnum, g_b_code";
						$query .= " )as c_table ON  a_table.modelnum=c_table.modelnum AND a_table.g_b_code=c_table.g_b_code ";
						$query .= " LEFT OUTER JOIN ";
						$query .= " (";
						//返品
						$query .= " SELECT  F.tvenueid, F.modelnum, 0 as g_b_code, SUM(F.hannum) as retnum ";
						$query .= " FROM php_t_pc_returned F";
						$query .= " WHERE F.tvenueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
						$query .= " GROUP BY F.modelnum ";
						$query .= " )as f_table ON  a_table.modelnum=f_table.modelnum AND a_table.g_b_code=f_table.g_b_code  ";
						$query .= " LEFT OUTER JOIN ";
						$query .= " (";
						//見本予約(予約テーブル)
						$query .= " SELECT J.venueid, J.modelnum, 0 as g_b_code, SUM(J.mrenum) as r_mrenum ";
						$query .= " FROM php_t_pc_reserv J";
						$query .= " WHERE J.delflg=0";
						$query .= " AND J.venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
						$query .= " GROUP BY J.modelnum ";
						$query .= " ) j_table ON a_table.modelnum=j_table.modelnum AND a_table.g_b_code=j_table.g_b_code  ";
						$query .= " LEFT OUTER JOIN ";
						$query .= " (";
						//レンタル
						$query .= " SELECT K.venueid ";
						$query .= " , K.modelnum ";
						$query .= " , CASE ";
						$query .= "  WHEN K.modelnum LIKE 'Ci5-8GB-SSD%' THEN SUBSTRING(K.b_code,1,2)";
						$query .= " ELSE '' END";
						$query .= " as g_b_code";
						$query .= " , SUM(K.hannum) as rentnum ";
						$query .= " , SUM(K.grenum)+SUM(K.c_grenum) as rentgrenum ";
						$query .= " , SUM(K.mrenum)+SUM(K.c_mrenum) as rentmrenum ";
						$query .= " FROM php_t_pc_hanbai K";
						$query .= " WHERE 1 ";
						$query .= " AND K.delflg=0";
						$query .= " AND K.kbn=5";
						$query .= " AND K.venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
						$query .= " GROUP BY K.modelnum, SUBSTRING(K.b_code, 0, 2)";
						$query .= " ) k_table ON a_table.modelnum=k_table.modelnum AND a_table.g_b_code=k_table.g_b_code  ";
						$query .= " GROUP BY a_table.modelnum, a_table.g_b_code  ";
						$query .= " ORDER BY a_table.kbn, a_table.modelnum, a_table.g_b_code ";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs = $db->query($query))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						$hannum = 0;
						$retnum = 0;
						$rowcnt = 0;
						while ($row = $rs->fetch_array()) {
							if ($row['modelnum'] <> ""){
						?>
						<? if(($rowcnt % 2) == 0){?>
						<tr style="background-color:#EDEDED;">
						<? }else{?>
						<tr style="background-color:white;">
						<? }?>
							<td class="tbd_td_p5_l"><? echo $row['modelnum']; ?><? if(mb_substr($row['modelnum'],0,11) == "Ci5-8GB-SSD"){echo "（出荷元：".$arr_factory[$row['g_b_code']]."）";} ?></td>
							<td class="tbd_td_p4_r"><? echo number_format($row['hannum']) ?></td>
							<td class="tbd_td_p4_r"><? echo number_format($row['grenum']) ?></td>
							<td class="tbd_td_p4_r"><? echo number_format($row['mrenum']) ?></td>
							<td class="tbd_td_p4_r"><? echo number_format($row['rentnum']) ?></td>
							<td class="tbd_td_p4_r"><? echo number_format($row['rentgrenum']) ?></td>
							<td class="tbd_td_p4_r"><? echo number_format($row['rentmrenum']) ?></td>
							<td class="tbd_td_p4_r"><? echo number_format($row['retnum']) ?></td>
						</tr>
							<?	
								if($row['kbn'] <> 3){
									$hannum += $row['hannum'] + $row['grenum'] + $row['mrenum'] + $row['rentnum'] + $row['rentmrenum'] + $row['rentgrenum']; //販売総数
									$retnum += $row['retnum']; //返品総数
								}
								$rowcnt += 1;
							?>
							<? } ?>
						<? } ?>
					</table>
					<!-- ================================================
					// ■　□　■　□　合計表示　■　□　■　□
					// ================================================-->
					<table class="tbh" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
						<tr><td class="category"><strong>■◇■総合計■◇■</strong></td></tr>
					</table>
					<table class="tbd" cellspacing="0" cellpadding="0" border="0" style="{padding-bottom: 40px;}" summary="ベーステーブル">
						<tr>
							<th class="tbd_th"><strong>販売台数/返品</strong></th>
							<td class="tbd_arb"></td>
							<td class="tbd_td">
								<input type="text" size="10" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($hannum) ?>">台
								<input type="text" size="10" style="text-align:right;font-size:20px;" disabled="disabled" value="<?php echo number_format($retnum) ?>">台
							</td>
						</tr>
					</table>

					<h2>日別実績</h2><br>
					<?php
					// ================================================
					// ■　□　■　□　全体表示　■　□　■　□
					// ================================================
					//----- データ抽出
					$query = " SELECT A.idxnum, A.buydt, F.prefecture, F.city, A.lane  ,A.locale, A.kaisainum, A.tradenum, A.hours, A.staff , A.buynum , A.resnum ,A.goalplan";
					$query .= " ,FORMAT(A.buykin,0) as buykin_f , A.buykin, FORMAT(IFNULL(round(A.buykin / A.buynum),0),0) as buykinAvg";
					$query .= " ,CONCAT(A.h_s_1  ,'-'  , A.h_e_1) as h_1 ";
					$query .= " ,CONCAT(A.h_s_2  ,'-'  , A.h_e_2) as h_2 ";
					$query .= " ,CONCAT(A.h_s_3  ,'-'  , A.h_e_3) as h_3 ";
					$query .= " ,CONCAT(A.trade_h_s_1  ,'-'  , A.trade_h_e_1) as t_h_1 ";
					$query .= " ,CONCAT(A.trade_h_s_2  ,'-'  , A.trade_h_e_2) as t_h_2 ";
					$query .= " ,A.upddt ,A.updcount ,A.buynum - A.goalplan as clear, A.camera, A.temperature_flg, A.roster_flg  ";
					$query .= " , (IFNULL(B.hannum1,0)+IFNULL(H.mrenum1,0)) as hannum1, (IFNULL(B.sumkin1,0)+IFNULL(E.sumkin1,0)+IFNULL(H.sumkin1,0)) as sumkin1";
					$query .= " , (IFNULL(C.hannum2,0)+IFNULL(I.mrenum2,0)) as hannum2, (IFNULL(C.sumkin2,0)+IFNULL(F.sumkin2,0)+IFNULL(I.sumkin2,0)) as sumkin2";
					$query .= " , (IFNULL(D.hannum3,0)+IFNULL(J.mrenum3,0)) as hannum3, (IFNULL(D.sumkin3,0)+IFNULL(G.sumkin3,0)+IFNULL(J.sumkin3,0)) as sumkin3 ";
					$query .= " , IFNULL(K.rentnum1,0) as rentnum1, IFNULL(K.rentkin1,0) as rentkin1";
					$query .= " , IFNULL(L.rentnum2,0) as rentnum2, IFNULL(L.rentkin2,0) as rentkin2";
					$query .= " , IFNULL(M.rentnum3,0) as rentnum3, IFNULL(M.rentkin3,0) as rentkin3";
					$query .= " , CONCAT(REPLACE(A.buydt,'-',''), LPAD(A.lane,2, '0'), '-', A.branch) as venueid ";
					$query .= "  ,(case DATE_FORMAT(A.buydt, '%w')   ";
					$query .= "      when 0 then '(日)'   ";
					$query .= "      when 1 then '(月)'   ";
					$query .= "      when 2 then '(火)'   ";
					$query .= "      when 3 then '(水)'   ";
					$query .= "      when 4 then '(木)'   ";
					$query .= "      when 5 then '(金)'   ";
					$query .= "      when 6 then '(土)'   ";
					$query .= "      else 'x' end) as date_name  ";
					$query .= " , A.eventflg ";
					$query .= " FROM php_performance A ";
					$query .= "   LEFT OUTER JOIN(";
					$query .= "   SELECT venueid ";
					$query .= "     , SUM(CASE WHEN section = 1 THEN hannum + grenum + mrenum + c_grenum + c_mrenum ELSE 0 END) as hannum1 ";
					$query .= "     , SUM(CASE WHEN section = 1 THEN tanka ELSE 0 END) as sumkin1 ";
					$query .= "     , SUM(CASE WHEN section = 2 THEN hannum + grenum + mrenum + c_grenum + c_mrenum ELSE 0 END) as hannum2 ";
					$query .= "     , SUM(CASE WHEN section = 2 THEN tanka ELSE 0 END) as sumkin2 ";
					$query .= "     , SUM(CASE WHEN section = 3 THEN hannum + grenum + mrenum + c_grenum + c_mrenum ELSE 0 END) as hannum3 ";
					$query .= "     , SUM(CASE WHEN section = 3 THEN tanka ELSE 0 END) as sumkin3 ";
					$query .= "     FROM php_t_pc_hanbai";
					$query .= "    WHERE section=1";
					$query .= "      AND kbn = 1 ";
					$query .= "      AND delflg=0";
					$query .= "      AND venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
					$query .= "   GROUP BY venueid";
					$query .= " )B ON CONCAT(REPLACE(A.buydt,'-',''), LPAD(A.lane,2, '0'), '-', A.branch)=B.venueid";
					$query .= "   LEFT OUTER JOIN(";
					$query .= "   SELECT venueid, SUM(hannum)+SUM(grenum)+SUM(mrenum)+SUM(c_grenum)+SUM(c_mrenum) as hannum2, SUM(tanka) as sumkin2";
					$query .= "     FROM php_t_pc_hanbai";
					$query .= "    WHERE section=2";
					$query .= "      AND kbn = 1 ";
					$query .= "      AND delflg=0";
					$query .= "      AND venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
					$query .= "   GROUP BY venueid";
					$query .= " )C ON CONCAT(REPLACE(A.buydt,'-',''), LPAD(A.lane,2, '0'), '-', A.branch)=C.venueid";
					$query .= "   LEFT OUTER JOIN(";
					$query .= "   SELECT venueid, SUM(hannum)+SUM(grenum)+SUM(mrenum)+SUM(c_grenum)+SUM(c_mrenum) as hannum3, SUM(tanka) as sumkin3";
					$query .= "     FROM php_t_pc_hanbai";
					$query .= "    WHERE section=3";
					$query .= "      AND kbn = 1 ";
					$query .= "      AND delflg=0";
					$query .= "      AND venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
					$query .= "   GROUP BY venueid";
					$query .= " )D ON CONCAT(REPLACE(A.buydt,'-',''), LPAD(A.lane,2, '0'), '-', A.branch)=D.venueid";
					$query .= "   LEFT OUTER JOIN(";
					$query .= "   SELECT venueid, 0 as hannum1, SUM(tanka) as sumkin1";
					$query .= "     FROM php_t_pc_hanbai";
					$query .= "    WHERE section=1";
					$query .= "      AND kbn in (2,3,6) ";
					$query .= "      AND delflg=0";
					$query .= "      AND venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
					$query .= "   GROUP BY venueid";
					$query .= " )E ON CONCAT(REPLACE(A.buydt,'-',''), LPAD(A.lane,2, '0'), '-', A.branch)=E.venueid";
					$query .= "   LEFT OUTER JOIN(";
					$query .= "   SELECT venueid, 0 as hannum2, SUM(tanka) as sumkin2";
					$query .= "     FROM php_t_pc_hanbai";
					$query .= "    WHERE section=2";
					$query .= "      AND kbn in (2,3,6) ";
					$query .= "      AND delflg=0";
					$query .= "      AND venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
					$query .= "   GROUP BY venueid";
					$query .= " )F ON CONCAT(REPLACE(A.buydt,'-',''), LPAD(A.lane,2, '0'), '-', A.branch)=F.venueid";
					$query .= "   LEFT OUTER JOIN(";
					$query .= "   SELECT venueid, 0 as hannum3, SUM(tanka) as sumkin3";
					$query .= "     FROM php_t_pc_hanbai";
					$query .= "    WHERE section=3";
					$query .= "      AND kbn in (2,3,6) ";
					$query .= "      AND delflg=0";
					$query .= "      AND venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
					$query .= "   GROUP BY venueid";
					$query .= " )G ON CONCAT(REPLACE(A.buydt,'-',''), LPAD(A.lane,2, '0'), '-', A.branch)=G.venueid";
					$query .= "   LEFT OUTER JOIN(";
					$query .= "   SELECT venueid, SUM(mrenum) as mrenum1, SUM(tanka) as sumkin1";
					$query .= "     FROM php_t_pc_reserv";
					$query .= "    WHERE section=1";
					$query .= "      AND delflg=0";
					$query .= "      AND venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
					$query .= "   GROUP BY venueid";
					$query .= " )H ON CONCAT(REPLACE(A.buydt,'-',''), LPAD(A.lane,2, '0'), '-', A.branch)=H.venueid";
					$query .= "   LEFT OUTER JOIN(";
					$query .= "   SELECT venueid, SUM(mrenum) as mrenum2, SUM(tanka) as sumkin2";
					$query .= "     FROM php_t_pc_reserv";
					$query .= "    WHERE section=2";
					$query .= "      AND delflg=0";
					$query .= "      AND venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
					$query .= "   GROUP BY venueid";
					$query .= " )I ON CONCAT(REPLACE(A.buydt,'-',''), LPAD(A.lane,2, '0'), '-', A.branch)=I.venueid";
					$query .= "   LEFT OUTER JOIN(";
					$query .= "   SELECT venueid, SUM(mrenum) as mrenum3, SUM(tanka) as sumkin3";
					$query .= "     FROM php_t_pc_reserv";
					$query .= "    WHERE section=3";
					$query .= "      AND delflg=0";
					$query .= "      AND venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
					$query .= "   GROUP BY venueid";
					$query .= " )J ON CONCAT(REPLACE(A.buydt,'-',''), LPAD(A.lane,2, '0'), '-', A.branch)=J.venueid";
					$query .= "   LEFT OUTER JOIN(";
					$query .= "   SELECT venueid, SUM(hannum)+SUM(grenum)+SUM(mrenum)+SUM(c_grenum)+SUM(c_mrenum) as rentnum1, SUM(tanka) as rentkin1";
					$query .= "     FROM php_t_pc_hanbai";
					$query .= "    WHERE section=1";
					$query .= "      AND kbn=5";
					$query .= "      AND delflg=0";
					$query .= "      AND venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
					$query .= "   GROUP BY venueid";
					$query .= " )K ON CONCAT(REPLACE(A.buydt,'-',''), LPAD(A.lane,2, '0'), '-', A.branch)=K.venueid";
					$query .= "   LEFT OUTER JOIN(";
					$query .= "   SELECT venueid, SUM(hannum)+SUM(grenum)+SUM(mrenum)+SUM(c_grenum)+SUM(c_mrenum) as rentnum2, SUM(tanka) as rentkin2";
					$query .= "     FROM php_t_pc_hanbai";
					$query .= "    WHERE section=2";
					$query .= "      AND kbn=5";
					$query .= "      AND delflg=0";
					$query .= "      AND venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
					$query .= "   GROUP BY venueid";
					$query .= " )L ON CONCAT(REPLACE(A.buydt,'-',''), LPAD(A.lane,2, '0'), '-', A.branch)=L.venueid";
					$query .= "   LEFT OUTER JOIN(";
					$query .= "   SELECT venueid, SUM(hannum)+SUM(grenum)+SUM(mrenum)+SUM(c_grenum)+SUM(c_mrenum) as rentnum3, SUM(tanka) as rentkin3";
					$query .= "     FROM php_t_pc_hanbai";
					$query .= "    WHERE section=3";
					$query .= "      AND kbn=5";
					$query .= "      AND delflg=0";
					$query .= "      AND venueid BETWEEN " . sprintf("'%s'",str_replace("-", "", $p_date1) ."01-1") . " and " . sprintf("'%s'",str_replace("-", "", $p_date2) ."99-9");//Test
					$query .= "   GROUP BY venueid";
					$query .= " )M ON CONCAT(REPLACE(A.buydt,'-',''), LPAD(A.lane,2, '0'), '-', A.branch)=M.venueid";
					$query .= " INNER JOIN php_facility F ";
					$query .= " ON F.facility_id = A.facility_id";
					$query .=" WHERE A.buydt BETWEEN CAST(" . sprintf("'%s'", $p_date1) . " AS DATE)";
					$query .="   AND CAST(" . sprintf("'%s'", $p_date2) . " AS DATE)";
					$query .="   AND A.tm_flg = 0 ";
					$query .=" ORDER BY A.buydt ,A.lane ,A.idxnum ";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//					if(! $rs = mysql_query($query, $db)){
					if (!($rs = $db->query($query))) {
//						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					//初期化
					$cnt = 0;
					$rowcnt = 0;
					$buynumSum = 0;
					$buykinSum = 0;
					$dmSum = 0;
					$dmcSum = 0;
					$buykinAvg = 0;
					$lane = 0;
// ----- 2019.06 ver7.0対応
//					while ($row = @mysql_fetch_array($rs)){
					while ($row = $rs->fetch_array()) {
						// ========================
						// 日別実績
						// ========================
						if($rowcnt == 0 || $buydt <> $row['buydt']){
							if($rowcnt > 0 && $buydt <> $row['buydt']){
								//合計
								if($buynumSum > 0 && $buykinSum > 0){
									$buykinAvg = round($buykinSum / $buynumSum);
								}else{
									$buykinAvg = 0;
								} ?>
								<tr style="background-color:#silver;">
									<td class="tbd_td_p4_c" COLSPAN="5">合計</td>
									<td class="tbd_td_p4_r"><?php echo number_format($buynumSum) ?>台</td>
									<td class="tbd_td_p4_r"><?php echo number_format($buykinSum) ?>円<br><?php echo number_format($buykinAvg) ?>円</td>
									<td class="tbd_td_p4_r"></td>
									<td class="tbd_td_p5_c"></td>
								</tr>
								<tr></tr>
								<?php
								//初期化
								$cnt = 0;
								$buykinAvg = 0;
								$buynumSum = 0;
								$buykinSum = 0;
							}
							//日付が変更
							if($buydt <> $row['buydt']){
								if($rowcnt > 0){ ?>
									</table>
								<?php } ?>
								<table class="tbh" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
										<tr><td class="category"><strong><?php echo date('Y/m/d', strtotime($row['buydt'])) . $row['date_name'] ?>の実績</strong></td></tr>
								</table>
								<table class="tbt" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">

								<tr style="background:#ccccff">
									<th class="tbd_th_p1"><strong>ﾚｰﾝ</strong></th>
									<th class="tbd_th_p1"><strong>開催場所</strong></th>
									<th class="tbd_th_p1"><strong>担当者</strong></th>
									<th class="tbd_th_p1"><strong>開催時間<br>(回数)</strong></th>
									<th class="tbd_th_p1"><strong>更新<br>時間</strong></th>
									<th class="tbd_th_p1"><strong>販売台数<br>（販売/下取）<br>(目標)</strong></th>
									<th class="tbd_th_p1"><strong>金額<br>平均単価</strong></th>
									<th class="tbd_th_p1"><strong>参加<br>メンバー</strong></th>
									<th class="tbd_th_p1"><strong>コロナ対策</strong></th>
								</tr>
								<?php
								//初期化
								$lane = "";
							}
						}
						//明細設定
						if($row['clear'] > 0 && $row['goalplan'] > 0){ ?>
							<tr style="background-color:#FFDDE4;">
						<?php }else{
							if(($cnt % 2) == 0){ ?>
								<tr style="background-color:#EDEDED;">
							<?php }else{ ?>
								<tr>
							<?php }
						}
							//会場
							if($row['lane'] <> "99"){ 
								//明細表示
								if($lane <> $row['lane']){ ?>
									<td class="tbd_td_p4_c"><?php echo $row['lane'] ?></td>
								<?php }else{ ?>
									<td class="tbd_td_p4_c"></td>
								<?php } ?>
								<td class="tbd_td_p3_l">
									<?php echo $row['prefecture'] ?><br><?php echo $row['city'] ?>
								</td>
								<?php if($row['camera'] == 0){ ?>
									<td class="tbd_td_p5_c"><?php echo $row['staff'] ?></td>
								<?php } elseif($row['camera'] == 1){ ?>
									<td class="tbd_td_p5_c"><?php echo $row['staff'] ?><br><img src='images/camera_m.png'></td>
								<?php } elseif($row['camera'] == 2){ ?>
									<td class="tbd_td_p5_c"><?php echo $row['staff'] ?><br><img src='images/camera_b.png'></td>
								<?php } ?>
								<td class="tbd_td_p5_c">
									<? if ($row['h_1'] <> "0:00-0:00") { ?>
										<? if ($row['h_1'] <> "-") {echo $row['h_1'];} ?>
										<? if ($row['h_2'] <> "-" && $row['h_2'] <> "0:00-0:00") {echo "<br>" . $row['h_2'];} ?>
										<? if ($row['h_3'] <> "-" && $row['h_3'] <> "0:00-0:00") {echo "<br>" . $row['h_3'];} ?>
									<? } if($row['t_h_1'] <> "0:00-0:00") { ?>
										<br><font color="gray" size="2" text-align="left"><下取>
										<br><? if ($row['t_h_1'] <> "-") {echo $row['t_h_1'];} ?>
										<? if ($row['t_h_2'] <> "0:00-0:00" && $row['t_h_2'] <> "-") {echo "<br>" . $row['t_h_2'];} ?></font>
									<? }if($row['kaisainum']+$row['tradenum'] > 0) { ?>
										<br>(<?php echo $row['kaisainum']+$row['tradenum'] ?>回)
									<? } ?>
								</td>
								<?php if($row['upddt'] > $row['buydt']){ ?>
									<td class="tbd_td_p4_c"><?php echo date('H:i', strtotime($row['upddt'])) ?></td>
								<?php }else{ ?>
									<td class="tbd_td_p4_c"></td>
								<?php }
								//販売台数・目標
								if($row['goalplan'] > 0){ ?>
									<td class="tbd_td_p4_r" style="text-align:right">
										<?php echo $row['buynum'] ?>台(<?php echo $row['goalplan'] ?>)<br>
										<?php if(($row['hannum1']+$row['rentnum1'])>0){ ?>
											<FONT color="gray" size="2">１部:<?php echo $row['hannum1']+$row['rentnum1'] ?>台(<?php echo $row['hannum1']."/".$row['rentnum1'] ?>)</font><br>
										<?php }if(($row['hannum2']+$row['rentnum2'])>0){ ?>
											<FONT color="gray" size="2">２部:<?php echo $row['hannum2']+$row['rentnum2'] ?>台(<?php echo $row['hannum2']."/".$row['rentnum2'] ?>)</font><br>
										<?php }if(($row['hannum3']+$row['rentnum3'])>0){ ?>
											<FONT color="gray" size="2">３部:<?php echo $row['hannum3']+$row['rentnum3'] ?>台(<?php echo $row['hannum3']."/".$row['rentnum3'] ?>)</font><br>
										<?php } ?>
										<?php if($row['clear'] > 0){ ?>
											<small><FONT color="red">(クリア：<?php echo  $row['clear']; ?>台)</FONT></small>
										<?php } ?>
									</td>
								<?php }else{ ?>
									<td class="tbd_td_p4_r" style="text-align:right">
										<?php echo $row['buynum'] ?>台<br>
										<?php if(($row['hannum1']+$row['rentnum1'])>0){ ?>
											<FONT color="gray" size="2">１部:<?php echo $row['hannum1']+$row['rentnum1'] ?>台(<?php echo $row['hannum1']."/".$row['rentnum1'] ?>)</font><br>
										<?php }if(($row['hannum2']+$row['rentnum2'])>0){ ?>
											<FONT color="gray" size="2">２部:<?php echo $row['hannum2']+$row['rentnum2'] ?>台(<?php echo $row['hannum2']."/".$row['rentnum2'] ?>)</font><br>
										<?php }if(($row['hannum3']+$row['rentnum3'])>0){ ?>
											<FONT color="gray" size="2">３部:<?php echo $row['hannum3']+$row['rentnum3'] ?>台(<?php echo $row['hannum3']."/".$row['rentnum3'] ?>)</font><br>
										<?php } ?>
									</td>
								<?php } ?>
								<!--平均単価-->
								<td class="tbd_td_p5_r">
									<?php echo number_format($row['buykin']) ?>円<br>
									<?php echo $row['buykinAvg'] ?>円<br>
									<?php if(($row['hannum1']+$row['rentnum1'])>0){ ?>
										<FONT color="gray" size="2">１部:<?php echo number_format(($row['sumkin1']+$row['rentkin1'])/($row['hannum1']+$row['rentnum1'])) ?>円</font><br>
									<?php }if(($row['hannum2']+$row['rentnum2'])>0){ ?>
										<FONT color="gray" size="2">２部:<?php echo number_format(($row['sumkin2']+$row['rentkin2'])/($row['hannum2']+$row['rentnum2'])) ?>円</font><br>
									<?php }if(($row['hannum3']+$row['rentnum3'])>0){ ?>
										<FONT color="gray" size="2">３部:<?php echo number_format(($row['sumkin3']+$row['rentkin3'])/($row['hannum3']+$row['rentnum3'])) ?>円</font><br>
									<?php } ?>
								</td>
								
								<!--参加メンバー-->
								<td class="tbd_td_p4_l">
								<small>
								<?
									// ================================================
									// ■　□　■　□　全体表示　■　□　■　□
									// ================================================
									//----- データ抽出
									$sub_query = "";
									$sub_query .= " SELECT (case A.assign ";
									$sub_query .= "    when 'K' then CONCAT('回線：', A.staff, '人')";
									$sub_query .= "    else A.staff end) as staff ";
									$sub_query .= " ,(case A.assign ";
									$sub_query .= "    when 'P' then '(プ)' ";
									$sub_query .= "    when 'B' then '(備)' ";
									$sub_query .= "    when 'K' then ' ' ";
									if ($row['eventflg'] == 1) {
										$sub_query .= "    when 'D' then '(派)' ";
									}
									$sub_query .= "    else ' ' end) as assign ";
									$sub_query .= " FROM php_event_staff A ";
									$sub_query .=" WHERE A.venueid =  " . sprintf("'%s'", $row['venueid']);
									$sub_query .="   AND A.staff   <> " . sprintf("'%s'", $row['staff']);
									$sub_query .="   AND A.assign   <> 'Y' ";
									$sub_query .=" ORDER BY A.venueno ";
									$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
									$comm->ouputlog($sub_query, $prgid, SYS_LOG_TYPE_DBUG);
									if (!($sub = $db->query($sub_query))) {
										$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
									}
									$sub_cnt=0;
									$assign="";
									$assign_flg=0;
									while ($sub_row = $sub->fetch_array()) {
										if ($sub_cnt == 0 && $row['eventflg'] == 1){
											echo "------------";
											$sub_cnt = 1;
										}
										if ($assign <> "" && $assign <> $sub_row['assign']){
											echo "<br>------------";
											$assign_flg=0;
										}
										if ($sub_cnt == 0){
											echo $sub_row['staff'] . $sub_row['assign'];
										} else {
											if ($assign_flg == 0){
												echo "<br>" . $sub_row['staff'] . $sub_row['assign'];
											} else {
												echo "、" . $sub_row['staff'] . $sub_row['assign'];
											}
										}
										$sub_cnt = 1;
										$assign_flg = 1;
										$assign=$sub_row['assign'];
									}
								?>
								<? if ($sub_cnt == 1 && $row['eventflg'] == 1) { ?>
								<br>
								------------
								<? } ?>
								</small>
								</td>
								<td class="tbd_td_p5_c">
									<?php if($row['temperature_flg'] == 1){ ?>
									<img src='./images/temperature.png'>
									<?php } ?>
									<?php if($row['roster_flg'] == 1){ ?>
									<img src='./images/roster.png'>
									<?php } ?>
								</td>
							<!--本部事務所-->
							<?php }else{ ?>
								<!--レーン-->
								<td class="tbd_td_p4_c"></td>
								<td class="tbd_td_p3_l" COLSPAN="4">
									<?php echo $row['prefecture'] ?>
								</td>
								<td class="tbd_td_p4_r" style="text-align:right">
									<?php echo $row['buynum'] ?>台<br>
								</td>
								<!--平均単価-->
								<td class="tbd_td_p5_r">
									<?php echo number_format($row['buykin']) ?>円<br>
									<?php echo $row['buykinAvg'] ?>円<br>
								</td>
								<td class="tbd_td_p4_c"></td>
								<td class="tbd_td_p4_c"></td>
							<?php } ?>
						</tr>
						<?php
						$cnt++;
						$rowcnt++;
						$buydt = $row['buydt'];
						$lane = $row['lane'];
						//合計金額
						$buynumSum = $buynumSum + $row['buynum'];
						$buykinSum = $buykinSum + $row['buykin'];
					}
					if($rowcnt > 0){
						//合計
						if($buykinSum > 0){
							$buykinAvg = number_format(round($buykinSum / $buynumSum));
						}else{
							$buykinAvg = 0;
						}
						$buynumSum = number_format($buynumSum);
						$buykinSum = number_format($buykinSum);
					?>
						<tr style="background-color:#silver;">
							<td class="tbd_td_p4_c" COLSPAN="5">合計</td>
							<td class="tbd_td_p4_r"><?php echo $buynumSum ?>台</td>
							<td class="tbd_td_p4_r"><?php echo $buykinSum ?>円<br><?php echo $buykinAvg ?>円</td>
							<td class="tbd_td_p4_r"></td>
							<td class="tbd_td_p5_c"></td>
						</tr>
						<tr></tr>
						</table>
					<?php } ?>
				</form>
			</div>
		</div>
	</div>
</div>
</body>

<!-- データベース切断 -->
<?php if($result){ $dba->mysql_discon($db); } ?>

</html>
