<?
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

// Добавить в группу
// https://www.mediasphera.ru/scripts/registerAPI.php?new_user=Y&EMAIL=79041602509@ya.ru&PERSONAL_GENDER=M&SECOND_NAME=Анатольевич&NAME=Андрей&LAST_NAME=Соловьев
// удалить из группы
// https://www.mediasphera.ru/scripts/registerAPI.php?new_user=N&EMAIL=79041602509@ya.ru&PERSONAL_GENDER=M&SECOND_NAME=Анатольевич&NAME=Андрей&LAST_NAME=Соловьев

global $USER;
$access = false; // изменил 28.12.2020
// $access = true;
$idGroup = '';
$mail_send = true;
$SEND_YES = false;

$filter = Array
(
    "!=DESCRITION" => false,
);
$rsGroups = CGroup::GetList(($by = "c_sort"), ($order = "desc"), $filter); // выбираем группы
$is_filtered = $rsGroups->is_filtered; // отфильтрована ли выборка ?

$REMOTEADDR = preg_replace("/[^.0-9]/", '', $_SERVER['REMOTE_ADDR']);

while ($rsGroups->NavNext(true, "f_")) :
   
    $DESCRIPTION = [];
    $f_DESCRIPTION = str_replace(array("\r\n", "\r", "\n"), ' ', $f_DESCRIPTION);
    $f_DESCRIPTION = str_replace(",", " ", $f_DESCRIPTION);
    $DESCRIPTION = explode(" ", $f_DESCRIPTION);
    $DESCRIPTION = array_diff($DESCRIPTION, array(''));

    if (in_array($REMOTEADDR, $DESCRIPTION)) {
   
        $access = true;
        $idGroup = $f_ID;
        break;
    } else {

        $access = false;
        $mail_send = true;
        $MESS_ERROR = ' <br> нет группы <br> ';
    }

endwhile;

foreach ($DESCRIPTION as $value) {
   
    $pos = strpos($value, '@');
    if ($pos !== false) {
        $EMAIL_COPY = $value;
  };
};

// отправляем контрольное письмо с данными кто пришел
/*  
$MESS = serialize($_REQUEST);
$MESS = $MESS.'&IP='.$REMOTEADDR.'&EMAIL_COPY='.$EMAIL_COPY.'!!!';

 if (mail("79041602509@ya.ru","Проверка почты 123", $MESS,"From: 79041602509@ya.ru")) {
 //echo "Сообщение передано функции mail, проверьте почту в ящике.";
 } else {
// echo "Функция mail не работает, свяжитесь с администрацией хостинга.";
 };

*/

