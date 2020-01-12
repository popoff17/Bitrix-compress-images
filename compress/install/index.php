<?
IncludeModuleLangFile(__FILE__);

Class compress extends CModule
{
	var $MODULE_ID = "compress";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;

	var $errors;

	function compress()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		else
		{
			$this->MODULE_VERSION = "demo";
			$this->MODULE_VERSION_DATE = "";
		}

		$this->MODULE_NAME = GetMessage("MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("MODULE_DESC");
	}

	function InstallDB($arParams = array())
	{
		global $DBType, $APPLICATION;
		$this->errors = false;

		$node_id = strlen($arParams["DATABASE"]) > 0? intval($arParams["DATABASE"]): false;
		if($node_id !== false)
			$DB = $GLOBALS["DB"]->GetDBNodeConnection($node_id);
		else
			$DB = $GLOBALS["DB"];

		// Database tables creation
		if(!$DB->Query("SELECT 'x' FROM b_compress_content WHERE 1=0", true))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/compress/install/db/".strtolower($DB->type)."/install.sql");
			if($this->errors === false && strtolower($DB->type) == "mssql")
			{
				/*  */
			}
		}

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}
		else
		{
			RegisterModule("compress");
			CModule::IncludeModule("compress");

			if($node_id !== false)
			{
				COption::SetOptionString("compress", "dbnode_id", $node_id);
				if(CModule::IncludeModule('cluster'))
					CClusterDBNode::SetOnline($node_id);
			}
			else
			{
				COption::SetOptionString("compress", "dbnode_id", "N");
			}
			COption::SetOptionString("compress", "dbnode_status", "ok");

			CSearchStatistic::SetActive(COption::GetOptionString("compress", "stat_phrase")=="Y");

			return true;
		}
	}

	function UnInstallDB($arParams = array())
	{
		global $DBType, $APPLICATION;
		$this->errors = false;
		$DB = CDatabase::GetModuleConnection('compress', true);

		if(is_object($DB))
		{
			if(!array_key_exists("savedata", $arParams) || ($arParams["savedata"] != "Y"))
			{
				$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/compress/install/db/".strtolower($DB->type)."/uninstall.sql");
			}

		}

		UnRegisterModule("compress");

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}

		return true;
	}

	function InstallEvents()
	{
		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}

	function InstallFiles($arParams = array())
	{
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/compress/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/compress/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/compress", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/compress/install/images", $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/compress", true, true);
		}

		$bReWriteAdditionalFiles = ($arParams["public_rewrite"] == "Y");

		if(array_key_exists("public_dir", $arParams) && strlen($arParams["public_dir"]))
		{
			$by = "sort";
			$order = "asc";
			$rsSite = CSite::GetList($by, $order);
			while ($site = $rsSite->Fetch())
			{
				$source = $_SERVER['DOCUMENT_ROOT']."/bitrix/modules/compress/install/public/";
				$target = $site['ABS_DOC_ROOT'].$site["DIR"].$arParams["public_dir"]."/";
				if(file_exists($source))
				{
					CheckDirPath($target);
					$dh = opendir($source);
					while($file = readdir($dh))
					{
						if($file == "." || $file == "..")
							continue;
						if($bReWriteAdditionalFiles || !file_exists($target.$file))
						{
							$fh = fopen($source.$file, "rb");
							$php_source = fread($fh, filesize($source.$file));
							fclose($fh);
							if(preg_match_all('/GetMessage\("(.*?)"\)/', $php_source, $matches))
							{
								IncludeModuleLangFile($source.$file, $site["LANGUAGE_ID"]);
								foreach($matches[0] as $i => $text)
								{
									$php_source = str_replace(
										$text,
										'"'.GetMessage($matches[1][$i]).'"',
										$php_source
									);
								}
							}
							$fh = fopen($target.$file, "wb");
							fwrite($fh, $php_source);
							fclose($fh);
						}
					}
				}
			}
		}

		return true;
	}

	function UnInstallFiles()
	{
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/compress/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
			DeleteDirFilesEx("/bitrix/images/compress/");//images
			DeleteDirFilesEx("/bitrix/js/compress/");//javascript
			DeleteDirFilesEx("/bitrix/components/compress");
		}
		return true;
	}

	function DoInstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = intval($step);
		if($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/compress/install/step1.php");
		}
		elseif($step == 2)
		{
			$db_install_ok = $this->InstallDB(array(
				"DATABASE" => $_REQUEST["DATABASE"],
			));
			if($db_install_ok)
			{
				$this->InstallEvents();
				$this->InstallFiles(array(
					"public_dir" => $_REQUEST["public_dir"],
					"public_rewrite" => $_REQUEST["public_rewrite"],
				));
			}
			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/compress/install/step2.php");
		}
	}

	function DoUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = intval($step);
		/* if($step < 2)
		{ */
			//$APPLICATION->IncludeAdminFile(GetMessage("UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/compress/install/unstep1.php");
		/* }
		elseif($step == 1)
		{ */
			$this->UnInstallDB(array(
				"savedata" => $_REQUEST["savedata"],
				"savestat" => $_REQUEST["savestat"],
			));
			$this->UnInstallFiles();
			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/compress/install/unstep2.php");
		/* } */
	}

	public static function OnGetTableList()
	{
		return array(
			"MODULE" => new compress,
			"TABLES" => array(
				"b_search_content" => "ID",
				"b_search_content_right" => "SEARCH_CONTENT_ID",
				"b_search_content_site" => "SEARCH_CONTENT_ID",
				"b_search_content_stem" => "SEARCH_CONTENT_ID",
				"b_search_content_title" => "SEARCH_CONTENT_ID",
				"b_search_content_freq" => "STEM",
				"b_search_custom_rank" => "ID",
				"b_search_tags" => "SEARCH_CONTENT_ID",
				"b_search_suggest" => "ID",
				"b_search_phrase" => "ID",
				"b_search_user_right" => "USER_ID",
				"b_search_content_param" => "SEARCH_CONTENT_ID",
				"b_search_stem" => "ID",
				"b_search_content_text" => "SEARCH_CONTENT_ID",
			),
		);
	}

	public static function OnGetTableSchema()
	{
		return array(
			"search" => array(
				"b_search_content" => array(
					"ID" => array(
						"b_search_content_stem" => "SEARCH_CONTENT_ID",
						"b_search_content_text" => "SEARCH_CONTENT_ID",
						"b_search_content_param" => "SEARCH_CONTENT_ID",
						"b_search_content_right" => "SEARCH_CONTENT_ID",
						"b_search_content_site" => "SEARCH_CONTENT_ID",
						"b_search_content_title" => "SEARCH_CONTENT_ID",
						"b_search_tags" => "SEARCH_CONTENT_ID",
					)
				),
				"b_search_stem" => array(
					"ID" => array(
						"b_search_content_stem" => "STEM",
						"b_search_content_freq" => "STEM",
					),
				),
			),
			"main" => array(
				"b_user" => array(
					"ID" => array(
						"b_search_user_right" => "USER_ID",
					)
				),
				"b_lang" => array(
					"LID" => array(
						"b_search_content_site" => "SITE_ID",
						"b_search_content_title" => "SITE_ID",
						"b_search_content_freq" => "SITE_ID",
						"b_search_custom_rank" => "SITE_ID",
						"b_search_tags" => "SITE_ID",
						"b_search_suggest" => "SITE_ID",
						"b_search_phrase" => "SITE_ID",
					)
				),
				"b_language" => array(
					"LID" => array(
						"b_search_content_stem" => "LANGUAGE_ID",
						"b_search_content_freq" => "LANGUAGE_ID",
					)
				),
				"b_module" => array(
					"ID" => array(
						"b_search_custom_rank" => "MODULE_ID",
					)
				),
			),
			"statistic" => array(
				"b_stat_session" => array(
					"ID" => array(
						"b_search_phrase" => "STAT_SESS_ID",
					)
				),
			),
		);
	}
}
?>