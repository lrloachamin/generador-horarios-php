<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ManejadorMaterias
 *
 * @author abs
 */


include_once '../acceso_datos/Conexion.php';
include_once 'Dia.php';
include_once 'Hora.php';
include_once 'Aula.php';
include_once 'Grupo.php';
include_once 'Materia.php';


abstract class ManejadorMaterias {
    
    public static function getTodasMaterias($cicloPar){
        $materias = array();
        if(!$cicloPar){
            $respuesta = Conexion::consulta("select m.cod_materia,m.nombre_materia,cm.unidades_valorativas,cm.ciclo,c.id_carrera,c.plan_estudio,cm.id_agrupacion,d.id_depar from materias as m join carreras_materias as cm on m.cod_materia=cm.materias_cod_materia join carreras as c on cm.carreras_id_carrera=c.id_carrera and cm.carreras_plan_estudio=c.plan_estudio join departamentos as d on c.id_depar=d.id_depar WHERE cm.ciclo IN (1,3,5,7,9) ORDER BY m.cod_materia");
        } else {
            $respuesta = Conexion::consulta("select m.cod_materia,m.nombre_materia,cm.unidades_valorativas,cm.ciclo,c.id_carrera,c.plan_estudio,cm.id_agrupacion,d.id_depar from materias as m join carreras_materias as cm on m.cod_materia=cm.materias_cod_materia join carreras as c on cm.carreras_id_carrera=c.id_carrera and cm.carreras_plan_estudio=c.plan_estudio join departamentos as d on c.id_depar=d.id_depar WHERE cm.ciclo IN (2,4,6,8,10) ORDER BY m.cod_materia");
        }
        while($fila = pg_fetch_array($respuesta)){
            $materia = new Materia($fila[0],$fila[1],$fila[3],$fila[2],$fila[7],$fila[4],$fila[5],$fila[6],true);
            $materias[] = $materia;
        }
        return $materias;
    }
    
    public static function getNombreMateria($codMateria){
        $respuesta = Conexion::consulta("SELECT nombre_materia FROM materias WHERE cod_materia='$codMateria'");
        while($fila = pg_fetch_array($respuesta)){
            $nombreMateria = $fila[1];
        }
        return $nombreMateria;
    }
    
    public static function obtenerCodMateria($nombre){
        $respuesta = Conexion::consulta("SELECT cod_materia FROM materias WHERE nombre_materia='$nombre'");
        while($fila = pg_fetch_array($respuesta)){
            $codigo = $fila[1];
        }
        return $codigo;
    }
    
    public static function getMateriasDeCarrera($materias, $idCarrera){
        $materiasCarrera = array();
        foreach($materias as $materia){
            if(strcmp($materia->getCodigoCarrera(), $idCarrera)==0){
                $materiasCarrera[] = $materia;
            }
        }
        return $materiasCarrera;
    }
    
    public static function obtenerMateriasDeDepartamento($materias, $idDepar){
        $materiasDepar = array();
        foreach($materias as $materia){
            if(strcmp($materia->getDepartamento(), $idDepar)==0){
                $materiasDepar[] = $materia;
            }
        }        
        $materiasSinRepetir = ManejadorMaterias::quitarMateriasRepetidas($materiasDepar);
        return $materiasSinRepetir;
    }
    
    public static function quitarMateriasRepetidas($materias){
        $resultado=array();
        for ($index = 0; $index < count($materias); $index++) {
            if($index>0){
                if(strcmp($materias[$index-1]->getNombre(),$materias[$index]->getNombre())==0){
                    
                }else{
                    $resultado[] = $materias[$index];
                }
            }else{
                $resultado[] = $materias[$index];
            }
        }
        return $resultado;
    }
    
    public static function getMateriaDeGrupo($id_agrup, $todas_mats){
        $materias = array();
        foreach ($todas_mats as $materia){
            $id = $materia->getIdAgrupacion();
            if(strcmp($id, $id_agrup)==0){
                $materias[] = $materia;
            }
        }
        return $materias;
    }
    
    public static function obtenerHorarioDeMateria($aulas,$cod_materia,$id_depar,$todas_mats,$tabla){
        foreach ($aulas as $aula){
            $dias = $aula->getDias();
            $cuentaDias = count($dias);
            for ($x=0;$x<$cuentaDias;$x++){
                $horas = $dias[$x]->getHoras();
                $cuentaHoras = count($horas);
                for ($y=0;$y<$cuentaHoras;$y++){
                    $grupo = $horas[$y]->getGrupo();
                    $materias = self::getMateriaDeGrupo($grupo->getId_Agrup(), $todas_mats);
                    foreach ($materias as $materia){
                        if($materia->getDepartamento() == $id_depar && strcmp($materia->getCodigo(),$cod_materia)==0){
                            $texto = $materia->getNombre()+" GT: "+$grupo->getId_grupo();
                            $tabla[$y][$x] = $texto;
                            break;
                        } else{
                            $tabla[$y][$x] = '';
                        }
                    }
                }
            }
        }
        return $tabla;
    }
    
    public static function getHorarioMateria($aulas,$cod_materia,$id_depar,$todas_mats){
        $horario = array();
        $arrayGrupo = array();                
        foreach ($aulas as $aula){
            $dias = $aula->getDias();
            $cuentaDias = count($dias);                   
            for ($x=0;$x<$cuentaDias;$x++){   
                $diaAnterior="";
                $dia=$dias[$x];
                $horas = $dia->getHoras();                
                $cuentaHoras = count($horas);                 
                $grupoAnterior="";
                for ($y=0;$y<$cuentaHoras;$y++){                                                                            
                    $hora = $horas[$y];
                    $grupo = $hora->getGrupo();
                    $materias = self::getMateriaDeGrupo($grupo->getId_Agrup(), $todas_mats);
                    foreach ($materias as $materia){
                        if($materia->getDepartamento() == $id_depar && strcmp($materia->getCodigo(),$cod_materia)==0){                                                        
                            if(strcmp($diaAnterior, $dia->getNombre())==0 && strcmp($grupoAnterior, $grupo->getId_grupo())==0){                                
                                $indiceUltimaHora = count($horario)-1;
                                $horario[$indiceUltimaHora]['horaFin'] = $hora->getFin();                                    
                            }else{                                       
                                $arrayGrupo = [
                                "aula" => $aula->getNombre(),
                                "dia" => $dia->getNombre(),
                                "horaInicio" => $hora->getInicio(),                                
                                "horaFin" => $hora->getFin(),
                                "grupo" => $grupo->getId_grupo()                                
                                ];
                                $horario[] = $arrayGrupo;                                        
                                $diaAnterior = $dia->getNombre();                                    
                                $grupoAnterior = $grupo->getId_grupo();
                            }                                                                                                                                     
                        }
                    }
                }            
        }
    }
    return $horario;
    }
}
