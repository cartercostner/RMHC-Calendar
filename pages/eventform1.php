<?php
//sanity check
if (empty($lcV)) { exit('not permitted ('.substr(basename(__FILE__),0,-4).')'); } //launch via script only

//functions
function catMenu($selCat) {
	global $usr;

	$eCats = $usr['eCats'];
	$stH = dbQuery("SELECT `ID`,`name`,`color`,`bgColor` FROM `categories` WHERE `status` >= 0".($eCats != '0' ? " AND `ID` IN ($eCats)" : '')." ORDER BY sequence");
	echo "\n<select name='cid' id='cid' onChange=\"document.forms['event'].submit();\">\n";
	while (list($ID,$name,$color,$bgColor) = $stH->fetch(PDO::FETCH_NUM)) {
		$selected = ($selCat == $ID) ? ' selected' : '';
		$catColor = ($color ? "color:{$color};" : '').($bgColor ? "background-color:{$bgColor};" : '');
		echo "<option value='{$ID}'".($catColor ? " style='{$catColor}'" : '')."{$selected}>{$name}</option>\n";
	}
	echo "</select>\n";
}

function scatMenu($sid) {
	global $cat;
	
	$options = '';
	foreach ($cat['sub'] as $k => $scat) {
		$i = $k + 1;
		if (empty($scat[0])) { continue; } //no name, no subcat
		$selected = $sid == $i ? ' selected' : '';
		$color = ($scat[1] ? "color:{$scat[1]};" : '').($scat[2] ? "background-color:{$scat[2]};" : '');
		$options .= "<option value='{$i}'".($color ? " style='{$color}'" : '')."{$selected}>{$scat[0]}</option>\n";
	}
	echo "\n<select name='sid' id='sid'>\n";
	echo $options ? $options : "<script>showX('scMenu',0)</script>\n";
	echo "</select>\n";
}

function userMenu($selUser) {
	$stH = dbQuery("SELECT `ID`,`name` FROM `users` WHERE `status` >= 0 ORDER BY `name`");
	while (list($ID,$name) = $stH->fetch(PDO::FETCH_NUM)) {
		$selected = ($selUser == $ID) ? ' selected' : '';
		echo "<option value='{$ID}'{$selected}>{$name}</option>\n";
	}
}

//init
$evtTemplTot = $set['evtTemplGen'].$set['evtTemplUpc'].$set['evtTemplPop'];
if ($set['evtWinSmall']) { //reduced Event window
	$eExt = isset($_POST['eExt']) ? $_POST['eExt'] : 0;
} else {
	$eExt = 1;
}
echo "<form id='event' name='event' method='post' enctype='multipart/form-data'>
{$formCal}
<input type='hidden' name='xP' value='30'>
<input type='hidden' name='mode' value='{$mode}'>
<input type='hidden' name='eid' value='{$eid}'>
<input type='hidden' name='evD' value='{$evD}'>
<input type='hidden' name='oUid' value='{$oUid}'>
<input type='hidden' name='ediN' value='{$ediN}'>
<input type='hidden' name='eExt' value='{$eExt}'>
<input type='hidden' id='att' name='att' value='{$att}'>\n";
if (strpos($evtTemplTot,'4') !== false and $usr['privs'] < $set['xField1Rights']) {
	echo "<input type='hidden' name='xf1' value='{$xf1}'>\n";
}
if (strpos($evtTemplTot,'5') !== false and $usr['privs'] < $set['xField2Rights']) {
	echo "<input type='hidden' name='xf2' value='{$xf2}'>\n";
}
if ($cat['app'] and $usr['privs'] > 3) { //manager or admin
	echo "<div class='apdBar'><input type='checkbox' name='apd' id='apd' value='yes'".($apd ? " checked" : '')."><label for='apd'>{$xx['evt_approved']}</label></div>\n";
}
echo "<div class='evtCanvas'>\n";
echo "<table class='evtForm'>\n<colgroup><col class='c11'><col class='c12'><col class='c13'><col></colgroup>\n";
echo "<tr>
<td>{$xx['evt_title']}:</td><td colspan='2'><input type='text' name='tit' id='tit' style='width:95%' maxlength='255' value=\"{$tit}\"></td>\n";
echo "<td>";
if ($usr['ID'] > 1 and $usr['pEvts'] and ($set['privEvents'] == 1 or $set['privEvents'] == 2)) { //logged in - private allowed
	$checked = ($pri or ($set['privEvents'] == 2 and $mode =='add' and !$eMsg)) ? " checked" : '';
	echo "<input type='checkbox' name='pri' id='pri' value='yes'{$checked}><label for='pri'>{$xx['evt_private']}</label>";
}
echo "</td>\n</tr>\n";
if (strpos($evtTemplTot,'1') !== false) {
	$placeHolder = $set['mapViewer'] ? " placeholder='{$xx['evt_address_button']}'" : '';
	echo "<tr>\n<td>{$xx['evt_venue']}:</td><td colspan='3'><input type='text' name='ven' id='ven'{$placeHolder} style='width:98%' maxlength='128' value=\"{$ven}\"></td>\n</tr>\n";
}
//category always required
echo "<tr>\n<td>{$xx['evt_category']}:</td><td>"; catMenu($cid); echo "</td>";
echo "<td colspan='2'><div id='scMenu'>{$xx['evt_subcategory']}:&nbsp;&nbsp;"; scatMenu($sid); echo "</div></td>\n</tr>\n";

