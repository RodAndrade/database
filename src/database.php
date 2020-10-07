<?php 
	function connect_db(){
		try {
			$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
			return $conn;
		} catch (Exception $e) {
			return null;
		}
	}
	function destroy_db($conn){
		mysqli_close($conn);
	}

	function insertDB($table, $data){
		$log['type'] = 'insert';

		if(count($data) > 0){
			$conn = connect_db();

			if($conn != null){
				$colunas = array();
				$values = array();

				foreach ($data as $coluna => $value) {
					$colunas[] = mysqli_real_escape_string($conn, trim($coluna));
					$values[] = "'".mysqli_real_escape_string($conn, trim($value))."'";
				}

				$colunas = implode(', ', $colunas);
				$values = implode(', ', $values);

			    $sql = "INSERT INTO $table ($colunas) VALUES ($values)";
			    $result = $conn->query($sql);

			    if($result){
			    	$log['cod'] = 1;
						$log['id'] = $conn->insert_id;
						$log['id_format'] = str_pad($log['id'], 4, "0", STR_PAD_LEFT);
			    } else {
			    	$log['cod'] = 0;
			    	$log['message'] = 'Erro ao cadastrar no banco de dados';
			    	$log['error'] = mysqli_error($conn);
			    }
			    destroy_db($conn);
			} else {
				$log['cod'] = 0;
				$log['message'] = 'Erro ao se conectar com o banco de dados';
			}
		} else {
			$log['cod'] = 0;
			$log['message'] = 'Nenhum dado para inserção';
		}
		return $log;
	}

	function deleteDB($table, $data){
		$log['type'] = 'delete';
		$log['id'] = [];

		if( is_array($data) || $data > 0 ){
			$conn = connect_db();

			if($conn != null){
				if(!is_array($data) ){
					$sql = "UPDATE $table SET ativado = 1 WHERE id = '$data'";

					$logData = $data;

					$result = $conn->query($sql);

					if($result){
						$log['cod'] = 1;
						$log['id'][] = $logData;
					} else {
						$log['cod'] = 0;
						$log['message'] = 'Erro ao deletar no banco de dados';
						$log['error'] = mysqli_error($conn);
					}
				} else {
					$queryDataDelete = selectDB($table, $data);

					foreach ($queryDataDelete['result'] as $key11 => $whereToDelete) {
						$logData = $whereToDelete['id'];
						$sql = "UPDATE $table SET ativado = 1 WHERE id = '$logData'";
	
						$result = $conn->query($sql);
	
						if($result){
							$log['id'][] = $logData;
						}
						$log['cod'] = 1;
					}
				}
			    destroy_db($conn);
			} else {
				$log['cod'] = 0;
				$log['message'] = 'Erro ao se conectar com o banco de dados';
			}
		} else {
			$log['cod'] = 0;
			$log['message'] = 'Nenhum dado para deletar';
		}
		return $log;
	}

	function updateDB($table, $data = [], $where = [], $operator = '='){
		$log['type'] = 'update';

		if(count($data) > 0){
			$conn = connect_db();

			if($conn != null){
				$values = array();
				$wheres = array();

				foreach ($data as $coluna => $value) {
					if(!is_array($value)){
						$values[] = "$coluna = '".mysqli_real_escape_string($conn, trim($value))."'";
					}
				}
				$values = implode(', ', $values);

				foreach ($where as $coluna => $value) {
					if(!is_array($value)){
						if($operator == '=') {
							$wheres[] = "$coluna = '".mysqli_real_escape_string($conn, trim($value))."'";
						} else {
							$wheres[] = "$coluna in (".mysqli_real_escape_string($conn, trim($value)).")";
						}
					}
				}
			    $log['data'] = $values;
			    $log['where'] = $wheres;

				if(count($wheres) > 0){
					$wheres = implode(' AND ', $wheres);
					
					$sql = "UPDATE $table SET $values WHERE $wheres";
				} else {
					$sql = "UPDATE $table SET $values";
				}
			    $result = $conn->query($sql);

			    if($result){
						$log['cod'] = 1;
						
						$createLog['data'] = $data;
						$createLog['where'] = $where;
			    } else {
			    	$log['cod'] = 0;
			    	$log['message'] = 'Erro ao cadastrar no banco de dados';
			    	$log['error'] = mysqli_error($conn);
			    }
			    destroy_db($conn);
			} else {
				$log['cod'] = 0;
				$log['message'] = 'Erro ao se conectar com o banco de dados';
			}
		} else {
			$log['cod'] = 0;
			$log['message'] = 'Nenhum dado para inserção';
		}
		return $log;
	}

	function selectDB($table, $where=array(), $order=array(), $operator = '='){
		$log['type'] = 'select';

		$conn = connect_db();

		if($conn != null){
			$wheres = array();
			if(is_array($where) AND count($where) > 0){
				foreach ($where as $coluna => $value) {
					if(!is_array($value)){

						if($operator == 'in' || $operator == 'IN'){
							$wheres[] = "$coluna in (".mysqli_real_escape_string($conn, trim($value)).")";
						} else if($operator == 'not in' || $operator == 'NOT IN') {
							$wheres[] = "$coluna not in (".mysqli_real_escape_string($conn, trim($value)).")";
						} else if($operator == 'like' || $operator == 'LIKE'){
							$wheres[] = "$coluna LIKE '".mysqli_real_escape_string($conn, trim($value))."'";
						} else {
							$wheres[] = "$coluna = '".mysqli_real_escape_string($conn, trim($value))."'";
						}
					}
				}
			}
		  	$log['where'] = $wheres;
			$wheres = implode(' AND ', $wheres);

			$orders = array();
			if(is_array($order) AND count($order) > 0){
				foreach ($order as $coluna => $value) {
					if(!is_array($value)){
						$value = mysqli_real_escape_string($conn, trim($value));
						$value = ($value == true || $value == 'ASC') ? 'ASC' : 'DESC';
						$orders[] = $coluna.' '.$value; 
					}
				}
			}
		  	$log['order'] = $orders;
			$orders = implode(', ', $orders);



			$sql = "SELECT * FROM $table";
			if($wheres != ''){
				$sql .= ' WHERE '.$wheres;
			}
			if($orders != ''){
				$sql .= ' ORDER BY '.$orders;
			}
		    $result = $conn->query($sql);
		    $log['result'] = array();

		    if($result){
		    	$log['cod'] = 1;
				if(DEBUG){
					$log['query'] = $sql;
					$log['operator'] = $operator;
				}

		    	while ($dado = mysqli_fetch_assoc($result)) {
					if(@$dado['id']){
						$dado['id_format'] = str_pad($dado['id'], 4, "0", STR_PAD_LEFT);
					}
		    	    $log['result'][] = $dado;
		    	}
		    } else {
		    	$log['cod'] = 0;
		    	$log['message'] = 'Erro ao buscar no banco de dados';
		    	$log['error'] = mysqli_error($conn);
		    }
		    destroy_db($conn);
		} else {
			$log['cod'] = 0;
			$log['message'] = 'Erro ao se conectar com o banco de dados';
		}
		return $log;
	}


	function forceDeleteWhereDB($table, $where=array()){
		$log['type'] = 'delete';

		if(count($where) > 0){
			$conn = connect_db();

			if($conn != null){
				$wheres = array();

				foreach ($where as $coluna => $value) {
					if(!is_array($value)){
						$wheres[] = "$coluna = '".mysqli_real_escape_string($conn, trim($value))."'";
					}
				}
			    $log['where'] = $wheres;

				$wheres = implode(' AND ', $wheres);

			    $sql = "DELETE FROM $table WHERE $wheres";
			    $result = $conn->query($sql);

			    if($result){
			    	$log['cod'] = 1;
			    } else {
			    	$log['cod'] = 0;
			    	$log['message'] = 'Erro ao deletar no banco de dados';
			    	$log['error'] = mysqli_error($conn);
			    }
			    destroy_db($conn);
			} else {
				$log['cod'] = 0;
				$log['message'] = 'Erro ao se conectar com o banco de dados';
			}
		} else {
			$log['cod'] = 0;
			$log['message'] = 'Nenhum dado para deletar';
		}
		return $log;
	}


	function forceDeleteDB($table, $id){
		$log['type'] = 'delete';

		if($id > 0){
			$conn = connect_db();

			if($conn != null){
			    $sql = "DELETE FROM $table WHERE id = '$id'";
			    $result = $conn->query($sql);

			    if($result){
			    	$log['cod'] = 1;
			    } else {
			    	$log['cod'] = 0;
			    	$log['message'] = 'Erro ao deletar no banco de dados';
			    	$log['error'] = mysqli_error($conn);
			    }
			    destroy_db($conn);
			} else {
				$log['cod'] = 0;
				$log['message'] = 'Erro ao se conectar com o banco de dados';
			}
		} else {
			$log['cod'] = 0;
			$log['message'] = 'Nenhum dado para deletar';
		}
		return $log;
	}


	function selectQueryDB($sql){
		$log['type'] = 'select';
		if(DEBUG){
			$log['query'] = $sql;
		}

		$conn = connect_db();

		if($conn != null){
		    $result = $conn->query($sql);
		    $log['result'] = array();

		    if($result){
		    	$log['cod'] = 1;

		    	while (@$dado = mysqli_fetch_assoc($result)) {
					if(@$dado['id']){
						$dado['id_format'] = str_pad($dado['id'], 4, "0", STR_PAD_LEFT);
					}

					foreach ($dado as $col => $value) {
						if(strpos($col, '|') !== false) {
							$dataEx = explode('|', $col);
		
							$colName = $dataEx[0];
							$colVal = $dataEx[1];
							$dado[$colName][$colVal] = $value;
		
							unset($dado[$col]);
						}
					}
					
		    	    $log['result'][] = $dado;
		    	}
		    } else {
		    	$log['cod'] = 0;
		    	$log['message'] = 'Erro ao buscar no banco de dados';
		    	$log['error'] = mysqli_error($conn);
		    }
		    destroy_db($conn);
		} else {
			$log['cod'] = 0;
			$log['message'] = 'Erro ao se conectar com o banco de dados';
		}
		return $log;
	}


	function scape_string($data){
		$con = connect_db();

		if(is_array($data)){
			foreach ($data as $key => $value) {
				$data[mb_strtolower($key)] = mysqli_real_escape_string($con, $value);
			}
		} else {
			$data = mysqli_real_escape_string($con, $data);
		}

		destroy_db($con);
		return $data;
	}
?>