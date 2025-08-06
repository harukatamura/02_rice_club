<?php header("Content-Type:text/html;charset=utf-8"); ?>
<?php //error_reporting(E_ALL | E_STRICT);
//==================================================================================================
// ■機能概要
//   ・返品処理 更新
//
// ■履歴
//   2019.06 バージョン更新対応 (PHP5.4.16 → PHP7.0.33)	K.Mizutani
//==================================================================================================
if (version_compare(PHP_VERSION, '5.1.0', '>=')) {
	date_default_timezone_set('Asia/Tokyo');
}

//スパム防止のためのリファラチェック
$Referer_check = 0;
//リファラチェックを「する」場合のドメイン
$Referer_check_domain = "forincs.com";

// 送信確認画面の表示
$confirmDsp = 1;

// 送信完了後に自動的に指定のページ
$jumpPage = 0;

// 送信完了後に表示するページURL
$thanksPage = "./pc_failure_list.php";
$thanksPage2 = "./pc_failure_nyukin_list.php";
$thanksPage3 = "./pc_failure_send_list.php";
$thanksPage4 = "./pc_failure_send_list_ns.php";

	//対象テーブル
$table = "php_pc_failure";

// 以下の変更は知識のある方のみ自己責任でお願いします。

//--------------------------
//  関数実行、変数初期化　　
//--------------------------
//このファイルの文字コード定義
$encode = "UTF-8";

if(isset($_GET)) $_GET = sanitize($_GET);
if(isset($_POST)) $_POST = sanitize($_POST);
if(isset($_COOKIE)) $_COOKIE = sanitize($_COOKIE);
if($encode == 'SJIS') $_POST = sjisReplace($_POST,$encode);

//変数初期化
$sendmail = 0;
$empty_flag = 0;
$post_mail = '';
$errm ='';
$header ='';

//外部ファイル取り込み
require_once("./lib/define.php");
require_once("./lib/comm.php");

//オブジェクト生成
$comm = new comm();

//実行プログラム名取得
$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
$comm->ouputlog("==== 販売実績 更新 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

//引数取得
$g_idx=$_GET['idxnum'];
$g_row=$_GET['row'];
$g_category=$_GET['category'];
$comm->ouputlog("idx=" . $g_idx, $prgid, SYS_LOG_TYPE_DBUG);
$comm->ouputlog("category=" . $g_category, $prgid, SYS_LOG_TYPE_DBUG);
$g_post=$_POST;

foreach($g_post as $key=>$val) {
	$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_DBUG);
}

//操作担当者
$g_staff = $_COOKIE['con_perf_staff'];

$require="";
$field="";

//カテゴリ別情報設定
//　0:返品取消
//　1:口座情報エラー
//　2:入金済み
//　3:ハガキ発送済み
//　4:ハガキ発送済み（まとめて）
//　5:メール送信
//　6:メール送信（まとめて）

if($empty_flag != 1){
	//--------------------------
	//  ＤＢ更新　　
	//--------------------------
	//外部ファイル取り込み
	require_once("./lib/dbaccess.php");

	date_default_timezone_set('Asia/Tokyo');

	//オブジェクト生成
	$dba = new dbaccess();

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);

	//処理実施
	if ($result) {
		// 文字コード指定
		$comm->ouputlog("===文字コード指定===", $prgid, SYS_LOG_TYPE_DBUG);
		$query = ' set character_set_client = utf8';
// ----- 2019.06 ver7.0対応
//		$result = mysql_query($query, $db);
		$result = $db->query($query);
		if (!$result) {
// ----- 2019.06 ver7.0対応
//			$comm->ouputlog("☆★☆文字コード指定エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
			$comm->ouputlog("☆★☆文字コード指定エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			$empty_flag = 1;
		}
		$comm->ouputlog("===文字コード指定完了===", $prgid, SYS_LOG_TYPE_DBUG);

		//　0:返品取消
		if ($g_category == 0) {
			//実績更新
			mysql_del($db);
		}
		//　1:口座情報エラー
		elseif ($g_category == 1) {
			//実績更新
			mysql_accountunm_err($db);
		}
		//　2:入金済み
		elseif ($g_category == 2) {
			//実績更新
			mysql_nyukin($db);
		}
		//　3:ハガキ発送済み
		elseif ($g_category == 3) {
			//実績更新
			mysql_send($db);
		}
		//　4:ハガキ発送済み（まとめて）
		elseif ($g_category == 4) {
			//実績更新
			mysql_send_all($db);
		}
		//　5:メール送信
		elseif ($g_category == 5) {
			//実績更新
			mysql_send_mail($db);
		}
		//　6:メール送信（まとめて）
		elseif ($g_category == 6) {
			//実績更新
			mysql_send_all_mail($db);
		}
		//データベース切断
		if ($result) { $dba->mysql_discon($db); }
	}
	//　0:返品取消
	if ($g_category == 0) {
		header("Location: ".$thanksPage);
	}
	//　1:口座情報エラー
	elseif ($g_category == 1) {
		header("Location: ".$thanksPage2);
	}
	//　2:入金済み
	elseif ($g_category == 2) {
		header("Location: ".$thanksPage2);
	}
	//　3:ハガキ発送済み
	elseif ($g_category == 3 || $g_category == 4) {
		header("Location: ".$thanksPage3);
	}
	//　5:メール送信済み
	elseif ($g_category == 5 || $g_category == 6) {
		header("Location: ".$thanksPage4);
	}
}

