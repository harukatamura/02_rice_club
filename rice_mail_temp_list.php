<?php
//==================================================================================================
// ■機能概要
//   ・infomailテンプレート
//==================================================================================================
//----------------------------------------------------------------------------------------------
// 初期処理
//----------------------------------------------------------------------------------------------
//ログイン確認(COOKIEを利用)
if((!$_COOKIE['j_office_Uid']) or (!$_COOKIE['j_office_Pwd'])) {
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
	$prgname = "infomailテンプレート一覧";
	$prgmemo = "　infomailテンプレートを一覧で確認できます。";
	$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);

	//本日日付
	$today = date('Y/m/d');
	$comm->ouputlog("today=" . $today, $prgid, SYS_LOG_TYPE_DBUG);


	//担当者確認(COOKIEを利用)
	if ($_COOKIE['con_perf_compcd']) {
		$p_compcd = $_COOKIE['con_perf_compcd'];
	}
	
	//----------------------------------------------------------------------------------------------
	// 引数取得処理
	//----------------------------------------------------------------------------------------------

	//担当者
	$p_staff = $_COOKIE['con_perf_staff'];

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<meta http-equiv="Refresh" content="60">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
<!--sweetalert2-->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@8"></script>

  <title>精米倶楽部メールお問い合わせ一覧</title>
  
  <!-- cascading style seet-->
  <link rel="stylesheet" type="text/css" href="css/bootstrap.css">


	<style type="text/css">  
	/*コンテナー（HPを囲むブロック）
	---------------------------------------------------------------------------*/
	#container {
		text-align: left;
		margin-right: auto;
		margin-left: auto;
		background-color: #FFFFFF;
		padding-right: 4px;
		padding-left: 4px;
	}
	body {
		color: #333333;
		background-color: #FFFFFF;
		margin: 0px;
		padding: 0px;
		text-align: center;
		font: 70%/2 "メイリオ", Meiryo, "ＭＳ Ｐゴシック", Osaka, "ヒラギノ角ゴ Pro W3", "Hiragino Kaku Gothic Pro";
	}
	#header {
		position: fixed;	/* ヘッダーを固定する */
		margin: 10x;
		padding: 10px;
		color: #f0ffff;
		background-color: #dcdcdc;
		padding: 0px;
		text-align: center;
		width:100%;
		height:180px; 
	}
	#main{
		width: 1200px;	/*コンテンツ幅*/
		text-align: center;
		margin: auto;
		padding-top: 200px;
		padding-bottom: 200px;
		overflow: auto; 	/* コンテンツの表示を自動に設定（スクロール） */
		background-color: white;
		color:gray;
	}
	#note {
		text-align: right;
		font-size:16px;
		width: 1200px;
		margin-right: auto;
		margin-left: auto;
	}
	#search {
		text-align: right;
		font-size:12px;
		width: 1050px;
		margin-right: auto;
		margin-left: auto;
	}
	#checkwait {
		text-align: right;
		font-size:12px;
		width: 1050px;
		margin-right: auto;
		margin-left: auto;
	}
	#note2 {
		text-align: right;
		font-size:12px;
		width: 1050px;
		margin-right: auto;
		margin-left: auto;
	}
	h2{
		font-size:36px;
		text-decoration: underline;
	}
	
	/*返信内容表示 td*/
	.correcont_td{
		width: 136px;
		max-height:64px;
		overflow-y:auto; 
		padding: 4px;
	}
	.sta00{
		background-color: #fff2cc;
	}
	.sta03{
		background-color: #ffdab9;
	}
	.sta08{
		background-color: #eaffff;
	}
	.sta09{
		background-color: #a9a9a9;
	}
	.ordinary{
		background-color: white;
	}
	.danger{
		background-color: #fdd5dc;
	}
	
	
	/* --- テーブル --- */
	table.tbh{
		margin:0 auto;
		border : 1px solid black;
	}
	/* --- テーブルヘッダーセル（th） --- */
	th.tbd_th_1 {
	padding: 20px 10px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: 0067c0; /* 見出しセルの背景色 */
	border-bottom : 1px solid black;
	border-top : 1px solid black;
	text-align: center;
	line-height: 130%;
	font-weight:bolder
	white-space: nowrap;
	width: 45px;
	}
	th.tbd_th_2 {
	padding: 20px 10px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: 0067c0; /* 見出しセルの背景色 */
	border-bottom : 1px solid black;
	border-top : 1px solid black;
	text-align: center;
	line-height: 130%;
	font-weight:bolder
	white-space: nowrap;
	width: 200px;
	}
	th.tbd_th_3 {
	padding: 20px 10px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: 0067c0; /* 見出しセルの背景色 */
	border-bottom : 1px solid black;
	border-top : 1px solid black;
	text-align: center;
	line-height: 130%;
	font-weight:bolder
	white-space: nowrap;
	width: 300px;
	}
	th.tbd_th_4 {
	padding: 20px 10px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: 0067c0; /* 見出しセルの背景色 */
	border-bottom : 1px solid black;
	border-top : 1px solid black;
	text-align: center;
	line-height: 130%;
	font-weight:bolder
	white-space: nowrap;
	width: 600px;
	}
	
	/* --- テーブルデータセル（td） --- */
	td.tbd_td_p1 {
		text-align: center;
		padding: 15px 10px; /* 見出しセルのパディング（上下、左右） */
	}
	td.tbd_td_p1_l {
		text-align: left;
		padding: 15px 10px; /* 見出しセルのパディング（上下、左右） */
	}
	td.tbd_td_p2 {
		text-align: center;
		padding: 15px 10px; /* 見出しセルのパディング（上下、左右） */
		width: 30px;
	}
	/*ボタン*/
	.search{
		display: inline-block;
		font-weight: bold;
		padding: 0.25em 0.5em;
		text-decoration: none;
		color: gray;
		background: #ECECEC;
		border-radius: 15px;
		transition: .4s;
	}
	.search:hover {
		background:gray ;
		color: #ECECEC;
	}
	/*検索*/
	#search_detail{
		background: #ECECEC;
		padding: 0.5em;
		margin: 10px 2px 0px 550px;
	}
	table.search_table {
		padding: 0.25em 0.5em;
		width: 600px;
	}
	th.sh_th {
		width: 130px;
		padding: 0.25em 0.5em;
	}
	td.sh_td_c {
		width: 20px;
		padding: 0.25em 0.5em;
	}
	td.sh_td_l {
		width: 450px;
		padding: 0.25em 0.5em;
	}
	table tbody tr:hover{
	  background-color: #A5E4FF;
	}
	/* 登録ボタン */
	.btn-borders.btn-infos.d {
		background:#8b0000;
		border:1px solid #8b0000;
		border-radius: 10px;
		font-weight: bold;
		font-size: 20px;
		color:#fffafa;
		width: 100px;
		height: 40px;
	}
	.btn-borders.btn-infos.d:hover,.btn-border.btn-info.d:active {
		border:4.5px solid #3dff9e;
	}
