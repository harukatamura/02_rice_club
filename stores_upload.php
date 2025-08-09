<?php
//==================================================================================================
// ■機能概要
//   ・通販販売実績取り込み画面
//==================================================================================================

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
	$prgname = "通販販売実績取込";
	$prgmemo = "　通販販売実績データの取込を行います。";
	$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);
	
	//担当者・会場情報を取得
	$query = " SELECT idxnum, sales_name, staff FROM php_headoffice_list ";
	$query .= " WHERE delflg=0 ";
	$query .= " AND aggregation_flg=1 ";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$sales_name[$row['idxnum']] = $row['sales_name'];
		$staff[$row['idxnum']] = $row['staff'];
	}
	
	//伝票番号未取込のデータを取得
	$query = " SELECT A.idxnum, A.name, A.response, A.outputdt ";
	$query .= " FROM `php_telorder__` A ";
	$query .= " LEFT OUTER JOIN php_pc_failure B ON A.idxnum=B.tel_idx AND B.delflg=0 ";
	$query .= " WHERE A.response<>'' ";
	$query .= " AND A.slipnumber='' ";
	$query .= " AND A.delflg=0 ";
	$query .= " AND B.kbn IS NULL ";
	$query .= " AND A.receptionday>'2022-06-01' ";
	$query .= " ORDER BY A.response, A.outputdt ";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	$arr_no_number = [];
	while ($row = $rs->fetch_array()) {
		$arr_no_number[$row['idxnum']] = array("お名前" => $row['name'], "発送元" => $row['response'], "出力日" => date('Y/n/j H:i', strtotime($row['outputdt'])));
	}
	
	$note = "<br><font color='red'>　オーダー番号と伝票番号に紐づいています。<br>　伝票番号の取込がされていない場合や、<br>　ひとつのオーダー番号で2台以上注文して別々に発送した場合、<br>　複数まとめて発送した場合などは<br>　うまく連携されない場合もあるのでご注意ください。</font>";

	//本日日付
	$today = date('Y-m-d H:i:s');
	$comm->ouputlog("today=" . $today, $prgid, SYS_LOG_TYPE_DBUG);

	//一括登録後の表示
	if($_GET['flg'] == 1){
		$alert = '<p>登録が完了しました。</p>';
	}
	if($_GET['flg'] == 2){
		$alert = '<p>登録に失敗しました。</p>';
	}
	if(isset($_FILES['upload_file'])){
		$filename = $_FILES["upload_file"]["name"];
		$pro_filepath = $_FILES["upload_file"]["tmp_name"];
		$expand = mime_content_type($pro_filepath);
		$expand = mb_strtolower($expand);
		if(strpos($expand,'csv') !== false || strpos($expand,'text/plain') !== false){
			$filepath = $pro_filepath;
			$g_style = 1;
		}
	}
	$p_remarks = "";
	$p_remarks .= "返品・交換・修理の際に必要になりますので、";
	$p_remarks .= "ヤマト伝票のお届け先控え欄を保証書に添付してお手元に保管ください。";
