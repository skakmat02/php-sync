<?php
/*
 * SOURCE: MS SQL
 */

/*
 * DESTINATION: MySQL
 */
define('MYSQL_HOST', 'localhost');
define('MYSQL_USER', 'root');
define('MYSQL_PASSWORD','');
define('MYSQL_DATABASE','pusbasacek');

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

$serverName = "ASEK-PC\SQLEXPRESS";
// Connect MS SQL
$connectionInfo = array(  "Database"=>"pusbasacek", "UID"=>"sa", "PWD"=>"asekmasuk");
$mssql_connect = sqlsrv_connect( $serverName, $connectionInfo);
if( $connectionInfo ) {
     echo "Connection established.<br />";
}else{
     echo "Connection could not be established.<br />";
     die( print_r( sqlsrv_errors(), true));
}
echo "=> Connected to Source MS SQL Server on '".$serverName."'\n";

// Select MS SQL Database
//$mssql_db = sqlsrv_select_db(MSSQL_DATABASE, $mssql_connect) or die("Couldn't open database '".MSSQL_DATABASE."'\n"); 
//echo "=> Found database '".MSSQL_DATABASE."'\n";

// Connect to MySQL
$mysql_connect = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD) or die("Couldn't connect to MySQL on '".MYSQL_HOST."'' user '".MYSQL_USER."'\n");
echo "\n=> Connected to Source MySQL Server on ".MYSQL_HOST."\n";

// Select MySQL Database
$mssql_db = mysql_select_db(MYSQL_DATABASE, $mysql_connect) or die("Couldn't open database '".MYSQL_DATABASE."'\n"); 
echo "=> Found database '".MYSQL_DATABASE."'\n";

$mssql_tables = array();

// Get MS SQL tables
$sql = "SELECT * FROM proficiency";
$params = array();
$options =  array( "Scrollable" => SQLSRV_CURSOR_KEYSET );
$res = sqlsrv_query($mssql_connect,$sql,$params,$options);
echo "\n=> Getting tables..\n";
while ($row = sqlsrv_fetch_array($res, 2))
{
	array_push($mssql_tables, $row['name']);
	//echo ($row['name'])."\n";
}
echo "==> Found ". number_format(count($mssql_tables),0,',','.') ." tables\n\n";

