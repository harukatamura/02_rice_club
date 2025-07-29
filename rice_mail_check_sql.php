<?php header("Content-Type:text/html;charset=utf-8"); ?>
<?php //error_reporting(E_ALL | E_STRICT);
//==================================================================================================
// ■機能概要
//   ・精米倶楽部メール情報 更新
//==================================================================================================
if (version_compare(PHP_VERSION, '5.1.0', '>=')) {
	date_default_timezone_set('Asia/Tokyo');
}

//外部ファイル取り込み
require_once("./lib/define.php");
require_once("./lib/comm.php");
require_once("./lib/dbaccess.php");
date_default_timezone_set('Asia/Tokyo');

//引数取得
$p_idx = $_POST['idx'];

//オブジェクト生成
$dba = new dbaccess();
$comm = new comm();

//データベース接続
$db = "";
$result = $dba->mysql_con($db);
//処理実施
$i = 0;
$query = "SELECT A.mail_status, A.correstaf ";
$query .= " FROM php_rice_mail A";
$query .= " WHERE A.mail_idxnum = ". sprintf("'%s'", $p_idx);
$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
if (!($rs = $db->query($query))) {
	$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
}
while ($row = $rs->fetch_array()) {
	$status = $row['status'];
	$staff = $row['correstaf'];
}
$re = [$status,$staff];
echo json_encode($re);
if ($result) { $dba->mysql_discon($db); }?>