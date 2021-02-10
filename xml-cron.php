<?php
// запуск по cron
// /usr/bin/php -f /home/web/vm-93fd898a.netangels.ru/www/personal/order/xml-cron.php

$_SERVER["DOCUMENT_ROOT"] = "/home/web/vm-93fd898a.netangels.ru/www";
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_AGENT_CHECK", true);
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
set_time_limit(0);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule('sale');

// добавляем информацию из order-1c.txt в order-1c-all.txt
$save_file_all = $_SERVER["DOCUMENT_ROOT"]."/personal/order/order-1c-all.txt";
$save_file_1 = $_SERVER["DOCUMENT_ROOT"]."/personal/order/order-1c.txt";
$file_content = '';
$file_content .= file_get_contents($save_file_1);
file_put_contents($save_file_all, $file_content, FILE_APPEND | LOCK_EX);
file_put_contents($save_file_1, '');

//удаляем дубли в файле order-1c-all.txt
$lines = file($save_file_all); 
$lines = array_unique($lines); 
file_put_contents($save_file_all, implode($lines)); 

// проверить скрипт выше

function myscandir($dir, $sort=0)
{
	$list = scandir($dir, $sort);
	
	// если директории не существует
	if (!$list) return false;
	
	// удаляем . и .. 
	if ($sort == 0) unset($list[0],$list[1]);
	else unset($list[count($list)-1], $list[count($list)-1]);
	return $list;
}

// для запуска в браузере
// $dir = 'order-list'; 
// для запуска по крону
$dir = $_SERVER["DOCUMENT_ROOT"]."/personal/order/order-list";
$files1 = myscandir($dir);
			
$Order1C = array();
	foreach ( $files1 as $files1c )
		{
		$Order1C[] = preg_replace("/[^0-9]/", '', $files1c);
		};

// список файлов в директории
// print_r($files1);
// список заказов битрикс из базы

$arOrdersID = array();
$dbOrders = CSaleOrder::GetList(array("ID" => "ASC"), array(), false, array(), array('ID', 'DATE_INSERT'));
while ($arOrder = $dbOrders->Fetch())
	{
		$arOrdersID[] = 'order-'.$arOrder['ID'].'.xml';
	}

$result = array_diff($arOrdersID, $files1);

foreach ( $result as $filid )
    {

		// получаем список ID заказов которых нет на диске    
		$nOrder = preg_replace("/[^0-9]/", '', $filid);
	
		// запускаем запрос заказа и запись его в директорию сайта

		if ( $nOrder && CSaleOrder::GetByID($nOrder) )
		{
			ob_end_clean();
			ob_start();
	  
			Bitrix\Main\Loader::includeModule('sale');

			$timeload = time();
			$timeload1 = $timeload+1;

			$order = \Bitrix\Sale\Order::load($nOrder);
			//$order->setField("USER_DESCRIPTION", $timeload);
			$order->getPropertyCollection("xmldate", $timeload);
			$shipmentCollection = $order->getShipmentCollection();
			/** @var Sale\Shipment $shipment */

			foreach ($shipmentCollection as $shipment)
				{
					if (!$shipment->isSystem())
					$shipment->allowDelivery();
				}

			$result = $order->save();

			if (!$result->isSuccess())
			{
				//$result->getErrors();
			};	  

			CSaleExport::ExportOrders2Xml(array('ID' => $nOrder));
			$sXml = ob_get_clean();
			$sXml = strtr($sXml, array('encoding="windows-1251"' => 'encoding="utf-8"'));
			// сохраняем xml на диск если в директории order-list его нет
			// для запуска в браузере
			//	$filename = "order-list/order-$nOrder.xml";
			// для запуска по крону
			$filename = $_SERVER["DOCUMENT_ROOT"]."/personal/order/order-list/order-$nOrder.xml";
			$filename1c = $_SERVER["DOCUMENT_ROOT"]."/personal/order/order-1c/order-$nOrder.xml";
			
			if (!file_exists($filename)) 
			{		
				$handler = fopen($filename, "w");
				$text = $sXml;
				//записываем текст в файл
				fwrite($handler, $text);
				fclose($handler);
			}

			$orderlist = $_SERVER["DOCUMENT_ROOT"]."/personal/order/order-list.txt";
			//$Order1C = explode("\n", $orderlist);
			file_put_contents($orderlist, $nOrder . PHP_EOL, FILE_APPEND | LOCK_EX);
			//echo '1';
			
			$Order1C[] = $nOrder;
			
		//	__($nOrder); 
		//	__($Order1C);
			
			if (in_array($nOrder, $Order1C)) 
			{
				//echo '2';
				copy($filename, $filename1c);
			};
	
			//	echo 'записали XML';
		}
		else
		{
			// если заказа нет ничего не делаем 
			// echo 'НЕТ ЗАКАЗА';
		}
		// конец скрпта записи XML в директорию сайта
		//удаляем дубли в файле order-1c-all.txt
		$lines = file($orderlist); 
		$lines = array_unique($lines); 
		file_put_contents($orderlist, implode($lines)); 		
    }



// копируем файлы из директории order-list в директорию order-1c (которые есть в заказах но нет в order-1c-all)
// $OrderListArry = array();
//	foreach ( $files1 as $OrderListId )
//		{
//			// получаем список ID заказов которых нет на диске    
//			$OrderListArry[] = preg_replace("/[^0-9]/", '', $OrderListId);
//		}
//
// $FilesOrder1C = $_SERVER["DOCUMENT_ROOT"]."/personal/order/order-1c.txt";
	
// $data1C = file_get_contents($FilesOrder1C);
// $Order1C = explode("\n", $data1C);

// print_r($Order1C);	
// $files1
// CCaptchaAgent::DeleteOldCaptcha(86400);
// require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>
