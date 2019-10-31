<?php
/*
 * DESTINATION: MySQLd
 */
define('MYSQL_HOST', 'localhost');
define('MYSQL_USER', 'root');
define('MYSQL_PASSWORD','');
define('MYSQL_DATABASE','k1267566_pusbasa');

/*
 * SOME HELPER CONSTANT
 */
define('CHUNK_SIZE', 1000);

/*
 * STOP EDITING!
 */

set_time_limit(0);

function addQuote($string)
{
	return "'".$string."'";
}

function addTilde($string)
{
	return "`".$string."`";
}

$serverName = "localhost";
// Connect MS SQL
$connectionInfo = array("Database"=>"pusBahasa", "UID"=>"sa", "PWD"=>"");
$mssql_connect = sqlsrv_connect( $serverName, $connectionInfo);
if( $connectionInfo ) {
     echo "Connection established.<br />";
}else{
     echo "Connection could not be established.<br />";
     die( print_r( sqlsrv_errors(), true));
}
echo "=> Connected to SQL Server on '".$serverName."'\n";

// Select MS SQL Database
//$mssql_db = sqlsrv_select_db(MSSQL_DATABASE, $mssql_connect) or die("Couldn't open database '".MSSQL_DATABASE."'\n");
//echo "=> Found database '".MSSQL_DATABASE."'\n";

// Connect to MySQL
$mysql_connect = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD) or die("Couldn't connect to MySQL on '".MYSQL_HOST."'' user '".MYSQL_USER."'\n");
echo "\n=> Connected to MySQL Server on ".MYSQL_HOST."\n";

// Select MySQL
$mysql_db = mysqli_select_db($mysql_connect, MYSQL_DATABASE) or die("Couldn't open database '".MYSQL_DATABASE."'\n");
echo "=> Found database '".MYSQL_DATABASE."'\n";

// Inisialisasi
$location = array();
$mssql_tables = array();
$tgltest = array();
$listening = array();
$structure = array();
$reading = array();
$tglentry = array();
$userentry = array();
$serial = array();
$codes = array();
$coden = array();
$total = array();
$level = array();
$qrcode = array();
$kodeasal = array();
$idnumber = array();

// Get MS SQL tables
$sql = "SELECT * FROM proficiency";
$params = array();
$options =  array( "Scrollable" => SQLSRV_CURSOR_KEYSET );
$res = sqlsrv_query($mssql_connect,$sql);
echo "\n=> Getting tables..<br/>";
while ($row = sqlsrv_fetch_array( $res, SQLSRV_FETCH_ASSOC))
{
	array_push($mssql_tables, $row['name']);
	array_push($location, $row['location']);
	array_push($tgltest, $row['tglTest']->format('Y-m-d H:i:s.u'));
	array_push($listening, $row['listening']);
	array_push($structure, $row['structure']);
	array_push($reading, $row['reading']);
	array_push($tglentry, $row['tglEntry']->format('Y-m-d H:i:s.u'));
	array_push($userentry, $row['userEntry']);
	array_push($serial, $row['serial']);
	array_push($codes, $row['codeS']);
	array_push($coden, $row['codeN']);
	array_push($total, $row['total']);
	array_push($level, $row['level']);
	array_push($qrcode, $row['qrcode']);
	array_push($kodeasal, $row['kodeAsal']);
	array_push($idnumber, $row['idNumber']);

}
echo "*> Found ". number_format(count($mssql_tables),0,',','.') ." tables\n\n<br/>";


if (!empty($mssql_tables AND $serial))
{
	$i = 1;
	foreach ($mssql_tables as $index => $table)
	{
		echo '> '.$i.'. '.$serial[$index]."<>".$table."<>".$tglentry[$index];
		echo "===> Get ".$serial[$index]." from SQL Server";

		$sql2 = "SELECT * FROM proficiency WHERE name = '$table' AND serial = '$serial[$index]'";
		if ($result=mysqli_query($mysql_connect,$sql2))
  {
   if(mysqli_num_rows($result) > 0)
    {
      echo "===>Exists<br/> ";
    }
  else{
      echo "===>Doesn't exist<br/>";

	  $sql3 = "INSERT INTO proficiency (name,tglTest,location,listening,structure,reading,tglEntry,userEntry,serial,codeS,codeN,total,level,qrcode,kodeAsal,idNumber)
VALUES ('$table','$tgltest[$index]','$location[$index]','$listening[$index]','$structure[$index]','$reading[$index]','$tglentry[$index]','$userentry[$index]','$serial[$index]','$codes[$index]','$coden[$index]','$total[$index]','$level[$index]','$qrcode[$index]','$kodeasal[$index]','$idnumber[$index]')";
		if (mysqli_query($mysql_connect, $sql3)) {
			echo "+>New record created successfully<br/>";
		} else {
			echo "*>Error: " . $sql . "<br>" . mysqli_error($mysql_connect);
		}
  }
  }
else{
echo "*>Query Failed.";}


		$i++;
	}

}

echo "\n*>Done!\n";

sqlsrv_close($mssql_connect);
mysqli_close($mysql_connect);
