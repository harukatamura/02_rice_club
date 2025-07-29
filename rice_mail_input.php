<?php
//==================================================================================================
// ■機能概要
//   ・法人新規登録
//==================================================================================================

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
$prgname = "お問い合わせ　新規登録";
$prgmemo = "　お問い合わせの新規登録ができます。";
$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

//データベース接続
$db = "";
$result = $dba->mysql_con($db);

//入力担当者(COOKIEを利用)
$p_staff = $_COOKIE['con_perf_staff'];
$g_staff = $p_staff;

//テーブル項目取得
$table = "php_info_mail";
$collist = $dba->mysql_get_collist($db, $table);

//入力担当者(COOKIEを利用)
$g_idxnum = "";
if(isset($_GET['idxnum'])){
	$g_idxnum = $_GET['idxnum'];
	$query = "SELECT A.name";
	foreach($collist as $key => $val){
		$query .= " , A.".$val;
	}
	$query .= " FROM php_info_mail A ";
	$query .= " WHERE A.idxnum = ".sprintf("'%s'", $g_idxnum);
	$query .= " AND A.delflg = 0";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()){
		foreach($collist as $key => $val){
			${$val} = $row[$val];
		}
	}
}
foreach($collist as $key => $val){
	if($_POST[$key] <> ""){
		${$val} = $_POST[$key];
	}
}
if($correstaf <> ""){
	$g_staff = $correstaf;
}

//都道府県
if (!$rs = $comm->getcode($db, "address1")) {
	$comm->ouputlog("データ取得エラー", $prgid, SYS_LOG_TYPE_DBUG);
}
while ($row = $rs->fetch_array()) {
	$arr_prefecture[] = $row;
}

