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

	$g_factory = "本部";
	//エクセル出力処理
	$outputno = $_POST['outputno'];
	// 佐川伝票出力
	if(isset($_POST['output_btn']) && $outputno != "") {
		$comm->ouputlog("Excel出力 実行", $prgid, SYS_LOG_TYPE_INFO);
		$reader = PHPExcel_IOFactory::createReader("Excel2007");
		$book = $reader->load("./rice_sagawa_template.xlsx");
		$sheet = $book->getSheetByName("佐川");
		$chksheet = $book->getSheetByName("リスト");
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
		$i = 1;
		$j = 3; // 開始行
		$t = 0;
		$g_category = "";
		$g_cash = 0;
		$g_buynum = 0;
		//出力対象のデータを取得
		$query = "SELECT A.ship_idxnum, A.tanka, A.category, A.weight, A.delivery_date, A.specified_times";
		$query .= " ,C.name, C.company, C.phonenum1, C.postcd1, C.postcd2, C.address1, C.address2, C.address3, C.p_way, B.remarks ";
		$query .= " FROM php_rice_shipment A";
		$query .= " LEFT OUTER JOIN php_rice_subscription B ON A.subsc_idxnum=B.subsc_idxnum ";
		$query .= " LEFT OUTER JOIN php_rice_personal_info C ON B.personal_idxnum=C.idxnum ";
		$query .= " WHERE A.output_flg = 3 AND A.stopflg = 0";
		$query .= " ORDER BY A.category, A.weight, C.postcd1, C.postcd2";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while($row = $rs->fetch_array()){
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
			$sheet->setCellValueByColumnAndRow(23, $i, "お米");
			$sheet->setCellValueByColumnAndRow(24, $i, "精米倶楽部");
			$sheet->setCellValueByColumnAndRow(25, $i, "お米の定期便");
			$sheet->setCellValueByColumnAndRow(26, $i, $row['category']);
			$sheet->setCellValueByColumnAndRow(27, $i, $row['weight']."kg");
			$sheet->setCellValueByColumnAndRow(64, $i, "RC");
			$sheet->setCellValueByColumnAndRow(65, $i ,"お米");
			$sheet->setCellValueByColumnAndRow(66, $i, "購入方法");
			$sheet->setCellValueByColumnAndRow(65, $i, $row['ship_idxnum']);
			// 時間指定があれば該当する時間帯指定サービスを選択（指定なしの場合は天地無用）・項目記入
			$sheet->setCellValueByColumnAndRow(44, $i, $row['delivery_date']);
			$sheet->setCellValueByColumnAndRow(45, $i, $row['specified_times']);
			$sheet->setCellValueByColumnAndRow(47, $i, $row['tanka']);
			$p_tax = $row['tanka'] * 0.08;
			$sheet->setCellValueByColumnAndRow(48, $i, $p_tax);
			// 指定シール設定
			$sheet->setCellValueExplicitByColumnAndRow(51, $i, "010"); // eコレクト(全て可能)
			$sheet->setCellValueExplicitByColumnAndRow(52, $i, "011"); // 取扱注意
			
			//チェックリストにデータ格納
			$chksheet->setCellValueByColumnAndRow(0, $j, "□");
			$chksheet->setCellValueByColumnAndRow(1, $j, $t);
			$chksheet->setCellValueByColumnAndRow(2, $j, $row['name']);
			$chksheet->setCellValueByColumnAndRow(3, $j, $row['category']);
			$chksheet->setCellValueByColumnAndRow(4, $j, $row['weight']);
			$chksheet->setCellValueByColumnAndRow(5, $j, $row['tanka']);
			$chksheet->setCellValueByColumnAndRow(6, $j, date('Y/n/j', strtotime($row['delivery_date'])));
			$chksheet->setCellValueByColumnAndRow(7, $j, $row['remarks']);
			//カテゴリーが変われば改ページ挿入
			if(($g_category <> $row['category'] || $row['weight'] <> $g_weight) && $j > 4){
				$chksheet->setBreak('A'.$j2, PHPExcel_Worksheet::BREAK_ROW);
			}
			$g_category = $row['category'];
			$g_weight = $row['weight'];
		}
		//罫線をつける
		$chksheet->getStyle('A5:H'.$j)->getBorders()->getAllBorders()->setBorderStyle( PHPExcel_Style_Border::BORDER_THIN );
		//印刷範囲を指定
		$chksheet->getPageSetup()->setPrintArea('A1:H'.$j);
		$chksheet->setCellValueByColumnAndRow(0, 1, "【精米倶楽部】".date('Y/n/j')."(".$p_staff."発行)");
		$chksheet->getStyleByColumnAndRow(0, 1)->getFont()->setBold(true);
		//シートを非表示にする
		$sheet->setSheetState(PHPExcel_Worksheet::SHEETSTATE_HIDDEN);
		//リストを作成する
		$j=4;
		asort($category_list);
		asort($weight_list);
		asort($p_date_list);
		foreach($category_list as $val){
			foreach($weight_list[$val] as $val2){
				foreach($p_date_list[$val][$val2] as $val3){
					$chksheet->setCellValueByColumnAndRow(15, $j, $val);
					$chksheet->setCellValueByColumnAndRow(16, $j, $val3);
					$chksheet->setCellValueByColumnAndRow(17, $j, date('Y/n/j',strtotime($val3)));
					$chksheet->setCellValueByColumnAndRow(18, $j, $sumnum_list[$val][$val2][$val3]);
					//セルに色をつける
					$chksheet->getStyle('P5:S'.$j)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("ffff00");
					//罫線をつける
					$chksheet->getStyle('P5:S'.$j)->getBorders()->getAllBorders()->setBorderStyle( PHPExcel_Style_Border::BORDER_THIN );
					++$j;
				}
			}
		}
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
		$book->setActiveSheetIndex(0);
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
		//対象月変更
		function Mclk_onChange(kbn){
			document.forms['frm'].action = './<? echo $prgid;?>.php?kbn=' + kbn;
			document.forms['frm'].submit();
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
			<p style="color:red">
				※伝票を発行された方は、必ず以下の3点をおこなってください。<br>
				　①出力したデータの担当者・日付・型番・数を確認した上で予約発送実績登録<br>
				　②佐川伝票データの取込<a href="./pdf/yamato_manual_ns.pdf" target="_blank">（マニュアル）</a>　<a href="./sagawa_upload.php" target="_blank">取込画面</a><br>
			</p>
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
					<h2>受注詳細</h2>
					<a href="javascript:MClickBtn('output_btn')" class="btn-circle-border-simple-sagawa">伝票出力</a>
					<input type="submit" name="output_btn" value="伝票データ出力" style="display:none;">
					<table class="tbh" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
							<tr><td class="category"><strong>■◇■販売データ■◇■</strong></td></tr>
					</table>
					<table class="tbd" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル" id= "TBL">
						<tr>
							<th class="tbd_th_c" ><label>全選択<br><input type="checkbox" class="list" id="checkAll" name="checkAll" onchange="Javascript:ChangeColor()"></label></th>
							<th class="tbd_th_c" >受付NO.</th>
							<th class="tbd_th_c" >名前</th>
							<th class="tbd_th_c" >コース</th>
							<th class="tbd_th_c" >量</th>
							<th class="tbd_th_c" >金額</th>
							<th class="tbd_th_c" title="全文">備考※</th>
						</tr>
						<!-- 個別表示 -->
						<?php
						$comm->ouputlog("☆★☆処理開始☆★☆ ", $prgid, SYS_LOG_TYPE_INFO);
						// ================================================
						// ■　□　■　□　個別表示　■　□　■　□
						// ================================================
						//----- データ抽出
						$query = "SELECT A.ship_idxnum, A.tanka, A.category, A.weight, A.delivery_date, A.specified_times, A.output_flg";
						$query .= " ,C.name, C.company, C.phonenum1, C.postcd1, C.postcd2, C.address1, C.address2, C.address3, B.remarks, B.subsc_idxnum ";
						$query .= " FROM php_rice_shipment A";
						$query .= " LEFT OUTER JOIN php_rice_subscription B ON A.subsc_idxnum=B.subsc_idxnum ";
						$query .= " LEFT OUTER JOIN php_rice_personal_info C ON B.personal_idxnum=C.idxnum ";
						$query .= " WHERE A.stopflg = 0";
						$query .= " AND A.output_flg = 0";
						$query .= " AND A.delivery_date BETWEEN '".$p_year.$p_month."01' AND LAST_DAY('".$p_year.$p_month."01')";
						$query .= " ORDER BY A.output_flg, A.category, A.weight, C.idxnum";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
						if (! $rs = $db->query($query)) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						$i=0;
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
								<td width="70" class="tbd_td_p1_l"><?php echo str_pad($row['ship_idxnum'], 6, "0", STR_PAD_LEFT) ?></td>
								</td>
								<!-- お名前 -->
								<td style="vertical-align:middle;">
									<?php echo $row['name'] ?>
								</td>
								<!-- コース -->
								<td class="tbd_td_p1_l"><?php echo $row['category']; ?></td>
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
