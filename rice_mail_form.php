<?
//==================================================================================================
// ■機能概要
//   ・精米倶楽部問い合わせメール返信フォーム
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
$prgname = "精米倶楽部問い合わせメール返信フォーム";
$prgmemo = "　精米倶楽部問い合わせメールの返信を行います";
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
				if(window.confirm('確認画面に移行します')){
					document.forms['frm'].action = './rice_mail_form.php?' + rowINX;
					document.forms['frm'].submit();
				}
			}
		}
		//メール返信
		function Mail_Reply(idx){
			var rowINX = 'do=reply&idxnum='+idx;
			if(window.confirm('メールを送信しますか？')){
				document.forms['frm'].action = './rice_mail_sql.php?' + rowINX;
				document.forms['frm'].submit();
			}
		}
		function Mail_Redo(idx){
			if(window.confirm('編集画面に戻りますか？')){
				document.forms['frm'].action = './rice_mail_form.php?do=redo&idxnum='+idx;
				document.forms['frm'].submit();
			}
		}
		//状態変更
		function Change_Sql(idx){
			var rowINX = 'do=change_send&idxnum='+idx;
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
		<?
		//----- データ抽出
		$query = "";
		$query .= " SELECT A.idxnum ,A.name ,A.insdt ,A.upddt ,A.ruby ,A.company,";
		$query .= " A.address1 ,A.address2 ,A.postcd1 ,A.postcd2 ,A.phonenum1 ,A.email ,B.mail_status ,";
		$query .= " B.correstaf ,B.contact";
		$query .= " FROM php_rice_mail B ";
		$query .= " LEFT OUTER JOIN php_rice_personal_info A ON A.idxnum=B.personal_idxnum ";
		$query .= " WHERE B.mail_idxnum = $g_idxnum";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) { ?>
			<table class="tbh" id= "TBL">
				<tr>
					<td class ="tbd_td_p3_l">
						<h2>名前：<? echo $row['name'] ?>（<? echo $row['ruby'] ?>）</h2>
						
					</td>
					<td class ="tbd_td_p3_c">
						<h2>
							<?
								if($row['contact'] == 'ＴＥＬ') {echo "☎";}
								else if($row['contact'] == 'メール') {echo "✉";}
								else{echo"☎/✉";}
							?>
						</h2>
					</td>
					<td class ="tbd_td_p3_l">
					</td>
				</tr>
				<? if($row['company']<>""){ ?>
					<tr>
						<td class ="tbd_td_p3_l">
							<p><font size="4"><? echo "会社名：".$row['company']; ?></font></p>
						</td>
					</tr>
				<? } ?>
			</table>
			<table class="tbh" id= "TBL">
				<tr>
					<th class = "tbd_th_p2_h">
						<p>初回問い合わせ：</p>
					</th>
					<td class = "tbd_td_p2_l">
						<p><? echo $row['insdt'] ?></p>
					</td>
					<th class = "tbd_th_p2_h">
						<p>Email：</p>
					</th>
					<td class = "tbd_td_p2_l">
						<p><? echo $row['email'] ?></p>
					</td>
					<th class = "tbd_th_p2_h">
						<p>住所：</p>
					</th>
					<td class = "tbd_td_p2_l">
						<p>〒<? echo $row['postcd1'] ."-". $row['postcd2'] ?></p>
					</td>
					<th class = "tbd_th_p2_h">
					</th>
					<td class = "tbd_td_p2_l">
					</td>
				</tr>
				<tr>
					<th class = "tbd_th_p2_h">
						<p>最終対応：</p>
					</th>
					<td class = "tbd_td_p2_l">
						<p><? echo $row['upddt'] ?></p>
					</td>
					<th class = "tbd_th_p2_h">
						<p>TEL：</p>
					</th>
					<td class = "tbd_td_p2_l">
						<p><? echo $row['phonenum1'] ?></p>
					</td>
					<th class = "tbd_th_p2_h">
					</th>
					<td class = "tbd_td_p2_l">
						<p><? echo $row['address1'] . $row['address2'] ?></p>
					</td>
					<th class = "tbd_th_p2_h">
						<p>担当者：</p>
					</th>
					<td class = "tbd_td_p2_l">
						<p><? echo $row['correstaf'] ?></p>
					</td>
				</tr>
			</table>
		<? } ?>
	</div>
	<div id="container">
		<form name="frm" method="post" enctype="multipart/form-data">
			<?
			if($_GET['do'] =="ins"){
				$query = "";
				$query .= "SELECT A.email, A.name, A.company ";
				$query .= " FROM php_rice_personal_info A ";
				$query .= " WHERE idxnum = $g_idxnum";
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs = $db->query($query))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				while ($row = $rs->fetch_array()) {?>
					<div class="row">
						<h1>コメント入力</h1>
					</div>
					<div id="main>"
						<table class="tbt">
							<tr>
								<th class="tbd_th_t_l">
									<select class="form-category" name="カテゴリー" id="category">返信内容
										<option value="未選択">未選択</option>
										<option value="コメント">コメント</option>
										<option value="メール受信">メール受信</option>
									</select>
								</th>
							</tr>
							<tr>
								<td class="tbd_tb_t_l">
									<textarea name="コメント" id="contents" rows="10" style="width:100%; font-size:12px;"></textarea>
								</td>
							</tr>
							<tr>
								<td class="tbd_tb_t_c">
									<br><button class="btn btn-default" style="position: absolute; left: 46%;" onClick="javascript:Ins_Contents(<? echo $g_idxnum; ?>); return false;">登録</button>
								</td>
							</tr>
							<tr>
								<td class="tbd_tb_t_r">
										<p style="text-align:right; font-size:12px;"><a href="Javascript:window.close()">閉じる</a></p>
								</td>
							</tr>
						</table>
						<input type="text" name="email" style="display:none;" value="<? echo $row['email']; ?>"></input>
						<input type="text" name="名前" style="display:none;" value="<? echo $row['name']; ?>"></input>
					</div>
				<? }
			}else if($_GET['do'] == "edit"){
				$query = "";
				$query .= "SELECT B.category, B.contents";
				$query .= " FROM php_rice_mail_detail B ";
				$query .= " WHERE mail_idxnum = $g_idxnum AND detail_idx=$g_mail_idx";
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs = $db->query($query))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				while ($row = $rs->fetch_array()) {?>
					<div class="row">
						<h1>コメント入力</h1>
					</div>
					<div id="main>"
						<table class="tbh">
							<tr>
								<th class="tbd_th_l">
									<select class="form-category" name="カテゴリー" id="category">
										<option>未選択</option>
										<option value="コメント" <? if($row['category'] == "コメント") echo 'selected'; ?>>コメント</option>
										<option value="メール受信" <? if($row['category'] == "メール受信") echo 'selected'; ?>>メール受信</option>
										<option value="メール送信" <? if($row['category'] == "メール送信") echo 'selected'; ?>>メール送信</option>
									</select>
								</th>
							</tr>
							<tr>
								<td class="tbd_tb_l">
									<textarea name="コメント" id="contents" rows="10" style="width:100%; font-size:12px;"><? echo $row['contents']; ?></textarea>
								</td>
							</tr>
							<tr>
								<td class="tbd_tb_c">
									<br><button class="btn btn-default" style="position: absolute; left: 46%;" onClick="javascript:Edit_Contents(<? echo $g_idxnum; ?>,<? echo $g_mail_idx; ?>); return false;">更新</button>
								</td>
							</tr>
							<tr>
								<td class="tbd_tb_r">
										<p style="text-align:right; font-size:12px;"><a href="Javascript:window.close()">閉じる</a></p>
								</td>
							</tr>
						</table>
						<input type="text" name="email" style="display:none;" value="<? echo $row['email']; ?>"></input>
					</div>
				<? }
			}else if($_GET['do'] == "reply"){
				//過去のメールの引用
				$query = "SELECT A.insdt, A.category, A.contents, A.email, A.checkflg, A.file";
				$query .= " FROM php_rice_mail_detail A ";
				$query .= " WHERE A.mail_idxnum = $g_idxnum";
				$query .= " AND A.category LIKE 'メール__'";
				$query .= " AND A.delflg = 0";
				$query .= " ORDER BY A.insdt DESC ";
				$query .= " LIMIT 0, 1 ";
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs = $db->query($query))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				$quote_flg = 0;
				$re = "";
				while ($row = $rs->fetch_array()) {
					$checkflg = $row['checkflg'];
					$contents = $row['contents'];
					$file = $row['file'];
					if($row['file'] <> ""){
						$filename = mb_substr($row['file'],11);
					}
					if($row['category'] == 'メール受信'){
						$wroteby = $row['email'];
					}else if($row['category'] == 'メール送信'){
						$wroteby = "JEMTCインフォメーションセンター";
					}
					$quote = "\n\n\nOn ".date('Y/n/j H:i', strtotime($row['insdt'])).", ".$wroteby." wrote:\n>";
					$quote .= str_replace("\n", "\n>", rtrim($row['contents'], "\n"));
					$quote .= "\n---";
					$quote_flg = 1;
					if ($checkflg == 0) {
						$re = "Re:";
					}
				}
				//テンプレート抽出
				$query = "";
				$query .= "SELECT A.title, A.contents, A.idxnum ";
				$query .= " FROM php_rice_mail_tmp A ";
				$query .= " WHERE A.delflg=0 ";
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
				$query = "";
				$query .= "SELECT A.email, A.name, A.company, A.company, B.mail_status, B.question";
				$query .= " FROM php_rice_mail B ";
				$query .= " LEFT OUTER JOIN php_rice_personal_info A ON A.idxnum=B.personal_idxnum ";
				$query .= " WHERE B.mail_idxnum = $g_idxnum";
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs = $db->query($query))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				while ($row = $rs->fetch_array()) {
					//返信定型文
					$phrase = $row['name'] . "　様\n\n";
					$phrase .= "この度は精米倶楽部についてのお問い合わせいただき誠にありがとうございます。\n";
					$phrase .= "お問合せについて回答させていただきます、JEMTCの" . $s_staff . "と申します。\n";
					$phrase .= "\n";
					$phrase .= "＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝\n";
					$phrase .= "【 お問合せ内容 】\n";
					$phrase .= "" . $row['question'] . "\n";
					$phrase .= "＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝\n";
					$phrase .= "\n";
					$phrase2 = "本件に関して、再度お問い合わせ頂く場合は、";
					$phrase2 .= "本メールに返信する形で送信お願い致します。\n";
					$sign = "\n";
					$sign .= "──────────────────────\n";
					$sign .= "日本電子機器補修協会　主食共同購入部\n";
					$sign .= "　TEL：050-5272-9665\n";
					$sign .= "　URL: https://jemtcnet.jp/kome/\n";
					$sign .= "──────────────────────\n";
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
									<input type="text" name="email" id="email" style="width:100%; font-size:12px;" value="<? echo $row['email'] ?>"></input>
								</td>
							</tr>
							<tr>
								<th class="tbd_th_l">
									<p>タイトル</p>
								</th>
							</tr>
							<tr>
								<td class="tbd_tb_l">
									<input type="text" name="件名" id="title" style="width:100%; font-size:12px;" value="<? echo $re; ?>【JEMTC】お問い合わせいただき誠にありがとうございます。※お問合せNo<? echo $g_idxnum ?>">
								</td>
							</tr>
							<tr>
								<th class="tbd_th_l">
									<br><p>本文</p>
									<? if($row['company'] <> ""){ ?>
										<p><font color="red" size="5">※法人の場合は、メール本文のはじめに法人名を入れてください。※</font></p>
									<? } ?>
									<p style="text-align:right"><font color="red">※左記の文字列は使用不可のため、削除されます。「"」,「'」,「\」</font></p>
								</th>
							</tr>
							<tr>
								<?
								$g_tmp = 0;
								foreach($arr_temp as $key => $val){
									if(isset($_POST[$val."_buttom"])){
										$g_tmp = $key;
									}
								}
								if ($quote_flg == 0) {
									if ($g_tmp > 0) {
										$honbun = $phrase.$arr_temp_contents[$arr_temp[$g_tmp]]."\n\n".$phrase2.$sign;
									} else {
										$honbun = $phrase.$phrase2.$sign;
									}
								} else if ($checkflg == 0) {
									if ($g_tmp > 0) {
										$honbun = $arr_temp_contents[$arr_temp[$g_tmp]]."\n\n".$quote.$sign;
									} else {
										$honbun = $quote.$sign;
									}
								} else {
									$honbun = $contents;
								}
								?>
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
							<tr>
								<th class="tbd_th_l">
									<br><p>添付ファイル</p>
									<?php
									if ($file != "" && $row['status'] == 8) { ?>
										<a href="<? echo $file ?>" target="_blank"><? echo $filename ?></a></br>
									<? } ?>
								</th>
							</tr>
							<tr>
								<td class="tbd_tb_l">
									<? if($row['status'] == 8){ ?>
										変更がある場合は、次画面で「確認待ちへ」ボタンを押下してください。<br>
									<? }else{ ?>
										確認画面で添付してください。<br>
									<? } ?>
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
						<input type="text" name="担当者" style="display:none;" value="<? echo $g_staff; ?>"></input>
						<input type="text" name="名前" style="display:none;" value="<? echo $row['name']; ?>"></input>
						<input type="text" name="法人名" style="display:none;" value="<? echo $row['company']; ?>"></input>
						<? if ($file != "" && $row['status'] == 8) { ?>
							<input type="text" name="アップロードファイル" style="display:none;" value="<? echo $file; ?>"></input>
						<? } ?>
					</div>
				<? }
			}else if($_GET['do'] == "redo"){
				//----- データ抽出
				$query = "";
				$query .= " SELECT A.mail_status";
				$query .= " FROM php_rice_mail A ";
				$query .= " WHERE A.mail_idxnum = $g_idxnum";
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs = $db->query($query))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				while ($row = $rs->fetch_array()) {
					$status = $row['status'];
				}
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
								<? if($_POST['法人名'] <> ""){ ?>
									<p><font color="red" size="5">※法人の場合は、メール本文のはじめに法人名を入れてください。※</font></p>
								<? } ?>
							</th>
						</tr>
						<tr>
							<td class="tbd_tb_l">
								<textarea name="本文" id="contents" rows="20" style="width:100%; font-size:12px;"><? echo $_POST['本文'] ?></textarea>
							</td>
						</tr>
						<tr>
							<th class="tbd_th_l">
								<br><p>添付ファイル</p>
							</th>
						</tr>
							<th class="tbd_th_l">
								<p>＜添付ファイル＞</p>
								<?php
								if ($status == 8 && $_POST['アップロードファイル'] <> "") {
									$filename = mb_substr($_POST['アップロードファイル'],11); ?>
									<p><a href="<? echo $_POST['アップロードファイル'] ?>" target="_blank"><? echo $filename ?></a></p>
								<? } ?>
							</th>
						</tr>
						<tr>
							<td class="tbd_tb_l">
								<? if($status == 8){ ?>
									変更がある場合は、次画面で「確認待ちへ」ボタンを押下してください。<br>
								<? } else { ?>
									確認画面で添付してください。<br>
								<? } ?>
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
					<input type="text" name="担当者" style="display:none;" value="<? echo $_POST['担当者']; ?>"></input>
					<input type="text" name="名前" style="display:none;" value="<? echo $_POST['名前']; ?>"></input>
					<input type="text" name="法人名" style="display:none;" value="<? echo $_POST['法人名']; ?>"></input>
					<?php
					if ($status == 8) {
						echo "<input type='text' name='アップロードファイル' style='display:none' value='".$_POST['アップロードファイル']."'></input>";
					}
					?>
				</div>
			<?php
			} else if($_GET['do'] == "check") { 
				//----- データ抽出
				$query = "";
				$query .= " SELECT A.mail_status";
				$query .= " FROM php_rice_mail A ";
				$query .= " WHERE A.mail_idxnum = $g_idxnum";
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs = $db->query($query))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				while ($row = $rs->fetch_array()) {
					$status = $row['status'];
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
								<?
								/*
								$main_text = $_POST['本文'];
								$main_text = str_replace("'", "", $main_text);
								$main_text = str_replace("\"", "", $main_text);
								$main_text = str_replace('"', "", $main_text);
								*/
								?>
								<td class="tbd_tb_l">
									<pre><? echo return_value($main_text) ?></pre>
									<textarea name="本文" style="display:none"><? echo return_value($main_text) ?></textarea>
								</td>
							</tr>
							<tr>
								<th class="tbd_th_l">
									<p>＜添付ファイル＞</p>
									<?php
									if ($_POST['アップロードファイル'] <> "") {
										$filename = mb_substr($_POST['アップロードファイル'],11); ?>
										<p><a href="<? echo $_POST['アップロードファイル'] ?>" target="_blank"><? echo $filename ?></a></p>
									<? } ?>
								</th>
							</tr>
							<tr>
								<td class="tbd_tb_l">
								<? if ($status <> "8") { ?>
									<input type="file" name="添付ファイル">
								<? } ?>
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
						<input type="text" name="担当者" style="display:none" value="<? echo $_POST['担当者']; ?>"></input>
						<input type="text" name="名前" style="display:none" value="<? echo $_POST['名前']; ?>"></input>
						<input type="text" name="法人名" style="display:none" value="<? echo $_POST['法人名']; ?>"></input>
						<?php
						if ($status == 8) {
							echo "<input type='text' name='アップロードファイル' style='display:none' value='".$_POST['アップロードファイル']."'></input>";
						}
						?>
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