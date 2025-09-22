<?
//==================================================================================================
// ■機能概要
//   ・問い合わせメールテンプレート編集
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
$prgname = "問い合わせメールテンプレート編集";
$prgmemo = "　問い合わせメールテンプレート編集を行います";
$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

//データベース接続
$db = "";
$result = $dba->mysql_con($db);

//入力担当者(COOKIEを利用)
$p_staff = $_COOKIE['con_perf_staff'];
$p_compcd = $_COOKIE['con_perf_compcd'];

//id取得
$g_idxnum=$_GET['idxnum'];
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title>infomailテンプレート登録画面</title>

	<link rel="stylesheet" type="text/css" href="css/bootstrap.css">

	<style type="text/css"> 
		#container {
			text-align: left;
			margin-right: auto;
			margin-left: auto;
			background-color: white;
			padding-top: 80px;
			padding-bottom: 200px;
			width: 700px;
			margin: 0px auto; /* （上下、左右） */
			font-size:18px;
		}
		body {
			color: #333333;
			background-color: white;
			margin: 0px;
			padding: 0px;
			text-align: center;
			font: 70%/2 "メイリオ", Meiryo, "ＭＳ Ｐゴシック", Osaka, "ヒラギノ角ゴ Pro W3", "Hiragino Kaku Gothic Pro";
		}
		#header {
			position: fixed;	/* ヘッダーを固定する */
			margin: 10x;
			padding: 10px;
			background-color: #dcdcdc;
			padding: 0px;
			text-align: center;
			width:100%;
			height:80px; 
		}
		#main{
			width: 600px;	/*コンテンツ幅*/
			text-align: center;
			margin: auto;
			padding-top: 80px;
			padding-bottom: 200px;
			overflow: auto; 	/* コンテンツの表示を自動に設定（スクロール） */
			background-color: white;
			color:gray;
		}
		
		h1{
			font-size:28px;
			text-align: center;
		}
		.form-category {
			overflow: hidden;
			width: 100px;
			margin: 5px;
			text-align: left;
			position: relative;
			background: white;
			color:gray;
			font-size:13px;
		}
		/* --- テーブル --- */
		table.tbh{
			margin:0 auto;
			width: 1015px;	/*コンテンツ幅*/
		}
		/* --- テーブルヘッダーセル（th） --- */
		th.tbd_th_1 {
			padding: 20px 10px; /* 見出しセルのパディング（上下、左右） */
			color: black;
			background-color: white; /* 見出しセルの背景色 */
			border-bottom : 1px solid black;
			border-top : 1px solid black;
			text-align: center;
			font-weight:bolder
			white-space: nowrap;
			width: 45px;
			font-size: 12px;
		}
		th.tbd_th_2 {
			color: black;
			background-color: white; /* 見出しセルの背景色 */
			border-bottom : 1px solid black;
			border-top : 1px solid black;
			text-align: center;
			line-height: 130%;
			font-weight:bolder
			white-space: nowrap;
			width: 130px;
			font-size: 12px;
		}
		th.tbd_th_p2_h {
			width:auto;
			text-align: left;
			font-weight:normal;
			font-size: 12px;
		}
		th.tbd_th_p3_l{
			text-align: left;
			width:auto;
			padding:0px 10px; /* 見出しセルのパディング（上下、左右） */
			margin: 0px auto;
			font-size: 12px;
		}
		
		/* --- テーブルデータセル（td） --- */
		td.tbd_td_p1_l {
			text-align: left;
			padding: 0px 10px; /* 見出しセルのパディング（上下、左右） */
			margin: 0px auto;
			font-size: 12px;
		}
		td.tbd_td_p1_r {
			text-align: right;
			padding: 0px 10px; /* 見出しセルのパディング（上下、左右） */
			text-decoration: underline;
			margin: 0px auto;
			font-size: 12px;
		}
		td.tbd_td_p2_l {
			width:auto;
			text-align: left;
			padding: 0px 10px; /* 見出しセルのパディング（上下、左右） */
			margin: 0px auto;
			font-size: 12px;
		}
		td.tbd_td_p3_l{
			text-align: left;
			padding:0px 10px; /* 見出しセルのパディング（上下、左右） */
			margin: 0px auto;
			font-size: 12px;
		}
		td.tbd_td_p3_c{
			text-align: center;
			padding:0px 10px; /* 見出しセルのパディング（上下、左右） */
			margin: 0px auto;
			font-size: 12px;
		}
		.temp_button {
			display: inline-block;
			padding: 0.05em 1em;
			text-decoration: none;
			color: #007bbb;
			border: solid 2px #007bbb;
			border-radius: 3px;
			transition: .2s;
			background: #ffffff;
		}

		.temp_button:hover {
			background: #007bbb;
			color: white;
		}
		.box11 {
		    padding: 0.5em 1em;
		    margin: 2em 0;
		    color: #434da2;
		    background: white;
		    border-top: solid 5px #434da2;
		    box-shadow: 0 3px 5px rgba(0, 0, 0, 0.22);
		}
		.box11 p {
		    margin: 0; 
		    padding: 0;
		}
	</style>

	<script type="text/javascript">
		//登録
		function Ins_Contents(){
			if(document.frm.title.value == ""){
				alert('タイトルを入力してください。');
			}else if(document.frm.contents.value == ""){
				alert('本文を入力してください。');
			}else{
				var rowINX = 'do=ins_temp';
				if(window.confirm('テンプレートを新規登録します')){
					document.forms['frm'].action = './rice_mail_sql.php?' + rowINX;
					document.forms['frm'].submit();
				}
			}
		}
		//更新
		function Upd_Contents(idx){
			if(document.frm.title.value == ""){
				alert('タイトルを入力してください。');
			}else if(document.frm.contents.value == ""){
				alert('本文を入力してください。');
			}else{
				var rowINX = 'do=upd_temp&idxnum='+idx;
				if(window.confirm('テンプレートを更新します')){
					document.forms['frm'].action = './rice_mail_sql.php?' + rowINX;
					document.forms['frm'].submit();
				}
			}
		}
		//削除
		function Del_Contents(idx){
			var rowINX = 'do=del_temp&idxnum='+idx;
			if(window.confirm('テンプレートを削除します')){
				document.forms['frm'].action = './rice_mail_sql.php?' + rowINX;
				document.forms['frm'].submit();
			}
		}
	</script>
