<?php
//==================================================================================================
// ■機能概要
//   ・テスト画面
//
// ■履歴
//   2019.06 バージョン更新対応 (PHP5.4.16 → PHP7.0.33)	K.Mizutani
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

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);

	//POSTデータ
	$jyokyo_pldn = $_POST['jyokyo_pldn'];
	$kensaku_pldn = $_POST['kensaku_pldn'];
	$kensaku = $_POST['kensaku'];
	$narabikae_pldn = $_POST['narabikae_pldn'];
	$jyunban_pldn = $_POST['jyunban_pldn'];
	$komoku_btn = $_POST['komoku_btn'];

	//担当者
	$p_staff = $_COOKIE['con_perf_staff'];
	//会社
	$p_compcd = $_COOKIE['con_perf_compcd'];
	
	if (isset($_POST['companycd'])) {
		$compcd = $_POST['companycd'];
	} else {
		$compcd = $p_compcd;
	}
?>

<!--------------------------------------------------------------------------------------------------
	コンテンツ表示
---------------------------------------------------------------------------------------------------->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<meta charset="UTF-8">
<head>
	<title>返品情報</title>
	<link rel="stylesheet" href="css/bootstrap.css">
	<!--sweetalert2-->
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@8"></script>
	<style type="text/css">
	.btn-flat-border {
	display: inline-block;
	padding: 0.3em 1em;
	text-decoration: none;
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
	<script type="text/javascript">
		//顧客詳細 選択ボタン
		function Push_Send(row){
///			var myTbl = document.getElementById('TBL');
//			var Cells=myTbl.rows[Cell.parentNode.rowIndex].cells[0]; 
			//画面項目設定
//			var rowINX = 'idxnum='+Cells.innerHTML;
			var rowINX = 'row='+row;
			document.forms['key_h'].action = './pc_failure_sql.php?category=5&' + rowINX;
			document.forms['key_h'].submit();
		}
		//すべてに発送完了チェックボタン
		function Send_all(ischecked){
			//データ数を取得
			var row_num = document.forms['key_h'].elements['row_num'].value;
			if(ischecked == false) {
				for(i=1; i<=row_num; ++i){
					document.forms['key_h'].elements['send_check'+i].checked = false;
				}
			}else{
				for(i=1; i<=row_num; ++i){
					document.forms['key_h'].elements['send_check'+i].checked = true;
				}
			}
		}
		//すべて発送完了ボタン
		function Push_Send_All(Cell){
			//画面項目設定
			document.forms['key_h'].action = './pc_failure_sql.php?category=6';
			document.forms['key_h'].submit();
		}
	</script>
</head>

<body>
<!-- ヘッダー -->
<header>
	<script>header();</script>
</header>
<!-- 本文 -->
<div class="container">
	<div class="row">
		<h1>返品一覧</h1>
	</div>
	<div class="bs-component">
		<h2 id="navbar">メール送信対象一覧</h2>
		<hr>
		<!--検索対象選択プルダウン-->
		<form action="pc_failure_send_list.php" method="post">
		<!--
		<div>
			<input type="submit" name="komoku_btn" value="返品" class="btn_r btn-default">
			<input type="submit" name="komoku_btn" value="初期不良" class="btn_r btn-default">
		</div>
		-->
		<div class="col-lg-12">
			<div class="form-group">
				<div class="from_back">
					<input type="radio" name="companycd" id="select1" value="M" <?php if ($compcd == "M") {echo "checked";} ?>><label for="select1">MSO</label>
					<input type="radio" name="companycd" id="select2" value="Z" <?php if ($compcd <> "M") {echo "checked";} ?>><label for="select2">JEMTC</label>
				</div>
			</div>
		</div>
		<br><br><br><br>
		<div>
			<select name="kensaku_pldn" >
				<option value="idxnum" <?php if ($kensaku_pldn == "idxnum"){echo "selected";} ?>>受付NO.</option>
				<option value="name" <?php if ($kensaku_pldn == "name"){echo "selected";} ?>>ふりがな</option>
			</select>
			<input name="kensaku" value=<?php echo $_POST['kensaku'] ?>>
			<input type="submit" name="kensaku_btn" value="検索" class="btn btn-default">
		</div>
		<br>
		<div>
			<?php
				//----- データ抽出
				$query = "SELECT FORMAT(SUM(A.bkcash), 0) as sum_cash";
				$query = $query."  FROM php_pc_failure A ";
				$query = $query."WHERE A.status =" . SYS_STATUS_7;
				$query = $query."  AND A.delflg = 0 ";
				//会社コード
				if ($compcd == "M") {
					$query = $query."  AND A.companycd = 'M'";
				} else {
					$query = $query."  AND A.companycd <> 'M'";
				}
				if(isset($_POST['kensaku'])){
					//----- 検索
					$kensaku = $_POST['kensaku'];
					if($kensaku_pldn == 'idxnum' ){
						$query = $query."AND A.idxnum LIKE '%$kensaku%'";
					} elseif($kensaku_pldn == 'name' ){
						$query = $query."AND A.ruby LIKE '%$kensaku%'";
					}
				}
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs = $db->query($query))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				$sum_cash = 0;
				while ($row = $rs->fetch_array()) {
					$sum_cash = $row['sum_cash'];
				}
			?>
		</div>
		</form>
		<!-- 顧客情報 -->
		<form name="key_h" method="post">
		<div>
			<table id="TBL" class="table table-striped table-hover">
				<tr class="success">
					<th>最終更新日</th>
					<th>受付NO.</th>
					<th>メールアドレス</th>
					<th>お名前</th>
					<th></th>
					<th>
						<input type="checkbox" onclick="Javascript:Send_all(this.checked)">
					</th>
				</tr>
				<?php
					//----- データ抽出
					$query = "SELECT A.idxnum , A.upddt, A.postcd1, A.postcd2 , A.address1, A.address2, A.address3 , A.name, B.mailaddress ";
					$query = $query."  FROM php_pc_failure A ";
					$query = $query."  LEFT OUTER JOIN ( ";
					$query = $query."  SELECT t_idx, mailaddress FROM php_telorder__ ";
					$query = $query."  WHERE mailaddress <> '' ";
					$query = $query."  AND delflg=0 ";
					$query = $query."  GROUP BY t_idx ";
					$query = $query."  ) B ON A.tel_idx=B.t_idx ";
					$query = $query." WHERE A.status =" . SYS_STATUS_10;
					$query = $query."  AND A.delflg = 0 ";
					$query = $query."  AND A.tel_idx <> '' ";
					$query = $query."  AND A.staff = 'NS' ";
					$query = $query."  AND B.mailaddress <> '' ";
					//会社コード
					if ($compcd == "M") {
						$query = $query."  AND A.companycd = 'M'";
					} else {
						$query = $query."  AND A.companycd <> 'M'";
					}
					if(isset($_POST['kensaku'])){
						//----- 検索
						$kensaku = $_POST['kensaku'];
						if($kensaku_pldn == 'idxnum' ){
							$query = $query."AND A.idxnum LIKE '%$kensaku%'";
						} elseif($kensaku_pldn == 'name' ){
							$query = $query."AND A.ruby LIKE '%$kensaku%'";
						}
					}
					$query = $query." ORDER BY A.idxnum ";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($query))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$i = 0;
					while ($row = $rs->fetch_array()) {
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
						$i++;
				?>
					<tr class="danger">
						<!-- インデックス -->
						<td style="display:none;"><?php echo $row['idxnum']; ?></td>
						<!-- 最終更新日 -->
						<td style="text-align:center;"><?php echo date('Y/m/d' , strtotime($row['upddt'])) ?></td>
						<!-- インデックス -->
						<td style="text-align:center;"><?php echo str_pad($row['idxnum'], 6, "0", STR_PAD_LEFT) ?></td>
						<!-- メールアドレス -->
						<td><?php echo $row['mailaddress'] ?></td>
						<!-- お名前 -->
						<td><?php echo $row['name'] ?></td>
						<!-- 削除 -->
						<td onClick="javascript:Push_Send(<? echo $i ?>)" style="text-align: center;">
							<a href="Javascript:Push_Send(<? echo $i ?>)" class="btn-flat-border">ﾒｰﾙ送信</a>
						</td>
						<!-- まとめて削除 -->
						<td><label style="display:block;width:100%;height:100%;"><input type="checkbox" name="send_check<? echo $i ?>"></label></td>
						<!-- 取込用 -->
						<td style="display:none">
							<input type="text" name="インデックス<? echo $i ?>" value="<? echo $row['idxnum'] ?>">
							<input type="text" name="メールアドレス<? echo $i ?>" value="<? echo $row['mailaddress'] ?>">
							<input type="text" name="お名前<? echo $i ?>" value="<? echo $row['name'] ?>">
							<input type="text" name="end<? echo $i ?>">
						</td>
					</tr>
				<?php }
					?>
			</table>
			<input type="text" name="row_num" value="<? echo $i ?>" style="display:none">
			<table id="TBL" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
				<tr>
					<td class="button">
						<a href="Javascript:Push_Send_All(this)" class="btn-flat-border">ﾒｰﾙ一括送信</a>
					</td>
				</tr>
			</table>
		</div>
		<!-- ページ数 -->
		<div class="page">

		</div>
	</form>
	</div>	
</body>

<!-- データベース切断 -->
<?php if ($result) { $dba->mysql_discon($db); } ?>

<script src="../js/jquery.min.js"></script>
<script src="../js/bootstrap.min.js"></script>

<script type="text/javascript">
  $('.bs-component [data-toggle="popover"]').popover();
  $('.bs-component [data-toggle="tooltip"]').tooltip();
</script>

</html>