//----------------------------------------------------------------------------------------------
// システムデータの取得
//----------------------------------------------------------------------------------------------
//販売担当者一覧の取得
$comm->ouputlog("連絡担当者一覧の取得", $prgid, SYS_LOG_TYPE_INFO);
if (!$rs = $comm->getstaff($db, 14)) {
	$comm->ouputlog("データ取得エラー", $prgid, SYS_LOG_TYPE_DBUG);
}
while ($row = $rs->fetch_array()) {
	$p_sendlist[] = $row;
	$comm->ouputlog("担当者=". $row[0], $prgid, SYS_LOG_TYPE_DBUG);
}
//サポートセンターアルバイトを担当者に追加
$query = "SELECT  A.staff ";
$query .= " FROM php_l_user A ";
$query .= " WHERE A.companycd = 'S' ";
$query .= " AND staff NOT LIKE '%テスト%' ";
$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
if (!($rs = $db->query($query))) {
	$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
}
while ($row = $rs->fetch_array()) {
	$p_sendlist[][0] = $row['staff'];
}
$p_sendlist[][0] = "ジェネシス";
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title>問い合わせメール新規登録</title>

	<link rel="stylesheet" type="text/css" href="css/bootstrap.css">
	<!-- ============================= -->
	<!-- ▼郵便番号から住所を取得 -->
	<!-- ============================= -->
	<!-- ▼スクリプトをCDNから読み込む -->
	<script type="text/javascript" src="//code.jquery.com/jquery-2.1.0.min.js"></script>
	<script type="text/javascript" src="//jpostal-1006.appspot.com/jquery.jpostal.js"></script>

	<!-- ▼郵便番号や各種住所の入力欄に関するID名を指定する -->
	<script type="text/javascript">
		$(window).ready( function() {
			$('#postcd1').jpostal({
				postcode : [
					'#postcd1',
					'#postcd2'
				],
				address : {
					'#address1'  : '%3',
					'#address3'  : '%4%5'
				}
			});
		});
	</script>
	<style type="text/css"> 
		#container {
			text-align: left;
			margin-right: auto;
			margin-left: auto;
			background-color: white;
			padding-top: 250px;
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
			height:200px; 
		}
		#main{
			width: 600px;	/*コンテンツ幅*/
			text-align: center;
			margin: auto;
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
		}
		/* --- テーブル --- */
		table.tbh{
			margin:0 auto;
			width: 600px;	/*コンテンツ幅*/
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
		}
		th.tbd_th_p2_h {
			width:auto;
			text-align: left;
			font-weight:normal;
		}
		th.tbd_th_p3_l{
			text-align: left;
			width:auto;
			padding:0px 10px; /* 見出しセルのパディング（上下、左右） */
			margin: 0px auto;
		}
		
		/* --- テーブルデータセル（td） --- */
		td.tbd_td_p1_l {
			text-align: left;
			padding: 0px 10px; /* 見出しセルのパディング（上下、左右） */
			margin: 0px auto;
		}
		td.tbd_td_p1_r {
			text-align: right;
			padding: 0px 10px; /* 見出しセルのパディング（上下、左右） */
			text-decoration: underline;
			margin: 0px auto;
		}
		td.tbd_td_p2_l {
			width:auto;
			text-align: left;
			padding: 0px 10px; /* 見出しセルのパディング（上下、左右） */
			margin: 0px auto;
		}
		td.tbd_td_p3_l{
			text-align: left;
			padding:0px 10px; /* 見出しセルのパディング（上下、左右） */
			margin: 0px auto;
		}
		td.tbd_td_p3_c{
			text-align: center;
			padding:0px 10px; /* 見出しセルのパディング（上下、左右） */
			margin: 0px auto;
		}
	</style>

	<script type="text/javascript">
		//コメント編集
		function Ins_Business(){
		/*	if(document.forms['frm'].elements['会社名'].value == ""
			|| document.forms['frm'].elements['お名前'].value == ""
			|| document.forms['frm'].elements['ふりがな'].value == ""
			|| document.forms['frm'].elements['郵便番号1'].value == ""
			|| document.forms['frm'].elements['郵便番号2'].value == ""
			|| document.forms['frm'].elements['ご住所'].value == ""
			|| document.forms['frm'].elements['電話番号'].value == ""
			|| document.forms['frm'].elements['メールアドレス'].value == ""
			|| document.forms['frm'].elements['お問合せ内容'].value == ""
			|| document.forms['frm'].elements['対応担当者'].value == ""){
				alert('入力項目が不足しています。');
				return false
			}else{
		*/		
				if(window.confirm('登録しますか？')){
					document.forms['frm'].action = './info_mail_sql.php?do=ins_mail';
					document.forms['frm'].submit();
				}
		//	}
		}
		function Upd_Business(idxnum){
		/*	if(document.forms['frm'].elements['会社名'].value == ""
			|| document.forms['frm'].elements['お名前'].value == ""
			|| document.forms['frm'].elements['ふりがな'].value == ""
			|| document.forms['frm'].elements['郵便番号1'].value == ""
			|| document.forms['frm'].elements['郵便番号2'].value == ""
			|| document.forms['frm'].elements['ご住所'].value == ""
			|| document.forms['frm'].elements['電話番号'].value == ""
			|| document.forms['frm'].elements['メールアドレス'].value == ""
			|| document.forms['frm'].elements['お問合せ内容'].value == ""
			|| document.forms['frm'].elements['対応担当者'].value == ""){
				alert('入力項目が不足しています。');
				return false
			}else{
		*/		if(window.confirm('登録しますか？')){
					document.forms['frm'].action = './info_mail_sql.php?do=upd_mail&idxnum='+idxnum;
					document.forms['frm'].submit();
				}
		//	}
		}
	</script>
</head>

