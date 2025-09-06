 <?php
//==================================================================================================
// ■機能概要
// ・精米倶楽部伝票出力画面
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
	require_once('./Classes/PHPExcel.php');
	require_once('./Classes/PHPExcel/IOFactory.php');
	//タイムゾーン
	date_default_timezone_set('Asia/Tokyo');

	//オブジェクト生成
	$html = new html();
	$comm = new comm();
	$dba = new dbaccess();
	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);

	//実行プログラム名取得
	$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
	$prgname = "精米倶楽部伝票出力画面";
	$prgmemo = "　精米倶楽部の伝票を発行できます。";
	$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

	//本日日付
	$today = date('Y-m-d H:i:s');
	$comm->ouputlog("today=" . $today, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog(" 対象月 =" . $_POST['対象月'], $prgid, SYS_LOG_TYPE_DBUG);
	$g_staff = $_GET['staff'];
	$p_staff = $_COOKIE['con_perf_staff'];
	
	//対象週取得
	if(isset($_POST['対象月'])) {
		$p_month = $_POST['対象月'];
		$p_year =  $_POST['対象年'];
	}else{
		$p_month = date('m');
		$p_year = date('Y');
	}
	
	//表示制御
	if (isset($_POST['表示制御'])) {
		$p_display = $_POST['表示制御'];
	}
	else {
		$p_display = 1;
	}
	//表示制御
	if (isset($_POST['表示制御2'])) {
		$p_display2 = $_POST['表示制御2'];
	}
	else {
		$p_display2 = 1;
	}

	$g_factory = "本部";
	//エクセル出力処理
	$outputno = $_POST['outputno'];
	// 佐川伝票出力
	if(isset($_POST['output_btn']) && $outputno != "") {
		$comm->ouputlog("Excel出力 実行", $prgid, SYS_LOG_TYPE_INFO);
		$reader = PHPExcel_IOFactory::createReader("Excel2007");
		$book = $reader->load("./rice_sagawa_template.xlsx");
		$sheet = $book->getSheetByName("Sheet0");
		$chksheet = $book->getSheetByName("リスト");
		$headsheet = $book->getSheetByName("表紙");
		//フラグを立てて出力するデータを取得
		foreach($outputno as $value){
			$query = "UPDATE php_rice_shipment ";
			$query .= " SET output_flg = 3";
			$query .= " , slip_staff = '".$p_staff."'";
			$query .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
			$query .= " ,updcount = updcount + 1";
			$query .= " WHERE ship_idxnum = $value";
			$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (!($rs = $db->query($query))) {
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
		}
		$time_list = array("0812" => "01","1214" => "12","1416" => "14","1618" => "16","1820" => "18","1821" => "04","1921" => "19");
		$time_list2 = array("0812" => "020","1214" => "022","1416" => "023","1618" => "024","1820" => "025","1821" => "021","1921" => "026");
		$i = 1;
		$j = 3; // 開始行
		$t = 0;
		$g_category = "";
		$g_remarks2 = "";
		$g_cash = 0;
		$allnum = 0;
		$category_list = [];
		$weight_list = [];
		$sumnum_list = [];
		//出力対象のデータを取得
		$query = "SELECT A.ship_idxnum, A.tanka, A.category, A.weight, A.delivery_date, A.specified_times";
		$query .= " ,C.name, C.company, C.phonenum1, C.postcd1, C.postcd2, C.address1, C.address2, C.address3, C.p_way, B.remarks ";
		$query .= " , CASE ";
		$query .= "  WHEN YEAR(B.date_s)=YEAR(A.delivery_date) AND MONTH(B.date_s)=MONTH(A.delivery_date) THEN '初回' ";
		$query .= "  WHEN YEAR(B.date_e)=YEAR(A.delivery_date) AND MONTH(B.date_e)=MONTH(A.delivery_date) THEN '最終回' ";
		$query .= "  ELSE '' END as remarks2 ";
		$query .= " FROM php_rice_shipment A";
		$query .= " LEFT OUTER JOIN php_rice_subscription B ON A.subsc_idxnum=B.subsc_idxnum ";
		$query .= " LEFT OUTER JOIN php_rice_personal_info C ON B.personal_idxnum=C.idxnum ";
		$query .= " WHERE A.output_flg = 3 AND A.stopflg = 0 AND A.delflg = 0";
		$query .= " ORDER BY  ";
		$query .= " CASE ";
		$query .= "  WHEN YEAR(B.date_s)=YEAR(A.delivery_date) AND MONTH(B.date_s)=MONTH(A.delivery_date) THEN 0 ";
		$query .= "  WHEN YEAR(B.date_e)=YEAR(A.delivery_date) AND MONTH(B.date_e)=MONTH(A.delivery_date) THEN 1 ";
		$query .= "  ELSE 2 END ";
		$query .= " , A.category, A.weight, C.postcd1, C.postcd2";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while($row = $rs->fetch_array()){
			if($g_category <> $row['category'] || $row['weight'] <> $g_weight){
				$t = 0;
			}
			++$i;
			++$j;
			++$t;
			$j2 = $j - 1;
			//佐川シートのセルに値をセット
			$sheet->setCellValueExplicitByColumnAndRow(2, $i, $row['phonenum1'], PHPExcel_Cell_DataType::TYPE_STRING);
			$sheet->setCellValueByColumnAndRow(3, $i, $row['postcd1']."-".$row['postcd2']);
			$sheet->setCellValueByColumnAndRow(4, $i, $row['address1']);
			$sheet->setCellValueByColumnAndRow(5, $i, $row['address2']);
			$sheet->setCellValueByColumnAndRow(6, $i, $row['address3']);
			$sheet->setCellValueByColumnAndRow(8, $i, $row['company']);
			$sheet->setCellValueByColumnAndRow(7, $i, $row['name']);
			$sheet->setCellValueExplicitByColumnAndRow(14, $i, "050-5272-9665", PHPExcel_Cell_DataType::TYPE_STRING);
			$sheet->setCellValueExplicitByColumnAndRow(16, $i, "293688870000", PHPExcel_Cell_DataType::TYPE_STRING);
			$sheet->setCellValueExplicitByColumnAndRow(17, $i, "050-5272-9665", PHPExcel_Cell_DataType::TYPE_STRING);
			$sheet->setCellValueExplicitByColumnAndRow(18, $i, "461-0011", PHPExcel_Cell_DataType::TYPE_STRING);
			$sheet->setCellValueByColumnAndRow(19, $i, "愛知県名古屋市東区白壁3-12-13");
			$sheet->setCellValueByColumnAndRow(20, $i, "中部産業連盟ビル新館8F");
			$sheet->setCellValueByColumnAndRow(21, $i, "(一社)日本電子機器補修協会");
			$sheet->setCellValueByColumnAndRow(22, $i, "主食共同購入部");
			$sheet->setCellValueByColumnAndRow(24, $i, "精米倶楽部");
			$sheet->setCellValueByColumnAndRow(25, $i, $row['category']);
			$sheet->setCellValueByColumnAndRow(26, $i, $row['weight']."kg");
			// 時間指定があれば該当する時間帯指定サービスを選択（指定なしの場合は天地無用）・項目記入
			$sheet->setCellValueByColumnAndRow(44, $i, date('Ymd',strtotime($row['delivery_date'])));
			$sheet->setCellValueExplicitByColumnAndRow(45, $i, $time_list[$row['specified_times']]);
			$sheet->setCellValueByColumnAndRow(47, $i, $row['tanka']);
			$p_tax = floor($row['tanka'] * 0.08);
			$sheet->setCellValueByColumnAndRow(48, $i, $p_tax);
			// 指定シール設定
			$sheet->setCellValueExplicitByColumnAndRow(51, $i, "010"); // eコレクト(全て可能)
			if($row['specified_times'] <> ""){
				$sheet->setCellValueExplicitByColumnAndRow(52, $i, $time_list2[$row['specified_times']]); // 時間帯指定サービス
			}
			$sheet->setCellValueByColumnAndRow(60, $i, date('Ym24',strtotime($row['delivery_date'])));
			$sheet->setCellValueByColumnAndRow(65, $i, "RC");
			$sheet->setCellValueByColumnAndRow(66, $i ,"お米");
			$sheet->setCellValueByColumnAndRow(67, $i, "購入方法");
			$sheet->setCellValueByColumnAndRow(68, $i, $row['ship_idxnum']);
			//チェックリストにデータ格納
			$chksheet->setCellValueByColumnAndRow(0, $j, "□");
			$chksheet->setCellValueByColumnAndRow(1, $j, $t);
			$chksheet->setCellValueByColumnAndRow(2, $j, $row['name']);
			$chksheet->setCellValueByColumnAndRow(3, $j, $row['remarks2']);
			//文字を赤くする
			$chksheet->getStyle('D'.$j)->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
			//文字を太くする
			$chksheet->getStyle('D'.$j)->getFont()->setBold(true);
			$chksheet->setCellValueByColumnAndRow(4, $j, $row['category']);
			$chksheet->setCellValueByColumnAndRow(5, $j, $row['weight']."kg");
			$chksheet->setCellValueByColumnAndRow(6, $j, $row['tanka']);
			$chksheet->setCellValueByColumnAndRow(7, $j, date('Y/n/j', strtotime($row['delivery_date'])));
			$chksheet->setCellValueByColumnAndRow(8, $j, $row['remarks']);
			//カテゴリーが変われば改ページ挿入
			if(($g_category <> $row['category'] || $row['weight'] <> $g_weight || $g_remarks2 <> $row['remarks2']) && $j > 4){
				$chksheet->setBreak('A'.$j2, PHPExcel_Worksheet::BREAK_ROW);
			}
			if($g_remarks2 <> $row['remarks2'] || $g_category <> $row['category']){
				$category_list[$row['remarks2']][] = $row['category'];
			}if($g_remarks2 <> $row['remarks2'] || $g_category <> $row['category'] || $g_weight <> $row['weight']){
				$weight_list[$row['remarks2']][$row['category']][] = $row['weight'];
			}
			$sumnum_list[$row['remarks2']][$row['category']][$row['weight']] = $sumnum_list[$row['remarks2']][$row['category']][$row['weight']] + 1;
			$sumnum_list[$row['remarks2']][0][0] = $sumnum_list[$row['remarks2']][0][0] + 1;
			$g_category = $row['category'];
			$g_weight = $row['weight'];
			$g_remarks2 = $row['remarks2'];
			$allnum += 1;
		}
		//罫線をつける
		$chksheet->getStyle('A4:I'.$j)->getBorders()->getAllBorders()->setBorderStyle( PHPExcel_Style_Border::BORDER_THIN );
		$chksheet->setBreakByColumnAndRow(0, 3, PHPExcel_Worksheet::BREAK_NONE);
		//印刷範囲を指定
		$chksheet->getPageSetup()->setPrintArea('A1:I'.$j);
		$chksheet->setCellValueByColumnAndRow(0, 1, "【精米倶楽部】".date('Y/n/j')."(".$p_staff."発行)");
		$chksheet->getStyleByColumnAndRow(0, 1)->getFont()->setBold(true);
		//リストを作成する
		$j=4;
		$headsheet->setCellValueByColumnAndRow(0, 2, date('Y年n月')."　精米倶楽部　発送予定数");
		foreach($category_list as $key => $val){
			$startrow = $j;
			$headsheet->setCellValueByColumnAndRow(0, $j, $key);
			foreach($val as $val2){
				$headsheet->setCellValueByColumnAndRow(1, $j, $val2);
				foreach($weight_list[$key][$val2] as $val3){
					$headsheet->setCellValueByColumnAndRow(2, $j, $val3."kg");
					$headsheet->setCellValueByColumnAndRow(3, $j, $sumnum_list[$key][$val2][$val3]);
					if($j % 2 == 0){
						//セルに色をつける
						$headsheet->getStyle('B'.$j.':D'.$j)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("f2f2f2");
					}
					++$j;
				}
			}
			//合計
			$headsheet->setCellValueByColumnAndRow(3, $j, $sumnum_list[$key][0][0]);
			$headsheet->setCellValueByColumnAndRow(1, $j, "合計");
			//文字を太くする
			$headsheet->getStyle('B'.$j.':D'.$j)->getFont()->setBold(true);
			//罫線をつける
			$headsheet->getStyle('B'.$startrow.':D'.$j)->getBorders()->getAllBorders()->setBorderStyle( PHPExcel_Style_Border::BORDER_THIN );
			//セルに色をつける
			$headsheet->getStyle('B'.$j.':D'.$j)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("B7DEE8");
			++$j;
			++$j;
		}
		++$j;
		//セル結合
		$headsheet->mergeCells('C'.$j.':D'.$j);
		//文字を右寄せ
		$headsheet->getStyle('C'.$j)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		//合計出力
		$headsheet->setCellValueByColumnAndRow(1, $j, "総合計");
		$headsheet->setCellValueByColumnAndRow(2, $j, $allnum);
		//文字を太くする
		$headsheet->getStyle('B'.$j.':D'.$j)->getFont()->setBold(true);
		//罫線をつける
		$headsheet->getStyle('B'.$j.':D'.$j)->getBorders()->getAllBorders()->setBorderStyle( PHPExcel_Style_Border::BORDER_THIN );
		//セルに色をつける
		$headsheet->getStyle('B'.$j.':D'.$j)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("31869B");
		//文字を白くする
		$headsheet->getStyle('B'.$j.':D'.$j)->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE);
		
		//フラグを更新
		$query = "UPDATE php_rice_shipment ";
		$query .= " SET output_flg = 9";
		$query .= " , slip_staff = '".$p_staff."'";
		$query .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
		$query .= " ,updcount = updcount + 1";
		$query .= " WHERE output_flg = 3 ";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		//ファイル出力
		$book->setActiveSheetIndexByName('リスト');
		//佐川シートを非表示にする
		$sheet->setSheetState(PHPExcel_Worksheet::SHEETSTATE_HIDDEN);
		$filename = "【精米倶楽部】".date('Ymd')."_佐川伝票.xlsx";
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $filename . '"');
		header('Cache-Control: max-age=0');
		//ファイル破損を防ぐ
		ob_clean(); 
		//対象ファイル保存する
		$objWriter = PHPExcel_IOFactory::createWriter($book,'Excel2007');
		$objWriter->save('php://output',$filename);
		exit;
		setcookie ('downloaded', '', time()-3600);
		setcookie ('downloaded', "yes");
	}
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
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
	th.tbd_th_c {
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #0C58A6; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
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
	width: 150px;
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

	td.tbd_td_p1_l {
	width: 200px;
	padding: 1px 10px 1px; /* データセルのパディング（上、左右、下） */
	border: none;
	text-align: left;
	vertical-align:middle;
	}
	td.tbd_td_p1_c {
	width: 200px;
	padding: 1px 10px 1px; /* データセルのパディング（上、左右、下） */
	border: none;
	text-align: center;
	vertical-align:middle;
	}
	td.tbd_td_p1_r {
	width: 200px;
	padding: 1px 10px 1px; /* データセルのパディング（上、左右、下） */
	border: none;
	text-align: right;
	vertical-align:middle;
	}
	td.tbd_td_p2 {
	width: 100px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p3_r {
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p3_c {
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
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
	<script type="text/javascript">
		//二重登録防止後伝票出力
		function MClickBtn(action) {
			var maxrow = document.getElementById('行数').value;
			g_num = 0;
			for(i=1; i<=maxrow; ++i){
				if(document.getElementById('box'+i).checked == true){
					g_num = g_num + 1;
				}
			}
			if(window.confirm('出荷用の伝票を出力します。\n'+ g_num +'件')){
				document.forms['frm'].elements[action].click();
				return;
			}else{
				return false;
			}
		}
		//チェックボックス全選択
		$(function(){
			var checkAll = '#checkAll'; //「すべて」のチェックボックスのidを指定
			var checkBox = 'input[name="outputno[]"]'; //チェックボックスのnameを指定
			$( checkAll ).on('click', function() {
			$( checkBox ).prop('checked', $(this).is(':checked') );
			});
			$( checkBox ).on( 'click', function() {
			var boxCount = $( checkBox ).length; //全チェックボックスの数を取得
			var checked = $( checkBox + ':checked' ).length; //チェックされているチェックボックスの数を取得
			if( checked === boxCount ) {
			$( checkAll ).prop( 'checked', true );
			} else {
			$( checkAll ).prop( 'checked', false );
			}
			});
		});
		//行選択でチェック
		function CheckRow(row) {
			console.log("CheckRow　" + row + "行目");
			// チェックボックスはスルー
			if (event.target.type !== 'checkbox') {
				document.getElementById('box'+row).click();
			}
		}
		function ChangeColor() {
			console.log("ChangeColor");
			
			const table = document.getElementById('TBL');
			const maxrow = parseInt(document.getElementById('行数').value, 10);

			for (let i = 1; i <= maxrow; ++i) {
				const box = document.getElementById('box' + i);
				const falseflg = document.getElementById('falseflg' + i);

				if (!table.rows[i]) continue; // 行が存在しない場合はスキップ

				if (box && box.checked) {
					table.rows[i].style.backgroundColor = "pink";
				} else {
					if (falseflg && falseflg.value == "1") {
						table.rows[i].style.backgroundColor = "#ff0000";
					} else {
						table.rows[i].style.backgroundColor = (i % 2 === 0) ? "white" : "#EDEDED";
					}
				}
			}
		}
		$("form").submit(function() {
			setInterval(function () {
				if($.cookie("downloaded")) {
					$.removeCookie("downloaded", { path: "/" });
					alert("ダウンロード完了");
				}
			}, 1000);
		});
		//検索条件　表示/非表示
		function clickBtn1(){
			const p1 = document.getElementById("p1");

			if (document.forms['frm'].elements['表示制御'].value == 2) {
				// noneで非表示
				p1.style.display ="none";
			}else{
				// blockで表示
				p1.style.display ="block";
			}
		}
		//検索条件　表示/非表示
		function clickBtn2(){
			const p2 = document.getElementById("p2");

			if (document.forms['frm'].elements['表示制御2'].value == 2) {
				// noneで非表示
				p2.style.display ="none";
			}else{
				// blockで表示
				p2.style.display ="block";
			}
		}
		//対象月変更
		function Mclk_onChange(kbn){
			document.forms['frm'].action = './<? echo $prgid;?>.php?kbn=' + kbn;
			document.forms['frm'].submit();
		}
		//発送完了メール送信ボタン
		function Push_Send(idxnum){
			if(window.confirm("No." +idxnum + "の発送完了メールを送信します")){
				//値をPHPに受け渡す
				$.ajax({
				type: "POST", //　GETでも可
				url: "./rice_mail_slip_reply.php", //　送り先
				data: { 
				ship_idxnum: idxnum
				 }, //　渡したいデータをオブジェクトで渡す
				dataType : "json", //　データ形式を指定
				scriptCharset: 'utf-8' //　文字コードを指定
				})
				.then(
				function(mail_result){　 //　paramに処理後のデータが入って戻ってくる
				alert("　結果：" + mail_result[1]);
				console.log('resister', "伝票番号：" + mail_result[0] + "　結果：" + mail_result[1]);
				},
				function(XMLHttpRequest, textStatus, errorThrown){
				console.log(errorThrown); //　エラー表示
				});
			}
		}
	</script>
	<style type="text/css">
		.btn-circle-border-simple {
		position: relative;
		display: inline-block;
		text-decoration: none;
		background: #b3e1ff;
		color: #668ad8;
		width: 250px;
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
		width: 250px;
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
		.btn-circle-border-simple-sagawa {
		position: relative;
		display: inline-block;
		text-decoration: none;
		background: #1e50a2;
		color: #dbffff;
		width: 250px;
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
		.btn-circle-border-simple-sagawa:hover {
		background: #0f2350;
		color: white;
		text-decoration: none;
		}
	fieldset {
	  border: none;
	  padding: 0;
	  margin: 0;
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
	.header_logo {
		vertical-align: middle;
		font-size:64px;
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
	.btn-flat-border {
	display: inline-block;
	padding: 0.3em 1em;
	text-decoration: none;
	background-color: #afeeee;
	color: #191970;
	border: solid 2px #191970;
	border-radius: 3px;
	transition: .4s;
	}
	.btn-flat-border:hover {
	background: #191970;
	color: white;
	}
	</style>
</head>

<body>
<br>
<div id="container">
	<table class="base" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
		<!-- ヘッダー情報生成 -->
		<div id="header">
			<p class="header_logo"><img src="images/logo_jemtc.png" alt="" />精米倶楽部伝票発行</p>
		</div>
	</table>
	<div id="contents">
		<div id="main">
			<?php echo $prgmemo; ?>
			<table style="width:95%;">
				<tr>
					<td>
						<p style="color:red">
							※伝票を発行された方は、必ず以下の3点をおこなってください。<br>
							　①佐川伝票データの取込<br>
						</p>
					</td>
					<td style="text-align:right;">
						<a href="./rice_slip_upload.php" class="btn-border-b" target="_blank">伝票番号取込</a>
					</td>
					<td style="text-align:right;">
						<a href="./rice_status_upload.php" class="btn-border-b" target="_blank">出荷日取込</a>
					</td>
				</tr>
			</table>
			<div id="formWrap">
				<form name="frm" method = "post" action="./rice_slip.php">
					<h2 id="sub1">検索条件</h2><br>
						<fieldset>
							<input type="radio" name="表示制御" id="d-item-1" class="radio-inline__input" style="font-size: 30px;" value="1" onChange="javascript:clickBtn1();" <? if($p_display==1){echo "checked=\"checked\"";}?>/>
							<label class="radio-inline__label" for="d-item-1"><b>表示</b></label>
							<input type="radio" name="表示制御" id="d-item-2" class="radio-inline__input" style="font-size: 30px;" value="2" onChange="javascript:clickBtn1();" <? if($p_display==2){echo "checked=\"checked\"";}?>/>
							<label class="radio-inline__label" for="d-item-2"><b>非表示</b></label>
						</fieldset>
						<p id="p1">
							<!--非表示ここから-->
							<table class="tbd" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
								<tr>
									<th class="tbd_th"><strong>対象月</strong></th>
									<td class="tbd_req"><img src="./images/hisu.gif" alt="必須"></td>
									<td class="tbd_td">

										<fieldset>
											<?
												$cnt=1;
												$query = "";
												$query = $query." SELECT DATE_FORMAT(A.date,'%Y') as year ";
												$query = $query." FROM php_calendar A ";
												$query = $query." GROUP BY year ";
												$query = $query." ORDER BY year ";
												$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
												$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
												if (!($rs = $db->query($query))) {
													$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
												}
												while ($row = $rs->fetch_array()) {
											?>
														<input id="y-item-<? echo $cnt ?>" class="radio-inline__input" type="radio" name="対象年" value="<? echo $row['year'] ?>" onChange="javascript:Mclk_onChange('y')" <? if($p_year == $row['year']) { echo "checked=\"checked\"";} ?>/>
														<label class="radio-inline__label" for="y-item-<? echo $cnt ?>"><center><? echo $row['year'] ?>年</center></label>
											<?
													$cnt = $cnt + 1;
												}
											?>
										</fieldset>
										<hr style="border:none;border-top:dashed 1px ;height:1px;">
										<fieldset>
											<? 
												$cnt=1;
												for($i=1; $i <= 12; $i++) {
											?>
														<input id="m-item-<? echo $cnt ?>" class="radio-inline__input" type="radio" name="対象月" value="<? echo sprintf('%02d', $cnt); ?>" onChange="javascript:Mclk_onChange('m')" <? if(ltrim($p_month, '0') == $cnt) { echo "checked=\"checked\"";} ?>/>
														<label class="radio-inline__label" for="m-item-<? echo $cnt ?>"><center><? echo $cnt ?>月</center></label>
											<?
													$cnt = $cnt + 1;
												}
											?>
										</fieldset>
									</td>
								</tr>
							</table>
							<!--ここまで-->
						</p>
						<!-- 個別表示 -->
						<h2>集計データ</h2>
						<table class="tbt" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
							<tr>
								<th class="tbd_th_p2" ></th>
								<th class="tbd_th_p2" >合計</th>
								<th class="tbd_th_p2" >伝票未発行</th>
								<th class="tbd_th_p2" >伝票発行エラー</th>
								<th class="tbd_th_p2" >発送準備中</th>
								<th class="tbd_th_p2" >配送中</th>
								<th class="tbd_th_p2" >伝票発行後ｷｬﾝｾﾙ</th>
								<th class="tbd_th_p2" >受取完了</th>
							</tr>
							<?php
							$comm->ouputlog("☆★☆処理開始☆★☆ ", $prgid, SYS_LOG_TYPE_INFO);
							// ================================================
							// ■　□　■　□　個別表示　■　□　■　□
							// ================================================
							//----- データ抽出
							$query = "SELECT 
								 CASE 
								 WHEN YEAR(B.date_s)=YEAR(A.delivery_date) AND MONTH(B.date_s)=MONTH(A.delivery_date) THEN '初回' 
								 WHEN YEAR(B.date_e)=YEAR(A.delivery_date) AND MONTH(B.date_e)=MONTH(A.delivery_date) THEN '最終回' 
								 ELSE '2回目以降' END as title
								,COUNT(*) AS total_num  -- 全件数
								,SUM(CASE WHEN A.output_flg = 0 THEN 1 ELSE 0 END) AS yet_num
								,SUM(CASE WHEN A.output_flg = 3 THEN 1 ELSE 0 END) AS error_num
								,SUM(CASE WHEN A.output_flg = 9 AND A.ship_date = '0000-00-00' THEN 1 ELSE 0 END) AS slip_num
								,SUM(CASE WHEN A.output_flg = 9 AND A.ship_date <> '0000-00-00'AND A.receive_date = '0000-00-00' THEN 1 ELSE 0 END) AS delivery_num
								,SUM(CASE WHEN A.delflg = 1 THEN 1 ELSE 0 END) AS cancel_num
								,SUM(CASE WHEN A.output_flg = 9 AND A.receive_date <> '0000-00-00' THEN 1 ELSE 0 END) AS done_num
								 FROM php_rice_shipment A 
								 LEFT OUTER JOIN php_rice_subscription B ON A.subsc_idxnum=B.subsc_idxnum  
								 LEFT OUTER JOIN php_rice_personal_info C ON B.personal_idxnum=C.idxnum  WHERE A.stopflg = 0 
								 AND (A.delflg = 0 OR A.slipnumber<>'') 
								 AND A.delivery_date BETWEEN '".$p_year.$p_month."01' AND LAST_DAY('".$p_year.$p_month."01')
								 GROUP BY 
								 CASE 
								 WHEN YEAR(B.date_s)=YEAR(A.delivery_date) AND MONTH(B.date_s)=MONTH(A.delivery_date) THEN 0 
								 WHEN YEAR(B.date_e)=YEAR(A.delivery_date) AND MONTH(B.date_e)=MONTH(A.delivery_date) THEN 2 
								 ELSE 1 END 
								 ORDER BY  
								 CASE 
								 WHEN YEAR(B.date_s)=YEAR(A.delivery_date) AND MONTH(B.date_s)=MONTH(A.delivery_date) THEN 0 
								 WHEN YEAR(B.date_e)=YEAR(A.delivery_date) AND MONTH(B.date_e)=MONTH(A.delivery_date) THEN 2 
								 ELSE 1 END ";
							$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
							if (! $rs = $db->query($query)) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
							$i = 0;
							$total_num = 0;
							$yet_num = 0;
							$error_num = 0;
							$slip_num = 0;
							$delivery_num = 0;
							$done_num = 0;
							while ($row = $rs->fetch_array()) {
								++$i;
								//明細設定
								if (($i % 2) == 0) { ?>
									<tr style="background-color:#EDEDED;">
								<? } else { ?>
									<tr>
								<? } ?>
									<th class="tbd_td_p1_c" ><? echo $row['title']; ?></th>
									<td class="tbd_td_p1_r"><? echo $row['total_num']; ?></td>
									<td class="tbd_td_p1_r"><? echo $row['yet_num']; ?></td>
									<td class="tbd_td_p1_r"><? echo $row['error_num']; ?></td>
									<td class="tbd_td_p1_r"><? echo $row['slip_num']; ?></td>
									<td class="tbd_td_p1_r"><? echo $row['delivery_num']; ?></td>
									<td class="tbd_td_p1_r"><? echo $row['cancel_num']; ?></td>
									<td class="tbd_td_p1_r"><? echo $row['done_num']; ?></td>
								</tr>
								<?
								$total_num += $row['total_num'];
								$yet_num += $row['yet_num'];
								$error_num += $row['error_num'];
								$slip_num += $row['slip_num'];
								$delivery_num += $row['delivery_num'];
								$cancel_num += $row['cancel_num'];
								$done_num += $row['done_num'];
							} ?>
							<tr style="background-color:d3d3d3;">
								<th class="tbd_td_p1_c" >合計</th>
								<td class="tbd_td_p1_r"><? echo $total_num; ?></td>
								<td class="tbd_td_p1_r"><? echo $yet_num; ?></td>
								<td class="tbd_td_p1_r"><? echo $error_num; ?></td>
								<td class="tbd_td_p1_r"><? echo $slip_num; ?></td>
								<td class="tbd_td_p1_r"><? echo $delivery_num; ?></td>
								<td class="tbd_td_p1_r"><? echo $cancel_num; ?></td>
								<td class="tbd_td_p1_r"><? echo $done_num; ?></td>
							</tr>
						</table><br><br>
						<!-- 個別表示 -->
						<?php
						$comm->ouputlog("☆★☆処理開始☆★☆ ", $prgid, SYS_LOG_TYPE_INFO);
						// ================================================
						// ■　□　■　□　個別表示　■　□　■　□
						// ================================================
						//----- データ抽出
						$query = "SELECT A.ship_idxnum, A.tanka, A.category, A.weight, A.delivery_date, A.specified_times, A.output_flg";
						$query .= " ,C.name, C.company, C.phonenum1, C.postcd1, C.postcd2, C.address1, C.address2, C.address3, B.remarks, B.subsc_idxnum ";
						$query .= " , CASE ";
						$query .= "  WHEN YEAR(B.date_s)=YEAR(A.delivery_date) AND MONTH(B.date_s)=MONTH(A.delivery_date) THEN '初回' ";
						$query .= "  WHEN YEAR(B.date_e)=YEAR(A.delivery_date) AND MONTH(B.date_e)=MONTH(A.delivery_date) THEN '最終回' ";
						$query .= "  ELSE '' END as remarks2 ";
						$query .= " FROM php_rice_shipment A";
						$query .= " LEFT OUTER JOIN php_rice_subscription B ON A.subsc_idxnum=B.subsc_idxnum ";
						$query .= " LEFT OUTER JOIN php_rice_personal_info C ON B.personal_idxnum=C.idxnum ";
						$query .= " WHERE A.stopflg = 0";
						$query .= " AND A.output_flg = 0";
						$query .= " AND A.delflg = 0";
						$query .= " AND A.delivery_date BETWEEN '".$p_year.$p_month."01' AND LAST_DAY('".$p_year.$p_month."01')";
						$query .= " ORDER BY  ";
						$query .= " CASE ";
						$query .= "  WHEN YEAR(B.date_s)=YEAR(A.delivery_date) AND MONTH(B.date_s)=MONTH(A.delivery_date) THEN 0 ";
						$query .= "  WHEN YEAR(B.date_e)=YEAR(A.delivery_date) AND MONTH(B.date_e)=MONTH(A.delivery_date) THEN 1 ";
						$query .= "  ELSE 2 END ";
						$query .= "  , A.category, A.weight, C.idxnum";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
						if (! $rs = $db->query($query)) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						$i=0;
						if($rs && $rs->num_rows > 0){ ?>
							<h2>伝票未出力データ</h2>
							<a href="javascript:MClickBtn('output_btn')" class="btn-circle-border-simple-sagawa">伝票出力</a>
							<input type="submit" name="output_btn" value="伝票データ出力" style="display:none;">
							<table class="tbh" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
									<tr><td class="category"><strong>■◇■販売データ■◇■</strong></td></tr>
							</table>
							<table class="tbd" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル" id= "TBL">
								<tr>
									<th class="tbd_th_c" ><label>全選択<br><input type="checkbox" class="list" id="checkAll" name="checkAll" onchange="Javascript:ChangeColor()"></label></th>
									<th class="tbd_th_c" >発送NO.</th>
									<th class="tbd_th_c" ></th>
									<th class="tbd_th_c" >名前</th>
									<th class="tbd_th_c" >コース</th>
									<th class="tbd_th_c" >量</th>
									<th class="tbd_th_c" >金額</th>
									<th class="tbd_th_c" title="全文">備考※</th>
								</tr>
							<?
							while ($row = $rs->fetch_array()) {
								++$i;
								//明細設定
								if (($rowcnt % 2) == 0) { ?>
									<tr onclick="Javascript:CheckRow(<? echo $i ?>)">
								<? } else { ?>
									<tr style="background-color:#EDEDED;" onclick="Javascript:CheckRow(<? echo $i ?>)">
								<? }
								$rowcnt = $rowcnt +1; ?>
									<td style="display:none;"><?php echo $row['subsc_idxnum']?></td>
									<!-- 印刷用チェックボックス -->
									<td class="tbd_td_p1_c" >
										<? if($row['output_flg']>0){ ?>
											<input id="box<? echo $i ?>" disabled="disabled" type="checkbox" value="<? echo $row['ship_idxnum'] ?>" name="outputno[]"  readonly="readonly" onchange="Javascript:ChangeColor()">
										<? }else{ ?>
											<input id="box<? echo $i ?>" type="checkbox" value="<? echo $row['ship_idxnum'] ?>" name="outputno[]"  readonly="readonly" onchange="Javascript:ChangeColor()">
										<? } ?>
									</td>
									<!-- インデックス -->
									<td width="70" class="tbd_td_p1_c"><?php echo str_pad($row['ship_idxnum'], 6, "0", STR_PAD_LEFT) ?></td>
									<!-- 初回/最終回チェック -->
									<td class="tbd_td_p1_c"><?php echo $row['remarks2']; ?></td>
									<!-- お名前 -->
									<td class="tbd_td_p1_l" style="vertical-align:middle;">
										<?php echo $row['name'] ?>
									</td>
									<!-- コース -->
									<td class="tbd_td_p1_c"><?php echo $row['category']; ?></td>
									<!-- 量 -->
									<td class="tbd_td_p1_c"><?php echo $row['weight']; ?></td>
									<!-- 金額 -->
									<td class="tbd_td_p1_r"><?php echo number_format($row['tanka'])."円"; ?></td>
									<!-- 備考 -->
									<td class="tbd_td_p1_l" title="<? echo $row['remarks'] ?>"><?php echo mb_substr($row['remarks'],0,10); ?><? if(mb_strlen($row['remarks']) > 9){echo "・・・";} ?></td>
								</tr>
							<? } ?>
							</table>
							<input type="text" name="行数" id="行数" value="<? echo $i ?>" style="display:none">
						<? } ?>
						<!-- 伝票出力済データ -->
						<?php
						$comm->ouputlog("☆★☆処理開始☆★☆ ", $prgid, SYS_LOG_TYPE_INFO);
						// ================================================
						// ■　□　■　□　個別表示　■　□　■　□
						// ================================================
						//----- データ抽出
						$query = "SELECT A.ship_idxnum, A.tanka, A.category, A.weight, A.delivery_date, A.specified_times, A.output_flg, A.slipnumber, A.mail_date, A.mail_flg, C.email";
						$query .= " ,C.name, C.company, C.phonenum1, C.postcd1, C.postcd2, C.address1, C.address2, C.address3, B.remarks, B.subsc_idxnum, A.ship_date, A.receive_date ";
						$query .= " , CASE ";
						$query .= "  WHEN YEAR(B.date_s)=YEAR(A.delivery_date) AND MONTH(B.date_s)=MONTH(A.delivery_date) THEN '初回' ";
						$query .= "  WHEN YEAR(B.date_e)=YEAR(A.delivery_date) AND MONTH(B.date_e)=MONTH(A.delivery_date) THEN '最終回' ";
						$query .= "  ELSE '' END as remarks2 ";
						$query .= " ,CASE ";
						$query .= " WHEN A.output_flg = 3 THEN 'エラー' ";
						$query .= " WHEN A.delflg = 1 AND A.ship_date <> '0000-00-00' THEN '発送後キャンセル' ";
						$query .= " WHEN A.delflg = 1 AND A.ship_date = '0000-00-00' THEN '発送前キャンセル' ";
						$query .= " WHEN A.output_flg = 9 AND A.ship_date = '0000-00-00' THEN '発送準備中' ";
						$query .= " WHEN A.output_flg = 9 AND A.ship_date <> '0000-00-00'AND A.receive_date = '0000-00-00' THEN '配送中' ";
						$query .= " WHEN A.output_flg = 9 AND A.receive_date <> '0000-00-00' THEN '受取完了' ";
						$query .= "  ELSE '?' END as status ";
						$query .= " FROM php_rice_shipment A";
						$query .= " LEFT OUTER JOIN php_rice_subscription B ON A.subsc_idxnum=B.subsc_idxnum ";
						$query .= " LEFT OUTER JOIN php_rice_personal_info C ON B.personal_idxnum=C.idxnum ";
						$query .= " WHERE A.stopflg = 0";
						$query .= " AND A.output_flg > 0";
						$query .= " AND (A.delflg = 0 OR A.slipnumber <> '')";
						$query .= " AND A.delivery_date BETWEEN '".$p_year.$p_month."01' AND LAST_DAY('".$p_year.$p_month."01')";
						$query .= " ORDER BY  ";
						$query .= " CASE ";
						$query .= " WHEN A.delflg = 1 AND A.ship_date <> '0000-00-00' THEN 7 ";
						$query .= " WHEN A.delflg = 1 AND A.ship_date = '0000-00-00' THEN 8 ";
						$query .= " WHEN A.output_flg = 3 THEN 1 ";
						$query .= " WHEN A.output_flg = 9 AND A.ship_date = '0000-00-00' THEN 2 ";
						$query .= " WHEN A.output_flg = 9 AND A.ship_date <> '0000-00-00'AND A.receive_date = '0000-00-00' THEN 3 ";
						$query .= " WHEN A.output_flg = 9 AND A.receive_date <> '0000-00-00' THEN 4 ";
						$query .= " WHEN YEAR(B.date_s)=YEAR(A.delivery_date) AND MONTH(B.date_s)=MONTH(A.delivery_date) THEN 5 ";
						$query .= " WHEN YEAR(B.date_e)=YEAR(A.delivery_date) AND MONTH(B.date_e)=MONTH(A.delivery_date) THEN 6 ";
						$query .= " ELSE 2 END ";
						$query .= " , A.category, A.weight, C.idxnum";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
						if (! $rs = $db->query($query)) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						$rowcnt = 0;
						if($rs && $rs->num_rows > 0){ ?>
							<h2>伝票出力済データ</h2>
							<fieldset>
								<input type="radio" name="表示制御2" id="d-item-3" class="radio-inline__input" style="font-size: 30px;" value="1" onChange="javascript:clickBtn2();" <? if($p_display2==1){echo "checked=\"checked\"";}?>/>
								<label class="radio-inline__label" for="d-item-3"><b>表示</b></label>
								<input type="radio" name="表示制御2" id="d-item-4" class="radio-inline__input" style="font-size: 30px;" value="2" onChange="javascript:clickBtn2();" <? if($p_display2==2){echo "checked=\"checked\"";}?>/>
								<label class="radio-inline__label" for="d-item-4"><b>非表示</b></label>
							</fieldset>
							<p id="p2">
								<table class="tbd" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
									<tr>
										<th class="tbd_th_c" >状況</th>
										<th class="tbd_th_c" >出荷日</th>
										<th class="tbd_th_c" >発送NO.</th>
										<th class="tbd_th_c" ></th>
										<th class="tbd_th_c" >名前</th>
										<th class="tbd_th_c" >コース</th>
										<th class="tbd_th_c" >量</th>
										<th class="tbd_th_c" >金額</th>
										<th class="tbd_th_c" title="全文">備考※</th>
										<th class="tbd_th_c" >出荷連絡</th>
									</tr>
								<?
								while ($row = $rs->fetch_array()) {
									//明細設定
									if($row['status'] == "エラー") { ?>
										<tr style="background-color:#ff6347;">
									<? }else if($row['status'] == "発送準備中" || $row['status'] == "配送中") { ?>
										<tr style="background-color:yellow;">
									<? }else if (($rowcnt % 2) == 0) { ?>
										<tr>
									<? } else { ?>
										<tr style="background-color:#EDEDED;">
									<? }
									$rowcnt = $rowcnt +1; ?>
										<!-- ステータス -->
										<td class="tbd_td_p1_c">
											<?php echo $row['status']; ?>
											<? if($row['status'] == "配送中"){ ?>
												<br>(<a href="https://k2k.sagawa-exp.co.jp/p/web/okurijosearch.do?okurijoNo=<?= $row['slipnumber'] ?>" target="_blank"><?= $row['slipnumber'] ?></a>)
											<? }else if($row['status'] == "受取完了"){
												echo "<br>(".date('Y/n/j', strtotime($row['receive_date'])).")";
											} ?>
										</td>
										<!-- 出荷日 -->
										<td class="tbd_td_p1_c"><?php if($row['ship_date'] == "0000-00-00 00:00:00"){echo "-";}else{echo date('Y/n/j', strtotime($row['ship_date']));} ?></td>
										<!-- インデックス -->
										<td width="70" class="tbd_td_p1_c"><?php echo str_pad($row['ship_idxnum'], 6, "0", STR_PAD_LEFT) ?></td>
										<!-- 初回/最終回チェック -->
										<td class="tbd_td_p1_c"><?php echo $row['remarks2']; ?></td>
										<!-- お名前 -->
										<td class="tbd_td_p1_l" style="vertical-align:middle;"><?php echo $row['name'] ?></td>
										<!-- コース -->
										<td class="tbd_td_p1_c"><?php echo $row['category']; ?></td>
										<!-- 量 -->
										<td class="tbd_td_p1_c"><?php echo $row['weight']; ?></td>
										<!-- 金額 -->
										<td class="tbd_td_p1_r"><?php echo number_format($row['tanka'])."円"; ?></td>
										<!-- 備考 -->
										<td class="tbd_td_p1_l" title="<? echo $row['remarks'] ?>"><?php echo mb_substr($row['remarks'],0,6); ?><? if(mb_strlen($row['remarks']) > 5){echo "...";} ?></td>
										<!-- 出荷メール -->
										<td class="tbd_td_p1_c">
											<? if($row['mail_flg'] == 1){
												echo "送信済<br>(".date('y/n/j H:i:s', strtotime($row['mail_date'])).")";
											}else if(($row['status'] == "配送中" || $row['status'] == "発送準備中") && $row['slipnumber'] <> "" && $row['email'] <> ""){ ?>
												<a href="Javascript:Push_Send(<? echo $row['ship_idxnum'] ?>)" class="btn-flat-border">ﾒｰﾙ送信</a>
											<? } ?>
										</td>
									</tr>
								<? } ?>
								</table>
							</p>
							<!-- ここまで -->
						<? } ?>
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