// Get Table Structures
if (!empty($mssql_tables))
{
	$i = 1;
	foreach ($mssql_tables as $table)
	{
		echo '====> '.$i.'. '.$table."\n";
		echo "=====> Getting info table ".$table." from SQL Server\n";

		$sql = "SELECT * FROM tableproficiency WHERE table_name = '".$table."'";
		$res = sqlsrv_query($mssql_connect,$sql);

		if ($res) 
		{
			$mssql_tables[$table] = array();

			$mysql = "DROP TABLE IF EXISTS '".$table."'";
			sqlsrv_query($mssql_connect,$mysql);
			$mysql = "CREATE TABLE ".$table."";
			$strctsql = $fields = array();

			while ($row = sqlsrv_fetch_array($res, 2))
			{
				//print_r($row); echo "\n";
				array_push($mssql_tables[$table], $row);

				switch ($row['DATA_TYPE']) {
					case 'bit':
					case 'tinyint':
					case 'smallint':
					case 'int':
					case 'bigint':
						$data_type = $row['DATA_TYPE'].(!empty($row['NUMERIC_PRECISION']) ? '('.$row['NUMERIC_PRECISION'].')' : '' );
						break;
					
					case 'money':
						$data_type = 'decimal(19,4)';
						break;
					case 'smallmoney':
						$data_type = 'decimal(10,4)';
						break;
					
					case 'real':
					case 'float':
					case 'decimal':
					case 'numeric':
						$data_type = $row['DATA_TYPE'].(!empty($row['NUMERIC_PRECISION']) ? '('.$row['NUMERIC_PRECISION'].(!empty($row['NUMERIC_SCALE']) ? ','.$row['NUMERIC_SCALE'] : '').')' : '' );
						break;

					case 'date':
					case 'datetime':
					case 'timestamp':
					case 'time':
						$data_type = $row['DATA_TYPE'];
					case 'datetime2':
					case 'datetimeoffset':
					case 'smalldatetime':
						$data_type = 'datetime';
						break;

					case 'nchar':
					case 'char':
						$data_type = 'char'.(!empty($row['CHARACTER_MAXIMUM_LENGTH']) && $row['CHARACTER_MAXIMUM_LENGTH'] > 0 ? '('.$row['CHARACTER_MAXIMUM_LENGTH'].')' : '(255)' );
						break;
					case 'nvarchar':
					case 'varchar':
						$data_type = 'varchar'.(!empty($row['CHARACTER_MAXIMUM_LENGTH']) && $row['CHARACTER_MAXIMUM_LENGTH'] > 0 ? '('.$row['CHARACTER_MAXIMUM_LENGTH'].')' : '(255)' );
						break;
					case 'ntext':
					case 'text':
						$data_type = 'text';
						break;

					case 'binary':
					case 'varbinary':
						$data_type = $data_type = $row['DATA_TYPE'];
					case 'image':
						$data_type = 'blob';
						break;

					case 'uniqueidentifier':
						$data_type = 'char(36)';//'CHAR(36) NOT NULL';
						break;

					case 'cursor':
					case 'hierarchyid':
					case 'sql_variant':
					case 'table':
					case 'xml':
					default:
						$data_type = false;
						break;
				}

				if (!empty($data_type))
				{
					$ssql = "`".$row['COLUMN_NAME']."` ".$data_type." ".($row['IS_NULLABLE'] == 'YES' ? 'NULL' : 'NOT NULL');
					array_push($strctsql, $ssql);
					array_push($fields, $row['COLUMN_NAME']);	
				}
				
			}

			$mysql .= "(".implode(',', $strctsql).");";
			echo "======> Creating table ".$table." on MySQL... ";
			$q = mysql_query($mysql);
			echo (($q) ? 'Success':'Failed!'."\n".$mysql."\n")."\n";
			
			echo "=====> Getting data from table ".$table." on SQL Server\n";
			
					
			$sql = "SELECT * FROM ".$table;
			$params = array();
$options =  array( "Scrollable" => SQLSRV_CURSOR_KEYSET );
			$qres = sqlsrv_query($mssql_connect,$sql,$params,$options);
			$numrow = sqlsrv_num_rows($mssql_connect,$qres,$sql,$params,$options);
			echo "======> Found ".number_format($numrow,0,',','.')." rows\n";

			if ($qres)
			{
				echo "=====> Inserting to table ".$table." on MySQL\n";
				$numdata = 0;
				if (!empty($fields))
				{
					$sfield = array_map('addTilde', $fields);
					while ($qrow = sqlsrv_fetch_array($qres, 2))
					{
						$datas = array();
						foreach ($fields as $field) 
						{
							$ddata = (!empty($qrow[$field])) ? $qrow[$field] : '';
							array_push($datas,"'".mysql_real_escape_string($ddata)."'");
						}

						if (!empty($datas))
						{
							//$datas = array_map('addQuote', $datas);
							//$fields = 
							$mysql = "INSERT INTO `".$table."` (".implode(',',$sfield).") VALUES (".implode(',',$datas).");";
							//$mysql = mysql_real_escape_string($mysql);
							//echo $mysql."\n";
							$q = mysql_query($mysql);
							$numdata += ($q ? 1 : 0 );
						}
						if ($numData % CHUNK_SIZE == 0) {
							echo "===> ".number_format($numdata,0,',','.')." data inserted so far\n";
						}
					}
				}
				echo "======> ".number_format($numdata,0,',','.')." data inserted total\n\n";
			}
		}
		$i++;
	}

}

echo "Done!\n";

sqlsrv_close($mssql_connect);
mysql_close($mysql_connect);
