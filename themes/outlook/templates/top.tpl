<div style="padding: 4px;">
    <table border="0" cellpadding="0" cellspacing="0" bgcolor="#606060" width="100%" align="center">
        <tr>
            <td width="100%">
                <table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#00309C">
                    <tr>
                        <td width="12" align="left" bgcolor="#D5E8F4" background="{$themedir}images/left.gif">&nbsp;</td>
                        <td height="22" bgcolor="#0073CE" background="{$themedir}images/dark_back.gif" valign="middle" align="left" nowrap>
                            <b><small style="color:#ffffff;font-size:9pt"><img src="{$themedir}images/publixheader.gif" border="0" alt="">&nbsp;{$title}</small></b>
                        </td>
                        <td width="12" align="right" bgcolor="#D5E8F4" background="{$themedir}images/right.gif">&nbsp;</td>
                    </tr>
                </table>
                <table width="100%" border="0" cellpadding="9" cellspacing="2" bgcolor="#00309C">
                    <tr>
                        <td bgcolor="#EEEEE0" align="center" class="block">
                            <table width="100%"><tr>
                                    <td align="left" width="20%"></td>
                                    <td align="center" width="60%">
                                        {$logintext}: <b>{$user}</b> &nbsp;
                                        <a href="{$logoutlink}" target="{$logouttarget}">{$logouttext}</a> &nbsp;
                                    {if $centerpiece}{$centerpiece}{/if}
                                </td>
                                <td align="right" width="20%" nowrap>{if $searchpiece}{$searchpiece}</span>{/if}</td>
                            </tr></table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</div>
<br>