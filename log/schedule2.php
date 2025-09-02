<?php
//==================================================================================================
// ■機能概要
//   ・開催会場検索画面
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

	//実行プログラム名取得
	$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
	$prgname = "全体スケジュール";
	$prgmemo = "　開催会場の検索をすることが可能です。。";
	$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);

	// ================================================
	// ■　□　■　□　引数取得　■　□　■　□
	// ================================================
	foreach($_POST as $key=>$val){
		$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_DBUG);
	}
	//担当者
	$p_staff = $_COOKIE['con_perf_staff'];
	//会社コード
	$p_compcd = $_COOKIE['con_perf_compcd'];
	//JENESIS担当フラグ
	$jenesisflg = 0;
	if($p_compcd == "T"){
		$jenesisflg = 1;
	}
	//編集フラグ
	$editflg = 0;
	if($_GET['edit'] == 1){
		$editflg = 1;
	}else if($_GET['edit'] == 2){
		$editflg = 2;
	}
	//**************************
	// アップデート
	//**************************
	if(isset($_POST['upd'])){
		//パラメータ
		$g_post=$_POST;
		foreach($g_post as $key=>$val) {
			$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_DBUG);
		}
		$today = date('YmdHis');
		//テーブル
		$table1 = "php_schedule_info";
		//テーブル項目取得
		$collist = $dba->mysql_get_collist($db, $table1);
		//**************************
		// SQL準備
		//**************************
		//UPDATE文
		$update1 =  "UPDATE " . $table1 . " SET ";
		$update2 =  $collist["更新日時"] . " = " . sprintf("'%s'", $today);
		$update2 .= "," . $collist["更新担当者"] . " = " . sprintf("'%s'", $p_staff);
		$update3 =  "," . $collist["休日種類１"] . " = ";
		$update4 =  "," . $collist["休日種類２"] . " = ";
		$update5 =  "," . $collist["出勤タイプ"] . " = ";
		$update6 =  " WHERE " . $collist["インデックス"] . " = ";
		//INSERT文
		$insert1 =  "INSERT INTO " . $table1;
		$insert1 .= " (" . $collist["登録日時"]   . " ," . $collist["更新日時"];
		$insert1 .= " ," . $collist["更新担当者"] . " ," . $collist["名前"];
		$insert1 .= " ," . $collist["日付"]       . " ," . $collist["休日種類１"];
		$insert1 .= " ," . $collist["休日種類２"] . " ," . $collist["出勤タイプ"].")";
		$insert1 .= " VALUE ('$today' ,'$today' ,'$p_staff' ";
		$comm->ouputlog("===登録処理開始===", $prgid, SYS_LOG_TYPE_DBUG);
		// エラーフラグ定義
		$error_flg = 0;
		$errror_message = "";
		//登録処理
		//行単位ループ
		for ($i=1; $i<=$g_post["行数"]; $i++) {
			if (isset($g_post["担当者".$i])) {
				//列単位ループ
				for ($j=1; $j<=$g_post["列数"]; $j++) {
					//変更がある場合のみ対象とする
					if ($g_post["select_type".$i."_".$j] != $g_post["t_select_type".$i."_".$j]) { 
						//「:」で分割を行う
						$select_type = explode(":", $g_post["select_type".$i."_".$j]);
						//インデックスが存在する場合：更新、存在しない場合：新規とする
						if ($g_post["インデックス".$i."_".$j] > 0) {
							/////////////////////////////////////////////////////
							// 更新処理
							/////////////////////////////////////////////////////
							$comm->ouputlog("===更新処理開始===", $prgid, SYS_LOG_TYPE_DBUG);
							//テーブル更新
							$query = "";
							$query .= $update1;
							$query .= $update2; //更新日時・担当者
							$query .= $update3 . sprintf("'%s'", $select_type[0]); //休日種類１
							$query .= $update4 . sprintf("'%s'", $select_type[1]); //休日種類２
							$query .= $update5 . sprintf("'%s'", $select_type[2]); //出勤タイプ
							//条件
							$query .= $update6 . sprintf("'%s'", $g_post["インデックス" .$i."_".$j]); //会場No
							$query .= ";"; //END
						} else {
							/////////////////////////////////////////////////////
							// 新規処理
							/////////////////////////////////////////////////////
							$comm->ouputlog("===新規処理開始===", $prgid, SYS_LOG_TYPE_DBUG);
							$query = "
							SELECT 
								* 
							FROM 
								php_schedule_info 
							WHERE 
								vacation_day = ". sprintf("'%s'", $g_post["日付" .$i."_".$j])."
							AND 
								s_staff = ". sprintf("'%s'", $g_post["担当者" . $i]);
								$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
								$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
							if (!($rs = $db->query($query))) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
							$count = 0;
							while ($row = $rs->fetch_array()) {
								$count++;
							}
							if ($count == 0) {
								$query = "";
								$query = $insert1;
								$query .= " ,'" . $g_post["担当者" . $i] . "' ,'" . $g_post["日付" .$i."_".$j] . "'";
								$query .= " ,'" . $select_type[0]        . "' ,'" . $select_type[1]      . "' ,'" . $select_type[2]      . "')";
							} else if ($count > 0) {
								$error_flg++;
								if ($error_flg == 1) {
									$error_message.= date('Y年m月d日',  strtotime($g_post["日付" .$i."_".$j])).": ".$g_post["担当者" . $i];
								} else if ($error_flg > 1) {
									$error_message.= "</br>".date('Y年m月d日',  strtotime($g_post["日付" .$i."_".$j])).": ".$g_post["担当者" . $i];
								}
							}
						}
						$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
						$comm->ouputlog("===データ追加・更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
						//データ追加実行
						if (! $db->query($query)) {
							$comm->ouputlog("☆★☆データ追加・更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						$comm->ouputlog("===処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
					}
				}
			}
		}
		if ($error_flg > 0) {
			?>
			<script type="text/javascript">
				localStorage.setItem('schedule_upd_error', '<?php echo $error_message?>');
			</script>
		<?
		} else {
		?>
			<script type="text/javascript">
				localStorage.setItem('schedule_upd_error', 'true');
			</script>
		<?
		}
	}

	//対象週
	if(isset($_POST['target_week'])){
		$target_week = $_POST['target_week'];
		//区分の指定がある場合
		if(isset($_GET['kbn'])){
			//次の週へ進む
			if($_GET['kbn'] == 1){
				//対象週取得
				$query = "";
				$query .= "SELECT DISTINCT s_week ";
				$query .= " FROM php_calendar A ";
				$query .= " WHERE A.s_week > " . sprintf("'%s'", $target_week);
				$query .= " ORDER BY week";
				$query .= " LIMIT 1";
				if (! $rs = $db->query($query)) {
					$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				while ($row = $rs->fetch_array()) {
					$target_week = $row['s_week'];
				}
			//前の週へ戻る
			} else if($_GET['kbn'] == 0){
				//対象週取得
				$query = "";
				$query .= "SELECT DISTINCT s_week ";
				$query .= " FROM php_calendar A ";
				$query .= " WHERE A.s_week < " . sprintf("'%s'", $target_week);
				$query .= " ORDER BY week desc";
				$query .= " LIMIT 1";
				if (! $rs = $db->query($query)) {
					$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				while ($row = $rs->fetch_array()) {
					$target_week = $row['s_week'];
				}
			}
		}
	} else {
		//対象週取得
		$query = "";
		$query .= "SELECT DISTINCT s_week ";
		$query .= " FROM php_calendar A ";
		$query .= " WHERE A.date = " . sprintf("'%s'", date('Y-m-d'));
		$query .= " ORDER BY s_week";
		$query .= " LIMIT 1";
		if (! $rs = $db->query($query)) {
			$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		$t_week_s = "";
		$t_week_e = "";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		while ($row = $rs->fetch_array()) {
			$target_week = $row['s_week'];
		}
	}
	//対象週取得
	$query = "";
	$query .= "SELECT DISTINCT s_week ";
	$query .= " FROM php_calendar A ";
	$query .= " WHERE A.s_week >= " . sprintf("'%s'", $target_week);
	$query .= " ORDER BY s_week";
	if ($editflg == 0){
		$query .= " LIMIT 4";
	} elseif ($editflg > 0) {
		$query .= " LIMIT 2";
	}
	if (! $rs = $db->query($query)) {
		$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	$t_week_s = "";
	$t_week_e = "";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	while ($row = $rs->fetch_array()) {
		if ($t_week_s == "") {
			 $t_week_s = $row['s_week'];
		}
		$t_week_e = $row['s_week'];
	}
	//日直実施回数取得（2022/3/1～）
	$query = "";
	$query .= "SELECT s_staff, COUNT(*) as cnt  ";
	$query .= " FROM php_schedule_info A ";
	$query .= " WHERE A.vacation_day >= '2022-03-01' ";
	$query .= " AND A.attendance_type = " . sprintf("'%s'", "日");
	$query .= " GROUP BY s_staff";
	if (! $rs = $db->query($query)) {
		$this->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	while ($row = $rs->fetch_array()) {
		$duty_num[$row['s_staff']] = $row['cnt'];
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
	h2{
		margin: 0px;
		padding: 0px;
	}

	/*コンテナー（HPを囲むブロック）
	---------------------------------------------------------------------------*/
	#container {
		text-align: left;
		width: 1000px;	/*コンテナー幅*/
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

	th {
	position: sticky;
	top: 0;
	}
	/* --- ヘッダーセル（th） --- */
	th.tbd_th_p1 {
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #2B8225; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	width: 400px;
	}
	th.tbd_th_p2 {
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #2B8225; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	}
	th.tbd_th_p3 {
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: blue;
	background-color: #2B8225; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	}
	th.tbd_th_p4 {
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: red;
	background-color: #2B8225; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	}


	/* --- データセル（td） --- */
	td.tbd_td_p3_l {
	width: 550px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p4_l {
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p4_c {
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

	/* --- マーク --- */
	a.vac1 {
	    width:30px;
	    height: 30px;
	    font-size:20px;
	    font-weight:bold;
	    text-decoration:none;
	    display:block;
	    text-align:center;
	    color:#fff;
	    background-color:#49a9d4;
	    border-radius:5px;
	}
	a.bt-01{  /* 出張関連 */
	  background: #FFB53C;
	  font-weight: bold;
	}
	a.bt-02{  /* 休暇関連 */
	  background: #DD4B39;
	}
	a.bt-03{  /* 出張関連 */
	  background: #3C7DD1;
	  font-weight: bold;
	}
	a.bt-04{  /* 在宅・半休関連 */
	  background: #463cd1;
	  font-weight: bold;
	}
	a.bt-05{  /* リモート担当 */
	  background: #FFA500;
	  font-weight: bold;
	}
	a.bt-06{  /* 休暇(週休3日)関連 */
	  background: #dd39b9;
	}
	a.bt-07{  /* 派遣　再生 */
	  background: #008080;
	  font-weight: bold;
	}

	a.vac2 {
	    width:30px;
	    height: 15px;
	    font-size:20px;
	    font-weight:bold;
	    text-decoration:none;
	    display:block;
	    text-align:center;
	    color:#fff;
	    background-color:#49a9d4;
	    border-radius:5px;
	}
	a.bt-01{  /* 休 */
	  background: #DD4B39;
	}

	/* checkbox */
	input[type=checkbox] {
	    width: 20px;
	    height: 20px;
	    vertical-align: middle;
	}

	select{
		-webkit-appearance: none;
		-moz-appearance: none;
		appearance: none;
		border: 3px solid #b0c4de;
		font-size: 17px;
		text-align: center;
	}
	optgroup.vacation {
	     background:#fdfbe7;
	     }
	optgroup.trip {
	     background:#eee;
	     }
	optgroup.other {
	     background:#eeeee1;
	     }
	     
	     
	     .status_gym{
		-webkit-appearance: none;
		-moz-appearance: none;
		appearance: none;
		border: 3px solid #b0c4de;
		font-size: 17px;
		text-align: center;
		width:60px;
	     
	     }

	</style>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
	<script src=" https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js "></script>
	<link href=" https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css " rel="stylesheet">
	<script type="text/javascript">
		<!--
		function hpbmapinit() {
			hpbmaponload();
		}
		//-->
	</script>
	<script type="text/javascript">
		$(
			function() {
				let result = localStorage.getItem('schedule_upd_error');
				if (result != null) {
					if (result == 'true') {
						Swal.fire
						(
							{
								icon: 'success',
								title: '登録完了しました。'
							}
						);
					} else if (result != 'true') {
						Swal.fire
						(
							{
								icon: 'error',
								title: '登録エラー', 
								html: '以下のスケジュールは既に登録済の可能性があります。</br>' + result
							}
						);
					}
					localStorage.removeItem('schedule_upd_error');
				}
			}
		);
		function Search(kbn){
			//画面項目設定
			var cell1 = '&kbn='+kbn;
			document.forms['frm'].action = './<? echo $prgid;?>.php?edit=<? echo $editflg?>' + cell1;
			document.forms['frm'].submit();
		}
		function display_choice(){
			let cnt = document.getElementsByName('行数')[0].value;
			for (let i=1; i<=cnt; i++) {
				if(document.forms['frm'].elements['check'+i].checked == false){
					document.getElementById('choice'+i).style.display = 'none';
				}
			}
		}
		function display_all(){
			let cnt = document.getElementsByName('行数')[0].value;
			for (let i=1; i<=cnt; i++) {
				document.getElementById('choice'+i).style.display = '';
			}
		}
		function chk_team(ischecked, team){
			let cnt = document.getElementsByName('行数')[0].value;
			//
			for (let i=1; i<=cnt; i++) {
				//指定チームのメンバーの場合
				if (document.getElementsByName('team'+i)[0].value == team) {
					document.forms['frm'].elements['check'+i].checked = ischecked;
				}
			}
		}
		function change_select_type(row,col){
			//同じ日に2人以上の日直がいたらアラート
		/*	if(document.getElementsByName('select_type'+row+'_'+col)[0].value == "::日"){
				let cnt = document.getElementsByName('行数')[0].value;
				for(i=2; i<cnt; ++i){
					if(document.getElementsByName('teamflg'+i)[0].value == 0 && row != i && document.getElementsByName('select_type'+i+'_'+col)[0].value == "::日"){
						alert('同じ日に２人以上日直が設定されています');
					}
				}
			}
		*/	if (document.getElementsByName('select_type'+row+'_'+col)[0].value != document.getElementsByName('t_select_type'+row+'_'+col)[0].value) {
				//背景色変更
				document.getElementById("td"+row+'_'+col).style.backgroundColor="red";
			} else {
				//背景色変更
				document.getElementById("td"+row+'_'+col).style.backgroundColor=document.getElementById("td"+row+'_0').style.backgroundColor;
			}
		}
		function Check_checkbox(row, col){
			if(document.getElementsByName('select_type'+row+'_'+col)[0].value == "::リ"){
				document.getElementsByName('select_type'+row+'_'+col)[0].value = "::"
				document.getElementsByName('select_type_button'+row+'_'+col)[0].value = "　";
			}else if(document.getElementsByName('select_type'+row+'_'+col)[0].value == "::"){
				document.getElementsByName('select_type'+row+'_'+col)[0].value = "::リ";
				document.getElementsByName('select_type_button'+row+'_'+col)[0].value = "リ";
			}
			change_select_type(row,col);
		}
	</script>
	<?php $html->output_htmlheadinfo3($prgname); ?>

</head>

<body>
<div id="container">
	<div id="contents">
		<div id="main">
			<!--ヘッダー-->
			<div class="header">
				<h1>[ 全体スケジュール ]</h1>
				<div>休 : 定期休暇　　/　　申 : 有給休暇　　/　　在 : 在宅勤務　　/　　AM : 午前中移動　　/　　PM : 午後移動</div>
				<div style="text-align:left;">
					<input type="button" name="back" value="←前の週" onclick="Javascript:Search('0')" class="btn_d" >
					<input type="button" name="next" value="次の週→" onclick="Javascript:Search('1')" class="btn_e" >
				</div>
			</div>
			<form name="frm" action="<? echo $prgid ?>.php?edit=<? echo $editflg ?>" method="post">
			<input type="text" name="target_week" value="<? echo $target_week ?>" style="display: none;">
			<table>
				<tr>
				<?php 
					// ================================================
					// ■　□　■　□　全体表示　■　□　■　□
					// ================================================
					//----- データ抽出
					$query = "";
					//----- ----- 　集計　 ----- -----
					$query = $query."  SELECT ";
					$query = $query."  0 as staffno";
					$query = $query."  ,AAA.date";
					$query = $query."  ,DATE_FORMAT(AAA.date,'%m') as month ";
					$query = $query."  ,DATE_FORMAT(AAA.date,'%d') as day ";
					$query = $query."  ,(case DATE_FORMAT(AAA.date, '%w')   ";
					$query = $query."  when 0 then '(日)'   ";
					$query = $query."  when 1 then '(月)'   ";
					$query = $query."  when 2 then '(火)'   ";
					$query = $query."  when 3 then '(水)'   ";
					$query = $query."  when 4 then '(木)'   ";
					$query = $query."  when 5 then '(金)'   ";
					$query = $query."  when 6 then '(土)'   ";
					$query = $query."  else 'x' end) as date_name  ";
					$query = $query."  , '　' as staff";
					$query = $query."  , '' as team";
					$query = $query."  , 0  as team_no";
					$query = $query."  , '' as vacation ";
					$query = $query."  , '' as vacation_type_1 ";
					$query = $query."  , '' as vacation_type_2 ";
					$query = $query."  , '' as department";
					$query = $query."      ,sum(case ";
					$query = $query."        when BBB.vacation_type_1 IS NULL AND kbn = 1 AND within = 0 then  1";
					$query = $query."        else         0 end)";
					$query = $query."     + sum(case ";
					$query = $query."        when BBB.vacation_type_1 = '' AND kbn = 1 AND within = 0 then  1";
					$query = $query."        else         0 end)";
					$query = $query."     + sum(case ";
					$query = $query."        when BBB.vacation_type_1 IS NULL AND kbn = 3 AND within = 0 then  1";
					$query = $query."        else         0 end)";
					$query = $query."     + sum(case ";
					$query = $query."        when BBB.vacation_type_1 = '' AND BBB.vacation_type_2 != '在' AND kbn = 3  AND within = 0 then  1";
					$query = $query."        else         0 end) as zimusyo1";
					$query = $query."      ,sum(case ";
					$query = $query."        when BBB.vacation_type_1 IS NULL AND kbn = 0 AND within = 0 then  1";
					$query = $query."        else         0 end)";
					$query = $query."     + sum(case ";
					$query = $query."        when BBB.vacation_type_1 = '' AND BBB.vacation_type_2 != '在' AND kbn = 0  AND within = 0 then  1";
					$query = $query."        else         0 end) as zimusyo2";
					
					$query = $query."      ,sum(case ";
					$query = $query."        when BBB.vacation_type_1 IS NULL AND kbn = 1 AND AAA.o_team = '再生' AND within = 0 then  1";
					$query = $query."        else         0 end)";
					$query = $query."     + sum(case ";
					$query = $query."        when BBB.vacation_type_1 = '' AND kbn = 1 AND AAA.o_team = '再生' AND within = 0 then  1";
					$query = $query."        else         0 end) ";
					$query = $query."     + sum(case ";
					$query = $query."        when BBB.vacation_type_1 IS NULL AND kbn = 3 AND within = 0 then  1";
					$query = $query."        else         0 end)";
					$query = $query."     + sum(case ";
					$query = $query."        when BBB.vacation_type_1 = '' AND BBB.vacation_type_2 != '在' AND kbn = 3  AND within = 0 then  1";
					$query = $query."        else         0 end) as zimusyo3";

					$query = $query."      ,sum(case ";
					$query = $query."        when BBB.vacation_type_1 IS NULL AND  BBB.attendance_type = 'リ' AND kbn = 1 AND within = 0 then  1";
					$query = $query."        else         0 end)";
					$query = $query."     + sum(case ";
					$query = $query."        when BBB.vacation_type_1 = '' AND  BBB.attendance_type = 'リ' AND kbn = 1 AND within = 0 then  1";
					$query = $query."        else         0 end) as nicchoku";
					
					$query = $query."      ,sum(case when BBB.vacation_type_2 = '在' AND kbn = 1 AND within = 0 then  1";
					$query = $query."        else         0 end) as zitaku";
					$query = $query."  , '' as attendance_type ";
					$query = $query."  , '' as select_type ";
					$query = $query."  , 0 as idxnum ";
					$query = $query."  FROM  ";
					$query = $query."  (  ";
					$query = $query."    SELECT  ";
					$query = $query."    A.date,B.staff,B.kbn";
					$query = $query."   ,(case when A.date >= B.s_date AND A.date <= B.e_date then 0 else 1 end) as within  , B.o_team , B.team ";
					$query = $query."    FROM  ";
					$query = $query."    (  ";
					$query = $query."     SELECT date  ";
					$query = $query."       FROM php_calendar  ";
					$query = $query."       WHERE s_week between  " . sprintf("'%s'", $t_week_s) ;
					$query = $query."                        and  " . sprintf("'%s'", $t_week_e) ;
					$query = $query."    ) as A  ";
					$query = $query."    ,  ";
					$query = $query."    (  ";
					$query = $query."     SELECT a.staff";
					$query = $query."      ,(case a.status";
					$query = $query."        when 1 then  1";
					$query = $query."        when 3 then  3";
					$query = $query."        else         0 end) as kbn";
					$query = $query."      , a.s_date, a.e_date  , b.team, b.o_team ";
					$query = $query."     FROM php_staff_info a ";
					//ジェネシスではない場合
					if ($jenesisflg == 0 ) {
						$query = $query."     LEFT OUTER JOIN php_staff b ";
						$query = $query."       ON a.displayname=b.staff ";
					//ジェネシスの場合（窓口担当者のみ表示する）
					} else {
						$query = $query."     INNER JOIN php_staff b ";
						$query = $query."       ON a.displayname=b.staff ";
						$query = $query."      AND b.jenesisflg = 1";
					}
					$query = $query."     WHERE a.delflg = 0 ";
					$query = $query."    ) B  ";
					$query = $query."  ) AAA  ";
					$query = $query."  left outer join php_schedule_info BBB  ";
					$query = $query."  ON  BBB.vacation_day = AAA.date  ";
					$query = $query."  and BBB.s_staff = AAA.staff  ";
					$query = $query."  and BBB.delflg = 0 ";
					$query = $query." group by month,date";
					//通常表示のみ表示する
					if ($editflg == 0){
						//----- ----- 自分自身 ----- -----
						$query = $query."  UNION ALL ";
						$query = $query."  SELECT AA.staffno ";
						$query = $query."  ,AA.date";
						$query = $query."  ,DATE_FORMAT(AA.date,'%m') as month ";
						$query = $query."  ,DATE_FORMAT(AA.date,'%d') as day ";
						$query = $query."  ,(case DATE_FORMAT(AA.date, '%w')   ";
						$query = $query."  when 0 then '(日)'   ";
						$query = $query."  when 1 then '(月)'   ";
						$query = $query."  when 2 then '(火)'   ";
						$query = $query."  when 3 then '(水)'   ";
						$query = $query."  when 4 then '(木)'   ";
						$query = $query."  when 5 then '(金)'   ";
						$query = $query."  when 6 then '(土)'   ";
						$query = $query."  else 'x' end) as date_name  ";
						$query = $query."  , AA.staff";
						$query = $query."  , AA.team";
						$query = $query."  , AA.team_no";
						$query = $query."  , AA.vacation ";
						$query = $query."  , ifnull(BB.vacation_type_1, '　') as vacation_type_1 ";
						$query = $query."  , ifnull(BB.vacation_type_2, '　') as vacation_type_2 ";
						$query = $query."  , AA.department";
						$query = $query."  , 0 as zimusyo1 ";
						$query = $query."  , 0 as zimusyo2 ";
						$query = $query."  , 0 as zimusyo3 ";
						$query = $query."  , 0 as nicchoku ";
						$query = $query."  , 0 as zitaku ";
						$query = $query."  , ifnull(BB.attendance_type, '　') as attendance_type ";
						$query = $query."  , '' as select_type ";
						$query = $query."  , 0 as idxnum ";
						$query = $query."  FROM  ";
						$query = $query."  (  ";
						$query = $query."    SELECT  ";
						$query = $query."    A.date,B.staff,B.displayname,B.team, B.vacation, B.department, B.staffno ";
						$query = $query."  ,(case B.team ";
						$query = $query."  when 'プレゼン' then  0 ";
						$query = $query."  else 1 end) as team_no ";
						$query = $query."    FROM  ";
						$query = $query."    (  ";
						$query = $query."     SELECT date  ";
						$query = $query."       FROM php_calendar  ";
						$query = $query."       WHERE s_week between  " . sprintf("'%s'", $t_week_s) ;
						$query = $query."                        and  " . sprintf("'%s'", $t_week_e) ;
						$query = $query."    ) as A  ";
						$query = $query."    ,  ";
						$query = $query."    (  ";
						$query = $query."     SELECT a.staff,a.displayname,IFNULL(b.team, 'その他') as team, a.vacation, a.department  ,1 as staffno";
						$query = $query."     FROM php_staff_info a ";
						$query = $query."     LEFT OUTER JOIN php_staff b ";
						$query = $query."       ON a.displayname=b.staff ";
						$query = $query."     WHERE a.delflg = 0 ";
						$query = $query."       AND a.displayname = " . sprintf("'%s'", $p_staff);
						$query = $query."    ) B  ";
						$query = $query."  ) AA  ";
						$query = $query."  left outer join php_schedule_info BB  ";
						$query = $query."  ON  BB.vacation_day = AA.date  ";
						$query = $query."  and BB.s_staff = AA.staff  ";
						$query = $query."  and BB.delflg = 0 ";
					}
					//----- ----- スタッフ ----- -----
					$query = $query."  UNION ALL ";
					$query = $query."  SELECT AA.staffno ";
					$query = $query."  ,AA.date";
					$query = $query."  ,DATE_FORMAT(AA.date,'%m') as month ";
					$query = $query."  ,DATE_FORMAT(AA.date,'%d') as day ";
					$query = $query."  ,(case DATE_FORMAT(AA.date, '%w')   ";
					$query = $query."  when 0 then '(日)'   ";
					$query = $query."  when 1 then '(月)'   ";
					$query = $query."  when 2 then '(火)'   ";
					$query = $query."  when 3 then '(水)'   ";
					$query = $query."  when 4 then '(木)'   ";
					$query = $query."  when 5 then '(金)'   ";
					$query = $query."  when 6 then '(土)'   ";
					$query = $query."  else 'x' end) as date_name  ";
					$query = $query."  , AA.staff";
					$query = $query."  , AA.team";
					$query = $query."  , AA.team_no";
					$query = $query."  , AA.vacation ";
					$query = $query."  , case when AA.within = 0 then ifnull(BB.vacation_type_1, '　') else '－' end as vacation_type_1 ";
					$query = $query."  , ifnull(BB.vacation_type_2, '　') as vacation_type_2 ";
					$query = $query."  , AA.department";
					$query = $query."  , 0 as zimusyo1 ";
					$query = $query."  , 0 as zimusyo2 ";
					$query = $query."  , 0 as zimusyo3 ";
					$query = $query."  , 0 as nicchoku ";
					$query = $query."  , 0 as zitaku ";
					$query = $query."  , ifnull(BB.attendance_type, '　') as attendance_type ";
					$query = $query."  , ifnull(CONCAT(BB.vacation_type_1 ,':' ,BB.vacation_type_2 ,':' ,BB.attendance_type), '::') as select_type ";
					$query = $query."  , ifnull(BB.s_idxnum, 0) as idxnum ";
					$query = $query."  FROM  ";
					$query = $query."  (  ";
					$query = $query."    SELECT  ";
					$query = $query."    A.date,B.staff,B.displayname,B.team, B.vacation, B.department, B.staffno ";
					$query = $query."  ,(case when A.date >= B.s_date AND A.date <= B.e_date then 0 else 1 end) as within ";
					$query = $query."  ,(case B.team ";
					$query = $query."  when 'プレゼン' then  0 ";
					$query = $query."  else 1 end) as team_no ";
					$query = $query."    FROM  ";
					$query = $query."    (  ";
					$query = $query."     SELECT date  ";
					$query = $query."       FROM php_calendar  ";
					$query = $query."       WHERE s_week between  " . sprintf("'%s'", $t_week_s) ;
					$query = $query."                        and  " . sprintf("'%s'", $t_week_e) ;
					$query = $query."    ) as A  ";
					$query = $query."    ,  ";
					$query = $query."    (  ";
					$query = $query."     SELECT a.staff,a.displayname ";
					$query = $query."            ,(case a.status   ";
					$query = $query."            when 1 then IFNULL(b.team, 'その他')   ";
					$query = $query."            when 3 then 'アルバイト(リペア)'   ";
					$query = $query."            when 4 then 'アルバイト(電話注文)'   ";
					$query = $query."            when 5 then '経営者'   ";
					$query = $query."            when 6 then '派遣'   ";
					$query = $query."            when 7 then '派遣(ラン)'   ";
					$query = $query."            when 9 then '派遣(ＨＲ)'   ";
					$query = $query."            else 'その他' end) as team  ";
					$query = $query."            , a.vacation, a.department  ,10 as staffno ";
					$query = $query."            , a.s_date, a.e_date ";
					$query = $query."     FROM php_staff_info a ";
					//ジェネシスではない場合
					if ($jenesisflg == 0 ) {
						$query = $query."     LEFT OUTER JOIN php_staff b ";
						$query = $query."       ON a.displayname=b.staff ";
					//ジェネシスの場合（窓口担当者のみ表示する）
					} else {
						$query = $query."     INNER JOIN php_staff b ";
						$query = $query."       ON a.displayname=b.staff ";
						$query = $query."      AND b.jenesisflg = 1";
					}
					$query = $query."     WHERE a.delflg = 0 ";
					//職員のみ表示する
					if ($editflg > 0){
						$query = $query."       AND a.status = 1 ";
					}
					$query = $query."    ) B  ";
					$query = $query."  ) AA  ";
					$query = $query."  left outer join php_schedule_info BB  ";
					$query = $query."  ON  BB.vacation_day = AA.date  ";
					$query = $query."  and BB.s_staff = AA.staff  ";
					$query = $query."  and BB.delflg = 0 ";
					$query = $query."  order by 1, 8, 7, 12, 6, 2";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($query))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$team = "";
					$staff = "";
					$month = "";
					$teamcnt=0;
					$rowcnt=0;
					$colcnt=0;
					$daycnt=0;
				?>
				<? 	while ($row = $rs->fetch_array()) { ?>
					<? if ($row['team'] <> $team AND $row['staffno'] >= 10) { ?>
							<? $rowcnt = $rowcnt + 1; ?>
							<tr style="background-color:#CCCCCC;" id="choice<?php echo $rowcnt ?>">
								<td class="tbd_td_p4_l">
								<b>
									<input type="checkbox" name="check<?php echo $rowcnt ?>" onChange="Javasctipt:chk_team(this.checked, '<?php echo $row['team']; ?>')">
									<?php echo $row['team'] ?>
								</b>
								</td>
								<td class="tbd_td_p4_l" colspan="<?php echo $daycnt ?>"></td>
								<td style="display:none">
									<input type="text" name="team<?php echo $rowcnt ?>" value="<?php echo $row['team'] ?>">
									<input type="text" name="teamflg<?php echo $rowcnt ?>" value="1">
								</td>
							</tr>
							<? $teamcnt = 0; ?>
							<? $rowcnt = $rowcnt + 1; ?>
					<? } ?>
					<? $team = $row['team']; ?>
					<!--- 行 --->
					<? if ($row['staff'] <> $staff) { ?>
						<? if ($teamcnt > 0) { ?>
							</tr>
							<? $rowcnt = $rowcnt+1; ?><!--- 全体の行数 --->
						<? } ?>
						<? if (($teamcnt % 2) == 0) { ?>
							<tr id="choice<?php echo $rowcnt ?>">
						<? } else { ?>
							<tr style="background-color:#EDEDED;" id="choice<?php echo $rowcnt ?>">
						<? } ?>
						<!--- 左列の担当者リスト --->
						<? if ($row['staffno'] == 0) {//ヘッダー情報 ?>
							<th class="tbd_th_p1">
								名前<?if ($jenesisflg == 0 ) { if($editflg==0){echo "<br>(有給休暇残数)<br><br>";}else{echo "<br><br>(日直実施回数)<br>";}}?><br>※事:事務所 (職員以外) 、<br>再：再生<br>在:在宅
									<br>
									<input type="button" name="choice" value="絞" onclick="Javascript:display_choice()" class="btn_d" style="width: 50px;" >
									<input type="button" name="choice" value="全" onclick="Javascript:display_all()" class="btn_d"  style="width: 50px;">
									<? if ($editflg > 0){ ?>
									<br><br>
									<input type="submit" name="upd" value="更新" class="btn_b"  style="width: 200px;height: 30px;">
									<? } ?>
							</th>
						<? } else { ?>
							<!--- 編集モード --->
							<? if ($editflg > 0){ ?>
								<td class="tbd_td_p4_l" id="td<?php echo $rowcnt ?>_0">
									　<input type="checkbox" id="<?php echo $row['team']; ?>" name="check<?php echo $rowcnt ?>">
										<?php echo $row['staff'] ?><?if(mb_substr($row['team'],0,2)<>"アル" && mb_substr($row['team'],0,2)<>"派遣"){ echo "（".number_format($duty_num[$row['staff']]). "回）";} ?>
								</td>
							<!--- 参照モード --->
							<? }else{ ?>
								<td class="tbd_td_p4_c" id="td<?php echo $rowcnt ?>_0">
									　<input type="checkbox" id="<?php echo $row['team']; ?>" name="check<?php echo $rowcnt ?>">
										<?php echo $row['staff'] ?><br><?if ($jenesisflg == 0 ) { echo "(".$row['vacation']. "日)";} else { echo "<br>";}?>
								</td>
							<? } ?>
						<? } ?>
						<? $teamcnt = $teamcnt+1; ?><!--- チーム単位のカウント --->
						<? $colcnt = 1; ?><!--- 列数 --->
						<? $staff = $row['staff']; ?>
					<? } ?>
					<!--- ヘッダー情報 --->
					<? if ($row['staffno'] == 0) { ?>
						<th class="tbd_th_p2">
							<?php if ($row['month'] <> $month) { ?>
								<?php echo $row['month'] ?>/
							<?php }  ?>
								<br><?php echo $row['day'] ?><br>
								<?php if ($row['date_name'] == "(土)") { ?>
								<font color="red"><?php echo $row['date_name'] ?></font>
								<?php } else if ($row['date_name'] == "(日)") { ?>
								<font color="blue"><?php echo $row['date_name'] ?></font>
								<?php } else  { ?>
								<?php echo $row['date_name'] ?>
								<?php } ?>
								<hr><font size="2">事:<?php echo $row['zimusyo1'] + $row['zimusyo2'] - $row['zimusyo3'] - $row['zitaku'] ?></font>
								<br><font size="2">　 (<?php echo $row['zimusyo2'] ?>)</font>
								<hr><font size="2">再:<?php echo $row['zimusyo3'] ?></font>
								<br><font size="2">在:<?php echo $row['zitaku'] ?></font>
								<? if($editflg > 0){ ?>
									<hr><font size="2">日:<?php echo $row['nicchoku'] ?></font>
								<? } ?>
						</th>
						<?php
							$daycnt = $daycnt + 1;
						?>
					<!--- 明細情報 --->
					<? } else { ?>
						<!--- 上段 --->
						<? if($editflg == 2){ ?>
							<td class="tbd_td_p4_c" id="td<?php echo $rowcnt ?>_<?php echo $colcnt ?>" onclick="Javasctipt:Check_checkbox('<?php echo $rowcnt ?>','<?php echo $colcnt ?>')">
						<? }else{ ?>
							<td class="tbd_td_p4_c" id="td<?php echo $rowcnt ?>_<?php echo $colcnt ?>">
						<? } ?>
							<!------------------------------------------------------------------------>
							<!--- 参照モード                                                       --->
							<!------------------------------------------------------------------------>
							<? if ($editflg == 0){ ?>
								<!--- 休憩関連 --->
								<? if ($row['vacation_type_1'] == "休" || $row['vacation_type_1'] == "半" ||
									   $row['vacation_type_1'] == "申" || $row['vacation_type_1'] == "忌") { ?>
									<? if ($row['attendance_type'] == "1") { ?>
										<a class="vac1 bt-06"><?php echo $row['vacation_type_1'] ?>
									<? } else { ?>
										<a class="vac1 bt-02"><?php echo $row['vacation_type_1'] ?>
									<? } ?>

								<!--- 外出関連 --->
								<? } else if ($row['vacation_type_1'] == "会" || $row['vacation_type_1'] == "イ" ||
												 $row['vacation_type_1'] == "販" || $row['vacation_type_1'] == "研" ||
												 $row['vacation_type_1'] == "リ" ||
												 $row['vacation_type_1'] == "移" || $row['vacation_type_1'] == "タ") { ?>
									<a class="vac1 bt-03"><?php echo $row['vacation_type_1'] ?>
									<? if ($row['attendance_type'] == "A") { ?>
										<font size="2" color="black">A</font></a>
									<? } ?>
									<? if ($row['attendance_type'] == "1") { ?>
										<font size="2" color="black">1</font></a>
									<? } ?>
									<? if ($row['attendance_type'] == "2") { ?>
										<font size="2" color="black">2</font></a>
									<? } ?>
								<!--- 再生関連 --->
								<? } else if ($row['vacation_type_1'] == "開" || $row['vacation_type_1'] == "整"){ ?>
									<a class="vac1 bt-07"><?php echo $row['vacation_type_1'] ?>
								<!--- 遅刻関連 --->
								<? } else if ($row['vacation_type_1'] == "遅") { ?>
									<a class="vac1 bt-01"><?php echo $row['vacation_type_1'] ?>
								<? } else { ?>
									<!--- 在宅関連 --->
									<? if ($row['vacation_type_2'] == "在") { ?>
										<a class="vac1 bt-04"><?php echo $row['vacation_type_2'] ?>
										<? if ($row['attendance_type'] == "A") { ?>
											<font size="2" color="black"><?php echo $row['attendance_type'] ?></font></a>
										<? } ?>
									<!--- 半休関連 --->
									<? } else if ($row['vacation_type_2'] == "AM" || $row['vacation_type_2'] == "PM") { ?>
										<a class="vac1 bt-04">半
									<? } else { ?>
										<?php echo $row['vacation_type_1'] ?>
									<? } ?>
								<? } ?>
								<? if ($row['vacation_type_2'] <> "在") { ?>
									<font size="2" color="black"><?php echo $row['vacation_type_2'] ?></font></a>
								<? } ?>
								<!--- 出勤パターン：Ａの場合 --->
								<? if ($row['vacation_type_1'] == "" && $row['vacation_type_2'] == "") { ?>
									<? if ($row['attendance_type'] == "A") { ?>
										<a class="vac1 bt-04">Ａ</a>
									<? } elseif ($row['attendance_type'] == "リ") { ?>
										<a class="vac1 bt-05">リ</a>
									<? } elseif ($row['attendance_type'] <> "") { ?>
										<? $time = explode("-", $row['attendance_type']); ?>
										<font size="1" color="B2B2B2">
											<b><?php echo $time[0] ?><br>～<br><?php echo $time[1] ?></b>
										</font>
									<? } ?>
								<? } ?>
							<!------------------------------------------------------------------------>
							<!--- 編集モード                                                       --->
							<!------------------------------------------------------------------------>
							<? } elseif ($editflg == 1) { ?>
								<p>
								<select name="select_type<?php echo $rowcnt ?>_<?php echo $colcnt ?>" onChange="Javasctipt:change_select_type('<?php echo $rowcnt ?>','<?php echo $colcnt ?>')">
									<option value="::" <? if($row['select_type']=="::"){ ?>selected="selected"<? } ?>>　</option>
									<option value=":AM:" <? if($row['select_type']==":PM:"){ ?>selected="selected"<? } ?>>半出</option>
									<optgroup label="休暇関連" class="vacation">
										<option value="休::" <? if($row['select_type']=="休::"){ ?>selected="selected"<? } ?> style="color:red;">休</option>
										<option value="休::1" <? if($row['select_type']=="休::1"){ ?>selected="selected"<? } ?> style="color:red;">休(週3)</option>
										<option value="申::" <? if($row['select_type']=="申::"){ ?>selected="selected"<? } ?> style="color:red;">申</option>
										<option value="半:AM:" <? if($row['select_type']=="半:AM:"){ ?>selected="selected"<? } ?> style="color:red;">午前休</option>
										<option value="半:PM:" <? if($row['select_type']=="半:PM:"){ ?>selected="selected"<? } ?> style="color:red;">午後休</option>
										<option value="忌::" <? if($row['select_type']=="忌::"){ ?>selected="selected"<? } ?> style="color:red;">忌</option>
									</optgroup>
									<optgroup label="出張関連" class="trip">
										<option value="イ::" <? if($row['select_type']=="イ::"){ ?>selected="selected"<? } ?>>イ</option>
										<option value="会:A:" <? if($row['select_type']=="会:A:"){ ?>selected="selected"<? } ?>>会Ａ</option>
										<option value="会:B:" <? if($row['select_type']=="会:B:"){ ?>selected="selected"<? } ?>>会Ｂ</option>
										<option value="会:C:" <? if($row['select_type']=="会:C:"){ ?>selected="selected"<? } ?>>会Ｃ</option>
										<option value="会:D:" <? if($row['select_type']=="会:D:"){ ?>selected="selected"<? } ?>>会Ｄ</option>
										<option value="会:経:" <? if($row['select_type']=="会:経:"){ ?>selected="selected"<? } ?>>会経</option>
										<option value="会::" <? if($row['select_type']=="会::"){ ?>selected="selected"<? } ?>>会</option>
										<option value="販::" <? if($row['select_type']=="販::"){ ?>selected="selected"<? } ?>>販</option>
										<option value="移:AM:" <? if($row['select_type']=="移:AM:"){ ?>selected="selected"<? } ?>>午前移</option>
										<option value="移:PM:" <? if($row['select_type']=="移:PM:"){ ?>selected="selected"<? } ?>>午後移</option>
										<option value="移:PM:A" <? if($row['select_type']=="移:PM:A"){ ?>selected="selected"<? } ?>>午後移A</option>
										<option value="タ::" <? if($row['select_type']=="タ::"){ ?>selected="selected"<? } ?>>タ</option>
									</optgroup>
									<optgroup label="その他" class="other">
										<option value="::リ" <? if($row['select_type']=="::リ"){ ?>selected="selected"<? } ?>>リモート</option>
										<option value="::A" <? if($row['select_type']=="::A"){ ?>selected="selected"<? } ?>>Ａ</option>
										<option value=":在:" <? if($row['select_type']==":在:"){ ?>selected="selected"<? } ?>>在</option>
										<option value=":在:A" <? if($row['select_type']==":在:A"){ ?>selected="selected"<? } ?>>在Ａ</option>
										<option value="遅::" <? if($row['select_type']=="遅::"){ ?>selected="selected"<? } ?>>遅</option>
										<option value="研::" <? if($row['select_type']=="研::"){ ?>selected="selected"<? } ?>>研</option>
										<option value="研::1" <? if($row['select_type']=="研::1"){ ?>selected="selected"<? } ?>>研1</option>
										<option value="研::2" <? if($row['select_type']=="研::2"){ ?>selected="selected"<? } ?>>研2</option>
									</optgroup>
								</select>
								</p>
							<? } elseif ($editflg == 2) { ?>
								<p>
								<? if ($row['vacation_type_1'] == "休" || $row['vacation_type_1'] == "半" ||
									   $row['vacation_type_1'] == "申" || $row['vacation_type_1'] == "忌") { ?>
									<? if ($row['attendance_type'] == "1") { ?>
										<a class="vac1 bt-06"><?php echo $row['vacation_type_1'] ?>
									<? } else { ?>
										<a class="vac1 bt-02"><?php echo $row['vacation_type_1'] ?>
									<? } ?>
								<!--- 外出関連 --->
								<? } else if ($row['vacation_type_1'] == "会" || $row['vacation_type_1'] == "イ" ||
												 $row['vacation_type_1'] == "販" || $row['vacation_type_1'] == "研" ||
												 $row['vacation_type_1'] == "移" || $row['vacation_type_1'] == "タ") { ?>
									<a class="vac1 bt-03"><?php echo $row['vacation_type_1'] ?>
									<? if ($row['attendance_type'] == "A") { ?>
										<font size="2" color="black">A</font></a>
									<? } ?>
									<? if ($row['attendance_type'] == "1") { ?>
										<font size="2" color="black">1</font></a>
									<? } ?>
									<? if ($row['attendance_type'] == "2") { ?>
										<font size="2" color="black">2</font></a>
									<? } ?>
								<!--- 遅刻関連 --->
								<? } else if ($row['vacation_type_1'] == "遅") { ?>
									<a class="vac1 bt-01"><?php echo $row['vacation_type_1'] ?>
								<? } else { ?>
									<!--- 在宅関連 --->
									<? if ($row['vacation_type_2'] == "在") { ?>
										<a class="vac1 bt-04"><?php echo $row['vacation_type_2'] ?>
										<? if ($row['attendance_type'] == "A") { ?>
											<font size="2" color="black"><?php echo $row['attendance_type'] ?></font></a>
										<? } ?>
									<!--- 半休関連 --->
									<? } else if ($row['vacation_type_2'] == "AM" || $row['vacation_type_2'] == "PM") { ?>
										<a class="vac1 bt-04">半
									<? } else { ?>
										<?php echo $row['vacation_type_1'] ?>
									<? } ?>
								<? } ?>
								<? if ($row['vacation_type_2'] <> "在") { ?>
									<font size="2" color="black"><?php echo $row['vacation_type_2'] ?></font></a>
								<? } ?>
								<!--- 出勤パターン：Ａの場合 --->
								<? if(($row['vacation_type_1'] == "　" || $row['vacation_type_1'] == "") && ($row['vacation_type_2'] == "　" || $row['vacation_type_2'] == "") && ($row['attendance_type'] == "リ" || $row['attendance_type'] == "" || $row['attendance_type'] == "　")){ ?>
									<input type="text" value="<? echo $row['attendance_type'] ?>" name="select_type_button<?php echo $rowcnt ?>_<?php echo $colcnt ?>" readonly="readonly" class="status_gym">
									<input type="text" value="<? echo $row['select_type'] ?>" name="select_type<?php echo $rowcnt ?>_<?php echo $colcnt ?>" onclick="Javasctipt:Check_checkbox('<?php echo $rowcnt ?>','<?php echo $colcnt ?>')" readonly="readonly" class="status_gym" style="display:none">
								<? }else{ ?>
									<input type="text" value="<? echo $row['select_type'] ?>" name="select_type<?php echo $rowcnt ?>_<?php echo $colcnt ?>" onclick="Javasctipt:Check_checkbox('<?php echo $rowcnt ?>','<?php echo $colcnt ?>')" readonly="readonly" class="status_gym" style="display:none">
								<? } ?>
								</p>
							<? } ?>
						</td>
						<td style="display:none">
							<input type="text" name="t_select_type<?php echo $rowcnt ?>_<?php echo $colcnt ?>" value="<?php echo $row['select_type']; ?>">
							<input type="text" name="日付<?php echo $rowcnt ?>_<?php echo $colcnt ?>" value="<?php echo $row['date']; ?>">
							<input type="text" name="インデックス<?php echo $rowcnt ?>_<?php echo $colcnt ?>" value="<?php echo $row['idxnum']; ?>">
							<input type="text" name="team<?php echo $rowcnt ?>" value="<?php echo $row['team']; ?>">
							<input type="text" name="teamflg<?php echo $rowcnt ?>" value="0">
							<input type="text" name="担当者<?php echo $rowcnt ?>" value="<?php echo $row['staff']; ?>">
						</td>
						<? $colcnt = $colcnt+1; ?>
					<? } ?>
					<? $month = $row['month']; ?>
				<? } ?>
			<table>
			<input type="hidden" name="行数" value="<?php echo $rowcnt ?>">
			<input type="hidden" name="列数" value="<?php echo $colcnt ?>">
			</form>
		</div>
	</div>
</div>
</body>

<!-- データベース切断 -->
<?php if ($result) { $dba->mysql_discon($db); } ?>

</html>