if ($ald) {
	$hidden = " style=\"visibility: hidden;\"";
	$checked= " checked";
} else {
	$hidden = $checked = '';
}
echo "<tr>
	<td>{$xx['evt_start_date']}:</td>
	<td><input type='text' name='sda' id='sda' value='{$sda}' size='9'><span class='dtPick' title=\"{$xx['evt_select_date']}\" onclick=\"dPicker(0,'','sda','eda');return false;\">&#x1F4C5;</span></td>
	<td id='dTimeS'{$hidden}><input type='text' name='sti' id='sti' value='{$sti}' size='6'><span class='dtPick' title=\"{$xx['evt_select_time']}\" onclick=\"tPicker('sti');return false;\">&#x1F552;</span></td>";
if (!$cat['fur']) { //no fixed event duration
	echo "<td><input type='checkbox' onclick=\"hideTimes(this);\" name='ald' id='ald' value='1'{$checked}><label for='ald'>{$xx['evt_all_day']}</label></td>";
} else {
	echo "<td></td>";
}
echo "</tr>\n";
if (!$cat['fur']) { //no fixed event duration
	echo "<tr>\n";
	if ($usr['mEvts']) {
		echo "<td>{$xx['evt_end_date']}:</td>
		<td><input type='text' name='eda' id='eda' value='{$eda}' size='9'><span class='dtPick' title=\"{$xx['evt_select_date']}\" onclick=\"dPicker(1,'','eda','sda');return false;\">&#x1F4C5;</span></td>\n";
	} else {
		echo "<td colspan='2'></td>";
	}
	echo "<td id='dTimeE'{$hidden}><input type='text' name='eti' id='eti' value='{$eti}' size='6'><span class='dtPick' title=\"{$xx['evt_select_time']}\" onclick=\"tPicker('eti');return false;\">&#x1F552;</span></td>";
	echo "<td></td>\n</tr>\n";
}
if ($usr['rEvts']) { //repeating allowed
	echo "<tr>
		<td colspan='4'>{$repTxt}&nbsp;<button class='noPrint' type='button' onclick=\"showX('repBox',1);\">{$xx['evt_change']}</button><br></td>
	</tr>\n";
}
echo "</table>\n";
if ($set['evtWinSmall']) { //reduced Event window
	$sign = $eExt ? '&#10134;' : '&#10133;'; //+ or -
	echo "<div class='evtExt' onclick=\"ewShow('eExt');\"><span id='eExtS' title='More details'>{$sign}</span></div>\n";
} else {
	echo "<div class='evtExt'></div>\n";
}