</style>
	<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<script type="text/javascript">
		//ページジャンプ
		function edit_temp(idx) {
			var rowINX = 'idxnum='+idx;
			window.open ( "./rice_mail_temp_edit.php?"+ rowINX);
		}
	</script> 

</head>
<body>
	<div id="container">
		<div id="header">
			<br><h2>infoメールテンプレート一覧</h2>
	  <!-- 項目①
	  ================================================== -->
			<br>
		</div>
		<div id="main">
			<br>
			<form name="search" method="post" action="./rice_mail.php">
				<div id="checkwait">
					<input type="button" class="search" OnClick="window.open('./rice_mail_temp_edit.php')" value="新規作成"><br>
				</div>
			</form>
			<form name="frm" method="post">
				<!-- 一覧テーブル -->
				<table class="tbh" id= "TBL">
				<thead>
					<tr>
						<th class="tbd_th_1">NO.</th>
						<th class="tbd_th_2">登録日<br>(最終更新日)</th>
						<th class="tbd_th_3" title="マウスオンで全文表示">件名※</th>
						<th class="tbd_th_4" title="マウスオンで全文表示">内容※</th>
					</tr>
				</thead>
				<tbody>
				<?php
					//----- データ抽出
					$query = "";
					$query .= " SELECT A.idxnum, A.title, A.contents, A.insdt, A.upddt ";
					$query .= " FROM php_rice_mail_tmp A ";
					$query .= " WHERE A.delflg = 0";
					$query .= " ORDER BY A.idxnum DESC";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($query))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$i = 0;
					while ($row = $rs->fetch_array()) {
						$i++;
						?>
						<tr onClick="Javascript:edit_temp(<?php echo $row['idxnum'] ?>,<?php echo $row['sendflg'] ?>)">
							<td class="tbd_td_p1">
								<a href=""><?php echo $row['idxnum'] ?></a>
							</td>
							<td class="tbd_td_p1">
								<?php echo date('Y/m/d H:i', strtotime($row['insdt'])); ?><br>
								(<?php echo date('Y/m/d H:i', strtotime($row['upddt'])); ?>)
							</td>
							<td class="tbd_td_p1_l" title="<? echo $row['title'] ?>">
								<?php echo $row['title']; ?>
							</td>
							<td class="tbd_td_p1_l" title="<? echo $row['contents'] ?>">
								<?php echo mb_substr($row['contents'],0,37,'UTF-8') ?><br>
								<?php echo mb_substr($row['contents'],37,37,'UTF-8') ?>...
							</td>
						</tr>
					<?php } ?>
				</tbody>
				</table>
			</form>
		</div>
	</div>
</body>

<!-- データベース切断 -->
<?php if ($result) { $dba->mysql_discon($db); } ?>
</html>