<?
//==================================================================================================
// ■機能概要
//   ・精米倶楽部メーリス送信フォーム
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
$prgname = "精米倶楽部メーリス送信フォーム";
$prgmemo = "　精米倶楽部メーリス送信を行います";
$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

//データベース接続
$db = "";
$result = $dba->mysql_con($db);

//入力担当者(COOKIEを利用)
$g_staff = $_COOKIE['con_perf_staff'];
$p_compcd = $_COOKIE['con_perf_compcd'];
$g_finish = $_GET['finish'];
$s_staff = $_COOKIE['con_perf_staff'];

//id取得
$g_idxnum=$_GET['idxnum'];
$g_mail_idx=$_GET['mail_idx'];

$query = "SELECT A.title, A.mail_group ";
$query .= " FROM php_rice_mail_list_group A ";
$query .= " WHERE A.delflg=0 ";
$query .= " GROUP BY A.mail_group ";
$query .= " ORDER BY A.mail_group ";
$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
if (!($rs = $db->query($query))) {
	$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
}
while ($row = $rs->fetch_array()) {
	$arr_grp[$row['mail_group']] = $row['title'];
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title>返信内容入力</title>

	<link rel="stylesheet" type="text/css" href="css/bootstrap.css">

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
			height:250px; 
		}
		#main{
			width: 600px;	/*コンテンツ幅*/
			text-align: center;
			margin: auto;
			padding-top: 250px;
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
		//コメント登録
		function Ins_Contents(idx){
			if(document.frm.category.selectedIndex == 0){
				alert('コメント/メール受信を選択してください。');
			}else if(document.frm.contents.value == ""){
				alert('内容を入力してください。');
			}else{
				var rowINX = 'do=inscontents&idxnum='+idx;
				var rowINX = 'do=inscontents';
				document.forms['frm'].action = './rice_mail_sql.php?' + rowINX;
				document.forms['frm'].submit();
			}
		}
		//コメント編集
		function Edit_Contents(idx,m_idx){
			if(document.frm.category.selectedIndex == 0){
				alert('コメント/メール受信/メール送信を選択してください。');
			}else if(document.frm.contents.value == ""){
				alert('内容を入力してください。');
			}else{
				var rowINX = 'do=editcontents&idxnum='+idx+'&mail_idx='+m_idx;
				var rowINX = 'do=editcontents';
				document.forms['frm'].action = './rice_mail_sql.php?' + rowINX;
				document.forms['frm'].submit();
			}
		}
		//メール確認
		function Mail_Check(idx){
			if(document.frm.email.value == ""){
				alert('宛先を入力してください。');
			}else if(document.frm.title.value == ""){
				alert('タイトルを入力してください。');
			}else if(document.frm.contents.value == ""){
				alert('内容を入力してください。');
			}else{
				var rowINX = 'do=check&idxnum='+idx;
				var rowINX = 'do=check';
				if(window.confirm('確認画面に移行します')){
					document.forms['frm'].action = './rice_mail_list_form.php?' + rowINX;
					document.forms['frm'].submit();
				}
			}
		}
		//メール返信
		function Mail_Reply(idx){
			var rowINX = 'do=reply&idxnum='+idx;
			var rowINX = 'do=reply';
			if(window.confirm('メールを送信しますか？')){
				document.forms['frm'].action = './rice_mail_sql.php?' + rowINX;
				document.forms['frm'].submit();
			}
		}
		function Mail_Redo(idx){
			if(window.confirm('編集画面に戻りますか？')){
				document.forms['frm'].action = './rice_mail_list_form.php?do=redo&idxnum='+idx;
				document.forms['frm'].submit();
			}
		}
		//状態変更
		function Change_Sql(idx){
			var rowINX = 'do=change_list&idxnum='+idx;
			var rowINX = 'do=change_list';
			document.forms['frm'].action = './rice_mail_sql.php?' + rowINX;
			document.forms['frm'].submit();
		}
	</script>
</head>