/*	$p_remarks .= "返品・交換・修理の際に必要になりますので、\n";
	$p_remarks .= "ヤマト伝票のお届け先控え欄を保証書に添付してお手元に保管ください。\n";
	$p_remarks .= "※ヤマト運輸からの通知をもとにメールをお送りしております。\n";
	$p_remarks .= "　万が一機器のお届け後にこのメールが届いた場合はご容赦ください。\n";
	$p_remarks .= "\n";
	$p_remarks .= "領収書は、ご希望の方のみメールで送付させていただきます。\n";
	$p_remarks .= "ご希望の場合は、宛名とオーダー番号を記載の上、\n";
	$p_remarks .= "ストア内のお問い合わせフォームよりご連絡ください。\n";
	$p_remarks .= "[https://jemtc-ns.stores.jp/inquiry]\n";
	$p_remarks .= "\n";
	$p_remarks .= "※※※※※※※※※※※※※※※※※※※※※※※※※※※※\n";
	$p_remarks .= "このメールは送信専用アドレスより配信されています。\n";
	$p_remarks .= "直接ご返信いただきましても確認できかねますのでご了承ください。\n";
	$p_remarks .= "※※※※※※※※※※※※※※※※※※※※※※※※※※※※";
*/	if($filepath<>""){
		//CSV読み込み
		if($g_style == 1){
			$locale = 100001;
			$data = file_get_contents($filepath);// ファイルの読み込み
		//	$data = mb_convert_encoding($data, 'UTF-8', 'SJIS-win');// 文字コードの変換（UTF-8 → SJIS-win）
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
			$g_row = 0;
			$p_list[] = array("オーダー番号", "オーダー日", "氏(配送先)", "名(配送先)", "配送方法", "到着予定日時", "問い合わせ番号", "備考", "発送完了");
			//1行ずつ値を取得する
			foreach ($file as $line) {
				//内容が空白の場合と先頭行の場合、取込済データは読み飛ばす
				if($line[0] <> "" && $line[0] <> "オーダー番号" && $line[8] <> "JSP"){
					//発送状況を取得
					$query = " SELECT B.status, A.slipnumber";
					$query .= " FROM php_telorder__ A ";
					$query .= " LEFT OUTER JOIN php_yamato_status B ON A.slipnumber=B.slipnumber ";
					$query .= " WHERE A.order_num = '".$line[0]."' ";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($query))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$g_status = "";
					while ($row = $rs->fetch_array()) {
						$g_status = $row['status'];
						$g_slipnumber = $row['slipnumber'];
					}
					if($g_status <> ""){
						$p_status[] = $g_status;
						$p_slipnumber[] = $g_slipnumber;
						$p_order_num[] = $line[0];
						$receptionday[] = $line[1];
						$p_name1[] = $line[2];
						$p_name2[] = $line[3];
						++$g_row;
						$p_list[] = array($line[0], $line[1], $line[2], $line[3], "ヤマト運輸", "", $g_slipnumber, $p_remarks, "1");
					}
				}
			}
			//CSVにデータを反映
			$view = "";
			foreach ($p_list as $val) {
				//配列の内容を「,」区切りで連結する
				$view .= implode(",", $val). "\r\n";
			}
			//BOMBを設定
			$view = pack('C*',0xEF,0xBB,0xBF). $view;
			//「$view」を「stores_up.csv」ファイルに書き出しする
			file_put_contents("stores_up.csv", $view);
			//エンコーディングする
		//	mb_convert_variables('UTF-8', 'SJIS-win', $arr_code);
		}
	}
	//エクセル出力処理
