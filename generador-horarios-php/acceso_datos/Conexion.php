<?php

abstract class Conexion {
	
	public static function conectar(){
		$cadena_de_conexion = "host=localhost port=5432 dbname=horarios_bd user=horarios_bd password=password connect_timeout=5";
		$conexion = pg_connect($cadena_de_conexion) or die ('No se ha podido conectar a la Base de Datos');
		return $conexion;	
	}
	
	public static function desconectar($conexion){
		pg_close($conexion);
	}
	
	public static function consulta($sql_consulta){
                $conexion = Conexion::conectar();
		$respuesta = pg_exec($conexion, $sql_consulta) or die("No se pudo ejecutar la consulta:".$sql_consulta."\n"); 		
                Conexion::desconectar($conexion);
		return $respuesta;
	}
        
        public static function consulta2($sql_consulta){
                $conexion = conexion::conectar();
		$respuesta = pg_exec($conexion, $sql_consulta) or die("No se pudo ejecutar la consulta:".$sql_consulta."\n"); 
		$array = pg_fetch_array($respuesta);
		conexion::desconectar($conexion);
		return $array;
	} 
        
        public static function consultaSinCerrarConexion($conexion,$sql_consulta){
            $respuesta = pg_exec($conexion, $sql_consulta) or die("No se pudo ejecutar la consulta:".$sql_consulta."\n"); 		
            return $respuesta;
        }
}
