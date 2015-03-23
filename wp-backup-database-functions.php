<?php

// 获取创建某一个表的SQL
function get_database_table_structure($table){
	global $wpdb;
	$create_table = $wpdb->get_results("SHOW CREATE TABLE $table");
	if(!$create_table)return;
	$table_dump = "DROP TABLE IF EXISTS $table;\n";	
	$create_table = (array)$create_table[0];
	$create_table = $create_table['Create Table'];
	$table_dump .= $create_table.";";
	return $table_dump;
}
// 获取某一个表内的数据
// 在BAE上，如果一次读取全部数据，数据行较多时容易备份失败
// 手动备份设定3000行可成功备份，计划任务就不成功，因此需要根据实际来设置
function get_database_table_records($table){
	global $wpdb;
	$limit = 0;
	$records = array();
	do{
		$table_data = $wpdb->get_results("SELECT * FROM $table limit $limit,1000",ARRAY_A);
		if(!$table_data)break;
		$search = array("\x00", "\x0a", "\x0d", "\x1a");
		$replace = array('\0', '\n', '\r', '\Z');
		if($table_data)foreach($table_data as $record){
			$values = array();
			foreach($record as $value){
				if('' === $value || $value === null){
					$values[] = "''";
				}elseif(is_numeric($value)){
					$values[] = $value;
				}else{
					$value = str_replace('\\','\\\\',$value);
					$value = str_replace('\'','\\\'',$value);
					$values[] = "'".str_replace($search,$replace,$value)."'";
				}
			}
			$records[] = "(".implode(',',$values).")";
		}
		$limit += 1000;
	}while(count($table_data)==1000);
	if(count($records)==0) return;
	$records_dump = "INSERT INTO $table VALUES \n".implode(", \n",$records).';';
	return $records_dump;
}
// 获得最终需要的数据表备份SQL语句
function get_database_backup_table_sql($table){
	$sql_table_structure .= get_database_table_structure($table);
	$sql_table_data .= get_database_table_records($table);
	$sql = '';
	$sql .= $sql_table_structure;
	$sql .= "\n\n";
	if(trim($sql_table_data)){
		$sql .= $sql_table_data;
		$sql .= "\n\n";
	}
	return $sql;
}
// 获取所有表
function get_database_tables(){
	global $wpdb;
	$tables = $wpdb->get_results("SHOW TABLE STATUS");
	return $tables;
}
// 获取最终的所有SQL语句，但这样可能让文件很大，不利于导入，因此建议采用分表导出的方式（还未开发）
function get_database_backup_all_sql(){
	$tables = get_database_tables();
	$sql = '';
	if(!empty($tables))foreach($tables as $table){
		$table = $table->Name;
		$sql .= get_database_backup_table_sql($table);
	}
	return $sql;
}