<?php

ini_set('max_execution_time', 60);
include_once '../reglas_negocio/Procesador.php';
include_once '../reglas_negocio/Facultad.php';
include_once '../reglas_negocio/Grupo.php';
include_once '../reglas_negocio/Aula.php';
include_once '../reglas_negocio/Dia.php';
include_once '../reglas_negocio/Hora.php';
include_once '../reglas_negocio/ManejadorMaterias.php';
include_once '../reglas_negocio/ManejadorGrupos.php';
include_once '../reglas_negocio/ManejadorReservaciones.php';
include_once '../reglas_negocio/ManejadorAgrupaciones.php';
include_once '../reglas_negocio/ManejadorDepartamentos.php';
include_once '../reglas_negocio/ManejadorAsignacionesDocs.php';
include_once '../reglas_negocio/ManejadorAulas.php';
include_once '../reglas_negocio/ManejadorHoras.php';
include_once '../reglas_negocio/ManejadorDocentes.php';
include_once '../reglas_negocio/ManejadorCargos.php';
session_start();

if(isset($_SESSION['facultad'])){
    $facultad = $_SESSION['facultad'];
}

if(isset($_GET['op'])){
    $op = $_GET['op'];
    if($op == 'generar'){
        $cicloPar = FALSE;
        generarHorario($cicloPar);
    } elseif ($op == 'intercambio') {
        $aula1 = htmlentities($_GET['aula1'], ENT_QUOTES, "UTF-8");
        $aula2 = htmlentities($_GET['aula2'], ENT_QUOTES, "UTF-8");
        $dia1 = htmlentities($_GET['dia1'], ENT_QUOTES, "UTF-8");
        $dia2 = htmlentities($_GET['dia2'], ENT_QUOTES, "UTF-8");
        $desde1 = htmlentities($_GET['desde1'], ENT_QUOTES, "UTF-8");
        $desde2 = htmlentities($_GET['desde2'], ENT_QUOTES, "UTF-8");
        $hasta1 = htmlentities($_GET['hasta1'], ENT_QUOTES, "UTF-8");
        $hasta2 = htmlentities($_GET['hasta2'], ENT_QUOTES, "UTF-8");
        inicioIntercambio($aula1, $dia1, $desde1, $hasta1, $aula2, $dia2, $desde2, $hasta2);
    } elseif ($op == 'confirm') {
        $aula1 = htmlentities($_GET['aula1'], ENT_QUOTES, "UTF-8");
        $aula2 = htmlentities($_GET['aula2'], ENT_QUOTES, "UTF-8");
        $dia1 = htmlentities($_GET['dia1'], ENT_QUOTES, "UTF-8");
        $dia2 = htmlentities($_GET['dia2'], ENT_QUOTES, "UTF-8");
        $desde1 = htmlentities($_GET['desde1'], ENT_QUOTES, "UTF-8");
        $desde2 = htmlentities($_GET['desde2'], ENT_QUOTES, "UTF-8");
        realizarIntercambio($aula1, $dia1, $desde1, $aula2, $dia2, $desde2);
    }
}

function generarHorario($cicloPar){
    $facultad = new Facultad(ManejadorAgrupaciones::getAgrupaciones(),  ManejadorDepartamentos::getDepartamentos(),  ManejadorMaterias::getTodasMaterias($cicloPar), ManejadorCargos::obtenerTodosCargos());
    $facultad->setDocentes(ManejadorDocentes::obtenerTodosDocentes($facultad->getCargos()));
    $facultad->setAsignaciones_docs(ManejadorAsignacionesDocs::obtenerTodasAsignacionesDocs($facultad->getDocentes()));
    ManejadorReservaciones::asignarRerservaciones($facultad);
    $materias = $facultad->getMaterias();
    $procesador = new Procesador();
    $procesador->asignarDatos($facultad);
    for ($i = 0; $i < count($materias); $i++) {
        $agrup = ManejadorAgrupaciones::getAgrupacion($materias[$i]->getIdAgrupacion(), $facultad->agrupaciones);
        if($agrup->getNum_grupos()==$agrup->getNumGruposAsignados()){
            continue;
        }
        $asignaciones = ManejadorAsignacionesDocs::obtenerAsignacionesDeAgrup($agrup->getId(), $facultad->getAsignaciones_docs());
        for ($j = 0; $j < count($asignaciones); $j++) {
            $asignacion = $asignaciones[$j];
            for ($k = 0; $k < $asignacion->getNum_grupos(); $k++) {
                try {
                    $procesador->procesarMateria($materias[$i], $asignacion->getDocente(), $agrup);
                } catch (Exception $exc) {
                    error_log($exc->getMessage(),0); //                    Se produce cuando ya no hay aulas disponibles
                }
                $agrup->setNumGruposAsignados($agrup->getNumGruposAsignados()+1);
            }
        }
    }
    $_SESSION['facultad'] = $facultad;
}

