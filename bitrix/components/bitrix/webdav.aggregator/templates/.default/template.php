<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die(); ?>
<?
if (isset($_REQUEST['AJAX']))
{
    $APPLICATION->RestartBuffer();
} else {
    if (isset($_REQUEST['help']))
    {

        $APPLICATION->IncludeComponent( "bitrix:webdav.help", "", Array(), false);
        exit;
    } else {
?>
<table style="width:100%" cellpadding="0" cellspacing="0">
<tr><td style="vertical-align:top;">
<?
        CUtil::InitJSCore(array('ajax'));
        echo "<div id='wd_aggregator_tree'>\n";
    }
}
?>
<?
$curdepth = -1;
foreach ($arResult['STRUCTURE'] as $node)
{
    $depth = $node['DEPTH_LEVEL'];
    if ($depth > $curdepth) echo "<ul>\n";
    while ($depth < $curdepth--) echo "</ul>\n";
    if ($depth >= 0)
    {
        $link = rtrim($arParams['SEF_FOLDER'],'/') . $node['PATH'];
        if (isset($_REQUEST['AJAX']))
            $class = 'wd_aggregator_remote';
        else
            $class = 'wd_aggregator_' . (isset($node['MODE'])?$node['MODE']:'remote');
        echo "<li class=\"{$class}\"><a href=\"{$link}\">{$node['NAME']}</a></li>\n";
    }
    $curdepth = $depth;
}
while (0 <= $curdepth--) echo "</ul>\n";
?>

<?
if (!isset($_REQUEST['AJAX']))
{
?>
</div>
</td>
<td width="50%" style="vertical-align:top;">
<?  $url = ($GLOBALS["APPLICATION"]->IsHTTPS() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$arParams['SEF_FOLDER']; ?>

<div id="wd_ag_bnr_user_search_outlook" class="wd-ag-bnr">
    <div class="wd-ag-bnr-head"><span style='margin:8px 0 0 8px; font-weight:bold; line-height:20px;'><?=GetMessage("WD_AG_MSGTITLE");?></span></div>
    <div class="wd-ag-bnr-body">
        <div class="wd-ag-bnr-icon"></div>
        <div class="wd-ag-bnr-content wd-ag-bnr-margin">
<ul>
<li><?=GetMessage("WD_AG_HELP1");?></li>
<li><?=GetMessage("WD_AG_HELP2");?></li>
<li><?=GetMessage("WD_AG_HELP3");?></li>
<li><?=GetMessage("WD_AG_HELP4");?> <nobr><a href="<?=$url?>"><?=$url?></a></nobr>. </li>
<li><?=GetMessage("WD_AG_HELP5");?></li>
</ul>
        </div>
<br />
<p><?=GetMessage("WD_AG_HELP6", array("#STARTLINK#" => "<a href='".$APPLICATION->GetCurPageParam("help=Y")."'>", "#ENDLINK#" => "</a>"));?></p>
        <div style="clear: both;"></div>
    </div>
</div>

</td>
</tr>
</table>

<script language='JavaScript'>
    function wd_dl_parseDoclistUl(ul)
    {
        if (!ul) return {};
        var rows = new Array();
        var li = BX.findChild(ul, {tag:'li'}, false);
        while (li != null)
        {
            rows.push(li);
            var subul = BX.findChild(li, {tag:'ul'}, false);
            if (subul)
            {
                rows.concat(parseDoclistUl(subul));
            }
            li = BX.findNextSibling(li, {tag:'li'}, false);
        }
        return rows;
    }

    function wd_dl_loadNode(result, target, show)
    {
        BX.closeWait(target, show);
        var tmp = document.getElementById('aggregator_temp');
        if (null == tmp)
        {
            tmp = BX.create('div', {props:{'id':'tmp'}, style:{'display':'none'}}, document);
        } 
        tmp.innerHTML = result;
        var parentUl = BX.findChild(tmp, {tag:'ul'}, false);
        var childUl = BX.findChild(parentUl, {tag:'ul'}, true); 
        var link = BX.findChild(target, {tag:'a'}, false);
        BX.unbindAll(link);
        if (childUl != null)
        {
            wd_dl_initTreeEvents(target.appendChild(childUl)); 
            BX.addClass(BX.removeClass(target, 'wd_aggregator_local'), 'wd_aggregator_expanded');
            BX.bind(link, 'click', function(e){wd_dl_collapseNode(this); return BX.PreventDefault(e);});
        } else {
            emptyUl = BX.create('ul');
            emptyLi = BX.create('li', {attrs:{'class':'wd_ag_empty'}});
            emptyLi.innerHTML = "<?=GetMessage('WD_NO_LIBRARIES');?>";
            emptyUl.appendChild(emptyLi);
            target.appendChild(emptyUl);
            BX.addClass(BX.removeClass(target, 'wd_aggregator_local'), 'wd_aggregator_empty');
            BX.bind(link, 'click', function(e){wd_dl_collapseNode(this); return BX.PreventDefault(e);});
        }
        BX.remove(tmp);
    }

    function wd_dl_collapseNode(tthis)
    {
        var ul = BX.findChild(tthis.parentNode, {tag:'ul'}, false);
        BX.remove(ul);
        BX.unbindAll(tthis);
        BX.addClass(BX.removeClass(tthis.parentNode, 'wd_aggregator_expanded'), 'wd_aggregator_local');
        BX.bind(tthis, 'click', function(e){wd_dl_expandNode(this); return BX.PreventDefault(e);});
    }

    function wd_dl_expandNode(tthis)
    {
        var href = tthis.href;
        href += ((href.indexOf('?') > 0) ? '&' : '?');
        href += 'AJAX=Y';
        var show = BX.showWait(tthis.parentNode);
        BX.unbindAll(tthis);
        BX.bind(tthis, 'click', function(e){return BX.PreventDefault(e);});
        BX.ajax.get(href, null, function(result) {wd_dl_loadNode(result, tthis.parentNode, show);});
    }

    function wd_dl_initTreeEvents(node)
    {
        var lis = wd_dl_parseDoclistUl(node);
        for (var i=0;i<lis.length;i++)
        {
            var li = lis[i];
            var link = BX.findChild(li, {tag:'a'}, false);
            if (li.className.indexOf('wd_aggregator_local') != -1) 
            {
                BX.bind(link, 'click', function(e){wd_dl_expandNode(this); return BX.PreventDefault(e);});
            }
        }
    }

BX.ready(function() {
    var div = document.getElementById('wd_aggregator_tree');
    var ul = BX.findChild(div, {tag:'ul'}, true);
    wd_dl_initTreeEvents(ul);
});
</script>
<?
} else {
    die();
}
?>
