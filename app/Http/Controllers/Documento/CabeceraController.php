<?php

namespace imfa\Http\Controllers\Documento;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use imfa\Http\Requests;
use imfa\Http\Controllers\Controller;
use imfa\Modelos\Documento\Cabecera;
use Illuminate\Contracts\Filesystem\Filesystem;
use imfa\Modelos\Documento\CabeceraDetalle;
use imfa\Modelos\Documento\DocumentoAdicional;
use imfa\Modelos\Nomencladores\Cliente;
use imfa\Modelos\Nomencladores\Schema;
use imfa\Modelos\Nomencladores\TipoDocumento;
use imfa\User;



class CabeceraController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $idusuario = Auth::user()->id;
        $userInstancet = User::find( $idusuario );

        if(  strlen(trim($userInstancet->schemaID)) == 0 ){
            $counts = Cabecera::where('identificacionCliente', $userInstancet->username )->count() ;
            $documentosList  = Cabecera::where('identificacionCliente', $userInstancet->username )->get() ;
        }
        else{

            $getschemasid = DB::table('schemas')->where('nombre', $userInstancet->schemaID )->value('id');

            $counts = Cabecera::where('schemas_id', $getschemasid )->count() ;
            $documentosList  = Cabecera::where('schemas_id', $getschemasid  )->get() ;
        }
        return view('documentos/documentosList')->with( ['documentosCabeceraList'=> $documentosList, 'counts' => $counts  ] );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    public function cargaftp(Filesystem $filesystem){

        $idusuario = Auth::user()->id;
        $userInstancet = User::find( $idusuario );

        if(  strlen(trim($userInstancet->schemaID)) > 2 ){

            $filesxmls = $filesystem->files('XML') ;

            foreach( $filesxmls as $fils ){

                $xmlx = $filesystem->get($fils) ;
                $xmls = new \SimpleXMLElement($xmlx);
                $xmlCDATA = new \SimpleXMLElement($xmls->comprobante);

                /*$date_array = explode(" ", (string) $xmls->fechaAutorizacion );
                $datefechaAutorizacion = $date_array[0] ;*/

                //$date = '25/05/2010 11:14:44.000';
                $date = str_replace('/', '-', $xmls->fechaAutorizacion );
                $fechaAutorizacionTmp = date('Y-m-d H:i:s', strtotime($date)) ;


                $dateFemision = str_replace('/', '-', $xmlCDATA->infoFactura->fechaEmision );
                $fechaEmisionTmp = date('Y-m-d H:i:s', strtotime($dateFemision)) ;

               /* $fecha_actual = date('Y-m-d');
                $date =  new \DateTime( '2016/10/28'  );  // (string) $xmls->fechaAutorizacion ;   // new \DateTime( '13/10/2016'  );
                $fromateada = $date->format('Y-m-d H:i:s');*/

                $direccionCliente = '';
                $emailCliente = '';


                foreach ($xmlCDATA->infoAdicional->campoAdicional as $campos) {
                    // echo ' --- campos --- : ' . $campos->attributes()  . '<br>' ;

                    if( $campos->attributes() == 'Direccion' ){
                        $direccionCliente =  $campos ;
                    }

                    if( $campos->attributes() == 'Email' ){
                        $emailCliente =  $campos ;
                    }
                }

                // tipoDocumento
                $tipoDocumentoInstanceId = DB::table('tipo_documentos')->where('codigo',  $xmlCDATA->infoTributaria->codDoc )->value('id');

                // tipoEmision
                $getTipoEmisionsid = DB::table('tipo_emisions')->where('codigo',  $xmlCDATA->infoTributaria->tipoEmision )->value('id');

                // id del esquema al que viene el xml a importado
                $getTipoAmbienteid = DB::table('tipo_ambientes')->where('codigo',  $xmlCDATA->infoTributaria->ambiente )->value('id');

                // buscando el equema al que pertenece ese xml
                $getschemasid = DB::table('schemas')->where('ruc',  $xmlCDATA->infoTributaria->ruc )
                    ->where('tipo_ambiente_id',  $getTipoAmbienteid )->value('id');

                $getTipoIdentificacionsid = DB::table('tipo_identificacions')->where('codigo',  $xmlCDATA->infoFactura->tipoIdentificacionComprador )->value('id');


                // buscando si existe el cliente  --  $xmlCDATA->infoFactura->identificacionComprador     '1756925461'
                $clientefind = DB::table('clientes')->where('identificacion',  $xmlCDATA->infoFactura->identificacionComprador  )
                    ->where('schemas_id',  $getschemasid )->value('id');

                if($clientefind){
                  //  echo ' el cliente no es null: ' . '<br>' ;
                }
                else{
                    $clienteInstance = new Cliente();
                    $clienteInstance->identificacion = $xmlCDATA->infoFactura->identificacionComprador ;
                    $clienteInstance->razonSocial = $xmlCDATA->infoFactura->razonSocialComprador ;
                    $clienteInstance->direccion = $direccionCliente ;
                    $clienteInstance->correoElectronico = $emailCliente ;
                    $clienteInstance->habilitado = true;
                    $clienteInstance->tipo_identificacion_id = $getTipoIdentificacionsid;
                    $clienteInstance->schemas_id = $getschemasid ;
                    $clienteInstance->save();

                    $clientefind = $clienteInstance->id;

                }

                // crear usuario
                $userfind = DB::table('users')->where('username',  $xmlCDATA->infoFactura->identificacionComprador )->value('id');
                if( $userfind ) {
                   // echo ' existe el usuario ' . '<br>' ;
                }
                else{

                    $tempRazonSocial = (string) $xmlCDATA->infoFactura->razonSocialComprador ;
                    $arr1 = explode(" ", $tempRazonSocial );
                    $arraySize = count($arr1);

                    if( $arraySize == 4 ){
                        $nametemp =  $arr1[0] . ' ' . $arr1[1];
                        $apellidoUno = $arr1[2] ;
                        $apellidoDos = $arr1[3] ;
                    }
                    elseif( $arraySize == 3 ){
                        $nametemp =  $arr1[0];
                        $apellidoUno = $arr1[1] ;
                        $apellidoDos = $arr1[2] ;
                    }
                    elseif($arraySize == 2){
                        $nametemp =  $arr1[0];
                        $apellidoUno =  $arr1[1] ;   // $arr1[1] ;  agregar validacion.
                    }
                    else{
                        $nametemp =  $arr1[0];
                        $apellidoUno = '' ;   // $arr1[1] ;  agregar validacion.
                    }

                    User::create([
                        // 'id' => 1,
                        'username' => $xmlCDATA->infoFactura->identificacionComprador ,
                        'password' => bcrypt($xmlCDATA->infoFactura->identificacionComprador),
                        'name' =>  $nametemp ,
                        'primerApellido' => $apellidoUno ,
                        'direccion' => $direccionCliente ,
                        'email' => $emailCliente ,
                    ])->save();
                }

                $cabeceraInstance = new Cabecera();
                $cabeceraInstance->autorizado = true ;
                $cabeceraInstance->autorizo = $xmls->numeroAutorizacion;
                $cabeceraInstance->fechaAutorizo = $fechaAutorizacionTmp   ;     //$fromateada;   $xmls->fechaAutorizacion  $temDate


                // ---- infoTributaria ----
                $cabeceraInstance->tipoAmbienteCodigo = $xmlCDATA->infoTributaria->ambiente ;           // nn
                $cabeceraInstance->tipoEmisionCodigo = $xmlCDATA->infoTributaria->tipoEmision ;
                $cabeceraInstance->razonSocialCliente =  $xmlCDATA->infoTributaria->razonSocial;        // nn
                // ruc  para verificar con schemas
                $cabeceraInstance->schemas_id = $getschemasid;
                $cabeceraInstance->claveAcceso = $xmlCDATA->infoTributaria->claveAcceso  ;
                $cabeceraInstance->tipoDocumentoCodigo = $xmlCDATA->infoTributaria->codDoc   ;          // nn
                $cabeceraInstance->establecimientoCodigo = $xmlCDATA->infoTributaria->estab ;           // nn
                $cabeceraInstance->puntoEmisionCodigo = $xmlCDATA->infoTributaria->ptoEmi ;             // nn
                $cabeceraInstance->comprobante = $xmlCDATA->infoTributaria->secuencial ;                // nn
                $cabeceraInstance->establecimientoDireccion =  $xmlCDATA->infoTributaria->dirMatriz ;


                if( $xmlCDATA->infoTributaria->codDoc == '01' ){
                    //echo 'es documento factura' ;
                }

                $cabeceraInstance->fechaEmision = $fechaEmisionTmp ; //$xmlCDATA->infoFactura->fechaEmision    $temDate ;
                $cabeceraInstance->establecimientoDireccion =  $xmlCDATA->infoFactura->dirEstablecimiento ;
                // obligadoContabilidad
                $cabeceraInstance->tipoIdentificacionClienteCodigo = $xmlCDATA->infoFactura->tipoIdentificacionComprador ;
                $cabeceraInstance->razonSocialCliente = $xmlCDATA->infoFactura->razonSocialComprador ;
                $cabeceraInstance->identificacionCliente = $xmlCDATA->infoFactura->identificacionComprador ;
                $cabeceraInstance->valorBase =  $xmlCDATA->infoFactura->totalSinImpuestos ;
                $cabeceraInstance->descuento =  $xmlCDATA->infoFactura->totalDescuento ;
                $cabeceraInstance->valorTotal =  $xmlCDATA->infoFactura->importeTotal ;
                $cabeceraInstance->tipo_documento_id = $tipoDocumentoInstanceId ;      // nn
                $cabeceraInstance->cliente_id = $clientefind  ;                // nn
                $cabeceraInstance->direccionCliente = $emailCliente ;                      // nn
                // tipo_ambientes
                $cabeceraInstance->tipo_ambiente_id = $getTipoAmbienteid ;                               // nn
                $cabeceraInstance->tipo_emision_id = $getTipoEmisionsid ;                              // nn
                $cabeceraInstance->seleccionado = true;

                $cabeceraInstance->xml = $filesystem->get($fils) ;     // $fils
                $cabeceraInstance->save();

                //Detalles --- Facturas   --- verificar  if( $xmlCDATA->infoTributaria->codDoc == '01' )
                foreach ($xmlCDATA->detalles->detalle as $detail) {
                    $cabeceraDetalleInstance = new CabeceraDetalle();
                    $cabeceraDetalleInstance->codigoProducto = $detail->codigoPrincipal ;
                    $cabeceraDetalleInstance->descripcion = $detail->descripcion ;
                    $cabeceraDetalleInstance->cantidad = $detail->cantidad ;
                    $cabeceraDetalleInstance->precio = $detail->precioUnitario;
                    $cabeceraDetalleInstance->descuento = $detail->descuento;
                    $cabeceraDetalleInstance->valorBase = $detail->precioTotalSinImpuesto;
                    $cabeceraDetalleInstance->cabeceras_id = $cabeceraInstance->id ;
                    $cabeceraDetalleInstance->save();
                }

                foreach ($xmlCDATA->infoAdicional->campoAdicional as $campos) {
                    DocumentoAdicional::create([
                        'nombre' => $campos->attributes() ,
                        'descripcion' => $campos ,
                        'cabeceras_id' => $cabeceraInstance->id ,
                    ])->save();
                }
            }

            try{

                foreach( $filesxmls as $fils ) {

                    Storage::disk('ftpCopy')->put( $fils , $fils );
                    
                    $filesystem->delete($fils) ;
                }
                /*$temp =  $filesxmls[$i] ;   //$fils ;
                $outizq = strstr($temp, "/", false);
                Storage::move( $filesxmls[$i] , 'XMLTMP' . $outizq  );   //Storage::move( $fils , 'XMLTMP' . $outizq  );*/

            }catch (FileNotFoundException $e ){
                echo ' FileNotFoundException ' . $e ;
            }

        }
        else{
           // echo ' este usuario no tiene esquema.... ';
        }

        return redirect('/documentos');
    }



}