<body>
	<?
	if ($g_finish == 'send') {
	?>
		<script type="text/javascript">
			localStorage.setItem('mail_check_flg', 1);
			window.opener.location.href = './rice_mail_checklist.php';
			close();
		</script>
	<?
	} else if ($g_finish == 'check') {
	?>
		<script type="text/javascript">
			localStorage.setItem('mail_check_before_flg', 1);
			window.opener.location.href = './rice_mail.php';
			close();
		</script>
	<?
	}
	?>
	<div id="header">
		<table class="tbh" id= "TBL">
			<tr>
				<td class ="tbd_td_p3_l">
					<h2>精米倶楽部　メーリス送信</h2>
					
				</td>
			</tr>
		</table>
	</div>
	<div id="container">
		<form name="frm" method="post" enctype="multipart/form-data">
			<? if($_GET['do'] == "reply"){
				//テンプレート抽出
				$query = "";
				$query .= "SELECT A.title, A.contents, A.idxnum ";
				$query .= " FROM php_rice_mail_tmp A ";
				$query .= " WHERE A.delflg=0 ";
				$query .= " AND A.listflg=1 ";
				$query .= " ORDER BY A.idxnum ";
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs = $db->query($query))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				$arr_temp = [];
				$arr_temp_contents = [];
				while ($row = $rs->fetch_array()) {
					$arr_temp[$row['idxnum']] = $row['title'];
					$arr_temp_contents[$row['title']] = $row['contents'];
				}
				//送信先アドレス
				$to_add = "kome@jemtcnet.jp";
				$bcc_add = "";
				//返信定型文
				$g_tmp = 0;
				foreach($arr_temp as $key => $val){
					if(isset($_POST[$val."_buttom"])){
						$g_tmp = $key;
					}
				}
				if ($quote_flg == 0) {
					if ($g_tmp > 0) {
						$honbun = $arr_temp_contents[$arr_temp[$g_tmp]]."\n\n";
					} else {
						$honbun = "";
					}
				} else if ($checkflg == 0) {
					if ($g_tmp > 0) {
						$honbun = $arr_temp_contents[$arr_temp[$g_tmp]]."\n\n".$quote;
					} else {
						$honbun = $quote;
					}
				} else {
					$honbun = $contents;
				}
				$title = "【精米倶楽部】JEMTCからのご案内";
				?>
				<div class="row">
					<h1>メーリングリスト送信</h1>
				</div>
				<div id="main>"
					<table class="tbh">
						<tr>
							<th class="tbd_th_l">
								<p>To</p>
							</th>
						</tr>
						<tr>
							<td class="tbd_tb_l">
								<input type="text" type="email" name="email" id="email" style="width:100%; font-size:12px;" value="<? echo $to_add ?>"></input>
							</td>
						</tr>
						<tr>
							<th class="tbd_th_l">
								<p>送信先</p>
							</th>
						</tr>
						<tr>
							<td class="tbd_tb_l">
								<select name="送信先" style="padding :3px 10px;">
									<? foreach($arr_grp as $key => $val){ ?>
										<option value="<?= $key; ?>"><?= $val; ?></option>
									<? } ?>
								</select>
							</td>
						</tr>
						<tr>
							<th class="tbd_th_l">
								<p>
									BCc
									<span style="text-align:left"><font color="red">※複数ある場合は、半角カンマ「,」で区切ってください</font></span>
								</p>
							</th>
						</tr>
						<tr>
							<td class="tbd_tb_l">
								<input type="text" type="email" name="bcc" id="bcc" style="width:100%; font-size:12px;" value="<? echo $bcc_add ?>"></input>
							</td>
						</tr>
						<tr>
							<th class="tbd_th_l">
								<p>タイトル</p>
							</th>
						</tr>
						<tr>
							<td class="tbd_tb_l">
								<input type="text" name="件名" id="title" style="width:100%; font-size:12px;" value="<?= $title; ?>">
							</td>
						</tr>
						<tr>
							<th class="tbd_th_l">
								<br><p>本文</p>
								<p style="text-align:left"><font color="red">※左記の文字列は使用不可のため、削除されます。「"」,「'」,「\」</font></p>
								<p style="text-align:left"><font color="red">※署名は自動で挿入されますので入力しないでください。</font></p>
							</th>
						</tr>
						<tr>
							<td class="tbd_tb_l">
								<textarea name="本文" id="contents" rows="20" style="width:100%; font-size:12px;"><?php echo $honbun ?></textarea>
							</td>
						</tr>
						<form method="post">
							<div class="box11">
								<tr>
									<th class="tbd_th_l">
										<div style="text-align:center;">テンプレート文挿入</div>
									</th>
								</tr>
								<tr>
									<? foreach($arr_temp as $key => $val){ ?>
										<td class="tbd_tb_c">
											<button type="submit" class="temp_button" name="<? echo $val ?>_buttom" style="text-align:left;"><? echo $val ?></button>
										</td>
									<? } ?>
								</tr>
							</div>
						</form>
					</table>
					<table class="tbh">
						<tr>
							<td class="tbd_tb_c">
								<button class="btn btn-default" style="position: absolute; left: 46%;" onClick="javascript:Mail_Check(<? echo $g_idxnum; ?>); return false;">送信内容確認</button>
							</td>
						</tr>
					</table>
					<table class="tbh">
						<tr>
							<td class="tbd_tb_r">
								<p style="text-align:right; font-size:12px;"><a href="Javascript:window.close()">閉じる</a></p>
							</td>
						</tr>
					</table>
				</div>
			<? }else if($_GET['do'] == "redo"){
				?>
				<div class="row">
					<h1>infoメール返信</h1>
				</div>
				<div id="main>"
					<table class="tbh">
						<tr>
							<th class="tbd_th_l">
								<p>To</p>
							</th>
						</tr>
						<tr>
							<td class="tbd_tb_l">
								<input type="text" name="email" id="email" style="width:100%; font-size:12px;" value="<? echo $_POST['email'] ?>"></input>
							</td>
						</tr>
						<tr>
							<th class="tbd_th_l">
								<p>送信先</p>
							</th>
						</tr>
						<tr>
							<td class="tbd_tb_l">
							<td class="tbd_tb_l">
								<select name="送信先" style="padding :3px 10px;">
									<? foreach($arr_grp as $key => $val){ ?>
										<option value="<?= $key; ?>" <? if($key == $_POST['送信先']){echo "selected='selected'";} ?>><?= $val; ?></option>
									<? } ?>
								</select>
							</td>
							</td>
						</tr>
						<tr>
							<th class="tbd_th_l">
								<p>BCc</p>
							</th>
						</tr>
						<tr>
							<td class="tbd_tb_l">
								<input type="text" name="bcc" id="bcc" style="width:100%; font-size:12px;" value="<? echo $_POST['bcc'] ?>"></input>
							</td>
						</tr>
							<tr>
							<th class="tbd_th_l">
								<p>タイトル</p>
							</th>
						</tr>
						<tr>
							<td class="tbd_tb_l">
								<input type="text" name="件名" id="title" style="width:100%; font-size:12px;" value="<? echo $_POST['件名']; ?>">
							</td>
						</tr>
						<tr>
							<th class="tbd_th_l">
								<br><p>本文</p>
							</th>
						</tr>
						<tr>
							<td class="tbd_tb_l">
								<textarea name="本文" id="contents" rows="20" style="width:100%; font-size:12px;"><? echo $_POST['本文'] ?></textarea>
							</td>
						</tr>
					</table>
					<table class="tbh">
						<tr>
							<td class="tbd_tb_c">
								<button class="btn btn-default" style="position: absolute; left: 46%;" onClick="javascript:Mail_Check(<? echo $g_idxnum; ?>); return false;">送信内容確認</button>
							</td>
						</tr>
					</table>
					<table class="tbh">
						<tr>
							<td class="tbd_tb_r">
								<p style="text-align:right; font-size:12px;"><a href="Javascript:window.close()">閉じる</a></p>
							</td>
						</tr>
					</table>
				</div>
			<?php
			} else if($_GET['do'] == "check") { 
				if(isset($g_idxnum)){
					//----- データ抽出
					$query = "";
					$query .= " SELECT A.mail_status";
					$query .= " FROM php_rice_mail_list A ";
					$query .= " WHERE A.mail_idxnum = $g_idxnum";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($query))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$status = $row['mail_status'];
					}
				}
				?>
					<div class="row">
						<h1>infoメール返信内容確認</h1>
					</div>
					<div id="main>"
						<table class="tbh">
							<tr>
								<th class="tbd_th_l">
									<p>＜To＞</p>
									<pre><? echo $_POST['email'] ?></pre>
									<input type="text" style="display:none" name="email" value="<? echo $_POST['email'] ?>">
								</th>
							</tr>
							<tr>
								<th class="tbd_th_l">
									<p>＜送信先＞</p>
									<pre><? echo $arr_grp[$_POST['送信先']] ?></pre>
									<input type="text" style="display:none" name="送信先" value="<? echo $_POST['送信先'] ?>">
								</th>
							</tr>
							<tr>
								<th class="tbd_th_l">
									<p>＜BCc＞</p>
									<pre><? echo $_POST['bcc'] ?></pre>
									<input type="text" style="display:none" name="bcc" value="<? echo $_POST['bcc'] ?>">
								</th>
							</tr>
							<tr>
								<th class="tbd_th_l">
									<p>＜タイトル＞</p>
									<?
									$p_subject = $_POST['件名'];
									$main_text = $_POST['本文'];
									// エスケープ処理
									$p_subject = addslashes($p_subject);
									$main_text = addslashes($main_text);
									?>
									<pre><? echo $p_subject ?></pre>
									<input type="text" style="display:none" name="件名" value="<? echo $p_subject ?>">
								</th>
							</tr>
							<tr>
								<th class="tbd_th_l">
									<p>＜本文＞</p>
								</th>
							</tr>
							<tr>
								<td class="tbd_tb_l">
									<pre><? echo return_value($main_text) ?></pre>
									<textarea name="本文" style="display:none"><? echo return_value($main_text) ?></textarea>
								</td>
							</tr>
						</table>
						<table class="tbh">
							<tr>
								<td class="tbd_tb_c">
									<button class="btn btn-default" style="position: absolute;left:43%" onClick="javascript:Mail_Redo(<? echo $g_idxnum; ?>); return false;">戻る</button>
								</td>
								<!--
								<button class="btn btn-default" style="position: absolute; left: 51%;" onClick="javascript:Mail_Reply(<? echo $g_idxnum; ?>); return false;">確認待ちへ</button>
								</td>
								-->
								<?php
								if ($status != 8) {
								?>
									<td class="tbd_tb_c">
										<input type="text" style="display:none;" name="状態<?php echo $g_idxnum ?>" value="8">
										<button class="btn btn-default" style="position: absolute; left: 51%;" onClick="javascript:Change_Sql(<? echo $g_idxnum; ?>); return false;">確認待ちへ</button>
									</td>
								<?php
								} else {
								?>
									<td class="tbd_tb_c">
										<button class="btn btn-default" style="position: absolute;" onClick="javascript:Mail_Reply(<? echo $g_idxnum; ?>);">メール送信</button>
									</td>
									<td class="tbd_tb_c">
										<button class="btn btn-default" style="position: absolute; left:61%;" onClick="javascript:Change_Sql(<? echo $g_idxnum; ?>);">確認待ちへ</button>
									</td>
								<?php
								}
								?>
							</tr>
						</table>
						<table class="tbh">
							<tr>
								<td class="tbd_tb_r">
									<p style="text-align:right; font-size:12px;"><a href="Javascript:window.close()">閉じる</a></p>
								</td>
							</tr>
						</table>
					</div>
			<? } ?>
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