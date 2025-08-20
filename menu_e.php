<?php
//==================================================================================================
// ■機能概要
//   ・メニュー一覧
//
// ■履歴
//   2019.06 バージョン更新対応 (PHP5.4.16 → PHP7.0.33)	K.Mizutani
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

	//グローバルIP取得
	$g_ip = $_SERVER['REMOTE_ADDR'];
	//事務所　グローバルIPアドレス
	$office_ip = '113.40.164.162';

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);
	//担当者
	$p_staff = $_COOKIE['con_perf_staff'];
	//権限
	$p_Auth = $_COOKIE['con_perf_Auth'];
	//経理
	$p_acco = $_COOKIE['con_perf_acco'];
	//会社
	$p_compcd = $_COOKIE['con_perf_compcd'];
	//タウンメール
	$p_tm = $_COOKIE['con_perf_tm'];

	//
	$datetime = new DateTime();
	$week = array("日", "月", "火", "水", "木", "金", "土");
	$w = (int)$datetime->format('w');
	$today = date('Y/m/d') . "(" .  $week[$w] . ")";

	$notice = array();
	// ================================================
	// ■　□　■　□　WEBカメラ取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "SELECT  A.url, A.pwd ";
	$query = $query." FROM php_web_camera A ";
	$query = $query." WHERE DATE_FORMAT(A.insdt, '%Y/%m/%d') = '" . date('Y/m/d') . "'";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	//データ設定
	$p_url = "";
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		if($row['pwd'] <> ""){
			$g_pwd =  "(パスワード：". $row['pwd'] . ")";
		}
		$notice[] =  $today . "　【Jemtc ビデオ通信URL】<a href='" . $row['url'] . "'>". $row['url']."</a>　".$g_pwd."";
//		$p_url =  $today . "　【Jemtc ビデオ通信URL】<a href='" . $row['url'] . "'>". $row['url'] . "</a>";
	}
//	$notice[] = "【201907-会場予定】　<a href='./price/201907-plan.pdf' target=_blank>2019年7月～</a>";

	$notice[] =  "【機器情報】<a href='./pdf/FUJITSUtabQ704.pdf' target=_blank>FUJITSUタブレットQ704</a>";
	// ================================================
	// ■　□　■　□　事務所スケジュール情報取得　■　□　■　□
	// ================================================
	$notice[] = "【電話注文】　<a href='./pdf/tel_order.pdf' target=_blank>チラシ折込情報</a>";
	//$p_end_year = "年末年始　<a href='./pdf/info.pdf' target=_blank>【各窓口休業日】</a>";

	// ================================================
	// ■　□　■　□　お知らせ情報取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "SELECT  A.target, A.subject, A.contents, A.url ";
	$query = $query." FROM php_info A ";
	$query = $query." WHERE A.target  = (SELECT week FROM php_calendar where date ='" . date('Y/m/d') . "')";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {

//		$comm->ouputlog("☆★☆データ取得エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ取得エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	//データ設定
	$p_info = "";
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$notice[] =  $row['target'] . "週　【" . $row['subject'] ."】<a href='" . $row['url'] . "' target=_blank>". $row['contents'] . "</a>";
	}
	$comm->ouputlog("☆★☆p_info☆★☆  " . $p_info[0], $prgid, SYS_LOG_TYPE_ERR);
	// ================================================
	// ■　□　■　□　カレンダーマスタ取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "SELECT A.week";
	$query = $query." FROM php_calendar A ";
	$query = $query." WHERE A.date = '".date('Y-m-d')."'";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	//データ設定
	$p_info = "";
	$p_assessment = "";
	$p_schedule = "";
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$notice[] =  $row['week'] . "週　【スケジュール】<a href='./schedule_today.php' target=_blank>本部出勤状況</a>";
		$notice[] =  $row['week'] . "週　【価格表】<a href='./pc_price.php' target=_blank>価格表</a>";
		$notice[] =  $row['week'] . "週　【個人評価】<a href='./assessment_input.php?kbn=1' target=_blank>会場評価シート</a>";
	}
	//データ設定
	$p_end_year = "";
	$p_iphone = "";
	//$p_end_year = "年末年始　<a href='./pdf/info.pdf' target=_blank>【各窓口休業日】</a>";
//	$notice[] =  "【個人評価】<a href='./assessment_input.php?kbn=2' target=_blank>事務所評価シート</a>";
	
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>Jemtc Office</title>
<script src="http://maps.google.com/maps/api/js?sensor=false" charset="UTF-8" type="text/javascript"></script>
<script src="js/hpbmapscript1.js" charset="UTF-8" type="text/javascript">HPBMAP_20150620053222</script>

