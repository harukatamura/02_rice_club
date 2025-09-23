<?php header("Content-Type:text/html;charset=utf-8"); ?>
<?php //error_reporting(E_ALL | E_STRICT);
//==================================================================================================
// ■機能概要
//   ・予約情報更新
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

//本日日付
$today = date('YmdHis');
	
// 送信完了後に表示するページURL
$thanksPage1 = "./rice_kokyaku.php";

//対象テーブル
$table_p = "php_rice_personal_info";
$table_s = "php_rice_subscription";
$table_y = "php_rice_shipment";

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
$comm->ouputlog("==== 精米倶楽部データ更新 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);


//引数取得
$p_staff = $_COOKIE['con_perf_staff'];
$g_idx = $_GET['idx'];
$do = $_GET['do'];
$g_post = $_POST;

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
		$result = $db->query($query);
		
		if (!$result) {
			$comm->ouputlog("☆★☆文字コード指定エラー☆★☆  " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			$empty_flag = 1;
		}
		$comm->ouputlog("===文字コード指定完了===", $prgid, SYS_LOG_TYPE_DBUG);

		//データベース更新
		if($do == "update"){
			$flg = mysql_rice_update($db);
			header("Location: ".$thanksPage1."?idx=".$g_idx."&flg=".$flg);
		}
		if($do == "cancel"){
			$flg = mysql_rice_cancel($db);
			header("Location: ".$thanksPage1."?idx=".$g_idx."&flg=".$flg);
		}
		//データベース切断
		if ($result) { $dba->mysql_discon($db); }
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
//   mysql_rice_update
//
// ■概要
//   精米倶楽部顧客情報　更新
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_rice_update($db) {
	//グローバル変数
	global $comm;
	global $dba;
	//対象テーブル
	global $table_p;
	global $table_s;
	global $table_y;
	//対象プログラム
	global $prgid;
	//引数
	global $today;
	global $g_idx;
	global $p_staff;
	global $g_post;
	$query = "";
	$update = "";
	$update_p = "";
	$update_s = "";
	$update_y = "";
	
	$comm->ouputlog("mysql_rice_updateログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$collist_p = $dba->mysql_get_collist($db, $table_p);
	$collist_s = $dba->mysql_get_collist($db, $table_s);
	$collist_y = $dba->mysql_get_collist($db, $table_y);
	$arrkey_p = array("名前","電話番号1","電話番号2","郵便番号１","郵便番号２","都道府県","市区町村","町名番地","建物名", "メールアドレス");
	$arrkey_s = array("注文時備考","伝票用備考１","伝票用備考２");
	$arrkey_y = array("コース","量","金額","伝票番号","配送日","到着指定時間帯");
	//共通項目
	$query_p = "UPDATE ".$table_p;
	$query_s = "UPDATE ".$table_s;
	$query_y = "UPDATE ".$table_y;
	$update = " SET ";
	$update .= $collist_p['更新日時']."='".$today."'";
	$update .= ",".$collist_p['更新回数']."=".$collist_p['更新回数']."+1";
	$update .= ",".$collist_p['最終更新者']."='".$p_staff."'";
	
	foreach($g_post as $key => $val){
		$comm->ouputlog($key."：".$val, $prgid, SYS_LOG_TYPE_INFO);
	}
	
	foreach($arrkey_p as $val){
		if($g_post[$val] <> $g_post["T".$val]){
			$update_p .= ",".$collist_p[$val]."='".$g_post[$val]."'";
		}
	}
	foreach($arrkey_s as $val){
		if($g_post[$val] <> $g_post["T".$val]){
			$update_s .= ",".$collist_s[$val]."='".$g_post[$val]."'";
		}
	}
	
	$max_row = $g_post['最大行'];
	//配送予定
	$update_y = "";
	for($i=0; $i<$max_row; ++$i){
		foreach($arrkey_y as $val){
			if($g_post[$val.$i] <> $g_post["T".$val.$i]){
				$update_y .= ",".$collist_y[$val]."='".$g_post[$val.$i]."'";
			}
		}
		if($update_y <> ""){
			$query = $query_y.$update;
			$query .= $update_y;
			$query .= " WHERE ".$collist_y["配送インデックス"]." ='".$g_post["配送インデックス".$i]."'";
			$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			//データ追加実行
			if (! $db->query($query)) {
				$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				return 2;
			}
		}
		$query = "";
		$update_y = "";
	}
	
	if($update_p <> ""){
		//データ更新
		$query = $query_p.$update;
		$query .= $update_p;
		$query .= " WHERE ".$collist_p["インデックス"]." ='".$g_post["インデックス"]."'";
		$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		//データ追加実行
		if (! $db->query($query)) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return 2;
		}
	}
	if($update_s <> ""){
		//データ更新
		$query = $query_s.$update;
		$query .= $update_s;
		$query .= " WHERE ".$collist_s["申込インデックス"]." ='".$g_idx."'";
		$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		//データ追加実行
		if (! $db->query($query)) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return 2;
		}
	}
	
	$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return 1;
}
//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_rice_cancel
//
// ■概要
//   精米倶楽部　キャンセル登録
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_rice_cancel($db) {
	//グローバル変数
	global $comm;
	global $dba;
	//対象テーブル
	global $table_p;
	global $table_s;
	global $table_y;
	//対象プログラム
	global $prgid;
	//引数
	global $today;
	global $g_idx;
	global $p_staff;
	global $g_post;
	$query = "";
	$update = "";
	$update_p = "";
	$update_s = "";
	$update_y = "";
	
	$comm->ouputlog("mysql_rice_cancelログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//共通項目
	$_update = " UPDATE ".$table_p." A";
	$_update .= " LEFT OUTER JOIN  ".$table_s." B ON A.idxnum=B.personal_idxnum ";
	$_update .= " LEFT OUTER JOIN  ".$table_y." C ON B.subsc_idxnum=C.subsc_idxnum ";
	$_update .= " SET ";
	$_update .= " A.upddt = '".$today."'";
	$_update .= " ,A.updcount = A.updcount + 1";
	$_update .= " ,A.updstaff = '".$p_staff."'";
	$_update .= " ,A.delflg = '1' ";
	$_update .= " ,B.upddt = '".$today."'";
	$_update .= " ,B.updcount = B.updcount + 1";
	$_update .= " ,B.updstaff = '".$p_staff."'";
	$_update .= " ,B.delflg = '1' ";
	$_update .= " ,B.candt = '".$today."'";
	$_update .= " ,B.canstaff = '".$p_staff."'";
//	$_update .= " ,B.canreason = '' ";
	$_update .= " ,C.upddt = '".$today."'";
	$_update .= " ,C.updcount = C.updcount + 1";
	$_update .= " ,C.updstaff = '".$p_staff."'";
	$_update .= " ,C.delflg = '1' ";
	$_update .= " WHERE A.idxnum ='".$g_idx."'";
	$_update .= " AND C.output_flg = 0 ";
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
	if (! $db->query($_update)) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return 2;
	}
	
	$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return 1;
}

?>
