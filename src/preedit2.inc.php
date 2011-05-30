<?php

/*

* 整形済みテキスト編集支援プラグイン - PreEdit2 0.1

PukiWikiで整形済みテキストを編集しやすくするプラグインです。
次の機能があります。

- 選択範囲を整形済みテキストへ変換

** インストール方法

*** skin (skin/pukiwiki.skin.php) の書換え
php echo $body を次の一行に置換。
 php include_once 'plugin/preedit2.inc.php'; echo plugin_preedit2_echo($body);

*** preedit2.inc.php のコピー
- preedit2.inc.php
をダウンロードして plugin ディレクトリ (./plugin) に preedit2.inc.php をコピー。

** Copyright
umorigu https://github.com/umorigu

** Licence
GPL2 (GNU General Public License version 2)

** Bugs & ToDo

** Version
- 0.1 2011/05/31

*/


define(PREEDIT2_VERSION, 0.1);

function plugin_preedit2_init()
{
}


function plugin_preedit2_convert()
{
	// HTML にコンバート時に呼び出される
	return "PreEdit2 plugin: version " . PREEDIT_VERSION . "\n";
}


function plugin_preedit2_action()
{
	// GET POST 時に呼び出される
}

function plugin_preedit2_echo($body)
{
	global $vars;
	
	if ($vars['cmd'] != 'edit'
	 && $vars['plugin'] != 'edit'
	 && $vars['plugin'] != 'paraedit'
	) {
		return $body;
	}
	
	// JavaScript のソースコードをセット
	$js_src = '<!-- preedit plugin version: ' . PREEDIT2_VERSION . "-->\n"
		 . plugin_preedit2_js();

	// $body の書換え
	// $js_src を先頭に追加
	$body = preg_replace("/^/", $js_src, $body, 1);
	
	return $body;
}

function plugin_preedit2_js()
{
	$js = <<<'_JS_SRC_'
<script type="text/javascript">
<!--

	// 選択範囲の位置を特定するIE専用の関数
	function getSelectionPos_IE(e)
	{
		e.focus();
		var r = document.selection.createRange();
		var len = r.text.length;
		var br = document.body.createTextRange();
		br.moveToElementText( e );
		var all_len = br.text.length;
		br.setEndPoint( "StartToStart", r );
		var s = all_len - br.text.length;
		var e = s + len;
		return { start: s, end: e };
	}

function insertPreBlock(e) {
	if( document.selection ) { // IE
		var pos = getSelectionPos_IE(e);
	} else if( e.setSelectionRange ){ // Mozilla (NN)
		var pos = {start: e.selectionStart, end: e.selectionEnd };
	}
	var text = e.value.substring( pos.start, pos.end );
	var prev = e.value.substring( 0, pos.start );
	var next = e.value.substring( pos.end, e.value.length );
	var left = "#pre{{";
	var right = "}}";
	{
		if (prev.length > 0) {
			if (prev.charAt(prev.length - 1) != "\n") {
				left = "\n" + left;
			}
		}
		if (text.length > 0) {
			if (text.charAt(0) != "\n" && text.charAt(0) != "\r") {
				left = left + "\n";
			}
			if (text.charAt(text.length - 1) != "\n") {
				right = "\n" + right;
			}
		}
		if (text.length == 0) {
			left += "\n"
		}
		if (next.length > 0) {
			if (next.charAt(0) != "\n" && next.charAt(0) != "\r") {
				right += "\n";
			}
		}
	}
	var text2 = left + text + right;
	e.value = prev + text2 + next;
	// 選択範囲の作成
	if( document.selection ) { // IE
		// Textareaのテキスト範囲を作成する
		var r = e.createTextRange();
		// 範囲の始点と終点を求める
		if( pos.start != pos.end ) {
			// テキストの長さをmove系メソッドで使えるように修正
			pos.start = prev.replace( /\r/g, "" ).length;
			pos.end = text2.replace( /\r/g, "" ).length;
		}  else {
			// カーソルの場合
			pos.start += text2.replace( /\r/g, "" ).length;
			pos.end = 0;
		}
		// moveEndの第２引数をpos.startからの位置として使うため
		r.collapse();
		// テキスト範囲を移動
		r.moveStart( "character",pos.start );
		r.moveEnd( "character", pos.end );
		// 現在のテキスト範囲を選択状態にする
		r.select();
	} else if( e.setSelectionRange ) { // Mozilla (NN)
		if( pos.start != pos.end ) {
			pos.end = pos.start + text2.length;
		}  else {
			pos.start += text2.length;
			pos.end = pos.start;
		}
		// 選択範囲を設定する
		e.setSelectionRange( pos.start, pos.end );
		// エレメント（テキストエリア）にフォーカスをあてる
		e.focus();
	}
}

function __preedit2_onload() {
	function getMsgTextArea() {
		var nodes = document.getElementsByTagName("textarea");
		for (var i = 0; i < nodes.length; i++) {
			if (nodes.item(i).name == "msg") {
				return nodes.item(i);
			}
		}
		return null;
	}

	var msgTextArea = getMsgTextArea();
	var div = document.createElement('div');
	var b = document.createElement('input');
	b.setAttribute('type', 'button');
	b.setAttribute('value', '-->');
	b.onclick = function(){var t = getMsgTextArea(); insertPreBlock(t); };
	div.appendChild(b);
	div.appendChild(document.createTextNode('選択範囲を整形済みテキストへ変換(<pre>で囲む)'));
	msgTextArea.parentNode.parentNode.appendChild(div);
}

if (window.addEventListener) window.addEventListener("load", __preedit2_onload, false);
if (window.attachEvent) window.attachEvent("onload", __preedit2_onload);

// -->
</script>


_JS_SRC_;

	return $js;
}

?>