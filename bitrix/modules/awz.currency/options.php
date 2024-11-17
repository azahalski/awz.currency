<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;

Loc::loadMessages(__FILE__);
global $APPLICATION;
$module_id = "awz.currency";
$MODULE_RIGHT = $APPLICATION->GetGroupRight($module_id);
$zr = "";
if (! ($MODULE_RIGHT >= "R"))
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

$APPLICATION->SetTitle(Loc::getMessage('AWZ_CURRENCY_OPT_TITLE'));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

Loader::includeModule($module_id);

if ($_SERVER["REQUEST_METHOD"] == "POST" && $MODULE_RIGHT == "W" && strlen($_REQUEST["Update"]) > 0 && check_bitrix_sessid())
{
    Option::set($module_id, "LOAD_NBRB", $_REQUEST["LOAD_NBRB"]=='Y' ? 'Y' : 'N', "");
    Option::set($module_id, "LOAD_CBRF", $_REQUEST["LOAD_CBRF"]=='Y' ? 'Y' : 'N', "");
    Option::set($module_id, "LOAD_PREW", $_REQUEST["LOAD_PREW"]=='Y' ? 'Y' : 'N', "");
    Option::set($module_id, "LOAD_BX", $_REQUEST["LOAD_BX"]=='Y' ? 'Y' : 'N', "");
    Option::set($module_id, "LOAD_BX_MAIN", $_REQUEST["LOAD_BX_MAIN"]=='Y' ? 'Y' : 'N', "");
    Option::set($module_id, "LOAD_CODES", preg_replace('/([^A-Z,])/is','',mb_strtoupper($_REQUEST["LOAD_CODES"])), "");
    if($_REQUEST["LOAD_CBRF"] == 'Y'){
        \Awz\Currency\Agents::getDayRf();
    }
    if($_REQUEST["LOAD_NBRB"] == 'Y'){
        \Awz\Currency\Agents::getDayRb();
    }
    if($_REQUEST["LOAD_BX_MAIN"] == 'Y') {
        \Awz\Currency\Agents::updateBxCurs();
    }
}

$aTabs = array();

$aTabs[] = array(
    "DIV" => "edit1",
    "TAB" => Loc::getMessage('AWZ_CURRENCY_OPT_SECT1'),
    "ICON" => "vote_settings",
    "TITLE" => Loc::getMessage('AWZ_CURRENCY_OPT_SECT1')
);

$aTabs[] = array(
    "DIV" => "edit3",
    "TAB" => Loc::getMessage('AWZ_CURRENCY_OPT_SECT2'),
    "ICON" => "vote_settings",
    "TITLE" => Loc::getMessage('AWZ_CURRENCY_OPT_SECT2')
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();
?>
    <style>.adm-workarea option:checked {background-color: rgb(206, 206, 206);}</style>
    <form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($module_id)?>&lang=<?=LANGUAGE_ID?>&mid_menu=1" id="FORMACTION">
        <?
        $tabControl->BeginNextTab();
        ?>

        <tr>
            <td style="width:200px;"><?=Loc::getMessage('AWZ_CURRENCY_OPT_LOAD_NBRB_TITLE')?></td>
            <td>
                <?$val = Option::get($module_id, "LOAD_NBRB", "N","");?>
                <input type="checkbox" value="Y" name="LOAD_NBRB" <?if ($val=="Y") echo "checked";?>></td>
            </td>
        </tr>


        <tr>
            <td style="width:200px;"><?=Loc::getMessage('AWZ_CURRENCY_OPT_LOAD_CBRF_TITLE')?></td>
            <td>
                <?$val = Option::get($module_id, "LOAD_CBRF", "N","");?>
                <input type="checkbox" value="Y" name="LOAD_CBRF" <?if ($val=="Y") echo "checked";?>></td>
            </td>
        </tr>
        <tr>
            <td style="width:200px;"><?=Loc::getMessage('AWZ_CURRENCY_OPT_LOAD_CODES_TITLE')?></td>
            <td>
                <?$val = Option::get($module_id, "LOAD_CODES", "","");?>
                <input type="text" name="LOAD_CODES" value="<?=$val?>"></td>
            </td>
        </tr>
        <?if(\Bitrix\Main\Loader::includeModule('currency')){?>
        <tr>
            <td style="width:200px;"><?=Loc::getMessage('AWZ_CURRENCY_OPT_LOAD_BX_TITLE')?></td>
            <td>
                <?$val = Option::get($module_id, "LOAD_BX", "N","");?>
                <input type="checkbox" value="Y" name="LOAD_BX" <?if ($val=="Y") echo "checked";?>></td>
            </td>
        </tr>
            <tr>
                <td style="width:200px;"><?=Loc::getMessage('AWZ_CURRENCY_OPT_LOAD_BX_MAIN_TITLE')?></td>
                <td>
                    <?$val = Option::get($module_id, "LOAD_BX_MAIN", "N","");?>
                    <input type="checkbox" value="Y" name="LOAD_BX_MAIN" <?if ($val=="Y") echo "checked";?>></td>
                </td>
            </tr>
        <?}?>

        <tr>
            <td style="width:200px;"><?=Loc::getMessage('AWZ_CURRENCY_OPT_LOAD_PREW_TITLE')?></td>
            <td>
                <?$val = Option::get($module_id, "LOAD_PREW", "N","");?>
                <input type="checkbox" value="Y" name="LOAD_PREW" <?if ($val=="Y") echo "checked";?>></td>
            </td>
        </tr>

        <?
        $tabControl->BeginNextTab();
        require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
        ?>

        <?
        $tabControl->Buttons();
        ?>
        <input <?if ($MODULE_RIGHT<"W") echo "disabled" ?> type="submit" class="adm-btn-green" name="Update" value="<?=Loc::getMessage('AWZ_CURRENCY_OPT_L_BTN_SAVE')?>" />
        <input type="hidden" name="Update" value="Y" />
        <?$tabControl->End();?>
    </form>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");