</head>

<body>
	<div id="header">
	</div>
	<div id="container">
		<form name="frm" method="post" enctype="multipart/form-data">
			<?
			$title = "";
			$contents = "";
			if($g_idxnum <> ""){
				//----- データ抽出
				$query = "";
				$query .= " SELECT A.title ,A.contents ";
				$query .= " FROM php_rice_mail_tmp A ";
				$query .= " WHERE A.idxnum = $g_idxnum";
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs = $db->query($query))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				while ($row = $rs->fetch_array()) {
					$title = $row['title'];
					$contents = $row['contents'];
				}
			} ?>
			<div class="row">
				<? if($g_idxnum <> ""){ ?>
					<h1>infomailテンプレート修正</h1>
				<? }else{ ?>
					<h1>infomailテンプレート登録</h1>
				<? } ?>
			</div>
			<div id="main>"
				<table class="tbh">
					<tr>
						<th class="tbd_th_l">
							<p>タイトル</p>
						</th>
					</tr>
					<tr>
						<td class="tbd_tb_l">
							<input type="text" name="タイトル" id="title" style="width:100%; font-size:12px;" value="<? echo $title; ?>">
						</td>
					</tr>
					<tr>
						<th class="tbd_th_l">
							<br><p>本文</p>
						</th>
					</tr>
					<tr>
						<td class="tbd_tb_t_l">
							<textarea name="本文" id="contents" rows="20" style="width:100%; font-size:12px;"><? echo $contents; ?></textarea>
						</td>
					</tr>
					<tr>
						<td class="tbd_tb_t_c">
							<? if($g_idxnum <> ""){ ?>
								<br>
								<button class="btn btn-default" style="position: absolute; left: 43%;" onClick="javascript:Upd_Contents(<? echo $g_idxnum; ?>); return false;">更新</button>
								<button class="btn btn-default" style="position: absolute; left: 53%;" onClick="javascript:Del_Contents(<? echo $g_idxnum; ?>); return false;">削除</button>
							<? }else{ ?>
								<br><button class="btn btn-default" style="position: absolute; left: 46%;" onClick="javascript:Ins_Contents(); return false;">登録</button>
							<? } ?>
						</td>
					</tr>
					<tr>
						<td class="tbd_tb_t_r">
								<p style="text-align:right; font-size:12px;"><a href="Javascript:window.close()">閉じる</a></p>
						</td>
					</tr>
				</table>
			</div>
		</form>
	</div>
</body>
<?
function return_value($value) {
	return htmlspecialchars($value, ENT_QUOTES|ENT_HTML5, "UTF-8");
}
?>
<!-- データベース切断 -->
<? if ($result) { $dba->mysql_discon($db); } ?>
</html>