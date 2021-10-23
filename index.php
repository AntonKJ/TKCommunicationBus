<?php

/**
 * Class CommunicationBus
 */

class CommunicationBus {

    public $persistency = '';
    public $user;
    public $password;
    public $server;
    public $dbname;
    public $db_connect_id;
    public $sql_time; // Время выполнения запроса
    public $sqlRequest; // запрос
    public $timeStartProcessing;
    public $timeEndProcessing;
    public $requestHandler;
    public $sqlRequestData; // ответ
    public $sqlRequestDataType; // тип ответа
    public $sqlEndRequest;
    public $flagResultProcessing; // успешность обработки true/false
    public $sqlRequestStatus; // обращение к скулю rtue/false
    public $sqlConnection; //Подключаться к БД или нет


    public function __construct ( $servername, $username, $password, $dbname, $sql = false, $type = false, $connection) {

        $mtime = microtime();
        $mtime = explode(" ", $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $this->timeStartProcessing = $mtime;

        if ($type === 'ODCB') {
           $echoconn =  $this->sql_db('MSSQLServer', $username, $password, $dbname, '', 'dsn', $connection);

	} else {
            $this->sql_db($servername, $username, $password, $dbname);
        }

        //$sql = $this->sql_query('Select GETDATE() as current_date');
        if ($sql) {
            $sql = $this->sql_query($sql);
        } else {
            $sql = $this->sql_query('SELECT @@VERSION');
        }

        $mtime = microtime();
        $mtime = explode(" ", $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $this->timeEndProcessing = $mtime;
    }

    public function sql_query($sql){
        $res = odbc_exec($this->db_connect_id, $sql);
        $items = 0;

	if (@odbc_error($res)) {
		$this->flagResultProcessing = 'FALSE';
	} else {
		$this->flagResultProcessing = 'TRUE';
	}

        $row = odbc_fetch_array($res);
	
	$this->sqlRequest = $sql;
	$this->sqlRequestDataType = (is_array($row) ? 'array' : (is_string($row) ? 'string' : 'object'));
        $this->sqlRequestData = print_r($row, true);
        //return $this->sqlRequestData;
    }

    public function sql_db($sqlserver, $sqluser, $sqlpassword, $database, $persistency = true, $dsn = false, $connection)
    {
        $mtime = microtime();
        $mtime = explode(" ", $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $starttime = $mtime;
        $this->persistency = $persistency;
        $this->user = $sqluser;
        $this->password = $sqlpassword;
        $this->server = $sqlserver;
        $this->dbname = $database;
        $this->sqlConnection = $connection;

        if ($dsn == 'dsn' && $connection == true) {

            $this->db_connect_id = odbc_connect($this->server, $this->user, $this->password);

            if (!$this->db_connect_id) {
                $this->sqlRequestStatus =  'Status connection FALSE';
                $mtime = microtime();
                $mtime = explode(" ", $mtime);
                $mtime = $mtime[1] + $mtime[0];
                $endtime = $mtime;
                $this->sql_time += $endtime - $starttime;
                return false;
            }

        } else {

            $this->db_connect_id = $this->persistency ? @mssql_pconnect($this->server, $this->user, $this->password) : @mssql_connect($this->server, $this->user, $this->password);

            if ($this->db_connect_id && $this->dbname != "") {
                if (!mssql_select_db($this->dbname, $this->db_connect_id)) {
                    mssql_close($this->db_connect_id);
                    $this->sqlRequestStatus = 'Status connection FALSE';
                    $mtime = microtime();
                    $mtime = explode(" ", $mtime);
                    $mtime = $mtime[1] + $mtime[0];
                    $endtime = $mtime;
                    $this->sql_time += $endtime - $starttime;
                    return false;
                }
            }
        }

        $this->sqlRequestStatus = 'Status connection TRUE';
        $mtime = microtime();
        $mtime = explode(" ", $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $endtime = $mtime;
        $this->sqlEndRequest = $endtime;
        $this->sql_time += $endtime - $starttime;
        return $this->db_connect_id;
    }
}

$result_data = '';

if(count($_POST)>1) {
 $result_data = implode(',',$_POST);
}

$data = array_shift(array_keys($_POST));

if(strlen($data) > 5){
	if (preg_match('|(\[||\])|i', $data)) {
		preg_match('|^(\[)(.*)(\])$|i', $data, $matches);
  		$result_data = $matches[1];
	} else {
		$result_data = $data;
	}
}

$dbconnect = new CommunicationBus( '', 'dwh_app', 'YKzyHpGfYP', 'gatestore_test', '', 'ODCB', true );
$dbconnect->sql_query("exec dbo.incoming_http_query @Data = N'".$result_data."';");

// print('exec dbo.incoming_http_query @Data = N\''.$result_data.'\';');

// var_dump($dbconnect);

echo 'id connection: '.$dbconnect->db_connect_id . '<br>';
echo 'Время выполнения запроса:'. $dbconnect->sql_time . '<br>'; // Время выполнения запроса
echo 'Запрос:'.$dbconnect->sqlRequest . '<br>'; // запрос
echo 'Общее время старта:'.$dbconnect->timeStartProcessing . '<br>';
echo 'Общее время остановки:'.$dbconnect->timeEndProcessing . '<br>';
echo 'Заголовки:'.$dbconnect->requestHandler . '<br>';
echo 'Тело ответа:'.$dbconnect->sqlRequestData . '<br>'; // ответ
echo 'Тип тела ответа:'.$dbconnect->sqlRequestDataType . '<br>'; // тип ответа
echo 'время завершения запроса в БД:'.$dbconnect->sqlEndRequest . '<br>';
echo 'Успешность обработки:'.$dbconnect->flagResultProcessing . '<br>'; // успешность обработки true/false
echo 'Обращение к скулю:'.$dbconnect->sqlRequestStatus . '<br>'; // обращение к скулю rtue/false


// запись в лог

$str = 'id connection: ' . $dbconnect->db_connect_id . ' | ' . PHP_EOL;
$str .= 'Время выполнения запроса:'. $dbconnect->sql_time . ' | ' . PHP_EOL; // Время выполнения
$str .= 'Запрос:'.$dbconnect->sqlRequest . ' | ' . PHP_EOL; // запрос
$str .= 'Общее время старта:'.$dbconnect->timeStartProcessing . ' | ' . PHP_EOL;
$str .= 'Общее время остановки:'.$dbconnect->timeEndProcessing . ' | ' . PHP_EOL;
$str .= 'Заголовки:'.$dbconnect->requestHandler . ' | ' . PHP_EOL;
$str .= 'Тело ответа:'.$dbconnect->sqlRequestData . ' | ' . PHP_EOL; // ответ
$str .= 'Тип тела ответа:'.$dbconnect->sqlRequestDataType . ' | ' . PHP_EOL; // тип ответа
$str .= 'время завершения запроса в БД:'.$dbconnect->sqlEndRequest . ' | ' . PHP_EOL;
$str .= 'Успешность обработки:'.$dbconnect->flagResultProcessing . ' | ' . PHP_EOL; // успех
$str .= 'Обращение к скулю:'.$dbconnect->sqlRequestStatus . ' | ' . PHP_EOL; // обращение

//echo $str;

$log = "communication_log.txt";
$message = $str . " - обновлен" . date('d.m.Y H:i:s') . "\n";
file_put_contents($log, $str, FILE_APPEND);

echo $massage;
