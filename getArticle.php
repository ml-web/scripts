<? if (!empty($_REQUEST)) {//если данные не пустые
//    print_r($_REQUEST);
    require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
    CModule::IncludeModule("iblock");
    CModule::IncludeModule("sale");
    CModule::IncludeModule("catalog");
//определяем тип пользователя
    $arGroups = CUser::GetUserGroup($USER->GetID());
    $typeOfUser = 'Физ';
    if (in_array(6, $arGroups)) {
        $typeOfUser = "Юр";
    }
// получаем список бусплатных выпусков из админки

$USER_NUMBER_FREE = Array();
$USER_ARTICLES_FREE = Array();

    $arFilter = array("ID" => $USER->GetID());
    $arParams["SELECT"] = array("UF_ARTICLES_FREE");
    $arRes = CUser::GetList($by,$desc,$arFilter,$arParams);
        if ($res = $arRes->Fetch()) {
            $USER_ARTICLES_FREE = $res["UF_ARTICLES_FREE"];
        }
//    __($USER_ARTICLES_FREE);
    $arParams["SELECT"] = array("UF_NUMBER_FREE");
    $arRes = CUser::GetList($by,$desc,$arFilter,$arParams);
        if ($res = $arRes->Fetch()) {
            $USER_NUMBER_FREE = $res["UF_NUMBER_FREE"];
        }
//    __($USER_NUMBER_FREE);
//получаем статьи
// получаем список разделов со статьями с которыми связан выпуск 
$VALUES_ARTICLES = array();
$res_art = CIBlockElement::GetProperty(6, $_REQUEST['ID'], array("sort" => "asc"), array("CODE" => "ARTICLES"));
while ($ob_art = $res_art->GetNext())
{
    $VALUES_ARTICLES[] = $ob_art['VALUE'];
}
// echo $_REQUEST['SECTION_ID'].''.$_REQUEST['SECTION_ID_1'];

$object = array();
//    $arFilter = array('IBLOCK_ID' => 4, "SECTION_ID" =>  array($_REQUEST['SECTION_ID']), 'ACTIVE' => 'Y');
    $arFilter = array('IBLOCK_ID' => 4, "SECTION_ID" =>  $VALUES_ARTICLES, 'ACTIVE' => 'Y');
    $res = CIBlockElement::GetList(array('PROPERTY_OLD_ID'=>'asc'), $arFilter, false, false,
        array('IBLOCK_ID', 'ID', 'CODE', 'IBLOCK_SECTION_ID', 'NAME', 'DETAIL_PAGE_URL', 'PROPERTY_ARTICLE_PAGES_START',
            'PROPERTY_ARTICLE_PAGES_END','PROPERTY_FREE_STAT', 'PROPERTY_KATEGOR_LIST', 'PROPERTY_NAME_EN', 'PROPERTY_PDF_EN', 'PROPERTY_OLD_ID'));
    while ($item = $res->GetNext()) {
    //    $object[$item['PROPERTY_ARTICLE_PAGES_START_VALUE'].$item['PROPERTY_ARTICLE_PAGES_END_VALUE']] = $item;
        $object[$item['CODE']] = $item; // сортируем статьи по коду
    };
    ksort($object,SORT_NUMERIC);
//получаем информацию о выпуске
    $vypuskjurn = CIBLockElement::GetList(array(),
        array('IBLOCK_ID' => 6, 'ACTIVE' => 'Y', 'ID' => $_REQUEST['ID']),
        false, false,
        array('IBLOCK_ID', 'ID', 'NAME', 'DETAIL_PAGE_URL', 'PROPERTY_GOD', 'PROPERTY_PDF', 'PROPERTY_PDF_EN', 'PROPERTY_NUMBER'));
    $current_issue = array();
    while ($itemJur = $vypuskjurn->GetNext()) {
//        $itemJur['PROPERTY_PDF_VALUE'] = CFile::GetPath($itemJur['PROPERTY_PDF_VALUE']);
        $current_issue = $itemJur;
    };

//получаем журнал
//__($_REQUEST);
    $journals = array();
    $journalsOBJ = CIBLockElement::GetList(array("NAME" => "asc"),
        array('IBLOCK_ID' => 5, 'ID' => $_REQUEST['JOURNAL_ID'], 'ACTIVE' => 'Y'),
        false, false,
        array('IBLOCK_ID', 'ID', 'NAME', 'PROPERTY_FREE_GROUP'));
    while ($item = $journalsOBJ->GetNext()) {
        $journals = $item;
    };

    $FREE_JOURNAL = array();
    $res_free_jour = CIBlockElement::GetProperty(5, $_REQUEST['JOURNAL_ID'], array("sort" => "asc"), array("CODE" => "FREE_GROUP"));
    while ($ob_free_jour = $res_free_jour->GetNext())
    {
        $FREE_JOURNAL[] = $ob_free_jour['VALUE'];
    };

   // $FREE_VUPUSK = array();
   $res_free_vup = CIBlockElement::GetProperty(6, $_REQUEST['ARTICLE_ID'], array("sort" => "asc"), array("CODE" => "FREE_GROUP"));
   while ($ob_free_vup = $res_free_vup->GetNext())
   {
       $FREE_JOURNAL[] = $ob_free_vup['VALUE'];
   };

   function array_delete(array $array, array $symbols = array(''))
   {
       return array_diff($array, $symbols);
   }

   $FREE_JOURNAL = array_delete($FREE_JOURNAL, array('', 0, null));
   $arGroups = array_delete($arGroups, array('', 0, null));

    $free_group = array_intersect($FREE_JOURNAL, $arGroups); 
 //   print_r($diff);

 // __($FREE_JOURNAL);
 //__($free_group);
 //__($arGroups);
 //__($vupusks);
    if ($free_group) { $globalAcces = true;} else {$globalAcces = false;};

 // $globalAcces = false;

 //    __($globalAcces);

    /*получаем цену для выпусков*/
    $issue_offers = array();
    $issue = CIBLockElement::GetList(array(),
        array('IBLOCK_ID' => 10, 'ACTIVE' => 'Y', 'PROPERTY_CML2_LINK' => $current_issue['ID'], 'PROPERTY_TYPE_PRICE_VALUE' => $typeOfUser),
        false, false,
        array('IBLOCK_ID', 'ID', 'PROPERTY_TYPE'));
    while ($item = $issue->GetNext()) {
        $ar_res = CPrice::GetBasePrice($item['ID'], false, false);
        $item['PRICE'] = number_format($ar_res["PRICE"], 0, ',', ' ');
        $issue_offers[$item['PROPERTY_TYPE_ENUM_ID']] = $item;
    }

    if (empty($issue_offers) || count($issue_offers) == 1) {
        //иначе ищем в торговых предлоежниях журнала
        $issue = CIBLockElement::GetList(array(),
            array('IBLOCK_ID' => 9, 'ACTIVE' => 'Y', 'PROPERTY_CML2_LINK' => $journals['ID'], 'PROPERTY_TYPE_PRICE_VALUE' => $typeOfUser, 'PROPERTY_ISSUE_VALUE' => 'да'),
            false, false,
            array('IBLOCK_ID', 'ID', 'PROPERTY_TYPE'));
        while ($item = $issue->GetNext()) {
            $ar_res = CPrice::GetBasePrice($item['ID'], false, false);
            $item['PRICE'] = number_format($ar_res["PRICE"], 0, ',', ' ');
            $issue_offers[$item['PROPERTY_TYPE_ENUM_ID'] + 2] = $item;
        }
    }
    /*получаем цену для статей*/
    $priceItem = CIBLockElement::GetList(array("NAME" => "asc"),
        array('IBLOCK_ID' => 9, 'ACTIVE' => 'Y', 'PROPERTY_CML2_LINK' => $_REQUEST['JOURNAL_ID'], '!=PROPERTY_ARTICLES' => false),
        false, false,
        array('IBLOCK_ID', 'ID', 'NAME', 'PROPERTY_CML2_LINK'))->GetNext();
    $ar_res = CPrice::GetBasePrice($priceItem['ID']);

//Получаем торг. предложения Выпусков и Жерналов.
    $offers = array();
    $res = CCatalogSKU::getOffersList(
        array($_REQUEST['ARTICLE_ID']), // массив ID товаров
        $iblockID = 6, // указываете ID инфоблока только в том случае, когда ВЕСЬ массив товаров из одного инфоблока и он известен
        $skuFilter = array(), // дополнительный фильтр предложений. по умолчанию пуст.
        $fields = array(),  // массив полей предложений. даже если пуст - вернет ID и IBLOCK_ID
        $propertyFilter = array()
    );
    foreach ($res as $itemRes) {
        foreach ($itemRes as $value) {
    //        $offers[] = $value['ID'];
        }
    }
    $res = CCatalogSKU::getOffersList(
        array($_REQUEST['JOURNAL_ID']), // массив ID товаров
        $iblockID = 5, // указываете ID инфоблока только в том случае, когда ВЕСЬ массив товаров из одного инфоблока и он известен
        $skuFilter = array(), // дополнительный фильтр предложений. по умолчанию пуст.
        $fields = array("ID"),  // массив полей предложений. даже если пуст - вернет ID и IBLOCK_ID
        $propertyFilter = array() /* свойства предложений. имеет 2 ключа:
                               ID - массив ID свойств предложений
                                      либо
                               CODE - массив символьных кодов свойств предложений
                                     если указаны оба ключа, приоритет имеет ID*/
    );
    foreach ($res as $item) {
        foreach ($item as $value) {
            $offers[] = $value['ID'];
        }
    }
//получаем корзину
    $arFilter = Array(
        "USER_ID" => $USER->GetID(),
        "PAYED" => 'Y'
    );
    $arOffers = array();
    $db_sales = CSaleOrder::GetList(array("DATE_INSERT" => "ASC"), $arFilter);
    while ($ar_sales = $db_sales->Fetch()) {
        $obBasket = \Bitrix\Sale\Basket::getList(array('filter' => array('ORDER_ID' => $ar_sales['ID'])));
        while ($bItem = $obBasket->Fetch()) {
            $arOffers[] = $bItem;
        }

    }

    //__($arOffers['PRODUCT_ID']);
//__($arOffers);
//получаем актуальную корзину для скрытия кнопок покупки
    $arBasket = array();
    //__(CSaleBasket::GetBasketUserID());
    $obBasket = \Bitrix\Sale\Basket::getList(array('filter' => array("FUSER_ID" => CSaleBasket::GetBasketUserID(),
        "LID" => SITE_ID, 'ORDER_ID' => 'NULL')));
    while ($arItems = $obBasket->Fetch()) {
        $db_res = CSaleBasket::GetPropsList(array("SORT" => "ASC", "NAME" => "ASC"), array("BASKET_ID" => $arItems['ID']));
        while ($prop = $db_res->Fetch()) {
            $arItems[$prop['CODE']] = $prop['VALUE'];
        }
        $arBasket[] = $arItems;
    }
//    print_r($current_issue);
//    print_r($issue_offers);

//__($arBasket);

    ?>

<?//__($offers);?>
<?//__($arItems['PRODUCT_ID']);?>
    <div class="container">
        <div class="arhiv-content-block-close"><span></span></div>
        <div class="arhiv-content-block-list">
            <div class="title">
                <p><?= $_SESSION['LANG'] == 'RU' ? 'Содержание' : 'Content' ?>, № <?= $_REQUEST['NAME'] ?> </p>
            </div>
            <?
            $accessIssue = false;
            $accessIssue = $current_issue['PROPERTY_GOD_VALUE'] < (date('Y')-1) ? true : false;
            if (!$accessIssue) {

                $ID_AA_NUMBER = array();
                $ID_AA_ARTICLES = array();
                $VUPUSK_SUB = Array();

                foreach ($arOffers as $arItems) {
                    // __($arItems['ORDER_ID']);
                    // $ID_AA = array();
                    // $PRODUCT_AA = array();
                    $obBasket = \Bitrix\Sale\Basket::getList(array('filter' => array('ORDER_ID' => $arItems['ORDER_ID'])),false, array("COUNT"=>$arItems['QUANTITY']));
                    while($bItem = $obBasket->Fetch()){
                        $PRODUCT = $bItem["ID"]; 
                        //__($bItem);	
                        $db_res = CSaleBasket::GetPropsList(array("SORT" => "ASC", "NAME" => "ASC"), array("BASKET_ID" => $bItem["ID"])); 
                        $ITEM = Array();
                        while ($prop = $db_res->Fetch()) {
                        	//__($prop);
                            $ITEM[$prop['CODE']] = $prop['VALUE'];
                        };

                        if ( $ITEM['NUMBER']) {$ID_AA_NUMBER[] = $ITEM['NUMBER'];};
                        if ( $ITEM['ARTICLES']) {$ID_AA_ARTICLES[] = $ITEM['ARTICLES'];};
                        if ( $ITEM['TIME']) {
                            // если подписка то определяем ID выпусков для доступности
                            $SKU_SUBSCRIP_NAME =  $bItem['NAME'];
                            $JURNAL_ID = $ITEM['JURNAL'];
                            //__($arItems);
                            $arOrder = CSaleOrder::GetByID($arItems['ORDER_ID']);
                            
                            $pos1 = strpos($SKU_SUBSCRIP_NAME, 'след');
                            if ($pos1 !== false) {
                                $ORDER_YEAR = date('Y', strtotime($arOrder['DATE_INSERT_FORMAT']))+1;
                            //    echo $ORDER_YEAR.' '.$pos1;
                                // найден "след"  
                            } else {
                                $ORDER_YEAR = date('Y', strtotime($arOrder['DATE_INSERT_FORMAT']));
                            }
                            $YEAR_PERIOD = 0;
                            if ($ITEM['TIME'] == 6) {
                                $pos2 = strpos($SKU_SUBSCRIP_NAME, ' 1 ');
                                if ($pos2 === false) {
                                    $YEAR_PERIOD = 2;
                                } else {
                                    $YEAR_PERIOD = 1;
                                }
                            } 

                            $arFilterSub = Array(5, "ID"=>$JURNAL_ID);
						    $resSub = CIBlockElement::GetList(Array(), $arFilterSub);
						    if ($obSub = $resSub->GetNextElement()){
                                $arPropsSub = $obSub->GetProperties();
                                $VYPUSK_JURN = $arPropsSub['VYPUSK_JURN']['VALUE'];
                                $PERIOD = $arPropsSub['PERIOD']['VALUE'];
                            }
                            // $YEAR_PERIOD = 0; была проверка
                            $PERIOD = intval(12/$PERIOD);
                            if ($YEAR_PERIOD == 1 ) { 
                                $START = 1; 
                                $END = intval($PERIOD/2);
                            } elseif  ($YEAR_PERIOD == 2 ) {
                                $START = intval($PERIOD/2)+1; 
                                $END = $PERIOD;
                            } else {
                                $START = 1; 
                                $END = $PERIOD;
                            }
                            $INDEX = 1;
                            //$VUPUSK_SUB = Array();
                            $arFilterVup = Array(6, "SECTION_ID"=>$VYPUSK_JURN, "PROPERTY_GOD"=>$ORDER_YEAR);
						    $resVup = CIBlockElement::GetList(Array(), $arFilterVup);
						    while ($obVup = $resVup->GetNextElement()){
                            $arFieldsVup = $obVup->GetFields();
                                if (($INDEX >= $START) && ($INDEX <= $END)) {
                                    $VUPUSK_SUB[] = $arFieldsVup['ID'];
                                };
                                $INDEX++;
                            }
                            //__($ITEM);
                            //__($bItem);
                            //__($prop);
                           // __($VUPUSK_SUB); // список выпусков в подписке
                            //__($VYPUSK_JURN); // ID раздела с выпусками
                            //__($PERIOD); // периодичность выпусков в год
                            //__($ORDER_YEAR); // год на который оформлена подписка
                            //__($YEAR_PERIOD); // период подписки (год, 1 полугодие, 2 полугодие)
                            //__($SKU_SUBSCRIP_NAME); // NAME торгового предложения с подпиской
                            //__($JURNAL_ID);  // ID                      
                        
                        };
                    };

                    //__($ITEM);
                    // конец кода
                    //__($current_issue['ID']);
                    /*
                    if (in_array($arItems['PRODUCT_ID'],$offers )) {
                        $accessIssue = true;
                    }
                    
                    if (in_array($current_issue['ID'], $ID_AA)) {
                        $accessIssue = true;
                    }
                    */
                }
                if ($VUPUSK_SUB) {$ID_AA_NUMBER = array_merge($ID_AA_NUMBER,$VUPUSK_SUB);};
                if ($USER_NUMBER_FREE) {$ID_AA_NUMBER = array_merge($ID_AA_NUMBER,$USER_NUMBER_FREE);};
                if ($USER_ARTICLES_FREE) {$ID_AA_ARTICLES = array_merge($ID_AA_ARTICLES,$USER_ARTICLES_FREE);};
//                __($ID_AA_ARTICLES);
//                __($ID_AA_NUMBER);
                //__($ID_AA_NUMBER);
                if (in_array($current_issue['ID'], $ID_AA_NUMBER)) {
                    $accessIssue = true;
                }
                if (in_array($arItems['ARTICLES'], $ID_AA_ARTICLES)) {
                    $isAdded = 1;
                }
            }
            if ($current_issue['PROPERTY_GOD_VALUE'] >= (date('Y')-1) || !$accessIssue) { ?>
                <div class="text" style="display: none;">
                    <?
                    if ($_REQUEST['OLD'] == 'N') {
                        ?>
                        <p>
                            <? if ($typeOfUser == 'Физ') {
                                echo ($_SESSION['LANG'] == 'RU' ? 'Стоимость каждой статьи составляет ' : 'The cost of each article is ')
                                    . number_format($ar_res['PRICE'], 0, ',', '') . '₽. ';
                            }
                            echo($_SESSION['LANG'] == 'RU' ? 'В соответствии с политикой издательства, в каждом новом выпуске одна статья (наиболее интересная по мнению редакции) выкладывается в открытом доступе ' :
                                'In accordance with the publisher’s policy, there is one article in each new issue (the most interesting according to the editors) is laid out in the public domain ') ?>
                        </p>
                    <? } else {
                        if ($typeOfUser == 'Физ') { ?>
                            <div class="price">
                                <?
                                echo ($_SESSION['LANG'] == 'RU' ? 'Стоимость каждой статьи составляет ' : 'The cost of each article is ')
                                    . number_format($ar_res['PRICE'], 0, ',', '') . '₽'; ?>
                            </div>
                        <? }
                    }
                    ?>
                </div>
            <? } ?>

            <div class="text">
<!--                 <pre>
    <?//print_r($current_issue)?>
</pre> -->

<?//__($current_issue)?>
<?//__($globalAcces)?>

                    <div class="text-buy">
                        <form class="buyVipusk">
                            <input type="hidden" name="IBLOCK_ID" value="6">
                            <input type="hidden" name="TYPE_USER" value="<?= $typeOfUser ?>">
                            <input type="hidden" name="JOURNAL" value="<?= $journals['ID'] ?>">
                            <input type="hidden" name="TYPE" value="<?= 16 ?>">
                            <input type="hidden" name="NAME_JURNAL"
                                   value="<?= $journals['NAME'] ?>">
                            <input type="hidden" name="PRODUCT"
                                   value="<?= $current_issue['ID'] ?>">
                            <input type="hidden" name="PRODUCT_ID" value="<?= $issue_offers[16]['ID'] ?>">
                            <button style="background-color: #000; color: #fff">
                                    <span><?= $_SESSION['LANG'] == 'RU' ? 'Печатный выпуск ' : 'Print issue' ?>
                                        <?= $issue_offers[16]['PRICE'] ?> ₽</span>
                                <span class="img">
                                        <img class="active"
                                             src="<?= SITE_TEMPLATE_PATH ?>/img/icon/magaz-right-white.png" alt="">
                                    </span>
                            </button>
                        </form>
                    </div>

                <? if (!$accessIssue) {
                    ?>

                    <?
                    if ($globalAcces) {
                        ?>
                        <div class="buy-full-article">
                            <a href="/scripts/secure/file.php?TYPE=ISSUE&ID=<?= $current_issue['ID'] ?>&LANG=<?= $_SESSION['LANG'] ?>"
                               target="_blank">
                                <span><?= $_SESSION['LANG'] == 'RU' ? 'Скачать весь выпуск ' : 'Download the entire issue' ?></span>
                                <div class="img"><img
                                            src="<?= SITE_TEMPLATE_PATH ?>/img/icon/magaz-right-white.png"
                                            alt="">
                                </div>
                            </a>
                        </div>
                        <?

                    } else {
                        ?>
                        <div class="text-buy">
                            <form class="buyVipusk">
                                <input type="hidden" name="IBLOCK_ID" value="6">
                                <input type="hidden" name="TYPE_USER" value="<?= $typeOfUser ?>">
                                <input type="hidden" name="JOURNAL" value="<?= $journals['ID'] ?>">
                                <input type="hidden" name="TYPE" value="<?= 17 ?>">
                                <input type="hidden" name="NAME_JURNAL"
                                       value="<?= $journals['NAME'] ?>">
                                <input type="hidden" name="PRODUCT"
                                       value="<?= $current_issue['ID'] ?>">
                                <input type="hidden" name="PRODUCT_ID" value="<?= $issue_offers[17]['ID'] ?>">
                                <button>
                                    <span><?= $_SESSION['LANG'] == 'RU' ? 'PDF выпуска ' : 'PDF version' ?>
                                        <?= $issue_offers[17]['PRICE'] ?> ₽</span>
                                    <span class="img">
                                        <img class="active"
                                             src="<?= SITE_TEMPLATE_PATH ?>/img/icon/magaz-right-black.png" alt="">
                                    </span>
                                </button>
                            </form>
                        </div>
                        <?
                    }
                } else {
                    ?>
                    <div class="buy-full-article">
                        <a href="/scripts/secure/file.php?TYPE=ISSUE&ID=<?= $current_issue['ID'] ?>&LANG=<?= $_SESSION['LANG'] ?>"
                           target="_blank">
                            <span><?= $_SESSION['LANG'] == 'RU' ? 'Скачать весь выпуск ' : 'Download the entire issue' ?></span>
                            <div class="img"><img src="<?= SITE_TEMPLATE_PATH ?>/img/icon/magaz-right-white.png"
                                                  alt="">
                            </div>
                        </a>
                    </div>
                <? } ?>
            </div>

            <div class="items setatribut">
<? //__($object);?>
                <? foreach ($object as $item) {
                    //Определяем доступность статьи для скачивания
                    $access = $globalAcces ? $globalAcces : false;
					
					
					
                    if (!$access) {

                        //1.Прошлогодняя статья?
                        $access = $current_issue['PROPERTY_GOD_VALUE'] < (date('Y')-1) ? true : false;
                        if (!$access) {
                            //2.Бесплатно?
                            $access = $item['PROPERTY_KATEGOR_LIST_VALUE'] == "Доступная для всех" ? true : false;
                            $articlesPriceId = 0; //торг. предложения с ценой для статьи
                            $price = 0;
                            if (!$access) {
                                //3.Куплен выпуск или статья или Покупка подписки на журнал?
 
                                $isAdded = 0;
                                if (in_array($current_issue['ID'], $ID_AA_NUMBER)) {
 //                                   $accessIssue = true;
                                    $isAdded = 1;
                                }
                                if (in_array($item['ID'], $ID_AA_ARTICLES)) {
                                $isAdded = 1;
 //                                   $access = true;
                                }
                                //__($current_issue['ID']);
                                //__($item['ID']);
                                //$arItems['ARTICLES']
                                //__($ID_AA_ARTICLES);
                            }
                            /*
                            $isAdded = 0;
                            foreach ($arBasket as $arItems) {
                                if ($arItems['PRODUCT_ID'] == $priceItem['ID'] && $arItems['ARTICLES'] == $item["ID"]) {//если это торговое предлоежния жернала для выпусков
                                    $isAdded = 1;
                                }
                            }
                            */
                        }
                    }
                    ?>
					<div style="font-size:2em;"><? //echo $item['DETAIL_PAGE_URL'];?></div><br>
                    <div class="item <?= $access ? "item-seen" : "" ?>">
                        <? if ($access) { ?>
						<?//__($item);
						// меняем путь к статье на свойство OLD_ID 
						?>

                            <a href="<?//=$item['PROPERTY_OLD_ID_VALUE']; ?><?= $item['DETAIL_PAGE_URL']?>" target="_blank" class="img">
                                <img src="<?= SITE_TEMPLATE_PATH ?>/img/icon/eye-yellow.svg" alt=""
                                     class="inactive">
                                <img src="<?= SITE_TEMPLATE_PATH ?>/img/icon/eye.svg" alt="" class="active">
                            </a>
                        <? } else if ($typeOfUser == 'Физ') {
                            if ($isAdded) {
                                ?>
                                <div class="img">
                                    <button>
                                        <img src="<?= SITE_TEMPLATE_PATH ?>/img/icon/eye.svg" alt="">
                                    <!--    <img src="<?= SITE_TEMPLATE_PATH ?>/img/icon/added.png" alt=""> -->
                                    </button>
                                </div>
                                <?
                            } else {
                                ?>
                                <div class="img buyArticle-but">
                                    <form action="" class="buyArticle">
                                        <input type="hidden" name="JOURNAL" value="<?= $_REQUEST['JOURNAL_ID'] ?>">
                                        <input type="hidden" name="NAME_JURNAL"
                                               value="<?= $journals['NAME'] ?>">
                                        <input type="hidden" name="PRODUCT_ID_ARTICLES" value="<?= $item['ID'] ?>">
                                        <input type="hidden" name="PRODUCT_NAME_ARTICLES"
                                               value="<?= $item['NAME'] ?>">
                                        <input type="hidden" name="PRODUCT_ID" value="<?= $priceItem['ID'] ?>">
                                        <button>
                                            <img src="<?= SITE_TEMPLATE_PATH ?>/img/icon/shopping-cart-black.png"
                                                 alt="" class="active">
                                            <img src="<?= SITE_TEMPLATE_PATH ?>/img/icon/shopping-cart-yellow.png"
                                                 alt="" class="inactive">
                                        </button>
                                    </form>
                                </div>
                                <div class="img inthecar-but" style="display: none">
                                    <button>
                                        <img src="<?= SITE_TEMPLATE_PATH ?>/img/icon/added.png" alt="">
                                    </button>
                                </div>
                                <?
                            }
                        } else {
                            ?>
                            <div class="img buyArticle-but">

                                <button>
                                    <img src="<?= SITE_TEMPLATE_PATH ?>/img/icon/shopping-cart-grey.png"
                                         alt="">
                                </button>

                            </div>
                            <?
                        } ?>
                        <div class="text">
                            <div class="text-name">
						<?//__($item);
						// меняем путь к статье на свойство OLD_ID 
						?>
                                <a target="_blank"
                                   href="<?//=$item['PROPERTY_OLD_ID_VALUE']; ?><?= $item['DETAIL_PAGE_URL']?>"><?= $_SESSION['LANG'] == 'RU' ? $item["NAME"] : $item["PROPERTY_NAME_EN_VALUE"] ?></a>
                            </div>
                            <div class="text-author">
                                <?
                                $authorList = array();
                                $db_props = CIBlockElement::GetProperty(4, $item['ID'], array("sort" => "asc"), Array("CODE" => "AUTORS"));
                                while ($db_props_value = $db_props->Fetch()) {
                                    $authorList[] = $db_props_value['VALUE'];
                                }
                                if ($_SESSION['LANG'] == 'EN') {

                                    $hldata = Bitrix\Highloadblock\HighloadBlockTable::getById(2)->fetch();
                                    $entity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hldata);
                                    $entityClass = $entity->getDataClass();

                                    $authorResult = $entityClass::getList(array(
                                        'select' => array('UF_NAME_EN'),
                                        'filter' => array('UF_NAME' => $authorList)
                                    ));

                                    $authorList = array();
                                    while ($row_two = $authorResult->fetch()) {
                                        $authorList[] = $row_two['UF_NAME_EN'];
                                    };
                                }
                                ?>
                                <? 
                                    $total = count($authorList);
                                    $counter = 0;
                                    foreach ($authorList as $value) { 
                                        $counter++;

                                        if($counter == $total){
                                        ?>

                                    <p><?= $value ?></p>

                                <? 
                                    }else{
                                        ?>
                                        <p><?= $value ?>, </p>

                                        <?
                                    }

                                } ?>

                            </div>
                            <? if ($item['PROPERTY_PDF_EN_VALUE']) {
                                ?>
                                <a href="<?= $item['DETAIL_PAGE_URL'] . '?LANG=EN' ?>"
                                   class="text-en">
                                    <?= $_SESSION['LANG'] == 'RU' ? 'EN-версия' : 'EN-version' ?>
                                </a>
                                <?
                            } ?>
                        </div>
                    </div>
                <? } ?>

                <div class="item item-seen">
                    <div class="img">
                        <!-- <img src="<?= SITE_TEMPLATE_PATH ?>/img/icon/eye-yellow.svg" alt=""> -->
                    </div>
                    <div class="text">
                        <div class="text-name">
                            <a target="_blank" href="javascript: void(0)"></a>
                        </div>
                        <div class="text-author">
                            <p></p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

<? } ?>

<script>
    $('.arhiv-content-block-close').click(function () {
        $('.arhiv').fadeOut();
        $('.arhiv-content').fadeOut();
    })
</script>