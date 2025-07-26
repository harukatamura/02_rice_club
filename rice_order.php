<?php
//==================================================================================================
// ■機能概要
// ・お米販売実績画面
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
	$prgname = "精米倶楽部販売実績登録画面";
	$prgmemo = "　精米倶楽部の注文実績です";
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
	$p_display = 1;
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
	$p_staff = $_COOKIE['con_perf_staff'];
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
	<title>J-Office | 精米倶楽部実績一覧</title>
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
	width:200px;
	padding: 5px 4px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #2B8225; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	}
	th.tbd_th_p2 {
	width: 300px;
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
	width: 300px;
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #FFB2CB; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	}
	th.tbd_th_p3 {
	width: 500px;
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #2B8225; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	}

	/* --- データセル（td） --- */

	td.tbd_td_p1 {
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p2 {
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p3 {
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p3_r {
	padding: 7px 10px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p3_err {
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p4 {
	padding: 5px 5px 5px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	td.tbd_td_p4_c {
	padding: 7px 10px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	td.tbd_td_p4_r {
	padding: 7px 10px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p4_l {
	padding: 7px 10px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p5_l {
	padding: 5px 5px 5px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p6_l {
	padding: 5px 5px 5px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p7_l {
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
    width: 120px;
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
    background-color: #ffffff ;
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
.header_logo {
	vertical-align: middle;
	font-size:64px;
}
	/*--ボタンデザイン--*/
	.btn-border {
	display: inline-block;
	text-align: left;
	border: 2px solid #ff8c00;
	font-size: 16px;
	color: #ff8c00;
	text-decoration: none;
	font-weight: bold;
	padding: 8px 16px;
	border-radius: 4px;
	transition: .4s;
	}
	.btn-border:hover,
	.btn-border::before,
	.btn-border::after,
	.btn-border:hover:before,
	.btn-border:hover:after {
	background-color: #ffa500;
	border-color: #ff8c00;
	color: #FFF;
	}
		/*--ボタンデザイン--*/
		.btn-border-b {
		display: inline-block;
		text-align: left;
		border: 2px solid #00608d;
		font-size: 24px;
		text-decoration: none;
		font-weight: bold;
		padding: 20px 20px;
		border-radius: 4px;
		transition: .4s;
		background-color: #bbe2f1;
		color: #0073a8;
		}
		.btn-border-b:hover,
		.btn-border-b::before,
		.btn-border-b::after,
		.btn-border-b:hover:before,
		.btn-border-b:hover:after {
		border-color: #00608d;
		background-color: #008db7;
		color: #fff;
		}
	</style>
	<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
	<script type="text/javascript">
		<!--
		function hpbmapinit() {
			hpbmaponload();
		}
		//-->
	</script>
	<?php $html->output_htmlheadinfo(); ?>
	<script type="text/javascript">
		//編集ボタン
		function Mclk_Stat(Cell,idx){
			//セル内のカンマを消す
			var kin = document.forms['frm'].elements['販売金額'+idx].value;
			var kin = kin.replace(/,/g, '');
			document.forms['frm'].elements['販売金額'+idx].value = kin;
			//画面項目設定
 			var myTbl = document.getElementById('TBL');
			//画面項目設定
			var Cell1=myTbl.rows[Cell.parentNode.rowIndex].cells[0]; //i番行のj番列のセル "td"
			//画面項目設定
			var cell1 = 'idx='+Cell1.innerHTML;
			document.forms['frm'].action = './performance_sql.php?kbn=performance&' + cell1;
			document.forms['frm'].submit();
		}
	</script>
	<script type="text/javascript">
		//編集ボタン
		function Change_RadioBox(display,ref){
			//画面項目設定
			document.forms['frm'].action = './<? echo $prgid ?>.php?display=' + display + '&ref=' + ref;
			document.forms['frm'].submit();
		}
		//入金額登録
		function Mclk_genkin(idxnum){
			//セル内のカンマを消す
			//入金
			var n_kin = document.forms['frm'].elements['銀行入金額'+idxnum].value;
			var n_kin = n_kin.replace(/,/g, '');
			document.forms['frm'].elements['銀行入金額'+idxnum].value = n_kin;
			//paypay
			var p_kin = document.forms['frm'].elements['paypay決済額'+idxnum].value;
			var p_kin = p_kin.replace(/,/g, '');
			document.forms['frm'].elements['paypay決済額'+idxnum].value = p_kin;
			document.forms['frm'].action = './performance_sql.php?kbn=pay&idx=' + idxnum;
			document.forms['frm'].submit();
		}
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
			<p class="header_logo"><img src="images/logo_jemtc.png" alt="" />精米倶楽部実績一覧</p>
		</div>
	</table>
	<div id="contents">
		<div id="main">
			<div id="formWrap">
				<form name="frm" method = "post" action="./<? echo $prgid ?>.php?display=<?echo $p_display ?>&ref=<?echo $refresh ?>" >
					<p style="text-align:right"><a href="./rice_order_list.php" class="btn-border-b">顧客情報一覧</a>　<a href="https://ws.formzu.net/fgen/S88742786/" target="_blank" class="btn-border-b">☎　新規注文受付</a></p>
					<h2>全体実績</h2><br>
					<div class="fare">
						<left>
							<div class="fare-calendar">
							<!--　販売実績グラフ -->
							【累計販売実績】
							<div class="fare-rates">
								<div class="fare-monthcontainer">
									<ul>
										<?
										$query = "SELECT category, SUM(weight) as weight ";
										$query .= " FROM php_rice_subscription ";
										$query .= " WHERE status='申込' ";
										$query .= " GROUP BY category ";
										$query .= " ORDER BY category";
										$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
										$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
										if (!($rs = $db->query($query))) {
											$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
										}
										$arr_performance =  [];
										$all_weight = 0;
										while ($row = $rs->fetch_array()) {
											$arr_performance[] = $row;
											$all_weight += $row['weight'];
										}
										if($all_weight > 200){
											$px = 0.7;
										}else if($all_weight > 100){
											$px = 1.0;
										}else{
											$px = 1.5;
										}
										for($i=0; $i<count($arr_performance); $i++) {
										?>
											<li class="fare-month">
												<span class="ttl2">
													<? if ($arr_performance[$i]['category'] <> "") { ?>
													<b><? echo $arr_performance[$i]['weight'] ?></b><font size="3">kg</font>
													<? $all_buynum +=$arr_performance[$i]['weight']; ?>
													<? } ?>
												</span>
												<span class="fare-price" style="height:<? echo $arr_performance[$i]['weight']  * $px ?>px;" ></span>
												<span class="ttl1" style="line-height: 1.0;">
													<font size="3">
														<b><? echo $arr_performance[$i]['category'] ?></b>
													</font>
												</span>
											</li>
										<?
										}
										?>
										<li class="fare-month">
											<span class="ttl2">
												<? if ($all_weight > 0) { ?>
												<b><? echo $all_weight ?></b><font size="3">kg</font>
												<? } ?>
											</span>
											<span class="fare-price" style="height:<? echo $all_weight  * $px ?>px;" ></span>
											<span class="ttl1" style="line-height: 1.0;">
												<font size="4">
													<b>合計</b>
												</font>
											</span>
										</li>
									</ul>
								</div>
							</div>
							<div>
							<!--　詳細内訳 -->
							</div>
							</div>
						</left>
					</div><br><br>
					<h2>実績詳細</h2><br>
					<table class="tbh" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
						<tr><td class="category"><strong>コース別実績</strong></td></tr>
					</table><br>
					<table class="tbt" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
						<tr style="background:#ccccff">
							<th class="tbd_th_p3"><strong>コース</strong></th>
							<th class="tbd_th_p1"><strong>量(kg)</strong></th>
							<th class="tbd_th_p1"><strong>件数(件)</strong></th>
							<th class="tbd_th_p2"><strong>単価(円)</strong></th>
							<th class="tbd_th_p2"><strong>小計(円)</strong></th>
							</tr>
						<?php
						// ================================================
						// ■　□　■　□　全体表示　■　□　■　□
						// ================================================
						//----- データ抽出
						$query = "
							SELECT A.category, A.weight, COUNT(*) as cnt, B.tanka
							FROM php_rice_subscription A 
							LEFT OUTER  JOIN php_rice_category B ON A.category=B.category AND A.weight=B.weight 
							WHERE A.status='申込' 
							GROUP BY A.category, A.weight 
							ORDER BY A.category, A.weight
						";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs = $db->query($query))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						//初期化
						$cnt = 0;
						$sum_cnt = 0;
						$sum_cash = 0;
						while ($row = $rs->fetch_array()) {
							if(($cnt % 2) == 0){ ?>
								<tr style="background-color:#f5f5f5;">
							<? }else{ ?>
								<tr style="background-color:#ffffff;">
							<? } ?>
								<? if($g_category <> $row['category']){ ?>
									<td class="tbd_td_p4_l"><? echo $row['category'] ?></td>
								<? }else{ ?>
									<td class="tbd_td_p4_l"></td>
								<? }
								$g_category = $row['category']; ?>
								<td class="tbd_td_p3_r"><? echo $row['weight']; ?></td>
								<td class="tbd_td_p3_r"><? echo $row['cnt']; ?></td>
								<td class="tbd_td_p4_r"><? echo number_format($row['tanka']); ?></td>
								<td class="tbd_td_p4_r"><? echo number_format($row['cnt'] * $row['tanka']); ?></td>
							</tr>
							<? 
							++$cnt;
							$sum_cnt += $row['cnt'];
							$sum_cash += $row['cnt'] * $row['tanka'];
						} ?>
						<tr style="background-color:#d3d3d3;">
							<td class="tbd_td_p4_l" COLSPAN="2">合計</td>
							<td class="tbd_td_p4_r"><?php echo number_format($sum_cnt) ?>件</td>
							<td class="tbd_td_p4_r" COLSPAN="2"><?php echo number_format($sum_cash) ?>円</td>
						</tr>
					</table><br><br><br>
					<h2>日別実績</h2><br>
						<?php
						// ================================================
						// ■　□　■　□　全体表示　■　□　■　□
						// ================================================
						//----- データ抽出
						$query = "
							SELECT DATE(A.insdt) as insdt, A.category, A.weight, COUNT(*) as cnt, B.tanka
							FROM php_rice_subscription A 
							LEFT OUTER  JOIN php_rice_category B ON A.category=B.category AND A.weight=B.weight 
							WHERE A.status='申込' 
							GROUP BY DATE(A.insdt), A.category, A.weight 
							ORDER BY A.insdt, A.category, A.weight
						";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs = $db->query($query))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						//初期化
						$cnt = 0;
						$g_date = "";
						$sum_cnt = 0;
						$sum_cash = 0;
						$g_category = "";
						while ($row = $rs->fetch_array()) {
							if($g_date <> $row['insdt']){ 
								if($cnt > 0){ ?>
									<tr style="background-color:#d3d3d3;">
										<td class="tbd_td_p4_l" COLSPAN="2">合計</td>
										<td class="tbd_td_p4_r"><?php echo number_format($sum_cnt) ?>件</td>
										<td class="tbd_td_p4_r" COLSPAN="2"><?php echo number_format($sum_cash) ?>円</td>
									</tr>
								</table><br>
								<? } ?>
								<table class="tbh" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
									<tr><td class="category"><strong><? echo date('Y/n/j', strtotime($row['insdt']))?></strong></td></tr>
								</table><br>
								<table class="tbt" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
									<tr style="background:#ccccff">
										<th class="tbd_th_p3"><strong>コース</strong></th>
										<th class="tbd_th_p1"><strong>量(kg)</strong></th>
										<th class="tbd_th_p1"><strong>件数(件)</strong></th>
										<th class="tbd_th_p2"><strong>単価(円)</strong></th>
										<th class="tbd_th_p2"><strong>小計(円)</strong></th>
										</tr>
							<?
								$g_date = $row['insdt'];
								$sum_cnt = 0;
								$sum_cash = 0;
							} ?>
							<? if(($cnt % 2) == 0){ ?>
								<tr style="background-color:#f5f5f5;">
							<? }else{ ?>
								<tr style="background-color:#ffffff;">
							<? } ?>
								<? if($g_category <> $row['category']){ ?>
									<td class="tbd_td_p4_l"><? echo $row['category'] ?></td>
								<? }else{ ?>
									<td class="tbd_td_p4_l"></td>
								<? }
								$g_category = $row['category']; ?>
								<td class="tbd_td_p3_r"><? echo $row['weight']; ?></td>
								<td class="tbd_td_p3_r"><? echo $row['cnt']; ?></td>
								<td class="tbd_td_p4_r"><? echo number_format($row['tanka']); ?></td>
								<td class="tbd_td_p4_r"><? echo number_format($row['cnt'] * $row['tanka']); ?></td>
							</tr>
							<? 
							++$cnt;
							$sum_cnt += $row['cnt'];
							$sum_cash += $row['cnt'] * $row['tanka'];
						} ?>
						<? if($cnt > 0){ ?>
							<tr style="background-color:#d3d3d3;">
								<td class="tbd_td_p4_l" COLSPAN="2">合計</td>
								<td class="tbd_td_p4_r"><?php echo number_format($sum_cnt) ?>件</td>
								<td class="tbd_td_p4_r" COLSPAN="2"><?php echo number_format($sum_cash) ?>円</td>
							</tr>
						</table><br>
						<? } ?>
				</form>
			</div>
		</div>
	</div>
</div>
</body>

<!-- データベース切断 -->
<?php if ($result) { $dba->mysql_discon($db); } ?>

</html>
