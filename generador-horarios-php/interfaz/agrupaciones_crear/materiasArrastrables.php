<div class="col-lg-6">
    <div class="panel panel-success">  
    
        <div class="panel-heading">  
            <p class="center center-block">Busque y arrastre las materias de la agrupación que desea crear</p>
        </div>
        <div class="panel-body">
            <div class="container-fluid">
                <div class="input-group col-sm-4">
                <input type="text" class="form-control" placeholder="Buscar" id="buscar_materia_para_agrupar" name="buscar_materia">
                <div class="input-group-btn">
                    <button class="btn btn-default" type="submit"><span class="glyphicon glyphicon-search"></span></button>
                </div>  
                <div id="mostrar_materias">
                    
                </div>
                </div>
                <br/>
                <div class="table-responsive">
                    <div id="example2_wrapper" class="dataTables_wrapper form-inline" role="grid">
                        <table class="table table-bordered table-condensed table-striped table-hover">
        <!--                <table class="table table-condensed table-striped table-hover ">-->
                        <thead id="cabecera_materias_arrastrables">
<!--                            <tr>                        
                                <th class="text-center">Codigo</th>
                                <th class="text-center">Materia</th>
                                <th class="text-center">Carrera</th>
                                <th class="text-center">Plan Estudio</th> 
                                <th class="text-center">Departamento</th>
                            </tr>-->
                        </thead>                        
                        <tbody id="contenido_materias_arrastrables">
                                                       
                        </tbody>                
                    </table>

                    <div class="row">                
                    <div class="col-xs-12">
                        <div class="dataTables_paginate paging_bootstrap">
                            <div id="paginacion" class="text-center">
                                    <?php //require_once './paginadorMaterias.php'; ?>
                            </div>                        
                        </div>
                    </div>
                    </div>

                    </div>
                </div>
            </div>
        </div>       
    </div>
</div>
 