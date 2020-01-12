<?
IncludeModuleLangFile(__FILE__);
/** @global CMain $APPLICATION */
global $APPLICATION;

if($APPLICATION->GetGroupRight("compress")!="D")
{
	$aMenu = array(
		"parent_menu" => "global_menu_settings",
		"section" => "compress",
		"sort" => 200,
		"text" => "Сжатие изображений",
		"title" => "Сжатие изображений",
		"icon" => "iblock_menu_icon_iblocks",
		"page_icon" => "iblock_menu_icon_iblocks",
		"items_id" => "menu_compress",
		"items" => array(
			array(
				"text" => "Запуск сжатия",
				"url" => "compress.php?lang=".LANGUAGE_ID,
				"more_url" => Array("compress.php"),
				"title" => "Запуск сжатия",
			),
			array(
				"text" => "Лог",
				"url" => "compress_list.php?lang=".LANGUAGE_ID,
				"more_url" => Array("compress_list.php"),
				"title" => "Лог",
			),
		)
	);
	return $aMenu;
}
return false;
?>
