<?php header("Content-Type:text/html;charset=utf-8"); ?>
<?php //error_reporting(E_ALL | E_STRICT);
//==================================================================================================
// ■機能概要
//   ・infoメール情報 更新
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
$thanksPage1 = "./info_mail.php";
$thanksPage2 = "./info_mail_detail.php";
$thanksPage3 = "./info_mail_form.php";
$thanksPage4 = "./info_mail_input.php";
$thanksPage5 = "./rice_mail_temp_edit.php";
$thanksPage6 = "./info_mail_temp_list.php";

	//対象テーブル
$table = "php_info_mail";
$table_detail = "php_info_mail_detail";

//入力担当者(COOKIEを利用)
$g_staff = $_COOKIE['con_perf_staff'];

//本日日付
$today = date('YmdHis');

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
$comm->ouputlog("==== infoメール情報 更新 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

//引数取得
$g_do=$_GET['do'];
$g_post = $_POST;
$comm->ouputlog("do=" . $g_do, $prgid, SYS_LOG_TYPE_DBUG);

$g_idxnum=$_GET['idxnum'];
$comm->ouputlog("idxnum=" . $g_idxnum, $prgid, SYS_LOG_TYPE_DBUG);

$g_post = $_POST;

//一覧
if ( $g_do == 'changetop'){
	$g_status=$_POST['状態'.$g_idxnum];
	$g_urgency=$_POST['緊急度'.$g_idxnum];
	$g_kind=$_POST['内容'.$g_idxnum];
	$g_correstaf=$_POST['担当者'.$g_idxnum];
	$g_kind_detail=$_POST['種別詳細'.$g_idxnum];
	$comm->ouputlog("====一覧から取得====", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog("d_status=" . $d_status, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("d_urgency=" . $d_urgency, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("d_kind=" . $d_kind, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("g_kind_detail=" . $g_kind_detail, $prgid, SYS_LOG_TYPE_DBUG);
}

//詳細ページの状態が変更された時
if ( $g_do == 'changedetail'){
	$d_status=$_POST['状態'];
	$d_urgency=$_POST['緊急度'];
	$d_kind=$_POST['内容'];
	$d_kind_detail=$_POST['種別詳細'];
	$comm->ouputlog("====詳細から取得====", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog("g_status=" . $g_status, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("g_urgency=" . $g_urgency, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("g_kind=" . $g_kind, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("g_correstaf=" . $g_correstaf, $prgid, SYS_LOG_TYPE_DBUG);
}

//コメントの登録ボタンが押された時
if ( $g_do == 'inscontents'){
	$g_contents=$_POST['コメント'];
	$g_category=$_POST['カテゴリー'];
	$g_email=$_POST['email'];
	$g_name=$_POST['名前'];
	$g_mail_idx=1;
	$comm->ouputlog("====コメント登録から取得====", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog("g_contents=" . $g_contents, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("g_category=" . $g_category, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("g_name=" . $g_name, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("g_email=" . $g_email, $prgid, SYS_LOG_TYPE_DBUG);
}

//コメントの更新ボタンが押された時
if ( $g_do == 'editcontents'){
	$g_contents=$_POST['コメント'];
	$g_category=$_POST['カテゴリー'];
	$g_email=$_POST['email'];
	$c_mail_idx=$_GET['mail_idx'];
	$comm->ouputlog("====コメント更新から取得====", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog("g_contents=" . $g_contents, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("g_category=" . $g_category, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("g_email=" . $g_email, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("mail_idx=" . $c_mail_idx, $prgid, SYS_LOG_TYPE_DBUG);
}

//メール返信ボタンが押された時
if ( $g_do == 'reply' || $g_do == 'change_send'){
	$m_title=$_POST['件名'];
	$m_contents=$_POST['本文'];
	$m_category="メール送信";
	$m_staff=$_POST['担当者'];
	$m_email=$_POST['email'];
	$m_name=$_POST['名前'];
	$m_mail_idx=1;
	//添付ファイルを保存
	if ($_POST['アップロードファイル'] != "") {
		$tmpfire = $_POST['アップロードファイル'];
		$m_file = $_POST['アップロードファイル'];
	} else {
		$tmpfile = $_FILES["添付ファイル"]["tmp_name"];
		if(is_uploaded_file($tmpfile)){
			$m_file = "./mail_pdf/".$_FILES["添付ファイル"]["name"];
			$comm->ouputlog("添付ファイルあり", $prgid, SYS_LOG_TYPE_INFO);
			$fileinfo = finfo_open(FILEINFO_MIME_TYPE);
			$mimeType = finfo_file($fileinfo, $tmpfile);
			finfo_close($fileinfo);
			if (move_uploaded_file($tmpfile, $m_file)) {
				$comm->ouputlog("添付ファイル登録", $prgid, SYS_LOG_TYPE_INFO);
				chmod($m_file, 0644);
			} else {
				$comm->ouputlog("添付ファイルファイルのアップロードに失敗しました。", $prgid, SYS_LOG_TYPE_INFO);
				return false;
			}
		}
	}
	$comm->ouputlog("====メール返信から取得====", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog("m_title=" . $m_title, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("m_contents=" . $m_contents, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("m_category=" . $m_category, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("m_staff=" . $m_staff, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("m_email=" . $m_email, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("m_name=" . $m_name, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("m_mail_idx=" . $m_mail_idx, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("tmpfile=" . $tmpfile, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("m_file=" . $m_file, $prgid, SYS_LOG_TYPE_DBUG);
}

//削除ボタンが押された時
if ( $g_do == 'delete'){
	$p_mail_idx=$_GET['mail_idx'];
	$comm->ouputlog("====削除ボタンから取得====", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog("mail_idx=" . $p_mail_idx, $prgid, SYS_LOG_TYPE_DBUG);
}

//連絡不通ボタンが押された時
if ($g_do == 'tabsence') {
	$g_category=$_POST['カテゴリー'];
	$g_email=$_POST['email'];
	$g_name=$_POST['名前'];
	$g_mail_idx=1;
	$comm->ouputlog("g_category=" . $g_category, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("g_name=" . $g_name, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("g_email=" . $g_email, $prgid, SYS_LOG_TYPE_DBUG);
}
//引き継ぎ登録がされたとき
if($g_do == "hikitugi"){
	$hikitugi_idx=$_GET['hikitugi_idx'];
}
//テンプレートの登録・修正・削除
if($g_do == "ins_temp" || $g_do == "upd_temp" || $g_do == "del_temp"){
	$g_post = $_POST;
}
//チェックページからの移送はチェックページに戻る
if($_GET['page'] == "check"){
	$thanksPage1 = "./info_mail_checklist.php";
}

//カテゴリ別情報設定
//　1:infoメール更新
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
			$comm->ouputlog("☆★☆文字コード指定エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			$empty_flag = 1;
		}
		$comm->ouputlog("===文字コード指定完了===", $prgid, SYS_LOG_TYPE_DBUG);

		//一覧リストの状態が変更された時
		if ( $g_do == 'changetop'){
			mysql_upd_info_mail($db);
			header("Location: ".$thanksPage1);
		}
		
		//詳細ページの状態が変更された時
		if ( $g_do == 'changedetail'){
			mysql_upd_info_mail_detail($db);
			header("Location: ".$thanksPage2."?idxnum=".$g_idxnum);
		}
		//コメントの登録ボタンが押された時
		if ( $g_do == 'inscontents'){
			contents_ins($db);
			header("Location: ".$thanksPage3."?do=ins&idxnum=".$g_idxnum);
		}
		//コメントの更新ボタンが押された時
		if ( $g_do == 'editcontents'){
			contents_upd($db);
			header("Location: ".$thanksPage3."?do=edit&idxnum=".$g_idxnum."&mail_idx=".$c_mail_idx);
		}
		//メール返信ボタンが押された時
		if ( $g_do == 'reply'){
			mail_reply($db);
			require_once('./info_mail_reply.php');
		}
		//削除ボタンが押された時
		if ( $g_do == 'delete'){
			mysql_upd_info_mail_delete($db);
			header("Location: ".$thanksPage2."?idxnum=".$g_idxnum);
		}

		//連絡不通ボタンが押された時
		if ($g_do == 'tabsence') {
			mysql_upd_info_mail_tabsence($db);
			header("Location: ".$thanksPage1);
		}
		
		//法人引き継ぎボタンが押された時
		if ($g_do == 'copy_business') {
			mysql_copy_business($db);
			header("Location: ".$thanksPage2."?idxnum=".$g_idxnum);
		}

		//メールを確認待ちにした時
		if ($g_do == 'change_send') {
			mysql_change_send($db);
			header("Location: ".$thanksPage3."?finish=check&idxnum=".$g_idxnum);
		}
		//引き継ぎ登録したとき
		if($g_do == 'hikitugi'){
			$comm->ouputlog("1 mysql_copy_hikitugiログ出力", $prgid, SYS_LOG_TYPE_DBUG);
			mysql_copy_hikitugi($db);
			header("Location: ".$thanksPage2."?idxnum=".$g_idxnum);
		}
		//情報新規登録
		if ($g_do == 'ins_mail') {
			$flg = mysql_ins_mail_kokyaku($db);
			$comm->ouputlog("登録インデックス　=　".$flg, $prgid, SYS_LOG_TYPE_DBUG);
			header("Location: ".$thanksPage4."?idxnum=".$flg);
		}
		//情報更新
		if ($g_do == 'upd_mail') {
			mysql_upd_mail_kokyaku($db);
			header("Location: ".$thanksPage4."?idxnum=".$g_idxnum);
		}
		//テンプレート新規登録
		if ($g_do == 'ins_temp') {
			$idxnum = mysql_ins_temp($db);
			header("Location: ".$thanksPage5."?idxnum=".$idxnum);
		}
		//テンプレート更新
		if ($g_do == 'upd_temp') {
			$idxnum = mysql_upd_temp($db);
			header("Location: ".$thanksPage5."?idxnum=".$g_idxnum);
		}
		//テンプレート新規登録
		if ($g_do == 'del_temp') {
			$idxnum = mysql_del_temp($db);
			header("Location: ".$thanksPage6);
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
//   mysql_upd_info_mail
//
// ■概要
//   一覧表示から状態のアップデート
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_upd_info_mail( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_idxnum;
	global $g_urgency;
	global $g_kind;
	global $g_status;
	global $g_correstaf;
	global $g_kind_detail;
	global $table_detail;
	global $today;

	$comm->ouputlog("mysql_upd_info_mailログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	$max_mail_idx = 0;
	$chk_done = "";
	if($g_status > 7){
		if($g_status  == 9){
			//チェック完了の場合はデータ格納
			$chk_done .= " ,chk_staff = ". sprintf("'%s'", $g_staff);
			$chk_done .= " , chk_datetime = " . sprintf("'%s'", date('YmdHis'));
			$p_contents = "確認完了";
		}elseif($g_status  == 8){
			$p_contents = "対応完了";
		}
		//完了/チェック完了の場合はデータ格納
		//同じインデックス・メールIDのデータが有るか確認
		$query = "SELECT idxnum,MAX(mail_idx) as max_mail_idx FROM  " . $table_detail . "  WHERE idxnum = '".$g_idxnum."'";
		$comm->ouputlog("===データ抽出ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {echo "データ取得エラー";}
		else {
		//あればメールIDを＋１にする
			if ($rs->num_rows > 0) {
				while ($row = $rs->fetch_array()) {
					$max_mail_idx = $row['max_mail_idx'];
					$comm->ouputlog("g_mail_idx=".$g_mail_idx, $prgid, SYS_LOG_TYPE_DBUG);
				}
			}
		}
		$g_mail_idx = $max_mail_idx + 1;
		$g_detail_idx = $g_idxnum . "-" . $g_mail_idx;
		
		//データ更新
		$_insert = "INSERT INTO " . $table_detail;
		$_insert .= " (insdt, upddt, corredt, idxnum, mail_idx, detail_idx,  ";
		$_insert .= " email, name, correstaf, category, contents)";
		$_insert .= " VALUE ('$today', '$today', '$today', '$g_idxnum', '$g_mail_idx', '$g_detail_idx', ";
		$_insert .= " '', '', '$g_staff', 'コメント', '$p_contents' )";
		$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
		//データ追加実行
		if (! $db->query($_insert)) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return false;
		}
	}
	//データ更新
	$_update = "UPDATE " . $table;
	$_update .= " SET upddt = " . sprintf("'%s'", date('YmdHis'));
	$_update .= " ,updcount = updcount + 1";
	$_update .= " ,status   = ". sprintf("'%s'", $g_status);
	$_update .= ",urgency = ". sprintf("'%s'", $g_urgency);
	$_update .= " ,kind = ". sprintf("'%s'", $g_kind);
	$_update .= " ,correstaf = ". sprintf("'%s'", $g_correstaf);
	$_update .= " ,kind_detail = ". sprintf("'%s'", $g_kind_detail);
	$_update .= $chk_done;
	$_update .= " WHERE idxnum = " . sprintf("'%s'", $g_idxnum);

	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
// ----- 2019.06 ver7.0対応
//	if (! mysql_query($_update, $db)) {
	if (! $db->query($_update)) {
//		$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return true;
}


//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_upd_info_mail_detail
//
// ■概要
//   詳細ページから状態のアップデート
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_upd_info_mail_detail( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_idxnum;
	global $d_status;
	global $d_urgency;
	global $d_kind;
	global $d_kind_detail;
	global $table_detail;
	global $today;

	$comm->ouputlog("mysql_upd_info_mailログ出力", $prgid, SYS_LOG_TYPE_DBUG);
	$max_mail_idx = 0;
	$chk_done = "";
	if($d_status > 7){
		if($d_status  == 9){
			//チェック完了の場合はデータ格納
			$chk_done .= " ,chk_staff = ". sprintf("'%s'", $g_staff);
			$chk_done .= " , chk_datetime = " . sprintf("'%s'", date('YmdHis'));
			$p_contents = "確認完了";
		}elseif($d_status  == 8){
			$p_contents = "対応完了";
		}
		//完了/チェック完了の場合はデータ格納
		//同じインデックス・メールIDのデータが有るか確認
		$query = "SELECT idxnum,MAX(mail_idx) as max_mail_idx FROM  " . $table_detail . "  WHERE idxnum = '".$g_idxnum."'";
		$comm->ouputlog("===データ抽出ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {echo "データ取得エラー";}
		else {
		//あればメールIDを＋１にする
			if ($rs->num_rows > 0) {
				while ($row = $rs->fetch_array()) {
					$max_mail_idx = $row['max_mail_idx'];
					$comm->ouputlog("g_mail_idx=".$g_mail_idx, $prgid, SYS_LOG_TYPE_DBUG);
				}
			}
		}
		$g_mail_idx = $max_mail_idx + 1;
		$g_detail_idx = $g_idxnum . "-" . $g_mail_idx;
		
		//データ更新
		$_insert = "INSERT INTO " . $table_detail;
		$_insert .= " (insdt, upddt, corredt, idxnum, mail_idx, detail_idx,  ";
		$_insert .= " email, name, correstaf, category, contents)";
		$_insert .= " VALUE ('$today', '$today', '$today', '$g_idxnum', '$g_mail_idx', '$g_detail_idx', ";
		$_insert .= " '', '', '$g_staff', 'コメント', '$p_contents' )";
		$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
		//データ追加実行
		if (! $db->query($_insert)) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return false;
		}
	}

	//データ更新
	$_update = "UPDATE " . $table;
	$_update .= " SET upddt = " . sprintf("'%s'", date('YmdHis'));
	$_update .= " ,updcount = updcount + 1";
	$_update .= " ,status   = ". sprintf("'%s'", $d_status);
	$_update .= ",urgency = ". sprintf("'%s'", $d_urgency);
	$_update .= " ,kind = ". sprintf("'%s'", $d_kind);
	$_update .= " ,kind_detail = ". sprintf("'%s'", $d_kind_detail);
	$_update .= $chk_done;
	$_update .= " WHERE idxnum = " . sprintf("'%s'", $g_idxnum);

	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
	if (! $db->query($_update)) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return true;
}


//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   contents_ins
//
// ■概要
//   コメント登録
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function contents_ins( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	global $table_detail;
	//対象プログラム
	global $prgid;
	//引数
	global $g_contents;
	global $g_idxnum;
	global $g_category;
	global $g_staff;
	global $g_email;
	global $g_name;
	global $g_mail_idx;
	global $today;

	// エスケープ処理
	if ($g_contents != "") {
		$g_contents = addslashes($g_contents);
	}
	if ($g_name != "") {
		$g_name = addslashes($g_name);
	}

	$comm->ouputlog("contents_insログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//同じインデックス・メールIDのデータが有るか確認
	$query = "SELECT idxnum,MAX(mail_idx) as max_mail_idx FROM  " . $table_detail . "  WHERE idxnum = '".$g_idxnum."'";
	if (!($rs = $db->query($query))) {echo "データ取得エラー";}
	else {
		//あればメールIDを＋１にする
		if ($rs->num_rows > 0) {
			while ($row = $rs->fetch_array()) {
				$max_mail_idx = $row['max_mail_idx'];
				$g_mail_idx = $max_mail_idx + 1;
				$comm->ouputlog("g_mail_idx=".$g_mail_idx, $prgid, SYS_LOG_TYPE_DBUG);
			}
		}
	}
	$g_detail_idx = $g_idxnum . "-" . $g_mail_idx;
	
	//データ更新
	$_insert = "INSERT INTO " . $table_detail;
	$_insert .= " (insdt, upddt, corredt, idxnum, mail_idx, detail_idx,  ";
	$_insert .= " email, name, correstaf, category, contents)";
	$_insert .= " VALUE ('$today', '$today', '$today', '$g_idxnum', '$g_mail_idx', '$g_detail_idx', ";
	$_insert .= " '$g_email', '$g_name', '$g_staff', '$g_category', '$g_contents' )";
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
// ----- 2019.06 ver7.0対応
//	if (! mysql_query($_insert, $db)) {
	if (! $db->query($_insert)) {
//		$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	
	//お客様情報の最終更新時間をアップデート
	$_update = "UPDATE " . $table;
	$_update .= " SET upddt = " . sprintf("'%s'", date('YmdHis'));
	$_update .= " WHERE idxnum = " . sprintf("'%s'", $g_idxnum);
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
// ----- 2019.06 ver7.0対応
//	if (! mysql_query($_update, $db)) {
	if (! $db->query($_update)) {
//		$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}

	$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   contents_upd
//
// ■概要
//   コメント更新
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function contents_upd( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table_detail;
	//対象プログラム
	global $prgid;
	//引数
	global $g_idxnum;
	global $c_mail_idx;
	global $g_category;
	global $g_contents;
	global $g_staff;

	$comm->ouputlog("contents_updログ出力", $prgid, SYS_LOG_TYPE_DBUG);
	
	//データ更新
	$_update = "UPDATE " . $table_detail;
	$_update .= " SET upddt = " . sprintf("'%s'", date('YmdHis'));
	$_update .= " ,updcount = updcount + 1";
	$_update .= " ,category   = ". sprintf("'%s'", $g_category);
	$_update .= " ,contents = ". sprintf("'%s'", $g_contents);
	$_update .= " ,correstaf = ". sprintf("'%s'", $g_staff);
	$_update .= " WHERE idxnum = " . sprintf("'%s'", $g_idxnum);
	$_update .= " AND mail_idx = " . sprintf("'%s'", $c_mail_idx);
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
// ----- 2019.06 ver7.0対応
//	if (! mysql_query($_update, $db)) {
	if (! $db->query($_update)) {
//		$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}

	$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mail_reply
//
// ■概要
//   コメント登録
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mail_reply( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	global $table_detail;
	//対象プログラム
	global $prgid;
	//引数
	global $g_idxnum;
	global $m_title;
	global $m_contents;
	global $m_category;
	global $m_staff;
	global $m_email;
	global $m_name;
	global $m_mail_idx;
	global $m_file;
	global $today;

	$comm->ouputlog("mail_replyログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//同じインデックス・メールIDのデータが有るか確認
	$query = "SELECT idxnum,MAX(mail_idx) as max_mail_idx FROM  " . $table_detail . " WHERE idxnum = '".$g_idxnum."'";
	$comm->ouputlog("===データ抽出ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (!($rs = mysql_query($query))) {echo "データ取得エラー";}
	if (!($rs = $db->query($query))) {echo "データ取得エラー";}
	else {
		while ($row = $rs->fetch_array()) {
			$max_mail_idx = $row['max_mail_idx'];
			//確認待ちメールを削除
			$delete_idx = $g_idxnum. "-" . $max_mail_idx;
			$delquery = "DELETE FROM " . $table_detail . " WHERE detail_idx = '".$delete_idx."' AND checkflg = 1";
			$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog($delquery, $prgid, SYS_LOG_TYPE_DBUG);
			if (! $db->query($delquery)) {
				$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				return false;
			}
		}
	}
	$m_detail_idx = $g_idxnum . "-" . $max_mail_idx;
	$m_mail_idx = $max_mail_idx;
	// ステータスを取得
	$query = "  SELECT A.status";
	$query.= " FROM " . $table . " A ";
	$query.= " WHERE A.idxnum = " . sprintf("'%s'", $g_idxnum);
	$query .=" AND A.delflg = 0";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$now_status = $row['status'];
	}
	// エスケープ処理
	if ($m_title != "") {
		$m_title = addslashes($m_title);
	}
	if ($m_contents != "") {
		$m_contents = addslashes($m_contents);
	}
	if ($m_name != "") {
		$m_name = addslashes($m_name);
	}

	//データ更新
	$_insert = "INSERT INTO " . $table_detail;
	$_insert .= " (insdt, upddt, corredt, idxnum, mail_idx, detail_idx, ";
	$_insert .= " email, name, correstaf, category, subject, contents, file)";
	$_insert .= " VALUE ('$today', '$today', '$today', '$g_idxnum', '$m_mail_idx', '$m_detail_idx',  ";
	$_insert .= " '$m_email', '$m_name', '$m_staff', '$m_category', '$m_title', '$m_contents', '$m_file')";
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
// ----- 2019.06 ver7.0対応
//	if (! mysql_query($_insert, $db)) {
	if (! $db->query($_insert)) {
//		$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}

	//お客様情報の最終更新時間をアップデート
	$_update = "UPDATE " . $table;
	$_update .= " SET upddt = " . sprintf("'%s'", date('YmdHis'));
	// 確認待ちの場合、完了に変更。
	if ($now_status == SYS_STATUS_8) {
		$_update .= ",status = ". sprintf("'%s'", SYS_STATUS_9);
	}
	$_update .= " WHERE idxnum = " . sprintf("'%s'", $g_idxnum);
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
// ----- 2019.06 ver7.0対応
//	if (! mysql_query($_update, $db)) {
	if (! $db->query($_update)) {
//		$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	
	$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_upd_info_mail_delete
//
// ■概要
//   詳細ページから削除
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_upd_info_mail_delete( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table_detail;
	
	//対象プログラム
	global $prgid;
	//引数
	global $g_idxnum;
	global $p_mail_idx;

	$comm->ouputlog("mysql_upd_info_mail_deleteログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//データ更新
	$_detele = "DELETE FROM " . $table_detail;
	$_detele .= " WHERE idxnum = " . sprintf("'%s'", $g_idxnum);
	$_detele .= " AND mail_idx = " . sprintf("'%s'", $p_mail_idx);

	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_detele, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
// ----- 2019.06 ver7.0対応
//	if (! mysql_query($_detele, $db)) {
	if (! $db->query($_detele)) {
//		$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_upd_info_mail_tabsence
//
// ■概要
//   連絡不通処理
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------

function mysql_upd_info_mail_tabsence( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	global $table_detail;
	//対象プログラム
	global $prgid;
	//引数
	global $g_idxnum;
	global $g_category;
	global $g_staff;
	global $g_email;
	global $g_name;
	global $g_mail_idx;
	global $today;
	$table2 = "php_tabsence_list";

	$comm->ouputlog("info_mail_tabsenceログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	$query = "";
	$query.= " SELECT A.idxnum, A.email, A.name, A.phonenum";
	$query.= " ,CASE ";
	$query.= " WHEN A.ruby = '' THEN A.name ";
	$query.= " ELSE A.ruby ";
	$query.= " END ";
	$query.= " as ruby ";
	$query.= " FROM " . $table . " A ";
	$query.= " WHERE A.idxnum = " . sprintf("'%s'", $g_idxnum);
	$query .=" AND A.delflg = 0";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$info_idx = $row['idxnum'];
		$info_name = $row['name'];
		$info_ruby = $row['ruby'];
		$info_telnum = $row['phonenum'];
		$info_email = $row['email'];
	}

	//折り返しリスト情報取得
	$status = "";
	$query = "";
	$query.= "  SELECT A.status";
	$query.= " FROM " . $table2 . " A ";
	$query.= " WHERE old_idxnum = " . sprintf("'%s'",$g_idxnum);
	$query.= " AND kbn = '問い合わせ'";
	$query .=" AND A.delflg = 0";
	$query.= " ORDER BY A.idxnum ASC ";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	if ($rs != ""){
		while ($row = $rs->fetch_array()) {
			$status = $row['status'];
		}
	} else {
		$status = 9;
	}

	//折り返しリスト登録無しの場合 or ステータス完了の場合、折り返し情報新規登録
	if ($status == 9 || $status == "") {
		$query = "INSERT INTO " . $table2;
		$query .= "( ";
		$query .= "insdt ";
		$query .= ",upddt ";
		$query .= ",updcount ";
		$query .= ",kbn ";
		$query .= ",status ";
		$query .= ",ruby ";
		$query .= ",phonenum1 ";
		$query .= ",phonenum2 ";
		$query .= ",inquiry ";
		$query .= ",receptionist ";
		$query .= ",response ";
		$query .= ",response_staff ";
		$query .= ",old_idxnum ";
		$query .= " )";
		$query .= " VALUES ( ";
		$query .= sprintf("'%s'", date('YmdHis'));
		$query .= sprintf(",'%s'", date('YmdHis'));
		$query .= sprintf(",'%s'", 1);
		$query .= sprintf(",'%s'", "問い合わせ");
		$query .= sprintf(",'%s'", SYS_STATUS_1);
		$query .= sprintf(",'%s'", $info_ruby);
		$query .= sprintf(",'%s'", $info_telnum);
		$query .= sprintf(",'%s'", $phonenum2);
		$query .= sprintf(",'%s'", "問い合わせメールの件で連絡");
		$query .= sprintf(",'%s'", $g_staff);
		$query .= sprintf(",'%s'", "その他");
		$query .= sprintf(",'%s'", "問い合わせ対応メンバー");
		$query .= sprintf(",'%s'", $g_idxnum);
		$query .= " )";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return false;
		}
		$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);

		//同じインデックス・メールIDのデータが有るか確認
		$query = "SELECT idxnum,MAX(mail_idx) as max_mail_idx FROM  " . $table_detail . "  WHERE idxnum = '".$g_idxnum."'";
	// ----- 2019.06 ver7.0対応
	//	if (!($rs = mysql_query($query))) {echo "データ取得エラー";}
		if (!($rs = $db->query($query))) {echo "データ取得エラー";}
		else {
			//あればメールIDを＋１にする
	// ----- 2019.06 ver7.0対応
	//		if (mysql_num_rows($rs) > 0) {
			if ($rs->num_rows > 0) {
	//			while ($row = @mysql_fetch_array($rs)) {
				while ($row = $rs->fetch_array()) {
					$max_mail_idx = $row['max_mail_idx'];
					$g_mail_idx = $max_mail_idx + 1;
					$comm->ouputlog("g_mail_idx=".$g_mail_idx, $prgid, SYS_LOG_TYPE_DBUG);
				}
			}
		}
		$query = "";
		$query.= "  SELECT A.idxnum";
		$query.= " FROM " . $table2 . " A ";
		$query.= " WHERE A.old_idxnum = " . sprintf("'%s'",$g_idxnum);
		$query.= " AND kbn = '問い合わせ'";
		$query .=" AND A.delflg = 0";
		$query.= " ORDER BY A.idxnum ASC ";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$tabsence_idx = $row['idxnum'];
		}
		$comment = "折り返し連絡リストに登録、No.".$tabsence_idx;
		//データ更新
		$_insert = "INSERT INTO " . $table_detail;
		$_insert .= " (insdt, upddt, corredt, idxnum, mail_idx, detail_idx,  ";
		$_insert .= " email, name, correstaf, category, contents)";
		$_insert .= " VALUE ('$today', '$today', '$today', '$g_idxnum', '$g_mail_idx', '$g_detail_idx', ";
		$_insert .= " '$info_email', '$info_name', '$g_staff', 'コメント', '$comment' )";
		$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
		//データ追加実行
	// ----- 2019.06 ver7.0対応
	//	if (! mysql_query($_insert, $db)) {
		if (! $db->query($_insert)) {
	//		$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return false;
		}
		$g_detail_idx = $g_idxnum . "-" . $g_mail_idx;
		//データ更新
		$_update = "UPDATE " . $table;
		$_update .= " SET upddt = " . sprintf("'%s'", date('YmdHis'));
		$_update .= " ,updcount = updcount + 1";
		$_update .= " ,status   = 2";
		$_update .= ",urgency = ". sprintf("'%s'", $d_urgency);
		$_update .= " ,kind = ". sprintf("'%s'", $d_kind);
		$_update .= " ,correstaf = ". sprintf("'%s'", $g_staff);
		$_update .= " ,kind_detail = ". sprintf("'%s'", $d_kind_detail);
		$_update .= $chk_done;
		$_update .= " WHERE idxnum = " . sprintf("'%s'", $g_idxnum);
		$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
	// ----- 2019.06 ver7.0対応
	//	if (! mysql_query($_update, $db)) {
		if (! $db->query($_update)) {
	//		$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return false;
		}
	}
	return true;
}
//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_copy_business
//
// ■概要
//   法人引き継ぎ
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------

function mysql_copy_business( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	global $table_detail;
	//対象プログラム
	global $prgid;
	//引数
	global $g_idxnum;
	global $g_category;
	global $g_staff;
	global $today;
	$table2 = "php_business_mail";

	$comm->ouputlog("mysql_copy_businessログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table2);
	//項目選択
	$arrkey = array("会社名","お名前","ふりがな","郵便番号1","郵便番号2","都道府県","ご住所","電話番号","メールアドレス","お問合せ内容","対応担当者");
	
	$query = "";
	$query.= "  SELECT A.idxnum, A.ruby, A.email, A.name";
	$query.= " FROM " . $table . " A ";
	$query.= " WHERE A.idxnum = " . sprintf("'%s'", $g_idxnum);
	$query .=" AND A.delflg = 0";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$info_idx = $row['idxnum'];
		$info_name = $row['name'];
		$info_ruby = $row['ruby'];
		$info_email = $row['email'];
	}
	$_insert_1 = "INSERT INTO " . $table2 . " (";
	$_insert_1 .= sprintf("%s ", $collist["登録日時"]);
	$_insert_3 .= sprintf("'%s'", $today);
	$_insert_1 .= sprintf(",%s ", $collist["更新日時"]);
	$_insert_3 .= sprintf(", '%s'", $today);
	$_insert_1 .= sprintf(",%s ", $collist["更新回数"]);
	$_insert_3 .= sprintf(", '%s'", 1);
	$_insert_1 .= sprintf(",%s ", $collist["問合せ状況"]);
	$_insert_3 .= sprintf(", '%s'", 0);
	$_insert_2 .= " )SELECT ";
	$_insert_4 .= " FROM php_info_mail WHERE idxnum= " . sprintf("'%s'", $g_idxnum);
	$query1 = "";
	$query2 = "";
	//折り返し情報新規登録
	foreach($arrkey as $val2){
		$query1 .= sprintf(", %s ", $collist[$val2]);
		$query2 .= sprintf(", %s ", $collist[$val2]);
	}
	$_insert .= $_insert_1;
	$_insert .= $query1;
	$_insert .= $_insert_2;
	$_insert .= $_insert_3;
	$_insert .= $query2;
	$_insert .= $_insert_4;
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
	if (! $db->query($_insert)) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	//インデックス取得
	$business_idx = mysqli_insert_id($db);
	$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
	
	//同じインデックス・メールIDのデータ確認
	$g_mail_idx = 1;
	$query = "SELECT idxnum,MAX(mail_idx) as max_mail_idx FROM  " . $table_detail . "  WHERE idxnum = '".$g_idxnum."'";
	if (!($rs = $db->query($query))) {echo "データ取得エラー";}
	else {
		//あればメールIDを＋１にする
		if ($rs->num_rows > 0) {
			while ($row = $rs->fetch_array()) {
				$max_mail_idx = $row['max_mail_idx'];
				$g_mail_idx = $max_mail_idx + 1;
				$comm->ouputlog("g_mail_idx=".$g_mail_idx, $prgid, SYS_LOG_TYPE_DBUG);
			}
		}
	}
	$g_detail_idx = $g_idxnum . "-" . $g_mail_idx;
	$comment = "法人に引き継ぎ、No.".$business_idx;
	//引き継ぎログ登録
	$_insert = "INSERT INTO " . $table_detail;
	$_insert .= " (insdt, upddt, corredt, idxnum, mail_idx, detail_idx,  ";
	$_insert .= " email, name, correstaf, category, contents)";
	$_insert .= " VALUE ('$today', '$today', '$today', '$g_idxnum', '$g_mail_idx', '$g_detail_idx', ";
	$_insert .= " '$info_email', '$info_name', '$g_staff', 'コメント', '$comment' )";
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
	if (! $db->query($_insert)) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	//データ更新
/*	$_update = "UPDATE " . $table;
	$_update .= " SET upddt = " . sprintf("'%s'", date('YmdHis'));
	$_update .= " ,updcount = updcount + 1";
	$_update .= " ,status   = 8";
	$_update .= " ,urgency = ". sprintf("'%s'", $d_urgency);
	$_update .= " ,kind = ". sprintf("'%s'", $d_kind);
	$_update .= " ,correstaf = ". sprintf("'%s'", $g_staff);
	$_update .= " ,kind_detail = ". sprintf("'%s'", $d_kind_detail);
	$_update .= $chk_done;
	$_update .= " WHERE idxnum = " . sprintf("'%s'", $g_idxnum);
	//データ更新実行
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
	if (! $db->query($_update)) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
*/	//法人テーブルにコメント登録
	//同じインデックス・メールIDのデータ確認
	$g_mail_idx = 1;
	$query = "SELECT idxnum,MAX(mail_idx) as max_mail_idx FROM php_business_mail_detail WHERE idxnum = '".$business_idx."'";
	if (!($rs = $db->query($query))) {echo "データ取得エラー";}
	else {
		//あればメールIDを＋１にする
		if ($rs->num_rows > 0) {
			while ($row = $rs->fetch_array()) {
				$max_mail_idx = $row['max_mail_idx'];
				$g_mail_idx = $max_mail_idx + 1;
				$comm->ouputlog("g_mail_idx=".$g_mail_idx, $prgid, SYS_LOG_TYPE_DBUG);
			}
		}
	}
	$g_detail_idx = $business_idx . "-" . $g_mail_idx;
	$comment = "問い合わせメールから引き継ぎ、No.".$g_idxnum;
	//データ更新
	$_insert = "INSERT INTO php_business_mail_detail";
	$_insert .= " (insdt, upddt, corredt, idxnum, mail_idx, detail_idx,  ";
	$_insert .= " email, name, correstaf, category, contents)";
	$_insert .= " VALUE ('$today', '$today', '$today', '$business_idx', '$g_mail_idx', '$g_detail_idx', ";
	$_insert .= " '$info_email', '$info_name', '$g_staff', 'コメント', '$comment' )";
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
	if (! $db->query($_insert)) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_copy_business
//
// ■概要
//   法人引き継ぎ
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------

function mysql_change_send( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	global $table_detail;
	//対象プログラム
	global $prgid;
	//引数
	global $g_idxnum;
	global $m_title;
	global $m_contents;
	global $m_category;
	global $m_staff;
	global $m_email;
	global $m_name;
	global $m_mail_idx;
	global $m_file;
	global $today;

	$comm->ouputlog("mail_replyログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	// エスケープ処理
	if ($m_title != "") {
		$m_title = addslashes($m_title);
	}
	if ($m_contents != "") {
		$m_contents = addslashes($m_contents);
	}
	if ($m_name != "") {
		$m_name = addslashes($m_name);
	}

	//同じインデックス・メールIDのデータが有るか確認
	$query = "SELECT idxnum,MAX(mail_idx) as max_mail_idx FROM  " . $table_detail . "  WHERE idxnum = '".$g_idxnum."'";
// ----- 2019.06 ver7.0対応
//	if (!($rs = mysql_query($query))) {echo "データ取得エラー";}
	if (!($rs = $db->query($query))) {echo "データ取得エラー";}
	else {
		//あればメールIDを＋１にする
// ----- 2019.06 ver7.0対応
//		if (mysql_num_rows($rs) > 0) {
		if ($rs->num_rows > 0) {
//			while ($row = @mysql_fetch_array($rs)) {
			while ($row = $rs->fetch_array()) {
				$max_mail_idx = $row['max_mail_idx'];
				$m_mail_idx = $max_mail_idx + 1;
				$comm->ouputlog("m_mail_idx=".$m_mail_idx, $prgid, SYS_LOG_TYPE_DBUG);
			}
		}
	}
	$m_detail_idx = $g_idxnum . "-" . $m_mail_idx;
	
	//データ更新
	$_insert = "INSERT INTO " . $table_detail;
	$_insert .= " (insdt, upddt, corredt, idxnum, mail_idx, detail_idx, ";
	$_insert .= " email, name, correstaf, category, subject, contents, file, checkflg)";
	$_insert .= " VALUE ('$today', '$today', '$today', '$g_idxnum', '$m_mail_idx', '$m_detail_idx',  ";
	$_insert .= " '$m_email', '$m_name', '$m_staff', 'コメント', '', 'メール確認待登録', '', 0)";
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
// ----- 2019.06 ver7.0対応
//	if (! mysql_query($_insert, $db)) {
	if (! $db->query($_insert)) {
//		$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	$m_mail_idx = $m_mail_idx + 1;
	$m_detail_idx = $g_idxnum . "-" . $m_mail_idx;

	$_insert = "INSERT INTO " . $table_detail;
	$_insert .= " (insdt, upddt, corredt, idxnum, mail_idx, detail_idx, ";
	$_insert .= " email, name, correstaf, category, subject, contents, file, checkflg)";
	$_insert .= " VALUE ('$today', '$today', '$today', '$g_idxnum', '$m_mail_idx', '$m_detail_idx',  ";
	$_insert .= " '$m_email', '$m_name', '$m_staff', '$m_category', '$m_title', '$m_contents', '$m_file', 1)";
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
// ----- 2019.06 ver7.0対応
//	if (! mysql_query($_insert, $db)) {
	if (! $db->query($_insert)) {
//		$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}

	//お客様情報の最終更新時間をアップデート
	$_update = "UPDATE " . $table;
	$_update .= " SET upddt = " . sprintf("'%s'", date('YmdHis'));
	$_update .= " ,status = 8";
	$_update .= " WHERE idxnum = " . sprintf("'%s'", $g_idxnum);
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
	if (! $db->query($_update)) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	
	$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_copy_hikitugi
//
// ■概要
//   引き継ぎ登録
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------

function mysql_copy_hikitugi( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	global $table_detail;
	//対象プログラム
	global $prgid;
	//引数
	global $g_idxnum;
	global $hikitugi_idx;
	global $g_category;
	global $g_staff;
	global $today;

	$comm->ouputlog("mysql_copy_hikitugiログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//同じインデックス・メールIDのデータ確認
	$g_mail_idx = 1;
	$query = "SELECT idxnum,MAX(mail_idx) as max_mail_idx FROM  " . $table_detail . "  WHERE idxnum = '".$g_idxnum."'";
	if (!($rs = $db->query($query))) {echo "データ取得エラー";}
	else {
		//あればメールIDを＋１にする
		if ($rs->num_rows > 0) {
			while ($row = $rs->fetch_array()) {
				$max_mail_idx = $row['max_mail_idx'];
				$g_mail_idx = $max_mail_idx + 1;
				$g_mail_idx2 = $max_mail_idx + 2;
				$comm->ouputlog("g_mail_idx=".$g_mail_idx, $prgid, SYS_LOG_TYPE_DBUG);
			}
		}
	}
	$g_detail_idx = $g_idxnum . "-" . $g_mail_idx;
	$g_detail_idx2 = $g_idxnum . "-" . $g_mail_idx2;
	$comment = "引継　No.".$hikitugi_idx;
	//引き継ぎログ登録
	$_insert = "INSERT INTO " . $table_detail;
	$_insert .= " (insdt, upddt, corredt, idxnum, mail_idx, detail_idx,  ";
	$_insert .= " email, name, correstaf, category, contents)";
	$_insert .= " VALUE ('$today', '$today', '$today', '$g_idxnum', '$g_mail_idx', '$g_detail_idx', ";
	$_insert .= " '$info_email', '$info_name', '$g_staff', 'コメント', '$comment' )";
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
	if (! $db->query($_insert)) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	//引き継ぎログ登録
	$_insert = "INSERT INTO " . $table_detail;
	$_insert .= " (insdt, upddt, corredt, idxnum, mail_idx, detail_idx,  ";
	$_insert .= " email, name, correstaf, category, contents)";
	$_insert .= " VALUE ('$today', '$today', '$today', '$g_idxnum', '$g_mail_idx2', '$g_detail_idx2', ";
	$_insert .= " '$info_email', '$info_name', '$g_staff', 'コメント', '確認完了' )";
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
	if (! $db->query($_insert)) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	//ステータス更新
	$_update = "UPDATE " . $table;
	$_update .= " SET upddt = " . sprintf("'%s'", date('YmdHis'));
	$_update .= " ,updcount = updcount + 1";
	$_update .= " ,status   = 9";
	$_update .= " ,chk_staff = ". sprintf("'%s'", $g_staff);
	$_update .= " , chk_datetime = " . sprintf("'%s'", date('YmdHis'));
	$_update .= " WHERE idxnum = " . sprintf("'%s'", $g_idxnum);
	//データ更新実行
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
	if (! $db->query($_update)) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_upd_mail_kokyaku
//
// ■概要
//   顧客情報更新
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_upd_mail_kokyaku( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_idxnum;
	global $table_detail;
	global $today;
	global $g_post;

	$comm->ouputlog("mysql_upd_mail_kokyakuログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	//項目選択
	$arrkey = array("会社名","お名前","ふりがな","郵便番号","郵便番号2","都道府県","ご住所","電話番号","メールアドレス","お問合せ内容","対応担当者");
	
	$comm->ouputlog("mysql_upd_mail_kokyakuログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	$query = "";
	$query1 = "";
	$query2 = "";

	foreach($g_post as $key => $val){
		foreach($arrkey as $val2){
			if ($val2 == $key) {
				// エスケープ処理
				if ($val != "") {
					$val = addslashes($val);
				}
				$query .= sprintf(", %s ", $collist[$val2]) . "=" . sprintf("'%s'", $val);
			}
		}
	}
	
	//データ更新
	$_update = "UPDATE " . $table;
	$_update .= " SET upddt = " . sprintf("'%s'", date('YmdHis'));
	$_update .= " ,updcount = updcount + 1";
	$_update .= $query;
	$_update .= " WHERE idxnum = " . sprintf("'%s'", $g_idxnum);
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
	if (! $db->query($_update)) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_ins_mail_kokyaku
//
// ■概要
//   顧客情報登録
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_ins_mail_kokyaku( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	global $table_detail;
	//対象プログラム
	global $prgid;
	//引数
	global $g_post;
	global $today;
	
	$_insert = "";
	$_insert_1 = "";
	$_insert_2 = "";
	$_insert_3 = "";
	$_insert_4 = "";
	
	$comm->ouputlog("mysql_ins_mail_kokyakuログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	//項目選択
	$arrkey = array("会社名","お名前","ふりがな","郵便番号","郵便番号2","都道府県","ご住所","電話番号","メールアドレス","お問合せ内容","対応担当者");
	//共通項目作成
	$_insert_1 = "INSERT INTO " . $table . " (";
	$_insert_1 .= sprintf("%s ", $collist["登録日時"]);
	$_insert_3 .= sprintf("'%s'", $today);
	$_insert_1 .= sprintf(",%s ", $collist["更新日時"]);
	$_insert_3 .= sprintf(", '%s'", $today);
	$_insert_1 .= sprintf(",%s ", $collist["更新回数"]);
	$_insert_3 .= sprintf(", '%s'", 1);
	$_insert_1 .= sprintf(",%s ", $collist["問合せ状況"]);
	$_insert_3 .= sprintf(", '%s'", 0);
	$_insert_1 .= sprintf(",%s ", $collist["希望連絡方法"]);
	$_insert_3 .= sprintf(", '%s'", "メール");
	$_insert_2 .= " )VALUE( ";
	$_insert_4 .= " )";
	$query1 = "";
	$query2 = "";
	
	//POSTデータ取得
	foreach($g_post as $key => $val){
		foreach($arrkey as $val2){
			if($val2 == $key){
				// エスケープ処理
				if ($val != "") {
					$val = addslashes($val);
				}
				$query1 .= sprintf(", %s ", $collist[$val2]);
				$query2 .= sprintf(", '%s'", $val);
			}
		}
		$comm->ouputlog("key = ".$key."　val = ".$val, $prgid, SYS_LOG_TYPE_DBUG);
	}
	
	//データ更新
	$_insert .= $_insert_1;
	$_insert .= $query1;
	$_insert .= $_insert_2;
	$_insert .= $_insert_3;
	$_insert .= $query2;
	$_insert .= $_insert_4;
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
	if (! $db->query($_insert)) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	//インデックス取得
	$g_idxnum = mysqli_insert_id($db);
	
	$comm->ouputlog("登録インデックス　=　".$g_idxnum, $prgid, SYS_LOG_TYPE_DBUG);

	$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return $g_idxnum;
}

function val_escape() {
	
}


//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_ins_temp
//
// ■概要
//   テンプレート新規登録
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_ins_temp( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_idxnum;
	global $today;
	global $g_post;
	
	$table = "php_info_mail_tmp";

	$comm->ouputlog("mysql_ins_infomail_tempログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);

	//初期値設定
	$m_query1 = "";
	$m_query2 = "";
	$m_query1 .= " INSERT INTO ".$table;
	$m_query1 .= " ( ";
	$m_query2 .= " VALUES ( ";
	//登録日時
	$m_query1 .= $collist["登録日時"];
	$m_query2 .= sprintf("'%s'", $today);
	//更新日時
	$m_query1 .= "," . $collist["更新日時"];
	$m_query2 .= sprintf(",'%s'", $today);
	//更新担当者
	$m_query1 .= "," . $collist["更新担当者"];
	$m_query2 .= sprintf(",'%s'", $g_staff);
	//テンプレート内容
	$m_query1 .= "," . $collist["タイトル"];
	$m_query2 .= sprintf(",'%s'", $g_post['タイトル']);
	$m_query1 .= "," . $collist["本文"];
	$m_query2 .= sprintf(",'%s'", $g_post['本文']);
	$m_query1 .= ")";
	$m_query2 .= ")";
	
	//DBに登録
	$_insert = "";
	$_insert = $m_query1.$m_query2;
	$comm->ouputlog("===データ更新ＳＱＬ===", $_insert, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
	if (!($rs = $db->query($_insert))) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	//インデックス取得
	$g_idx = mysqli_insert_id($db);
	
	$comm->ouputlog("===データ登録処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return $g_idx;
}
//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_upd_temp
//
// ■概要
//   テンプレート更新
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_upd_temp( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_idxnum;
	global $today;
	global $g_post;
	
	$table = "php_info_mail_tmp";

	$comm->ouputlog("mysql_upd_infomail_tempログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//初期値設定
	$_update = "UPDATE " . $table;
	$_update .= " SET upddt = " . sprintf("'%s'", date('YmdHis'));
	$_update .= " ,updcount = updcount + 1";
	$_update .= " ,updstaff = " . sprintf("'%s'", $g_staff);
	$_update .= " ,title = " . sprintf("'%s'", $g_post['タイトル']);
	$_update .= " ,contents = " . sprintf("'%s'", $g_post['本文']);
	$_update .= " WHERE idxnum = " . sprintf("'%s'", $g_idxnum);
	
	//DB更新
	$comm->ouputlog("===データ更新ＳＱＬ===", $_update, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
	if (!($rs = $db->query($_update))) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	
	$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return true;
}
//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_del_temp
//
// ■概要
//   テンプレート削除
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_del_temp( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_idxnum;
	global $today;
	global $g_post;
	
	$table = "php_info_mail_tmp";

	$comm->ouputlog("mysql_del_infomail_tempログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//初期値設定
	$_update = "UPDATE " . $table;
	$_update .= " SET upddt = " . sprintf("'%s'", date('YmdHis'));
	$_update .= " ,updcount = updcount + 1";
	$_update .= " ,delflg = 1";
	$_update .= " WHERE idxnum = " . sprintf("'%s'", $g_idxnum);
	
	//DB更新
	$comm->ouputlog("===データ更新ＳＱＬ===", $_update, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
	//データ追加実行
	if (!($rs = $db->query($_update))) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	
	$comm->ouputlog("===データ更新処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return true;
}

?>


