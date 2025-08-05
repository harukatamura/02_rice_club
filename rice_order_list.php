<?php
//==================================================================================================
// ■機能概要
// ・精米倶楽部　顧客情報一覧
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
	$prgname = "精米倶楽部　顧客情報一覧";
	$prgmemo = "　精米倶楽部の顧客情報一覧です";
	$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);
	
	foreach($_POST as $key=>$val) {
		$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_DBUG);
	}

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
	//担当者
	$p_staff = $_COOKIE['con_perf_staff'];
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
	<title>J-Office | <?= prgname ?></title>
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
	width:100px;
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
	td.tbd_td_p3_c {
	padding: 7px 10px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	td.tbd_td_p3_r {
	padding: 7px 10px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p3_l {
	padding: 7px 10px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
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
	<?php $html->output_htmlheadinfo2($prgname); ?>
	<script type="text/javascript">
		//編集ボタン
		function edit_name(idxnum){
			//画面項目設定
			window.open('./rice_kokyaku.php?idx=' + idxnum);
		}
	</script>
</head>

<body>
<br>
<div id="container">
	<table class="base" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
		<!-- ヘッダー情報生成 -->
		<div id="header">
			<p class="header_logo"><img src="images/logo_jemtc.png" alt="" /><?= $prgname; ?></p>
		</div>
	</table>
	<div id="contents">
		<div id="main">
			<div id="formWrap">
				<form name="frm" method = "post" action="./<? echo $prgid ?>.php?display=<?echo $p_display ?>&ref=<?echo $refresh ?>" >
					<p style="text-align:right"><a href="./rice_order.php" class="btn-border-b">実績一覧に戻る</a></p>
					<h2>顧客情報一覧</h2><br>
					<p>
					※お名前クリックで顧客情報修正画面が開きます<br><br>
					　＜検索方法＞<br>
					　①Ctrl + F<br>
					　②お名前を入力（※ご注文された際の漢字）
					</p>
					<table class="tbt" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
						<tr style="background:#ccccff">
							<th class="tbd_th_p1"><strong>No.</strong></th>
							<th class="tbd_th_p2"><strong>申込日時</strong></th>
							<th class="tbd_th_p2"><strong>お名前</strong></th>
							<th class="tbd_th_p3"><strong>ご住所</strong></th>
							<th class="tbd_th_p2"><strong>コース</strong></th>
							<th class="tbd_th_p2"><strong>次回配送予定</strong></th>
<!--							<th class="tbd_th_p2" COLSPAN="12"><strong>配送予定</strong></th>-->
							</tr>
						<?php
						// ================================================
						// ■　□　■　□　全体表示　■　□　■　□
						// ================================================
						//----- データ抽出
						$query = "
							SELECT A.name, B.category, B.weight, C.delivery_date, B.date_s, C.stopflg, C.output_flg, A.idxnum
							, A.area, A.address2, A.insdt
							FROM php_rice_personal_info A
							LEFT OUTER JOIN php_rice_subscription B ON A.idxnum=B.personal_idxnum 
							LEFT OUTER  JOIN php_rice_shipment C ON B.subsc_idxnum=C.subsc_idxnum 
							WHERE A.status='申込' 
							ORDER BY A.idxnum DESC
						";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs = $db->query($query))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						//初期化
						$cnt = 0;
						$g_idxnum = "";
						$g_year = "";
						$d_flg = 0;
						while ($row = $rs->fetch_array()) {
							if($g_idxnum <> $row['idxnum']){
								$d_flg = 0;
								$g_idxnum = $row['idxnum'];
								if($cnt > 0){ ?>
									</tr>
								<? }if(($cnt % 2) == 0){ ?>
									<tr style="background-color:#f5f5f5;">
								<? }else{ ?>
									<tr style="background-color:#ffffff;">
								<? } ?>
									<td class="tbd_td_p3_c"><? echo $row['idxnum']; ?></td>
									<td class="tbd_td_p3_c"><? echo date('y/n/j H:i', strtotime($row['insdt'])); ?></td>
									<td class="tbd_td_p3_c"><a href="Javascript:edit_name('<?= $row['idxnum']; ?>')"><? echo $row['name']; ?></a></td>
									<? if($p_staff == "田村"|| $p_staff == "林" || $p_staff == "島村" || $p_compcd == "A"){ ?>
										<td class="tbd_td_p3_l"><? echo $row['address2']; ?></td>
									<? }else{ ?>
										<td class="tbd_td_p3_l"><? echo $row['area']; ?></td>
									<? } ?>
									<td class="tbd_td_p3_r"><? echo $row['category']."<br>".$row['weight']."kg"; ?></td>
								<? 
								++$cnt;
							} ?>
							<? if($d_flg == 0){
								$d_flg = 1; ?>
								<td class="tbd_td_p4_r"><?= date('Y/n/j', strtotime($row['delivery_date'])); ?></td>
							<? } ?>
						<? } ?>
						</tr>
					</table><br><br><br>
				</form>
			</div>
		</div>
	</div>
</div>
</body>

<!-- データベース切断 -->
<?php if ($result) { $dba->mysql_discon($db); } ?>

</html>