function inicioIntercambio($aula1,$dia1,$desde1,$hasta1,$aula2,$dia2,$desde2,$hasta2){
    global $facultad;
    for($i=$desde1;$i<=$hasta1;$i++){
        $grupos1[] = ManejadorGrupos::getGrupo($facultad->getAulas(), $aula1, $dia1, $i);
    }
    for($i=$desde2;$i<=$hasta2;$i++){
        $grupos2[] = ManejadorGrupos::getGrupo($facultad->getAulas(), $aula2, $dia2, $i);
    }
    if(count($grupos1) > 3 || count($grupos2) > 3 || count($grupos1) != count($grupos2)){
        echo "Eliga bloques iguales de 1, 2 o 3 horas";
    }
    elseif((count($grupos1) == 1 && ManejadorHoras::grupoHuerfano(ManejadorAulas::getAula($facultad->getAulas(), $aula2)->getDia($dia2)->getHoras(), $desde2, $grupos1[0], $desde1)) || (count($grupos2) == 1 && ManejadorHoras::grupoHuerfano(ManejadorAulas::getAula($facultad->getAulas(), $aula1)->getDia($dia1)->getHoras(), $desde1, $grupos2[0], $desde2))){
        echo 'Se permiten 2 horas minimo y 3 maximo al dia de un grupo/materia (horas continuas)';
    }
    elseif (!bloqueFuncional($grupos1) || !bloqueFuncional($grupos2)) {
        echo "No pueden haber 2 horas sin grupo asignado";
    }
    elseif (ManejadorHoras::bloqueCompleto($desde1, $hasta1, ManejadorAulas::getAula($facultad->getAulas(), $aula1)->getDia($dia1)->getHoras(), $grupos1) && ManejadorHoras::bloqueCompleto($desde2, $hasta2, ManejadorAulas::getAula($facultad->getAulas(), $aula2)->getDia($dia2)->getHoras(), $grupos2)){
        $info = infoDeBloque($grupos1[0], $grupos2[0]);
        $grupos[0] = $grupos1;
        $grupos[1] = $grupos2;
        $_SESSION['grupos'] = $grupos;
        if(testIntercambio($info[0]['materia'], $dia2, $desde2, $facultad->getAulas(), $hasta2, $facultad->getMaterias(), $info[0]['docente']) && testIntercambio($info[1]['materia'], $dia1, $desde1, $facultad->getAulas(), $hasta1, $facultad->getMaterias(), $info[1]['docente'])){
            realizarIntercambio($aula1,$dia1,$desde1,$aula2,$dia2,$desde2);
        } else{
            echo ". Desea continuar?";
        }
    } else {
        echo "Bloques incorrectos: Eliga un intervalo de horas asignadas a un mismo grupo/materia; se permiten 2 horas minimo y 3 maximo al dia de un grupo/materia (horas continuas)";
    }
}

function testIntercambio($materia,$dia,$desde,$aulas,$hasta,$materias,$docente){
    if($materia == null && $docente == null){
        return true;
    }
    else if(!ManejadorHoras::chocaMateria($dia, $desde, $aulas, $materia, ($hasta+1)-$desde, $materias) && !ManejadorHoras::chocaGrupoDocente($docente, $desde, $hasta, $aulas, $dia)){
        return true;
    } else{
        return false;
    }
}

function realizarIntercambio($aula1,$dia1,$desde1,$aula2,$dia2,$desde2){
    $grupos = $_SESSION['grupos'];
    global $facultad;
    ManejadorHoras::intercambiar($aula1, $dia1, $desde1, $aula2, $dia2, $desde2, $grupos, $facultad->getAulas());
    unset($_SESSION['grupos']);
    echo 'Exito';
}

function bloqueFuncional($grupos){
    $contador = 0;
    foreach ($grupos as $grupo){
        if(!is_a($grupo, "Grupo")){
            $contador++;
        }
    }
    if($contador == count($grupos)){
        return true;
    }
    elseif($contador >= 2){
        return false;
    } else{
        return true;
    }
}

function infoDeBloque($grupo1,$grupo2){
    global $facultad;
    
    if($grupo1->getId_grupo() == 0){
        $materia1 = null;
        $docente1 = null;
    } else{
        $materia1 = ManejadorMaterias::getMateriaDeGrupo($grupo1->getId_agrup(), $facultad->getMaterias())[0];
        $docente1 = $grupo1->getDocente();
    }
    
    if($grupo2->getId_grupo() == 0){
        $materia2 = null;
        $docente2 = null;
    } else{
        $materia2 = ManejadorMaterias::getMateriaDeGrupo($grupo2->getId_agrup(), $facultad->getMaterias())[0];
        $docente2 = $grupo2->getDocente();
    }
    
    $info[0]['materia'] = $materia1;
    $info[0]['docente'] = $docente1;
    $info[1]['materia'] = $materia2;
    $info[1]['docente'] = $docente2;
    
    return $info;
}
