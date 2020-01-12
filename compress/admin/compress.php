<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/compress/prolog.php");
IncludeModuleLangFile(__FILE__);
/** @global CMain $APPLICATION */
global $APPLICATION;
/** @var CAdminMessage $message */
$searchDB = CDatabase::GetModuleConnection('compress');

$POST_RIGHT = $APPLICATION->GetGroupRight("compress");
if($POST_RIGHT=="D")
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

		function microtime_float() {
			list($usec, $sec) = explode(" ", microtime());
			return ((float)$usec + (float)$sec);
		}

		function FileListinfile($directory, $outputfile) {
          if ($handle = opendir($directory)) {
            while (false !== ($file = readdir($handle))) {
              if (is_file($directory.$file)) {
                  if ((strpos(mb_strtolower($file), '.jpg') > 0 ) or
                     (strpos(mb_strtolower($file), '.jpeg') > 0 ) or
                     (strpos(mb_strtolower($file), '.gif') > 0 ) or
                     (strpos(mb_strtolower($file), '.png') > 0 )) {
                         file_put_contents($outputfile, $directory.$file."\n", FILE_APPEND);
                         
                  }
              } elseif ($file != '.' and $file != '..' and is_dir($directory.$file)) {
                FileListinfile($directory.$file.'/', $outputfile);
              }
            }
          }
          closedir($handle);
        }

    class SimpleImage {
       
         var $image;
         var $image_type;
       
         function load($filename) {
			$image_info = getimagesize($filename);
			$this->image_type = $image_info[2];
            if( $this->image_type == IMAGETYPE_JPEG ) {
               $this->image = imagecreatefromjpeg($filename);
            } elseif( $this->image_type == IMAGETYPE_GIF ) {
               $this->image = imagecreatefromgif($filename);
            } elseif( $this->image_type == IMAGETYPE_PNG ) {
               $this->image = imagecreatefrompng($filename);
               imagealphablending($this->image, false);
               imagesavealpha($this->image, true);               
            } else {
               $this->image_type = false;
               return false;
            }
            return true;               
         }
         function save($filename, $image_type=IMAGETYPE_JPEG, $compression=75, $permissions=null) {
            if( $image_type == IMAGETYPE_JPEG ) {
               imageinterlace ($this->image ,1);
               imagejpeg($this->image,$filename,$compression);
            } elseif( $image_type == IMAGETYPE_GIF ) {
               imagegif($this->image,$filename);
            } elseif( $image_type == IMAGETYPE_PNG ) {
               imagealphablending($this->image, false);
               imagesavealpha($this->image, true);               
               imagepng($this->image,$filename);
            }
            if( $permissions != null) {
               chmod($filename,$permissions);
            }
         }
         function output($image_type=IMAGETYPE_JPEG) {

            if( $image_type == IMAGETYPE_JPEG ) {
               imageinterlace ($this->image ,1);
               imagejpeg($this->image);
            } elseif( $image_type == IMAGETYPE_GIF ) {
               imagegif($this->image);
            } elseif( $image_type == IMAGETYPE_PNG ) {
               imagepng($this->image);
            }
         }
         function getWidth() {
            return imagesx($this->image);
         }
         function getHeight() {
            return imagesy($this->image);
         }
         function resizeToHeight($height) {
            $ratio = $height / $this->getHeight();
            $width = $this->getWidth() * $ratio;
            $this->resize($width,$height);
         }
         function resizeToWidth($width) {
            $ratio = $width / $this->getWidth();
            $height = $this->getheight() * $ratio;
            $this->resize($width,$height);
         }
         function scale($scale) {
            $width = $this->getWidth() * $scale/100;
            $height = $this->getheight() * $scale/100;
            $this->resize($width,$height);
         }
         
         function resize($width,$height) {
            $new_image = imagecreatetruecolor($width, $height);
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
            $this->image = $new_image;
         }
         function cover ($width,$height) {
            /* Заполнить область */
            $w = $this->getWidth();
            if ($width != $w) {
              $this->resizeToWidth($width);
            }
            $h = $this->getHeight();
            if ($height > $h) {
              $this->resizeToHeight($height);
            }
            $this->wrapInTo ($width,$height);
         }
         
         function wrapInTo ($width,$height) {
            /* Обрезает все что не вмещается в область */
            $new_image = imagecreatetruecolor($width, $height);
            $w = $this->getWidth();
            $h = $this->getHeight();
            if ($width > $w) {
              $dst_x = round(($width - $w) / 2);
              $src_x = 0;
              $dst_w = $w;
              $src_w = $w;
            } else {
              $dst_x = 0;
              $src_x = round(($w - $width) / 2);
              $dst_w = $width;
              $src_w = $width;
            }
            if ($height > $h) {
              $dst_y = round(($height - $h) / 2);
              $src_y = 0;
              $dst_h = $h;
              $src_h = $h;
            } else {
              $dst_y = 0;
              $src_y = round(($h - $height) / 2);
              $dst_h = $height;
              $src_h = $height;
            }
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparentindex = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefill($new_image, 0, 0, $transparentindex);
            imagecopyresampled($new_image, $this->image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
            $this->image = $new_image;
         }
         
         function resizeInTo($width,$height) {
            /* Масштабюировать чтобы изображение влезло в рамки */
            $ratiow = $width / $this->getWidth()*100;
            $ratioh = $height / $this->getHeight()*100;
            $ratio = min($ratiow, $ratioh);
            $this->scale($ratio);
         }   
         function crop($x1,$y1,$x2,$y2) {
            /* Вырезать кусок */
            $w = abs($x2 - $x1);
            $h = abs($y2 - $y1);
            $x = min($x1,$x2);
            $y = min($y1,$y2);
           	$new_image = imagecreatetruecolor($w, $h);
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            imagecopy($new_image, $this->image, 0, 0, $x, $y, $w, $h);
            $this->image = $new_image;
         }
      };
		

$res = false;

if($_SERVER["REQUEST_METHOD"] == "POST" && $_REQUEST["Compress"]=="Y"){
	/* CUtil::JSPostUnescape(); */
	@set_time_limit(0);
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
	
	$message = "Ошибка";
	$details = "Произошла неизвестная ощибка";
	$res = false;

	if ($_POST['step'] == 'start') {
		if ($_POST['upload_dir'] != '') {
			if (!is_numeric($_POST['max_width'])) {$_POST['max_width'] = 99999;};
			if (!is_numeric($_POST['max_height'])) {$_POST['max_height'] = 99999;};
			if (!is_numeric($_POST['count'])) {$_POST['count'] = 10;};
			if ($_POST['count']<1) {$_POST['count'] = 1;};
			if (!is_numeric($_POST['quality'])) {$_POST['quality'] = 75;};
			if ($_POST['quality']>100) {$_POST['quality'] = 100;};
			if ($_POST['quality']<1) {$_POST['quality'] = 1;};
			$_POST['upload_dir_backup'] = trim($_POST['upload_dir_backup'], '/');
			$_POST['upload_dir'] = trim($_POST['upload_dir'], '/').'/';

			@unlink($_SERVER['DOCUMENT_ROOT'].'/'.$_POST['logfile']);
			FileListinfile($_SERVER['DOCUMENT_ROOT'].'/'.$_POST['upload_dir'], $_SERVER['DOCUMENT_ROOT'].'/'.$_POST['logfile']);

			$json_post = urlencode(json_encode(array("step"=>"go",
													"n"=>0,
													"upload_dir"=>$_POST['upload_dir'],
													"upload_dir_backup"=>$_POST['upload_dir_backup'],
													"max_width"=>$_POST['max_width'],
													"max_height"=>$_POST['max_height'],
													"quality"=>$_POST['quality'],
													"count"=>$_POST['count'],
													"logfile"=>$_POST['logfile'],
													"pause"=>$_POST['pause'],
													"bs"=>0,
													"as"=>0,
													)));
			$message = "Файлы изображений собраны в файл /{$_POST['logfile']}";
			$details = "Пауза. Продолжение через <span id='pause'>".$pause."</span> сек. <br/> <a id=\"continue_href\" onclick=\"ContinueCompress('{$json_post}'); return false;\"  href=\"compress.php\">Продолжить</a>";
			$res = true;
		} else {
			$message = "Ошибка";
			$details = "Не указан каталог с изображениями";
			$res = false;
		}
	}else if ($_POST['step'] == 'go') {
		$img = new SimpleImage();

		$starttime = microtime_float();
		$n=$_POST['n']+0;
		$files = file($_SERVER['DOCUMENT_ROOT'].'/'.$_POST['logfile']);
		
		$upload_dir = $_POST['upload_dir'];
		$upload_dir_backup = $_POST['upload_dir_backup'];
		$max_width = $_POST['max_width'];
		$max_height = $_POST['max_height'];
		$quality = $_POST['quality'];
		$count = $_POST['count'];
		$logfile = $_POST['logfile'];
		$pause = $_POST['pause'];
		$bs = $_POST['bs'];
		$as = $_POST['as'];

		$folderName = $upload_dir;
		$curtime=microtime_float();
		$runtime=$curtime-$starttime;  
		$curfiles = 0;
		while (($runtime < 5) and ($n < count($files)) and ($curfiles < $count)) {
			$file = trim($files[$n]);
			$img->load($file);
			if ($max_width + $max_height > 0) {
				if (($img->getWidth() > $max_width) or ($img->getHeight() > $max_height)) {
					$img->resizeInTo($max_width, $max_height);
				}
			}
			if ($upload_dir_backup != '') {
				@mkdir($_SERVER['DOCUMENT_ROOT'].'/'.$upload_dir_backup);
				//$dirs = explode('/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $file));
				$dirs = explode('/', str_replace($_SERVER['DOCUMENT_ROOT'], '', str_replace($_SERVER['DOCUMENT_ROOT'].'/'.str_replace("/","",$upload_dir), '', $file)));
				if (is_array($dirs)) {
					array_shift($dirs);
					array_pop($dirs);
					if (count($dirs) > 0) {
						$crdir = $_SERVER['DOCUMENT_ROOT'].'/'.$upload_dir_backup;
						foreach ($dirs as $key=>$dir) {
							$crdir .= '/'.$dir;
							@mkdir($crdir);
						}
					}
				}
				$newfile = str_replace($upload_dir, $upload_dir_backup.'/', $file);
				copy($file, $newfile);
			}
			$img->save($file, $img->image_type, $quality);
			$bs += filesize($newfile); /* было */
			$as += filesize($file); /* стало */
			$curtime = microtime_float();
			$runtime = $curtime-$starttime ;
			$n ++;
			$curfiles ++;
		};

		$message = 'Текущее время работы сессии '.$runtime.' сек.';
		$message = 'Обработка файлов';
		if ($n < count($files)) {
			$json_post = urlencode(json_encode(array("step"=>"go",
													"n"=>$n,
													"upload_dir"=>$_POST['upload_dir'],
													"upload_dir_backup"=>$_POST['upload_dir_backup'],
													"max_width"=>$_POST['max_width'],
													"max_height"=>$_POST['max_height'],
													"quality"=>$_POST['quality'],
													"count"=>$_POST['count'],
													"logfile"=>$_POST['logfile'],
													"pause"=>$_POST['pause'],
													"bs"=>$bs,
													"as"=>$as,
													)));
			
			$details = "Обработано ".$n." файлов. <br />".$bs." байт -> ".$as." байт <br/> 
						Пауза. Продолжение через <span id='pause'>".$pause."</span> сек.
						<br/>
						<a id=\"continue_href\" onclick=\"ContinueCompress('{$json_post}'); return false;\"  href=\"compress.php\">Продолжить</a>";
			$res = true;
		}else{
			$message = "Сжатие завершено!";
			$details = "Обработано ".$n." файлов. <br />".$bs." байт -> ".$as." байт ";
			$res = false;
		}
	}

	$res = true;
	if($res):
		CAdminMessage::ShowMessage(array(
			"MESSAGE" => $message,
			"DETAILS" => $details,
			"HTML"=>true,
			"TYPE"=>"PROGRESS",
		));
	?>
		<script>
			CloseWaitWindow();
			if(!stop){
				sleep(<?=$pause?>000);
				ContinueCompress('<?=$json_post?>');
			}
		</script>
	<?else:
		CAdminMessage::ShowMessage(array(
			"MESSAGE" => $message,
			"DETAILS" => $details,
			"HTML"=>true,
			"TYPE"=>"OK",
		));
	?>
		<script>
			CloseWaitWindow();
			EndCompress();
		</script>
	<?endif;
	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_js.php");
}
else
{

$APPLICATION->SetTitle("Сжатие изображений");

$aTabs = array(
	array("DIV" => "edit1", "TAB" => "Сжатие изображений", "ICON"=>"main_user_edit", "TITLE"=>"Сжатие изображений"),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

?>
<script language="JavaScript">
var stop;
var queryString;

function sleep(milliseconds) {
  const date = Date.now();
  let currentDate = null;
  do {
    currentDate = Date.now();
  } while (currentDate - date < milliseconds);
}

function StartCompress()
{
	stop=false;
	document.getElementById('compress_result_div').innerHTML='';
	document.getElementById('stop_button').disabled=false;
	document.getElementById('start_button').disabled=true;
	document.getElementById('upload_dir').disabled=true;
	document.getElementById('upload_dir_backup').disabled=true;
	document.getElementById('max_width').disabled=true;
	document.getElementById('max_height').disabled=true;
	document.getElementById('count').disabled=true;
	document.getElementById('quality').disabled=true;
	document.getElementById('logfile').disabled=true;
	document.getElementById('pause').disabled=true;
	
	var post = {};
	post['step'] = "start";
	post['upload_dir'] = document.getElementById('upload_dir').value;
	post['upload_dir_backup'] = document.getElementById('upload_dir_backup').value;
	post['max_width'] = document.getElementById('max_width').value;
	post['max_height'] = document.getElementById('max_height').value;
	post['count'] = document.getElementById('count').value;
	post['quality'] = document.getElementById('quality').value;
	post['logfile'] = document.getElementById('logfile').value;
	post['pause'] = document.getElementById('pause').value;
	
	DoNext(post);
}
function ContinueCompress(json_post)
{
	stop=false;
	
	var post = JSON.parse(decodeURI(json_post).replace(/%3A/g,":").replace(/%2C/g,",").replace(/%2F/g,"/"));
	
	document.getElementById('stop_button').disabled=false;
	document.getElementById('start_button').disabled=true;
	document.getElementById('upload_dir').disabled=true;
	document.getElementById('upload_dir_backup').disabled=true;
	document.getElementById('max_width').disabled=true;
	document.getElementById('max_height').disabled=true;
	document.getElementById('count').disabled=true;
	document.getElementById('quality').disabled=true;
	document.getElementById('logfile').disabled=true;
	document.getElementById('pause').disabled=true;
	DoNext(post);
}
function DoNext(post = {})
{
	var queryString = 'Compress=Y';
	if(!stop)
	{
		ShowWaitWindow();
		BX.ajax.post(
			'compress.php?'+queryString,
			post,
			function(result){
				document.getElementById('compress_result_div').innerHTML = result;
				var href = document.getElementById('continue_href');
				if(!href)
				{
					CloseWaitWindow();
					StopCompress();
				}
			}
		);
	}

	return false;
}
function StopCompress()
{
	stop=true;
	CloseWaitWindow();
	document.getElementById('stop_button').disabled=true;
	document.getElementById('start_button').disabled=false;
	document.getElementById('upload_dir').disabled=false;
	document.getElementById('upload_dir_backup').disabled=false;
	document.getElementById('max_width').disabled=false;
	document.getElementById('max_height').disabled=false;
	document.getElementById('count').disabled=false;
	document.getElementById('quality').disabled=false;
	document.getElementById('logfile').disabled=false;
	document.getElementById('pause').disabled=false;
}
function EndCompress()
{
	stop=true;
	CloseWaitWindow();
	document.getElementById('stop_button').disabled=true;
	document.getElementById('start_button').disabled=false;
	document.getElementById('upload_dir').disabled=false;
	document.getElementById('upload_dir_backup').disabled=false;
	document.getElementById('max_width').disabled=false;
	document.getElementById('max_height').disabled=false;
	document.getElementById('count').disabled=false;
	document.getElementById('quality').disabled=false;
	document.getElementById('logfile').disabled=false;
	document.getElementById('pause').disabled=false;
}
</script>

<div id="compress_result_div" style="margin:0px"></div>

<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?lang=<?echo htmlspecialcharsbx(LANG)?>" name="fs1">
<?
$tabControl->Begin();
$tabControl->BeginNextTab();

$upload_dir = COption::GetOptionString("compress", "upload_dir");
$upload_dir_backup = COption::GetOptionString("compress", "upload_dir_backup");
$max_width = COption::GetOptionString("compress", "max_width");
$max_height = COption::GetOptionString("compress", "max_height");
$count = COption::GetOptionString("compress", "count");
$quality = COption::GetOptionString("compress", "quality");
$pause = COption::GetOptionString("compress", "pause");
$logfile = COption::GetOptionString("compress", "logfile");
?>
	<tr>
		<td width="350">Путь к папке с изображениями</td>
		<td><input type="text" name="upload_dir" id="upload_dir" value="<?echo $upload_dir;?>"></td>
	</tr>
	<tr>
		<td width="350">Путь к папке для хранения <br/>резервных копий изображений</td>
		<td><input type="text" name="upload_dir_backup" id="upload_dir_backup" value="<?echo $upload_dir_backup;?>"></td>
	</tr>
	<tr>
		<td width="350">Максимальная ширина изображения, пикс</td>
		<td><input type="text" name="max_width" id="max_width" value="<?echo $max_width;?>"></td>
	</tr>
	<tr>
		<td width="350">Максимальная высота изображения, пикс</td>
		<td><input type="text" name="max_height" id="max_height" value="<?echo $max_height;?>"></td>
	</tr>
	<tr>
		<td width="350">Количество файлов <br/>для обработки за один шаг</td>
		<td><input type="text" name="count" id="count" value="<?echo $count;?>"></td>
	</tr>
	<tr>
		<td width="350">Качество</td>
		<td><input type="text" name="quality" id="quality" value="<?echo $quality;?>"></td>
	</tr>
	<tr>
		<td width="350">Пауза между шагами</td>
		<td><input type="text" name="pause" id="pause" value="<?echo $pause;?>"></td>
	</tr>
	<tr>
		<td width="350">Имя лог-файла</td>
		<td><input type="text" name="logfile" id="logfile" value="<?echo $logfile;?>"></td>
	</tr>
<?
$tabControl->Buttons();
?>
	<input type="button" id="start_button" value="Старт" OnClick="StartCompress();" class="adm-btn-save">
	<input type="button" id="stop_button" value="Стоп" OnClick="StopCompress();" disabled>
<?
$tabControl->End();
?>
</form>

<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
}
?>
