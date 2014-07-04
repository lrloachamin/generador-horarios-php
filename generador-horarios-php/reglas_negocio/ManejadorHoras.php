<?php

/**
 * Description of ManejadorHoras
 *
 * @author abs
 */
chdir(dirname(__FILE__));
include_once '../acceso_datos/Conexion.php';
include_once 'Hora.php';
include_once 'Dia.php';
include_once 'Aula.php';
include_once 'Docente.php';
include_once 'Grupo.php';
include_once 'Carrera.php';
include_once 'Departamento.php';
include_once 'Materia.php';
include_once 'ManejadorAgrupaciones.php';
include_once 'ManejadorGrupos.php';

class ManejadorHoras {
    
    public static function chocaMateria($nombre_dia, $id_hora, $aulas, $materia, $num_horas){
        $materiasAsignacion = $materia->getAgrupacion()->getMaterias();
        foreach($aulas as $aula){
            $dia = $aula->getDia($nombre_dia);
            for($h=$id_hora; $h<$id_hora+$num_horas; $h++){
                $hora = $dia->getHoras()[$h-1];
                if(!$hora->estaDisponible()){
                    $grupo = $hora->getGrupo();
                    if($materia->getAgrupacion() === $grupo->getAgrup()){
                        echo "Agrupacion ".$materia->getAgrupacion()->getId()." en conflicto en hora $h del dia ".$dia->getNombre()." en aula ".$aula->getNombre();
                            return true;
                        }
                    $materiasHora = $grupo->getAgrup()->getMaterias();
                    foreach ($materiasAsignacion as $materiaDeAgrup) {
                        foreach ($materiasHora as $materiaDeAgrupHora) {
                            if(strcmp($materiaDeAgrupHora->getCarrera()->getCodigo(),$materiaDeAgrup->getCarrera()->getCodigo())==0 && $materiaDeAgrupHora->getCiclo() == $materiaDeAgrup->getCiclo()){
                                //$p = ManejadorAgrupaciones::obtenerNombrePropietario($grupo->getId_Agrup(),$todas_mats);
                                //error_log ("Esta materia $m choca con $p GT $g en hora: $h del dia $nombre_dia en aula: $a",0);
                                echo "Esta materia ".$materiaDeAgrup->getCodigo()." choca con ".$materiaDeAgrupHora->getCodigo()." GT ".$grupo->getId_grupo()." en hora: $h del dia $nombre_dia en aula: ".$aula->getNombre();
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    public static function chocaGrupoDocente($docentes, $desde, $hasta, $aulas, $nombre_dia){
        foreach ($aulas as $aula) {
            $dia = $aula->getDia($nombre_dia);
            for($h=$desde; $h<$hasta; $h++){
                $hora = $dia->getHoras()[$h-1];
                if(!$hora->estaDisponible()){
                    $grupoHora = $hora->getGrupo();
                    foreach ($docentes as $docente){
                        if(in_array($docente, $grupoHora->getDocentes())){
                            //error_log ("El docente: ".$docente->getIdDocente()." atiende ya el grupo: ".$grupoHora->getId_grupo()." a la hora: ".$hora->getIdHora(),0);
                            echo "El docente: ".$docente->getIdDocente()." atiende ya el grupo: ".$grupoHora->getId_grupo()." a la hora: ".$hora->getIdHora();
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }
    
    public static function chocaGrupo($nombre_dia,$desde,$hasta,$aulas,$grupo){
        foreach ($aulas as $aula) {
            $dia = $aula->getDia($nombre_dia);
            for($h=$desde; $h<$hasta; $h++){
                $hora = $dia->getHoras()[$h-1];
                if(!$hora->estaDisponible()){
                    $grupoHora = $hora->getGrupo();
                    if($grupoHora === $grupo){
                        error_log ("Este grupo: ".$grupo->getId_grupo()." de la Agrupacion ".$grupo->getAgrup()->getId()." choca en hora: $h del dia $nombre_dia",0);
                        return true;
                    }
                }
            }
        }
        return false;
    }
    
    /** Devuelve las primeras horas disponibles consecutivas que encuentre
     * 
     * @param Docente $docentes
     * @param Hora $horas = horas del dia en que va a tratar de asignar
     * @param Integer $cantidadHoras = cuantas horas a asignar
     * @param Integer $desde = desde cual hora tratar de asignar
     * @param Integer $hasta = hasta cual hora tratar de asginar
     * @param String $nombre_dia = nombre del dia en el que se quiere asignar, se usa para comprobar choques
     * @param Materia $materia = objeto materia que se esta tratando de asignar
     * @param Aula[] $aulas = todas las aulas de campus, se usan para verificar choques
     * @param boolean $ultimoRecurso
     * @return Hora[] las horas disponibles sin choque en las que se puede asignar el grupoHora; null si no hay ninguna
     */
    public static function buscarHorasDisponibles($docentes,$horas,$cantidadHoras,$desde,$hasta,$nombre_dia,$materia,$aulas,$ultimoRecurso){
        $horasDisponibles = array();
        $resultado = null;
        for($i=$desde;$i<$hasta;$i++){
            $hayBloquesDisponibles=false;
            if($horas[$i]->estaDisponible() && $horas[$i]->getIdHora()<=($hasta+1)-$cantidadHoras){
                $hayBloquesDisponibles = true;
                for($j=$i+1;$j<$i+$cantidadHoras;$j++){
                    $hora = $horas[$j];
                    if($hora->getIdHora()==8){
                        $hayBloquesDisponibles=false;
                        break;
                    }
                    if(!$hora->estaDisponible()){
                        $hayBloquesDisponibles=false;
                        break;
                    }
                }
            }
            if($hayBloquesDisponibles){
                $chocaMateria = self::chocaMateria($nombre_dia, $horas[$i]->getIdHora(), $aulas, $materia, $cantidadHoras);
                $chocaDocente = self::chocaGrupoDocente($docentes, $horas[$i]->getIdHora(), $horas[$i]->getIdHora()+$cantidadHoras, $aulas, $nombre_dia);
                if(!$chocaMateria && !$chocaDocente){
                    for ($j = $i; $j < $i+$cantidadHoras; $j++) {
                        $horasDisponibles[] = $horas[$j];
                    }
                    error_log("ahi va un bloque para asignar",0);
                    $resultado = $horasDisponibles;
                    return $resultado;
                } else{
                    $resultado = "Choque";
                    if(!$ultimoRecurso){
                        return $resultado;
                    }
                }
            }
        }
        return $resultado;
    }
    
    /**
     * 
     * @param Hora[] $horas = horas del dia en que va a tratar de asignar
     * @param Integer $cantidadHoras = cuantas horas a asignar
     * @param Integer $desde = desde cual hora tratar de asignar
     * @param Integer $hasta = hasta cual hora tratar de asginar
     * @param String $nombre_dia
     * @param Aula[] $aulasConCapa
     * @param Grupo $grupo
     * @return horas disponibles en las que se puede asignar el grupoHora aunque hayan choques
     */
    public static function buscarHorasDisponiblesParaChoque($horas,$cantidadHoras,$desde,$hasta,$nombre_dia,$aulasConCapa,$grupo){
        $horasDisponibles = array();
        for($i=$desde;$i<$hasta;$i++){
            $hayBloquesDisponibles=false;
            if($horas[$i]->estaDisponible() && $horas[$i]->getIdHora()<=($hasta+1)-$cantidadHoras){
                $hayBloquesDisponibles = true;
                for($j=$i+1;$j<$i+$cantidadHoras;$j++){
                    $hora = $horas[$j];
                    if($hora->getIdHora()==8){
                        $hayBloquesDisponibles=false;
                        break;
                    }
                    if(!$hora->estaDisponible()){
                        $hayBloquesDisponibles=false;
                        break;
                    }
                }
            }
            if($hayBloquesDisponibles){
                $grupoChocaConElMismo = self::chocaGrupo($nombre_dia, $horas[$i]->getIdHora(), $horas[$i]->getIdHora()+$cantidadHoras, $aulasConCapa, $grupo);
                $chocaDocente = self::chocaGrupoDocente($grupo->getDocentes(), $horas[$i]->getIdHora(), $horas[$i]->getIdHora()+$cantidadHoras, $aulasConCapa, $nombre_dia);
                if(!$grupoChocaConElMismo && !$chocaDocente){
                    for ($j = $i; $j < $i+$cantidadHoras; $j++) {
                        $horasDisponibles[] = $horas[$j];
                    }
                    return $horasDisponibles;
                }
            }
        }
        return null;
    }
    
    /** Metodo para buscar horas en un dia elegido debajo de una materia del mismo nivel
     * 
     * @param Integer $idDocente = para verificar si el docente no tiene asignado un grupo a la misma hora
     * @param Integer $cantidadHoras = numero de horas que se quieren asignar
     * @param Integer $desde = desde cual hora se quiere hacer la asignacion
     * @param Integer $hasta = hasta cual hora tratar de hacer la asignacion
     * @param String $nombre_dia = nombre del dia en que se quiere hacer la asignacion
     * @param Materia $materia = objeto materia de la cual se quiere asignar un grupoHora
     * @param Aula[] $aulasConCapa = array de aulas que tienen capacidad para asignar al grupoHora de la materia
     * @param Aula[] $aulas = array de todas las aulas que tiene el campus, se usa para verificar si hay choques
     * @param Materia[] $todas_mats = array de todas las materias del campus, se usa para comprobar choques
     * @return horas disponibles en las que se puede asignar el grupoHora
     */
    public static function buscarHoras($docentes,$cantidadHoras,$desde,$hasta,$nombre_dia,$materia,$aulasConCapa,$aulas){
        $horasDisponibles = null;
        for($x=0; $x<count($aulasConCapa); $x++){
            error_log ("A probar en aula ".$aulasConCapa[$x]->getNombre(),0);
            $dia = $aulasConCapa[$x]->getDia($nombre_dia);
            $resul = self::buscarHorasDisponibles($docentes,$dia->getHoras(),$cantidadHoras,$desde,$hasta,$nombre_dia,$materia,$aulas,false);
            if($resul != null && $resul == "Choque"){
                break;
            } else if($resul != null && is_array($resul)){
                $horasDisponibles = $resul;
                break;
            }
        }
        return $horasDisponibles;
    }
    
    public static function buscarHorasUltimoRecurso($docentes,$cantidadHoras,$desde,$hasta,$nombre_dia,$materia,$aulasConCapa,$aulas){
        $horasDisponibles = null;
        for($x=0; $x<count($aulasConCapa); $x++){
            $a = $aulasConCapa[$x]->getNombre();
            error_log ("A probar en aula $a Desde: $desde Hasta: $hasta",0);
            $dia = $aulasConCapa[$x]->getDia($nombre_dia);
            $resul = self::buscarHorasDisponibles($docentes,$dia->getHoras(),$cantidadHoras,$desde,$hasta,$nombre_dia,$materia,$aulas,true);
            if($resul != null && $resul != "Choque"){
                $horasDisponibles = $resul;
                break;
            }
        }
        return $horasDisponibles;
    }
    
    public static function buscarHorasConChoque($cantidadHoras,$desde,$hasta,$nombre_dia,$aulasConCapa,$grupo){
        $horasDisponibles = null;
        for($x=0; $x<count($aulasConCapa); $x++){
            $dia = $aulasConCapa[$x]->getDia($nombre_dia);
            if(!self::grupoPresente($desde, $hasta, $nombre_dia, $grupo, $aulasConCapa)){
                $horasDisponibles = self::buscarHorasDisponiblesParaChoque($dia->getHoras(),$cantidadHoras,$desde,$hasta,$nombre_dia,$aulasConCapa,$grupo);
            }
            else{
                break;
            }
            if($horasDisponibles != null){
                break;
            }
        }
        return $horasDisponibles;
    }
    
    /**
     * Para ver si ya se asignó el grupo en un día
     * @param desde
     * @param hasta
     * @param nombre_dia
     * @param grupo
     * @param aulas
     * @return 
     */
    public static function grupoPresente($desde, $hasta, $nombre_dia, $grupo, $aulas){
        foreach ($aulas as $aula) {
            $dia = $aula->getDia($nombre_dia);
            for($i=$desde; $i<$hasta; $i++){
                $hora = $dia->getHoras()[$i];
                if(!$hora->estaDisponible()){
                    $grupoHora = $hora->getGrupo();
                    if($grupoHora === $grupo){
                        return true;
                    }
                }
            }
        }
        return false;
    }
    
    /** Meotodo para relizar busquedas de una materia que pertenece al mismo nivel en el dia elegido
     *
     * @param grupo = grupo que se quiere asignar en dia elegido
     * @param agrupacion
     * @param aulas
     * @param nombreDia
     * @return ultima hora en la que hay una materia del mismo nivel
     */
    public static function getUltimasHoraDeNivel($agrupacion,$aulas,$nombreDia){
        $horasNivel = array();
        $materiasAgrupacion = $agrupacion->getMaterias();
        foreach ($materiasAgrupacion as $materia) {
            foreach ($aulas as $aula) {
                $hora = -1;
                $horas = $aula->getDia($nombreDia)->getHoras();
                for($x=0; $x<count($horas); $x++){
                    if(!$horas[$x]->estaDisponible() && self::mismoDepartamentoAgrupacionMateria($horas[$x]->getGrupo()->getAgrup(), $materia)){
                        $grupoHora = $horas[$x]->getGrupo();
                        $materias = $grupoHora->getAgrup()->getMaterias();
                        foreach ($materias as $materiaHora) {
                            if(strcmp($materiaHora->getCarrera()->getCodigo(),$materia->getCarrera()->getCodigo())==0 && $materiaHora->getCiclo() == $materia->getCiclo()){
                                $hora = $x;
                                break;
                            }
                        }
                    }
                }
                if($hora != -1){
                    $horasNivel[$materia->getCodigo()][] = $hora;
                }
            }
        }
        return $horasNivel;
    }
    
    public static function bloqueCompleto($desde,$hasta,$horas,$grupos){
        if($desde != 1 && $hasta != 15){
            $grupoAnterior = $horas[$desde-2]->getGrupo();
            $grupoPosterior = $horas[$hasta]->getGrupo();
            $grupoEnDesde = $horas[$desde-1]->getGrupo();
            $grupoEnHasta = $horas[$hasta-1]->getGrupo();
        } elseif ($desde == 1) {
            $grupoAnterior = null;
            $grupoPosterior = $horas[$hasta]->getGrupo();
            $grupoEnDesde = $horas[$desde-1]->getGrupo();
            $grupoEnHasta = $horas[$hasta-1]->getGrupo();
        } elseif ($hasta == 15) {
            $grupoAnterior = $horas[$desde-2]->getGrupo();
            $grupoPosterior = null;
            $grupoEnDesde = $horas[$desde-1]->getGrupo();
            $grupoEnHasta = $horas[$hasta-1]->getGrupo();
        }
        if(count($grupos) == 1 && $grupoAnterior == $grupoEnDesde && $grupoEnHasta == $grupoPosterior){
            echo 'incorrecto';
            return false;
        } elseif (count($grupos) > 1){
            if(is_a($grupoAnterior, "Grupo") && $grupoAnterior == $grupoEnDesde && $grupoAnterior->getId_grupo() != 0){
                echo 'incorrecto1';
                return false;
            } elseif(is_a($grupoPosterior, "Grupo") && $grupoPosterior == $grupoEnHasta && $grupoPosterior->getId_grupo() != 0){
                echo 'incorrecto2';
                return false;
            } elseif(!ManejadorGrupos::gruposIgualesEnBloque($grupos)){
                echo 'incorrecto3';
                return false;
            }
        }
        return true;
    }
    
    public static function grupoHuerfano($horas,$desde,$grupo,$origen){
        if($grupo->getAgrup() == null){
            return false;
        }
        if($horas[$desde-1]->getGrupo() == $grupo){
            echo "Grupos iguales en horas origen y destino del intercambio";
            exit(0);
        }
        $contador = 0;
        $i = ($desde-1);
        if($i == 14){
            goto evalUp;
        }
        evalDown:{
            while($i < ($desde+1) && $i < 14){
                if($horas[$i+1]->getGrupo()->getAgrup() != $grupo->getAgrup() || ($horas[$i+1]->getGrupo()->getAgrup() == $grupo->getAgrup() && $horas[$i+1]->getGrupo()->getId_grupo() != $grupo->getId_grupo()) || ($horas[$i+1]->getGrupo() === $grupo && ($i+1) == ($origen-1))){
                    $contador++;
                }
                $i += 1;
            }
            if($contador > 1 && $desde == 1){
                return true;
            } elseif($desde == 1 && $contador <= 1){
                return false;
            } elseif($desde != 1 && $contador <= 1){
                $huerfano1 = false;
            } else {
                $huerfano1 = true;
            }
            $contador = 0;
            $i = ($desde-1);
        }
        evalUp:{
            while($i > 0 && $i > ($desde-3)){
                if($horas[$i-1]->getGrupo()->getAgrup() != $grupo->getAgrup() || ($horas[$i-1]->getGrupo()->getAgrup() == $grupo->getAgrup() && $horas[$i-1]->getGrupo()->getId_grupo() != $grupo->getId_grupo()) || ($horas[$i-1]->getGrupo() === $grupo && ($i-1) == ($origen-1))){
                    $contador++;
                }
                $i -= 1;
            }
            if($contador > 1 && $desde == 15){
                return true;
            } elseif ($contador <= 1 && $desde == 15){ 
                return false;
            } elseif ($contador <= 1 && $desde != 15){
                $huerfano2 = false;
            } else{
                $huerfano2 = true;
            }
        }
        endEval:{
            if($huerfano1 == true && $huerfano2 == true){
                return true;
            } else{
                return false;
            }
        }
    }
    
    public static function intercambiar($aula1,$dia1,$desde1,$aula2,$dia2,$desde2,$grupos,$aulas){
        for ($i = 0; $i < count($grupos[0]); $i++){
            ManejadorAulas::getAula($aulas, $aula2)->getDia($dia2)->getHoras()[$desde2-1]->setGrupo($grupos[0][$i]);
            if(ManejadorGrupos::getGrupoEnHora($aulas, $aula2, $dia2, $desde2)->getId_grupo() == 0){
                ManejadorAulas::getAula($aulas, $aula2)->getDia($dia2)->getHoras()[$desde2-1]->setDisponible(true);
            } else{
                ManejadorAulas::getAula($aulas, $aula2)->getDia($dia2)->getHoras()[$desde2-1]->setDisponible(false);
            }
            $desde2++;
        }
        for($i=0; $i < count($grupos[1]); $i++){
            ManejadorAulas::getAula($aulas, $aula1)->getDia($dia1)->getHoras()[$desde1-1]->setGrupo($grupos[1][$i]);
            if(ManejadorGrupos::getGrupoEnHora($aulas, $aula1, $dia1, $desde1)->getId_grupo() == 0){
                ManejadorAulas::getAula($aulas, $aula1)->getDia($dia1)->getHoras()[$desde1-1]->setDisponible(true);
            } else {
                ManejadorAulas::getAula($aulas, $aula1)->getDia($dia1)->getHoras()[$desde1-1]->setDisponible(false);
            }
            $desde1++;
        }
    }

    /** Metodo para generar nuevas horas clase
     * 
     * @param initManana = hora de inicio del dia clase
     * @param initTarde = hora de final dia clase
     * @return horas generadas en los limites recibidos
     */
    public static function generarHoras($initManana,$initTarde){
        $id=1;
        $horaInicial=$initManana;
        $horaFinal=new DateTime();
        $duracionHora = 60 * 50;
        $horas = array();
        try{
            if($horaInicial->getTimestamp()+($duracionHora*7) > $initTarde->getTimestamp()){
                throw new Exception("Horas se sobrelapan");
            }
            while($id <= 15){
                $horaFinal->setTimestamp($horaInicial->getTimestamp()+($duracionHora*7));
                $hora = new Hora();
                $hora->setId($id);
                $elementosHora = getdate($horaInicial->getTimestamp());
                $hora->setInicio($elementosHora[hours]+":"+$elementosHora[minutes]+":00");
                $elementosHora = getdate($horaFinal->getTimestamp());
                $hora->setFin($elementosHora[hours]+":"+$elementosHora[minutes]+":00");
                $horas[] = $hora;
                $horaInicial->setTimestamp($horaFinal->getTimestamp());
                $id++;
                if($id == 8){
                    $horaInicial->setTimestamp($initTarde->getTimestamp());
                    $horaFinal->setTimestamp($horaInicial->setTimestamp());
                }
            }
        } catch (Exception $ex) {
            echo "Error en generarHoras()";
        }
        return $horas;
    }
    
    /** Metodo para actulizar las horas creadas con generarHoras() en la base de datos
     * 
     * @param horas = horas generadas con generarHoras()
     */
    public static function actualizarHoras($horas){
        foreach ($horas as $hora) {
            $resultado = Conexion::consulta("UPDATE horas_test SET inicio='$hora->getIdHora()',final='$h->getInicio()' WHERE id_hora='$h->getFin()'");
        }
    }
    
    public static function mismoDepartamentoAgrupacionMateria($agrupacion,$materia){
        $materiasAgrup = $agrupacion->getMaterias();
        foreach ($materiasAgrup as $materiaAgrup){
            if($materiaAgrup->getCarrera()->getDepartamento() == $materia->getCarrera()->getDepartamento()){
                return true;
}
        }
        return false;
    }
    
    public static function getIdHoraSegunInicio($inicioHora,$horas){
        foreach ($horas as $hora){
            if(strcmp($hora->getInicio(),$inicioHora)==0){
                return $hora->getIdHora();
            }
        }
        return null;
    }
}