$display = $eExt ? 'block' : 'none';
echo "<div id='eExt' style='display:{$display};'>\n";
echo "<table class='evtForm'>\n<colgroup><col class='c11'><col><col><col></colgroup>\n";
if (strpos($evtTemplTot,'3') !== false) {
	$descrHelp = $xx['evt_descr_help'];
	if ($set['showImgInMV']) { $descrHelp .= '<br>'.$xx['evt_descr_help_img']; }
	$descrHelp .= '<br>'.$xx['evt_descr_help_eml'].'<br>'.$xx['evt_descr_help_url'];
	echo "<tr>\n<td>{$xx['evt_description']}:<br><br><span class='point noPrint' onmouseover=\"pop(this,'".htmlspecialchars($descrHelp)."','normal',80)\">(&#10067;)</span></td>";
	echo "<td colspan='3'><textarea name='des' id='des' rows='3' cols='1' style='width:98%'>{$des}</textarea></td>\n</tr>\n";
}
if (strpos($evtTemplTot,'4') !== false and $usr['privs'] >= $set['xField1Rights']) {
	echo "<tr>\n<td>".($set['xField1Label'] ? "{$set['xField1Label']}" : $xx['sch_extra_field1']).":</td><td colspan='3'><textarea name='xf1' id='xf1' rows='1' cols='1' style='width:98%' maxlength='255'>{$xf1}</textarea></td>\n</tr>\n";
}
if (strpos($evtTemplTot,'5') !== false and $usr['privs'] >= $set['xField2Rights']) {
	echo "<tr>\n<td>".($set['xField2Label'] ? "{$set['xField2Label']}" : $xx['sch_extra_field2']).":</td><td colspan='3'><textarea name='xf2' id='xf2' rows='1' cols='1' style='width:98%' maxlength='255'>{$xf2}</textarea></td>\n</tr>\n";
}
if ($att) {
	$label = $xx['evt_attachments'].':';
	foreach(explode(';',trim($att,';')) as $attachment) {
		echo "<tr>\n<td>{$label}</td><td colspan='3'><span class='select' title='{$xx['evt_click_to_remove']}' onclick=\"detach(this,'{$attachment}');\">&nbsp;&#10060;&nbsp;</span><a title='{$xx['evt_click_to_open']}' href='dloader.php?ftd=./attachments/".rawurlencode($attachment)."&amp;nwN=".substr($attachment,14)."'>".substr($attachment,14)."</a></td>\n</tr>\n";
		$label = '';
	}
}
if ($usr['upload']) { //may upload
	echo "<tr>\n<td>{$xx['evt_attach_file']}:</td><td colspan='3'><input type='file' id='uplAtt' name='uplAtt[]' multiple>&nbsp;&nbsp;({$xx['max']} {$set['maxUplSize']} MB)</td>\n</tr>";
}
$maySms = ($set['smsService'] and $usr['sndSms']);
if ($set['emlService'] or $maySms) {
	$hide = $set['emlService'] ? '' : " class='hide'";
	echo "<tr>\n<td colspan='4'><hr></td>\n</tr>\n";
	echo "<tr{$hide}>\n<td>{$xx['evt_send_eml']}:</td><td colspan='3'>";
	if ((!$cat['app'] or $apd)) { echo "<input type='checkbox' name='nen' id='nen' value='yes'".($nen ? " checked" : '')."><label for='nen'>{$xx['evt_now_and_or']}</label>&nbsp;"; }
	echo "<input type='text' name='nom' style='width:20px' maxlength='2' value=\"{$nom}\"> {$xx['evt_days_before_event']}
	</td>\n</tr>";
	$hide = $maySms ? '' : " class='hide'";
	echo "<tr{$hide}>\n<td>{$xx['evt_send_sms']}:</td><td colspan='3'>";
	if ((!$cat['app'] or $apd)) { echo "<input type='checkbox' name='nsn' id='nsn' value='yes'".($nsn ? " checked" : '')."><label for='nsn'>{$xx['evt_now_and_or']}</label>&nbsp;"; }
	echo "<input type='text' name='nos' style='width:20px' maxlength='2' value=\"{$nos}\"> {$xx['evt_days_before_event']}
	</td>\n</tr>";
	$notHelp = $xx['evt_not_help'];
	if ($set['emlService']) {
		$notHelp .= '<br>'.$xx['evt_eml_help'];
	}
	if ($set['smsService']) {
		$notHelp .= '<br>'.$xx['evt_sms_help'];
	}
	echo "<tr>
		<td>{$xx['evt_to']}:</td>
		<td colspan='3'>
		<input type='text' name='nal' id='nal' style='width:90%' maxlength='255' value=\"{$nal}\">
		<span class='floatR point noprint' onmouseover=\"pop(this,'".htmlspecialchars($notHelp)."','normal',80)\"><b>(&#10067;)</b></span>
		</td>\n</tr>\n";
}
if (strpos($set['evtTemplGen'],'7') !== false) {
	echo "<tr>\n<td colspan='4'><hr></td>\n</tr>\n";
	echo "<tr>\n<td>{$xx['evt_added']}:</td><td colspan='3'>".IDTtoDDT($_SESSION['evt']['adt'])." {$xx['by']} ";
	if ($usr['privs'] > 3) { //manager or admin
		echo "<select name='uid' id='uid'>\n"; userMenu($uid); echo "</select>\n";
	} else {
		echo $_SESSION['evt']['own'];
	}
	if ($_SESSION['evt']['mdt'] and $_SESSION['evt']['edr']) {
		echo "</td>\n</tr>\n<tr>\n<td>{$xx['evt_edited']}:</td><td colspan='3'>".IDTtoDDT($_SESSION['evt']['mdt'])." {$xx['by']} {$_SESSION['evt']['edr']}"; }
	echo "</td>\n</tr>\n";
}
echo "</table>\n";
echo "</div>\n";

