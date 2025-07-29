<?php
//==================================================================================================
// ■機能概要
//   ・infoお問い合わせ詳細
//
// ■履歴
//   2019.06 バージョン更新対応 (PHP5.4.16 → PHP7.0.33)	K.Mizutani
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
	$prgname = "infoお問い合わせ詳細";
	$prgmemo = "　infoお問い合わせの詳細情報を確認をすることが可能です。。";
	$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);

	//本日日付
	$today = date('Y/m/d');
	$comm->ouputlog("today=" . $today, $prgid, SYS_LOG_TYPE_DBUG);

	//----------------------------------------------------------------------------------------------
	// 引数取得処理
	//----------------------------------------------------------------------------------------------

	//GETデータ
	$g_idxnum = $_GET['idxnum'];
	$p_Auth = $_COOKIE['con_perf_Auth'];
	$p_staff = $_COOKIE['con_perf_staff'];
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>infoメールお問い合わせ詳細</title>
  
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
		background-color: #dcdcdc;
		padding: 0px;
		text-align: center;
		width:100%;
		height:250px; 
	}
	#main{
		width: 1015px;	/*コンテンツ幅*/
		text-align: center;
		margin: auto;
		padding-top: 250px;
		padding-bottom: 200px;
		overflow: auto; 	/* コンテンツの表示を自動に設定（スクロール） */
		background-color: white;
		color:gray;
	}
	h2{
		font-size:24px;
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
	th.tbd_th_p2_c {
		width:auto;
		text-align: center;
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
	/*メインコンテンツ内テーブル*/
	table.tbm{
		margin:0px auto auto 0px;	/*上, 右, 下, 左*/
		padding:0px 10px;
		width: 870px;	/*コンテンツ幅*/
	}
	th.tbd_th_main_l{
		text-align: left;
		padding:10px 10px; /* （上下、左右） */
		margin: 0px auto;
		width:145px;
		font-weight:normal;
		font-size: 12px;
	}
	th.tbd_th_main_r{
		text-align: right;
		padding:10px 10px; /* （上下、左右） */
		margin: 0px auto;
		width:145px;
		font-weight:normal;
		font-size: 12px;
	}
	th.tbd_th_main_c{
		text-align: center;
		padding:10px 10px; /* （上下、左右） */
		margin: 0px auto;
		width:145px;
		font-weight:normal;
		font-size: 12px;
	}
	td.tbd_td_main_l{
		text-align: left;
		padding:10px 10px; /* （上下、左右） */
		margin: 0px auto;
		font-size: 12px;
	}
	td.tbd_td_main_r{
		text-align: right;
		padding:10px 10px; /* （上下、左右） */
		margin: 0px auto;
	}
	
	/*セレクトボックス*/
	.form-gray {
		overflow: hidden;
		width: 100px;
		margin: 2em auto 0px;/* 上 | 左右 | 下 */
		text-align: center;
		position: relative;
		background: #a9a9a9;
		color:white;
	}
	.form-gray select {
		cursor: pointer;
		text-overflow: ellipsis;
	}
	.form-gray select::-ms-expand {
		display: none;
	}
	.box {
		padding: 0.5em 1em;
		margin: 2em 0;
		font-weight: bold;
		background: #FFF;
		border: solid 1px gray;/*線*/
		border-radius: 10px;/*角の丸み*/
	}
	.box p {
		margin: 0; 
		padding: 0;
	}
	hr.mail{
		border-top: 1px solid #dcdcdc;
		width:725px;
	}
	
	pre{
		white-space: pre-wrap ;
	}
	.btn-flat-border {
	display: inline-block;
	padding: 0.3em 1em;
	text-decoration: none;
	background: #191970;
	color: white;
	border: solid 2px #191970;
	border-radius: 3px;
	transition: .4s;
	}
	.btn-flat-border:hover {
	color: #191970;
	background: white;
	}
</style>

	<script type="text/javascript">
		//状態変更
		function Change_Sql(idx){
			var rowINX = 'do=changedetail&idxnum='+idx;
			document.forms['frm'].action = './info_mail_sql.php?' + rowINX;
			document.forms['frm'].submit();
		}

		//返信ボタンクリック
		function Push_Reply(idx){
			var rowINX = 'do=reply&idxnum='+idx;
			window.location.href = './info_mail_form.php?' + rowINX;
		}
		//コメントボタンクリック
		function Push_Coment(idx){
			var rowINX = 'do=ins&idxnum='+idx;
			window.open('./info_mail_form.php?' + rowINX);
		}
		
		//編集ボタンクリック
		function Push_Edit(idx,m_idx){
			var rowINX = 'do=edit&idxnum='+idx+'&mail_idx='+m_idx;
			window.open('./info_mail_form.php?' + rowINX);
		}
		//削除ボタンクリック
		function Push_Delete(idx,m_idx){
			var rowINX = 'do=delete&idxnum='+idx+'&mail_idx='+m_idx;
			//確認ダイアログの表示
			if(window.confirm('削除しますか？')){
				//OKのときは実行
				document.forms['frm'].action = './info_mail_sql.php?' + rowINX;
				document.forms['frm'].submit();
			}
		}
		//法人に引き継ぎ
		function Copy_business(idx){
			var rowINX = 'do=copy_business&idxnum='+idx;
			//確認ダイアログの表示
			if(window.confirm('法人に引き継ぎますか？')){
				//OKのときは実行
				document.forms['frm'].action = './info_mail_sql.php?' + rowINX;
				document.forms['frm'].submit();
			}
		}
		//返品登録
		function henpin_toroku(idx) {
			var rowINX = 'i_idx=' + idx;
			window.open('./manual/henpin/henpin_tel_top0.php?' + rowINX);
		}
		//初期不良登録
		function huryou_toroku(idx){
			var rowINX = 'i_idx=' + idx;
			window.open('./manual/huryou/huryou_tel_top0.php?' + rowINX);
		}
	</script> 

</head>
<body>
	<div id="container">
		<div id="header">
		<?php
			//----- データ抽出
			$query = "";
			$query = $query."  SELECT A.idxnum ,A.updcount,A.name ,A.insdt ,A.upddt ,A.ruby ,A.company,";
			$query = $query."  A.address1 ,A.address2 ,A.postcd1 ,A.postcd2 ,A.phonenum ,A.email ,A.status ,";
			$query = $query."  A.correstaf ,A.question ,A.urgency ,A.kind ,A.kind_detail ,A.correcont ,A.contact";
			$query = $query."  , A.buydt, A.baddt";
			$query = $query."  FROM php_info_mail A ";
			$query = $query."  WHERE A.idxnum = $g_idxnum";
			$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (!($rs = $db->query($query))) {
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
			while ($row = $rs->fetch_array()) {
				if($row['kind'] == "操作方法"){
					$kind_detail_list = array('JMBOOK', 'WPS設定', 'クレーム', 'ネット設定', 'プリンタ設定', 'メール設定', '不具合', '付属品', '初期設定', '問い合わせ', '基本操作', '検査・修理', '返品' );
				}
				$phonenum = $row['phonenum'];
				$email = $row['email'];
		?>
			<form name="frm" method = "post">
				<table class="tbh" id= "TBL">
					<tr>
						<td class="tbd_td_p1_l">
							<select class="form-gray" id="status" name="状態" onchange="Change_Sql(<?php echo $row['idxnum'] ?>)">
								<option>状態</option>
								<option value="0" <?php if($row['status'] == SYS_STATUS_0) echo 'selected'; ?>>未連絡</option>
								<option value="2" <?php if($row['status'] == SYS_STATUS_2) echo 'selected'; ?>>対応中</option>
								<option value="8" <?php if($row['status'] == SYS_STATUS_8) echo 'selected'; ?>>確認待</option>
								<option value="9" <?php if($row['status'] == SYS_STATUS_9) echo 'selected'; ?>>完了</option>
								<option value="3" <?php if($row['status'] == SYS_STATUS_3) echo 'selected'; ?>>返信有</option>
							</select>
							<select class="form-gray" id="urgency" name="緊急度" onchange="Change_Sql(<?php echo $row['idxnum'] ?>)">
								<option>緊急度</option>
								<option <?php if($row['urgency'] === '火急') echo 'selected'; ?>>火急</option>
								<option <?php if($row['urgency'] === '早急') echo 'selected'; ?>>早急</option>
								<option <?php if($row['urgency'] === '普通') echo 'selected'; ?>>普通</option>
							</select>
							<select class="form-gray" id="state" name="内容" onchange="Change_Sql(<?php echo $row['idxnum'] ?>)">
								<option>内容</option>
								<option <?php if($row['kind'] === '会場案内') echo 'selected'; ?>>会場案内</option>
								<option <?php if($row['kind'] === '購入案内') echo 'selected'; ?>>購入案内</option>
								<option <?php if($row['kind'] === '操作方法') echo 'selected'; ?>>操作方法</option>
								<option <?php if($row['kind'] === '修理') echo 'selected'; ?>>修理</option>
								<option <?php if($row['kind'] === '返品') echo 'selected'; ?>>返品</option>
								<option <?php if($row['kind'] === 'クレーム') echo 'selected'; ?>>クレーム</option>
							</select>
							<select class="form-gray" id="state" name="種別詳細" onchange="Change_Sql(<?php echo $row['idxnum'] ?>)">
								<option>種別詳細</option>
								<? for($i=0; $i<count($kind_detail_list); ++$i){ ?>
									<option <?php if($row['kind_detail'] == $kind_detail_list[$i]){echo "selected='selected'";} ?>><? echo $kind_detail_list[$i] ?></option>
								<? } ?>
							</select>
						</td>
						<td class = "tbd_td_p1_r">
							<a href="./info_mail_hikitugi.php?idxnum=<?php echo $row['idxnum'] ?>" class="btn-flat-border"> 引継登録へ </a>
						</td>
						<td class = "tbd_td_p1_r">
							<a href="javascript:Copy_business(<?php echo $row['idxnum'] ?>)" class="btn-flat-border">  法人に引継 </a>
						</td>
						<td class = "tbd_td_p1_r">
							<a href="javascript:henpin_toroku(<?php echo $row['idxnum'] ?>)" class="btn-flat-border">返品登録</a>
						</td>
						<td class = "tbd_td_p1_r">
							<a href="javascript:huryou_toroku(<?php echo $row['idxnum'] ?>)" class="btn-flat-border">初期不良登録</a>
						</td>
						<td class = "tbd_td_p1_r">
							<p>No.<?php echo $row['idxnum'] ?></p>
						</td>
					</tr>
				</table>
				<table class="tbh" id= "TBL">
					<tr>
						<td class ="tbd_td_p3_l">
							<h2>名前：<?php echo $row['name'] ?>（<?php echo $row['ruby'] ?>）<a href="info_mail_input.php?idxnum=<? echo $row['idxnum']; ?>" target="_blank">✎</a></h2>
							
						</td>
						<td class ="tbd_td_p3_c">
							<h2>希望連絡方法：
								<?php
									if($row['contact'] == 'ＴＥＬ') {echo "☎";}
									else if($row['contact'] == 'メール') {echo "✉";}
									else{echo"☎/✉";}
								?>
							</h2>
						</td>
						<td class ="tbd_td_p3_l">
						</td>
					</tr>
					<?php if($row['company']<>""){ ?>
						<tr>
							<td class ="tbd_td_p3_l">
								<p><font size="4"><?php echo "会社名：".$row['company']; ?></font></p>
							</td>
						</tr>
					<?php } ?>
				</table>
				<table class="tbh" id= "TBL">
					<tr>
						<th class = "tbd_th_p2_h">
							<p>初回問い合わせ：</p>
						</th>
						<td class = "tbd_td_p2_l">
							<p><?php echo $row['insdt'] ?></p>
						</td>
						<th class = "tbd_th_p2_h">
							<p>Email：</p>
						</th>
						<td class = "tbd_td_p2_l">
							<p><?php echo $row['email'] ?></p>
						</td>
						<th class = "tbd_th_p2_h">
							<p>住所：</p>
						</th>
						<td class = "tbd_td_p2_l">
							<p>〒<?php echo $row['postcd1'] ."-". $row['postcd2'] ?></p>
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
							<p><?php echo $row['upddt'] ?></p>
						</td>
						<th class = "tbd_th_p2_h">
							<p>TEL：</p>
						</th>
						<td class = "tbd_td_p2_l">
							<p><?php echo $row['phonenum'] ?></p>
						</td>
						<th class = "tbd_th_p2_h">
						</th>
						<td class = "tbd_td_p2_l">
							<p><?php echo $row['address1'] . $row['address2'] ?></p>
						</td>
						<th class = "tbd_th_p2_h">
							<p>担当者：</p>
						</th>
						<td class = "tbd_td_p2_l">
							<p><?php echo $row['correstaf'] ?></p>
						</td>
					</tr>
					<tr>
						<th class = "tbd_th_p2_h">
							<p>譲渡時期：</p>
						</th>
						<td class = "tbd_td_p2_l">
							<p><?php echo $row['buydt'] ?></p>
						</td>
						<th class = "tbd_th_p2_h">
							<p>不具合時期：</p>
						</th>
						<td class = "tbd_td_p2_l">
							<p><?php echo $row['baddt'] ?></p>
						</td>
						<th class = "tbd_th_p2_h">
						</th>
						<td class = "tbd_td_p2_l">
						</td>
						<th class = "tbd_th_p2_h">
						</th>
						<td class = "tbd_td_p2_l">
						</td>
					</tr>
				</table>
				<br>
			</form>
		</div>
		<div id="main">
			<table class="tbh">
				<tr>
					<th class="tbd_th_main_c" COLSPAN = "7">
						<p style="color:red">※電話や引継で対応した場合は、コメントでログを残してください。※</p>
					</th>
				</tr>
				<tr>
					<th class="tbd_th_main_c" COLSPAN = "5">
					</th>
					<th class="tbd_th_main_c">
						<a href="javascript:Push_Reply(<?php echo $row['idxnum'] ?>)">
							<div class="box">
								<p>返信する</p>
							</div>
						</a>
					</th>
					<th class="tbd_th_main_c">
						<a href="javascript:Push_Coment(<?php echo $row['idxnum'] ?>)">
							<div class="box">
								<p>コメントする</p>
							</div>
						</a>
					</th>
				</tr>
			</table>

			<table class="tbm">
				<tr>
					<th class="tbd_th_main_l">
						初回問い合わせ内容
					</th>
					<th class="tbd_th_main_l" COLSPAN = "5">
					</th>
				</tr>
				<tr>
					<th class="tbd_th_main_r">
					</th>
					<th class="tbd_th_main_l" COLSPAN = "3">
						<p><strong><?php echo $row['name'] ?></strong>＜<?php echo $row['email'] ?>＞</p>
					</th>
					<th class="tbd_th_main_r" COLSPAN = "2">
						<p><?php echo date("Y/n/j H:i", strtotime($row['insdt'])) ?></p>
					</th>
				</tr>
				<tr>
					<td class="tbd_td_main_l">
					</td>
					<td class="tbd_td_main_l" COLSPAN = "5">
						<pre>
<?php echo $row['question'] ?>
						</pre>
					</td>
				</tr>
			</table>
			<hr>
			<?php
			}
			//----- データ抽出
			$query = "";
			$query = $query."  SELECT A.insdt, A.upddt, A.updcount, A.idxnum, A.mail_idx, A.detail_idx,";
			$query = $query."  A.name, A.email, A.correstaf, A.category, A.subject, A.contents, A.file";
			$query = $query."  FROM php_info_mail_detail A";
			$query = $query."  WHERE A.idxnum = $g_idxnum";
			$query = $query."  ORDER BY A.mail_idx DESC";
			$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//			if (! $rs = mysql_query($query, $db)) {
			if (!($rs = $db->query($query))) {
//				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
			?>
			<hr class="mail">
				<table class="tbm">
					<tr>
						<th class="tbd_th_main_r">
							<p><?php echo $row['category'] ?></p>
						</th>
						<th class="tbd_th_main_l" COLSPAN = "4">
							<?php if($row['category'] == "メール受信"){ ?>
								<p><strong><?php echo $row['name'] ?></strong>＜<?php echo $row['email'] ?>＞</p>
							<?php }else{ ?>
								<p>担当：<?php echo $row['correstaf'] ?></p>
							<?php } ?>
						</th>
						<th class="tbd_th_main_r" >
							<p><?php echo  date("Y/n/j H:i", strtotime($row['insdt'])) ?></p>
							<p><a href="javascript:Push_Edit(<?php echo $row['idxnum'] ?>,<?php echo $row['mail_idx'] ?>)">編集</a>　<a href="javascript:Push_Delete(<?php echo $row['idxnum'] ?>,<?php echo $row['mail_idx'] ?>)">削除</a></p>
						</th>
					</tr>
					<?php if($row['subject'] != ""){ ?>
					<tr>
						<td class="tbd_td_main_l">
						</td>
						<td class="tbd_td_main_l" COLSPAN = "5">
							件名：<?php echo $row['subject'] ?>
						</td>
					</tr>
					<?php } ?>
					<?php if($row['file'] <> ""){
						$p_filename = mb_substr($row['file'],11);?>
					<tr>
						<td class="tbd_td_main_l">
						</td>
						<td class="tbd_td_main_l" COLSPAN = "5">
							添付ファイル：<a href="<?php echo $row['file'] ?>" target="_blank"><?php echo $p_filename ?></a>
						</td>
					</tr>
					<?php } ?>
					<tr>
						<td class="tbd_td_main_l">
						</td>
						<td class="tbd_td_main_l" COLSPAN = "5">
							<pre>
<?php echo $row['contents'] ?>
							</pre>
						</td>
					</tr>
				</table>
			</hr>
		<?php
			//----- データ抽出
			$query = "";
			$query = $query."  SELECT B.insdt, B.upddt, B.updcount, B.idxnum, B.mail_idx, B.detail_idx,";
			$query = $query."  B.name, B.email, B.correstaf, B.category, B.subject, B.contents, B.file";
			$query = $query."  FROM php_info_mail_detail B";
			$query = $query."  WHERE B.idxnum = $g_idxnum";
			$query = $query."  ORDER BY B.mail_idx DESC";
			$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//			if (! $rs = mysql_query($query, $db)) {
			if (!($rs = $db->query($query))) {
//				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
			$i = 0;
// ----- 2019.06 ver7.0対応
//			while ($row = @mysql_fetch_array($rs)) {
			while ($row = $rs->fetch_array()) {
				$i++;
				
		?>
				<hr class="mail">
				<table class="tbm">
					<tr>
						<th class="tbd_th_main_r">
							<p><?php echo $row['category'] ?></p>
						</th>
						<th class="tbd_th_main_l" COLSPAN = "4">
							<?php if($row['category'] == "メール受信"){ ?>
								<p><strong><?php echo $row['name'] ?></strong>＜<?php echo $row['email'] ?>＞</p>
							<?php }else{ ?>
								<p>担当：<?php echo $row['correstaf'] ?></p>
							<?php } ?>
						</th>
						<th class="tbd_th_main_r" >
							<p><?php echo  date("Y/n/j H:i", strtotime($row['insdt'])) ?></p>
							<p><a href="javascript:Push_Edit(<?php echo $row['idxnum'] ?>,<?php echo $row['mail_idx'] ?>)">編集</a>　<a href="javascript:Push_Delete(<?php echo $row['idxnum'] ?>,<?php echo $row['mail_idx'] ?>)">削除</a></p>
						</th>
					</tr>
					<?php if($row['subject'] != ""){ ?>
					<tr>
						<td class="tbd_td_main_l">
						</td>
						<td class="tbd_td_main_l" COLSPAN = "5">
							件名：<?php echo $row['subject'] ?>
						</td>
					</tr>
					<?php } ?>
					<?php if($row['file'] <> ""){
						$p_filename = mb_substr($row['file'],11);?>
					<tr>
						<td class="tbd_td_main_l">
						</td>
						<td class="tbd_td_main_l" COLSPAN = "5">
							添付ファイル：<a href="<?php echo $row['file'] ?>" target="_blank"><?php echo $p_filename ?></a>
						</td>
					</tr>
					<?php } ?>
					<tr>
						<td class="tbd_td_main_l">
						</td>
						<td class="tbd_td_main_l" COLSPAN = "5">
							<pre>
<?php echo $row['contents'] ?>
							</pre>
						</td>
					</tr>
				</table>
			<?php } ?>
			<table class="tbm">
				<?php	
					$query = "";
					$query = $query."  SELECT A.insdt, A.name, A.email, A.question";
					$query = $query."  FROM php_info_mail A";
					$query = $query."  WHERE A.idxnum = $g_idxnum";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//					if (! $rs = mysql_query($query, $db)) {
					if (!($rs = $db->query($query))) {
//						$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
// ----- 2019.06 ver7.0対応
//					while ($row = @mysql_fetch_array($rs)) {
					while ($row = $rs->fetch_array()) {
				?>
				<tr>
					<th class="tbd_th_main_r">
						<p>初回問い合わせ</p>
					</th>
					<th class="tbd_th_main_l" COLSPAN = "3">
						<p><strong><?php echo $row['name'] ?></strong>＜<?php echo $row['email'] ?>＞</p>
					</th>
					<th class="tbd_th_main_r" COLSPAN = "2">
						<p><?php echo $row['insdt'] ?></p>
					</th>
				</tr>
				<tr>
					<td class="tbd_td_main_l">
					</td>
					<td class="tbd_td_main_l" COLSPAN = "5">
						<pre>
<?php echo $row['question'] ?>
						</pre>
					</td>
				</tr>
					<?php } ?>
			</table>
		</div>
	</div>
</body>

<!-- データベース切断 -->
<?php if ($result) { $dba->mysql_discon($db); } ?>
</html>