?>

<?php
//----------------------------------------------------------------------
//  関数定義(START)
//----------------------------------------------------------------------
function h($string) {
	global $encode;
	return htmlspecialchars($string, ENT_QUOTES,$encode);
}
function sanitize($arr){
	if(is_array($arr)){
		return array_map('sanitize',$arr);
	}
	return str_replace("\0","",$arr);
}

//全角→半角変換
function zenkaku2hankaku($key,$out,$hankaku_array){
	global $encode;
	if(is_array($hankaku_array) && function_exists('mb_convert_kana')){
		foreach($hankaku_array as $hankaku_array_val){
			if($key == $hankaku_array_val){
				$out = mb_convert_kana($out,'a',$encode);
			}
		}
	}
	return $out;
}
//配列連結の処理
function connect2val($arr){
	$out = '';
	foreach($arr as $key => $val){
		if($key === 0 || $val == ''){//配列が未記入（0）、または内容が空のの場合には連結文字を付加しない（型まで調べる必要あり）
			$key = '';
		}elseif(strpos($key,"円") !== false && $val != '' && preg_match("/^[0-9]+$/",$val)){
			$val = number_format($val);//金額の場合には3桁ごとにカンマを追加
		}
		$out .= $val . $key;
	}
	return $out;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_del
//
// ■概要
//   データ削除
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_del( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_idx;
	global $g_post;
	$comm->ouputlog("mysql_delログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//
	$tbl_name_ = "";

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);

	//データ更新
	$query = "";
	$comm->ouputlog("===データ更新===", $prgid, SYS_LOG_TYPE_INFO);
	$query = "UPDATE " . $table . " set ";
	//更新日時
	$query .=$collist["更新日時"] . "=" . sprintf("'%s'", date('YmdHis'));
	//更新回数
	$query .= "," . $collist["更新回数"] . "=" . $collist["更新回数"] . "+1";
	//削除フラグ
	$query .= "," . $collist["削除フラグ"] . "= 1";
	//検索条件
	$query .= " where idxnum = " . $g_idx;		//問合せＮｏ
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);

	//データ追加実行
// ----- 2019.06 ver7.0対応
//	if (! mysql_query($query, $db)) {
	if (! $db->query($query)) {
//		$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	$comm->ouputlog("===データ更新完了===", $prgid, SYS_LOG_TYPE_DBUG);

	return true;
}
//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_accountunm_err
//
// ■概要
//   データ削除
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_accountunm_err( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_idx;
	global $g_post;
	$comm->ouputlog("mysql_accountunm_errログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//
	$tbl_name_ = "";

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);

	//データ更新
	$query = "";
	$comm->ouputlog("===データ更新===", $prgid, SYS_LOG_TYPE_INFO);
	$query = "UPDATE " . $table . " set ";
	//更新日時
	$query .=$collist["更新日時"] . "=" . sprintf("'%s'", date('YmdHis'));
	//更新回数
	$query .= "," . $collist["更新回数"] . "=" . $collist["更新回数"] . "+1";
	//削除フラグ
	$query .= "," . $collist["問合せ状況"] . "= " . SYS_STATUS_8;
	//検索条件
	$query .= " where idxnum = " . $g_idx;		//問合せＮｏ
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);

	//データ追加実行
// ----- 2019.06 ver7.0対応
//	if (! mysql_query($query, $db)) {
	if (! $db->query($query)) {
//		$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	$comm->ouputlog("===データ更新完了===", $prgid, SYS_LOG_TYPE_DBUG);

	return true;
}
//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_nyukin
//
// ■概要
//   データ削除
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_nyukin( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//操作担当者
	global $g_staff;
	//引数
	global $g_idx;
	global $g_post;
	$comm->ouputlog("mysql_nyukinログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//
	$tbl_name_ = "";

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);

	//データ更新
	$query = "";
	$comm->ouputlog("===データ更新===", $prgid, SYS_LOG_TYPE_INFO);
	$query = "UPDATE " . $table . " set ";
	//更新日時
	$query .=$collist["更新日時"] . "=" . sprintf("'%s'", date('YmdHis'));
	//更新回数
	$query .= "," . $collist["更新回数"] . "=" . $collist["更新回数"] . "+1";
	//削除フラグ
	$query .= "," . $collist["問合せ状況"] . "= " . SYS_STATUS_9;
	//返金日
	$query .= "," . $collist["返金日"] . "= " . sprintf("'%s'", date('YmdHis'));
	//入金者
	$query .= "," . $collist["入金者"] . "= " . sprintf("'%s'", $g_staff);
	//検索条件
	$query .= " where idxnum = " . $g_idx;		//問合せＮｏ
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);

	//データ追加実行
// ----- 2019.06 ver7.0対応
//	if (! mysql_query($query, $db)) {
	if (! $db->query($query)) {
//		$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	$comm->ouputlog("===データ更新完了===", $prgid, SYS_LOG_TYPE_DBUG);

	return true;
}
//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_send
//
// ■概要
//   ハガキ発送
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_send( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//操作担当者
	global $g_staff;
	//引数
	global $g_idx;
	global $g_post;
	$comm->ouputlog("mysql_sendログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//
	$tbl_name_ = "";

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);

	//データ更新
	$query = "";
	$comm->ouputlog("===データ更新===", $prgid, SYS_LOG_TYPE_INFO);
	$query = "UPDATE " . $table . " set ";
	//更新日時
	$query .=$collist["更新日時"] . "=" . sprintf("'%s'", date('YmdHis'));
	//更新回数
	$query .= "," . $collist["更新回数"] . "=" . $collist["更新回数"] . "+1";
	//削除フラグ
	$query .= "," . $collist["問合せ状況"] . "= " . SYS_STATUS_9;
	//検索条件
	$query .= " where idxnum = " . $g_idx;		//問合せＮｏ
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);

	//データ追加実行
// ----- 2019.06 ver7.0対応
//	if (! mysql_query($query, $db)) {
	if (! $db->query($query)) {
//		$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	$comm->ouputlog("===データ更新完了===", $prgid, SYS_LOG_TYPE_DBUG);

	return true;
}
//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_send_all
//
// ■概要
//   ハガキ発送完了（まとめて）
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_send_all( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//操作担当者
	global $g_staff;
	//引数
	global $g_idx;
	global $g_post;
	$comm->ouputlog("mysql_send_allログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//
	$tbl_name_ = "";
	//変数初期化
	$check = 0;
	$idx = 0;
	$dataCnt = 1;

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);

	foreach($g_post as $key=>$val) {
		if($key == "end".$dataCnt) {
			$comm->ouputlog("dataCnt = ".$dataCnt." check = ".$check. " idx=".$idx, $prgid, SYS_LOG_TYPE_INFO);
			if($check == 1){
				$idx_list[] = $idx;
			}
			//変数初期化
			$check = 0;
			$idx = 0;
			++$dataCnt;
		}else{
			if(isset($_POST["send_check".$dataCnt])){
				$check = 1;
			}if($key == "インデックス".$dataCnt) {
				$idx = $val;
			}
		}
	}
	for($i=0; $i<count($idx_list); ++$i){
		//データ更新
		$query = "";
		$comm->ouputlog("===データ更新===", $prgid, SYS_LOG_TYPE_INFO);
		$query = "UPDATE " . $table . " set ";
		//更新日時
		$query .=$collist["更新日時"] . "=" . sprintf("'%s'", date('YmdHis'));
		//更新回数
		$query .= "," . $collist["更新回数"] . "=" . $collist["更新回数"] . "+1";
		//削除フラグ
		$query .= "," . $collist["問合せ状況"] . "= " . SYS_STATUS_9;
		//検索条件
		$query .= " where idxnum = " . $idx_list[$i];	//問合せＮｏ
		$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		//データ追加実行
		if (! $db->query($query)) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return false;
		}
	}
	$comm->ouputlog("===データ更新完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_send
//
// ■概要
//   メール送信
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_send_mail( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//操作担当者
	global $g_staff;
	//引数
	global $g_idx;
	global $g_row;
	global $g_post;
	$comm->ouputlog("mysql_send_mailログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//
	$tbl_name_ = "";

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);

	$idx = $g_post["インデックス".$g_row];
	//データ更新
	$query = "";
	$comm->ouputlog("===データ更新===", $prgid, SYS_LOG_TYPE_INFO);
	$query = "UPDATE " . $table . " set ";
	//更新日時
	$query .=$collist["更新日時"] . "=" . sprintf("'%s'", date('YmdHis'));
	//更新回数
	$query .= "," . $collist["更新回数"] . "=" . $collist["更新回数"] . "+1";
	//削除フラグ
	$query .= "," . $collist["問合せ状況"] . "= " . SYS_STATUS_9;
	//検索条件
	$query .= " where idxnum = " . $idx;		//問合せＮｏ
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);

	$email = $g_post["メールアドレス".$g_row];
	$name = $g_post["お名前".$g_row];
	$comm->ouputlog("お名前 = ".$name." メールアドレス = ".$email. " インデックス=".$idx, $prgid, SYS_LOG_TYPE_INFO);
	//データ追加実行
	if (! $db->query($query)) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	$comm->ouputlog("===データ更新完了===", $prgid, SYS_LOG_TYPE_DBUG);
	require_once(dirname(_FILE_).'/send_henkin_mail.php');
	
	return true;
}
//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_send_all_mail
//
// ■概要
//   メール送信（まとめて）
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_send_all_mail( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//操作担当者
	global $g_staff;
	//引数
	global $g_idx;
	global $g_post;
	$comm->ouputlog("mysql_send_all_mailログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//
	$tbl_name_ = "";
	//変数初期化
	$check = 0;
	$idx = 0;
	$dataCnt = 1;

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);

	foreach($g_post as $key=>$val) {
		if($key == "end".$dataCnt) {
			$comm->ouputlog("dataCnt = ".$dataCnt." check = ".$check. " idx=".$idx, $prgid, SYS_LOG_TYPE_INFO);
			if($check == 1){
				$idx_list[] = $idx;
				$comm->ouputlog("お名前 = ".$name." メールアドレス = ".$email. " インデックス=".$idx, $prgid, SYS_LOG_TYPE_INFO);
				require(dirname(_FILE_).'/send_henkin_mail.php');
			}
			//変数初期化
			$check = 0;
			$idx = 0;
			++$dataCnt;
		}else{
			if(isset($_POST["send_check".$dataCnt])){
				$check = 1;
			}if($key == "インデックス".$dataCnt) {
				$idx = $val;
			}if($key == "メールアドレス".$dataCnt) {
				$email = $val;
			}if($key == "お名前".$dataCnt) {
				$name = $val;
			}
		}
	}
	for($i=0; $i<count($idx_list); ++$i){
		//データ更新
		$query = "";
		$comm->ouputlog("===データ更新===", $prgid, SYS_LOG_TYPE_INFO);
		$query = "UPDATE " . $table . " set ";
		//更新日時
		$query .=$collist["更新日時"] . "=" . sprintf("'%s'", date('YmdHis'));
		//更新回数
		$query .= "," . $collist["更新回数"] . "=" . $collist["更新回数"] . "+1";
		//削除フラグ
		$query .= "," . $collist["問合せ状況"] . "= " . SYS_STATUS_9;
		//検索条件
		$query .= " where idxnum = " . $idx_list[$i];	//問合せＮｏ
		$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		//データ追加実行
		if (! $db->query($query)) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return false;
		}
	}
	$comm->ouputlog("===データ更新完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return true;
}

//----------------------------------------------------------------------
//  関数定義(END)
//----------------------------------------------------------------------
?>
