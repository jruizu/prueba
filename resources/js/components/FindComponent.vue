<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                   

                    <div class="card-body">
                         
                         <div class="row">
                             <div class="col-md-6 col-sm-12">
                                 <div class="form-group">
                                     <label for="name">Nombre de quien deseas buscar</label>
                                     <input type="text" class="form-control" v-model="name" name="name">
                                 </div>
                             </div>
                              <div class="col-md-4 col-sm-12">
                                 <div class="form-group">
                                     <label for="percent">Porcentaje de coincidencia</label>
                                     <input type="number" min="0" class="form-control" v-model="percent" name="percent">
                                 </div>
                             </div>
                             <div class="col-md-2 col-sm-12 mt-1">
                                
                                 <button class="btn btn-success  mt-4" @click="Search()" :disabled="name.length==0 || percent.length==0">Buscar</button>
                             </div>
                         </div>

                        <div class="row">
                            
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header d-flex bg-info text-white">Resultados</div>
                                    
                                    <div class="d-flex col-12 mt-3">
                                            <!-- Componente para exportar Excel-->
                                        <export-excel v-if="results.length>0"
                                            :data="results"
                                            :fields="fields"
                                            name= "directorio.xls">
                                           
                                            <button class="btn btn-success" type="button"> Exportar a Excel</button>

                                        </export-excel>
                                            <button v-if="results.length>0" class="float-rigth btn btn-danger ml-3" type="button" @click="exportPDF"> Exportar a PDF</button>

                                    </div>
                                    
                                    <div class="card-body">
                                        <!--Tabla para mostrar los resultados-->
                                        
                                        <table id="table" class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Personaje Encontrado</th>
                                            <th>Cargo</th>
                                            <th>Porcentaje de coincidencia</th>

                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in results" :key="index">
                                            <td>{{item.personaje.nombre}}</td>
                                            <td>{{item.personaje.tipo_cargo}}</td>
                                            <td>{{item.percent_result}}%</td>
                                        </tr>
                                    </tbody>
                                </table>
                                    </div>
                                   
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
//importamos jsPDF el cual es un plugin para generar pdf del lado del cliente
import {jsPDF} from 'jspdf'
import 'jspdf-autotable'
    export default {

        data:()=>({
            name:'', 
            percent:'',
            name_found:'', 
            percent_found:'',
            results:[], 
            fields:{
                'Personaje' : 'personaje.nombre', 
                'Cargo' : 'personaje.tipo_cargo', 
                'Porcentaje de Coincidencia' : 'percent_result', 
            }
        }),
        mounted(){
            $('#table').DataTable();
        },
        methods:{
            //metodo para realizar la peticion get al servidor
            Search(){ 
                 
                axios.get(`/search_name?name=${this.name}&percent=${this.percent}`).then(response=>{
                    this.results = response.data.data;
                    this.name_found=response.data.nombre_buscado; 
                    this.percent_found=response.data.porcentaje_buscado; 
                    this.$toasted.show(`${response.data.estado_ejecucion}`, { 
                        theme: "toasted-primary", 
                        position: "bottom-right", 
                        duration : 5000, 
                         action : {
                        text : 'Cancel',
                        onClick : (e, toastObject) => {
                            toastObject.goAway(0);
                        }
                    },
                    }); 
                }).catch(error=>{
                    if(error.response.status==422){
                         const  obj_erros = Object.values(error.response.data.errors);
                   
                  obj_erros.forEach((err, index) => {
                        this.$toasted.show(`Error ${err[0]}`, { 
                        theme: "bubble", 
                        position: "bottom-right", 
                        duration : 5000, 
                         action : {
                        text : 'Cancel',
                        onClick : (e, toastObject) => {
                            toastObject.goAway(0);
                        }
                    },
                    }); 
                   }); 
                    };
                   
                }); 
            }, 

            //funcion para exportar PDF
            exportPDF(){
               
                var doc = new jsPDF('p', 'pt', 'letter'); 
                    doc.text(`Resultados encontrados al nombre buscado ${this.name_found} \ncon una coincidencia mayor o igual al ${this.percent_found}% `, 35, 35);
                     doc.autoTable({ margin: { top: 70 },html: '#table' }); 
                     doc.save('Personajes.pdf');
                } 
            
        }
    }
</script>