<body>
	<div id="header">
		<h1>顧客情報　新規登録</h1>
	</div>
	<div id="container">
		<div id="main">
			<form name="frm" method="post" enctype="multipart/form-data">
				<table class="tbh">
					<tr>
						<td>会社名</td>
					</tr>
					<tr>
						<td class="tbd_tb_t_l">
							<input type="text" size="50" maxlength="50" name="会社名" value="<? echo $company ?>" placeholder="例）一般社団法人　日本電子機器補修協会">
						</td>
					</tr>
					<tr>
						<td>お名前</td>
					</tr>
					<tr>
						<td class="tbd_tb_t_l">
							<input type="text" size="50" maxlength="50" name="お名前" value="<? echo $name ?>" placeholder="例）田中　太郎">
						</td>
					</tr>
					<tr>
						<td>ふりがな</td>
					</tr>
					<tr>
						<td class="tbd_tb_t_l">
							<input type="text" size="50" maxlength="50" name="ふりがな" value="<? echo $ruby ?>" placeholder="例）たなか　たろう">
						</td>
					</tr>
					<tr>
						<td>住所</td>
					</tr>
					<tr>
						<td class="tbd_tb_t_l">
							〒<input type="text" name="郵便番号" size="3" maxlength="3" value="<?php echo $postcd1 ?>" id="postcd1" placeholder="例）461"> -
							<input type="text" name="郵便番号2" size="4" maxlength="4" value="<?php echo $postcd2 ?>" id="postcd2" placeholder="例）0011">
						</td>
					</tr>
					<tr>
						<td class="tbd_tb_t_l">
							<select name = "都道府県" id="address1">
								<? foreach($arr_prefecture as $line) {
									if($line[0] == $address1 ){ ?>
										<option value="<? echo $line[0] ?>" selected="selected"><? echo $line[0] ?></option>
									<? }else{ ?>
										<option value="<? echo $line[0] ?>"><? echo $line[0] ?></option>
									<? }
								} ?>
							</select>
						</td>
					</tr>
					<tr>
						<td class="tbd_tb_t_l">
							<input type="text" name="ご住所" id="address3" size="30" maxlength="50" value="<?php echo $address2; ?>" placeholder="ご住所"><br>
						</td>
					</tr>
					<tr>
						<td>電話番号</td>
					</tr>
					<tr>
						<td class="tbd_tb_t_l">
							<input type="text" size="50" maxlength="50" name="電話番号" value="<?php echo $phonenum ?>" placeholder="例）052-936-8887">
						</td>
					</tr>
					<tr>
						<td>メールアドレス</td>
					</tr>
					<tr>
						<td class="tbd_tb_t_l">
							<input type="text" size="50" maxlength="50" name="メールアドレス" value="<?php echo $email ?>" placeholder="例）sample@jemtc.jp">
						</td>
					</tr>
					<tr>
						<td>問い合わせ内容</td>
					</tr>
					<tr>
						<td class="tbd_tb_t_l">
							<textarea name="お問合せ内容" id="contents" rows="10" style="width:100%; font-size:12px;"><?php echo $question ?></textarea>
						</td>
					</tr>
					<tr>
						<td>対応担当者</td>
					</tr>
					<tr>
							<td class="tbd_tb_t_l">
								<select name="対応担当者">
									<? foreach($p_sendlist as $list) {
										if($g_staff == $list[0]){ ?>
											<option value="<? echo $list[0] ?>" selected="selected" ><? echo $list[0] ?></option>
										<? }else{ ?>
											<option value="<? echo $list[0] ?>"><? echo $list[0] ?></option>
										<? }
									} ?>
								</select>
							</td>
						</tr>
					<tr>
						<td class="tbd_tb_t_c">
							<? if(isset($_GET['idxnum'])){ ?>
								<button class="btn btn-default" style="position: absolute; left: 46%;" onClick="javascript:Upd_Business(<? echo $g_idxnum ?>); return false;">更新</button>
							<? }else{ ?>
								<button class="btn btn-default" style="position: absolute; left: 46%;" onClick="javascript:Ins_Business(); return false;">登録</button>
							<? } ?>
							<p style="text-align:right; font-size:12px;"><a href="Javascript:window.close()">閉じる</a></p>
						</td>
					</tr>
				</table>
			</form>
		</div>
	</div>
</body>

<!-- データベース切断 -->
<?php if ($result) { $dba->mysql_discon($db); } ?>
</html>