if ($access && $_REQUEST['EMAIL'] && (($_REQUEST['new_user'] == 'Y') || ($_REQUEST['new_user'] == 'N'))) {

    $mail_send = false;

    // собираем поля
    if ($_REQUEST['NAME']) {$NAME = $_REQUEST['NAME'];} //имя
    if ($_REQUEST['LAST_NAME']) {$LAST_NAME = $_REQUEST['LAST_NAME'];} //фамилия
    if ($_REQUEST['SECOND_NAME']) {$SECOND_NAME = $_REQUEST['SECOND_NAME'];} //Отчество
    if ($_REQUEST['LOGIN']) {$EMAIL = $_REQUEST['EMAIL'];} //имя входа
    if ($_REQUEST['EMAIL']) {$EMAIL = $_REQUEST['EMAIL'];} //E-Mail адрес
    if ($_REQUEST['WORK_COMPANY']) {$WORK_COMPANY = $_REQUEST['WORK_COMPANY'];} // место работы
    if ($_REQUEST['WORK_POSITION']) {$WORK_POSITION = $_REQUEST['WORK_POSITION'];} //должность
    if ($_REQUEST['PERSONAL_PHONE']) {$COUNTRY = $_REQUEST['PHONE'];} //номер телефона
    if ($_REQUEST['PERSONAL_STATE']) {$PHONE = $_REQUEST['STATE'];} //область / край
    if ($_REQUEST['PERSONAL_CITY']) {$CITY = $_REQUEST['CITY'];} //город
    if ($_REQUEST['PERSONAL_ZIP']) {$ZIP = $_REQUEST['ZIP'];} //почтовый индекс
    if ($_REQUEST['PERSONAL_STREET']) {$STREET = $_REQUEST['STREET'].', '.$_REQUEST['HOME'].', '.$_REQUEST['APARTMENT'];} // улица + дом + квартира
    if ($_REQUEST['UF_COUNTRY']) {$COUNTRY = $_REQUEST['COUNTRY'];} //Страна
    if ($_REQUEST['UF_PERSONAL_HOME']) {$HOME = $_REQUEST['HOME'];} //Дом
    if ($_REQUEST['UF_PERSONAL_APARTMEN']) {$APARTMENT = $_REQUEST['APARTMENT'];} //Квартира
    if ($_REQUEST['PERSONAL_GENDER']) {$FLOOR = $_REQUEST['PERSONAL_GENDER'];} // пол пользователя
    // ищем пользователя по почте и если нет то создаем его

    if ($FLOOR == "M") {

        $FLOOR_NAME = 'Уважаемый';
        $FLOOR = 1;

    } elseif ($FLOOR == "F") {

        $FLOOR_NAME = 'Уважаемая';
        $FLOOR = 2;

    } else {

        $FLOOR_NAME = 'Уважаемый(ая)';

    };


    $filter1 = Array("EMAIL" => $EMAIL);
        $GROUP_ID = Array(3,4);
        $id_user = 0;

        // ищем на сайте пользователя по email и получаем его ID если он найден
        // получаем группы этого пользователя 
	 $sql = CUser::GetList(($by="id"), ($order="desc"), $filter1);
	 if ($sql->NavNext(true, "f_")) { $id_user = $f_ID; $GROUP_ID = CUser::GetUserGroup($id_user);};

        // __($_REQUEST);
        // добавляем группу к массиву групп (если уже были на сайте)
	// $GROUP_ID[] = 5;
	// $GROUP_ID[] = $idGroup;
	// $GROUP_ID = array_diff($GROUP_ID, array(6));
    // $GROUP_ID = array_unique($GROUP_ID);
    
    if ($_REQUEST['new_user'] == "Y") {

            if (in_array($idGroup, $GROUP_ID)) {
                $SEND_YES = false;
            } else {
                $SEND_YES = true;
            };

        $GROUP_ID[] = 5;
        $GROUP_ID[] = $idGroup;
        $GROUP_ID = array_diff($GROUP_ID, array(6));
        $GROUP_ID = array_unique($GROUP_ID);

    } else {

        $GROUP_ID[] = 5;
       // $GROUP_ID[] = $idGroup;
        $GROUP_ID = array_diff($GROUP_ID, array($idGroup));
        $GROUP_ID = array_unique($GROUP_ID);

    };

	$fields1 = Array(
	"GROUP_ID"          	=> $GROUP_ID,
  	"LID"               	=> 's1',
  	"ACTIVE"            	=> "Y",
  	"NAME" 					=> $NAME, //имя
  	"LAST_NAME" 			=> $LAST_NAME, //фамилия
  	"SECOND_NAME"			=> $SECOND_NAME, //Отчество
  	"LOGIN"					=> $EMAIL, //имя входа
  	"EMAIL"					=> $EMAIL, //E-Mail адрес
  	"WORK_COMPANY"			=> $WORK_COMPANY, // место работы
  	"WORK_POSITION"			=> $WORK_POSITION, //должность
  	"PERSONAL_PHONE"		=> $COUNTRY, //номер телефона
	"PERSONAL_STATE"		=> $PHONE, //область / край
  	"PERSONAL_CITY"			=> $CITY, //город
  	"PERSONAL_ZIP"			=> $ZIP, //почтовый индекс
  	"PERSONAL_STREET"		=> $STREET, // улица + дом + квартира
  	"UF_COUNTRY"			=> $COUNTRY, //Страна
  	"UF_PERSONAL_HOME"		=> $HOME, //Дом
    "UF_PERSONAL_APARTMEN"	=> $APARTMENT, //Квартира
    "UF_FLOOR"              => $FLOOR, // пол пользователя
  	);

	  $user = new CUser;
	  if ($id_user >0) {
            // если пользователь найден в базе то обновляем его данные
            $user->Update($id_user, $fields1);
            $strError .= $user->LAST_ERROR;
            // echo 'Пользователь изменен. '. $EMAIL;
	  } else {
            // если пользователь ненайдет то создаем нового с новыми данными
            $passuser = time();
            $SEND_YES = true;
            if (!$passuser) {$passuser = 'lR06Tno9';};

            $pass = Array(
                "PASSWORD"          => $passuser,
                "CONFIRM_PASSWORD"  => $passuser
            );
            $fields1 = array_merge($fields1,$pass);
            $ID = $user->Add($fields1);
                if (intval($ID) > 0) {
                //    echo '<b>Пользователь успешно добавлен.</b> '. $EMAIL;
                }	else {
                //echo $user->LAST_ERROR;  
                $LAST_ERROR = $user->LAST_ERROR;
                echo json_encode(array('status' => false, 'message' => $LAST_ERROR));
                //__($fields1);
                };


            // конец кода 

            // получаем данные из журнала для рассылки
            // если добавлен пользователя то отправляем рассылку
		};

        CModule::IncludeModule("iblock");
        $element = CIBlockElement::GetList(false, array('IBLOCK_ID' => 5, 'PROPERTY_FREE_GROUP' => $idGroup), false, false, array('DETAIL_PAGE_URL', 'NAME'))->GetNext();

        $LINK_JOURNAL = $element['DETAIL_PAGE_URL'];
        $NAME_JOURNAL = $element['NAME'];

        if (($_REQUEST['new_user'] == 'Y') && ($SEND_YES)){

            if ($idGroup == 15) {
                
                $SEND_GROUP = 'SEND_15';

            } else {

                $SEND_GROUP = 'SEND_JOURNAL_FREE';

            }

            \Bitrix\Main\Mail\Event::sendImmediate(array(

              "EVENT_NAME" => $SEND_GROUP, 
            //    "EVENT_NAME" => 'SEND_JOURNAL_FREE', 
                "LID" => "s1", 
                "C_FIELDS" => array( 
                    "EMAIL_TO" => $EMAIL,
                //	"USER_ID" => 235 
                    "NAME" => $NAME,
                    "SECOND_NAME" => $SECOND_NAME,
                    "LINK_JOURNAL" => $LINK_JOURNAL,
                    "NAME_JOURNAL" => $NAME_JOURNAL,
                    "FLOOR_NAME" => $FLOOR_NAME,
                    "EMAIL_COPY" => $EMAIL_COPY,
                ),         
            ));
            
        };



        
            echo json_encode(array('status' => true, 'message' => 'Journal had been opened for user'));

} else {
    if (!$access) {
        $MESS_ERROR = $MESS_ERROR.' <br> error: connection denied. You don\'t have access to api<br> ';
        $mail_send = true;
        echo json_encode(array("status" => false, "message" => 'error: connection denied. You don\'t have access to api'));
    } else {
        $MESS_ERROR = $MESS_ERROR.' <br> error: connection denied. Received data is not correct<br> ';
        $mail_send = true;
        echo json_encode(array("status" => false, "message" => 'error: connection denied. Received data is not correct'));
    }
}

if ($mail_send) {

    $MESS = serialize($_REQUEST);
    $MESS = $MESS.'&IP='.$REMOTEADDR.'&EMAIL_COPY='.$EMAIL_COPY.'!!!';
    $MESS = $MESS.$MESS_ERROR;

    $MESS_TEMA = 'Ошибка registerAPI.php от mediasphera.ru';

    if (mail("79041602509@ya.ru",$MESS_TEMA, $MESS,"From: 79041602509@ya.ru")) {
    //echo "Сообщение передано функции mail, проверьте почту в ящике.";
    } else {
    // echo "Функция mail не работает, свяжитесь с администрацией хостинга.";
    };

};

?>