<script src="./js/jquery-1.11.1.min.js" charset="UTF-8" type="text/javascript"></script>
<script src="./js/memf.js" charset="UTF-8" type="text/javascript"></script>

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
	width:1000px;
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
 background-image:url("./images/satei.jpg");
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
	width: 1000px;	/*メインコンテンツ幅*/
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
	height: 40px;
	width: 100%;
	padding-left: 40px;
	overflow: hidden;
}
/*段落タグの余白設定*/
#main p {
	padding: 0.5em 10px 1em;	/*左から、上、左右、下への余白*/
}
/*ヘッダー（ロゴが入っている最上段のブロック）
---------------------------------------------------------------------------*/
#header {
	background-repeat: no-repeat;
	height: 100px;	/*ヘッダーの高さ*/
	width: 100%;
	position: relative;
}
/*h1タグ設定*/
#header h1 {
	font-size: 10px;	/*文字サイズ*/
	line-height: 16px;	/*行間*/
	position: absolute;
	font-weight: normal;	/*文字サイズをデフォルトの太字から標準に。太字がいいならこの１行削除。*/
	right: 0px;		/*ヘッダーブロックに対して、右側から0pxの位置に配置*/
	bottom: 0px;	/*ヘッダーブロックに対して、下側から0pxの位置に配置*/
}
#header h1 a {
	text-decoration: none;
}
/*ロゴ画像設定*/
#header #logo {
	position: absolute;
	left: 10px;	/*ヘッダーブロックに対して、左側から10pxの位置に配置*/
	top: 12px;	/*ヘッダーブロックに対して、上側から12pxの位置に配置*/
}

/*コンテンツ（左右ブロックとフッターを囲むブロック）
---------------------------------------------------------------------------*/
#contents {
	clear: left;
	width: 100%;
	padding-top: 4px;
}

/*表示/非表示制御（お知らせ）
---------------------------------------------------------------------------*/
/*全体*/
.hidden_box {
    margin: 2em 0;
    padding: 0;
}

/*ボタン装飾*/
.hidden_box label {
    padding: 5px;
    font-weight: bold;
    background: #efefef;
    border-radius: 5px;
    cursor :pointer;
    transition: .5s;
}

/*アイコンを表示*/
.hidden_box label:before {
    display: inline-block;
    content: '\f078';
    font-family: 'FontAwesome';
    padding-right: 5px;
    transition: 0.2s;
}

/*ボタンホバー時*/
.hidden_box label:hover {
    background: silver;
}

/*アイコンを切り替え*/
.hidden_box input:checked ~ label:before {
     content: '\f00d';
     -webkit-transform: rotate(360deg);
     transform: rotate(360deg);
     color: #668ad8;
}

/*チェックは見えなくする*/
.hidden_box input {
    display: none;
}

/*中身を非表示にしておく*/
.hidden_box .hidden_show {
    height: 0;
    padding: 0;
    overflow: hidden;
    opacity: 0;
    transition: 0.8s;
}

/*クリックで中身表示*/
.hidden_box input:checked ~ .hidden_show {
    padding: 10px 0;
    height: auto;
    opacity: 1;
}

</style>
<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">

<script type="text/javascript">
	function hpbmapinit() {
		hpbmaponload();
	}
</script>

