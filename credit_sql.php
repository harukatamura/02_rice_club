<?php header("Content-Type:text/html;charset=utf-8"); ?>
<?php //error_reporting(E_ALL | E_STRICT);
//==================================================================================================
// ■機能概要
//   ・STORES決済データ
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
$thanksPage1 = "./stores_credit_upload.php";

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
$comm->ouputlog("==== STORES決済データ取込 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

//グローバルIPアドレス取得
$g_ip = $_SERVER['REMOTE_ADDR'];

//引数取得
$do = $_GET['do'];
$g_post = $_POST;
$require="";
$field="";

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
		if($do == "upload"){
			$flg = mysql_credit_input($db);
			header("Location: ".$thanksPage1."?flg=".$flg);
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
//   mysql_credit_input
//
// ■概要
//   決済データ取込
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_credit_input($db) {
	//グローバル変数
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $today;
	global $g_post;
	
	$comm->ouputlog("==== mysql_credit_input 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);
	
	//データを配列に格納
	$p_list = array('店舗名', '取引内容', '決済日', '決済時間', '決済方法', '決済メモ',  '決済金額', '加盟店手数料', '手数料率', '加盟店手数料（消費税率%）', '加盟店手数料（消費税額）', '入金予定額', 'ブランド', '支払区分', 'カード番号', '取引番号', '承認番号', '担当（スタッフ名）', '業務代行');

	//テーブル項目取得
	$table = "php_stores_credit";
	$collist = $dba->mysql_get_collist($db, $table);
	
	$max_row = $g_post['max_row'];
	
	//初期値設定
	$m_query1 = "";
	$m_query2 = "";
	$m_query3 = "";
	$m_query4 = "";
	$resetflg = 0;
	
	$m_query1 .= " INSERT INTO ".$table;
	$m_query1 .= " ( ";
	foreach($p_list as $key => $val){
		if($key > 0){
			$m_query1 .= ",";
		}
		$m_query1 .=  $collist[$val];
	}
	$m_query1 .= " ) ";
	$m_query2 .= " VALUES  ";

	$m_query4 .= "ON DUPLICATE KEY UPDATE ".$collist["取引番号"].'='.$collist["取引番号"].";";
	
	
	for($i=0; $i<$max_row; ++$i){
		if($resetflg > 0){
			$m_query3 .= ",";
		}
		$m_query3 .= " ( ";
		foreach($p_list as $key => $val){
			if($key > 0){
				$m_query3 .= ",";
			}
			$m_query3 .= "'".$g_post[$val.$i]."'";
		}
		$m_query3 .= " ) ";
		++$resetflg;
		if($i > 0 && $i % 100 == 0){
			//DBに登録
			$_insert = "";
			$_insert = $m_query1.$m_query2.$m_query3.$m_query4;
			$comm->ouputlog("===データ更新ＳＱＬ===", $prgid , SYS_LOG_TYPE_DBUG);
			$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
			//データ追加実行
			if (!($rs = $db->query($_insert))) {
				$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				return 2;
			}
			$m_query3 ="";
			$resetflg = 0;
		}
	}
	//DBに登録
	$_insert = "";
	$_insert = $m_query1.$m_query2.$m_query3.$m_query4;
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid , SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
	if (!($rs = $db->query($_insert))) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return 2;
	}
	return 1;
}


?>
