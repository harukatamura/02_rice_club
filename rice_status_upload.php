<?php
//==================================================================================================
// ■機能概要
//   ・佐川伝票配送状況取り込み画面
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
	require_once './Classes/PHPExcel.php';
	require_once './Classes/PHPExcel/IOFactory.php';
	//タイムゾーン
	date_default_timezone_set('Asia/Tokyo');

	//オブジェクト生成
	$html = new html();
	$comm = new comm();
	$dba = new dbaccess();

	//実行プログラム名取得
	$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
	$prgname = "佐川伝票配送状況取込";
	$prgmemo = "　精米倶楽部の配送状況の取込を行います。";
	$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);
	
	//本日日付
	$today = date('Y-m-d H:i:s');
	$comm->ouputlog("today=" . $today, $prgid, SYS_LOG_TYPE_DBUG);
	
	$p_staff = $_COOKIE['con_perf_staff'];
	
	$alert = '<div style="font-size: 40px; color: red; font-weight: bold; margin-top: 30px; margin-bottom: 30px;">CSVのBOMを削除して、Shif-jis形式で取り込みを行ってください。</div>';
	
	//一括登録後の表示
	if($_GET['flg'] == 1){
		$alert .= '<p>登録が完了しました。</p>';
	}
	if($_GET['flg'] == 2){
		$alert .= '<div style="font-size: 40px; color: red; font-weight: bold; margin-top: 30px; margin-bottom: 30px;">登録に失敗しました。</div>';
	}
	if(isset($_FILES['upload_file'])){
		$filename = $_FILES["upload_file"]["name"];
		$pro_filepath = $_FILES["upload_file"]["tmp_name"];
		$expand = mime_content_type($pro_filepath);
		$expand = mb_strtolower($expand);
		if(strpos($expand,'csv') !== false || strpos($expand,'text/plain') !== false){
			$filepath = $pro_filepath;
		}else{
			$alert .= '<p><font color="red">csv形式のファイルで登録してください。</font></p>';
		}
	}
	if($filepath<>""){
		//CSV読み込み
		$data = file_get_contents($filepath);// ファイルの読み込み
		$data = mb_convert_encoding($data, 'UTF-8', 'SJIS-win');// 文字コードの変換（UTF-8 → SJIS-win）
		$temp = tmpfile(); //テンポラリファイルの作成
		$meta = stream_get_meta_data($temp); //メタデータの取得
		fwrite($temp, $data); //ファイル書き込み
		rewind($temp); //ファイルポインタの位置を戻す
		//オブジェクトを生成する
		$file = new SplFileObject($meta['uri'], 'rb');
		//CSVファイルの読み込み
		$file->setFlags(
			SplFileObject::READ_CSV
		);
		$file->setCsvControl(',');
		$file->rewind();
		$firstLine = $file->current();
		
		//テーブル項目取得
		$table = "php_rice_shipment";
		$collist = $dba->mysql_get_collist($db, $table);
		
		//初期設定
		$_update_1  = "UPDATE ".$table;
		$_update_1  .= " SET upddt = ".sprintf("'%s'", $today);
		$_update_1  .= " , updcount = updcount + 1";
		$_update_1  .= " , updstaff = ".sprintf("'%s'", $p_staff);
		

		//変数初期化
		$_update = [];
		$i = 0;
		//対象のデータを確認
		if($firstLine[0] == "お問い合せ送り状No."){
			$comm->ouputlog("==== 出荷日を更新します ====", $prgid, SYS_LOG_TYPE_INFO);
			//1行ずつ値を取得する
			foreach ($file as $line) {
				//ヘッダは読み飛ばす
				if($line[0] <> "お問い合せ送り状No."){
					$_update[$i] = "";
					$_update[$i]  .= " , ship_date = ".sprintf("'%s'", $line[13]);
					$_update[$i]  .= " WHERE slipnumber = ".sprintf("'%s'", $line[0]);
					$_update[$i]  .= " AND ship_date = '0000-00-00 00:00:00' ";
					$_update[$i]  .= " AND output_flg = '9' ";
					++$i;
				}
			}
		}else if($firstLine[0] == "発送日"){
			$comm->ouputlog("==== 配達完了日を更新します ====", $prgid, SYS_LOG_TYPE_INFO);
			//1行ずつ値を取得する
			foreach ($file as $line) {
				//ヘッダは読み飛ばす
				if($line[0] <> "発送日" && $line[10] == "配達終了" && $line[13] <> ""){
					$_update[$i] = "";
					$_update[$i]  .= " , receive_date = ".sprintf("'%s'", $line[13]);
					$_update[$i]  .= " WHERE slipnumber = ".sprintf("'%s'", $line[1]);
					$_update[$i]  .= " AND receive_date = '0000-00-00' ";
					$_update[$i]  .= " AND output_flg = '9' ";
					++$i;
				}
			}
		}
		if($i > 1000){
			$i = 1000;
			$alert .= '<p>1000行以上のデータがあったため、1000行目で取り込みを停止しました。</font></p>';
		}
		for($j=0; $j<$i; ++$j){
			$query = "";
			$query = $_update_1.$_update[$j];
			$comm->ouputlog("データ更新 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (!($rs = $db->query($query))) {
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
		}
		//エンコーディングする
		mb_convert_variables('UTF-8', 'SJIS-win', $arr_code);
	}

?>

<!--------------------------------------------------------------------------------------------------
	コンテンツ表示
---------------------------------------------------------------------------------------------------->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<meta charset="UTF-8">
<head>
	<style type="text/css">
		#postcode1, #postcode2 { width: 3em; ime-mode: inactive; }
		#address3 { width: 15em; }
	</style>
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
	table.tbf3{
	width: 100%;
	text-align: center;
	padding: auto;
	margin: auto auto 30px;
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

	/* --- 固定テーブル --- */
	table.tbs{
	width: 1720px;
	text-align: center;
	margin: 10px;
	}
	thead.tbs_thead{
	display:block;
	width:1700px;
	}
	tbody.tbs_tbody{
	display:block;
	overflow-y:scroll;
	width:1720px;
	height:400px;
	}
	th.tbs_th_p1_c {
	color: white;
	background-color: #2B8225; /* 見出しセルの背景色 */
	width: 250px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	th.tbs_th_p2_c {
	color: white;
	background-color: #2B8225; /* 見出しセルの背景色 */
	width: 110px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	th.tbs_th_p3_c {
	color: white;
	background-color: #2B8225; /* 見出しセルの背景色 */
	width: 400px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	td.tbs_td_p1 {
	width: 250px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid gray;
	text-align: right;
	}
	td.tbs_td_p2 {
	width: 110px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid gray;
	text-align: right;
	}
	td.tbs_td_p1_l {
	width: 250px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid gray;
	text-align: left;
	}
	td.tbs_td_p2_l {
	width: 110px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid gray;
	text-align: left;
	}
	td.tbs_td_p3_l {
	width: 400px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid gray;
	text-align: left;
	}
	.header_logo {
		vertical-align: middle;
		font-size:56px;
	}
	th {
		position: sticky;
		top: 0;
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
	<script type="text/javascript">
		//アップロードボタン
		function Mclk_Upload(){
			//画面項目設定
			document.forms['frm'].action = './rice_status_upload.php';
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
			<p class="header_logo"><img src="images/logo_jemtc.png" alt="" /><?= $prgname; ?></p>
		</div>
	</table>
	<div id="contents">
		<div id="main">
			<?php echo $prgmemo; ?>
			<div id="formWrap">
				<?php echo $alert ?>
				<form name="frm" method = "post" enctype="multipart/form-data" action="./barcode_upload_manual.php">
					<h2>ファイル情報</h2><br>
					<div>
						<table class="tbd" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
							<tr>
								<th class="tbd_th"><strong>ファイル</strong></th>
								<td class="tbd_req"></td>
								<td class="tbd_td">
									<input type="file" name="upload_file" size="30" /><br />
									<input type="hidden" name="mode" value="upload" /><br />
								</td>
							</tr>
						</table>
						<table class="tbf3" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
							<td class="tbf3_td_p1_c"><input type="button" name="アップロード" value="アップロード" onclick="javascript:Mclk_Upload()"></td>
							<td class="tbf3_td_p2_c"><a href="#" onClick="window.close(); return false;"><input type="button" value="閉じる"></a></td>
						</table>
					</div>
					<div>
					<?php if($i > 0){ ?>
						<p><?php echo  $i ?>行のデータを登録しました。</br>
						<input type="text" name="max_row" value="<?php echo  $max_row ?>" style="display:none">
						※すでに出荷日が登録されているデータはスキップして登録されます。</p>
					<?php } ?>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
</body>

<!-- データベース切断 -->
<?php if ($result) { $dba->mysql_discon($db); } ?>

</html>