/*	if($filepath<>""){
		$comm->ouputlog("CSV出力 実行", $prgid, SYS_LOG_TYPE_INFO);
		$reader = PHPExcel_IOFactory::createReader("Excel2007");
		$book = $reader->load("./stores_up.xlsx");
		$sheet = $book->getSheetByName("stores_up");
		
		$data = file_get_contents($filepath);// ファイルの読み込み
	//	$data = mb_convert_encoding($data, 'UTF-8', 'SJIS-win');// 文字コードの変換（UTF-8 → SJIS-win）
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
		$j = 1;
		//1行ずつ値を取得する
		foreach ($file as $line) {
			//発送状況を取得
			$query = " SELECT B.status, A.slipnumber";
			$query .= " FROM php_telorder__ A ";
			$query .= " LEFT OUTER JOIN php_yamato_status B ON A.slipnumber=B.slipnumber ";
			$query .= " WHERE A.order_num = '".$line[0]."' ";
			$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (!($rs = $db->query($query))) {
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
			$g_status = "";
			while ($row = $rs->fetch_array()) {
				$g_status = $row['status'];
				$g_slipnumber = $row['slipnumber'];
			}
			if($g_status <> ""){
				$p_status[] = $g_status;
				$p_slipnumber[] = $g_slipnumber;
				$p_order_num[] = $line[0];
				$receptionday[] = $line[1];
				$p_name1[] = $line[2];
				$p_name2[] = $line[3];
				++$j;
				$sheet->setCellValueByColumnAndRow(0, $j, $line[0]);
				$sheet->setCellValueByColumnAndRow(1, $j, $line[1]);
				$sheet->setCellValueByColumnAndRow(2, $j, $line[2]);
				$sheet->setCellValueByColumnAndRow(3, $j, $line[3]);
				$sheet->setCellValueByColumnAndRow(4, $j, "ヤマト運輸");
				$sheet->setCellValueByColumnAndRow(5, $j, "");
				$sheet->setCellValueByColumnAndRow(6, $j, $g_slipnumber);
				$sheet->setCellValueByColumnAndRow(7, $j, $p_remarks);
				$sheet->setCellValueByColumnAndRow(8, $j, "1");
			}
		}
		//ファイル出力
		$filename = "stores_up(".date('ymd').").csv";
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $filename . '"');
		header('Cache-Control: max-age=0');
		//ファイル破損を防ぐ
		ob_clean(); 
		//対象ファイル保存する
		$objWriter = PHPExcel_IOFactory::createWriter($book,'Excel2007');
		// 「UTF-8」ファイルとしての「BOM」ファイルヘッダ
	//	$objWriter->setUseBOM(true);
		
		$objWriter->save('php://output',$filename);
		exit;
		setcookie ('downloaded', '', time()-3600);
		setcookie ('downloaded', "yes");
	}
*/	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
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
	td.tbs_td_p1_c {
	width: 250px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid gray;
	text-align: center;
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
	.btn-circle-border-simple {
	position: relative;
	display: inline-block;
	text-decoration: none;
	background: #b3e1ff;
	color: #668ad8;
	width: 400px;
	border-radius: 10%;
	border: solid 2px #668ad8;
	text-align: center;
	overflow: hidden;
	font-weight: bold;
	transition: .4s;
	box-shadow: 1px 1px 3px #666666;
	font-size: 30px;
	padding: 20px;
	margin: 20px 30px 20px 30px;
	}
	.btn-circle-border-simple:hover {
	background: #668ad8;
	color: white;
	text-decoration: none;
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
		//出力ボタン
		function Mclk_slipData(){
			if(window.confirm("データを出力しますか？")){
				document.forms['frm'].elements['excel_btn'].click();
			}else{
				return false;
			}
		}
		function Mclk_pushData(){
			window.open('stores_up.csv');
		}
		//取り込みボタン
		function Mclk_Upload(){
			document.forms['frm'].action = "./stores_upload.php";
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
			<p><img src="images/logo_ecommerce.png" alt="" /></p>
		</div>
	</table>
	<div id="contents">
		<div id="main">
			<?php echo $prgmemo; ?>
			<p style="text-align:right">
			<a href="https://bmypage.kuronekoyamato.co.jp/bmypage/servlet/jp.co.kuronekoyamato.wur.hmp.servlet.user.HMPLGI0010JspServlet" target="_blank">ヤマトB2クラウド</a><br>
			<a href="https://id.stores.jp/oauth2/auth?client_id=976d5e56-9f24-479b-996f-489e29ba4dfc&nonce=147aa3163832e44727192a97b020fd58&redirect_uri=https%3A%2F%2Fstores.jp%2Fauth%2Fstoresid%2Fcallback&response_type=code&scope=openid+email&state=82a8a3c281e469074f30c27d9740fb38" target="_blank">STORES管理者画面</a>
			</p>
			<?php echo $note; ?>
			<div id="formWrap">
				<? if(count($arr_no_number) > 0){ ?>
					<p style="color:red;"><strong>　※※※※　伝票を出力後、伝票番号が連携されていないデータがあります　※※※※</strong></p>
					<table class="tbs" summary="ベーステーブル" >
						<thead class="tbs_thead">
							<tr>
								<th class="tbs_th_p1_c">出力日時</th>
								<th class="tbs_th_p2_c">発送元</th>
								<th class="tbs_th_p2_c">J-Office<br>注文番号</th>
								<th class="tbs_th_p1_c">お名前</th>
							</tr>
						</thead>
						<tbody class="tbs_tbody">
							<? foreach($arr_no_number as $key => $val){ ?>
								<tr>
									<td class="tbs_td_p1_l"><? echo $val["出力日"]; ?></td>
									<td class="tbs_td_p2_l"><? echo $val["発送元"]; ?></td>
									<td class="tbs_td_p2_l"><? echo $key ?></td>
									<td class="tbs_td_p1_c"><? echo $val["お名前"]; ?></td>
								</tr>
							<? } ?>
						</tbody>
					</table>
				<? } ?>
				<?php echo $alert ?>
				<form name="frm" method = "post" enctype="multipart/form-data">
					<p style="text-align:left">
						<a href="./yamato_status_upload.php" target="_blank" class="btn-circle-border-simple">①ﾔﾏﾄ配送状況ｱｯﾌﾟﾛｰﾄﾞ</a>
					</p>
					<h2>ファイル情報</h2><br>
					<div>
						<table class="tbd" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
							<tr>
								<th class="tbd_th"><strong>②STORESよりダウンロードしたファイルを選択</strong></th>
								<td class="tbd_req"></td>
								<td class="tbd_td">
									<input type="file" name="upload_file" size="30" /><br />
									<input type="hidden" name="mode" value="upload" /><br />
								</td>
							</tr>
						</table>
					<p style="text-align:left">
						<a href="javascript:Mclk_Upload()" class="btn-circle-border-simple">③STORESﾃﾞｰﾀｱｯﾌﾟﾛｰﾄﾞ</a>
						<input type="submit" name="アップロード" style="display:none;">
					</p>
					</div>
					<br>
					<div>
					<?php if($filepath <> ""){ ?>
						<h2>取得データ一覧</h2><br>
						<? echo "販売方法：".$sales_name[$locale] ?>
						<table class="tbs" summary="ベーステーブル" >
							<thead class="tbs_thead">
								<tr>
									<th class="tbs_th_p2_c">オーダー番号</td>
									<th class="tbs_th_p2_c">注文日</td>
									<th class="tbs_th_p2_c">名前</td>
									<th class="tbs_th_p2_c">伝票番号</td>
									<th class="tbs_th_p2_c">発送状況</td>
								</tr>
							</thead>
							<tbody class="tbs_tbody">
								<?php for($i=0; $i<$g_row; ++$i){ ?>
									<tr>
										<td class="tbs_td_p2">
											<?php echo $p_order_num[$i]; ?>
										</td>
										<td class="tbs_td_p2">
											<?php echo date('Y/n/j', strtotime($receptionday[$i])); ?>
										</td>
										<td class="tbs_td_p2_l">
											<?php echo $p_name1[$i]." ".$p_name2[$i]; ?>
										</td>
										<td class="tbs_td_p2_l">
											<?php echo $p_slipnumber[$i]; ?>
										</td>
										<td class="tbs_td_p2_l">
											<?php echo $p_status[$i]; ?>
										</td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
						<p><?php echo  $g_row ?>行のデータを取得しました。</p>
						<input type="text" value="<? echo $g_row ?>" name="行数" style="display:none">
					</div>
					<?php } ?>
					<p style="text-align:left">
						<a href="javascript:Mclk_pushData()" class="btn-circle-border-simple">④取込用ﾃﾞｰﾀﾀﾞｳﾝﾛｰﾄﾞ</a>
					</p>
					<p style="text-align:left">
						<strong>⑤ダウンロードしたファイルをSTORESにアップロード</strong><br><br>
					</p>
					<a href="#" onClick="window.close(); return false;"><input type="button" value="⑥閉じる"></a>
				</form>
			</div>
		</div>
	</div>
</div>
</body>

<!-- データベース切断 -->
<?php if ($result) { $dba->mysql_discon($db); } ?>

</html>
