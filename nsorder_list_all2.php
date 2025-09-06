
<?php
//==================================================================================================
// ■機能概要
// ・通販伝票出力画面
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
	require_once('./Classes/PHPExcel.php');
	require_once('./Classes/PHPExcel/IOFactory.php');
	//タイムゾーン
	date_default_timezone_set('Asia/Tokyo');

	//オブジェクト生成
	$html = new html();
	$comm = new comm();
	$dba = new dbaccess();
	$sql = new SQL_aggregate();
	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);

	$j_office_Uid = $_COOKIE['j_office_Uid'];

	//実行プログラム名取得
	$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
	$prgname = "通販注文一覧画面";
	$prgmemo = "　通販の注文一覧が確認できます。";
	$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);
	$arr_sales_name = array("100001_新規" => "新規顧客ネット通販", "100001_既存" => "既存顧客ネット通販", "100001_代理店" => "代理店経由ネット通販", "100004" => "レンタル", "100002" => "電話注文");
	$s_sales_name = array(
		"100001_新規" => array("販売方法" => "100001", "電話番号" => "新規")
		,"100001_既存" => array("販売方法" => "100001", "電話番号" => "既存")
		,"100001_代理店" => array("販売方法" => "100001", "電話番号" => "代理店")
		,"100004" => array("販売方法" => "100004", "電話番号" => "新規")
		,"100002" => array("販売方法" => "100002", "電話番号" => "")
	);
	$sales_name = array("100001_新規" => "新規顧客ネット通販", "100001_" => "新規顧客ネット通販", "100001_既存" => "既存顧客ネット通販", "100001_代理店" => "代理店経由ネット通販", "100004_" => "レンタル", "100004_新規" => "レンタル", "100004_新規" => "レンタル", "100002_" => "電話注文");
	$arr_status = array("1" => "伝票未発行", "2" => "保留中", "9" => "伝票発行済");
	$arr_delflg = array("1" => "削除済");
	if(isset($_POST['search'])){
		$p_arr_status = $_POST['ステータス'];
		$p_arr_delflg = $_POST['削除フラグ'];
		$p_sales_name = $_POST['販売方法'];
		$slip_status = $_POST['slip_status'];
	}else{
		if($_GET['rentflg'] == 1){
			$p_arr_status = array("1", "2", "9");
			$p_sales_name = array("100004");
		}else if($_GET['horyuflg'] == 1){
			$p_arr_status = array("2");
			$p_sales_name = array("100001_新規","100001_代理店","100001_既存","100002","100004");
		}else{
			$p_arr_status = array("1", "2");
			$p_sales_name = array("100001_新規","100001_代理店","100001_既存","100004");
		}
	}
	$_select = " SELECT B.modelnum  ";
	$_select .= " FROM php_telorder__ A ";
	$_select .= " LEFT OUTER JOIN ( ";
	$_select .= " SELECT modelnum, desktopflg FROM php_ecommerce_pc_info  ";
	$_select .= " GROUP BY modelnum ";
	$_select .= " )B ON A.modelnum=B.modelnum ";
	$_select .= " WHERE A.delflg = 0 ";
	$_select .= " AND (B.desktopflg = 0 OR B.desktopflg IS NULL) ";
	$_select .= " AND A.output_flg=0";
	$_select .= " AND A.status=1";
	$_select .= " GROUP BY B.modelnum ";
	$_select .= " ORDER BY B.modelnum ";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($_select, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($_select))) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	$n_modelnum_list = [];
	while ($row = $rs->fetch_array()) {
		$n_modelnum_list[] = $row['modelnum'];
	}
	$_select = " SELECT B.modelnum  ";
	$_select .= " FROM php_telorder__ A ";
	$_select .= " LEFT OUTER JOIN ( ";
	$_select .= " SELECT modelnum, desktopflg FROM php_ecommerce_pc_info  ";
	$_select .= " GROUP BY modelnum ";
	$_select .= " )B ON A.modelnum=B.modelnum ";
	$_select .= " WHERE A.delflg = 0 ";
	$_select .= " AND B.desktopflg = 1 ";
	$_select .= " AND A.output_flg=0";
	$_select .= " AND A.status=1";
	$_select .= " GROUP BY B.modelnum ";
	$_select .= " ORDER BY B.modelnum ";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($_select, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($_select))) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	$d_modelnum_list = [];
	while ($row = $rs->fetch_array()) {
		$d_modelnum_list[] = $row['modelnum'];
	}
	$g_n_modelnum_list = [];
	$g_d_modelnum_list = [];
	$g_n_modelnum_list = $_POST['型番ノート'];
	$g_d_modelnum_list = $_POST['型番デスク'];
	if(count($g_n_modelnum_list)>0 && count($g_d_modelnum_list)>0){
		$s_modelnum_list = array_merge($g_d_modelnum_list, $g_n_modelnum_list);
	}else if(count($g_n_modelnum_list)>0){
		$s_modelnum_list = $g_n_modelnum_list;
	}else if(count($g_d_modelnum_list)>0){
		$s_modelnum_list = $g_d_modelnum_list;
	}else{
		$g_n_modelnum_list = $n_modelnum_list;
		$g_d_modelnum_list = $d_modelnum_list;
		$s_modelnum_list = array_merge($d_modelnum_list, $n_modelnum_list);
	}
	$s_modelnum_list = str_replace("\n","",$s_modelnum_list);
	//共通型番取得
	$_select = " SELECT A.sales_name, A.sales_name, A.category, A.modelnum ";
	$_select .= " FROM php_ecommerce_pc_info A ";
	$_select .= " WHERE A.delflg = 0 ";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($_select, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($_select))) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$modelnum[$row['sales_name']][$row['category']] = $row['modelnum'];
	}
	
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	
	
	$json_array_s_modelnum = json_encode($s_modelnum_list);
