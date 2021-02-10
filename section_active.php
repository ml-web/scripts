<?
$_SERVER["DOCUMENT_ROOT"] = '/home/web/vm-93fd898a.netangels.ru/www/';
require($_SERVER["DOCUMENT_ROOT"]."bitrix/header.php");?>

<?
if (CModule::IncludeModule("iblock")):

		$iblock_id = 2;
		# show url my elements
		$my_elements = CIBlockElement::GetList (
		Array("ID" => "ASC"),
		Array("IBLOCK_ID" => $iblock_id, "ACTIVE" => "Y"),
		false,
		false,
		Array('ID', 'NAME', 'IBLOCK_SECTION_ID', 'ACTIVE', 'GLOBAL_ACTIVE' )
		);
	$NS = array();
	$CNT = 0;
		while($ar_fields = $my_elements->GetNext())
		{
		
			if (!in_array($ar_fields['IBLOCK_SECTION_ID'], $NS)) 
			{
				
				array_push($NS, $ar_fields['IBLOCK_SECTION_ID']);
				
				$res = CIBlockSection::GetByID($ar_fields['IBLOCK_SECTION_ID']);
				if($ar_res = $res->GetNext())

				// echo $ar_res['ACTIVE'];
				// echo $ar_fields['IBLOCK_SECTION_NAME'].", ".$ar_res['ACTIVE']." IBLOCK_SECTION_NAME<br> ";
				
				// определяем название раздела 
				$ressec = CIBlockSection::GetByID($ar_fields['IBLOCK_SECTION_ID']);  
				if($ar_ressec = $ressec->GetNext())  
				$NAMESEC = $ar_ressec['NAME'];   
				
				if (($ar_res['ACTIVE'] !="Y") && (strpos($NAMESEC, 'рхив') != 1)) { // проверяем все неактивные секции
				
				$bs = new CIBlockSection;
				$arFields = Array(
				"ACTIVE" => "Y",
				//		"IBLOCK_SECTION_ID" => $ar_fields['IBLOCK_SECTION_ID'],
				//		"IBLOCK_ID" => 2,
				);
				
				$bs->Update($ar_fields['IBLOCK_SECTION_ID'], $arFields);
				$CNT++;
				//		echo '-1-';
				//	    echo $ar_fields['NAME'].", ".$ar_res['ACTIVE']." NAME<br> ";
				//		__($ar_fields);


				}
				
				// определяем название раздела 
						
				// echo $NAMESEC.'<br> ';
			
				//	 echo strpos($NAMESEC, 'рхив').' == 1 рхив ========== '.$NAMESEC.'<br>';
				
				if (($ar_res['ACTIVE'] == "Y" ) && (strpos($NAMESEC, 'рхив') == 1)) { // проверяем активные секции которые с названием архив
				
				$ACTIVE = "N";
				
				$bs = new CIBlockSection;
				$arFields = Array(
				"ACTIVE" => $ACTIVE,
				//		"IBLOCK_SECTION_ID" => $ar_fields['IBLOCK_SECTION_ID'],
				//		"IBLOCK_ID" => 2,
				);
				
				$bs->Update($ar_fields['IBLOCK_SECTION_ID'], $arFields);
				$CNT++;
				//		echo '-1-';
				//	    echo $NAMESEC.", ".$ar_res['ACTIVE']." $NAMESEC - АРХИВ<br> ";
				//		echo strpos($NAMESEC, 'архив').'=============<br>';

				// echo strpos($NAMESEC, 'рхив').' == 1 рхив ========== '.$NAMESEC.' '.$ar_res['ACTIVE'].'<br>';


				}

				
				$resSection = CIBlockSection::GetNavChain(false, $ar_fields['IBLOCK_SECTION_ID']);
				while ($arSection = $resSection->GetNext()) {
				$array_sections = $arSection;
				
				$res = CIBlockSection::GetByID($arSection['ID']);
				if($ar_res = $res->GetNext())

				//		echo $ar_res['ACTIVE'];
				//		echo $arSection['NAME'].", ".$ar_res['ACTIVE']." arSection<br> ";
				
				if (($ar_res['ACTIVE'] !="Y" ) && (strpos($arSection['NAME'], 'рхив') != 1 )) { // проверяем все неактивные секции
				
				$bs = new CIBlockSection;
				$arFields = Array(
				"ACTIVE" => "Y",
				//		"IBLOCK_SECTION_ID" => $arSection['ID'],
				//		"IBLOCK_ID" => 2, 
				);

				$bs->Update($arSection['ID'], $arFields);
				$CNT++;
				//		echo '-2-';
				//		echo $arSection['NAME'].", ".$ar_res['ACTIVE']." arSection <br> ";

				}
				
				//		echo strpos($arSection['NAME'], 'рхив').' == 2 рхив ========== '.$arSection['NAME'].'<br>';
				
				if (($ar_res['ACTIVE'] == "Y" ) && (strpos($arSection['NAME'], 'рхив') == 1 )) { // проверяем активные секции которые с названием архив
				
				$ACTIVE = "N";
				
				$bs = new CIBlockSection;
				$arFields = Array(
				"ACTIVE" => $ACTIVE,
				//		"IBLOCK_SECTION_ID" => $arSection['ID'],
				//		"IBLOCK_ID" => 2, 
				);

				$bs->Update($arSection['ID'], $arFields); 
				$CNT++;
				//		echo '-2-';
				//		echo $arSection['NAME'].", ".$ar_res['ACTIVE']."  arSection - Архив <br> ";

				// echo strpos($arSection['NAME'], 'рхив').' == 2 рхив ========== '.$arSection['NAME'].' '.$ar_res['ACTIVE'].'<br>';

				}
				
				
				
				if (!in_array($arSection['ID'], $NS)) { array_push($NS, $arSection['ID']); }
				}
				
			}
				//	  echo ' '; 
		}
	
		$my_sections = CIBlockSection::GetList (
			Array("ID" => "ASC"),
			Array("IBLOCK_ID" => $iblock_id, "ACTIVE" => "Y"),
			false,
			Array('ID', 'NAME')
		);

		while($ar_fields = $my_sections->GetNext())
		{
				// echo urldecode($ar_fields['NAME'])." ;<br>";
			
				if (($ar_res['ACTIVE'] == "Y" ) && (strpos($ar_fields['NAME'], 'рхив') == 1 )) 
				{ // проверяем активные секции которые с названием архив
					echo urldecode($ar_fields['NAME'])." ;<br>";
				
					$ACTIVE = "N";
					
					$bs = new CIBlockSection;
					$arFields = Array(
					"ACTIVE" => $ACTIVE,
					//		"IBLOCK_SECTION_ID" => $ar_fields['ID'],
					//		"IBLOCK_ID" => 2, 
					);

					$bs->Update($ar_fields['ID'], $arFields); 
					$CNT++;
				} 
 		}
   
endif;

// echo $CNT;
?>

<?require($_SERVER["DOCUMENT_ROOT"]."bitrix/footer.php");?>