if ($usr['rEvts']) { //make repeat box
	echo "<div class='repBox' id='repBox'>
<div class='floatC'><b>{$xx['evt_set_repeat']}</b></div>
<div>
<input type='radio' name='r_t' id='r_t0' value='0'".(!$r_t ? " checked" : '')."><label for='r_t0'>{$xx['evt_no_repeat']}</label>
<br>
<input type='radio' name='r_t' id='r_t1' value='1'".($r_t == "1" ? " checked" : '')."><label for='r_t1'>{$xx['evt_repeat_on']}</label>
<input type='number' min='1' max='99' name='ri1' style='width:30px' onclick=\"\$I('r_t1').checked=true\" value='{$ri1}'>
<select name='rp1' id='rp1' onclick=\"\$I('r_t1').checked=true\">\n";
	for ($i = 1; $i < 5; $i++) { echo "<option value='{$i}'".($rp1 == $i ? ' selected' : '').">".$xx["evt_period1_{$i}"]."</option>\n"; }
echo "</select>
<br>
<input type='radio' name='r_t' id='r_t2' value='2'".($r_t == "2" ? " checked" : '')."><label for='r_t2'>{$xx['evt_repeat_on']}</label> 
<select name='ri2' id='ri2' onclick=\"\$I('r_t2').checked=true\">\n";
for ($i = 1; $i < 6; $i++) { echo "<option value='{$i}'".($ri2 == $i ? ' selected' : '').">".$xx["evt_interval2_{$i}"]."</option>\n"; }
echo "</select>\n";
echo "<select name='rp2' id='rp2' onclick=\"\$I('r_t2').checked=true\">\n";
for ($i = 1; $i < 8; $i++) { echo "<option value='{$i}'".($rp2 == $i ? ' selected' : '').">{$wkDays[$i]}</option>\n"; }
echo "</select>
		{$xx['of']} ";
echo "<select name='rpm' id='rpm' onclick=\"\$I('r_t2').checked=true\">
<option value='0'".($r_m == 0 ? " selected" : '').">{$xx['evt_each_month']}</option>\n";
for ($i = 1; $i < 13; $i++) { echo "<option value='{$i}'".($r_m == $i ? ' selected' : '').">{$months[$i-1]}</option>\n"; }
echo "</select>
<br><br>{$xx['evt_until']} 
<input type='text' name='rul' id='rul' value='{$rul}' size='9'><span class='dtPick' title=\"{$xx['evt_select_date']}\" onclick=\"dPicker(1,'','rul','sda');return false;\">&#x1F4C5;</span> ({$xx['evt_blank_no_end']})
</div>
<br>
<div class='floatC'><button type='submit' name='refresh' value='1'>{$xx['evt_set']}</button></div>
</div>\n";
}
echo "</div>\n";

echo "<div class='ewButtons floatC noPrint'>\n";
if ($mode[0] == 'a') { //add
	echo "<button type='submit' name='action' value='add_exe_cls'>{$xx['evt_add_close']}</button>
&nbsp;&nbsp;<button type='submit' name='action' value='add_exe'>{$xx['evt_add']}</button>";
} else { //edit
	echo "<button type='submit' name='action' value='upd_exe_cls'>{$xx['evt_save_close']}</button>
		&nbsp;&nbsp;<button type='submit' name='action' value='upd_exe'>{$xx['evt_save']}</button>
		&nbsp;&nbsp;<button type='submit' name='action' value='add_exe'>{$xx['evt_clone']}</button>\n";
	if ($set['evtDelButton'] == 1 or ($set['evtDelButton'] == 2 and $usr['privs'] > 3)) {
		echo "&nbsp;&nbsp;<button type='submit' name='action' value='del_exe_cls' onclick=\"return confirm('{$xx['evt_delete']} {$tit}?');\">{$xx['evt_delete']}</button>\n";
	}
}
echo "&nbsp;&nbsp;<button type='button' onclick='javascript:self.close();'>{$xx['evt_close']}</button>
</div>
</form>\n";
?>
<script>$I("tit").focus();</script>