?>

<!--------------------------------------------------------------------------------------------------
	コンテンツ表示
---------------------------------------------------------------------------------------------------->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<meta charset="UTF-8">
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
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
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
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	td.tbd_td_p4_r {
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
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

	.tbd thead th {
	  /* 縦スクロール時に固定する */
	  position: -webkit-sticky;
	  position: sticky;
	  top: 0;
	  background-color:#00885a;
	  height: 3em;
	   /* tbody内のセルより手前に表示する */
	  z-index: 1;
	  color:white;			
	}
	input[type=checkbox] {
	  transform: scale(1.5);
	}
	</style>
	<script type="text/javascript">
		<!--
		function hpbmapinit() {
			hpbmaponload();
		}
		//-->
	</script>
	<?php $html->output_htmlheadinfo3($prgname); ?>
	<script type="text/javascript" src="//code.jquery.com/jquery-2.1.0.min.js"></script>
	<!--sweetalert2-->
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@8"></script>
	<script type="text/javascript">
		//顧客詳細 選択ボタン
		function Push_kokyaku(Cell){
			//画面項目設定
			var rowINX = 'idxnum='+Cell;
			window.open('./manual/telorder/telorder_kokyaku.php?' + rowINX,'_blank');
		}
		//削除ボタン
		function Push_del(idx, name,p_way){
			if(p_way == 0 || p_way == 7 || p_way == 8){
				var p_text = "支払済です。返金が必要なため、NSチームに引き継いでください。";
			}else{
				var p_text = "返金はありません。データを削除します。";
			}
			Swal.fire({
				title: 'No.'+idx+' '+name+' 様',
				text: p_text,
				type: 'warning',
				showCancelButton: true,
				confirmButtonText: '削除',
				cancelButtonText: 'やめる'  , 
				allowOutsideClick : false   //枠外クリックは許可しない
				}).then((result) => {
				if (result.value) {
					console.log(idx+' '+name+" けします！");
					if(p_way == 0 || p_way == 7 || p_way == 8){
						Swal.fire({
							title: '返金方法を選択',
							text: '※どちらか決まってない場合は画面を閉じて処理をキャンセルしてください',
							type: 'warning',
							showCancelButton: true,
							confirmButtonText: 'ｸﾚｼﾞｯﾄ返金',
							cancelButtonText: '口座返金'  , 
							allowOutsideClick : false   //枠外クリックは許可しない
							}).then((result) => {
							if (result.value) {
								console.log(idx+' '+name+" ｸﾚｼﾞｯﾄ返金");
								document.forms['frm'].action = './nsorder_sql.php?do=delete_stores&idxnum='+idx;
								document.forms['frm'].submit();
							}else{
								console.log(idx+' '+name+" 返品登録へ");
								document.forms['frm'].action = './nsorder_sql.php?do=delete_bank&idxnum='+idx;
								document.forms['frm'].submit();
							}
						});
					}else{
						console.log(idx+' '+name+" 削除！返金なし");
						document.forms['frm'].action = './nsorder_sql.php?do=delete&idxnum='+idx;
						document.forms['frm'].submit();
					}
				}else{
					console.log(idx+' '+name+" けすのやめといた！！");
				}
			});
		}
	</script>
	<style type="text/css">
		.btn-circle-border-simple {
		position: relative;
		display: inline-block;
		text-decoration: none;
		background: #b3e1ff;
		color: #668ad8;
		width: 300px;
		border-radius: 10%;
		border: solid 2px #668ad8;
		text-align: center;
		overflow: hidden;
		font-weight: bold;
		transition: .4s;
		box-shadow: 1px 1px 3px #666666;
		font-size: 30px;
		padding: 50px;
		margin: 20px 30px 20px 30px;
		}
		.btn-circle-border-simple:hover {
		background: #668ad8;
		color: white;
		text-decoration: none;
		}
		.btn-circle-border-simple2 {
		display: inline-block;
		text-decoration: none;
		background: #ffdab9;
		color: #ff8c00;
		width: 300px;
		border-radius: 10%;
		border: solid 2px #ff8c00;
		text-align: center;
		overflow: hidden;
		font-weight: bold;
		transition: .4s;
		box-shadow: 1px 1px 3px #666666;
		font-family: Courier New;
		font-size: 30px;
		padding: 50px;
		margin: 20px 30px 20px 30px;
		}
		.btn-circle-border-simple2:hover {
		background: #ff8c00;
		color: white;
		text-decoration: none;
		}
		.btn-flat-admin {
		display: inline-block;
		padding: 10 10 10 10;
		margin: 10 40 10 10;
		text-decoration: none;
		background: #ffc0cb;
		color: #8b4513;
		border: solid 2px #ffc0cb;
		border-radius: 3px;
		transition: .4s;
		font-size:120%;
		}
		.btn-flat-admin:hover {
		background: #800000;
		color: #ffc0cb
		}
	</style>
</head>

<body>
<br>
<div id="container">
	<table class="base" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
		<!-- ヘッダー情報生成 -->
		<div id="header">
			<p><img src="images/logo_ecommerce_output.png" alt="" /></p>
		</div>
	</table>
	<div id="contents">
		<div id="main">
			<div id="formWrap">
				<? if($_GET['rentflg'] == 0){ ?>
					<p style="text-align:right"><a href="./nsorder_list_top.html" class="btn-flat-admin">過去の形式で見たい場合はこちら</a></p>
				<? } ?>
				<h2>検索条件</h2><br>
				<form name="frm" method = "post" action="./nsorder_list_all2.php" >
					<table class="tbd" align="center" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
						<tr>
							<th class="tbd_th"><strong>担当者</strong></th>
							<td class="tbd_req"><img src="./images/hisu.gif" alt="必須"></td>
							<td class="tbd_td">
								<? foreach($arr_sales_name as $key => $val){ ?>
									<label><input type="checkbox" name="販売方法[]" value="<? echo $key ?>" <? if(in_array($key, $p_sales_name)){ ?>checked='checked'<? } ?>><? echo $val ?></label>
								<? } ?>
							</td>
						</tr>
						<th class="tbd_th"><strong>ステータス</strong></th>
							<td class="tbd_req"></td>
							<td class="tbd_td">
								<? foreach($arr_status as $key => $val){ ?>
									<label><input type="checkbox" name="ステータス[]" value="<? echo $key ?>" <? if(count($p_arr_status)>0 && in_array($key, $p_arr_status)){ ?>checked='checked'<? } ?>><? echo $val ?></label>
								<? } ?>
								<? /* foreach($arr_delflg as $key => $val){ ?>
									<label><input type="checkbox" name="削除フラグ[]" value="<? echo $key ?>" <? if(count($p_arr_delflg)>0 && in_array($key, $p_arr_delflg)){ ?>checked='checked'<? } ?>><? echo $val ?></label>
								<?  } */?>
							</td>
						</tr>
						<th class="tbd_th"><strong>受付日</strong></th>
							<td class="tbd_req"></td>
							<td class="tbd_td">
							<input type="date" name="order_date1" value="<?php if ($_POST['order_date1']<>'') { echo substr($_POST['order_date1'], 0,10); } ?>">～<input type="date" name="order_date2" value="<?php if ($_POST['order_date2']<>'') { echo substr($_POST['order_date2'], 0,10); } ?>"><font color="red"></font>
							</td>
						</tr>
						<tr>
							<th class="tbd_th"><strong>受付NO.</strong></th>
							<td class="tbd_req"></td>
							<td class="tbd_td">
								<input name="インデックス" style="height:25px; width:240px;" value=<?php echo $_POST['インデックス'] ?>>
							</td>
						</tr>
						<tr>
							<th class="tbd_th"><strong>名前</strong></th>
							<td class="tbd_req"></td>
							<td class="tbd_td">
								<input name="名前" style="height:25px; width:240px;" value=<?php echo $_POST['名前'] ?>>
							</td>
						</tr>
						<tr>
							<th class="tbd_th"><strong>電話番号</strong></th>
							<td class="tbd_req"></td>
							<td class="tbd_td">
								<input name="電話番号" style="height:25px; width:240px;" value=<?php echo $_POST['電話番号'] ?>>
							</td>
						</tr>
						<tr>
							<th class="tbd_th"><strong>STORESオーダー番号</strong></th>
							<td class="tbd_req"></td>
							<td class="tbd_td">
								<input name="オーダー番号" style="height:25px; width:240px;" value=<?php echo $_POST['オーダー番号'] ?>>
							</td>
						</tr>
					</table>
					<table class="tbf3" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
						<tr>
							<td class="tbf3_td_p1_c"><input type="submit" name="search" style="width:100px; height:30px; font-size:12px;" value="検索"></td>
						</tr>
					</table>
					<h2>受注詳細</h2><br>
					<table class="tbh" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
							<tr><td class="category"><strong>■◇■販売データ■◇■　※お名前クリックで詳細データの確認・修正ができます</strong></td></tr>
					</table>
					<table class="tbd" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル" id= "TBL">
						<thead>
							<tr>
								<th></th>
								<th>受付NO.</th>
								<th>受付日</th>
								<th>状態<br>(伝票発行日時)</th>
								<th>名前</th>
								<th>型番</th>
								<th title="全文">オプション※</th>
								<th>金額</th>
								<th>購入台数</th>
								<th>購入方法<br>(支払方法)</th>
								<th title="全文">備考※</th>
								<th>伝票番号<br>(出荷元)</th>
							</tr>
						</thead>
						<!-- 個別表示 -->
						<?php
						$comm->ouputlog("☆★☆処理開始☆★☆ ", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog("　電話番号＝" . $p_phoneno, $prgid, SYS_LOG_TYPE_INFO);
						//データ存在フラグ
						$dateflg1 = 0;
						$dateflg2 = 0;
						//データ存在フラグ
						$custodydt = "";
						// ================================================
						// ■　□　■　□　個別表示　■　□　■　□
						// ================================================
						//----- データ抽出
						$query .= "
						SELECT 
							A.t_idx , COUNT(*) as cnt, A.idxnum , A.updcount , A.insdt , A.receptionist , A.upddt , A.receptionday , A.status , A.name , A.phonenum1 , A.response, IFNULL(A.modelnum, IFNULL(B.modelnum, A.category)) as modelnum, A.cash, A.buynum, A.remark, A.option_han, A.sales_name, A.designated_day, A.output_flg, A.slipnumber, A.response, A.reception_telnum, A.p_way, A.outputdt, C.kbn, A.p_method 
						FROM 
							php_telorder__ A 
						LEFT OUTER JOIN 
							php_ecommerce_pc_info B 
						ON 
							A.category=B.category AND A.sales_name=B.sales_name 
						LEFT OUTER JOIN php_pc_failure C 
							ON A.idxnum = C.tel_idx AND C.delflg=0 
						WHERE 1 ";
						if (count($p_sales_name)>0){
							$i = 0;
							$query .= " AND (( ";
							foreach($p_sales_name as $val){
								if($i > 0){
									$query .= ") OR (";
								}
								if($val == "100001_新規" || $val == "100004"){
									$query .= " A.sales_name = " . sprintf("'%s'", $s_sales_name[$val]['販売方法']);
									$query .= " AND (A.reception_telnum LIKE " . sprintf("'%%%s%%'", $s_sales_name[$val]['電話番号']);
									$query .= " OR A.reception_telnum ='') ";
								}else{
									$query .= " A.sales_name = " . sprintf("'%s'", $s_sales_name[$val]['販売方法']);
									$query .= " AND A.reception_telnum LIKE " . sprintf("'%%%s%%'", $s_sales_name[$val]['電話番号']);
								}
								++$i;
							}
							$query .= " ))";
						}
						if (count($p_arr_status)>0){
							$i = 0;
							$query .= " AND  A.status IN ( ";
							foreach($p_arr_status as $key => $val){
								if($i > 0){
									$query .= ", ";
								}
								$query .= sprintf("'%s'", $val);
								++$i;
							}
							$query .= " )";
						}
						if (!in_array("9", $p_arr_status, true)) {
							$query .= " AND  C.kbn IS NULL ";
						}
					//	if (count($p_arr_delflg) == 0){
							$query .= " AND  A.delflg = 0 ";
					//	}
						if ($_POST['order_date1'] <> '' ) {
							$search_q_date1 = $_POST['order_date1']." 00:00:00";
							$query .= " AND A.receptionday >=  " . sprintf("'%s'", $search_q_date1);
						}
						if ($_POST['order_date2'] <> '' ) {
							$search_q_date2 = $_POST['order_date2']." 23:59:59";
							$query .= " AND A.receptionday <=  " . sprintf("'%s'", $search_q_date2);
						}
						if ($_POST['電話番号'] <> "") {
							$kensaku_val = str_replace(array("-", "ー", "‐", "‑"), "", $_POST['電話番号']);
							$kensaku_val = trim($kensaku_val);
							$query .= " 
							AND 
							(
								(
									CASE 
										WHEN A.phonenum1 LIKE '%-%' THEN REPLACE(A.phonenum1,'-','') 
										WHEN A.phonenum1 LIKE '%ー%' THEN REPLACE(A.phonenum1,'ー','') 
										WHEN A.phonenum1 LIKE '%‐%' THEN REPLACE(A.phonenum1,'‐','') 
										WHEN A.phonenum1 LIKE '%‑%' THEN REPLACE(A.phonenum1,'‑','') 
										ELSE A.phonenum1 
									END 
									LIKE ".sprintf("'%%%s%%'", $kensaku_val).
								")
								OR
								(
									CASE 
										WHEN A.phonenum2 LIKE '%-%' THEN REPLACE(A.phonenum2,'-','') 
										WHEN A.phonenum2 LIKE '%ー%' THEN REPLACE(A.phonenum2,'ー','') 
										WHEN A.phonenum2 LIKE '%‐%' THEN REPLACE(A.phonenum2,'‐','') 
										WHEN A.phonenum2 LIKE '%‑%' THEN REPLACE(A.phonenum2,'‑','') 
										ELSE A.phonenum2 
									END 
									LIKE ".sprintf("'%%%s%%'", $kensaku_val).
								")
							)";
						}
						if ($_POST['名前'] <> "") {
							$kensaku_val = str_replace(array(" ", "　"), "", $_POST['名前']);
							$kensaku_val = trim($kensaku_val);
							$query .= "
							AND 
							(
								(
									CASE 
										WHEN A.name LIKE '%　%' THEN REPLACE(A.name,'　','') 
										WHEN A.name LIKE '% %' THEN REPLACE(A.name,' ','') 
										ELSE A.name 
									END 
									LIKE ".sprintf("'%%%s%%'", $kensaku_val).
								") 
								OR 
								(
									CASE 
										WHEN A.ruby LIKE '%　%' THEN REPLACE(A.ruby,'　','') 
										WHEN A.ruby LIKE '% %' THEN REPLACE(A.ruby,' ','') 
										ELSE A.ruby 
									END 
									LIKE ".sprintf("'%%%s%%'", $kensaku_val).
								") 
							)";
						}if($_POST['インデックス'] <> "") {
							$query .= " AND A.t_idx LIKE " . sprintf("'%%%s%%'", $_POST['インデックス']);
						}if($_POST['オーダー番号'] <> "") {
							$query .= " AND A.order_num LIKE " . sprintf("'%%%s%%'", $_POST['オーダー番号']);
						}
						$query .= " GROUP BY A.t_idx";
						$query .= " ORDER BY A.receptionday DESC, A.idxnum DESC";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
						if (! $rs = $db->query($query)) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						$i = 0;
						$cash = 0;
						$category = "";
						$buynum = 0;
						$idxnum = "";
						$option_han = "";
						$p_option_han = "";
						$g_category = [];
						$g_option_han = [];
						while ($row = $rs->fetch_array()) {
							$cash = 0;
							$category = "";
							$remark = "";
							$buynum = 0;
							$idxnum = "";
							$option_han = "";
							$p_option_han = "";
							$g_category = [];
							$g_option_han = [];
							$remark = $row['remark'];
							if($row['cnt'] > 1){
							//	$comm->ouputlog("１台注文　変数に格納", $prgid, SYS_LOG_TYPE_INFO);
								$query2 = "SELECT A.idxnum, A.cash, A.category, IFNULL(A.modelnum, IFNULL(B.modelnum, A.category)) as modelnum, A.postcd1, A.postcd2, A.address1, A.address2, A.address3, A.name, A.phonenum1, A.option_han, A.designated_day, A.specified_times, A.buynum, A.remark, A.sales_name, A.designated_day, A.slipnumber, A.outputdt";
								$query2 .= " FROM php_telorder__ A";
								$query2 .= " LEFT OUTER JOIN php_ecommerce_pc_info B ON A.category=B.category AND A.sales_name=B.sales_name ";
								$query2 .= " WHERE A.delflg = '".$row['delflg']."'";
								$query2 .= " AND A.output_flg = '".$row['output_flg']."'";
								$query2 .= " AND A.status = '".$row['status']."'";
								$query2 .= " AND A.t_idx = '".$row['t_idx']."'";
								$query2 .= " ORDER BY A.idxnum DESC ";
								$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
								$comm->ouputlog($query2, $prgid, SYS_LOG_TYPE_DBUG);
								if (!($rs2 = $db->query($query2))) {
									$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								}
								while($row2 = $rs2->fetch_array()) {
									$cash += $row2['cash'];
									$buynum += $row2['buynum'];
									$g_category[] = $row2['modelnum'];
									if($row2['option_han'] <> "なし"){
										if($p_option_han ==""){
											$p_option_han = $row2['option_han'];
										}else{
											$p_option_han .= "・".$row2['option_han'];
										}
									}
									$idxnum = $row2['idxnum'];
									if($row['remark'] <> $row2['remark']){
										$remark .= $row2['remark'];
									}
								}
								foreach(array_count_values($g_category) as $key => $val){
									$category .= $key." × ".$val." 台 <br>";
								}
								if($p_option_han == ""){
									$option_han = "なし";
								}else{
									$g_option_han = explode("・", $p_option_han);
									foreach(array_count_values($g_option_han) as $key => $val){
										$option_han .= $key." × ".$val." 個 <br>";
									}
								}
							}else{
							//	$comm->ouputlog("１台注文　変数に格納", $prgid, SYS_LOG_TYPE_INFO);
								$cash = $row['cash'];
								$category = $row['modelnum'];
								$buynum = $row['buynum'];
								$idxnum = $row['idxnum'];
								$option_han = $row['option_han'];
							}
							$i++;
							//明細設定
							if (($i % 2) == 0) { ?>
								<tr style="">
							<? } else { ?>
								<tr style="background-color:#EDEDED">
							<? }?>
								<!-- インデックス -->
								<td width="70" style="text-align:center; vertical-align:middle;"><a href="javascript:Push_del(<? echo $row['t_idx'] ?>,'<? echo $row['name'] ?>','<? echo $row['p_way'] ?>')"><img src="images/batu.png" alt="" /></a></td>
								<!-- インデックス -->
								<td width="70" style="text-align:center; vertical-align:middle;"><?php echo str_pad($row['t_idx'], 6, "0", STR_PAD_LEFT) ?></td>
								<!-- 受付日時 -->
								<td style="text-align:center; vertical-align:middle;"><?php echo date('Y/n/j', strtotime($row['receptionday'])); ?><br>
								<? echo date('H:i:s',strtotime($row['receptionday'])) ?>
								</td>
								<!-- 状態 -->
								<td style="vertical-align:middle;">
									<? if($row['kbn'] <> ""){
										echo $row['kbn'];
									}else{
										 echo $arr_status[$row['status']];
									}?>
									<? if($row['status'] == 9 && $row['kbn'] == "" && $category <> "JSP"){echo "<br>（".date('Y/n/j', strtotime($row['outputdt']))."<br>".date('H:i:s', strtotime($row['outputdt']))."）";} ?>
								</td>
								<!-- お名前 -->
								<td style="vertical-align:middle;">
									<a href="javascript:Push_kokyaku(<? echo $row['t_idx'] ?>)"><?php echo $row['name'] ?></a>
								</td>
								<!-- 型番 -->
								<td style="text-align:center; vertical-align:middle;"><?php echo $category; ?></td>
								<!-- オプション -->
								<td style="text-align:center; vertical-align:middle;" title="<? echo $option_han ?>"><?php echo mb_substr($option_han,0,10); ?><? if(mb_strlen($option_han) > 9){echo "・・・";} ?></td>
								<!-- 金額 -->
								<td style="text-align:center; vertical-align:middle;"><?php echo number_format($cash)."円"; ?></td>
								<!-- 台数 -->
								<td style="text-align:center; vertical-align:middle;"><?php echo $buynum."台"; ?></td>
								<!-- 販売方法 -->
								<td style="text-align:center; vertical-align:middle;">
									<?php echo $sales_name[$row['sales_name']."_".$row['reception_telnum']]; ?><br>
									<?php
										$method = $row['p_method'];
										if ($method == "") {
											if ($row['p_way'] == 2) {
												$method = "代金引換";
											}
										}
										echo "(" . $method . ")";
									?>
								</td>
								<!-- 備考 -->
								<td style="text-align:left; vertical-align:middle;" title="<? echo $remark ?>"><?php echo mb_substr($remark,0,10); ?><? if(mb_strlen($remark) > 9){echo "・・・";} ?></td>
								<!-- 伝票番号 -->
								<td style="text-align:center; vertical-align:middle;"><a href="https://member.kms.kuronekoyamato.co.jp/parcel/detail?pno=<? echo $row['slipnumber']; ?>" target="_blank"><? echo $row['slipnumber']; ?></a><? if($row['response'] <> ""){ ?><Br>(<?php echo $row['response']; ?>)<? } ?></td>
							</tr>
						<? } ?>
					</table>
					<input type="text" name="行数"  id="行数" value="<? echo $i ?>" style="display:none">
					<table class="tbf3" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
						<td class="tbf3_td_p2_c"><a href="#" onClick="window.close(); return false;"><input type="button" value="閉じる"></a></td>
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
