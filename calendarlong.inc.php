<?php
// $Id: calendarlong.inc.php,v 0.4 2021/10/12 Haruka Tomose
// calender2 を改造したバリエーション。
//
// ・カレンダーを横一列に1行表示する。
// ・祝日対応。友瀬の dayweek ライブラリを使用する前提。
// ・めくり先は calender_viewer につないでいる
// ・他カレンダーと違い、日付クリックでは「参照」だけ。
// 　（他カレンダーでは editになっていることが普通）
//---
// ver 0.4
//  カレンダー全体を<div class="style_calendarlong">でくくる形に修正。


function plugin_calendarlong_convert()
{
	global $script,$vars,$post,$get,$weeklabels,$WikiName,$BracketName;

	global $_calendarlong_plugin_edit, $_calendarlong_plugin_empty;

	$date_str = get_date('Ym');
	$base = strip_bracket($vars['page']);

	$today_view = TRUE;
	if (func_num_args() > 0) {
		$args = func_get_args();
		foreach ($args as $arg) {
			if (is_numeric($arg) && strlen($arg) == 6) {
				$date_str = $arg;
			} else if ($arg == 'off') {
				$today_view = FALSE;
			} else {
				$base = strip_bracket($arg);
			}
		}
	}
	if ($base == '*') {
		$base = '';
		$prefix = '';
	}
	else {
		$prefix = $base.'/';
	}
	$r_base = rawurlencode($base);
	$s_base   = htmlsc($base);
	$r_prefix = rawurlencode($prefix);
	$s_prefix = htmlsc($prefix);

	$yr = substr($date_str,0,4);
	$mon = substr($date_str,4,2);
	$cDW = new Dayweek(); // 祝祭日判定処理。カレンダーの配列取得
	$tcalender = $cDW->getCal($yr,$mon);

	if ($yr != get_date('Y') || $mon != get_date('m')) {
		$now_day = 1;
		$other_month = 1;
	}
	else {
		$now_day = get_date('d');
		$other_month = 0;
	}

	$today = getdate(mktime(0,0,0,$mon,$now_day,$yr) - LOCALZONE + ZONETIME);

	$m_num = $today['mon'];
	$d_num = $today['mday'];
	$year = $today['year'];

	$f_today = getdate(mktime(0,0,0,$m_num,1,$year) - LOCALZONE + ZONETIME);
//	$wday = $f_today['wday'];
	$day = 1;

	$m_name = "$year-$m_num"; // yyyy-mm形式

	$y = substr($date_str,0,4)+0;
	$m = substr($date_str,4,2)+0;

	$prev_date_str = ($m == 1) ?
		sprintf('%04d-%02d',$y - 1,12) : sprintf('%04d-%02d',$y,$m - 1);

	$next_date_str = ($m == 12) ?
		sprintf('%04d-%02d',$y + 1,1) : sprintf('%04d-%02d',$y,$m + 1);

	$ret = '';
	if ($today_view) {
		$ret = "<table border=\"0\" summary=\"calendar frame\">\n <tr>\n  <td valign=\"top\">\n";
	}
	$ret .= <<<EOD
   <div class="style_calendarlong">
   <table class="style_calendarlong" cellspacing="1" width="350" border="0" summary="calendar body">
    <tr>
     <td class="style_td_caltop" colspan="31">

<a href="$script?plugin=calendar_viewer&amp;file=$r_base&amp;date=$next_date_str">new&lt;&lt;</a>
      <strong>$m_name</strong>;
<a href="$script?plugin=calendar_viewer&amp;file=$r_base&amp;date=$prev_date_str">&gt;&gt;old</a>

EOD;
	if ($prefix) {
		$ret .= "\n      <br />[<a href=\"".$script."?".$r_base."\">".$s_base."</a>]";
	}

	$ret .= "\n     </td>\n    </tr>\n    <tr>\n";
	$ret .= "    </tr>\n    <tr>\n";


	while (checkdate($m_num,$day,$year)) {
		$dt = sprintf('%4d-%02d-%02d', $year, $m_num, $day);
		$page = $prefix.$dt;
		$r_page = rawurlencode($page);
		$s_page =htmlsc($tcalender[$day]['info']);

		$style = 'style_td_day'; // Weekday

		switch($tcalender[$day]['wday']){
			case '6':
				$style = 'style_td_sat';
				break;
			case '0':
				$style = 'style_td_sun';
				break;
			case '9':
				$style = 'style_td_sun';
				break;
			default:
				$style = 'style_td_day';
		}
		if (!$other_month && ($day == $today['mday']) && ($m_num == $today['mon']) && ($year == $today['year'])) { // Today
			$style = 'style_td_today';
		}


		if (is_page($page)) {
			$s_page = htmlsc($page);
			$link = '<a href="' . $script . '?' . $r_page . '" title="' . $s_page .
				'"><strong>' . $day . '</strong></a>';
		} else {
			if (PKWK_READONLY) {
				$link = '<span class="small">' . $day . '</small>';
			} else {
				$link = '<a class="small" href="' . $script . '?' . $r_page . '" title="' . $s_page .
				'">' . $day . '</a>';
//				$link = $script . '?cmd=edit&amp;page=' . $r_page . '&amp;refer=' . $r_base;
//				$link = '<a class="small" href="' . $link . '" title="' . $s_page . '">' . $day . '</a>';
			}
		}

		$ret .= '     <td class="' . $style . '">' . "\n" .
			'      ' . $link . "\n" .
			'     </td>' . "\n";
		++$day;

	}

	$ret .= "    </tr>\n   </table></div>\n";

	if ($today_view) {
		$tpage = $prefix.sprintf("%4d-%02d-%02d", $today['year'], $today['mon'], $today['mday']);
		$r_tpage = rawurlencode($tpage);
		if (is_page($tpage)) {
			$_page = $vars['page'];
			$get['page'] = $post['page'] = $vars['page'] = $tpage;
			$str = convert_html(get_source($tpage));
			$str .= "<hr /><a class=\"small\" href=\"$script?cmd=edit&amp;page=$r_tpage\">$_calendarlong_plugin_edit</a>";
			$get['page'] = $post['page'] = $vars['page'] = $_page;
		}
		else {
			$str = sprintf($_calendarlong_plugin_empty,make_pagelink(sprintf('%s%4d-%02d-%02d',$prefix, $today['year'], $today['mon'], $today['mday'])));
		}

		$ret .= "  </td>\n  <td valign=\"top\">$str</td>\n </tr>\n</table>\n";
	}

	return $ret;
}

function plugin_calendarlong_action()
{
	global $vars;

	$page = strip_bracket($vars['page']);
	$vars['page'] = '*';
	if ($vars['file'])
	{
		$vars['page'] = $vars['file'];
	}

	$date = $vars['date'];

	if ($date == '')
	{
		$date = get_date("Ym");
	}
	$yy = sprintf("%04d.%02d",substr($date,0,4),substr($date,4,2));

	$aryargs = array($vars['page'],$date);
	$s_page = htmlsc($vars['page']);

	$ret['msg'] = "calendar $s_page/$yy";
	$ret['body'] = call_user_func_array('plugin_calendarlong_convert',$aryargs);

	$vars['page'] = $page;

	return $ret;
}




?>