</head>
<body onload="hpbmapinit();">
	<!--container-->
	<div id="container">
		<div id="header">
			<p>
			<img src="images/logo.gif" alt="" align="left"/>
			</p>
		</div>
		<div class="userset" align="right">
			<div class="uname"><?php echo $today ?>　こんにちは、<?php echo $p_staff; ?>さん</div>
			<div class="out"><img src="images/logout.png" alt=""/><a href="./login.php">ログアウト</a></div>
		</div>
		<!--contents-->
		<div id="contents">
			<div id="main">
			<h2>お知らせ</h2>
				<!--contents-->
				<div class="hidden_box">
					【困ったことがありましたら、こちらのファイル参照してください】<a href='./pdf/QA.pdf' target=_blank>DownLoad</a><br>
					【本日のシフト表】<a href='./shift_schedule.php' target="_blank">確　認</a><br>
					【作業実績登録】<a href='./plan_kanri3.php' target="_blank">登　録</a><br>
					【ISO】<a href="https://jemtc.jp/qualitypolicy/" target="_blank" >基本方針について</a>
			<!--		【機器情報】<a href='./pdf/FUJITSUtabQ704.pdf' target=_blank>FUJITSUタブレットQ704</a><br>
					【機器情報】<a href='./pdf/FujitsutabQL2.pdf' target=_blank>FUJITSUタブレットQL2</a><br>
					【機器情報】<a href='./pdf/NEC VersaPro VK15E.pdf' target=_blank>NECタブレットVZ-H(VK15)</a>
			-->	</div>
			<h2>電話メニュー</h2>
				<p align="left">
				<a href="./manual/top.php" target="_blank"><img src="images/manual.png" onmouseover="this.src='images/manual_b.png'" onmouseout="this.src='images/manual.png'"></a>
				<a href="./tel_performance.php" target="_blank"><img src="images/tel_performance.png" onmouseover="this.src='images/tel_performance_b.png'" onmouseout="this.src='images/tel_performance.png'"></a>
				<a href="http://tel.jemtc.top/" target="_blank"><img src="images/tel_jemtc_top.png" onmouseover="this.src='images/tel_jemtc_top_b.png'" onmouseout="this.src='images/tel_jemtc_top.png'"></a><br>
				<?php if ($p_Auth <> 0) { ?>
				<a href="./tel_order_d.php?display=4" target="_blank"><img src="images/tel_order_d4.png" onmouseover="this.src='images/tel_order_d4_b.png'" onmouseout="this.src='images/tel_order_d4.png'"></a><br>
				<?php } ?>
				<a href="./ns_list_dispatch.php" target="_blank"><img src="images/nsorder_list.png" onmouseover="this.src='images/nsorder_list_b.png'" onmouseout="this.src='images/nsorder_list.png'" height="200px;"></a>
				<a href="https://pc-helper.or.jp/" target="_blank"><img src="images/pc-helper.png" onmouseover="this.src='images/pc-helper_b.png'" onmouseout="this.src='images/pc-helper.png'"></a><br>
				</p>
			<h2>会場詳細メニュー</h2>
				<a href="./kaijyo_top.php" target="_blank"><img src="images/kaijyo_top.png" onmouseover="this.src='images/kaijyo_top_b.png'" onmouseout="this.src='images/kaijyo_top.png'"></a>
				<a href="./syousai_display.php" target="_blank"><img src="images/syousai_display.png" onmouseover="this.src='images/syousai_display_b.png'" onmouseout="this.src='images/syousai_display.png'"></a><br>
				<a href="./holding_pref_venue.php" target="_blank"><img src="images/holding_pref_venue.png" onmouseover="this.src='images/holding_pref_venue_b.png'" onmouseout="this.src='images/holding_pref_venue.png'"></a>
				<a href="./facility_check.php" target="_blank"><img src="images/facility_check.png" onmouseover="this.src='images/facility_check_b.png'" onmouseout="this.src='images/facility_check.png'"></a>
			<h2>住所メニュー</h2>
				<p align="left">
				<a href="./tellorder_check.php" target="_blank"><img src="images/tellorder_check.png" onmouseover="this.src='images/tellorder_check_b.png'" onmouseout="this.src='images/tellorder_check.png'"></a>
				<a href="./personal_delete.php" target="_blank"><img src="images/dm_back.png" onmouseover="this.src='images/dm_back_b.png'" onmouseout="this.src='images/dm_back.png'" height="200px;" width="320px;"></a>
				</p>
			<h2>WPSメニュー</h2>
				<a href="./wps_input.php?kbn=2" target="_blank"><img src="images/wps_input.png" onmouseover="this.src='images/wps_input_b.png'" onmouseout="this.src='images/wps_input.png'"></a>
				<a href="./wps_check.php" target="_blank"><img src="images/wps_check.png" onmouseover="this.src='images/wps_check_b.png'" onmouseout="this.src='images/wps_check.png'"></a>
			<h2>郵便局メニュー</h2>
				<a href="./postoffice_list.php" target="_blank"><img src="images/postoffice_list.png" onmouseover="this.src='images/postoffice_list_b.png'" onmouseout="this.src='images/postoffice_list.png'"></a>
			<h2>タウンメールメニュー</h2>
				<p align="left">
				<a href="./postoffice_refer.php" target="_blank"><img src="images/postoffice_refer.png" onmouseover="this.src='images/postoffice_refer_b.png'" onmouseout="this.src='images/postoffice_refer.png'"></a>
				</p>
			<? if($p_Auth > 0){ ?>
				<h2>スタッフ管理メニュー</h2>
					<p align="left">
					<a href="./ep_master.php" target="_blank"><img src="images/ep_master.png" onmouseover="this.src='images/ep_master_b.png'" onmouseout="this.src='images/ep_master.png'" height="200px;" width="320px;"></a>
					<a href="./ep_schedule.php" target="_blank"><img src="images/schedule.png" onmouseover="this.src='images/schedule_b.png'" onmouseout="this.src='images/schedule.png'" height="200px;" width="320px;"></a>
					</p>
				</div>
			<? } ?>
		</div>
		<!--/contents-->
	</div>
	<!--/container-->
</body>
</html>
